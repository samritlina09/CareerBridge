<?php
/**
 * Student Resume Upload & Retrieval Endpoint
 */

require_once __DIR__ . '/../auth/session.php';
$user = requireStudent();
$studentId = $user['entity_id'];
$pdo = getDatabaseConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("SELECT resume_path FROM students WHERE student_id = :sid");
    $stmt->execute(['sid' => $studentId]);
    $path = $stmt->fetchColumn();
    sendSuccess('Resume info', ['resume_path' => $path]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method Not Allowed', 405);
}

if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
    sendError('Please choose a valid resume file to upload.');
}

$file = $_FILES['resume'];
$maxSize = 5 * 1024 * 1024; // 5 MB

if ($file['size'] > $maxSize) {
    sendError('File size exceeds the 5MB limit.');
}

$allowedExtensions = ['pdf', 'doc', 'docx'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExtensions, true)) {
    sendError('Invalid file type. Only PDF and Word documents are permitted.');
}

// Upload directory setup
$uploadDir = __DIR__ . '/../../assets/uploads/resumes/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$newFileName = 'resume_std_' . $studentId . '_' . time() . '.' . $ext;
$targetPath = $uploadDir . $newFileName;
$relativePath = 'assets/uploads/resumes/' . $newFileName;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    sendError('Failed to save the uploaded file on the server.', 500);
}

// Update database record
$update = $pdo->prepare("UPDATE students SET resume_path = :path WHERE student_id = :sid");
$update->execute(['path' => $relativePath, 'sid' => $studentId]);

// Create notification
$notif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, link_url) VALUES (:uid, 'Resume Uploaded', 'Your resume was successfully uploaded and attached to your profile.', 'resume.html')");
$notif->execute(['uid' => $user['user_id']]);

sendSuccess('Resume uploaded successfully!', [
    'resume_path' => $relativePath,
    'file_name'   => $file['name']
]);
