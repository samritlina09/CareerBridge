<?php
/**
 * Authentication, Session & Role-Based Access Control (RBAC)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400, // 24 hours
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/../config/database.php';

const CAREERBRIDGE_AUTH_SECRET = 'careerbridge_jwt_token_secret_key_2026';

function generateAuthToken(int $userId, string $role, ?int $entityId = null): string {
    $payload = base64_encode(json_encode([
        'user_id'   => $userId,
        'role'      => $role,
        'entity_id' => $entityId,
        'created'   => time()
    ]));
    $sig = hash_hmac('sha256', $payload, CAREERBRIDGE_AUTH_SECRET);
    return $payload . '.' . $sig;
}

function authenticateFromToken(): void {
    $token = '';
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (empty($authHeader) && function_exists('getallheaders')) {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if (!empty($authHeader) && preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
        $token = $matches[1];
    } elseif (!empty($_GET['token']) && is_string($_GET['token'])) {
        $token = trim($_GET['token']);
    }

    if (empty($token)) {
        return;
    }
    $parts = explode('.', $token);
    if (count($parts) !== 2) {
        return;
    }

    list($payloadB64, $signature) = $parts;
    $expected = hash_hmac('sha256', $payloadB64, CAREERBRIDGE_AUTH_SECRET);
    if (!hash_equals($expected, $signature)) {
        return;
    }

    $data = json_decode(base64_decode($payloadB64), true);
    if (!$data || empty($data['user_id'])) {
        return;
    }

    $uid = (int)$data['user_id'];
    // If session is already authenticated as this exact user, keep it
    if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $uid) {
        return;
    }

    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("SELECT user_id, email, role, status FROM users WHERE user_id = :uid LIMIT 1");
    $stmt->execute(['uid' => $uid]);
    $u = $stmt->fetch();

    if ($u && $u['status'] !== 'SUSPENDED') {
        $_SESSION['user_id'] = (int)$u['user_id'];
        $_SESSION['email'] = $u['email'];
        $_SESSION['role'] = $u['role'];
        $_SESSION['status'] = $u['status'];
        $_SESSION['entity_id'] = $data['entity_id'] ?? null;

        if ($u['role'] === 'STUDENT') {
            $st = $pdo->prepare("SELECT student_id, first_name, last_name FROM students WHERE user_id = :uid");
            $st->execute(['uid' => $u['user_id']]);
            $s = $st->fetch();
            if ($s) {
                $_SESSION['entity_id'] = (int)$s['student_id'];
                $_SESSION['name'] = trim($s['first_name'] . ' ' . $s['last_name']);
            }
        } elseif ($u['role'] === 'RECRUITER') {
            $rt = $pdo->prepare("SELECT r.recruiter_id, r.recruiter_name, r.company_id, r.approval_status, c.company_name 
                                 FROM recruiters r 
                                 JOIN companies c ON r.company_id = c.company_id 
                                 WHERE r.user_id = :uid");
            $rt->execute(['uid' => $u['user_id']]);
            $r = $rt->fetch();
            if ($r) {
                $_SESSION['entity_id'] = (int)$r['recruiter_id'];
                $_SESSION['company_id'] = (int)$r['company_id'];
                $_SESSION['approval_status'] = $r['approval_status'];
                $_SESSION['company_name'] = $r['company_name'];
                $_SESSION['name'] = !empty($r['recruiter_name']) ? $r['recruiter_name'] : ($r['company_name'] . ' Recruiter');
            }
        } elseif ($u['role'] === 'ADMIN') {
            $_SESSION['name'] = 'T&P Administrator';
        }
    }
}

function getJsonInput(): array {
    if (empty($raw = file_get_contents('php://input'))) {
        return $_POST ?? [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function isLoggedIn(): bool {
    authenticateFromToken();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'user_id'         => $_SESSION['user_id'],
        'email'           => $_SESSION['email'] ?? '',
        'role'            => $_SESSION['role'] ?? '',
        'status'          => $_SESSION['status'] ?? 'ACTIVE',
        'entity_id'       => $_SESSION['entity_id'] ?? null, // student_id or recruiter_id
        'company_id'      => $_SESSION['company_id'] ?? null,
        'company_name'    => $_SESSION['company_name'] ?? null,
        'approval_status' => $_SESSION['approval_status'] ?? 'APPROVED',
        'name'            => $_SESSION['name'] ?? '',
    ];
}

function requireLogin(): array {
    if (!isLoggedIn()) {
        sendError('Authentication required. Please log in.', 401);
    }
    return getCurrentUser();
}

function requireRole($roles): array {
    $user = requireLogin();
    if (is_string($roles)) {
        $roles = [$roles];
    }
    if (!in_array($user['role'], $roles, true)) {
        sendError('Access forbidden: You do not possess the required authorization (' . implode(', ', $roles) . ').', 403);
    }
    return $user;
}

function requireStudent(): array {
    return requireRole('STUDENT');
}

function requireRecruiter(): array {
    $user = requireRole('RECRUITER');
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("SELECT r.approval_status, r.recruiter_name, c.company_name, c.is_verified 
                            FROM recruiters r 
                            JOIN companies c ON r.company_id = c.company_id 
                            WHERE r.recruiter_id = :rid");
    $stmt->execute(['rid' => $user['entity_id']]);
    $rec = $stmt->fetch();
    if ($rec) {
        $user['approval_status'] = $rec['approval_status'];
        $user['company_name'] = $rec['company_name'];
        $user['is_verified'] = (int)$rec['is_verified'];
        $_SESSION['approval_status'] = $rec['approval_status'];
    }
    return $user;
}

function requireApprovedRecruiter(): array {
    $user = requireRecruiter();
    $status = $user['approval_status'] ?? 'PENDING';
    if ($status !== 'APPROVED') {
        if ($status === 'REJECTED') {
            sendError('Your recruiter account registration was rejected by the Training & Placement Cell.', 403, [
                'approval_status' => 'REJECTED'
            ]);
        }
        sendError('Your recruiter account is waiting for admin approval before you can post jobs.', 403, [
            'approval_status' => 'PENDING'
        ]);
    }
    return $user;
}

function requireAdmin(): array {
    return requireRole('ADMIN');
}
