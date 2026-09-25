-- ====================================================================
-- DATABASE INDEXES FOR QUERY OPTIMIZATION
-- ====================================================================

USE placement_management;

-- Users table indexing
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role_status ON users(role, status);

-- Students table indexing
CREATE INDEX idx_students_roll ON students(roll_number);
CREATE INDEX idx_students_branch ON students(branch_id);
CREATE INDEX idx_students_cgpa ON students(cgpa);
CREATE INDEX idx_students_placement_status ON students(placement_status);

-- Companies and Recruiters
CREATE INDEX idx_companies_name ON companies(company_name);
CREATE INDEX idx_recruiters_company ON recruiters(company_id);
CREATE INDEX idx_recruiters_approval ON recruiters(approval_status);

-- Jobs table indexing
CREATE INDEX idx_jobs_status ON jobs(status);
CREATE INDEX idx_jobs_deadline ON jobs(deadline);
CREATE INDEX idx_jobs_company ON jobs(company_id);
CREATE INDEX idx_jobs_type_mode ON jobs(job_type, work_mode);
CREATE INDEX idx_jobs_min_cgpa ON jobs(min_cgpa);

-- Applications table indexing
CREATE INDEX idx_applications_job ON applications(job_id);
CREATE INDEX idx_applications_student ON applications(student_id);
CREATE INDEX idx_applications_status ON applications(status);
CREATE INDEX idx_applications_applied_at ON applications(applied_at);

-- Interviews table indexing
CREATE INDEX idx_interviews_app ON interviews(application_id);
CREATE INDEX idx_interviews_sched ON interviews(scheduled_at);
CREATE INDEX idx_interviews_status ON interviews(status);

-- Placements table indexing
CREATE INDEX idx_placements_student ON placements(student_id);
CREATE INDEX idx_placements_company ON placements(company_id);
CREATE INDEX idx_placements_package ON placements(package_lpa);

-- Notifications table indexing
CREATE INDEX idx_notif_user_read ON notifications(user_id, is_read);
