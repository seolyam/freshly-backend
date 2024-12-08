<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\TursoClient;

class OrderController
{
    private TursoClient $db;

    public function __construct(TursoClient $db)
    {
        $this->db = $db;
    }

    // Method to get all orders for the authenticated user
    public function getOrders(Request $request, Response $response, array $args): Response
    {
        $userClaims = $request->getAttribute('user');
        $email = $userClaims['email'] ?? null;

        if (!$email) {
            return $this->respondWithJson($response, ['error' => 'User not authenticated'], 401);
        }

        try {
            // Get all orders for the user
            $sql = 'SELECT id, createdAt, contactNumber, address FROM orders WHERE email = ? ORDER BY createdAt DESC';
            $ordersResult = $this->db->executeQuery($sql, [$email]);
            $orderRows = $ordersResult['results'][0]['response']['result']['rows'] ?? [];

            $orders = [];
            foreach ($orderRows as $orderRow) {
                $orderId = $orderRow[0]['value'];
                $createdAt = $orderRow[1]['value'];
                $contactNumber = $orderRow[2]['value'] ?? null;
                $address = $orderRow[3]['value'] ?? null;

                // Get items for each order, including imageUrl
                $itemsSql = 'SELECT oi.productId, p.name AS productName, oi.quantity, p.price, p.image_url AS imageUrl 
                             FROM orderItems oi 
                             JOIN products p ON oi.productId = p.id 
                             WHERE oi.orderId = ?';
                $itemsResult = $this->db->executeQuery($itemsSql, [$orderId]);
                $itemRows = $itemsResult['results'][0]['response']['result']['rows'] ?? [];

                $items = array_map(function ($itemRow) {
                    return [
                        'productId' => $itemRow[0]['value'],
                        'productName' => $itemRow[1]['value'],
                        'quantity' => $itemRow[2]['value'],
                        'price' => $itemRow[3]['value'],
                        'imageUrl' => $itemRow[4]['value'] // Use the aliased field name
                    ];
                }, $itemRows);

                $orders[] = [
                    'orderId' => $orderId,
                    'createdAt' => $createdAt,
                    'contactNumber' => $contactNumber,
                    'address' => $address,
                    'status' => 'Pending', // Replace with actual status logic
                    'items' => $items
                ];
            }

            return $this->respondWithJson($response, ['success' => true, 'orders' => $orders], 200);
        } catch (\Exception $e) {
            error_log('GetOrders Error: ' . $e->getMessage());
            return $this->respondWithJson($response, ['error' => 'Failed to fetch orders'], 500);
        }
    }

    // Method to place a new order
    public function placeOrder(Request $request, Response $response): Response
    {
        $userClaims = $request->getAttribute('user');
        $email = $userClaims['email'] ?? null;

        if (!$email) {
            return $this->respondWithJson($response, ['error' => 'User not authenticated'], 401);
        }

        $data = $request->getParsedBody();
        $cartItems = $data['items'] ?? [];
        $totalPrice = $data['totalPrice'] ?? 0;

        if (empty($cartItems)) {
            return $this->respondWithJson($response, ['error' => 'Cart is empty'], 400);
        }

        try {
            // Fetch user contact info from user_extras
            $userExtrasSql = 'SELECT contactNumber, address FROM user_extras WHERE email = ?';
            $userExtrasResult = $this->db->executeQuery($userExtrasSql, [$email]);
            $userExtras = $userExtrasResult['results'][0]['response']['result']['rows'][0] ?? null;

            if (!$userExtras) {
                return $this->respondWithJson($response, ['error' => 'User contact info not found'], 400);
            }

            $contactNumber = $userExtras[0]['value'] ?? null;
            $address = $userExtras[1]['value'] ?? null;

            if (!$contactNumber || !$address) {
                return $this->respondWithJson($response, ['error' => 'Contact number or address is missing'], 400);
            }

            // Insert the order into the orders table
            $insertOrderSql = 'INSERT INTO orders (email, createdAt, contactNumber, address) VALUES (?, ?, ?, ?)';
            $createdAt = date('Y-m-d H:i:s');
            $this->db->executeQuery($insertOrderSql, [$email, $createdAt, $contactNumber, $address]);

            // Get the last inserted order ID
            $orderIdResult = $this->db->executeQuery('SELECT last_insert_rowid() as orderId');
            $orderId = $orderIdResult['results'][0]['response']['result']['rows'][0][0]['value'] ?? null;

            if (!$orderId) {
                throw new \Exception('Failed to retrieve order ID');
            }

            // Insert items into orderItems table
            foreach ($cartItems as $item) {
                $insertItemSql = 'INSERT INTO orderItems (orderId, productId, quantity, price) VALUES (?, ?, ?, ?)';
                $this->db->executeQuery($insertItemSql, [
                    $orderId,
                    $item['productId'],
                    $item['quantity'],
                    $item['price']
                ]);
            }

            return $this->respondWithJson($response, ['success' => true, 'orderId' => $orderId], 201);
        } catch (\Exception $e) {
            error_log('PlaceOrder Error: ' . $e->getMessage());
            return $this->respondWithJson($response, ['error' => 'Failed to place order'], 500);
        }
    }

    private function respondWithJson(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
