<?php
/**
 * Student Education CRUD Endpoint
 */

require_once __DIR__ . '/../auth/session.php';
$user = requireStudent();
$studentId = $user['entity_id'];
$pdo = getDatabaseConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM education WHERE student_id = :sid ORDER BY passing_year DESC");
    $stmt->execute(['sid' => $studentId]);
    sendSuccess('Education records', ['education' => $stmt->fetchAll()]);

} elseif ($method === 'POST') {
    $input = getJsonInput();
    $eduId = !empty($input['education_id']) ? (int)$input['education_id'] : null;
    $degree = trim($input['degree'] ?? '');
    $institution = trim($input['institution'] ?? '');
    $boardUniv = trim($input['board_university'] ?? '');
    $passingYear = (int)($input['passing_year'] ?? 0);
    $score = (float)($input['score_percentage'] ?? 0);

    if (empty($degree) || empty($institution) || empty($passingYear)) {
        sendError('Degree, institution, and passing year are required.');
    }

    if ($eduId) {
        $stmt = $pdo->prepare("UPDATE education SET degree = :deg, institution = :inst, board_university = :board, passing_year = :yr, score_percentage = :score WHERE education_id = :eid AND student_id = :sid");
        $stmt->execute(['deg' => $degree, 'inst' => $institution, 'board' => $boardUniv, 'yr' => $passingYear, 'score' => $score, 'eid' => $eduId, 'sid' => $studentId]);
        sendSuccess('Education record updated.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO education (student_id, degree, institution, board_university, passing_year, score_percentage) VALUES (:sid, :deg, :inst, :board, :yr, :score)");
        $stmt->execute(['sid' => $studentId, 'deg' => $degree, 'inst' => $institution, 'board' => $boardUniv, 'yr' => $passingYear, 'score' => $score]);
        sendSuccess('Education record added.');
    }

} elseif ($method === 'DELETE') {
    $input = getJsonInput();
    $eduId = (int)($input['education_id'] ?? ($_GET['education_id'] ?? 0));
    $stmt = $pdo->prepare("DELETE FROM education WHERE education_id = :eid AND student_id = :sid");
    $stmt->execute(['eid' => $eduId, 'sid' => $studentId]);
    sendSuccess('Education record deleted.');
} else {
    sendError('Method Not Allowed', 405);
}
