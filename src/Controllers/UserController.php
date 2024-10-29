<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\TursoClient;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;

class UserController
{
    private $db;
    private Configuration $jwtConfig;

    public function __construct(TursoClient $db)
    {
        $this->db = $db;
        $secretKey = $_ENV['JWT_SECRET'];

        $this->jwtConfig = Configuration::forSymmetricSigner(
            new Sha256(),
            \Lcobucci\JWT\Signer\Key\InMemory::plainText($secretKey)
        );
    }

    public function register(Request $request, Response $response, $args): Response
    {
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            $error = ['error' => 'All fields are required.'];
            $response->getBody()->write(json_encode($error));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $insertSQL = "INSERT INTO users (username, email, password) VALUES (?, ?, ?);";
            $params = [$username, $email, $hashedPassword];
            $this->db->execute($insertSQL, $params);

            $message = ['message' => 'User registered successfully.'];
            $response->getBody()->write(json_encode($message));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $error = ['error' => 'Registration failed.'];
            $response->getBody()->write(json_encode($error));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function getProfile(Request $request, Response $response, $args): Response
    {
        $user = $request->getAttribute('user');
        $userId = $user['uid'];

        try {
            $selectSQL = "SELECT firstName, middleInitial, lastName, birthdate, address FROM users WHERE id = ?";
            $result = $this->db->execute($selectSQL, [$userId]);

            if (empty($result)) {
                $error = ['error' => 'User not found.'];
                $response->getBody()->write(json_encode($error));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode($result[0]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $error = ['error' => 'Failed to fetch profile.'];
            $response->getBody()->write(json_encode($error));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function updateProfile(Request $request, Response $response, $args): Response
    {
        $user = $request->getAttribute('user');
        $userId = $user['uid'];
        $data = $request->getParsedBody();

        $firstName = $data['firstName'] ?? null;
        $middleInitial = $data['middleInitial'] ?? null;
        $lastName = $data['lastName'] ?? null;
        $birthdate = $data['birthdate'] ?? null;
        $address = $data['address'] ?? null;

        if (!$firstName || !$lastName || !$address) {
            $error = ['error' => 'First name, last name, and address are required.'];
            $response->getBody()->write(json_encode($error));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $updateSQL = "UPDATE users SET firstName = ?, middleInitial = ?, lastName = ?, birthdate = ?, address = ? WHERE id = ?";
            $params = [$firstName, $middleInitial, $lastName, $birthdate, $address, $userId];
            $this->db->execute($updateSQL, $params);

            $message = ['message' => 'Profile updated successfully.'];
            $response->getBody()->write(json_encode($message));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $error = ['error' => 'Failed to update profile.'];
            $response->getBody()->write(json_encode($error));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    private function generateJWT($user)
    {
        $now = new \DateTimeImmutable();
        $expiry = $now->modify('+1 hour');

        $token = $this->jwtConfig->builder()
            ->issuedBy('Freshly-Backend')
            ->permittedFor('your-application')
            ->identifiedBy(bin2hex(random_bytes(16)))
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($expiry)
            ->withClaim('uid', $user['id'])
            ->withClaim('uname', $user['username'])
            ->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey());

        return $token->toString();
    }
}
