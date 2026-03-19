<?php
// Test file to debug auth.php access
echo "<h1>Auth Test</h1>";
echo "PHP is working<br>";
echo "Current file: " . __FILE__ . "<br>";
echo "Session status: " . session_status() . "<br>";
echo "POST data: <pre>";
print_r($_POST);
echo "</pre>";
echo "GET data: <pre>";
print_r($_GET);
echo "</pre>";
echo "\nIf you can see this, php/auth.php should be accessible.<br>";
?>
