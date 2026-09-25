<?php
/**
 * Notifications Management Endpoint
 */

require_once __DIR__ . '/../auth/session.php';
$user = requireLogin();
$userId = $user['user_id'];
$pdo = getDatabaseConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT 50");
    $stmt->execute(['uid' => $userId]);
    $notifs = $stmt->fetchAll();

    $unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
    $unreadStmt->execute(['uid' => $userId]);
    $unreadCount = (int)$unreadStmt->fetchColumn();

    sendSuccess('Notifications loaded', [
        'notifications' => $notifs,
        'unread_count'  => $unreadCount
    ]);

} elseif ($method === 'POST') {
    $input = getJsonInput();
    $action = $input['action'] ?? 'mark_read';
    $notifId = !empty($input['notification_id']) ? (int)$input['notification_id'] : null;

    if ($action === 'mark_all_read') {
        $upd = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :uid");
        $upd->execute(['uid' => $userId]);
        sendSuccess('All notifications marked as read.');
    } else {
        if (empty($notifId)) {
            sendError('Notification ID is required.');
        }
        $upd = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = :nid AND user_id = :uid");
        $upd->execute(['nid' => $notifId, 'uid' => $userId]);
        sendSuccess('Notification marked as read.');
    }
} else {
    sendError('Method Not Allowed', 405);
}
