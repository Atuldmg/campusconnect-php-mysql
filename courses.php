<?php

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Academic Course Catalogue (XML/DTD)';

$xmlFilePath = __DIR__ . '/xml/courses.xml';
$dtdValidationStatus = false;
$coursesList = [];
$academicYear = '';
$universityName = '';
$xmlParseError = '';

if (file_exists($xmlFilePath)) {

    $dom = new DOMDocument();

    $dom->resolveExternals = true;
    libxml_use_internal_errors(true);

    if ($dom->load($xmlFilePath)) {
        if ($dom->validate()) {
            $dtdValidationStatus = true;
        }

        $xml = simplexml_import_dom($dom);
        if ($xml) {
            $academicYear = (string)$xml['academic_year'];
            $universityName = (string)$xml->university;

            foreach ($xml->course as $c) {
                $modules = [];
                foreach ($c->modules->module as $m) {
                    $modules[] = [
                        'number' => (string)$m['number'],
                        'hours'  => (string)$m['hours'],
                        'title'  => (string)$m->title,
                        'topics' => (string)$m->topics,
                    ];
                }

                $prereqs = [];
                foreach ($c->prerequisites->prerequisite as $p) {
                    $prereqs[] = (string)$p;
                }

                $coursesList[] = [
                    'id'          => (string)$c['id'],
                    'code'        => (string)$c['code'],
                    'category'    => (string)$c['category'],
                    'title'       => (string)$c->title,
                    'department'  => (string)$c->department,
                    'credits'     => (string)$c->credits,
                    'semester'    => (string)$c->semester,
                    'instructor'  => [
                        'name'   => (string)$c->instructor->name,
                        'email'  => (string)$c->instructor->email,
                        'office' => (string)$c->instructor->office,
                    ],
                    'description' => (string)$c->description,
                    'modules'     => $modules,
                    'prereqs'     => $prereqs,
                ];
            }
        }
    } else {
        $xmlParseError = 'Unable to parse courses.xml: ' . libxml_get_last_error()->message;
    }
    libxml_clear_errors();
} else {
    $xmlParseError = 'courses.xml file not found in xml/ directory.';
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">

  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
      <h3 class="fw-bold mb-1">Academic Curriculum & Course Catalogue</h3>
      <p class="text-muted mb-0">
        <?= e($universityName) ?> &bull; Session <?= e($academicYear) ?>
      </p>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= url('xml/courses.xml') ?>" target="_blank" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-file-earmark-code me-1"></i>View Raw XML
      </a>
      <a href="<?= url('xml/courses.dtd') ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-file-earmark-text me-1"></i>View DTD Schema
      </a>
    </div>
  </div>

  <div class="card card-custom p-3 mb-4 bg-body-tertiary">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div class="d-flex align-items-center gap-2">
        <?php if ($dtdValidationStatus): ?>
          <span class="badge bg-success-subtle text-success border border-success-subtle py-2 px-3">
            <i class="bi bi-check-circle-fill me-1"></i>DTD Schema Validated
          </span>
          <span class="small text-muted">Document parsed via PHP DOMDocument with 0 validation warnings.</span>
        <?php else: ?>
          <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle py-2 px-3">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>DTD Validation Notice
          </span>
          <span class="small text-muted"><?= e($xmlParseError ?: 'XML loaded without explicit DTD check.') ?></span>
        <?php endif; ?>
      </div>
      <div class="small text-muted">
        <strong>Total Courses:</strong> <?= count($coursesList) ?> Active Subjects
      </div>
    </div>
  </div>

  <div class="row g-4">
    <?php foreach ($coursesList as $course): ?>
      <div class="col-12 col-lg-6">
        <div class="card card-custom h-100 shadow-none border">
          <div class="card-header-clean">
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= e($course['code']) ?></span>
              <span class="badge bg-secondary-subtle text-secondary"><?= e($course['category']) ?></span>
            </div>
            <span class="small fw-semibold text-muted"><?= e($course['credits']) ?> Credits</span>
          </div>

          <div class="card-body p-3">
            <h5 class="fw-bold mb-2 text-body"><?= e($course['title']) ?></h5>
            <p class="text-muted small mb-3"><?= e($course['description']) ?></p>

            <div class="row g-2 mb-3 small text-muted">
              <div class="col-6">
                <i class="bi bi-building me-1"></i><strong>Dept:</strong> <?= e($course['department']) ?>
              </div>
              <div class="col-6">
                <i class="bi bi-calendar3 me-1"></i><strong>Level:</strong> <?= e($course['semester']) ?>
              </div>
              <div class="col-12">
                <i class="bi bi-person-fill me-1"></i><strong>Faculty:</strong> <?= e($course['instructor']['name']) ?> (<code><?= e($course['instructor']['email']) ?></code>)
              </div>
            </div>

            <div class="accordion accordion-flush border rounded" id="accordion-<?= e($course['id']) ?>">
              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed py-2 px-3 small fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-modules-<?= e($course['id']) ?>" aria-expanded="false">
                    <i class="bi bi-list-check me-2 text-primary"></i>Syllabus Modules (<?= count($course['modules']) ?>)
                  </button>
                </h2>
                <div id="collapse-modules-<?= e($course['id']) ?>" class="accordion-collapse collapse" data-bs-parent="#accordion-<?= e($course['id']) ?>">
                  <div class="accordion-body p-3 small">
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                      <?php foreach ($course['modules'] as $mod): ?>
                        <li class="border-bottom pb-2">
                          <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold text-body">Module <?= e($mod['number']) ?>: <?= e($mod['title']) ?></span>
                            <span class="badge bg-light text-muted border"><?= e($mod['hours']) ?> hrs</span>
                          </div>
                          <div class="text-muted"><?= e($mod['topics']) ?></div>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                </div>
              </div>
            </div>

            <?php if (!empty($course['prereqs'])): ?>
              <div class="mt-3 pt-2 border-top small text-muted">
                <strong>Prerequisites:</strong> <?= implode(', ', array_map('htmlspecialchars', $course['prereqs'])) ?>
              </div>
            <?php endif; ?>

          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
