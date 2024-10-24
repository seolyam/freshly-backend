<?php
// public/db_test.php

require_once __DIR__ . '/../vendor/autoload.php';

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
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Retrieve environment variables
$host = getenv('MYSQL_URL') ?: getenv('DATABASE_URL'); // Ensure this matches your Railway variable
// Since MYSQL_URL is a full connection string, we need to parse it as done in database.php
$databaseUrl = getenv('MYSQL_URL') ?: getenv('DATABASE_URL');

echo '<pre>';
print_r($_ENV);
echo '</pre>';

try {
    if ($databaseUrl) {
        $parsedUrl = parse_url($databaseUrl);

        $host = $parsedUrl['host'];
        $port = $parsedUrl['port'] ?? 3306;
        $database = ltrim($parsedUrl['path'], '/');
        $username = $parsedUrl['user'];
        $password = $parsedUrl['pass'];
    } else {
        $host = getenv('MYSQLHOST') ?: '127.0.0.1';
        $port = getenv('MYSQLPORT') ?: '3306';
        $database = getenv('MYSQLDATABASE') ?: 'railway';
        $username = getenv('MYSQLUSER') ?: 'root';
        $password = getenv('MYSQLPASSWORD') ?: '';
    }

    // Set DSN (Data Source Name)
    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8";

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
