<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Add these functions at the top before the main registration code
function checkEmail($email, $conn) {
    $query = "SELECT email FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return ['exists' => true, 'message' => 'Email address is already registered'];
    }
    return ['exists' => false];
}

function getGraduationYears($conn) {
    $query = "SELECT file_name FROM alumni_data";
    $result = $conn->query($query);
    $years = array();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if (preg_match('/\b(20\d{2})[-_\s]+(20\d{2})\b/', $row['file_name'], $matches)) {
                $yearRange = $matches[1] . '-' . $matches[2];
                $years[] = $yearRange;
            }
        }
    }
    
    $years = array_unique($years);
    rsort($years);
    return $years;
}

function checkLRN($lrn, $conn) {
    // Check if LRN exists in the users table
    $query = "SELECT lrn FROM users WHERE lrn = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $lrn);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return ['exists' => true, 'message' => 'This LRN is already registered in our system'];
    }

    // Check if LRN exists in alumni_data
    $query = "SELECT sheet_data FROM alumni_data";
    $result = $conn->query($query);
    $found = false;
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $sheetData = json_decode($row['sheet_data'], true);
            foreach ($sheetData as $record) {
                if (isset($record['LRN']) && trim($record['LRN']) === trim($lrn)) {
                    $found = true;
                    break 2;
                }
            }
        }
    }
    
    if ($found) {
        return ['exists' => true, 'message' => 'LRN verified'];
    }
    
    return ['exists' => false, 'message' => 'LRN not found in alumni records'];
}

function getGraduationYearByLRN($lrn, $conn) {
    $query = "SELECT file_name, sheet_data FROM alumni_data";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $sheetData = json_decode($row['sheet_data'], true);
            foreach ($sheetData as $record) {
                if (isset($record['LRN']) && trim($record['LRN']) === trim($lrn)) {
                    // Extract year range from file_name
                    if (preg_match('/\b(20\d{2})[-_\s]+(20\d{2})\b/', $row['file_name'], $matches)) {
                        $yearRange = $matches[1] . '-' . $matches[2];
                        return ['success' => true, 'year' => $yearRange];
                    }
                }
            }
        }
    }
    return ['success' => false, 'message' => 'Graduation year not found for this LRN'];
}

// Update the AJAX handlers section
if (isset($_POST['action']) || isset($_GET['action'])) {
    include 'sqlconnection.php';
    $action = $_POST['action'] ?? $_GET['action'];
    
    switch ($action) {
        case 'check_email':
            $email = $_POST['email'];
            echo json_encode(checkEmail($email, $conn));
            exit;
            
        case 'check_lrn':
            $lrn = $_POST['lrn'];
            $result = checkLRN($lrn, $conn);
            echo json_encode($result);
            exit;
            
        case 'get_years':
            $years = getGraduationYears($conn);
            echo json_encode(['success' => true, 'years' => $years]);
            exit;

        case 'send_otp':
            session_start(); // Ensure session is started
            $email = $_POST['email'];
            $otp = rand(100000, 999999);
            $_SESSION['otp'] = $otp;
            $_SESSION['otp_expiry'] = time() + 180; // 3 minutes
            $_SESSION['otp_email'] = $email;

            // Debug log
            error_log("Generated OTP: " . $otp . ", Expiry: " . $_SESSION['otp_expiry']);

            // Send OTP via email using PHPMailer
            require 'vendor/phpmailer/phpmailer/src/Exception.php';
            require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
            require 'vendor/phpmailer/phpmailer/src/SMTP.php';
            
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'nesrac22@gmail.com';
                $mail->Password = 'cegq qqrk jjdw xwbs';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                
                $mail->setFrom('nesrac22@gmail.com', 'Alumni MS');
                $mail->addAddress($email);
                
                $mail->isHTML(true);
                $mail->Subject = 'Your OTP for Email Verification';
                $mail->Body = "Your OTP code is: <strong>$otp</strong><br><brThis code will expire in 3 minutes.";
                
                $mail->send();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to send OTP']);
            }
            exit;

        case 'verify_otp':
            session_start();
            $user_otp = $_POST['otp'];
            
            // Debug log
            error_log("Session OTP: " . $_SESSION['otp'] . ", User OTP: " . $user_otp . ", Expiry: " . $_SESSION['otp_expiry'] . ", Current time: " . time());
            
            if (!isset($_SESSION['otp']) || !isset($_SESSION['otp_expiry'])) {
                echo json_encode(['success' => false, 'message' => 'No OTP found. Please request a new one.']);
            } elseif (time() > $_SESSION['otp_expiry']) {
                unset($_SESSION['otp']);
                unset($_SESSION['otp_expiry']);
                echo json_encode(['success' => false, 'message' => 'OTP has expired. Please request a new one.']);
            } elseif ($user_otp != $_SESSION['otp']) {
                echo json_encode(['success' => false, 'message' => 'Invalid OTP. Please try again.']);
            } else {
                // Set verification flag and clear OTP data
                $_SESSION['otp_verified'] = true;
                unset($_SESSION['otp']);
                unset($_SESSION['otp_expiry']);
                echo json_encode(['success' => true]);
            }
            exit;

        case 'get_year_by_lrn':
            $lrn = $_POST['lrn'];
            echo json_encode(getGraduationYearByLRN($lrn, $conn));
            exit;
    }
}

// Update the POST request handling to stop if LRN exists
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['action'])) {
    include 'sqlconnection.php'; // Database connection
    session_start();
    
    // Check if OTP was verified
    if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Email verification required']);
        exit;
    }

    $lrn = $_POST['lrn'];
    $lrn_check = checkLRN($lrn, $conn);

    if ($lrn_check['exists'] && $lrn_check['message'] === 'This LRN is already registered in our system') {
        echo json_encode(['success' => false, 'message' => $lrn_check['message']]);
        exit;
    }

    // Get all form fields
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'] ?? NULL;
    $last_name = $_POST['last_name'];
    $gender = $_POST['gender'];
    $birthdate = $_POST['birthdate'];
    $graduation_year = $_POST['graduation_year'];
    $employment_status = $_POST['employment_status'] ?? 'pending';
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $address = $_POST['address'];
    $skills = $_POST['skills'];
    $work_experience = $_POST['work_experience'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'Pending'; // Initial role is Pending
    $profile_img = 'profile_img/user_default.png'; // Set default profile image

    // Handle resume upload
    $resume_file = NULL;
    if (!empty($_FILES['resume_file']['name'])) {
        $resume_name = time() . '_' . $_FILES['resume_file']['name'];
        $resume_tmp = $_FILES['resume_file']['tmp_name'];
        $resume_destination = 'uploads/resumes/' . $resume_name;
        if (move_uploaded_file($resume_tmp, $resume_destination)) {
            $resume_file = $resume_destination;
        }
    }

    // After handling file uploads, insert into database
    $created_at = date('Y-m-d H:i:s');
    $last_updated = $created_at;

    $query = "INSERT INTO users (lrn, first_name, middle_name, last_name, gender, 
            birthdate, graduation_year, email, phone_number, address, skills, 
            work_experience, resume_file, profile_img, role, created_at, 
            employment_status, last_updated, username, password, acc_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssssssssssssssssss", 
        $lrn, 
        $first_name, 
        $middle_name, 
        $last_name, 
        $gender,
        $birthdate, 
        $graduation_year, 
        $email, 
        $phone_number, 
        $address,
        $skills, 
        $work_experience, 
        $resume_file, 
        $profile_img, 
        $role, 
        $created_at, 
        $employment_status,
        $last_updated,
        $username,
        $password
    );

    if ($stmt->execute()) {
        // Clear session data after successful registration
        unset($_SESSION['otp_verified']);
        unset($_SESSION['pending_user']);
        echo json_encode(['success' => true, 'message' => 'Registration successful']);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $conn->error]);
        exit;
    }

    // Store all data in session for OTP verification
    session_start();
    $_SESSION['pending_user'] = [
        'lrn' => $lrn,
        'first_name' => $first_name,
        'middle_name' => $middle_name,
        'last_name' => $last_name,
        'gender' => $gender,
        'birthdate' => $birthdate,
        'graduation_year' => $graduation_year,
        'employment_status' => $employment_status,
        'email' => $email,
        'phone_number' => $phone_number,
        'address' => $address,
        'skills' => $skills,
        'work_experience' => $work_experience,
        'username' => $username,
        'password' => $password,
        'resume_file' => $resume_file,
        'profile_img' => $profile_img,
        'role' => $role
    ];

    // Send OTP email using PHPMailer
    require 'vendor/phpmailer/phpmailer/src/Exception.php';
    require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require 'vendor/phpmailer/phpmailer/src/SMTP.php';
    
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'nesrac22@gmail.com';
        $mail->Password   = 'cegq qqrk jjdw xwbs';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('nesrac22@gmail.com', 'Alumni MS');
        $mail->addAddress($email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP for Email Verification';
        $mail->Body    = "Your OTP code is: <strong>$otp</strong><br><brThis code will expire in 3 minutes.";
        $mail->AltBody = "Your OTP code is: $otp\n\nThis code will expire in 3 minutes.";

        $mail->send();
        // Redirect to OTP verification page
        header("Location: verify_otp.php");
        exit();
    } catch (Exception $e) {
        // If email fails, show error but keep the form data
        $error_message = "We couldn't send the OTP email. Please try again or contact support.";
        error_log("Mailer Error: {$mail->ErrorInfo}");
    }
}
?>      
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration Form</title>
    <!-- Add SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="css/reg.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .otp-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }
        .otp-input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            border: 2px solid #ddd;
            border-radius: 8px;
            margin: 0 4px;
            transition: border-color 0.3s;
        }
        .otp-input:focus {
            border-color: #4361ee;
            outline: none;
            box-shadow: 0 0 5px rgba(67, 97, 238, 0.3);
        }
        .otp-input.filled {
            border-color: #4361ee;
            background-color: #f8f9ff;
        }
        .resend-otp {
            color: #4361ee;
            text-decoration: underline;
            cursor: pointer;
            margin-top: 15px;
            display: inline-block;
        }
        .resend-otp.disabled {
            color: #999;
            cursor: not-allowed;
            text-decoration: none;
            pointer-events: none;
        }
        .resend-timer {
            color: #666;
            font-size: 14px;
            margin-left: 5px;
        }
        
        .timer {
            color: #666;
            margin-top: 10px;
            font-size: 14px;
        }
        .close-btn {
            position: absolute;
            right: 15px;
            top: 15px;
            font-size: 20px;
            cursor: pointer;
            color: #666;
            transition: color 0.3s;
        }
        .close-btn:hover {
            color: #000;
        }
        /* Custom OTP Popup Styles */
        .custom-popup {
            background: #fff;
            border-radius: 12px;
            padding: 18px; /* reduced from 30px */
            max-width: 340px; /* reduced from 450px */
            width: 98%;
        }
        .custom-popup .swal2-title {
            color: #333;
            font-size: 1.1rem; /* reduced from 1.5rem */
            font-weight: 600;
            margin-bottom: 12px; /* reduced */
        }
        .verification-message {
            font-size: 14px;
            color: #555;
            margin-bottom: 12px;
            word-break: break-word;
        }
        .custom-popup .otp-input-container {
            display: flex;
            justify-content: center;
            gap: 4px; /* reduced from 8px */
            margin: 15px 0; /* reduced from 25px */
        }
        .custom-popup .otp-digit {
            width: 28px;
            height: 38px;
            border: 2px solid #4361ee;
            border-radius: 8px;
            font-size: 16px;
            text-align: center;
            margin: 0 1px;
            transition: all 0.3s;
            -webkit-appearance: none;
            appearance: none;
            color: #000;
            background-color: #fff;
            font-weight: 700;
            caret-color: #4361ee;
        }
        .custom-popup .otp-digit:focus {
            border-color: #3451db;
            box-shadow: 0 0 5px rgba(67, 97, 238, 0.5);
            outline: none;
            background-color: #f8f9ff;
        }
        .custom-popup .verify-btn {
            min-width: 120px !important;
            padding: 8px 24px !important;
            font-size: 14px !important;
            background-color: #4361ee !important;
            color: white !important;
            border: none !important;
            border-radius: 6px !important;
            font-weight: 500 !important;
        }
        .custom-popup .verify-btn:disabled {
            background-color: #a0a0a0 !important;
            cursor: not-allowed !important;
        }
        .custom-popup .cancel-btn {
            min-width: 120px !important;
            padding: 8px 24px !important;
            font-size: 14px !important;
            background-color: #e2e8f0 !important;
            color: #475569 !important;
            border: none !important;
            border-radius: 6px !important;
            font-weight: 500 !important;
        }
        .swal2-popup {
            padding: 1.2em; /* reduced from 2em */
            width: 20em; /* reduced from 36em */
            max-width: 98vw;
        }
        /* Responsive adjustments for Samsung S21 FE and similar small screens */
        @media screen and (max-width: 430px) {
            .custom-popup {
                padding: 10px;
                max-width: 98vw;
            }
            .custom-popup .swal2-title {
                font-size: 1rem;
                margin-bottom: 8px;
            }
            .verification-message {
                font-size: 13px;
                margin-bottom: 10px;
            }
            .custom-popup .otp-input-container {
                gap: 2px;
                margin: 10px 0;
            }
            .custom-popup .otp-digit {
                width: 24px;
                height: 34px;
                font-size: 16px;
                margin: 0 1px;
                border: 2px solid #4361ee;
                color: #000 !important;
                background-color: #fff !important;
                font-weight: 800 !important;
                -webkit-text-fill-color: #000 !important;
                /* Force text to be visible */
                text-shadow: 0 0 0 #000;
            }
            .custom-popup .verify-btn {
                padding: 7px 12px;
                font-size: 0.9rem;
            }
            .custom-popup .resend-otp {
                font-size: 12px;
            }
            .custom-popup .resend-timer {
                font-size: 11px;
            }
            .swal2-popup {
                padding: 0.7em;
                width: 98vw;
                max-width: 320px;
            }
        }
        .password-container {
        position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            z-index: 2;
        }
        .input-wrapper {
            position: relative;
        }
        .input-wrapper input {
            width: 100%;
            padding-right: 30px; /* Space for the eye icon */
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }

        /* Add these styles for password input fields */
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

        .input-with-icon {
            padding-left: 2.5rem;
        }

        /* Password field styling for registration (match login.php) */
        .form-control.input-with-icon {
            height: 45px;
            padding-left: 2.5rem;
            padding-right: 2.5rem;
            border: 1.5px solid #e5e7eb;
            transition: all 0.2s;
            font-size: 1rem;
            box-sizing: border-box;
        }
        .form-control.input-with-icon:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .input-icon {
            position: absolute;
            left: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1rem;
            pointer-events: none;
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
        .position-relative {
            position: relative;
        }

        /* Fix OTP input visibility */
    .otp-digit {
        width: 40px !important;
        height: 45px !important;
        padding: 5px !important;
        font-size: 20px !important;
        text-align: center !important;
        border: 2px solid #4361ee !important;
        border-radius: 8px !important;
        margin: 0 5px !important;
        background-color: white !important;
        color: black !important;
        -webkit-appearance: none !important;
        -moz-appearance: textfield !important;
        appearance: none !important;
    }

    .otp-digit:focus {
        outline: none !important;
        border-color: #1d3fdb !important;
        box-shadow: 0 0 5px rgba(67, 97, 238, 0.3) !important;
    }

    /* Hide spinner buttons for number inputs */
    .otp-digit::-webkit-outer-spin-button,
    .otp-digit::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Update the OTP container and input styles */
    .custom-popup {
        width: 100%;
        max-width: 340px;
        margin: 0 auto;
        padding: 1.5rem 1.5rem 2rem;
        box-sizing: border-box;
        overflow: visible;
    }

    .otp-input-container {
        display: flex;
        justify-content: space-between;
        padding: 1rem 0.5rem;
        margin: 1rem auto;
        max-width: 300px;
    }

    .swal2-popup {
        padding: 0 !important;
        width: auto !important;
        min-width: 340px !important;
        max-width: 95vw !important;
        margin: 0 1rem !important;
    }

    .swal2-actions {
        padding: 0 1.5rem 1.5rem !important;
        justify-content: space-between !important;
        gap: 1rem !important;
        margin-top: 1rem !important;
    }

    .verify-btn, .cancel-btn {
        flex: 1 !important;
        min-width: 120px !important;
        margin: 0 !important;
    }

    .verification-message {
        padding: 0 0.5rem;
        text-align: center;
        margin: 1rem 0;
    }

    @media screen and (max-width: 430px) {
        .otp-digit {
            width: 30px !important;
            height: 35px !important;
            font-size: 16px !important;
        }

        .custom-popup {
            padding: 1rem;
        }

        .otp-input-container {
            gap: 4px;
        }
    }

    .custom-popup .swal2-actions {
    margin: 1.5rem auto 1rem !important;
    padding-bottom: 0.5rem;
}

.resend-section {
    text-align: center;
    margin-top: 1rem;
    margin-bottom: 0.5rem;
    font-size: 13px;
}

.resend-otp {
    color: #4361ee;
    text-decoration: underline;
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
    font: inherit;
    margin-right: 4px;
}

.resend-otp.disabled {
    color: #94a3b8;
    text-decoration: none;
    cursor: default;
}

.resend-timer {
    color: #64748b;
    font-size: 0.85em;
}
    </style>    
</head>
<body>
    <div class="container">
        <h1>Create Your Account</h1>
        <div class="form-container">
            <!-- ===== STEP INDICATOR HTML STRUCTURE - START ===== -->
            <!-- This is the main container for the step indicator -->
            <div class="step-indicator">
                <!-- This is the progress bar that fills as steps are completed -->
                <div class="step-progress" id="step-progress"></div>
                <!-- Step 1 -->
                <div class="step active" data-step="1">
                    <!-- EDIT THIS: You can change the content inside step-circle to customize the indicator -->
                    <div class="step-circle">1</div>
                    <div class="step-title">Personal Information</div>
                </div>
                <!-- Step 2 -->
                <div class="step" data-step="2">
                    <div class="step-circle">2</div>
                    <div class="step-title">Education & Contact</div>
                </div>
                <!-- Step 3 -->
                <div class="step" data-step="3">
                    <div class="step-circle">3</div>
                    <div class="step-title">Professional Details</div>
                </div>
                <!-- Step 4 -->
                <div class="step" data-step="4">
                    <div class="step-circle">4</div>
                    <div class="step-title">Account Setup</div>
                </div>
                <!-- Step 5 -->
                <div class="step" data-step="5">
                    <div class="step-circle">5</div>
                    <div class="step-title">File Upload</div>
                </div>
            </div>
            <!-- ===== STEP INDICATOR HTML STRUCTURE - END ===== -->
            <!-- Registration Form -->
            <form id="registration-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                <!-- Step 1: Personal Information -->
                <div class="form-step active" data-step="1">
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="first_name">First Name <span class="required">*</span></label>
                                <input type="text" id="first_name" name="first_name" placeholder="Enter your first name">
                                <div class="error-message" id="first_name-error">First name is required</div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="middle_name">Middle Name</label>
                                <input type="text" id="middle_name" name="middle_name" placeholder="Enter your middle name (optional)">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="last_name">Last Name <span class="required">*</span></label>
                                <input type="text" id="last_name" name="last_name" placeholder="Enter your last name">
                                <div class="error-message" id="last_name-error">Last name is required</div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Gender <span class="required">*</span></label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="gender-male" name="gender" value="male">
                                <label for="gender-male">Male</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="gender-female" name="gender" value="female">
                                <label for="gender-female">Female</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="gender-other" name="gender" value="other">
                                <label for="gender-other">Other</label>
                            </div>
                        </div>
                        <div class="error-message" id="gender-error">Please select a gender</div>
                    </div>

                    <div class="form-group">
                        <label for="birthdate">Birthdate <span class="required">*</span></label>
                        <input type="date" id="birthdate" name="birthdate">
                        <div class="error-message" id="birthdate-error">Birthdate is required</div>
                    </div>
                </div>
                <!-- Step 2: Education & Contact -->
                <div class="form-step" data-step="2">
                    <div class="form-group">
                        <label for="lrn">LRN (Learner Reference Number) <span class="required">*</span></label>
                        <input type="text" id="lrn" name="lrn" placeholder="Enter your 12-digit LRN">
                        <div class="error-message" id="lrn-error">Please enter a valid 12-digit LRN</div>
                    </div>
                    <div class="form-group">
                        <label for="graduation_year">Academic Year <span class="required">*</span></label>
                        <select id="graduation_year" name="graduation_year" readonly>
                            <option value="">Select academic year</option>
                        </select>
                        <div class="error-message" id="graduation_year-error">Please select your academic year</div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" placeholder="your.email@example.com">
                        <div class="error-message" id="email-error">Please enter a valid email address</div>
                    </div>
                    <div class="form-group">
                        <label for="phone_number">Phone Number <span class="required">*</span></label>
                        <input type="tel" id="phone_number" name="phone_number" placeholder="Enter your phone number">
                        <div class="error-message" id="phone_number-error">Please enter a valid phone number</div>
                    </div>
                </div>
                <!-- Step 3: Professional Details -->
                <div class="form-step" data-step="3">
                    <div class="form-group">
                        <label for="address">Address <span class="required">*</span></label>
                        <textarea id="address" name="address" rows="3" placeholder="Enter your full address"></textarea>
                        <div class="error-message" id="address-error">Address is required</div>
                    </div>
                    <div class="form-group">
                        <label for="employment_status">Employment Status <span class="required">*</span></label>
                        <select id="employment_status" name="employment_status">
                            <option value="">Select your employment status</option>
                            <option value="employed">Employed</option>
                            <option value="unemployed">Unemployed</option>
                            <option value="student">Student</option>
                        </select>
                        <div class="error-message" id="employment_status-error">Please select your employment status</div>
                    </div>
                    <div class="form-group">
                        <label for="skills">Skills <span class="required">*</span></label>
                        <textarea id="skills" name="skills" rows="3" placeholder="Enter your skills (e.g., JavaScript, Project Management, Communication)"></textarea>
                        <div class="error-message" id="skills-error">Please enter at least one skill</div>
                    </div>
                    <div class="form-group">
                        <label for="work_experience">Work Experience</label>
                        <textarea id="work_experience" name="work_experience" rows="3" placeholder="Describe your work experience (optional)"></textarea>
                    </div>
                </div>
                <!-- Step 4: Account Setup -->
                <div class="form-step" data-step="4">
                    <div class="form-group">
                        <label for="username">Username <span class="required">*</span></label>
                        <input type="text" id="username" name="username" placeholder="Choose a username">
                        <div class="error-message" id="username-error">Username must be at least 4 characters</div>
                    </div>
                    <div class="form-group password-container">
                        <label for="password">Password <span class="required">*</span></label>
                        <div class="position-relative">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" class="form-control input-with-icon" id="password" name="password" placeholder="Create a strong password">
                            <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                        </div>
                        <div class="error-message" id="password-error">Password doesn't meet requirements</div>
                    </div>
                    <div class="form-group password-container">
                        <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                        <div class="position-relative">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" class="form-control input-with-icon" id="confirm_password" name="confirm_password" placeholder="Confirm your password">
                            <i class="fas fa-eye password-toggle" id="toggleConfirmPassword"></i>
                        </div>
                        <div class="error-message" id="confirm_password-error">Passwords do not match</div>
                    </div>
                    <div class="info-box">
                        <div class="info-box-title">Password requirements:</div>
                        <ul>
                            <li>At least 8 characters long</li>
                            <li>Contains at least one uppercase letter</li>
                            <li>Contains at least one lowercase letter</li>
                            <li>Contains at least one number</li>
                        </ul>
                    </div>
                </div>

                <!-- Step 5: File Upload -->
                <div class="form-step" data-step="5">
                    <div class="form-group">
                        <label for="resume_file">Resume/CV</label>
                        <div class="file-upload" id="resume-upload-area">
                            <div class="file-upload-icon">📄</div>
                            <div class="file-upload-text">Click to upload or drag and drop</div>
                            <div class="file-upload-subtext">PDF, DOC, or DOCX (Max 5MB)</div>
                            <input type="file" id="resume_file" name="resume_file" accept=".pdf,.doc,.docx" style="display: none;">
                        </div>
                        <div class="file-preview" id="resume-preview">
                            <span class="file-preview-icon">✓</span>
                            <span id="resume-filename"></span>
                        </div>
                    </div>
                    <div class="info-box">
                        By submitting this form, you agree to our Terms of Service and Privacy Policy.
                        Your information will be processed in accordance with our data protection guidelines.
                    </div>
                </div>

                <!-- Form Navigation -->
                <div class="form-navigation">
                    <button type="button" id="prev-btn" class="btn btn-outline" disabled>Previous</button>
                    <button type="button" id="next-btn" class="btn btn-primary">Next</button>
                    <button type="submit" id="submit-btn" class="btn btn-success" style="display: none;">
                        <span id="submit-spinner" class="spinner" style="display: none;"></span>
                        Complete Registration
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (isset($success_message)): ?>
    <script>
        Swal.fire({
            title: 'Success!',
            text: '<?php echo $success_message; ?>',
            icon: 'success',
            confirmButtonText: 'OK',
            confirmButtonColor: '#4361ee'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'login.php';
            }
        });
    </script>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let otpCooldownActive = false;
            let otpCooldownTimeout = null;
            let otpCooldownInterval = null;
            let remainingCooldownTime = 0;

            // Form elements
            const form = document.getElementById('registration-form');
            const formSteps = document.querySelectorAll('.form-step');
            const stepIndicator = document.querySelectorAll('.step');
            const stepProgress = document.getElementById('step-progress');

            // Navigation buttons
            const prevBtn = document.getElementById('prev-btn');
            const nextBtn = document.getElementById('next-btn');
            const submitBtn = document.getElementById('submit-btn');
            const submitSpinner = document.getElementById('submit-spinner');

            // File upload elements
            const resumeUploadArea = document.getElementById('resume-upload-area');
            const resumeInput = document.getElementById('resume_file');
            const resumePreview = document.getElementById('resume-preview');
            const resumeFilename = document.getElementById('resume-filename');

            // Current step tracking
            let currentStep = 1;
            const totalSteps = formSteps.length;

            // ===== STEP INDICATOR JAVASCRIPT - START =====
            // Calculate step positions for progress bar
            // This function determines where each step is positioned for the progress bar
            function calculateStepPositions() {
                // Define custom widths for each step (total should be 100)
                const stepWidths = {
                    1: 0,      // Starting position
                    2: 30,     // Education & Contact
                    3: 50,     // Professional Details
                    4: 75,     // Account Setup
                    5: 100     // File Upload
                };
                
                return Object.values(stepWidths);
            }
            const stepPositions = calculateStepPositions();

            // Update step indicator
            // This function updates the visual appearance of the step indicator
            function updateStepIndicator() {
                // Update progress bar width based on custom step positions
                const progressWidth = currentStep === 1 ? 0 : stepPositions[currentStep - 1];
                stepProgress.style.width = `${progressWidth}%`;
                
                // Update step classes
                stepIndicator.forEach(step => {
                    const stepNumber = parseInt(step.dataset.step);
                    step.classList.remove('active', 'completed');
                    // EDIT THIS: You can change the logic for how steps are marked as completed or active
                    if (stepNumber < currentStep) {
                        step.classList.add('completed');
                    } else if (stepNumber === currentStep) {
                        step.classList.add('active');
                    }
                });
                
                // Update button states
                prevBtn.disabled = currentStep === 1;
                if (currentStep === totalSteps) {
                    nextBtn.style.display = 'none';
                    submitBtn.style.display = 'block';
                } else {
                    nextBtn.style.display = 'block';
                    submitBtn.style.display = 'none';
                }
            }
            // ===== STEP INDICATOR JAVASCRIPT - END =====

            // Show specific step
            function showStep(stepNumber) {
                formSteps.forEach(step => {
                    step.classList.remove('active');
                });
                document.querySelector(`.form-step[data-step="${stepNumber}"]`).classList.add('active');
                currentStep = stepNumber;
                updateStepIndicator();
            }

            // Validate current step
            async function validateStep(stepNumber) {
                let isValid = true;
                const currentStepElement = document.querySelector(`.form-step[data-step="${stepNumber}"]`);
                // Reset all error messages in current step
                const errorMessages = currentStepElement.querySelectorAll('.error-message');
                errorMessages.forEach(error => error.classList.remove('visible'));
                const inputs = currentStepElement.querySelectorAll('input, textarea, select');
                inputs.forEach(input => input.classList.remove('input-error'));

                // Step 1 validation
                if (stepNumber === 1) {
                    // First name validation
                    const firstName = document.getElementById('first_name').value.trim();
                    if (firstName.length < 2) {
                        document.getElementById('first_name').classList.add('input-error');
                        document.getElementById('first_name-error').classList.add('visible');
                        isValid = false;
                    }
                    
                    // Last name validation
                    const lastName = document.getElementById('last_name').value.trim();
                    if (lastName.length < 2) {
                        document.getElementById('last_name').classList.add('input-error');
                        document.getElementById('last_name-error').classList.add('visible');
                        isValid = false;
                    }
                    
                    // Gender validation
                    const gender = document.querySelector('input[name="gender"]:checked');
                    if (!gender) {
                        document.getElementById('gender-error').classList.add('visible');
                        isValid = false;
                    }
                    
                    // Birthdate validation
                    const birthdate = document.getElementById('birthdate').value;
                    if (!birthdate) {
                        document.getElementById('birthdate').classList.add('input-error');
                        document.getElementById('birthdate-error').classList.add('visible');
                        isValid = false;
                    }
                }
                // Step 2 validation
                else if (stepNumber === 2) {
                    const lrn = document.getElementById('lrn').value.trim();
                    const email = document.getElementById('email').value.trim();

                    // Basic format validation
                    if (!/^\d{12}$/.test(lrn)) {
                        document.getElementById('lrn').classList.add('input-error');
                        document.getElementById('lrn-error').classList.add('visible');
                        return false;
                    }

                    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                        document.getElementById('email').classList.add('input-error');
                        document.getElementById('email-error').classList.add('visible');
                        return false;
                    }

                    // Check LRN and Email existence BEFORE OTP
                    return new Promise(async (resolve) => {
                        try {
                            // Check LRN
                            const lrnResponse = await fetch('registration.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `action=check_lrn&lrn=${encodeURIComponent(lrn)}`
                            });
                            const lrnData = await lrnResponse.json();
                            if (lrnData.exists && lrnData.message === 'This LRN is already registered in our system') {
                                Swal.fire({
                                    title: 'Error',
                                    text: lrnData.message,
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                                resolve(false);
                                return;
                            }
                            if (!lrnData.exists || lrnData.message !== 'LRN verified') {
                                Swal.fire({
                                    title: 'Error',
                                    text: lrnData.message,
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                                resolve(false);
                                return;
                            }

                            // Check Email
                            const emailResponse = await fetch('registration.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `action=check_email&email=${encodeURIComponent(email)}`
                            });
                            const emailData = await emailResponse.json();
                            if (emailData.exists) {
                                Swal.fire({
                                    title: 'Error',
                                    text: emailData.message,
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                                resolve(false);
                                return;
                            }

                            // If both checks pass, proceed to OTP popup
                            let countdown = 60;
                            let countdownInterval;

                            // Check if there's an existing cooldown for this email
                            const lastAttemptTime = sessionStorage.getItem(`otpCooldown_${email}`);
                            if (lastAttemptTime) {
                                const timeElapsed = Math.floor((Date.now() - parseInt(lastAttemptTime)) / 1000);
                                if (timeElapsed < 60) {
                                    countdown = 60 - timeElapsed;
                                }
                            }

                            Swal.fire({
                                title: 'Email Verification',
                                html: `
                                    <div class="custom-popup">
                                        <p class="verification-message">Sending verification code to ${email}</p>
                                        <div class="otp-input-container">
                                            <input type="number" maxLength="1" class="otp-digit" data-index="1" min="0" max="9">
                                            <input type="number" maxLength="1" class="otp-digit" data-index="2" min="0" max="9">
                                            <input type="number" maxLength="1" class="otp-digit" data-index="3" min="0" max="9">
                                            <input type="number" maxLength="1" class="otp-digit" data-index="4" min="0" max="9">
                                            <input type="number" maxLength="1" class="otp-digit" data-index="5" min="0" max="9">
                                            <input type="number" maxLength="1" class="otp-digit" data-index="6" min="0" max="9">
                                        </div>
                                        <div class="resend-section">
                                            <span id="resendOtp" class="resend-otp disabled">Resend code</span>
                                            <span class="resend-timer">(${countdown}s)</span>
                                        </div>
                                    </div>
                                `,
                                showCancelButton: true,
                                confirmButtonText: 'Verify',
                                cancelButtonText: 'Cancel',
                                customClass: {
                                    popup: 'custom-popup',
                                    confirmButton: 'verify-btn',
                                    cancelButton: 'cancel-btn'
                                },
                                showLoaderOnConfirm: true,
                                allowOutsideClick: false,
                                didOpen: async () => {
                                    const otpInputs = document.querySelectorAll('.otp-digit');
                                    const verifyButton = document.querySelector('.swal2-confirm');
                                    verifyButton.disabled = true;

                                    function checkOTPCompletion() {
                                        const isComplete = Array.from(otpInputs).every(input => input.value.length === 1);
                                        verifyButton.disabled = !isComplete;
                                    }

                                    otpInputs.forEach((input, index) => {
                                        // Prevent user from moving cursor left
                                        input.addEventListener('keydown', function(e) {
                                            // Prevent all arrow key navigation
                                            if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
                                                e.preventDefault();
                                            }
                                            
                                            // Allow only numbers, backspace, and tab
                                            if (!/^\d$/.test(e.key) && e.key !== 'Backspace' && e.key !== 'Tab') {
                                                e.preventDefault();
                                            }
                                            
                                            if (e.key === 'Backspace' && this.value === '') {
                                                e.preventDefault();
                                                const prevInput = otpInputs[index - 1];
                                                if (prevInput) {
                                                    prevInput.focus();
                                                    prevInput.value = '';
                                                }
                                            }
                                        });

                                        // Force cursor to end on click/focus
                                        ['click', 'focus'].forEach(eventName => {
                                            input.addEventListener(eventName, function(e) {
                                                setTimeout(() => {
                                                    this.selectionStart = this.selectionEnd = this.value.length;
                                                }, 0);
                                            });
                                        });

                                        // Handle input
                                        input.addEventListener('input', function(e) {
                                            // Remove any non-digit characters
                                            this.value = this.value.replace(/[^\d]/g, '');
                                            
                                            // Keep only first digit if multiple entered
                                            if (this.value.length > 1) {
                                                this.value = this.value[0];
                                            }

                                            // Move to next input if value entered
                                            if (this.value !== '') {
                                                const nextInput = otpInputs[index + 1];
                                                if (nextInput) {
                                                    nextInput.focus();
                                                }
                                            }
                                            
                                            // Force cursor to end
                                            setTimeout(() => {
                                                this.selectionStart = this.selectionEnd = this.value.length;
                                            }, 0);
                                            
                                            checkOTPCompletion();
                                        });

                                        // Prevent selection/cursor movement
                                        input.addEventListener('select', function(e) {
                                            this.selectionStart = this.selectionEnd = this.value.length;
                                        });
                                    });

                                    // Only send new OTP if no active cooldown
                                    if (!lastAttemptTime || (Date.now() - parseInt(lastAttemptTime)) >= 60000) {
                                        try {
                                            const otpResponse = await fetch('registration.php', {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/x-www-form-urlencoded',
                                                },
                                                body: `action=send_otp&email=${encodeURIComponent(email)}`
                                            });
                                            const otpData = await otpResponse.json();
                                            if (otpData.success) {
                                                sessionStorage.setItem(`otpCooldown_${email}`, Date.now().toString());
                                                document.querySelector('.verification-message').textContent = `We've sent a verification code to ${email}`;
                                            } else {
                                                Swal.showValidationMessage('Failed to send OTP. Please try again.');
                                            }
                                        } catch (e) {
                                            Swal.showValidationMessage('Error sending OTP. Please try again.');
                                        }
                                    } else {
                                        document.querySelector('.verification-message').textContent = `Use the previous code sent to ${email}`;
                                    }

                                    // Start countdown timer
                                    const resendBtn = document.getElementById('resendOtp');
                                    const timerSpan = document.querySelector('.resend-timer');
                                    resendBtn.classList.add('disabled'); // Always start disabled
                                    countdownInterval = setInterval(() => {
                                        countdown--;
                                        timerSpan.textContent = `(${countdown}s)`;
                                        
                                        if (countdown <= 0) {
                                            clearInterval(countdownInterval);
                                            resendBtn.classList.remove('disabled');
                                            timerSpan.textContent = '';
                                            sessionStorage.removeItem(`otpCooldown_${email}`);
                                        }
                                    }, 1000);

                                    resendBtn.addEventListener('click', async () => {
                                        if (!resendBtn.classList.contains('disabled')) {
                                            try {
                                                // Immediately disable button and start countdown
                                                resendBtn.classList.add('disabled');
                                                countdown = 60;
                                                timerSpan.textContent = `(${countdown}s)`;
                                                sessionStorage.setItem(`otpCooldown_${email}`, Date.now().toString());
                                                
                                                // Clear any existing interval
                                                if (countdownInterval) {
                                                    clearInterval(countdownInterval);
                                                }
                                                
                                                // Start new countdown
                                                countdownInterval = setInterval(() => {
                                                    countdown--;
                                                    timerSpan.textContent = `(${countdown}s)`;
                                                    
                                                    if (countdown <= 0) {
                                                        clearInterval(countdownInterval);
                                                        resendBtn.classList.remove('disabled');
                                                        timerSpan.textContent = '';
                                                        sessionStorage.removeItem(`otpCooldown_${email}`);
                                                    }
                                                }, 1000);

                                                // Send new OTP
                                                const response = await fetch('registration.php', {
                                                    method: 'POST',
                                                    headers: {
                                                        'Content-Type': 'application/x-www-form-urlencoded',
                                                    },
                                                    body: `action=send_otp&email=${encodeURIComponent(email)}`
                                                });
                                                const result = await response.json();
                                                if (!result.success) {
                                                    throw new Error('Failed to send OTP');
                                                }
                                            } catch (error) {
                                                console.error('Error sending OTP:', error);
                                                Swal.showValidationMessage('Failed to send OTP. Please try again.');
                                            }
                                        }
                                    });
                                },
                                preConfirm: async () => {
                                    // Gather OTP from all inputs
                                    const otpDigits = Array.from(document.querySelectorAll('.otp-digit'))
                                        .map(input => input.value)
                                        .join('');
                                        
                                    const verifyResponse = await fetch('registration.php', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/x-www-form-urlencoded',
                                        },
                                        body: `action=verify_otp&otp=${otpDigits}`
                                    });
                                    const verifyData = await verifyResponse.json();
                                    if (!verifyData.success) {
                                        Swal.showValidationMessage(verifyData.message);
                                        return false;
                                    }
                                    return true;
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    resolve(true);
                                } else {
                                    resolve(false);
                                }
                            });
                        } catch (err) {
                            Swal.fire({
                                title: 'Error',
                                text: 'An error occurred while checking your LRN or Email.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                            resolve(false);
                        }
                    });
                }
                // Step 3 validation
                else if (stepNumber === 3) {
                    // Address validation
                    const address = document.getElementById('address').value.trim();
                    if (address.length < 5) {
                        document.getElementById('address').classList.add('input-error');
                        document.getElementById('address-error').classList.add('visible');
                        isValid = false;
                    }

                    // Employment status validation
                    const employmentStatus = document.getElementById('employment_status').value;
                    if (!employmentStatus) {
                        document.getElementById('employment_status').classList.add('input-error');
                        document.getElementById('employment_status-error').classList.add('visible');
                        isValid = false;
                    }

                    // Skills validation
                    const skills = document.getElementById('skills').value.trim();
                    if (skills.length < 2) {
                        document.getElementById('skills').classList.add('input-error');
                        document.getElementById('skills-error').classList.add('visible');
                        isValid = false;
                    }
                }
                // Step 4 validation
                else if (stepNumber === 4) {
                    // Password validation
                    const password = document.getElementById('password').value;
                    const hasUppercase = /[A-Z]/.test(password);
                    const hasLowercase = /[a-z]/.test(password);
                    const hasNumber = /[0-9]/.test(password);
                    
                    if (password.length < 8 || !hasUppercase || !hasLowercase || !hasNumber) {
                        document.getElementById('password').classList.add('input-error');
                        document.getElementById('password-error').classList.add('visible');
                        isValid = false;
                    }

                    // Confirm password validation
                    const confirmPassword = document.getElementById('confirm_password').value;
                    if (password !== confirmPassword) {
                        document.getElementById('confirm_password').classList.add('input-error');
                        document.getElementById('confirm_password-error').classList.add('visible');
                        isValid = false;
                    }
                }
                return isValid;
            }

            // Event listeners for navigation
            prevBtn.addEventListener('click', function() {
                if (currentStep > 1) {
                    showStep(currentStep - 1);
                }
            });

            nextBtn.addEventListener('click', async function() {
                const validationResult = await validateStep(currentStep);
                if (validationResult && currentStep < totalSteps) {
                    showStep(currentStep + 1);
                }
            });

            // File upload handling
            resumeUploadArea.addEventListener('click', function() {
                resumeInput.click();
            });

            resumeInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    resumeFilename.textContent = file.name;
                    resumePreview.classList.add('visible');
                }
            });

            // Drag and drop functionality
            function setupDragAndDrop(dropArea, fileInput, preview, filenameElement) {
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropArea.addEventListener(eventName, preventDefaults, false);
                });
                
                function preventDefaults(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                
                ['dragenter', 'dragover'].forEach(eventName => {
                    dropArea.addEventListener(eventName, highlight, false);
                });
                
                ['dragleave', 'drop'].forEach(eventName => {
                    dropArea.addEventListener(eventName, unhighlight, false);
                });
                
                function highlight() {
                    dropArea.style.borderColor = '#4361ee';
                }
                
                function unhighlight() {
                    dropArea.style.borderColor = '#ddd';
                }
                
                dropArea.addEventListener('drop', handleDrop, false);
                
                function handleDrop(e) {
                    const dt = e.dataTransfer;
                    const files = dt.files;
                    
                    if (files && files.length) {
                        fileInput.files = files;
                        filenameElement.textContent = files[0].name;
                        preview.classList.add('visible');
                    }
                }
            }
            setupDragAndDrop(resumeUploadArea, resumeInput, resumePreview, resumeFilename);

            // Form submission
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                if (!await validateStep(currentStep)) {
                    return;
                }
                
                // Show loading state
                submitBtn.disabled = true;
                submitSpinner.style.display = 'inline-block';

                // Create FormData object
                const formData = new FormData(form);

                try {
                    const response = await fetch('registration.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (result.success) {
                        Swal.fire({
                            title: 'Registration Successful!',
                            html: `<b>Please note:</b> Your account is currently pending approval.<br>
                                   You will be notified via email once your account is approved.`,
                            icon: 'success',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#4361ee'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'login.php';
                            }
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: result.message || 'Registration failed',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred during registration',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                } finally {
                    submitBtn.disabled = false;
                    submitSpinner.style.display = 'none';
                }
            });

            // Helper function to generate UUID
            function generateUUID() {
                return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                    const r = Math.random() * 16 | 0;
                    const v = c === 'x' ? r : (r & 0x3 | 0x8);
                    return v.toString(16);
                });
            }

            // Add LRN validation function
            function validateLRN(lrnValue) {
                return new Promise((resolve, reject) => {
                    fetch('check_lrn.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `lrn=${lrnValue}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.duplicate) {
                            reject(data.message);
                        } else if (data.exists) {
                            resolve(true);
                        } else {
                            reject(data.message);
                        }
                    })
                    .catch(error => reject(error));
                });
            }
            
            // Fetch and populate graduation years
            fetch('registration.php?action=get_years')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const yearSelect = document.getElementById('graduation_year');
                        data.years.forEach(year => {
                            const option = document.createElement('option');
                            option.value = year;
                            option.textContent = `S.Y. ${year}`;
                            yearSelect.appendChild(option);
                        });
                    }
                });

            // Autofill graduation year based on LRN
            const lrnInput = document.getElementById('lrn');
            const graduationYearSelect = document.getElementById('graduation_year');
            let lastLrnValue = '';
            lrnInput.addEventListener('input', async function() {
                const lrn = lrnInput.value.trim();
                if (lrn === lastLrnValue) return;
                lastLrnValue = lrn;
                graduationYearSelect.readOnly = true; // Use readOnly instead of disabled
                
                if (/^\d{12}$/.test(lrn)) {
                    try {
                        const response = await fetch('registration.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `action=get_year_by_lrn&lrn=${encodeURIComponent(lrn)}`
                        });
                        const data = await response.json();
                        if (data.success && data.year) {
                            graduationYearSelect.value = data.year;
                            // Add a hidden input to ensure the value is submitted
                            if (!document.getElementById('hidden_graduation_year')) {
                                const hiddenInput = document.createElement('input');
                                hiddenInput.type = 'hidden';
                                hiddenInput.id = 'hidden_graduation_year';
                                hiddenInput.name = 'graduation_year';
                                hiddenInput.value = data.year;
                                graduationYearSelect.parentNode.appendChild(hiddenInput);
                            } else {
                                document.getElementById('hidden_graduation_year').value = data.year;
                            }
                        } else {
                            graduationYearSelect.value = '';
                        }
                    } catch (e) {
                        graduationYearSelect.value = '';
                    }
                } else {
                    graduationYearSelect.value = '';
                }
            });

            // Initialize the form
            updateStepIndicator();
        });
    </script>
    <script>
    // Password toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.getElementById('togglePassword');
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    });
</script>
</body>
</html>