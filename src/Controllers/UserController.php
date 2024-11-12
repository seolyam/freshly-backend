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

    // Register a new user
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
            $res = $this->client->post('http://pzf.22b.mytemp.website/api/register.php', [
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
            error_log("Registration error: " . $e->getMessage());
            return $this->respondWithJson($response, ['error' => 'Registration failed.'], 500);
        }
    }

    // Log in an existing user
    public function login(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            return $this->respondWithJson($response, ['error' => 'Email and password are required.'], 400);
        }

        try {
            $res = $this->client->post('http://pzf.22b.mytemp.website/api/validate.php', [
                'form_params' => [
                    'api_key' => $this->apiKey,
                    'email' => $email,
                    'password' => $password,
                ]
            ]);

            $body = json_decode($res->getBody(), true);
            return $this->respondWithJson($response, $body, $res->getStatusCode());

        } catch (\Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return $this->respondWithJson($response, ['error' => 'Login failed due to server error.'], 500);
        }
    }

    // Fetch all users
    public function listUsers(Request $request, Response $response, array $args): Response
    {
        try {
            $res = $this->client->post('http://pzf.22b.mytemp.website/api/listing.php', [
                'form_params' => [
                    'api_key' => $this->apiKey
                ]
            ]);

            $body = json_decode($res->getBody(), true);
            return $this->respondWithJson($response, $body, $res->getStatusCode());

        } catch (\Exception $e) {
            error_log("List Users error: " . $e->getMessage());
            return $this->respondWithJson($response, ['error' => 'Failed to retrieve users.'], 500);
        }
    }

    // Utility function to respond with JSON
    private function respondWithJson(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
