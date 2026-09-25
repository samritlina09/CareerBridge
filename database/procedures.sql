-- ====================================================================
-- STORED PROCEDURES (Business Logic & Transactions)
-- ====================================================================

USE placement_management;

DELIMITER $$

-- --------------------------------------------------------------------
-- 1. PROCEDURE: Get all eligible students for a given Job ID
-- --------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_get_eligible_students$$
CREATE PROCEDURE sp_get_eligible_students(IN p_job_id INT)
BEGIN
    DECLARE v_min_cgpa DECIMAL(3,2);
    
    -- Retrieve job minimum CGPA
    SELECT min_cgpa INTO v_min_cgpa FROM jobs WHERE job_id = p_job_id;
    
    SELECT 
        s.student_id,
        s.roll_number,
        CONCAT(s.first_name, ' ', s.last_name) AS student_name,
        u.email,
        s.cgpa,
        b.branch_code,
        d.dept_name,
        s.placement_status,
        COUNT(DISTINCT js.skill_id) AS matched_skills_count,
        (SELECT COUNT(*) FROM job_skills WHERE job_id = p_job_id) AS total_required_skills
    FROM students s
    JOIN users u ON s.user_id = u.user_id
    JOIN branches b ON s.branch_id = b.branch_id
    JOIN departments d ON b.dept_id = d.dept_id
    LEFT JOIN student_skills ss ON s.student_id = ss.student_id
    LEFT JOIN job_skills js ON js.job_id = p_job_id AND ss.skill_id = js.skill_id
    -- Branch eligibility check (either explicitly listed in job_eligible_branches or all branches allowed)
    WHERE (
        EXISTS (
            SELECT 1 FROM job_eligible_branches jeb 
            WHERE jeb.job_id = p_job_id AND jeb.branch_id = s.branch_id
        ) 
        OR NOT EXISTS (
            SELECT 1 FROM job_eligible_branches jeb WHERE jeb.job_id = p_job_id
        )
    )
    AND s.cgpa >= COALESCE(v_min_cgpa, 0.00)
    AND u.status = 'ACTIVE'
    GROUP BY s.student_id, s.roll_number, s.first_name, s.last_name, u.email, s.cgpa, b.branch_code, d.dept_name, s.placement_status
    ORDER BY matched_skills_count DESC, s.cgpa DESC;
END$$

-- --------------------------------------------------------------------
-- 2. PROCEDURE: Get all applications for a specific student
-- --------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_get_student_applications$$
CREATE PROCEDURE sp_get_student_applications(IN p_student_id INT)
BEGIN
    SELECT 
        a.application_id,
        a.job_id,
        j.title AS job_title,
        j.job_type,
        j.work_mode,
        j.location,
        j.salary_stipend,
        c.company_name,
        c.logo_path,
        a.applied_at,
        a.status AS application_status,
        a.notes,
        (SELECT COUNT(*) FROM interviews i WHERE i.application_id = a.application_id) AS total_interviews,
        (
            SELECT CONCAT(i.round_name, ' (', DATE_FORMAT(i.scheduled_at, '%d %b %Y %h:%i %p'), ')') 
            FROM interviews i 
            WHERE i.application_id = a.application_id 
            ORDER BY i.scheduled_at DESC LIMIT 1
        ) AS latest_interview_info
    FROM applications a
    JOIN jobs j ON a.job_id = j.job_id
    JOIN companies c ON j.company_id = c.company_id
    WHERE a.student_id = p_student_id
    ORDER BY a.applied_at DESC;
END$$

-- --------------------------------------------------------------------
-- 3. PROCEDURE: Get applicants for a company's jobs with filters
-- --------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_get_company_applicants$$
CREATE PROCEDURE sp_get_company_applicants(IN p_company_id INT)
BEGIN
    SELECT 
        a.application_id,
        a.job_id,
        j.title AS job_title,
        s.student_id,
        s.roll_number,
        CONCAT(s.first_name, ' ', s.last_name) AS student_name,
        u.email AS student_email,
        s.phone,
        s.cgpa,
        b.branch_code,
        d.dept_code,
        s.resume_path,
        a.status AS application_status,
        a.applied_at,
        (
            SELECT GROUP_CONCAT(sk.skill_name SEPARATOR ', ')
            FROM student_skills ss
            JOIN skills sk ON ss.skill_id = sk.skill_id
            WHERE ss.student_id = s.student_id
        ) AS student_skills
    FROM applications a
    JOIN jobs j ON a.job_id = j.job_id
    JOIN students s ON a.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    LEFT JOIN branches b ON s.branch_id = b.branch_id
    LEFT JOIN departments d ON b.dept_id = d.dept_id
    WHERE j.company_id = p_company_id
    ORDER BY a.applied_at DESC;
END$$

-- --------------------------------------------------------------------
-- 4. PROCEDURE: Department-wise placement performance statistics
-- --------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_get_department_placement_stats$$
CREATE PROCEDURE sp_get_department_placement_stats()
BEGIN
    SELECT 
        d.dept_id,
        d.dept_name,
        d.dept_code,
        COUNT(DISTINCT s.student_id) AS total_eligible,
        COUNT(DISTINCT CASE WHEN s.placement_status = 'PLACED' THEN s.student_id END) AS total_placed,
        ROUND(
            (COUNT(DISTINCT CASE WHEN s.placement_status = 'PLACED' THEN s.student_id END) * 100.0) / 
            NULLIF(COUNT(DISTINCT s.student_id), 0), 2
        ) AS placement_rate_percent,
        ROUND(AVG(p.package_lpa), 2) AS avg_package,
        COALESCE(MAX(p.package_lpa), 0) AS max_package
    FROM departments d
    LEFT JOIN branches b ON d.dept_id = b.dept_id
    LEFT JOIN students s ON b.branch_id = s.branch_id
    LEFT JOIN placements p ON s.student_id = p.student_id
    GROUP BY d.dept_id, d.dept_name, d.dept_code
    ORDER BY placement_rate_percent DESC;
END$$

-- --------------------------------------------------------------------
-- 5. PROCEDURE: Atomic Candidate Selection Transaction Workflow
-- Demonstrates BEGIN, COMMIT, ROLLBACK and Error Handlers
-- --------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_process_candidate_selection$$
CREATE PROCEDURE sp_process_candidate_selection(
    IN p_application_id INT,
    IN p_package_lpa DECIMAL(10,2),
    OUT p_result VARCHAR(100)
)
proc_label: BEGIN
    DECLARE v_student_id INT;
    DECLARE v_job_id INT;
    DECLARE v_company_id INT;
    DECLARE v_user_id INT;
    DECLARE v_job_title VARCHAR(150);
    DECLARE v_company_name VARCHAR(150);
    DECLARE v_current_status VARCHAR(50);
    
    -- Error handling declaration
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_result = 'ERROR: Transaction rolled back due to SQL exception';
    END;

    -- Validate input application
    SELECT a.student_id, a.job_id, a.status, j.company_id, j.title, c.company_name, s.user_id
    INTO v_student_id, v_job_id, v_current_status, v_company_id, v_job_title, v_company_name, v_user_id
    FROM applications a
    JOIN jobs j ON a.job_id = j.job_id
    JOIN companies c ON j.company_id = c.company_id
    JOIN students s ON a.student_id = s.student_id
    WHERE a.application_id = p_application_id;

    IF v_student_id IS NULL THEN
        SET p_result = 'ERROR: Application ID not found';
        LEAVE proc_label;
    END IF;

    IF v_current_status = 'WITHDRAWN' OR v_current_status = 'REJECTED' THEN
        SET p_result = CONCAT('ERROR: Cannot select candidate in ', v_current_status, ' status');
        LEAVE proc_label;
    END IF;

    -- BEGIN ATOMIC TRANSACTION
    START TRANSACTION;

    -- Step 1: Update application status
    UPDATE applications 
    SET status = 'SELECTED', notes = CONCAT('Candidate selected with offer of ', p_package_lpa, ' LPA')
    WHERE application_id = p_application_id;

    -- Step 2: Insert or update placement record
    INSERT INTO placements (application_id, student_id, company_id, job_id, package_lpa)
    VALUES (p_application_id, v_student_id, v_company_id, v_job_id, p_package_lpa)
    ON DUPLICATE KEY UPDATE package_lpa = p_package_lpa, accepted_at = CURRENT_TIMESTAMP;

    -- Step 3: Update student's global placement status
    UPDATE students 
    SET placement_status = 'PLACED' 
    WHERE student_id = v_student_id;

    -- Step 4: Generate formal notification
    INSERT INTO notifications (user_id, title, message, link_url)
    VALUES (
        v_user_id,
        'Congratulations! Placement Offer Received',
        CONCAT('You have been selected for the position of ', v_job_title, ' at ', v_company_name, ' with a package of ₹', p_package_lpa, ' LPA!'),
        'placement.html'
    );

    -- COMMIT TRANSACTION
    COMMIT;
    SET p_result = 'SUCCESS: Candidate selected and placement confirmed';
END proc_label$$

DELIMITER ;
