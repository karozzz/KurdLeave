<?php
// admin/admin_leaves.php - Leave Management Page
// This is where administrators can view, filter, and manage all leave requests
// Think of it as the central command center for vacation and time-off management

require_once '../php/functions.php';
require_admin(); // Make sure only admins can access this page

// Variables to hold messages we'll show to the user
$success_message = '';
$error_message = '';

// HANDLE LEAVE APPROVAL/REJECTION ACTIONS
// If an admin clicked "approve" or "reject" on a leave request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $leave_id = (int)($_POST['leave_id'] ?? 0);  // Which leave request?
    $action = $_POST['action'];                   // "approve" or "reject"?
    $comments = sanitize_input($_POST['admin_comments'] ?? ''); // Admin's comments

    // Make sure we have valid data before proceeding
    if ($leave_id && in_array($action, ['approve', 'reject'])) {
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        $admin_id = $_SESSION['user_id']; // Who is making this decision?

        // Prepare the data we'll update in the database
        $update_data = [
            'status' => $status,
            'admin_comments' => $comments,
            'approved_by' => $admin_id,
            'approved_at' => date('Y-m-d H:i:s') // Timestamp of when this decision was made
        ];

        // Try to update the leave request in the database
        if (db_update('leaves', $update_data, 'id = :id', [':id' => $leave_id])) {
            // If we approved the leave, we need to deduct days from their vacation balance
            if ($status === 'approved') {
                $leave = db_fetch("SELECT * FROM leaves WHERE id = ?", [$leave_id]);
                if ($leave) {
                    $year = date('Y', strtotime($leave['start_date']));
                    $balance = get_user_leave_balance($leave['user_id'], $leave['leave_type_id'], $year);
                    if ($balance) {
                        // Calculate new balances (they used some vacation days)
                        $new_used = $balance['used_days'] + $leave['working_days'];
                        $new_remaining = $balance['remaining_days'] - $leave['working_days'];
                        // Update their balance (but never let it go below 0)
                        db_update('leave_balances', [
                            'used_days' => $new_used,
                            'remaining_days' => max(0, $new_remaining)
                        ], 'user_id = :user_id AND leave_type_id = :leave_type_id AND year = :year',
                        [':user_id' => $leave['user_id'], ':leave_type_id' => $leave['leave_type_id'], ':year' => $year]);
                    }
                }
            }

            // Show success message and log what happened for the audit trail
            $success_message = "Leave request #L{$leave_id} has been {$status}.";
            log_activity($admin_id, 'Leave ' . ucfirst($action), "Leave request #L{$leave_id} {$status}");
        } else {
            $error_message = "Failed to update leave request.";
        }
    }
}

// GET FILTER PARAMETERS FROM THE URL
// These let admins filter the leave requests they want to see
$status_filter = $_GET['status'] ?? 'pending';      // Show pending by default
$department_filter = $_GET['department'] ?? 'all';   // Show all departments by default

// BUILD THE DATABASE QUERY CONDITIONS
// This is like building a search query - we start with everything, then narrow it down
$where_conditions = ["1=1"]; // This always evaluates to true, so we can add more conditions
$params = [];

// If they want to filter by status (pending, approved, rejected)
if ($status_filter !== 'all') {
    $where_conditions[] = "l.status = ?";
    $params[] = $status_filter;
}

// If they want to filter by department
if ($department_filter !== 'all') {
    $where_conditions[] = "d.id = ?";
    $params[] = $department_filter;
}

// Combine all our conditions with AND
$where_clause = implode(' AND ', $where_conditions);

// GET ALL LEAVE REQUESTS FROM THE DATABASE
// This big query joins multiple tables to get all the info we need in one go
$leaves = db_fetch_all("
    SELECT l.*, u.name as user_name, u.employee_id, lt.name as leave_type_name,
           d.name as department_name, mgr.name as manager_name,
           approver.name as approved_by_name
    FROM leaves l
    JOIN users u ON l.user_id = u.id                    -- Get employee info
    JOIN leave_types lt ON l.leave_type_id = lt.id      -- Get leave type info
    LEFT JOIN departments d ON u.department_id = d.id   -- Get department info (if any)
    LEFT JOIN users mgr ON u.manager_id = mgr.id        -- Get manager info (if any)
    LEFT JOIN users approver ON l.approved_by = approver.id -- Get who approved it (if anyone)
    WHERE {$where_clause}
    ORDER BY
        CASE WHEN l.status = 'pending' THEN 0 ELSE 1 END,  -- Show pending requests first
        l.submitted_at DESC                                  -- Then newest first
", $params);

// GET SUPPORTING DATA FOR THE PAGE
$departments = get_all_departments(); // For the department filter dropdown

// Calculate statistics to show on the dashboard
$stats = [
    'pending' => db_fetch("SELECT COUNT(*) as count FROM leaves WHERE status = 'pending'")['count'],
    'approved' => db_fetch("SELECT COUNT(*) as count FROM leaves WHERE status = 'approved'")['count'],
    'rejected' => db_fetch("SELECT COUNT(*) as count FROM leaves WHERE status = 'rejected'")['count'],
    'total' => db_fetch("SELECT COUNT(*) as count FROM leaves")['count']
];
?>
<!--
HTML SECTION - This builds the web page that admins see
The page has several sections:
1. Navigation menu at the top
2. Statistics cards showing request counts
3. Filter form to narrow down results
4. Data table showing all leave requests
5. Modal popups for detailed views and actions
-->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KurdLeave — Leave Management</title>
  <!-- Load our custom admin styles and icons -->
  <link rel="stylesheet" href="../admincss/admin-styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="container">
    <!-- Main navigation menu - same on all admin pages -->
    <table class="main-header">
      <tr>
        <td colspan="7">
          <h1>KurdLeave System - ADMIN PANEL</h1>
        </td>
      </tr>
      <tr>
        <!-- Current page is highlighted with <b> tags -->
        <td><a href="admin_dashboard.php"><i class="fas fa-home"></i> Admin Home</a></td>
        <td><b><a href="admin_leaves.php"><i class="fas fa-calendar-alt"></i> Leave Management</a></b></td>
        <td><a href="admin_users.php"><i class="fas fa-users"></i> User Management</a></td>
        <td><a href="admin_reports.php"><i class="fas fa-chart-bar"></i> Reports</a></td>
        <td><a href="admin_settings.php"><i class="fas fa-cog"></i> System Settings</a></td>
        <td><a href="admin_logs.php"><i class="fas fa-history"></i> Activity Logs</a></td>
        <td><a href="../user/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></td>
      </tr>
    </table>

    <!-- Page header with title and statistics -->
    <div class="content-panel">
      <div class="panel-heading text-center">
        <h2><i class="fas fa-calendar-alt"></i> Leave Management</h2>
        <p>Review and manage employee leave requests</p>
      </div>

      <!-- Show success/error messages if any -->
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

      <!-- Statistics cards showing counts of different request types -->
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

    <!-- LEAVE REQUESTS TABLE SECTION -->
    <!-- This is where we show all the leave requests in a nice organized table -->
    <div class="content-panel">
      <h3><i class="fas fa-list"></i> Leave Requests
        <?php if ($status_filter !== 'all' || $department_filter !== 'all'): ?>
          <small>(Filtered)</small>  <!-- Show this if we're filtering results -->
        <?php endif; ?>
      </h3>

      <?php if (empty($leaves)): ?>
        <!-- If no results found, show a friendly message -->
        <div class="alert alert-info">
          <i class="fas fa-info-circle"></i> No leave requests found matching your criteria.
        </div>
      <?php else: ?>
        <!-- THE DATA TABLE - This is like a spreadsheet showing all requests -->
        <div class="table-responsive">
          <table class="data-table">
            <!-- Table headers - like column titles in a spreadsheet -->
            <thead>
              <tr>
                <th>Request ID</th>     <!-- Unique number for each request -->
                <th>Employee</th>       <!-- Who made the request -->
                <th>Leave Type</th>     <!-- Vacation, sick, etc. -->
                <th>Dates</th>          <!-- When they want off -->
                <th>Days</th>           <!-- How many work days -->
                <th>Status</th>         <!-- Pending, approved, rejected -->
                <th>Submitted</th>      <!-- When they asked -->
                <th>Actions</th>        <!-- Buttons to do stuff -->
              </tr>
            </thead>
            <!-- Table body - the actual data rows -->
            <tbody>
              <?php foreach ($leaves as $leave): ?>
                <!-- Each leave request gets its own row -->
                <tr>
                  <!-- Request ID with # prefix (like #L123) -->
                  <td>
                    <b>#L<?php echo $leave['id']; ?></b>
                  </td>
                  <!-- Employee info: name, ID, department -->
                  <td>
                    <b><?php echo htmlspecialchars($leave['user_name']); ?></b><br>
                    <small><?php echo htmlspecialchars($leave['employee_id']); ?></small>
                    <?php if ($leave['department_name']): ?>
                      <br><small class="text-muted"><?php echo htmlspecialchars($leave['department_name']); ?></small>
                    <?php endif; ?>
                  </td>
                  <!-- What type of leave (vacation, sick, etc.) -->
                  <td><?php echo htmlspecialchars($leave['leave_type_name']); ?></td>
                  <!-- Date range in nice format (Jan 15 - Jan 20, 2025) -->
                  <td>
                    <?php echo format_date($leave['start_date'], 'M j'); ?> -
                    <?php echo format_date($leave['end_date'], 'M j, Y'); ?>
                  </td>
                  <!-- Number of working days (excludes weekends) -->
                  <td class="text-center"><?php echo $leave['working_days']; ?></td>
                  <!-- Status with color coding -->
                  <td>
                    <?php
                    // Choose the right CSS class for status color
                    $status_class = '';
                    switch ($leave['status']) {
                        case 'pending': $status_class = 'status-pending'; break;    // Yellow/orange
                        case 'approved': $status_class = 'status-approved'; break;  // Green
                        case 'rejected': $status_class = 'status-rejected'; break;  // Red
                    }
                    ?>
                    <span class="status-badge <?php echo $status_class; ?>">
                      <?php echo ucfirst($leave['status']); ?>  <!-- Capitalize first letter -->
                    </span>
                  </td>
                  <!-- When the request was submitted -->
                  <td><?php echo format_datetime($leave['submitted_at']); ?></td>
                  <!-- Action buttons - View, Approve, Reject -->
                  <td class="text-center">
                    <!-- Always show View button -->
                    <button onclick="viewLeaveDetails(<?php echo htmlspecialchars(json_encode($leave)); ?>)" class="btn btn-sm">
                      <i class="fas fa-eye"></i> View
                    </button>
                    <!-- Only show Approve/Reject buttons for pending requests -->
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

    <!-- PAGE FOOTER -->
    <div class="footer">
      <p>KurdLeave System &copy; 2025</p>
    </div>
  </div>

  <!-- MODAL POPUPS SECTION -->
  <!-- These are like popup windows that appear on top of the page -->

  <!-- LEAVE DETAILS MODAL - Shows detailed info about a leave request -->
  <div id="leaveModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000;">
    <!-- The dark overlay that covers the whole screen -->
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; max-width: 800px; width: 90%; max-height: 90%; overflow-y: auto;">
      <!-- The actual popup window in the center -->
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 id="modalTitle">Leave Request Details</h3>  <!-- Title changes based on request -->
      </div>
      <div id="modalContent">
        <!-- Content gets filled by JavaScript when you click "View" -->
      </div>
    </div>
  </div>

  <!-- ACTION CONFIRMATION MODAL - For approve/reject decisions -->
  <div id="actionModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1001;">
    <!-- Higher z-index so it appears on top of the details modal -->
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%;">
      <h3 id="actionTitle">Confirm Action</h3>  <!-- Title changes to "Approve" or "Reject" -->
      <!-- Form that submits the admin's decision -->
      <form method="POST" action="admin_leaves.php">
        <input type="hidden" id="actionLeaveId" name="leave_id">      <!-- Which request -->
        <input type="hidden" id="actionType" name="action">           <!-- approve or reject -->
        <div style="margin: 1rem 0;">
          <label for="admin_comments">Comments (optional):</label>
          <!-- Text area for admin to explain their decision -->
          <textarea name="admin_comments" id="admin_comments" rows="3" style="width: 100%; margin-top: 5px;" placeholder="Add any comments about your decision..."></textarea>
        </div>
        <div style="text-align: center; margin-top: 1rem;">
          <button type="submit" id="confirmActionBtn" class="btn">Confirm</button>  <!-- Submit decision -->
          <button type="button" onclick="closeActionModal()" class="btn btn-danger">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- BACK TO TOP BUTTON - Appears when you scroll down -->
  <button class="back-to-top" id="backToTop">
    <i class="fas fa-arrow-up"></i>
  </button>

  <!-- JAVASCRIPT SECTION -->
  <!-- This is the code that makes the page interactive (buttons, popups, etc.) -->
  <script>
    // SETUP CODE - Runs when the page is fully loaded
    document.addEventListener('DOMContentLoaded', function() {
      // Setup the "back to top" button
      const backToTopButton = document.getElementById('backToTop');

      // Show/hide the back-to-top button based on scroll position
      window.addEventListener('scroll', function() {
        // Show button when user scrolls down more than 300 pixels
        if (window.pageYOffset > 300) {
          backToTopButton.style.display = 'block';
        } else {
          backToTopButton.style.display = 'none';
        }
      });

      // When back-to-top is clicked, smoothly scroll to the top
      backToTopButton.addEventListener('click', function() {
        window.scrollTo({
          top: 0,
          behavior: 'smooth'  // Smooth scrolling animation
        });
      });
    });

    // SHOW LEAVE DETAILS FUNCTION
    // This function runs when you click the "View" button on any leave request
    // It creates a detailed popup with all the information about that request
    function viewLeaveDetails(leave) {
      // Set the popup title to show the request number
      document.getElementById('modalTitle').textContent = 'Leave Request #L' + leave.id;

      // Build the detailed content HTML - like filling out a form with all the info
      document.getElementById('modalContent').innerHTML = \`
        <!-- Employee section - who made the request -->
        <div class="modal-section">
          <h4>Employee Information</h4>
          <p><strong>Name:</strong> \${leave.user_name}</p>
          <p><strong>Employee ID:</strong> \${leave.employee_id}</p>
          <p><strong>Department:</strong> \${leave.department_name || 'Not assigned'}</p>
        </div>

        <!-- Leave details section - what they're asking for -->
        <div class="modal-section">
          <h4>Leave Details</h4>
          <p><strong>Leave Type:</strong> \${leave.leave_type_name}</p>
          <p><strong>Start Date:</strong> \${leave.start_date}</p>
          <p><strong>End Date:</strong> \${leave.end_date}</p>
          <p><strong>Working Days:</strong> \${leave.working_days}</p>
          <p><strong>Status:</strong> <span class="status-badge status-\${leave.status}">\${leave.status.charAt(0).toUpperCase() + leave.status.slice(1)}</span></p>
        </div>

        <!-- Request info section - when and why -->
        <div class="modal-section">
          <h4>Request Information</h4>
          <p><strong>Submitted:</strong> \${leave.submitted_at}</p>
          <p><strong>Reason:</strong> \${leave.reason || 'No reason provided'}</p>
        </div>

        <!-- Admin decision section - only show if already decided -->
        \${leave.status !== 'pending' ? \`
        <div class="modal-section">
          <h4>Admin Decision</h4>
          <p><strong>Approved By:</strong> \${leave.approved_by_name || 'System'}</p>
          <p><strong>Decision Date:</strong> \${leave.approved_at || 'N/A'}</p>
          <p><strong>Comments:</strong> \${leave.admin_comments || 'No comments'}</p>
        </div>
        \` : ''}

        <!-- Action buttons at the bottom -->
        <div class="modal-actions">
          <!-- Show approve/reject buttons only for pending requests -->
          \${leave.status === 'pending' ? \`
            <button onclick="approveLeave(\${leave.id})" class="btn btn-success">
              <i class="fas fa-check"></i> Approve
            </button>
            <button onclick="rejectLeave(\${leave.id})" class="btn btn-danger">
              <i class="fas fa-times"></i> Reject
            </button>
          \` : ''}
          <button onclick="closeModal()" class="btn btn-secondary">Close</button>
        </div>
      \`;

      // Show the popup modal
      document.getElementById('leaveModal').style.display = 'block';
    }

    // CLOSE DETAILS MODAL FUNCTION
    // Hides the leave details popup
    function closeModal() {
      document.getElementById('leaveModal').style.display = 'none';
    }

    // APPROVE LEAVE FUNCTION
    // This runs when you click the "Approve" button
    // It shows a confirmation popup before actually approving
    function approveLeave(leaveId) {
      // Setup the confirmation popup for approval
      document.getElementById('actionTitle').textContent = 'Approve Leave Request #L' + leaveId;
      document.getElementById('actionLeaveId').value = leaveId;           // Which request
      document.getElementById('actionType').value = 'approve';           // What action
      document.getElementById('confirmActionBtn').textContent = 'Approve';
      document.getElementById('confirmActionBtn').className = 'btn btn-success';  // Green button
      document.getElementById('admin_comments').placeholder = 'Add any comments about the approval...';

      // Show the confirmation popup and hide the details popup
      document.getElementById('actionModal').style.display = 'block';
      closeModal();  // Close the details modal
    }

    // REJECT LEAVE FUNCTION
    // This runs when you click the "Reject" button
    // It shows a confirmation popup before actually rejecting
    function rejectLeave(leaveId) {
      // Setup the confirmation popup for rejection
      document.getElementById('actionTitle').textContent = 'Reject Leave Request #L' + leaveId;
      document.getElementById('actionLeaveId').value = leaveId;           // Which request
      document.getElementById('actionType').value = 'reject';            // What action
      document.getElementById('confirmActionBtn').textContent = 'Reject';
      document.getElementById('confirmActionBtn').className = 'btn btn-danger';   // Red button
      document.getElementById('admin_comments').placeholder = 'Please provide a reason for rejection...';

      // Show the confirmation popup and hide the details popup
      document.getElementById('actionModal').style.display = 'block';
      closeModal();  // Close the details modal
    }

    // CLOSE CONFIRMATION MODAL FUNCTION
    // Hides the approve/reject confirmation popup and clears the comment box
    function closeActionModal() {
      document.getElementById('actionModal').style.display = 'none';
      document.getElementById('admin_comments').value = '';  // Clear any typed comments
    }

    // CLICK OUTSIDE TO CLOSE MODALS
    // If user clicks on the dark overlay (not the popup itself), close the modal

    // For the details modal
    document.getElementById('leaveModal').addEventListener('click', function(e) {
      if (e.target === this) closeModal();  // Only close if clicked on overlay, not popup content
    });

    // For the confirmation modal
    document.getElementById('actionModal').addEventListener('click', function(e) {
      if (e.target === this) closeActionModal();  // Only close if clicked on overlay, not popup content
    });
  </script>
</body>
</html>
