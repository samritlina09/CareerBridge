<?php
/**
 * Admin Dynamic Reports & CSV Export Engine
 */

require_once __DIR__ . '/../auth/session.php';
$admin = requireAdmin();
$pdo = getDatabaseConnection();

$type = $_GET['type'] ?? 'placements';
$format = $_GET['format'] ?? 'json'; // json or csv

if ($type === 'placements') {
    $stmt = $pdo->query("SELECT p.placement_id, s.roll_number, CONCAT(s.first_name, ' ', s.last_name) AS student_name,
                                s.cgpa, b.branch_code, d.dept_name, c.company_name, j.title AS job_role,
                                p.package_lpa, p.accepted_at
                         FROM placements p
                         JOIN students s ON p.student_id = s.student_id
                         LEFT JOIN branches b ON s.branch_id = b.branch_id
                         LEFT JOIN departments d ON b.dept_id = d.dept_id
                         JOIN companies c ON p.company_id = c.company_id
                         JOIN jobs j ON p.job_id = j.job_id
                         ORDER BY p.package_lpa DESC");
    $data = $stmt->fetchAll();
    $filename = "placements_report_" . date('Y-m-d') . ".csv";

} elseif ($type === 'students') {
    $stmt = $pdo->query("SELECT s.roll_number, CONCAT(s.first_name, ' ', s.last_name) AS full_name,
                                u.email, s.phone, s.gender, s.cgpa, s.tenth_percent, s.twelfth_percent,
                                b.branch_code, d.dept_name, s.placement_status
                         FROM students s
                         JOIN users u ON s.user_id = u.user_id
                         LEFT JOIN branches b ON s.branch_id = b.branch_id
                         LEFT JOIN departments d ON b.dept_id = d.dept_id
                         ORDER BY s.roll_number");
    $data = $stmt->fetchAll();
    $filename = "students_report_" . date('Y-m-d') . ".csv";

} elseif ($type === 'applications') {
    $stmt = $pdo->query("SELECT a.application_id, s.roll_number, CONCAT(s.first_name, ' ', s.last_name) AS student_name,
                                j.title AS job_title, c.company_name, a.status AS application_status, a.applied_at
                         FROM applications a
                         JOIN students s ON a.student_id = s.student_id
                         JOIN jobs j ON a.job_id = j.job_id
                         JOIN companies c ON j.company_id = c.company_id
                         ORDER BY a.applied_at DESC");
    $data = $stmt->fetchAll();
    $filename = "applications_report_" . date('Y-m-d') . ".csv";

} elseif ($type === 'departments') {
    $stmt = $pdo->query("SELECT * FROM vw_department_placement_summary");
    $data = $stmt->fetchAll();
    $filename = "department_placement_report_" . date('Y-m-d') . ".csv";

} else {
    sendError('Invalid report type.');
}

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    } else {
        $defaultHeaders = [
            'placements'   => ['placement_id', 'roll_number', 'student_name', 'cgpa', 'branch_code', 'dept_name', 'company_name', 'job_role', 'package_lpa', 'accepted_at'],
            'students'     => ['roll_number', 'full_name', 'email', 'phone', 'gender', 'cgpa', 'tenth_percent', 'twelfth_percent', 'branch_code', 'dept_name', 'placement_status'],
            'applications' => ['application_id', 'roll_number', 'student_name', 'job_title', 'company_name', 'application_status', 'applied_at'],
            'departments'  => ['dept_id', 'dept_code', 'dept_name', 'total_students', 'placed_students', 'placement_percentage', 'avg_package_lpa', 'max_package_lpa']
        ];
        if (isset($defaultHeaders[$type])) {
            fputcsv($output, $defaultHeaders[$type]);
        }
    }
    fclose($output);
    exit;
} else {
    sendSuccess('Report generated', [
        'type'  => $type,
        'count' => count($data),
        'data'  => $data
    ]);
}
