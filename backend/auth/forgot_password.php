<?php
/**
 * Password Reset Endpoint (Demonstration Implementation)
 */

require_once __DIR__ . '/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method Not Allowed', 405);
}

$input = getJsonInput();
$email = trim($input['email'] ?? '');
$newPassword = $input['new_password'] ?? '';

if (empty($email) || empty($newPassword)) {
    sendError('Please provide your registered email and a new password.');
}

if (strlen($newPassword) < 6) {
    sendError('New password must be at least 6 characters.');
}

$pdo = getDatabaseConnection();
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user) {
    sendError('No account found with this email address.', 404);
}

$hash = password_hash($newPassword, PASSWORD_DEFAULT);
$update = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE user_id = :uid");
$update->execute(['hash' => $hash, 'uid' => $user['user_id']]);

// Create notification
$notif = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (:uid, 'Password Updated', 'Your account password has been successfully reset.')");
$notif->execute(['uid' => $user['user_id']]);

sendSuccess('Your password has been reset successfully. You may now log in with your new password.', [
    'redirect' => 'login.html'
]);
