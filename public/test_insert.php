// test_insert.php

require 'path/to/TursoClient.php';

$db = new TursoClient($databaseUrl, $authToken);

try {
    $insertSQL = "INSERT INTO users (username, email, password) VALUES (?, ?, ?);";
    $params = ['testuser', 'test@example.com', 'testpassword'];
    $db->execute($insertSQL, $params);
    echo "User inserted successfully.";
} catch (\Exception $e) {
    echo "Insert failed: " . $e->getMessage();
}
