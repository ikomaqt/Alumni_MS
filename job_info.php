<?php
session_start();
require_once 'sqlconnection.php';

// Check if user is not logged in
if(!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Fetch jobs from the database (include posted_by_id and posted_by_type)
$jobs = [];
$sql = "SELECT * FROM job_listings WHERE status = 'open' ORDER BY created_at DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Fetch poster name based on posted_by_type
        $poster_name = 'Unknown';
        if (!empty($row['posted_by_id']) && !empty($row['posted_by_type'])) {
            $poster_id = (int)$row['posted_by_id'];
            $poster_type = $row['posted_by_type'];
            if ($poster_type === 'admin') {
                $poster_sql = "SELECT name FROM admin_users WHERE admin_id = ?";
                $poster_stmt = $conn->prepare($poster_sql);
                $poster_stmt->bind_param("i", $poster_id);
                $poster_stmt->execute();
                $poster_result = $poster_stmt->get_result();
                if ($poster_row = $poster_result->fetch_assoc()) {
                    $poster_name = $poster_row['name'];
                }
            } else {
                $poster_sql = "SELECT first_name, last_name FROM users WHERE user_id = ?";
                $poster_stmt = $conn->prepare($poster_sql);
                $poster_stmt->bind_param("i", $poster_id);
                $poster_stmt->execute();
                $poster_result = $poster_stmt->get_result();
                if ($poster_row = $poster_result->fetch_assoc()) {
                    $poster_name = trim($poster_row['first_name'] . ' ' . $poster_row['last_name']);
                }
            }
        }
        $row['posted_by_name'] = $poster_name;
        $jobs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Board</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0ea5e9;
            --primary-dark: #0284c7;
            --primary-light: #e0f2fe;
            --text-color: #1e293b;
            --text-light: #64748b;
            --background-color: #f8fafc;
            --card-background: #ffffff;
            --border-color: #e2e8f0;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --radius: 8px;
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --transition: all 0.2s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            color: var(--text-color);
            background-color: var(--background-color);
            line-height: 1.5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* Main Content */
        .main-content {
            display: flex;
            flex-direction: column;
            margin-top: 90px; /* Increased from 80px to provide more clearance */
            padding: 1rem;
        }

        .search-filter-row {
            display: flex;
            flex-direction: row;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .search-input-container {
            position: relative;
            flex: 1;
            width: 100%;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
        }

        .filter-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            max-width: 100%;
        }

        .filter-label {
            font-weight: 500;
            color: var(--text-color);
            white-space: nowrap;
        }

        .category-filter {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 0.95rem;
            transition: var(--transition);
            max-width: 100%;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1rem;
            padding-right: 2.5rem;
        }

        .category-filter:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
        }

        .job-count {
            font-size: 0.875rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        /* Job List */
        .job-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .job-item {
            position: relative;
            background-color: var(--card-background);
            border-radius: var(--radius);
            overflow: hidden;
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
        }

        .job-item:hover {
            box-shadow: var(--shadow-hover);
        }

        .job-item-content {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .job-header {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            position: relative;
        }

        .company-logo {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            border-radius: var(--radius);
            flex-shrink: 0;
        }

        .job-title-container {
            flex: 1;
            min-width: 0;
        }

        .job-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.25rem;
        }

        .company-name {
            font-size: 0.875rem;
            color: var(--primary-color);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .posted-by {
            font-size: 0.85em;
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }

        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .job-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-light);
        }

        .job-meta-icon {
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
        }

        .job-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .job-tag {
            background-color: #f1f5f9;
            color: var(--text-color);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .job-description {
            font-size: 0.875rem;
            color: var(--text-color);
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .job-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.5rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--border-color);
        }

        .job-posted {
            font-size: 0.75rem;
            color: var(--text-light);
        }

        /* Job Detail Slide Panel */
        .job-detail-panel {
            position: fixed;
            top: 64px; /* Match navbar height */
            right: -100%;
            width: 100%;
            height: calc(100% - 64px); /* Subtract navbar height */
            background-color: var(--card-background);
            z-index: 20;
            overflow-y: auto;
            transition: right 0.3s ease;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
        }

        .job-detail-panel.active {
            right: 0;
        }

        .panel-header {
            position: sticky;
            top: 0;
            background-color: var(--card-background);
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            z-index: 1;
        }

        .back-button {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-color);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
            margin-right: 0.5rem;
            border-radius: var(--radius);
            transition: var(--transition);
        }

        .back-button:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .panel-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .panel-content {
            padding: 1.5rem;
        }

        .job-detail-company {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .detail-company-logo {
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            border-radius: var(--radius);
            font-size: 1.5rem;
        }

        .detail-company-info {
            flex: 1;
        }

        .detail-company-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.25rem;
        }

        .detail-company-location {
            font-size: 0.875rem;
            color: var(--text-light);
        }

        .job-detail-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .job-detail-meta {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .detail-meta-item {
            background-color: #f8fafc;
            padding: 0.75rem;
            border-radius: var(--radius);
        }

        .detail-meta-label {
            font-size: 0.75rem;
            color: var(--text-light);
            margin-bottom: 0.25rem;
        }

        .detail-meta-value {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .job-detail-section {
            margin-bottom: 1.5rem;
        }

        .detail-section-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 1rem;
        }

        .job-detail-description {
            font-size: 0.9375rem;
            color: var(--text-color);
            line-height: 1.6;
        }

        .requirements-list {
            list-style-type: none;
            padding-left: 0;
        }

        .requirements-list li {
            position: relative;
            padding-left: 1.5rem;
            margin-bottom: 0.75rem;
            line-height: 1.6;
            font-size: 0.9375rem;
        }

        .requirements-list li::before {
            content: "•";
            position: absolute;
            left: 0.25rem;
            color: var(--primary-color);
            font-weight: bold;
        }

        .job-detail-contact {
            font-size: 0.9375rem;
            color: var(--text-color);
        }

        .job-detail-footer {
            position: sticky;
            bottom: 0;
            background-color: var(--card-background);
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 -4px 6px rgba(0, 0, 0, 0.05);
        }

        .detail-posted {
            font-size: 0.875rem;
            color: var(--text-light);
        }

        .apply-button {
            background: linear-gradient(90deg, var(--primary-color) 60%, var(--primary-dark) 100%);
            color: #fff;
            border: none;
            border-radius: var(--radius);
            padding: 0.6rem 1.3rem;
            font-weight: 600;
            font-size: 0.98rem;
            letter-spacing: 0.01em;
            box-shadow: 0 2px 8px rgba(14, 165, 233, 0.10);
            cursor: pointer;
            transition: background 0.18s, box-shadow 0.18s, transform 0.12s;
            outline: none;
            display: inline-block;
            text-align: center;
        }

        .apply-button:hover,
        .apply-button:focus {
            background: linear-gradient(90deg, var(--primary-dark) 60%, var(--primary-color) 100%);
            box-shadow: 0 4px 16px rgba(14, 165, 233, 0.18);
            transform: translateY(-2px) scale(1.03);
        }

        .apply-button:active {
            background: var(--primary-dark);
            box-shadow: 0 1px 4px rgba(14, 165, 233, 0.10);
            transform: scale(0.98);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin: 2rem 0;
        }

        .page-button {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border-color);
            background-color: var(--card-background);
            border-radius: var(--radius);
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.875rem;
        }

        .page-button.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .page-button:hover:not(.active) {
            background-color: rgba(14, 165, 233, 0.1);
            border-color: var(--primary-color);
        }

        /* Desktop Styles */
        @media (min-width: 768px) {
            .search-filter-row {
                flex-direction: row;
                align-items: center;
                gap: 1rem;
            }

            .search-input-container {
                max-width: 60%;
            }

            .filter-container {
                max-width: 40%;
            }

            .job-detail-panel {
                width: 60%;
                right: -60%;
            }

            .job-detail-meta {
                grid-template-columns: repeat(4, 1fr);
            }

            .job-item-content {
                padding: 1.5rem;
            }

            .job-header {
                align-items: center;
            }
        }

        /* Mobile Styles - Specifically for Samsung S21 FE and similar */
        @media (max-width: 380px) {
            .search-bar-container {
                padding: 0.75rem;
            }

            .job-item-content {
                padding: 1rem;
            }

            .company-logo {
                width: 40px;
                height: 40px;
            }

            .job-title {
                font-size: 1rem;
            }

            .job-meta {
                flex-direction: column;
                gap: 0.5rem;
                align-items: flex-start;
            }

            .panel-content {
                padding: 1rem;
            }

            .job-detail-meta {
                grid-template-columns: 1fr;
            }

            .job-detail-footer {
                flex-direction: column;
                align-items: stretch;
                gap: 0.75rem;
            }

            .apply-button {
                width: 100%;
                padding: 0.8rem 0.5rem;
                font-size: 1rem;
                border-radius: 999px;
            }
        }

        .heart-btn {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            width: 32px;
            height: 32px;
            background-color: white;
            border: 1px solid var(--border-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            z-index: 2;
        }
        
        .heart-btn:hover {
            background-color: #f1f5f9;
        }
        
        .heart-btn i {
            font-size: 1rem;
            color: var(--text-light);
            transition: all 0.2s;
        }
        
        .heart-btn i.active {
            color: #ef4444;
        }

        /* Toast notification style */
        .toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        }
        
        .toast.show {
            opacity: 1;
            visibility: visible;
        }
        
        /* Page header styling */
        .page-header {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <?php include 'user_navbar.php'; ?>
    
    <div class="container" style="padding-top: 32px; padding-bottom: 0;">
        <h1 class="page-header">Job Information</h1>
    </div>
    <main class="container main-content" style="padding-top: 0; margin-top: 10px;">
        <div class="search-filter-row">
            <div class="search-input-container">
                <div class="search-icon">
                    <i class="fas fa-search"></i>
                </div>
                <input type="text" class="search-input" placeholder="Search a jobs">
            </div>
        </div>

        <div class="job-count">Showing <?php echo count($jobs); ?> jobs</div>

        <div class="job-list">
            <?php
            // Fetch user's bookmarked jobs
            $bookmarkedJobs = [];
            if (isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];
                $bm_sql = "SELECT job_id FROM bookmarks WHERE user_id = ?";
                $bm_stmt = $conn->prepare($bm_sql);
                $bm_stmt->bind_param("i", $user_id);
                $bm_stmt->execute();
                $bm_result = $bm_stmt->get_result();
                while ($bm_row = $bm_result->fetch_assoc()) {
                    $bookmarkedJobs[] = $bm_row['job_id'];
                }
            }
            ?>
            <?php foreach ($jobs as $job): ?>
            <div class="job-item" data-job-id="<?php echo $job['job_id']; ?>">
                <div class="job-item-content">
                    <div class="job-header">
                        <div class="company-logo">
                            <?php
                                // Use company initials as logo, with fallback for empty strings
                                $words = !empty($job['company']) ? explode(' ', $job['company']) : ['N/A'];
                                $initials = '';
                                foreach($words as $w) { 
                                    if (!empty($w)) {
                                        $initials .= strtoupper($w[0]); 
                                    }
                                }
                                echo htmlspecialchars(substr($initials, 0, 2) ?: 'NA');
                            ?>
                        </div>
                        <div class="job-title-container">
                            <h2 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h2>
                            <div class="company-name"><?php echo htmlspecialchars($job['company']); ?></div>
                            <div class="posted-by" style="font-size: 0.85em; color: var(--text-light); margin-bottom: 0.5rem;">
                                Posted by: <?php echo htmlspecialchars($job['posted_by_name']); ?>
                            </div>
                            
                            <div class="job-meta">
                                <div class="job-meta-item">
                                    <i class="fas fa-map-marker-alt job-meta-icon"></i>
                                    <span><?php echo htmlspecialchars($job['location']); ?></span>
                                </div>
                                <div class="job-meta-item">
                                    <i class="fas fa-briefcase job-meta-icon"></i>
                                    <span><?php echo htmlspecialchars($job['employment_type']); ?></span>
                                </div>
                            </div>
                        </div>
                        <!-- Heart bookmark button -->
                        <button class="heart-btn" data-id="<?php echo $job['job_id']; ?>" onclick="event.stopPropagation(); toggleJobBookmark(this)">
                            <i class="<?php echo in_array($job['job_id'], $bookmarkedJobs) ? 'fas' : 'far'; ?> fa-heart <?php echo in_array($job['job_id'], $bookmarkedJobs) ? 'active' : ''; ?>"></i>
                        </button>
                    </div>
                    
                    <div class="job-tags">
                        <span class="job-tag"><?php echo htmlspecialchars($job['job_type']); ?></span>
                    </div>
                    
                    <p class="job-description">
                        <?php echo htmlspecialchars(mb_strimwidth(strip_tags($job['description']), 0, 160, '...')); ?>
                    </p>
                    
                    <div class="job-footer">
                        <div class="job-posted">Posted <?php 
                            $date = new DateTime($job['posted_at']);
                            echo $date->format('F j, Y \a\t g:i A'); 
                        ?></div>
                        <button class="view-info-btn" data-job-id="<?php echo $job['job_id']; ?>" style="margin-left: 1rem; background: var(--primary-color); color: #fff; border: none; border-radius: var(--radius); padding: 0.5rem 1rem; cursor: pointer;">View Info</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="pagination">
            <button class="page-button active">1</button>
            <button class="page-button">2</button>
            <button class="page-button">3</button>
            <button class="page-button">4</button>
            <button class="page-button">Next</button>
        </div>
    </main>

    <!-- Job Detail Slide Panel -->
    <div class="job-detail-panel" id="jobDetailPanel">
        <div class="panel-header">
            <button class="back-button" id="backButton">
                <i class="fas fa-arrow-left"></i>
            </button>
            <h1 class="panel-title">Job Details</h1>
        </div>
        <div class="panel-content">
            <div class="job-detail-company" style="position: relative;">
                <div class="detail-company-logo" id="detailLogo">TC</div>
                <div class="detail-company-info">
                    <!-- Move heart button to top right of detail-company-info -->
                    <button class="heart-btn" id="detailHeartBtn" data-id="" style="position: absolute; top: 0; right: 0;">
                        <i class="far fa-heart"></i>
                    </button>
                    <div id="detailPostedBy" style="font-size: 0.95em; color: var(--text-light); margin-bottom: 0.5rem;">
                        <!-- Poster name will be injected here -->
                    </div>
                    <div class="detail-company-name" id="detailCompany">TechCorp Inc.</div>
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; width: 100%;">
                        <div>
                            <h2 class="job-detail-title" id="detailTitle">Senior Frontend Developer</h2>
                            <div class="detail-company-location" id="detailLocation">
                                <i class="fas fa-map-marker-alt" style="color: var(--text-light); margin-right: 5px;"></i>
                                San Francisco, CA (Hybrid)
                            </div>
                        </div>
                        <!-- Remove heart button from here -->
                    </div>
                </div>
            </div>

            <div class="job-detail-meta">
                <div class="detail-meta-item">
                    <div class="detail-meta-label">Employment Type</div>
                    <div class="detail-meta-value" id="detailEmploymentType">Full-time</div>
                </div>
                <div class="detail-meta-item">
                    <div class="detail-meta-label">Job Type</div>
                    <div class="detail-meta-value" id="detailJobType">Engineering</div>
                </div>
                <div class="detail-meta-item">
                    <div class="detail-meta-label">Salary Range</div>
                    <div class="detail-meta-value" id="detailSalary">$120,000 - $150,000</div>
                </div>
            </div>

            <div class="job-detail-section">
                <h3 class="detail-section-title">Job Description</h3>
                <div class="job-detail-description" id="detailDescription">
                    <p>We're looking for a Senior Frontend Developer to join our growing team at TechCorp Inc. As a Senior Frontend Developer, you'll be responsible for building user interfaces and implementing new features for our flagship product.</p>
                    <p>You'll work closely with our product and design teams to create intuitive, responsive, and accessible web applications that delight our users. This is a key role that will have a significant impact on our product and company growth.</p>
                    <p>Our tech stack includes React, TypeScript, and GraphQL, and we're looking for someone who is comfortable with these technologies and excited to learn new ones as needed.</p>
                </div>
            </div>

            <div class="job-detail-section">
                <h3 class="detail-section-title">Requirements</h3>
                <div id="detailRequirements">
                    <ul class="requirements-list">
                        <li>5+ years of experience in frontend development</li>
                        <li>Strong proficiency in JavaScript, HTML, and CSS</li>
                        <li>Experience with React and modern frontend frameworks</li>
                        <li>Knowledge of responsive design and cross-browser compatibility</li>
                        <li>Understanding of web performance optimization techniques</li>
                        <li>Experience with version control systems (Git)</li>
                        <li>Excellent problem-solving skills and attention to detail</li>
                        <li>Strong communication skills and ability to work in a team</li>
                        <li>Bachelor's degree in Computer Science or related field (or equivalent experience)</li>
                    </ul>
                </div>
            </div>

            <div class="job-detail-section">
                <h3 class="detail-section-title">Contact Information</h3>
                <div class="job-detail-contact" id="detailContactEmail">careers@techcorp.com</div>
            </div>
        </div>

        <div class="job-detail-footer">
            <div class="detail-posted">
                <div id="detailPostedAt">Posted 2 days ago</div>
                <!-- Removed status from detail panel -->
                <!-- <div id="detailStatus" class="job-status status-active">Active</div> -->
            </div>
            <a href="#" id="detailApplyBtn" class="apply-button" style="text-decoration:none;">Apply Now</a>
        </div>
    </div>

    <?php
    // Add this at the beginning of the file, after session_start()
    if (isset($_GET['ajax']) && isset($_GET['check_page'])) {
        echo json_encode(['page' => 1]); // For now return page 1, implement pagination logic later
        exit;
    }
    ?>

    <script>
        // Pass PHP job data to JS
        const jobData = <?php
            // Prepare job data for JS (escape HTML for safety)
            $jsJobs = [];
            foreach ($jobs as $job) {
                // Create safe company initials for logo
                $company = !empty($job['company']) ? $job['company'] : 'N/A';
                $words = explode(' ', $company);
                $initials = '';
                foreach($words as $w) {
                    if (!empty($w)) {
                        $initials .= strtoupper($w[0]);
                    }
                }
                $logo = substr($initials, 0, 2) ?: 'NA';

                $jsJobs[] = [
                    'id' => (int)$job['job_id'],
                    'title' => $job['title'],
                    'company' => $company,
                    'location' => $job['location'],
                    'employment_type' => $job['employment_type'],
                    'job_type' => $job['job_type'],
                    'salary_range' => $job['salary_range'],
                    'description' => $job['description'],
                    'requirements' => $job['requirements'],
                    'contact_email' => $job['contact_email'],
                    'posted_at' => $job['posted_at'],
                    'posted_by_id' => $job['posted_by_id'],
                    'posted_by_type' => $job['posted_by_type'],
                    'posted_by_name' => $job['posted_by_name'],
                    'status' => $job['status'],
                    'logo' => $logo
                ];
            }
            echo json_encode($jsJobs, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
        ?>;

        // DOM Elements
        const jobItems = document.querySelectorAll('.job-item');
        const jobDetailPanel = document.getElementById('jobDetailPanel');
        const backButton = document.getElementById('backButton');

        // Update the selected job handling logic
        const urlParams = new URLSearchParams(window.location.search);
        const selectedJobId = urlParams.get('id');
        const isSelected = urlParams.get('selected') === 'true';

        // If there's a selected job, show its details
        if (selectedJobId) {
            const job = jobData.find(j => j.id === parseInt(selectedJobId));
            if (job) {
                // Wait for DOM to be fully loaded
                window.addEventListener('load', () => {
                    // Find and scroll to the job element
                    const jobElement = document.querySelector(`.job-item[data-job-id="${selectedJobId}"]`);
                    if (jobElement) {
                        // Scroll to job element with offset for navbar
                        setTimeout(() => {
                            const offset = 100;
                            window.scrollTo({
                                top: jobElement.offsetTop - offset,
                                behavior: 'smooth'
                            });

                            // Add highlight effect
                            jobElement.style.transition = 'background-color 0.5s';
                            jobElement.style.backgroundColor = '#f0f7ff';
                            setTimeout(() => {
                                jobElement.style.backgroundColor = '';
                            }, 2000);

                            // Populate and show job details panel
                            detailLogo.textContent = job.logo;
                            detailTitle.textContent = job.title;
                            detailCompany.textContent = job.company;
                            detailPostedBy.textContent = "Posted by: " + (job.posted_by_name || "Unknown");
                            detailLocation.innerHTML = `<i class="fas fa-map-marker-alt" style="color: var(--text-light); margin-right: 5px;"></i>${job.location}`;
                            detailEmploymentType.textContent = job.employment_type;
                            detailJobType.textContent = job.job_type;
                            detailSalary.textContent = job.salary_range;
                            detailDescription.innerHTML = job.description;
                            detailRequirements.innerHTML = job.requirements;
                            detailContactEmail.textContent = job.contact_email;
                            detailPostedAt.textContent = `Posted ${job.posted_at}`;

                            // Update heart button state
                            detailHeartBtn.dataset.id = job.id;
                            const isBookmarked = bookmarkedJobs.includes(job.id);
                            detailHeartIcon.className = isBookmarked ? 'fas fa-heart active' : 'far fa-heart';

                            // Set Apply button link
                            document.getElementById('detailApplyBtn').href = 'apply.php?id=' + job.id;

                            // Show panel
                            jobDetailPanel.classList.add('active');
                            document.body.style.overflow = 'hidden';
                        }, 300);
                    }
                });
            }
        }

        // Detail Panel Elements
        const detailLogo = document.getElementById('detailLogo');
        const detailTitle = document.getElementById('detailTitle');
        const detailCompany = document.getElementById('detailCompany');
        const detailLocation = document.getElementById('detailLocation');
        const detailEmploymentType = document.getElementById('detailEmploymentType');
        const detailJobType = document.getElementById('detailJobType');
        const detailSalary = document.getElementById('detailSalary');
        const detailDescription = document.getElementById('detailDescription');
        const detailRequirements = document.getElementById('detailRequirements');
        const detailContactEmail = document.getElementById('detailContactEmail');
        const detailPostedAt = document.getElementById('detailPostedAt');
        const detailPostedBy = document.getElementById('detailPostedBy');

        // Add reference for detail heart button and initialize it properly
        const detailHeartBtn = document.getElementById('detailHeartBtn');
        const detailHeartIcon = detailHeartBtn.querySelector('i');

        // Helper to check if job is bookmarked (from PHP)
        const bookmarkedJobs = <?php echo json_encode(array_map('intval', $bookmarkedJobs)); ?>;

        // Function to update heart button states
        function updateHeartButtons(jobId, isBookmarked) {
            // Update all heart buttons for this job (both in list and detail view)
            document.querySelectorAll(`.heart-btn[data-id="${jobId}"] i`).forEach(icon => {
                if (isBookmarked) {
                    icon.classList.remove('far');
                    icon.classList.add('fas', 'active');
                } else {
                    icon.classList.remove('fas', 'active');
                    icon.classList.add('far');
                }
            });
        }

        // Single toggleJobBookmark function for both list and detail views
        async function toggleJobBookmark(button) {
            const jobId = button.dataset.id;
            const icon = button.querySelector('i');
            const isBookmarked = icon.classList.contains('active');
            const action = isBookmarked ? 'remove' : 'save';

            try {
                const response = await fetch('bookmark_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        job_id: jobId,
                        action: action
                    })
                });
                const data = await response.json();
                if (data.status === 'success') {
                    // Update bookmarkedJobs array
                    const jobIdNum = Number(jobId);
                    const idx = bookmarkedJobs.indexOf(jobIdNum);
                    if (isBookmarked && idx !== -1) {
                        bookmarkedJobs.splice(idx, 1);
                    } else if (!isBookmarked && idx === -1) {
                        bookmarkedJobs.push(jobIdNum);
                    }
                    
                    // Update all heart buttons (both list and detail)
                    updateHeartButtons(jobId, !isBookmarked);
                    showToast(isBookmarked ? 'Job removed from bookmarks' : 'Job saved to bookmarks');
                } else {
                    showToast(data.message || 'Error updating bookmark');
                }
            } catch (error) {
                showToast('Error updating bookmark');
            }
        }

        // Use the same toggle function for both list and detail heart buttons
        document.querySelectorAll('.heart-btn').forEach(btn => {
            btn.addEventListener('click', async function(e) {
                e.stopPropagation();
                await toggleJobBookmark(this);
            });
        });

        // Update the view info button click handler
        document.querySelectorAll('.view-info-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const jobId = parseInt(this.getAttribute('data-job-id'));
                const job = jobData.find(j => j.id === jobId);

                if (job) {
                    // Populate panel with job details
                    detailLogo.textContent = job.logo;
                    detailTitle.textContent = job.title;
                    detailCompany.textContent = job.company;
                    detailPostedBy.textContent = "Posted by: " + (job.posted_by_name || "Unknown");
                    detailLocation.innerHTML = `<i class="fas fa-map-marker-alt" style="color: var(--text-light); margin-right: 5px;"></i>${job.location}`;
                    detailEmploymentType.textContent = job.employment_type;
                    detailJobType.textContent = job.job_type;
                    detailSalary.textContent = job.salary_range;
                    detailDescription.innerHTML = job.description;
                    detailRequirements.innerHTML = job.requirements;
                    detailContactEmail.textContent = job.contact_email;
                    detailPostedAt.textContent = "Posted " + formatDate(job.posted_at);
                    // Set heart button state for detail panel
                    detailHeartBtn.dataset.id = job.id;
                    const isBookmarked = bookmarkedJobs.includes(Number(job.id));
                    detailHeartIcon.className = isBookmarked ? 'fas fa-heart active' : 'far fa-heart';
                    
                    // Set Apply button link
                    document.getElementById('detailApplyBtn').href = 'apply.php?id=' + job.id;
                    
                    // Show panel
                    jobDetailPanel.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }
            });
        });

        // Close panel when back button is clicked
        backButton.addEventListener('click', function() {
            jobDetailPanel.classList.remove('active');
            document.body.style.overflow = ''; // Restore scrolling
        });

        // Close panel when clicking outside the details card
        jobDetailPanel.addEventListener('mousedown', function(e) {
            // Only close if clicking directly on the panel background, not inside the content
            if (e.target === jobDetailPanel && jobDetailPanel.classList.contains('active')) {
                jobDetailPanel.classList.remove('active');
                document.body.style.overflow = '';
            }
        });

        // Remove sortSelect, jobList declarations and sorting related code
        const searchInput = document.querySelector('.search-input');

        // Simple search function
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            let visibleCount = 0;
            
            jobItems.forEach(item => {
                const title = item.querySelector('.job-title').textContent.toLowerCase();
                const company = item.querySelector('.company-name').textContent.toLowerCase();
                const location = item.querySelector('.job-meta-item span').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || 
                    company.includes(searchTerm) || 
                    location.includes(searchTerm)) {
                    item.style.display = '';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
            
            document.querySelector('.job-count').textContent = `Showing ${visibleCount} jobs`;
        });

        // Toast notification
        function showToast(message) {
            // Remove any existing toasts
            const existingToasts = document.querySelectorAll('.toast');
            existingToasts.forEach(toast => toast.remove());
            
            // Create new toast
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.textContent = message;
            document.body.appendChild(toast);
            
            // Show the toast with a slight delay for the animation
            setTimeout(() => toast.classList.add('show'), 100);
            
            // Hide and remove the toast after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Add date formatting function
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                month: 'long',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: 'numeric',
                hour12: true
            });
        }
    </script>
</body>
</html>
