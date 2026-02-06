<?php
// Load environment variables from .env file if it exists
if (file_exists(__DIR__ . '/../../.env')) {
    $env = parse_ini_file(__DIR__ . '/../../.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// Get database credentials from environment variables
$DB_HOST = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'localhost';
$DB_USER = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? '';
$DB_PASS = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? '';
$DB_NAME = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? '';

// Validate that credentials are set
if (empty($DB_USER) || empty($DB_NAME)) {
    die('Database configuration error: Missing required environment variables. Please copy .env.example to .env and configure your database credentials.');
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
