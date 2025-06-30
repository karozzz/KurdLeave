<?php
require_once '../php/functions.php';
require_admin();
$success_message = '';
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $leave_id = (int)($_POST['leave_id'] ?? 0);
    $action = $_POST['action'];
    $comments = sanitize_input($_POST['admin_comments'] ?? '');
    if ($leave_id && in_array($action, ['approve', 'reject'])) {
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        $admin_id = $_SESSION['user_id'];
        $update_data = [
            'status' => $status,
            'admin_comments' => $comments,
            'approved_by' => $admin_id,
            'approved_at' => date('Y-m-d H:i:s')
        ];
        if (db_update('leaves', $update_data, 'id = :id', [':id' => $leave_id])) {
            if ($status === 'approved') {
                $leave = db_fetch("SELECT * FROM leaves WHERE id = ?", [$leave_id]);
                if ($leave) {
                    $year = date('Y', strtotime($leave['start_date']));
                    $balance = get_user_leave_balance($leave['user_id'], $leave['leave_type_id'], $year);
                    if ($balance) {
                        $new_used = $balance['used_days'] + $leave['working_days'];
                        $new_remaining = $balance['remaining_days'] - $leave['working_days'];
                        db_update('leave_balances', [
                            'used_days' => $new_used,
                            'remaining_days' => max(0, $new_remaining)
                        ], 'user_id = :user_id AND leave_type_id = :leave_type_id AND year = :year',
                        [':user_id' => $leave['user_id'], ':leave_type_id' => $leave['leave_type_id'], ':year' => $year]);
                    }
                }
            }
            $success_message = "Leave request #L{$leave_id} has been {$status}.";
            log_activity($admin_id, 'Leave ' . ucfirst($action), "Leave request #L{$leave_id} {$status}");
        } else {
            $error_message = "Failed to update leave request.";
        }
    }
}
$status_filter = $_GET['status'] ?? 'pending';
$department_filter = $_GET['department'] ?? 'all';
$where_conditions = ["1=1"];
$params = [];
if ($status_filter !== 'all') {
    $where_conditions[] = "l.status = ?";
    $params[] = $status_filter;
}
if ($department_filter !== 'all') {
    $where_conditions[] = "d.id = ?";
    $params[] = $department_filter;
}
$where_clause = implode(' AND ', $where_conditions);
$leaves = db_fetch_all("
    SELECT l.*, u.name as user_name, u.employee_id, lt.name as leave_type_name,
           d.name as department_name, mgr.name as manager_name,
           approver.name as approved_by_name
    FROM leaves l
    JOIN users u ON l.user_id = u.id
    JOIN leave_types lt ON l.leave_type_id = lt.id
    LEFT JOIN departments d ON u.department_id = d.id
    LEFT JOIN users mgr ON u.manager_id = mgr.id
    LEFT JOIN users approver ON l.approved_by = approver.id
    WHERE {$where_clause}
    ORDER BY
        CASE WHEN l.status = 'pending' THEN 0 ELSE 1 END,
        l.submitted_at DESC
", $params);
$departments = get_all_departments();
$stats = [
    'pending' => db_fetch("SELECT COUNT(*) as count FROM leaves WHERE status = 'pending'")['count'],
    'approved' => db_fetch("SELECT COUNT(*) as count FROM leaves WHERE status = 'approved'")['count'],
    'rejected' => db_fetch("SELECT COUNT(*) as count FROM leaves WHERE status = 'rejected'")['count'],
    'total' => db_fetch("SELECT COUNT(*) as count FROM leaves")['count']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KurdLeave — Leave Management</title>
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
        <td><b><a href="admin_leaves.php"><i class="fas fa-calendar-alt"></i> Leave Management</a></b></td>
        <td><a href="admin_users.php"><i class="fas fa-users"></i> User Management</a></td>
        <td><a href="admin_reports.php"><i class="fas fa-chart-bar"></i> Reports</a></td>
        <td><a href="admin_settings.php"><i class="fas fa-cog"></i> System Settings</a></td>
        <td><a href="admin_logs.php"><i class="fas fa-history"></i> Activity Logs</a></td>
        <td><a href="../user/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></td>
      </tr>
    </table>
    <div class="content-panel">
      <div class="panel-heading text-center">
        <h2><i class="fas fa-calendar-alt"></i> Leave Management</h2>
        <p>Review and manage employee leave requests</p>
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
          <div class="stat-value"><?php echo $stats['pending']; ?></div>
          <div class="stat-label">Pending Requests</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?php echo $stats['approved']; ?></div>
          <div class="stat-label">Approved</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?php echo $stats['rejected']; ?></div>
          <div class="stat-label">Rejected</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?php echo $stats['total']; ?></div>
          <div class="stat-label">Total Requests</div>
        </div>
      </div>
    </div>
    <div class="content-panel">
      <h3><i class="fas fa-filter"></i> Filter Leave Requests</h3>
      <form method="GET" action="" class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
        <div>
          <label for="status">Status:</label>
          <select name="status" id="status">
            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
            <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
          </select>
        </div>
        <div>
          <label for="department">Department:</label>
          <select name="department" id="department">
            <option value="all">All Departments</option>
            <?php foreach ($departments as $dept): ?>
              <option value="<?php echo $dept['id']; ?>" <?php echo $department_filter == $dept['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($dept['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="display: flex; align-items: end; gap: 10px;">
          <button type="submit" class="btn"><i class="fas fa-search"></i> Filter</button>
          <a href="admin_leaves.php" class="btn btn-secondary"><i class="fas fa-times"></i> Clear</a>
        </div>
      </form>
    </div>
    <div class="content-panel">
      <h3><i class="fas fa-list"></i> Leave Requests
        <?php if ($status_filter !== 'all' || $department_filter !== 'all'): ?>
          <small>(Filtered)</small>
        <?php endif; ?>
      </h3>
      <?php if (empty($leaves)): ?>
        <div class="alert alert-info">
          <i class="fas fa-info-circle"></i> No leave requests found matching your criteria.
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Request ID</th>
                <th>Employee</th>
                <th>Leave Type</th>
                <th>Dates</th>
                <th>Days</th>
                <th>Status</th>
                <th>Submitted</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($leaves as $leave): ?>
                <tr>
                  <td>
                    <b>#L<?php echo $leave['id']; ?></b>
                  </td>
                  <td>
                    <b><?php echo htmlspecialchars($leave['user_name']); ?></b><br>
                    <small><?php echo htmlspecialchars($leave['employee_id']); ?></small>
                    <?php if ($leave['department_name']): ?>
                      <br><small class="text-muted"><?php echo htmlspecialchars($leave['department_name']); ?></small>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($leave['leave_type_name']); ?></td>
                  <td>
                    <?php echo format_date($leave['start_date'], 'M j'); ?> -
                    <?php echo format_date($leave['end_date'], 'M j, Y'); ?>
                  </td>
                  <td class="text-center"><?php echo $leave['working_days']; ?></td>
                  <td>
                    <?php
                    $status_class = '';
                    switch ($leave['status']) {
                        case 'pending': $status_class = 'status-pending'; break;
                        case 'approved': $status_class = 'status-approved'; break;
                        case 'rejected': $status_class = 'status-rejected'; break;
                    }
                    ?>
                    <span class="status-badge <?php echo $status_class; ?>">
                      <?php echo ucfirst($leave['status']); ?>
                    </span>
                  </td>
                  <td><?php echo format_datetime($leave['submitted_at']); ?></td>
                  <td class="text-center">
                    <button onclick="viewLeaveDetails(<?php echo htmlspecialchars(json_encode($leave)); ?>)" class="btn btn-sm">
                      <i class="fas fa-eye"></i> View
                    </button>
                    <?php if ($leave['status'] === 'pending'): ?>
                      <button onclick="approveLeave(<?php echo $leave['id']; ?>)" class="btn btn-sm btn-success">
                        <i class="fas fa-check"></i> Approve
                      </button>
                      <button onclick="rejectLeave(<?php echo $leave['id']; ?>)" class="btn btn-sm btn-danger">
                        <i class="fas fa-times"></i> Reject
                      </button>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
    <div class="footer">
      <p>KurdLeave System &copy; 2025</p>
    </div>
  </div>
  <div id="leaveModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; max-width: 800px; width: 90%; max-height: 90%; overflow-y: auto;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 id="modalTitle">Leave Request Details</h3>
      </div>
      <div id="modalContent">
      </div>
    </div>
  </div>
  <div id="actionModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1001;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%;">
      <h3 id="actionTitle">Confirm Action</h3>
      <form method="POST" action="admin_leaves.php">
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
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const backToTopButton = document.getElementById('backToTop');
      window.addEventListener('scroll', function() {
      });
      backToTopButton.addEventListener('click', function() {…});
      });
    });
    function viewLeaveDetails(leave) {
      document.getElementById('modalTitle').textContent = 'Leave Request #L' + leave.id;
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
        <div class="modal-actions">
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
    document.getElementById('leaveModal').addEventListener('click', function(e) {
      if (e.target === this) closeModal();
    });
    document.getElementById('actionModal').addEventListener('click', function(e) {
      if (e.target === this) closeActionModal();
    });
  </script>
</body>
</html>
