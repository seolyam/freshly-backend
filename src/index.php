<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Illuminate\Database\Capsule\Manager as Capsule;

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/database.php';  // Include your database configuration

$app = AppFactory::create();

// Use the port from the environment variable (default to 8080 if not set)
$port = getenv('PORT') ?: 8080;

// Define a simple route to test database connection
$app->get('/test-db', function (Request $request, Response $response, $args): Response {
    try {
        $products = Capsule::table('products')->get(); // Assuming you have a 'products' table
        $response->getBody()->write($products->toJson());
    } catch (Exception $e) {
        $response->getBody()->write("Database connection failed: " . $e->getMessage());
    }

    return $response;
});

// Run the application on 0.0.0.0 to bind to all available interfaces and use the provided port
$app->run('0.0.0.0', $port);
