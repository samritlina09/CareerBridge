<?php
/**
 * Current Session / Auth Info Endpoint
 */

require_once __DIR__ . '/session.php';

if (!isLoggedIn()) {
    sendSuccess('Guest', [
        'authenticated' => false,
        'user' => null
    ]);
}

$user = getCurrentUser();
$pdo = getDatabaseConnection();

$extra = [];
if ($user['role'] === 'STUDENT' && $user['entity_id']) {
    $stmt = $pdo->prepare("SELECT s.*, b.branch_name, b.branch_code, d.dept_name 
                           FROM students s 
                           LEFT JOIN branches b ON s.branch_id = b.branch_id 
                           LEFT JOIN departments d ON b.dept_id = d.dept_id 
                           WHERE s.student_id = :sid");
    $stmt->execute(['sid' => $user['entity_id']]);
    $extra['student'] = $stmt->fetch() ?: [];
} elseif ($user['role'] === 'RECRUITER' && $user['entity_id']) {
    $stmt = $pdo->prepare("SELECT r.*, c.company_name, c.logo_path, c.industry 
                           FROM recruiters r 
                           JOIN companies c ON r.company_id = c.company_id 
                           WHERE r.recruiter_id = :rid");
    $stmt->execute(['rid' => $user['entity_id']]);
    $extra['recruiter'] = $stmt->fetch() ?: [];
}

sendSuccess('Authenticated', [
    'authenticated' => true,
    'user' => $user,
    'profile' => $extra
]);
