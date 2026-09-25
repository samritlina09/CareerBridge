<?php
/**
 * Automated Database Initializer & Importer
 */

$ports = [3306, 3308, 3307];
$dbName = 'placement_management';
$user = 'root';
$pass = '';

$pdo = null;
$connectedPort = null;

foreach ($ports as $port) {
    try {
        $test = new PDO("mysql:host=127.0.0.1;port={$port}", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $pdo = $test;
        $connectedPort = $port;
        echo "[OK] Connected to MySQL on port {$port}\n";
        break;
    } catch (Exception $e) {
        // continue
    }
}

if (!$pdo) {
    die("[ERROR] Could not connect to MySQL on any port (3306, 3308, 3307).\n");
}

// 1. Create database
$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo "[OK] Database `{$dbName}` verified/created.\n";
$pdo->exec("USE `{$dbName}`");

// List of SQL files to import in exact order
$files = [
    __DIR__ . '/../database/schema.sql',
    __DIR__ . '/../database/indexes.sql',
    __DIR__ . '/../database/views.sql',
    __DIR__ . '/../database/procedures.sql',
    __DIR__ . '/../database/functions.sql',
    __DIR__ . '/../database/triggers.sql',
    __DIR__ . '/../database/sample_data.sql',
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "[WARN] File not found: {$file}\n";
        continue;
    }

    $basename = basename($file);
    echo "Importing {$basename} ... ";
    $sql = file_get_contents($file);

    // If procedures/triggers/functions, remove DELIMITER statements for PDO
    $sql = preg_replace('/DELIMITER\s+[^\n\r]+/i', '', $sql);
    $sql = str_replace('$$', ';', $sql);

    try {
        $pdo->exec($sql);
        echo "[SUCCESS]\n";
    } catch (Exception $e) {
        // If batch fails, split by semicolon and execute queries one by one
        $queries = explode(';', $sql);
        $errors = 0;
        foreach ($queries as $q) {
            $trimmed = trim($q);
            if (empty($trimmed)) continue;
            try {
                $pdo->exec($trimmed);
            } catch (Exception $qe) {
                // Ignore harmless table exists/duplicate warnings
                $msg = $qe->getMessage();
                if (!str_contains($msg, 'already exists') && !str_contains($msg, 'Duplicate')) {
                    $errors++;
                }
            }
        }
        echo "[PARTIAL/SPLIT] ({$errors} notable errors)\n";
    }
}

// Verify tables count
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "\n============================================\n";
echo "Total tables in `{$dbName}`: " . count($tables) . "\n";
foreach ($tables as $t) {
    $cnt = $pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
    echo "  - {$t}: {$cnt} rows\n";
}
echo "============================================\n";
echo "DATABASE INITIALIZATION COMPLETE!\n";
