<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\TursoClient;

class CartController
{
    private TursoClient $db;

    public function __construct(TursoClient $db)
    {
        $this->db = $db;
    }

    // Add or update cart item
    public function addCartItem(Request $request, Response $response, array $args): Response
    {
        // Retrieve user email from JWT claims set by AuthMiddleware
        $userClaims = $request->getAttribute('user');
        $email = $userClaims['email'] ?? null;

        if (!$email) {
            return $this->respondWithJson($response, ['error' => 'User not authenticated'], 401);
        }

        $data = $request->getParsedBody();
        $productId = $data['productId'] ?? null;
        $quantity = $data['quantity'] ?? 1;

        if (!is_numeric($productId) || $productId <= 0 || !is_numeric($quantity) || $quantity <= 0) {
            return $this->respondWithJson($response, ['error' => 'Invalid productId or quantity'], 400);
        }

        try {
            // Check if the item already exists in the cart
            $checkSql = 'SELECT id, quantity FROM cartItems WHERE email = ? AND productId = ?';
            $existingItem = $this->db->executeQuery($checkSql, [$email, $productId]);

            if (!empty($existingItem['results'][0]['response']['result']['rows'])) {
                // Item exists, update the quantity
                $existingQuantity = $existingItem['results'][0]['response']['result']['rows'][0][1]['value'];
                $newQuantity = $existingQuantity + $quantity;

                $updateSql = 'UPDATE cartItems 
                              SET quantity = ?, updatedAt = CURRENT_TIMESTAMP 
                              WHERE email = ? AND productId = ?';
                $this->db->executeQuery($updateSql, [$newQuantity, $email, $productId]);

                return $this->respondWithJson($response, [
                    'success' => true,
                    'message' => 'Cart item updated',
                    'quantity' => $newQuantity,
                ], 200);
            } else {
                // Item does not exist, insert it
                $insertSql = 'INSERT INTO cartItems (email, productId, quantity, createdAt, updatedAt) 
                              VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)';
                $this->db->executeQuery($insertSql, [$email, $productId, $quantity]);

                return $this->respondWithJson($response, [
                    'success' => true,
                    'message' => 'Item added to cart',
                ], 201);
            }
        } catch (\Exception $e) {
            error_log('AddCartItem Error: ' . $e->getMessage());
            return $this->respondWithJson($response, ['error' => 'Failed to add item to cart'], 500);
        }
    }

    // Fetch all cart items for a user
    public function getCartItems(Request $request, Response $response, array $args): Response
{
    $userClaims = $request->getAttribute('user');
    $email = $userClaims['email'] ?? null;

    if (!$email) {
        return $this->respondWithJson($response, ['error' => 'User not authenticated'], 401);
    }

    try {
        $sql = 'SELECT ci.id, ci.productId, IFNULL(p.name, "Unknown") AS name, 
               IFNULL(p.price, 0.0) AS price, ci.quantity, 
               IFNULL(p.image_url, NULL) AS imageUrl
        FROM cartItems ci
        LEFT JOIN products p ON ci.productId = p.id
        WHERE ci.email = ?';

        
        $cartItems = $this->db->executeQuery($sql, [$email]);

        if (!empty($cartItems['results'][0]['response']['result']['rows'])) {
            $items = array_map(function ($row) {
                return [
                    'id' => $row[0]['value'],
                    'productId' => $row[1]['value'],
                    'name' => $row[2]['value'],
                    'price' => $row[3]['value'],
                    'quantity' => $row[4]['value'],
                    'imageUrl' => $row[5]['value'] ?? null,
                ];
            }, $cartItems['results'][0]['response']['result']['rows']);

            return $this->respondWithJson($response, ['success' => true, 'cartItems' => $items], 200);
        } else {
            return $this->respondWithJson($response, ['success' => true, 'cartItems' => []], 200);
        }
    } catch (\Exception $e) {
        error_log('GetCartItems Error: ' . $e->getMessage());
        return $this->respondWithJson($response, ['error' => 'Failed to fetch cart items'], 500);
    }
}

    

    private function respondWithJson(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
