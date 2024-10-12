<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Illuminate\Database\Capsule\Manager as Capsule;

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/database.php';  // Adjusted path to database.php

$app = AppFactory::create();

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

$app->get('/', function (Request $request, Response $response, $args): Response {
    $response->getBody()->write("Welcome to the Freshly Backend API!");
    return $response;
});
// Add Error Middleware
$app->addErrorMiddleware(true, true, true);

// Run the application
$app->run();
