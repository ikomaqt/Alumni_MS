<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once 'sqlconnection.php';

// Pagination settings
$items_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Fetch saved files
$savedFiles = [];
$result = $conn->query("SELECT id, file_name, upload_date FROM alumni_data ORDER BY upload_date DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $savedFiles[] = $row;
    }
}

// Get default file (latest upload)
$defaultFile = null;
if (!empty($savedFiles)) {
    $defaultFile = $savedFiles[0];
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only handle JSON upload if Content-Type is application/json
    if (
        isset($_SERVER["CONTENT_TYPE"]) && 
        strpos($_SERVER["CONTENT_TYPE"], "application/json") !== false
    ) {
        $rawData = file_get_contents("php://input");
        $data = json_decode($rawData, true);

        // Only process if action is upload
        if (isset($data['action']) && $data['action'] === 'upload') {
            try {
                // Ensure we're getting JSON data
                if (!isset($_SERVER["CONTENT_TYPE"]) || strpos($_SERVER["CONTENT_TYPE"], "application/json") === false) {
                    throw new Exception("Invalid content type. Expected application/json");
                }

                // Read raw POST data
                $rawData = file_get_contents("php://input");
                if (!$rawData) {
                    throw new Exception('No data received');
                }

                // Decode JSON with error checking
                $data = json_decode($rawData, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Invalid JSON: ' . json_last_error_msg());
                }

                // Validate required fields
                if (!isset($data['fileName']) || !isset($data['sheetData'])) {
                    throw new Exception('Missing required fields');
                }

                // Validate sheet data
                if (empty($data['sheetData']) || !is_array($data['sheetData'])) {
                    throw new Exception('Invalid or empty sheet data');
                }

                $fileName = $conn->real_escape_string($data['fileName']);
                $sheetData = $conn->real_escape_string(json_encode($data['sheetData']));
                
                // Extract academic year from filename (e.g., 2022-2023)
                $year = null;
                if (preg_match('/\b(20\d{2}-20\d{2})\b/', $fileName, $matches)) {
                    $year = $matches[0];
                }
                
                // Save to alumni_data table
                $sql = "INSERT INTO alumni_data (file_name, sheet_data, upload_date) 
                        VALUES ('$fileName', '$sheetData', NOW())";

                if (!$conn->query($sql)) {
                    throw new Exception('Database error: ' . $conn->error);
                }

                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'error' => $e->getMessage()
                ]);
            }
            exit;
        }
    }
}

// Handle file deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $fileId = isset($_POST['file_id']) ? (int)$_POST['file_id'] : 0;
    
    if ($fileId > 0) {
        // First, get the file data to check if we need to update graduation years
        $fileResult = $conn->query("SELECT file_name, sheet_data FROM alumni_data WHERE id = $fileId");
        $fileData = $fileResult->fetch_assoc();
        
        // Delete the file
        $deleteResult = $conn->query("DELETE FROM alumni_data WHERE id = $fileId");
        
        if ($deleteResult) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid file ID']);
        exit;
    }
}

// Handle file renaming
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rename') {
    $fileId = isset($_POST['file_id']) ? (int)$_POST['file_id'] : 0;
    $newName = isset($_POST['new_name']) ? $_POST['new_name'] : '';
    
    if ($fileId > 0 && !empty($newName)) {
        $newName = $conn->real_escape_string($newName);
        
        // Get old file data
        $oldFileResult = $conn->query("SELECT file_name FROM alumni_data WHERE id = $fileId");
        $oldFileData = $oldFileResult->fetch_assoc();
        
        // Update the file name
        $updateResult = $conn->query("UPDATE alumni_data SET file_name = '$newName' WHERE id = $fileId");
        
        if ($updateResult) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid file ID or name']);
        exit;
    }
}

// Handle AJAX request for file data
if (isset($_GET['get_file_data'])) {
    $fileId = (int)$_GET['get_file_data'];
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
    
    $result = $conn->query("SELECT sheet_data FROM alumni_data WHERE id = $fileId");
    if ($row = $result->fetch_assoc()) {
        $allData = json_decode($row['sheet_data'], true);
        
        // If search term exists, filter all data first
        if (!empty($searchTerm)) {
            $searchTerm = strtolower($searchTerm);
            $allData = array_filter($allData, function($row) use ($searchTerm) {
                foreach ($row as $value) {
                    if (stripos(strtolower((string)$value), $searchTerm) !== false) {
                        return true;
                    }
                }
                return false;
            });
            $allData = array_values($allData); // Reset array keys
        }
        
        $total_items = count($allData);
        $total_pages = ceil($total_items / $items_per_page);
        $offset = ($page - 1) * $items_per_page;
        
        // Get paginated data after filtering
        $paginatedData = array_slice($allData, $offset, $items_per_page);
        
        echo json_encode([
            'data' => $paginatedData,
            'pagination' => [
                'total_pages' => $total_pages,
                'current_page' => $page,
                'items_per_page' => $items_per_page,
                'total_items' => $total_items
            ]
        ]);
    }
    exit;
}

// Add AJAX endpoint to get total unique LRNs from all files
if (isset($_GET['get_total_lrn_count'])) {
    $lrnSet = [];
    $result = $conn->query("SELECT sheet_data FROM alumni_data");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $sheetData = json_decode($row['sheet_data'], true);
            if (is_array($sheetData)) {
                foreach ($sheetData as $entry) {
                    foreach ($entry as $key => $value) {
                        if (strtolower(trim($key)) === 'lrn' && !empty($value)) {
                            $lrnSet[trim($value)] = true;
                            break;
                        }
                    }
                }
            }
        }
    }
    echo json_encode(['total_lrn' => count($lrnSet)]);
    exit;
}

// Add AJAX endpoint for filtered count
if (isset($_GET['get_filtered_lrn_count'])) {
    $yearFilter = isset($_GET['year']) ? $_GET['year'] : '';
    $lrnSet = [];
    
    $query = "SELECT file_name, sheet_data FROM alumni_data";
    if (!empty($yearFilter)) {
        // Add year filter to query
        $query .= " WHERE file_name LIKE '%$yearFilter%'";
    }
    
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $sheetData = json_decode($row['sheet_data'], true);
            if (is_array($sheetData)) {
                foreach ($sheetData as $entry) {
                    foreach ($entry as $key => $value) {
                        if (strtolower(trim($key)) === 'lrn' && !empty($value)) {
                            $lrnSet[trim($value)] = true;
                            break;
                        }
                    }
                }
            }
        }
    }
    echo json_encode(['total_lrn' => count($lrnSet)]);
    exit;
}

// Get file statistics
if (isset($_GET['get_file_stats'])) {
    $fileId = (int)$_GET['get_file_stats'];
    
    $result = $conn->query("SELECT sheet_data FROM alumni_data WHERE id = $fileId");
    if ($row = $result->fetch_assoc()) {
        $sheetData = json_decode($row['sheet_data'], true);
        $maleCount = 0;
        $femaleCount = 0;
        $totalRecords = 0;
        $lrnSet = [];
        $headerSkipped = false;

        if (is_array($sheetData) && !empty($sheetData)) {
            foreach ($sheetData as $entry) {
                // Skip header row
                if (!$headerSkipped) {
                    $headerSkipped = true;
                    continue;
                }

                // Check for LRN in the current row
                foreach ($entry as $key => $value) {
                    if (strtolower(trim($key)) === 'lrn' && !empty($value)) {
                        $lrnValue = trim($value);
                        if ($lrnValue !== '') {
                            $totalRecords++;
                            $lrnSet[$lrnValue] = true;
                            
                            // Count gender if available
                            foreach ($entry as $k => $v) {
                                if (in_array(strtolower(trim($k)), ['gender', 'sex'])) {
                                    $gender = strtolower(trim($v));
                                    if (in_array($gender, ['male', 'm'])) {
                                        $maleCount++;
                                    } else if (in_array($gender, ['female', 'f'])) {
                                        $femaleCount++;
                                    }
                                    break;
                                }
                            }
                            break;
                        }
                    }
                }
            }
        }

        echo json_encode([
            'total_records' => $totalRecords,
            'male_count' => $maleCount,
            'female_count' => $femaleCount,
            'lrn_count' => count($lrnSet)
        ]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alumni Data Management</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Add SweetAlert2 CSS and JS -->
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-material-ui@4/material-ui.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #3b82f6;
            --primary-light: #93c5fd;
            --primary-dark: #1d4ed8;
            --secondary: #64748b;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #06b6d4;
            --background: #f1f5f9;
            --surface: #ffffff;
            --text: #0f172a;
            --text-light: #64748b;
            --border: #e2e8f0;
            --border-light: #f1f5f9;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius-sm: 0.25rem;
            --radius: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --transition: all 0.2s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }

        body {
            background-color: var(--background);
            color: var(--text);
            line-height: 1.5;
            font-size: 0.875rem;
            padding: 1.5rem;
        }

        .container {
            margin-left: 80px; /* Increased from 88px to prevent overlap */
            padding: 1.5rem;
            width: calc(100% - 100px); /* Adjust width to account for margin */
        }

        /* Remove the adaptive margin styles */

        .page-header {
            margin-bottom: 1.5rem;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .page-description {
            color: var(--text-light);
            font-size: 0.875rem;
        }

        .admin-layout {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .card {
            background-color: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-title i {
            color: var(--primary);
        }

        .card-body {
            padding: 1.5rem;
        }

        .file-selector {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .file-selector-dropdown {
            flex: 1;
            min-width: 250px;
        }

        .file-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background-color: var(--surface);
            color: var (--text);
            font-size: 0.875rem;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1rem;
        }

        .file-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .upload-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            white-space: nowrap;
        }

        .upload-btn:hover {
            background-color: var(--primary-dark);
        }

        .upload-btn i {
            font-size: 1.25rem;
        }

        .file-input {
            display: none;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background-color: var(--border-light);
            padding: 1.25rem;
            border-radius: var (--radius);
            text-align: center;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var (--text);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.75rem;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .action-bar {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            border-radius: var(--radius);
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: var(--transition);
            border: none;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background-color: #b91c1c;
        }

        .btn-warning {
            background-color: var(--warning);
            color: white;
        }

        .btn-warning:hover {
            background-color: #d97706;
        }

        .btn-success {
            background-color: var(--success);
            color: white;
        }

        .btn-success:hover {
            background-color: #059669;
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--border);
            color: var(--text);
        }

        .btn-outline:hover {
            background-color: var(--border-light);
        }

        .btn i {
            font-size: 1.125rem;
        }

        .search-container {
            position: relative;
            width: 100%;
            max-width: 300px;
        }

        .search-input {
            width: 100%;
            padding: 0.625rem 1rem 0.625rem 2.5rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-size: 0.875rem;
            transition: var(--transition);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1.125rem;
        }

        .table-container {
            overflow-x: auto;
            margin-top: 1rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-light);
        }

        th {
            font-weight: 600;
            color: var(--text);
            background-color: var(--border-light);
        }

        tr:hover {
            background-color: var(--border-light);
        }

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .pagination-controls {
            display: flex;
            gap: 0.5rem;
        }

        .pagination-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: var(--radius);
            background-color: var(--surface);
            border: 1px solid var(--border);
            color: var(--text);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .pagination-btn:hover:not(:disabled) {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination-btn.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .page-info {
            padding: 0.5rem 1rem;
            background-color: var(--border-light);
            border-radius: var(--radius);
            font-size: 0.875rem;
            color: var(--text);
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 1.5rem;
            text-align: center;
        }

        .empty-icon {
            font-size: 3rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        .empty-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var (--text);
            margin-bottom: 0.5rem;
        }

        .empty-description {
            color: var (--text-light);
            max-width: 400px;
            margin-bottom: 1.5rem;
        }

        /* Modal styles */
        .modal-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            backdrop-filter: blur(4px);
        }

        .modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1001;
            width: 100%;
            max-width: 500px;
        }

        .modal-content {
            background-color: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-light);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            transition: var(--transition);
        }

        .modal-close:hover {
            background-color: var(--border-light);
            color: var(--text);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-size: 0.875rem;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .alert {
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .alert-warning {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fbbf24;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #f87171;
        }

        .alert-icon {
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .alert-content {
            flex: 1;
        }

        .alert-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        /* Modern SweetAlert2 custom styles */
        .modern-alert {
            padding: 1.5rem !important;
        }

        .modern-alert .swal2-title {
            padding: 0.75rem 0 !important;
            margin: 0 !important;
        }

        .modern-alert .swal2-html-container {
            margin: 0.75rem 0 !important;
        }

        .modern-alert .swal2-actions {
            margin: 1rem 0 0 0 !important;
            width: 100% !important;
            justify-content: center !important;
            gap: 0.5rem !important;
        }

        .modern-alert .swal2-confirm {
            min-width: 120px !important;
        }

        .upload-progress {
            padding: 2rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    <div class="container">
        <header class="page-header">
            <h1 class="page-title">Alumni Data Management</h1>
            <p class="page-description">Upload, manage, and analyze alumni records</p>
        </header>
        
        <div class="admin-layout">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class='bx bx-file'></i> File Management
                    </h2>
                    <?php if (!empty($savedFiles)): ?>
                    <button class="upload-btn" id="uploadBtn">
                        <i class='bx bx-upload'></i> Upload Excel File
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="file-selector">
                        <div class="file-selector-dropdown">
                            <?php if (!empty($savedFiles)): ?>
                            <select id="fileSelect" class="file-select">
                                <?php foreach ($savedFiles as $file): ?>
                                <option value="<?php echo $file['id']; ?>">
                                    <?php echo htmlspecialchars($file['file_name']); ?> 
                                    (<?php echo date('M d, Y', strtotime($file['upload_date'])); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php endif; ?>
                        </div>
                        <input type="file" id="fileInput" class="file-input" accept=".xlsx, .xls">
                    </div>
                    
                    <div id="fileInfoContainer" class="stats-grid" style="<?php echo empty($savedFiles) ? 'display: none;' : ''; ?>">
                        <!-- File stats will be populated here -->
                    </div>
                    
                    <div class="action-bar" id="fileActions" style="<?php echo empty($savedFiles) ? 'display: none;' : ''; ?>">
                        <button id="renameFileBtn" class="btn btn-warning">
                            <i class='bx bx-edit'></i> Rename
                        </button>
                        <button id="deleteFileBtn" class="btn btn-danger">
                            <i class='bx bx-trash'></i> Delete
                        </button>
                        <button id="exportFileBtn" class="btn btn-success">
                            <i class='bx bx-download'></i> Export
                        </button>
                    </div>
                    
                    <?php if (empty($savedFiles)): ?>
                    <div class="empty-state">
                        <i class='bx bx-upload empty-icon'></i>
                        <h3 class="empty-title">No Files Yet</h3>
                        <p class="empty-description">Upload an Excel file to get started with managing your alumni data.</p>
                        <!-- Use a different ID for the outside button -->
                        <button class="btn btn-primary" id="uploadBtnEmpty">
                            <i class='bx bx-upload'></i> Upload Excel File
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card" id="dataCard" style="<?php echo empty($savedFiles) ? 'display: none;' : ''; ?>">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class='bx bx-table'></i> Alumni Records
                    </h2>
                    <div class="search-container">
                        <i class='bx bx-search search-icon'></i>
                        <input type="text" id="searchInput" class="search-input" placeholder="Search records...">
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-container" id="tableContainer">
                        <!-- Table will be populated here -->
                    </div>
                    
                    <div class="pagination">
                        <div class="pagination-controls">
                            <button id="prevPage" class="pagination-btn">
                                <i class='bx bx-chevron-left'></i>
                            </button>
                            <button id="nextPage" class="pagination-btn">
                                <i class='bx bx-chevron-right'></i>
                            </button>
                        </div>
                        <span id="pageInfo" class="page-info">Page 1 of 1</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal-backdrop" id="modalBackdrop"></div>
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Delete</h3>
                <button class="modal-close" id="closeDeleteModal">
                    <i class='bx bx-x'></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class='bx bx-error-circle alert-icon'></i>
                    <div class="alert-content">
                        <div class="alert-title">Warning</div>
                        <p>This action cannot be undone. All data associated with this file will be permanently removed.</p>
                    </div>
                </div>
                <p>Are you sure you want to delete this file?</p>
                <p id="deleteFileName" style="font-weight: 600; margin-top: 0.5rem;"></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="cancelDelete">Cancel</button>
                <button class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>

    <!-- Rename Modal -->
    <div class="modal" id="renameModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Rename File</h3>
                <button class="modal-close" id="closeRenameModal">
                    <i class='bx bx-x'></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="newFileName" class="form-label">New File Name</label>
                    <input type="text" id="newFileName" class="form-control" placeholder="Enter new file name">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="cancelRename">Cancel</button>
                <button class="btn btn-warning" id="confirmRename">Rename</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.full.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elements
            const fileInput = document.getElementById('fileInput');
            const uploadBtn = document.getElementById('uploadBtn');
            // Add this line to get the outside button
            const uploadBtnEmpty = document.getElementById('uploadBtnEmpty');
            const dataCard = document.getElementById('dataCard');
            const fileInfoContainer = document.getElementById('fileInfoContainer');
            const fileActions = document.getElementById('fileActions');
            const tableContainer = document.getElementById('tableContainer');
            const fileSelect = document.getElementById('fileSelect');
            const prevPageBtn = document.getElementById('prevPage');
            const nextPageBtn = document.getElementById('nextPage');
            const pageInfo = document.getElementById('pageInfo');
            const searchInput = document.getElementById('searchInput');
            const deleteFileBtn = document.getElementById('deleteFileBtn');
            const renameFileBtn = document.getElementById('renameFileBtn');
            const exportFileBtn = document.getElementById('exportFileBtn');
            
            // Modal elements
            const modalBackdrop = document.getElementById('modalBackdrop');
            const deleteModal = document.getElementById('deleteModal');
            const closeDeleteModal = document.getElementById('closeDeleteModal');
            const cancelDelete = document.getElementById('cancelDelete');
            const confirmDelete = document.getElementById('confirmDelete');
            const deleteFileName = document.getElementById('deleteFileName');
            
            const renameModal = document.getElementById('renameModal');
            const closeRenameModal = document.getElementById('closeRenameModal');
            const cancelRename = document.getElementById('cancelRename');
            const confirmRename = document.getElementById('confirmRename');
            const newFileName = document.getElementById('newFileName');
            
            // Store current file data
            let currentFileId = null;
            let currentFileName = '';
            let currentData = []; // Store current table data

            // Initialize with default file if available
            if (fileSelect && fileSelect.options.length > 0) {
                const defaultFileId = fileSelect.options[0].value;
                fileSelect.selectedIndex = 0;
                currentFileId = defaultFileId;
                currentFileName = fileSelect.options[0].text;
                loadFileData(defaultFileId, 1);
                fileInfoContainer.style.display = 'grid';
                fileActions.style.display = 'flex';
                dataCard.style.display = 'block';
            } else {
                fileInfoContainer.style.display = 'none';
                fileActions.style.display = 'none';
                dataCard.style.display = 'none';
            }
            
            // Handle file selection via button (inside card)
            if (uploadBtn) {
                uploadBtn.addEventListener('click', function() {
                    fileInput.click();
                });
            }
            // Handle file selection via button (outside card/empty state)
            if (uploadBtnEmpty) {
                uploadBtnEmpty.addEventListener('click', function() {
                    fileInput.click();
                });
            }
            
            // Handle file selection
            fileInput.addEventListener('change', function(e) {
                handleFiles(e.target.files);
            });
            
            // File selection change
            if (fileSelect) {
                fileSelect.addEventListener('change', function() {
                    const fileId = this.value;
                    if (fileId) {
                        currentFileId = fileId;
                        currentFileName = this.options[this.selectedIndex].text;
                        loadFileData(fileId, 1);
                        fileInfoContainer.style.display = 'grid';
                        fileActions.style.display = 'flex';
                        dataCard.style.display = 'block';
                    } else {
                        fileInfoContainer.style.display = 'none';
                        fileActions.style.display = 'none';
                        dataCard.style.display = 'none';
                    }
                });
            }
            
            // Helper function for modern alerts
            function showAlert(options) {
                return Swal.fire({
                    showClass: {
                        popup: 'animate__animated animate__fadeInDown'
                    },
                    hideClass: {
                        popup: 'animate__animated animate__fadeOutUp'
                    },
                    customClass: {
                        popup: 'modern-alert',
                        title: 'modern-alert-title',
                        htmlContainer: 'modern-alert-content',
                        confirmButton: 'modern-alert-confirm btn btn-primary',
                        cancelButton: 'modern-alert-cancel btn btn-outline',
                        actions: 'modern-alert-actions'
                    },
                    showConfirmButton: true,
                    confirmButtonText: 'Okay',
                    buttonsStyling: false,
                    ...options
                });
            }

            // Process the uploaded files
            function handleFiles(files) {
                if (files.length === 0) return;
                
                const file = files[0];
                
                if (!file.name.match(/\.(xlsx|xls)$/i)) {
                    showAlert({
                        icon: 'error',
                        title: 'Invalid File Type',
                        text: 'Please upload only Excel files (.xlsx or .xls)',
                        confirmButtonText: 'Try Again'
                    });
                    return;
                }

                showAlert({
                    title: 'Uploading File...',
                    html: `
                        <div class="upload-progress">
                            <i class='bx bx-loader-alt bx-spin' style="font-size: 3rem; color: var(--primary)"></i>
                            <p style="margin-top: 1rem; color: var(--text-light)">Please wait while we process your file</p>
                        </div>
                    `,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false
                });

                const reader = new FileReader();
                reader.onload = async function(e) {
                    try {
                        const data = new Uint8Array(e.target.result);
                        const workbook = XLSX.read(data, { type: 'array' });
                        
                        if (!workbook.SheetNames.length) {
                            throw new Error('Excel file is empty');
                        }

                        // Get the first sheet data
                        const firstSheetName = workbook.SheetNames[0];
                        const worksheet = workbook.Sheets[firstSheetName];
            
                        // Convert to JSON with headers
                        const sheetData = XLSX.utils.sheet_to_json(worksheet, {
                            raw: true,
                            defval: ''  // Default empty cells to empty string
                        });

                        if (!sheetData.length) {
                            throw new Error('No data found in Excel file');
                        }

                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                action: 'upload',
                                fileName: file.name,
                                sheetData: sheetData
                            })
                        });

                        if (!response.ok) {
                            const errorData = await response.json();
                            throw new Error(errorData.error || 'Upload failed');
                        }

                        const result = await response.json();
            
                        // Update success message
                        if (result.success) {
                            showAlert({
                                icon: 'success',
                                title: 'Success!',
                                text: 'File has been uploaded successfully',
                                confirmButtonText: 'Continue'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            throw new Error(result.error || 'Failed to save file');
                        }

                    } catch (error) {
                        console.error('Error processing file:', error);
                        // Update error message
                        showAlert({
                            icon: 'error',
                            title: 'Upload Failed',
                            text: error.message,
                            confirmButtonText: 'Try Again'
                        });
                    }
                };
                
                reader.onerror = function() {
                    showAlert({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Error reading file: ' + file.name,
                        confirmButtonText: 'Try Again'
                    });
                };
                
                reader.readAsArrayBuffer(file);
            }
            
            // Pagination variables
            let currentPage = 1;
            let totalPages = 1;

            function loadFileData(fileId, page = 1, searchTerm = '') {
                if (!fileId) return;

                currentFileId = fileId;
                tableContainer.innerHTML = '<div class="empty-state"><i class="bx bx-loader-alt bx-spin" style="font-size: 2rem;"></i><p>Loading data...</p></div>';
                
                const url = new URL(window.location.href);
                url.searchParams.set('get_file_data', fileId);
                url.searchParams.set('page', page);
                if (searchTerm) {
                    url.searchParams.set('search', searchTerm);
                }

                // Load file stats
                loadFileStats(fileId);

                fetch(url)
                    .then(response => response.json())
                    .then(response => {
                        const { data, pagination } = response;
                        currentData = data;
                        currentPage = pagination.current_page;
                        totalPages = pagination.total_pages;
                        updatePaginationControls();
                        displayTableData(data);

                        // Update total alumni count in admin_dash.php if it exists
                        if (window.opener && !window.opener.closed && typeof window.opener.updateTotalAlumniCount === 'function') {
                            fetchAllLrnCount().then(totalLrnCount => {
                                window.opener.updateTotalAlumniCount(totalLrnCount);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error loading saved file:', error);
                        tableContainer.innerHTML = '<div class="empty-state"><i class="bx bx-error-circle" style="font-size: 2rem; color: var(--danger);"></i><p>Error loading data</p></div>';
                    });
            }

            // Load file statistics
            function loadFileStats(fileId) {
                const url = new URL(window.location.href);
                url.searchParams.set('get_file_stats', fileId);
                
                fileInfoContainer.innerHTML = '<div class="empty-state"><i class="bx bx-loader-alt bx-spin" style="font-size: 2rem;"></i><p>Loading stats...</p></div>';
                
                fetch(url)
                    .then(response => response.json())
                    .then(stats => {
                        // Display file stats including LRN Count
                        fileInfoContainer.innerHTML = `
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class='bx bx-file'></i>
                                </div>
                                <div class="stat-value">${stats.total_records}</div>
                                <div class="stat-label">Total Records</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class='bx bx-male'></i>
                                </div>
                                <div class="stat-value">${stats.male_count}</div>
                                <div class="stat-label">Male</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class='bx bx-female'></i>
                                </div>
                                <div class="stat-value">${stats.female_count}</div>
                                <div class="stat-label">Female</div>
                            </div>
                        `;
                        // Update Total Alumni in admin_dash.php if opener supports it
                        if (window.opener && !window.opener.closed && typeof window.opener.updateTotalAlumniCount === 'function') {
                            window.opener.updateTotalAlumniCount(stats.lrn_count);
                        }
                    })
                    .catch(error => {
                        console.error('Error loading file stats:', error);
                        fileInfoContainer.innerHTML = '<div class="empty-state"><i class="bx bx-error-circle" style="font-size: 2rem; color: var(--danger);"></i><p>Error loading statistics</p></div>';
                    });
            }

            // Helper to fetch all LRNs from all files and count unique (calls backend for accuracy)
            async function fetchAllLrnCount() {
                const url = new URL(window.location.href);
                url.search = ''; // clear query params
                url.searchParams.set('get_total_lrn_count', '1');
                const resp = await fetch(url);
                const result = await resp.json();
                return result.total_lrn || 0;
            }

            function displayTableData(data) {
                if (!data || data.length === 0) {
                    tableContainer.innerHTML = `
                        <div class="empty-state">
                            <i class='bx bx-search empty-icon'></i>
                            <h3 class="empty-title">No Records Found</h3>
                            <p class="empty-description">Try adjusting your search or upload a new file.</p>
                        </div>
                    `;
                    return;
                }
                
                // Create table from the data
                const table = document.createElement('table');
                table.id = 'data-table';
                
                // Get all possible headers from all rows
                const headers = data.reduce((acc, row) => {
                    Object.keys(row).forEach(key => {
                        if (!acc.includes(key)) {
                            acc.push(key);
                        }
                    });
                    return acc;
                }, []);
                
                // Create header row
                const thead = table.createTHead();
                const headerRow = thead.insertRow();
                headers.forEach(header => {
                    const th = document.createElement('th');
                    th.textContent = header;
                    headerRow.appendChild(th);
                });

                // Create data rows ensuring all columns are present
                const tbody = table.createTBody();
                data.forEach(row => {
                    const tr = tbody.insertRow();
                    headers.forEach(header => {
                        const td = tr.insertCell();
                        td.textContent = row[header] || '';
                    });
                });

                tableContainer.innerHTML = '';
                tableContainer.appendChild(table);
            }

            function updatePaginationControls() {
                pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
                prevPageBtn.disabled = currentPage <= 1;
                nextPageBtn.disabled = currentPage >= totalPages;
            }

            // Pagination controls
            prevPageBtn.addEventListener('click', function() {
                if (currentPage > 1) {
                    loadFileData(currentFileId, currentPage - 1, searchInput.value.trim());
                }
            });

            nextPageBtn.addEventListener('click', function() {
                if (currentPage < totalPages) {
                    loadFileData(currentFileId, currentPage + 1, searchInput.value.trim());
                }
            });

            // Add search functionality
            let searchTimeout;
            searchInput.addEventListener('input', function(e) {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const searchTerm = e.target.value.trim();
                    currentPage = 1; // Reset to first page when searching
                    loadFileData(currentFileId, currentPage, searchTerm);
                }, 300); // Debounce search for better performance
            });

            // Delete file functionality
            deleteFileBtn.addEventListener('click', function() {
                if (!currentFileId) return;
                
                // Show delete confirmation modal
                deleteFileName.textContent = currentFileName;
                modalBackdrop.style.display = 'block';
                deleteModal.style.display = 'block';
                deleteModal.classList.add('fade-in');
            });

            // Delete modal controls
            closeDeleteModal.addEventListener('click', closeDeleteModalFunc);
            cancelDelete.addEventListener('click', closeDeleteModalFunc);
            
            function closeDeleteModalFunc() {
                modalBackdrop.style.display = 'none';
                deleteModal.style.display = 'none';
            }

            confirmDelete.addEventListener('click', function() {
                if (!currentFileId) return;
                
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('file_id', currentFileId);
                
                confirmDelete.disabled = true;
                confirmDelete.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Deleting...';
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        window.location.reload();
                    } else {
                        throw new Error(result.error || 'Failed to delete file');
                    }
                })
                .catch(error => {
                    console.error('Error deleting file:', error);
                    showAlert({
                        icon: 'error',
                        title: 'Delete Failed',
                        text: error.message
                    });
                    confirmDelete.disabled = false;
                    confirmDelete.innerHTML = 'Delete';
                })
                .finally(() => {
                    closeDeleteModalFunc();
                });
            });

            // Export file functionality
            exportFileBtn.addEventListener('click', function() {
                if (!currentFileId || !currentData.length) return;
                
                // Create a new workbook
                const wb = XLSX.utils.book_new();
                const ws = XLSX.utils.json_to_sheet(currentData);
                XLSX.utils.book_append_sheet(wb, ws, "Alumni Data");
                
                // Generate Excel file
                XLSX.writeFile(wb, `${currentFileName.replace(/\.[^/.]+$/, '')}_export.xlsx`);
            });

            // Close modals when clicking outside
            modalBackdrop.addEventListener('click', function() {
                closeDeleteModalFunc();
                closeRenameModalFunc();
            });
        });
    </script>
</body>
</html>