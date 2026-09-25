<?php
/**
 * Registration Endpoint
 * Handles Student and Recruiter registration. Admin creation is strictly prohibited.
 */

require_once __DIR__ . '/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method Not Allowed', 405);
}

$input = getJsonInput();
$role = strtoupper(trim($input['role'] ?? ''));

if (!in_array($role, ['STUDENT', 'RECRUITER'], true)) {
    sendError('Registration role must be either STUDENT or RECRUITER.');
}

$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendError('Please enter a valid email address.');
}

if (strlen($password) < 6) {
    sendError('Password must be at least 6 characters long.');
}

$pdo = getDatabaseConnection();

// Check if email already exists
$checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
$checkStmt->execute(['email' => $email]);
if ($checkStmt->fetch()) {
    sendError('An account with this email address already exists.');
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();

    if ($role === 'STUDENT') {
        $rollNumber = trim($input['roll_number'] ?? '');
        $firstName = trim($input['first_name'] ?? '');
        $lastName = trim($input['last_name'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $branchId = !empty($input['branch_id']) ? (int)$input['branch_id'] : null;
        $cgpa = isset($input['cgpa']) ? (float)$input['cgpa'] : 0.00;
        $currentYear = !empty($input['current_year']) ? (int)$input['current_year'] : 4;

        if (empty($rollNumber) || empty($firstName) || empty($lastName)) {
            $pdo->rollBack();
            sendError('Roll number, first name, and last name are required for students.');
        }

        // Check if roll number exists
        $rollStmt = $pdo->prepare("SELECT student_id FROM students WHERE roll_number = :roll");
        $rollStmt->execute(['roll' => $rollNumber]);
        if ($rollStmt->fetch()) {
            $pdo->rollBack();
            sendError('This roll number is already registered.');
        }

        // Create User
        $uStmt = $pdo->prepare("INSERT INTO users (email, password_hash, role, status) VALUES (:email, :hash, 'STUDENT', 'ACTIVE')");
        $uStmt->execute(['email' => $email, 'hash' => $passwordHash]);
        $userId = (int)$pdo->lastInsertId();

        // Create Student Profile
        $sStmt = $pdo->prepare("INSERT INTO students (user_id, roll_number, first_name, last_name, phone, branch_id, current_year, cgpa)
                                VALUES (:uid, :roll, :fname, :lname, :phone, :bid, :yr, :cgpa)");
        $sStmt->execute([
            'uid'   => $userId,
            'roll'  => $rollNumber,
            'fname' => $firstName,
            'lname' => $lastName,
            'phone' => $phone,
            'bid'   => $branchId,
            'yr'    => $currentYear,
            'cgpa'  => $cgpa
        ]);
        $studentId = (int)$pdo->lastInsertId();

        // Add welcome notification
        $nStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, link_url) VALUES (:uid, :title, :msg, :link)");
        $nStmt->execute([
            'uid'   => $userId,
            'title' => 'Welcome to Campus Placement Portal!',
            'msg'   => 'Your account is active. Please complete your profile and skills to unlock AI-based job recommendations.',
            'link'  => 'profile.html'
        ]);

        $pdo->commit();

        sendSuccess('Student registration completed successfully. You can now log in.', [
            'role' => 'STUDENT',
            'redirect' => 'login.html'
        ]);

    } elseif ($role === 'RECRUITER') {
        $recruiterName = trim($input['recruiter_name'] ?? '');
        $companyName = trim($input['company_name'] ?? '');
        $designation = trim($input['designation'] ?? 'Hiring Specialist');
        $phone = trim($input['phone'] ?? '');
        $website = trim($input['website'] ?? '');
        $location = trim($input['location'] ?? '');
        $industry = trim($input['industry'] ?? 'Technology');

        if (empty($companyName)) {
            $pdo->rollBack();
            sendError('Company name is required for recruiter registration.');
        }

        if (empty($recruiterName)) {
            $prefix = explode('@', $email)[0];
            $recruiterName = ucwords(str_replace(['.', '_', '-'], ' ', $prefix));
        }

        // Find or create company
        $cStmt = $pdo->prepare("SELECT company_id, is_verified FROM companies WHERE company_name = :cname LIMIT 1");
        $cStmt->execute(['cname' => $companyName]);
        $existingComp = $cStmt->fetch();

        if ($existingComp) {
            $companyId = (int)$existingComp['company_id'];
        } else {
            $compInsert = $pdo->prepare("INSERT INTO companies (company_name, website, location, industry, is_verified) VALUES (:name, :web, :loc, :ind, 1)");
            $compInsert->execute([
                'name' => $companyName,
                'web'  => $website,
                'loc'  => $location,
                'ind'  => $industry
            ]);
            $companyId = (int)$pdo->lastInsertId();
        }

        // Create User with ACTIVE status (can log in and access portal immediately)
        $uStmt = $pdo->prepare("INSERT INTO users (email, password_hash, role, status) VALUES (:email, :hash, 'RECRUITER', 'ACTIVE')");
        $uStmt->execute(['email' => $email, 'hash' => $passwordHash]);
        $userId = (int)$pdo->lastInsertId();

        // Create Recruiter record with APPROVED status immediately
        $rStmt = $pdo->prepare("INSERT INTO recruiters (user_id, recruiter_name, company_id, designation, phone, approval_status, approved_at)
                                VALUES (:uid, :rname, :cid, :desig, :phone, 'APPROVED', NOW())");
        $rStmt->execute([
            'uid'   => $userId,
            'rname' => $recruiterName,
            'cid'   => $companyId,
            'desig' => $designation,
            'phone' => $phone
        ]);

        // Send welcome notification to Recruiter
        $recNotif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, link_url) VALUES (:uid, :title, :msg, :link)");
        $recNotif->execute([
            'uid'   => $userId,
            'title' => 'Welcome to CareerBridge',
            'msg'   => "Your recruiter account for {$companyName} is ready. You can now post campus job opportunities for review.",
            'link'  => 'post-job.html'
        ]);

        $pdo->commit();

        sendSuccess('Registration successful! You can now log in to post jobs.', [
            'role'     => 'RECRUITER',
            'status'   => 'APPROVED',
            'redirect' => 'login.html'
        ]);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    sendError('Registration failed: ' . $e->getMessage(), 500);
}
