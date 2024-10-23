<?php
// src/database.php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Initialize Logger
$log = new Logger('freshly_logger');
$log->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG));

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Retrieve environment variables
$host = $_ENV['MYSQLHOST'] ?? $_SERVER['MYSQLHOST'] ?? null;
$database = $_ENV['MYSQLDATABASE'] ?? $_SERVER['MYSQLDATABASE'] ?? null;
$port = $_ENV['MYSQLPORT'] ?? $_SERVER['MYSQLPORT'] ?? null;
$username = $_ENV['MYSQLUSER'] ?? $_SERVER['MYSQLUSER'] ?? null;
$password = $_ENV['MYSQLPASSWORD'] ?? $_SERVER['MYSQLPASSWORD'] ?? null;

try {
    // Set DSN (Data Source Name)
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8";

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
