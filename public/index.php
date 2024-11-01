<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use App\TursoClient;
use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;
use Slim\Middleware\BodyParsingMiddleware;
use Dotenv\Dotenv;

// Load environment variables explicitly for local development or any other environment
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Confirm environment variables are loaded by logging them
error_log("TURSO_DB_URL: " . ($_ENV['TURSO_DB_URL'] ?? 'Not set'));
error_log("TURSO_AUTH_TOKEN: " . ($_ENV['TURSO_AUTH_TOKEN'] ?? 'Not set'));

// Initialize database connection with environment variables
$databaseUrl = $_ENV['TURSO_DB_URL'];
$authToken = $_ENV['TURSO_AUTH_TOKEN'];
$tursoClient = new TursoClient($databaseUrl, $authToken);

// Set up Slim App
$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// Instantiate controllers
$userController = new UserController($tursoClient);

// Define routes
$app->post('/register', [$userController, 'register']);
$app->post('/login', [$userController, 'login']);
$app->get('/profile', [$userController, 'getProfile'])->add(new AuthMiddleware());
$app->post('/profile', [$userController, 'updateProfile'])->add(new AuthMiddleware());

// Test route for direct database query
$app->get('/test-user', function ($request, $response) use ($tursoClient) {
    $user = $tursoClient->execute("SELECT * FROM users WHERE username = 'leeyam21'");

    // Log the complete structure of $user for inspection
    error_log("Full Query Result: " . print_r($user, true));
    
    $response->getBody()->write(json_encode($user));
    return $response->withHeader('Content-Type', 'application/json');
});




// Run the app
$app->run();
