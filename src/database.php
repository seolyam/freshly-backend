<?php
// src/database.php

require_once __DIR__ . '/../vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Initialize Logger
$log = new Logger('freshly_logger');
$log->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG));

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Get the TURSODB_URL environment variable
$databaseUrl = getenv('TURSODB_URL');

if ($databaseUrl) {
    // Parse the libSQL URL
    $parsedUrl = parse_url($databaseUrl);

    if ($parsedUrl === false) {
        $log->error('Invalid TURSODB_URL format.');
        die('Database connection failed.');
    }

    $scheme = $parsedUrl['scheme'] ?? '';
    $host = $parsedUrl['host'] ?? '';
    $port = $parsedUrl['port'] ?? 5432; // Default PostgreSQL port
    $user = $parsedUrl['user'] ?? '';
    $pass = $parsedUrl['pass'] ?? '';
    $path = ltrim($parsedUrl['path'] ?? '', '/');

    // Construct PostgreSQL DSN
    $dsn = "pgsql:host={$host};port={$port};dbname={$path};";

} else {
    // Fallback for local development (if applicable)
    $dsn = "pgsql:host=localhost;port=5432;dbname=freshly;";
    $user = 'your_local_username';
    $pass = 'your_local_password';
}

try {
    // Create a PDO instance using PostgreSQL driver
    $pdo = new PDO($dsn, $user, $pass);

    // Set PDO attributes for error handling and fetch mode
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Log successful connection
    $log->info('Database connection successful.');

    // Return the PDO instance
    return $pdo;

} catch (PDOException $e) {
    // Log the error
    $log->error('Connection failed: ' . $e->getMessage());
    // Terminate the script with a generic message
    die('Database connection failed.');
}
?>
