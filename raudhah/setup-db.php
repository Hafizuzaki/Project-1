<?php
require_once __DIR__ . '/php/config.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET, DB_USER, DB_PASS);
    
    echo "Checking Database Setup...\n";
    echo "===========================\n\n";
    
    // Check if database exists
    $result = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    
    if ($result->rowCount() == 0) {
        echo "Database not found. Creating...\n";
        
        // Read SQL file
        $sqlFile = __DIR__ . '/sql/database.sql';
        if (!file_exists($sqlFile)) {
            die("ERROR: database.sql not found at: " . $sqlFile . "\n");
        }
        
        // Read and execute SQL
        $sql = file_get_contents($sqlFile);
        
        // Split by ; and execute each statement
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($s) => !empty($s) && !str_starts_with($s, '--')
        );
        
        foreach ($statements as $statement) {
            if (!empty(trim($statement))) {
                try {
                    $pdo->exec($statement);
                } catch (Exception $e) {
                    // Continue on error (some statements may have conditional creates)
                }
            }
        }
        
        echo "✓ Database created and populated\n\n";
    } else {
        echo "✓ Database already exists\n\n";
    }
    
    // Switch to database and check admin user
    $pdo->exec("USE " . DB_NAME);
    
    $adminCheck = $pdo->query("SELECT username, email, password FROM users WHERE username = 'admin'");
    if ($adminCheck->rowCount() > 0) {
        $admin = $adminCheck->fetch(PDO::FETCH_ASSOC);
        echo "Admin User Status:\n";
        echo "==================\n";
        echo "Username: " . $admin['username'] . "\n";
        echo "Email: " . $admin['email'] . "\n";
        echo "Password Hash: " . substr($admin['password'], 0, 20) . "...\n\n";
        
        // Test password
        if (password_verify('Admin@1234', $admin['password'])) {
            echo "✓ Password verification: CORRECT\n";
            echo "✓ You can login with: admin / Admin@1234\n";
        } else {
            echo "❌ Password verification: WRONG\n";
            echo "⚠ Password hash mismatch. This is unusual.\n";
        }
    } else {
        echo "❌ Admin user NOT found in database!\n";
        echo "Trying to insert admin user...\n\n";
        
        $hash = password_hash('Admin@1234', PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare(
            "INSERT INTO users (username, full_name, email, phone, password, referral_code, role, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([
            'admin',
            'Administrator',
            'admin@raudhah.com',
            '08123456789',
            $hash,
            'RAW-ADMIN',
            'admin',
            'active'
        ]);
        
        echo "✓ Admin user inserted successfully\n";
        echo "✓ You can now login with: admin / Admin@1234\n";
    }
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
?>
