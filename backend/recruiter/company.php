<?php
/**
 * Recruiter Company Profile Endpoint
 */

require_once __DIR__ . '/../auth/session.php';
$user = requireRole('RECRUITER');
$recruiterId = $user['entity_id'];
$pdo = getDatabaseConnection();

$method = $_SERVER['REQUEST_METHOD'];

// Fetch recruiter and company info
$stmt = $pdo->prepare("SELECT r.*, c.company_name, c.website, c.industry, c.description, c.location, c.logo_path
                       FROM recruiters r
                       JOIN companies c ON r.company_id = c.company_id
                       WHERE r.recruiter_id = :rid");
$stmt->execute(['rid' => $recruiterId]);
$data = $stmt->fetch();

if (!$data) {
    sendError('Recruiter profile not found.', 404);
}

if ($method === 'GET') {
    sendSuccess('Company profile loaded', ['profile' => $data]);

} elseif ($method === 'POST') {
    $input = getJsonInput();
    $companyName = trim($input['company_name'] ?? '');
    $website = trim($input['website'] ?? '');
    $industry = trim($input['industry'] ?? '');
    $description = trim($input['description'] ?? '');
    $location = trim($input['location'] ?? '');
    $designation = trim($input['designation'] ?? '');
    $phone = trim($input['phone'] ?? '');

    if (empty($companyName)) {
        sendError('Company name cannot be empty.');
    }

    $cUpd = $pdo->prepare("UPDATE companies SET company_name = :cname, website = :web, industry = :ind, description = :desc, location = :loc WHERE company_id = :cid");
    $cUpd->execute([
        'cname' => $companyName,
        'web'   => $website,
        'ind'   => $industry,
        'desc'  => $description,
        'loc'   => $location,
        'cid'   => $data['company_id']
    ]);

    $rUpd = $pdo->prepare("UPDATE recruiters SET designation = :desig, phone = :phone WHERE recruiter_id = :rid");
    $rUpd->execute([
        'desig' => $designation,
        'phone' => $phone,
        'rid'   => $recruiterId
    ]);

    sendSuccess('Company and recruiter profile updated successfully.');
} else {
    sendError('Method Not Allowed', 405);
}
