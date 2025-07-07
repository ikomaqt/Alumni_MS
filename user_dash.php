<?php
session_start();
include 'sqlconnection.php';
include 'user_navbar.php';
// Pagination logic
$jobsPerPage = 5; // Number of jobs to display per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page number
$offset = ($page - 1) * $jobsPerPage; // Calculate the offset for the SQL query

// Fetch total number of jobs
$sqlTotal = "SELECT COUNT(*) as total FROM job_listings WHERE status != 'archived'";
$resultTotal = $conn->query($sqlTotal);
$totalJobs = $resultTotal->fetch_assoc()['total'];
$totalPages = ceil($totalJobs / $jobsPerPage); // Calculate total pages

// Fetch jobs for the current page
$sql = "SELECT * FROM job_listings WHERE status != 'archived' LIMIT $jobsPerPage OFFSET $offset";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Job Offerings</title>
    <link rel="stylesheet" href="css/user.css">
    <style>
        /* General Styles */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        .job-container {
            display: flex;
            gap: 20px;
            padding: 20px;
            justify-content: space-between;
            align-items: flex-start;
        }

        .job-list {
            width: 40%;
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
        }

        .job-card {
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
            cursor: pointer;
            background-color: #fff;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .job-card:hover {
            background-color: #f9f9f9;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .job-card h3 {
            margin: 0;
            font-size: 25px;
            color: #333;
        }

        .job-card p {
            margin: 5px 0;
            font-size: 20px;
            color: #666;
        }

        .job-card .status {
            color: black;
            font-weight: bold;
        }

        .job-details {
            width: 55%;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .details-card {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            height: 829px;
        }

        .details-card h2 {
            margin-bottom: 15px;
            font-size: 25px;
            color: #333;
        }

        .details-card p {
            margin: 10px 0;
            font-size: 23px;
            color: #555;
        }

        .details-card .apply-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-size: 20px;
            transition: background-color 0.3s ease;
        }

        .details-card .apply-btn:hover {
            background-color: #0056b3;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 10px;
        }

        .pagination a {
            padding: 8px 12px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .pagination a:hover {
            background-color: #0056b3;
        }

        .pagination .current-page {
            padding: 8px 12px;
            background-color: #0056b3;
            color: #fff;
            border-radius: 5px;
            font-size: 14px;
        }

        @media screen and (max-width: 768px) {
            .job-container {
                flex-direction: column;
                padding: 10px;
            }

            .job-list, .job-details {
                width: 95%;
                padding: 10px;
            }

            /* Hide job details on mobile */
            .job-details {
                display: none;
            }

            .job-card {
                padding: 10px;
            }

            .job-card h3 {
                font-size: 20px;
            }

            .job-card p {
                font-size: 16px;
            }

            .pagination {
                flex-wrap: wrap;
            }

            .pagination a, .pagination .current-page {
                padding: 6px 10px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>

    <!-- Navigation Bar -->
    

    <!-- Hero Section -->
   

    <!-- Job Container -->
    <div class="job-container">
        <!-- Job Listings (Left) -->
        <div class="job-list">
            <?php
            if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo '<div class="job-card" data-job-id="' . $row["users_id"] . '" onclick="showJobDetails(`' . htmlspecialchars(json_encode($row)) . '`)">';
        echo '<h3>' . $row["title"] . '</h3>';
        echo '<p>' . $row["type"] . '</p>';
        echo '<p class="status">' . $row["status"] . '</p>';
        echo '</div>';
    }
} else {
    echo '<p>No job listings available.</p>';
}

            ?>

            <!-- Pagination Controls -->
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>">Previous</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current-page"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>">Next</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Job Details (Right) - Initially Empty -->
        <div class="job-details" id="jobDetails">
            <div class="details-card">
                <h2>Select a job</h2>
                <p>Click on a job listing to view details here.</p>
            </div>
        </div>
    </div>

    <script>
        // Background Image Slideshow
        const images = [
            "img/aski_bg.jpg",
            "img/aski_bg1.jpg",
            "img/sample.jpg",
        ];

        let currentIndex = 0;
        const heroSections = document.querySelectorAll(".hero");

        function changeBackground() {
            heroSections.forEach((hero, index) => {
                hero.style.backgroundImage = `url(${images[(currentIndex + index) % images.length]})`;
            });
            currentIndex = (currentIndex + 1) % images.length;
        }

        setInterval(changeBackground, 3000); // Change image every 3 seconds
        changeBackground(); // Initialize the first images

        // Function to show job details
        function showJobDetails(jobData) {
            const job = JSON.parse(jobData);
            document.getElementById("jobDetails").innerHTML = 
                `<div class="details-card">
                    <h2>${job.title}</h2>
                    <p><strong>Company:</strong> ${job.company}</p>
                    <p><strong>Location:</strong> ${job.location}</p>
                    <p><strong>Type:</strong> ${job.type}</p>
                    <p><strong>Salary:</strong> ${job.salary || "Not specified"}</p>
                    <p><strong>Description:</strong> ${job.description}</p>
                    <a href="#" class="apply-btn">Apply Now</a>
                </div>`;
        }

        // Store scroll position before navigating
        document.addEventListener('DOMContentLoaded', function() {
            const paginationLinks = document.querySelectorAll('.pagination a');
            paginationLinks.forEach(link => {
                link.addEventListener('click', function(event) {
                    // Store the current scroll position
                    localStorage.setItem('scrollPosition', window.scrollY);
                });
            });

            // Restore scroll position after page load
            const scrollPosition = localStorage.getItem('scrollPosition');
            if (scrollPosition) {
                window.scrollTo(0, parseInt(scrollPosition));
                localStorage.removeItem('scrollPosition'); // Clear the stored position
            }
        });

        // Function to redirect to job details page on mobile
        // Function to redirect to job details page on mobile
function redirectToJobDetails(jobId) {
    window.location.href = `job_display.php?id=${jobId}`;
}

// Add click event listeners to job cards
document.querySelectorAll('.job-card').forEach(card => {
    card.addEventListener('click', function () {
        const jobId = this.getAttribute('data-job-id');
        if (window.innerWidth <= 768) { // Check if the screen is mobile
            redirectToJobDetails(jobId); // Redirect to job details page
        } else {
            // Show job details on desktop (existing functionality)
            fetch(`fetch_job_display.php?id=${jobId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById("jobDetails").innerHTML = `
                        <div class="details-card">
                            <h2>${data.title}</h2>
                            <p><strong>Company:</strong> ${data.company}</p>
                            <p><strong>Location:</strong> ${data.location}</p>
                            <p><strong>Type:</strong> ${data.type}</p>
                            <p><strong>Salary:</strong> ${data.salary || "Not specified"}</p>
                            <p><strong>Description:</strong> ${data.description}</p>
                            <a href="#" class="apply-btn">Apply Now</a>
                        </div>`;
                })
                .catch(error => console.error('Error fetching job details:', error));
        }
    });
});

document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".job-card").forEach(card => {
        card.addEventListener("click", function () {
            const jobData = this.getAttribute("data-job");
            const job = JSON.parse(jobData);
            const jobId = job.id; // Assuming 'id' is the primary key in the database

            if (window.innerWidth <= 768) {
                // Redirect to job_display.php on mobile
                window.location.href = `job_display.php?id=${jobId}`;
            } else {
                // Display job details on desktop
                document.getElementById("jobDetails").innerHTML = `
                    <div class="details-card">
                        <h2>${job.title}</h2>
                        <p><strong>Company:</strong> ${job.company}</p>
                        <p><strong>Location:</strong> ${job.location}</p>
                        <p><strong>Type:</strong> ${job.type}</p>
                        <p><strong>Salary:</strong> ${job.salary || "Not specified"}</p>
                        <p><strong>Description:</strong> ${job.description}</p>
                        <a href="#" class="apply-btn">Apply Now</a>
                    </div>`;
            }
        });
    });
});

    </script>
</body>
</html>