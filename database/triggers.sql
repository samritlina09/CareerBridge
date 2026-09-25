-- ====================================================================
-- DATABASE TRIGGERS (Automated Business Rules & Event Auditing)
-- ====================================================================

USE placement_management;

DELIMITER $$

-- --------------------------------------------------------------------
-- 1. TRIGGER: Prevent applying to non-live or expired jobs
-- --------------------------------------------------------------------
DROP TRIGGER IF EXISTS trg_prevent_application_on_closed_job$$
CREATE TRIGGER trg_prevent_application_on_closed_job
BEFORE INSERT ON applications
FOR EACH ROW
BEGIN
    DECLARE v_job_status VARCHAR(20);
    DECLARE v_deadline DATE;

    SELECT status, deadline INTO v_job_status, v_deadline
    FROM jobs WHERE job_id = NEW.job_id;

    IF v_job_status NOT IN ('LIVE', 'APPROVED') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Application Rejected: Job posting is not currently LIVE or approved by Admin.';
    END IF;

    IF v_deadline < CURDATE() THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Application Rejected: The deadline for this job posting has expired.';
    END IF;
END$$

-- --------------------------------------------------------------------
-- 2. TRIGGER: Auto-generate student notification on application status change
-- --------------------------------------------------------------------
DROP TRIGGER IF EXISTS trg_application_status_notification$$
CREATE TRIGGER trg_application_status_notification
AFTER UPDATE ON applications
FOR EACH ROW
BEGIN
    DECLARE v_user_id INT;
    DECLARE v_job_title VARCHAR(150);
    DECLARE v_company_name VARCHAR(150);
    DECLARE v_status_msg TEXT;

    IF OLD.status != NEW.status THEN
        -- Fetch student user_id and job details
        SELECT s.user_id, j.title, c.company_name
        INTO v_user_id, v_job_title, v_company_name
        FROM students s
        JOIN jobs j ON j.job_id = NEW.job_id
        JOIN companies c ON j.company_id = c.company_id
        WHERE s.student_id = NEW.student_id;

        -- Format custom message based on new status
        CASE NEW.status
            WHEN 'UNDER_REVIEW' THEN
                SET v_status_msg = CONCAT('Your application for ', v_job_title, ' at ', v_company_name, ' is now under review.');
            WHEN 'SHORTLISTED' THEN
                SET v_status_msg = CONCAT('Congratulations! You have been shortlisted for ', v_job_title, ' at ', v_company_name, '.');
            WHEN 'INTERVIEW_SCHEDULED' THEN
                SET v_status_msg = CONCAT('An interview has been scheduled for your application to ', v_job_title, ' at ', v_company_name, '. Check your interview schedule.');
            WHEN 'SELECTED' THEN
                SET v_status_msg = CONCAT('Fantastic news! You have been selected for ', v_job_title, ' at ', v_company_name, '!');
            WHEN 'REJECTED' THEN
                SET v_status_msg = CONCAT('Update regarding ', v_job_title, ' at ', v_company_name, ': Your application was not selected at this time.');
            WHEN 'WITHDRAWN' THEN
                SET v_status_msg = CONCAT('You have withdrawn your application for ', v_job_title, ' at ', v_company_name, '.');
            ELSE
                SET v_status_msg = CONCAT('Status updated to ', NEW.status, ' for ', v_job_title, ' at ', v_company_name, '.');
        END CASE;

        INSERT INTO notifications (user_id, title, message, link_url)
        VALUES (
            v_user_id,
            CONCAT('Application Update: ', NEW.status),
            v_status_msg,
            'applications.html'
        );
    END IF;
END$$

-- --------------------------------------------------------------------
-- 3. TRIGGER: Auto-update student placement_status when placed
-- --------------------------------------------------------------------
DROP TRIGGER IF EXISTS trg_auto_update_student_status_on_placement$$
CREATE TRIGGER trg_auto_update_student_status_on_placement
AFTER INSERT ON placements
FOR EACH ROW
BEGIN
    UPDATE students 
    SET placement_status = 'PLACED'
    WHERE student_id = NEW.student_id;
END$$

-- --------------------------------------------------------------------
-- 4. TRIGGER: Notify Recruiter upon Admin approval/rejection
-- --------------------------------------------------------------------
DROP TRIGGER IF EXISTS trg_recruiter_status_notification$$
CREATE TRIGGER trg_recruiter_status_notification
AFTER UPDATE ON recruiters
FOR EACH ROW
BEGIN
    IF OLD.approval_status != NEW.approval_status THEN
        IF NEW.approval_status = 'APPROVED' THEN
            INSERT INTO notifications (user_id, title, message, link_url)
            VALUES (
                NEW.user_id,
                'Recruiter Account Approved',
                'Your company recruiter account has been approved by the T&P Cell! You can now post jobs and review applicants.',
                'dashboard.html'
            );
        ELSEIF NEW.approval_status = 'REJECTED' THEN
            INSERT INTO notifications (user_id, title, message, link_url)
            VALUES (
                NEW.user_id,
                'Recruiter Registration Status',
                'Your recruiter account registration was not approved. Please contact the Placement Cell for assistance.',
                'profile.html'
            );
        END IF;
    END IF;
END$$

-- --------------------------------------------------------------------
-- 5. TRIGGER: Notify Recruiter when a new application is submitted
-- --------------------------------------------------------------------
DROP TRIGGER IF EXISTS trg_new_application_recruiter_notif$$
CREATE TRIGGER trg_new_application_recruiter_notif
AFTER INSERT ON applications
FOR EACH ROW
BEGIN
    DECLARE v_recruiter_user_id INT;
    DECLARE v_job_title VARCHAR(150);
    DECLARE v_student_name VARCHAR(100);

    -- Find recruiter user_id for the job
    SELECT r.user_id, j.title, CONCAT(s.first_name, ' ', s.last_name)
    INTO v_recruiter_user_id, v_job_title, v_student_name
    FROM jobs j
    LEFT JOIN recruiters r ON j.posted_by_recruiter_id = r.recruiter_id
    JOIN students s ON s.student_id = NEW.student_id
    WHERE j.job_id = NEW.job_id;

    IF v_recruiter_user_id IS NOT NULL THEN
        INSERT INTO notifications (user_id, title, message, link_url)
        VALUES (
            v_recruiter_user_id,
            'New Job Application Received',
            CONCAT(v_student_name, ' has submitted an application for ', v_job_title, '.'),
            'applicants.html'
        );
    END IF;
END$$

DELIMITER ;
