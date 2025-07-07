<?php
include 'sqlconnection.php'; // Include your database connection file

// Check if the job ID is set in the URL
if (isset($_GET['id'])) {
    $job_id = $_GET['id'];

    // Fetch job details from the database
    $sql = "SELECT job_listings.*, job_categories.category_name 
            FROM job_listings 
            LEFT JOIN job_categories ON job_listings.category_id = job_categories.category_id 
            WHERE job_listings.job_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the job exists
    if ($result->num_rows > 0) {
        $job = $result->fetch_assoc();
    } else {
        echo "Job not found.";
        exit;
    }
} else {
    echo "No job ID provided.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($job['title']); ?> - Job Details</title>
    <link rel="stylesheet" href="css/view_job.css"> <!-- Link to your CSS file -->
    <style>
        body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    padding: 20px;
}

.container {
    max-width: 800px;
    margin: auto;
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

h1 {
    color: #333;
}

.job-details {
    margin-top: 20px;
}

.job-details h2 {
    color: #007BFF;
}

.job-details p {
    line-height: 1.6;
}

.back-button {
    display: inline-block;
    margin-top: 20px;
    padding: 10px 15px;
    background-color: #007BFF;
    color: white;
    text-decoration: none;
    border-radius: 5px;
}

.back-button:hover {
    background-color: #0056b3;
}
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($job['title']); ?></h1>
        <div class="job-details">
            <h2>Company: <?php echo htmlspecialchars($job['company_name']); ?></h2>
            <p><strong>Category:</strong> <?php echo htmlspecialchars($job['category_name']); ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?></p>
            <p><strong>Type:</strong> <?php echo htmlspecialchars($job['type']); ?></p>
            <p><strong>Salary:</strong> <?php echo htmlspecialchars($job['salary']); ?></p>
            <h3>Job Description</h3>
            <p><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
            <h3>Requirements</h3>
            <p><?php echo nl2br(htmlspecialchars($job['requirements'])); ?></p>
            <h3>About the Company</h3>
            <p><?php echo nl2br(htmlspecialchars($job['company_description'])); ?></p>
            <h3>Contact Information</h3>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($job['company_contact_email']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($job['company_contact_phone']); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($job['status']); ?></p>
        </div>
        <a href="job_list_new.php" class="back-button">Back to Job Listings</a>
    </div>
</body>
</html>