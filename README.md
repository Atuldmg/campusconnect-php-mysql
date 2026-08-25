# CampusConnect — Smart Campus Management System

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://www.php.net/)
[![MySQL Database](https://img.shields.io/badge/MySQL-5.7%2B%20%7C%208.0%2B-orange.svg)](https://www.mysql.com/)
[![Bootstrap Framework](https://img.shields.io/badge/Bootstrap-5.3.3-purple.svg)](https://getbootstrap.com/)
[![Architecture](https://img.shields.io/badge/Architecture-Modular%20PHP%20%2F%20PDO-green.svg)](#architecture)
[![License](https://img.shields.io/badge/License-Academic%20Evaluation-lightgrey.svg)](#)

A centralized full-stack academic and campus management web application engineered for college students and academic administrators. Built using **PHP 8 (PDO)**, **MySQL / MariaDB**, **Vanilla JavaScript**, **Bootstrap 5**, **AJAX (Fetch API)**, **JSON APIs**, and **XML / DTD**.

Developed as an **Advanced Web Technologies (AWT) Final Capstone Project** with complete academic syllabus coverage.

---

## Table of Contents

1. [Project Overview & Problem Statement](#project-overview--problem-statement)
2. [Key Features](#key-features)
   - [Public Portal](#public-portal)
   - [Student Portal](#student-portal)
   - [Administrator Console](#administrator-console)
3. [Technology Stack](#technology-stack)
4. [System Architecture & Folder Structure](#system-architecture--folder-structure)
5. [Database Architecture & Schema Summary](#database-architecture--schema-summary)
6. [XAMPP Local Installation & Setup Guide](#xampp-local-installation--setup-guide)
7. [Default Demonstration Accounts](#default-demonstration-accounts)
8. [CLI Administrator Provisioning Tool](#cli-administrator-provisioning-tool)
9. [Web Development Syllabus Coverage Checklist](#web-development-syllabus-coverage-checklist)
10. [Application Security Safeguards](#application-security-safeguards)
11. [Testing & Quality Assurance Results](#testing--quality-assurance-results)
12. [Known Limitations & Future Roadmap](#known-limitations--future-roadmap)

---

## Project Overview & Problem Statement

### The Problem
In modern university and collegiate environments, critical student resources are frequently fragmented across disparate platforms: lecture slides reside on disparate cloud drives, examination circulars sit on physical notice boards, assignment submissions rely on email attachments, and symposium registrations use external forms. This fragmentation leads to missed deadlines, administrative overhead, and communication lapses.

### The Solution: CampusConnect
CampusConnect unifies campus operations into a secure, intuitive, and responsive single-application environment:
- **For Students:** Instant access to course notes, assignment deadlines, submission portals, symposium registration, grievance feedback, and curriculum catalogues.
- **For Administrators & Faculty:** Central control panel for managing circulars, evaluating student submissions, tracking event capacity, auditing user records, and moderating institutional queries.

---

## Key Features

### Public Portal
- **Natural, Human-Crafted UI:** Minimalist Bootstrap 5 styling with muted blue accents, clean typography, and zero overdesigned gimmicks.
- **Dynamic Notice Board:** Real-time announcements retrieved directly from MySQL.
- **Upcoming Symposiums Calendar:** Featured academic and technical events.
- **Curriculum Catalogue (XML/DTD):** Interactive browser parsing an XML course syllabus validated against a Document Type Definition (`courses.dtd`).
- **Tri-State Theme Switcher:** Smooth toggling between **Light Mode**, **Dark Mode** (charcoal/slate theme), and **System Auto Mode** (following OS `prefers-color-scheme`) with `localStorage` persistence and zero theme flashing (FOUC).

### Student Portal
- **Personalized Student Dashboard:** Academic metrics, enrollment summary, upcoming deadlines, and recent announcements.
- **Study Resource Library:** Asynchronous live search (AJAX Fetch API) with subject filtering and authenticated document downloads.
- **Assignment Submissions:** Detailed problem briefs, due date countdowns, status badges (Pending, Submitted, Graded), file upload support (PDF, DOCX, ZIP), and teacher score/feedback reviews.
- **Campus Event Booking:** One-click symposium registration with duplicate prevention constraints and real-time seat availability.
- **Student Grievance / Feedback:** Submission form with live character counter, category tags, and tracking of administrative responses.
- **Profile Management:** Update personal contact details and change password with current password verification.

### Administrator Console
- **Institutional Analytics:** Real-time metrics on enrolled students, uploaded materials, pending inquiries, and active assignments.
- **User Roster:** Search and filter users by role (Student, Admin), provision new accounts, and delete inactive records.
- **Study Resource Management (CRUD):** Upload syllabus notes with MIME/extension allowlists, edit metadata, and securely delete files from disk.
- **Assignment Creator & Submissions Grading:** Publish assignments with deadlines, view all submitted student solutions, download files, and assign marks and remarks.
- **Event Scheduling & Attendee Rosters:** Schedule symposiums with venue and seat capacity limits, view printable participant lists.
- **Notice Board Publisher (CRUD):** Publish, update, and pin urgent notices to the top of boards.
- **Feedback & Grievance Resolution:** Review student queries, update status (New, Reviewed, Resolved), and send official responses.

---

## Technology Stack

| Layer | Technologies Used | Rationale |
| :--- | :--- | :--- |
| **Frontend Framework** | Bootstrap 5.3.3, Bootstrap Icons 1.11.3 | Clean, industry-standard responsive component library. |
| **Styling & Theme** | CSS3 Custom Properties, Native Dark Mode | Zero-dependency styling with FOUC prevention and reduced motion support. |
| **Client Scripting** | Vanilla JavaScript (ES6+), jQuery 3.7.1 | Real-time validation, AJAX live search, theme storage, and syllabus animation demo. |
| **Backend Engine** | PHP 8.0+ | Server-side validation, secure sessions, role-based access control, file streaming. |
| **Data Layer** | PHP Data Objects (PDO) | 100% prepared SQL statements to eradicate SQL Injection vulnerabilities. |
| **Relational Database**| MySQL / MariaDB (InnoDB) | Foreign keys (`ON DELETE CASCADE`), unique constraints, composite indexes. |
| **Data Interchange** | XML, DTD, JSON | Dynamic syllabus validation with `DOMDocument`, RESTful search endpoints. |
| **Dev Environment** | XAMPP (Apache, MySQL, phpMyAdmin) | Standard local collegiate deployment stack. |

---

## System Architecture & Execution Flow

The CampusConnect portfolio architecture is engineered for dual-mode execution: instantaneous static asset delivery via optimized Apache web servers, and modular server-side processing powered by PHP 8.x PDO, XML DOM/DTD parsing, and MySQL relational backends.

### High-Level Flow

```mermaid
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
    
