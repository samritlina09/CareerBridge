<?php
/**
 * Admin Student Management Endpoint
 */

require_once __DIR__ . '/../auth/session.php';
$admin = requireAdmin();
$pdo = getDatabaseConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $studentId = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM vw_student_profiles_complete WHERE student_id = :sid");
        $stmt->execute(['sid' => $studentId]);
        $student = $stmt->fetch();

        if (!$student) {
            sendError('Student not found.', 404);
        }

        // Skills
        $sk = $pdo->prepare("SELECT ss.proficiency_level, s.skill_name, s.category FROM student_skills ss JOIN skills s ON ss.skill_id = s.skill_id WHERE ss.student_id = :sid");
        $sk->execute(['sid' => $studentId]);
        $student['skills'] = $sk->fetchAll();

        // Projects
        $pr = $pdo->prepare("SELECT * FROM projects WHERE student_id = :sid");
        $pr->execute(['sid' => $studentId]);
        $student['projects'] = $pr->fetchAll();

        // Certifications
        $cr = $pdo->prepare("SELECT * FROM certifications WHERE student_id = :sid");
        $cr->execute(['sid' => $studentId]);
        $student['certifications'] = $cr->fetchAll();

        // Applications
        $ap = $pdo->prepare("SELECT a.application_id, a.status, a.applied_at, j.title AS job_title, c.company_name, p.package_lpa
                             FROM applications a
                             JOIN jobs j ON a.job_id = j.job_id
                             JOIN companies c ON j.company_id = c.company_id
                             LEFT JOIN placements p ON a.application_id = p.application_id
                             WHERE a.student_id = :sid ORDER BY a.applied_at DESC");
        $ap->execute(['sid' => $studentId]);
        $student['applications'] = $ap->fetchAll();

        sendSuccess('Student details', ['student' => $student]);
    }

    // List query
    $deptId = !empty($_GET['dept_id']) ? (int)$_GET['dept_id'] : null;
    $branchId = !empty($_GET['branch_id']) ? (int)$_GET['branch_id'] : null;
    $placementStatus = trim($_GET['status'] ?? '');
    $query = trim($_GET['q'] ?? '');

    $sql = "SELECT * FROM vw_student_profiles_complete WHERE 1=1";
    $params = [];

    if ($deptId) {
        $sql .= " AND dept_id = :did";
        $params['did'] = $deptId;
    }
    if ($branchId) {
        $sql .= " AND branch_id = :bid";
        $params['bid'] = $branchId;
    }
    if (!empty($placementStatus)) {
        $sql .= " AND placement_status = :status";
        $params['status'] = $placementStatus;
    }
    if (!empty($query)) {
        $sql .= " AND (full_name LIKE :q OR roll_number LIKE :q OR email LIKE :q)";
        $params['q'] = "%{$query}%";
    }

    $sql .= " ORDER BY cgpa DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();

    $depts = $pdo->query("SELECT dept_id, dept_name, dept_code FROM departments ORDER BY dept_name")->fetchAll();
    $branches = $pdo->query("SELECT branch_id, branch_name, branch_code FROM branches ORDER BY branch_name")->fetchAll();

    sendSuccess('Students loaded', [
        'count'       => count($students),
        'students'    => $students,
        'departments' => $depts,
        'branches'    => $branches
    ]);

} elseif ($method === 'POST') {
    $input = getJsonInput();
    $studentId = !empty($input['student_id']) ? (int)$input['student_id'] : null;
    $status = strtoupper(trim($input['placement_status'] ?? ''));

    if (empty($studentId) || !in_array($status, ['NOT_PLACED', 'PLACED', 'OPTED_OUT'], true)) {
        sendError('Student ID and valid placement status required.');
    }

    $upd = $pdo->prepare("UPDATE students SET placement_status = :st WHERE student_id = :sid");
    $upd->execute(['st' => $status, 'sid' => $studentId]);

    sendSuccess('Student placement status updated.');
} else {
    sendError('Method Not Allowed', 405);
}
