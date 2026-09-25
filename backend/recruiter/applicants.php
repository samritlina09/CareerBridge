<?php
/**
 * Recruiter Applicants Management Endpoint
 */

require_once __DIR__ . '/../auth/session.php';
$user = requireRecruiter();
$companyId = $user['company_id'];
$pdo = getDatabaseConnection();

$method = $_SERVER['REQUEST_METHOD'];

// Single applicant detail lookup
if (isset($_GET['student_id'])) {
    $studentId = (int)$_GET['student_id'];
    
    // Verify applicant has applied to this company's job
    $verify = $pdo->prepare("SELECT a.application_id, a.status, a.applied_at, a.notes, j.title AS job_title, j.job_id 
                             FROM applications a 
                             JOIN jobs j ON a.job_id = j.job_id 
                             WHERE a.student_id = :sid AND j.company_id = :cid");
    $verify->execute(['sid' => $studentId, 'cid' => $companyId]);
    $appRecords = $verify->fetchAll();

    if (empty($appRecords)) {
        sendError('Student is not an applicant to your company jobs.', 403);
    }

    $sStmt = $pdo->prepare("SELECT s.*, u.email, b.branch_name, b.branch_code, d.dept_name
                            FROM students s
                            JOIN users u ON s.user_id = u.user_id
                            LEFT JOIN branches b ON s.branch_id = b.branch_id
                            LEFT JOIN departments d ON b.dept_id = d.dept_id
                            WHERE s.student_id = :sid");
    $sStmt->execute(['sid' => $studentId]);
    $student = $sStmt->fetch();

    // Skills
    $sk = $pdo->prepare("SELECT ss.proficiency_level, s.skill_name, s.category FROM student_skills ss JOIN skills s ON ss.skill_id = s.skill_id WHERE ss.student_id = :sid");
    $sk->execute(['sid' => $studentId]);
    $student['skills'] = $sk->fetchAll();

    // Projects
    $pr = $pdo->prepare("SELECT * FROM projects WHERE student_id = :sid");
    $pr->execute(['sid' => $studentId]);
    $student['projects'] = $pr->fetchAll();

    // Certifications
    $cr = $pdo->prepare("SELECT * FROM certifications WHERE student_id = :sid");
    $cr->execute(['sid' => $studentId]);
    $student['certifications'] = $cr->fetchAll();

    // Education
    $ed = $pdo->prepare("SELECT * FROM education WHERE student_id = :sid");
    $ed->execute(['sid' => $studentId]);
    $student['education'] = $ed->fetchAll();

    sendSuccess('Applicant details loaded', [
        'student'      => $student,
        'applications' => $appRecords
    ]);
}

// List applicants with filters
$jobId = !empty($_GET['job_id']) ? (int)$_GET['job_id'] : null;
$status = trim($_GET['status'] ?? '');
$minCgpa = !empty($_GET['min_cgpa']) ? (float)$_GET['min_cgpa'] : 0.0;
$branchId = !empty($_GET['branch_id']) ? (int)$_GET['branch_id'] : null;
$query = trim($_GET['q'] ?? '');

$sql = "SELECT a.application_id, a.job_id, a.student_id, a.status AS application_status, a.applied_at, a.notes,
               j.title AS job_title, j.salary_stipend, j.job_type,
               s.roll_number, CONCAT(s.first_name, ' ', s.last_name) AS student_name, s.cgpa, s.resume_path,
               u.email AS student_email, s.phone,
               b.branch_name, b.branch_code, d.dept_name,
               (
                   SELECT GROUP_CONCAT(sk.skill_name SEPARATOR ', ')
                   FROM student_skills ss
                   JOIN skills sk ON ss.skill_id = sk.skill_id
                   WHERE ss.student_id = s.student_id
               ) AS student_skills,
               (
                   SELECT COUNT(*) FROM interviews i WHERE i.application_id = a.application_id
               ) AS interview_count
        FROM applications a
        JOIN jobs j ON a.job_id = j.job_id
        JOIN students s ON a.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN branches b ON s.branch_id = b.branch_id
        LEFT JOIN departments d ON b.dept_id = d.dept_id
        WHERE j.company_id = :cid";

$params = ['cid' => $companyId];

if ($jobId) {
    $sql .= " AND a.job_id = :jid";
    $params['jid'] = $jobId;
}

if (!empty($status)) {
    $sql .= " AND a.status = :status";
    $params['status'] = $status;
}

if ($minCgpa > 0) {
    $sql .= " AND s.cgpa >= :min_cgpa";
    $params['min_cgpa'] = $minCgpa;
}

if ($branchId) {
    $sql .= " AND s.branch_id = :bid";
    $params['bid'] = $branchId;
}

if (!empty($query)) {
    $sql .= " AND (s.first_name LIKE :q OR s.last_name LIKE :q OR s.roll_number LIKE :q OR u.email LIKE :q)";
    $params['q'] = "%{$query}%";
}

$sql .= " ORDER BY a.applied_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applicants = $stmt->fetchAll();

// Jobs list for filter dropdown
$jobsList = $pdo->prepare("SELECT job_id, title FROM jobs WHERE company_id = :cid ORDER BY title");
$jobsList->execute(['cid' => $companyId]);

sendSuccess('Applicants loaded', [
    'count'      => count($applicants),
    'applicants' => $applicants,
    'jobs'       => $jobsList->fetchAll()
]);
