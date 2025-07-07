<?php
session_start();
include 'sqlconnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job_id = $_POST['job_id'];
    $user_id = $_SESSION['user_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    
    // Handle resume upload
    $resume_path = '';
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === 0) {
        $upload_dir = 'uploads/resumes/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $filename = uniqid() . '_' . $_FILES['resume']['name'];
        $resume_path = $upload_dir . $filename;
        move_uploaded_file($_FILES['resume']['tmp_name'], $resume_path);
    }

    $cover_letter = $_POST['cover_letter'];
    $status = 'pending'; // Default status
    
    $sql = "INSERT INTO job_applications (job_id, user_id, name, email, phone, resume_path, cover_letter, applied_at, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissssss", $job_id, $user_id, $name, $email, $phone, $resume_path, $cover_letter, $status);
    
    if ($stmt->execute()) {
        header("Location: view_job.php?id=" . $job_id . "&applied=1");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
