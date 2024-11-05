<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\TursoClient;

class ProductController
{
    private TursoClient $db;

    public function __construct(TursoClient $db)
    {
        $this->db = $db;
    }

    // Get all products
    public function getAllProducts(Request $request, Response $response, array $args): Response
    {
        try {
            $sql = 'SELECT * FROM products';
            $productResult = $this->db->executeQuery($sql);

            // Parse results
            $products = $productResult['results'][0]['response']['result']['rows'] ?? [];

            $response->getBody()->write(json_encode(['products' => $products]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to fetch products.');
        }
    }

    // Get a single product by ID
    public function getProductById(Request $request, Response $response, array $args): Response
    {
        $productId = $args['id'] ?? null;
        if (!$productId) {
            return $this->errorResponse($response, 'Product ID is required.', 400);
        }

        try {
            $sql = 'SELECT * FROM products WHERE id = ?';
            $productResult = $this->db->executeQuery($sql, [$productId]);

            $product = $productResult['results'][0]['response']['result']['rows'][0] ?? null;

            if (!$product) {
                return $this->errorResponse($response, 'Product not found', 404);
            }

            $response->getBody()->write(json_encode(['product' => $product]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to fetch product.');
        }
    }

    // Add a new product
    public function createProduct(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();
        $name = $data['name'] ?? '';
        $price = $data['price'] ?? '';

        if (empty($name) || empty($price)) {
            return $this->errorResponse($response, 'Name and price are required.', 400);
        }

        try {
            $sql = 'INSERT INTO products (name, price) VALUES (?, ?)';
            $this->db->executeQuery($sql, [$name, $price]);

            return $this->successResponse($response, 'Product created successfully.', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to create product.');
        }
    }

    // Update an existing product by ID
    public function updateProduct(Request $request, Response $response, array $args): Response
    {
        $productId = $args['id'] ?? null;
        $data = $request->getParsedBody();
        $name = $data['name'] ?? null;
        $price = $data['price'] ?? null;

        if (!$productId || !$name || !$price) {
            return $this->errorResponse($response, 'Product ID, name, and price are required.', 400);
        }

        try {
            $sql = 'UPDATE products SET name = ?, price = ? WHERE id = ?';
            $this->db->executeQuery($sql, [$name, $price, $productId]);

            return $this->successResponse($response, 'Product updated successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to update product.');
        }
    }

    // Delete a product by ID
    public function deleteProduct(Request $request, Response $response, array $args): Response
    {
        $productId = $args['id'] ?? null;
        if (!$productId) {
            return $this->errorResponse($response, 'Product ID is required.', 400);
        }

        try {
            $sql = 'DELETE FROM products WHERE id = ?';
            $this->db->executeQuery($sql, [$productId]);

            return $this->successResponse($response, 'Product deleted successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to delete product.');
        }
    }

    // Helper methods for consistent response formatting
    private function successResponse(Response $response, string $message, int $status = 200): Response
    {
        $response->getBody()->write(json_encode(['message' => $message]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    private function errorResponse(Response $response, string $message, int $status = 500): Response
    {
        $response->getBody()->write(json_encode(['error' => $message]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
