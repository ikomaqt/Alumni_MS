<?php
session_start();

// Check if user is logged in (either admin or regular user)
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    // Redirect to login page with error message
    $_SESSION['errors'] = array("Please login to view resumes");
    header("Location: login.php");
    exit();
}

// Validate file parameter
if (!isset($_GET['file']) || empty($_GET['file'])) {
    die("No file specified");
}

$filename = basename($_GET['file']); // Get base name for security
$filepath = "uploads/resumes/" . $filename; // Adjust this path to match your resume upload directory

// Check if file exists
if (!file_exists($filepath)) {
    die("File not found");
}

// Get file mime type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $filepath);
finfo_close($finfo);

// Set headers for PDF display
header('Content-Type: ' . $mime_type);
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: public, must-revalidate, max-age=0');
header('Pragma: public');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

readfile($filepath);
exit;
