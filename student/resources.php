<?php
/**
 * CampusConnect - Student Study Resource Library
 * Interactive repository featuring asynchronous live search, subject filters, and secure downloads.
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_role('student');

$pageTitle = 'Study Resources';

$subjects = [];
$initialResources = [];

try {
    $pdo = get_db_connection();

    // Fetch distinct subjects for filter
    $stmtSubjects = $pdo->query("SELECT DISTINCT subject FROM resources WHERE subject IS NOT NULL AND subject != '' ORDER BY subject ASC");
    $subjects = $stmtSubjects->fetchAll(PDO::FETCH_COLUMN);

    // Initial server-side load of resources
    $stmtRes = $pdo->query("SELECT id, title, subject, description, file_name, file_size, file_type, created_at FROM resources ORDER BY created_at DESC");
    $initialResources = $stmtRes->fetchAll();

} catch (Exception $e) {
    error_log('Resource page error: ' . $e->getMessage());
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="portal-container">
  <?php require_once dirname(__DIR__) . '/includes/student_sidebar.php'; ?>

  <main class="portal-content">
    
    <!-- Page Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
      <div>
        <h4 class="fw-bold mb-1">Study Resource Library</h4>
        <p class="text-muted small mb-0">Browse lecture notes, laboratory guides, and reference materials.</p>
      </div>
      <div>
        <span class="badge bg-secondary-subtle text-secondary px-3 py-2" id="searchCountBadge">
          <?= count($initialResources) ?> resources available
        </span>
      </div>
    </div>

    <!-- AJAX Search & Filter Controls -->
    <div class="card card-custom p-3 mb-4 shadow-none border">
      <div class="row g-2 align-items-center">
        <div class="col-12 col-md-8">
          <div class="search-wrapper">
            <i class="bi bi-search"></i>
            <input 
              type="text" 
              class="form-control search-input" 
              id="ajaxSearchInput" 
              placeholder="Search by topic, keyword, or document title..." 
              data-endpoint="<?= url('api/search.php') ?>"
              data-base-url="<?= url() ?>"
              autocomplete="off"
            >
          </div>
        </div>

        <div class="col-12 col-md-4">
          <select class="form-select" id="subjectFilter">
            <option value="">All Subjects & Modules</option>
            <?php foreach ($subjects as $sub): ?>
              <option value="<?= e($sub) ?>"><?= e($sub) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Live Search Status Indicator -->
      <div id="searchLoadingSpinner" class="d-none mt-2 text-muted small">
        <div class="spinner-border spinner-border-sm text-primary me-1" role="status"></div>
        Searching repository...
      </div>
    </div>

    <!-- Results Container (Dynamically updated via Fetch API) -->
    <div id="searchResultsContainer">
      <?php if (empty($initialResources)): ?>
        <div class="card card-custom p-5 text-center text-muted">
          <i class="bi bi-folder2-open display-5 d-block mb-2"></i>
          <h6>No study resources available yet.</h6>
          <small>Check back later or contact your course faculty.</small>
        </div>
      <?php else: ?>
        <?php foreach ($initialResources as $item): ?>
          <div class="card card-custom mb-3 shadow-none border">
            <div class="card-body p-3">
              <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle mb-1">
                    <?= e($item['subject']) ?>
                  </span>
                  <h6 class="card-title mb-1 fw-bold text-body"><?= e($item['title']) ?></h6>
                  <p class="card-text text-muted small mb-2"><?= e($item['description'] ?: 'No additional description provided.') ?></p>
                  <div class="d-flex gap-3 text-muted small flex-wrap">
                    <span><i class="bi bi-file-earmark-pdf me-1 text-danger"></i><?= e($item['file_name']) ?></span>
                    <span><i class="bi bi-hdd me-1"></i><?= format_bytes((int)$item['file_size']) ?></span>
                    <span><i class="bi bi-clock me-1"></i><?= time_ago($item['created_at']) ?></span>
                  </div>
                </div>
                <div class="text-end flex-shrink-0">
                  <a href="<?= url('download.php?id=' . $item['id']) ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-download me-1"></i>Download
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </main>
</div>

<!-- AJAX Search Handler -->
<script src="<?= url('assets/js/search.js') ?>"></script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
