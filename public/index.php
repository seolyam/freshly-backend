<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Dotenv\Dotenv;
use App\TursoClient;
use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;
use Slim\Middleware\BodyParsingMiddleware; // Add this line

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Create App
$app = AppFactory::create();

// Add the Body Parsing Middleware
$app->addBodyParsingMiddleware(); // Add this line

// Set up database connection
$databaseUrl = $_ENV['TURSO_DB_URL'] ?? null;
$authToken = $_ENV['TURSO_AUTH_TOKEN'] ?? null;
$tursoClient = new TursoClient($databaseUrl, $authToken);

// Instantiate controllers
$userController = new UserController($tursoClient);

// Routes
$app->post('/register', [$userController, 'register']);
$app->post('/login', [$userController, 'login']);

// Protected route example
$app->get('/protected', function ($request, $response, $args) {
    $user = $request->getAttribute('user');
    $response->getBody()->write("Hello, " . $user['uname']);
    return $response->withHeader('Content-Type', 'text/plain');
})->add(new AuthMiddleware());
$app->get('/profile', [$userController, 'getProfile'])->add(new AuthMiddleware());

// Run app
$app->run();
