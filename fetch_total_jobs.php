<?php
include 'sqlconnection.php';

// Fetch total number of jobs
$sqlTotal = "SELECT COUNT(*) as total FROM job_listings WHERE status != 'archived'";
$resultTotal = $conn->query($sqlTotal);
$totalJobs = $resultTotal->fetch_assoc()['total'];

// Return JSON response
header('Content-Type: application/json');
echo json_encode(['total' => $totalJobs]);
?>