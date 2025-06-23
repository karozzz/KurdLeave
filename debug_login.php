<?php
// debug_login.php - Debug login issues

require_once 'php/functions.php';

echo "<h2>Login Debug Information</h2>";

try {
    // Check if we can connect to database
    $users = db_fetch_all("SELECT id, employee_id, name, email, password, status FROM users LIMIT 5");

    echo "<h3>✓ Database Connection Working</h3>";
    echo "<p>Found " . count($users) . " users in database</p>";

    echo "<h3>Users in Database:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Employee ID</th><th>Name</th><th>Email</th><th>Status</th><th>Password Hash (first 20 chars)</th></tr>";

    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . $user['employee_id'] . "</td>";
        echo "<td>" . $user['name'] . "</td>";
        echo "<td>" . $user['email'] . "</td>";
        echo "<td>" . $user['status'] . "</td>";
        echo "<td>" . substr($user['password'], 0, 20) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Test password verification
    echo "<h3>Password Verification Test:</h3>";

    $test_emails = ['admin@example.com', 'rawa@example.com', 'michael@example.com'];
    $test_password = 'admin123';

    foreach ($test_emails as $email) {
        $user = db_fetch("SELECT * FROM users WHERE email = ? AND status = 'active'", [$email]);

        if ($user) {
            $verify_result = password_verify($test_password, $user['password']);
            echo "<p><strong>$email:</strong> ";
            echo $verify_result ?
                "<span style='color: green;'>✓ Password 'admin123' is CORRECT</span>" :
                "<span style='color: red;'>✗ Password 'admin123' is INCORRECT</span>";
            echo "</p>";
        } else {
            echo "<p><strong>$email:</strong> <span style='color: red;'>✗ User not found or inactive</span></p>";
        }
    }

    echo "<hr>";
    echo "<h3>Generate New Password Hashes:</h3>";
    echo "<p>If passwords are incorrect, here are new hashes for 'admin123':</p>";
    echo "<code>";
    echo "UPDATE users SET password = '" . password_hash('admin123', PASSWORD_DEFAULT) . "' WHERE email = 'admin@example.com';<br>";
    echo "UPDATE users SET password = '" . password_hash('admin123', PASSWORD_DEFAULT) . "' WHERE email = 'rawa@example.com';<br>";
    echo "UPDATE users SET password = '" . password_hash('admin123', PASSWORD_DEFAULT) . "' WHERE email = 'michael@example.com';<br>";
    echo "</code>";

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database Error: " . $e->getMessage() . "</p>";

    echo "<h3>Connection Details:</h3>";
    echo "<ul>";
    echo "<li>Host: " . DB_HOST . "</li>";
    echo "<li>Port: " . DB_PORT . "</li>";
    echo "<li>Database: " . DB_DATABASE . "</li>";
    echo "<li>Username: " . DB_USERNAME . "</li>";
    echo "</ul>";
}

// Manual login test
if (isset($_POST['test_login'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    echo "<h3>Manual Login Test Result:</h3>";

    if (login($email, $password)) {
        echo "<p style='color: green;'>✓ Login SUCCESSFUL for $email</p>";
        echo "<p>Session variables set:</p>";
        echo "<ul>";
        echo "<li>User ID: " . ($_SESSION['user_id'] ?? 'not set') . "</li>";
        echo "<li>User Name: " . ($_SESSION['user_name'] ?? 'not set') . "</li>";
        echo "<li>User Role: " . ($_SESSION['user_role'] ?? 'not set') . "</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>✗ Login FAILED for $email</p>";
    }
}
?>

<hr>
<h3>Test Login Form:</h3>
<form method="POST">
    <p>
        <label>Email: <input type="email" name="email" value="admin@example.com" required></label>
    </p>
    <p>
        <label>Password: <input type="password" name="password" value="admin123" required></label>
    </p>
    <p>
        <button type="submit" name="test_login">Test Login</button>
    </p>
</form>
