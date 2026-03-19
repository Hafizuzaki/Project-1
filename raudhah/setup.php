<?php
// Direct MySQL connection dan setup admin
$host = 'localhost';
$db = 'raudhah_travel';
$user = 'root';
$pass = '';

echo "<h1>Database Setup</h1>";
echo "<hr>";

try {
    // Connect
    $conn = new mysqli($host, $user, $pass);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    echo "✓ Connected to MySQL<br>";
    
    // Create database
    $conn->query("CREATE DATABASE IF NOT EXISTS `raudhah_travel`");
    $conn->select_db($db);
    echo "✓ Database selected<br>";
    
    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        full_name VARCHAR(150) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        phone VARCHAR(20),
        password VARCHAR(255) NOT NULL,
        referral_code VARCHAR(20) NOT NULL UNIQUE,
        role ENUM('admin','user') DEFAULT 'user',
        status ENUM('pending','active','suspended') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql)) {
        echo "✓ Users table ready<br>";
    }
    
    // Check if admin exists
    $result = $conn->query("SELECT id FROM users WHERE username = 'admin'");
    
    if ($result->num_rows > 0) {
        echo "✓ Admin user exists<br>";
        // Delete and recreate
        $conn->query("DELETE FROM users WHERE username = 'admin'");
        echo "  Re-creating admin...<br>";
    }
    
    // Insert admin user
    $password = 'Admin@1234';
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    $stmt = $conn->prepare(
        "INSERT INTO users (username, full_name, email, phone, password, referral_code, role, status) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    
    $stmt->bind_param(
        "ssssssss",
        $admin_user,
        $admin_name,
        $admin_email,
        $admin_phone,
        $admin_pass,
        $admin_ref,
        $admin_role,
        $admin_status
    );
    
    $admin_user = 'admin';
    $admin_name = 'Administrator';
    $admin_email = 'admin@raudhah.com';
    $admin_phone = '08123456789';
    $admin_pass = $hash;
    $admin_ref = 'RAW-ADMIN';
    $admin_role = 'admin';
    $admin_status = 'active';
    
    if ($stmt->execute()) {
        echo "✓ Admin user created/updated<br>";
    } else {
        echo "✗ Error: " . $stmt->error . "<br>";
    }
    
    // Verify
    $result = $conn->query("SELECT username, email, role, status, password FROM users WHERE username = 'admin'");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "<br>";
        echo "<strong>Admin Details:</strong><br>";
        echo "Username: " . htmlspecialchars($row['username']) . "<br>";
        echo "Email: " . htmlspecialchars($row['email']) . "<br>";
        echo "Role: " . htmlspecialchars($row['role']) . "<br>";
        echo "Status: " . htmlspecialchars($row['status']) . "<br>";
        
        // Test password
        if (password_verify('Admin@1234', $row['password'])) {
            echo "Password verification: <strong style='color:green'>✓ CORRECT</strong><br>";
        } else {
            echo "Password verification: <strong style='color:red'>✗ WRONG</strong><br>";
        }
    }
    
    echo "<hr>";
    echo "<h2 style='color:green'>Setup Complete!</h2>";
    echo "You can now login at: <a href='http://localhost/raudhah/login.php'>login.php</a><br>";
    echo "<strong>Username:</strong> admin<br>";
    echo "<strong>Password:</strong> Admin@1234<br>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<strong style='color:red'>Error:</strong> " . $e->getMessage();
}
?>
