<?php
/**
 * Student Certifications CRUD Endpoint
 */

require_once __DIR__ . '/../auth/session.php';
$user = requireStudent();
$studentId = $user['entity_id'];
$pdo = getDatabaseConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM certifications WHERE student_id = :sid ORDER BY issue_date DESC");
    $stmt->execute(['sid' => $studentId]);
    sendSuccess('Certifications loaded', ['certifications' => $stmt->fetchAll()]);

} elseif ($method === 'POST') {
    $input = getJsonInput();
    $certId = !empty($input['cert_id']) ? (int)$input['cert_id'] : null;
    $title = trim($input['title'] ?? '');
    $issuingOrg = trim($input['issuing_org'] ?? '');
    $issueDate = !empty($input['issue_date']) ? $input['issue_date'] : null;
    $credentialUrl = trim($input['credential_url'] ?? '');

    if (empty($title) || empty($issuingOrg)) {
        sendError('Certification title and issuing organization are required.');
    }

    if ($certId) {
        $stmt = $pdo->prepare("UPDATE certifications SET title = :title, issuing_org = :org, issue_date = :dt, credential_url = :url WHERE cert_id = :cid AND student_id = :sid");
        $stmt->execute(['title' => $title, 'org' => $issuingOrg, 'dt' => $issueDate, 'url' => $credentialUrl, 'cid' => $certId, 'sid' => $studentId]);
        sendSuccess('Certification updated.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO certifications (student_id, title, issuing_org, issue_date, credential_url) VALUES (:sid, :title, :org, :dt, :url)");
        $stmt->execute(['sid' => $studentId, 'title' => $title, 'org' => $issuingOrg, 'dt' => $issueDate, 'url' => $credentialUrl]);
        sendSuccess('Certification added.');
    }

} elseif ($method === 'DELETE') {
    $input = getJsonInput();
    $certId = (int)($input['cert_id'] ?? ($_GET['cert_id'] ?? 0));
    $stmt = $pdo->prepare("DELETE FROM certifications WHERE cert_id = :cid AND student_id = :sid");
    $stmt->execute(['cid' => $certId, 'sid' => $studentId]);
    sendSuccess('Certification deleted.');
} else {
    sendError('Method Not Allowed', 405);
}
