<?php
session_start();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include 'sqlconnection.php'; // Include the database connection

// Implement basic brute force protection
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt'] = time();
}

// Reset attempts after 30 minutes
if ((time() - $_SESSION['last_attempt']) > 1800) {
    $_SESSION['login_attempts'] = 0;
}

if ($_SESSION['login_attempts'] >= 5) {
    $waitTime = 1800 - (time() - $_SESSION['last_attempt']);
    if ($waitTime > 0) {
        die("Too many login attempts. Please try again in " . ceil($waitTime/60) . " minutes.");
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $_SESSION['login_attempts']++;
    $_SESSION['last_attempt'] = time();

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Query to get admin details
    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE email = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if ($admin && password_verify($password, $admin['password'])) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_role'] = $admin['role'];

        // Only redirect to dashboard after successful login
        header("Location: admin_dash.php");
        exit();
    } else {
        $error = "Invalid email or password!";
        // Set a flag for JS to show SweetAlert
        $_SESSION['login_error'] = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Job Finder</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 1rem;
        }
        
        .login-container {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 8px 20px rgba(0,0,0,0.06);
            width: 100%;
            max-width: 380px;
            padding: 2rem;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .logo-container img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        h2 {
            color: #1e3a8a;
            font-size: 1.5rem;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .form-control {
            height: 45px;
            padding-left: 2.5rem;
            border: 1.5px solid #e5e7eb;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        
        .input-icon {
            position: absolute;
            left: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.9rem;
        }
        
        .input-with-icon {
            padding-left: 2.5rem;
        }
        
        .password-toggle {
            position: absolute;
            right: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            cursor: pointer;
            padding: 0.25rem;
            font-size: 1rem;
            z-index: 2;
        }
        
        .btn {
            height: 45px;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .btn-primary {
            background: #1e3a8a;
            border: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-container">
            <img src="img/aski_logo.jpg" alt="ASKI Logo">
        </div>
        <h2>Admin Login</h2>
        <p class="text-center text-muted mb-4">Please login to your admin account</p>
        
        <form id="loginForm" method="POST">
            <div class="form-group mb-3">
                <label for="username" class="form-label">Email Address</label>
                <div class="position-relative">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" class="form-control" id="username" name="username" placeholder="Enter your email" required>
                </div>
            </div>
            
            <div class="form-group mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="position-relative">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" class="form-control input-with-icon" id="password" name="password" placeholder="Enter your password" required>
                    <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </div>
            
            <div class="text-center mt-4">
                <a href="admin_registration.php" class="text-primary text-decoration-none">Create new admin account</a>
            </div>
        </form
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function (event) {
            event.preventDefault(); // Prevent immediate submission

            Swal.fire({
                title: 'Logging in...',
                text: 'Please wait...',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            setTimeout(() => {
                this.submit(); // Submit form after 3 seconds
            }, 3000);
        });

        // Password toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });

            // SweetAlert for login error
            <?php if (isset($_SESSION['login_error']) && $_SESSION['login_error']): ?>
            Swal.fire({
                icon: 'error',
                title: 'Login Failed',
                text: 'Invalid email or password!',
                confirmButtonColor: '#1e3a8a'
            });
            <?php unset($_SESSION['login_error']); endif; ?>
        });
    </script>
</body>
</html>
