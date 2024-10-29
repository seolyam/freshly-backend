<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\TursoClient;

echo "Attempting to instantiate TursoClient...\n";

$databaseUrl = getenv('TURSO_DB_URL');
$authToken = getenv('TURSO_AUTH_TOKEN');

$tursoClient = new TursoClient($databaseUrl, $authToken);

echo "TursoClient instantiated successfully.\n";
