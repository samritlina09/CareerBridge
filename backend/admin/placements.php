<?php
/**
 * Admin Placements Overview Endpoint
 */

require_once __DIR__ . '/../auth/session.php';
$admin = requireAdmin();
$pdo = getDatabaseConnection();

$stmt = $pdo->query("SELECT p.placement_id, p.package_lpa, p.accepted_at, p.offer_letter_path,
                            s.student_id, s.roll_number, CONCAT(s.first_name, ' ', s.last_name) AS student_name, s.cgpa,
                            u.email AS student_email,
                            b.branch_code, d.dept_name,
                            c.company_name, c.logo_path,
                            j.title AS job_title, j.job_type
                     FROM placements p
                     JOIN students s ON p.student_id = s.student_id
                     JOIN users u ON s.user_id = u.user_id
                     LEFT JOIN branches b ON s.branch_id = b.branch_id
                     LEFT JOIN departments d ON b.dept_id = d.dept_id
                     JOIN companies c ON p.company_id = c.company_id
                     JOIN jobs j ON p.job_id = j.job_id
                     ORDER BY p.package_lpa DESC, p.accepted_at DESC");
$placements = $stmt->fetchAll();

$stats = $pdo->query("SELECT COUNT(*) AS total_placed,
                             ROUND(AVG(package_lpa), 2) AS avg_package,
                             MAX(package_lpa) AS highest_package,
                             MIN(package_lpa) AS lowest_package
                      FROM placements")->fetch();

sendSuccess('Placements loaded', [
    'count'      => count($placements),
    'placements' => $placements,
    'stats'      => $stats
]);
