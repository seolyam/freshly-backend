<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\TursoClient;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Retrieve environment variables
$databaseUrl = $_ENV['TURSO_DB_URL'] ?? null;
$authToken = $_ENV['TURSO_AUTH_TOKEN'] ?? null;

if (!$databaseUrl || !$authToken) {
    die("Environment variables TURSO_DB_URL and TURSO_AUTH_TOKEN must be set.\n");
}

$tursoClient = new TursoClient($databaseUrl, $authToken);

try {
    $createProductsTableSQL = "
        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            price REAL NOT NULL
        );
    ";

    $createUsersTableSQL = "
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ";

    $tursoClient->execute($createProductsTableSQL);
    $tursoClient->execute($createUsersTableSQL);

    echo "Database initialized successfully.\n";
} catch (\Exception $e) {
    echo "Database initialization failed: " . $e->getMessage() . "\n";
    exit(1);
}
