<?php

namespace App\Controllers;

use App\Repository\UserExtraRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserExtraController
{
    private UserExtraRepository $extraRepo;

    public function __construct(UserExtraRepository $extraRepo)
    {
        $this->extraRepo = $extraRepo;
    }

    public function updateUserInfo(Request $request, Response $response, array $args): Response
    {
        $userClaims = $request->getAttribute('user');
        $email = $userClaims['email'] ?? null;

        if (!$email) {
            return $this->respondWithJson($response, ['error' => 'User not found.'], 404);
        }

        $data = $request->getParsedBody();
        $contactNumber = $data['contactNumber'] ?? '';
        $address = $data['address'] ?? '';
        $birthdate = $data['birthdate'] ?? '';

        if (empty($contactNumber) || empty($address)) {
            return $this->respondWithJson($response, [
                'error' => 'Contact number and address are required.'
            ], 400);
        }
        
        // If birthdate is optional, handle accordingly
        

        // Update local Turso DB with the extra fields
        $this->extraRepo->upsertExtra($email, $contactNumber, $address, $birthdate);

        return $this->respondWithJson($response, ['success' => true, 'message' => 'Extra info updated'], 200);
    }

    private function respondWithJson(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
