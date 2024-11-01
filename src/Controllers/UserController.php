<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\TursoClient;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\UnencryptedToken;

class UserController
{
    private TursoClient $db;
    private Configuration $jwtConfig;

    public function __construct(TursoClient $db)
    {
        $this->db = $db;
        $secretKey = $_ENV['JWT_SECRET'] ?? '';

        $this->jwtConfig = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($secretKey)
        );
    }

    public function register(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            return $this->respondWithJson($response, ['error' => 'All fields are required.'], 400);
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $this->db->execute("INSERT INTO users (username, email, password) VALUES (?, ?, ?);", [$username, $email, $hashedPassword]);
            return $this->respondWithJson($response, ['message' => 'User registered successfully.'], 201);
        } catch (\Exception $e) {
            return $this->respondWithJson($response, ['error' => 'Registration failed.'], 500);
        }
    }

    public function login(Request $request, Response $response, array $args): Response
{
    error_log("TURSO_DB_URL: " . $_ENV['TURSO_DB_URL']);
error_log("TURSO_AUTH_TOKEN: " . $_ENV['TURSO_AUTH_TOKEN']);

    $data = $request->getParsedBody();
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    error_log("Username provided: $username");
    error_log("Password provided: $password");

    if (empty($username) || empty($password)) {
        return $this->respondWithJson($response, ['error' => 'Username and password are required.'], 400);
    }

    // Create a new TursoClient instance directly
    $databaseUrl = $_ENV['TURSO_DB_URL'];
    $authToken = $_ENV['TURSO_AUTH_TOKEN'];
    $db = new TursoClient($databaseUrl, $authToken);

    try {
        // Hardcode the username directly in the query for testing
        $userResult = $db->execute("SELECT * FROM users WHERE username = 'leeyam21'");

        // Log and return the raw query result to check if data is returned
        error_log("Raw Query Result (Direct Connection): " . json_encode($userResult));
        return $this->respondWithJson($response, ['query_result' => $userResult]);

    } catch (\Exception $e) {
        error_log("Exception encountered: " . $e->getMessage());
        return $this->respondWithJson($response, ['error' => 'Login failed due to server error.'], 500);
    }
}

    


    
    


    public function getProfile(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $userId = $user['uid'];

        try {
            $result = $this->db->execute("SELECT firstName, middleInitial, lastName, birthdate, address FROM users WHERE id = ?", [$userId]);
            if (empty($result)) {
                return $this->respondWithJson($response, ['error' => 'User not found.'], 404);
            }

            return $this->respondWithJson($response, $result[0]);
        } catch (\Exception $e) {
            return $this->respondWithJson($response, ['error' => 'Failed to fetch profile.'], 500);
        }
    }

    public function updateProfile(Request $request, Response $response, array $args): Response
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
            return $this->respondWithJson($response, ['error' => 'First name, last name, and address are required.'], 400);
        }

        try {
            $this->db->execute("UPDATE users SET firstName = ?, middleInitial = ?, lastName = ?, birthdate = ?, address = ? WHERE id = ?", [$firstName, $middleInitial, $lastName, $birthdate, $address, $userId]);
            return $this->respondWithJson($response, ['message' => 'Profile updated successfully.']);
        } catch (\Exception $e) {
            return $this->respondWithJson($response, ['error' => 'Failed to update profile.'], 500);
        }
    }

    private function generateJWT(array $user): string
    {
        $now = new \DateTimeImmutable();
        $expiry = $now->modify('+1 hour');

        return $this->jwtConfig->builder()
            ->issuedBy('Freshly-Backend')
            ->permittedFor('your-application')
            ->identifiedBy(bin2hex(random_bytes(16)))
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($expiry)
            ->withClaim('uid', $user['id'])
            ->withClaim('uname', $user['username'])
            ->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey())
            ->toString();
    }

    private function respondWithJson(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}

