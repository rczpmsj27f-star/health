<?php
$DB_HOST = "localhost";
$DB_USER = "u983097270_ht";
$DB_PASS = "Bananas9082!";
$DB_NAME = "u983097270_ht";

$pdo = new PDO(
    "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
    $DB_USER,
    $DB_PASS,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);
