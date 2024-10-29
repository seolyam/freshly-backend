<?php

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$databaseUrl = $_ENV['TURSO_DB_URL'] ?? null;
$authToken = $_ENV['TURSO_AUTH_TOKEN'] ?? null;

if (!$databaseUrl || !$authToken) {
    die("Environment variables TURSO_DB_URL and TURSO_AUTH_TOKEN must be set.\n");
}

// Convert libsql URL to HTTPS URL
$apiUrl = preg_replace('/^libsql:\/\//', 'https://', $databaseUrl);

// Create Guzzle client
$client = new Client([
    'base_uri' => $apiUrl,
    'timeout'  => 30.0,
]);

try {
    $response = $client->post('/v1/execute', [
        'json' => [
            'statements' => [
                [
                    'q' => 'SELECT 1;',
                    'params' => [],
                ],
            ],
        ],
        'headers' => [
            'Authorization' => 'Bearer ' . $authToken,
            'Content-Type' => 'application/json',
        ],
    ]);

    $body = $response->getBody();
    $data = json_decode($body, true);

    print_r($data);
} catch (\Exception $e) {
    echo "Connection test failed: " . $e->getMessage() . "\n";
}
