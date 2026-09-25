<?php
/**
 * Admin Company Management Endpoint
 */

require_once __DIR__ . '/../auth/session.php';
$admin = requireAdmin();
$pdo = getDatabaseConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT c.*, 
                                COUNT(DISTINCT j.job_id) AS total_jobs,
                                COUNT(DISTINCT r.recruiter_id) AS recruiter_count,
                                COUNT(DISTINCT p.placement_id) AS total_hires
                         FROM companies c
                         LEFT JOIN jobs j ON c.company_id = j.company_id
                         LEFT JOIN recruiters r ON c.company_id = r.company_id
                         LEFT JOIN placements p ON c.company_id = p.company_id
                         GROUP BY c.company_id
                         ORDER BY total_hires DESC, c.company_name ASC");
    $companies = $stmt->fetchAll();
    sendSuccess('Companies loaded', ['companies' => $companies]);

} elseif ($method === 'POST') {
    $input = getJsonInput();
    $companyId = !empty($input['company_id']) ? (int)$input['company_id'] : null;
    $name = trim($input['company_name'] ?? '');
    $website = trim($input['website'] ?? '');
    $industry = trim($input['industry'] ?? '');
    $description = trim($input['description'] ?? '');
    $location = trim($input['location'] ?? '');
    $isVerified = isset($input['is_verified']) ? (int)$input['is_verified'] : 1;

    if (empty($name)) {
        sendError('Company name is required.');
    }

    if ($companyId) {
        $stmt = $pdo->prepare("UPDATE companies SET company_name = :name, website = :web, industry = :ind, description = :desc, location = :loc, is_verified = :ver WHERE company_id = :cid");
        $stmt->execute(['name' => $name, 'web' => $website, 'ind' => $industry, 'desc' => $description, 'loc' => $location, 'ver' => $isVerified, 'cid' => $companyId]);
        sendSuccess('Company updated.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO companies (company_name, website, industry, description, location, is_verified) VALUES (:name, :web, :ind, :desc, :loc, :ver)");
        $stmt->execute(['name' => $name, 'web' => $website, 'ind' => $industry, 'desc' => $description, 'loc' => $location, 'ver' => $isVerified]);
        sendSuccess('Company added successfully.');
    }
} else {
    sendError('Method Not Allowed', 405);
}
