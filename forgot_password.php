<?php
// Start session at the very beginning
session_start();

// Include database connection and PHPMailer
require 'sqlconnection.php';
require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

function generateOTP() {
    return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Clear any previous reset session data
if (!isset($_POST['reset_password'])) {
    unset($_SESSION['reset_otp']);
    unset($_SESSION['reset_email']);
    unset($_SESSION['reset_expiry']);
    unset($_SESSION['otp_attempts']);
    unset($_SESSION['reset_success']);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $email = sanitizeInput($_POST['email']);

    if (empty($email)) {
        $_SESSION['reset_error'] = "Please enter your email address.";
        header("Location: forgot_password.php");
        exit();
    }

    // Check if email exists
    $stmt = $conn->prepare("SELECT user_id, first_name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows != 1) {
        $_SESSION['reset_error'] = "No account found with that email address.";
        header("Location: forgot_password.php");
        exit();
    }

    $user = $result->fetch_assoc();
    $otp = generateOTP();
    $expiry = time() + 60; // 1 minute expiry
    
    // Store OTP in session
    $_SESSION['reset_otp'] = password_hash($otp, PASSWORD_DEFAULT);
    $_SESSION['reset_email'] = $email;
    $_SESSION['reset_expiry'] = $expiry;
    $_SESSION['otp_attempts'] = 0; // Track OTP attempts
    $_SESSION['reset_success'] = "OTP has been sent to your email address."; // Success message

    // Send OTP email using PHPMailer
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'nesrac22@gmail.com';
        $mail->Password   = 'cegq qqrk jjdw xwbs';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        
        // Recipients
        $mail->setFrom('no-reply@yourdomain.com', 'Health Record System');
        $mail->addAddress($email, $user['first_name']);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Password Reset OTP';
        $mail->Body    = "
            <h2>Password Reset OTP</h2>
            <p>Hello {$user['first_name']},</p>
            <p>Your OTP for password reset is: <strong>$otp</strong></p>
            <p>This OTP is valid for 1 minute.</p>
            <p>If you didn't request this, please ignore this email.</p>
        ";
        
        $mail->send();
        // Don't redirect here - let the JavaScript handle it after showing the success message
    } catch (Exception $e) {
        // Clear session variables if email fails
        unset($_SESSION['reset_otp']);
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_expiry']);
        unset($_SESSION['otp_attempts']);
        
        error_log("Mailer Error: " . $mail->ErrorInfo);
        $_SESSION['reset_error'] = "Failed to send OTP. Please try again later.";
        header("Location: forgot_password.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
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
            color: #1e3a8a;
            border-color: #d3d3d3;
        }
        .container {
            max-width: 400px;
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
        body {
        padding-right: 10px;
        padding: 30px;
        padding-top: 60px;
        margin: 0;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        font-family: Arial, sans-serif;
    }

    .container {
        max-width: 350px;
        border-radius: 20px;
        padding: 30px; /* Reduced padding */
        box-shadow: rgba(100, 100, 111, 1.2) 0px 7px 29px 0px;
        background-color: white;
    }

    .form-control {
        margin-bottom: -15px; /* Reduced margin */
        border-radius: 25px;
        border-color: blue;
    }

    .form-btn {
        margin-bottom: 10px; /* Reduced margin */
    }

    .btn {
        border-radius: 25px;
        width: 100%; /* Ensure buttons take full width */
    }

    .text-center {
        margin-bottom: 20px; /* Adjusted margin */
    }

    .password-error {
        color: red;
        font-size: 0.875em;
        display: none; /* Hidden by default */
    }
    </style>
</head>
<body>
    <div class="container d-flex flex-column align-items-center">
        <div class="text-center mb-4">
            <img src="img/aski_logo.jpg" alt="header" width="100">
        </div>
        <h2 class="text-center mb-4">Forgot Password</h2>
        <form method="post" class="w-100" id="forgotForm">
            <div class="form-group">
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <br>
            <div class="form-btn mt-2">
                <button type="submit" name="reset_password" class="btn btn-primary w-100">Send OTP</button>
            </div>
            <div class="mt-3 text-center">
                <a href="login.php" class="text-decoration-none">Back to Login</a>
            </div>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($_SESSION['reset_error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: <?= json_encode($_SESSION['reset_error']) ?>,
                confirmButtonColor: '#3085d6'
            });
            <?php unset($_SESSION['reset_error']); ?>
        <?php elseif (!empty($_SESSION['reset_success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: <?= json_encode($_SESSION['reset_success']) ?>,
                confirmButtonColor: '#3085d6',
                showCancelButton: false,
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'verify_otp.php';
                }
            });
            <?php unset($_SESSION['reset_success']); ?>
        <?php endif; ?>
    });
    </script>
</body>
</html>