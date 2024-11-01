<?php

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use Dotenv\Dotenv;

// Load .env file
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$databaseUrl = $_ENV['TURSO_DB_URL'] ?? null;
$authToken = $_ENV['TURSO_AUTH_TOKEN'] ?? null;

if (!$databaseUrl || !$authToken) {
    echo "Database URL or Auth Token is not set.\n";
    exit;
}

try {
    $client = new Client([
        'base_uri' => preg_replace('/^libsql:\/\//', 'https://', $databaseUrl),
        'timeout'  => 30.0,
    ]);

    $response = $client->post('/v2/pipeline', [
        'json' => [
            'requests' => [
                [
                    'type' => 'execute',
                    'stmt' => ['sql' => "SELECT * FROM users WHERE username = 'leeyam21'"],
                ],
                ['type' => 'close']
            ],
        ],
        'headers' => [
            'Authorization' => 'Bearer ' . $authToken,
            'Content-Type' => 'application/json',
        ],
    ]);

    $data = json_decode($response->getBody(), true);
    echo "Response Data:\n";
    print_r($data);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
