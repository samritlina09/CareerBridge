<?php
/**
 * Student Projects Management Endpoint
 */

require_once __DIR__ . '/../auth/session.php';
$user = requireStudent();
$studentId = $user['entity_id'];
$pdo = getDatabaseConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE student_id = :sid ORDER BY created_at DESC");
    $stmt->execute(['sid' => $studentId]);
    sendSuccess('Projects loaded', ['projects' => $stmt->fetchAll()]);

} elseif ($method === 'POST') {
    $input = getJsonInput();
    $projId = !empty($input['project_id']) ? (int)$input['project_id'] : null;
    $title = trim($input['title'] ?? '');
    $description = trim($input['description'] ?? '');
    $technologies = trim($input['technologies'] ?? '');
    $githubUrl = trim($input['github_url'] ?? '');
    $liveUrl = trim($input['live_url'] ?? '');

    if (empty($title)) {
        sendError('Project title is required.');
    }

    if ($projId) {
        $stmt = $pdo->prepare("UPDATE projects SET
            title = :title,
            description = :desc,
            technologies = :tech,
            github_url = :git,
            live_url = :live
            WHERE project_id = :pid AND student_id = :sid");
        $stmt->execute([
            'title' => $title,
            'desc'  => $description,
            'tech'  => $technologies,
            'git'   => $githubUrl,
            'live'  => $liveUrl,
            'pid'   => $projId,
            'sid'   => $studentId
        ]);
        sendSuccess('Project updated successfully.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO projects (student_id, title, description, technologies, github_url, live_url)
                               VALUES (:sid, :title, :desc, :tech, :git, :live)");
        $stmt->execute([
            'sid'   => $studentId,
            'title' => $title,
            'desc'  => $description,
            'tech'  => $technologies,
            'git'   => $githubUrl,
            'live'  => $liveUrl
        ]);
        sendSuccess('Project added successfully.');
    }

} elseif ($method === 'DELETE') {
    $input = getJsonInput();
    $projId = (int)($input['project_id'] ?? ($_GET['project_id'] ?? 0));

    if (empty($projId)) {
        sendError('Project ID is required.');
    }

    $stmt = $pdo->prepare("DELETE FROM projects WHERE project_id = :pid AND student_id = :sid");
    $stmt->execute(['pid' => $projId, 'sid' => $studentId]);

    sendSuccess('Project deleted.');
} else {
    sendError('Method Not Allowed', 405);
}
