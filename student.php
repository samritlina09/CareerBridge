<?php
require_once __DIR__ . '/backend/auth/session.php';
$_GET['role'] = 'STUDENT';
$_GET['redirect'] = 'student/dashboard.html';
require_once __DIR__ . '/backend/auth/auto_login.php';
