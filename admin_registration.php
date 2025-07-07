<?php
include 'sqlconnection.php'; // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    // Basic validation
    if (empty($name) || empty($email) || empty($password)) {
        echo "<script>alert('All fields are required!');</script>";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT * FROM admin_users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo "<script>alert('Email already exists!');</script>";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Set default profile picture path
            $default_profile_pic = 'profile_img/aski_default.jpg'; // Replace with your default image path
            
            // Insert new admin
            $role = 'admin'; // Set role to 'admin' by default
            $stmt = $conn->prepare("INSERT INTO admin_users (name, email, password, role, profile_pic, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->bind_param("sssss", $name, $email, $hashed_password, $role, $default_profile_pic);
            
            if ($stmt->execute()) {
                echo "<script>alert('Registration successful!'); window.location='admin_login.php';</script>";
            } else {
                echo "<script>alert('Registration failed!');</script>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration | Job Finder</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        
        .divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #94a3b8;
            margin: 1rem 0;
        }
        
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-container">
            <img src="img/aski_logo.jpg" alt="ASKI Logo">
        </div>
        <h2>Create Admin Account</h2>
        <p class="text-center text-muted mb-4">Fill in the details to register as an admin</p>
        <form method="POST">
            <div class="form-group mb-3">
                <label for="name" class="form-label">Full Name</label>
                <div class="position-relative">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter your full name" required>
                </div>
            </div>
            
            <div class="form-group mb-3">
                <label for="email" class="form-label">Email Address</label>
                <div class="position-relative">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                </div>
            </div>
            
            <div class="form-group mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="position-relative">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" class="form-control input-with-icon" id="password" name="password" placeholder="Create a password" required>
                    <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                </div>
                <div class="form-text">Password must be at least 8 characters long</div>
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary w-100">Register Account</button>
            </div>
            
            <div class="divider">
                <span>or</span>
            </div>
            
            <div class="text-center mt-3">
                <a href="admin_login.php" class="btn btn-outline-primary w-100">Return to Login</a>
            </div>
        </form    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const passwordInput = document.getElementById('password');
            
            form.addEventListener('submit', function(e) {
                if (passwordInput.value.length < 8) {
                    e.preventDefault();
                    alert('Password must be at least 8 characters long');
                }
            });

            // Password toggle functionality
            const togglePassword = document.getElementById('togglePassword');
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>
</html>
