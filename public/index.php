<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use App\Controllers\ProductController;
use App\Controllers\UserController;
use App\Controllers\UserExtraController;
use App\Controllers\CartController;
use App\Controllers\OrderController;
use App\Middleware\AuthMiddleware;
use App\TursoClient;
use App\Repository\UserExtraRepository;
use Dotenv\Dotenv;

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Validate database environment variables
$databaseUrl = $_ENV['TURSO_DB_URL'] ?? '';
$authToken = $_ENV['TURSO_AUTH_TOKEN'] ?? '';
if (empty($databaseUrl) || empty($authToken)) {
    throw new \Exception('Database URL or Auth Token is not set in the environment variables.');
}

// Initialize Turso client
$tursoClient = new TursoClient($databaseUrl, $authToken);

// Create Slim app
$app = AppFactory::create();
$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();

// Controllers
$productController = new ProductController($tursoClient);
$userController = new UserController();
$cartController = new CartController($tursoClient);
$orderController = new OrderController($tursoClient);

// Instantiate UserExtraController
$extraRepo = new UserExtraRepository($tursoClient);
$userExtraController = new UserExtraController($extraRepo);

// Define routes
$app->group('/products', function ($group) use ($productController) {
    $group->get('', [$productController, 'getAllProducts']);
    $group->get('/{id}', [$productController, 'getProductById']);
    $group->post('', [$productController, 'createProduct']);
    $group->put('/{id}', [$productController, 'updateProduct']);
    $group->delete('/{id}', [$productController, 'deleteProduct']);
});

$app->group('/user', function ($group) use ($userController) {
    $group->post('/register', [$userController, 'register']);
    $group->post('/login', [$userController, 'login']);
    $group->get('/profile', [$userController, 'getProfile'])->add(new AuthMiddleware());
    $group->post('/profile', [$userController, 'updateProfile'])->add(new AuthMiddleware());
});

$app->group('/cart', function ($group) use ($cartController) {
    $group->post('', [$cartController, 'addCartItem'])->add(new AuthMiddleware());
    $group->post('/add', [$cartController, 'addCartItem'])->add(new AuthMiddleware());
    $group->post('/update-quantity', [$cartController, 'updateCartItemQuantity'])->add(new AuthMiddleware());
    $group->get('', [$cartController, 'getCartItems'])->add(new AuthMiddleware());
});

$app->post('/checkout', [$cartController, 'checkout'])->add(new AuthMiddleware());
$app->get('/orders', [$orderController, 'getOrders'])->add(new AuthMiddleware());
$app->post('/update-user-info', [$userExtraController, 'updateUserInfo'])->add(new AuthMiddleware());

// Add error middleware for debugging
$app->addErrorMiddleware(true, true, true);

// Test route for debugging purposes
$app->get('/test-products', function ($request, $response) use ($tursoClient) {
    $sql = 'SELECT * FROM products';
    $productResult = $tursoClient->executeQuery($sql);
    $response->getBody()->write(json_encode($productResult));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

// Run the app
$app->run();
