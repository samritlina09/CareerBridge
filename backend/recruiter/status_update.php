<?php
/**
 * Recruiter Application Status Update Endpoint
 * Executes state transitions and atomic candidate selection workflows
 */

require_once __DIR__ . '/../auth/session.php';
$user = requireRecruiter();
$companyId = $user['company_id'];
$pdo = getDatabaseConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method Not Allowed', 405);
}

$input = getJsonInput();
$appId = !empty($input['application_id']) ? (int)$input['application_id'] : null;
$status = strtoupper(trim($input['status'] ?? ''));
$notes = trim($input['notes'] ?? '');
$packageLpa = isset($input['package_lpa']) ? (float)$input['package_lpa'] : 0.00;

if (empty($appId) || empty($status)) {
    sendError('Application ID and new status are required.');
}

$allowedStatuses = ['UNDER_REVIEW', 'SHORTLISTED', 'REJECTED', 'SELECTED'];
if (!in_array($status, $allowedStatuses, true)) {
    sendError('Invalid target status.');
}

// Verify application belongs to this company's job
$verify = $pdo->prepare("SELECT a.application_id, a.student_id, a.job_id, j.salary_stipend, j.title, j.company_id, s.user_id 
                         FROM applications a 
                         JOIN jobs j ON a.job_id = j.job_id 
                         JOIN students s ON a.student_id = s.student_id 
                         WHERE a.application_id = :aid AND j.company_id = :cid");
$verify->execute(['aid' => $appId, 'cid' => $companyId]);
$app = $verify->fetch();

if (!$app) {
    sendError('Application not found or unauthorized for your company.', 403);
}

if ($status === 'SELECTED') {
    // If package not provided, default to job's salary
    if ($packageLpa <= 0) {
        $packageLpa = round((float)$app['salary_stipend'] / 100000.0, 2);
        if ($packageLpa <= 0) $packageLpa = 8.50; // default fallback LPA
    }

    try {
        // Execute Candidate Selection Stored Procedure with ACID transaction
        $stmt = $pdo->prepare("CALL sp_process_candidate_selection(:aid, :pkg, @p_res)");
        $stmt->execute(['aid' => $appId, 'pkg' => $packageLpa]);
        
        $res = $pdo->query("SELECT @p_res AS result")->fetchColumn();
        
        if (str_starts_with($res, 'ERROR')) {
            sendError($res);
        }

        sendSuccess('Candidate selected successfully! Placement record created and student notified.', [
            'status'      => 'SELECTED',
            'package_lpa' => $packageLpa,
            'result'      => $res
        ]);
    } catch (Exception $e) {
        sendError('Transaction error: ' . $e->getMessage(), 500);
    }
} else {
    // Update application status
    $upd = $pdo->prepare("UPDATE applications SET status = :status, notes = :notes WHERE application_id = :aid");
    $upd->execute([
        'status' => $status,
        'notes'  => $notes ?: "Status changed to {$status} by recruiter",
        'aid'    => $appId
    ]);

    sendSuccess("Application status updated to {$status}.");
}
