<?php
require_once __DIR__ . '/php/config.php';
require_once __DIR__ . '/php/database.php';

try {
    $db = Database::getInstance();
    $admin = $db->fetchOne("SELECT id, username, email, password, role, status FROM users WHERE username = 'admin' LIMIT 1", []);
    
    if ($admin) {
        echo "Admin User Found:\n";
        echo "================\n";
        echo "ID: " . $admin['id'] . "\n";
        echo "Username: " . $admin['username'] . "\n";
        echo "Email: " . $admin['email'] . "\n";
        echo "Role: " . $admin['role'] . "\n";
        echo "Status: " . $admin['status'] . "\n";
        echo "Password Hash: " . $admin['password'] . "\n\n";
        
        // Test password
        $testPassword = 'Admin@1234';
        $hashMatch = password_verify($testPassword, $admin['password']);
        echo "Password Test (Admin@1234): " . ($hashMatch ? "✓ CORRECT" : "✗ WRONG") . "\n";
    } else {
        echo "❌ Admin user NOT FOUND in database!\n";
        echo "Need to insert admin user manually.\n";
    }
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    echo "Make sure database raudhah_travel exists and is populated.\n";
}
?>
