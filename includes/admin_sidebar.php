<?php
/**
 * CampusConnect - Administrator Portal Sidebar Navigation
 */
$currentScript = basename($_SERVER['PHP_SELF']);
?>
<aside class="portal-sidebar" aria-label="Administrator Navigation">
  <div class="d-flex align-items-center gap-2 mb-3 px-2">
    <div class="stat-icon-wrapper bg-primary-subtle text-primary">
      <i class="bi bi-shield-lock-fill"></i>
    </div>
    <div>
      <h6 class="mb-0 fw-bold">Admin Console</h6>
      <small class="text-muted">Master Control</small>
    </div>
  </div>

  <div class="sidebar-heading">Management</div>
  <nav class="nav flex-column">
    <a class="sidebar-nav-link <?= ($currentScript === 'dashboard.php') ? 'active' : '' ?>" href="<?= url('admin/dashboard.php') ?>">
      <i class="bi bi-speedometer2"></i>
      <span>Overview</span>
    </a>
    <a class="sidebar-nav-link <?= ($currentScript === 'users.php') ? 'active' : '' ?>" href="<?= url('admin/users.php') ?>">
      <i class="bi bi-people-fill"></i>
      <span>Users & Students</span>
    </a>
    <a class="sidebar-nav-link <?= ($currentScript === 'announcements.php') ? 'active' : '' ?>" href="<?= url('admin/announcements.php') ?>">
      <i class="bi bi-megaphone-fill"></i>
      <span>Announcements</span>
    </a>
    <a class="sidebar-nav-link <?= ($currentScript === 'resources.php') ? 'active' : '' ?>" href="<?= url('admin/resources.php') ?>">
      <i class="bi bi-folder-fill"></i>
      <span>Study Resources</span>
    </a>
    <a class="sidebar-nav-link <?= ($currentScript === 'assignments.php') ? 'active' : '' ?>" href="<?= url('admin/assignments.php') ?>">
      <i class="bi bi-journal-text"></i>
      <span>Assignments</span>
    </a>
    <a class="sidebar-nav-link <?= ($currentScript === 'events.php') ? 'active' : '' ?>" href="<?= url('admin/events.php') ?>">
      <i class="bi bi-calendar-check-fill"></i>
      <span>Campus Events</span>
    </a>
    <a class="sidebar-nav-link <?= ($currentScript === 'feedback.php') ? 'active' : '' ?>" href="<?= url('admin/feedback.php') ?>">
      <i class="bi bi-chat-square-quote-fill"></i>
      <span>Student Feedback</span>
    </a>
  </nav>

  <div class="sidebar-heading mt-3">Curriculum & System</div>
  <nav class="nav flex-column">
    <a class="sidebar-nav-link <?= ($currentScript === 'courses.php') ? 'active' : '' ?>" href="<?= url('courses.php') ?>">
      <i class="bi bi-filetype-xml"></i>
      <span>Course XML</span>
    </a>
    <a class="sidebar-nav-link text-danger mt-2" href="<?= url('logout.php') ?>">
      <i class="bi bi-box-arrow-right"></i>
      <span>Sign Out</span>
    </a>
  </nav>
</aside>
