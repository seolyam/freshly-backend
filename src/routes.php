<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use App\Controllers\ProductController;
use App\TursoClient;

return function (App $app) {
    // Retrieve TursoDB configuration from environment variables
    $databaseUrl = getenv('TURSO_DB_URL');
    $authToken = getenv('TURSO_AUTH_TOKEN');

    // Instantiate the TursoClient
    $tursoClient = new TursoClient($databaseUrl, $authToken);

    // Instantiate the ProductController with TursoClient
    $productController = new ProductController($tursoClient);

    // Define your routes
    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write("Welcome to the Freshly Backend API!");
        return $response;
    });

    // Get All Products
    $app->get('/products', [$productController, 'getAllProducts']);

    // Define other routes similarly...
};

