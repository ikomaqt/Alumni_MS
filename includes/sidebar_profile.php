<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  * {
    font-family: 'Poppins', sans-serif;
  }

  .profile-sidebar__name {
    font-family: 'Montserrat', sans-serif;
    font-weight: 600;
  }

  .profile-sidebar {
    position: sticky;
    top: 88px;
    height: fit-content;
    max-height: calc(100vh - 88px);
    overflow-y: auto;
    align-self: start;
  }

  .profile-sidebar__card {
    background-color: white;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }

  .profile-sidebar__header {
    position: relative;
    height: 8rem;
    background: url('img/cover.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
  }

  .profile-sidebar__avatar {
    position: absolute;
    bottom: -3rem;
    left: 50%;
    transform: translateX(-50%);
    width: 6rem;
    height: 6rem;
    border-radius: 50%;
    border: 4px solid white;
    background-color: white;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .profile-sidebar__avatar svg {
    width: 3.5rem;
    height: 3.5rem;
    color: #9ca3af;
  }

  .profile-sidebar__content {
    padding-top: 3.5rem;
    padding: 3.5rem 1.5rem 1.5rem;
    text-align: center;
  }

  .profile-sidebar__title {
    font-family: 'Poppins', sans-serif;
    font-size: 0.875rem;
    color: #6b7280;
  }

  .profile-sidebar__separator {
    height: 1px;
    background-color: #e5e7eb;
    margin: 1rem 0;
  }

  .profile-sidebar__nav-list {
    list-style: none;
    padding: 0;
  }

  .profile-sidebar__nav-item {
    margin-bottom: 0.25rem;
  }

  .profile-sidebar__nav-link {
    font-family: 'Poppins', sans-serif;
    display: block;
    padding: 0.5rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #4b5563;
    text-decoration: none;
    transition: background-color 0.2s;
  }

  .profile-sidebar__nav-link:hover {
    background-color: #f3f4f6;
  }

  .profile-sidebar__nav-link.active {
    background-color: rgba(79, 70, 229, 0.1);
    color: #4f46e5;
  }

  .profile-sidebar__button {
    font-family: 'Poppins', sans-serif;
    display: inline-block;
    width: 100%;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    text-align: center;
    text-decoration: none;
    background-color: transparent;
    color: #4b5563;
    border: 1px solid #d1d5db;
    transition: all 0.2s;
  }

  .profile-sidebar__button:hover {
    background-color: #f3f4f6;
  }

  @media (max-width: 767px) {
    .profile-sidebar {
      display: none;
    }
  }
</style>

<!-- Sidebar -->
<div class="profile-sidebar sidebar">
  <div class="profile-sidebar__card card">
    <div class="profile-sidebar__header">
      <div class="profile-sidebar__avatar">
        <?php if (!empty($userData['profile_img'])): ?>
          <img src="<?php echo htmlspecialchars($userData['profile_img']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
        <?php else: ?>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
        <?php endif; ?>
      </div>
    </div>
    <div class="profile-sidebar__content">
      <h3 class="profile-sidebar__name"><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></h3>
      <p class="profile-sidebar__title"><?php echo htmlspecialchars($userData['occupation'] ?? 'Alumni Member'); ?></p>
      
      <div class="profile-sidebar__separator"></div>
      
      <nav class="profile-sidebar__nav">
        <ul class="profile-sidebar__nav-list">
          <!--<li class="profile-sidebar__nav-item">
            <a href="home.php" class="profile-sidebar__nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'home.php' ? 'active' : ''; ?>">Posted Jobs</a>
          </li>-->
          <li class="profile-sidebar__nav-item">
            <a href="saved_jobs.php" class="profile-sidebar__nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'saved_jobs.php' ? 'active' : ''; ?>">Saved Jobs</a>
          </li>
          <li class="profile-sidebar__nav-item">
            <a href="saved_events.php" class="profile-sidebar__nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'saved_events.php' ? 'active' : ''; ?>">Saved Events</a>
          </li>
          <li class="profile-sidebar__nav-item">
            <a href="user_posted_jobs.php" class="profile-sidebar__nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'user_posted_jobs.php' ? 'active' : ''; ?>">My Posted Jobs</a>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</div>
