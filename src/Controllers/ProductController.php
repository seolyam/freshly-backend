<?php

namespace App\Controllers;

use App\TursoClient;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProductController
{
    protected $db;

    public function __construct(TursoClient $db)
    {
        $this->db = $db;
    }

    public function getAllProducts(Request $request, Response $response, $args): Response
    {
        try {
            $sql = 'SELECT * FROM products';
            $products = $this->db->execute($sql);

            $response->getBody()->write(json_encode($products));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            error_log("Error fetching products: " . $e->getMessage());
            $error = ['error' => 'Failed to fetch products.'];
            $response->getBody()->write(json_encode($error));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // Implement other methods (getProductById, createProduct, etc.) similarly, adjusting SQL and parameters.
}

