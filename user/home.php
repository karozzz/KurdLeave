<?php
// user/home.php - User Dashboard

require_once '../php/functions.php';

// Require login
require_login();

// Get current user data
$user = get_logged_in_user();
$department = get_department_by_id($user['department_id']);

// Get user's leave balances for current year
$year = date('Y');
$leave_types = get_all_leave_types();
$leave_balances = [];
foreach ($leave_types as $leave_type) {
    $balance = get_user_leave_balance($user['id'], $leave_type['id'], $year);
    if ($balance) {
        $leave_balances[$leave_type['id']] = $balance;
    }
}

// Get recent leave history
$recent_leaves = get_user_leave_history($user['id'], 5);

// Get pending leave requests count
$pending_count = db_fetch("SELECT COUNT(*) as count FROM leaves WHERE user_id = ? AND status = 'pending'", [$user['id']])['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KurdLeave — Dashboard</title>
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
        <td><b><a href="home.php"><i class="fas fa-home"></i> Home</a></b></td>
        <td><a href="apply_leave.php"><i class="fas fa-plus-circle"></i> Apply Leave</a></td>
        <td><a href="my_leaves.php"><i class="fas fa-list-check"></i> My Leaves</a></td>
        <td><a href="calendar.php"><i class="fas fa-calendar"></i> Calendar</a></td>
        <td><a href="profile.php"><i class="fas fa-user"></i> Profile</a></td>
        <td><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></td>
      </tr>
    </table>

    <!-- Welcome Panel -->
    <div class="content-panel">
      <div class="panel-heading text-center">
        <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>
        <p>Today is <b><?php echo date('F j, Y'); ?></b> (<?php echo date('l'); ?>)</p>
        <?php if ($department): ?>
          <p>Department: <b><?php echo htmlspecialchars($department['name']); ?></b> |
             Employee ID: <b><?php echo htmlspecialchars($user['employee_id']); ?></b></p>
        <?php endif; ?>
      </div>

      <!-- Quick Stats -->
      <div class="stats-container">
        <div class="stat-card">
          <div class="stat-value"><?php echo $pending_count; ?></div>
          <div class="stat-label">Pending Requests</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">
            <?php
            $annual_balance = $leave_balances[1] ?? null;
            echo $annual_balance ? $annual_balance['remaining_days'] : '0';
            ?>
          </div>
          <div class="stat-label">Annual Leave Days</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">
            <?php
            $sick_balance = $leave_balances[2] ?? null;
            echo $sick_balance ? $sick_balance['remaining_days'] : '0';
            ?>
          </div>
          <div class="stat-label">Sick Leave Days</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">
            <?php
            $total_used = 0;
            foreach ($leave_balances as $balance) {
                $total_used += $balance['used_days'];
            }
            echo $total_used;
            ?>
          </div>
          <div class="stat-label">Total Used (YTD)</div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="content-panel">
      <h2><i class="fas fa-bolt"></i> Quick Actions</h2>

      <div class="card-container" style="display: flex; flex-wrap: wrap; gap: 1rem;">
        <div class="card" style="flex: 1; min-width: 250px; text-align: center;">
          <div class="card-header"><i class="fas fa-plus-circle"></i> Apply for Leave</div>
          <p>Submit a new leave request</p>
          <a href="apply_leave.php" class="btn"><i class="fas fa-plus-circle"></i> Apply Now</a>
        </div>

        <div class="card" style="flex: 1; min-width: 250px; text-align: center;">
          <div class="card-header"><i class="fas fa-list-check"></i> My Leave Status</div>
          <p>View your leave requests and status</p>
          <a href="my_leaves.php" class="btn"><i class="fas fa-eye"></i> View Leaves</a>
        </div>

        <div class="card" style="flex: 1; min-width: 250px; text-align: center;">
          <div class="card-header"><i class="fas fa-calendar"></i> Team Calendar</div>
          <p>View team leave schedule</p>
          <a href="calendar.php" class="btn"><i class="fas fa-calendar"></i> View Calendar</a>
        </div>
      </div>
    </div>

    <!-- Leave Balance Overview -->
    <div class="content-panel">
      <h2><i class="fas fa-chart-pie"></i> Leave Balance Overview</h2>

      <table class="data-table">
        <thead>
          <tr>
            <th>Leave Type</th>
            <th>Total Allocation</th>
            <th>Used</th>
            <th>Remaining</th>
            <th>Percentage Used</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($leave_types as $leave_type): ?>
            <?php
            $balance = $leave_balances[$leave_type['id']] ?? null;
            $total = $balance ? $balance['total_allocation'] : $leave_type['default_allocation'];
            $used = $balance ? $balance['used_days'] : 0;
            $remaining = $balance ? $balance['remaining_days'] : $total;
            $percentage = $total > 0 ? round(($used / $total) * 100, 1) : 0;
            ?>
            <tr>
              <td><?php echo htmlspecialchars($leave_type['name']); ?></td>
              <td class="text-center"><?php echo $total; ?> days</td>
              <td class="text-center"><?php echo $used; ?> days</td>
              <td class="text-center"><?php echo $remaining; ?> days</td>
              <td class="text-center">
                <div style="display: flex; align-items: center; gap: 10px;">
                  <div style="flex: 1; height: 10px; background-color: #f1f1f1; border-radius: 5px; overflow: hidden;">
                    <div style="height: 100%; width: <?php echo $percentage; ?>%; background-color: <?php echo $percentage > 80 ? '#e74c3c' : ($percentage > 60 ? '#f39c12' : '#27ae60'); ?>; border-radius: 5px;"></div>
                  </div>
                  <span><?php echo $percentage; ?>%</span>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Recent Leave History -->
    <div class="content-panel">
      <h2><i class="fas fa-history"></i> Recent Leave History</h2>

      <?php if (empty($recent_leaves)): ?>
        <div class="alert alert-info">
          <i class="fas fa-info-circle"></i> No leave history found. <a href="apply_leave.php">Apply for your first leave</a>.
        </div>
      <?php else: ?>
        <table class="data-table">
          <thead>
            <tr>
              <th>Leave Type</th>
              <th>Dates</th>
              <th>Days</th>
              <th>Status</th>
              <th>Submitted</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent_leaves as $leave): ?>
              <tr>
                <td><?php echo htmlspecialchars($leave['leave_type_name']); ?></td>
                <td>
                  <?php echo format_date($leave['start_date'], 'M j'); ?> -
                  <?php echo format_date($leave['end_date'], 'M j, Y'); ?>
                </td>
                <td class="text-center"><?php echo $leave['working_days']; ?></td>
                <td class="text-center">
                  <span class="status status-<?php echo $leave['status']; ?>">
                    <?php echo ucfirst($leave['status']); ?>
                  </span>
                </td>
                <td><?php echo format_datetime($leave['submitted_at']); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="text-center mt-3">
          <a href="my_leaves.php" class="btn"><i class="fas fa-list"></i> View All Leaves</a>
        </div>
      <?php endif; ?>
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
    });
  </script>
</body>
</html>
