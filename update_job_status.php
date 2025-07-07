<?php
session_start();
include 'sqlconnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job_id = $_POST['job_id'];
    $status = $_POST['status'];

    // Debugging: Log received data
    error_log("Received job_id: $job_id, status: $status");

    // Validate the status
    if (!in_array($status, ['open', 'closed'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    // Update the job status in the database
    $sql = "UPDATE job_listings SET status = ? WHERE job_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $job_id);

    if ($stmt->execute()) {
        // Debugging: Log success
        error_log("Job status updated successfully for job_id: $job_id");
        echo json_encode(['success' => true]);
    } else {
        // Debugging: Log error
        error_log("Failed to update job status for job_id: $job_id");
        echo json_encode(['success' => false, 'message' => 'Failed to update job status']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>