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

    private function respondWithJson(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
