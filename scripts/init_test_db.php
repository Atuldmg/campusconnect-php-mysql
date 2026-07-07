<?php
/**
 * CampusConnect - SQLite Test Database Initializer
 * Generates an offline SQLite test database with schema and seed data
 * matching campusconnect.sql for environments where MySQL service is offline.
 */

$dbPath = dirname(__DIR__) . '/database/campusconnect_dev.sqlite';

if (file_exists($dbPath)) {
    unlink($dbPath);
}

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA foreign_keys = ON;');

$schema = <<<SQL
CREATE TABLE users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  email TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  role TEXT NOT NULL DEFAULT 'student',
  department TEXT NULL,
  roll_number TEXT NULL,
  phone TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE announcements (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  title TEXT NOT NULL,
  content TEXT NOT NULL,
  category TEXT NOT NULL DEFAULT 'General',
  is_pinned INTEGER NOT NULL DEFAULT 0,
  created_by INTEGER NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE resources (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  title TEXT NOT NULL,
  subject TEXT NOT NULL,
  description TEXT NULL,
  file_path TEXT NOT NULL,
  file_name TEXT NOT NULL,
  file_size INTEGER NOT NULL DEFAULT 0,
  file_type TEXT NOT NULL DEFAULT 'application/pdf',
  uploaded_by INTEGER NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE assignments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  title TEXT NOT NULL,
  description TEXT NULL,
  subject TEXT NOT NULL,
  due_date DATETIME NOT NULL,
  max_marks INTEGER NOT NULL DEFAULT 100,
  created_by INTEGER NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE assignment_submissions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  assignment_id INTEGER NOT NULL,
  student_id INTEGER NOT NULL,
  file_path TEXT NULL,
  file_name TEXT NULL,
  submission_notes TEXT NULL,
  status TEXT NOT NULL DEFAULT 'submitted',
  grade TEXT NULL,
  feedback TEXT NULL,
  submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE (assignment_id, student_id)
);

CREATE TABLE events (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  title TEXT NOT NULL,
  description TEXT NULL,
  event_date DATETIME NOT NULL,
  venue TEXT NOT NULL,
  capacity INTEGER NOT NULL DEFAULT 100,
  category TEXT NOT NULL DEFAULT 'Academic',
  created_by INTEGER NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE event_registrations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  event_id INTEGER NOT NULL,
  student_id INTEGER NOT NULL,
  registered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE (event_id, student_id)
);

CREATE TABLE feedback (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  student_id INTEGER NOT NULL,
  subject TEXT NOT NULL,
  category TEXT NOT NULL DEFAULT 'General',
  message TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'new',
  admin_response TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);
SQL;

$pdo->exec($schema);

// Seed Users
$adminHash = password_hash('AdminPassword123!', PASSWORD_BCRYPT);
$studentHash = password_hash('StudentPass123!', PASSWORD_BCRYPT);

$stmtUser = $pdo->prepare("INSERT INTO users (id, name, email, password_hash, role, department, roll_number, phone, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmtUser->execute([1, 'System Administrator', 'admin@campusconnect.edu', $adminHash, 'admin', 'Campus IT & Administration', 'ADM-001', '+91 9876543210', date('Y-m-d H:i:s', strtotime('-30 days'))]);
$stmtUser->execute([2, 'Aarav Sharma', 'aarav.sharma@campusconnect.edu', $studentHash, 'student', 'Computer Science (BCA)', 'CC-2023-BCA-001', '+91 9811223344', date('Y-m-d H:i:s', strtotime('-20 days'))]);
$stmtUser->execute([3, 'Priya Patel', 'priya.patel@campusconnect.edu', $studentHash, 'student', 'Computer Science (BCA)', 'CC-2023-BCA-002', '+91 9822334455', date('Y-m-d H:i:s', strtotime('-18 days'))]);
$stmtUser->execute([4, 'Rohan Verma', 'rohan.verma@campusconnect.edu', $studentHash, 'student', 'Information Technology (B.Sc IT)', 'CC-2024-IT-015', '+91 9833445566', date('Y-m-d H:i:s', strtotime('-10 days'))]);
$stmtUser->execute([5, 'Sneha Kulkarni', 'sneha.kulkarni@campusconnect.edu', $studentHash, 'student', 'Computer Applications (MCA)', 'CC-2024-MCA-008', '+91 9844556677', date('Y-m-d H:i:s', strtotime('-5 days'))]);

// Seed Announcements
$stmtAnn = $pdo->prepare("INSERT INTO announcements (id, title, content, category, is_pinned, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmtAnn->execute([1, 'Odd Semester Examination Schedule Released', 'The tentative datesheet for the Semester 5 BCA and B.Sc IT theory and practical examinations has been published on the notice board and department desk. Students are advised to clear all pending library dues and verify their exam roll cards before October 25th.', 'Academic', 1, 1, date('Y-m-d H:i:s', strtotime('-2 days'))]);
$stmtAnn->execute([2, 'Annual Tech Symposium — HackCampus 2026 Registration Open', 'Registrations are officially open for HackCampus 2026, an inter-college 24-hour hackathon focusing on Web Technologies, AI Applications, and IoT Solutions. Exciting cash prizes and internship opportunities await top performers. Register via the Campus Events tab.', 'Events', 1, 1, date('Y-m-d H:i:s', strtotime('-4 days'))]);
$stmtAnn->execute([3, 'Central Library Extended Hours During Midterm Week', 'To facilitate research and group study, the Central Library reading halls will remain open until 9:00 PM on weekdays throughout the upcoming examination period. Digital catalog stations and high-speed Wi-Fi will be fully operational.', 'General', 0, 1, date('Y-m-d H:i:s', strtotime('-6 days'))]);
$stmtAnn->execute([4, 'Computer Labs High-Speed Fiber Network Upgrade', 'Network infrastructure upgrade has been successfully completed in Labs 1, 2, and 3. Students can now access local research servers and development sandboxes with enhanced bandwidth.', 'Facilities', 0, 1, date('Y-m-d H:i:s', strtotime('-10 days'))]);

// Seed Resources
$stmtRes = $pdo->prepare("INSERT INTO resources (id, title, subject, description, file_path, file_name, file_size, file_type, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmtRes->execute([1, 'Advanced Web Technologies (AWT) Lecture Slides & PDO Guide', 'Web Technology', 'Comprehensive lecture notes covering PHP 8, PDO secure transactions, CSRF token mitigation, and Bootstrap 5 responsive layout architectures.', 'uploads/resources/awt_lecture_notes_php_pdo.pdf', 'awt_lecture_notes_php_pdo.pdf', 2450000, 'application/pdf', 1, date('Y-m-d H:i:s', strtotime('-5 days'))]);
$stmtRes->execute([2, 'Relational Database Engineering: Normalization & Indexing Cheat Sheet', 'Database Management', 'Study reference with 1NF through BCNF normalization examples, B-Tree index optimization tips, and SQL execution plan breakdowns.', 'uploads/resources/rdbms_normalization_guide.pdf', 'rdbms_normalization_guide.pdf', 1820000, 'application/pdf', 1, date('Y-m-d H:i:s', strtotime('-8 days'))]);
$stmtRes->execute([3, 'Data Structures & Algorithms: Balanced Binary Trees in C++', 'Data Structures', 'Code examples and step-by-step visualizations for AVL Trees, Red-Black Trees, and Graph Traversal algorithms.', 'uploads/resources/dsa_trees_algorithms.pdf', 'dsa_trees_algorithms.pdf', 3120000, 'application/pdf', 1, date('Y-m-d H:i:s', strtotime('-12 days'))]);
$stmtRes->execute([4, 'Computer Networks: OSI and TCP/IP Protocol Stack Reference', 'Computer Networks', 'Summary notes illustrating protocol header structures, subnetting calculations, and socket programming principles.', 'uploads/resources/computer_networks_osi_reference.pdf', 'computer_networks_osi_reference.pdf', 1450000, 'application/pdf', 1, date('Y-m-d H:i:s', strtotime('-15 days'))]);

// Seed Assignments
$stmtAsg = $pdo->prepare("INSERT INTO assignments (id, title, description, subject, due_date, max_marks, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmtAsg->execute([1, 'PHP & MySQL Full-Stack Portfolio Application', 'Develop a functional, secure web application with student and administrator roles, password hashing, prepared statements, and Bootstrap 5 responsive layout.', 'Web Technology', date('Y-m-d H:i:s', strtotime('+7 days')), 100, 1, date('Y-m-d H:i:s', strtotime('-3 days'))]);
$stmtAsg->execute([2, 'Data Structures: Binary Search Tree Implementation', 'Implement an interactive BST with insertion, deletion, and in-order, pre-order, and post-order traversal functions in C++ or Java.', 'Data Structures', date('Y-m-d H:i:s', strtotime('+3 days')), 50, 1, date('Y-m-d H:i:s', strtotime('-5 days'))]);
$stmtAsg->execute([3, 'Advanced SQL Query Design & Schema Normalization', 'Write complex JOIN queries, subqueries, and create an ER diagram normalized to 3NF for a hospital management case study.', 'Database Management', date('Y-m-d H:i:s', strtotime('+12 days')), 50, 1, date('Y-m-d H:i:s', strtotime('-2 days'))]);
$stmtAsg->execute([4, 'Network Packet Capture & Wireshark Analysis Lab', 'Perform HTTP vs HTTPS packet captures, trace TCP three-way handshakes, and submit an annotated PDF report.', 'Computer Networks', date('Y-m-d H:i:s', strtotime('-2 days')), 30, 1, date('Y-m-d H:i:s', strtotime('-9 days'))]);

// Seed Submissions
$stmtSub = $pdo->prepare("INSERT INTO assignment_submissions (id, assignment_id, student_id, file_path, file_name, submission_notes, status, grade, feedback, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmtSub->execute([1, 4, 2, 'uploads/submissions/aarav_networks_lab.pdf', 'aarav_networks_lab.pdf', 'Completed packet captures for TCP handshake and DNS queries as requested.', 'graded', '28/30', 'Excellent breakdown of TCP sequence numbers and SYN-ACK exchange.', date('Y-m-d H:i:s', strtotime('-3 days'))]);
$stmtSub->execute([2, 2, 2, 'uploads/submissions/aarav_bst_cpp.zip', 'aarav_bst_cpp.zip', 'Source code and console output screenshots included in archive.', 'submitted', null, null, date('Y-m-d H:i:s', strtotime('-1 days'))]);
$stmtSub->execute([3, 4, 3, 'uploads/submissions/priya_networks_lab.pdf', 'priya_networks_lab.pdf', 'Submitted Wireshark log analysis with commentary.', 'graded', '27/30', 'Well-structured diagrams. Minor omission on TLS certificate exchange.', date('Y-m-d H:i:s', strtotime('-3 days'))]);

// Seed Events
$stmtEvt = $pdo->prepare("INSERT INTO events (id, title, description, event_date, venue, capacity, category, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmtEvt->execute([1, 'HackCampus 2026: Annual Inter-College Hackathon', 'A 24-hour collaborative sprint where student developer teams design and build innovative web, cloud, and mobile prototypes addressing real-world civic and educational challenges.', date('Y-m-d H:i:s', strtotime('+14 days')), 'Main University Auditorium & Lab Complex', 120, 'Technical', 1, date('Y-m-d H:i:s', strtotime('-4 days'))]);
$stmtEvt->execute([2, 'Alumni Career & Industry Readiness Masterclass', 'Senior industry leaders and alumni share actionable roadmaps for software engineering interviews, resume optimization, and full-stack project building.', date('Y-m-d H:i:s', strtotime('+5 days')), 'Seminar Hall B, Academic Block 2', 80, 'Career', 1, date('Y-m-d H:i:s', strtotime('-7 days'))]);
$stmtEvt->execute([3, 'CodeSprint 2026: Fast Algorithmic Problem Solving', 'A high-energy, 3-round speed programming challenge with challenges spanning arrays, graphs, and dynamic programming.', date('Y-m-d H:i:s', strtotime('+9 days')), 'Advanced Computing Center (Lab 4)', 60, 'Competition', 1, date('Y-m-d H:i:s', strtotime('-1 days'))]);
$stmtEvt->execute([4, 'Guest Lecture: Practical Cybersecurity in Modern Cloud Platforms', 'Distinguished industry speaker presentation focusing on zero-trust architectures, OWASP Top 10 vulnerabilities, and security hardening.', date('Y-m-d H:i:s', strtotime('+20 days')), 'Virtual Conference Room (Hybrid)', 200, 'Academic', 1, date('Y-m-d H:i:s', strtotime('-2 days'))]);

// Seed Registrations
$stmtReg = $pdo->prepare("INSERT INTO event_registrations (id, event_id, student_id, registered_at) VALUES (?, ?, ?, ?)");
$stmtReg->execute([1, 1, 2, date('Y-m-d H:i:s', strtotime('-2 days'))]);
$stmtReg->execute([2, 2, 2, date('Y-m-d H:i:s', strtotime('-3 days'))]);
$stmtReg->execute([3, 1, 3, date('Y-m-d H:i:s', strtotime('-1 days'))]);
$stmtReg->execute([4, 3, 3, date('Y-m-d H:i:s', strtotime('-12 hours'))]);
$stmtReg->execute([5, 2, 4, date('Y-m-d H:i:s', strtotime('-2 days'))]);

// Seed Feedback
$stmtFb = $pdo->prepare("INSERT INTO feedback (id, student_id, subject, category, message, status, admin_response, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmtFb->execute([1, 2, 'Air Conditioning in Computer Lab 3', 'Facilities', 'During the afternoon practical lab sessions, the central AC unit in Lab 3 has been shutting down intermittently. Requesting routine servicing.', 'reviewed', 'Technician dispatched today. Servicing scheduled for Saturday.', date('Y-m-d H:i:s', strtotime('-3 days'))]);
$stmtFb->execute([2, 3, 'Request for Additional Cloud Database Study Material', 'Academics', 'Could the department share reference textbooks or laboratory practice sheets on PostgreSQL and Redis indexing strategies?', 'new', null, date('Y-m-d H:i:s', strtotime('-1 days'))]);
$stmtFb->execute([3, 4, 'Gymnasium Timings Extension Request', 'Student Life', 'Can the indoor gymnasium timings be extended by 30 minutes in the evening to accommodate students with late afternoon lab schedules?', 'resolved', 'Gymnasium hours have now been extended until 8:00 PM starting this week.', date('Y-m-d H:i:s', strtotime('-7 days'))]);

echo "SQLite test database initialized successfully at: " . $dbPath . PHP_EOL;
