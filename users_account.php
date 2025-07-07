<?php
session_start();

// Check if user is not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once('sqlconnection.php');

// Add helper function for status classes
function getStatusClass($status) {
    switch (strtolower($status)) {
        case 'active': return 'status-active';
        case 'deactivated': return 'status-deactivated';
        case 'employed': return 'status-employed';
        case 'unemployed': return 'status-unemployed';
        case 'student': return 'status-student';
        default: return '';
    }
}

// Update pagination parameters
$items_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Modify the queries to include pagination
$pending = [];
$pending_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'Pending'"))['count'];
$query = "SELECT * FROM users WHERE role = 'Pending' LIMIT $items_per_page OFFSET $offset";
$result = mysqli_query($conn, $query);

while($row = mysqli_fetch_assoc($result)) {
    $pending[] = [
        'id' => $row['lrn'],
        'name' => $row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name'],
        'email' => $row['email'],
        'role' => $row['role'],
        'requestDate' => $row['created_at'],
        'created' => $row['created_at']
    ];
}

// Add AJAX handler for user details
if (isset($_GET['action']) && $_GET['action'] === 'get_user_details' && isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
    $userQuery = "SELECT * FROM users WHERE lrn = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $userResult = $stmt->get_result();

    if ($userResult->num_rows === 0) {
        echo "<p class='info-value'>User not found</p>";
        exit;
    }

    $user = $userResult->fetch_assoc();

    // Profile Section
    echo "<div class='profile-section'>";
    if(!empty($user['profile_img'])) {
        echo "<img src='" . htmlspecialchars($user['profile_img']) . "' class='profile-image' alt='Profile Image'>";
    } else {
        echo "<div class='profile-placeholder'><i class='fas fa-user'></i></div>";
    }
    echo "<h3 class='profile-name'>" . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "</h3>";
    echo "</div>";

    // Basic Information
    echo "<div class='detail-set'>";
    echo "<div class='detail-set-title'>Basic Information</div>";
    echo "<div class='detail-set-content'>";
    echo "<div class='detail-item'><span class='detail-label'>Email:</span> <span class='detail-value'>" . htmlspecialchars($user['email']) . "</span></div>";
    echo "<div class='detail-item'><span class='detail-label'>Role:</span> <span class='detail-value'>" . htmlspecialchars($user['role']) . "</span></div>";
    echo "<div class='detail-item'><span class='detail-label'>Status:</span> <span class='detail-value " . getStatusClass($user['status']) . "'>" . htmlspecialchars($user['status']) . "</span></div>";
    echo "</div>";
    echo "</div>";

    // Contact Information
    echo "<div class='detail-set'>";
    echo "<div class='detail-set-title'>Contact Information</div>";
    echo "<div class='detail-set-content'>";
    echo "<div class='detail-item'><span class='detail-label'>Phone:</span> <span class='detail-value'>" . htmlspecialchars($user['phone']) . "</span></div>";
    echo "<div class='detail-item'><span class='detail-label'>Address:</span> <span class='detail-value'>" . htmlspecialchars($user['address']) . "</span></div>";
    echo "</div>";
    echo "</div>";

    // Employment Information
    echo "<div class='detail-set'>";
    echo "<div class='detail-set-title'>Employment Information</div>";
    echo "<div class='detail-set-content'>";
    echo "<div class='detail-item'><span class='detail-label'>Company:</span> <span class='detail-value'>" . htmlspecialchars($user['company']) . "</span></div>";
    echo "<div class='detail-item'><span class='detail-label'>Position:</span> <span class='detail-value'>" . htmlspecialchars($user['position']) . "</span></div>";
    echo "<div class='detail-item'><span class='detail-label'>Employment Status:</span> <span class='detail-value " . getStatusClass($user['employment_status']) . "'>" . htmlspecialchars($user['employment_status']) . "</span></div>";
    echo "</div>";
    echo "</div>";

    // Resume Information
    echo "<div class='detail-set'>";
    echo "<div class='detail-set-title'>Resume</div>";
    echo "<div class='detail-set-content'>";
    echo "<div class='detail-item'><span class='detail-label'>Resume:</span> <span class='detail-value'>";
    if (!empty($user['resume'])) {
        echo "<a href='view_resume.php?file=" . urlencode($user['resume']) . "' target='_blank'>";
        echo "<i class='fas fa-file-pdf'></i> View Resume";
        echo "</a>";
    } else {
        echo "No resume uploaded";
    }
    echo "</span></div>";
    echo "</div>";
    echo "</div>";

    // Education Information
    echo "<div class='detail-set'>";
    echo "<div class='detail-set-title'>Education Information</div>";
    echo "<div class='detail-set-content'>";
    echo "<div class='detail-item'><span class='detail-label'>Institution:</span> <span class='detail-value'>" . htmlspecialchars($user['institution']) . "</span></div>";
    echo "<div class='detail-item'><span class='detail-label'>Degree:</span> <span class='detail-value'>" . htmlspecialchars($user['degree']) . "</span></div>";
    echo "<div class='detail-item'><span class='detail-label'>Field of Study:</span> <span class='detail-value'>" . htmlspecialchars($user['field_of_study']) . "</span></div>";
    echo "<div class='detail-item'><span class='detail-label'>Graduation Year:</span> <span class='detail-value'>" . htmlspecialchars($user['graduation_year']) . "</span></div>";
    echo "</div>";
    echo "</div>";

    echo "</div>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Approval</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Add SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Add these new styles at the beginning */
        .dashboard {
            display: flex;
            width: 100%;
        }
        
        .content-wrapper {
            flex: 1;
            margin-left: 78px; /* Default margin for collapsed sidebar */
            transition: margin-left 0.3s;
            padding: 20px;
        }

        /* When sidebar is open */
        .sidebar.open ~ .content-wrapper {
            margin-left: 250px;
        }

        /* Modify your existing styles */
        .main-content {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        /* Dashboard layout */
        .dashboard {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        .dashboard-header {
            margin-bottom: 2rem;
        }

        .dashboard-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .dashboard-header p {
            color: #6b7280;
            font-size: 0.875rem;
        }

        /* Tabs */
        .tabs {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .tab-content {
            display: block;
            padding: 1.5rem;
        }

        /* Badge */
        .badge {
            background-color: #ef4444;
            color: white;
            font-size: 0.75rem;
            border-radius: 9999px;
            padding: 0.125rem 0.5rem;
            margin-left: 0.5rem;
        }

        /* Search */
        .search-container {
            display: flex;
            align-items: center;
            max-width: 24rem;
            margin-bottom: 1rem;
            background-color: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
        }

        .search-container i {
            color: #6b7280;
            margin-right: 0.5rem;
        }

        .search-container input {
            border: none;
            outline: none;
            width: 100%;
            font-size: 0.875rem;
        }

        /* Table */
        .table-container {
            overflow-x: auto;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
        }

        .user-table th {
            background-color: #f9fafb;
            font-weight: 500;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            text-align: left;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .user-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.875rem;
        }

        .user-table tr:last-child td {
            border-bottom: none;
        }

        .user-table tr:hover {
            background-color: #f9fafb;
            cursor: pointer;
        }

        .user-row {
            transition: background-color 0.2s;
        }

        /* Action buttons */
        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s;
        }

        .btn-approve {
            background-color: #f0fdf4;
            color: #16a34a;
            border-color: #dcfce7;
        }

        .btn-approve:hover {
            background-color: #dcfce7;
        }

        .btn-reject {
            background-color: #fef2f2;
            color: #dc2626;
            border-color: #fee2e2;
        }

        .btn-reject:hover {
            background-color: #fee2e2;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .user-table th:nth-child(5),
            .user-table td:nth-child(5) {
                display: none;
            }
        }

        /* Add these styles to your existing CSS */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
            padding: 10px;
        }

        .pagination-controls {
            display: flex;
            gap: 10px;
        }

        .pagination-btn {
            padding: 8px;
            border: none;
            background-color: #e2e8f0;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .pagination-btn:hover {
            background-color: #cbd5e1;
        }

        .page-info {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 4px;
            color: white;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 1000;
        }

        .notification.success {
            background-color: #4caf50;
            opacity: 1;
        }

        .notification.error {
            background-color: #f44336;
            opacity: 1;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Modal styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal {
            background: white;
            border-radius: 8px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .modal-body {
            padding: 1rem;
        }

        .btn-primary {
            background-color: #3490dc;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2779bd;
        }

        .modal-tabs {
            display: flex;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 1rem;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Additional styles for alumni details modal */
        .profile-section {
            text-align: center;
            margin-bottom: 2rem;
            padding: 1rem;
        }

        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 1rem;
        }

        .profile-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 3rem;
            color: #9ca3af;
        }

        .profile-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
        }

        .detail-set {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .detail-set-title {
            background: #f9fafb;
            padding: 0.75rem 1rem;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-set-content {
            padding: 1rem;
        }

        .detail-item {
            display: flex;
            margin-bottom: 0.75rem;
            font-size: 0.875rem;
        }

        .detail-item:last-child {
            margin-bottom: 0;
        }

        .detail-label {
            flex: 0 0 150px;
            color: #6b7280;
            font-weight: 500;
        }

        .detail-value {
            color: #1f2937;
            flex: 1;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-active {
            background-color: #dcfce7;
            color: #16a34a;
        }

        .status-deactivated {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .status-employed {
            background-color: #dcfce7;
            color: #16a34a;
        }

        .status-unemployed {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .status-student {
            background-color: #fef3c7;
            color: #d97706;
        }

        /* Style adjustments for modal content */
        .modal {
            background: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .modal-header {
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 1.5rem;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #111827;
        }

        .modal-close {
            color: #6b7280;
            transition: color 0.2s;
        }

        .modal-close:hover {
            color: #111827;
        }

        .modal-body {
            padding: 1.5rem;
            max-height: calc(90vh - 100px);
            overflow-y: auto;
        }

        /* Additional styles for file links */
        .detail-value a {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: #3b82f6;
        }

        .detail-value a:hover {
            text-decoration: underline;
        }

        .detail-value a i {
            font-size: 1rem;
        }

        /* These styles ensure consistent spacing and alignment */
        .detail-set + .detail-set {
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
    <div class="content-wrapper">
        <div class="dashboard">
            <main class="main-content">
                <div class="dashboard-header">
                    <h2>Pending Approval</h2>
                    <p>View and manage all pending user approval requests.</p>
                </div>
                
                <div class="tabs">
                    <div class="tab-content active" id="pending-users">
                        <div class="search-container">
                            <i class="fas fa-search"></i>
                            <input type="text" id="pending-search" placeholder="Search pending requests...">
                        </div>
                        
                        <div class="table-container">
                            <table class="user-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Requested Role</th>
                                        <th>Request Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="pending-users-table">
                                    <!-- Pending user rows will be populated by JavaScript -->
                                </tbody>
                            </table>
                            <div class="pagination">
                                <div class="pagination-controls">
                                    <button id="prevPage" class="pagination-btn" onclick="navigatePage('prev')">
                                        <i class='bx bx-chevron-left'></i>
                                    </button>
                                    <button id="nextPage" class="pagination-btn" onclick="navigatePage('next')">
                                        <i class='bx bx-chevron-right'></i>
                                    </button>
                                </div>
                                <span id="pageInfo" class="page-info">Page <?php echo $page; ?> of <?php echo ceil($pending_total / $items_per_page); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal for viewing alumni details -->
    <div class="modal-overlay" id="alumniModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Alumni Details</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-tabs">
                    <div class="modal-tab active" data-tab="details">Details</div>
                </div>
                <div class="tab-content active" id="details-tab">
                    <div id="alumni-details-content">
                        <p>Loading details...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="notification" class="notification"></div>

    <script>
        // Replace mock data with PHP data
        const pendingUsers = <?php echo json_encode($pending); ?>;

        // DOM elements
        const pendingUsersTable = document.getElementById('pending-users-table');
        const pendingSearch = document.getElementById('pending-search');

        // Render pending users table
        function renderPendingUsers(users) {
            pendingUsersTable.innerHTML = '';
            
            if (users.length === 0) {
                pendingUsersTable.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-8">No pending requests found.</td>
                    </tr>
                `;
                return;
            }
            
            users.forEach(user => {
                const row = document.createElement('tr');
                row.className = 'user-row';
                row.setAttribute('data-id', user.id);
                
                row.innerHTML = `
                    <td>${user.name}</td>
                    <td>${user.email}</td>
                    <td>${user.role}</td>
                    <td>${user.requestDate}</td>
                    <td>
                        <div class="action-buttons">
                            <button class='btn btn-primary' onclick='viewAlumniDetails("${user.id}")'><i class='fas fa-eye'></i> View</button>
                            <button class="btn btn-approve" data-id="${user.id}">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            <button class="btn btn-reject" data-id="${user.id}">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </div>
                    </td>
                `;
                
                pendingUsersTable.appendChild(row);
            });
            
            // Add event listeners for approve/reject buttons
            document.querySelectorAll('.btn-approve').forEach(button => {
                button.addEventListener('click', (e) => {
                    e.stopPropagation();
                    approveUser(button.getAttribute('data-id'));
                });
            });
            
            document.querySelectorAll('.btn-reject').forEach(button => {
                button.addEventListener('click', (e) => {
                    e.stopPropagation();
                    rejectUser(button.getAttribute('data-id'));
                });
            });
        }

        // Approve pending user
        function approveUser(userId) {
            const button = document.querySelector(`.btn-approve[data-id="${userId}"]`);
            const rejectButton = document.querySelector(`.btn-reject[data-id="${userId}"]`);
            
            button.disabled = true;
            rejectButton.disabled = true;

            // Show loading alert
            Swal.fire({
                title: 'Processing...',
                text: 'Approving user request',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch('approve_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${userId}&action=approve`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: data.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message,
                        icon: 'error'
                    });
                    button.disabled = false;
                    rejectButton.disabled = false;
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred',
                    icon: 'error'
                });
                button.disabled = false;
                rejectButton.disabled = false;
            });
        }

        // Reject pending user
        function rejectUser(userId) {
            const button = document.querySelector(`.btn-reject[data-id="${userId}"]`);
            const approveButton = document.querySelector(`.btn-approve[data-id="${userId}"]`);
            
            // Ask for confirmation before rejecting
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, reject it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    button.disabled = true;
                    approveButton.disabled = true;

                    // Show loading alert
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Rejecting user request',
                        icon: 'info',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch('approve_user.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${userId}&action=reject`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Rejected!',
                                text: 'User has been rejected.',
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: data.message,
                                icon: 'error'
                            });
                            button.disabled = false;
                            approveButton.disabled = false;
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred',
                            icon: 'error'
                        });
                        button.disabled = false;
                        approveButton.disabled = false;
                    });
                }
            });
        }

        // Search functionality for pending users
        pendingSearch.addEventListener('input', () => {
            const searchTerm = pendingSearch.value.toLowerCase();
            const filteredUsers = pendingUsers.filter(user => 
                user.name.toLowerCase().includes(searchTerm) ||
                user.email.toLowerCase().includes(searchTerm) ||
                user.role.toLowerCase().includes(searchTerm)
            );
            renderPendingUsers(filteredUsers);
        });

        // Initialize table
        renderPendingUsers(pendingUsers);

        // Pagination navigation
        let currentPage = <?php echo $page; ?>;
        const totalPages = Math.ceil(<?php echo $pending_total; ?> / <?php echo $items_per_page; ?>);

        function navigatePage(direction) {
            if (direction === 'prev' && currentPage > 1) {
                window.location.href = `?page=${currentPage - 1}`;
            } else if (direction === 'next' && currentPage < totalPages) {
                window.location.href = `?page=${currentPage + 1}`;
            }
        }

        // Add these new functions for the view functionality
        function viewAlumniDetails(lrn) {
            document.getElementById('alumniModal').style.display = 'flex';
            loadUserDetails(lrn);
            window.currentLRN = lrn;
        }
        
        function loadUserDetails(lrn) {
            const xhr = new XMLHttpRequest();
            xhr.open("GET", "admin_dash.php?action=get_user_details&user_id=" + lrn, true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    document.getElementById('alumni-details-content').innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }
        
        function closeModal() {
            document.getElementById('alumniModal').style.display = 'none';
        }
    </script>
</body>
</html>