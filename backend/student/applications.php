<?php
/**
 * Student Applications Workflow Endpoint
 * Apply, Withdraw, and Track Applications
 */

require_once __DIR__ . '/../auth/session.php';
$user = requireStudent();
$studentId = $user['entity_id'];
$pdo = getDatabaseConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // List all student applications with timeline and interview rounds
    $stmt = $pdo->prepare("SELECT a.application_id, a.job_id, a.applied_at, a.status, a.notes,
                                  j.title AS job_title, j.job_type, j.work_mode, j.location, j.salary_stipend, j.deadline,
                                  c.company_name, c.logo_path,
                                  p.placement_id, p.package_lpa, p.accepted_at
                           FROM applications a
                           JOIN jobs j ON a.job_id = j.job_id
                           JOIN companies c ON j.company_id = c.company_id
                           LEFT JOIN placements p ON a.application_id = p.application_id
                           WHERE a.student_id = :sid
                           ORDER BY a.applied_at DESC");
    $stmt->execute(['sid' => $studentId]);
    $apps = $stmt->fetchAll();

    if (!empty($apps)) {
        $appIds = array_column($apps, 'application_id');
        $inPlaceholders = implode(',', array_fill(0, count($appIds), '?'));
        $iStmt = $pdo->prepare("SELECT interview_id, application_id, round_name, scheduled_at, meeting_link_location, status, result 
                                FROM interviews 
                                WHERE application_id IN ($inPlaceholders)
                                ORDER BY scheduled_at ASC");
        $iStmt->execute($appIds);
        $interviews = $iStmt->fetchAll();

        $groupedInterviews = [];
        foreach ($interviews as $inv) {
            $groupedInterviews[$inv['application_id']][] = $inv;
        }

        foreach ($apps as &$app) {
            $app['interviews'] = $groupedInterviews[$app['application_id']] ?? [];
        }
    }

    sendSuccess('Applications loaded', ['applications' => $apps]);

} elseif ($method === 'POST') {
    $input = getJsonInput();
    $action = $input['action'] ?? 'apply';
    $jobId = !empty($input['job_id']) ? (int)$input['job_id'] : null;

    if ($action === 'apply') {
        if (empty($jobId)) {
            sendError('Job ID is required to apply.');
        }

        // Fetch Student Info
        $sStmt = $pdo->prepare("SELECT cgpa, branch_id, resume_path FROM students WHERE student_id = :sid");
        $sStmt->execute(['sid' => $studentId]);
        $student = $sStmt->fetch();

        // Fetch Job Info
        $jStmt = $pdo->prepare("SELECT * FROM jobs WHERE job_id = :jid");
        $jStmt->execute(['jid' => $jobId]);
        $job = $jStmt->fetch();

        if (!$job || !in_array($job['status'], ['APPROVED', 'LIVE'], true)) {
            sendError('This job is no longer accepting applications.', 400);
        }

        if (strtotime($job['deadline']) < strtotime(date('Y-m-d'))) {
            sendError('The application deadline for this position has passed.', 400);
        }

        // Academic eligibility check
        if ((float)$student['cgpa'] < (float)$job['min_cgpa']) {
            sendError("Minimum CGPA requirement is {$job['min_cgpa']}. Your current CGPA is {$student['cgpa']}.");
        }

        // Branch eligibility check
        $bStmt = $pdo->prepare("SELECT COUNT(*) FROM job_eligible_branches WHERE job_id = :jid");
        $bStmt->execute(['jid' => $jobId]);
        $hasBranchRestrictions = ((int)$bStmt->fetchColumn() > 0);

        if ($hasBranchRestrictions) {
            $checkBranch = $pdo->prepare("SELECT 1 FROM job_eligible_branches WHERE job_id = :jid AND branch_id = :bid");
            $checkBranch->execute(['jid' => $jobId, 'bid' => $student['branch_id']]);
            if (!$checkBranch->fetch()) {
                sendError('Your academic branch is not eligible for this job posting.');
            }
        }

        // Check for existing application
        $exStmt = $pdo->prepare("SELECT application_id, status FROM applications WHERE job_id = :jid AND student_id = :sid");
        $exStmt->execute(['jid' => $jobId, 'sid' => $studentId]);
        $existing = $exStmt->fetch();

        if ($existing) {
            if ($existing['status'] === 'WITHDRAWN') {
                // Allow re-applying if previously withdrawn
                $upd = $pdo->prepare("UPDATE applications SET status = 'APPLIED', applied_at = CURRENT_TIMESTAMP WHERE application_id = :aid");
                $upd->execute(['aid' => $existing['application_id']]);
                sendSuccess('Your application has been re-submitted successfully!');
            } else {
                sendError("You have already applied for this job (Status: {$existing['status']}).");
            }
        }

        // Create application
        $ins = $pdo->prepare("INSERT INTO applications (job_id, student_id, status, notes) VALUES (:jid, :sid, 'APPLIED', 'Application submitted via student portal')");
        $ins->execute(['jid' => $jobId, 'sid' => $studentId]);
        $appId = (int)$pdo->lastInsertId();

        // Create notification for student
        $notif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, link_url) VALUES (:uid, 'Application Submitted', :msg, 'applications.html')");
        $notif->execute([
            'uid' => $user['user_id'],
            'msg' => "Successfully submitted your application for {$job['title']}."
        ]);

        sendSuccess('Application submitted successfully!', [
            'application_id' => $appId,
            'status'         => 'APPLIED'
        ]);

    } elseif ($action === 'withdraw') {
        $appId = !empty($input['application_id']) ? (int)$input['application_id'] : null;
        if (empty($appId)) {
            sendError('Application ID is required to withdraw.');
        }

        $check = $pdo->prepare("SELECT a.status, j.title FROM applications a JOIN jobs j ON a.job_id = j.job_id WHERE a.application_id = :aid AND a.student_id = :sid");
        $check->execute(['aid' => $appId, 'sid' => $studentId]);
        $app = $check->fetch();

        if (!$app) {
            sendError('Application record not found.', 404);
        }

        if (in_array($app['status'], ['SELECTED', 'WITHDRAWN', 'REJECTED'])) {
            sendError("Cannot withdraw application in {$app['status']} status.");
        }

        $upd = $pdo->prepare("UPDATE applications SET status = 'WITHDRAWN', notes = 'Withdrawn by student' WHERE application_id = :aid");
        $upd->execute(['aid' => $appId]);

        sendSuccess('Application withdrawn.');
    } else {
        sendError('Invalid action specified.');
    }
} else {
    sendError('Method Not Allowed', 405);
}
