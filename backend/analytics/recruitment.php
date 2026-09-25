<?php
/**
 * Recruitment & Company Hiring Analytics Endpoint
 */

require_once __DIR__ . '/../auth/session.php';
$user = getCurrentUser();
$pdo = getDatabaseConnection();

// 1. Top Hiring Companies by Placed Candidates
$topCompanies = $pdo->query("SELECT c.company_name, c.industry,
                                    COUNT(DISTINCT j.job_id) AS jobs_posted,
                                    COUNT(DISTINCT a.application_id) AS total_applicants,
                                    COUNT(DISTINCT p.placement_id) AS hired_students,
                                    ROUND(AVG(p.package_lpa), 2) AS avg_package
                             FROM companies c
                             LEFT JOIN jobs j ON c.company_id = j.company_id
                             LEFT JOIN applications a ON j.job_id = a.job_id
                             LEFT JOIN placements p ON c.company_id = p.company_id
                             GROUP BY c.company_id, c.company_name, c.industry
                             ORDER BY hired_students DESC, total_applicants DESC
                             LIMIT 10")->fetchAll();

// 2. Recruitment Funnel Conversion (Applied -> Under Review -> Shortlisted -> Interview -> Selected)
$funnel = $pdo->query("SELECT 
    COUNT(*) AS total_applied,
    COUNT(CASE WHEN status IN ('UNDER_REVIEW', 'SHORTLISTED', 'INTERVIEW_SCHEDULED', 'SELECTED') THEN 1 END) AS under_review,
    COUNT(CASE WHEN status IN ('SHORTLISTED', 'INTERVIEW_SCHEDULED', 'SELECTED') THEN 1 END) AS shortlisted,
    COUNT(CASE WHEN status IN ('INTERVIEW_SCHEDULED', 'SELECTED') THEN 1 END) AS interview_stage,
    COUNT(CASE WHEN status = 'SELECTED' THEN 1 END) AS selected,
    COUNT(CASE WHEN status = 'REJECTED' THEN 1 END) AS rejected,
    COUNT(CASE WHEN status = 'WITHDRAWN' THEN 1 END) AS withdrawn
FROM applications")->fetch();

sendSuccess('Recruitment Analytics', [
    'top_companies' => $topCompanies,
    'funnel'        => $funnel
]);
