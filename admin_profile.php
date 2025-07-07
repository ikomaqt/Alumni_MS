<?php
include 'sqlconnection.php'; 
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Redirect if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Fetch admin info from database
$admin_id = $_SESSION['admin_id'];
$query = "SELECT name, email, role, profile_pic, created_at FROM admin_users WHERE admin_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result($admin_name, $admin_email, $admin_role, $admin_profile_pic, $created_at);
$stmt->fetch();
$stmt->close();

// Handle profile update
$update_msg = '';
$update_status = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle profile info update
    if (isset($_POST['update_profile'])) {
        $new_name = trim($_POST['name']);
        $new_email = trim($_POST['email']);
        $profile_pic_path = $admin_profile_pic;

        // Handle profile picture upload
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (in_array($_FILES['profile_pic']['type'], $allowed_types)) {
                $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
                $target_dir = "img/";
                $target_file = $target_dir . "admin_" . $admin_id . "_profile." . $ext;
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
                    $profile_pic_path = $target_file;
                } else {
                    $update_msg = "Failed to upload profile picture.";
                    $update_status = "error";
                }
            } else {
                $update_msg = "Invalid file type. Please upload a JPG, PNG, or GIF image.";
                $update_status = "error";
            }
        }

        // Only update if no errors occurred
        if ($update_status !== "error") {
            // Update database
            $update_query = "UPDATE admin_users SET name=?, email=?, profile_pic=? WHERE admin_id=?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("sssi", $new_name, $new_email, $profile_pic_path, $admin_id);
            if ($update_stmt->execute()) {
                $update_msg = "Profile updated successfully.";
                $update_status = "success";
                $admin_name = $new_name;
                $admin_email = $new_email;
                $admin_profile_pic = $profile_pic_path;
            } else {
                $update_msg = "Failed to update profile: " . $conn->error;
                $update_status = "error";
            }
            $update_stmt->close();
        }
    }
    
    // Handle password update
    if (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        $password_query = "SELECT password FROM admin_users WHERE admin_id = ?";
        $password_stmt = $conn->prepare($password_query);
        $password_stmt->bind_param("i", $admin_id);
        $password_stmt->execute();
        $password_stmt->bind_result($stored_password);
        $password_stmt->fetch();
        $password_stmt->close();
        
        if (password_verify($current_password, $stored_password)) {
            // Check if new passwords match
            if ($new_password === $confirm_password) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_password_query = "UPDATE admin_users SET password=? WHERE admin_id=?";
                $update_password_stmt = $conn->prepare($update_password_query);
                $update_password_stmt->bind_param("si", $hashed_password, $admin_id);
                
                if ($update_password_stmt->execute()) {
                    $update_msg = "Password updated successfully.";
                    $update_status = "success";
                    // Destroy session and redirect to login
                    session_unset();
                    session_destroy();
                    header("Location: admin_login.php?password_changed=1");
                    exit();
                } else {
                    $update_msg = "Failed to update password: " . $conn->error;
                    $update_status = "error";
                }
                $update_password_stmt->close();
            } else {
                $update_msg = "New passwords do not match.";
                $update_status = "error";
            }
        } else {
            $update_msg = "Current password is incorrect.";
            $update_status = "error";
        }
    }
}

// Fallbacks if no data
if (!$admin_name) $admin_name = 'Admin';
if (!$admin_email) $admin_email = 'admin@gmail.com';
if (!$admin_role) $admin_role = 'Administrator';
if (!$admin_profile_pic) $admin_profile_pic = 'img/default_avatar.png';
if (!$created_at) $created_at = date('Y-m-d H:i:s');

// Format the created_at date
$formatted_date = date("F j, Y", strtotime($created_at));
?>
<?php include 'admin_navbar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile | Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-light: #e6effd;
            --primary-dark: #3a56d4;
            --secondary-color: #f8f9fa;
            --text-color: #333;
            --text-muted: #6c757d;
            --border-color: #e0e0e0;
            --success-color: #10b981;
            --error-color: #ef4444;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --transition: all 0.3s ease;
        }
        
        /* Base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fb;
            color: var(--text-color);
            line-height: 1.6;
        }
        
        /* Main container */
        .admin-container {
            margin-left: 90px; /* Reduced from 260px for closer profile */
            padding: 2rem;
            min-height: 100vh;
            transition: var(--transition);
        }
        
        /* Page header */
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .admin-header-title h1 {
            font-size: 1.75rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .admin-header-title p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }
        
        /* Alert messages */
        .admin-alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .admin-alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            border-left: 4px solid var(--success-color);
            color: var(--success-color);
        }
        
        .admin-alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            border-left: 4px solid var(--error-color);
            color: var(--error-color);
        }
        
        /* Profile grid layout */
        .admin-profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        /* Profile card */
        .admin-card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }
        
        .admin-card-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .admin-card-title i {
            color: var(--primary-color);
        }
        
        .admin-card-body {
            padding: 1.5rem;
        }
        
        /* Profile info section */
        .admin-profile-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
        }
        
        /* Profile avatar with upload overlay */
        .admin-profile-avatar-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin-bottom: 1rem;
            border-radius: 50%;
            overflow: hidden;
        }
        
        .admin-profile-avatar {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border: 4px solid var(--primary-light);
            border-radius: 50%;
        }
        
        .admin-profile-avatar-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s;
            cursor: pointer;
            border-radius: 50%;
            color: white;
        }
        
        .admin-profile-avatar-container:hover .admin-profile-avatar-overlay {
            opacity: 1;
        }
        
        .admin-profile-avatar-icon {
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }
        
        .admin-profile-avatar-text {
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .admin-profile-avatar-input {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        
        .admin-profile-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .admin-profile-role {
            display: inline-block;
            background-color: var(--primary-light);
            color: var(--primary-dark);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.75rem;
        }
        
        .admin-profile-email {
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        /* Profile details */
        .admin-profile-details {
            display: grid;
            gap: 1rem;
        }
        
        .admin-detail-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .admin-detail-item:last-child {
            border-bottom: none;
        }
        
        .admin-detail-label {
            color: var(--text-muted);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .admin-detail-label i {
            color: var(--primary-color);
            font-size: 0.875rem;
        }
        
        .admin-detail-value {
            font-weight: 500;
        }
        
        /* Form styles */
        .admin-form {
            display: grid;
            gap: 1.25rem;
        }
        
        .admin-form-group {
            display: grid;
            gap: 0.5rem;
        }
        
        .admin-form-label {
            font-weight: 500;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .admin-form-label i {
            color: var(--primary-color);
        }
        
        .admin-form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .admin-form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        .admin-form-text {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }
        
        /* Buttons */
        .admin-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            border: none;
        }
        
        .admin-btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .admin-btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .admin-btn-outline {
            background-color: transparent;
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }
        
        .admin-btn-outline:hover {
            background-color: var(--secondary-color);
        }
        
        .admin-form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        /* Password section */
        .admin-password-section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }
        
        .admin-password-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .admin-password-title i {
            color: var(--primary-color);
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .admin-container {
                margin-left: 0;
                padding: 1.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .admin-profile-grid {
                grid-template-columns: 1fr;
            }
            
            .admin-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .admin-form-actions {
                flex-direction: column;
            }
            
            .admin-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="admin-header-title">
                <h1>Admin Profile</h1>
                <p>View and manage your account information</p>
            </div>
        </div>
        
        <?php
        // Remove the alert box for update messages (only use SweetAlert2)
        ?>
        
        <div class="admin-profile-grid">
            <!-- Profile Information Card -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <div class="admin-card-title">
                        <i class="fas fa-user-circle"></i>
                        <span>Profile Information</span>
                    </div>
                </div>
                <div class="admin-card-body">
                    <div class="admin-profile-info">
                        <form method="post" enctype="multipart/form-data" id="avatar-form">
                            <div class="admin-profile-avatar-container">
                                <img src="<?php echo htmlspecialchars($admin_profile_pic); ?>" alt="Admin Avatar" class="admin-profile-avatar" onerror="this.src='img/default_avatar.png'">
                                <div class="admin-profile-avatar-overlay">
                                    <div class="admin-profile-avatar-icon">
                                        <i class="fas fa-camera"></i>
                                    </div>
                                    <div class="admin-profile-avatar-text">Change Photo</div>
                                    <input type="file" name="profile_pic" class="admin-profile-avatar-input" accept="image/*" onchange="submitAvatarForm()">
                                </div>
                            </div>
                            <input type="hidden" name="update_profile" value="1">
                            <input type="hidden" name="name" value="<?php echo htmlspecialchars($admin_name); ?>">
                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($admin_email); ?>">
                        </form>
                        <h2 class="admin-profile-name"><?php echo htmlspecialchars($admin_name); ?></h2>
                        <span class="admin-profile-role"><?php echo htmlspecialchars($admin_role); ?></span>
                        <div class="admin-profile-email">
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($admin_email); ?></span>
                        </div>
                    </div>
                    
                    <div class="admin-profile-details">
                        <div class="admin-detail-item">
                            <div class="admin-detail-label">
                                <i class="fas fa-id-badge"></i>
                                <span>Admin ID</span>
                            </div>
                            <div class="admin-detail-value">#<?php echo htmlspecialchars($admin_id); ?></div>
                        </div>
                        <div class="admin-detail-item">
                            <div class="admin-detail-label">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Account Created</span>
                            </div>
                            <div class="admin-detail-value"><?php echo htmlspecialchars($formatted_date); ?></div>
                        </div>
                        <div class="admin-detail-item">
                            <div class="admin-detail-label">
                                <i class="fas fa-shield-alt"></i>
                                <span>Account Status</span>
                            </div>
                            <div class="admin-detail-value">
                                <span style="color: var(--success-color); display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-check-circle"></i> Active
                                </span>
                            </div>
                        </div>
                        <div class="admin-detail-item">
                            <div class="admin-detail-label">
                                <i class="fas fa-clock"></i>
                                <span>Last Updated</span>
                            </div>
                            <div class="admin-detail-value">
                                <?php 
                                    // Get updated_at date if available
                                    $date_query = "SELECT updated_at FROM admin_users WHERE admin_id = ?";
                                    $date_stmt = $conn->prepare($date_query);
                                    $date_stmt->bind_param("i", $admin_id);
                                    $date_stmt->execute();
                                    $date_stmt->bind_result($updated_at);
                                    $date_stmt->fetch();
                                    $date_stmt->close();
                                    
                                    echo $updated_at ? date("F j, Y, g:i a", strtotime($updated_at)) : "N/A";
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Edit Profile Card -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <div class="admin-card-title">
                        <i class="fas fa-edit"></i>
                        <span>Edit Profile</span>
                    </div>
                </div>
                <div class="admin-card-body">
                    <form class="admin-form" method="post" enctype="multipart/form-data">
                        <div class="admin-form-group">
                            <label class="admin-form-label" for="name">
                                <i class="fas fa-user"></i>
                                <span>Full Name</span>
                            </label>
                            <input type="text" id="name" name="name" class="admin-form-control" value="<?php echo htmlspecialchars($admin_name); ?>" required>
                            <div class="admin-form-text">Your full name as it will appear across the admin panel</div>
                        </div>
                        
                        <div class="admin-form-group">
                            <label class="admin-form-label" for="email">
                                <i class="fas fa-envelope"></i>
                                <span>Email Address</span>
                            </label>
                            <input type="email" id="email" name="email" class="admin-form-control" value="<?php echo htmlspecialchars($admin_email); ?>" required>
                            <div class="admin-form-text">Your email address for notifications and account recovery</div>
                        </div>
                        
                        <div class="admin-form-actions">
                            <button type="submit" name="update_profile" class="admin-btn admin-btn-primary">
                                <i class="fas fa-save"></i>
                                <span>Save Changes</span>
                            </button>
                        </div>
                        
                        <!-- Password Change Section -->
                        <div class="admin-password-section">
                            <div class="admin-password-title">
                                <i class="fas fa-lock"></i>
                                <span>Change Password</span>
                            </div>
                            
                            <div class="admin-form-group">
                                <label class="admin-form-label" for="current_password">
                                    <i class="fas fa-key"></i>
                                    <span>Current Password</span>
                                </label>
                                <input type="password" id="current_password" name="current_password" class="admin-form-control" placeholder="Enter your current password">
                            </div>
                            
                            <div class="admin-form-group">
                                <label class="admin-form-label" for="new_password">
                                    <i class="fas fa-lock"></i>
                                    <span>New Password</span>
                                </label>
                                <input type="password" id="new_password" name="new_password" class="admin-form-control" placeholder="Enter your new password">
                                <div class="admin-form-text">Use at least 8 characters with a mix of letters, numbers & symbols</div>
                            </div>
                            
                            <div class="admin-form-group">
                                <label class="admin-form-label" for="confirm_password">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Confirm New Password</span>
                                </label>
                                <input type="password" id="confirm_password" name="confirm_password" class="admin-form-control" placeholder="Confirm your new password">
                            </div>
                            
                            <div class="admin-form-actions">
                                <button type="submit" name="update_password" class="admin-btn admin-btn-primary">
                                    <i class="fas fa-key"></i>
                                    <span>Update Password</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function submitAvatarForm() {
            document.getElementById('avatar-form').submit();
        }

        // Prevent Enter key from submitting the password change section
        document.querySelectorAll('.admin-password-section input').forEach(function(input) {
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    return false;
                }
            });
        });

        <?php if (
            isset($_POST['update_password']) && $update_msg
        ): ?>
        // SweetAlert2 for password change (success or error)
        Swal.fire({
            icon: '<?php echo $update_status === "success" ? "success" : "error"; ?>',
            title: '<?php echo $update_status === "success" ? "Success" : "Error"; ?>',
            text: <?php echo json_encode($update_msg); ?>,
            confirmButtonColor: '#4361ee'
        });
        <?php elseif ($update_status === 'success' && $update_msg): ?>
        // SweetAlert2 success popup for profile update
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: <?php echo json_encode($update_msg); ?>,
            confirmButtonColor: '#4361ee'
        });
        <?php endif; ?>
    </script>
</body>
</html>