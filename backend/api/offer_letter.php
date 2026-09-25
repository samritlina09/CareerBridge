<?php
/**
 * Dynamic Offer Letter Generator & Viewer Endpoint
 * Serves or generates official campus placement offer letters for students & admins.
 */

require_once __DIR__ . '/../config/database.php';

$placementId = (int)($_GET['placement_id'] ?? $_GET['id'] ?? 0);
$appId = (int)($_GET['application_id'] ?? $_GET['app_id'] ?? 0);

if ($placementId <= 0 && $appId <= 0) {
    http_response_code(400);
    die('Invalid request: placement_id or application_id required.');
}

$pdo = getDatabaseConnection();

$sql = "SELECT p.placement_id, p.package_lpa, p.accepted_at, p.offer_letter_path,
               s.student_id, s.roll_number, CONCAT(s.first_name, ' ', s.last_name) AS student_name,
               u.email AS student_email,
               b.branch_code, b.branch_name, d.dept_name,
               c.company_name,
               j.title AS job_title, j.job_type, j.work_mode, j.location AS job_location
        FROM placements p
        JOIN students s ON p.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN branches b ON s.branch_id = b.branch_id
        LEFT JOIN departments d ON b.dept_id = d.dept_id
        JOIN companies c ON p.company_id = c.company_id
        JOIN jobs j ON p.job_id = j.job_id
        WHERE " . ($placementId > 0 ? "p.placement_id = :val" : "p.application_id = :val") . "
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute(['val' => $placementId > 0 ? $placementId : $appId]);
$p = $stmt->fetch();

if (!$p) {
    http_response_code(404);
    die('Placement offer record not found.');
}

$projectRoot = dirname(__DIR__, 2);
$offersDir = $projectRoot . '/assets/uploads/offers/';
if (!is_dir($offersDir)) {
    mkdir($offersDir, 0777, true);
}

$filePath = !empty($p['offer_letter_path']) ? $projectRoot . '/' . ltrim($p['offer_letter_path'], '/') : '';

// If physical file doesn't exist on disk, generate it dynamically
if (empty($filePath) || !file_exists($filePath)) {
    $cleanStudent = preg_replace('/[^a-zA-Z0-9]+/', '_', strtolower($p['student_name']));
    $cleanCompany = preg_replace('/[^a-zA-Z0-9]+/', '_', strtolower($p['company_name']));
    $fileName = "offer_{$cleanStudent}_{$cleanCompany}.pdf";
    $relPath = "assets/uploads/offers/{$fileName}";
    $filePath = $projectRoot . '/' . $relPath;

    $dateStr = date('d M Y', strtotime($p['accepted_at'] ?? 'now'));
    $pdfData = buildOfferPdf(
        $p['student_name'],
        $p['roll_number'],
        $p['company_name'],
        $p['job_title'],
        (string)$p['package_lpa'],
        $dateStr
    );

    file_put_contents($filePath, $pdfData);

    $upStmt = $pdo->prepare("UPDATE placements SET offer_letter_path = :path WHERE placement_id = :pid");
    $upStmt->execute(['path' => $relPath, 'pid' => $p['placement_id']]);
}

// Serve PDF inline in the browser
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($filePath);
exit;

/**
 * Builds a lightweight standalone valid PDF document
 */
function buildOfferPdf(string $studentName, string $rollNumber, string $company, string $role, string $package, string $date): string {
    $studentName = str_replace(['(', ')'], '', $studentName);
    $company = str_replace(['(', ')'], '', $company);
    $role = str_replace(['(', ')'], '', $role);
    $rollNumber = str_replace(['(', ')'], '', $rollNumber);
    $ref = "REF: CB/OFFER/" . strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $company), 0, 3)) . "/" . date('Y') . "/" . rand(100, 999);

    $text = "BT\n"
          . "/F1 20 Tf\n"
          . "50 730 Td\n"
          . "($company) Tj\n"
          . "/F1 14 Tf\n"
          . "0 -28 Td\n"
          . "(OFFICIAL CAMPUS PLACEMENT OFFER LETTER) Tj\n"
          . "/F1 10 Tf\n"
          . "0 -22 Td\n"
          . "($ref    |    Issue Date: $date) Tj\n"
          . "/F1 11 Tf\n"
          . "0 -35 Td\n"
          . "(Dear $studentName [Roll No: $rollNumber],) Tj\n"
          . "0 -24 Td\n"
          . "(We are pleased to extend this formal offer of employment on behalf of $company.) Tj\n"
          . "0 -20 Td\n"
          . "(Following the Campus Recruitment Drive, you have been selected for:) Tj\n"
          . "/F1 12 Tf\n"
          . "0 -28 Td\n"
          . "(Designation: $role) Tj\n"
          . "0 -20 Td\n"
          . "(Annual CTC: Rs. $package Lakhs Per Annum - LPA) Tj\n"
          . "/F1 10 Tf\n"
          . "0 -30 Td\n"
          . "(Terms & Conditions:) Tj\n"
          . "0 -18 Td\n"
          . "(1. This offer is contingent upon successful completion of your graduating degree.) Tj\n"
          . "0 -16 Td\n"
          . "(2. Reporting instructions and onboarding schedules will follow via email.) Tj\n"
          . "0 -16 Td\n"
          . "(3. Verified through CareerBridge Campus Placement Management System.) Tj\n"
          . "/F1 10 Tf\n"
          . "0 -45 Td\n"
          . "(Authorized Signatory: Head - Talent Acquisition, $company) Tj\n"
          . "0 -20 Td\n"
          . "(Endorsed by: Training & Placement Cell - Central Directorate) Tj\n"
          . "ET\n";

    $len = strlen($text);

    $obj1 = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $obj2 = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
    $obj3 = "3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 4 0 R >> >> /MediaBox [0 0 612 792] /Contents 5 0 R >>\nendobj\n";
    $obj4 = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n";
    $obj5 = "5 0 obj\n<< /Length $len >>\nstream\n" . $text . "endstream\nendobj\n";

    $header = "%PDF-1.4\n";
    $offset1 = strlen($header);
    $offset2 = $offset1 + strlen($obj1);
    $offset3 = $offset2 + strlen($obj2);
    $offset4 = $offset3 + strlen($obj3);
    $offset5 = $offset4 + strlen($obj4);
    $xrefOffset = $offset5 + strlen($obj5);

    $body = $header . $obj1 . $obj2 . $obj3 . $obj4 . $obj5;

    $xref = sprintf(
        "xref\n0 6\n0000000000 65535 f \n%010d 00000 n \n%010d 00000 n \n%010d 00000 n \n%010d 00000 n \n%010d 00000 n \n",
        $offset1, $offset2, $offset3, $offset4, $offset5
    );

    $trailer = "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF\n";

    return $body . $xref . $trailer;
}
