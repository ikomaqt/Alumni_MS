<?php
include 'sqlconnection.php'; // Include your database connection file
include 'user_navbar.php';

session_start(); // Start the session to access user data

// Fetch user's job interests
$user_id = $_SESSION['user_id']; // Assuming you store user_id in the session
$sql_user_interests = "SELECT job_interest FROM users WHERE user_id = ?";
$stmt_user_interests = $conn->prepare($sql_user_interests);
$stmt_user_interests->bind_param("i", $user_id);
$stmt_user_interests->execute();
$result_user_interests = $stmt_user_interests->get_result();

$user_interests = [];
if ($result_user_interests->num_rows > 0) {
    $row = $result_user_interests->fetch_assoc();
    $user_interests = explode(',', $row['job_interest']); // Assuming job_interest is a comma-separated string
}

// Fetch job listings based on user's job interests
$sql_all_jobs = "SELECT j.*, c.category_name
                 FROM job_listings j
                 LEFT JOIN job_categories c ON j.category = c.category_name
                 WHERE j.status = 'open'
                 AND c.category_name IN ('" . implode("','", $user_interests) . "')
                 ORDER BY j.created_at DESC";
$result_all_jobs = $conn->query($sql_all_jobs);

$all_jobs = [];
if ($result_all_jobs->num_rows > 0) {
    while ($row = $result_all_jobs->fetch_assoc()) {
        // Check if the job is bookmarked by the user
        $job_id = $row['job_id'];
        $is_bookmarked = false;

        if ($user_id) {
            $sql_check_bookmark = "SELECT * FROM bookmarks WHERE user_id = ? AND job_id = ?";
            $stmt_check = $conn->prepare($sql_check_bookmark);
            $stmt_check->bind_param("ii", $user_id, $job_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                $is_bookmarked = true;
            }
        }

        $row['is_bookmarked'] = $is_bookmarked; // Add bookmark status to the job data
        $all_jobs[] = $row;
    }
}

// Get job ID from URL
$job_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch specific job details from the database
$job = null;
if ($job_id > 0) {
    $sql_job = "SELECT j.*, c.category_name
                FROM job_listings j
                LEFT JOIN job_categories c ON j.category = c.category_name
                WHERE j.job_id = ?";
    $stmt = $conn->prepare($sql_job);
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result_job = $stmt->get_result();

    if ($result_job->num_rows > 0) {
        $job = $result_job->fetch_assoc();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Listings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/job_info.css">
    <style>
        .message {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #0a66c2;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            z-index: 1000;
            transition: opacity 0.5s ease;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="jobs-container">
            <!-- Job Cards List -->
            <div class="jobs-list" id="jobsList">
                <?php if (!empty($all_jobs)): ?>
                    <?php foreach ($all_jobs as $job_listing): ?>
                        <div class="job-card <?php echo $job_listing['job_id'] == $job_id ? 'active' : ''; ?>" data-job-id="<?php echo $job_listing['job_id']; ?>">
                            <div class="job-title"><?php echo htmlspecialchars($job_listing['title']); ?></div>
                            <div class="job-info">
                                <span><?php echo htmlspecialchars($job_listing['company']); ?></span>
                                <span><?php echo htmlspecialchars($job_listing['location']); ?></span>
                            </div>
                            <div class="job-type"><?php echo htmlspecialchars($job_listing['employment_type']); ?></div>
                            <div class="job-category"><?php echo htmlspecialchars($job_listing['category_name']); ?></div>
                            <!-- Bookmark Icon for Job Card -->
                            <i class="fa fa-bookmark bookmark-icon <?php echo $job_listing['is_bookmarked'] ? 'active' : ''; ?>"
                               data-job-id="<?php echo $job_listing['job_id']; ?>">
                            </i>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No job listings found based on your interests.</p>
                <?php endif; ?>
            </div>

            <!-- Job Details Section -->
            <div class="job-details" id="jobDetails">
                <?php if ($job): ?>
                    <button class="back-button" id="backButton">← Back to listings</button>
                    <div class="job-details-header">
                        <h1 class="job-details-title"><?php echo htmlspecialchars($job['title']); ?></h1>
                        <div class="job-details-company">
                            <div><?php echo htmlspecialchars($job['company']); ?> • <?php echo htmlspecialchars($job['location']); ?></div>
                            <div><?php echo htmlspecialchars($job['employment_type']); ?></div>
                        </div>
                        <button class="apply-button">Apply Now</button>
                        <i class="fa fa-bookmark bookmark-icon <?php echo $job['is_bookmarked'] ? 'active' : ''; ?>"
                           id="bookmarkIcon"
                           data-job-id="<?php echo $job['job_id']; ?>">
                        </i>
                    </div>
                    <div class="job-description">
                        <p><b>Description:</b> <?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                        <p><b>Location:</b> <?php echo htmlspecialchars($job['location']); ?></p>
                        <p><b>Employment Type:</b> <?php echo htmlspecialchars($job['employment_type']); ?></p>
                        <p><b>Job Type:</b> <?php echo htmlspecialchars($job['job_type']); ?></p>
                        <p><b>Category:</b> <?php echo htmlspecialchars($job['category_name']); ?></p>
                        <p><b>Salary Range:</b> <?php echo htmlspecialchars($job['salary_range']); ?></p>
                        <p><b>Requirements:</b> <?php echo nl2br(htmlspecialchars($job['requirements'])); ?></p>
                        <p><b>Contact Email:</b> <?php echo htmlspecialchars($job['contact_email']); ?></p>
                    </div>
                <?php else: ?>
                    <p>Select a job to view details.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    const jobsList = document.getElementById('jobsList');
    const jobDetails = document.getElementById('jobDetails');

    // Function to display messages
    function showMessage(message) {
        const messageContainer = document.createElement('div');
        messageContainer.className = 'message';
        messageContainer.textContent = message;
        document.body.appendChild(messageContainer);

        // Remove the message after 3 seconds
        setTimeout(() => {
            messageContainer.remove();
        }, 3000);
    }

    // Function to handle bookmark actions
    function handleBookmark(jobId, action) {
        fetch('bookmark_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `job_id=${jobId}&action=${action}`,
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Update the bookmark icon immediately based on the action
                updateBookmarkIcon(jobId, action === 'save');

                // Show success message
                const message = action === 'save' ? 'Job bookmarked successfully!' : 'Bookmark removed successfully!';
                showMessage(message);
            } else {
                console.error(data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Function to update the bookmark icon state
    function updateBookmarkIcon(jobId, isBookmarked) {
        const bookmarkIcons = document.querySelectorAll(`.bookmark-icon[data-job-id="${jobId}"]`);
        bookmarkIcons.forEach(icon => {
            if (isBookmarked) {
                icon.classList.add('active');
            } else {
                icon.classList.remove('active');
            }
        });
    }

    // Function to show job details
    function showJobDetails(jobId) {
        // Update URL without reloading the page
        history.pushState(null, '', `job_info.php?id=${jobId}`);

        // Fetch job details via AJAX
        fetch(`job_info.php?id=${jobId}`)
            .then(response => response.text())
            .then(data => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(data, 'text/html');
                const jobDetailSection = doc.getElementById('jobDetails').innerHTML;

                // Update job details section
                jobDetails.innerHTML = jobDetailSection;

                // Update active state of job cards
                document.querySelectorAll('.job-card').forEach(card => {
                    card.classList.remove('active');
                });
                document.querySelector(`[data-job-id="${jobId}"]`).classList.add('active');

                // Show details on mobile
                jobDetails.classList.add('mobile-visible');
                if (window.innerWidth < 768) {
                    jobsList.style.display = 'none';
                }
            })
            .catch(error => console.error('Error fetching job details:', error));
    }

    // Event listeners
    jobsList.addEventListener('click', (e) => {
        const jobCard = e.target.closest('.job-card');
        if (jobCard) {
            const jobId = jobCard.dataset.jobId;
            showJobDetails(jobId);
        }
    });

    document.addEventListener('click', (e) => {
        if (e.target.id === 'backButton') {
            jobsList.style.display = 'block';
            jobDetails.classList.remove('mobile-visible');
            history.pushState(null, '', 'job_info.php'); // Reset URL
        }

        if (e.target.closest('.bookmark-icon')) {
            const jobId = e.target.dataset.jobId;
            if (e.target.classList.contains('active')) {
                handleBookmark(jobId, 'remove');
            } else {
                handleBookmark(jobId, 'save');
            }
        }
    });

    // Handle window resize
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 768) {
            jobsList.style.display = 'block';
            jobDetails.classList.remove('mobile-visible');
        }
    });

    // Initialize based on URL
    const urlParams = new URLSearchParams(window.location.search);
    const jobId = urlParams.get('id');
    if (jobId) {
        showJobDetails(jobId);
    } else {
        // If no job ID in URL, ensure the job list is visible
        jobsList.style.display = 'block';
        jobDetails.classList.remove('mobile-visible');
    }
</script>
</body>
</html>