<?php
/**
 * FIX DATABASE SCHEMA
 * Drop dan recreate table users dengan struktur lengkap
 */

echo "<h1>Database Schema Fix</h1>";
echo "<hr>";

$host = 'localhost';
$dbname = 'raudhah_travel';
$user = 'root';
$pass = '';

try {
    // Connect
    echo "[1] Connecting to MySQL...\n";
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected<br><br>";
    
    // Create database
    echo "[2] Creating/Selecting Database...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");
    echo "✓ Database ready<br><br>";
    
    // Drop existing users table
    echo "[3] Recreating users table...\n";
    $pdo->exec("DROP TABLE IF EXISTS users CASCADE");
    echo "✓ Old table dropped<br>";
    
    // Create users table dengan struktur lengkap
    $sql = "CREATE TABLE users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        full_name VARCHAR(150) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        phone VARCHAR(20) NOT NULL,
        password VARCHAR(255) NOT NULL,
        referral_code VARCHAR(20) NOT NULL UNIQUE,
        referred_by INT UNSIGNED NULL,
        position ENUM('left','right') NULL COMMENT 'Posisi di bawah parent (kiri/kanan)',
        parent_id INT UNSIGNED NULL COMMENT 'User ID parent langsung',
        role ENUM('admin','user') NOT NULL DEFAULT 'user',
        status ENUM('pending','active','suspended') NOT NULL DEFAULT 'active',
        profile_photo VARCHAR(255) NULL,
        address TEXT NULL,
        nik VARCHAR(20) NULL COMMENT 'Nomor Induk Kependudukan',
        bank_name VARCHAR(100) NULL,
        bank_account VARCHAR(50) NULL,
        bank_holder VARCHAR(150) NULL,
        total_commission DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        withdrawn_commission DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (referred_by) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_username (username),
        INDEX idx_email (email),
        INDEX idx_role (role),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✓ Table created with all columns<br><br>";
    
    // Create other needed tables
    echo "[4] Creating other tables...\n";
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS packages (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Table 'packages' ready<br>";
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS registrations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        package_id INT UNSIGNED NOT NULL,
        registration_number VARCHAR(30) NOT NULL UNIQUE,
        payment_proof VARCHAR(255) NULL,
        payment_amount DECIMAL(15,2) NOT NULL DEFAULT 2000000.00,
        payment_status ENUM('unpaid','pending_verification','verified','rejected') NOT NULL DEFAULT 'unpaid',
        payment_date TIMESTAMP NULL,
        verified_by INT UNSIGNED NULL,
        verified_at TIMESTAMP NULL,
        rejection_reason TEXT NULL,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (package_id) REFERENCES packages(id),
        FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Table 'registrations' ready<br>";
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS commissions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        from_user_id INT UNSIGNED NOT NULL,
        registration_id INT UNSIGNED NOT NULL,
        amount DECIMAL(15,2) NOT NULL DEFAULT 500000.00,
        level INT NOT NULL DEFAULT 1,
        position ENUM('left','right') NULL,
        status ENUM('pending','approved','paid','cancelled') NOT NULL DEFAULT 'pending',
        paid_at TIMESTAMP NULL,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Table 'commissions' ready<br>";
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS bank_accounts (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        bank_name VARCHAR(100) NOT NULL,
        account_number VARCHAR(50) NOT NULL,
        account_holder VARCHAR(150) NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Table 'bank_accounts' ready<br><br>";
    
    // Insert admin user
    echo "[5] Creating admin user...\n";
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
    
    echo "✓ Admin user created<br><br>";
    
    // Verify
    echo "[6] Verification...\n";
    $result = $pdo->query("SELECT id, username, email, role, status FROM users WHERE username = 'admin'");
    $admin = $result->fetch(PDO::FETCH_ASSOC);
    
    echo "✓ Admin Details:\n";
    echo "  - ID: " . $admin['id'] . "\n";
    echo "  - Username: " . $admin['username'] . "\n";
    echo "  - Email: " . $admin['email'] . "\n";
    echo "  - Role: " . $admin['role'] . "\n";
    echo "  - Status: " . $admin['status'] . "\n";
    
    // Test password
    $userRow = $pdo->query("SELECT password FROM users WHERE username = 'admin'")->fetch();
    if (password_verify('Admin@1234', $userRow['password'])) {
        echo "  - Password: ✓ CORRECT\n";
    }
    
    echo "\n========================================\n";
    echo "✓ DATABASE FIXED SUCCESSFULLY!\n";
    echo "========================================\n\n";
    echo "You can now login at:\n";
    echo "URL: http://localhost/raudhah/login.php\n";
    echo "Username: admin\n";
    echo "Password: Admin@1234\n";
    
} catch (Exception $e) {
    echo "<strong style='color:red'>ERROR:</strong> " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
