-- ====================================================================
-- STORED FUNCTIONS (Calculations & Metrics)
-- ====================================================================

USE placement_management;

DELIMITER $$

-- --------------------------------------------------------------------
-- 1. FUNCTION: Calculate Placement Rate (Department or Overall)
-- --------------------------------------------------------------------
DROP FUNCTION IF EXISTS fn_calculate_placement_rate$$
CREATE FUNCTION fn_calculate_placement_rate(p_dept_id INT) 
RETURNS DECIMAL(5,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_total_students INT DEFAULT 0;
    DECLARE v_placed_students INT DEFAULT 0;
    DECLARE v_rate DECIMAL(5,2) DEFAULT 0.00;

    IF p_dept_id IS NULL OR p_dept_id = 0 THEN
        -- Global placement rate
        SELECT COUNT(*), COUNT(CASE WHEN placement_status = 'PLACED' THEN 1 END)
        INTO v_total_students, v_placed_students
        FROM students;
    ELSE
        -- Department specific placement rate
        SELECT 
            COUNT(s.student_id),
            COUNT(CASE WHEN s.placement_status = 'PLACED' THEN 1 END)
        INTO v_total_students, v_placed_students
        FROM students s
        JOIN branches b ON s.branch_id = b.branch_id
        WHERE b.dept_id = p_dept_id;
    END IF;

    IF v_total_students > 0 THEN
        SET v_rate = ROUND((v_placed_students * 100.0) / v_total_students, 2);
    END IF;

    RETURN v_rate;
END$$

-- --------------------------------------------------------------------
-- 2. FUNCTION: Calculate Application Success Rate for a Student
-- --------------------------------------------------------------------
DROP FUNCTION IF EXISTS fn_student_application_success_rate$$
CREATE FUNCTION fn_student_application_success_rate(p_student_id INT)
RETURNS DECIMAL(5,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_total_apps INT DEFAULT 0;
    DECLARE v_success_apps INT DEFAULT 0;
    DECLARE v_rate DECIMAL(5,2) DEFAULT 0.00;

    SELECT 
        COUNT(*),
        COUNT(CASE WHEN status = 'SELECTED' THEN 1 END)
    INTO v_total_apps, v_success_apps
    FROM applications
    WHERE student_id = p_student_id;

    IF v_total_apps > 0 THEN
        SET v_rate = ROUND((v_success_apps * 100.0) / v_total_apps, 2);
    END IF;

    RETURN v_rate;
END$$

-- --------------------------------------------------------------------
-- 3. FUNCTION: SQL-based Heuristic Match Score between Student & Job
-- (Calculates composite 0-100% score based on skills, CGPA & branch)
-- --------------------------------------------------------------------
DROP FUNCTION IF EXISTS fn_calculate_student_match_score$$
CREATE FUNCTION fn_calculate_student_match_score(p_student_id INT, p_job_id INT)
RETURNS DECIMAL(5,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_cgpa DECIMAL(3,2);
    DECLARE v_branch_id INT;
    DECLARE v_min_cgpa DECIMAL(3,2);
    DECLARE v_total_req_skills INT DEFAULT 0;
    DECLARE v_matched_skills INT DEFAULT 0;
    DECLARE v_branch_eligible INT DEFAULT 0;
    
    DECLARE v_skill_score DECIMAL(5,2) DEFAULT 0.00;
    DECLARE v_cgpa_score DECIMAL(5,2) DEFAULT 0.00;
    DECLARE v_branch_score DECIMAL(5,2) DEFAULT 0.00;
    DECLARE v_final_score DECIMAL(5,2) DEFAULT 0.00;

    -- Get student parameters
    SELECT cgpa, branch_id INTO v_cgpa, v_branch_id
    FROM students WHERE student_id = p_student_id;

    -- Get job parameters
    SELECT min_cgpa INTO v_min_cgpa
    FROM jobs WHERE job_id = p_job_id;

    -- 1. Skill overlap calculation (Weight: 50%)
    SELECT COUNT(*) INTO v_total_req_skills
    FROM job_skills WHERE job_id = p_job_id;

    IF v_total_req_skills > 0 THEN
        SELECT COUNT(*) INTO v_matched_skills
        FROM job_skills js
        JOIN student_skills ss ON js.skill_id = ss.skill_id
        WHERE js.job_id = p_job_id AND ss.student_id = p_student_id;

        SET v_skill_score = (v_matched_skills / v_total_req_skills) * 50.0;
    ELSE
        SET v_skill_score = 50.00; -- If no specific skills required, full skill points
    END IF;

    -- 2. CGPA score calculation (Weight: 30%)
    IF v_min_cgpa > 0 THEN
        IF v_cgpa >= v_min_cgpa THEN
            SET v_cgpa_score = 30.00;
        ELSE
            SET v_cgpa_score = (v_cgpa / v_min_cgpa) * 20.0; -- penalized
        END IF;
    ELSE
        SET v_cgpa_score = 30.00;
    END IF;

    -- 3. Branch match calculation (Weight: 20%)
    SELECT COUNT(*) INTO v_branch_eligible
    FROM job_eligible_branches
    WHERE job_id = p_job_id AND branch_id = v_branch_id;

    -- If no branches specified, all branches are eligible
    IF (SELECT COUNT(*) FROM job_eligible_branches WHERE job_id = p_job_id) = 0 OR v_branch_eligible > 0 THEN
        SET v_branch_score = 20.00;
    ELSE
        SET v_branch_score = 5.00;
    END IF;

    SET v_final_score = ROUND(LEAST(100.00, GREATEST(0.00, v_skill_score + v_cgpa_score + v_branch_score)), 2);
    RETURN v_final_score;
END$$

DELIMITER ;
