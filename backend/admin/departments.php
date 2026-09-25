<?php
/**
 * Admin Departments & Branches Management Endpoint
 */

require_once __DIR__ . '/../auth/session.php';
$admin = requireAdmin();
$pdo = getDatabaseConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $depts = $pdo->query("SELECT d.*, 
                                 COUNT(DISTINCT b.branch_id) AS total_branches,
                                 COUNT(DISTINCT s.student_id) AS total_students
                          FROM departments d
                          LEFT JOIN branches b ON d.dept_id = b.dept_id
                          LEFT JOIN students s ON b.branch_id = s.branch_id
                          GROUP BY d.dept_id
                          ORDER BY d.dept_name")->fetchAll();

    $branches = $pdo->query("SELECT b.*, d.dept_name, d.dept_code,
                                    COUNT(DISTINCT s.student_id) AS student_count
                             FROM branches b
                             JOIN departments d ON b.dept_id = d.dept_id
                             LEFT JOIN students s ON b.branch_id = s.branch_id
                             GROUP BY b.branch_id
                             ORDER BY d.dept_name, b.branch_name")->fetchAll();

    sendSuccess('Academic structure loaded', [
        'departments' => $depts,
        'branches'    => $branches
    ]);

} elseif ($method === 'POST') {
    $input = getJsonInput();
    $type = $input['type'] ?? 'branch';

    if ($type === 'department') {
        $deptName = trim($input['dept_name'] ?? '');
        $deptCode = strtoupper(trim($input['dept_code'] ?? ''));

        if (empty($deptName) || empty($deptCode)) {
            sendError('Department name and code are required.');
        }

        $ins = $pdo->prepare("INSERT INTO departments (dept_name, dept_code) VALUES (:name, :code)");
        $ins->execute(['name' => $deptName, 'code' => $deptCode]);
        sendSuccess('Department added successfully.');

    } elseif ($type === 'branch') {
        $deptId = !empty($input['dept_id']) ? (int)$input['dept_id'] : null;
        $branchName = trim($input['branch_name'] ?? '');
        $branchCode = strtoupper(trim($input['branch_code'] ?? ''));
        $degreeType = $input['degree_type'] ?? 'B.Tech';

        if (empty($deptId) || empty($branchName) || empty($branchCode)) {
            sendError('Department, branch name, and branch code are required.');
        }

        $ins = $pdo->prepare("INSERT INTO branches (dept_id, branch_name, branch_code, degree_type) VALUES (:did, :name, :code, :deg)");
        $ins->execute(['did' => $deptId, 'name' => $branchName, 'code' => $branchCode, 'deg' => $degreeType]);
        sendSuccess('Branch added successfully.');
    } else {
        sendError('Invalid type specified.');
    }
} else {
    sendError('Method Not Allowed', 405);
}
