<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class TursoClient
{
    private $client;
    private $authToken;

    public function __construct($databaseUrl, $authToken)
    {
        $this->authToken = $authToken;

        $this->client = new Client([
            'base_uri' => $this->convertToHttpUrl($databaseUrl),
            'timeout'  => 30.0,
        ]);
    }

    private function convertToHttpUrl($libsqlUrl)
    {
        return preg_replace('/^libsql:\/\//', 'https://', $libsqlUrl);
    }

    public function execute($sql, $params = [])
    {
        try {
            $requests = [
                [
                    'type' => 'execute',
                    'stmt' => [
                        'sql' => $sql,
                    ],
                ],
                [
                    'type' => 'close',
                ],
            ];

            // Handle parameters (if any)
            if (!empty($params)) {
                $args = [];
                foreach ($params as $param) {
                    $args[] = [
                        'type' => 'text',
                        'value' => $param,
                    ];
                }
                $requests[0]['stmt']['args'] = $args;
            }

            $response = $this->client->post('/v2/pipeline', [
                'json' => [
                    'requests' => $requests,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->authToken,
                    'Content-Type' => 'application/json',
                ],
            ]);

            $body = $response->getBody();
            $data = json_decode($body, true);

            if (isset($data['error'])) {
                throw new \Exception('TursoDB Error: ' . $data['error']);
            }

            // Return the results from the execute request
            return $data['results'][0]['rows'] ?? [];

        } catch (RequestException $e) {
            $response = $e->getResponse();
            $statusCode = $response ? $response->getStatusCode() : 'N/A';
            $reason = $response ? $response->getReasonPhrase() : $e->getMessage();

            throw new \Exception("HTTP Request failed: {$statusCode} {$reason}");
        }
    }
}
