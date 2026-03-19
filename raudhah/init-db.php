<?php
/**
 * DATABASE & ADMIN SETUP
 * Pastikan database dan user admin sudah tersedia
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'raudhah_travel');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

echo "========================================\n";
echo "   RAUDHAH DATABASE SETUP\n";
echo "========================================\n\n";

try {
    // STEP 1: Create PDO connection
    echo "[1] Connecting to MySQL Server...\n";
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "    ✓ Connected to MySQL\n\n";
    
    // STEP 2: Create database
    echo "[2] Creating Database (if not exists)...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");
    echo "    ✓ Database ready: " . DB_NAME . "\n\n";
    
    // STEP 3: Create tables using SQL
    echo "[3] Creating Tables...\n";
    
    // Create users table first
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            full_name VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            phone VARCHAR(20) NOT NULL,
            password VARCHAR(255) NOT NULL,
            referral_code VARCHAR(20) NOT NULL UNIQUE,
            referred_by INT UNSIGNED NULL,
            position ENUM('left','right') NULL,
            parent_id INT UNSIGNED NULL,
            role ENUM('admin','user') NOT NULL DEFAULT 'user',
            status ENUM('pending','active','suspended') NOT NULL DEFAULT 'pending',
            profile_photo VARCHAR(255) NULL,
            address TEXT NULL,
            nik VARCHAR(20) NULL,
            bank_name VARCHAR(100) NULL,
            bank_account VARCHAR(50) NULL,
            bank_holder VARCHAR(150) NULL,
            total_commission DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            withdrawn_commission DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (referred_by) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "    ✓ Table 'users' ready\n";
    
    // Create other necessary tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS packages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            slug VARCHAR(200) NOT NULL UNIQUE,
            description TEXT NULL,
            duration_days INT NOT NULL DEFAULT 9,
            price DECIMAL(15,2) NOT NULL,
            quota INT NOT NULL DEFAULT 50,
            filled INT NOT NULL DEFAULT 0,
            departure_date DATE NULL,
            return_date DATE NULL,
            hotel_makkah VARCHAR(200) NULL,
            hotel_madinah VARCHAR(200) NULL,
            airline VARCHAR(100) NULL,
            facilities TEXT NULL,
            thumbnail VARCHAR(255) NULL,
            is_featured TINYINT(1) NOT NULL DEFAULT 0,
            status ENUM('active','inactive','full','completed') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "    ✓ Table 'packages' ready\n";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS registration_payments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            package_id INT UNSIGNED NOT NULL,
            amount DECIMAL(15,2) NOT NULL,
            payment_proof VARCHAR(255) NULL,
            status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            verified_by INT UNSIGNED NULL,
            verified_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (package_id) REFERENCES packages(id),
            FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "    ✓ Table 'registration_payments' ready\n\n";
    
    // STEP 4: Setup Admin User
    echo "[4] Setting up Admin User...\n";
    
    // Check if admin exists
    $adminCheck = $pdo->query("SELECT id FROM users WHERE username = 'admin'");
    
    if ($adminCheck->rowCount() > 0) {
        // Update existing admin
        echo "    Admin user exists, updating password...\n";
        $hash = password_hash('Admin@1234', PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare("UPDATE users SET password = ?, status = 'active', role = 'admin' WHERE username = 'admin'")
            ->execute([$hash]);
    } else {
        // Insert new admin
        echo "    Creating new admin user...\n";
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
    }
    echo "    ✓ Admin user ready\n\n";
    
    // STEP 5: Verify
    echo "[5] Verification...\n";
    $userCount = $pdo->query("SELECT COUNT(*) as cnt FROM users")->fetch(PDO::FETCH_ASSOC)['cnt'];
    $adminUser = $pdo->query("SELECT username, email, role, status FROM users WHERE username = 'admin'")->fetch(PDO::FETCH_ASSOC);
    
    echo "    Users in database: " . $userCount . "\n";
    echo "    Admin username: " . $adminUser['username'] . "\n";
    echo "    Admin email: " . $adminUser['email'] . "\n";
    echo "    Admin role: " . $adminUser['role'] . "\n";
    echo "    Admin status: " . $adminUser['status'] . "\n\n";
    
    // Test password
    $adminFromDb = $pdo->query("SELECT password FROM users WHERE username = 'admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $testPass = password_verify('Admin@1234', $adminFromDb['password']);
    echo "    Password verification: " . ($testPass ? "✓ CORRECT" : "✗ WRONG") . "\n\n";
    
    echo "========================================\n";
    echo "   SETUP COMPLETED SUCCESSFULLY!\n";
    echo "========================================\n\n";
    echo "You can now login at:\n";
    echo "URL: http://localhost/raudhah/login.php\n";
    echo "Username: admin\n";
    echo "Password: Admin@1234\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ DATABASE ERROR:\n";
    echo $e->getMessage() . "\n\n";
    die();
} catch (Exception $e) {
    echo "\n❌ ERROR:\n";
    echo $e->getMessage() . "\n\n";
    die();
}
?>
