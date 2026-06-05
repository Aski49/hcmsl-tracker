<?php
// ============================================================
//  db.php — Database connection with full error diagnostics
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');   // default XAMPP username
define('DB_PASS', '');       // default XAMPP password is EMPTY
define('DB_NAME', 'hcmsl_tracker');

// CORS & JSON headers — must come before any output
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle pre-flight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// 1. Check if PDO MySQL driver is available
if (!extension_loaded('pdo_mysql')) {
    http_response_code(500);
    echo json_encode([
        'error'  => 'Database not connected',
        'reason' => 'PHP pdo_mysql extension is not enabled. Enable it in php.ini'
    ]);
    exit;
}

// 2. Try to connect
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);

    $code    = $e->getCode();
    $message = $e->getMessage();

    // Give a human-friendly reason based on the error code
    if ($code == 1049) {
        $reason = "Database 'hcmsl_tracker' does not exist. Please run database.sql in phpMyAdmin first.";
    } elseif ($code == 1045) {
        $reason = "Wrong username or password. Check DB_USER and DB_PASS in db.php.";
    } elseif ($code == 2002) {
        $reason = "Cannot reach MySQL server. Make sure MySQL is Running in XAMPP Control Panel.";
    } else {
        $reason = $message;
    }

    echo json_encode([
        'error'  => 'Database not connected',
        'code'   => $code,
        'reason' => $reason
    ]);
    exit;
}