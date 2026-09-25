<?php
/**
 * Admin Jobs Management & Approval Endpoint
 */

require_once __DIR__ . '/../auth/session.php';
$admin = requireAdmin();
$pdo = getDatabaseConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $status = strtoupper(trim($_GET['status'] ?? ''));
    $sql = "SELECT j.*, c.company_name, c.logo_path, c.industry, c.location AS company_location,
                   r.recruiter_id, 
                   COALESCE(NULLIF(r.recruiter_name, ''), CONCAT(c.company_name, ' Recruiter')) AS recruiter_name,
                   r.designation AS recruiter_designation,
                   r.phone AS recruiter_phone,
                   u.email AS recruiter_email,
                   COUNT(DISTINCT a.application_id) AS applicant_count,
                   (
                       SELECT GROUP_CONCAT(DISTINCT s.skill_name SEPARATOR ', ')
                       FROM job_skills js
                       JOIN skills s ON js.skill_id = s.skill_id
                       WHERE js.job_id = j.job_id
                   ) AS required_skills,
                   (
                       SELECT GROUP_CONCAT(DISTINCT b.branch_name SEPARATOR ', ')
                       FROM job_eligible_branches jeb
                       JOIN branches b ON jeb.branch_id = b.branch_id
                       WHERE jeb.job_id = j.job_id
                   ) AS eligible_branches
            FROM jobs j
            JOIN companies c ON j.company_id = c.company_id
            LEFT JOIN recruiters r ON j.posted_by_recruiter_id = r.recruiter_id
            LEFT JOIN users u ON r.user_id = u.user_id
            LEFT JOIN applications a ON j.job_id = a.job_id";

    $params = [];
    if (!empty($status)) {
        if ($status === 'APPROVED') {
            $sql .= " WHERE (j.status = 'APPROVED' OR j.status = 'LIVE')";
        } else {
            $sql .= " WHERE j.status = :st";
            $params['st'] = $status;
        }
    }
    $sql .= " GROUP BY j.job_id ORDER BY (j.status = 'PENDING') DESC, j.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll();

    // Summary counts for filter tabs & badge
    $countsStmt = $pdo->query("SELECT 
        COUNT(*) AS total,
        COUNT(CASE WHEN status = 'PENDING' THEN 1 END) AS pending,
        COUNT(CASE WHEN status IN ('APPROVED', 'LIVE') THEN 1 END) AS approved,
        COUNT(CASE WHEN status = 'REJECTED' THEN 1 END) AS rejected,
        COUNT(CASE WHEN status = 'CLOSED' THEN 1 END) AS closed
        FROM jobs");
    $counts = $countsStmt->fetch() ?: ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'closed' => 0];

    sendSuccess('Jobs loaded', [
        'count'  => count($jobs),
        'jobs'   => $jobs,
        'counts' => $counts
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

    $newStatus = 'APPROVED';
    $msg = "Job opportunity '{$job['title']}' has been approved and is now visible to students!";

    if ($action === 'reject') {
        $newStatus = 'REJECTED';
        $msg = "Job opportunity '{$job['title']}' was rejected by the Placement Cell.";
    } elseif ($action === 'close') {
        $newStatus = 'CLOSED';
        $msg = "Job opportunity '{$job['title']}' has been closed.";
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

    sendSuccess("Job status updated to {$newStatus}.", [
        'job_id' => $jobId,
        'status' => $newStatus
    ]);
} else {
    sendError('Method Not Allowed', 405);
}
