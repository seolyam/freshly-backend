<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Retrieve environment variables
$databaseUrl = $_ENV('TURSO_DB_URL');
$authToken = $_ENV('TURSO_AUTH_TOKEN');

if (!$databaseUrl || !$authToken) {
    die("Environment variables TURSO_DB_URL and TURSO_AUTH_TOKEN must be set.\n");
}

echo "TURSO_DB_URL: " . $databaseUrl . "\n";
echo "TURSO_AUTH_TOKEN: " . $authToken . "\n";
