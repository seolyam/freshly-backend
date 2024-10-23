<?php
// public/db_test.php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize Logger
$log = new Logger('test_logger');
$log->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG));

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

$host = $_ENV['MYSQLHOST'] ?? $_SERVER['MYSQLHOST'] ?? null;
$database = $_ENV['MYSQLDATABASE'] ?? $_SERVER['MYSQLDATABASE'] ?? null;
$port = $_ENV['MYSQLPORT'] ?? $_SERVER['MYSQLPORT'] ?? null;
$username = $_ENV['MYSQLUSER'] ?? $_SERVER['MYSQLUSER'] ?? null;
$password = $_ENV['MYSQLPASSWORD'] ?? $_SERVER['MYSQLPASSWORD'] ?? null;


// After loading the environment variables
echo '<pre>';
print_r($_ENV);
echo '</pre>';



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

    echo "Database connection successful.";

    // Optional: Fetch and display products
    $stmt = $pdo->query('SELECT * FROM products');
    $products = $stmt->fetchAll();

    echo "<pre>";
    print_r($products);
    echo "</pre>";

} catch (PDOException $e) {
    // Log the error
    $log->error('Connection failed: ' . $e->getMessage());
    // Display the error message (Development only)
    die('Database connection failed: ' . $e->getMessage());
}
?>
