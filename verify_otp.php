<?php
session_start();

if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_otp'])) {
    header("Location: forgot_password.php");
    exit();
}

// Check if OTP has expired
if (time() > $_SESSION['reset_expiry']) {
    unset($_SESSION['reset_otp']);
    unset($_SESSION['reset_email']);
    unset($_SESSION['reset_expiry']);
    $_SESSION['reset_error'] = "OTP has expired. Please request a new one.";
    header("Location: forgot_password.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_otp'])) {
    $user_otp = $_POST['otp'];
    
    // Verify OTP
    if (password_verify($user_otp, $_SESSION['reset_otp'])) {
        $_SESSION['otp_verified'] = true;
        $_SESSION['otp_success'] = "OTP verified successfully!";
        // Don't redirect here - let JavaScript handle it after showing success message
    } else {
        $_SESSION['otp_attempts']++;
        
        if ($_SESSION['otp_attempts'] >= 3) {
            unset($_SESSION['reset_otp']);
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_expiry']);
            $_SESSION['reset_error'] = "Too many failed attempts. Please request a new OTP.";
            header("Location: forgot_password.php");
            exit();
        }
        
        $_SESSION['otp_error'] = "Invalid OTP. You have " . (3 - $_SESSION['otp_attempts']) . " attempts remaining.";
    }
}

if (isset($_GET['resend_otp'])) {
    // Generate a new OTP
    $new_otp = rand(100000, 999999);
    $_SESSION['reset_otp'] = password_hash($new_otp, PASSWORD_DEFAULT);
    $_SESSION['reset_expiry'] = time() + 300; // OTP valid for 5 minutes

    // Send the new OTP to the user's email
    if (isset($_SESSION['reset_email'])) {
        $to = $_SESSION['reset_email'];
        $subject = "Your New OTP";
        $message = "Your new OTP is: $new_otp";
        $headers = "From: no-reply@yourdomain.com";

        mail($to, $subject, $message, $headers);

        $_SESSION['otp_success'] = "A new OTP has been sent to your email.";
    } else {
        $_SESSION['reset_error'] = "Email not found in session. Please try again.";
    }

    header("Location: verify_otp.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
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
            padding: 30px;
            box-shadow: rgba(100, 100, 111, 1.2) 0px 7px 29px 0px;
            background-color: white;
        }
        .form-control {
            margin-bottom: -15px;
            border-radius: 25px;
            border-color: blue;
        }
        .form-btn {
            margin-bottom: 10px;
        }
        .btn {
            border-radius: 25px;
            width: 100%;
        }
        .text-center {
            margin-bottom: 20px;
        }
        .form-footer {
            text-align: center;
            margin-top: 10px;
        }
        .form-footer div {
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container d-flex flex-column align-items-center">
        <div class="text-center mb-4">
            <img src="img/aski_logo.jpg" alt="header" width="100">
        </div>
        <h2 class="text-center mb-4">Verify OTP</h2>
        <p class="text-center">We've sent a 6-digit OTP to your email</p>
        
        <form method="post" class="w-100">
            <div class="form-group">
                <input type="text" class="form-control" id="otp" name="otp" placeholder="Enter 6-digit OTP" required
                       maxlength="6" pattern="\d{6}" title="Please enter a 6-digit number">
            </div>
            <br>
            <div class="form-btn mt-2">
                <button type="submit" name="verify_otp" class="btn btn-primary w-100">Verify OTP</button>
            </div>
            <div class="form-footer">
            <a href="verify_otp.php?resend_otp=1">Request new OTP</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($_SESSION['otp_error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: <?= json_encode($_SESSION['otp_error']) ?>,
                confirmButtonColor: '#3085d6'
            });
            <?php unset($_SESSION['otp_error']); ?>
        <?php elseif (!empty($_SESSION['otp_success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: <?= json_encode($_SESSION['otp_success']) ?>,
                confirmButtonColor: '#3085d6',
                showCancelButton: false,
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    <?php if (isset($_SESSION['otp_verified'])): ?>
                        window.location.href = 'reset_password.php';
                    <?php endif; ?>
                }
            });
            <?php unset($_SESSION['otp_success']); ?>
        <?php endif; ?>
    });

    document.addEventListener('DOMContentLoaded', function() {
        let expiryTime = <?= json_encode($_SESSION['reset_expiry'] ?? 0) ?>;
        let timerElement = document.createElement('div');
        timerElement.style.marginTop = '10px';
        timerElement.style.fontWeight = 'bold';
        document.querySelector('.form-footer').appendChild(timerElement);

        function updateTimer() {
            let currentTime = Math.floor(Date.now() / 1000);
            let remainingTime = expiryTime - currentTime;

            if (remainingTime > 0) {
                let minutes = Math.floor(remainingTime / 60);
                let seconds = remainingTime % 60;
                timerElement.textContent = `OTP expires in: ${minutes}:${seconds.toString().padStart(2, '0')}`;
            } else {
                timerElement.textContent = 'OTP has expired. Please request a new one.';
                document.querySelector('button[name="verify_otp"]').disabled = true;
            }
        }

        setInterval(updateTimer, 1000);
        updateTimer();
    });
    </script>
</body>
</html>