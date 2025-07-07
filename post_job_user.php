<?php
session_start(); 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); 
    exit();
}

include 'sqlconnection.php';


function getCategories($conn) {
    $sql = "SELECT category_id, category_name FROM job_categories";
    $result = $conn->query($sql);
    $categories = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    return $categories;
}

// Function to insert a new job listing into the database
function postJob($conn, $title, $company, $location, $employment_type, $job_type, $category, $salary_range, $description, $requirements, $contact_email, $posted_by_id) {
    $posted_at = date('Y-m-d H:i:s'); // Current timestamp
    $status = 'open'; // Default status for new job listings
    $posted_by_type = 'user'; // Assuming the posted_by_type is always 'user'

    // SQL query to insert data into the job_listings table
    $sql = "INSERT INTO job_listings (title, company, location, employment_type, job_type, category, salary_range, description, requirements, contact_email, posted_at, posted_by_id, posted_by_type, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    // Prepare the SQL statement
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }

    // Bind parameters to the SQL statement
    $stmt->bind_param("ssssssssssssss", $title, $company, $location, $employment_type, $job_type, $category, $salary_range, $description, $requirements, $contact_email, $posted_at, $posted_by_id, $posted_by_type, $status);
    
    // Execute the statement and return true/false based on success
    return $stmt->execute();
}

// Fetch categories from the database
$categories = getCategories($conn);

// Handle form submission for new job posting
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['job-title'])) {
    // Retrieve form data
    $title = $_POST['job-title'];
    $company = $_POST['company'];
    $location = $_POST['job-location'];
    $employment_type = $_POST['employment-type'];
    $job_type = $_POST['job-type'];
    $category = $_POST['job-category'];
    $salary_range = $_POST['salary-range'];
    $description = $_POST['job-description'];
    $requirements = $_POST['job-requirements'];
    $contact_email = $_POST['contact-email'];
    $posted_by_id = $_SESSION['user_id']; // Get the user ID from the session

    // Call the postJob function to insert data into the database
    if (postJob($conn, $title, $company, $location, $employment_type, $job_type, $category, $salary_range, $description, $requirements, $contact_email, $posted_by_id)) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Job posted successfully!',
                    confirmButtonColor: '#3085d6'
                }).then(() => {
                    window.location.href = 'home.php';
                });
            });
        </script>";
    } else {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to post job. Please try again!',
                    confirmButtonColor: '#d33'
                });
            });
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post a Job</title>
    <link rel="stylesheet" href="css/post_job_user.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Your existing CSS styles */
    </style>
</head>
<body>
    <div class="card-container">
        <div class="card">
            <h1>POST A JOB</h1>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Job Title</label>
                    <input type="text" class="form-control" id="job-title" name="job-title" placeholder="Job Title" required>
                </div>
                <div class="form-group">
                    <label>Company Name</label>
                    <input type="text" class="form-control" id="company" name="company" placeholder="Company Name" required>
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" class="form-control" id="job-location" name="job-location" placeholder="Location" required>
                </div>
                <div class="form-group">
                    <label>Employment Type</label>
                    <select id="employment-type" name="employment-type" class="form-control" required>
                        <option value="">Select Employment Type</option>
                        <option value="Full-time">Full-time</option>
                        <option value="Part-time">Part-time</option>
                        <option value="Contract">Contract</option>
                        <option value="Internship">Internship</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Job Type</label>
                    <select id="job-type" name="job-type" class="form-control" required>
                        <option value="">Select Job Type</option>
                        <option value="On-site">On-site</option>
                        <option value="Remote">Remote</option>
                        <option value="Hybrid">Hybrid</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Job Category</label>
                    <select id="job-category" name="job-category" class="form-control" required>
                        <option value="">Select Job Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>"><?php echo $category['category_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Salary Range</label>
                    <input type="text" class="form-control" id="salary-range" name="salary-range" placeholder="Salary Range">
                </div>
                <div class="form-group">
                    <label>Job Description</label>
                    <textarea class="form-control" id="job-description" name="job-description" placeholder="Job Description" required maxlength="500" oninput="updateCounter('job-description', 'desc-counter', 500)"></textarea>
                    <small id="desc-counter">0/500</small>
                </div>
                <div class="form-group">
                    <label>Requirements</label>
                    <textarea class="form-control" id="job-requirements" name="job-requirements" placeholder="Requirements" required maxlength="500" oninput="updateCounter('job-requirements', 'req-counter', 500)"></textarea>
                    <small id="req-counter">0/500</small>
                </div>
                <div class="form-group">
                    <label>Contact Email</label>
                    <input type="email" class="form-control" id="contact-email" name="contact-email" placeholder="Contact Email" required>
                </div>
                <div class="form-btn">
                    <button type="button" class="btn btn-back" onclick="window.location.href='home.php'">Back</button>
                    <button type="submit" class="btn">Post Job</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function updateCounter(textareaId, counterId, maxLength) {
            const textarea = document.getElementById(textareaId);
            const counter = document.getElementById(counterId);
            counter.textContent = `${textarea.value.length}/${maxLength}`;
        }
    </script>
</body>
</html>