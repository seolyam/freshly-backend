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

$host = $_ENV['MYSQLHOST'] ?? $_ENV['MYSQL_HOST'] ?? $_SERVER['MYSQLHOST'] ?? $_SERVER['MYSQL_HOST'] ?? null;
$database = $_ENV['MYSQLDATABASE'] ?? $_ENV['MYSQL_DATABASE'] ?? $_SERVER['MYSQLDATABASE'] ?? $_SERVER['MYSQL_DATABASE'] ?? null;
$port = $_ENV['MYSQLPORT'] ?? $_ENV['MYSQL_PORT'] ?? $_SERVER['MYSQLPORT'] ?? $_SERVER['MYSQL_PORT'] ?? '3306';
$username = $_ENV['MYSQLUSER'] ?? $_ENV['MYSQL_USER'] ?? $_SERVER['MYSQLUSER'] ?? $_SERVER['MYSQL_USER'] ?? null;
$password = $_ENV['MYSQLPASSWORD'] ?? $_ENV['MYSQL_PASSWORD'] ?? $_SERVER['MYSQLPASSWORD'] ?? $_SERVER['MYSQL_PASSWORD'] ?? null;


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
