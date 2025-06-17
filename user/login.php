<?php
// user/login.php - Login Page

require_once '../php/functions.php';

// If already logged in, redirect
if (is_logged_in()) {
    if (is_admin()) {
        redirect(APP_URL . '/admin/admin_dashboard.php');
    } else {
        redirect(APP_URL . '/user/home.php');
    }
}

$error_message = '';
$success_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error_message = 'Please enter both email and password.';
    } else {
        if (login($email, $password)) {
            // Redirect based on role
            if (is_admin()) {
                redirect(APP_URL . '/admin/admin_dashboard.php');
            } else {
                redirect(APP_URL . '/user/home.php');
            }
        } else {
            $error_message = 'Invalid email or password.';
        }
    }
}

// Get flash messages
$flash = get_flash_message();
if ($flash) {
    if ($flash['type'] === 'success') {
        $success_message = $flash['message'];
    } else {
        $error_message = $flash['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KurdLeave — Login</title>
  <link rel="stylesheet" href="../usercss/user-styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="container">
    <div class="login-container">
      <div class="system-title">
        <h1><i class="fas fa-calendar-check"></i> KurdLeave System</h1>
        <p>Employee Leave Management</p>
      </div>

      <?php if ($error_message): ?>
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
        </div>
      <?php endif; ?>

      <?php if ($success_message): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="form-group">
          <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
          <input type="email" id="email" name="email" required
                 value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                 placeholder="Enter your email address">
        </div>

        <div class="form-group">
          <label for="password"><i class="fas fa-lock"></i> Password</label>
          <input type="password" id="password" name="password" required
                 placeholder="Enter your password">
        </div>

        <div class="form-group checkbox-group">
          <input type="checkbox" id="remember" name="remember">
          <label for="remember">Remember me</label>
        </div>

        <button type="submit" class="login-btn">
          <i class="fas fa-sign-in-alt"></i> Login
        </button>
      </form>

      <div class="forgot-password">
        <a href="#"><i class="fas fa-question-circle"></i> Forgot your password?</a>
      </div>

      <div class="mt-3">
        <h3>Demo Accounts:</h3>
        <div class="alert alert-info">
          <strong>Admin:</strong> admin@example.com / admin123<br>
          <strong>Manager:</strong> rawa@example.com / admin123<br>
          <strong>Employee:</strong> michael@example.com / admin123
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="footer">
      <p>KurdLeave System &copy; 2025</p>
    </div>
  </div>

  <script>
    // Auto-focus on email field
    document.getElementById('email').focus();

    // Show/hide password functionality (optional)
    document.addEventListener('DOMContentLoaded', function() {
      const passwordField = document.getElementById('password');

      // You can add show/hide password toggle here if needed
    });
  </script>
</body>
</html>
