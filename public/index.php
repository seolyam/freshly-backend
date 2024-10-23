
<?php
// Enable error reporting for debugging (Development only)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Rest of your code...

// public/index.php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables (already done in database.php, so this may be redundant)
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Include database.php and get the PDO instance
$pdo = require_once __DIR__ . '/../src/database.php';

$app = AppFactory::create();

// Add Error Middleware
// Set displayErrorDetails to true for development, false for production
$app->addErrorMiddleware(true, true, true);

// Define a simple root route
$app->get('/', function (Request $request, Response $response, $args): Response {
    $response->getBody()->write("Welcome to the Freshly Backend API!");
    return $response;
});

// Define a route to test database connection
$app->get('/test-db', function (Request $request, Response $response, $args) use ($pdo): Response {
    try {
        // Example query: Fetch all products
        $stmt = $pdo->query('SELECT * FROM products'); // Ensure 'products' table exists
        $products = $stmt->fetchAll();

        // Convert to JSON and write to response
        $response->getBody()->write(json_encode($products));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {
        // Log the error
        error_log("Database query failed: " . $e->getMessage());
        // Return a generic error message
        $error = ['error' => 'Database query failed.'];
        $response->getBody()->write(json_encode($error));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// Define additional routes as needed
// Example: Get a specific product by ID
$app->get('/products/{id}', function (Request $request, Response $response, $args) use ($pdo): Response {
    $id = (int)$args['id'];

    try {
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $product = $stmt->fetch();

        if ($product) {
            $response->getBody()->write(json_encode($product));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $error = ['error' => 'Product not found.'];
            $response->getBody()->write(json_encode($error));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
    } catch (PDOException $e) {
        error_log("Database query failed: " . $e->getMessage());
        $error = ['error' => 'Database query failed.'];
        $response->getBody()->write(json_encode($error));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// Run the Slim application
$app->run();
?>
