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
    
            if (empty($productResult['results'][0]['response']['result']['rows'])) {
                return $this->respondWithJson($response, ['products' => []], 200);
            }
    
            $rows = $productResult['results'][0]['response']['result']['rows'];
            $products = [];
    
            foreach ($rows as $row) {
                $products[] = [
                    'id' => $row[0]['value'], // Extract 'value' for each field
                    'name' => $row[1]['value'],
                    'description' => $row[2]['value'],
                    'price' => $row[3]['value'],
                    'image_url' => $row[4]['value'],
                    'allergens' => $row[5]['value'],
                ];
            }
    
            return $this->respondWithJson($response, ['products' => $products], 200);
        } catch (\Exception $e) {
            error_log('GetAllProducts Error: ' . $e->getMessage());
            return $this->respondWithJson($response, ['error' => 'Failed to fetch products.'], 500);
        }
    }
    

    // Get Product by ID
    public function getProductById(Request $request, Response $response, array $args): Response
    {
        $productId = $args['id'] ?? null;
        if (!$productId) {
            return $this->respondWithJson($response, ['error' => 'Product ID is required.'], 400);
        }

        try {
            $sql = 'SELECT * FROM products WHERE id = ?';
            $productResult = $this->db->executeQuery($sql, [$productId]);

            if (empty($productResult['results'][0]['response']['result']['rows'])) {
                return $this->respondWithJson($response, ['error' => 'Product not found'], 404);
            }

            $row = $productResult['results'][0]['response']['result']['rows'][0];

            $product = [
                'id' => $row[0],
                'name' => $row[1],
                'description' => $row[2],
                'price' => $row[3],
                'image_url' => $row[4],
                'allergens' => $row[5],
            ];

            return $this->respondWithJson($response, ['product' => $product], 200);
        } catch (\Exception $e) {
            error_log('GetProductById Error: ' . $e->getMessage());
            return $this->respondWithJson($response, ['error' => 'Failed to fetch product.'], 500);
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
        $allergens = $data['allergens'] ?? '';

        if (empty($name) || empty($price) || empty($imageUrl)) {
            return $this->respondWithJson($response, ['error' => 'Name, price, and image URL are required.'], 400);
        }

        try {
            $sql = 'INSERT INTO products (name, description, price, image_url, allergens) VALUES (?, ?, ?, ?, ?) RETURNING id';
            $result = $this->db->executeQuery($sql, [$name, $description, $price, $imageUrl, $allergens]);

            if (empty($result['results'][0]['response']['result']['rows'])) {
                return $this->respondWithJson($response, ['error' => 'Failed to create product.'], 500);
            }

            $productId = $result['results'][0]['response']['result']['rows'][0][0];

            return $this->respondWithJson($response, ['message' => 'Product created successfully.', 'product_id' => $productId], 201);
        } catch (\Exception $e) {
            error_log('CreateProduct Error: ' . $e->getMessage());
            return $this->respondWithJson($response, ['error' => 'Failed to create product.'], 500);
        }
    }

    // Update Product
    public function updateProduct(Request $request, Response $response, array $args): Response
    {
        $productId = $args['id'] ?? null;
        if (!$productId) {
            return $this->respondWithJson($response, ['error' => 'Product ID is required.'], 400);
        }

        $data = $request->getParsedBody();
        $fields = [];
        $params = [];

        if (isset($data['name'])) {
            $fields[] = 'name = ?';
            $params[] = $data['name'];
        }
        if (isset($data['description'])) {
            $fields[] = 'description = ?';
            $params[] = $data['description'];
        }
        if (isset($data['price'])) {
            $fields[] = 'price = ?';
            $params[] = $data['price'];
        }
        if (isset($data['image_url'])) {
            $fields[] = 'image_url = ?';
            $params[] = $data['image_url'];
        }
        if (isset($data['allergens'])) {
            $fields[] = 'allergens = ?';
            $params[] = $data['allergens'];
        }

        if (empty($fields)) {
            return $this->respondWithJson($response, ['error' => 'At least one field is required to update.'], 400);
        }

        try {
            $params[] = $productId;
            $sql = 'UPDATE products SET ' . implode(', ', $fields) . ' WHERE id = ?';
            $this->db->executeQuery($sql, $params);

            return $this->respondWithJson($response, ['message' => 'Product updated successfully.'], 200);
        } catch (\Exception $e) {
            error_log('UpdateProduct Error: ' . $e->getMessage());
            return $this->respondWithJson($response, ['error' => 'Failed to update product.'], 500);
        }
    }

    // Delete Product
    public function deleteProduct(Request $request, Response $response, array $args): Response
    {
        $productId = $args['id'] ?? null;
        if (!$productId) {
            return $this->respondWithJson($response, ['error' => 'Product ID is required.'], 400);
        }

        try {
            $sql = 'DELETE FROM products WHERE id = ?';
            $this->db->executeQuery($sql, [$productId]);

            return $this->respondWithJson($response, ['message' => 'Product deleted successfully.'], 200);
        } catch (\Exception $e) {
            error_log('DeleteProduct Error: ' . $e->getMessage());
            return $this->respondWithJson($response, ['error' => 'Failed to delete product.'], 500);
        }
    }

    // Private helper method
    private function respondWithJson(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
