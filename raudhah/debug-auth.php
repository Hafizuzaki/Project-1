<?php
/**
 * AUTHENTICATION DEBUG TOOL
 * Test database connection, user existence, dan password verification
 */

echo "<h1>Authentication Debug Tool</h1>";
echo "<hr>";

require_once __DIR__ . '/php/config.php';

// Test 1: Database Connection
echo "<h2>1. Database Connection</h2>";
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✓ Connected to: " . DB_HOST . "/" . DB_NAME . "<br>";
} catch (Exception $e) {
    echo "✗ Connection failed: " . $e->getMessage() . "<br>";
    die();
}

// Test 2: Check users table
echo "<h2>2. Check Users Table</h2>";
try {
    $result = $pdo->query("SELECT COUNT(*) as cnt FROM users");
    $count = $result->fetch(PDO::FETCH_ASSOC)['cnt'];
    echo "✓ Table exists with " . $count . " users<br>";
} catch (Exception $e) {
    echo "✗ Table error: " . $e->getMessage() . "<br>";
    die();
}

// Test 3: Check admin user
echo "<h2>3. Check Admin User</h2>";
$stmt = $pdo->prepare("SELECT id, username, email, full_name, role, status, password FROM users WHERE username = 'admin'");
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    echo "✗ Admin user NOT FOUND<br>";
    echo "Need to create admin user<br><br>";
    
    // Create admin
    echo "<h2>Creating Admin User...</h2>";
    $password = 'Admin@1234';
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    $stmt = $pdo->prepare(
        "INSERT INTO users (username, full_name, email, phone, password, referral_code, role, status) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    
    try {
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
        echo "✓ Admin user created successfully<br>";
        $admin = ['username' => 'admin', 'password' => $hash, 'role' => 'admin', 'status' => 'active'];
    } catch (Exception $e) {
        echo "✗ Error creating admin: " . $e->getMessage() . "<br>";
        die();
    }
} else {
    echo "✓ Admin user found:<br>";
    echo "  ID: " . $admin['id'] . "<br>";
    echo "  Username: " . $admin['username'] . "<br>";
    echo "  Email: " . $admin['email'] . "<br>";
    echo "  Role: " . $admin['role'] . "<br>";
    echo "  Status: " . $admin['status'] . "<br>";
}

// Test 4: Password Verification
echo "<h2>4. Password Verification</h2>";
$testPassword = 'Admin@1234';
if (password_verify($testPassword, $admin['password'])) {
    echo "✓ Password 'Admin@1234' matches the hash<br>";
} else {
    echo "✗ Password does NOT match<br>";
    echo "Hash: " . $admin['password'] . "<br>";
    
    // Regenerate hash
    echo "<br>Regenerating hash...<br>";
    $newHash = password_hash($testPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$newHash]);
    echo "✓ Hash updated<br>";
}

// Test 5: Login Simulation
echo "<h2>5. Login Simulation</h2>";
$username = 'admin';

// This mimics the auth.php logic
$stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) LIMIT 1");
$stmt->execute([$username, $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "✗ User not found during login<br>";
} else {
    echo "✓ User found: " . $user['username'] . "<br>";
    
    if (password_verify($testPassword, $user['password'])) {
        echo "✓ Password verified<br>";
    } else {
        echo "✗ Password NOT verified<br>";
    }
    
    if ($user['status'] !== 'active') {
        echo "✗ User status is '" . $user['status'] . "' (not 'active')<br>";
    } else {
        echo "✓ User status is 'active'<br>";
    }
}

// Summary
echo "<hr>";
echo "<h2>Summary</h2>";
echo "Database: <strong>" . DB_NAME . "</strong><br>";
echo "Admin Username: <strong>admin</strong><br>";
echo "Admin Password: <strong>Admin@1234</strong><br>";
echo "Admin Role: <strong>" . $admin['role'] . "</strong><br>";
echo "<br>";
echo "You can now login at: <a href='http://localhost/raudhah/login.php' target='_blank'>login.php</a><br>";
?>
