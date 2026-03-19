<?php
require_once __DIR__ . '/php/config.php';

try {
    // Connect ke MySQL tanpa database tertentu
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS
    );
    
    echo "Testing MySQL Connection...\n";
    echo "✓ MySQL Connection OK\n\n";
    
    // Check apakah database sudah ada
    $result = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    if ($result->rowCount() > 0) {
        echo "✓ Database '" . DB_NAME . "' sudah ada\n";
    } else {
        echo "❌ Database '" . DB_NAME . "' BELUM ada\n";
        echo "Perlu menjalankan SQL script untuk setup database.\n";
    }
    
    // Switch ke database dan check table users
    $pdo->exec("USE " . DB_NAME);
    $userCheck = $pdo->query("SELECT COUNT(*) as total FROM users");
    $userCount = $userCheck->fetch(PDO::FETCH_ASSOC)['total'];
    echo "\nTotal Users in Database: " . $userCount . "\n";
    
} catch (PDOException $e) {
    echo "❌ Connection Error: " . $e->getMessage() . "\n";
}
?>
