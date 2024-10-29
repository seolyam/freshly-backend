<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use App\TursoClient;
use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;
use Slim\Middleware\BodyParsingMiddleware;

// Load environment variables only if not in Railway production
if (!getenv('RAILWAY_ENVIRONMENT')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Create App
$app = AppFactory::create();

// Add the Body Parsing Middleware
$app->addBodyParsingMiddleware();

// Set up database connection
$databaseUrl = getenv('TURSO_DB_URL') ?: $_ENV['TURSO_DB_URL'];
$authToken = getenv('TURSO_AUTH_TOKEN') ?: $_ENV['TURSO_AUTH_TOKEN'];
$tursoClient = new TursoClient($databaseUrl, $authToken);

// Instantiate controllers
$userController = new UserController($tursoClient);

// Routes
$app->post('/register', [$userController, 'register']);
$app->post('/login', [$userController, 'login']);
$app->get('/profile', [$userController, 'getProfile'])->add(new AuthMiddleware());
$app->post('/profile', [$userController, 'updateProfile'])->add(new AuthMiddleware());

// Debug Route
$app->get('/test-db', function ($request, $response, $args) use ($tursoClient) {
    try {
        $result = $tursoClient->execute("SELECT 1;");
        $response->getBody()->write("Database connection successful!");
    } catch (\Exception $e) {
        $response->getBody()->write("Database connection failed: " . $e->getMessage());
    }
    return $response->withHeader('Content-Type', 'text/plain');
});


// Run app
$app->run();
