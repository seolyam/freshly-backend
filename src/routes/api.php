<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Illuminate\Database\Capsule\Manager as Capsule;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->get('/test-db', function (Request $request, Response $response, $args) {
    try {
        $products = Capsule::table('products')->get(); // Assuming you have a 'products' table
        $response->getBody()->write($products->toJson());
    } catch (Exception $e) {
        $response->getBody()->write("Database connection failed: " . $e->getMessage());
    }

    return $response;
});

$app->run();
