<?php
// admin/admin_reports.php - Admin Reports

require_once '../php/functions.php';

require_admin();

$report_type = $_GET['report_type'] ?? '';
$department = $_GET['department'] ?? 'all';
$leave_type = $_GET['leave_type'] ?? 'all';
$date_from = $_GET['date_from'] ?? date('Y-01-01');
$date_to = $_GET['date_to'] ?? date('Y-12-31');
$format = $_GET['format'] ?? 'html';

$report_data = [];
$report_title = '';

if (!empty($report_type)) {
    switch ($report_type) {
        case 'leave-usage':
            $report_title = 'Leave Usage Summary';

            $where_conditions = ["l.status = 'approved'"];
            $params = [];

            if ($department !== 'all') {
                $where_conditions[] = "d.id = ?";
                $params[] = $department;
            }

            if ($leave_type !== 'all') {
                $where_conditions[] = "l.leave_type_id = ?";
                $params[] = $leave_type;
            }

            $where_conditions[] = "l.start_date BETWEEN ? AND ?";
            $params[] = $date_from;
            $params[] = $date_to;

            $where_clause = implode(' AND ', $where_conditions);

            $report_data = db_fetch_all("
                SELECT
                    d.name as department_name,
                    u.name as user_name,
                    u.employee_id,
                    lt.name as leave_type_name,
                    COUNT(l.id) as total_requests,
                    SUM(l.working_days) as total_days,
                    AVG(l.working_days) as avg_days
                FROM leaves l
                JOIN users u ON l.user_id = u.id
                JOIN leave_types lt ON l.leave_type_id = lt.id
                LEFT JOIN departments d ON u.department_id = d.id
                WHERE {$where_clause}
                GROUP BY u.id, lt.id
                ORDER BY d.name, u.name, lt.name
            ", $params);
            break;

        case 'dept-absence':
            $report_title = 'Department Absence Analysis';

            $where_conditions = ["l.status = 'approved'"];
            $params = [];

            if ($department !== 'all') {
                $where_conditions[] = "d.id = ?";
                $params[] = $department;
            }

            $where_conditions[] = "l.start_date BETWEEN ? AND ?";
            $params[] = $date_from;
            $params[] = $date_to;

            $where_clause = implode(' AND ', $where_conditions);

            $report_data = db_fetch_all("
                SELECT
                    d.name as department_name,
                    COUNT(DISTINCT u.id) as total_employees,
                    COUNT(l.id) as total_requests,
                    SUM(l.working_days) as total_days,
                    ROUND(AVG(l.working_days), 2) as avg_days_per_request,
                    ROUND(SUM(l.working_days) / COUNT(DISTINCT u.id), 2) as avg_days_per_employee
                FROM departments d
                LEFT JOIN users u ON d.id = u.department_id AND u.status = 'active'
                LEFT JOIN leaves l ON u.id = l.user_id AND {$where_clause}
                GROUP BY d.id
                ORDER BY total_days DESC
            ", $params);
            break;

        case 'pending-leaves':
            $report_title = 'Pending Leave Applications';

            $where_conditions = ["l.status = 'pending'"];
            $params = [];

            if ($department !== 'all') {
                $where_conditions[] = "d.id = ?";
                $params[] = $department;
            }

            $where_clause = implode(' AND ', $where_conditions);

            $report_data = db_fetch_all("
                SELECT
                    l.id,
                    u.name as user_name,
                    u.employee_id,
                    d.name as department_name,
                    lt.name as leave_type_name,
                    l.start_date,
                    l.end_date,
                    l.working_days,
                    l.submitted_at,
                    DATEDIFF(l.start_date, CURDATE()) as days_until_start
                FROM leaves l
                JOIN users u ON l.user_id = u.id
                JOIN leave_types lt ON l.leave_type_id = lt.id
                LEFT JOIN departments d ON u.department_id = d.id
                WHERE {$where_clause}
                ORDER BY l.submitted_at ASC
            ", $params);
            break;

        case 'leave-balance':
            $report_title = 'Employee Leave Balance';

            $where_conditions = ["lb.year = ?"];
            $params = [date('Y')];

            if ($department !== 'all') {
                $where_conditions[] = "d.id = ?";
                $params[] = $department;
            }

            if ($leave_type !== 'all') {
                $where_conditions[] = "lb.leave_type_id = ?";
                $params[] = $leave_type;
            }

            $where_clause = implode(' AND ', $where_conditions);

            $report_data = db_fetch_all("
                SELECT
                    u.name as user_name,
                    u.employee_id,
                    d.name as department_name,
                    lt.name as leave_type_name,
                    lb.total_allocation,
                    lb.used_days,
                    lb.remaining_days,
                    ROUND((lb.used_days / lb.total_allocation) * 100, 1) as usage_percentage
                FROM leave_balances lb
                JOIN users u ON lb.user_id = u.id
                JOIN leave_types lt ON lb.leave_type_id = lt.id
                LEFT JOIN departments d ON u.department_id = d.id
                WHERE {$where_clause} AND u.status = 'active'
                ORDER BY d.name, u.name, lt.name
            ", $params);
            break;
    }
}

$departments = get_all_departments();
$leave_types = get_all_leave_types();

if ($format === 'csv' && !empty($report_data)) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . strtolower(str_replace(' ', '_', $report_title)) . '_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    if (!empty($report_data)) {
        fputcsv($output, array_keys($report_data[0]));
        foreach ($report_data as $row) {
            fputcsv($output, $row);
        }
    }

    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KurdLeave — Reports</title>
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
        <td><b><a href="admin_reports.php"><i class="fas fa-chart-bar"></i> Reports</a></b></td>
        <td><a href="admin_settings.php"><i class="fas fa-cog"></i> System Settings</a></td>
        <td><a href="admin_logs.php"><i class="fas fa-history"></i> Activity Logs</a></td>
        <td><a href="../user/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></td>
      </tr>
    </table>

    <div class="content-panel">
      <div class="panel-heading">
        <h2><i class="fas fa-file-alt"></i> Administrative Reports</h2>
        <p>Generate comprehensive reports for analysis and compliance</p>
      </div>

      <div class="card">
        <div class="card-header"><i class="fas fa-filter"></i> Generate Report</div>
        <form action="" method="get" class="form-grid mt-2">
          <div>
            <label for="report-type">Report Type:</label>
            <select id="report-type" name="report_type" required>
              <option value="">-- Select Report Type --</option>
              <option value="leave-usage" <?php echo $report_type === 'leave-usage' ? 'selected' : ''; ?>>Leave Usage Summary</option>
              <option value="dept-absence" <?php echo $report_type === 'dept-absence' ? 'selected' : ''; ?>>Department Absence Analysis</option>
              <option value="pending-leaves" <?php echo $report_type === 'pending-leaves' ? 'selected' : ''; ?>>Pending Leave Applications</option>
              <option value="leave-balance" <?php echo $report_type === 'leave-balance' ? 'selected' : ''; ?>>Employee Leave Balance</option>
            </select>
          </div>

          <div>
            <label for="department">Department:</label>
            <select id="department" name="department">
              <option value="all" <?php echo $department === 'all' ? 'selected' : ''; ?>>All Departments</option>
              <?php foreach ($departments as $dept): ?>
                <option value="<?php echo $dept['id']; ?>" <?php echo $department == $dept['id'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($dept['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label for="date-range">Date Range:</label>
            <div style="display: flex; gap: 10px; align-items: center;">
              <input type="date" id="date-from" name="date_from" style="flex: 1;" value="<?php echo $date_from; ?>">
              <span>to</span>
              <input type="date" id="date-to" name="date_to" style="flex: 1;" value="<?php echo $date_to; ?>">
            </div>
          </div>

          <div>
            <label for="leave-type">Leave Type:</label>
            <select id="leave-type" name="leave_type">
              <option value="all" <?php echo $leave_type === 'all' ? 'selected' : ''; ?>>All Types</option>
              <?php foreach ($leave_types as $type): ?>
                <option value="<?php echo $type['id']; ?>" <?php echo $leave_type == $type['id'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($type['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label for="export-format">Export Format:</label>
            <select id="export-format" name="format">
              <option value="html" <?php echo $format === 'html' ? 'selected' : ''; ?>>View Online</option>
              <option value="csv" <?php echo $format === 'csv' ? 'selected' : ''; ?>>CSV Export</option>
            </select>
          </div>

          <div>
            <label>&nbsp;</label>
            <div>
              <button type="submit" class="btn-success"><i class="fas fa-file-export"></i> Generate Report</button>
              <a href="admin_reports.php" class="btn-danger"><i class="fas fa-times"></i> Clear</a>
            </div>
          </div>
        </form>
      </div>
    </div>

    <?php if (!empty($report_type) && !empty($report_data)): ?>
    <div class="content-panel">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2><i class="fas fa-chart-line"></i> <?php echo htmlspecialchars($report_title); ?></h2>
        <div>
          <a href="?<?php echo http_build_query(array_merge($_GET, ['format' => 'csv'])); ?>" class="btn">
            <i class="fas fa-download"></i> Download CSV
          </a>
        </div>
      </div>

      <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <strong>Report Parameters:</strong>
        Department: <?php echo $department === 'all' ? 'All' : htmlspecialchars($departments[array_search($department, array_column($departments, 'id'))]['name'] ?? 'Unknown'); ?> |
        Period: <?php echo date('M j, Y', strtotime($date_from)); ?> - <?php echo date('M j, Y', strtotime($date_to)); ?> |
        Records: <?php echo count($report_data); ?>
      </div>

      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <?php if (!empty($report_data)): ?>
                <?php foreach (array_keys($report_data[0]) as $column): ?>
                  <th><?php echo ucwords(str_replace('_', ' ', $column)); ?></th>
                <?php endforeach; ?>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($report_data as $row): ?>
              <tr>
                <?php foreach ($row as $key => $value): ?>
                  <td>
                    <?php
                    if (strpos($key, 'date') !== false && !empty($value)) {
                        echo format_date($value);
                    } elseif (strpos($key, 'percentage') !== false) {
                        echo $value . '%';
                    } elseif (is_numeric($value) && strpos($key, 'days') !== false) {
                        echo number_format($value, 1);
                    } else {
                        echo htmlspecialchars($value ?? 'N/A');
                    }
                    ?>
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php elseif (!empty($report_type) && empty($report_data)): ?>
    <div class="content-panel">
      <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> No data found for the selected criteria.
      </div>
    </div>
    <?php endif; ?>

    <div class="content-panel">
      <h2><i class="fas fa-bolt"></i> Quick Reports</h2>

      <div class="card-container" style="display: flex; flex-wrap: wrap; gap: 1rem;">
        <div class="card" style="flex: 1; min-width: 250px; text-align: center;">
          <div class="card-header"><i class="fas fa-chart-pie"></i> Current Month Summary</div>
          <p>Leave usage for <?php echo date('F Y'); ?></p>
          <a href="?report_type=leave-usage&date_from=<?php echo date('Y-m-01'); ?>&date_to=<?php echo date('Y-m-t'); ?>" class="btn">
            <i class="fas fa-eye"></i> View Report
          </a>
        </div>

        <div class="card" style="flex: 1; min-width: 250px; text-align: center;">
          <div class="card-header"><i class="fas fa-clock"></i> Pending Approvals</div>
          <p>All pending leave requests</p>
          <a href="?report_type=pending-leaves" class="btn">
            <i class="fas fa-eye"></i> View Report
          </a>
        </div>

        <div class="card" style="flex: 1; min-width: 250px; text-align: center;">
          <div class="card-header"><i class="fas fa-balance-scale"></i> Leave Balances</div>
          <p>Current year leave balances</p>
          <a href="?report_type=leave-balance" class="btn">
            <i class="fas fa-eye"></i> View Report
          </a>
        </div>
      </div>
    </div>

    <div class="footer">
      <p>KurdLeave System &copy; 2025</p>
    </div>
  </div>

  <button class="back-to-top" id="backToTop">
    <i class="fas fa-arrow-up"></i>
  </button>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
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
  </script>
</body>
</html>
