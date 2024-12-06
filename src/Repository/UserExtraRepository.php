<?php

namespace App\Repository;

use App\TursoClient;

class UserExtraRepository
{
    private TursoClient $db;

    public function __construct(TursoClient $db)
    {
        $this->db = $db;
    }

    public function getExtraByEmail(string $email): ?array
    {
        $result = $this->db->executeQuery(
            'SELECT contactNumber, address, birthdate FROM user_extras WHERE email = ?',
            [$email]
        );

        $rows = $result['results'][0]['response']['result']['rows'] ?? [];
        if (empty($rows)) {
            return null;
        }

        // Adjusting indexing based on TursoClient response structure:
        // Each row will be something like [ [ "value" => "..." ], [ "value" => "..." ], [ "value" => "..." ] ]
        return [
            'contactNumber' => $rows[0][0]['value'] ?? null,
            'address' => $rows[0][1]['value'] ?? null,
            'birthdate' => $rows[0][2]['value'] ?? null,
        ];
    }

    public function upsertExtra(string $email, string $contactNumber, string $address, string $birthdate): bool
    {
        $this->db->executeQuery(
            'INSERT INTO user_extras (email, contactNumber, address, birthdate)
             VALUES (?, ?, ?, ?)
             ON CONFLICT(email) DO UPDATE SET
                contactNumber = excluded.contactNumber,
                address = excluded.address,
                birthdate = excluded.birthdate',
            [$email, $contactNumber, $address, $birthdate]
        );

        return true;
    }
}
