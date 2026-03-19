<?php
/**
 * ADMIN PASSWORD RESET TOOL
 * Ini akan auto-setup database dan reset password admin jika diperlukan
 */

require_once __DIR__ . '/php/config.php';

echo "=== RAUDHAH ADMIN SETUP TOOL ===\n\n";

try {
    // Step 1: Connect to MySQL
    echo "Step 1: Connecting to MySQL...\n";
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✓ MySQL Connection OK\n\n";
    
    // Step 2: Check/Create Database
    echo "Step 2: Checking Database...\n";
    try {
        $pdo->exec("USE " . DB_NAME);
        echo "✓ Database '" . DB_NAME . "' exists\n\n";
    } catch (Exception $e) {
        echo "Creating database '" . DB_NAME . "'...\n";
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE " . DB_NAME);
        echo "✓ Database created\n\n";
    }
    
    // Step 3: Check if users table exists
    echo "Step 3: Checking Tables...\n";
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($tableCheck->rowCount() == 0) {
        echo "Users table not found. Importing SQL schema...\n";
        
        $sqlFile = __DIR__ . '/sql/database.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("SQL file not found: " . $sqlFile);
        }
        
        $sql = file_get_contents($sqlFile);
        
        // Remove SQL comments and split by semicolon
        $sql = preg_replace('/^--.*$/m', '', $sql); // Remove comments
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($s) => !empty(trim($s))
        );
        
        $count = 0;
        foreach ($statements as $statement) {
            try {
                if (stripos($statement, 'USE') !== 0) { // Skip USE statements
                    $pdo->exec($statement);
                    $count++;
                }
            } catch (Exception $e) {
                // Silently continue on errors (duplicate keys, etc)
            }
        }
        
        echo "✓ Database schema imported (" . $count . " statements)\n\n";
    } else {
        echo "✓ Tables already exist\n\n";
    }
    
    // Step 4: Check/Insert Admin User
    echo "Step 4: Setting up Admin User...\n";
    
    $adminCheck = $pdo->query("SELECT id, username, password FROM users WHERE username = 'admin'");
    
    if ($adminCheck->rowCount() > 0) {
        $admin = $adminCheck->fetch(PDO::FETCH_ASSOC);
        echo "Admin user found (ID: " . $admin['id'] . ")\n";
        
        // Generate new password hash
        $newPassword = 'Admin@1234';
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        
        $pdo->prepare("UPDATE users SET password = ?, status = 'active' WHERE id = ?")
            ->execute([$newHash, $admin['id']]);
        
        echo "✓ Admin password reset to: " . $newPassword . "\n\n";
    } else {
        echo "Admin user not found. Creating...\n";
        
        $password = 'Admin@1234';
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        
        $stmt = $pdo->prepare(
            "INSERT INTO users (username, full_name, email, phone, password, referral_code, role, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        $stmt->execute([
            'admin',
            'Administrator',
            'admin@raudhah.com',
            '08123456789',
            $hash,
            'RAW-ADMIN',
            'admin',
            'active'
        ]);
        
        echo "✓ Admin user created\n";
        echo "✓ Default password: " . $password . "\n\n";
    }
    
    // Step 5: Verify Setup
    echo "Step 5: Verifying Setup...\n";
    $userCount = $pdo->query("SELECT COUNT(*) as cnt FROM users")->fetch(PDO::FETCH_ASSOC)['cnt'];
    echo "✓ Total users in database: " . $userCount . "\n\n";
    
    echo "=== SETUP COMPLETE ===\n";
    echo "✓ Database: " . DB_NAME . "\n";
    echo "✓ Admin Username: admin\n";
    echo "✓ Admin Password: Admin@1234\n\n";
    echo "You can now login at: http://localhost/raudhah/login.php\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
?>
