<?php
// Include the database connection
include 'sqlconnection.php';

// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch user data from the database
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
} else {
    die("User not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "profile_img/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
        $allowed_extensions = array("jpg", "jpeg", "png", "gif");
        
        // Validate file extension
        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = "profile_" . $user_id . "_" . time() . "." . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                $sql = "UPDATE users SET profile_img = ? WHERE user_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "si", $target_file, $user_id);
                mysqli_stmt_execute($stmt);
                
                if (isset($_POST['ajax'])) {
                    echo json_encode(['success' => true, 'new_image' => $target_file]);
                    exit;
                }
            } else {
                if (isset($_POST['ajax'])) {
                    echo json_encode(['success' => false, 'error' => 'Failed to upload image']);
                    exit;
                }
            }
        } else {
            if (isset($_POST['ajax'])) {
                echo json_encode(['success' => false, 'error' => 'Invalid file type']);
                exit;
            }
        }
    }
    
    // Handle resume upload
    if (isset($_FILES['resume_file']) && $_FILES['resume_file']['error'] == 0) {
        $resume_dir = "uploads/resumes/";
        if (!file_exists($resume_dir)) {
            mkdir($resume_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["resume_file"]["name"], PATHINFO_EXTENSION));
        $allowed_extensions = array("pdf", "doc", "docx");
        
        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = "resume_" . $user_id . "_" . time() . "." . $file_extension;
            $target_file = $resume_dir . $new_filename;
            
            if (move_uploaded_file($_FILES["resume_file"]["tmp_name"], $target_file)) {
                $resume_file = $target_file;
            }
        }
    } else {
        $resume_file = $user['resume_file'] ?? '';
    }
    
    // Update user profile data
    if (!isset($_POST['ajax'])) {
        $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
        $middle_name = mysqli_real_escape_string($conn, $_POST['middle_name'] ?? '');
        $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
        $gender = mysqli_real_escape_string($conn, $_POST['gender']);
        $birthdate = mysqli_real_escape_string($conn, $_POST['birthdate']);
        $graduation_year = mysqli_real_escape_string($conn, $_POST['graduation_year']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $skills = mysqli_real_escape_string($conn, $_POST['skills']);
        $work_experience = mysqli_real_escape_string($conn, $_POST['work_experience']);
        $employment_status = mysqli_real_escape_string($conn, $_POST['employment_status']);
        $resume_file = isset($resume_file) ? $resume_file : ($user['resume_file'] ?? '');
        $last_updated = date('Y-m-d H:i:s');

        $sql = "UPDATE users SET 
                first_name = ?, 
                middle_name = ?,
                last_name = ?, 
                gender = ?, 
                birthdate = ?, 
                graduation_year = ?, 
                email = ?, 
                phone_number = ?, 
                address = ?, 
                skills = ?, 
                work_experience = ?,
                employment_status = ?,
                resume_file = ?,
                last_updated = ?
                WHERE user_id = ?";
                
        $stmt = mysqli_prepare($conn, $sql);
        // 14 variables: 13 strings, 1 integer
        mysqli_stmt_bind_param($stmt, "ssssssssssssssi", 
            $first_name, $middle_name, $last_name, $gender, $birthdate, $graduation_year,
            $email, $phone_number, $address, $skills, $work_experience, $employment_status,
            $resume_file, $last_updated, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Profile updated successfully!";
            // Refresh user data
            $sql = "SELECT * FROM users WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
        } else {
            $error_message = "Failed to update profile: " . mysqli_error($conn);
        }
    }
}

// Close the database connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --primary-dark: #3f37c9;
            --secondary: #4cc9f0;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --radius-sm: 0.25rem;
            --radius-md: 0.375rem;
            --radius-lg: 0.5rem;
            --radius-xl: 0.75rem;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --card-bg: white;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--gray-100);
            color: var(--gray-800);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem;
        }

        .page-header {
            background: linear-gradient(to right, var(--primary), var(--primary-light));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
        }

        .page-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
        }

        .page-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            margin-top: 0.5rem;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-md);
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .back-button:hover {
            background-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .profile-form {
            margin-bottom: 3rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        @media (min-width: 768px) {
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .form-grid-full {
                grid-column: span 2;
            }
        }

        .card {
            background-color: var(--card-bg);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        .card-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-header h2 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--gray-800);
            margin: 0;
        }

        .card-header-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            background: linear-gradient(to right, var(--primary-light), var(--secondary));
            color: white;
            border-radius: var(--radius-lg);
        }

        .card-body {
            padding: 1.25rem;
        }

        .form-group {
            margin-bottom: 1.125rem;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--gray-700);
        }

        .form-control, .form-select {
            width: 100%;
            padding: 0.675rem 0.875rem;
            /* Remove border and background */
            border: none;
            border-bottom: 2px solid var(--primary);
            border-radius: 0;
            font-size: 0.875rem;
            font-family: inherit;
            color: var(--gray-800);
            background-color: transparent;
            transition: border-color 0.2s, box-shadow 0.2s;
            min-height: 2.75rem;
            box-shadow: none;
        }

        .form-control:focus, .form-select:focus {
            border-bottom: 2.5px solid var(--primary-dark);
            outline: none;
            box-shadow: none;
        }

        .form-control::placeholder {
            color: var(--gray-400);
        }

        /* Remove default arrow for select, keep custom arrow if needed */
        .form-select {
            appearance: none;
            background: none;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .form-select {
            width: 100%;
            padding: 0.675rem 0.875rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-family: inherit;
            color: var(--gray-800);
            background-color: white;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.875rem center;
            background-size: 1rem;
            transition: all 0.2s ease;
        }

        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
            outline: none;
        }

        .profile-image-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.875rem;
            margin-bottom: 1.25rem;
        }

        .profile-image-preview {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: var(--shadow-md);
            position: relative;
        }

        .profile-image-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            border-radius: 50%;
            opacity: 0;
            transition: opacity 0.2s ease;
            cursor: pointer;
        }

        .profile-image-preview:hover .profile-image-overlay {
            opacity: 1;
        }

        .file-upload {
            position: relative;
            display: inline-block;
        }

        .file-upload-label {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            background-color: var(--gray-100);
            border: 1px solid var(--gray-300);
            border-radius: var(--radius-md);
            color: var(--gray-700);
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            min-height: 2.75rem; /* Ensure minimum height for touch targets */
            min-width: 8rem; /* Ensure minimum width for touch targets */
        }

        .file-upload-label:hover {
            background-color: var(--gray-200);
            color: var(--gray-800);
        }

        .file-upload input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-name {
            margin-top: 0.5rem;
            font-size: 0.75rem;
            color: var(--gray-500);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(to right, var(--primary), var(--primary-light));
            color: white;
            border: none;
            box-shadow: 0 2px 5px rgba(67, 97, 238, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--primary));
            box-shadow: 0 4px 8px rgba(67, 97, 238, 0.4);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: white;
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
        }

        .btn-secondary:hover {
            background-color: var(--gray-50);
            color: var(--gray-800);
            border-color: var(--gray-400);
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .alert-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 50%;
        }

        .alert-success .alert-icon {
            background-color: var(--success);
            color: white;
        }

        .alert-danger .alert-icon {
            background-color: var(--danger);
            color: white;
        }

        .skills-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.625rem;
            max-width: 100%;
            overflow-x: hidden;
        }

        .skill-tag {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
            white-space: nowrap;
            margin-bottom: 0.25rem;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            visibility: hidden;
            opacity: 0;
            transition: all 0.3s;
        }

        .loading-overlay.active {
            visibility: visible;
            opacity: 1;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 0.75rem;
            }

            .page-header {
                padding: 1rem 0;
                margin-bottom: 1rem;
            }

            .page-title {
                font-size: 1.375rem;
            }
            
            .page-subtitle {
                font-size: 0.875rem;
            }
            
            .form-grid {
                gap: 0.75rem;
            }

            .card-header, .card-body {
                padding: 0.875rem;
            }
            
            .card {
                margin-bottom: 0.75rem;
                border-radius: var(--radius-lg);
            }

            .form-actions {
                flex-direction: column;
                margin-top: 1.25rem;
                gap: 0.5rem;
            }

            .btn {
                width: 100%;
                padding: 0.625rem 0.875rem;
            }
            
            .form-group {
                margin-bottom: 0.75rem;
            }
            
            .profile-image-upload {
                gap: 0.5rem;
                margin-bottom: 1rem;
            }
        }

        @media (max-width: 576px) {
            .container {
                padding: 0.625rem;
            }
            
            .page-header .container {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .back-button {
                align-self: flex-start;
                padding: 0.375rem 0.75rem;
                font-size: 0.8125rem;
            }

            .profile-image-preview {
                width: 90px;
                height: 90px;
            }
            
            .card-header {
                padding: 0.75rem;
            }
            
            .card-body {
                padding: 0.75rem;
            }
            
            .card-header-icon {
                width: 1.75rem;
                height: 1.75rem;
                font-size: 0.875rem;
            }
            
            .card-header h2 {
                font-size: 1rem;
            }
            
            .form-label {
                margin-bottom: 0.25rem;
                font-size: 0.8125rem;
            }
            
            .form-control, .form-select {
                padding: 0.5rem 0.75rem;
                font-size: 0.8125rem;
                height: 2.5rem;
            }
            
            textarea.form-control {
                height: auto;
                min-height: 100px;
            }
            
            .file-upload-label {
                padding: 0.375rem 0.75rem;
                font-size: 0.8125rem;
            }
            
            .skills-preview {
                gap: 0.25rem;
                margin-top: 0.375rem;
            }
            
            .skill-tag {
                padding: 0.25rem 0.5rem;
                font-size: 0.6875rem;
            }
            
            .btn {
                font-size: 0.8125rem;
                padding: 0.5rem 0.75rem;
            }
            
            .alert {
                padding: 0.75rem;
                font-size: 0.8125rem;
                margin-bottom: 1rem;
            }
        }

        /* Specific optimizations for narrow phones like Samsung S21 FE */
        @media (max-width: 400px) {
            .form-grid {
                gap: 0.5rem;
            }
            
            .card {
                border-radius: var(--radius-md);
                margin-bottom: 0.5rem;
            }
            
            .card-header, .card-body {
                padding: 0.625rem;
            }
            
            .profile-image-preview {
                width: 80px;
                height: 80px;
            }
            
            .form-group {
                margin-bottom: 0.625rem;
            }
            
            .form-control, .form-select {
                padding: 0.4375rem 0.625rem;
            }
            
            .file-name {
                font-size: 0.6875rem;
            }
            
            .form-actions {
                margin-top: 1rem;
            }
        }

    </style>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <div class="container" style="margin-top:2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h1 class="page-title">Update Profile</h1>
            </div>
            <!-- Back button removed -->
        </div>
        <?php if (isset($success_message)): ?>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
                Swal.fire({
                    title: 'Success!',
                    text: '<?php echo addslashes($success_message); ?>',
                    icon: 'success',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#4361ee'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'edit_profile.php';
                    }
                });
            </script>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <div class="alert-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div><?php echo $error_message; ?></div>
            </div>
        <?php endif; ?>

        <form id="profileForm" method="POST" enctype="multipart/form-data" class="profile-form">
            <div class="form-grid">
                <!-- Personal Information -->
                <div class="card form-grid-full">
                    <div class="card-header">
                        <div class="card-header-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <h2>Personal Information</h2>
                    </div>
                    <div class="card-body">
                        <div class="profile-image-upload">
                            <div style="position: relative; display: flex; flex-direction: column; align-items: center;">
                                <img
                                    id="profileImagePreview"
                                    src="<?php echo !empty($user['profile_img']) ? $user['profile_img'] : 'https://via.placeholder.com/120'; ?>"
                                    alt="Profile Image"
                                    width="120"
                                    height="120"
                                    class="profile-image-preview"
                                    style="border-radius: 50%; object-fit: cover; border: 3px solid white; box-shadow: var(--shadow-md);"
                                >
                                <button
                                    type="button"
                                    class="profile-image-overlay"
                                    style="
                                        position: absolute;
                                        top: 0; left: 0; width: 100%; height: 100%;
                                        display: flex; align-items: center; justify-content: center;
                                        background: rgba(0,0,0,0.45); color: #fff;
                                        border: none; border-radius: 50%; opacity: 0;
                                        transition: opacity 0.2s;
                                        font-size: 1.5rem; cursor: pointer;
                                    "
                                    aria-label="Change profile photo"
                                    onclick="document.getElementById('profileImage').click()"
                                    onmouseover="this.style.opacity=1"
                                    onmouseout="this.style.opacity=0"
                                    tabindex="0"
                                >
                                    <i class="fas fa-camera"></i>
                                </button>
                            </div>
                            <div class="file-upload" style="margin-top: 0.5rem;">
                                <label class="file-upload-label" style="background: var(--primary); color: #fff; border: none;">
                                    <i class="fas fa-upload"></i>
                                    <span>Upload New Photo</span>
                                    <input
                                        type="file"
                                        id="profileImage"
                                        name="profile_image"
                                        accept="image/*"
                                        style="display: none;"
                                        aria-label="Upload new profile photo"
                                    >
                                </label>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="firstName" class="form-label">First Name</label>
                                <input type="text" id="firstName" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="middleName" class="form-label">Middle Name (Optional)</label>
                                <input type="text" id="middleName" name="middle_name" class="form-control" value="<?php echo htmlspecialchars($user['middle_name'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="lastName" class="form-label">Last Name</label>
                                <input type="text" id="lastName" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="gender" class="form-label">Gender</label>
                                <select id="gender" name="gender" class="form-select">
                                    <option value="male" <?php echo $user['gender'] == 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo $user['gender'] == 'female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo $user['gender'] == 'other' ? 'selected' : ''; ?>>Other</option>
                                    <option value="prefer_not_to_say" <?php echo $user['gender'] == 'prefer_not_to_say' ? 'selected' : ''; ?>>Prefer not to say</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="birthdate" class="form-label">Date of Birth</label>
                                <input type="date" id="birthdate" name="birthdate" class="form-control" value="<?php echo htmlspecialchars($user['birthdate']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="employmentStatus" class="form-label">Employment Status</label>
                                <select id="employmentStatus" name="employment_status" class="form-select">
                                    <option value="employed" <?php echo $user['employment_status'] == 'employed' ? 'selected' : ''; ?>>Employed</option>
                                    <option value="unemployed" <?php echo $user['employment_status'] == 'unemployed' ? 'selected' : ''; ?>>Unemployed</option>
                                    <option value="freelance" <?php echo $user['employment_status'] == 'freelance' ? 'selected' : ''; ?>>Freelance</option>
                                    <option value="student" <?php echo $user['employment_status'] == 'student' ? 'selected' : ''; ?>>Student</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="card form-grid-full">
                    <div class="card-header">
                        <div class="card-header-icon">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <h2>Contact Information</h2>
                    </div>
                    <div class="card-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="phoneNumber" class="form-label">Phone Number</label>
                                <input type="tel" id="phoneNumber" name="phone_number" class="form-control" value="<?php echo htmlspecialchars($user['phone_number']); ?>">
                            </div>
                            
                            <div class="form-group form-grid-full">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" id="address" name="address" class="form-control" value="<?php echo htmlspecialchars($user['address']); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Education -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h2>Education</h2>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="graduationYear" class="form-label">Graduation Year</label>
                            <input type="text" id="graduationYear" name="graduation_year" class="form-control" value="<?php echo htmlspecialchars($user['graduation_year']); ?>" placeholder="e.g. 2023">
                        </div>
                    </div>
                </div>

                <!-- Career -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <h2>Career</h2>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="resumeFile" class="form-label">Resume</label>
                            <div class="file-upload">
                                <label class="file-upload-label">
                                    <i class="fas fa-file-upload"></i>
                                    Choose File
                                    <input type="file" id="resumeFile" name="resume_file" accept=".pdf,.doc,.docx">
                                </label>
                            </div>
                            <div class="file-name" id="resumeFileName">
                                <?php echo !empty($user['resume_file']) ? basename($user['resume_file']) : 'No file chosen'; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Skills -->
                <div class="card form-grid-full">
                    <div class="card-header">
                        <div class="card-header-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h2>Skills</h2>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="skills" class="form-label">Skills (separate with commas)</label>
                            <input type="text" id="skills" name="skills" class="form-control" value="<?php echo htmlspecialchars($user['skills']); ?>" placeholder="e.g. JavaScript, Python, Project Management">
                        </div>
                        
                        <div class="skills-preview" id="skillsPreview">
                            <?php
                            if (!empty($user['skills'])) {
                                $skills = explode(',', $user['skills']);
                                foreach ($skills as $skill) {
                                    $skill = trim($skill);
                                    if (!empty($skill)) {
                                        echo '<span class="skill-tag">' . htmlspecialchars($skill) . '</span>';
                                    }
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Work Experience -->
                <div class="card form-grid-full">
                    <div class="card-header">
                        <div class="card-header-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <h2>Work Experience</h2>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="workExperience" class="form-label">Work Experience (separate different positions with blank lines)</label>
                            <textarea id="workExperience" name="work_experience" class="form-control" rows="6" placeholder="e.g. Software Developer at Tech Company (2020-Present)
- Led development of customer-facing web application
- Implemented responsive design and improved performance

Marketing Intern at Marketing Agency (2019-2020)
- Assisted with social media campaigns
- Analyzed marketing metrics and created reports"><?php echo htmlspecialchars($user['work_experience']); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="saveButton">
                    <i class="fas fa-save"></i>
                    Save Changes
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='edit_profile.php'">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
            </div>
        </form>
    </div>

    <script>
        // Show skills as tags
        document.getElementById('skills').addEventListener('input', function() {
            const skillsContainer = document.getElementById('skillsPreview');
            skillsContainer.innerHTML = '';
            
            const skills = this.value.split(',');
            skills.forEach(skill => {
                skill = skill.trim();
                if (skill) {
                    const tag = document.createElement('span');
                    tag.className = 'skill-tag';
                    tag.textContent = skill;
                    skillsContainer.appendChild(tag);
                }
            });
        });

        // Show file name when resume is selected
        document.getElementById('resumeFile').addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : 'No file chosen';
            document.getElementById('resumeFileName').textContent = fileName;
        });

        // Handle profile image upload
        document.getElementById('profileImage').addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                
                // Show loading overlay
                document.getElementById('loadingOverlay').classList.add('active');
                
                // Preview the image immediately
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profileImagePreview').src = e.target.result;
                }
                reader.readAsDataURL(file);
                
                // Upload the image
                const formData = new FormData();
                formData.append('profile_image', file);
                formData.append('ajax', 'true');
                
                fetch('update_profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Hide loading overlay
                    document.getElementById('loadingOverlay').classList.remove('active');
                    
                    if (data.success) {
                        // Update the profile image with the new one from server
                        document.getElementById('profileImagePreview').src = data.new_image;
                        showNotification('Profile picture updated successfully!', 'success');
                    } else {
                        showNotification(data.error || 'Failed to update profile picture', 'error');
                    }
                })
                .catch(error => {
                    // Hide loading overlay
                    document.getElementById('loadingOverlay').classList.remove('active');
                    console.error('Error:', error);
                    showNotification('An error occurred while updating the profile picture', 'error');
                });
            }
        });

        // Show notification
        function showNotification(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'}`;
            
            const iconDiv = document.createElement('div');
            iconDiv.className = 'alert-icon';
            
            const icon = document.createElement('i');
            icon.className = type === 'success' ? 'fas fa-check' : 'fas fa-exclamation-triangle';
            
            iconDiv.appendChild(icon);
            
            const messageDiv = document.createElement('div');
            messageDiv.textContent = message;
            
            alertDiv.appendChild(iconDiv);
            alertDiv.appendChild(messageDiv);
            
            // Insert at the top of the form container
            const container = document.querySelector('.container');
            container.insertBefore(alertDiv, container.firstChild);
            
            // Remove after 5 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

        // Handle form submission
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            // Show loading overlay
            document.getElementById('loadingOverlay').classList.add('active');
            
            // Form will submit normally, loading overlay will be hidden on page reload
        });

        // Animation for cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100 * index);
            });
        });
    </script>
</body>
</html>
