<?php

namespace App\Controllers;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController
{
    private Client $client;
    private string $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = $_ENV['USER_API_KEY'] ?? '';

        if (empty($this->apiKey)) {
            throw new \Exception('API Key is not set in the environment variables.');
        }
    }

    public function register(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();
        $firstName = $data['first_name'] ?? '';
        $lastName = $data['last_name'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
            return $this->respondWithJson($response, ['error' => 'All fields are required.'], 400);
        }

        try {
            $res = $this->client->post('https://devlab.helioho.st/api/register.php', [
                'form_params' => [
                    'api_key' => $this->apiKey,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'password' => $password,
                ]
            ]);

            $body = json_decode($res->getBody(), true);
            return $this->respondWithJson($response, $body, $res->getStatusCode());

        } catch (\Exception $e) {
            return $this->respondWithJson($response, ['error' => 'Registration failed.'], 500);
        }
    }

    public function login(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            return $this->respondWithJson($response, ['error' => 'Email and password are required.'], 400);
        }

        try {
            $res = $this->client->post('https://devlab.helioho.st/api/validate.php', [
                'form_params' => [
                    'api_key' => $this->apiKey,
                    'email' => $email,
                    'password' => $password,
                ]
            ]);

            $body = json_decode($res->getBody(), true);
            return $this->respondWithJson($response, $body, $res->getStatusCode());

        } catch (\Exception $e) {
            return $this->respondWithJson($response, ['error' => 'Login failed due to server error.'], 500);
        }
    }

    private function respondWithJson(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
