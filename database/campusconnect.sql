-- ============================================================
-- CampusConnect - Smart Campus Management System
-- Database Schema and Demonstration Seed Data
-- Compatible with MySQL 5.7+, MySQL 8.0+, MariaDB 10.3+
-- ============================================================

CREATE DATABASE IF NOT EXISTS `campusconnect` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `campusconnect`;

-- Disable foreign key checks for clean recreation
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `feedback`;
DROP TABLE IF EXISTS `event_registrations`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `assignment_submissions`;
DROP TABLE IF EXISTS `assignments`;
DROP TABLE IF EXISTS `resources`;
DROP TABLE IF EXISTS `announcements`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- Table: users
-- ------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'admin') NOT NULL DEFAULT 'student',
  `department` VARCHAR(100) NULL,
  `roll_number` VARCHAR(50) NULL,
  `phone` VARCHAR(20) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: announcements
-- ------------------------------------------------------------
CREATE TABLE `announcements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `content` TEXT NOT NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT 'General',
  `is_pinned` TINYINT(1) NOT NULL DEFAULT 0,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_announcements_created` (`created_at`),
  INDEX `idx_announcements_pinned` (`is_pinned`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: resources
-- ------------------------------------------------------------
CREATE TABLE `resources` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `subject` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_size` INT NOT NULL DEFAULT 0,
  `file_type` VARCHAR(100) NOT NULL DEFAULT 'application/pdf',
  `uploaded_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_resources_subject` (`subject`),
  INDEX `idx_resources_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: assignments
-- ------------------------------------------------------------
CREATE TABLE `assignments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NULL,
  `subject` VARCHAR(100) NOT NULL,
  `due_date` DATETIME NOT NULL,
  `max_marks` INT NOT NULL DEFAULT 100,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_assignments_due` (`due_date`),
  INDEX `idx_assignments_subject` (`subject`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: assignment_submissions
-- ------------------------------------------------------------
CREATE TABLE `assignment_submissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `assignment_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `file_path` VARCHAR(255) NULL,
  `file_name` VARCHAR(255) NULL,
  `submission_notes` TEXT NULL,
  `status` ENUM('pending', 'submitted', 'graded') NOT NULL DEFAULT 'submitted',
  `grade` VARCHAR(10) NULL,
  `feedback` TEXT NULL,
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`assignment_id`) REFERENCES `assignments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_student_assignment` (`assignment_id`, `student_id`),
  INDEX `idx_submissions_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: events
-- ------------------------------------------------------------
CREATE TABLE `events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NULL,
  `event_date` DATETIME NOT NULL,
  `venue` VARCHAR(150) NOT NULL,
  `capacity` INT NOT NULL DEFAULT 100,
  `category` VARCHAR(50) NOT NULL DEFAULT 'Academic',
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_events_date` (`event_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: event_registrations
-- ------------------------------------------------------------
CREATE TABLE `event_registrations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `registered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_student_event` (`event_id`, `student_id`),
  INDEX `idx_reg_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: feedback
-- ------------------------------------------------------------
CREATE TABLE `feedback` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `subject` VARCHAR(150) NOT NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT 'General',
  `message` TEXT NOT NULL,
  `status` ENUM('new', 'reviewed', 'resolved') NOT NULL DEFAULT 'new',
  `admin_response` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_feedback_status` (`status`),
  INDEX `idx_feedback_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA
-- Default Passwords:
-- Admin: AdminPassword123!
-- Students: StudentPass123!
-- ============================================================

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `department`, `roll_number`, `phone`) VALUES
(1, 'System Administrator', 'admin@campusconnect.edu', '$2y$10$Ie6KwmvM6F6vv1OyBViN4e23e5mIkKVXcBGXPcQOTl.G0GOyXJ5i.', 'admin', 'Campus IT & Administration', 'ADM-001', '+91 9876543210'),
(2, 'Aarav Sharma', 'aarav.sharma@campusconnect.edu', '$2y$10$sDfB4Mbj5QfDfebyA4PRiuWvqgwb9HaVVqxvupv1AvqQtkupKMRAi', 'student', 'Computer Science (BCA)', 'CC-2023-BCA-001', '+91 9811223344'),
(3, 'Priya Patel', 'priya.patel@campusconnect.edu', '$2y$10$sDfB4Mbj5QfDfebyA4PRiuWvqgwb9HaVVqxvupv1AvqQtkupKMRAi', 'student', 'Computer Science (BCA)', 'CC-2023-BCA-002', '+91 9822334455'),
(4, 'Rohan Verma', 'rohan.verma@campusconnect.edu', '$2y$10$sDfB4Mbj5QfDfebyA4PRiuWvqgwb9HaVVqxvupv1AvqQtkupKMRAi', 'student', 'Information Technology (B.Sc IT)', 'CC-2024-IT-015', '+91 9833445566'),
(5, 'Sneha Kulkarni', 'sneha.kulkarni@campusconnect.edu', '$2y$10$sDfB4Mbj5QfDfebyA4PRiuWvqgwb9HaVVqxvupv1AvqQtkupKMRAi', 'student', 'Computer Applications (MCA)', 'CC-2024-MCA-008', '+91 9844556677');

-- Announcements
INSERT INTO `announcements` (`id`, `title`, `content`, `category`, `is_pinned`, `created_by`, `created_at`) VALUES
(1, 'Odd Semester Examination Schedule Released', 'The tentative datesheet for the Semester 5 BCA and B.Sc IT theory and practical examinations has been published on the notice board and department desk. Students are advised to clear all pending library dues and verify their exam roll cards before October 25th.', 'Academic', 1, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 'Annual Tech Symposium — HackCampus 2026 Registration Open', 'Registrations are officially open for HackCampus 2026, an inter-college 24-hour hackathon focusing on Web Technologies, AI Applications, and IoT Solutions. Exciting cash prizes and internship opportunities await top performers. Register via the Campus Events tab.', 'Events', 1, 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(3, 'Central Library Extended Hours During Midterm Week', 'To facilitate research and group study, the Central Library reading halls will remain open until 9:00 PM on weekdays throughout the upcoming examination period. Digital catalog stations and high-speed Wi-Fi will be fully operational.', 'General', 0, 1, DATE_SUB(NOW(), INTERVAL 6 DAY)),
(4, 'Computer Labs High-Speed Fiber Network Upgrade', 'Network infrastructure upgrade has been successfully completed in Labs 1, 2, and 3. Students can now access local research servers and development sandboxes with enhanced bandwidth.', 'Facilities', 0, 1, DATE_SUB(NOW(), INTERVAL 10 DAY));

-- Resources
INSERT INTO `resources` (`id`, `title`, `subject`, `description`, `file_path`, `file_name`, `file_size`, `file_type`, `uploaded_by`, `created_at`) VALUES
(1, 'Advanced Web Technologies (AWT) Lecture Slides & PDO Guide', 'Web Technology', 'Comprehensive lecture notes covering PHP 8, PDO secure transactions, CSRF token mitigation, and Bootstrap 5 responsive layout architectures.', 'uploads/resources/awt_lecture_notes_php_pdo.pdf', 'awt_lecture_notes_php_pdo.pdf', 2450000, 'application/pdf', 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(2, 'Relational Database Engineering: Normalization & Indexing Cheat Sheet', 'Database Management', 'Study reference with 1NF through BCNF normalization examples, B-Tree index optimization tips, and SQL execution plan breakdowns.', 'uploads/resources/rdbms_normalization_guide.pdf', 'rdbms_normalization_guide.pdf', 1820000, 'application/pdf', 1, DATE_SUB(NOW(), INTERVAL 8 DAY)),
(3, 'Data Structures & Algorithms: Balanced Binary Trees in C++', 'Data Structures', 'Code examples and step-by-step visualizations for AVL Trees, Red-Black Trees, and Graph Traversal algorithms.', 'uploads/resources/dsa_trees_algorithms.pdf', 'dsa_trees_algorithms.pdf', 3120000, 'application/pdf', 1, DATE_SUB(NOW(), INTERVAL 12 DAY)),
(4, 'Computer Networks: OSI and TCP/IP Protocol Stack Reference', 'Computer Networks', 'Summary notes illustrating protocol header structures, subnetting calculations, and socket programming principles.', 'uploads/resources/computer_networks_osi_reference.pdf', 'computer_networks_osi_reference.pdf', 1450000, 'application/pdf', 1, DATE_SUB(NOW(), INTERVAL 15 DAY));

-- Assignments
INSERT INTO `assignments` (`id`, `title`, `description`, `subject`, `due_date`, `max_marks`, `created_by`, `created_at`) VALUES
(1, 'PHP & MySQL Full-Stack Portfolio Application', 'Develop a functional, secure web application with student and administrator roles, password hashing, prepared statements, and Bootstrap 5 responsive layout.', 'Web Technology', DATE_ADD(NOW(), INTERVAL 7 DAY), 100, 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 'Data Structures: Binary Search Tree Implementation', 'Implement an interactive BST with insertion, deletion, and in-order, pre-order, and post-order traversal functions in C++ or Java.', 'Data Structures', DATE_ADD(NOW(), INTERVAL 3 DAY), 50, 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(3, 'Advanced SQL Query Design & Schema Normalization', 'Write complex JOIN queries, subqueries, and create an ER diagram normalized to 3NF for a hospital management case study.', 'Database Management', DATE_ADD(NOW(), INTERVAL 12 DAY), 50, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(4, 'Network Packet Capture & Wireshark Analysis Lab', 'Perform HTTP vs HTTPS packet captures, trace TCP three-way handshakes, and submit an annotated PDF report.', 'Computer Networks', DATE_SUB(NOW(), INTERVAL 2 DAY), 30, 1, DATE_SUB(NOW(), INTERVAL 9 DAY));

-- Assignment Submissions
INSERT INTO `assignment_submissions` (`id`, `assignment_id`, `student_id`, `file_path`, `file_name`, `submission_notes`, `status`, `grade`, `feedback`, `submitted_at`) VALUES
(1, 4, 2, 'uploads/submissions/aarav_networks_lab.pdf', 'aarav_networks_lab.pdf', 'Completed packet captures for TCP handshake and DNS queries as requested.', 'graded', '28/30', 'Excellent breakdown of TCP sequence numbers and SYN-ACK exchange.', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 2, 2, 'uploads/submissions/aarav_bst_cpp.zip', 'aarav_bst_cpp.zip', 'Source code and console output screenshots included in archive.', 'submitted', NULL, NULL, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 4, 3, 'uploads/submissions/priya_networks_lab.pdf', 'priya_networks_lab.pdf', 'Submitted Wireshark log analysis with commentary.', 'graded', '27/30', 'Well-structured diagrams. Minor omission on TLS certificate exchange.', DATE_SUB(NOW(), INTERVAL 3 DAY));

-- Events
INSERT INTO `events` (`id`, `title`, `description`, `event_date`, `venue`, `capacity`, `category`, `created_by`, `created_at`) VALUES
(1, 'HackCampus 2026: Annual Inter-College Hackathon', 'A 24-hour collaborative sprint where student developer teams design and build innovative web, cloud, and mobile prototypes addressing real-world civic and educational challenges.', DATE_ADD(NOW(), INTERVAL 14 DAY), 'Main University Auditorium & Lab Complex', 120, 'Technical', 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(2, 'Alumni Career & Industry Readiness Masterclass', 'Senior industry leaders and alumni share actionable roadmaps for software engineering interviews, resume optimization, and full-stack project building.', DATE_ADD(NOW(), INTERVAL 5 DAY), 'Seminar Hall B, Academic Block 2', 80, 'Career', 1, DATE_SUB(NOW(), INTERVAL 7 DAY)),
(3, 'CodeSprint 2026: Fast Algorithmic Problem Solving', 'A high-energy, 3-round speed programming challenge with challenges spanning arrays, graphs, and dynamic programming.', DATE_ADD(NOW(), INTERVAL 9 DAY), 'Advanced Computing Center (Lab 4)', 60, 'Competition', 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 'Guest Lecture: Practical Cybersecurity in Modern Cloud Platforms', 'Distinguished industry speaker presentation focusing on zero-trust architectures, OWASP Top 10 vulnerabilities, and security hardening.', DATE_ADD(NOW(), INTERVAL 20 DAY), 'Virtual Conference Room (Hybrid)', 200, 'Academic', 1, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Event Registrations
INSERT INTO `event_registrations` (`id`, `event_id`, `student_id`, `registered_at`) VALUES
(1, 1, 2, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 2, 2, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 1, 3, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 3, 3, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(5, 2, 4, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Feedback
INSERT INTO `feedback` (`id`, `student_id`, `subject`, `category`, `message`, `status`, `admin_response`, `created_at`) VALUES
(1, 2, 'Air Conditioning in Computer Lab 3', 'Facilities', 'During the afternoon practical lab sessions, the central AC unit in Lab 3 has been shutting down intermittently. Requesting routine servicing.', 'reviewed', 'Technician dispatched today. Servicing scheduled for Saturday.', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 3, 'Request for Additional Cloud Database Study Material', 'Academics', 'Could the department share reference textbooks or laboratory practice sheets on PostgreSQL and Redis indexing strategies?', 'new', NULL, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 4, 'Gymnasium Timings Extension Request', 'Student Life', 'Can the indoor gymnasium timings be extended by 30 minutes in the evening to accommodate students with late afternoon lab schedules?', 'resolved', 'Gymnasium hours have now been extended until 8:00 PM starting this week.', DATE_SUB(NOW(), INTERVAL 7 DAY));
