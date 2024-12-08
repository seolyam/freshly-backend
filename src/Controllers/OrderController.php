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

    // Fetch all orders for the authenticated user
    public function getOrders(Request $request, Response $response, array $args): Response
    {
        $userClaims = $request->getAttribute('user');
        $email = $userClaims['email'] ?? null;

        if (!$email) {
            return $this->respondWithJson($response, ['error' => 'User not authenticated'], 401);
        }

        try {
            // Get all orders for the user
            $sql = 'SELECT id, createdAt FROM orders WHERE email = ? ORDER BY createdAt DESC';
            $ordersResult = $this->db->executeQuery($sql, [$email]);
            $orderRows = $ordersResult['results'][0]['response']['result']['rows'] ?? [];

            $orders = [];
            foreach ($orderRows as $orderRow) {
                $orderId = $orderRow[0]['value'];
                $createdAt = $orderRow[1]['value'];

                // Get items for each order
                $itemsSql = 'SELECT productId, quantity, price FROM orderItems WHERE orderId = ?';
                $itemsResult = $this->db->executeQuery($itemsSql, [$orderId]);
                $itemRows = $itemsResult['results'][0]['response']['result']['rows'] ?? [];

                $items = array_map(function ($itemRow) {
                    return [
                        'productId' => $itemRow[0]['value'],
                        'quantity' => $itemRow[1]['value'],
                        'price' => $itemRow[2]['value']
                    ];
                }, $itemRows);

                // You can have a status field if you've implemented it, for now let's say "on the way"
                $orders[] = [
                    'orderId' => $orderId,
                    'createdAt' => $createdAt,
                    'status' => 'On the way',
                    'items' => $items
                ];
            }

            return $this->respondWithJson($response, ['success' => true, 'orders' => $orders], 200);
        } catch (\Exception $e) {
            error_log('GetOrders Error: ' . $e->getMessage());
            return $this->respondWithJson($response, ['error' => 'Failed to fetch orders'], 500);
        }
    }

    private function respondWithJson(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
