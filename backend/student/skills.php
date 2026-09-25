<?php
/**
 * Student Skills Management Endpoint
 */

require_once __DIR__ . '/../auth/session.php';
$user = requireStudent();
$studentId = $user['entity_id'];
$pdo = getDatabaseConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Current skills of student
    $stmt = $pdo->prepare("SELECT ss.student_skill_id, ss.skill_id, ss.proficiency_level, sk.skill_name, sk.category
                           FROM student_skills ss
                           JOIN skills sk ON ss.skill_id = sk.skill_id
                           WHERE ss.student_id = :sid
                           ORDER BY sk.category, sk.skill_name");
    $stmt->execute(['sid' => $studentId]);
    $studentSkills = $stmt->fetchAll();

    // Master skills for selection
    $allSkills = $pdo->query("SELECT skill_id, skill_name, category FROM skills ORDER BY category, skill_name")->fetchAll();

    sendSuccess('Skills loaded', [
        'skills'     => $studentSkills,
        'all_skills' => $allSkills
    ]);

} elseif ($method === 'POST') {
    $input = getJsonInput();
    $skillId = !empty($input['skill_id']) ? (int)$input['skill_id'] : null;
    $customSkill = trim($input['custom_skill'] ?? '');
    $proficiency = strtoupper($input['proficiency_level'] ?? 'INTERMEDIATE');

    if (!in_array($proficiency, ['BEGINNER', 'INTERMEDIATE', 'ADVANCED', 'EXPERT'], true)) {
        $proficiency = 'INTERMEDIATE';
    }

    if (empty($skillId) && !empty($customSkill)) {
        // Insert custom skill into master skills if not exists
        $find = $pdo->prepare("SELECT skill_id FROM skills WHERE LOWER(skill_name) = LOWER(:name)");
        $find->execute(['name' => $customSkill]);
        $existing = $find->fetch();
        if ($existing) {
            $skillId = (int)$existing['skill_id'];
        } else {
            $ins = $pdo->prepare("INSERT INTO skills (skill_name, category) VALUES (:name, 'Technical')");
            $ins->execute(['name' => $customSkill]);
            $skillId = (int)$pdo->lastInsertId();
        }
    }

    if (empty($skillId)) {
        sendError('Please select or specify a skill.');
    }

    $stmt = $pdo->prepare("INSERT INTO student_skills (student_id, skill_id, proficiency_level)
                           VALUES (:sid, :skid, :prof)
                           ON DUPLICATE KEY UPDATE proficiency_level = :prof");
    $stmt->execute([
        'sid'  => $studentId,
        'skid' => $skillId,
        'prof' => $proficiency
    ]);

    sendSuccess('Skill saved successfully.');

} elseif ($method === 'DELETE') {
    $input = getJsonInput();
    $skillId = (int)($input['skill_id'] ?? ($_GET['skill_id'] ?? 0));

    if (empty($skillId)) {
        sendError('Skill ID is required.');
    }

    $stmt = $pdo->prepare("DELETE FROM student_skills WHERE student_id = :sid AND skill_id = :skid");
    $stmt->execute(['sid' => $studentId, 'skid' => $skillId]);

    sendSuccess('Skill removed.');
} else {
    sendError('Method Not Allowed', 405);
}
