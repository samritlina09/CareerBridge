<?php
/**
 * Recruiter Job Postings Endpoint
 * Create, List, and Close Jobs
 */

require_once __DIR__ . '/../auth/session.php';

// Allow metadata query (branches & skills) for forms
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['meta_only']) || isset($_GET['meta']))) {
    $pdo = getDatabaseConnection();
    $branches = $pdo->query("SELECT branch_id, branch_name, branch_code FROM branches ORDER BY branch_name")->fetchAll();
    $skills = $pdo->query("SELECT skill_id, skill_name, category FROM skills ORDER BY category, skill_name")->fetchAll();
    sendSuccess('Metadata loaded', [
        'branches' => $branches,
        'skills'   => $skills
    ]);
}

$user = requireRecruiter();
$recruiterId = $user['entity_id'];
$companyId = $user['company_id'] ?? 1;
$pdo = getDatabaseConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Single job query
    if (isset($_GET['id'])) {
        $jobId = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM jobs WHERE job_id = :jid AND company_id = :cid");
        $stmt->execute(['jid' => $jobId, 'cid' => $companyId]);
        $job = $stmt->fetch();
        if (!$job) {
            sendError('Job posting not found.', 404);
        }

        // Skills
        $sk = $pdo->prepare("SELECT s.skill_id, s.skill_name FROM job_skills js JOIN skills s ON js.skill_id = s.skill_id WHERE js.job_id = :jid");
        $sk->execute(['jid' => $jobId]);
        $job['skills'] = $sk->fetchAll();

        // Branches
        $br = $pdo->prepare("SELECT b.branch_id, b.branch_name, b.branch_code FROM job_eligible_branches jeb JOIN branches b ON jeb.branch_id = b.branch_id WHERE jeb.job_id = :jid");
        $br->execute(['jid' => $jobId]);
        $job['branches'] = $br->fetchAll();

        sendSuccess('Job details', ['job' => $job]);
    }

    // List all company's jobs with applicant stats
    $stmt = $pdo->prepare("SELECT j.*, 
                                  COUNT(DISTINCT a.application_id) AS total_applicants,
                                  COUNT(DISTINCT CASE WHEN a.status = 'SHORTLISTED' THEN a.application_id END) AS shortlisted_count,
                                  COUNT(DISTINCT CASE WHEN a.status = 'INTERVIEW_SCHEDULED' THEN a.application_id END) AS interview_count,
                                  COUNT(DISTINCT CASE WHEN a.status = 'SELECTED' THEN a.application_id END) AS selected_count
                           FROM jobs j
                           LEFT JOIN applications a ON j.job_id = a.job_id
                           WHERE j.company_id = :cid
                           GROUP BY j.job_id
                           ORDER BY j.created_at DESC");
    $stmt->execute(['cid' => $companyId]);
    $jobs = $stmt->fetchAll();

    // Also send master branches and skills for form dropdowns
    $branches = $pdo->query("SELECT branch_id, branch_name, branch_code FROM branches ORDER BY branch_name")->fetchAll();
    $skills = $pdo->query("SELECT skill_id, skill_name, category FROM skills ORDER BY category, skill_name")->fetchAll();

    sendSuccess('Jobs loaded', [
        'jobs'     => $jobs,
        'branches' => $branches,
        'skills'   => $skills
    ]);

} elseif ($method === 'POST') {
    $input = getJsonInput();
    $action = $input['action'] ?? 'create';

    if ($action === 'create') {
        $user = requireRecruiter();
        $recruiterId = $user['entity_id'];
        $companyId = $user['company_id'] ?? $companyId;

        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $jobType = in_array($input['job_type'] ?? '', ['INTERNSHIP', 'FULL_TIME', 'PART_TIME']) ? $input['job_type'] : 'FULL_TIME';
        $workMode = in_array($input['work_mode'] ?? '', ['ON_SITE', 'REMOTE', 'HYBRID']) ? $input['work_mode'] : 'ON_SITE';
        $location = trim($input['location'] ?? 'Flexible');
        $minCgpa = isset($input['min_cgpa']) ? (float)$input['min_cgpa'] : 6.00;
        $salary = isset($input['salary_stipend']) ? (float)$input['salary_stipend'] : 0.00;
        $experience = trim($input['experience_req'] ?? 'Fresher');
        $deadline = !empty($input['deadline']) ? $input['deadline'] : date('Y-m-d', strtotime('+30 days'));
        $openings = !empty($input['openings_count']) ? (int)$input['openings_count'] : 1;
        $skillsArr = $input['skills'] ?? [];
        $branchesArr = $input['branches'] ?? [];

        if (empty($title) || empty($description) || $salary <= 0) {
            sendError('Job title, description, and salary/stipend are required.');
        }

        try {
            $pdo->beginTransaction();

            // Insert Job with PENDING status awaiting admin review
            $stmt = $pdo->prepare("INSERT INTO jobs (company_id, posted_by_recruiter_id, title, description, job_type, work_mode, location, min_cgpa, salary_stipend, experience_req, deadline, openings_count, status)
                                   VALUES (:cid, :rid, :title, :desc, :type, :mode, :loc, :cgpa, :salary, :exp, :dead, :open, 'PENDING')");
            $stmt->execute([
                'cid'    => $companyId,
                'rid'    => $recruiterId,
                'title'  => $title,
                'desc'   => $description,
                'type'   => $jobType,
                'mode'   => $workMode,
                'loc'    => $location,
                'cgpa'   => $minCgpa,
                'salary' => $salary,
                'exp'    => $experience,
                'dead'   => $deadline,
                'open'   => $openings
            ]);
            $newJobId = (int)$pdo->lastInsertId();

            // Insert Job Skills
            if (!empty($skillsArr)) {
                $skStmt = $pdo->prepare("INSERT INTO job_skills (job_id, skill_id, is_required) VALUES (:jid, :skid, 1) ON DUPLICATE KEY UPDATE is_required = 1");
                foreach ($skillsArr as $skid) {
                    $skStmt->execute(['jid' => $newJobId, 'skid' => (int)$skid]);
                }
            }

            // Insert Eligible Branches
            if (!empty($branchesArr)) {
                $brStmt = $pdo->prepare("INSERT IGNORE INTO job_eligible_branches (job_id, branch_id) VALUES (:jid, :bid)");
                foreach ($branchesArr as $bid) {
                    $brStmt->execute(['jid' => $newJobId, 'bid' => (int)$bid]);
                }
            }

            // Notify Admin
            $admin = $pdo->query("SELECT user_id FROM users WHERE role = 'ADMIN' LIMIT 1")->fetch();
            if ($admin) {
                $nStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, link_url) VALUES (:uid, 'New Job Awaiting Approval', :msg, 'jobs.html')");
                $nStmt->execute([
                    'uid' => $admin['user_id'],
                    'msg' => "New job opportunity '{$title}' posted and awaiting admin approval."
                ]);
            }

            $pdo->commit();

            sendSuccess('Job opportunity submitted successfully! It is now pending admin approval before appearing to students.', [
                'job_id' => $newJobId,
                'status' => 'PENDING'
            ]);

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            sendError('Failed to create job: ' . $e->getMessage(), 500);
        }

    } elseif ($action === 'close') {
        $jobId = (int)($input['job_id'] ?? 0);
        if (empty($jobId)) {
            sendError('Job ID is required to close listing.');
        }

        $stmt = $pdo->prepare("UPDATE jobs SET status = 'CLOSED' WHERE job_id = :jid AND company_id = :cid");
        $stmt->execute(['jid' => $jobId, 'cid' => $companyId]);

        sendSuccess('Job posting has been marked as CLOSED.');
    } else {
        sendError('Invalid action.');
    }
} else {
    sendError('Method Not Allowed', 405);
}
