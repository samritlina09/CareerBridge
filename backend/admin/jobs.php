<?php
/**
 * Admin Jobs Management & Approval Endpoint
 */

require_once __DIR__ . '/../auth/session.php';
$admin = requireAdmin();
$pdo = getDatabaseConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $status = trim($_GET['status'] ?? '');
    $sql = "SELECT j.*, c.company_name, c.logo_path,
                   r.recruiter_id, u.email AS recruiter_email,
                   COUNT(DISTINCT a.application_id) AS applicant_count,
                   GROUP_CONCAT(DISTINCT s.skill_name SEPARATOR ', ') AS required_skills
            FROM jobs j
            JOIN companies c ON j.company_id = c.company_id
            LEFT JOIN recruiters r ON j.posted_by_recruiter_id = r.recruiter_id
            LEFT JOIN users u ON r.user_id = u.user_id
            LEFT JOIN applications a ON j.job_id = a.job_id
            LEFT JOIN job_skills js ON j.job_id = js.job_id
            LEFT JOIN skills s ON js.skill_id = s.skill_id";

    $params = [];
    if (!empty($status)) {
        $sql .= " WHERE j.status = :st";
        $params['st'] = $status;
    }
    $sql .= " GROUP BY j.job_id ORDER BY (j.status = 'PENDING') DESC, j.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll();

    sendSuccess('Jobs loaded', [
        'count' => count($jobs),
        'jobs'  => $jobs
    ]);

} elseif ($method === 'POST') {
    $input = getJsonInput();
    $jobId = !empty($input['job_id']) ? (int)$input['job_id'] : null;
    $action = strtolower(trim($input['action'] ?? ''));

    if (empty($jobId) || !in_array($action, ['approve', 'reject', 'close'], true)) {
        sendError('Job ID and valid action (approve/reject/close) are required.');
    }

    $jStmt = $pdo->prepare("SELECT j.job_id, j.title, j.posted_by_recruiter_id, r.user_id 
                            FROM jobs j 
                            LEFT JOIN recruiters r ON j.posted_by_recruiter_id = r.recruiter_id 
                            WHERE j.job_id = :jid");
    $jStmt->execute(['jid' => $jobId]);
    $job = $jStmt->fetch();

    if (!$job) {
        sendError('Job record not found.', 404);
    }

    $newStatus = 'LIVE';
    $msg = "Job '{$job['title']}' has been approved and is now LIVE for students to apply!";

    if ($action === 'reject') {
        $newStatus = 'REJECTED';
        $msg = "Job '{$job['title']}' was rejected by the Placement Cell.";
    } elseif ($action === 'close') {
        $newStatus = 'CLOSED';
        $msg = "Job '{$job['title']}' has been closed.";
    }

    $upd = $pdo->prepare("UPDATE jobs SET status = :st WHERE job_id = :jid");
    $upd->execute(['st' => $newStatus, 'jid' => $jobId]);

    // Send notification to recruiter if available
    if (!empty($job['user_id'])) {
        $notif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, link_url) VALUES (:uid, 'Job Posting Update', :msg, 'manage-jobs.html')");
        $notif->execute([
            'uid' => $job['user_id'],
            'msg' => $msg
        ]);
    }

    sendSuccess("Job status updated to {$newStatus}.");
} else {
    sendError('Method Not Allowed', 405);
}
