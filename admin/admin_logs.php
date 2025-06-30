<?php
/*
 * ADMIN ACTIVITY LOGS PAGE - The System's Security Camera! 📹
 * ============================================================
 *
 * Hey there! This page is like having security cameras for your leave management system.
 * It keeps track of everything that everyone does, so you can:
 *
 * - 🕵️ See who did what and when (like a detective reviewing evidence)
 * - 🔍 Search for specific activities or users
 * - 📊 Monitor system usage and security
 * - 🚨 Spot any unusual or suspicious behavior
 * - 📝 Keep records for compliance and auditing
 *
 * WHAT THIS PAGE DOES (The Big Picture):
 * Think of this as the "Recent Activity" feed on social media, but for your business system.
 * Every time someone logs in, creates a user, approves a leave, or changes settings,
 * it gets recorded here with a timestamp and details.
 *
 * TYPES OF ACTIVITIES WE TRACK:
 * - 🔐 Login/Logout events
 * - 👥 User management (create, update, delete users)
 * - 📅 Leave management (create, approve, reject requests)
 * - ⚙️ Settings changes
 * - 📊 Report generation
 * - 💾 Database backups
 *
 * It's like having a detailed diary of everything that happens in your system! 📚
 */

// admin/admin_logs.php - Admin Activity Logs

require_once '../php/functions.php';

// SECURITY CHECK: Only admin users can view activity logs
// (Regular employees shouldn't see what everyone else is doing!)
require_admin();

// COLLECT FILTER PARAMETERS: What specific activities does the admin want to see?
$action_filter = $_GET['action'] ?? 'all';           // What type of action (login, create user, etc.)
$user_filter = $_GET['user'] ?? 'all';               // Activities by specific user
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));  // Start date (default: last 7 days)
$date_to = $_GET['date_to'] ?? date('Y-m-d');        // End date (default: today)

// BUILD DATABASE QUERY CONDITIONS: Start with basic rules, then add filters
$where_conditions = ["1=1"];  // Always true - a starting point for adding more conditions
$params = [];                 // Values for the database query

// APPLY ACTION FILTER: If they want to see specific types of activities
if ($action_filter !== 'all') {
    $where_conditions[] = "al.action = ?";  // Add action filter to the query
    $params[] = $action_filter;             // Remember which action they want
}

// APPLY USER FILTER: If they want to see activities by a specific person
if ($user_filter !== 'all') {
    $where_conditions[] = "al.user_id = ?";  // Add user filter to the query
    $params[] = $user_filter;                // Remember which user they want
}

// APPLY DATE RANGE FILTER: Only show activities within the chosen time period
$where_conditions[] = "DATE(al.created_at) BETWEEN ? AND ?";
$params[] = $date_from;  // From this date...
$params[] = $date_to;    // ...to this date

// COMBINE ALL FILTERS: Join them with 'AND' so all conditions must be true
$where_clause = implode(' AND ', $where_conditions);

// GET THE ACTIVITY LOGS: Retrieve all matching activities from the database
// This is like asking: "Show me all activities that match my filters, with user details"
$logs = db_fetch_all("
    SELECT al.*, u.name as user_name, u.employee_id     -- Get log details and user info
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id              -- Connect logs to user information
    WHERE {$where_clause}                                -- Apply all our filters
    ORDER BY al.created_at DESC                          -- Show newest activities first
    LIMIT 1000                                           -- Don't overwhelm with too many results
", $params);

// GET AVAILABLE ACTIONS: For the filter dropdown - what types of activities exist?
$actions = db_fetch_all("
    SELECT DISTINCT action                               -- Get unique action types
    FROM activity_logs
    WHERE action IS NOT NULL                             -- Skip any empty actions
    ORDER BY action                                      -- Sort alphabetically
");

// GET ACTIVE USERS: For the user filter dropdown - who has been doing activities?
$users = db_fetch_all("
    SELECT DISTINCT u.id, u.name, u.employee_id         -- Get unique users who have activity
    FROM activity_logs al
    JOIN users u ON al.user_id = u.id                   -- Connect logs to users
    ORDER BY u.name                                      -- Sort by name alphabetically
");

// CALCULATE ACTIVITY STATISTICS: Get some quick numbers for the dashboard
$stats = [
    // TODAY'S ACTIVITY: How busy has the system been today?
    'total_today' => db_fetch("SELECT COUNT(*) as count FROM activity_logs WHERE DATE(created_at) = CURDATE()")['count'],

    // WEEKLY ACTIVITY: How busy has it been this week?
    'total_week' => db_fetch("SELECT COUNT(*) as count FROM activity_logs WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")['count'],

    // UNIQUE USERS TODAY: How many different people used the system today?
    'unique_users_today' => db_fetch("SELECT COUNT(DISTINCT user_id) as count FROM activity_logs WHERE DATE(created_at) = CURDATE()")['count'],

    // SECURITY ALERTS: Any failed login attempts today? (Potential security issues)
    'failed_logins_today' => db_fetch("SELECT COUNT(*) as count FROM activity_logs WHERE action LIKE '%Failed%' AND DATE(created_at) = CURDATE()")['count']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KurdLeave — Activity Logs</title>
  <link rel="stylesheet" href="../admincss/admin-styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="container">
    <table class="main-header">
      <tr>
        <td colspan="7">
          <h1>KurdLeave System - ADMIN PANEL</h1>
        </td>
      </tr>
      <tr>
        <td><a href="admin_dashboard.php"><i class="fas fa-home"></i> Admin Home</a></td>
        <td><a href="admin_leaves.php"><i class="fas fa-calendar-alt"></i> Leave Management</a></td>
        <td><a href="admin_users.php"><i class="fas fa-users"></i> User Management</a></td>
        <td><a href="admin_reports.php"><i class="fas fa-chart-bar"></i> Reports</a></td>
        <td><a href="admin_settings.php"><i class="fas fa-cog"></i> System Settings</a></td>
        <td><b><a href="admin_logs.php"><i class="fas fa-history"></i> Activity Logs</a></b></td>
        <td><a href="../user/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></td>
      </tr>
    </table>

    <!-- Page Header -->
    <div class="content-panel">
      <div class="panel-heading">
        <h2><i class="fas fa-history"></i> System Activity Logs</h2>
        <p>Monitor and audit all system activities</p>
      </div>

      <div class="stats-container">
        <div class="stat-card">
          <div class="stat-value"><?php echo $stats['total_today']; ?></div>
          <div class="stat-label">Activities Today</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?php echo $stats['total_week']; ?></div>
          <div class="stat-label">Activities This Week</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?php echo $stats['unique_users_today']; ?></div>
          <div class="stat-label">Active Users Today</div>
        </div>
        <div class="stat-card">
          <div class="stat-value" style="color: <?php echo $stats['failed_logins_today'] > 0 ? 'var(--danger-color)' : 'var(--success-color)'; ?>">
            <?php echo $stats['failed_logins_today']; ?>
          </div>
          <div class="stat-label">Failed Logins Today</div>
        </div>
      </div>
    </div>

    <div class="content-panel">
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-filter"></i> Filter Activity Logs</h3>
        </div>
        <form method="GET" action="" class="form-grid mt-2">
          <div>
            <label>Date Range:</label>
            <div style="display: flex; gap: 10px; align-items: center;">
              <input type="date" name="date_from" value="<?php echo $date_from; ?>" style="flex: 1;">
              <span>to</span>
              <input type="date" name="date_to" value="<?php echo $date_to; ?>" style="flex: 1;">
            </div>
          </div>

          <div>
            <label for="action">Action Type:</label>
            <select id="action" name="action">
              <option value="all" <?php echo $action_filter === 'all' ? 'selected' : ''; ?>>All Activities</option>
              <?php foreach ($actions as $action): ?>
                <option value="<?php echo htmlspecialchars($action['action']); ?>" <?php echo $action_filter === $action['action'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($action['action']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label for="user">User:</label>
            <select id="user" name="user">
              <option value="all" <?php echo $user_filter === 'all' ? 'selected' : ''; ?>>All Users</option>
              <?php foreach ($users as $user): ?>
                <option value="<?php echo $user['id']; ?>" <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($user['name'] . ' (' . $user['employee_id'] . ')'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label>&nbsp;</label>
            <button type="submit" class="btn" style="margin-top: 10px;"><i class="fas fa-search"></i> Apply Filters</button>
            <a href="admin_logs.php" class="btn btn-danger" style="margin-top: 10px;"><i class="fas fa-times"></i> Clear</a>
          </div>
        </form>
      </div>
    </div>

    <div class="content-panel">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3><i class="fas fa-list"></i> Activity Log (<?php echo count($logs); ?> records)</h3>
        <div>
          <button onclick="exportLogs()" class="btn"><i class="fas fa-file-csv"></i> Export CSV</button>
          <button onclick="window.print()" class="btn"><i class="fas fa-print"></i> Print</button>
        </div>
      </div>

      <?php if (empty($logs)): ?>
        <div class="alert alert-info">
          <i class="fas fa-info-circle"></i> No activity logs found for the selected criteria.
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table" id="logsTable">
            <thead>
              <tr>
                <th>Timestamp</th>
                <th>User</th>
                <th>Action</th>
                <th>Description</th>
                <th>IP Address</th>
                <th>User Agent</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($logs as $log): ?>
                <tr>
                  <td>
                    <strong><?php echo format_datetime($log['created_at'], 'M j, Y'); ?></strong><br>
                    <small><?php echo format_datetime($log['created_at'], 'H:i:s'); ?></small>
                  </td>
                  <td>
                    <?php if ($log['user_name']): ?>
                      <strong><?php echo htmlspecialchars($log['user_name']); ?></strong><br>
                      <small><?php echo htmlspecialchars($log['employee_id']); ?></small>
                    <?php else: ?>
                      <span class="text-muted">System</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span style="
                      padding: 3px 8px;
                      border-radius: 12px;
                      font-size: 0.85rem;
                      background-color: <?php
                        echo strpos($log['action'], 'Login') !== false ? 'var(--success-color-light)' :
                             (strpos($log['action'], 'Failed') !== false ? 'var(--danger-color-light)' :
                              (strpos($log['action'], 'Logout') !== false ? 'var(--warning-color-light)' : 'var(--info-color-light)'));
                      ?>;
                      color: white;
                    ">
                      <?php echo htmlspecialchars($log['action']); ?>
                    </span>
                  </td>
                  <td>
                    <?php echo htmlspecialchars($log['description']); ?>
                  </td>
                  <td>
                    <code><?php echo htmlspecialchars($log['ip_address'] ?? 'Unknown'); ?></code>
                  </td>
                  <td>
                    <small title="<?php echo htmlspecialchars($log['user_agent'] ?? 'Unknown'); ?>">
                      <?php
                      $user_agent = $log['user_agent'] ?? 'Unknown';
                      if (strlen($user_agent) > 50) {
                          echo htmlspecialchars(substr($user_agent, 0, 50)) . '...';
                      } else {
                          echo htmlspecialchars($user_agent);
                      }
                      ?>
                    </small>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if (count($logs) >= 1000): ?>
          <div class="alert alert-warning mt-3">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Note:</strong> Only the most recent 1000 records are displayed. Use filters to narrow down results.
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <!-- Security Alerts -->
    <div class="content-panel">
      <h3><i class="fas fa-shield-alt"></i> Security Alerts</h3>

      <?php
      // Get recent security-related activities
      $security_logs = db_fetch_all("
          SELECT al.*, u.name as user_name, u.employee_id
          FROM activity_logs al
          LEFT JOIN users u ON al.user_id = u.id
          WHERE al.action LIKE '%Failed%' OR al.action LIKE '%Security%' OR al.action LIKE '%Block%'
          ORDER BY al.created_at DESC
          LIMIT 10
      ");
      ?>

      <?php if (empty($security_logs)): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle"></i> No security alerts in the recent activity.
        </div>
      <?php else: ?>
        <table class="data-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Severity</th>
              <th>Alert Type</th>
              <th>Description</th>
              <th>IP Address</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($security_logs as $security_log): ?>
              <tr>
                <td><?php echo format_datetime($security_log['created_at'], 'M j, Y H:i'); ?></td>
                <td>
                  <span class="status status-<?php echo strpos($security_log['action'], 'Failed') !== false ? 'rejected' : 'pending'; ?>">
                    <?php echo strpos($security_log['action'], 'Failed') !== false ? 'Medium' : 'Low'; ?>
                  </span>
                </td>
                <td><?php echo htmlspecialchars($security_log['action']); ?></td>
                <td><?php echo htmlspecialchars($security_log['description']); ?></td>
                <td><code><?php echo htmlspecialchars($security_log['ip_address'] ?? 'Unknown'); ?></code></td>
                <td>
                  <span class="status status-approved">Logged</span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
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

      window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
          backToTopButton.classList.add('show');
        } else {
          backToTopButton.classList.remove('show');
        }
      });

      backToTopButton.addEventListener('click', function() {
        window.scrollTo({
          top: 0,
          behavior: 'smooth'
        });
      });
    });

    function exportLogs() {
      // Create CSV content
      const table = document.getElementById('logsTable');
      let csv = [];

      // Get headers
      const headers = [];
      table.querySelectorAll('thead th').forEach(th => {
        headers.push('"' + th.textContent.trim() + '"');
      });
      csv.push(headers.join(','));

      // Get rows
      table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => {
          // Clean up the cell content
          let cellText = td.textContent.trim().replace(/\s+/g, ' ');
          row.push('"' + cellText.replace(/"/g, '""') + '"');
        });
        csv.push(row.join(','));
      });

      // Download CSV
      const csvContent = csv.join('\n');
      const blob = new Blob([csvContent], { type: 'text/csv' });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'activity_logs_' + new Date().toISOString().split('T')[0] + '.csv';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
    }
  </script>
</body>
</html>
