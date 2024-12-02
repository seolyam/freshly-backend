<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use App\Controllers\ProductController;
use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;
use App\TursoClient;
use Dotenv\Dotenv;


if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}


$databaseUrl = $_ENV['TURSO_DB_URL'] ?? '';
$authToken = $_ENV['TURSO_AUTH_TOKEN'] ?? '';
if (empty($databaseUrl) || empty($authToken)) {
    throw new \Exception('Database URL or Auth Token is not set in the environment variables.');
}


$tursoClient = new TursoClient($databaseUrl, $authToken);


$app = AppFactory::create();
$app->addRoutingMiddleware(); 
$app->addBodyParsingMiddleware();



$productController = new ProductController($tursoClient);
$userController = new UserController();


$app->get('/products', [$productController, 'getAllProducts']);
$app->get('/products/{id}', [$productController, 'getProductById']);
$app->post('/products', [$productController, 'createProduct']);
$app->put('/products/{id}', [$productController, 'updateProduct']);
$app->delete('/products/{id}', [$productController, 'deleteProduct']);


$app->post('/register', [$userController, 'register']);
$app->post('/login', [$userController, 'login']);
$app->get('/profile', [$userController, 'getProfile'])->add(new AuthMiddleware());
$app->post('/profile', [$userController, 'updateProfile'])->add(new AuthMiddleware());


$app->addErrorMiddleware(true, true, true);


$app->run();
