<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Include required files
require_once 'sqlconnection.php';
require_once 'admin_navbar.php';

// Class to handle event operations
class EventManager {
    private $conn;
    private $uploadDir = "uploads/event_img/"; // Changed upload directory
    private $maxFileSize = 5000000; // 5MB
    private $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    
    public function __construct($connection) {
        $this->conn = $connection;
        
        // Create uploads and event_img directories if they don't exist
        if (!file_exists("uploads/")) {
            mkdir("uploads/", 0755, true);
        }
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    // Sanitize input data
    private function sanitizeInput($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }
    
    // Handle file upload
    private function handleFileUpload($file) {
        if (empty($file['name'])) {
            return false;
        }
        
        $fileName = basename($file['name']);
        $targetFile = $this->uploadDir . time() . '_' . $fileName; // Will now save in uploads/event_img/
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        
        // Check if file is an actual image
        $check = getimagesize($file['tmp_name']);
        if ($check === false) {
            $_SESSION['error_message'] = "File is not an image.";
            return false;
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            $_SESSION['error_message'] = "Sorry, your file is too large (max 5MB).";
            return false;
        }
        
        // Check file type
        if (!in_array($imageFileType, $this->allowedTypes)) {
            $_SESSION['error_message'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            return false;
        }
        
        // Upload file
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            return $targetFile;
        } else {
            $_SESSION['error_message'] = "Sorry, there was an error uploading your file.";
            return false;
        }
    }
    
    // Insert new event
    public function insertEvent($data, $file) {
        // Sanitize inputs
        $title = $this->sanitizeInput($data['eventtitle']);
        $date = $this->sanitizeInput($data['eventdate']);
        $startTime = $this->sanitizeInput($data['eventstarttime']);
        $endTime = $this->sanitizeInput($data['eventendtime']);
        $description = $this->sanitizeInput($data['eventdescription']);
        $place = $this->sanitizeInput($data['eventplace']);
        $status = $this->sanitizeInput($data['eventstatus']);
        
        // Handle file upload
        $imagePath = $this->handleFileUpload($file);
        if ($imagePath === false && !empty($file['name'])) {
            return false;
        }
        
        // Use default image if none provided
        if (empty($imagePath)) {
            $imagePath = "uploads/default-event.jpg";
        }
        
        // Prepare and execute query using prepared statements
        $stmt = $this->conn->prepare("INSERT INTO events (event_title, event_date, event_start_time, event_end_time, event_description, event_place, event_image, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                
        $stmt->bind_param("ssssssss", $title, $date, $startTime, $endTime, $description, $place, $imagePath, $status);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Event successfully posted!";
            return true;
        } else {
            $_SESSION['error_message'] = "Error posting event: " . $stmt->error;
            return false;
        }
    }
    
    // Update existing event
    public function updateEvent($id, $data, $file) {
        // Fetch existing event data
        $stmt = $this->conn->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if (!$row) {
            $_SESSION['error_message'] = "Event not found.";
            return false;
        }
        
        // Use existing values if fields are empty
        $title = !empty($data['eventtitle']) ? $this->sanitizeInput($data['eventtitle']) : $row['event_title'];
        $date = !empty($data['eventdate']) ? $this->sanitizeInput($data['eventdate']) : $row['event_date'];
        $startTime = !empty($data['eventstarttime']) ? $this->sanitizeInput($data['eventstarttime']) : $row['event_start_time'];
        $endTime = !empty($data['eventendtime']) ? $this->sanitizeInput($data['eventendtime']) : $row['event_end_time'];
        $description = !empty($data['eventdescription']) ? $this->sanitizeInput($data['eventdescription']) : $row['event_description'];
        $place = !empty($data['eventplace']) ? $this->sanitizeInput($data['eventplace']) : $row['event_place'];
        $status = !empty($data['eventstatus']) ? $this->sanitizeInput($data['eventstatus']) : $row['status'];
        $imagePath = $row['event_image'];
        
        // Handle new image upload if provided
        if (!empty($file['name'])) {
            $newImagePath = $this->handleFileUpload($file);
            if ($newImagePath !== false) {
                // Delete old image if it exists and is not the default
                if (!empty($imagePath) && file_exists($imagePath) && $imagePath != "uploads/default-event.jpg") {
                    unlink($imagePath);
                }
                $imagePath = $newImagePath;
            } else {
                return false; // Error in file upload
            }
        }
        
        // Update event using prepared statement
        $stmt = $this->conn->prepare("UPDATE events SET 
                event_title = ?, 
                event_date = ?, 
                event_start_time = ?, 
                event_end_time = ?, 
                event_description = ?, 
                event_place = ?, 
                event_image = ?,
                status = ? 
            WHERE id = ?");
            
        $stmt->bind_param("ssssssssi", $title, $date, $startTime, $endTime, $description, $place, $imagePath, $status, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Event successfully updated!";
            return true;
        } else {
            $_SESSION['error_message'] = "Error updating event: " . $stmt->error;
            return false;
        }
    }
    
    // Delete an event
    public function deleteEvent($id) {
        // Get image path before deleting
        $stmt = $this->conn->prepare("SELECT event_image FROM events WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row) {
            $imagePath = $row['event_image'];
            
            // Delete the event
            $stmt = $this->conn->prepare("DELETE FROM events WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                // Delete the image file if it exists and is not the default
                if (!empty($imagePath) && file_exists($imagePath) && $imagePath != "uploads/default-event.jpg") {
                    unlink($imagePath);
                }
                
                $_SESSION['success_message'] = "Event successfully deleted!";
                return true;
            } else {
                $_SESSION['error_message'] = "Error deleting event: " . $stmt->error;
                return false;
            }
        } else {
            $_SESSION['error_message'] = "Event not found.";
            return false;
        }
    }
    
    // Get upcoming events
    public function getUpcomingEvents() {
        $today = date('Y-m-d');
        $stmt = $this->conn->prepare("SELECT * FROM events WHERE event_date >= ? ORDER BY event_date ASC");
        $stmt->bind_param("s", $today);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    // Get past events
    public function getPastEvents() {
        $today = date('Y-m-d');
        $stmt = $this->conn->prepare("SELECT * FROM events WHERE event_date < ? ORDER BY event_date DESC");
        $stmt->bind_param("s", $today);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    // Count total events
    public function countEvents() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM events");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total'];
    }
    
    // Count upcoming events
    public function countUpcomingEvents() {
        $today = date('Y-m-d');
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM events WHERE event_date >= ?");
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total'];
    }
    
    // Count past events
    public function countPastEvents() {
        $today = date('Y-m-d');
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM events WHERE event_date < ?");
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total'];
    }
}

// Initialize event manager
$eventManager = new EventManager($conn);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['post_event'])) {
        $success = $eventManager->insertEvent($_POST, $_FILES['eventimage']);
        if ($success) {
            // Send email notification to all active users except the admin poster
            require_once 'vendor/phpmailer/phpmailer/src/Exception.php';
            require_once 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
            require_once 'vendor/phpmailer/phpmailer/src/SMTP.php';
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            // Get all user emails except the admin poster
            $user_emails = [];
            $user_sql = "SELECT email FROM users WHERE acc_status = 'active' AND email IS NOT NULL AND email != ''";
            $user_result = $conn->query($user_sql);
            if ($user_result && $user_result->num_rows > 0) {
                while ($row = $user_result->fetch_assoc()) {
                    $user_emails[] = $row['email'];
                }
            }

            // Get admin poster email
            $poster_email = '';
            $admin_id = $_SESSION['admin_id'];
            $admin_sql = "SELECT email FROM admin_users WHERE admin_id = ?";
            $admin_stmt = $conn->prepare($admin_sql);
            $admin_stmt->bind_param("i", $admin_id);
            $admin_stmt->execute();
            $admin_result = $admin_stmt->get_result();
            if ($admin_result && $admin_row = $admin_result->fetch_assoc()) {
                $poster_email = $admin_row['email'];
            }
            $admin_stmt->close();

            // Remove poster email from user_emails if present
            $user_emails = array_filter($user_emails, function($email) use ($poster_email) {
                return strtolower($email) !== strtolower($poster_email);
            });

            // Prepare event info for email
            $event_title = htmlspecialchars($_POST['eventtitle']);
            $event_date = htmlspecialchars($_POST['eventdate']);
            $event_start = htmlspecialchars($_POST['eventstarttime']);
            $event_end = htmlspecialchars($_POST['eventendtime']);
            $event_place = htmlspecialchars($_POST['eventplace']);
            $event_desc = nl2br(htmlspecialchars($_POST['eventdescription']));
            $event_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/alumni/event.php";

            foreach ($user_emails as $user_email) {
                try {
                    $mail->clearAllRecipients();
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'nesrac22@gmail.com';
                    $mail->Password = 'cegq qqrk jjdw xwbs';
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    $mail->setFrom('nesrac22@gmail.com', 'Alumni MS');
                    $mail->addAddress($user_email);
                    $mail->isHTML(true);
                    $mail->Subject = "New Event Posted: $event_title";
                    $mail->Body = "
                        <h3>New Event Announcement!</h3>
                        <p><strong>Title:</strong> $event_title<br>
                        <strong>Date:</strong> $event_date<br>
                        <strong>Time:</strong> $event_start - $event_end<br>
                        <strong>Location:</strong> $event_place</p>
                        <p><strong>Description:</strong><br>$event_desc</p>
                        <p><a href='$event_url'>View Event Details</a></p>
                        <br>
                        <small>This is an automated notification from Alumni MS.</small>
                    ";
                    $mail->send();
                } catch (Exception $e) {
                    // Optionally log or ignore email errors
                }
            }
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } elseif (isset($_POST['update_event'])) {
        $eventManager->updateEvent($_POST['event_id'], $_POST, $_FILES['eventimage']);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } elseif (isset($_POST['delete_event'])) {
        $eventManager->deleteEvent($_POST['event_id']);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Get event counts for dashboard
$totalEvents = $eventManager->countEvents();
$upcomingEventsCount = $eventManager->countUpcomingEvents();
$pastEventsCount = $eventManager->countPastEvents();

// Get upcoming and past events
$upcomingEvents = $eventManager->getUpcomingEvents();
$pastEvents = $eventManager->getPastEvents();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-light: #6366f1;
            --primary-dark: #4338ca;
            --secondary-color: #f3f4f6;
            --text-color: #1f2937;
            --text-light: #6b7280;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --border-color: #e5e7eb;
            --card-bg: #ffffff;
            --body-bg: #f9fafb;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--body-bg);
            color: var(--text-color);
            line-height: 1.6;
            padding: 2rem;
            margin-left: 78px; /* Add margin to prevent overlap with navbar */
        }
        
        /* Main Content */
        .main-content {
            max-width: 1400px;
            margin: 0 auto;
            padding-left: 1rem; /* Add padding for better spacing */
        }
        
        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-color);
        }
        
        /* Dashboard Stats */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background-color: var(--card-bg);
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .stat-icon.primary {
            background-color: var(--primary-color);
        }
        
        .stat-icon.success {
            background-color: var(--success-color);
        }
        
        .stat-icon.warning {
            background-color: var(--warning-color);
        }
        
        .stat-info h3 {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .stat-info p {
            color: var(--text-light);
            font-size: 0.875rem;
        }
        
        /* Dashboard Sections */
        .dashboard-section {
            background-color: var(--card-bg);
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-title i {
            color: var(--primary-color);
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            outline: none;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-color);
        }
        
        .btn-outline:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #dc2626;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        
        /* Event Cards */
        .event-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .event-card {
            background-color: var(--card-bg);
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .event-image {
            height: 180px;
            overflow: hidden;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
        }
        
        .event-image img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
        }
        
        .event-date-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background-color: rgba(255, 255, 255, 0.9);
            padding: 0.5rem;
            border-radius: 0.375rem;
            font-weight: 600;
            font-size: 0.75rem;
            color: var(--primary-color);
        }
        
        .event-content {
            padding: 1.25rem;
        }
        
        .event-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }
        
        .event-details {
            margin-bottom: 1rem;
        }
        
        .event-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-light);
            font-size: 0.875rem;
        }
        
        .event-detail i {
            color: var(--primary-color);
            width: 16px;
        }
        
        .event-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        /* Event Table */
        .event-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .event-table th, .event-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .event-table th {
            font-weight: 600;
            color: var(--text-color);
            background-color: var(--secondary-color);
        }
        
        .event-table tr:hover {
            background-color: rgba(79, 70, 229, 0.05);
        }
        
        .event-table td {
            color: var(--text-light);
        }
        
        .event-table .event-title-cell {
            font-weight: 500;
            color: var(--text-color);
        }
        
        .event-actions-cell {
            display: flex;
            gap: 0.5rem;
        }
        
        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            z-index: 1000;
            backdrop-filter: blur(5px);
        }
        
        .modal-overlay.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal {
            background-color: var(--card-bg);
            border-radius: 0.5rem;
            width: 90%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            position: relative;
            animation: modalFadeIn 0.3s ease;
        }
        
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .modal-close {
            background: none;
            border: none;
            color: var(--text-light);
            font-size: 1.5rem;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .modal-close:hover {
            color: var(--danger-color);
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }
        
        /* Form */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group.full-width {
            grid-column: span 2;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
            font-size: 0.875rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            background-color: white;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-hint {
            margin-top: 0.25rem;
            font-size: 0.75rem;
            color: var (--text-light);
        }
        
        .form-file-preview {
            margin-top: 1rem;
            display: none;
            width: 300px;
            height: 200px; /* Fixed height */
            padding: 10px;
            border-radius: 0.375rem;
            border: 1px solid var(--border-color);
            background-color: #f8f9fa;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-file-preview img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            border-radius: 0.25rem;
        }

        /* View Event Modal Image Styles - Update */
        .event-image-preview {
            width: 100%;
            height: 300px; /* Fixed height */
            padding: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .event-image-preview img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            border-radius: 0.375rem;
        }

        @media (max-width: 768px) {
            .form-file-preview {
                height: 180px;
                padding: 8px;
            }

            .event-image-preview {
                height: 250px;
                padding: 10px;
            }
        }
        
        /* View Switcher */
        .view-switcher {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .view-switcher-btn {
            background-color: var(--secondary-color);
            border: none;
            padding: 0.5rem;
            border-radius: 0.375rem;
            color: var (--text-light);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .view-switcher-btn.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        /* Tab Navigation */
        .tab-nav {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .tab-link {
            padding: 0.75rem 1.5rem;
            color: var(--text-light);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 2px solid transparent;
            white-space: nowrap;
        }
        
        .tab-link.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }
        
        .empty-state-icon {
            font-size: 3rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }
        
        .empty-state-text {
            color: var(--text-light);
            margin-bottom: 1.5rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }
            
            .modal {
                width: 95%;
            }
            
            .event-cards {
                grid-template-columns: 1fr;
            }
        }
        
        /* View Event Modal Styles */
        .view-event-content {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .event-image-preview {
            width: 100%;
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            border: 1px solid var(--border-color);
            overflow: hidden;
            padding: 10px;
        }

        .event-image-preview img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            border-radius: 0.375rem;
        }

        .event-info {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .info-group {
            display: flex;
            gap: 1rem;
            align-items: baseline;
        }

        .info-group.full {
            flex-direction: column;
            gap: 0.5rem;
        }

        .info-group label {
            font-weight: 600;
            min-width: 100px;
            color: var (--text-color);
        }

        .info-group label i {
            color: var(--primary-color);
            margin-right: 0.5rem;
            width: 16px;
        }

        .info-group span, .info-group p {
            color: var(--text-light);
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .view-event-content {
                gap: 1rem;
            }

            .event-image-preview {
                max-height: 200px;
            }

            .info-group {
                flex-direction: column;
                gap: 0.25rem;
            }

            .info-group label {
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Event Management</h1>
            <button class="btn btn-primary" onclick="openEventModal()">
                <i class="fas fa-plus"></i> Add Event
            </button>
        </div>
        
        <!-- Dashboard Stats -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $totalEvents; ?></h3>
                    <p>Total Events</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $upcomingEventsCount; ?></h3>
                    <p>Upcoming Events</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-history"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $pastEventsCount; ?></h3>
                    <p>Past Events</p>
                </div>
            </div>
        </div>

        <!-- Events Section -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-calendar"></i> Events
                </h2>
                <div class="view-switcher">
                    <button class="view-switcher-btn active" id="cardViewBtn">
                        <i class="fas fa-th-large"></i>
                    </button>
                    <button class="view-switcher-btn" id="tableViewBtn">
                        <i class="fas fa-list"></i>
                    </button>
                </div>
            </div>
            
            <div class="tab-nav">
                <div class="tab-link active" data-tab="upcoming-events">Upcoming Events</div>
                <div class="tab-link" data-tab="past-events">Past Events</div>
            </div>

            <!-- Upcoming Events Tab -->
            <div class="tab-content active" id="upcoming-events">
                <div class="event-view card-view">
                    <?php if ($upcomingEvents->num_rows > 0): ?>
                        <div class="event-cards">
                            <?php while ($row = $upcomingEvents->fetch_assoc()): ?>
                                <div class="event-card">
                                    <div class="event-image">
                                        <img src="<?php echo htmlspecialchars($row['event_image']); ?>" alt="<?php echo htmlspecialchars($row['event_title']); ?>">
                                        <div class="event-date-badge">
                                            <?php echo date('M d, Y', strtotime($row['event_date'])); ?>
                                        </div>
                                    </div>
                                    <div class="event-content">
                                        <h3 class="event-title"><?php echo htmlspecialchars($row['event_title']); ?></h3>
                                        <div class="event-details">
                                            <div class="event-detail">
                                                <i class="fas fa-clock"></i>
                                                <span><?php echo date('h:i A', strtotime($row['event_start_time'])); ?> - <?php echo date('h:i A', strtotime($row['event_end_time'])); ?></span>
                                            </div>
                                            <div class="event-detail">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span><?php echo htmlspecialchars($row['event_place']); ?></span>
                                            </div>
                                        </div>
                                        <div class="event-actions">
                                            <button class="btn btn-primary btn-sm" onclick="viewEvent(<?php echo $row['id']; ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="btn btn-outline btn-sm" onclick="editEvent(<?php echo $row['id']; ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['event_title']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <h3>No Upcoming Events</h3>
                            <p class="empty-state-text">You don't have any upcoming events scheduled.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Table View (hidden by default) -->
                <div class="event-view table-view" style="display: none;">
                    <?php if ($upcomingEvents->num_rows > 0): ?>
                        <table class="event-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Location</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Reset the result pointer to the beginning
                                $upcomingEvents->data_seek(0);
                                while ($row = $upcomingEvents->fetch_assoc()): 
                                ?>
                                    <tr>
                                        <td class="event-title-cell"><?php echo htmlspecialchars($row['event_title']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($row['event_date'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($row['event_start_time'])); ?> - <?php echo date('h:i A', strtotime($row['event_end_time'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['event_place']); ?></td>
                                        <td class="event-actions-cell">
                                            <button class="btn btn-primary btn-sm" onclick="viewEvent(<?php echo $row['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline btn-sm" onclick="editEvent(<?php echo $row['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['event_title']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <h3>No Upcoming Events</h3>
                            <p class="empty-state-text">You don't have any upcoming events scheduled.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Past Events Tab -->
            <div class="tab-content" id="past-events">
                <div class="event-view card-view">
                    <?php if ($pastEvents->num_rows > 0): ?>
                        <div class="event-cards">
                            <?php while ($row = $pastEvents->fetch_assoc()): ?>
                                <div class="event-card">
                                    <div class="event-image">
                                        <img src="<?php echo htmlspecialchars($row['event_image']); ?>" alt="<?php echo htmlspecialchars($row['event_title']); ?>">
                                        <div class="event-date-badge">
                                            <?php echo date('M d, Y', strtotime($row['event_date'])); ?>
                                        </div>
                                    </div>
                                    <div class="event-content">
                                        <h3 class="event-title"><?php echo htmlspecialchars($row['event_title']); ?></h3>
                                        <div class="event-details">
                                            <div class="event-detail">
                                                <i class="fas fa-clock"></i>
                                                <span><?php echo date('h:i A', strtotime($row['event_start_time'])); ?> - <?php echo date('h:i A', strtotime($row['event_end_time'])); ?></span>
                                            </div>
                                            <div class="event-detail">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span><?php echo htmlspecialchars($row['event_place']); ?></span>
                                            </div>
                                        </div>
                                        <div class="event-actions">
                                            <button class="btn btn-primary btn-sm" onclick="viewEvent(<?php echo $row['id']; ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="btn btn-outline btn-sm" onclick="editEvent(<?php echo $row['id']; ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['event_title']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <h3>No Past Events</h3>
                            <p class="empty-state-text">You don't have any past events. Events that have already occurred will appear here.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Table View (hidden by default) -->
                <div class="event-view table-view" style="display: none;">
                    <?php if ($pastEvents->num_rows > 0): ?>
                        <table class="event-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Location</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Reset the result pointer to the beginning
                                $pastEvents->data_seek(0);
                                while ($row = $pastEvents->fetch_assoc()): 
                                ?>
                                    <tr>
                                        <td class="event-title-cell"><?php echo htmlspecialchars($row['event_title']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($row['event_date'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($row['event_start_time'])); ?> - <?php echo date('h:i A', strtotime($row['event_end_time'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['event_place']); ?></td>
                                        <td class="event-actions-cell">
                                            <button class="btn btn-primary btn-sm" onclick="viewEvent(<?php echo $row['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline btn-sm" onclick="editEvent(<?php echo $row['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['event_title']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <h3>No Past Events</h3>
                            <p class="empty-state-text">You don't have any past events. Events that have already occurred will appear here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Event Modal -->
    <div class="modal-overlay" id="eventModal">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Create New Event</h2>
                <button class="modal-close" onclick="closeEventModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="eventForm">
                <div class="modal-body">
                    <input type="hidden" name="event_id" id="eventId">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="eventtitle" class="form-label">Event Title</label>
                            <input type="text" class="form-control" id="eventtitle" name="eventtitle" required>
                        </div>
                        <div class="form-group">
                            <label for="eventdate" class="form-label">Event Date</label>
                            <input type="date" class="form-control" id="eventdate" name="eventdate" required>
                        </div>
                        <div class="form-group">
                            <label for="eventstarttime" class="form-label">Start Time</label>
                            <input type="time" class="form-control" id="eventstarttime" name="eventstarttime" required>
                        </div>
                        <div class="form-group">
                            <label for="eventendtime" class="form-label">End Time</label>
                            <input type="time" class="form-control" id="eventendtime" name="eventendtime" required>
                        </div>
                        <div class="form-group full-width">
                            <label for="eventplace" class="form-label">Event Location</label>
                            <input type="text" class="form-control" id="eventplace" name="eventplace" required>
                        </div>
                        <div class="form-group full-width">
                            <label for="eventdescription" class="form-label">Event Description</label>
                            <textarea class="form-control" id="eventdescription" name="eventdescription" required></textarea>
                        </div>
                        <div class="form-group full-width">
                            <label for="eventstatus" class="form-label">Event Status</label>
                            <select class="form-control" id="eventstatus" name="eventstatus" required>
                                <option value="open">Open</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label for="eventimage" class="form-label">Event Image</label>
                            <input type="file" class="form-control" id="eventimage" name="eventimage" accept="image/*">
                            <p class="form-hint">Accepted formats: JPG, JPEG, PNG, GIF (Max size: 5MB)</p>
                            <div class="form-file-preview" id="imagePreview">
                                <img id="previewImg" src="#" alt="Preview">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeEventModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Create Event</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Event Modal -->
    <div class="modal-overlay" id="viewEventModal">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title" id="viewModalTitle">View Event</h2>
                <button class="modal-close" onclick="closeViewModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="view-event-content">
                    <div class="event-image-preview">
                        <img id="viewEventImage" src="" alt="Event Image">
                    </div>
                    <div class="event-info">
                        <div class="info-group">
                            <label><i class="fas fa-calendar"></i> Date:</label>
                            <span id="viewEventDate"></span>
                        </div>
                        <div class="info-group">
                            <label><i class="fas fa-clock"></i> Time:</label>
                            <span id="viewEventTime"></span>
                        </div>
                        <div class="info-group">
                            <label><i class="fas fa-map-marker-alt"></i> Location:</label>
                            <span id="viewEventPlace"></span>
                        </div>
                        <div class="info-group full">
                            <label><i class="fas fa-align-left"></i> Description:</label>
                            <p id="viewEventDescription"></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Form (hidden) -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="event_id" id="deleteEventId">
        <input type="hidden" name="delete_event" value="1">
    </form>

    <script>
        // View switcher
        document.getElementById('cardViewBtn').addEventListener('click', function() {
            this.classList.add('active');
            document.getElementById('tableViewBtn').classList.remove('active');
            document.querySelectorAll('.card-view').forEach(view => view.style.display = 'block');
            document.querySelectorAll('.table-view').forEach(view => view.style.display = 'none');
        });
        
        document.getElementById('tableViewBtn').addEventListener('click', function() {
            this.classList.add('active');
            document.getElementById('cardViewBtn').classList.remove('active');
            document.querySelectorAll('.card-view').forEach(view => view.style.display = 'none');
            document.querySelectorAll('.table-view').forEach(view => view.style.display = 'block');
        });
        
        // Tab navigation
        document.querySelectorAll('.tab-link').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                document.querySelectorAll('.tab-link').forEach(t => t.classList.remove('active'));
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Hide all tab contents
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                // Show the corresponding tab content
                document.getElementById(this.dataset.tab).classList.add('active');
            });
        });
        
        // Image preview
        document.getElementById('eventimage').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('imagePreview').classList.add('active');
                }
                reader.readAsDataURL(file);
            } else {
                document.getElementById('imagePreview').classList.remove('active');
            }
        });
        
        // Modal functions
        function openEventModal() {
            // Reset form
            document.getElementById('eventForm').reset();
            document.getElementById('imagePreview').classList.remove('active');
            document.getElementById('modalTitle').textContent = 'Create New Event';
            document.getElementById('submitBtn').textContent = 'Create Event';
            document.getElementById('submitBtn').name = 'post_event';
            document.getElementById('eventId').value = '';
            
            // Show modal
            document.getElementById('eventModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeEventModal() {
            document.getElementById('eventModal').classList.remove('active');
            document.body.style.overflow = '';
        }
        
        function editEvent(id) {
            fetch('get_event.php?id=' + id)
                .then(response => response.json())
                .then(response => {
                    if (!response.success) {
                        throw new Error(response.error);
                    }
                    
                    const data = response.data;
                    
                    // Populate form for updating
                    document.getElementById('eventId').value = data.id;
                    document.getElementById('eventtitle').value = data.event_title;
                    document.getElementById('eventdate').value = data.event_date;
                    document.getElementById('eventstarttime').value = data.event_start_time;
                    document.getElementById('eventendtime').value = data.event_end_time;
                    document.getElementById('eventplace').value = data.event_place;
                    document.getElementById('eventdescription').value = data.event_description;
                    document.getElementById('eventstatus').value = data.status;
                    
                    // Show image preview if available
                    if (data.event_image) {
                        document.getElementById('previewImg').src = data.event_image;
                        document.getElementById('imagePreview').classList.add('active');
                    }
                    
                    // Update modal title and button text for edit mode
                    document.getElementById('modalTitle').textContent = 'Edit Event';
                    document.getElementById('submitBtn').textContent = 'Update Event';
                    document.getElementById('submitBtn').name = 'update_event';
                    
                    // Add notification checkbox for updates
                    const notifyCheckbox = document.createElement('div');
                    notifyCheckbox.style.marginRight = 'auto'; // Push to left side
                    notifyCheckbox.innerHTML = `
                        <label class="form-group" style="display: flex; align-items: center; gap: 8px; margin: 0;">
                            <input type="checkbox" name="notify_users" id="notifyUsers">
                            <span style="font-size: 0.875rem;">Notify users about this update</span>
                        </label>
                    `;
                    document.querySelector('.modal-footer').insertBefore(notifyCheckbox, document.querySelector('.modal-footer').firstChild);
                    
                    // Show modal
                    document.getElementById('eventModal').classList.add('active');
                    document.body.style.overflow = 'hidden';
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'Failed to load event data. Please try again.',
                    });
                });
        }

        function closeEventModal() {
            document.getElementById('eventModal').classList.remove('active');
            document.body.style.overflow = '';
            // Remove notification checkbox if it exists
            const notifyCheckbox = document.querySelector('.modal-footer label');
            if (notifyCheckbox) {
                notifyCheckbox.remove();
            }
        }
        
        function viewEvent(id) {
            fetch('get_event.php?id=' + id)
                .then(response => response.json())
                .then(response => {
                    if (!response.success) {
                        throw new Error(response.error);
                    }
                    
                    const data = response.data;
                    
                    // Populate view modal with event details
                    document.getElementById('viewModalTitle').textContent = data.event_title;
                    document.getElementById('viewEventImage').src = data.event_image;
                    document.getElementById('viewEventDate').textContent = data.formatted_date;
                    document.getElementById('viewEventTime').textContent = `${data.formatted_start_time} - ${data.formatted_end_time}`;
                    document.getElementById('viewEventPlace').textContent = data.event_place;
                    document.getElementById('viewEventDescription').textContent = data.event_description;
                    
                    // Show modal
                    document.getElementById('viewEventModal').classList.add('active');
                    document.body.style.overflow = 'hidden';
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'Failed to load event data. Please try again.',
                    });
                });
        }

        function closeViewModal() {
            document.getElementById('viewEventModal').classList.remove('active');
            document.body.style.overflow = '';
        }
        
        function confirmDelete(id, title) {
            Swal.fire({
                title: 'Delete Event',
                text: `Are you sure you want to delete "${title}"? This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('deleteEventId').value = id;
                    document.getElementById('deleteForm').submit();
                }
            });
        }
        
        // Form validation and submission
        document.getElementById('eventForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const startTime = document.getElementById('eventstarttime').value;
            const endTime = document.getElementById('eventendtime').value;
            
            if (startTime >= endTime) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Time',
                    text: 'End time must be after start time',
                });
                return;
            }

            // Add appropriate hidden input based on operation
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            
            // Check if we're updating or creating
            if (document.getElementById('eventId').value) {
                hiddenInput.name = 'update_event';
            } else {
                hiddenInput.name = 'post_event';
            }
            
            hiddenInput.value = '1';
            this.appendChild(hiddenInput);

            // Show loading state
            Swal.fire({
                title: document.getElementById('eventId').value ? 'Updating Event...' : 'Posting Event...',
                text: 'Please wait...',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                    setTimeout(() => {
                        this.submit();
                    }, 100);
                }
            });
        });
        
        // SweetAlert for success/error messages
        <?php if (isset($_SESSION['success_message'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '<?php echo $_SESSION['success_message']; ?>',
            });
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '<?php echo $_SESSION['error_message']; ?>',
            });
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
    </script>
</body>
</html>