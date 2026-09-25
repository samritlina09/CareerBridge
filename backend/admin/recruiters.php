<?php
/**
 * Admin Recruiter Verification & Management Endpoint
 */

require_once __DIR__ . '/../auth/session.php';
$admin = requireAdmin();
$pdo = getDatabaseConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $status = strtoupper(trim($_GET['status'] ?? ''));
    $sql = "SELECT r.recruiter_id, r.user_id, 
                   COALESCE(NULLIF(r.recruiter_name, ''), CONCAT(c.company_name, ' Recruiter')) AS recruiter_name,
                   r.designation, r.phone, r.approval_status, r.approved_at, r.created_at,
                   u.email, u.status AS user_status,
                   c.company_id, c.company_name, c.industry, c.location, c.website, c.description AS company_desc, c.is_verified
            FROM recruiters r
            JOIN users u ON r.user_id = u.user_id
            JOIN companies c ON r.company_id = c.company_id";

    $params = [];
    if (!empty($status) && in_array($status, ['PENDING', 'APPROVED', 'REJECTED'], true)) {
        $sql .= " WHERE r.approval_status = :st";
        $params['st'] = $status;
    }
    $sql .= " ORDER BY (r.approval_status = 'PENDING') DESC, r.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $recruiters = $stmt->fetchAll();

    // Summary counts for tabs/badges
    $countsStmt = $pdo->query("SELECT 
        COUNT(*) AS total,
        COUNT(CASE WHEN approval_status = 'PENDING' THEN 1 END) AS pending,
        COUNT(CASE WHEN approval_status = 'APPROVED' THEN 1 END) AS approved,
        COUNT(CASE WHEN approval_status = 'REJECTED' THEN 1 END) AS rejected
        FROM recruiters");
    $counts = $countsStmt->fetch() ?: ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];

    sendSuccess('Recruiters loaded', [
        'count'      => count($recruiters),
        'recruiters' => $recruiters,
        'counts'     => $counts
    ]);

} elseif ($method === 'POST') {
    $input = getJsonInput();
    $recruiterId = !empty($input['recruiter_id']) ? (int)$input['recruiter_id'] : null;
    $action = strtolower(trim($input['action'] ?? ''));

    if (empty($recruiterId) || !in_array($action, ['approve', 'reject', 'revoke'], true)) {
        sendError('Recruiter ID and valid action (approve/reject/revoke) are required.');
    }

    $stmt = $pdo->prepare("SELECT r.recruiter_id, r.user_id, r.company_id, r.recruiter_name, c.company_name 
                            FROM recruiters r 
                            JOIN companies c ON r.company_id = c.company_id 
                            WHERE r.recruiter_id = :rid");
    $stmt->execute(['rid' => $recruiterId]);
    $rec = $stmt->fetch();

    if (!$rec) {
        sendError('Recruiter record not found.', 404);
    }

    $recName = !empty($rec['recruiter_name']) ? $rec['recruiter_name'] : $rec['company_name'] . ' Recruiter';

    if ($action === 'approve') {
        $upd = $pdo->prepare("UPDATE recruiters SET approval_status = 'APPROVED', approved_at = CURRENT_TIMESTAMP WHERE recruiter_id = :rid");
        $upd->execute(['rid' => $recruiterId]);

        $uUpd = $pdo->prepare("UPDATE users SET status = 'ACTIVE' WHERE user_id = :uid");
        $uUpd->execute(['uid' => $rec['user_id']]);

        // Verify company
        $cUpd = $pdo->prepare("UPDATE companies SET is_verified = 1 WHERE company_id = :cid");
        $cUpd->execute(['cid' => $rec['company_id']]);

        // Send approval notification
        $notif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, link_url) VALUES (:uid, 'Account Approved', :msg, 'dashboard.html')");
        $notif->execute([
            'uid' => $rec['user_id'],
            'msg' => "Congratulations! Your recruiter account for {$rec['company_name']} has been approved by the Placement Cell. You can now publish campus job opportunities."
        ]);

        sendSuccess("Recruiter {$recName} for {$rec['company_name']} has been APPROVED.");
    } elseif ($action === 'reject') {
        $upd = $pdo->prepare("UPDATE recruiters SET approval_status = 'REJECTED' WHERE recruiter_id = :rid");
        $upd->execute(['rid' => $recruiterId]);

        // Close any active jobs posted by this recruiter
        $jUpd = $pdo->prepare("UPDATE jobs SET status = 'CLOSED' WHERE posted_by_recruiter_id = :rid AND status = 'LIVE'");
        $jUpd->execute(['rid' => $recruiterId]);

        // Send rejection notification
        $notif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, link_url) VALUES (:uid, 'Registration Not Approved', :msg, 'profile.html')");
        $notif->execute([
            'uid' => $rec['user_id'],
            'msg' => "Your recruiter registration for {$rec['company_name']} was not approved by the Training & Placement Cell."
        ]);

        sendSuccess("Recruiter {$recName} for {$rec['company_name']} has been REJECTED.");
    } elseif ($action === 'revoke') {
        $upd = $pdo->prepare("UPDATE recruiters SET approval_status = 'REJECTED' WHERE recruiter_id = :rid");
        $upd->execute(['rid' => $recruiterId]);

        // Close any live jobs
        $jUpd = $pdo->prepare("UPDATE jobs SET status = 'CLOSED' WHERE posted_by_recruiter_id = :rid AND status = 'LIVE'");
        $jUpd->execute(['rid' => $recruiterId]);

        // Send revocation notification
        $notif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, link_url) VALUES (:uid, 'Recruiter Access Revoked', :msg, 'dashboard.html')");
        $notif->execute([
            'uid' => $rec['user_id'],
            'msg' => "Your recruiter permissions for {$rec['company_name']} have been disabled by the Training & Placement Cell."
        ]);

        sendSuccess("Recruiter permissions for {$recName} ({$rec['company_name']}) have been REVOKED.");
    }
} else {
    sendError('Method Not Allowed', 405);
}
