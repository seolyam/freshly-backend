<?php
// src/database.php

require_once __DIR__ . '/../vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Initialize Logger
$log = new Logger('freshly_logger');
$log->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG));

// Load environment variables (only in local environment)
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Get the DATABASE_URL environment variable
$databaseUrl = getenv('MYSQL_URL') ?: getenv('DATABASE_URL');

// Fallback to individual variables if DATABASE_URL is not set
if ($databaseUrl) {
    // Parse the URL
    $parsedUrl = parse_url($databaseUrl);

    $host = $parsedUrl['host'];
    $port = $parsedUrl['port'] ?? 3306; // Default to 3306 if port is not set
    $database = ltrim($parsedUrl['path'], '/');
    $username = $parsedUrl['user'];
    $password = $parsedUrl['pass'];
} else {
    // Use individual environment variables
    $host = getenv('MYSQLHOST') ?: '127.0.0.1';
    $port = getenv('MYSQLPORT') ?: '3306';
    $database = getenv('MYSQLDATABASE') ?: 'railway';
    $username = getenv('MYSQLUSER') ?: 'root';
    $password = getenv('MYSQLPASSWORD') ?: '';
}

// Set DSN (Data Source Name)
$dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8";

try {
    // Create a PDO instance
    $pdo = new PDO($dsn, $username, $password);

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
