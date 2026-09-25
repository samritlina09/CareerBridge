<?php
/**
 * Recruiter Interview Scheduling & Management Endpoint
 */

require_once __DIR__ . '/../auth/session.php';
$user = requireRecruiter();
$companyId = $user['company_id'];
$pdo = getDatabaseConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT i.*, a.status AS app_status, j.title AS job_title, j.job_id,
                                  s.student_id, s.roll_number, CONCAT(s.first_name, ' ', s.last_name) AS student_name,
                                  u.email AS student_email, s.phone AS student_phone, s.cgpa
                           FROM interviews i
                           JOIN applications a ON i.application_id = a.application_id
                           JOIN jobs j ON a.job_id = j.job_id
                           JOIN students s ON a.student_id = s.student_id
                           JOIN users u ON s.user_id = u.user_id
                           WHERE j.company_id = :cid
                           ORDER BY i.scheduled_at DESC");
    $stmt->execute(['cid' => $companyId]);
    $interviews = $stmt->fetchAll();

    sendSuccess('Interviews loaded', ['interviews' => $interviews]);

} elseif ($method === 'POST') {
    $input = getJsonInput();
    $action = $input['action'] ?? 'schedule';

    if ($action === 'schedule') {
        $appId = !empty($input['application_id']) ? (int)$input['application_id'] : null;
        $roundName = trim($input['round_name'] ?? 'Technical Round');
        $scheduledAt = trim($input['scheduled_at'] ?? '');
        $meetingLink = trim($input['meeting_link_location'] ?? 'Virtual Meeting / Campus Room');

        if (empty($appId) || empty($scheduledAt)) {
            sendError('Application ID and scheduled date/time are required.');
        }

        // Verify application
        $verify = $pdo->prepare("SELECT a.application_id, a.student_id, j.title, s.user_id 
                                 FROM applications a 
                                 JOIN jobs j ON a.job_id = j.job_id 
                                 JOIN students s ON a.student_id = s.student_id 
                                 WHERE a.application_id = :aid AND j.company_id = :cid");
        $verify->execute(['aid' => $appId, 'cid' => $companyId]);
        $app = $verify->fetch();

        if (!$app) {
            sendError('Application not found or unauthorized.', 403);
        }

        try {
            $pdo->beginTransaction();

            // Insert Interview
            $ins = $pdo->prepare("INSERT INTO interviews (application_id, round_name, scheduled_at, meeting_link_location, status, result)
                                   VALUES (:aid, :round, :sched, :link, 'SCHEDULED', 'PENDING')");
            $ins->execute([
                'aid'   => $appId,
                'round' => $roundName,
                'sched' => $scheduledAt,
                'link'  => $meetingLink
            ]);

            // Update application status to INTERVIEW_SCHEDULED
            $upd = $pdo->prepare("UPDATE applications SET status = 'INTERVIEW_SCHEDULED' WHERE application_id = :aid");
            $upd->execute(['aid' => $appId]);

            // Send notification to student
            $notif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, link_url) VALUES (:uid, :title, :msg, 'interviews.html')");
            $notif->execute([
                'uid'   => $app['user_id'],
                'title' => 'Interview Scheduled!',
                'msg'   => "Your {$roundName} for {$app['title']} has been scheduled for {$scheduledAt}."
            ]);

            $pdo->commit();

            sendSuccess('Interview successfully scheduled!', [
                'round_name'   => $roundName,
                'scheduled_at' => $scheduledAt
            ]);

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            sendError('Failed to schedule interview: ' . $e->getMessage(), 500);
        }

    } elseif ($action === 'update_result') {
        $interviewId = !empty($input['interview_id']) ? (int)$input['interview_id'] : null;
        $result = strtoupper(trim($input['result'] ?? 'PASSED'));
        $feedback = trim($input['interviewer_feedback'] ?? '');
        $status = in_array($input['status'] ?? '', ['SCHEDULED', 'COMPLETED', 'CANCELLED']) ? $input['status'] : 'COMPLETED';

        if (empty($interviewId)) {
            sendError('Interview ID is required.');
        }

        $upd = $pdo->prepare("UPDATE interviews SET result = :res, status = :st, interviewer_feedback = :fb WHERE interview_id = :iid");
        $upd->execute([
            'res' => $result,
            'st'  => $status,
            'fb'  => $feedback,
            'iid' => $interviewId
        ]);

        sendSuccess('Interview result and feedback updated.');
    } else {
        sendError('Invalid action.');
    }
} else {
    sendError('Method Not Allowed', 405);
}
