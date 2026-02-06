<?php
// Load environment variables from .env file if it exists
if (file_exists(__DIR__ . '/../../.env')) {
    $env = parse_ini_file(__DIR__ . '/../../.env', false, INI_SCANNER_RAW);
    
    if ($env === false) {
        die('Database configuration error: Failed to parse .env file. Please check the file format.');
    }
    
    // Only load database-related environment variables for security
    $allowedKeys = ['DB_HOST', 'DB_USER', 'DB_PASS', 'DB_NAME'];
    foreach ($env as $key => $value) {
        if (in_array($key, $allowedKeys, true)) {
            $_ENV[$key] = $value;
        }
    }
}

// Get database credentials from environment variables
// DB_HOST defaults to 'localhost' as it's a common default for most installations
$DB_HOST = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'localhost';
$DB_USER = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? '';
$DB_PASS = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? '';
$DB_NAME = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? '';

// Validate that all required credentials are set
// Note: DB_HOST defaults to 'localhost' and is not validated as empty
if (empty($DB_USER) || empty($DB_NAME) || empty($DB_PASS)) {
    die('Database configuration error: Missing required environment variables (DB_USER, DB_NAME, or DB_PASS). Please copy .env.example to .env and configure your database credentials.');
}

$pdo = new PDO(
    "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
    $DB_USER,
    $DB_PASS,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);
