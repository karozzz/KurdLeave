<?php
// admin/admin_dashboard.php - Admin Dashboard

require_once '../php/functions.php';

// Require admin access
require_admin();

// Get dashboard statistics
$stats = get_dashboard_stats();

// Get recent activity
$recent_activity = get_recent_activity(10);

// Get pending leave requests
$pending_leaves = get_pending_leaves();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KurdLeave — Admin Dashboard</title>
  <link rel="stylesheet" href="../admincss/admin-styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="container">
    <!-- Main Header -->
    <table class="main-header">
      <tr>
        <td colspan="7">
          <h1>KurdLeave System - ADMIN PANEL</h1>
        </td>
      </tr>
      <tr>
        <td><b><a href="admin_dashboard.php"><i class="fas fa-home"></i> Admin Home</a></b></td>
        <td><a href="admin_leaves.php"><i class="fas fa-calendar-alt"></i> Leave Management</a></td>
        <td><a href="admin_users.php"><i class="fas fa-users"></i> User Management</a></td>
        <td><a href="admin_reports.php"><i class="fas fa-chart-bar"></i> Reports</a></td>
        <td><a href="admin_settings.php"><i class="fas fa-cog"></i> System Settings</a></td>
        <td><a href="admin_logs.php"><i class="fas fa-history"></i> Activity Logs</a></td>
        <td><a href="../user/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></td>
      </tr>
    </table>

    <!-- Welcome Panel -->
    <div class="content-panel">
      <div class="panel-heading text-center">
        <h2>Welcome, Administrator!</h2>
        <p>Today is <b><?php echo date('F j, Y'); ?></b> (<?php echo date('l'); ?>)</p>
      </div>

      <!-- Dashboard Stats -->
      <div class="stats-container">
        <div class="stat-card">
          <div class="stat-value"><?php echo $stats['active_users']; ?></div>
          <div class="stat-label">Active Users</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?php echo $stats['pending_leaves']; ?></div>
          <div class="stat-label">Pending Leave Requests</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?php echo $stats['users_on_leave_today']; ?></div>
          <div class="stat-label">Users on Leave Today</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?php echo number_format($stats['total_leave_days'], 1); ?></div>
          <div class="stat-label">Total Leave Days Used (YTD)</div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="content-panel">
      <h3>Administrative Quick Actions</h3>

      <div class="card-container" style="display: flex; flex-wrap: wrap; gap: 1rem;">
        <div class="card" style="flex: 1; min-width: 250px;">
          <div class="card-header"><i class="fas fa-clipboard-check"></i> Pending Approvals</div>
          <p>You have <b><?php echo $stats['pending_leaves']; ?></b> leave requests awaiting approval</p>
          <a href="admin_leaves.php" class="btn"><i class="fas fa-eye"></i> Review Requests</a>
        </div>

        <div class="card" style="flex: 1; min-width: 250px;">
          <div class="card-header"><i class="fas fa-user-plus"></i> User Management</div>
          <p>Add new users or modify existing user accounts</p>
          <a href="admin_users.php" class="btn"><i class="fas fa-plus-circle"></i> Manage Users</a>
        </div>

        <div class="card" style="flex: 1; min-width: 250px;">
          <div class="card-header"><i class="fas fa-chart-bar"></i> Reports</div>
          <p>Generate and view system reports</p>
          <a href="admin_reports.php" class="btn"><i class="fas fa-chart-line"></i> View Reports</a>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="content-panel">
      <h3><i class="fas fa-history"></i> Recent Activity</h3>

      <?php if (empty($recent_activity)): ?>
        <div class="alert alert-info">
          <i class="fas fa-info-circle"></i> No recent activity to display.
        </div>
      <?php else: ?>
        <table class="data-table">
          <thead>
            <tr>
              <th>Time</th>
              <th>User</th>
              <th>Action</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent_activity as $activity): ?>
              <tr>
                <td><?php echo format_datetime($activity['created_at'], 'H:i'); ?></td>
                <td><?php echo htmlspecialchars($activity['user_name'] ?? 'System'); ?></td>
                <td><?php echo htmlspecialchars($activity['action']); ?></td>
                <td><?php echo htmlspecialchars($activity['description']); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>

      <div class="text-right">
        <a href="admin_logs.php" class="btn"><i class="fas fa-list"></i> View All Activity</a>
      </div>
    </div>

    <!-- Pending Leave Requests Preview -->
    <?php if (!empty($pending_leaves)): ?>
    <div class="content-panel">
      <h3><i class="fas fa-clock"></i> Pending Leave Requests</h3>

      <table class="data-table">
        <thead>
          <tr>
            <th>Employee</th>
            <th>Leave Type</th>
            <th>Dates</th>
            <th>Days</th>
            <th>Submitted</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (array_slice($pending_leaves, 0, 5) as $leave): ?>
            <tr>
              <td>
                <b><?php echo htmlspecialchars($leave['user_name']); ?></b><br>
                <small><?php echo htmlspecialchars($leave['employee_id']); ?> - <?php echo htmlspecialchars($leave['department_name']); ?></small>
              </td>
              <td><?php echo htmlspecialchars($leave['leave_type_name']); ?></td>
              <td>
                <?php echo format_date($leave['start_date'], 'M j'); ?> -
                <?php echo format_date($leave['end_date'], 'M j, Y'); ?>
              </td>
              <td class="text-center"><?php echo $leave['working_days']; ?></td>
              <td><?php echo format_datetime($leave['submitted_at']); ?></td>
              <td class="text-center">
                <a href="admin_leave_review.php?id=<?php echo $leave['id']; ?>" class="btn btn-sm">
                  <i class="fas fa-eye"></i> Review
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <?php if (count($pending_leaves) > 5): ?>
        <div class="text-center mt-3">
          <a href="admin_leaves.php" class="btn">
            <i class="fas fa-list"></i> View All <?php echo count($pending_leaves); ?> Pending Requests
          </a>
        </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

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
    });
  </script>
</body>
</html>
