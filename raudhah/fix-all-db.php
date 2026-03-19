<?php
/**
 * COMPREHENSIVE DATABASE FIX
 * Memastikan semua table dan column ada dengan struktur yang benar
 */

echo "<h1>Database Comprehensive Fix</h1>";
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
    echo "✓ Connected to MySQL Server<br><br>";
    
    // Create/Select database
    echo "[2] Setting up Database...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");
    echo "✓ Database '$dbname' ready<br><br>";
    
    // Drop all foreign key constraints dan recreate tables
    echo "[3] Dropping old tables (if exist)...\n";
    $tables = ['commissions', 'registrations', 'notifications', 'activity_logs', 'withdrawals', 'bank_accounts', 'packages', 'users'];
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
    }
    echo "✓ Old tables cleared<br><br>";
    
    // Create users table
    echo "[4] Creating all tables...\n";
    $pdo->exec("CREATE TABLE users (
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
        status ENUM('pending','active','suspended') NOT NULL DEFAULT 'active',
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
        FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_username (username),
        INDEX idx_email (email),
        INDEX idx_role (role),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Table 'users' created<br>";
    
    // Packages
    $pdo->exec("CREATE TABLE packages (
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
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Table 'packages' created<br>";
    
    // Registrations - NOTE: payment_status NOT status
    $pdo->exec("CREATE TABLE registrations (
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
        FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_payment_status (payment_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Table 'registrations' created (payment_status NOT status)<br>";
    
    // Commissions
    $pdo->exec("CREATE TABLE commissions (
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
        FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Table 'commissions' created<br>";
    
    // Withdrawals
    $pdo->exec("CREATE TABLE withdrawals (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        amount DECIMAL(15,2) NOT NULL,
        bank_name VARCHAR(100),
        account_number VARCHAR(50),
        account_holder VARCHAR(150),
        status ENUM('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
        approved_by INT UNSIGNED NULL,
        approved_at TIMESTAMP NULL,
        rejection_reason TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Table 'withdrawals' created<br>";
    
    // Bank accounts
    $pdo->exec("CREATE TABLE bank_accounts (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        bank_name VARCHAR(100) NOT NULL,
        account_number VARCHAR(50) NOT NULL,
        account_holder VARCHAR(150) NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_is_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Table 'bank_accounts' created<br>";
    
    // Notifications - IMPORTANT: has is_read column
    $pdo->exec("CREATE TABLE notifications (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        title VARCHAR(200) NOT NULL,
        message TEXT,
        type ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info',
        link VARCHAR(255) NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_is_read (is_read)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Table 'notifications' created (with is_read column)<br>";
    
    // Activity logs
    $pdo->exec("CREATE TABLE activity_logs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NULL,
        action VARCHAR(100) NOT NULL,
        description TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_action (action),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Table 'activity_logs' created<br><br>";
    
    // Insert admin operator
    echo "[5] Creating Admin Operator...\n";
    $password = 'Admin@1234';
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    $stmt = $pdo->prepare(
        "INSERT INTO users (username, full_name, email, phone, password, referral_code, role, status) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    
    $stmt->execute([
        'admin',
        'Administrator (Operator)',
        'admin@raudhah.com',
        '08123456789',
        $hash,
        'RAW-ADMIN',
        'admin',
        'active'
    ]);
    echo "✓ Admin Operator created<br>";
    
    // Insert sample banks
    echo "[6] Populating sample data...\n";
    $pdo->exec("INSERT INTO bank_accounts (bank_name, account_number, account_holder) VALUES 
        ('BCA', '1234567890', 'PT RAUDHAH AMANAH WISATA'),
        ('BRI', '0987654321', 'PT RAUDHAH AMANAH WISATA'),
        ('Mandiri', '1122334455', 'PT RAUDHAH AMANAH WISATA')");
    echo "✓ Bank accounts inserted<br><br>";
    
    // Verify
    echo "[7] Verification...\n";
    $admin = $pdo->query("SELECT id, username, role, status FROM users WHERE username = 'admin'")->fetch(PDO::FETCH_ASSOC);
    echo "✓ Admin found:\n";
    echo "  - Username: " . $admin['username'] . "<br>";
    echo "  - Role: " . $admin['role'] . "<br>";
    echo "  - Status: " . $admin['status'] . "<br><br>";
    
    // Check table columns
    echo "[8] Verifying table structures...\n";
    
    $tables_check = [
        'users' => ['status', 'role', 'email'],
        'registrations' => ['payment_status'],  // IMPORTANT: NOT status
        'withdrawals' => ['status', 'user_id'],
        'notifications' => ['is_read', 'user_id'],
        'commissions' => ['status', 'user_id']
    ];
    
    foreach ($tables_check as $table => $columns) {
        foreach ($columns as $col) {
            $result = $pdo->query("SHOW COLUMNS FROM $table WHERE Field = '$col'")->rowCount();
            if ($result > 0) {
                echo "✓ Table '$table' has column '$col'<br>";
            } else {
                echo "✗ Table '$table' MISSING column '$col'<br>";
            }
        }
    }
    
    echo "<br>========================================<br>";
    echo "✓ DATABASE FIXED SUCCESSFULLY!<br>";
    echo "========================================<br><br>";
    echo "Admin Operator Login:<br>";
    echo "URL: http://localhost/raudhah/login.php<br>";
    echo "Username: <strong>admin</strong><br>";
    echo "Password: <strong>Admin@1234</strong><br><br>";
    echo "All tables created with correct columns!<br>";
    
} catch (Exception $e) {
    echo "<strong style='color:red'>ERROR:</strong> " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
