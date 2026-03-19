<?php
// Test password hash
$hash = '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TiGc2L2SJFXe6DHNMQ5SiW9Ozt0G';
$password = 'Admin@1234';

echo "Password Hash Verification Test:\n";
echo "================================\n";
echo "Stored Hash: " . $hash . "\n";
echo "Test Password: " . $password . "\n";
echo "Result: " . (password_verify($password, $hash) ? "✓ CORRECT" : "✗ WRONG") . "\n";
?>
