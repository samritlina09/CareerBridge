-- ====================================================================
-- AI-BASED INTERNSHIP & PLACEMENT MANAGEMENT SYSTEM
-- Relational Database Schema (MySQL 8.x) - 3rd Normal Form (3NF)
-- ====================================================================

DROP DATABASE IF EXISTS placement_management;
CREATE DATABASE placement_management
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE placement_management;

-- --------------------------------------------------------------------
-- 1. DEPARTMENTS TABLE
-- --------------------------------------------------------------------
CREATE TABLE departments (
    dept_id INT AUTO_INCREMENT PRIMARY KEY,
    dept_name VARCHAR(100) NOT NULL UNIQUE,
    dept_code VARCHAR(10) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------------------
-- 2. BRANCHES TABLE
-- --------------------------------------------------------------------
CREATE TABLE branches (
    branch_id INT AUTO_INCREMENT PRIMARY KEY,
    dept_id INT NOT NULL,
    branch_name VARCHAR(100) NOT NULL,
    branch_code VARCHAR(10) NOT NULL,
    degree_type ENUM('B.Tech', 'M.Tech', 'MCA', 'MBA', 'B.Sc') NOT NULL DEFAULT 'B.Tech',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_branch_dept FOREIGN KEY (dept_id) 
        REFERENCES departments(dept_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT uq_branch_code UNIQUE (branch_code)
) ENGINE=InnoDB;

-- --------------------------------------------------------------------
-- 3. USERS TABLE (Authentication & Role Base)
-- --------------------------------------------------------------------
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('STUDENT', 'RECRUITER', 'ADMIN') NOT NULL,
    status ENUM('ACTIVE', 'PENDING', 'SUSPENDED') NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------------------
-- 4. STUDENTS TABLE (1:1 with USERS)
-- --------------------------------------------------------------------
CREATE TABLE students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    roll_number VARCHAR(50) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    dob DATE,
    gender ENUM('MALE', 'FEMALE', 'OTHER'),
    address TEXT,
    branch_id INT,
    current_year INT NOT NULL DEFAULT 4 CHECK (current_year BETWEEN 1 AND 5),
    cgpa DECIMAL(3,2) NOT NULL DEFAULT 0.00 CHECK (cgpa BETWEEN 0.00 AND 10.00),
    tenth_percent DECIMAL(5,2) DEFAULT 0.00 CHECK (tenth_percent BETWEEN 0.00 AND 100.00),
    twelfth_percent DECIMAL(5,2) DEFAULT 0.00 CHECK (twelfth_percent BETWEEN 0.00 AND 100.00),
    resume_path VARCHAR(255) DEFAULT NULL,
    placement_status ENUM('NOT_PLACED', 'PLACED', 'OPTED_OUT') NOT NULL DEFAULT 'NOT_PLACED',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_student_user FOREIGN KEY (user_id) 
        REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_student_branch FOREIGN KEY (branch_id) 
        REFERENCES branches(branch_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------------------
-- 5. COMPANIES TABLE
-- --------------------------------------------------------------------
CREATE TABLE companies (
    company_id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(150) NOT NULL UNIQUE,
    website VARCHAR(255),
    industry VARCHAR(100),
    description TEXT,
    location VARCHAR(100),
    logo_path VARCHAR(255) DEFAULT NULL,
    is_verified TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------------------
-- 6. RECRUITERS TABLE (1:1 with USERS, M:1 with COMPANIES)
-- --------------------------------------------------------------------
CREATE TABLE recruiters (
    recruiter_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    recruiter_name VARCHAR(150) DEFAULT NULL,
    company_id INT NOT NULL,
    designation VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    approval_status ENUM('PENDING', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'PENDING',
    approved_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_recruiter_user FOREIGN KEY (user_id) 
        REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_recruiter_company FOREIGN KEY (company_id) 
        REFERENCES companies(company_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------------------
-- 7. SKILLS MASTER TABLE
-- --------------------------------------------------------------------
CREATE TABLE skills (
    skill_id INT AUTO_INCREMENT PRIMARY KEY,
    skill_name VARCHAR(100) NOT NULL UNIQUE,
    category VARCHAR(50) NOT NULL DEFAULT 'Technical'
) ENGINE=InnoDB;

-- --------------------------------------------------------------------
-- 8. STUDENT_SKILLS (M:N between STUDENTS and SKILLS)
-- --------------------------------------------------------------------
CREATE TABLE student_skills (
    student_skill_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    skill_id INT NOT NULL,
    proficiency_level ENUM('BEGINNER', 'INTERMEDIATE', 'ADVANCED', 'EXPERT') NOT NULL DEFAULT 'INTERMEDIATE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_student_skill UNIQUE (student_id, skill_id),
    CONSTRAINT fk_ss_student FOREIGN KEY (student_id) 
        REFERENCES students(student_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ss_skill FOREIGN KEY (skill_id) 
        REFERENCES skills(skill_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------------------
-- 9. EDUCATION TABLE (1:M with STUDENTS)
-- --------------------------------------------------------------------
CREATE TABLE education (
    education_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    degree VARCHAR(100) NOT NULL,
    institution VARCHAR(150) NOT NULL,
    board_university VARCHAR(150),
    passing_year INT NOT NULL,
    score_percentage DECIMAL(5,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_edu_student FOREIGN KEY (student_id) 
        REFERENCES students(student_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------------------
-- 10. PROJECTS TABLE (1:M with STUDENTS)
-- --------------------------------------------------------------------
CREATE TABLE projects (
    project_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    technologies VARCHAR(255),
    github_url VARCHAR(255),
    live_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_proj_student FOREIGN KEY (student_id) 
        REFERENCES students(student_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------------------
-- 11. CERTIFICATIONS TABLE (1:M with STUDENTS)
-- --------------------------------------------------------------------
CREATE TABLE certifications (
    cert_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    issuing_org VARCHAR(150) NOT NULL,
    issue_date DATE,
    credential_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cert_student FOREIGN KEY (student_id) 
        REFERENCES students(student_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------------------
-- 12. JOBS TABLE
-- --------------------------------------------------------------------
CREATE TABLE jobs (
    job_id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    posted_by_recruiter_id INT DEFAULT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    job_type ENUM('INTERNSHIP', 'FULL_TIME', 'PART_TIME') NOT NULL DEFAULT 'FULL_TIME',
    work_mode ENUM('ON_SITE', 'REMOTE', 'HYBRID') NOT NULL DEFAULT 'ON_SITE',
    location VARCHAR(100) NOT NULL,
    min_cgpa DECIMAL(3,2) NOT NULL DEFAULT 6.00,
    salary_stipend DECIMAL(12,2) NOT NULL,
    experience_req VARCHAR(50) DEFAULT 'Fresher',
    deadline DATE NOT NULL,
    openings_count INT DEFAULT 1,
    status ENUM('PENDING', 'APPROVED', 'REJECTED', 'LIVE', 'CLOSED') NOT NULL DEFAULT 'PENDING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_job_company FOREIGN KEY (company_id) 
        REFERENCES companies(company_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_job_recruiter FOREIGN KEY (posted_by_recruiter_id) 
        REFERENCES recruiters(recruiter_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------------------
-- 13. JOB_ELIGIBLE_BRANCHES (M:N between JOBS and BRANCHES)
-- --------------------------------------------------------------------
CREATE TABLE job_eligible_branches (
    job_branch_id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    branch_id INT NOT NULL,
    CONSTRAINT uq_job_branch UNIQUE (job_id, branch_id),
    CONSTRAINT fk_jeb_job FOREIGN KEY (job_id) 
        REFERENCES jobs(job_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_jeb_branch FOREIGN KEY (branch_id) 
        REFERENCES branches(branch_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------------------
-- 14. JOB_SKILLS (M:N between JOBS and SKILLS)
-- --------------------------------------------------------------------
CREATE TABLE job_skills (
    job_skill_id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    skill_id INT NOT NULL,
    is_required TINYINT(1) DEFAULT 1,
    CONSTRAINT uq_job_skill UNIQUE (job_id, skill_id),
    CONSTRAINT fk_js_job FOREIGN KEY (job_id) 
        REFERENCES jobs(job_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_js_skill FOREIGN KEY (skill_id) 
        REFERENCES skills(skill_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------------------
-- 15. APPLICATIONS TABLE (Core recruitment transactions)
-- --------------------------------------------------------------------
CREATE TABLE applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    student_id INT NOT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('APPLIED', 'UNDER_REVIEW', 'SHORTLISTED', 'INTERVIEW_SCHEDULED', 'SELECTED', 'REJECTED', 'WITHDRAWN') NOT NULL DEFAULT 'APPLIED',
    notes TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_job_student UNIQUE (job_id, student_id),
    CONSTRAINT fk_app_job FOREIGN KEY (job_id) 
        REFERENCES jobs(job_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_app_student FOREIGN KEY (student_id) 
        REFERENCES students(student_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------------------
-- 16. INTERVIEWS TABLE (1:M with APPLICATIONS)
-- --------------------------------------------------------------------
CREATE TABLE interviews (
    interview_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    round_name VARCHAR(100) NOT NULL,
    scheduled_at DATETIME NOT NULL,
    meeting_link_location VARCHAR(255),
    interviewer_feedback TEXT,
    status ENUM('SCHEDULED', 'COMPLETED', 'CANCELLED') NOT NULL DEFAULT 'SCHEDULED',
    result ENUM('PENDING', 'PASSED', 'FAILED') NOT NULL DEFAULT 'PENDING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_interview_app FOREIGN KEY (application_id) 
        REFERENCES applications(application_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------------------
-- 17. PLACEMENTS TABLE (Offers accepted / confirmed)
-- --------------------------------------------------------------------
CREATE TABLE placements (
    placement_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL UNIQUE,
    student_id INT NOT NULL,
    company_id INT NOT NULL,
    job_id INT NOT NULL,
    package_lpa DECIMAL(10,2) NOT NULL,
    offer_letter_path VARCHAR(255) DEFAULT NULL,
    accepted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_place_app FOREIGN KEY (application_id) 
        REFERENCES applications(application_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_place_student FOREIGN KEY (student_id) 
        REFERENCES students(student_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_place_company FOREIGN KEY (company_id) 
        REFERENCES companies(company_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_place_job FOREIGN KEY (job_id) 
        REFERENCES jobs(job_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------------------
-- 18. NOTIFICATIONS TABLE
-- --------------------------------------------------------------------
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    link_url VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) 
        REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;
