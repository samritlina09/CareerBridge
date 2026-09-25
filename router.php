<?php
/**
 * CLI Server Router for clean directory URL redirection
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$cleanUri = trim($uri, '/');
$path = __DIR__ . '/' . $cleanUri;

// Redirect /recruiter or /recruiter/ to /recruiter/dashboard.html
if (in_array($cleanUri, ['recruiter', 'student', 'admin'], true)) {
    header("Location: /" . $cleanUri . "/dashboard.html", true, 302);
    exit;
}

if (is_dir($path) && substr($uri, -1) !== '/') {
    header("Location: " . $uri . "/", true, 301);
    exit;
}

return false;
