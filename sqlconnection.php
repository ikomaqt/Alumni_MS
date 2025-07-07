<?php
$conn = mysqli_connect('localhost', 'root', '', 'aski_db');

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>