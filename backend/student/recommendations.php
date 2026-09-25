<?php
/**
 * Skill-Based Job Recommendations Endpoint
 * Uses MySQL criteria matching function (skills, branch, and CGPA fit).
 */

require_once __DIR__ . '/../auth/session.php';
$user = requireStudent();
$studentId = $user['entity_id'];
$pdo = getDatabaseConnection();

$methodUsed = 'Database Criteria Matching (Skills, CGPA, Branch Matrix)';

// Fetch live jobs ranked by student profile match score
$sql = "SELECT j.job_id, j.title, j.job_type, j.work_mode, j.location, j.min_cgpa, 
               j.salary_stipend, j.experience_req, j.deadline,
               c.company_name, c.logo_path, c.industry,
               COALESCE(fn_calculate_student_match_score(:sid, j.job_id), 50.00) AS match_score,
               GROUP_CONCAT(DISTINCT s.skill_name SEPARATOR ', ') AS required_skills,
               (
                   SELECT GROUP_CONCAT(DISTINCT sk2.skill_name SEPARATOR ', ')
                   FROM job_skills js2
                   JOIN student_skills ss2 ON js2.skill_id = ss2.skill_id
                   JOIN skills sk2 ON js2.skill_id = sk2.skill_id
                   WHERE js2.job_id = j.job_id AND ss2.student_id = :sid
               ) AS matched_skills,
               (SELECT a.status FROM applications a WHERE a.job_id = j.job_id AND a.student_id = :sid) AS application_status
        FROM jobs j
        JOIN companies c ON j.company_id = c.company_id
        LEFT JOIN recruiters r ON j.posted_by_recruiter_id = r.recruiter_id
        LEFT JOIN job_skills js ON j.job_id = js.job_id
        LEFT JOIN skills s ON js.skill_id = s.skill_id
        WHERE j.status = 'LIVE' AND j.deadline >= CURDATE()
          AND c.is_verified = 1 AND (j.posted_by_recruiter_id IS NULL OR r.approval_status = 'APPROVED')
        GROUP BY j.job_id, c.company_name, c.logo_path, c.industry
        ORDER BY match_score DESC, j.salary_stipend DESC";
        
$stmt = $pdo->prepare($sql);
$stmt->execute(['sid' => $studentId]);
$recs = $stmt->fetchAll();

sendSuccess('Job recommendations retrieved successfully', [
    'recommendations' => $recs,
    'engine_used'     => $methodUsed,
    'student_id'      => $studentId
]);
