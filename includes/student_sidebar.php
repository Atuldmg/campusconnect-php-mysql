<?php

$currentScript = basename($_SERVER['PHP_SELF']);
?>
<aside class="portal-sidebar" aria-label="Student Navigation">
  <div class="d-flex align-items-center gap-2 mb-3 px-2">
    <div class="stat-icon-wrapper bg-primary-subtle text-primary">
      <i class="bi bi-person-workspace"></i>
    </div>
    <div>
      <h6 class="mb-0 fw-bold">Student Portal</h6>
      <small class="text-muted"><?= e($_SESSION['user_roll_number'] ?? 'Student') ?></small>
    </div>
  </div>

  <div class="sidebar-heading">Academic Tools</div>
  <nav class="nav flex-column">
    <a class="sidebar-nav-link <?= ($currentScript === 'dashboard.php') ? 'active' : '' ?>" href="<?= url('student/dashboard.php') ?>">
      <i class="bi bi-grid-fill"></i>
      <span>Dashboard</span>
    </a>
    <a class="sidebar-nav-link <?= ($currentScript === 'resources.php') ? 'active' : '' ?>" href="<?= url('student/resources.php') ?>">
      <i class="bi bi-folder-fill"></i>
      <span>Study Resources</span>
    </a>
    <a class="sidebar-nav-link <?= ($currentScript === 'assignments.php') ? 'active' : '' ?>" href="<?= url('student/assignments.php') ?>">
      <i class="bi bi-journal-check"></i>
      <span>Assignments</span>
    </a>
    <a class="sidebar-nav-link <?= ($currentScript === 'events.php') ? 'active' : '' ?>" href="<?= url('student/events.php') ?>">
      <i class="bi bi-calendar-event"></i>
      <span>Campus Events</span>
    </a>
    <a class="sidebar-nav-link <?= ($currentScript === 'feedback.php') ? 'active' : '' ?>" href="<?= url('student/feedback.php') ?>">
      <i class="bi bi-chat-left-dots"></i>
      <span>Feedback</span>
    </a>
  </nav>

  <div class="sidebar-heading mt-3">Account & Info</div>
  <nav class="nav flex-column">
    <a class="sidebar-nav-link <?= ($currentScript === 'courses.php') ? 'active' : '' ?>" href="<?= url('courses.php') ?>">
      <i class="bi bi-filetype-xml"></i>
      <span>Course XML</span>
    </a>
    <a class="sidebar-nav-link <?= ($currentScript === 'profile.php') ? 'active' : '' ?>" href="<?= url('student/profile.php') ?>">
      <i class="bi bi-person-gear"></i>
      <span>My Profile</span>
    </a>
    <a class="sidebar-nav-link text-danger mt-2" href="<?= url('logout.php') ?>">
      <i class="bi bi-box-arrow-right"></i>
      <span>Sign Out</span>
    </a>
  </nav>
</aside>
