<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class TursoClient
{
    private Client $client;
    private string $authToken;

    public function __construct(string $databaseUrl, string $authToken)
    {
        $this->authToken = $authToken;
        $this->client = new Client([
            'base_uri' => $this->convertToHttpUrl($databaseUrl),
            'timeout'  => 30.0,
        ]);
    }

    private function convertToHttpUrl(string $libsqlUrl): string
    {
        return preg_replace('/^libsql:\/\//', 'https://', $libsqlUrl);
    }

    public function execute(string $sql, array $params = []): array
    {
        try {
            $requests = [
                [
                    'type' => 'execute',
                    'stmt' => ['sql' => $sql],
                ],
                ['type' => 'close']
            ];

            if (!empty($params)) {
                $args = array_map(fn($param) => ['type' => 'text', 'value' => $param], $params);
                $requests[0]['stmt']['args'] = $args;
            }

            $response = $this->client->post('/v2/pipeline', [
                'json' => ['requests' => $requests],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->authToken,
                    'Content-Type' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                throw new \Exception('TursoDB Error: ' . $data['error']);
            }

            return $data['results'][0]['rows'] ?? [];

        } catch (RequestException $e) {
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 'N/A';
            $reason = $e->getResponse() ? $e->getResponse()->getReasonPhrase() : $e->getMessage();

            throw new \Exception("HTTP Request failed: {$statusCode} {$reason}");
        }
    }
}
