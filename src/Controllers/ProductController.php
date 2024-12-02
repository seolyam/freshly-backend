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

    // Get All Products
    public function getAllProducts(Request $request, Response $response, array $args): Response
    {
        try {
            $sql = 'SELECT * FROM products';
            $productResult = $this->db->executeQuery($sql);

            // Extract products from result
            $products = $productResult['results'][0]['rows'] ?? [];

            $response->getBody()->write(json_encode(['products' => $products]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            error_log('GetAllProducts Error: ' . $e->getMessage());
            return $this->errorResponse($response, 'Failed to fetch products.');
        }
    }

    // Get Product by ID
    public function getProductById(Request $request, Response $response, array $args): Response
    {
        $productId = $args['id'] ?? null;
        if (!$productId) {
            return $this->errorResponse($response, 'Product ID is required.', 400);
        }

        try {
            $sql = 'SELECT * FROM products WHERE id = ?';
            $productResult = $this->db->executeQuery($sql, [$productId]);

            $product = $productResult['results'][0]['rows'][0] ?? null;

            if (!$product) {
                return $this->errorResponse($response, 'Product not found', 404);
            }

            $response->getBody()->write(json_encode(['product' => $product]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            error_log('GetProductById Error: ' . $e->getMessage());
            return $this->errorResponse($response, 'Failed to fetch product.');
        }
    }

    // Create Product
    public function createProduct(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();
        $name = $data['name'] ?? '';
        $description = $data['description'] ?? '';
        $price = $data['price'] ?? '';
        $imageUrl = $data['image_url'] ?? '';

        if (empty($name) || empty($price) || empty($imageUrl)) {
            return $this->errorResponse($response, 'Name, price, and image URL are required.', 400);
        }

        try {
            $sql = 'INSERT INTO products (name, description, price, image_url) VALUES (?, ?, ?, ?)';
            $this->db->executeQuery($sql, [$name, $description, $price, $imageUrl]);

            return $this->successResponse($response, 'Product created successfully.', 201);
        } catch (\Exception $e) {
            error_log('CreateProduct Error: ' . $e->getMessage());
            return $this->errorResponse($response, 'Failed to create product.');
        }
    }

    // Update Product
    public function updateProduct(Request $request, Response $response, array $args): Response
    {
        $productId = $args['id'] ?? null;
        if (!$productId) {
            return $this->errorResponse($response, 'Product ID is required.', 400);
        }

        $data = $request->getParsedBody();
        $name = $data['name'] ?? null;
        $description = $data['description'] ?? null;
        $price = $data['price'] ?? null;
        $imageUrl = $data['image_url'] ?? null;

        if (!$name && !$description && !$price && !$imageUrl) {
            return $this->errorResponse($response, 'At least one field is required to update.', 400);
        }

        try {
            $fields = [];
            $params = [];

            if ($name !== null) {
                $fields[] = 'name = ?';
                $params[] = $name;
            }
            if ($description !== null) {
                $fields[] = 'description = ?';
                $params[] = $description;
            }
            if ($price !== null) {
                $fields[] = 'price = ?';
                $params[] = $price;
            }
            if ($imageUrl !== null) {
                $fields[] = 'image_url = ?';
                $params[] = $imageUrl;
            }

            $params[] = $productId;
            $sql = 'UPDATE products SET ' . implode(', ', $fields) . ' WHERE id = ?';
            $this->db->executeQuery($sql, $params);

            return $this->successResponse($response, 'Product updated successfully.');
        } catch (\Exception $e) {
            error_log('UpdateProduct Error: ' . $e->getMessage());
            return $this->errorResponse($response, 'Failed to update product.');
        }
    }

    // Delete Product
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
            error_log('DeleteProduct Error: ' . $e->getMessage());
            return $this->errorResponse($response, 'Failed to delete product.');
        }
    }

    // Private helper methods
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
