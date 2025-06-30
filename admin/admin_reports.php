<?php
/*
 * ADMIN REPORTS PAGE - The Company's Data Detective! 📊
 * =====================================================
 *
 * Hi there! This page is like having a super smart assistant that can answer questions like:
 * - "Who's been taking the most vacation days?"
 * - "Which department is busiest or needs more staff coverage?"
 * - "What leave requests are still waiting for approval?"
 *
 * Think of this as your company's report generator - it looks at all the leave data
 * and creates helpful summaries that managers and HR can use to make smart decisions.
 *
 * WHAT THIS PAGE DOES (The Big Picture):
 * 1. 🎯 Takes your questions (via filters like date range, department, leave type)
 * 2. 🔍 Searches through the database like a detective finding clues
 * 3. 📈 Creates easy-to-read reports with numbers, charts, and summaries
 * 4. 📄 Shows results on screen OR exports to Excel/CSV for sharing
 *
 * TYPES OF REPORTS WE CREATE:
 * - Leave Usage Summary: Who used how many days of what type of leave
 * - Department Absence Analysis: Which departments have people out most often
 * - Pending Applications: What requests are waiting for manager approval
 * - Balance Reports: How many vacation days does everyone have left
 *
 * It's like having a crystal ball for workforce planning! 🔮
 */

// admin/admin_reports.php - Admin Reports

require_once '../php/functions.php';

// SECURITY CHECK: Make sure only admin users can access this reporting system
// (We don't want regular employees seeing everyone's leave data!)
require_admin();

// COLLECT THE FILTERS: What kind of report does the user want?
// These are like the questions you ask when ordering food - "What size? What toppings?"
$report_type = $_GET['report_type'] ?? '';        // What kind of report? (leave-usage, dept-absence, etc.)
$department = $_GET['department'] ?? 'all';       // Which department? (or 'all' for everyone)
$leave_type = $_GET['leave_type'] ?? 'all';       // Which type of leave? (vacation, sick, etc.)
$date_from = $_GET['date_from'] ?? date('Y-01-01'); // Start date (default: beginning of this year)
$date_to = $_GET['date_to'] ?? date('Y-12-31');     // End date (default: end of this year)
$format = $_GET['format'] ?? 'html';              // How to show it? (on screen or download as file)

// PREPARE THE RESULTS: Start with empty containers
$report_data = [];    // This will hold all the numbers and facts we find
$report_title = '';   // This will be the title of our report

// THE MAIN REPORT GENERATOR: Based on what type of report they want, do different things
if (!empty($report_type)) {
    switch ($report_type) {
        case 'leave-usage':
            // LEAVE USAGE SUMMARY REPORT 📊
            // This report answers: "Who took how many days off and what kind?"
            // Perfect for HR to see patterns and plan for busy/slow periods

            $report_title = 'Leave Usage Summary';

            // BUILD THE SEARCH CRITERIA: Start with basic rules, then add filters
            $where_conditions = ["l.status = 'approved'"];  // Only count leaves that were actually approved
            $params = [];  // This holds the actual values for our database search

            // APPLY DEPARTMENT FILTER: If they picked a specific department
            if ($department !== 'all') {
                $where_conditions[] = "d.id = ?";  // Add department filter
                $params[] = $department;           // Remember which department
            }

            // APPLY LEAVE TYPE FILTER: If they picked a specific type (vacation, sick, etc.)
            if ($leave_type !== 'all') {
                $where_conditions[] = "l.leave_type_id = ?";  // Add leave type filter
                $params[] = $leave_type;                      // Remember which type
            }

            // APPLY DATE RANGE FILTER: Only look at leaves within the specified time period
            $where_conditions[] = "l.start_date BETWEEN ? AND ?";
            $params[] = $date_from;  // From this date...
            $params[] = $date_to;    // ...to this date

            // COMBINE ALL FILTERS: Join them with 'AND' so all conditions must be true
            $where_clause = implode(' AND ', $where_conditions);

            // RUN THE BIG QUERY: Get all the leave usage data
            // This is like asking the database: "Show me every approved leave that matches my filters,
            // and for each person, tell me how many requests they made and total days they used"
            $report_data = db_fetch_all("
                SELECT
                    d.name as department_name,          -- Which department they work in
                    u.name as user_name,               -- Employee's name
                    u.employee_id,                     -- Their employee ID number
                    lt.name as leave_type_name,        -- Type of leave (vacation, sick, etc.)
                    COUNT(l.id) as total_requests,     -- How many separate requests they made
                    SUM(l.working_days) as total_days, -- Total days they took off
                    AVG(l.working_days) as avg_days    -- Average days per request
                FROM leaves l
                JOIN users u ON l.user_id = u.id                    -- Connect leaves to employees
                JOIN leave_types lt ON l.leave_type_id = lt.id       -- Connect leaves to leave types
                LEFT JOIN departments d ON u.department_id = d.id    -- Connect employees to departments
                WHERE {$where_clause}
                GROUP BY u.id, lt.id                                 -- Group by person and leave type
                ORDER BY d.name, u.name, lt.name                    -- Sort by department, then name, then leave type
            ", $params);
            break;

        case 'dept-absence':
            // DEPARTMENT ABSENCE ANALYSIS REPORT 🏢
            // This report answers: "Which departments have the most people out?"
            // Great for managers to plan workload and identify departments that might need extra staff

            $report_title = 'Department Absence Analysis';

            // START WITH BASIC RULES: Only count approved leaves (not just requests)
            $where_conditions = ["l.status = 'approved'"];
            $params = [];

            // APPLY DEPARTMENT FILTER: If they want to focus on just one department
            if ($department !== 'all') {
                $where_conditions[] = "d.id = ?";
                $params[] = $department;
            }

            // APPLY DATE RANGE: Only look at leaves within the time period they chose
            $where_conditions[] = "l.start_date BETWEEN ? AND ?";
            $params[] = $date_from;
            $params[] = $date_to;

            // COMBINE FILTERS: All conditions must be true
            $where_clause = implode(' AND ', $where_conditions);

            // RUN THE DEPARTMENT ANALYSIS QUERY:
            // This is like asking: "For each department, how many people work there,
            // how many leave requests happened, and what's the average absence rate?"
            $report_data = db_fetch_all("
                SELECT
                    d.name as department_name,                                          -- Department name
                    COUNT(DISTINCT u.id) as total_employees,                          -- How many people work there
                    COUNT(l.id) as total_requests,                                    -- How many leave requests
                    SUM(l.working_days) as total_days,                               -- Total days of absence
                    ROUND(AVG(l.working_days), 2) as avg_days_per_request,           -- Average days per request
                    ROUND(SUM(l.working_days) / COUNT(DISTINCT u.id), 2) as avg_days_per_employee  -- Average days per person
                FROM departments d                                                   -- Start with all departments
                LEFT JOIN users u ON d.id = u.department_id AND u.status = 'active'  -- Get active employees in each dept
                LEFT JOIN leaves l ON u.id = l.user_id AND {$where_clause}           -- Get their approved leaves
                GROUP BY d.id                                                        -- Group results by department
                ORDER BY total_days DESC                                             -- Show departments with most absences first
            ", $params);
            break;

        case 'pending-leaves':
            // PENDING LEAVE APPLICATIONS REPORT ⏳
            // This report answers: "What requests are waiting for approval?"
            // Super important for managers to see what needs their attention!

            $report_title = 'Pending Leave Applications';

            // START WITH PENDING STATUS: Only show requests that haven't been approved/rejected yet
            $where_conditions = ["l.status = 'pending'"];
            $params = [];

            // APPLY DEPARTMENT FILTER: If they want to see just one department's pending requests
            if ($department !== 'all') {
                $where_conditions[] = "d.id = ?";
                $params[] = $department;
            }

            // COMBINE FILTERS: All conditions must be true
            $where_clause = implode(' AND ', $where_conditions);

            // GET ALL PENDING REQUESTS:
            // This is like asking: "Show me every request that's waiting for approval,
            // who submitted it, when they want to take leave, and how urgent it is"
            $report_data = db_fetch_all("
                SELECT
                    l.id,                                                    -- Leave request ID
                    u.name as user_name,                                    -- Who submitted it
                    u.employee_id,                                          -- Their employee ID
                    d.name as department_name,                              -- What department they're in
                    lt.name as leave_type_name,                             -- Type of leave requested
                    l.start_date,                                           -- When they want to start
                    l.end_date,                                             -- When they want to return
                    l.working_days,                                         -- How many work days they'll miss
                    l.submitted_at,                                         -- When they submitted the request
                    DATEDIFF(l.start_date, CURDATE()) as days_until_start  -- How many days until they leave (urgency!)
                FROM leaves l
                JOIN users u ON l.user_id = u.id                          -- Connect to employee info
                JOIN leave_types lt ON l.leave_type_id = lt.id             -- Connect to leave type info
                LEFT JOIN departments d ON u.department_id = d.id          -- Connect to department info
                WHERE {$where_clause}
                ORDER BY l.submitted_at ASC                                -- Show oldest requests first (first come, first served)
            ", $params);
            break;

        case 'leave-balance':
            // EMPLOYEE LEAVE BALANCE REPORT 🏖️
            // This report answers: "How many vacation days does everyone have left?"
            // Perfect for HR planning and helping employees know their available time off

            $report_title = 'Employee Leave Balance';

            // FOCUS ON CURRENT YEAR: Most people care about this year's vacation days
            $where_conditions = ["lb.year = ?"];
            $params = [date('Y')];  // Current year

            // APPLY DEPARTMENT FILTER: If they want to see just one department
            if ($department !== 'all') {
                $where_conditions[] = "d.id = ?";
                $params[] = $department;
            }

            // APPLY LEAVE TYPE FILTER: If they want to focus on specific leave type (vacation, sick, etc.)
            if ($leave_type !== 'all') {
                $where_conditions[] = "lb.leave_type_id = ?";
                $params[] = $leave_type;
            }

            // COMBINE FILTERS: All conditions must be true
            $where_clause = implode(' AND ', $where_conditions);

            // GET EVERYONE'S LEAVE BALANCES:
            // This is like asking: "For each active employee, show me how many vacation days
            // they started with, how many they've used, and how many they have left"
            $report_data = db_fetch_all("
                SELECT
                    u.name as user_name,                                              -- Employee name
                    u.employee_id,                                                    -- Their ID number
                    d.name as department_name,                                        -- What department
                    lt.name as leave_type_name,                                       -- Type of leave (vacation, sick, etc.)
                    lb.total_allocation,                                              -- How many days they got this year
                    lb.used_days,                                                     -- How many they've already used
                    lb.remaining_days,                                                -- How many they have left
                    ROUND((lb.used_days / lb.total_allocation) * 100, 1) as usage_percentage  -- What % they've used
                FROM leave_balances lb
                JOIN users u ON lb.user_id = u.id                                   -- Connect to employee info
                JOIN leave_types lt ON lb.leave_type_id = lt.id                     -- Connect to leave type info
                LEFT JOIN departments d ON u.department_id = d.id                   -- Connect to department info
                WHERE {$where_clause} AND u.status = 'active'                       -- Only active employees
                ORDER BY d.name, u.name, lt.name                                    -- Sort by dept, name, leave type
            ", $params);
            break;
    }
}

// GET DATA FOR THE FILTER DROPDOWNS: So users can pick what they want to see
$departments = get_all_departments();  // List of all departments
$leave_types = get_all_leave_types();   // List of all leave types (vacation, sick, etc.)

// EXPORT TO CSV FILE: If they want to download the report instead of viewing on screen
if ($format === 'csv' && !empty($report_data)) {
    // PREPARE THE DOWNLOAD: Set up the browser to download a CSV file
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . strtolower(str_replace(' ', '_', $report_title)) . '_' . date('Y-m-d') . '.csv"');

    // CREATE THE CSV CONTENT: Start writing the file
    $output = fopen('php://output', 'w');

    if (!empty($report_data)) {
        // WRITE THE HEADER ROW: Column names from the first row of data
        fputcsv($output, array_keys($report_data[0]));

        // WRITE ALL THE DATA ROWS: Each row becomes a line in the CSV
        foreach ($report_data as $row) {
            fputcsv($output, $row);  // Convert each database row to CSV format
        }
    }

    // FINISH THE FILE: Close and send it to the user's browser for download
    fclose($output);
    exit;  // Stop here - we're done, user is downloading the file
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
  <!-- MAIN ADMIN NAVIGATION: Same as other admin pages for consistency -->
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
        <td><b><a href="admin_reports.php"><i class="fas fa-chart-bar"></i> Reports</a></b></td>  <!-- This is the current page -->
        <td><a href="admin_settings.php"><i class="fas fa-cog"></i> System Settings</a></td>
        <td><a href="admin_logs.php"><i class="fas fa-history"></i> Activity Logs</a></td>
        <td><a href="../user/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></td>
      </tr>
    </table>

    <!-- MAIN CONTENT AREA: Where all the reporting magic happens -->
    <div class="content-panel">
      <div class="panel-heading">
        <h2><i class="fas fa-file-alt"></i> Administrative Reports</h2>
        <p>Generate comprehensive reports for analysis and compliance</p>
      </div>

      <div class="card">
        <div class="card-header"><i class="fas fa-filter"></i> Generate Report</div>
        <!-- THE REPORT FILTER FORM: This is like a restaurant menu where you pick what you want -->
        <form action="" method="get" class="form-grid mt-2">

          <!-- REPORT TYPE SELECTOR: What kind of report do you want? -->
          <div>
            <label for="report-type">Report Type:</label>
            <select id="report-type" name="report_type" required>
              <option value="">-- Select Report Type --</option>
              <!-- Each option generates a different type of report with different data -->
              <option value="leave-usage" <?php echo $report_type === 'leave-usage' ? 'selected' : ''; ?>>Leave Usage Summary</option>
              <option value="dept-absence" <?php echo $report_type === 'dept-absence' ? 'selected' : ''; ?>>Department Absence Analysis</option>
              <option value="pending-leaves" <?php echo $report_type === 'pending-leaves' ? 'selected' : ''; ?>>Pending Leave Applications</option>
              <option value="leave-balance" <?php echo $report_type === 'leave-balance' ? 'selected' : ''; ?>>Employee Leave Balance</option>
            </select>
          </div>

          <!-- DEPARTMENT FILTER: Focus on one department or see all -->
          <div>
            <label for="department">Department:</label>
            <select id="department" name="department">
              <option value="all" <?php echo $department === 'all' ? 'selected' : ''; ?>>All Departments</option>
              <?php foreach ($departments as $dept): ?>
                <!-- Show each department as an option, mark the selected one -->
                <option value="<?php echo $dept['id']; ?>" <?php echo $department == $dept['id'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($dept['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- DATE RANGE PICKER: What time period are you interested in? -->
          <div>
            <label for="date-range">Date Range:</label>
            <div style="display: flex; gap: 10px; align-items: center;">
              <!-- From this date... -->
              <input type="date" id="date-from" name="date_from" style="flex: 1;" value="<?php echo $date_from; ?>">
              <span>to</span>
              <!-- ...to this date -->
              <input type="date" id="date-to" name="date_to" style="flex: 1;" value="<?php echo $date_to; ?>">
            </div>
          </div>

          <!-- LEAVE TYPE FILTER: Focus on vacation, sick leave, etc. or see all types -->
          <div>
            <label for="leave-type">Leave Type:</label>
            <select id="leave-type" name="leave_type">
              <option value="all" <?php echo $leave_type === 'all' ? 'selected' : ''; ?>>All Types</option>
              <?php foreach ($leave_types as $type): ?>
                <!-- Show each leave type as an option, mark the selected one -->
                <option value="<?php echo $type['id']; ?>" <?php echo $leave_type == $type['id'] ? 'selected' : ''; ?>>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- EXPORT FORMAT: How do you want to see the results? -->
          <div>
            <label for="export-format">Export Format:</label>
            <select id="export-format" name="format">
              <option value="html" <?php echo $format === 'html' ? 'selected' : ''; ?>>View Online</option>      <!-- Show on screen -->
              <option value="csv" <?php echo $format === 'csv' ? 'selected' : ''; ?>>CSV Export</option>        <!-- Download as file -->
            </select>
          </div>

          <!-- ACTION BUTTONS: Generate the report or start over -->
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

    <!-- REPORT RESULTS SECTION: Only show this if we have data to display -->
    <?php if (!empty($report_type) && !empty($report_data)): ?>
    <div class="content-panel">
      <!-- REPORT HEADER: Title and download option -->
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2><i class="fas fa-chart-line"></i> <?php echo htmlspecialchars($report_title); ?></h2>
        <div>
          <!-- QUICK CSV DOWNLOAD: Same report but as downloadable file -->
          <a href="?<?php echo http_build_query(array_merge($_GET, ['format' => 'csv'])); ?>" class="btn">
            <i class="fas fa-download"></i> Download CSV
          </a>
        </div>
      </div>

      <!-- REPORT SUMMARY: Show what filters were applied -->
      <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <strong>Report Parameters:</strong>
        Department: <?php echo $department === 'all' ? 'All' : htmlspecialchars($departments[array_search($department, array_column($departments, 'id'))]['name'] ?? 'Unknown'); ?> |
        Period: <?php echo date('M j, Y', strtotime($date_from)); ?> - <?php echo date('M j, Y', strtotime($date_to)); ?> |
        Leave Type: <?php echo $leave_type === 'all' ? 'All' : htmlspecialchars($leave_types[array_search($leave_type, array_column($leave_types, 'id'))]['name'] ?? 'Unknown'); ?> |
        Total Records: <?php echo count($report_data); ?>
      </div>

      <!-- THE ACTUAL DATA TABLE: This is where all the numbers and facts are displayed -->
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
