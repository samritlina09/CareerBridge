<?php
/**
 * Placement Analytics Endpoint
 * Department distributions, salary brackets, and branch conversion metrics
 */

require_once __DIR__ . '/../auth/session.php';
$user = requireLogin();
$pdo = getDatabaseConnection();

// 1. Department Placement Summary from View
$deptSummary = $pdo->query("SELECT * FROM vw_department_placement_summary ORDER BY placement_percentage DESC")->fetchAll();

// 2. Branch Level Placement Breakdown
$branchStats = $pdo->query("SELECT b.branch_code, b.branch_name, d.dept_code,
                                   COUNT(DISTINCT s.student_id) AS total_students,
                                   COUNT(DISTINCT CASE WHEN s.placement_status = 'PLACED' THEN s.student_id END) AS placed_students,
                                   ROUND((COUNT(DISTINCT CASE WHEN s.placement_status = 'PLACED' THEN s.student_id END) * 100.0) / NULLIF(COUNT(DISTINCT s.student_id), 0), 2) AS placement_rate,
                                   ROUND(AVG(p.package_lpa), 2) AS avg_package
                            FROM branches b
                            JOIN departments d ON b.dept_id = d.dept_id
                            LEFT JOIN students s ON b.branch_id = s.branch_id
                            LEFT JOIN placements p ON s.student_id = p.student_id
                            GROUP BY b.branch_id, b.branch_code, b.branch_name, d.dept_code
                            ORDER BY placement_rate DESC")->fetchAll();

// 3. Salary Brackets Distribution for Chart.js Bar / Pie Chart
$salaryBrackets = $pdo->query("SELECT 
    CASE 
        WHEN package_lpa < 6.0 THEN '< 6 LPA'
        WHEN package_lpa BETWEEN 6.0 AND 10.0 THEN '6 - 10 LPA'
        WHEN package_lpa BETWEEN 10.01 AND 18.0 THEN '10 - 18 LPA'
        WHEN package_lpa BETWEEN 18.01 AND 25.0 THEN '18 - 25 LPA'
        ELSE '> 25 LPA (Super Dream)'
    END AS salary_range,
    COUNT(*) AS count
FROM placements
GROUP BY salary_range
ORDER BY MIN(package_lpa) ASC")->fetchAll();

sendSuccess('Placement Analytics', [
    'department_summary' => $deptSummary,
    'branch_stats'       => $branchStats,
    'salary_brackets'    => $salaryBrackets
]);
