<?php
/**
 * CampusConnect - About & Syllabus Reference Page
 * Educational reference detailing architecture, security models, and syllabus coverage.
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'About & Syllabus Reference';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
  
  <!-- Page Header -->
  <div class="mb-4">
    <h3 class="fw-bold mb-1">About CampusConnect & Syllabus Demonstration</h3>
    <p class="text-muted">A full-stack Web Development academic project demonstrating HTML5, CSS3, Bootstrap 5, Vanilla JS, jQuery, XML/DTD, PHP 8, and MySQL.</p>
  </div>

  <!-- Educational Sandbox for Obsolete HTML Syllabus Requirement (Marquee) -->
  <div class="syllabus-marquee-container shadow-none">
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-primary text-white"><i class="bi bi-info-circle me-1"></i>Syllabus Demo: Legacy HTML Tag</span>
      <!-- Educational inclusion of syllabus <marquee> element in isolated container -->
      <marquee behavior="scroll" direction="left" scrollamount="5" class="fw-semibold text-primary">
        CampusConnect Portal: Welcome Evaluators & Faculty &bull; PHP 8.0 &bull; MySQL PDO &bull; Bootstrap 5 &bull; XML/DTD &bull; AJAX Fetch API &bull; Bcrypt Security
      </marquee>
    </div>
  </div>

  <div class="row g-4">
    
    <!-- Left Column: Project Overview -->
    <div class="col-lg-8">
      
      <!-- Core Architecture Card -->
      <div class="card card-custom p-4 mb-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-diagram-3-fill text-primary me-2"></i>System Architecture</h5>
        <p class="text-muted small">
          CampusConnect is designed using an MVC-inspired modular PHP architecture. It separates presentation layers (reusable PHP partials and Bootstrap components), business logic (authentication guards, validation rules, action handlers), and relational persistence (PDO transactions with MySQL).
        </p>
        
        <div class="row g-3 mt-1">
          <div class="col-md-6">
            <div class="p-3 border rounded bg-body-tertiary">
              <h6 class="fw-bold mb-2"><i class="bi bi-person-badge text-primary me-2"></i>Student Portal</h6>
              <ul class="list-unstyled small text-muted mb-0 d-flex flex-column gap-1">
                <li>&bull; Dashboard with personalized stats</li>
                <li>&bull; Live AJAX search of study materials</li>
                <li>&bull; Assignment submission & grade tracker</li>
                <li>&bull; Campus event registration engine</li>
                <li>&bull; Administrative grievance/feedback form</li>
              </ul>
            </div>
          </div>

          <div class="col-md-6">
            <div class="p-3 border rounded bg-body-tertiary">
              <h6 class="fw-bold mb-2"><i class="bi bi-shield-check text-success me-2"></i>Administrator Console</h6>
              <ul class="list-unstyled small text-muted mb-0 d-flex flex-column gap-1">
                <li>&bull; Metric overview & database analytics</li>
                <li>&bull; Resource management with file validation</li>
                <li>&bull; Assignment creator & submission grading</li>
                <li>&bull; Event management & participant roster</li>
                <li>&bull; Announcement publisher & user roster</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Meaningful jQuery Syllabus Demonstration -->
      <div class="card card-custom p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="fw-bold mb-0">
            <i class="bi bi-code-slash text-warning me-2"></i>Syllabus Feature: jQuery Interactive Demonstration
          </h5>
          <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">jQuery 3.7.1</span>
        </div>
        <p class="text-muted small mb-3">
          To satisfy the Web Development syllabus coverage for jQuery without compromising modern Vanilla JS standards, the interactive panel below demonstrates jQuery selectors, DOM manipulation, <code>.slideToggle()</code>, and dynamic animation events.
        </p>

        <div class="border rounded p-3 bg-body-tertiary">
          <div class="d-flex justify-content-between align-items-center">
            <span class="fw-semibold" id="jquery-status-text">Click the button below to trigger jQuery animation</span>
            <button class="btn btn-sm btn-outline-primary" id="btn-jquery-demo">
              <i class="bi bi-chevron-down me-1"></i><span id="btn-jquery-label">Expand Module Notes</span>
            </button>
          </div>

          <!-- Collapsible content managed via jQuery -->
          <div id="jquery-collapsible-box" style="display: none;" class="mt-3 pt-3 border-top small text-muted">
            <div class="alert alert-info py-2 mb-2">
              <i class="bi bi-check2-circle me-1"></i>jQuery event <code>$('#btn-jquery-demo').on('click')</code> fired successfully.
            </div>
            <p class="mb-1"><strong>Curriculum Learning Outcomes:</strong></p>
            <ul class="mb-0 ps-3">
              <li>DOM Element Selection: <code>$('#jquery-collapsible-box')</code></li>
              <li>Smooth Slide Animation: <code>$element.slideToggle(300)</code></li>
              <li>Text Alteration: <code>$('#jquery-status-text').html(...)</code></li>
              <li>CSS Class Toggling: <code>$btn.toggleClass('btn-outline-primary btn-primary')</code></li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Syllabus Coverage Checklist Card -->
      <div class="card card-custom p-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-card-checklist text-success me-2"></i>Web Development Syllabus Coverage</h5>
        <div class="table-responsive">
          <table class="table table-custom small align-middle">
            <thead>
              <tr>
                <th>Curriculum Module</th>
                <th>Concept Demonstrated</th>
                <th>Implementation Location</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="fw-semibold">HTML5 & Semantics</td>
                <td>Semantic elements (header, nav, main, section, footer), forms, tables, lists</td>
                <td><code>includes/header.php</code>, <code>index.php</code></td>
              </tr>
              <tr>
                <td class="fw-semibold">CSS3 & Styling</td>
                <td>CSS variables, media queries, flexbox, custom scrollbars, Bootstrap 5</td>
                <td><code>assets/css/style.css</code></td>
              </tr>
              <tr>
                <td class="fw-semibold">Theme Switching</td>
                <td>Light / Dark / System mode persistence via localStorage & prefers-color-scheme</td>
                <td><code>assets/js/app.js</code>, <code>includes/header.php</code></td>
              </tr>
              <tr>
                <td class="fw-semibold">JavaScript & DOM</td>
                <td>Event listeners, real-time validation, password visibility, debounce</td>
                <td><code>assets/js/validation.js</code>, <code>assets/js/app.js</code></td>
              </tr>
              <tr>
                <td class="fw-semibold">AJAX & JSON</td>
                <td>Asynchronous resource search with Fetch API and JSON REST endpoints</td>
                <td><code>assets/js/search.js</code>, <code>api/search.php</code></td>
              </tr>
              <tr>
                <td class="fw-semibold">XML & DTD</td>
                <td>Well-formed XML course catalogue with DTD validation and PHP DOM parser</td>
                <td><code>xml/courses.xml</code>, <code>xml/courses.dtd</code>, <code>courses.php</code></td>
              </tr>
              <tr>
                <td class="fw-semibold">PHP 8 Backend</td>
                <td>Session auth, PDO prepared statements, file uploads, role authorization</td>
                <td><code>includes/auth.php</code>, <code>config/database.php</code></td>
              </tr>
              <tr>
                <td class="fw-semibold">MySQL Relational DB</td>
                <td>Foreign keys, unique constraints, timestamps, indexes, normalization</td>
                <td><code>database/campusconnect.sql</code></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- Right Column: Security & Environment Specs -->
    <div class="col-lg-4">
      
      <!-- Security Checklist Card -->
      <div class="card card-custom p-4 mb-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-shield-lock-fill text-danger me-2"></i>Security Safeguards</h6>
        <ul class="list-unstyled small text-muted d-flex flex-column gap-2 mb-0">
          <li class="d-flex align-items-start gap-2">
            <i class="bi bi-check-circle-fill text-success mt-1"></i>
            <div><strong>SQL Injection Defense:</strong> 100% prepared statements using PDO parameters.</div>
          </li>
          <li class="d-flex align-items-start gap-2">
            <i class="bi bi-check-circle-fill text-success mt-1"></i>
            <div><strong>Password Hashing:</strong> PHP <code>password_hash()</code> using Bcrypt algorithm.</div>
          </li>
          <li class="d-flex align-items-start gap-2">
            <i class="bi bi-check-circle-fill text-success mt-1"></i>
            <div><strong>XSS Mitigation:</strong> Strict HTML escaping via <code>e()</code> helper on all user-supplied data.</div>
          </li>
          <li class="d-flex align-items-start gap-2">
            <i class="bi bi-check-circle-fill text-success mt-1"></i>
            <div><strong>CSRF Tokens:</strong> Cryptographically generated session tokens on all state-changing forms.</div>
          </li>
          <li class="d-flex align-items-start gap-2">
            <i class="bi bi-check-circle-fill text-success mt-1"></i>
            <div><strong>Session Fixation Prevention:</strong> <code>session_regenerate_id(true)</code> on login.</div>
          </li>
          <li class="d-flex align-items-start gap-2">
            <i class="bi bi-check-circle-fill text-success mt-1"></i>
            <div><strong>Secure File Delivery:</strong> Download authorization checks, mime verification, and traversal guards.</div>
          </li>
        </ul>
      </div>

      <!-- Environment Specifications Card -->
      <div class="card card-custom p-4 bg-body-tertiary">
        <h6 class="fw-bold mb-3"><i class="bi bi-cpu-fill text-info me-2"></i>Runtime Environment</h6>
        <div class="small text-muted d-flex flex-column gap-2">
          <div class="d-flex justify-content-between">
            <span>PHP Engine:</span>
            <span class="fw-semibold text-body"><?= PHP_VERSION ?></span>
          </div>
          <div class="d-flex justify-content-between">
            <span>PDO Drivers:</span>
            <span class="fw-semibold text-body"><?= implode(', ', PDO::getAvailableDrivers()) ?></span>
          </div>
          <div class="d-flex justify-content-between">
            <span>Server Software:</span>
            <span class="fw-semibold text-body"><?= e($_SERVER['SERVER_SOFTWARE'] ?? 'PHP CLI / Apache') ?></span>
          </div>
          <div class="d-flex justify-content-between">
            <span>Database Driver:</span>
            <span class="fw-semibold text-body">MySQL (PDO)</span>
          </div>
          <div class="d-flex justify-content-between">
            <span>Target Platform:</span>
            <span class="fw-semibold text-body">XAMPP / Production</span>
          </div>
        </div>
      </div>

    </div>

  </div>

</div>

<!-- Inline jQuery Script for the Demonstration Section -->
<script>
  $(document).ready(function() {
    $('#btn-jquery-demo').on('click', function(e) {
      e.preventDefault();
      var $content = $('#jquery-collapsible-box');
      var $label = $('#btn-jquery-label');
      var $status = $('#jquery-status-text');

      $content.slideToggle(250, function() {
        if ($content.is(':visible')) {
          $label.text('Collapse Module Notes');
          $status.html('<span class="text-success"><i class="bi bi-eye-fill me-1"></i>Panel opened via jQuery .slideToggle()</span>');
        } else {
          $label.text('Expand Module Notes');
          $status.html('<span class="text-muted"><i class="bi bi-eye-slash me-1"></i>Panel closed via jQuery .slideToggle()</span>');
        }
      });
    });
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
