<?php
require_once __DIR__ . '/backend/auth/session.php';
$_GET['role'] = 'RECRUITER';
$_GET['redirect'] = 'recruiter/dashboard.html';
require_once __DIR__ . '/backend/auth/auto_login.php';
