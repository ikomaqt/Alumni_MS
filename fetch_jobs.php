<?php
include 'sqlconnection.php';

// Pagination logic
$jobsPerPage = 5; // Number of jobs to display per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page number
$offset = ($page - 1) * $jobsPerPage; // Calculate the offset for the SQL query

// Fetch jobs for the current page
$sql = "SELECT * FROM job_listings WHERE status != 'archived' LIMIT $jobsPerPage OFFSET $offset";
$result = $conn->query($sql);

$jobs = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($jobs);
?>