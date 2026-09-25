<?php
/**
 * Database Connection & Configuration Handler
 * AI-Based Internship & Placement Management System
 */

// Enable error logging while hiding display errors in production responses
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// CORS & Preflight headers for local development & cross-port preview
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept");

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// JSON Response Helpers
function jsonResponse(array $data, int $statusCode = 200): void {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function sendError(string $message, int $statusCode = 400, array $extra = []): void {
    jsonResponse(array_merge([
        'success' => false,
        'error'   => $message,
    ], $extra), $statusCode);
}

function sendSuccess(string $message, array $data = [], int $statusCode = 200): void {
    jsonResponse(array_merge([
        'success' => true,
        'message' => $message,
    ], $data), $statusCode);
}

// Global exception handler ensuring errors always return JSON
set_exception_handler(function (Throwable $e) {
    error_log("Unhandled exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    sendError('An unexpected server error occurred: ' . $e->getMessage(), 500);
});

class Database {
    // Default XAMPP MySQL Configuration
    private string $host = '127.0.0.1';
    private string $db_name = 'placement_management';
    private string $username = 'root';
    private string $password = '';
    // Auto-check common ports: 3306 (standard MySQL/MariaDB), 3308 (XAMPP alternate)
    private array $ports = [3306, 3308, 3307];
    private ?PDO $conn = null;

    public function __construct() {
        // Allow environment overrides if configured
        if (getenv('DB_HOST')) $this->host = getenv('DB_HOST');
        if (getenv('DB_NAME')) $this->db_name = getenv('DB_NAME');
        if (getenv('DB_USER')) $this->username = getenv('DB_USER');
        if (getenv('DB_PASS') !== false) $this->password = getenv('DB_PASS');
        if (getenv('DB_PORT')) array_unshift($this->ports, (int)getenv('DB_PORT'));
    }

    public function getConnection(): ?PDO {
        if ($this->conn !== null) {
            return $this->conn;
        }

        $lastException = null;

        foreach ($this->ports as $port) {
            try {
                $dsn = "mysql:host={$this->host};port={$port};dbname={$this->db_name};charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => true,
                    PDO::ATTR_TIMEOUT            => 2,
                ];

                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
                return $this->conn;
            } catch (PDOException $e) {
                $lastException = $e;
                // If database doesn't exist, try connecting without dbname to create it if possible
                if ($e->getCode() == 1049) {
                    try {
                        $dsnNoDb = "mysql:host={$this->host};port={$port};charset=utf8mb4";
                        $initConn = new PDO($dsnNoDb, $this->username, $this->password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                        $initConn->exec("CREATE DATABASE IF NOT EXISTS `{$this->db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        $this->conn = new PDO($dsn, $this->username, $this->password, $options);
                        return $this->conn;
                    } catch (Exception $inner) {
                        $lastException = $inner;
                    }
                }
                continue;
            }
        }

        error_log("Database connection failure: " . ($lastException ? $lastException->getMessage() : 'Unknown error'));
        return null;
    }
}

function getDatabaseConnection(): PDO {
    static $db = null;
    if ($db === null) {
        $db = new Database();
    }
    $conn = $db->getConnection();
    if (!$conn) {
        sendError('Database connection failed. Ensure MySQL is running in XAMPP on port 3306 or 3308.', 500);
    }
    return $conn;
}
