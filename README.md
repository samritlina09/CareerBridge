# CareerBridge

> **A Comprehensive Campus Internship & Placement Management Platform**

CareerBridge is a full-stack campus recruitment and placement management web platform that connects **Students**, **Corporate Recruiters**, and **Training & Placement (T&P) Administrators** in a single, unified system. It streamlines the entire campus hiring lifecycle—from vacancy publication and candidate screening to interview scheduling and final placement confirmation.

---

## Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
  - [Student Portal](#student-portal)
  - [Recruiter Portal](#recruiter-portal)
  - [Admin Portal](#admin-portal)
- [How CareerBridge Works](#how-careerbridge-works)
- [Technology Stack](#technology-stack)
- [Project Structure](#project-structure)
- [Installation & Setup](#installation--setup)
  - [Default Administrator Credentials](#default-administrator-credentials)
- [Environment Variables & Configuration](#environment-variables--configuration)
- [Database Architecture](#database-architecture)
- [User Roles](#user-roles)
- [Screenshots](#screenshots)
- [Security](#security)
- [Future Enhancements](#future-enhancements)
- [Contributing](#contributing)
- [License](#license)

---

## Overview

Traditional campus recruitment often relies on fragmented spreadsheets, physical noticeboards, and disconnected communication channels. This leads to missed deadlines, lack of visibility into application statuses, manual verification overhead, and difficulty compiling institutional reports.

**CareerBridge** addresses these challenges by providing:
- **A Single Digital Hub**: Unifies job discovery, resume submissions, interview rounds, and offer tracking.
- **Academic Eligibility Verification**: Automatically validates candidate qualifications (minimum CGPA, eligible academic branches) against job prerequisites before an application can be submitted.
- **Transparent Progress Tracking**: Students monitor the real-time status of their applications as they progress from applied to shortlisted, interview scheduled, and selected.
- **Centralized Recruitment Management**: Recruiters manage job postings, review applicant dossiers, schedule multi-round interviews, and extend offers.
- **Institutional Analytics**: Administrators access real-time statistics on student participation, company engagement, departmental placement percentages, and compensation packages.

---

## Key Features

### Student Portal
- **Account Registration & Authentication**: Sign up with university roll number, academic branch, and CGPA. Login using email or roll number.
- **Academic Profile Management**: Update personal info, 10th/12th percentages, graduation year, and degree branch.
- **Portfolio & Skill Management**: Add and categorize technical skills with proficiency levels; record academic and personal projects with live links; document professional certifications.
- **Resume Upload & Viewer**: Upload and store PDF resumes for recruiter inspection.
- **Opportunities Catalog**: Browse active job and internship postings from verified employers with dynamic filters (search keywords, employment type, work mode, and eligibility). Displays a clean *"No jobs available yet."* state when no active postings are present.
- **Eligibility Pre-Check**: Detailed view displays specific academic prerequisites (CGPA cutoff, eligible engineering/MCA branches) and highlights whether the student qualifies.
- **Direct 1-Click Application**: Apply directly for eligible vacancies, submitting candidate details to the recruiter.
- **Application Status Tracker**: Chronological timeline displaying the real-time stage of each application:
  `APPLIED` &rarr; `UNDER_REVIEW` &rarr; `SHORTLISTED` &rarr; `INTERVIEW_SCHEDULED` &rarr; `SELECTED` (or `REJECTED` / `WITHDRAWN`).
- **Interview Schedule Hub**: View scheduled interview rounds, interviewers, dates, times, and venue/meeting links.
- **Placement Dossier**: View confirmed placement offers, hiring company, designation, and accepted CTC compensation (LPA).
- **In-App Notifications**: Receive alerts for application status updates and upcoming interview schedules.

### Recruiter Portal
- **Immediate Recruiter Registration & Access**: Register with recruiter full name, official email, phone, corporate designation, and company profile. Any recruiter can create an account and log in immediately without waiting for admin account approval.
- **Job Opportunity Submission**: Submit new campus job opportunities specifying employment type (Full-Time, Internship, Part-Time), work mode (On-Site, Hybrid, Remote), location, stipend/salary, minimum CGPA cutoff, required technical competencies, and eligible branches. Newly submitted jobs are securely placed in **Pending Approval** (`PENDING`) status.
- **Job Status Tracking**: Monitor all posted job openings with real-time lifecycle status badges (**Pending Approval**, **Approved**, **Rejected**, **Closed**). Recruiters cannot self-publish unapproved listings.
- **Job Listings Management**: Monitor applicant flow, track active listings, and close positions when vacancies are filled.
- **Candidate Dossier Review & Screening**: View applicant lists filtered by job posting, application stage (`APPLIED`, `UNDER_REVIEW`, `SHORTLISTED`), or minimum CGPA. Review candidate details, categorized skills, projects, certifications, and uploaded PDF resumes.
- **Multi-Round Interview Scheduling**: Schedule interview rounds (Technical, HR, Coding Assessment) with date, time, and physical venue or video meeting link, automatically advancing application status to `INTERVIEW_SCHEDULED` and notifying the student.
- **Interview Result Evaluation**: Record interview outcomes (`PASSED`, `FAILED`, `PENDING`) and add feedback notes.
- **Candidate Selection & Offer**: Extend final selection offers with agreed package (LPA), atomically recording the placement and updating the candidate's placement status to `SELECTED` via an ACID database transaction.
- **Recruiter Alerts**: Receive notifications when students submit applications and when administrators review posted jobs.

### Admin Portal (Training & Placement Cell)
- **Macro Institutional Dashboard**: Real-time university KPIs including total students, registered companies, active job openings, total applications, placed students, university placement rate, and package statistics.
- **Dynamic Analytics Visualizations**: Interactive charts powered by Chart.js displaying department placement rates and application status distributions.
- **Dedicated Job Approvals Queue**: Dedicated **Job Approvals (Pending Review)** section presenting all pending job postings with job title, description, company name, recruiter contact info (name, email, phone), eligibility requirements (min CGPA, branches), required skills, compensation, location, and application deadline with direct **Approve Job** and **Reject Job** action controls.
- **Job Drive Management & Status Filtering**: Central registry to monitor and review all campus job postings across companies, with quick filters across **All**, **Pending**, **Approved**, and **Rejected** listings.
- **Recruiter & Employer Directory**: Search, inspect, and manage registered recruiters and corporate partners across all hiring programs.
- **Student Directory**: Search, inspect, and filter the complete student database by branch, academic year, and placement status.
- **Master Applications Audit Trail**: Institutional overview of all student applications, screening stages, and outcomes.
- **Centralized Interview Schedule**: Calendar view of all active interview rounds across all recruiting companies.
- **Official Placement Records**: Master database of all student selections, accepted packages (LPA), and confirmed placement history.
- **Academic Hierarchy Configuration**: Manage academic departments, branches of study, and degree programs.
- **Skills Taxonomy Management**: Maintain the central repository of technical skills categorized by domain.
- **Accreditation & Placement Reporting**: Generate placement statistics for institutional audits (NAAC / NIRF reporting compliance).

---

## How CareerBridge Works

The diagram below illustrates the actual end-to-end recruitment workflow implemented in CareerBridge:

```
┌─────────────┐
│  Recruiter  │
└──────┬──────┘
       │
       ▼
   Registers Account & Logs In Immediately (Account Active, Profile Created)
       │
       ▼
   Submits Campus Job Opportunity (Title, Salary, CGPA Cutoff, Skills, Branches)
       │
       ▼
   Saved in Database (`jobs`, status: 'PENDING')
       │
       ▼
┌─────────────┐
│    Admin    │
└──────┬──────┘
       │
       ▼
   Reviews in "Job Approvals" Queue (Job Specs, Recruiter Info, Eligibility)
       │
       ├────────────────────────────────────────┐
       │ (Approve)                              │ (Reject)
       ▼                                        ▼
   Job Status: 'APPROVED'                   Job Status: 'REJECTED'
       │                                        │
       │ (Visible to Students)                  │ (Blocked from Students;
       │                                        │  Recruiter Sees Status: REJECTED)
       ▼                                        └────────────────────────┐
┌─────────────┐                                                          │
│   Student   │                                                          │
└──────┬──────┘                                                          │
       │                                                                 ▼
       ▼                                                          Recruiter Notified;
   Browses Approved Opportunities                                 Listing Hidden
       │
       ▼
   Checks Academic Eligibility & Applies
       │
       ▼
   Application Saved (`applications`, status: 'APPLIED')
       │
       ├────────────────────────────────────────┐
       ▼                                        ▼
┌─────────────┐                          ┌─────────────┐
│  Recruiter  │                          │   Student   │
└──────┬──────┘                          └─────────────┘
       │                                        │
       ▼                                        ▼
   Reviews Applicant Dossiers & Resumes   Tracks "My Applications"
       │
       ▼
   Shortlists & Schedules Interview (`interviews`, status: 'SCHEDULED')
       │
       ├────────────────────────────────────────┐
       ▼                                        ▼
┌─────────────┐                          ┌─────────────┐
│   Student   │                          │  Recruiter  │
└─────────────┘                          └──────┬──────┘
       │                                        │
       ▼                                        ▼
   Views Round in "My Interviews"         Conducts Interview Round
                                                │
                                                ▼
                                          Evaluates Result (`PASSED` / `FAILED`)
                                                │
                                                ▼
                                          Selects Candidate (`sp_process_candidate_selection`)
                                                │
                                                ▼
                                          Placement Confirmed (`placements`, status: 'SELECTED')
```

---

## Technology Stack

The project uses clean, modular web standards without heavy frameworks:

| Layer | Technologies Used | Details |
| :--- | :--- | :--- |
| **Frontend** | HTML5, CSS3, JavaScript (ES6+) | Vanilla JavaScript, CSS custom properties, responsive grid/flexbox layout, Fetch API |
| **Icons & UI** | Font Awesome 6.4.0 | CDN-delivered vector icons |
| **Charts** | Chart.js 4.4.0 | Client-side responsive charts for placement and application statistics |
| **Backend** | PHP 8.x | Modular RESTful JSON API endpoints, custom URL routing (`router.php`) |
| **Database** | MySQL / MariaDB (via PDO) | Normalized 3NF schema, InnoDB engine, prepared statements, multi-port support (3306/3308) |
| **SQL Architecture** | 19 Tables, 6 Views, 5 Procedures, 3 Functions, 5 Triggers | ACID transactions (`START TRANSACTION` / `COMMIT` / `ROLLBACK`), auto-indexing, constraint enforcement |
| **Authentication** | PHP Session + HMAC-SHA256 Bearer Tokens | Dual-auth support (cookie-based session + Bearer token header for cross-port support) |
| **Data Modeling (AI)** | Python 3.9+ (pandas, scikit-learn) | Standalone scripts in `ai/` for job matching prototypes and placement prediction datasets |

---

## Project Structure

```
CareerBridge/
├── index.html                      # Public Homepage & Discovery Portal
├── about.html                      # Placement Cell Institutional Overview
├── opportunities.html              # Public Vacancies Browser
├── companies.html                  # Corporate Hiring Partners Directory
├── how-it-works.html               # 4-Step Campus Hiring Process Guide
├── contact.html                    # T&P Office Contact & Help Desk
├── login.html                      # Role-Aware Sign In (Student, Recruiter, Admin)
├── register.html                   # Account Registration (Student / Recruiter)
├── start_server.bat                # Windows One-Click Server Launcher
├── router.php                      # PHP Built-in Server URL Router
│
├── student/                        # STUDENT PORTAL
│   ├── dashboard.html              # Academic Overview, KPIs & Recent Activity
│   ├── profile.html                # Personal Data, Roll Number, CGPA
│   ├── education.html              # 10th, 12th & Degree Semester Academic Records
│   ├── skills.html                 # Technical Skills & Proficiency Levels
│   ├── projects.html               # Academic Projects with Live Links
│   ├── certifications.html         # Professional Certifications
│   ├── resume.html                 # PDF Resume Upload & Viewer
│   ├── jobs.html                   # Opportunities Catalog with Eligibility Check
│   ├── job-details.html            # Detailed Specs & 1-Click Application
│   ├── recommendations.html        # Skill-Matched Opportunities
│   ├── applications.html           # Live Application Status Funnel
│   ├── interviews.html             # Scheduled Interview Rounds & Venues
│   ├── placement.html              # Confirmed Placement Offer & Package
│   └── notifications.html          # Alerts & Interview Feeds
│
├── recruiter/                      # RECRUITER PORTAL
│   ├── dashboard.html              # Recruiter Overview & KPIs
│   ├── profile.html                # Corporate Identity & Organization Details
│   ├── post-job.html               # Publish Vacancy (CGPA, Branches, Skills)
│   ├── manage-jobs.html            # View, Filter & Close Active Listings
│   ├── applicants.html             # Candidate Screening & Profile Review
│   ├── shortlisted.html            # Shortlisted Candidates for Technical Rounds
│   ├── interviews.html             # Schedule Technical & HR Interviews
│   ├── selected-candidates.html    # Hired Candidates & Placement Records
│   ├── analytics.html              # Recruitment Funnel & Conversion Rates
│   └── notifications.html          # Recruiter Activity & Decision Alerts
│
├── admin/                          # TRAINING & PLACEMENT ADMIN PORTAL
│   ├── dashboard.html              # Institutional Macro Dashboard (8 KPIs + Charts)
│   ├── students.html               # Student Directory with Academic Filters
│   ├── recruiters.html             # Recruiter Verification & Approvals
│   ├── companies.html              # Hiring Partners Management Directory
│   ├── jobs.html                   # Job Drive Central Review
│   ├── applications.html           # Central Applications Master Audit Trail
│   ├── interviews.html             # Institutional Interview Schedules
│   ├── placements.html             # Official Placement Archive & Compensation
│   ├── departments.html            # Academic Hierarchy (Departments & Branches)
│   ├── skills.html                 # Central Technical Skills Taxonomy
│   ├── analytics.html              # Cross-Departmental Placement Analytics
│   ├── reports.html                # Institutional Accreditation Reports (NAAC/NIRF)
│   └── notifications.html          # System Event Audit Log
│
├── backend/                        # SECURE PHP REST API
│   ├── config/database.php         # PDO Connector with Multi-Port Detection (3306/3308)
│   ├── setup_db.php                # Database Migration & Seed Initializer
│   ├── clean_fake_data.php         # Database Reset Utility (Optional Clean Slate)
│   ├── auth/                       # Auth Endpoints (login, register, session, logout, me)
│   ├── student/                    # Student Portal Endpoints (profile, jobs, applications)
│   ├── recruiter/                  # Recruiter Endpoints (jobs, applicants, interviews)
│   ├── admin/                      # Admin Management & Approval Endpoints
│   ├── analytics/                  # Real-Time Dynamic SQL Analytics Endpoints
│   └── api/                        # Public Reference Data (master_data.php)
│
├── database/                       # MYSQL DATABASE ASSETS
│   ├── schema.sql                  # 19 Normalized Relational Tables (3NF/BCNF)
│   ├── indexes.sql                 # Secondary B-Tree Optimization Indexes
│   ├── views.sql                   # 6 Analytical Views for Dynamic Reporting
│   ├── procedures.sql              # 5 Stored Procedures (ACID Transaction Logic)
│   ├── functions.sql               # 3 User-Defined Stored Functions
│   ├── triggers.sql                # 5 Automated Validation & Notification Triggers
│   └── sample_data.sql             # Reference Master Data & Academic Setup
│
├── documentation/                  # PROJECT DOCUMENTATION
│   ├── ER-Diagram.png              # Entity-Relationship (ER) Diagram
│   ├── normalization.md            # Normalization Report (1NF to BCNF)
│   ├── SQL-queries.md              # Key SQL Queries & Operations Reference
│   ├── advanced-sql.md             # Triggers, Procedures, Views & Transactions
│   ├── analytics.md                # Placement Analytics Documentation
│   └── system-workflow.md          # Placement Workflow Specification
│
├── css/                            # STYLESHEET SUITE
│   ├── style.css                   # Global Design System, Typography & Layout
│   ├── dashboard.css               # Portal Dashboard Styling & Sidebar Navigation
│   └── responsive.css              # Responsive Media Queries
│
├── js/                             # CLIENT CONTROLLERS
│   ├── main.js                     # Core API Connector & Role-Aware Bearer Auth
│   ├── auth.js                     # Login & Registration Handlers
│   ├── student.js                  # Student Portal State & Profile Controller
│   ├── recruiter.js                # Recruiter Portal Workflow Controller
│   ├── admin.js                    # Admin Portal Management Controller
│   ├── jobs.js                     # Job Marketplace Search & Filtering
│   ├── applications.js             # Applications Tracking & Timeline Controller
│   └── charts.js                   # Chart.js Dynamic Visualizations
│
├── assets/                         # STATIC ASSETS
│   └── uploads/                    # Directory for Resumes & Offer Letters
│
└── ai/                             # PYTHON DATA SCIENCE UTILITIES (OPTIONAL)
    ├── requirements.txt            # Python Dependencies (pandas, scikit-learn)
    ├── job_matching.py             # TF-IDF Matching Prototype
    ├── placement_prediction.py     # Student Placement Prediction Model
    └── train_model.py              # Model Training Script
```

---

## Installation & Setup

Follow these steps to set up and run CareerBridge on your local machine:

### Prerequisites
- **PHP**: Version 8.0 or higher
- **MySQL / MariaDB**: Version 8.0+ or MariaDB 10.4+ (available via [XAMPP](https://www.apachefriends.org/))
- **Web Browser**: Google Chrome, Mozilla Firefox, or Microsoft Edge
- *(Optional)* **Python 3.9+**: Only required if running the standalone ML models in `ai/`

---

### Step 1: Clone or Download the Project
```bash
git clone https://github.com/your-username/CareerBridge.git
cd CareerBridge
```

---

### Step 2: Start MySQL Service
1. Open the **XAMPP Control Panel**.
2. Click **Start** next to **MySQL** (default port `3306` or `3308`).

---

### Step 3: Initialize the Database
Run the automated PHP database setup script from your terminal:

```bash
php backend/setup_db.php
```

This script will:
- Connect to your running MySQL instance (automatically testing ports `3306`, `3308`, and `3307`).
- Create the database `placement_management` if it does not already exist.
- Import `schema.sql`, `indexes.sql`, `views.sql`, `procedures.sql`, `functions.sql`, `triggers.sql`, and initial master reference data.

*Alternatively, you can manually import the files in the `database/` folder in order using MySQL Workbench or phpMyAdmin.*

---

### Step 4: Start the Web Server

#### Option A: One-Click Launcher (Windows)
Double-click [`start_server.bat`](start_server.bat) in the project root directory.

#### Option B: Terminal Command (Any OS)
From the project root directory, run:

```bash
php -S localhost:8000 router.php
```

---

### Step 5: Open the Application
Open your web browser and navigate to:

```
http://localhost:8000
```

---

### Default Administrator Credentials

CareerBridge includes a pre-configured administrator account for the Training & Placement (T&P) Cell:

| Field | Detail |
| :--- | :--- |
| **Admin Name** | `T&P Administrator` *(displayed as `T&P Admin` in the dashboard UI)* |
| **Email / Username** | `admin@campusplacement.com` |
| **Password** | `Admin@123` |
| **Role in Login Dropdown** | `Training & Placement Cell (Admin)` |
| **Portal URL** | `http://localhost:8000/admin/dashboard.html` |

> **Sign In Instructions:** Visit `http://localhost:8000/login.html`, select **Training & Placement Cell (Admin)** from the role dropdown, enter `admin@campusplacement.com` with password `Admin@123`, and click **Sign In**.

---

## Environment Variables & Configuration

CareerBridge works out of the box with default XAMPP settings (`127.0.0.1`, user `root`, no password). If your environment requires custom database credentials, you can configure the following environment variables:

| Variable | Description | Default Value |
| :--- | :--- | :--- |
| `DB_HOST` | Host address of the MySQL server | `127.0.0.1` |
| `DB_PORT` | Port number of the MySQL server | Auto-detected (`3306`, `3308`, `3307`) |
| `DB_NAME` | Name of the database schema | `placement_management` |
| `DB_USER` | Database username | `root` |
| `DB_PASS` | Database user password | `""` (empty) |

### Example `.env` (Optional)
```ini
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=placement_management
DB_USER=root
DB_PASS=your_database_password
```

---

## Database Architecture

The system uses a normalized relational database schema with referential integrity enforced by foreign key constraints:

### Core Entities & Tables (19 Tables)

1. **`users`**: Base authentication table storing email, bcrypt password hash, role (`STUDENT`, `RECRUITER`, `ADMIN`), and account status.
2. **`departments`**: Academic departments (CSE, IT, ECE, MECH, MCA).
3. **`branches`**: Academic branches linked to departments with degree designations (B.Tech, MCA).
4. **`students`**: Student profiles (roll number, personal info, branch, CGPA, graduation year, placement status, resume link).
5. **`education`**: Historical academic qualifications (10th, 12th, and semester records).
6. **`skills`**: Standardized technical competencies categorized by domain.
7. **`student_skills`**: Junction table mapping student proficiencies (Beginner, Intermediate, Advanced, Expert).
8. **`projects`**: Student portfolio projects with technologies used and repository links.
9. **`certifications`**: Professional certifications earned by students.
10. **`companies`**: Registered corporate employers with industry, location, website, and verification status (`is_verified = 1` for admin-approved partners).
11. **`recruiters`**: Recruiter profiles linked to user accounts and companies, capturing full name (`recruiter_name`), corporate designation, phone, and organization profile.
12. **`jobs`**: Job postings with employment type, work mode, salary/stipend, CGPA cutoff, deadline, and status (`status`: `PENDING`, `APPROVED`, `REJECTED`, `LIVE`, `CLOSED`). Jobs posted by recruiters enter the system in `PENDING` status and become visible to students only once approved by an administrator (`APPROVED` or `LIVE`).
13. **`job_skills`**: Competencies required or preferred for each job.
14. **`job_eligible_branches`**: Branch-specific eligibility restrictions per job posting.
15. **`applications`**: Job applications submitted by students with status progression (`APPLIED`, `UNDER_REVIEW`, `SHORTLISTED`, `INTERVIEW_SCHEDULED`, `SELECTED`, `REJECTED`, `WITHDRAWN`).
16. **`interviews`**: Scheduled interview rounds with date, venue/meeting link, status, and feedback.
17. **`placements`**: Official placement offers with accepted compensation package (LPA) and offer letter links.
18. **`notifications`**: In-app notifications with read status and direct links.
19. **`audit_logs`**: System audit trail tracking critical data changes (e.g., CGPA updates).

### Advanced SQL Components
- **6 Analytical Views**: Multi-table aggregations for complete student profiles, expanded job specs, application tracking, department placement summaries, top skills, and company hiring volumes.
- **5 Stored Procedures**: Includes `sp_process_candidate_selection` using ACID transactions (`START TRANSACTION`, `COMMIT`, `ROLLBACK`) for atomic candidate selection and placement creation.
- **3 Stored Functions**: Calculates composite student match scores, institutional placement rates, and interview success rates.
- **5 Triggers**: Enforces business rules (preventing applications on unapproved or closed jobs, updating student placement status upon selection, audit logging).

---

## User Roles

| Capability | Student | Recruiter | Admin |
| :--- | :---: | :---: | :---: |
| **Register & Immediate Sign In** | Yes | Yes (Immediate Access) | Yes (Pre-configured) |
| **Manage Profile, Skills & Resume** | Yes | Company Profile | System Profile |
| **Browse Job Openings** | Yes (Approved Jobs Only) | Yes (Own Jobs) | Yes (All Jobs) |
| **Check Academic Eligibility** | Yes | View Criteria | Manage Criteria |
| **Apply for Job Openings** | Yes | No | No |
| **Track Application Status** | Yes (Own) | Yes (Company Applicants) | Yes (All Applications) |
| **Submit Job Postings** | No | Yes (Pending Admin Approval) | Yes |
| **Review & Approve/Reject Jobs** | No | No | Yes (Dedicated Queue) |
| **Screen Candidate Resumes** | No | Yes | Yes |
| **Schedule Interview Rounds** | View Own | Yes (Company Applicants) | View Institutional Schedule |
| **Extend Placement Offers** | Accept/View Offer | Yes (Atomic Transaction) | View Records |
| **Manage Departments & Branches** | No | No | Yes |
| **Institutional Analytics & Reports** | Personal Stats | Recruitment Funnel | University-Wide KPIs |

### Role Breakdown

#### 1. Student
- **Account Creation:** Students register their own accounts with their university roll number, academic branch, and CGPA. Login using email or roll number.
- **Key Responsibilities:** Build and maintain an academic and technical portfolio (skills, projects, certifications, PDF resume), browse active vacancies from verified employers, verify academic eligibility against job prerequisites, submit job applications, track application progress through each stage (`APPLIED` &rarr; `UNDER_REVIEW` &rarr; `SHORTLISTED` &rarr; `INTERVIEW_SCHEDULED` &rarr; `SELECTED`), attend scheduled interview rounds, and view confirmed placement packages.

#### 2. Recruiter
- **Account Creation:** Corporate recruiters register with their full name, corporate email, phone, corporate designation, and company profile. Recruiter accounts are activated immediately upon registration without requiring admin approval.
- **Key Responsibilities:** Set up and maintain company profile; submit campus job opportunities (saved in `PENDING` status for T&P Cell review); track job approval statuses (**Pending Approval**, **Approved**, **Rejected**, **Closed**); review and filter candidate applications once jobs are approved; inspect applicant skills, projects, and PDF resumes; shortlist qualified candidates; schedule multi-round interviews with physical venues or video meeting links; submit interview evaluations (`PASSED`/`FAILED`); and extend official placement offers via atomic database transaction (`sp_process_candidate_selection`).

#### 3. Administrator (T&P Cell)
- **Account Details:**
  - **Admin Name:** `T&P Administrator` *(Sidebar UI Display: `T&P Admin`)*
  - **Email:** `admin@campusplacement.com`
  - **Default Password:** `Admin@123`
  - **Login Role:** `Training & Placement Cell (Admin)`
- **Key Responsibilities:** Review newly submitted job opportunities in the dedicated **Job Approvals** queue; evaluate job specifications, compensation, academic eligibility, and recruiter contact info, and approve or reject job postings; oversee institution-wide campus hiring drives; inspect all student applications, interview calendars, and placement records; manage academic departments, branches, and the technical skills repository; and generate official placement statistics for institutional accreditation (NAAC/NIRF).

---

## Security

CareerBridge implements standard web security practices across all layers:

1. **Password Hashing**: Passwords are encrypted using PHP's native `password_hash()` with the `PASSWORD_BCRYPT` algorithm and verified with `password_verify()`.
2. **SQL Injection Protection**: All database queries use PDO prepared statements with strict parameter binding across 100% of API endpoints.
3. **Session & Token Authentication**: Uses PHP session management alongside HMAC-SHA256 signed Bearer tokens (`backend/auth/session.php`) to ensure authenticated requests across different ports and clients.
4. **Role-Based Access Control (RBAC)**: Server-side route guards (`requireStudent()`, `requireRecruiter()`, `requireAdmin()`) enforce authorization before executing operations.
5. **Job Approval & Visibility Enforcement Guard**: Backend query filters strictly isolate unapproved (`PENDING`) and rejected (`REJECTED`) job postings from student visibility (catalog queries, recommendations, and single-job lookup return HTTP 404). Database trigger `trg_prevent_application_on_closed_job` guarantees at the database engine level that applications can only be inserted for `APPROVED` or `LIVE` jobs.
6. **Transactional Integrity**: Critical operations, such as extending candidate offers, use ACID transactions with automatic rollback (`DECLARE EXIT HANDLER FOR SQLEXCEPTION`) to prevent data inconsistency.
7. **CORS & Preflight Handling**: Configured with strict origin checks, credential allowances, and allowed HTTP method headers.

---

## Future Enhancements

The following features represent logical enhancements for future versions:

- **Automated Email Notifications**: Integration with SMTP/PHPMailer for automated email alerts when interviews are scheduled or offers are extended.
- **Resume Parsing**: Automated extraction of skills, education, and experience directly from uploaded PDF resumes.
- **Video Interview Integration**: Embedded WebRTC video rooms or calendar meeting links (Zoom/Google Meet API).
- **Calendar Synchronization**: Export interview rounds directly to Google Calendar or Microsoft Outlook (`.ics` integration).
- **Mobile Application**: Dedicated mobile app (React Native / Flutter) for real-time student alerts.

---

## Contributing

Contributions are welcome! If you would like to contribute:

1. Fork the repository.
2. Create a new feature branch:
   ```bash
   git checkout -b feature/your-feature-name
   ```
3. Commit your changes with clear messages:
   ```bash
   git commit -m "Add descriptive commit message"
   ```
4. Push to your branch:
   ```bash
   git push origin feature/your-feature-name
   ```
5. Open a Pull Request detailing the changes made.

---

## License

License information can be added when the project is released.

---

**CareerBridge &copy; 2026** — Campus Internship & Placement Management System.
