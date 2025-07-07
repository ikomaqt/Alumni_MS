<?php
include 'sqlconnection.php';

$user_id = $_GET['user_id'];

$sql = "SELECT user_id, first_name, middle_name, last_name, gender, birthdate, graduation_year, email, phone_number, address, skills, work_experience, job_interest, username, resume_file, profile_img, role, created_at, employment_status FROM users WHERE user_id = '$user_id'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "
        <div class='modal-card'>
            <h3>Profile Image</h3>
            <p><img src='uploads/{$row['profile_img']}' alt='Profile Image' width='100'></p>
        </div>
        <div class='modal-card'>
            <h3>User ID</h3>
            <p>{$row['user_id']}</p>
        </div>
        <div class='modal-card'>
            <h3>First Name</h3>
            <p>{$row['first_name']}</p>
        </div>
        <div class='modal-card'>
            <h3>Middle Name</h3>
            <p>{$row['middle_name']}</p>
        </div>
        <div class='modal-card'>
            <h3>Last Name</h3>
            <p>{$row['last_name']}</p>
        </div>
        <div class='modal-card'>
            <h3>Gender</h3>
            <p>{$row['gender']}</p>
        </div>
        <div class='modal-card'>
            <h3>Birthdate</h3>
            <p>{$row['birthdate']}</p>
        </div>
        <div class='modal-card'>
            <h3>Graduation Year</h3>
            <p>{$row['graduation_year']}</p>
        </div>
        <div class='modal-card'>
            <h3>Email</h3>
            <p>{$row['email']}</p>
        </div>
        <div class='modal-card'>
            <h3>Phone Number</h3>
            <p>{$row['phone_number']}</p>
        </div>
        <div class='modal-card'>
            <h3>Address</h3>
            <p>{$row['address']}</p>
        </div>
        <div class='modal-card'>
            <h3>Skills</h3>
            <p>{$row['skills']}</p>
        </div>
        <div class='modal-card'>
            <h3>Work Experience</h3>
            <p>{$row['work_experience']}</p>
        </div>
        <div class='modal-card'>
            <h3>Job Interest</h3>
            <p>{$row['job_interest']}</p>
        </div>
        <div class='modal-card'>
            <h3>Username</h3>
            <p>{$row['username']}</p>
        </div>
        <div class='modal-card'>
            <h3>Resume File</h3>
            <p><a href='uploads/{$row['resume_file']}' target='_blank'>View Resume</a></p>
        </div>
        <div class='modal-card'>
            <h3>Role</h3>
            <p>{$row['role']}</p>
        </div>
        <div class='modal-card'>
            <h3>Created At</h3>
            <p>{$row['created_at']}</p>
        </div>
        <div class='modal-card'>
            <h3>Employment Status</h3>
            <p>{$row['employment_status']}</p>
        </div>
    ";
} else {
    echo "<p>No details found for this user.</p>";
}

$conn->close();
?>
