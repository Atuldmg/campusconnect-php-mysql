<?php
/**
 * CampusConnect - Global Footer Template
 * Semantic HTML5 footer, script loading, and accessible UI links.
 */
?>
  </main>

  <footer class="footer-custom">
    <div class="container-fluid px-3 px-lg-4">
      <div class="row g-4 align-items-center justify-content-between">
        <div class="col-12 col-md-6">
          <div class="d-flex align-items-center gap-2 mb-1">
            <i class="bi bi-mortarboard-fill text-primary"></i>
            <span class="fw-bold text-body">CampusConnect</span>
            <span class="badge bg-secondary-subtle text-secondary">v1.0 MVP</span>
          </div>
          <p class="mb-0 small text-muted">
            Smart Campus Management System — Advanced Web Technologies Academic Project.
          </p>
        </div>
        <div class="col-12 col-md-6 text-md-end">
          <ul class="list-inline mb-1 small">
            <li class="list-inline-item"><a href="<?= url('index.php') ?>" class="text-decoration-none text-muted">Home</a></li>
            <li class="list-inline-item">&bull;</li>
            <li class="list-inline-item"><a href="<?= url('about.php') ?>" class="text-decoration-none text-muted">About & Syllabus</a></li>
            <li class="list-inline-item">&bull;</li>
            <li class="list-inline-item"><a href="<?= url('courses.php') ?>" class="text-decoration-none text-muted">Course Catalogue (XML)</a></li>
            <li class="list-inline-item">&bull;</li>
            <li class="list-inline-item"><a href="<?= url('login.php') ?>" class="text-decoration-none text-muted">Sign In</a></li>
          </ul>
          <p class="mb-0 small text-muted">
            Crafted with PHP 8, MySQL PDO, Bootstrap 5, Vanilla JS, and XML/DTD.
          </p>
        </div>
      </div>
    </div>
  </footer>

  <!-- Bootstrap 5.3.3 Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <!-- jQuery 3.7.1 (For Web Development Syllabus jQuery demonstration) -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

  <!-- CampusConnect Application Scripts -->
  <script src="<?= url('assets/js/app.js') ?>"></script>
  <script src="<?= url('assets/js/validation.js') ?>"></script>
</body>
</html>
