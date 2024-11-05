<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use App\TursoClient;
use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;
use Dotenv\Dotenv;



if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}


// Initialize the database connection using TursoClient
$databaseUrl = $_ENV['TURSO_DB_URL'];
$authToken = $_ENV['TURSO_AUTH_TOKEN'];
$tursoClient = new TursoClient($databaseUrl, $authToken);

// Set up Slim App
$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// Instantiate the UserController with TursoClient
$userController = new UserController($tursoClient);

// Define routes
$app->post('/register', [$userController, 'register']);
$app->post('/login', [$userController, 'login']);
$app->get('/profile', [$userController, 'getProfile'])->add(new AuthMiddleware());
$app->post('/profile', [$userController, 'updateProfile'])->add(new AuthMiddleware());


// Test route for direct database query
$app->get('/test-user', function ($request, $response) use ($tursoClient) {
    $user = $tursoClient->executeQuery("SELECT * FROM users WHERE username = 'leeyam20'");

    $response->getBody()->write(json_encode($user));
    return $response->withHeader('Content-Type', 'application/json');
});

// Run the app
$app->run();
