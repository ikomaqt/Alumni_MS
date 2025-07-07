<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'sqlconnection.php';
// Check if user is not logged in
if(!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Add this near the top after session check
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Get user's creation date first
    $userQuery = "SELECT created_at FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $userResult = $stmt->get_result();
    $userData = $userResult->fetch_assoc();
    $userCreatedAt = $userData['created_at'];
    
    // Modified notification count query (exclude jobs posted by user)
    $stmt = $conn->prepare("
        SELECT 
            SUM(CASE WHEN nr.notification_id IS NULL THEN 1 ELSE 0 END) as total_unread,
            SUM(CASE WHEN nr.notification_id IS NULL AND n.type = 'event' THEN 1 ELSE 0 END) as event_unread,
            SUM(CASE WHEN nr.notification_id IS NULL AND n.type = 'job' THEN 1 ELSE 0 END) as job_unread
        FROM (
            SELECT CONCAT('event-', id) as id, 'event' as type FROM events WHERE created_at >= ?
            UNION ALL
            SELECT CONCAT('job-', job_id), 'job' FROM job_listings WHERE created_at >= ? AND (posted_by_id IS NULL OR posted_by_id != ?)
        ) n
        LEFT JOIN notifications_read nr ON nr.notification_id = n.id AND nr.user_id = ?
    ");
    $stmt->bind_param("ssii", $userCreatedAt, $userCreatedAt, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $counts = $result->fetch_assoc();
    $notification_count = $counts['total_unread'];
}

// Add new endpoint for AJAX count updates
if (isset($_GET['action']) && $_GET['action'] === 'get_count') {
    header('Content-Type: application/json');
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        
        // Get user's creation date
        $userQuery = "SELECT created_at FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($userQuery);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $userResult = $stmt->get_result();
        $userData = $userResult->fetch_assoc();
        $userCreatedAt = $userData['created_at'];
        
        // Modified notification count query (exclude jobs posted by user)
        $stmt = $conn->prepare("
            SELECT 
                SUM(CASE WHEN nr.notification_id IS NULL THEN 1 ELSE 0 END) as total_unread,
                SUM(CASE WHEN nr.notification_id IS NULL AND n.type = 'event' THEN 1 ELSE 0 END) as event_unread,
                SUM(CASE WHEN nr.notification_id IS NULL AND n.type = 'job' THEN 1 ELSE 0 END) as job_unread
            FROM (
                SELECT CONCAT('event-', id) as id, 'event' as type FROM events WHERE created_at >= ?
                UNION ALL
                SELECT CONCAT('job-', job_id), 'job' FROM job_listings WHERE created_at >= ? AND (posted_by_id IS NULL OR posted_by_id != ?)
            ) n
            LEFT JOIN notifications_read nr ON nr.notification_id = n.id AND nr.user_id = ?
        ");
        $stmt->bind_param("ssii", $userCreatedAt, $userCreatedAt, $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $counts = $result->fetch_assoc();
        echo json_encode([
            'count' => (int)$counts['total_unread'],
            'eventCount' => (int)$counts['event_unread'],
            'jobCount' => (int)$counts['job_unread']
        ]);
        exit;
    }
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Fix the syntax error in current_page declaration
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Alumni Navbar</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Base Styles */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
  background-color: #f9fafb;
  color: #333;
  padding-top: 64px; /* Add padding equal to navbar height */
  transition: background-color 0.3s ease, color 0.3s ease;
}

/* Dark Mode Base Styles */
body.dark-mode {
  background-color: #1a202c;
  color: #e2e8f0;
}

/* Header - Fixed Position */
.unique-header {
  position: fixed;
  top: 0;
  left: 0;
  z-index: 50;
  width: 100%;
  background-color: white;
  border-bottom: 1px solid #e5e7eb;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: background-color 0.3s ease, border-color 0.3s ease;
}

body.dark-mode .unique-header {
  background-color: #2d3748;
  border-bottom-color: #4a5568;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.unique-container {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 64px;
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 8px;
}

@media (min-width: 640px) {
  .unique-container {
    padding: 0 16px;
  }
}

@media (min-width: 1024px) {
  .unique-container {
    padding: 0 24px;
  }
}

/* Logo and Navigation */
.unique-logo-nav {
  display: flex;
  align-items: center;
  gap: 8px;
}

.unique-logo {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  overflow: hidden;
  border: 3px solid #1e3a8a;
  transition: border-color 0.3s ease;
}

body.dark-mode .unique-logo {
  border-color: #63b3ed;
}

.unique-logo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

@media (min-width: 1024px) {
  .unique-logo-nav {
    gap: 24px;
  }
}

.unique-logo-link {
  display: flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
}

.unique-brand-name {
  display: none;
  font-weight: 600;
  color: #1e3a8a;
  font-family: 'Montserrat', sans-serif;
  transition: color 0.3s ease;
}

body.dark-mode .unique-brand-name {
  color: #63b3ed;
}

@media (min-width: 768px) {
  .unique-brand-name {
    display: inline-block;
  }
}

@media (min-width: 768px) and (max-width: 1199px) {
  .unique-brand-name {
    display: none;
  }
}

@media (min-width: 1200px) {
  .unique-brand-name {
    display: inline-block;
  }
}

/* Desktop Navigation */
.unique-desktop-nav {
  display: none;
}

@media (min-width: 768px) {
  .unique-desktop-nav {
    display: flex;
    align-items: center;
    gap: 32px;
  }
}

@media (min-width: 768px) and (max-width: 1199px) {
  .unique-desktop-nav {
    gap: 16px;
  }
}

.unique-nav-link {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 500;
  color: #4b5563;
  text-decoration: none;
  transition: color 0.2s;
  font-family: 'Poppins', sans-serif;
  position: relative;
}

body.dark-mode .unique-nav-link {
  color: #cbd5e0;
}

.unique-nav-link:hover {
  color: #1e3a8a;
}

body.dark-mode .unique-nav-link:hover {
  color: #ffffff;
}

.unique-icon {
  width: 20px;
  height: 20px;
  transition: stroke 0.3s ease;
}

body.dark-mode .unique-icon {
  stroke: #cbd5e0;
}

.unique-blue-icon {
  color: #1e3a8a;
}

body.dark-mode .unique-blue-icon {
  color: #63b3ed;
}

@media (min-width: 768px) and (max-width: 1199px) {
  .unique-nav-text {
    display: none;
  }
}

/* Active State */
.unique-nav-link.active {
  color: #1e3a8a;
  font-weight: 600;
  position: relative;
}

body.dark-mode .unique-nav-link.active {
  color: #ffffff;
}

.unique-nav-link.active::after {
  content: '';
  position: absolute;
  bottom: -12px;
  left: 0;
  width: 100%;
  height: 2px;
  background-color: #1e3a8a;
  transition: background-color 0.3s ease;
}

body.dark-mode .unique-nav-link.active::after {
  background-color: #63b3ed;
}

/* Search and Actions */
.unique-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

@media (min-width: 768px) {
  .unique-actions {
    gap: 16px;
  }
}

/* Theme Toggle */
.unique-theme-toggle {
  background: none;
  border: none;
  cursor: pointer;
  color: #4b5563;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  transition: background-color 0.2s;
}

body.dark-mode .unique-theme-toggle {
  color: #cbd5e0;
}

.unique-theme-toggle:hover {
  background-color: #f3f4f6;
}

body.dark-mode .unique-theme-toggle:hover {
  background-color: #4a5568;
}

.unique-theme-icon {
  width: 20px;
  height: 20px;
}

/* Search Toggle Button */
.unique-search-toggle {
  display: none;
  background: none;
  border: none;
  cursor: pointer;
  color: #4b5563;
}

body.dark-mode .unique-search-toggle {
  color: #cbd5e0;
}

@media (min-width: 768px) and (max-width: 1199px) {
  .unique-search-toggle {
    display: flex;
  }
}

/* Search Container */
.unique-search-container {
  position: relative;
  display: none;
  width: 180px;
  transition: width 0.3s ease;
}

@media (min-width: 768px) {
  .unique-search-container {
    display: block;
  }
}

@media (min-width: 768px) and (max-width: 1199px) {
  .unique-search-container.unique-expanded {
    width: 300px;
  }
  
  .unique-search-container:not(.unique-expanded) {
    display: none;
  }
}

@media (min-width: 1024px) {
  .unique-search-container {
    width: 220px;
  }
}

@media (min-width: 1280px) {
  .unique-search-container {
    width: 300px;
  }
}

.unique-search-icon {
  position: absolute;
  left: 10px;
  top: 10px;
  width: 16px;
  height: 16px;
  color: #6b7280;
}

body.dark-mode .unique-search-icon {
  color: #a0aec0;
}

.unique-search-input {
  width: 100%;
  height: 36px;
  padding: 8px 8px 8px 32px;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 14px;
  transition: all 0.3s ease;
}

body.dark-mode .unique-search-input {
  background-color: #2d3748;
  border-color: #4a5568;
  color: #e2e8f0;
}

.unique-search-input:focus {
  outline: none;
  border-color: #1e3a8a;
  box-shadow: 0 0 0 2px rgba(30, 58, 138, 0.1);
}

body.dark-mode .unique-search-input:focus {
  border-color: #63b3ed;
  box-shadow: 0 0 0 2px rgba(99, 179, 237, 0.2);
}

.unique-close-search {
  position: absolute;
  right: 0;
  top: 0;
  height: 100%;
  background: none;
  border: none;
  cursor: pointer;
  display: none;
}

.unique-search-container.unique-expanded .unique-close-search {
  display: block;
}

/* Profile Button */
.unique-profile-button {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 1px solid #e5e7eb;
  background: none;
  color: #4b5563;
  cursor: pointer;
  transition: all 0.3s ease;
}

body.dark-mode .unique-profile-button {
  border-color: #4a5568;
  color: #cbd5e0;
}

.unique-profile-button:hover {
  background-color: #f9fafb;
}

body.dark-mode .unique-profile-button:hover {
  background-color: #4a5568;
}

/* Mobile Menu Toggle */
.unique-mobile-toggle {
  display: flex;
  background: none;
  border: none;
  color: #4b5563;
  cursor: pointer;
}

body.dark-mode .unique-mobile-toggle {
  color: #cbd5e0;
}

@media (min-width: 768px) {
  .unique-mobile-toggle {
    display: none;
  }
}

/* Mobile Menu */
.unique-mobile-menu {
  position: fixed;
  top: 0;
  right: -300px; /* Changed from left: -300px */
  width: 300px;
  height: 100vh;
  background-color: white;
  z-index: 100;
  box-shadow: -4px 0 10px rgba(0, 0, 0, 0.1); /* Changed from 4px to -4px */
  transition: right 0.3s ease, background-color 0.3s ease; /* Changed from left to right */
  display: flex;
  flex-direction: column;
  overflow-y: auto;
}

body.dark-mode .unique-mobile-menu {
  background-color: #2d3748;
  box-shadow: -4px 0 10px rgba(0, 0, 0, 0.3); /* Changed from 4px to -4px */
}

.unique-mobile-menu.unique-open {
  right: 0; /* Changed from left: 0 */
}

@media (min-width: 640px) {
  .unique-mobile-menu {
    width: 350px;
    right: -350px; /* Changed from left: -350px */
  }
}

.unique-mobile-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  border-bottom: 1px solid #e5e7eb;
  transition: border-color 0.3s ease;
}

body.dark-mode .unique-mobile-header {
  border-bottom-color: #4a5568;
}

.unique-mobile-logo .unique-logo {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  overflow: hidden;
  border: 1px solid #1e3a8a;
  transition: border-color 0.3s ease;
}

body.dark-mode .unique-mobile-logo .unique-logo {
  border-color: #63b3ed;
}

.unique-mobile-logo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.unique-close-menu {
  background: none;
  border: none;
  color: #4b5563;
  cursor: pointer;
  transition: color 0.3s ease;
}

body.dark-mode .unique-close-menu {
  color: #cbd5e0;
}

/* Mobile Navigation */
.unique-mobile-nav {
  display: flex;
  flex-direction: column;
  gap: 16px;
  padding: 0 16px;
}

.unique-mobile-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 12px;
  border-radius: 4px;
  text-decoration: none;
  color: #4b5563;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
}

body.dark-mode .unique-mobile-link {
  color: #cbd5e0;
}

.unique-mobile-link:hover {
  background-color: #f9fafb;
}

body.dark-mode .unique-mobile-link:hover {
  background-color: #4a5568;
}

/* Mobile Bookmark Submenu */
#bookmarkSubmenu {
  display: none;
  flex-direction: column;
  margin-left: 32px;
}

/* Overlay */
.unique-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 90;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.3s;
}

/* Page Content (for demo) */
.unique-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 40px 20px;
}

.unique-title {
  font-size: 28px;
  font-weight: bold;
  color: #1e3a8a;
  margin-bottom: 20px;
  font-family: 'Montserrat', sans-serif;
  transition: color 0.3s ease;
}

body.dark-mode .unique-title {
  color: #63b3ed;
}

.unique-description {
  font-size: 16px;
  line-height: 1.6;
  color: #4b5563;
  margin-bottom: 30px;
  transition: color 0.3s ease;
}

body.dark-mode .unique-description {
  color: #a0aec0;
}

.unique-card-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 20px;
}

@media (min-width: 640px) {
  .unique-card-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1024px) {
  .unique-card-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

.unique-card {
  background-color: white;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
}

body.dark-mode .unique-card {
  background-color: #2d3748;
  border-color: #4a5568;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.unique-card-title {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 8px;
  transition: color 0.3s ease;
}

body.dark-mode .unique-card-title {
  color: #ffffff;
}

.unique-card-text {
  font-size: 14px;
  color: #6b7280;
  transition: color 0.3s ease;
}

body.dark-mode .unique-card-text {
  color: #a0aec0;
}

.unique-profile-dropdown {
  position: relative;
}

.unique-profile-menu {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  background-color: white;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  min-width: 150px;
  display: none;
  z-index: 100;
  transition: all 0.3s ease;
}

body.dark-mode .unique-profile-menu {
  background-color: #2d3748;
  border-color: #4a5568;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
}

.unique-profile-menu.active {
  display: block;
}

.unique-profile-menu a {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  color: #4b5563;
  text-decoration: none;
  font-size: 14px;
  transition: all 0.2s;
}

body.dark-mode .unique-profile-menu a {
  color: #cbd5e0;
}

.unique-profile-menu a:hover {
  background-color: #f3f4f6;
}

body.dark-mode .unique-profile-menu a:hover {
  background-color: #4a5568;
}

.unique-profile-menu a:first-child {
  border-bottom: 1px solid #e5e7eb;
}

body.dark-mode .unique-profile-menu a:first-child {
  border-bottom-color: #4a5568;
}

/* Notification Badges */
.nav-icon-container {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.notification-badge {
  position: absolute;
  top: -6px;
  right: -6px;
  background-color: #ef4444;
  color: white;
  border-radius: 9999px;
  font-size: 0.75rem;
  min-width: 16px;
  height: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
  font-weight: 600;
}

/* Adjust mobile notification badge position */
.unique-mobile-link .notification-badge {
  position: absolute;
  top: 50%;
  right: 16px;
  transform: translateY(-50%);
}

/* Hide search, theme, and profile on mobile (move to burger menu) */
@media (max-width: 767px) {
  .unique-theme-toggle,
  .unique-profile-dropdown {
    display: none !important;
  }
  
  /* Modify search container for mobile */
  .unique-search-container {
    width: 100%;
    max-width: 200px;
  }
}
  </style>
</head>
<body>
  <header class="unique-header">
    <div class="unique-container">
      <div class="unique-logo-nav">
        <!-- Logo -->
        <a href="home.php" class="unique-logo-link">
          <div class="unique-logo">
            <img src="img/logo.png" alt="ASKI-SKI Logo">
          </div>
         
        </a>
        
        <!-- Desktop Navigation -->
        <nav class="unique-desktop-nav">
          <a href="home.php" class="unique-nav-link <?php echo $current_page === 'home.php' ? 'active' : ''; ?>">
            <svg class="unique-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
              <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            <span class="unique-nav-text">Home</span>
          </a>
          <a href="job_info.php" class="unique-nav-link <?php echo $current_page === 'job_info.php' ? 'active' : ''; ?>">
            <svg class="unique-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect width="20" height="14" x="2" y="7" rx="2" ry="2"></rect>
              <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
            </svg>
            <span class="unique-nav-text">Jobs</span>
          </a>
          <a href="user_view_event.php" class="unique-nav-link <?php echo $current_page === 'user_view_event.php' ? 'active' : ''; ?>">
            <svg class="unique-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
              <line x1="16" x2="16" y1="2" y2="6"></line>
              <line x1="8" x2="8" y1="2" y2="6"></line>
              <line x1="3" x2="21" y1="10" y2="10"></line>
            </svg>
            <span class="unique-nav-text">Events</span>
          </a>
          <a href="notifications.php" class="unique-nav-link <?php echo $current_page === 'notifications.php' ? 'active' : ''; ?>">
            <div class="nav-icon-container">
              <svg class="unique-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
                <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
              </svg>
              <?php if ($notification_count > 0): ?>
                  <span class="notification-badge"><?php echo $notification_count; ?></span>
              <?php endif; ?>
            </div>
            <span class="unique-nav-text">Notifications</span>
          </a>
        </nav>
      </div>
      
      <!-- Search and Profile (hide on mobile) -->
      <div class="unique-actions">
        <!-- Search Bar (always visible on large, toggles on medium, hidden on mobile) -->
        <form id="searchForm" class="unique-search-container" action="search_user.php" method="get" style="display:inline-block; position:relative;">
          <input 
            type="search" 
            name="q" 
            placeholder="Search users..." 
            class="unique-search-input" 
            autocomplete="off" 
            aria-label="Search users"
            style="padding-left:38px; padding-right:38px; background:#f3f4f6; border-radius:20px; border:1px solid #d1d5db; height:38px;"
          >
          <!-- Search icon inside input -->
          
          <!-- Search button at right -->
          <button 
            type="submit" 
            class="unique-search-btn" 
            aria-label="Search"
            style="position:absolute;right:4px;top:50%;transform:translateY(-50%);background:#1e3a8a;border:none;border-radius:50%;width:30px;height:30px;display:flex;align-items:center;justify-content:center;cursor:pointer;"
          >
            <svg width="16" height="16" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
          </button>
        </form>
        <!-- Search Toggle Button (Medium Screens Only, hidden on mobile) -->
        <button id="searchToggle" class="unique-search-toggle" type="button" aria-label="Open search" style="margin-left:4px;">
          <svg class="unique-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
        </button>

        <!-- Profile Dropdown (keep) -->
        <div class="unique-profile-dropdown">
          <button id="profileButton" class="unique-profile-button">
            <svg class="unique-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
          </button>
          <div id="profileMenu" class="unique-profile-menu">
            <a href="edit_profile.php">
              <svg class="unique-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
              Profile
            </a>
            <a href="login.php">
              <svg class="unique-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
              </svg>
              Logout
            </a>
          </div>
        </div>

        <!-- Mobile Menu Button -->
        <button id="mobileMenuToggle" class="unique-mobile-toggle">
          <svg class="unique-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="4" x2="20" y1="12" y2="12"></line>
            <line x1="4" x2="20" y1="6" y2="6"></line>
            <line x1="4" x2="20" y1="18" y2="18"></line>
          </svg>
        </button>
      </div>
    </div>
  </header>
  
  <!-- Mobile Menu Drawer -->
  <div id="mobileMenu" class="unique-mobile-menu">
    <div class="unique-mobile-header">
      <!-- Replace logo with burger icon as close toggle (24x24, thin lines) -->
      <button id="closeMenu" class="unique-close-menu" style="background:none;border:none;padding:0;display:flex;align-items:center;justify-content:center;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
          <rect x="4" y="6" width="16" height="2" rx="1" fill="black"/>
          <rect x="4" y="11" width="12" height="2" rx="1" fill="black"/>
          <rect x="4" y="16" width="8" height="2" rx="1" fill="black"/>
        </svg>
      </button>
    </div>
    
    <!-- Mobile Navigation -->
    <nav class="unique-mobile-nav">
      <!-- Profile in burger menu (moved before Home) -->
      <a href="edit_profile.php" class="unique-mobile-link">
        <svg class="unique-icon unique-blue-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
          <circle cx="12" cy="7" r="4"></circle>
        </svg>
        <span>Profile</span>
      </a>
      <a href="home.php" class="unique-mobile-link <?php echo $current_page === 'home.php' ? 'active' : ''; ?>">
        <svg class="unique-icon unique-blue-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
          <polyline points="9 22 9 12 15 12 15 22"></polyline>
        </svg>
        <span>Home</span>
      </a>
      <a href="job_info.php" class="unique-mobile-link <?php echo $current_page === 'job_info.php' ? 'active' : ''; ?>">
        <svg class="unique-icon unique-blue-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect width="20" height="14" x="2" y="7" rx="2" ry="2"></rect>
          <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
        </svg>
        <span>Jobs</span>
      </a>
      <a href="user_view_event.php" class="unique-mobile-link <?php echo $current_page === 'user_view_event.php' ? 'active' : ''; ?>">
        <svg class="unique-icon unique-blue-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
          <line x1="16" x2="16" y1="2" y2="6"></line>
          <line x1="8" x2="8" y1="2" y2="6"></line>
          <line x1="3" x2="21" y1="10" y2="10"></line>
        </svg>
        <span>Events</span>
      </a>
      <!-- Bookmark collapsible menu -->
      <div id="bookmarkMenuWrapper" style="position:relative;">
        <a href="#" id="bookmarkToggle" class="unique-mobile-link" style="display:flex;justify-content:space-between;align-items:center;">
          <span style="display:flex;align-items:center;gap:12px;">
            <svg class="unique-icon unique-blue-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
            </svg>
            <span>Bookmark</span>
          </span>
          <svg id="bookmarkChevron" width="18" height="18" style="transition:transform 0.2s;" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <polyline points="6 9 12 15 18 9"></polyline>
          </svg>
        </a>
        <div id="bookmarkSubmenu" style="display:none;flex-direction:column;margin-left:32px;">
          <a href="saved_jobs.php" class="unique-mobile-link" style="padding-left:0;">
            <svg class="unique-icon unique-blue-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect width="20" height="14" x="2" y="7" rx="2" ry="2"></rect>
              <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
            </svg>
            <span>Saved Jobs</span>
          </a>
          <a href="saved_events.php" class="unique-mobile-link" style="padding-left:0;">
            <svg class="unique-icon unique-blue-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
              <line x1="16" x2="16" y1="2" y2="6"></line>
              <line x1="8" x2="8" y1="2" y2="6"></line>
              <line x1="3" x2="21" y1="10" y2="10"></line>
            </svg>
            <span>Saved Events</span>
          </a>
          <a href="user_posted_jobs.php" class="unique-mobile-link" style="padding-left:0;">
            <svg class="unique-icon unique-blue-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect width="20" height="14" x="2" y="7" rx="2" ry="2"></rect>
              <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
              <polyline points="7 10 12 15 17 10"></polyline>
            </svg>
            <span>My Posted Jobs</span>
          </a>
        </div>
      </div>
      <!-- Remove Settings collapsible menu -->
      <a href="notifications.php" class="unique-mobile-link <?php echo $current_page === 'notifications.php' ? 'active' : ''; ?>">
        <div class="nav-icon-container">
          <svg class="unique-icon unique-blue-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
          </svg>
          <?php if ($notification_count > 0): ?>
              <span class="notification-badge"><?php echo $notification_count; ?></span>
          <?php endif; ?>
        </div>
        <span>Notifications</span>
      </a>
      <a href="login.php" class="unique-mobile-link">
        <svg class="unique-icon unique-blue-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
          <polyline points="16 17 21 12 16 7"></polyline>
          <line x1="21" y1="12" x2="9" y2="12"></line>
        </svg>
        <span>Logout</span>
      </a>
    </nav>
    
    <!-- Remove Mobile Language Selector -->
  </div>

  <!-- Overlay will be created by JavaScript -->
  

  <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Elements
        const searchToggle = document.getElementById('searchToggle');
        const searchContainer = document.getElementById('searchContainer');
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mobileMenu = document.getElementById('mobileMenu');
        const closeMenu = document.getElementById('closeMenu');
        // Update search input selector for focus/blur logic
        const searchInput = document.querySelector('.unique-search-container input[name="q"]');
        
        // Create overlay
        const overlay = document.createElement('div');
        overlay.className = 'unique-overlay';
        document.body.appendChild(overlay);
        
        // Window width tracking
        let windowWidth = window.innerWidth;
        const isCompactMode = () => windowWidth >= 768 && windowWidth < 1200;
        
        // Search toggle functionality
        if (searchToggle) {
          searchToggle.addEventListener('click', function() {
            if (isCompactMode()) {
              searchContainer.classList.add('unique-expanded');
              searchToggle.style.display = 'none';
              searchContainer.style.display = 'block';
              setTimeout(() => {
                searchInput.focus();
              }, 100);
            }
          });
        }
        
        // Search blur event
        if (searchInput) {
          searchInput.addEventListener('blur', function() {
            if (isCompactMode() && !searchInput.value) {
              setTimeout(() => {
                searchContainer.classList.remove('unique-expanded');
                searchToggle.style.display = 'flex';
                searchContainer.style.display = 'none';
              }, 200);
            }
          });
        }
        
        // Mobile menu toggle
        if (mobileMenuToggle) {
          mobileMenuToggle.addEventListener('click', function() {
            mobileMenu.classList.add('unique-open');
            overlay.classList.add('unique-visible');
            document.body.style.overflow = 'hidden';
          });
        }
        
        // Close mobile menu
        if (closeMenu) {
          closeMenu.addEventListener('click', function() {
            mobileMenu.classList.remove('unique-open');
            overlay.classList.remove('unique-visible');
            document.body.style.overflow = '';
          });
        }
        
        // Close menu when clicking overlay
        overlay.addEventListener('click', function() {
          mobileMenu.classList.remove('unique-open');
          overlay.classList.remove('unique-visible');
          document.body.style.overflow = '';
        });
        
        // Close menu when clicking mobile nav links
        const mobileLinks = document.querySelectorAll('.unique-mobile-link:not(#bookmarkToggle):not(#settingsToggle):not(#mobileThemeToggle)');
        mobileLinks.forEach(link => {
          link.addEventListener('click', function(e) {
            // Don't close menu if clicking bookmark or settings submenu items
            if (e.target.closest('#bookmarkSubmenu') || e.target.closest('#settingsSubmenu')) {
              return;
            }
            mobileMenu.classList.remove('unique-open');
            overlay.classList.remove('unique-visible');
            document.body.style.overflow = '';
          });
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
          windowWidth = window.innerWidth;
          
          // Reset search expansion on larger screens
          if (windowWidth >= 1200) {
            searchContainer.classList.remove('unique-expanded');
            if (searchToggle) searchToggle.style.display = 'none';
            if (searchContainer) searchContainer.style.display = 'block';
          } else if (windowWidth >= 768 && windowWidth < 1200) {
            if (!searchContainer.classList.contains('unique-expanded')) {
              if (searchToggle) searchToggle.style.display = 'flex';
              if (searchContainer) searchContainer.style.display = 'none';
            }
          }
          
          // Close mobile menu on larger screens
          if (windowWidth >= 768) {
            mobileMenu.classList.remove('unique-open');
            overlay.classList.remove('unique-visible');
            document.body.style.overflow = '';
          }
        });

        // --- Profile dropdown functionality (run only once) ---
        (function() {
          const profileButton = document.getElementById('profileButton');
          const profileMenu = document.getElementById('profileMenu');
          if (profileButton && profileMenu) {
            // Remove any previous listeners to avoid duplicates
            profileButton.onclick = function(e) {
              e.preventDefault();
              e.stopPropagation();
              profileMenu.classList.toggle('active');
            };
            document.addEventListener('click', function(e) {
              if (!profileButton.contains(e.target)) {
                profileMenu.classList.remove('active');
              }
            });
          }
        })();

        // Simplified SSE setup with better error handling
        function setupNotificationSSE() {
            const evtSource = new EventSource('notifications.php?count_only=1');
            
            evtSource.onmessage = function(event) {
                try {
                    const data = JSON.parse(event.data);
                    updateNavbarBadges({
                        count: data.unreadCount,
                        eventCount: data.unreadEventCount,
                        jobCount: data.unreadJobCount
                    });
                } catch (error) {
                    console.error('Error parsing SSE data:', error);
                }
            };

            evtSource.onerror = function(err) {
                console.error('SSE Error:', err);
                evtSource.close();
                // Attempt to reconnect after 5 seconds
                setTimeout(setupNotificationSSE, 5000);
            };
        }

        // Simplified badge update function
        function updateNavbarBadges(counts) {
            const badgeElements = {
                notifications: document.querySelectorAll('a[href*="notifications.php"] .notification-badge'),
                events: document.querySelectorAll('a[href*="user_view_event.php"] .notification-badge'),
                jobs: document.querySelectorAll('a[href*="job_info.php"] .notification-badge')
            };

            // Helper function to update badge visibility and count
            function updateBadge(elements, count) {
                elements.forEach(badge => {
                    if (count > 0) {
                        badge.textContent = count;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                });
            }

            // Update all badges
            updateBadge(badgeElements.notifications, counts.count);
            updateBadge(badgeElements.events, counts.eventCount);
            updateBadge(badgeElements.jobs, counts.jobCount);
        }

        // Make functions available globally
        window.updateNavbarBadges = updateNavbarBadges;
        window.updateNavbarNotificationCount = function() {
            fetch('user_navbar.php?action=get_count')
                .then(response => response.json())
                .then(data => {
                    if (!data.error) {
                        updateNavbarBadges(data);
                    }
                })
                .catch(error => console.error('Error updating notification count:', error));
        };

        // Initialize notifications
        setupNotificationSSE();
        updateNavbarNotificationCount();

        // Remove theme toggle functionality
        // Remove setupThemeToggle and related icon logic

        // Bookmark submenu toggle for mobile nav
        const bookmarkToggle = document.getElementById('bookmarkToggle');
        const bookmarkSubmenu = document.getElementById('bookmarkSubmenu');
        const bookmarkChevron = document.getElementById('bookmarkChevron');
        if (bookmarkToggle && bookmarkSubmenu && bookmarkChevron) {
          bookmarkToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent event from bubbling up
            const isOpen = bookmarkSubmenu.style.display === 'flex';
            bookmarkSubmenu.style.display = isOpen ? 'none' : 'flex';
            bookmarkChevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
          });
        }
    });
  </script>
</body>
</html>