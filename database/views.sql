-- ====================================================================
-- DATABASE VIEWS FOR REPORTING AND REUSABLE ANALYTICS
-- ====================================================================

USE placement_management;

-- --------------------------------------------------------------------
-- 1. VIEW: Complete Student Profiles with Academic & Skill Counts
-- --------------------------------------------------------------------
CREATE OR REPLACE VIEW vw_student_profiles_complete AS
SELECT 
    s.student_id,
    s.user_id,
    u.email,
    s.roll_number,
    CONCAT(s.first_name, ' ', s.last_name) AS full_name,
    s.first_name,
    s.last_name,
    s.phone,
    s.gender,
    s.dob,
    s.cgpa,
    s.tenth_percent,
    s.twelfth_percent,
    s.current_year,
    s.placement_status,
    s.resume_path,
    b.branch_id,
    b.branch_name,
    b.branch_code,
    b.degree_type,
    d.dept_id,
    d.dept_name,
    d.dept_code,
    COUNT(DISTINCT ss.skill_id) AS total_skills,
    COUNT(DISTINCT p.project_id) AS total_projects,
    COUNT(DISTINCT c.cert_id) AS total_certifications,
    COUNT(DISTINCT a.application_id) AS total_applications
FROM students s
JOIN users u ON s.user_id = u.user_id
LEFT JOIN branches b ON s.branch_id = b.branch_id
LEFT JOIN departments d ON b.dept_id = d.dept_id
LEFT JOIN student_skills ss ON s.student_id = ss.student_id
LEFT JOIN projects p ON s.student_id = p.student_id
LEFT JOIN certifications c ON s.student_id = c.student_id
LEFT JOIN applications a ON s.student_id = a.student_id
GROUP BY s.student_id, s.user_id, u.email, s.roll_number, s.first_name, s.last_name, 
         s.phone, s.gender, s.dob, s.cgpa, s.tenth_percent, s.twelfth_percent, 
         s.current_year, s.placement_status, s.resume_path, b.branch_id, b.branch_name, 
         b.branch_code, b.degree_type, d.dept_id, d.dept_name, d.dept_code;

-- --------------------------------------------------------------------
-- 2. VIEW: Expanded Job Information with Aggregated Requirements
-- --------------------------------------------------------------------
CREATE OR REPLACE VIEW vw_job_details_expanded AS
SELECT 
    j.job_id,
    j.company_id,
    c.company_name,
    c.industry,
    c.location AS company_headquarters,
    c.logo_path AS company_logo,
    j.title AS job_title,
    j.description,
    j.job_type,
    j.work_mode,
    j.location AS job_location,
    j.min_cgpa,
    j.salary_stipend,
    j.experience_req,
    j.deadline,
    j.openings_count,
    j.status AS job_status,
    j.created_at,
    GROUP_CONCAT(DISTINCT sk.skill_name ORDER BY sk.skill_name SEPARATOR ', ') AS required_skills_list,
    GROUP_CONCAT(DISTINCT br.branch_code ORDER BY br.branch_code SEPARATOR ', ') AS eligible_branches_list,
    COUNT(DISTINCT app.application_id) AS applicant_count
FROM jobs j
JOIN companies c ON j.company_id = c.company_id
LEFT JOIN job_skills js ON j.job_id = js.job_id
LEFT JOIN skills sk ON js.skill_id = sk.skill_id
LEFT JOIN job_eligible_branches jeb ON j.job_id = jeb.job_id
LEFT JOIN branches br ON jeb.branch_id = br.branch_id
LEFT JOIN applications app ON j.job_id = app.job_id
GROUP BY j.job_id, j.company_id, c.company_name, c.industry, c.location, c.logo_path,
         j.title, j.description, j.job_type, j.work_mode, j.location, j.min_cgpa,
         j.salary_stipend, j.experience_req, j.deadline, j.openings_count, j.status, j.created_at;

-- --------------------------------------------------------------------
-- 3. VIEW: Complete Application Lifecycle Tracker
-- --------------------------------------------------------------------
CREATE OR REPLACE VIEW vw_application_tracker AS
SELECT 
    a.application_id,
    a.job_id,
    a.student_id,
    s.roll_number,
    CONCAT(s.first_name, ' ', s.last_name) AS student_name,
    u.email AS student_email,
    s.cgpa,
    b.branch_code,
    d.dept_name,
    j.title AS job_title,
    j.job_type,
    c.company_name,
    j.salary_stipend,
    a.applied_at,
    a.status AS application_status,
    a.notes,
    i.interview_id,
    i.round_name,
    i.scheduled_at AS interview_time,
    i.status AS interview_status,
    i.result AS interview_result,
    p.placement_id,
    p.package_lpa
FROM applications a
JOIN students s ON a.student_id = s.student_id
JOIN users u ON s.user_id = u.user_id
LEFT JOIN branches b ON s.branch_id = b.branch_id
LEFT JOIN departments d ON b.dept_id = d.dept_id
JOIN jobs j ON a.job_id = j.job_id
JOIN companies c ON j.company_id = c.company_id
LEFT JOIN (
    SELECT application_id, interview_id, round_name, scheduled_at, status, result
    FROM interviews
    WHERE (application_id, scheduled_at) IN (
        SELECT application_id, MAX(scheduled_at)
        FROM interviews
        GROUP BY application_id
    )
) i ON a.application_id = i.application_id
LEFT JOIN placements p ON a.application_id = p.application_id;

-- --------------------------------------------------------------------
-- 4. VIEW: Department-wise Placement Summary
-- --------------------------------------------------------------------
CREATE OR REPLACE VIEW vw_department_placement_summary AS
SELECT 
    d.dept_id,
    d.dept_name,
    d.dept_code,
    COUNT(DISTINCT s.student_id) AS total_students,
    COUNT(DISTINCT CASE WHEN s.placement_status = 'PLACED' THEN s.student_id END) AS placed_students,
    ROUND(
        (COUNT(DISTINCT CASE WHEN s.placement_status = 'PLACED' THEN s.student_id END) * 100.0) / 
        NULLIF(COUNT(DISTINCT s.student_id), 0), 2
    ) AS placement_percentage,
    ROUND(AVG(p.package_lpa), 2) AS average_package_lpa,
    COALESCE(MAX(p.package_lpa), 0.00) AS highest_package_lpa,
    COALESCE(MIN(p.package_lpa), 0.00) AS lowest_package_lpa
FROM departments d
LEFT JOIN branches b ON d.dept_id = b.dept_id
LEFT JOIN students s ON b.branch_id = s.branch_id
LEFT JOIN placements p ON s.student_id = p.student_id
GROUP BY d.dept_id, d.dept_name, d.dept_code;

-- --------------------------------------------------------------------
-- 5. VIEW: Top Demanded Skills vs Student Supply
-- --------------------------------------------------------------------
CREATE OR REPLACE VIEW vw_top_demanded_skills AS
SELECT 
    sk.skill_id,
    sk.skill_name,
    sk.category,
    COUNT(DISTINCT js.job_id) AS jobs_requiring_skill,
    COUNT(DISTINCT ss.student_id) AS students_with_skill,
    (COUNT(DISTINCT js.job_id) - COUNT(DISTINCT ss.student_id)) AS demand_supply_gap
FROM skills sk
LEFT JOIN job_skills js ON sk.skill_id = js.skill_id
LEFT JOIN student_skills ss ON sk.skill_id = ss.skill_id
GROUP BY sk.skill_id, sk.skill_name, sk.category
ORDER BY jobs_requiring_skill DESC;

-- --------------------------------------------------------------------
-- 6. VIEW: Company Hiring & Recruitment Performance Summary
-- --------------------------------------------------------------------
CREATE OR REPLACE VIEW vw_company_hiring_summary AS
SELECT 
    c.company_id,
    c.company_name,
    c.industry,
    COUNT(DISTINCT j.job_id) AS total_jobs_posted,
    COUNT(DISTINCT a.application_id) AS total_applicants,
    COUNT(DISTINCT CASE WHEN a.status = 'SHORTLISTED' THEN a.application_id END) AS total_shortlisted,
    COUNT(DISTINCT p.placement_id) AS total_hires,
    ROUND(AVG(p.package_lpa), 2) AS avg_package_lpa,
    MAX(p.package_lpa) AS max_package_lpa
FROM companies c
LEFT JOIN jobs j ON c.company_id = j.company_id
LEFT JOIN applications a ON j.job_id = a.job_id
LEFT JOIN placements p ON c.company_id = p.company_id
GROUP BY c.company_id, c.company_name, c.industry;
