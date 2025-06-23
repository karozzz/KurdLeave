<?php
// generate_hash.php - Generate correct password hash

echo "<h2>Password Hash Generator</h2>";

$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<p><strong>Password:</strong> $password</p>";
echo "<p><strong>Generated Hash:</strong></p>";
echo "<code style='word-break: break-all; background: #f8f9fa; padding: 10px; display: block; margin: 10px 0;'>$hash</code>";

echo "<p>Copy this hash and use it in your sample_data.sql file.</p>";

// Verify it works
$verify = password_verify($password, $hash);
echo "<p><strong>Verification:</strong> " . ($verify ? "✅ CORRECT" : "❌ FAILED") . "</p>";
?>
