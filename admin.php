<?php
require_once __DIR__ . '/backend/auth/session.php';
$_GET['role'] = 'ADMIN';
$_GET['redirect'] = 'admin/dashboard.html';
require_once __DIR__ . '/backend/auth/auto_login.php';
