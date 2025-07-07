<?php
include 'sqlconnection.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_GET['job_id'])) {
    die(json_encode([
        'success' => false,
        'error' => 'Invalid request parameters'
    ]));
}

$job_id = intval($_GET['job_id']);
$user_id = $_SESSION['user_id'];

// First verify that this job belongs to the current user
$verify_sql = "SELECT job_id FROM job_listings WHERE job_id = ? AND posted_by_id = ?";
$verify_stmt = $conn->prepare($verify_sql);
$verify_stmt->bind_param("ii", $job_id, $user_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    die(json_encode([
        'success' => false,
        'error' => 'Unauthorized access'
    ]));
}

// Get applicants for the specific job
$sql = "SELECT 
            application_id,
            job_id,
            user_id,
            name,
            email,
            phone,
            resume_path,
            cover_letter,
            DATE_FORMAT(applied_at, '%M %d, %Y') as applied_at,
            status
        FROM job_applications 
        WHERE job_id = ?
        ORDER BY applied_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();

$applicants = [];
while ($row = $result->fetch_assoc()) {
    $applicants[] = [
        'application_id' => $row['application_id'],
        'name' => htmlspecialchars($row['name']),
        'email' => htmlspecialchars($row['email']),
        'phone' => htmlspecialchars($row['phone']),
        'resume_path' => htmlspecialchars($row['resume_path']),
        'cover_letter' => htmlspecialchars($row['cover_letter']),
        'applied_at' => $row['applied_at'],
        'status' => htmlspecialchars($row['status'])
    ];
}

echo json_encode([
    'success' => true,
    'count' => count($applicants),
    'applicants' => $applicants
]);
