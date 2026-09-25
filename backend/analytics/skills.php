<?php
/**
 * Skill Demand vs Supply Analytics Endpoint
 */

require_once __DIR__ . '/../auth/session.php';
$user = requireLogin();
$pdo = getDatabaseConnection();

$skillsData = $pdo->query("SELECT sk.skill_name, sk.category,
                                  COUNT(DISTINCT js.job_id) AS jobs_requiring,
                                  COUNT(DISTINCT ss.student_id) AS students_possessing,
                                  (COUNT(DISTINCT js.job_id) - COUNT(DISTINCT ss.student_id)) AS market_gap
                           FROM skills sk
                           LEFT JOIN job_skills js ON sk.skill_id = js.skill_id
                           LEFT JOIN student_skills ss ON sk.skill_id = ss.skill_id
                           GROUP BY sk.skill_id, sk.skill_name, sk.category
                           ORDER BY jobs_requiring DESC, students_possessing DESC
                           LIMIT 12")->fetchAll();

sendSuccess('Skill Analytics', ['skills' => $skillsData]);
