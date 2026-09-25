<?php
/**
 * User Login Endpoint
 * Supports STUDENT, RECRUITER, ADMIN
 */

require_once __DIR__ . '/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method Not Allowed', 405);
}

$input = getJsonInput();
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$selectedRole = trim($input['role'] ?? '');

if (empty($email) || empty($password)) {
    sendError('Please provide both username/email and password.');
}

$pdo = getDatabaseConnection();

$stmt = $pdo->prepare("SELECT u.user_id, u.email, u.password_hash, u.role, u.status 
                       FROM users u 
                       LEFT JOIN students s ON u.user_id = s.user_id 
                       WHERE u.email = :identifier OR s.roll_number = :identifier 
                       LIMIT 1");
$stmt->execute(['identifier' => $email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    sendError('Invalid username/email or password.', 401);
}

if (!empty($selectedRole) && $user['role'] !== $selectedRole) {
    sendError("Role mismatch: This account is registered as {$user['role']}. Please select the corresponding role.", 401);
}

if ($user['status'] === 'SUSPENDED') {
    sendError('Your account has been suspended. Please contact the administrator.', 403);
}

// Fetch role-specific details
$entityId = null;
$companyId = null;
$displayName = $user['email'];

if ($user['role'] === 'STUDENT') {
    $sStmt = $pdo->prepare("SELECT student_id, first_name, last_name, roll_number, placement_status FROM students WHERE user_id = :uid");
    $sStmt->execute(['uid' => $user['user_id']]);
    $student = $sStmt->fetch();
    if ($student) {
        $entityId = $student['student_id'];
        $displayName = $student['first_name'] . ' ' . $student['last_name'];
    }
} elseif ($user['role'] === 'RECRUITER') {
    $rStmt = $pdo->prepare("SELECT r.recruiter_id, r.recruiter_name, r.company_id, r.designation, r.approval_status, c.company_name 
                            FROM recruiters r 
                            JOIN companies c ON r.company_id = c.company_id 
                            WHERE r.user_id = :uid");
    $rStmt->execute(['uid' => $user['user_id']]);
    $recruiter = $rStmt->fetch();
    if ($recruiter) {
        $entityId = $recruiter['recruiter_id'];
        $companyId = $recruiter['company_id'];
        $displayName = !empty($recruiter['recruiter_name']) ? $recruiter['recruiter_name'] : ($recruiter['company_name'] . ' Recruiter');
        $_SESSION['approval_status'] = $recruiter['approval_status'];
        $_SESSION['recruiter_name'] = $recruiter['recruiter_name'] ?? null;
    }
} elseif ($user['role'] === 'ADMIN') {
    $displayName = 'T&P Administrator';
}

// Save in Session
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];
$_SESSION['status'] = $user['status'];
$_SESSION['entity_id'] = $entityId;
$_SESSION['company_id'] = $companyId;
$_SESSION['name'] = $displayName;

// Determine redirection dashboard
$redirect = 'student/dashboard.html';
if ($user['role'] === 'ADMIN') {
    $redirect = 'admin/dashboard.html';
} elseif ($user['role'] === 'RECRUITER') {
    $redirect = 'recruiter/dashboard.html';
}

sendSuccess('Login successful', [
    'user' => [
        'user_id'         => $user['user_id'],
        'email'           => $user['email'],
        'role'            => $user['role'],
        'name'            => $displayName,
        'entity_id'       => $entityId,
        'company_id'      => $companyId,
        'approval_status' => $_SESSION['approval_status'] ?? 'APPROVED',
        'recruiter_name'  => $_SESSION['recruiter_name'] ?? $displayName
    ],
    'token'    => generateAuthToken((int)$user['user_id'], $user['role'], $entityId),
    'redirect' => $redirect
]);
