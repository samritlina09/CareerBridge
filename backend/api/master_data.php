<?php
/**
 * Master Data Endpoint
 * Public reference data for branches and skills used across form dropdowns and checkboxes.
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDatabaseConnection();

$branches = $pdo->query("SELECT branch_id, branch_name, branch_code FROM branches ORDER BY branch_name")->fetchAll();
$skills = $pdo->query("SELECT skill_id, skill_name, category FROM skills ORDER BY category, skill_name")->fetchAll();

sendSuccess('Master reference data loaded', [
    'branches' => $branches,
    'skills'   => $skills
]);
