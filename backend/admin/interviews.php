<?php
/**
 * Admin Central Interviews Schedule Endpoint
 */

require_once __DIR__ . '/../auth/session.php';
$admin = requireAdmin();
$pdo = getDatabaseConnection();

$stmt = $pdo->query("SELECT i.*, a.status AS app_status,
                            j.title AS job_title, c.company_name,
                            s.roll_number, CONCAT(s.first_name, ' ', s.last_name) AS student_name, s.phone,
                            b.branch_code
                     FROM interviews i
                     JOIN applications a ON i.application_id = a.application_id
                     JOIN jobs j ON a.job_id = j.job_id
                     JOIN companies c ON j.company_id = c.company_id
                     JOIN students s ON a.student_id = s.student_id
                     LEFT JOIN branches b ON s.branch_id = b.branch_id
                     ORDER BY i.scheduled_at DESC");
$interviews = $stmt->fetchAll();

sendSuccess('Interviews loaded', [
    'count'      => count($interviews),
    'interviews' => $interviews
]);
