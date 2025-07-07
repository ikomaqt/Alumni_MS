<?php
include 'sqlconnection.php';

$lrn = $_GET['lrn'];

$sql = "SELECT lrn, name, address, employment_status, email, phone, graduation_year FROM alumni WHERE lrn = '$lrn'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo json_encode($user);
} else {
    echo json_encode([]);
}

$conn->close();
?>
