<?php
// Remove the session_start() check since it's handled in admin_navbar.php
include 'sqlconnection.php'; 
include 'admin_navbar.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

// Prevent caching to protect sensitive data
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include 'sqlconnection.php'; 
include 'admin_navbar.php';

// Pagination settings
$items_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Function to safely handle SQL queries with parameters
function executeQuery($conn, $query, $params = []) {
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        error_log("Query preparation failed: " . $conn->error);
        return false;
    }

    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        error_log("Query execution failed: " . $stmt->error);
        return false;
    }

    return $stmt->get_result();
}

function getFileIcon($fileType) {
    switch (strtolower($fileType)) {
        case 'pdf': return 'fas fa-file-pdf';
        case 'doc': case 'docx': return 'fas fa-file-word';
        case 'xls': case 'xlsx': return 'fas fa-file-excel';
        case 'jpg': case 'jpeg': case 'png': case 'gif': return 'fas fa-file-image';
        default: return 'fas fa-file';
    }
}

function getGraduationYears($conn) {
    $years = [];
    $query = "SELECT file_name FROM alumni_data";
    $result = executeQuery($conn, $query);
    
    while ($row = $result->fetch_assoc()) {
        if (preg_match('/\b(20\d{2}-20\d{2})\b/', $row['file_name'], $matches)) {
            $years[] = $matches[0];
        }
    }
    
    $years = array_unique($years);
    $years = array_filter($years);
    rsort($years);
    return $years;
}

// Update the getStatusClass function to return appropriate classes for active and deactivated statuses
function getStatusClass($status) {
    switch (strtolower($status)) {
        case 'active': return 'status-active'; // Green color for active
        case 'deactivated': return 'status-deactivated'; // Red color for deactivated
        case 'employed': return 'status-employed';
        case 'unemployed': return 'status-unemployed';
        case 'student': return 'status-student';
        default: return '';
    }
}

function countFilesByGraduationYear($conn, $yearFilter = 'all') {
    $query = "SELECT sheet_data, file_name FROM alumni_data";
    $result = executeQuery($conn, $query);
    
    $totalCount = 0;
    
    while ($row = $result->fetch_assoc()) {
        if ($yearFilter !== 'all' && strpos($row['file_name'], $yearFilter) === false) {
            continue;
        }
        
        $sheetData = json_decode($row['sheet_data'], true);
        if (!is_array($sheetData)) continue;
        
        array_shift($sheetData); 
        $totalCount += count($sheetData);
        
        if ($yearFilter !== 'all') {
            break;
        }
    }
    
    return $totalCount;
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'get_user_details' && isset($_GET['user_id'])) {
        $user_id = $_GET['user_id'];
        $userQuery = "SELECT * FROM users WHERE lrn = ?";
        $userResult = executeQuery($conn, $userQuery, [$user_id]);

        if ($userResult->num_rows === 0) {
            echo "<p class='info-value'>User not found</p>";
            exit;
        }

        $user = $userResult->fetch_assoc();

        // Profile Section
        echo "<div class='profile-section'>";
        if(!empty($user['profile_img'])) {
            echo "<img src='" . htmlspecialchars($user['profile_img']) . "' class='profile-image' alt='Profile Image'>";
        } else {
            echo "<div class='profile-placeholder'><i class='fas fa-user'></i></div>";
        }
        echo "<h3 class='profile-name'>" . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "</h3>";
        echo "</div>";

        // Basic Information
        echo "<div class='detail-set'>
                <div class='detail-set-title'>Basic Information</div>
                <div class='detail-set-content'>
                    <div class='detail-item'><span class='detail-label'>LRN:</span><span class='detail-value'>" . htmlspecialchars($user['lrn']) . "</span></div>
                    <div class='detail-item'><span class='detail-label'>First Name:</span><span class='detail-value'>" . htmlspecialchars($user['first_name']) . "</span></div>
                    <div class='detail-item'><span class='detail-label'>Middle Name:</span><span class='detail-value'>" . htmlspecialchars($user['middle_name']) . "</span></div>
                    <div class='detail-item'><span class='detail-label'>Last Name:</span><span class='detail-value'>" . htmlspecialchars($user['last_name']) . "</span></div>
                    <div class='detail-item'><span class='detail-label'>Gender:</span><span class='detail-value'>" . htmlspecialchars($user['gender']) . "</span></div>
                    <div class='detail-item'><span class='detail-label'>Birthdate:</span><span class='detail-value'>" . htmlspecialchars($user['birthdate']) . "</span></div>
                </div>
              </div>";


        // Contact Information
        echo "<div class='detail-set'>
                <div class='detail-set-title'>Contact Information</div>
                <div class='detail-set-content'>
                    <div class='detail-item'><span class='detail-label'>Email:</span><span class='detail-value'>" . htmlspecialchars($user['email']) . "</span></div>
                    <div class='detail-item'><span class='detail-label'>Phone Number:</span><span class='detail-value'>" . htmlspecialchars($user['phone_number']) . "</span></div>
                    <div class='detail-item'><span class='detail-label'>Address:</span><span class='detail-value'>" . htmlspecialchars($user['address']) . "</span></div>
                </div>
              </div>";

        // Educational Information
        echo "<div class='detail-set'>
                <div class='detail-set-title'>Educational Information</div>
                <div class='detail-set-content'>
                    <div class='detail-item'><span class='detail-label'>Graduation Year:</span><span class='detail-value'>" . htmlspecialchars($user['graduation_year']) . "</span></div>
                </div>
              </div>";

        // Professional Information
        echo "<div class='detail-set'>
                <div class='detail-set-title'>Professional Information</div>
                <div class='detail-set-content'>
                    <div class='detail-item'><span class='detail-label'>Skills:</span><span class='detail-value'>" . nl2br(htmlspecialchars($user['skills'])) . "</span></div>
                    <div class='detail-item'><span class='detail-label'>Work Experience:</span><span class='detail-value'>" . nl2br(htmlspecialchars($user['work_experience'])) . "</span></div>
                    <div class='detail-item'><span class='detail-label'>Employment Status:</span><span class='detail-value'><span class='status-badge " . getStatusClass($user['employment_status']) . "'>" . htmlspecialchars($user['employment_status']) . "</span></span></div>
                </div>
              </div>";

        // Account Information
        echo "<div class='detail-set'>
                <div class='detail-set-title'>Account Information</div>
                <div class='detail-set-content'>
                    <div class='detail-item'><span class='detail-label'>Username:</span><span class='detail-value'>" . htmlspecialchars($user['username']) . "</span></div>
                    <div class='detail-item'><span class='detail-label'>Role:</span><span class='detail-value'>" . htmlspecialchars($user['role']) . "</span></div>
                    <div class='detail-item'><span class='detail-label'>Created At:</span><span class='detail-value'>" . htmlspecialchars($user['created_at']) . "</span></div>
                    <div class='detail-item'><span class='detail-label'>Last Updated:</span><span class='detail-value'>" . htmlspecialchars($user['last_updated']) . "</span></div>
                    <div class='detail-item'><span class='detail-label'>Account Status:</span><span class='detail-value'><span class='status-badge " . getStatusClass($user['acc_status']) . "'>" . htmlspecialchars($user['acc_status']) . "</span></span></div>
                </div>
              </div>";
        
        // Resume Information
        if (!empty($user['resume_file'])) {
            echo "<div class='detail-set'>
                    <div class='detail-set-title'>Files</div>
                    <div class='detail-set-content'>
                        <div class='detail-item'>
                            <span class='detail-label'>Resume:</span>
                            <span class='detail-value'>
                                <a href='view_resume.php?file=" . urlencode($user['resume_file']) . "' target='_blank' style='color: #007bff; text-decoration: none;' onmouseover=\"this.style.textDecoration='underline';\" onmouseout=\"this.style.textDecoration='none';\">
                                    <i class='fas fa-file-pdf'></i> View Resume
                                </a>
                            </span>
                        </div>
                    </div>
                  </div>";
        } else {
            echo "<div class='detail-set'>
                    <div class='detail-set-title'>Files</div>
                    <div class='detail-set-content'>
                        <div class='detail-item'>
                            <span class='detail-label'>Resume:</span>
                            <span class='detail-value'>No resume uploaded</span>
                        </div>
                    </div>
                  </div>";
        }
        exit;
    }
    
    if ($_GET['action'] === 'get_attachments' && isset($_GET['user_id'])) {
        $user_id = $_GET['user_id'];
        $attachmentsQuery = "SELECT * FROM users WHERE user_id = ?";
        $attachmentsResult = executeQuery($conn, $attachmentsQuery, [$user_id]);

        if ($attachmentsResult->num_rows === 0) {
            echo "<p>No attachments found for this user.</p>";
            exit;
        }

        echo "<ul class='attachment-list'>";
        while ($attachment = $attachmentsResult->fetch_assoc()) {
            $fileIcon = getFileIcon($attachment['file_type']);
            $isResume = strtolower($attachment['file_type']) === 'pdf' && strpos(strtolower($attachment['file_name']), 'resume') !== false;

            echo "<li class='attachment-item'>";
            echo "<i class='" . $fileIcon . "'></i>";
            echo "<a href='uploads/" . htmlspecialchars($attachment['file_name']) . "' target='_blank'>" . htmlspecialchars($attachment['file_name']) . "</a>";

            if ($isResume) {
                echo " <span class='badge badge-primary'>Resume</span>";
            }

            echo "</li>";
        }
        echo "</ul>";
        exit;
    }
    
    if ($_GET['action'] === 'filter_alumni') {
        $employmentFilter = isset($_GET['employmentFilter']) ? $_GET['employmentFilter'] : 'all';
        $yearFilter = isset($_GET['yearFilter']) ? $_GET['yearFilter'] : 'all';
        $searchQuery = isset($_GET['searchQuery']) ? $_GET['searchQuery'] : '';

        $alumniQuery = "SELECT lrn, CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) as name, 
                        address, graduation_year, employment_status, acc_status FROM users WHERE role = 'User' AND 1=1";
        $alumniParams = [];

        if ($yearFilter !== 'all') {
            $alumniQuery .= " AND graduation_year = ?";
            $alumniParams[] = $yearFilter;
        }

        if ($employmentFilter !== 'all') {
            $alumniQuery .= " AND employment_status = ?";
            $alumniParams[] = $employmentFilter;
        }

        if (!empty($searchQuery)) {
            $alumniQuery .= " AND (first_name LIKE ? OR last_name LIKE ? OR CONCAT(first_name, ' ', last_name) LIKE ?)";
            $alumniParams[] = "%$searchQuery%";
            $alumniParams[] = "%$searchQuery%";
            $alumniParams[] = "%$searchQuery%";
        }

        // Add pagination
        $alumniQuery .= " LIMIT ? OFFSET ?";
        $alumniParams[] = $items_per_page;
        $alumniParams[] = $offset;

        $result = executeQuery($conn, $alumniQuery, $alumniParams);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $statusClass = getStatusClass($row["employment_status"]);

                echo "<tr>";
                echo "<td>" . htmlspecialchars($row["lrn"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["name"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["address"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["graduation_year"]) . "</td>";
                echo "<td><span class='status-badge " . $statusClass . "'>" . htmlspecialchars($row["employment_status"]) . "</span></td>";
                echo "<td>
    <div class='action-buttons'>
        <button class='btn btn-primary' onclick='viewAlumniDetails(\"" . htmlspecialchars($row["lrn"]) . "\")'><i class='fas fa-eye'></i> View</button>";

// Corrected button logic - check acc_status instead of just showing deactivate for all
if ($row["acc_status"] === "Active") {
    echo "<button class='btn btn-danger deactivate-btn' data-user-id='" . htmlspecialchars($row["lrn"]) . "' data-user-name='" . htmlspecialchars($row["name"]) . "' data-action='deactivate'><i class='fas fa-ban'></i> Deactivate</button>";
} else {
    echo "<button class='btn btn-success reactivate-btn' data-user-id='" . htmlspecialchars($row["lrn"]) . "' data-user-name='" . htmlspecialchars($row["name"]) . "' data-action='reactivate'><i class='fas fa-check'></i> Reactivate</button>";
}

echo "</div></td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='6' style='text-align: center; padding: 1.5rem;'>No alumni found</td></tr>";
        }
        exit;
    }
    
    if ($_GET['action'] === 'get_count') {
        $yearFilter = isset($_GET['yearFilter']) ? $_GET['yearFilter'] : 'all';
        echo countFilesByGraduationYear($conn, $yearFilter);
        exit;
    }

    if ($_GET['action'] === 'deactivate_user' && isset($_GET['user_id'])) {
        $user_id = $_GET['user_id'];
        $query = "UPDATE users SET acc_status = 'Deactivated' WHERE lrn = ?";
        $result = executeQuery($conn, $query, [$user_id]);

        if ($result === false) {
            error_log("Failed to deactivate user with LRN: $user_id");
            echo json_encode(["status" => "error", "message" => "Failed to deactivate user."]);
            exit;
        }

        // Log the deactivation
        $admin_id = $_SESSION['user_id'] ?? null;
        if ($admin_id) {
            $log_query = "INSERT INTO user_logs (user_id, action, action_by, action_date) VALUES (?, 'Account Deactivated', ?, NOW())";
            $log_result = executeQuery($conn, $log_query, [$user_id, $admin_id]);

            if ($log_result === false) {
                error_log("Failed to log deactivation for user with LRN: $user_id by admin: $admin_id");
            }
        } else {
            error_log("Admin ID is missing. Unable to log deactivation for user with LRN: $user_id");
        }

        echo json_encode(["status" => "success", "message" => "User deactivated successfully."]);
        exit;
    }

    if ($_GET['action'] === 'reactivate_user' && isset($_GET['user_id'])) {
        $user_id = $_GET['user_id'];
        $query = "UPDATE users SET acc_status = 'Active' WHERE lrn = ?";
        $result = executeQuery($conn, $query, [$user_id]);

        if ($result === false) {
            error_log("Failed to reactivate user with LRN: $user_id");
            echo json_encode(["status" => "error", "message" => "Failed to reactivate user."]);
            exit;
        }

        // Log the reactivation
        $admin_id = $_SESSION['user_id'] ?? null;
        if ($admin_id) {
            $action_text = ($_GET['action'] === 'deactivate_user') ? 'Account Deactivated' : 'Account Reactivated';
            $log_query = "INSERT INTO user_logs (user_id, action, action_by, action_date) VALUES (?, ?, ?, NOW())";
            $log_stmt = $conn->prepare($log_query);
            $log_stmt->bind_param("sss", $user_id, $action_text, $admin_id);
            $log_stmt->execute();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

// Get filter parameters
$employmentFilter = isset($_GET['employmentFilter']) ? $_GET['employmentFilter'] : 'all';
$yearFilter = isset($_GET['yearFilter']) ? $_GET['yearFilter'] : 'all';
$searchQuery = isset($_GET['searchQuery']) ? $_GET['searchQuery'] : '';

// Get graduation years for dropdown
$graduationYears = getGraduationYears($conn);

// Get statistics
$totalUsersQuery = "SELECT COUNT(*) as total_users FROM users WHERE role = 'User' AND 1=1";
$params = [];

if ($yearFilter !== 'all') {
    $totalUsersQuery .= " AND graduation_year = ?";
    $params[] = $yearFilter;
}

if ($employmentFilter !== 'all') {
    $totalUsersQuery .= " AND employment_status = ?";
    $params[] = $employmentFilter;
}

if (!empty($searchQuery)) {
    $totalUsersQuery .= " AND (first_name LIKE ? OR last_name LIKE ? OR CONCAT(first_name, ' ', last_name) LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

$totalUsersResult = executeQuery($conn, $totalUsersQuery, $params);
$totalUsers = $totalUsersResult->fetch_assoc()['total_users'];

$activeJobsResult = executeQuery($conn, "SELECT COUNT(*) as active_jobs FROM job_listings WHERE status = 'open'");
$activeJobs = $activeJobsResult->fetch_assoc()['active_jobs'];

$employedResult = executeQuery($conn, "SELECT COUNT(*) as employed FROM users WHERE role = 'User' AND employment_status = 'Employed'");
$employed = $employedResult->fetch_assoc()['employed'];

$unemployedResult = executeQuery($conn, "SELECT COUNT(*) as unemployed FROM users WHERE role = 'User' AND employment_status = 'Unemployed'");
$unemployed = $unemployedResult->fetch_assoc()['unemployed'];

$studentResult = executeQuery($conn, "SELECT COUNT(*) as student FROM users WHERE role = 'User' AND employment_status = 'Student'");
$student = $studentResult->fetch_assoc()['student'];

$totalAlumni = countFilesByGraduationYear($conn, $yearFilter);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/admin_dash.css">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Dashboard</h1>
        </div>
        <div class="filter-container">
            <div class="filter-item">
                <label class="filter-label" for="employmentFilter">Employment Status</label>
                <select class="filter-select" id="employmentFilter" name="employmentFilter">
                    <option value="all" <?php echo $employmentFilter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="Employed" <?php echo $employmentFilter === 'Employed' ? 'selected' : ''; ?>>Employed</option>
                    <option value="Unemployed" <?php echo $employmentFilter === 'Unemployed' ? 'selected' : ''; ?>>Unemployed</option>
                    <option value="Student" <?php echo $employmentFilter === 'Student' ? 'selected' : ''; ?>>Student</option>
                </select>
            </div>
            <div class="filter-item">
                <label class="filter-label" for="yearFilter">Graduation Year</label>
                <select class="filter-select" id="yearFilter" name="yearFilter">
                    <option value="all">All Years</option>
                    <?php foreach ($graduationYears as $year): ?>
                        <option value="<?php echo $year; ?>" <?php echo $yearFilter === $year ? 'selected' : ''; ?>><?php echo $year; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-item">
                <label class="filter-label" for="searchQuery">Search</label>
                <input type="text" class="filter-input" id="searchQuery" name="searchQuery" placeholder="Search by name..." value="<?php echo htmlspecialchars($searchQuery); ?>">
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Total Users</div>
                    <div class="stat-icon icon-primary">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $totalUsers; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Active Jobs</div>
                    <div class="stat-icon icon-primary">
                        <i class="fas fa-briefcase"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $activeJobs; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Employed</div>
                    <div class="stat-icon icon-success">
                        <i class="fas fa-user-tie"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $employed; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Unemployed</div>
                    <div class="stat-icon icon-danger">
                        <i class="fas fa-user-slash"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $unemployed; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Student</div>
                    <div class="stat-icon icon-warning">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $student; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Total Alumni</div>
                    <div class="stat-icon icon-primary">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $totalAlumni; ?></div>
            </div>
        </div>
        
        <div class="section-header">
            <h2 class="section-title">Alumni List</h2>
        </div>
        
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>LRN</th>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Year Graduated</th> <!-- Added column for Year Graduated -->
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Build the alumni query with filters
                    $alumniQuery = "SELECT lrn, CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) as name, 
                                    address, graduation_year, employment_status, acc_status 
                                    FROM users WHERE role = 'User' AND 1=1";
                    $alumniParams = [];
                    
                    if ($yearFilter !== 'all') {
                        $alumniQuery .= " AND graduation_year = ?";
                        $alumniParams[] = $yearFilter;
                    }
                    
                    if ($employmentFilter !== 'all') {
                        $alumniQuery .= " AND employment_status = ?";
                        $alumniParams[] = $employmentFilter;
                    }
                    
                    if (!empty($searchQuery)) {
                        $alumniQuery .= " AND (first_name LIKE ? OR last_name LIKE ? OR CONCAT(first_name, ' ', last_name) LIKE ?)";
                        $alumniParams[] = "%$searchQuery%";
                        $alumniParams[] = "%$searchQuery%";
                        $alumniParams[] = "%$searchQuery%";
                    }
                    
                    // Add pagination
                    $alumniQuery .= " LIMIT ? OFFSET ?";
                    $alumniParams[] = $items_per_page;
                    $alumniParams[] = $offset;
                    
                    $result = executeQuery($conn, $alumniQuery, $alumniParams);

                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $statusClass = '';
                            switch($row["employment_status"]) {
                                case 'Employed': $statusClass = 'status-employed'; break;
                                case 'Unemployed': $statusClass = 'status-unemployed'; break;
                                case 'Student': $statusClass = 'status-student'; break;
                            }
                            
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row["lrn"]) . "</td>";
                            echo "<td>" . htmlspecialchars($row["name"]) . "</td>";
                            echo "<td>" . htmlspecialchars($row["address"]) . "</td>";
                            echo "<td>" . htmlspecialchars($row["graduation_year"]) . "</td>"; // Added Year Graduated column data
                            echo "<td><span class='status-badge " . $statusClass . "'>" . htmlspecialchars($row["employment_status"]) . "</span></td>";
                            echo "<td>
                                <div class='action-buttons'>
                                    <button class='btn btn-primary' onclick='viewAlumniDetails(\"" . htmlspecialchars($row["lrn"]) . "\")'><i class='fas fa-eye'></i> View</button>";

                            // CORRECTED: Properly check account status (case-insensitive)
                            if (strtolower($row["acc_status"]) === 'active') {
                                echo "<button class='btn btn-danger deactivate-btn' data-user-id='" . htmlspecialchars($row["lrn"]) . "' data-user-name='" . htmlspecialchars($row["name"]) . "' data-action='deactivate'><i class='fas fa-ban'></i> Deactivate</button>";
                            } else {
                                echo "<button class='btn btn-success reactivate-btn' data-user-id='" . htmlspecialchars($row["lrn"]) . "' data-user-name='" . htmlspecialchars($row["name"]) . "' data-action='reactivate'><i class='fas fa-check'></i> Reactivate</button>";
                            }

                            echo "</div></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align: center; padding: 1.5rem;'>No alumni found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
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
            <span id="pageInfo" class="page-info">Page <?php echo $page; ?> of <?php echo ceil($totalUsers / $items_per_page); ?></span>
        </div>
    </div>
    
    <!-- Modal for viewing alumni details -->
    <div class="modal-overlay" id="alumniModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Alumni Details</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-tabs">
                    <div class="modal-tab active" data-tab="details">Details</div>
                </div>
                <div class="tab-content active" id="details-tab">
                    <div id="alumni-details-content">
                        <p>Loading details...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentPage = <?php echo $page; ?>;
        const totalPages = Math.ceil(<?php echo $totalUsers; ?> / <?php echo $items_per_page; ?>);

        // Debounce function to limit the frequency of function calls
        function debounce(func, delay) {
            let timeout;
            return function (...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), delay);
            };
        }

        // Function to fetch and update the alumni list dynamically
        function fetchAlumniData(page = 1) {
            const employmentFilter = document.getElementById("employmentFilter").value;
            const yearFilter = document.getElementById("yearFilter").value;
            const searchQuery = document.getElementById("searchQuery").value;

            const params = new URLSearchParams({
                employmentFilter: employmentFilter,
                yearFilter: yearFilter,
                searchQuery: searchQuery,
                page: page,
                action: 'filter_alumni'
            });

            const xhr = new XMLHttpRequest();
            xhr.open("GET", "<?php echo $_SERVER['PHP_SELF']; ?>?" + params.toString(), true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    document.querySelector(".data-table tbody").innerHTML = xhr.responseText;
                    updatePaginationControls(page, totalPages);
                }
            };
            xhr.send();
        }
        
        // Function to view alumni details in a modal
        function viewAlumniDetails(lrn) {
            // Show the modal
            document.getElementById('alumniModal').style.display = 'flex';
            
            // Set active tab to details
            setActiveTab('details');
            
            // Load user details
            loadUserDetails(lrn);
            
            // Store the current LRN for tab switching
            window.currentLRN = lrn;
        }
        
        // Function to load user details via AJAX
        function loadUserDetails(lrn) {
            const xhr = new XMLHttpRequest();
            xhr.open("GET", "<?php echo $_SERVER['PHP_SELF']; ?>?action=get_user_details&user_id=" + lrn, true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    document.getElementById('alumni-details-content').innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }
        
        // Function to close the modal
        function closeModal() {
            document.getElementById('alumniModal').style.display = 'none';
        }
        
        // Function to set active tab
        function setActiveTab(tabName) {
            // Update tab buttons
            const tabs = document.querySelectorAll('.modal-tab');
            tabs.forEach(tab => {
                if (tab.dataset.tab === tabName) {
                    tab.classList.add('active');
                } else {
                    tab.classList.remove('active');
                }
            });
            
            // Update tab content
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                if (content.id === tabName + '-tab') {
                    content.classList.add('active');
                } else {
                    content.classList.remove('active');
                }
            });
        }
        
        // Function to handle user account status changes
        function handleAccountStatusChange(action, userId, userName, buttonElement) {
            const actionText = action === 'deactivate' ? 'Deactivate' : 'Reactivate';
            const actionMessage = action === 'deactivate' 
                ? `Are you sure you want to deactivate ${userName}'s account? They will be logged out and unable to access the system.` 
                : `Are you sure you want to reactivate ${userName}'s account? They will be able to access the system again.`;

            Swal.fire({
                title: `${actionText} Account?`,
                text: actionMessage,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: action === 'deactivate' ? '#d33' : '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Yes, ${actionText}`,
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const xhr = new XMLHttpRequest();
                    xhr.open("GET", `<?php echo $_SERVER['PHP_SELF']; ?>?action=${action}_user&user_id=${userId}`, true);
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState == 4 && xhr.status == 200) {
                            const response = JSON.parse(xhr.responseText);
                            if (response.status === "success") {
                                Swal.fire({
                                    title: 'Success!',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    // Automatically toggle the button state
                                    if (action === 'deactivate') {
                                        buttonElement.classList.remove('btn-danger');
                                        buttonElement.classList.add('btn-success');
                                        buttonElement.innerHTML = "<i class='fas fa-check'></i> Reactivate";
                                        buttonElement.dataset.action = 'reactivate';
                                    } else {
                                        buttonElement.classList.remove('btn-success');
                                        buttonElement.classList.add('btn-danger');
                                        buttonElement.innerHTML = "<i class='fas fa-ban'></i> Deactivate";
                                        buttonElement.dataset.action = 'deactivate';
                                    }
                                    
                                    // Refresh the alumni list to show updated status
                                    fetchAlumniData(currentPage);
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: response.message || 'Something went wrong',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        }
                    };
                    xhr.send();
                }
            });
        }

        // Event delegation for deactivate/reactivate buttons
        document.addEventListener('click', function(e) {
            const button = e.target.closest('.deactivate-btn, .reactivate-btn');
            if (button) {
                const action = button.dataset.action;
                const userId = button.dataset.userId;
                const userName = button.dataset.userName;
                handleAccountStatusChange(action, userId, userName, button);
            }
        });

        // Function to update pagination controls
        function updatePaginationControls(page, totalPages) {
            document.getElementById('pageInfo').textContent = `Page ${page} of ${totalPages}`;
            document.getElementById('prevPage').disabled = page <= 1;
            document.getElementById('nextPage').disabled = page >= totalPages;
            currentPage = page;
        }

        // Event listeners for pagination
        document.getElementById('prevPage').addEventListener('click', function() {
            if (currentPage > 1) {
                fetchAlumniData(currentPage - 1);
            }
        });

        document.getElementById('nextPage').addEventListener('click', function() {
            if (currentPage < totalPages) {
                fetchAlumniData(currentPage + 1);
            }
        });

        // Event listeners for filter changes with debounce
        document.getElementById('employmentFilter').addEventListener('change', function() {
            fetchAlumniData(1);
        });

        document.getElementById('yearFilter').addEventListener('change', function() {
            fetchAlumniData(1);
        });

        document.getElementById('searchQuery').addEventListener('input', debounce(function() {
            fetchAlumniData(1);
        }, 300));

        // Initialize pagination controls
        updatePaginationControls(currentPage, totalPages);

        // Tab click handlers for modal
        document.querySelectorAll('.modal-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                setActiveTab(this.dataset.tab);
            });
        });

        // View attachment with modal preview
        function viewAttachment(attachmentId) {
            Swal.fire({
                title: 'Attachment Preview',
                html: `<iframe src="view_attachment.php?id=${attachmentId}" style="width:100%; height:500px; border:none;"></iframe>`,
                showCloseButton: true,
                showConfirmButton: false,
                width: '80%'
            });
        }

        // Download attachment
        function downloadAttachment(attachmentId) {
            window.location.href = `download_attachment.php?id=${attachmentId}`;
        }
    </script>
</body>
</html>