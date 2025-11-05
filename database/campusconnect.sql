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
