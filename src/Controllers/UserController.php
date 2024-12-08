<?php

namespace App\Controllers;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

class UserController
{
    private Client $client;
    private string $apiKey;
    private Configuration $jwtConfig;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = $_ENV['USER_API_KEY'] ?? '';
        if (empty($this->apiKey)) {
            throw new \Exception('API Key is not set in the environment variables.');
        }

        $jwtSecret = $_ENV['JWT_SECRET'] ?? '';
        if (empty($jwtSecret)) {
            throw new \Exception('JWT Secret is not set in the environment variables.');
        }

        $this->jwtConfig = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($jwtSecret)
        );
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

            if ($body['success'] ?? false) {
                $jwtToken = $this->generateJwtToken($email);
                $refreshToken = bin2hex(random_bytes(32));

                $sql = 'UPDATE users SET refresh_token = ? WHERE email = ?';
                $db = new \App\TursoClient($_ENV['TURSO_DB_URL'], $_ENV['TURSO_AUTH_TOKEN']);
                $db->executeQuery($sql, [$refreshToken, $email]);

                return $this->respondWithJson($response, [
                    'success' => true,
                    'message' => 'User registered successfully.',
                    'token' => $jwtToken,
                    'refreshToken' => $refreshToken
                ], 200);
            } else {
                return $this->respondWithJson($response, [
                    'success' => false,
                    'message' => $body['message'] ?? 'Registration failed.'
                ], 400);
            }
        } catch (\Exception $e) {
            error_log('Registration Error: ' . $e->getMessage());
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
            $res = $this->client->post('http://pzf.22b.mytemp.website/api/validate.php', [
                'form_params' => [
                    'api_key' => $this->apiKey,
                    'email' => $email,
                    'password' => $password,
                ]
            ]);

            $body = json_decode($res->getBody(), true);

            if ($body['success'] ?? false) {
                $jwtToken = $this->generateJwtToken($email);
                $refreshToken = bin2hex(random_bytes(32));

                $sql = 'UPDATE users SET refresh_token = ? WHERE email = ?';
                $db = new \App\TursoClient($_ENV['TURSO_DB_URL'], $_ENV['TURSO_AUTH_TOKEN']);
                $db->executeQuery($sql, [$refreshToken, $email]);

                return $this->respondWithJson($response, [
                    'success' => true,
                    'message' => 'User validated successfully.',
                    'token' => $jwtToken,
                    'refreshToken' => $refreshToken
                ], 200);
            } else {
                return $this->respondWithJson($response, [
                    'success' => false,
                    'message' => $body['message'] ?? 'Invalid credentials.'
                ], 401);
            }
        } catch (\Exception $e) {
            error_log('Login Error: ' . $e->getMessage());
            return $this->respondWithJson($response, ['error' => 'Login failed.'], 500);
        }
    }

    public function refreshToken(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();
        $refreshToken = $data['refresh_token'] ?? '';

        if (empty($refreshToken)) {
            return $this->respondWithJson($response, ['error' => 'Refresh token is required.'], 400);
        }

        try {
            $sql = 'SELECT email FROM users WHERE refresh_token = ?';
            $db = new \App\TursoClient($_ENV['TURSO_DB_URL'], $_ENV['TURSO_AUTH_TOKEN']);
            $result = $db->executeQuery($sql, [$refreshToken]);
            $rows = $result['results'][0]['response']['result']['rows'] ?? [];

            if (empty($rows)) {
                return $this->respondWithJson($response, ['error' => 'Invalid refresh token.'], 401);
            }

            $email = $rows[0][0]['value'];
            $newJwtToken = $this->generateJwtToken($email);
            $newRefreshToken = bin2hex(random_bytes(32));

            $updateSql = 'UPDATE users SET refresh_token = ? WHERE email = ?';
            $db->executeQuery($updateSql, [$newRefreshToken, $email]);

            return $this->respondWithJson($response, [
                'success' => true,
                'token' => $newJwtToken,
                'refreshToken' => $newRefreshToken
            ], 200);
        } catch (\Exception $e) {
            error_log('Refresh Token Error: ' . $e->getMessage());
            return $this->respondWithJson($response, ['error' => 'Failed to refresh token.'], 500);
        }
    }

    private function respondWithJson(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    private function generateJwtToken(string $email): string
    {
        $now = new \DateTimeImmutable();
        return $this->jwtConfig->builder()
            ->issuedBy('https://freshly-backend-production.up.railway.app/')
            ->issuedAt($now)
            ->expiresAt($now->modify('+1 hour'))
            ->withClaim('email', $email)
            ->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey())
            ->toString();
    }
}
