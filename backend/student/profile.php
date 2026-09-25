<?php
/**
 * Student Profile Management Endpoint
 */

require_once __DIR__ . '/../auth/session.php';
$user = requireStudent();
$studentId = $user['entity_id'];
$pdo = getDatabaseConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Fetch full profile info
    $stmt = $pdo->prepare("SELECT s.*, u.email, b.branch_name, b.branch_code, d.dept_name, d.dept_code
                           FROM students s
                           JOIN users u ON s.user_id = u.user_id
                           LEFT JOIN branches b ON s.branch_id = b.branch_id
                           LEFT JOIN departments d ON b.dept_id = d.dept_id
                           WHERE s.student_id = :sid");
    $stmt->execute(['sid' => $studentId]);
    $profile = $stmt->fetch();

    if (!$profile) {
        sendError('Student record not found.', 404);
    }

    // Counts for completion calculation
    $cSkill = $pdo->prepare("SELECT COUNT(*) FROM student_skills WHERE student_id = :sid");
    $cSkill->execute(['sid' => $studentId]);
    $skillCount = (int)$cSkill->fetchColumn();

    $cProj = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE student_id = :sid");
    $cProj->execute(['sid' => $studentId]);
    $projCount = (int)$cProj->fetchColumn();

    $cCert = $pdo->prepare("SELECT COUNT(*) FROM certifications WHERE student_id = :sid");
    $cCert->execute(['sid' => $studentId]);
    $certCount = (int)$cCert->fetchColumn();

    // Profile Completion Percentage Heuristic
    $completion = 20; // base profile created
    if (!empty($profile['phone'])) $completion += 10;
    if (!empty($profile['address'])) $completion += 10;
    if (!empty($profile['dob']) && !empty($profile['gender'])) $completion += 10;
    if ($profile['cgpa'] > 0) $completion += 15;
    if ($skillCount >= 3) $completion += 15;
    if ($projCount >= 1) $completion += 10;
    if (!empty($profile['resume_path'])) $completion += 10;

    $completion = min(100, $completion);

    // Fetch branches for dropdown
    $bList = $pdo->query("SELECT b.branch_id, b.branch_name, b.branch_code, d.dept_name 
                          FROM branches b 
                          JOIN departments d ON b.dept_id = d.dept_id 
                          ORDER BY d.dept_name, b.branch_name")->fetchAll();

    sendSuccess('Profile loaded', [
        'profile'            => $profile,
        'completion_percent' => $completion,
        'skills_count'       => $skillCount,
        'projects_count'     => $projCount,
        'certs_count'        => $certCount,
        'branches'           => $bList
    ]);

} elseif ($method === 'POST') {
    $input = getJsonInput();

    $firstName = trim($input['first_name'] ?? '');
    $lastName = trim($input['last_name'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $dob = !empty($input['dob']) ? $input['dob'] : null;
    $gender = !empty($input['gender']) ? $input['gender'] : null;
    $address = trim($input['address'] ?? '');
    $branchId = !empty($input['branch_id']) ? (int)$input['branch_id'] : null;
    $currentYear = !empty($input['current_year']) ? (int)$input['current_year'] : 4;
    $cgpa = isset($input['cgpa']) ? (float)$input['cgpa'] : 0.00;
    $tenthPercent = isset($input['tenth_percent']) ? (float)$input['tenth_percent'] : 0.00;
    $twelfthPercent = isset($input['twelfth_percent']) ? (float)$input['twelfth_percent'] : 0.00;

    if (empty($firstName) || empty($lastName)) {
        sendError('First and last name are required.');
    }

    $update = $pdo->prepare("UPDATE students SET
        first_name = :fname,
        last_name = :lname,
        phone = :phone,
        dob = :dob,
        gender = :gender,
        address = :addr,
        branch_id = :bid,
        current_year = :yr,
        cgpa = :cgpa,
        tenth_percent = :tenth,
        twelfth_percent = :twelfth
        WHERE student_id = :sid");

    $update->execute([
        'fname'   => $firstName,
        'lname'   => $lastName,
        'phone'   => $phone,
        'dob'     => $dob,
        'gender'  => $gender,
        'addr'    => $address,
        'bid'     => $branchId,
        'yr'      => $currentYear,
        'cgpa'    => $cgpa,
        'tenth'   => $tenthPercent,
        'twelfth' => $twelfthPercent,
        'sid'     => $studentId
    ]);

    sendSuccess('Profile updated successfully.');
} else {
    sendError('Method Not Allowed', 405);
}
