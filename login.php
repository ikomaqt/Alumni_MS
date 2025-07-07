<?php
session_start();
include('sqlconnection.php'); // Database connection

// Handle login form submission
if (isset($_POST["login"])) {
    $email = $_POST["email"];
    $password = $_POST["password"];
    $errors = array();

    // Validate input fields
    if (empty($email) || empty($password)) {
        array_push($errors, "ALL FIELDS ARE REQUIRED");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        array_push($errors, "EMAIL IS NOT VALID");
    }
    if (strlen($password) < 8) {
        array_push($errors, "Password is too short");
    }

    // If there are no errors, check the database for the user
    if (count($errors) === 0) {
        $email = mysqli_real_escape_string($conn, $email);
        $password_input = $password; // Save original password for verification
        $password = mysqli_real_escape_string($conn, $password);

        // Query the database for the user
        $sql = "SELECT * FROM users WHERE email='$email' LIMIT 1";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);

            // Check if the account is deactivated during login
            if ($user['acc_status'] === 'Deactivated') {
                // Show SweetAlert and stop further processing
                $_SESSION['errors'] = array("Your account has been deactivated by the admin. Please contact us for further assistance:\n\nEmail: askiassessmentcenter@gmail.com\nMobile: (+63) 997 698 6046\nTelephone: (+63) 944 940 1800");
                header("Location: login.php");
                exit;
            }

            // Prevent deactivated users from logging in
            if ($user['status'] === 'Deactivated') {
                array_push($errors, "Your account has been deactivated by the admin.");
            } else if ($user['role'] === 'Pending') {
                array_push($errors, "Your account is pending approval. Please wait for admin confirmation.");
            } else if ($user['role'] === 'Rejected') {
                array_push($errors, "Your account has been rejected. Please contact administrator.");
            } else {
                // Verify password
                if (password_verify($password_input, $user['password'])) {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['email'] = $user['email'];
                    header("Location: home.php");
                    exit();
                } else {
                    array_push($errors, "Incorrect password");
                }
            }
        } else {
            array_push($errors, "User not found");
        }
    }

    // Store errors in session
    $_SESSION['errors'] = $errors;
    header("Location: login.php"); // Redirect back to login with errors
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Job Finder</title>
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
        
        /* Hide default password toggle */
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear,
        input[type="password"]::-webkit-contacts-auto-fill-button,
        input[type="password"]::-webkit-credentials-auto-fill-button {
            display: none !important;
        }
        
        .password-toggle {
            position: absolute;
            right: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            cursor: pointer;
            padding: 0.25rem;
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
        
        .forgot-password {
            font-size: 0.9rem;
            text-align: center;
            margin-top: 1rem;
        }
        
        .forgot-password a {
            color: #1e3a8a;
            text-decoration: none;
        }
        
        .password-error {
            color: #dc2626;
            font-size: 0.8rem;
            margin-top: 0.25rem;
            display: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-container">
            <img src="img/aski_logo.jpg" alt="ASKI Logo">
        </div>
        <h2>Welcome Back</h2>
        <form action="login.php" method="post">
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <div class="position-relative">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" class="form-control input-with-icon" id="email" name="email" placeholder="example@gmail.com" required>
                </div>
            </div>
            <div class="form-group password-container">
                <label for="password" class="form-label">Password</label>
                <div class="position-relative">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" class="form-control input-with-icon" id="password" name="password" placeholder="Enter your password" required>
                    <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                </div>
                <div id="passwordError" class="password-error">Password must be at least 8 characters long.</div>
            </div>
            <div class="form-btn mt-4">
                <input type="submit" class="btn btn-primary w-100" id="submitBtn" name="login" value="Sign In">
            </div>
            <div class="divider">
                <span>or</span>
            </div>
            <div class="form-btn">
                <a href="registration.php" class="btn btn-outline-primary w-100">Create an Account</a>
            </div>
            <div class="forgot-password">
                <a href="forgot_password.php">Forgot Password?</a>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const submitBtn = document.getElementById('submitBtn');
            const passwordError = document.getElementById('passwordError');
            const togglePassword = document.getElementById('togglePassword');

            // Toggle password visibility
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });

            // Form submission validation
            document.querySelector('form').addEventListener('submit', function(e) {
                const password = passwordInput.value.trim();
                
                if (password.length < 8) {
                    e.preventDefault(); // Prevent form submission
                    passwordError.style.display = 'block';
                } else {
                    passwordError.style.display = 'none';
                }
            });

            // Remove the checkInputs function and its event listeners since we're validating on submit
            submitBtn.disabled = false;
        });
        
        // Display errors using SweetAlert
        <?php
        if (isset($_SESSION['errors']) && count($_SESSION['errors']) > 0) {
            foreach ($_SESSION['errors'] as $error) {
                // Escape newlines for JS
                $error_js = str_replace(array("\r", "\n"), array('', '\\n'), addslashes($error));
                echo "Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    html: '".nl2br($error_js)."',
                });";
            }
            unset($_SESSION['errors']); // Clear errors after displaying
        }
        ?>
    </script>
</body>
</html>
