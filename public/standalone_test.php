<?php

require __DIR__ . '/../vendor/autoload.php'; // Adjust this to reach the vendor folder

use Dotenv\Dotenv;
use GuzzleHttp\Client;

// Load environment variables from the project root
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$databaseUrl = $_ENV['TURSO_DB_URL'];
$authToken = $_ENV['TURSO_AUTH_TOKEN'];

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
    echo "Standalone Script Query Result:\n";
    print_r($data);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
