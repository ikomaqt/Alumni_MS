<?php
include 'user_navbar.php';
include 'sqlconnection.php';

if (!isset($_SESSION['users_id'])) {
    header("Location: login.php"); 
    exit();
}

$user_id = $_SESSION['users_id']; // Get the session user ID

// Fetch user details
$stmt = $conn->prepare("SELECT firstname, middlename, lastname, birthdate, email, education, work_experience, skills, job_interest, profile_img FROM users WHERE users_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User  not found in database. Please log in again.");
}

$fullname = htmlspecialchars($user['firstname'] . ' ' . $user['middlename'] . ' ' . $user['lastname']);
$profile_img = !empty($user['profile_img']) ? htmlspecialchars($user['profile_img']) : 'https://via.placeholder.com/100';
$email = htmlspecialchars($user['email']);
$education = htmlspecialchars($user['education']);
$work_experience = htmlspecialchars($user['work_experience']);
$skills = htmlspecialchars($user['skills']);
$job_interest = htmlspecialchars($user['job_interest']);

// Function to fetch job listings from the database
function fetchJobListings($conn) {
    $sql = "SELECT * FROM job_listings WHERE status = 'open'"; // Fetch all open jobs
    $result = $conn->query($sql);
    
    if (!$result) {
        die("Query failed: " . $conn->error); // Error handling
    }

    $jobListings = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $jobListings[] = $row;
        }
    }
    
    return $jobListings;
}

// Fetch job listings
$jobListings = fetchJobListings($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/home.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f3f2ef;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            padding: 20px;
        }

        .main-content {
            display: flex;
            gap: 20px;
        }

        /* User Profile */
        .user_profile {
            width: 300px;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 20px;
        }

        .profile_img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-bottom: 15px;
        }

        .edit_btn {
            width: 100%;
            background-color: #0073b1;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: 0.3s;
        }

        .edit_btn:hover {
            background-color: #005582;
        }

        /* Job List Section */
        .job_listings {
            flex: 1;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .single_job {
            display: flex;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #ddd;
            transition: 0.3s;
        }

        .single_job:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }

        .job_thumb img {
            width: 50px;
            height: 50px;
        }

        .job_details {
            flex: 1;
        }

        .job_details h5 {
            font-size: 18px;
            margin-bottom: 5px;
            color: #333;
        }

        .job_details p {
            font-size: 14px;
            color: #777;
        }
        .job-card {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }

        .job-card:hover {
            transform: translateY(-5px);
        }

        .user-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .user-details {
            display: flex;
            align-items: center;
        }

        .profile-pic {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
            border: 2px solid #ffcc00;
        }

        .user-info h4 {
            margin: 0;
            font-size: 16px;
            color: #333;
        }

        .user-info p {
            margin: 0;
            font-size: 12px;
            color: gray;
        }

        .bookmark {
            font-size: 24px;
            cursor: pointer;
            transition: color 0.3s ease-in-out;
            color: gray;
        }

        .bookmark.active {
            color: gold;
        }

        .job-details h3 {
            margin: 10px 0;
            font-size: 20px;
            color: #007bff;
        }

        .job-details p {
            margin: 5px 0;
            font-size: 14px;
            color: #555;
        }

        .job-description {
            margin: 10px 0;
            font-size: 14px;
            color: #666;
        }

        .email-btn {
            background: #ffcc00;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            text-align: center;
            display: block;
            transition: background 0.3s;
        }

        .email-btn:hover {
            background: #e6b800;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                flex-direction: column;
            }

            .user_profile {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-content">

            <!-- User Profile (Left Side) -->
            <div class="col-md-3 offset-md-1 sidebar-hidden">
                <div class="profile-card" onclick="window.location.href='profile.php';">
                    <div class="cover-photo-container">
                        <img src="img/cover.jpg" alt="Cover Photo" class="cover-photo">
                    </div>
                    <div class="profile-picture-container">
                        <img src="<?= $profile_img ?>" alt="Profile Picture" class="profile-picture">
                    </div>
                    <div class="profile-name"><?= $fullname ?></div>
                </div>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Saved Posts & Events</h5>
                    </div>
                    <div class="card-body">
                        <h6>Saved Posts</h6>
                        <ul id="saved-posts"></ul>
                        <hr>
                        <h6>Saved Events</h6>
                        <ul id="saved-events"></ul>
                    </div>
                </div>
            </div>

            <!-- Job Listings (Right Side) -->
            <div class="job_listings">
                <h4>Open Job Listings</h4>
                <?php foreach ($jobListings as $job): ?>
                    <div class="single_job">
                        <div class="job_thumb">
                            <img src="img/svg_icon/1.svg" alt="Job">
                        </div>
                        <div class="job_details">
                            <h5><?= htmlspecialchars($job['title']) ?></h5>
                            <p><?= htmlspecialchars($job['location']) ?> | <?= htmlspecialchars($job['type']) ?></p>
                            <p>Salary: <?= htmlspecialchars($job['salary']) ?></p>
                            <p>Posted on: <?= htmlspecialchars($job['created_at']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</body>
</html>