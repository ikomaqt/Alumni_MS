<?php
// Include database connection
require_once 'sqlconnection.php';

session_start();

// Redirect if OTP not verified
if (!isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified']) {
    $_SESSION['reset_error'] = "Please verify your OTP first.";
    header("Location: forgot_password.php");
    exit();
}

// Clear previous messages
unset($_SESSION['reset_error']);
unset($_SESSION['reset_success']);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validate passwords
    if (empty($password) || empty($confirm_password)) {
        $_SESSION['reset_error'] = "Please fill in both password fields.";
    } elseif ($password !== $confirm_password) {
        $_SESSION['reset_error'] = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $_SESSION['reset_error'] = "Password must be at least 8 characters long.";
    } else {
        // Update password in database
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $_SESSION['reset_email']);
        
        if ($stmt->execute()) {
            // Set success message
            $_SESSION['reset_success'] = "Password has been reset successfully! You can now login with your new password.";
            
            // Clear OTP verification flag but keep success message
            unset($_SESSION['otp_verified']);
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_otp']);
            
            // Don't redirect - we'll show success on this page
        } else {
            $_SESSION['reset_error'] = "Failed to reset password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: #F5F5F5;
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: Arial, sans-serif;
        }
        .btn-primary {
            background-color: #1e3a8a;
            border-color: #1e3a8a;
        }
        .btn-primary:hover {
            background-color: #1a3377;
            border-color: #1a3377;
        }
        .btn-outline-primary {
            color: #1e3a8a;
            border-color: #1e3a8a;
        }
        .btn-outline-primary:hover {
            background-color: #f8f9fa;
        }
        .text-center {
            text-align: center;
        }
        .mb-4 {
            margin-bottom: 1.5rem;
        }
        .mt-2 {
            margin-top: 0.5rem;
        }
        .mt-3 {
            margin-top: 1rem;
        }
        .w-100 {
            width: 100%;
        }
        .text-decoration-none {
            text-decoration: none;
        }
        .container {
            max-width: 400px;
            border-radius: 20px;
            padding: 30px;
            box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
            background-color: white;
        }
        .form-control {
            margin-bottom: 10px;
            border-radius: 25px;
            border-color: #1e3a8a;
            padding: 10px 15px;
        }
        .form-btn {
            margin-bottom: 10px;
        }
        .btn {
            border-radius: 25px;
            width: 100%;
            padding: 10px;
        }
        .password-field {
            position: relative;
            margin-bottom: 20px;
        }
        .password-field .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #777;
        }
        .validation-message {
            font-size: 14px;
            margin-top: 5px;
            text-align: left;
        }
        .validation-message.valid {
            color: #2ecc71;
        }
        .validation-message.invalid {
            color: #e74c3c;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .password-rules {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 15px;
            text-align: center;
        }
        .success-message {
            text-align: center;
            margin: 20px 0;
        }
        .success-message p {
            color: green;
            font-weight: bold;
        }
        .login-link {
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container d-flex flex-column align-items-center">
        <div class="text-center mb-4">
            <img src="img/aski_logo.jpg" alt="header" width="100">
        </div>
        <h2 class="text-center mb-4">Reset Your Password</h2>
        
        <?php if (!isset($_SESSION['reset_success'])): ?>
        <form method="post" class="w-100" id="resetForm">
            <div class="password-field">
                <label for="password">New Password</label>
                <div style="position: relative;">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Enter new password" required minlength="8">
                    <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                </div>
                <span id="password-validation" class="validation-message"></span>
            </div>

            <div class="password-field">
                <label for="confirm_password">Confirm Password</label>
                <div style="position: relative;">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                           placeholder="Confirm new password" required minlength="8">
                    <i class="fas fa-eye toggle-password" id="toggleConfirmPassword"></i>
                </div>
                <div class="password-rules">Password must be at least 8 characters long</div>
                <span id="confirm-password-validation" class="validation-message"></span>
            </div>
            
            <div class="button-group">
                <button type="submit" name="reset_password" class="btn btn-primary">Reset Password</button>
                <a href="login.php" class="btn btn-outline-primary">Cancel</a>
            </div>
        </form>
        <?php else: ?>
            <div class="success-message">
                <p><?= htmlspecialchars($_SESSION['reset_success']) ?></p>
                <div class="login-link">
                    <a href="login.php">Click here to login</a>
                </div>
            </div>
            <?php unset($_SESSION['reset_success']); ?>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle password visibility
        const togglePassword = document.querySelector('#togglePassword');
        const toggleConfirmPassword = document.querySelector('#toggleConfirmPassword');
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');

        if (togglePassword && toggleConfirmPassword) {
            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.classList.toggle('fa-eye-slash');
            });

            toggleConfirmPassword.addEventListener('click', function() {
                const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmPassword.setAttribute('type', type);
                this.classList.toggle('fa-eye-slash');
            });
        }

        // Password validation
        const passwordValidation = document.querySelector('#password-validation');
        const confirmPasswordValidation = document.querySelector('#confirm-password-validation');

        if (password && confirmPassword) {
            password.addEventListener('input', function() {
                if (this.value.length >= 8) {
                    passwordValidation.textContent = 'Password is valid';
                    passwordValidation.className = 'validation-message valid';
                } else {
                    passwordValidation.textContent = 'Password must be at least 8 characters';
                    passwordValidation.className = 'validation-message invalid';
                }
            });

            confirmPassword.addEventListener('input', function() {
                if (this.value === password.value && this.value.length >= 8) {
                    confirmPasswordValidation.textContent = 'Passwords match';
                    confirmPasswordValidation.className = 'validation-message valid';
                } else {
                    confirmPasswordValidation.textContent = 'Passwords do not match';
                    confirmPasswordValidation.className = 'validation-message invalid';
                }
            });
        }

        <?php if (!empty($_SESSION['reset_error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '<?php echo $_SESSION['reset_error']; ?>',
                confirmButtonColor: '#3085d6'
            });
            <?php unset($_SESSION['reset_error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['reset_success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: <?= json_encode($_SESSION['reset_success']) ?>,
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Go to Login'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'login.php';
                }
            });
            <?php unset($_SESSION['reset_success']); ?>
        <?php endif; ?>

        // Form submission validation
        const form = document.getElementById('resetForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (password.value !== confirmPassword.value) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Passwords do not match',
                        confirmButtonColor: '#3085d6'
                    });
                    confirmPassword.focus();
                }
                
                if (password.value.length < 8) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Password must be at least 8 characters',
                        confirmButtonColor: '#3085d6'
                    });
                    password.focus();
                }
            });
        }

        // Prevent form resubmission on refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    });
    </script>
</body>
</html>