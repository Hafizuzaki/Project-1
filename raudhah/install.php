<?php
/**
 * SETUP DATABASE FROM SQL FILE
 * Lansung import database.sql dan setup admin
 */

$host = 'localhost';
$dbname = 'raudhah_travel';
$user = 'root';
$pass = '';

echo "<pre>\n";
echo "========================================\n";
echo "  DATABASE SETUP - IMPORT SQL FILE\n";
echo "========================================\n\n";

try {
    // Step 1: Connect to MySQL
    echo "[1] Connecting to MySQL...\n";
    $conn = new mysqli($host, $user, $pass);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    echo "✓ Connected\n\n";
    
    // Step 2: Create database
    echo "[2] Creating database...\n";
    $conn->query("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db($dbname);
    echo "✓ Database ready\n\n";
    
    // Step 3: Drop and recreate tables (fresh setup)
    echo "[3] Creating tables from schema...\n";
    
    // Users table
    $sql_users = "CREATE TABLE IF NOT EXISTS users (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql_users)) {
        echo "✓ Table 'users' created\n";
    }
    
    // Packages table
    $sql_packages = "CREATE TABLE IF NOT EXISTS packages (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->query($sql_packages);
    echo "✓ Table 'packages' created\n";
    
    // Bank accounts table
    $sql_banks = "CREATE TABLE IF NOT EXISTS bank_accounts (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        bank_name VARCHAR(100) NOT NULL,
        account_number VARCHAR(50) NOT NULL,
        account_holder VARCHAR(150) NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->query($sql_banks);
    echo "✓ Table 'bank_accounts' created\n";
    
    echo "\n";
    
    // Step 4: Delete existing admin and insert fresh admin
    echo "[4] Setting up admin user...\n";
    $conn->query("DELETE FROM users WHERE username = 'admin'");
    
    $password = 'Admin@1234';
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    $stmt = $conn->prepare(
        "INSERT INTO users (username, full_name, email, phone, password, referral_code, role, status) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    
    if (!$stmt) {
        echo "Prepare failed: " . $conn->error . "\n";
        die();
    }
    
    $stmt->bind_param(
        "ssssssss",
        $username,
        $fullname,
        $email,
        $phone,
        $hash,
        $referral,
        $role,
        $status
    );
    
    $username = 'admin';
    $fullname = 'Administrator';
    $email = 'admin@raudhah.com';
    $phone = '08123456789';
    $role = 'admin';
    $status = 'active';
    $referral = 'RAW-ADMIN';
    
    if ($stmt->execute()) {
        echo "✓ Admin user created\n\n";
    } else {
        echo "✗ Failed to create admin: " . $stmt->error . "\n";
    }
    
    // Step 5: Verify
    echo "[5] Verification...\n";
    $result = $conn->query("SELECT id, username, email, role, status, password FROM users WHERE username = 'admin'");
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "✓ Admin found in database\n";
        echo "  ID: " . $row['id'] . "\n";
        echo "  Username: " . $row['username'] . "\n";
        echo "  Email: " . $row['email'] . "\n";
        echo "  Role: " . $row['role'] . "\n";
        echo "  Status: " . $row['status'] . "\n";
        
        if (password_verify('Admin@1234', $row['password'])) {
            echo "  Password: ✓ CORRECT\n";
        } else {
            echo "  Password: ✗ WRONG\n";
        }
    }
    
    echo "\n========================================\n";
    echo "  ✓ SETUP COMPLETED SUCCESSFULLY!\n";
    echo "========================================\n\n";
    echo "You can now login:\n";
    echo "URL: http://localhost/raudhah/login.php\n";
    echo "Username: admin\n";
    echo "Password: Admin@1234\n";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
?>
