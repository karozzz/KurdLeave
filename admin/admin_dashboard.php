<?php
/*
 * ADMIN DASHBOARD - The Command Center! 🚀
 * ========================================
 *
 * Welcome to the admin's home base! This page is like the cockpit of an airplane -
 * it shows all the important information at a glance and lets you take quick actions.
 *
 * Think of this as your company's "mission control" where you can:
 * - 👀 See what's happening right now (pending requests, recent activity)
 * - 📊 Check system health (how many users, requests, etc.)
 * - ⚡ Take quick actions (approve/reject leave requests)
 * - 🔍 Spot issues that need attention
 *
 * WHAT YOU'LL SEE HERE:
 * - Statistics cards showing key numbers
 * - Pending leave requests waiting for your decision
 * - Recent system activity (who did what when)
 * - Quick links to other admin functions
 *
 * It's designed to give you the "big picture" in just a few seconds! 🖼️
 */

// admin/admin_dashboard.php - Main Admin Dashboard

require_once '../php/functions.php';

// SECURITY CHECKPOINT: Only admin users can access this command center
require_admin(); // Make sure only admins can access this page

// MESSAGE CONTAINERS: For showing success/error messages to the user
$success_message = '';
$error_message = '';

// HANDLE LEAVE APPROVAL/REJECTION FROM THE DASHBOARD
// This is like having a "quick action" button - admins can approve/reject right from here
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // COLLECT THE ACTION DETAILS: What are they trying to do?
    $leave_id = (int)($_POST['leave_id'] ?? 0);              // Which leave request?
    $action = $_POST['action'];                               // "approve" or "reject"?
    $comments = sanitize_input($_POST['admin_comments'] ?? ''); // Any admin comments?

    // VALIDATE THE INPUT: Make sure we have everything we need
    if ($leave_id && in_array($action, ['approve', 'reject'])) {
        // DETERMINE THE NEW STATUS: Convert action to database status
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        $admin_id = $_SESSION['user_id']; // Who is making this decision?

        // PREPARE THE DATABASE UPDATE: What needs to change in the database
        $update_data = [
            'status' => $status,
            'admin_comments' => $comments,
            'approved_by' => $admin_id,
            'approved_at' => date('Y-m-d H:i:s') // When did this happen?
        ];

        // Try to update the leave request in the database
        if (db_update('leaves', $update_data, 'id = :id', [':id' => $leave_id])) {
            // If we approved the leave, we need to update their vacation balance
            if ($status === 'approved') {
                $leave = db_fetch("SELECT * FROM leaves WHERE id = ?", [$leave_id]);
                if ($leave) {
                    $year = date('Y', strtotime($leave['start_date']));
                    $balance = get_user_leave_balance($leave['user_id'], $leave['leave_type_id'], $year);
                    if ($balance) {
                        // Subtract the vacation days from their remaining balance
                        $new_used = $balance['used_days'] + $leave['working_days'];
                        $new_remaining = $balance['remaining_days'] - $leave['working_days'];
                        db_update('leave_balances', [
                            'used_days' => $new_used,
                            'remaining_days' => max(0, $new_remaining) // Don't go below 0
                        ], 'user_id = :user_id AND leave_type_id = :leave_type_id AND year = :year',
                        [':user_id' => $leave['user_id'], ':leave_type_id' => $leave['leave_type_id'], ':year' => $year]);
                    }
                }
            }

            // Show success message and log what happened
            $success_message = "Leave request #L{$leave_id} has been {$status}.";
            log_activity($admin_id, 'Leave ' . ucfirst($action), "Leave request #L{$leave_id} {$status}");
        } else {
            $error_message = "Failed to update leave request.";
        }
    }
}

// GET DATA FOR THE DASHBOARD
// Collect all the information we need to show on the dashboard
$stats = get_dashboard_stats();        // Numbers like total users, pending requests, etc.
$recent_activity = get_recent_activity(10); // Last 10 things that happened in the system
$pending_leaves = get_pending_leaves(); // Leave requests waiting for approval
?>
<!--
This is the HTML part of the admin dashboard
It shows a nice web page with:
- Navigation menu at the top
- Statistics cards showing important numbers
- Quick action buttons for common tasks
- Recent activity log
- Pending leave requests that need attention
- Modal popups for approving/rejecting leaves
-->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KurdLeave — Admin Dashboard</title>
  <!-- Load our custom admin styles -->
  <link rel="stylesheet" href="../admincss/admin-styles.css">
  <!-- Load Font Awesome for nice icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="container">
    <!-- Main navigation menu - this appears on all admin pages -->
    <table class="main-header">
      <tr>
        <td colspan="7">
          <h1>KurdLeave System - ADMIN PANEL</h1>
        </td>
      </tr>
      <tr>
        <!-- Current page is highlighted with <b> tags -->
        <td><b><a href="admin_dashboard.php"><i class="fas fa-home"></i> Admin Home</a></b></td>
        <td><a href="admin_leaves.php"><i class="fas fa-calendar-alt"></i> Leave Management</a></td>
        <td><a href="admin_users.php"><i class="fas fa-users"></i> User Management</a></td>
        <td><a href="admin_reports.php"><i class="fas fa-chart-bar"></i> Reports</a></td>
        <td><a href="admin_settings.php"><i class="fas fa-cog"></i> System Settings</a></td>
        <td><a href="admin_logs.php"><i class="fas fa-history"></i> Activity Logs</a></td>
        <td><a href="../user/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></td>
      </tr>
    </table>
    <div class="content-panel">
      <div class="panel-heading text-center">
        <h2>Welcome, Administrator!</h2>
        <p>Today is <b><?php echo date('F j, Y'); ?></b> (<?php echo date('l'); ?>)</p>
      </div>

      <?php if ($success_message): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
        </div>
      <?php endif; ?>
      <?php if ($error_message): ?>
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
        </div>
      <?php endif; ?>

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
                <button onclick="viewLeaveDetails(<?php echo htmlspecialchars(json_encode($leave)); ?>)" class="btn btn-sm">
                  <i class="fas fa-eye"></i> Review
                </button>
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
    <div class="footer">
      <p>KurdLeave System &copy; 2025</p>
    </div>
  </div>

  <!-- Leave Details Modal -->
  <div id="leaveModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; max-width: 800px; width: 90%; max-height: 90%; overflow-y: auto;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 id="modalTitle">Leave Request Details</h3>
        <button onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
      </div>
      <div id="modalContent">
      </div>
    </div>
  </div>

  <!-- Action Modal -->
  <div id="actionModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1001;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%;">
      <h3 id="actionTitle">Confirm Action</h3>
      <form method="POST" action="admin_dashboard.php">
        <input type="hidden" id="actionLeaveId" name="leave_id">
        <input type="hidden" id="actionType" name="action">
        <div style="margin: 1rem 0;">
          <label for="admin_comments">Comments (optional):</label>
          <textarea name="admin_comments" id="admin_comments" rows="3" style="width: 100%; margin-top: 5px;" placeholder="Add any comments about your decision..."></textarea>
        </div>
        <div style="text-align: center; margin-top: 1rem;">
          <button type="submit" id="confirmActionBtn" class="btn">Confirm</button>
          <button type="button" onclick="closeActionModal()" class="btn btn-danger">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <button class="back-to-top" id="backToTop">
    <i class="fas fa-arrow-up"></i>
  </button>
  <!--
  JavaScript section - this handles interactive features like:
  - Showing modal popups when viewing leave details
  - Handling approve/reject buttons
  - Smooth scrolling back to top
  - Making forms submit properly
  -->
  <script>
    // When the page loads, set up some interactive features
    document.addEventListener('DOMContentLoaded', function() {
      const backToTopButton = document.getElementById('backToTop');

      // Show/hide the "back to top" button based on scroll position
      window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
          backToTopButton.classList.add('show');
        } else {
          backToTopButton.classList.remove('show');
        }
      });

      // When they click "back to top", smoothly scroll to the top
      backToTopButton.addEventListener('click', function() {
        window.scrollTo({
          top: 0,
          behavior: 'smooth'
        });
      });
    });

    // Show detailed information about a leave request in a popup modal
    function viewLeaveDetails(leave) {
      document.getElementById('modalTitle').textContent = 'Leave Request #L' + leave.id;

      // Build the HTML content for the modal with all the leave details
      document.getElementById('modalContent').innerHTML = `
        <div class="modal-section">
          <h4>Employee Information</h4>
          <p><strong>Name:</strong> ${leave.user_name}</p>
          <p><strong>Employee ID:</strong> ${leave.employee_id}</p>
          <p><strong>Department:</strong> ${leave.department_name || 'Not assigned'}</p>
        </div>
        <div class="modal-section">
          <h4>Leave Details</h4>
          <p><strong>Leave Type:</strong> ${leave.leave_type_name}</p>
          <p><strong>Start Date:</strong> ${leave.start_date}</p>
          <p><strong>End Date:</strong> ${leave.end_date}</p>
          <p><strong>Working Days:</strong> ${leave.working_days}</p>
          <p><strong>Status:</strong> <span class="status-badge status-${leave.status}">${leave.status.charAt(0).toUpperCase() + leave.status.slice(1)}</span></p>
        </div>
        <div class="modal-section">
          <h4>Request Information</h4>
          <p><strong>Submitted:</strong> ${leave.submitted_at}</p>
          <p><strong>Reason:</strong> ${leave.reason || 'No reason provided'}</p>
        </div>
        ${leave.status !== 'pending' ? `
        <div class="modal-section">
          <h4>Admin Decision</h4>
          <p><strong>Approved By:</strong> ${leave.approved_by_name || 'System'}</p>
          <p><strong>Decision Date:</strong> ${leave.approved_at || 'N/A'}</p>
          <p><strong>Comments:</strong> ${leave.admin_comments || 'No comments'}</p>
        </div>
        ` : ''}
        <div class="modal-actions" style="margin-top: 1rem; text-align: center;">
          ${leave.status === 'pending' ? `
            <button onclick="approveLeave(${leave.id})" class="btn btn-success">
              <i class="fas fa-check"></i> Approve
            </button>
            <button onclick="rejectLeave(${leave.id})" class="btn btn-danger">
              <i class="fas fa-times"></i> Reject
            </button>
          ` : ''}
          <button onclick="closeModal()" class="btn btn-secondary">Close</button>
        </div>
      `;
      document.getElementById('leaveModal').style.display = 'block';
    }

    function closeModal() {
      document.getElementById('leaveModal').style.display = 'none';
    }

    function approveLeave(leaveId) {
      document.getElementById('actionTitle').textContent = 'Approve Leave Request #L' + leaveId;
      document.getElementById('actionLeaveId').value = leaveId;
      document.getElementById('actionType').value = 'approve';
      document.getElementById('confirmActionBtn').textContent = 'Approve';
      document.getElementById('confirmActionBtn').className = 'btn btn-success';
      document.getElementById('admin_comments').placeholder = 'Add any comments about the approval...';
      document.getElementById('actionModal').style.display = 'block';
      closeModal();
    }

    function rejectLeave(leaveId) {
      document.getElementById('actionTitle').textContent = 'Reject Leave Request #L' + leaveId;
      document.getElementById('actionLeaveId').value = leaveId;
      document.getElementById('actionType').value = 'reject';
      document.getElementById('confirmActionBtn').textContent = 'Reject';
      document.getElementById('confirmActionBtn').className = 'btn btn-danger';
      document.getElementById('admin_comments').placeholder = 'Please provide a reason for rejection...';
      document.getElementById('actionModal').style.display = 'block';
      closeModal();
    }

    function closeActionModal() {
      document.getElementById('actionModal').style.display = 'none';
      document.getElementById('admin_comments').value = '';
    }

    // Close modals when clicking outside
    document.getElementById('leaveModal').addEventListener('click', function(e) {
      if (e.target === this) closeModal();
    });

    document.getElementById('actionModal').addEventListener('click', function(e) {
      if (e.target === this) closeActionModal();
    });
  </script>
</body>
</html>
