<?php
// Start the session before any output
session_start();

// Determine which user to show: self or from search
$view_user_id = null;
if (isset($_GET['user_id'])) {
    $view_user_id = intval($_GET['user_id']);
} elseif (isset($_SESSION['user_id'])) {
    $view_user_id = intval($_SESSION['user_id']);
} else {
    header('Location: login.php');
    exit();
}

// Include files after session check
include 'sqlconnection.php';
include 'user_navbar.php';

// Fetch user data from the database
$sql = "SELECT *, DATE_FORMAT(last_updated, '%M %d, %Y at %h:%i %p') as formatted_last_updated FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $view_user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    // Create full name from parts
    $user['full_name'] = trim($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']);
} else {
    die("User not found.");
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

// Helper function to format employment status
function getStatusClass($status) {
    switch(strtolower($status)) {
        case 'employed': return 'status-employed';
        case 'unemployed': return 'status-unemployed';
        case 'freelance': return 'status-freelance';
        case 'student': return 'status-student';
        default: return 'status-employed';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --primary-dark: #3f37c9;
            --secondary: #4cc9f0;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --radius-sm: 0.25rem;
            --radius-md: 0.375rem;
            --radius-lg: 0.5rem;
            --radius-xl: 0.75rem;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --card-bg: white;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--gray-100);
            color: var(--gray-800);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem;
        }

        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .profile-header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .profile-header h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--gray-800);
            margin: 0;
        }

        .profile-header p {
            color: var(--gray-500);
            margin: 0;
        }

        .profile-header-actions {
            display: flex;
            gap: 0.75rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            font-weight: 500;
            font-size: 0.875rem;
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(to right, var(--primary), var(--primary-light));
            color: white;
            border: none;
            box-shadow: 0 2px 5px rgba(67, 97, 238, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--primary));
            box-shadow: 0 4px 8px rgba(67, 97, 238, 0.4);
            transform: translateY(-1px);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
        }

        .btn-outline:hover {
            background: rgba(67, 97, 238, 0.05);
            transform: translateY(-1px);
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 1.5rem;
        }

        @media (max-width: 992px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background-color: var(--card-bg);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        .card-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-header h2 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--gray-800);
            margin: 0;
        }

        .card-header-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            background: linear-gradient(to right, var(--primary-light), var(--secondary));
            color: white;
            border-radius: var(--radius-lg);
        }

        .card-body {
            padding: 1.25rem;
        }

        .profile-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .profile-main {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .profile-image-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 1.25rem 0;
        }

        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: var(--shadow-lg);
            margin-bottom: 1.25rem;
        }

        .profile-name {
            margin-bottom: 0.75rem;
        }

        .profile-name h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 0.5rem;
        }

        .profile-status {
            display: inline-flex;
            align-items: center;
            padding: 0.375rem 0.875rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-employed {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .status-unemployed {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .status-freelance {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }

        .status-student {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--secondary);
        }

        .info-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .info-item {
            display: flex;
            padding: 0.875rem 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            width: 40%;
            font-size: 0.875rem;
            color: var(--gray-500);
            font-weight: 500;
            padding-right: 1rem;
        }

        .info-value {
            width: 60%;
            font-size: 0.875rem;
            color: var(--gray-800);
            font-weight: 500;
        }

        .text-content {
            font-size: 0.875rem;
            color: var(--gray-700);
            line-height: 1.7;
            white-space: pre-line;
        }

        .contact-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 0.875rem;
            padding: 0.875rem 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .contact-item:last-child {
            border-bottom: none;
        }

        .contact-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            background-color: var(--gray-100);
            border-radius: var(--radius-lg);
            color: var(--primary);
        }

        .contact-info {
            flex: 1;
        }

        .contact-label {
            font-size: 0.75rem;
            color: var(--gray-500);
            margin-bottom: 0.25rem;
        }

        .contact-value {
            font-size: 0.875rem;
            color: var(--gray-800);
            font-weight: 500;
        }

        .resume-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
            border-radius: var(--radius-md);
            background-color: rgba(67, 97, 238, 0.1);
            transition: all 0.2s ease;
        }

        .resume-link:hover {
            background-color: rgba(67, 97, 238, 0.2);
        }

        .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--gray-200);
        }

        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .skill-tag {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .experience-item {
            padding: 1rem;
            background-color: var(--gray-50);
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
        }

        .experience-item:last-child {
            margin-bottom: 0;
        }

        .last-updated {
            font-size: 0.75rem;
            color: var(--gray-500);
            margin-top: 0.5rem;
            font-style: italic;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .profile-header {
                flex-direction: column;
                align-items: flex-start;
                margin-bottom: 1.25rem;
            }

            .profile-header-actions {
                width: 100%;
                justify-content: flex-start;
            }

            .btn {
                padding: 0.5rem 1rem;
            }

            .card-header, .card-body {
                padding: 1rem;
            }

            .profile-image {
                width: 100px;
                height: 100px;
                margin-bottom: 0.75rem;
            }

            .profile-name {
                margin-bottom: 0.5rem;
            }

            .info-item {
                flex-direction: column;
                gap: 0.25rem;
                padding: 0.75rem 0;
            }

            .info-label, .info-value {
                width: 100%;
            }

            .contact-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
                padding: 0.75rem 0;
            }
            
            .profile-grid {
                gap: 1rem;
            }
        }

        @media (max-width: 576px) {
            .profile-header h1 {
                font-size: 1.5rem;
            }

            .card-header-icon {
                width: 2rem;
                height: 2rem;
            }
            
            .card {
                margin-bottom: 0.75rem;
            }

            .profile-image-container {
                padding: 1rem 0;
            }

            .profile-image {
                width: 80px;
                height: 80px;
            }

            .profile-name h3 {
                font-size: 1.25rem;
                margin-bottom: 0.25rem;
            }
            
            .profile-status {
                padding: 0.25rem 0.625rem;
                font-size: 0.7rem;
            }
            
            .card-header {
                padding: 0.875rem;
            }
            
            .card-body {
                padding: 0.875rem;
            }
            
            .experience-item {
                padding: 0.75rem;
                margin-bottom: 0.75rem;
            }
            
            .skills-list {
                gap: 0.375rem;
            }
            
            .skill-tag {
                padding: 0.25rem 0.5rem;
                font-size: 0.7rem;
            }
            /* Add: Make grid single column and reduce padding for very small screens */
            .profile-grid {
                grid-template-columns: 1fr !important;
                gap: 0.5rem;
            }
            .container {
                padding: 0.5rem;
                margin-left: 0.5rem;
                margin-right: 0.5rem;
            }
            .card, .card-header, .card-body {
                border-radius: var(--radius-md);
            }
        }
        /* Extra small devices (e.g. S21 FE, ~400px width) */
        @media (max-width: 400px) {
            .container {
                padding: 0.25rem;
                margin-left: 0.25rem;
                margin-right: 0.25rem;
            }
            .profile-header h1 {
                font-size: 1.1rem;
            }
            .card-header, .card-body {
                padding: 0.5rem;
            }
            .profile-image {
                width: 60px;
                height: 60px;
            }
            .profile-name h3 {
                font-size: 1rem;
            }
            .profile-status {
                font-size: 0.6rem;
                padding: 0.15rem 0.4rem;
            }
            .skill-tag {
                font-size: 0.6rem;
                padding: 0.15rem 0.3rem;
            }
            .experience-item {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-header">
            <div class="profile-header-left">
                <h1>
                    <?php
                    if (isset($_GET['user_id']) && $_GET['user_id'] != $_SESSION['user_id']) {
                        echo "User Profile";
                    } else {
                        echo "My Profile";
                    }
                    ?>
                </h1>
            </div>
            <?php if (isset($_GET['user_id']) && $_GET['user_id'] != $_SESSION['user_id']): ?>
                <div class="profile-header-actions">
                    <a href="search_user.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i>
                        Back to Search
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <div class="profile-grid">
            <div class="profile-sidebar">
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title">
                            <div class="card-header-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <h2>Personal Information</h2>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="profile-image-container">
                            <img src="<?php echo htmlspecialchars($user['profile_img'] ?? 'https://via.placeholder.com/120'); ?>" 
                                 alt="Profile Image" class="profile-image">
                            <div class="profile-name">
                                <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                                <span class="profile-status <?php echo getStatusClass($user['employment_status']); ?>">
                                    <?php echo htmlspecialchars($user['employment_status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <ul class="info-list">
                            <li class="info-item">
                                <div class="info-label">Full Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></div>
                            </li>
                            <li class="info-item">
                                <div class="info-label">Gender</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['gender']); ?></div>
                            </li>
                            <li class="info-item">
                                <div class="info-label">Birthdate</div>
                                <div class="info-value"><?php echo date('F j, Y', strtotime($user['birthdate'])); ?></div>
                            </li>
                            <li class="info-item">
                                <div class="info-label">Graduation Year</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['graduation_year']); ?></div>
                            </li>
                            <li class="info-item">
                                <div class="info-label">Username</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['username']); ?></div>
                            </li>
                            <li class="info-item">
                                <div class="info-label">Employment Status</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['employment_status']); ?></div>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title">
                            <div class="card-header-icon">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <h2>Contact Information</h2>
                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="contact-list">
                            <li class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="contact-info">
                                    <div class="contact-label">Email</div>
                                    <div class="contact-value"><?php echo htmlspecialchars($user['email']); ?></div>
                                </div>
                            </li>
                            <li class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-phone-alt"></i>
                                </div>
                                <div class="contact-info">
                                    <div class="contact-label">Phone</div>
                                    <div class="contact-value"><?php echo htmlspecialchars($user['phone_number']); ?></div>
                                </div>
                            </li>
                            <li class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="contact-info">
                                    <div class="contact-label">Address</div>
                                    <div class="contact-value"><?php echo htmlspecialchars($user['address']); ?></div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Skills Card -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title">
                            <div class="card-header-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <h2>Skills</h2>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($user['skills'])): ?>
                            <div class="skills-list">
                                <?php 
                                $skills = explode(',', $user['skills']);
                                foreach ($skills as $skill) {
                                    $skill = trim($skill);
                                    if (!empty($skill)) {
                                        echo '<span class="skill-tag">' . htmlspecialchars($skill) . '</span>';
                                    }
                                }
                                ?>
                            </div>
                        <?php else: ?>
                            <p class="text-content">No skills listed yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Work Experience Card -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title">
                            <div class="card-header-icon">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <h2>Work Experience</h2>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($user['work_experience'])): ?>
                            <?php 
                            $experiences = explode("\n\n", trim($user['work_experience']));
                            foreach ($experiences as $experience) {
                                if (!empty($experience)) {
                                    echo '<div class="experience-item">';
                                    echo nl2br(htmlspecialchars($experience));
                                    echo '</div>';
                                }
                            }
                            ?>
                        <?php else: ?>
                            <p class="text-content">No work experience listed yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Education Card -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title">
                            <div class="card-header-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <h2>Education</h2>
                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="info-list">
                            <li class="info-item">
                                <div class="info-label">Degree</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['degree'] ?? 'Not specified'); ?></div>
                            </li>
                            <li class="info-item">
                                <div class="info-label">Graduation Year</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['graduation_year']); ?></div>
                            </li>
                            <li class="info-item">
                                <div class="info-label">Institution</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['institution'] ?? 'Not specified'); ?></div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="profile-main">
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-title">
                            <div class="card-header-icon">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <h2>Jobs Posted</h2>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        // Pagination setup
                        include 'sqlconnection.php';
                        $jobs_per_page = 7;
                        $page = isset($_GET['jobs_page']) && is_numeric($_GET['jobs_page']) ? (int)$_GET['jobs_page'] : 1;
                        if ($page < 1) $page = 1;
                        $offset = ($page - 1) * $jobs_per_page;

                        // Get total jobs count for pagination
                        $count_sql = "SELECT COUNT(*) as total_jobs FROM job_listings WHERE posted_by_id = ?";
                        $count_stmt = mysqli_prepare($conn, $count_sql);
                        mysqli_stmt_bind_param($count_stmt, "i", $view_user_id);
                        mysqli_stmt_execute($count_stmt);
                        $count_result = mysqli_stmt_get_result($count_stmt);
                        $total_jobs = 0;
                        if ($count_result && $row = mysqli_fetch_assoc($count_result)) {
                            $total_jobs = (int)$row['total_jobs'];
                        }
                        mysqli_stmt_close($count_stmt);

                        $total_pages = ceil($total_jobs / $jobs_per_page);

                        // Fetch jobs for current page
                        $jobs_sql = "SELECT job_id, title, company, location, employment_type, posted_at, status FROM job_listings WHERE posted_by_id = ? ORDER BY posted_at DESC LIMIT ? OFFSET ?";
                        $jobs_stmt = mysqli_prepare($conn, $jobs_sql);
                        mysqli_stmt_bind_param($jobs_stmt, "iii", $view_user_id, $jobs_per_page, $offset);
                        mysqli_stmt_execute($jobs_stmt);
                        $jobs_result = mysqli_stmt_get_result($jobs_stmt);

                        if ($jobs_result && mysqli_num_rows($jobs_result) > 0): ?>
                            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                                <?php while ($job = mysqli_fetch_assoc($jobs_result)): ?>
                                    <div style="
                                        border: 1px solid #e5e7eb;
                                        border-radius: 0.75rem;
                                        padding: 1.25rem 1rem;
                                        background: #f9fafb;
                                        box-shadow: 0 1px 3px rgba(67,97,238,0.05);
                                        display: flex;
                                        flex-direction: column;
                                        gap: 0.5rem;
                                        position: relative;
                                    ">
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <span style="font-size: 1.1rem; font-weight: 600; color: #3f37c9;">
                                                <?php echo htmlspecialchars($job['title']); ?>
                                            </span>
                                            <?php if ($job['status'] !== 'active'): ?>
                                                <span style="color:#ef4444;font-size:0.85em; background: #fee2e2; border-radius: 0.5em; padding: 0.1em 0.6em; margin-left: 0.5em;">
                                                    <?php echo htmlspecialchars($job['status']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="color: #4895ef; font-size: 1em; font-weight: 500;">
                                            <i class="fas fa-building"></i>
                                            <?php echo htmlspecialchars($job['company']); ?>
                                        </div>
                                        <div style="display: flex; gap: 1em; flex-wrap: wrap; font-size: 0.95em; color: #6b7280;">
                                            <span>
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?php echo htmlspecialchars($job['location']); ?>
                                            </span>
                                            <span>
                                                <i class="fas fa-clock"></i>
                                                <?php echo htmlspecialchars($job['employment_type']); ?>
                                            </span>
                                        </div>
                                        <div style="font-size:0.85em;color:#9ca3af;">
                                            <i class="fas fa-calendar-alt"></i>
                                            Posted: <?php echo date('F j, Y', strtotime($job['posted_at'])); ?>
                                        </div>
                                        <div style="margin-top:0.5em;">
                                            <button onclick="showJobDetails(<?php echo $job['job_id']; ?>)" 
                                               class="btn btn-primary"
                                               style="padding: 0.5em 1.2em; font-size: 0.95em; border-radius: 0.5em; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5em;">
                                                <i class="fas fa-eye"></i> View Details
                                            </button>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            <?php
                            // Pagination controls
                            if ($total_pages > 1): ?>
                                <div style="margin-top:1.5em; display:flex; justify-content:center; gap:0.5em; flex-wrap:wrap;">
                                    <?php
                                    $base_url = strtok($_SERVER["REQUEST_URI"], '?');
                                    $query_params = $_GET;
                                    for ($i = 1; $i <= $total_pages; $i++):
                                        $query_params['jobs_page'] = $i;
                                        $page_url = $base_url . '?' . http_build_query($query_params);
                                        $is_active = $i == $page;
                                    ?>
                                        <a href="<?php echo htmlspecialchars($page_url); ?>"
                                           style="padding: 0.5em 1em; border-radius: 0.4em; border: 1px solid #e5e7eb; background: <?php echo $is_active ? '#4361ee' : '#fff'; ?>; color: <?php echo $is_active ? '#fff' : '#4361ee'; ?>; font-weight: 600; text-decoration: none; margin: 0 2px;">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-content">No jobs posted yet.</p>
                        <?php endif;
                        mysqli_stmt_close($jobs_stmt);
                        mysqli_close($conn);
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Job Details Modal -->
    <div id="jobDetailsModal" class="modal-overlay">
        <div class="modal" style="max-width: 700px;">
            <div class="modal-header">
                <h2>Job Details</h2>
                <button onclick="closeJobModal()" class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="jobDetailsContent">
                <!-- Job details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Add these styles before the closing </style> tag -->
    <style>
        /* Modal styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: white;
            border-radius: var(--radius-lg);
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: var(--gray-500);
            cursor: pointer;
            padding: 0.5rem;
        }

        .modal-close:hover {
            color: var(--gray-700);
        }

        .modal-body {
            padding: 1.5rem;
        }
    </style>

    <script>
        // Add any JavaScript functionality here
        document.addEventListener('DOMContentLoaded', function() {
            // Animation for cards
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100 * index);
            });
        });

        function showJobModal() {
            document.getElementById('jobDetailsModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeJobModal() {
            document.getElementById('jobDetailsModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        async function showJobDetails(jobId) {
            try {
                const response = await fetch(`job_info.php?ajax=1&id=${jobId}`);
                if (!response.ok) throw new Error('Network response was not ok');
                
                const jobDetails = await response.text();
                document.getElementById('jobDetailsContent').innerHTML = jobDetails;
                showJobModal();

                // Add event listener to the new save button in the modal
                const saveBtn = document.querySelector('#jobDetailsContent .save-job-detail-btn');
                if (saveBtn) {
                    saveBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        toggleSavedJob(this.dataset.id);
                    });
                }
            } catch (error) {
                console.error('Error loading job details:', error);
                document.getElementById('jobDetailsContent').innerHTML = `
                    <div class="empty-state">
                        <p>Error loading job details. Please try again.</p>
                    </div>
                `;
                showJobModal();
            }
        }

        // Close modal when clicking outside
        document.getElementById('jobDetailsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeJobModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('jobDetailsModal').classList.contains('active')) {
                closeJobModal();
            }
        });
    </script>
</body>
</html>
