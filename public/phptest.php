<?php
use App\TursoClient;

require 'path/to/TursoClient.php'; // Adjust the path accordingly

$tursoClient = new TursoClient('your_db_url', 'your_auth_token');

$sql = 'SELECT * FROM products';
$productResult = $tursoClient->executeQuery($sql);

print_r($productResult);
