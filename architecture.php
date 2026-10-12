<?php
/**
 * CampusConnect - System Architecture & Execution Flow
 * Interactive visual architecture specification featuring high-level flowcharts
 * and sequence execution diagrams rendered with Mermaid.js.
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'System Architecture & Execution Flow';
require_once __DIR__ . '/includes/header.php';
?>

<style>
  /* Architecture Dark Canvas Theme (Matches Professional Portfolio Specs) */
  .arch-wrapper {
    background-color: #0b0f19;
    color: #e2e8f0;
    border-radius: 12px;
    border: 1px solid #1e293b;
    padding: 2.25rem;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
  }

  .arch-title {
    font-size: 1.65rem;
    font-weight: 700;
    color: #f8fafc;
    display: flex;
    align-items: center;
    gap: 0.65rem;
    margin-bottom: 0.75rem;
    letter-spacing: -0.02em;
    border-bottom: 1px solid #1e293b;
    padding-bottom: 1rem;
  }

  .arch-lead {
    font-size: 0.95rem;
    line-height: 1.65;
    color: #94a3b8;
    margin-bottom: 2rem;
  }

  .diagram-section-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: #f1f5f9;
    margin-bottom: 1rem;
  }

  .diagram-card {
    background-color: #0f172a;
    border: 1px solid #1e293b;
    border-radius: 10px;
    position: relative;
    padding: 1.5rem;
    margin-bottom: 2.5rem;
    overflow: hidden;
  }

  .diagram-actions {
    position: absolute;
    top: 1rem;
    right: 1rem;
    display: flex;
    gap: 0.4rem;
    z-index: 10;
  }

  .diagram-btn {
    background-color: rgba(30, 41, 59, 0.8);
    border: 1px solid #334155;
    color: #94a3b8;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.15s ease;
  }

  .diagram-btn:hover {
    background-color: #334155;
    color: #ffffff;
    border-color: #475569;
  }

  .diagram-viewport {
    min-height: 420px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow-x: auto;
    padding: 1rem 0;
  }

  .diagram-viewport svg {
    max-width: 100% !important;
    height: auto !important;
  }

  .diagram-controls-floating {
    position: absolute;
    bottom: 1rem;
    right: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    z-index: 10;
  }

  .code-accordion {
    border-top: 1px solid #1e293b;
    margin-top: 1rem;
    padding-top: 0.75rem;
  }

  .code-pre {
    background-color: #020617;
    border: 1px solid #1e293b;
    border-radius: 6px;
    color: #38bdf8;
    font-size: 0.8rem;
    padding: 1rem;
    max-height: 240px;
    overflow-y: auto;
  }
</style>

<div class="container-fluid px-3 px-lg-4 py-4">
  
  <!-- Breadcrumb Navigation -->
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small">
      <li class="breadcrumb-item"><a href="<?= url('index.php') ?>" class="text-decoration-none">Home</a></li>
      <li class="breadcrumb-item"><a href="<?= url('about.php') ?>" class="text-decoration-none">About</a></li>
      <li class="breadcrumb-item active" aria-current="page">Architecture Specification</li>
    </ol>
  </nav>

  <!-- Main Architecture Container -->
  <div class="arch-wrapper">
    
    <div class="arch-title">
      <span>🏗️ Architecture</span>
    </div>

    <p class="arch-lead">
      The <strong>CampusConnect</strong> portfolio architecture is engineered for dual-mode execution: instantaneous static asset delivery via optimized Apache web servers, and modular server-side processing powered by PHP 8.x PDO, XML DOM/DTD parsing, and MySQL relational backends.
    </p>

    <!-- SECTION 1: High-Level Flow -->
    <div class="mb-4">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h4 class="diagram-section-title">High-Level Flow</h4>
      </div>

      <div class="diagram-card" id="card-high-level">
        <!-- Top Right Actions -->
        <div class="diagram-actions">
          <button class="diagram-btn" onclick="copyMermaid('mermaid-flowchart-code')" title="Copy Mermaid Definition">
            <i class="bi bi-clipboard"></i>
          </button>
          <button class="diagram-btn" onclick="toggleFullscreen('card-high-level')" title="Toggle Fullscreen">
            <i class="bi bi-arrows-fullscreen"></i>
          </button>
        </div>

        <!-- Mermaid Render Viewport -->
        <div class="diagram-viewport" id="viewport-flowchart">
          <pre class="mermaid" id="mermaid-flowchart-svg">
flowchart TD
    Browser(["Evaluator / Client Browser"]):::client
    Gateway["Frontend Hub & Apache Web Server"]:::gateway
    PHP["PHP 8.x Runtime Engine\n(Session, Auth & Validation)"]:::engine
    Status{"Query Execution\nStatus?"}:::decision
    Success["Render Interactive\nScorecard & Data Grid"]:::success
    Error["Trigger Inline Validation\n& Error Alert"]:::error
    
    XML[("XML Data Store:\n courses.xml & courses.dtd")]:::xmlStore
    MySQL[("MySQL Database:\n campusconnect (InnoDB)")]:::sqlStore

    Browser -->|"HTTP Navigation"| Gateway
    Gateway -->|"POST / GET Request"| PHP
    
    PHP -->|"XPath Query & DOM"| XML
    XML -.->|"Parsed Subject Trees"| PHP
    
    PHP -->|"Prepared SQL Query"| MySQL
    MySQL -.->|"Resultset & RowCount"| PHP
    
    PHP --> Status
    Status -->|"Success"| Success
    Status -->|"Error"| Error
    
    Success -->|"Response Payload"| Gateway
    Error -->|"Response Payload"| Gateway
    Gateway -->|"Render to Viewport"| Browser

    classDef client fill:#2563eb,stroke:#60a5fa,stroke-width:2px,color:#ffffff;
    classDef gateway fill:#1e293b,stroke:#475569,stroke-width:1px,color:#f8fafc;
    classDef engine fill:#1e293b,stroke:#3b82f6,stroke-width:2px,color:#f8fafc;
    classDef decision fill:#1e293b,stroke:#94a3b8,stroke-width:2px,color:#f8fafc;
    classDef success fill:#059669,stroke:#34d399,stroke-width:2px,color:#ffffff;
    classDef error fill:#dc2626,stroke:#f87171,stroke-width:2px,color:#ffffff;
    classDef xmlStore fill:#1e293b,stroke:#eab308,stroke-width:1px,color:#f8fafc;
    classDef sqlStore fill:#1e293b,stroke:#06b6d4,stroke-width:1px,color:#f8fafc;
          </pre>
        </div>

        <!-- Floating Zoom Controls -->
        <div class="diagram-controls-floating">
          <button class="diagram-btn" onclick="zoomDiagram('viewport-flowchart', 1.15)" title="Zoom In"><i class="bi bi-zoom-in"></i></button>
          <button class="diagram-btn" onclick="zoomDiagram('viewport-flowchart', 0.85)" title="Zoom Out"><i class="bi bi-zoom-out"></i></button>
          <button class="diagram-btn" onclick="resetZoom('viewport-flowchart')" title="Reset Zoom"><i class="bi bi-arrow-counterclockwise"></i></button>
        </div>

        <!-- Raw Mermaid Definition Accordion -->
        <div class="code-accordion">
          <details>
            <summary class="small text-muted" style="cursor: pointer;">
              <i class="bi bi-code-slash me-1"></i>View Raw Mermaid Definition Code
            </summary>
            <pre class="code-pre mt-2" id="mermaid-flowchart-code">flowchart TD
    Browser(["Evaluator / Client Browser"]):::client
    Gateway["Frontend Hub & Apache Web Server"]:::gateway
    PHP["PHP 8.x Runtime Engine\n(Session, Auth & Validation)"]:::engine
    Status{"Query Execution\nStatus?"}:::decision
    Success["Render Interactive\nScorecard & Data Grid"]:::success
    Error["Trigger Inline Validation\n& Error Alert"]:::error
    
    XML[("XML Data Store:\n courses.xml & courses.dtd")]:::xmlStore
    MySQL[("MySQL Database:\n campusconnect (InnoDB)")]:::sqlStore

    Browser -->|"HTTP Navigation"| Gateway
    Gateway -->|"POST / GET Request"| PHP
    
    PHP -->|"XPath Query & DOM"| XML
    XML -.->|"Parsed Subject Trees"| PHP
    
    PHP -->|"Prepared SQL Query"| MySQL
    MySQL -.->|"Resultset & RowCount"| PHP
    
    PHP --> Status
    Status -->|"Success"| Success
    Status -->|"Error"| Error
    
    Success -->|"Response Payload"| Gateway
    Error -->|"Response Payload"| Gateway
    Gateway -->|"Render to Viewport"| Browser</pre>
          </details>
        </div>
      </div>
    </div>

    <!-- SECTION 2: Component & Execution Flow -->
    <div>
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h4 class="diagram-section-title">Component & Execution Flow</h4>
      </div>

      <div class="diagram-card" id="card-sequence">
        <!-- Top Right Actions -->
        <div class="diagram-actions">
          <button class="diagram-btn" onclick="copyMermaid('mermaid-sequence-code')" title="Copy Mermaid Definition">
            <i class="bi bi-clipboard"></i>
          </button>
          <button class="diagram-btn" onclick="toggleFullscreen('card-sequence')" title="Toggle Fullscreen">
            <i class="bi bi-arrows-fullscreen"></i>
          </button>
        </div>

        <!-- Mermaid Render Viewport -->
        <div class="diagram-viewport" id="viewport-sequence">
          <pre class="mermaid" id="mermaid-sequence-svg">
sequenceDiagram
    autonumber
    actor Frontend as Frontend
    participant PHP as PHP Backend
    participant XML as XML Data
    participant DB as MySQL Database

    Note over Frontend, DB: 1. Student Course Lookup & Scorecard
    Frontend->>PHP: POST student_lookup.php { enrollment_no: "2024BCAR001" }
    PHP->>XML: simplexml_load_file("students.xml") -> xpath query
    XML-->>PHP: Student Record: Aarav Sharma [88, 92, 79, 95, 84]
    PHP->>PHP: computeTotal(438/500) & computePercentage(87.6%)
    PHP-->>Frontend: { status: 200, scorecard_html, grade: "Distinction" }
    Frontend->>Frontend: UI Transition to Scorecard State

    Note over Frontend, DB: 2. Relational Transactional Mutation
    Frontend->>PHP: POST mysql_crud_demo.php { action: "UPDATE", id: 101 }
    PHP->>DB: PDO prepare & execute("UPDATE employees SET salary = ?")
    DB-->>PHP: { affected_rows: 1, status: "OK" }
    PHP-->>Frontend: { status: 200, records: [...], notice: "Record Updated" }
    Frontend->>Frontend: UI Transition to Updated Data Grid
          </pre>
        </div>

        <!-- Floating Zoom Controls -->
        <div class="diagram-controls-floating">
          <button class="diagram-btn" onclick="zoomDiagram('viewport-sequence', 1.15)" title="Zoom In"><i class="bi bi-zoom-in"></i></button>
          <button class="diagram-btn" onclick="zoomDiagram('viewport-sequence', 0.85)" title="Zoom Out"><i class="bi bi-zoom-out"></i></button>
          <button class="diagram-btn" onclick="resetZoom('viewport-sequence')" title="Reset Zoom"><i class="bi bi-arrow-counterclockwise"></i></button>
        </div>

        <!-- Raw Mermaid Definition Accordion -->
        <div class="code-accordion">
          <details>
            <summary class="small text-muted" style="cursor: pointer;">
              <i class="bi bi-code-slash me-1"></i>View Raw Mermaid Definition Code
            </summary>
            <pre class="code-pre mt-2" id="mermaid-sequence-code">sequenceDiagram
    autonumber
    actor Frontend as Frontend
    participant PHP as PHP Backend
    participant XML as XML Data
    participant DB as MySQL Database

    Note over Frontend, DB: 1. Student Course Lookup & Scorecard
    Frontend->>PHP: POST student_lookup.php { enrollment_no: "2024BCAR001" }
    PHP->>XML: simplexml_load_file("students.xml") -> xpath query
    XML-->>PHP: Student Record: Aarav Sharma [88, 92, 79, 95, 84]
    PHP->>PHP: computeTotal(438/500) & computePercentage(87.6%)
    PHP-->>Frontend: { status: 200, scorecard_html, grade: "Distinction" }
    Frontend->>Frontend: UI Transition to Scorecard State

    Note over Frontend, DB: 2. Relational Transactional Mutation
    Frontend->>PHP: POST mysql_crud_demo.php { action: "UPDATE", id: 101 }
    PHP->>DB: PDO prepare & execute("UPDATE employees SET salary = ?")
    DB-->>PHP: { affected_rows: 1, status: "OK" }
    PHP-->>Frontend: { status: 200, records: [...], notice: "Record Updated" }
    Frontend->>Frontend: UI Transition to Updated Data Grid</pre>
          </details>
        </div>
      </div>
    </div>

  </div>

</div>

<!-- Mermaid.js 10 Bundle -->
<script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
<script>
  mermaid.initialize({
    startOnLoad: true,
    theme: 'dark',
    themeVariables: {
      darkMode: true,
      background: '#0f172a',
      primaryColor: '#1e293b',
      primaryTextColor: '#f8fafc',
      primaryBorderColor: '#3b82f6',
      lineColor: '#64748b',
      secondaryColor: '#059669',
      tertiaryColor: '#dc2626'
    },
    flowchart: {
      useMaxWidth: false,
      htmlLabels: true,
      curve: 'basis'
    },
    sequence: {
      useMaxWidth: false,
      actorFontSize: 13,
      messageFontSize: 12
    }
  });

  // Interactive Zoom Helper
  const zoomLevels = {};
  function zoomDiagram(viewportId, factor) {
    if (!zoomLevels[viewportId]) zoomLevels[viewportId] = 1;
    zoomLevels[viewportId] *= factor;
    zoomLevels[viewportId] = Math.max(0.5, Math.min(2.5, zoomLevels[viewportId]));
    const el = document.getElementById(viewportId);
    if (el) {
      const svg = el.querySelector('svg');
      if (svg) {
        svg.style.transform = `scale(${zoomLevels[viewportId]})`;
        svg.style.transformOrigin = 'center top';
        svg.style.transition = 'transform 0.2s ease';
      }
    }
  }

  function resetZoom(viewportId) {
    zoomLevels[viewportId] = 1;
    const el = document.getElementById(viewportId);
    if (el) {
      const svg = el.querySelector('svg');
      if (svg) {
        svg.style.transform = 'scale(1)';
      }
    }
  }

  // Copy Mermaid Code
  function copyMermaid(codeElementId) {
    const el = document.getElementById(codeElementId);
    if (!el) return;
    navigator.clipboard.writeText(el.textContent.trim()).then(() => {
      window.showToast ? showToast('Mermaid code copied to clipboard!', 'success') : alert('Copied to clipboard!');
    });
  }

  // Fullscreen Toggle
  function toggleFullscreen(cardId) {
    const card = document.getElementById(cardId);
    if (!card) return;
    if (!document.fullscreenElement) {
      card.requestFullscreen().catch(err => {
        console.error('Fullscreen request error:', err);
      });
    } else {
      document.exitFullscreen();
    }
  }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
