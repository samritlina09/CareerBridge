<?php
/**
 * Direct Portal Auto-Login Endpoint
 * Allows instant, 1-click access to Student, Recruiter, and Admin portals without login forms.
 */

require_once __DIR__ . '/session.php';

$role = strtoupper(trim($_GET['role'] ?? ''));
if (!in_array($role, ['STUDENT', 'RECRUITER', 'ADMIN'], true)) {
    sendError('Invalid portal role requested.');
}

$userId = null;
$email = '';
$entityId = null;
$companyId = null;
$displayName = '';

// Default fallback credentials
if ($role === 'ADMIN') {
    $userId = 1;
    $email = 'admin@campusplacement.com';
    $displayName = 'T&P Administrator';
} elseif ($role === 'RECRUITER') {
    $userId = 2;
    $email = 'recruiter.google@campus.com';
    $entityId = 1;
    $companyId = 1;
    $displayName = 'Google Recruiter';
} else {
    $userId = 14;
    $email = 'aarav.sharma@student.campus.edu';
    $entityId = 1;
    $displayName = 'Aarav Sharma';
}

// Try connecting to database to get actual records if available
try {
    $db = new Database();
    $pdo = $db->getConnection();

    if ($pdo) {
        if ($role === 'ADMIN') {
            $admin = $pdo->query("SELECT user_id, email, role, status FROM users WHERE role = 'ADMIN' LIMIT 1")->fetch();
            if ($admin) {
                $userId = (int)$admin['user_id'];
                $email = $admin['email'];
            }
        } elseif ($role === 'RECRUITER') {
            $recruiter = $pdo->query("SELECT r.recruiter_id, r.user_id, r.company_id, c.company_name, u.email 
                                      FROM recruiters r 
                                      JOIN users u ON r.user_id = u.user_id 
                                      JOIN companies c ON r.company_id = c.company_id 
                                      WHERE r.approval_status = 'APPROVED' 
                                      ORDER BY r.recruiter_id ASC 
                                      LIMIT 1")->fetch();
            if ($recruiter) {
                $userId = (int)$recruiter['user_id'];
                $email = $recruiter['email'];
                $entityId = (int)$recruiter['recruiter_id'];
                $companyId = (int)$recruiter['company_id'];
                $displayName = ($recruiter['company_name'] ?? 'Google') . ' Recruiter';
            }
        } elseif ($role === 'STUDENT') {
            $student = $pdo->query("SELECT s.student_id, s.user_id, s.first_name, s.last_name, u.email 
                                    FROM students s 
                                    JOIN users u ON s.user_id = u.user_id 
                                    ORDER BY s.student_id ASC 
                                    LIMIT 1")->fetch();
            if ($student) {
                $userId = (int)$student['user_id'];
                $email = $student['email'];
                $entityId = (int)$student['student_id'];
                $displayName = $student['first_name'] . ' ' . $student['last_name'];
            }
        }
    }
} catch (Throwable $e) {
    // Keep default fallbacks on any database connection issue
}

// Establish session credentials
$_SESSION['user_id'] = $userId;
$_SESSION['email'] = $email;
$_SESSION['role'] = $role;
$_SESSION['status'] = 'ACTIVE';
$_SESSION['entity_id'] = $entityId;
$_SESSION['company_id'] = $companyId;
$_SESSION['name'] = $displayName;

if (!empty($_GET['redirect'])) {
    header("Location: " . $_GET['redirect'], true, 302);
    exit;
}

sendSuccess("Direct access granted for {$role}", [
    'user' => [
        'user_id'    => $userId,
        'email'      => $email,
        'role'       => $role,
        'name'       => $displayName,
        'entity_id'  => $entityId,
        'company_id' => $companyId
    ],
    'token' => generateAuthToken((int)$userId, $role, $entityId)
]);
