<?php
/**
 * Applications Volume and Status Breakdown Analytics
 */

require_once __DIR__ . '/../auth/session.php';
$user = requireLogin();
$pdo = getDatabaseConnection();

// Status breakdown
$statusDistribution = $pdo->query("SELECT status, COUNT(*) AS count FROM applications GROUP BY status ORDER BY count DESC")->fetchAll();

// Monthly application trend (last 6 months)
$monthlyTrends = $pdo->query("SELECT DATE_FORMAT(applied_at, '%b %Y') AS month_label,
                                     DATE_FORMAT(applied_at, '%Y-%m') AS ym,
                                     COUNT(*) AS total_applications,
                                     COUNT(CASE WHEN status = 'SELECTED' THEN 1 END) AS selected_count
                              FROM applications
                              GROUP BY month_label, ym
                              ORDER BY ym ASC")->fetchAll();

sendSuccess('Applications Analytics', [
    'status_distribution' => $statusDistribution,
    'monthly_trends'      => $monthlyTrends
]);
