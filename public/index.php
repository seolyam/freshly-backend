<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use App\Controllers\ProductController;
use App\Controllers\UserController;
use App\Controllers\UserExtraController;
use App\Controllers\CartController;
use App\Middleware\AuthMiddleware;
use App\TursoClient;
use App\Repository\UserExtraRepository;
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

// Instantiate UserExtraController
$extraRepo = new UserExtraRepository($tursoClient);
$userExtraController = new UserExtraController($extraRepo);

// Products routes
$app->get('/products', [$productController, 'getAllProducts']);
$app->get('/products/{id}', [$productController, 'getProductById']);
$app->post('/products', [$productController, 'createProduct']);
$app->put('/products/{id}', [$productController, 'updateProduct']);
$app->delete('/products/{id}', [$productController, 'deleteProduct']);

// User routes
$app->post('/register', [$userController, 'register']);
$app->post('/login', [$userController, 'login']);
$app->get('/profile', [$userController, 'getProfile'])->add(new AuthMiddleware());
$app->post('/profile', [$userController, 'updateProfile'])->add(new AuthMiddleware());
$app->post('/cart', [CartController::class, 'addCartItem'])->add(new AuthMiddleware());

// Extra user info route (for contactNumber, address, birthdate)
$app->post('/update-user-info', [$userExtraController, 'updateUserInfo'])->add(new AuthMiddleware());

$app->addErrorMiddleware(true, true, true);

$app->get('/test-products', function ($request, $response) use ($tursoClient) {
    $sql = 'SELECT * FROM products';
    $productResult = $tursoClient->executeQuery($sql);
    $response->getBody()->write(json_encode($productResult));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

$app->run();
