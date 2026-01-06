<?php
/**
 * CampusConnect - Global Header Template
 * Semantic HTML5 structure, theme switcher, responsive navigation, and flash message container.
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$currentUser = get_logged_in_user();
$pageTitle = isset($pageTitle) ? $pageTitle . ' — CampusConnect' : 'CampusConnect — Smart Campus Management System';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="CampusConnect is a centralized student and campus management portal for academic resources, assignments, events, announcements, and feedback.">
  <meta name="color-scheme" content="light dark">
  <title><?= e($pageTitle) ?></title>

  <!-- Prevent Theme Flash (FOUC) - Execute immediately before render -->
  <script>
    (function() {
      try {
        var stored = localStorage.getItem('campusconnect_theme') || 'system';
        var resolved = stored;
        if (stored === 'system') {
          resolved = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        document.documentElement.setAttribute('data-bs-theme', resolved);
      } catch (e) {}
    })();
  </script>

  <!-- Bootstrap 5.3.3 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  
  <!-- Bootstrap Icons 1.11.3 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <!-- CampusConnect Custom Stylesheet -->
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>

  <!-- Top Navigation Bar -->
  <header>
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top" aria-label="Main Navigation">
      <div class="container-fluid px-3 px-lg-4">
        <!-- Brand Wordmark -->
        <a class="navbar-brand brand-wordmark" href="<?= url('index.php') ?>">
          <i class="bi bi-mortarboard-fill"></i>
          <span>CampusConnect</span>
          <span class="brand-badge d-none d-sm-inline-block">Portal</span>
        </a>

        <!-- Mobile Toggler -->
        <button class="navbar-toggler border-0" type="button" data-bs-override="collapse" data-bs-toggle="collapse" data-bs-target="#navbarMainContent" aria-controls="navbarMainContent" aria-expanded="false" aria-label="Toggle navigation">
          <i class="bi bi-list fs-3"></i>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="navbarMainContent">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
            <li class="nav-item">
              <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'index.php') ? 'active' : '' ?>" href="<?= url('index.php') ?>">Home</a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'about.php') ? 'active' : '' ?>" href="<?= url('about.php') ?>">About & Syllabus</a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'architecture.php') ? 'active' : '' ?>" href="<?= url('architecture.php') ?>">
                <i class="bi bi-diagram-3 me-1"></i>Architecture
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'courses.php') ? 'active' : '' ?>" href="<?= url('courses.php') ?>">
                <i class="bi bi-filetype-xml me-1"></i>Curriculum (XML)
              </a>
            </li>
            <?php if ($currentUser): ?>
              <?php if ($currentUser['role'] === 'student'): ?>
                <li class="nav-item">
                  <a class="nav-link text-primary fw-semibold" href="<?= url('student/dashboard.php') ?>">
                    <i class="bi bi-speedometer2 me-1"></i>Student Dashboard
                  </a>
                </li>
              <?php elseif ($currentUser['role'] === 'admin'): ?>
                <li class="nav-item">
                  <a class="nav-link text-primary fw-semibold" href="<?= url('admin/dashboard.php') ?>">
                    <i class="bi bi-shield-check me-1"></i>Admin Dashboard
                  </a>
                </li>
              <?php endif; ?>
            <?php endif; ?>
          </ul>

          <div class="d-flex align-items-center gap-2">
            <!-- Theme Selector Dropdown (System / Light / Dark) -->
            <div class="dropdown">
              <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2" id="themeSelectorDropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Select theme mode">
                <i id="theme-active-icon" class="bi bi-circle-half"></i>
                <span class="d-none d-md-inline" style="font-size: 0.85rem;">Theme</span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="themeSelectorDropdown">
                <li>
                  <button class="dropdown-item d-flex align-items-center justify-content-between py-2" type="button" data-theme-value="light">
                    <span class="d-flex align-items-center gap-2"><i class="bi bi-sun-fill text-warning"></i> Light</span>
                    <i class="bi bi-check2 theme-check-icon d-none text-primary"></i>
                  </button>
                </li>
                <li>
                  <button class="dropdown-item d-flex align-items-center justify-content-between py-2" type="button" data-theme-value="dark">
                    <span class="d-flex align-items-center gap-2"><i class="bi bi-moon-stars-fill text-info"></i> Dark</span>
                    <i class="bi bi-check2 theme-check-icon d-none text-primary"></i>
                  </button>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <button class="dropdown-item d-flex align-items-center justify-content-between py-2" type="button" data-theme-value="system">
                    <span class="d-flex align-items-center gap-2"><i class="bi bi-circle-half text-secondary"></i> System Auto</span>
                    <i class="bi bi-check2 theme-check-icon d-none text-primary"></i>
                  </button>
                </li>
              </ul>
            </div>

            <!-- User Auth Links -->
            <?php if ($currentUser): ?>
              <div class="dropdown">
                <button class="btn btn-sm btn-outline-primary dropdown-toggle d-flex align-items-center gap-2" type="button" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="bi bi-person-circle"></i>
                  <span class="fw-semibold"><?= e($currentUser['name']) ?></span>
                  <span class="badge bg-secondary-subtle text-secondary small text-uppercase"><?= e($currentUser['role']) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userMenuDropdown">
                  <li><h6 class="dropdown-header"><?= e($currentUser['email']) ?></h6></li>
                  <?php if ($currentUser['role'] === 'student'): ?>
                    <li><a class="dropdown-item" href="<?= url('student/dashboard.php') ?>"><i class="bi bi-grid me-2"></i>My Dashboard</a></li>
                    <li><a class="dropdown-item" href="<?= url('student/profile.php') ?>"><i class="bi bi-person-gear me-2"></i>Profile Settings</a></li>
                  <?php else: ?>
                    <li><a class="dropdown-item" href="<?= url('admin/dashboard.php') ?>"><i class="bi bi-shield me-2"></i>Admin Dashboard</a></li>
                    <li><a class="dropdown-item" href="<?= url('admin/users.php') ?>"><i class="bi bi-people me-2"></i>Manage Users</a></li>
                  <?php endif; ?>
                  <li><hr class="dropdown-divider"></li>
                  <li>
                    <a class="dropdown-item text-danger" href="<?= url('logout.php') ?>">
                      <i class="bi bi-box-arrow-right me-2"></i>Sign Out
                    </a>
                  </li>
                </ul>
              </div>
            <?php else: ?>
              <div class="d-flex gap-2">
                <a href="<?= url('login.php') ?>" class="btn btn-sm btn-outline-secondary">Sign In</a>
                <a href="<?= url('register.php') ?>" class="btn btn-sm btn-primary">Student Register</a>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </nav>
  </header>

  <!-- Flash Messages Alert Bar -->
  <div class="container-fluid px-3 px-lg-4 pt-3">
    <?php foreach (get_flashes() as $flash): ?>
      <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi <?= ($flash['type'] === 'success') ? 'bi-check-circle-fill' : (($flash['type'] === 'danger') ? 'bi-exclamation-octagon-fill' : 'bi-info-circle-fill') ?> me-2"></i>
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endforeach; ?>
  </div>

  <main>
