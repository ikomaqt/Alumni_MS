<?php
require_once('sqlconnection.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $action = $_POST['action'];
    
    // Get user's email before updating status
    $emailQuery = "SELECT email, first_name, last_name FROM users WHERE lrn = ?";
    $stmt = $conn->prepare($emailQuery);
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    
    if ($action === 'approve') {
        $query = "UPDATE users SET role = 'user', acc_status = 'active' WHERE lrn = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $id);
        
        if ($stmt->execute()) {
            // Send approval email
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'nesrac22@gmail.com'; // Your email
                $mail->Password = 'cegq qqrk jjdw xwbs'; // Your email password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('nesrac22@gmail.com', 'Alumni MS');
                $mail->addAddress($userData['email']);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Account Approved - Alumni Management System';
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                        <h2 style='color: #4361ee;'>Account Approved!</h2>
                        <p>Dear {$userData['first_name']} {$userData['last_name']},</p>
                        <p>We're pleased to inform you that your account has been approved. You can now log in to the Alumni Management System using your credentials.</p>
                        <p><strong>Next Steps:</strong></p>
                        <ol>
                            <li>Visit our login page</li>
                            <li>Enter your email and password</li>
                            <li>Start exploring the alumni network!</li>
                        </ol>
                        <p style='margin-top: 20px;'>Welcome to our alumni community!</p>
                        <p><small>If you didn't request this account, please contact our support team.</small></p>
                    </div>";

                $mail->send();
                echo json_encode(['success' => true, 'message' => 'User approved successfully']);
            } catch (Exception $e) {
                error_log("Email sending failed: {$mail->ErrorInfo}");
                echo json_encode(['success' => true, 'message' => 'User approved but email notification failed']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to approve user']);
        }
    } elseif ($action === 'reject') {
        $query = "DELETE FROM users WHERE lrn = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reject user']);
        }
    }
    
    $stmt->close();
    $conn->close();
    exit;
}
?>
