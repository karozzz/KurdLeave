<?php
// user/my_leaves.php - My Leave Requests

require_once '../php/functions.php';

// Require login
require_login();

// Get current user data
$user = get_logged_in_user();

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$year_filter = $_GET['year'] ?? date('Y');

// Build query conditions
$where_conditions = ["l.user_id = ?"];
$params = [$user['id']];

if ($status_filter !== 'all') {
    $where_conditions[] = "l.status = ?";
    $params[] = $status_filter;
}

if ($year_filter !== 'all') {
    $where_conditions[] = "YEAR(l.start_date) = ?";
    $params[] = $year_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Get user's leave requests
$leaves = db_fetch_all("
    SELECT l.*, lt.name as leave_type_name, u.name as approved_by_name
    FROM leaves l
    LEFT JOIN leave_types lt ON l.leave_type_id = lt.id
    LEFT JOIN users u ON l.approved_by = u.id
    WHERE {$where_clause}
    ORDER BY l.submitted_at DESC
", $params);

// Get statistics
$total_requests = db_fetch("SELECT COUNT(*) as count FROM leaves WHERE user_id = ?", [$user['id']])['count'];
$pending_requests = db_fetch("SELECT COUNT(*) as count FROM leaves WHERE user_id = ? AND status = 'pending'", [$user['id']])['count'];
$approved_requests = db_fetch("SELECT COUNT(*) as count FROM leaves WHERE user_id = ? AND status = 'approved'", [$user['id']])['count'];
$total_days_used = db_fetch("SELECT SUM(working_days) as total FROM leaves WHERE user_id = ? AND status = 'approved' AND YEAR(start_date) = ?", [$user['id'], date('Y')])['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KurdLeave — My Leaves</title>
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
        <td><b><a href="my_leaves.php"><i class="fas fa-list-check"></i> My Leaves</a></b></td>
        <td><a href="calendar.php"><i class="fas fa-calendar"></i> Calendar</a></td>
        <td><a href="profile.php"><i class="fas fa-user"></i> Profile</a></td>
        <td><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></td>
      </tr>
    </table>

    <!-- Page Header -->
    <div class="content-panel">
      <div class="panel-heading text-center">
        <h2><i class="fas fa-list-check"></i> My Leave Requests</h2>
        <p>View and track all your leave requests</p>
      </div>

      <!-- Statistics -->
      <div class="stats-container">
        <div class="stat-card">
          <div class="stat-value"><?php echo $total_requests; ?></div>
          <div class="stat-label">Total Requests</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?php echo $pending_requests; ?></div>
          <div class="stat-label">Pending Approval</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?php echo $approved_requests; ?></div>
          <div class="stat-label">Approved Requests</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?php echo number_format($total_days_used, 1); ?></div>
          <div class="stat-label">Days Used (<?php echo date('Y'); ?>)</div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="content-panel">
      <h3><i class="fas fa-filter"></i> Filter Requests</h3>

      <form method="GET" action="" class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
        <div>
          <label for="status">Status:</label>
          <select id="status" name="status">
            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
            <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
          </select>
        </div>

        <div>
          <label for="year">Year:</label>
          <select id="year" name="year">
            <option value="all" <?php echo $year_filter === 'all' ? 'selected' : ''; ?>>All Years</option>
            <?php for ($y = date('Y'); $y >= date('Y') - 2; $y--): ?>
              <option value="<?php echo $y; ?>" <?php echo $year_filter == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
            <?php endfor; ?>
          </select>
        </div>

        <div style="display: flex; align-items: end; gap: 10px;">
          <button type="submit" class="btn"><i class="fas fa-search"></i> Filter</button>
          <a href="my_leaves.php" class="btn btn-danger"><i class="fas fa-times"></i> Clear</a>
        </div>
      </form>
    </div>

    <!-- Leave Requests -->
    <div class="content-panel">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3><i class="fas fa-calendar-check"></i> Leave Requests
          <?php if ($status_filter !== 'all' || $year_filter !== 'all'): ?>
            <small>(Filtered)</small>
          <?php endif; ?>
        </h3>
        <a href="apply_leave.php" class="btn btn-success">
          <i class="fas fa-plus-circle"></i> Apply for New Leave
        </a>
      </div>

      <?php if (empty($leaves)): ?>
        <div class="alert alert-info">
          <i class="fas fa-info-circle"></i>
          <?php if ($status_filter !== 'all' || $year_filter !== 'all'): ?>
            No leave requests found matching your filter criteria.
          <?php else: ?>
            You haven't submitted any leave requests yet. <a href="apply_leave.php">Apply for your first leave</a>.
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Request ID</th>
                <th>Leave Type</th>
                <th>Dates</th>
                <th>Duration</th>
                <th>Status</th>
                <th>Submitted</th>
                <th>Approved By</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($leaves as $leave): ?>
                <tr>
                  <td>
                    <strong>#L<?php echo $leave['id']; ?></strong>
                  </td>
                  <td><?php echo htmlspecialchars($leave['leave_type_name']); ?></td>
                  <td>
                    <strong><?php echo format_date($leave['start_date'], 'M j'); ?></strong> -
                    <strong><?php echo format_date($leave['end_date'], 'M j, Y'); ?></strong>
                    <br>
                    <small class="text-muted">
                      <?php
                      $days_until = (strtotime($leave['start_date']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
                      if ($days_until > 0) {
                          echo "Starts in " . ceil($days_until) . " days";
                      } elseif ($days_until < 0 && strtotime($leave['end_date']) >= strtotime(date('Y-m-d'))) {
                          echo "Currently on leave";
                      } elseif ($days_until < 0) {
                          echo "Completed";
                      } else {
                          echo "Starts today";
                      }
                      ?>
                    </small>
                  </td>
                  <td class="text-center">
                    <strong><?php echo $leave['working_days']; ?></strong> working days<br>
                    <small><?php echo $leave['total_days']; ?> total days</small>
                  </td>
                  <td class="text-center">
                    <span class="status status-<?php echo $leave['status']; ?>">
                      <?php echo ucfirst($leave['status']); ?>
                    </span>
                  </td>
                  <td>
                    <?php echo format_datetime($leave['submitted_at'], 'M j, Y'); ?><br>
                    <small><?php echo format_datetime($leave['submitted_at'], 'H:i'); ?></small>
                  </td>
                  <td>
                    <?php if ($leave['approved_by_name']): ?>
                      <?php echo htmlspecialchars($leave['approved_by_name']); ?><br>
                      <small><?php echo $leave['approved_at'] ? format_datetime($leave['approved_at'], 'M j, Y') : ''; ?></small>
                    <?php else: ?>
                      <span class="text-muted">Pending</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <button type="button" class="btn btn-sm" onclick="viewLeaveDetails(<?php echo $leave['id']; ?>)">
                      <i class="fas fa-eye"></i> View
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="content-panel">
      <h3><i class="fas fa-bolt"></i> Quick Actions</h3>

      <div class="card-container" style="display: flex; flex-wrap: wrap; gap: 1rem;">
        <div class="card" style="flex: 1; min-width: 250px; text-align: center;">
          <div class="card-header"><i class="fas fa-plus-circle"></i> Apply for Leave</div>
          <p>Submit a new leave request</p>
          <a href="apply_leave.php" class="btn"><i class="fas fa-plus-circle"></i> Apply Now</a>
        </div>

        <div class="card" style="flex: 1; min-width: 250px; text-align: center;">
          <div class="card-header"><i class="fas fa-chart-pie"></i> Leave Balance</div>
          <p>Check your current leave balances</p>
          <a href="profile.php" class="btn"><i class="fas fa-chart-pie"></i> View Balances</a>
        </div>

        <div class="card" style="flex: 1; min-width: 250px; text-align: center;">
          <div class="card-header"><i class="fas fa-calendar"></i> Team Calendar</div>
          <p>View team leave schedule</p>
          <a href="calendar.php" class="btn"><i class="fas fa-calendar"></i> View Calendar</a>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="footer">
      <p>KurdLeave System &copy; 2025</p>
    </div>
  </div>

  <!-- Leave Details Modal -->
  <div id="leaveModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; max-width: 600px; width: 90%;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 id="modalTitle">Leave Request Details</h3>
        <button onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
      </div>
      <div id="modalContent">
        <!-- Content will be loaded here -->
      </div>
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

    // Modal functions
    function viewLeaveDetails(leaveId) {
      // Find the leave data from the table
      const rows = document.querySelectorAll('table tbody tr');
      let leaveData = null;

      rows.forEach(row => {
        const idCell = row.cells[0].textContent;
        if (idCell.includes('#L' + leaveId)) {
          leaveData = {
            id: leaveId,
            type: row.cells[1].textContent,
            dates: row.cells[2].textContent.split('\n')[0],
            duration: row.cells[3].textContent,
            status: row.cells[4].textContent.trim(),
            submitted: row.cells[5].textContent,
            approvedBy: row.cells[6].textContent
          };
        }
      });

      if (leaveData) {
        document.getElementById('modalTitle').textContent = 'Leave Request #L' + leaveId;
        document.getElementById('modalContent').innerHTML = `
          <table style="width: 100%; border-collapse: collapse;">
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Leave Type:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">${leaveData.type}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Dates:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">${leaveData.dates}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Duration:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">${leaveData.duration}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Status:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">${leaveData.status}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Submitted:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">${leaveData.submitted}</td></tr>
            <tr><td style="padding: 8px;"><strong>Approved By:</strong></td><td style="padding: 8px;">${leaveData.approvedBy}</td></tr>
          </table>
          <div style="margin-top: 1rem; text-align: center;">
            <button onclick="closeModal()" class="btn">Close</button>
          </div>
        `;
        document.getElementById('leaveModal').style.display = 'block';
      }
    }

    function closeModal() {
      document.getElementById('leaveModal').style.display = 'none';
    }

    // Close modal when clicking outside
    document.getElementById('leaveModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeModal();
      }
    });
  </script>
</body>
</html>
