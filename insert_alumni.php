<?php
include 'sqlconnection.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['lrn'], $data['name'], $data['year'], $data['address'])) {
    $lrn = mysqli_real_escape_string($conn, $data['lrn']);
    $name = mysqli_real_escape_string($conn, $data['name']);
    $year = mysqli_real_escape_string($conn, $data['year']);
    $address = mysqli_real_escape_string($conn, $data['address']);

    $query = "INSERT INTO alumni (lrn, name, graduation_year, address) VALUES ('$lrn', '$name', '$year', '$address')";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false]);
    }
} else {
    echo json_encode(["success" => false]);
}
?>
