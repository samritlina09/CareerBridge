<?php
/**
 * Global Dashboard Analytics & Real-Time KPIs
 * Dynamic calculation directly from MySQL 8.x
 */

require_once __DIR__ . '/../auth/session.php';
$user = getCurrentUser();
$pdo = getDatabaseConnection();

// Global Stats (Admin / General)
$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalCompanies = (int)$pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn();
$activeJobs = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'LIVE' AND deadline >= CURDATE()")->fetchColumn();
$totalApplications = (int)$pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$shortlistedStudents = (int)$pdo->query("SELECT COUNT(DISTINCT student_id) FROM applications WHERE status = 'SHORTLISTED'")->fetchColumn();
$selectedStudents = (int)$pdo->query("SELECT COUNT(DISTINCT student_id) FROM applications WHERE status = 'SELECTED'")->fetchColumn();
$totalPlacements = (int)$pdo->query("SELECT COUNT(*) FROM placements")->fetchColumn();

// Calculated Metrics
$placementRate = $totalStudents > 0 ? round(($selectedStudents * 100.0) / $totalStudents, 2) : 0.00;

$salaryStats = $pdo->query("SELECT ROUND(AVG(package_lpa), 2) AS avg_package,
                                  COALESCE(MAX(package_lpa), 0.00) AS max_package,
                                  COALESCE(MIN(package_lpa), 0.00) AS min_package
                           FROM placements")->fetch();

// Role-specific stats if Student or Recruiter
$roleSpecific = [];
if ($user && ($user['role'] ?? '') === 'STUDENT' && !empty($user['entity_id'])) {
    $sid = $user['entity_id'];
    $sApps = $pdo->prepare("SELECT COUNT(*) AS total_apps,
                                  COUNT(CASE WHEN status = 'UNDER_REVIEW' THEN 1 END) AS under_review,
                                  COUNT(CASE WHEN status = 'SHORTLISTED' THEN 1 END) AS shortlisted,
                                  COUNT(CASE WHEN status = 'INTERVIEW_SCHEDULED' THEN 1 END) AS interviews,
                                  COUNT(CASE WHEN status = 'SELECTED' THEN 1 END) AS offers
                           FROM applications WHERE student_id = :sid");
    $sApps->execute(['sid' => $sid]);
    $roleSpecific = $sApps->fetch();

    $sProfile = $pdo->prepare("SELECT cgpa, placement_status, resume_path FROM students WHERE student_id = :sid");
    $sProfile->execute(['sid' => $sid]);
    $roleSpecific['profile'] = $sProfile->fetch();

} elseif ($user && ($user['role'] ?? '') === 'RECRUITER' && !empty($user['company_id'])) {
    $cid = $user['company_id'];
    $rStats = $pdo->prepare("SELECT 
                                COUNT(DISTINCT j.job_id) AS total_jobs,
                                COUNT(DISTINCT CASE WHEN j.status = 'LIVE' THEN j.job_id END) AS active_jobs,
                                COUNT(DISTINCT a.application_id) AS total_applicants,
                                COUNT(DISTINCT CASE WHEN a.status = 'SHORTLISTED' THEN a.application_id END) AS shortlisted,
                                COUNT(DISTINCT CASE WHEN a.status = 'INTERVIEW_SCHEDULED' THEN a.application_id END) AS interviews,
                                COUNT(DISTINCT p.placement_id) AS selected_hires
                             FROM jobs j
                             LEFT JOIN applications a ON j.job_id = a.job_id
                             LEFT JOIN placements p ON j.job_id = p.job_id
                             WHERE j.company_id = :cid");
    $rStats->execute(['cid' => $cid]);
    $roleSpecific = $rStats->fetch();
}

sendSuccess('KPI Statistics loaded', [
    'global' => [
        'total_students'       => $totalStudents,
        'total_companies'      => $totalCompanies,
        'active_jobs'          => $activeJobs,
        'total_applications'   => $totalApplications,
        'shortlisted_students' => $shortlistedStudents,
        'selected_students'    => $selectedStudents,
        'total_placements'     => $totalPlacements,
        'placement_rate'       => $placementRate,
        'avg_package_lpa'      => (float)($salaryStats['avg_package'] ?? 0.00),
        'max_package_lpa'      => (float)($salaryStats['max_package'] ?? 0.00),
        'min_package_lpa'      => (float)($salaryStats['min_package'] ?? 0.00)
    ],
    'role_specific' => $roleSpecific
]);
