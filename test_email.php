<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Create log file if it doesn't exist
$logFile = 'email_debug.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " Starting email test\n", FILE_APPEND);

$mail = new PHPMailer(true);
try {
    $mail->SMTPDebug = 3;
    $mail->Debugoutput = function($str, $level) use ($logFile) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . ": $str\n", FILE_APPEND);
    };

    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'nesrac22@gmail.com';
    $mail->Password = 'cegq qqrk jjdw xwbs';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;
    $mail->Timeout = 30;

    $mail->setFrom('nesrac22@gmail.com', 'Test System');
    $mail->addAddress('nesrac22@gmail.com');

    $mail->isHTML(true);
    $mail->Subject = 'Test Subject ' . date('Y-m-d H:i:s');
    $mail->Body = 'This is a test email sent at ' . date('Y-m-d H:i:s');

    $result = $mail->send();
    echo "Mail sent successfully! Check email_debug.log for details.";
    file_put_contents($logFile, date('Y-m-d H:i:s') . " Email sent successfully\n", FILE_APPEND);
} catch (Exception $e) {
    $error = "Mail error: " . $mail->ErrorInfo;
    echo $error;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " $error\n", FILE_APPEND);
}
