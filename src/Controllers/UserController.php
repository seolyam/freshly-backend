<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\TursoClient;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Slim\Exception\HttpBadRequestException;

class UserController
{
    private $db;
    private Configuration $jwtConfig;

    public function __construct(TursoClient $db)
    {
        $this->db = $db;

        // Load the secret key from environment variables
        $secretKey = $_ENV['JWT_SECRET'];

        // Initialize JWT Configuration
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

        // Basic validation
        if (empty($username) || empty($email) || empty($password)) {
            $error = ['error' => 'All fields are required.'];
            $response->getBody()->write(json_encode($error));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $insertSQL = "INSERT INTO users (username, email, password) VALUES (?, ?, ?);";
            $params = [$username, $email, $hashedPassword];

            $this->db->execute($insertSQL, $params);

            $message = ['message' => 'User registered successfully.'];
            $response->getBody()->write(json_encode($message));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            // Handle duplicate entries
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                $error = ['error' => 'Username or email already exists.'];
                $response->getBody()->write(json_encode($error));
                return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
            }

            // General error
            $error = ['error' => 'Registration failed.'];
            $response->getBody()->write(json_encode($error));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function login(Request $request, Response $response, $args): Response
    {
        $data = $request->getParsedBody();

        $usernameOrEmail = $data['usernameOrEmail'] ?? '';
        $password = $data['password'] ?? '';

        // Basic validation
        if (empty($usernameOrEmail) || empty($password)) {
            $error = ['error' => 'Username/email and password are required.'];
            $response->getBody()->write(json_encode($error));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $selectSQL = "SELECT * FROM users WHERE username = ? OR email = ?;";
            $params = [$usernameOrEmail, $usernameOrEmail];

            $result = $this->db->execute($selectSQL, $params);

            if (empty($result)) {
                $error = ['error' => 'Invalid credentials.'];
                $response->getBody()->write(json_encode($error));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }

            $user = $result[0];

            // Verify password
            if (!password_verify($password, $user['password'])) {
                $error = ['error' => 'Invalid credentials.'];
                $response->getBody()->write(json_encode($error));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }

            // Generate JWT token
            $token = $this->generateJWT($user);

            $response->getBody()->write(json_encode(['token' => $token]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $error = ['error' => 'Login failed.'];
            $response->getBody()->write(json_encode($error));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    private function generateJWT($user)
    {
        $now = new \DateTimeImmutable();
        $expiry = $now->modify('+1 hour');

        $token = $this->jwtConfig->builder()
            ->issuedBy('Freshly-Backend') // Configures the issuer (iss claim)
            ->permittedFor('your-application') // Configures the audience (aud claim)
            ->identifiedBy(bin2hex(random_bytes(16))) // Configures the id (jti claim)
            ->issuedAt($now) // Configures the time that the token was issued (iat claim)
            ->canOnlyBeUsedAfter($now) // Configures the time before which the token cannot be accepted (nbf claim)
            ->expiresAt($expiry) // Configures the expiration time of the token (exp claim)
            ->withClaim('uid', $user['id']) // Configures a new claim, called "uid"
            ->withClaim('uname', $user['username']) // Adds "uname" claim
            ->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey());

        return $token->toString();
    }
}
