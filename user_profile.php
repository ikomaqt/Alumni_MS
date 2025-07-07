<?php
session_start(); // Start session to store messages
include 'sqlconnection.php'; // Database connection

// Assuming you have a logged-in user, retrieve their ID
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];  // Ensure user_id is stored in session

// Fetch user data from the database using 'user_id' as the unique identifier
$query = "SELECT * FROM users WHERE user_id = '$user_id'"; // Adjusted to use 'user_id'
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    // Redirect to login if user is not found
    header("Location: login.php");
    exit();
}

// Set default profile image if not uploaded
$profile_img = !empty($user['profile_img']) ? $user['profile_img'] : 'profile_img/default.png'; // Default image
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile UI</title>
    <link rel="stylesheet" href="css/profile.css">
    <style>
        .edit-buttons {
            display: none;
            margin-left: auto;
        }

        .edit-buttons button {
            margin-left: 5px;
            padding: 4px 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .edit-btn {
            background-color: #007bff;
            color: white;
        }

        .save-btn {
            background-color: #28a745;
            color: white;
        }

        .cancel-btn {
            background-color: #dc3545;
            color: white;
        }

        .content input, .content textarea {
            width: 100%;
            padding: 5px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .profile-pic {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <div class="profile-section">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-left">
                <!-- Profile Picture -->
                <img src="<?php echo $profile_img; ?>" alt="Profile Picture" class="profile-pic">
            </div>
            <div class="profile-right">
                <h1><?php echo $user['firstname'] . ' ' . $user['lastname']; ?></h1>
                <div class="profile-details">
                    <p>Email: <?php echo $user['email']; ?></p>
                </div> 
                <!-- Edit Profile Button -->
                <!-- Edit Profile Button -->
<button class="edit-profile-btn" onclick="window.location.href='edit_profile.php';">Edit Profile</button>

            </div>
        </div>

        <!-- Resume Section -->
        <div class="section" id="resume">
            <div class="section-header" onclick="toggleSection(this)">
                <span>Resume</span>
                <span class="icon">▾</span>
            </div>
            <div class="content">
                <button class="upload-resume-btn">Upload Resume</button>
            </div>
        </div>

        <!-- Work Experience Section -->
        <div class="section" id="work-experience">
            <div class="section-header" onclick="toggleSection(this)">
                <span>Work Experience</span>
                <span class="icon">▾</span>
            </div>
            <div class="content">
                <h3><?php echo $user['work_experience']; ?></h3>
            </div>
        </div>

        <!-- Education Section -->
        <div class="section" id="education">
            <div class="section-header" onclick="toggleSection(this)">
                <span>Education</span>
                <span class="icon">▾</span>
            </div>
            <div class="content">
                <h3><?php echo $user['education']; ?></h3>
            </div>
        </div>

        <!-- Skills Section -->
        <div class="section" id="skills">
            <div class="section-header" onclick="toggleSection(this)">
                <span>Skills</span>
                <span class="icon">▾</span>
            </div>
            <div class="content">
                <ul>
                    <?php
                    // Assuming skills are stored as a comma-separated string
                    $skills = explode(",", $user['skills']);
                    foreach ($skills as $skill) {
                        echo "<li>$skill</li>";
                    }
                    ?>
                </ul>
            </div>
        </div>

        <!-- Job Interest Section -->
        <div class="section" id="job-interest">
            <div class="section-header" onclick="toggleSection(this)">
                <span>Job Interest</span>
                <span class="icon">▾</span>
            </div>
            <div class="content">
                <p>Looking for roles in the following fields:</p>
                <ul>
                    <?php
                    // Assuming job_interest is stored as a comma-separated string
                    $job_interests = explode(",", $user['job_interest']);
                    foreach ($job_interests as $job) {
                        echo "<li>$job</li>";
                    }
                    ?>
                </ul>
            </div>
        </div>

        <!-- Logout Button -->
        <button class="logout-btn">Logout</button>
    </div>

    <script>
        function toggleSection(element) {
            const content = element.nextElementSibling;
            const icon = element.querySelector('.icon');
            const editButtons = element.querySelector('.edit-buttons');

            if (content.style.display === 'none' || !content.style.display) {
                content.style.display = 'block';
                icon.innerText = '▴';
                editButtons.style.display = 'flex';
            } else {
                content.style.display = 'none';
                icon.innerText = '▾';
                editButtons.style.display = 'none';
            }
        }

        function editSection(button) {
            const section = button.closest('.section');
            const content = section.querySelector('.content');

            // Convert text into input fields
            content.querySelectorAll('p, h3, ul li').forEach(el => {
                const input = document.createElement(el.tagName === 'LI' ? 'input' : 'textarea');
                input.value = el.innerText;
                el.replaceWith(input);
            });

            // Show Save & Cancel buttons, hide Edit
            section.querySelector('.edit-btn').style.display = 'none';
            section.querySelector('.save-btn').style.display = 'inline-block';
            section.querySelector('.cancel-btn').style.display = 'inline-block';
        }

        function saveSection(button) {
            const section = button.closest('.section');
            const content = section.querySelector('.content');

            // Convert inputs back to text
            content.querySelectorAll('input, textarea').forEach(input => {
                const text = document.createElement(input.tagName === 'INPUT' ? 'li' : 'p');
                text.innerText = input.value;
                input.replaceWith(text);
            });

            // Hide Save & Cancel, show Edit
            section.querySelector('.edit-btn').style.display = 'inline-block';
            section.querySelector('.save-btn').style.display = 'none';
            section.querySelector('.cancel-btn').style.display = 'none';
        }

        function cancelEdit(button) {
            const section = button.closest('.section');
            toggleSection(section.querySelector('.section-header'));
        }
    </script>
</body>
</html>