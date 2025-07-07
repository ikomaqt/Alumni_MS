<?php
// Only start session if one doesn't exist
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Responsive Sidebar Menu | Aski</title>
    <link rel="stylesheet" href="css/admin_navbar.css">
    <!-- Boxicons CDN Link -->
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .sidebar {
            position: fixed;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo-details">
            <img src="img/aski_logo.jpg" alt="Nesrac Logo" class="logo-image">
            <div class="logo_name">ASKI</div>
            <i class='bx bx-menu' id="btn"></i>
        </div>
        <ul class="nav-list">
            <li>
                <a href="admin_dash.php">
                    <i class='bx bx-grid-alt'></i>
                    <span class="links_name">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="job_list_new.php">
                    <i class='bx bxs-briefcase-alt-2'></i>
                    <span class="links_name">Jobs</span>
                </a>
            </li>
            <li>
                 <a href="event.php">
                    <i class='bx bxs-calendar-event'></i>
                    <span class="links_name">Events</span>
                </a>
            </li>
            <li>
                <a href="users_account.php">
                    <i class='bx bxs-user-account'></i>
                    <span class="links_name">Users Account</span>
                </a>
            </li>
            <li>
                <a href="alumni_list.php">
                    <i class='bx bxs-user-detail'></i>
                    <span class="links_name">Alumni List</span>
                </a>
            </li>
            <li>
                <a href="admin_profile.php">
                    <i class='bx bxs-user-circle'></i>
                    <span class="links_name">My Profile</span>
                </a>
            </li>

            <li class="profile">
                <a href="admin_logout.php" title="Logout">
                    <i class='bx bx-log-out' id="log_out"></i>
                </a>
            </li>
        </ul>
    </div>

    <script>
    let sidebar = document.querySelector(".sidebar");
    let closeBtn = document.querySelector("#btn");

    closeBtn.addEventListener("click", ()=> {
        sidebar.classList.toggle("open");
        menuBtnChange(); // calling the function (optional)
    });

    // Function to change sidebar button (optional)
    function menuBtnChange() {
        if (sidebar.classList.contains("open")) {
            closeBtn.classList.replace("bx-menu", "bx-menu-alt-right"); // replacing the icons class
        } else {
            closeBtn.classList.replace("bx-menu-alt-right", "bx-menu"); // replacing the icons class
        }
    }
    </script>
</body>
</html>