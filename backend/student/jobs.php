<?php
/**
 * Student Job Search, Filter & Details Endpoint
 */

require_once __DIR__ . '/../auth/session.php';
$user = getCurrentUser();
$isStudent = ($user && ($user['role'] ?? '') === 'STUDENT');
$studentId = $isStudent ? (int)($user['entity_id'] ?? 0) : 0;
$pdo = getDatabaseConnection();

$studentCgpa = 0.0;
$studentBranch = 0;
if ($studentId > 0) {
    // Fetch student profile for eligibility hints
    $sStmt = $pdo->prepare("SELECT cgpa, branch_id FROM students WHERE student_id = :sid");
    $sStmt->execute(['sid' => $studentId]);
    $student = $sStmt->fetch();
    $studentCgpa = (float)($student['cgpa'] ?? 0.0);
    $studentBranch = (int)($student['branch_id'] ?? 0);
}

// Single Job Details query
if (isset($_GET['id'])) {
    $jobId = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT j.*, c.company_name, c.industry, c.description AS company_desc, 
                                  c.website, c.logo_path, c.location AS company_location,
                                  r.designation AS recruiter_designation,
                                  a.application_id, a.status AS user_application_status, a.applied_at
                           FROM jobs j
                           JOIN companies c ON j.company_id = c.company_id
                           LEFT JOIN recruiters r ON j.posted_by_recruiter_id = r.recruiter_id
                           LEFT JOIN applications a ON j.job_id = a.job_id AND a.student_id = :sid
                           WHERE j.job_id = :jid AND (j.status = 'APPROVED' OR j.status = 'LIVE')");
    $stmt->execute(['jid' => $jobId, 'sid' => $studentId]);
    $job = $stmt->fetch();

    if (!$job) {
        sendError('Job posting not found or not active.', 404);
    }

    // Required Skills
    $skStmt = $pdo->prepare("SELECT s.skill_id, s.skill_name, s.category, js.is_required,
                                    CASE WHEN ss.student_id IS NOT NULL THEN 1 ELSE 0 END AS student_has_skill
                             FROM job_skills js
                             JOIN skills s ON js.skill_id = s.skill_id
                             LEFT JOIN student_skills ss ON s.skill_id = ss.skill_id AND ss.student_id = :sid
                             WHERE js.job_id = :jid
                             ORDER BY js.is_required DESC, s.skill_name");
    $skStmt->execute(['jid' => $jobId, 'sid' => $studentId]);
    $job['skills'] = $skStmt->fetchAll();

    // Eligible branches
    $bStmt = $pdo->prepare("SELECT b.branch_id, b.branch_name, b.branch_code 
                            FROM job_eligible_branches jeb 
                            JOIN branches b ON jeb.branch_id = b.branch_id 
                            WHERE jeb.job_id = :jid");
    $bStmt->execute(['jid' => $jobId]);
    $job['branches'] = $bStmt->fetchAll();

    // Eligibility check
    if ($isStudent && $studentId > 0) {
        $branchEligible = empty($job['branches']);
        if (!$branchEligible) {
            foreach ($job['branches'] as $b) {
                if ($b['branch_id'] == $studentBranch) {
                    $branchEligible = true;
                    break;
                }
            }
        }
        $cgpaEligible = ($studentCgpa >= (float)$job['min_cgpa']);
        $job['is_eligible'] = ($branchEligible && $cgpaEligible);
        $job['cgpa_eligible'] = $cgpaEligible;
        $job['branch_eligible'] = $branchEligible;
    } else {
        $job['is_eligible'] = false;
        $job['cgpa_eligible'] = false;
        $job['branch_eligible'] = false;
    }

    sendSuccess('Job details loaded', ['job' => $job]);
}

// Multi-criteria Catalog Query
$query = trim($_GET['q'] ?? '');
$jobType = trim($_GET['type'] ?? '');
$workMode = trim($_GET['mode'] ?? '');
$location = trim($_GET['location'] ?? '');
$eligibleOnly = !empty($_GET['eligible_only']);
$skillFilter = !empty($_GET['skill_id']) ? (int)$_GET['skill_id'] : null;

$sql = "SELECT j.job_id, j.title, j.job_type, j.work_mode, j.location, j.min_cgpa, 
               j.salary_stipend, j.experience_req, j.deadline, j.status, j.created_at,
               c.company_id, c.company_name, c.logo_path, c.industry,
               a.status AS my_application_status,
               GROUP_CONCAT(DISTINCT s.skill_name SEPARATOR ', ') AS skills_list,
               (
                   SELECT COUNT(*) FROM job_skills js2 
                   JOIN student_skills ss2 ON js2.skill_id = ss2.skill_id 
                   WHERE js2.job_id = j.job_id AND ss2.student_id = :sid_match
               ) AS matched_skills_count,
               (SELECT COUNT(*) FROM job_skills js3 WHERE js3.job_id = j.job_id) AS total_skills_count
        FROM jobs j
        JOIN companies c ON j.company_id = c.company_id
        LEFT JOIN recruiters r ON j.posted_by_recruiter_id = r.recruiter_id
        LEFT JOIN job_skills js ON j.job_id = js.job_id
        LEFT JOIN skills s ON js.skill_id = s.skill_id
        LEFT JOIN applications a ON j.job_id = a.job_id AND a.student_id = :sid_app
        WHERE (j.status = 'APPROVED' OR j.status = 'LIVE') AND j.deadline >= CURDATE()";

$params = [
    'sid_match' => $studentId,
    'sid_app'   => $studentId
];

if (!empty($query)) {
    $sql .= " AND (j.title LIKE :q OR c.company_name LIKE :q OR j.description LIKE :q)";
    $params['q'] = "%{$query}%";
}

if (!empty($jobType) && in_array($jobType, ['INTERNSHIP', 'FULL_TIME', 'PART_TIME'])) {
    $sql .= " AND j.job_type = :type";
    $params['type'] = $jobType;
}

if (!empty($workMode) && in_array($workMode, ['ON_SITE', 'REMOTE', 'HYBRID'])) {
    $sql .= " AND j.work_mode = :mode";
    $params['mode'] = $workMode;
}

if (!empty($location)) {
    $sql .= " AND j.location LIKE :loc";
    $params['loc'] = "%{$location}%";
}

if ($skillFilter) {
    $sql .= " AND EXISTS (SELECT 1 FROM job_skills js_filter WHERE js_filter.job_id = j.job_id AND js_filter.skill_id = :f_skill)";
    $params['f_skill'] = $skillFilter;
}

if ($eligibleOnly) {
    $sql .= " AND j.min_cgpa <= :cgpa AND (
        NOT EXISTS (SELECT 1 FROM job_eligible_branches jeb WHERE jeb.job_id = j.job_id) OR
        EXISTS (SELECT 1 FROM job_eligible_branches jeb WHERE jeb.job_id = j.job_id AND jeb.branch_id = :bid)
    )";
    $params['cgpa'] = $studentCgpa;
    $params['bid'] = $studentBranch;
}

$sql .= " GROUP BY j.job_id, c.company_id ORDER BY j.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

sendSuccess('Jobs loaded', [
    'count'        => count($jobs),
    'jobs'         => $jobs,
    'student_cgpa' => $studentCgpa
]);
