<?php
include 'sqlconnection.php';
include 'user_navbar.php';

// Start session before accessing $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the search query from URL parameter
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

// Get current user_id from session
$currentUserId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

// Prepare the SQL query with search conditions
$sql = "SELECT user_id, first_name, middle_name, last_name, graduation_year, employment_status, profile_img 
        FROM users 
        WHERE role = 'user' 
        AND acc_status = 'active'";

// Exclude current user from results
if ($currentUserId > 0) {
    $sql .= " AND user_id != $currentUserId";
}

// Add search conditions if query is not empty
if (!empty($searchQuery)) {
    $searchTerms = explode(' ', $searchQuery);
    $conditions = [];
    
    foreach ($searchTerms as $term) {
        if (!empty($term)) {
            $term = $conn->real_escape_string($term);
            $conditions[] = "(first_name LIKE '%$term%' OR 
                             middle_name LIKE '%$term%' OR 
                             last_name LIKE '%$term%' OR
                             CONCAT(first_name, ' ', last_name) LIKE '%$term%' OR
                             CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE '%$term%')";
        }
    }
    
    if (!empty($conditions)) {
        $sql .= " AND (" . implode(' AND ', $conditions) . ")";
    }
}

$result = $conn->query($sql);
$hasResults = $result && $result->num_rows > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Search Results</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-bg: #f0f2f5;
            --secondary-bg: #ffffff;
            --primary-text: #050505;
            --secondary-text: #65676b;
            --divider-color: #ced0d4;
            --highlight-color: #1877f2;
            --button-bg: #e4e6eb;
            --button-hover: #d8dadf;
            --shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            --border-radius: 8px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--primary-bg);
            color: var(--primary-text);
            line-height: 1.5;
        }
        
        .container {
            width: 100%;
            max-width: 900px;
            margin: 20px auto;
            padding: 0 15px;
        }
        
        .search-header {
            background-color: var(--secondary-bg);
            border-radius: var(--border-radius);
            padding: 16px 20px;
            margin-bottom: 10px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .search-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--primary-text);
        }
        
        .search-query {
            font-size: 15px;
            color: var(--secondary-text);
            margin-top: 4px;
        }
        
        .results-count {
            color: var(--secondary-text);
            font-size: 14px;
        }
        
        .search-results {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .profile-card {
            background-color: var(--secondary-bg);
            border-radius: var(--border-radius);
            padding: 16px;
            box-shadow: var(--shadow);
            transition: background-color 0.2s;
        }
        
        .profile-card:hover {
            background-color: #f7f8fa;
        }
        
        .profile-content {
            display: flex;
            margin-bottom: 12px;
        }
        
        .profile-picture-container {
            margin-right: 16px;
            flex-shrink: 0;
        }
        
        .profile-picture {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .profile-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0; /* Prevents flex items from overflowing */
        }
        
        .profile-name {
            font-weight: 600;
            font-size: 16px;
            color: var(--primary-text);
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .profile-details {
            color: var(--secondary-text);
            font-size: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
        }
        
        .detail-item i {
            font-size: 12px;
            margin-right: 4px;
            color: var(--secondary-text);
        }
        
        .profile-actions {
            width: 100%;
        }
        
        .view-profile-btn {
            padding: 8px 12px;
            background-color: var(--button-bg);
            color: var(--primary-text);
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
            width: 100%;
            text-align: center;
        }
        
        .view-profile-btn:hover {
            background-color: var(--button-hover);
        }
        
        .no-results {
            text-align: center;
            padding: 40px 20px;
            background-color: var(--secondary-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            color: var(--secondary-text);
            font-size: 15px;
        }
        
        .no-results i {
            font-size: 32px;
            color: var(--secondary-text);
            margin-bottom: 12px;
            display: block;
        }
        
        /* Media Queries for better responsiveness */
        @media (max-width: 768px) {
            .container {
                padding: 0 12px;
                margin: 15px auto;
            }
            
            .profile-card {
                padding: 14px;
            }
        }
        
        /* Specific optimizations for mobile devices */
        @media (max-width: 576px) {
            .container {
                padding: 0 10px;
                margin: 10px auto;
                width: 100%;
            }
            
            .search-header {
                padding: 14px 16px;
                flex-direction: column;
                align-items: flex-start;
            }
            
            .results-count {
                margin-top: 8px;
            }
            
            .profile-card {
                padding: 12px;
            }
            
            .profile-picture {
                width: 56px;
                height: 56px;
            }
            
            .profile-picture-container {
                margin-right: 12px;
            }
            
            .profile-info {
                flex: 1;
                min-width: 0;
            }
            
            .profile-details {
                gap: 8px;
                margin-top: 2px;
            }
        }
    </style>
</head>
<body>
    <!-- Main Content -->
    <div class="container">
        <div class="search-header">
            <div>
                <h2 class="search-title">Search Results</h2>
                <?php if (!empty($searchQuery) && $hasResults): ?>
                    <p class="search-query">for "<?php echo htmlspecialchars($searchQuery); ?>"</p>
                <?php endif; ?>
            </div>
            <?php if ($hasResults): ?>
                <p class="results-count">
                    <?php echo $result->num_rows; ?> people found
                </p>
            <?php endif; ?>
        </div>
        
        <!-- Profile Cards -->
        <?php if ($hasResults): ?>
            <div class="search-results">
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="profile-card">
                        <div class="profile-content">
                            <div class="profile-picture-container">
                                <img 
                                    src="<?php echo !empty($row['profile_img']) ? htmlspecialchars($row['profile_img']) : 'https://via.placeholder.com/150'; ?>" 
                                    alt="<?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>" 
                                    class="profile-picture"
                                >
                            </div>
                            <div class="profile-info">
                                <h3 class="profile-name">
                                    <?php echo htmlspecialchars($row['first_name'] . ' ' . (!empty($row['middle_name']) ? $row['middle_name'] . ' ' : '') . $row['last_name']); ?>
                                </h3>
                                <div class="profile-details">
                                    <div class="detail-item">
                                        <i class="fas fa-graduation-cap"></i>
                                        <?php echo htmlspecialchars($row['graduation_year']); ?>
                                    </div>
                                    <?php if (!empty($row['employment_status'])): ?>
                                        <div class="detail-item">
                                            <i class="fas fa-briefcase"></i>
                                            <?php echo htmlspecialchars($row['employment_status']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="profile-actions">
                            <a href="view_profile.php?user_id=<?php echo urlencode($row['user_id']); ?>">
                                <button class="view-profile-btn">View Profile</button>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-search"></i>
                <p>No people found <?php echo !empty($searchQuery) ? 'for "' . htmlspecialchars($searchQuery) . '"' : ''; ?></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>