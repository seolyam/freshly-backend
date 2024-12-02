<?php

require __DIR__ . '/../vendor/autoload.php';

use App\TursoClient;

// Replace these with your actual database URL and token
$databaseUrl = 'libsql://freshly-seolyam.turso.io';
$authToken = 'eyJhbGciOiJFZERTQSIsInR5cCI6IkpXVCJ9.eyJhIjoicnciLCJpYXQiOjE3MzAxMDYyNTUsImlkIjoiNzU1Nzg3ZmYtM2JkZC00NjdmLWE5NjQtODc5Yzc1NmJkZjIyIn0.wuF3HiZRuLLfuUonWn2Ow7RqqEKFkmHiqH6WrSb8Qhdl7h3XeAfS4-pbPC4mWy2pmxbxaSS_bbPpKljKMVWFBg';

$tursoClient = new TursoClient($databaseUrl, $authToken);

$sql = 'SELECT * FROM products';
$result = $tursoClient->executeQuery($sql);

print_r($result);
