<?php
/**
 * Admin Skills Master Management Endpoint
 */

require_once __DIR__ . '/../auth/session.php';
$admin = requireAdmin();
$pdo = getDatabaseConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $skills = $pdo->query("SELECT * FROM vw_top_demanded_skills")->fetchAll();
    sendSuccess('Skills loaded', ['skills' => $skills]);

} elseif ($method === 'POST') {
    $input = getJsonInput();
    $name = trim($input['skill_name'] ?? '');
    $category = trim($input['category'] ?? 'Technical');

    if (empty($name)) {
        sendError('Skill name is required.');
    }

    $ins = $pdo->prepare("INSERT INTO skills (skill_name, category) VALUES (:name, :cat) ON DUPLICATE KEY UPDATE category = :cat");
    $ins->execute(['name' => $name, 'cat' => $category]);

    sendSuccess('Skill saved successfully.');

} elseif ($method === 'DELETE') {
    $input = getJsonInput();
    $skillId = (int)($input['skill_id'] ?? ($_GET['skill_id'] ?? 0));

    if (empty($skillId)) {
        sendError('Skill ID is required.');
    }

    $del = $pdo->prepare("DELETE FROM skills WHERE skill_id = :sid");
    $del->execute(['sid' => $skillId]);

    sendSuccess('Skill deleted.');
} else {
    sendError('Method Not Allowed', 405);
}
