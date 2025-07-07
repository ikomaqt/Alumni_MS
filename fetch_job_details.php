<?php
include 'sqlconnection.php'; // Database connection

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $job_id = $_GET['id'];
    
    // Fetch the selected job from the database
    $sql = "SELECT * FROM job_listings WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'Job not found']);
    }
} else {
    echo json_encode(['error' => 'Invalid job selection']);
}

$conn->close();
?>