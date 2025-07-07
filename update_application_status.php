<?php
session_start();
include 'sqlconnection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['application_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$application_id = (int)$_POST['application_id'];
$status = $_POST['status'];
$user_id = $_SESSION['user_id'];

// Verify that this application belongs to a job posted by the current user
$sql = "SELECT ja.* FROM job_applications ja 
        JOIN job_listings jl ON ja.job_id = jl.job_id 
        WHERE ja.application_id = ? AND jl.posted_by_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $application_id, $user_id);
$stmt->execute();

if ($stmt->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Update the application status
$sql = "UPDATE job_applications SET status = ? WHERE application_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $status, $application_id);

$success = $stmt->execute();
echo json_encode(['success' => $success]);
