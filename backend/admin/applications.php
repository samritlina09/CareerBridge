<?php
/**
 * Admin Central Applications Management Endpoint
 */

require_once __DIR__ . '/../auth/session.php';
$admin = requireAdmin();
$pdo = getDatabaseConnection();

$status = trim($_GET['status'] ?? '');
$companyId = !empty($_GET['company_id']) ? (int)$_GET['company_id'] : null;
$jobId = !empty($_GET['job_id']) ? (int)$_GET['job_id'] : null;

$sql = "SELECT a.application_id, a.applied_at, a.status, a.notes,
               s.student_id, s.roll_number, CONCAT(s.first_name, ' ', s.last_name) AS student_name, s.cgpa,
               b.branch_code, d.dept_name,
               j.job_id, j.title AS job_title, j.salary_stipend, j.job_type,
               c.company_id, c.company_name,
               p.placement_id, p.package_lpa
        FROM applications a
        JOIN students s ON a.student_id = s.student_id
        LEFT JOIN branches b ON s.branch_id = b.branch_id
        LEFT JOIN departments d ON b.dept_id = d.dept_id
        JOIN jobs j ON a.job_id = j.job_id
        JOIN companies c ON j.company_id = c.company_id
        LEFT JOIN placements p ON a.application_id = p.application_id
        WHERE 1=1";

$params = [];
if (!empty($status)) {
    $sql .= " AND a.status = :status";
    $params['status'] = $status;
}
if ($companyId) {
    $sql .= " AND j.company_id = :cid";
    $params['cid'] = $companyId;
}
if ($jobId) {
    $sql .= " AND a.job_id = :jid";
    $params['jid'] = $jobId;
}

$sql .= " ORDER BY a.applied_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$apps = $stmt->fetchAll();

sendSuccess('Applications loaded', [
    'count'        => count($apps),
    'applications' => $apps
]);
