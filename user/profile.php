<?php
// user/profile.php - User Profile Page

require_once '../php/functions.php';

// Require login
require_login();

// Get current user data
$user = get_logged_in_user();
$department = get_department_by_id($user['department_id']);
$manager = $user['manager_id'] ? get_user_by_id($user['manager_id']) : null;

// Get user's leave balances for current year
$year = date('Y');
$leave_types = get_all_leave_types();
$leave_balances = [];
foreach ($leave_types as $leave_type) {
    $balance = get_user_leave_balance($user['id'], $leave_type['id'], $year);
    if ($balance) {
        $leave_balances[$leave_type['id']] = $balance;
    } else {
        // Create default balance if doesn't exist
        $default_balance = [
            'total_allocation' => $leave_type['default_allocation'],
            'used_days' => 0,
            'remaining_days' => $leave_type['default_allocation']
        ];
        $leave_balances[$leave_type['id']] = $default_balance;
    }
}

$success_message = '';
$error_message = '';

// Handle password update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = 'All password fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'New passwords do not match.';
    } elseif (strlen($new_password) < 8) {
        $error_message = 'Password must be at least 8 characters long.';
    } elseif (!password_verify($current_password, $user['password'])) {
        $error_message = 'Current password is incorrect.';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        if (db_update('users', ['password' => $hashed_password], 'id = ?', [$user['id']])) {
            $success_message = 'Password updated successfully.';
            log_activity($user['id'], 'Password Update', 'User updated their password');
        } else {
            $error_message = 'Failed to update password. Please try again.';
        }
    }
}

// Handle contact information update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_contact'])) {
    $phone = sanitize_input($_POST['phone'] ?? '');
    $emergency_contact = sanitize_input($_POST['emergency_contact'] ?? '');
    $emergency_phone = sanitize_input($_POST['emergency_phone'] ?? '');

    $update_data = [
        'phone' => $phone,
        'emergency_contact' => $emergency_contact,
        'emergency_phone' => $emergency_phone
    ];

    if (db_update('users', $update_data, 'id = ?', [$user['id']])) {
        $success_message = 'Contact information updated successfully.';
        log_activity($user['id'], 'Profile Update', 'User updated contact information');
        // Refresh user data
        $user = get_logged_in_user();
    } else {
        $error_message = 'Failed to update contact information. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KurdLeave — Profile</title>
  <link rel="stylesheet" href="../usercss/user-styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="container">
    <!-- Header & Navigation -->
    <table class="main-header">
      <tr>
        <td colspan="6">
          <h1>KurdLeave System</h1>
        </td>
      </tr>
      <tr>
        <td><a href="home.php"><i class="fas fa-home"></i> Home</a></td>
        <td><a href="apply_leave.php"><i class="fas fa-plus-circle"></i> Apply Leave</a></td>
        <td><a href="my_leaves.php"><i class="fas fa-list-check"></i> My Leaves</a></td>
        <td><a href="calendar.php"><i class="fas fa-calendar"></i> Calendar</a></td>
        <td><b><a href="profile.php"><i class="fas fa-user"></i> Profile</a></b></td>
        <td><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></td>
      </tr>
    </table>

    <div class="content-panel">
      <div class="panel-heading text-center">
        <h2><i class="fas fa-user-circle"></i> My Profile</h2>
      </div>

      <?php if ($success_message): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
      <?php endif; ?>

      <?php if ($error_message): ?>
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
        </div>
      <?php endif; ?>

      <div style="display: flex; flex-wrap: wrap; gap: var(--spacing-lg);">
        <!-- Personal Information -->
        <div style="flex: 1; min-width: 300px;">
          <h3><i class="fas fa-id-card"></i> Personal Information</h3>
          <table class="bordered">
            <tr>
              <td><b>Name:</b></td>
              <td><?php echo htmlspecialchars($user['name']); ?></td>
            </tr>
            <tr>
              <td><b>Employee ID:</b></td>
              <td><?php echo htmlspecialchars($user['employee_id']); ?></td>
            </tr>
            <tr>
              <td><b>Email:</b></td>
              <td><?php echo htmlspecialchars($user['email']); ?></td>
            </tr>
            <tr>
              <td><b>Department:</b></td>
              <td><?php echo $department ? htmlspecialchars($department['name']) : 'Not assigned'; ?></td>
            </tr>
            <tr>
              <td><b>Role:</b></td>
              <td><?php echo ucfirst(htmlspecialchars($user['role'])); ?></td>
            </tr>
            <tr>
              <td><b>Manager:</b></td>
              <td><?php echo $manager ? htmlspecialchars($manager['name']) : 'Not assigned'; ?></td>
            </tr>
            <tr>
              <td><b>Join Date:</b></td>
              <td><?php echo $user['join_date'] ? format_date($user['join_date']) : 'Not set'; ?></td>
            </tr>
            <tr>
              <td><b>Last Login:</b></td>
              <td><?php echo $user['last_login'] ? format_datetime($user['last_login']) : 'Never'; ?></td>
            </tr>
          </table>

          <!-- Profile Photo Section (Optional) -->
          <div class="card mt-3 text-center">
            <div class="card-header">Profile Photo</div>
            <div style="padding: 1rem;">
              <div style="width: 150px; height: 150px; margin: 0 auto; background-color: var(--primary-color); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 3rem; font-weight: bold;">
                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
              </div>
              <div class="mt-2">
                <button class="btn" disabled><i class="fas fa-camera"></i> Upload New Photo</button>
                <small style="display: block; margin-top: 5px; color: #666;">Photo upload coming soon</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Update Password -->
        <div style="flex: 1; min-width: 300px;">
          <h2><i class="fas fa-lock"></i> Update Password</h2>
          <form action="" method="post" class="card">
            <div style="padding: var(--spacing-md);">
              <div class="mb-2">
                <label for="current-password">Current Password:</label>
                <input type="password" id="current-password" name="current_password" required>
              </div>
              <div class="mb-2">
                <label for="new-password">New Password:</label>
                <input type="password" id="new-password" name="new_password" required>
                <small>Password must be at least 8 characters with numbers and special characters</small>
              </div>
              <div class="mb-2">
                <label for="confirm-password">Confirm Password:</label>
                <input type="password" id="confirm-password" name="confirm_password" required>
              </div>
              <div class="text-center mt-2">
                <button type="submit" name="update_password" class="btn-success"><i class="fas fa-key"></i> Update Password</button>
              </div>
            </div>
          </form>

          <!-- Contact Information -->
          <h2 class="mt-3"><i class="fas fa-address-book"></i> Contact Information</h2>
          <form action="" method="post" class="card">
            <div style="padding: var(--spacing-md);">
              <div class="mb-2">
                <label for="phone">Phone Number:</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
              </div>
              <div class="mb-2">
                <label for="emergency-contact">Emergency Contact:</label>
                <input type="text" id="emergency-contact" name="emergency_contact" value="<?php echo htmlspecialchars($user['emergency_contact'] ?? ''); ?>">
              </div>
              <div class="mb-2">
                <label for="emergency-phone">Emergency Contact Phone:</label>
                <input type="tel" id="emergency-phone" name="emergency_phone" value="<?php echo htmlspecialchars($user['emergency_phone'] ?? ''); ?>">
              </div>
              <div class="text-center mt-2">
                <button type="submit" name="update_contact" class="btn-success"><i class="fas fa-save"></i> Update Contact Information</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Leave Balances -->
    <div class="content-panel">
      <h2><i class="fas fa-chart-pie"></i> Leave Balances</h2>

      <div class="stats-container">
        <?php foreach (array_slice($leave_types, 0, 3) as $leave_type): ?>
          <?php $balance = $leave_balances[$leave_type['id']]; ?>
          <div class="stat-card">
            <div class="stat-label"><?php echo htmlspecialchars($leave_type['name']); ?></div>
            <div class="stat-value">
              <?php echo $balance['remaining_days']; ?>/<?php echo $balance['total_allocation']; ?>
            </div>
            <small>
              <?php
              $percentage = $balance['total_allocation'] > 0 ? round(($balance['remaining_days'] / $balance['total_allocation']) * 100) : 0;
              echo $percentage; ?>% remaining
            </small>
          </div>
        <?php endforeach; ?>
      </div>

      <table class="data-table mt-3">
        <thead>
          <tr>
            <th>Leave Type</th>
            <th>Total Allocation</th>
            <th>Used</th>
            <th>Remaining</th>
            <th>Expires On</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($leave_types as $leave_type): ?>
            <?php $balance = $leave_balances[$leave_type['id']]; ?>
            <tr>
              <td><?php echo htmlspecialchars($leave_type['name']); ?></td>
              <td><?php echo $balance['total_allocation']; ?> days</td>
              <td><?php echo $balance['used_days']; ?> days</td>
              <td><?php echo $balance['remaining_days']; ?> days</td>
              <td>December 31, <?php echo $year; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="alert alert-info mt-3">
        <i class="fas fa-info-circle"></i> Your leave entitlement will be reset on January 1, <?php echo $year + 1; ?>.
        Any remaining Annual Leave days over 5 will be forfeited as per company policy.
      </div>
    </div>

    <!-- Footer -->
    <div class="footer">
      <p>KurdLeave System &copy; 2025</p>
    </div>
  </div>

  <!-- Back to Top Button -->
  <button class="back-to-top" id="backToTop">
    <i class="fas fa-arrow-up"></i>
  </button>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Back to Top button functionality
      const backToTopButton = document.getElementById('backToTop');

      // Show/hide button based on scroll position
      window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
          backToTopButton.classList.add('show');
        } else {
          backToTopButton.classList.remove('show');
        }
      });

      // Scroll to top when clicked
      backToTopButton.addEventListener('click', function() {
        window.scrollTo({
          top: 0,
          behavior: 'smooth'
        });
      });

      // Password confirmation validation
      const newPassword = document.getElementById('new-password');
      const confirmPassword = document.getElementById('confirm-password');

      function validatePasswords() {
        if (newPassword.value !== confirmPassword.value) {
          confirmPassword.setCustomValidity('Passwords do not match');
        } else {
          confirmPassword.setCustomValidity('');
        }
      }

      newPassword.addEventListener('input', validatePasswords);
      confirmPassword.addEventListener('input', validatePasswords);
    });
  </script>
</body>
</html>
