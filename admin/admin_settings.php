<?php
// admin/admin_settings.php - Admin System Settings

require_once '../php/functions.php';

// Require admin access
require_admin();

$success_message = '';
$error_message = '';

// Handle settings updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_company'])) {
        // Company settings update
        $company_name = sanitize_input($_POST['company_name'] ?? '');
        $admin_email = sanitize_input($_POST['admin_email'] ?? '');
        $timezone = sanitize_input($_POST['timezone'] ?? 'UTC');
        $date_format = sanitize_input($_POST['date_format'] ?? 'DD/MM/YYYY');

        if (!empty($company_name) && !empty($admin_email)) {
            // In a real application, these would be stored in a settings table
            // For now, we'll just show success
            $success_message = 'Company settings updated successfully.';
            log_activity($_SESSION['user_id'], 'Settings Update', 'Updated company settings');
        } else {
            $error_message = 'Company name and admin email are required.';
        }
    }

    if (isset($_POST['backup_database'])) {
        // Database backup simulation
        $backup_file = 'backup_' . date('Y_m_d_H_i_s') . '.sql';
        $success_message = "Database backup initiated. File: {$backup_file}";
        log_activity($_SESSION['user_id'], 'Database Backup', 'Manual database backup initiated');
    }

    if (isset($_POST['create_leave_type'])) {
        $name = sanitize_input($_POST['leave_type_name'] ?? '');
        $allocation = (int)($_POST['allocation'] ?? 0);
        $carry_forward = (int)($_POST['carry_forward'] ?? 0);
        $notice = (int)($_POST['notice'] ?? 0);
        $documentation = $_POST['documentation'] ?? 'no';
        $status = $_POST['status'] ?? 'active';

        if (!empty($name)) {
            $leave_type_data = [
                'name' => $name,
                'default_allocation' => $allocation,
                'carry_forward_limit' => $carry_forward,
                'min_notice_days' => $notice,
                'requires_documentation' => $documentation === 'yes' ? 1 : 0,
                'status' => $status
            ];

            if (db_insert('leave_types', $leave_type_data)) {
                $success_message = 'Leave type created successfully.';
                log_activity($_SESSION['user_id'], 'Leave Type Management', "Created leave type: {$name}");
                // Clear form
                $_POST = [];
            } else {
                $error_message = 'Failed to create leave type.';
            }
        } else {
            $error_message = 'Leave type name is required.';
        }
    }

    if (isset($_POST['create_holiday'])) {
        $holiday_name = sanitize_input($_POST['holiday_name'] ?? '');
        $holiday_date = $_POST['holiday_date'] ?? '';
        $holiday_type = $_POST['holiday_type'] ?? 'public';
        $holiday_location = $_POST['holiday_location'] ?? 'all';
        $holiday_description = sanitize_input($_POST['holiday_description'] ?? '');

        if (!empty($holiday_name) && !empty($holiday_date)) {
            $holiday_data = [
                'name' => $holiday_name,
                'date' => $holiday_date,
                'type' => $holiday_type,
                'applies_to' => $holiday_location,
                'description' => $holiday_description
            ];

            if (db_insert('holidays', $holiday_data)) {
                $success_message = 'Holiday created successfully.';
                log_activity($_SESSION['user_id'], 'Holiday Management', "Created holiday: {$holiday_name}");
                // Clear form
                $_POST = [];
            } else {
                $error_message = 'Failed to create holiday.';
            }
        } else {
            $error_message = 'Holiday name and date are required.';
        }
    }
}

// Get system information
$system_info = [
    'php_version' => phpversion(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'database_version' => db_fetch("SELECT VERSION() as version")['version'] ?? 'Unknown',
    'total_users' => db_fetch("SELECT COUNT(*) as count FROM users")['count'],
    'total_leaves' => db_fetch("SELECT COUNT(*) as count FROM leaves")['count'],
    'disk_space' => '2.5 GB', // Placeholder
    'last_backup' => '2025-04-27 23:00:00' // Placeholder
];

// Get leave types
$leave_types = get_all_leave_types();

// Get holidays
$upcoming_holidays = db_fetch_all("
    SELECT * FROM holidays
    WHERE date >= CURDATE()
    ORDER BY date
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KurdLeave — System Settings</title>
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
        <td><a href="admin_dashboard.php"><i class="fas fa-home"></i> Admin Home</a></td>
        <td><a href="admin_leaves.php"><i class="fas fa-calendar-alt"></i> Leave Management</a></td>
        <td><a href="admin_users.php"><i class="fas fa-users"></i> User Management</a></td>
        <td><a href="admin_reports.php"><i class="fas fa-chart-bar"></i> Reports</a></td>
        <td><b><a href="admin_settings.php"><i class="fas fa-cog"></i> System Settings</a></b></td>
        <td><a href="admin_logs.php"><i class="fas fa-history"></i> Activity Logs</a></td>
        <td><a href="../user/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></td>
      </tr>
    </table>

    <div class="content-panel">
      <div class="panel-heading text-center">
        <h2><i class="fas fa-cog"></i> System Settings</h2>
        <p>Configure and manage system preferences</p>
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

      <!-- Configuration Categories -->
      <div class="card-container" style="display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: var(--spacing-lg);">
        <div class="card" style="flex: 1; min-width: 200px; text-align: center;">
          <div class="card-header"><i class="fas fa-sliders-h"></i> General Settings</div>
          <p>Company information and system defaults</p>
          <button onclick="showSection('general')" class="btn"><i class="fas fa-cog"></i> Configure</button>
        </div>

        <div class="card" style="flex: 1; min-width: 200px; text-align: center;">
          <div class="card-header"><i class="fas fa-list"></i> Leave Types</div>
          <p>Manage available leave types</p>
          <button onclick="showSection('leave-types')" class="btn"><i class="fas fa-plus"></i> Manage</button>
        </div>

        <div class="card" style="flex: 1; min-width: 200px; text-align: center;">
          <div class="card-header"><i class="fas fa-calendar"></i> Holidays</div>
          <p>Configure company holidays</p>
          <button onclick="showSection('holidays')" class="btn"><i class="fas fa-calendar-plus"></i> Manage</button>
        </div>

        <div class="card" style="flex: 1; min-width: 200px; text-align: center;">
          <div class="card-header"><i class="fas fa-database"></i> System Info</div>
          <p>View system information and backup</p>
          <button onclick="showSection('system')" class="btn"><i class="fas fa-info"></i> View</button>
        </div>
      </div>
    </div>

    <!-- General Settings Section -->
    <div id="general-section" class="content-panel" style="display: block;">
      <h3><i class="fas fa-sliders-h"></i> General Settings</h3>
      <form method="POST" action="">
        <div class="form-grid">
          <div>
            <label for="company-name">Company Name:</label>
            <input type="text" id="company-name" name="company_name" value="KurdLeave Corporation" required>
          </div>

          <div>
            <label for="admin-email">System Administrator Email:</label>
            <input type="email" id="admin-email" name="admin_email" value="admin@example.com" required>
          </div>

          <div>
            <label for="timezone">Time Zone:</label>
            <select id="timezone" name="timezone">
              <option value="UTC" selected>UTC (Coordinated Universal Time)</option>
              <option value="America/New_York">Eastern Time (US & Canada)</option>
              <option value="America/Chicago">Central Time (US & Canada)</option>
              <option value="America/Denver">Mountain Time (US & Canada)</option>
              <option value="America/Los_Angeles">Pacific Time (US & Canada)</option>
              <option value="Europe/London">London</option>
              <option value="Europe/Paris">Paris</option>
              <option value="Asia/Baghdad">Baghdad</option>
              <option value="Asia/Dubai">Dubai</option>
            </select>
          </div>

          <div>
            <label for="date-format">Date Format:</label>
            <select id="date-format" name="date_format">
              <option value="MM/DD/YYYY">MM/DD/YYYY (US Format)</option>
              <option value="DD/MM/YYYY" selected>DD/MM/YYYY (International)</option>
              <option value="YYYY-MM-DD">YYYY-MM-DD (ISO Format)</option>
            </select>
          </div>
        </div>

        <div class="text-center mt-3">
          <button type="submit" name="update_company" class="btn-success">
            <i class="fas fa-save"></i> Save General Settings
          </button>
        </div>
      </form>
    </div>

    <!-- Leave Types Section -->
    <div id="leave-types-section" class="content-panel" style="display: none;">
      <h3><i class="fas fa-list"></i> Leave Types Management</h3>

      <!-- Current Leave Types -->
      <h4>Current Leave Types</h4>
      <table class="data-table">
        <thead>
          <tr>
            <th>Leave Type</th>
            <th>Default Allocation</th>
            <th>Carry Forward Limit</th>
            <th>Minimum Notice</th>
            <th>Requires Documentation</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($leave_types as $leave_type): ?>
            <tr>
              <td><?php echo htmlspecialchars($leave_type['name']); ?></td>
              <td class="text-center"><?php echo $leave_type['default_allocation']; ?> days</td>
              <td class="text-center"><?php echo $leave_type['carry_forward_limit']; ?> days</td>
              <td class="text-center"><?php echo $leave_type['min_notice_days']; ?> days</td>
              <td class="text-center"><?php echo $leave_type['requires_documentation'] ? 'Yes' : 'No'; ?></td>
              <td class="text-center">
                <span class="status status-<?php echo $leave_type['status'] === 'active' ? 'approved' : 'rejected'; ?>">
                  <?php echo ucfirst($leave_type['status']); ?>
                </span>
              </td>
              <td class="text-center">
                <button class="btn btn-sm" disabled><i class="fas fa-edit"></i> Edit</button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- Add New Leave Type -->
      <div class="card mt-3">
        <div class="card-header"><i class="fas fa-plus-circle"></i> Add New Leave Type</div>
        <form method="POST" action="" class="form-grid mt-2">
          <div>
            <label for="leave-type-name">Leave Type Name:</label>
            <input type="text" id="leave-type-name" name="leave_type_name" required value="<?php echo htmlspecialchars($_POST['leave_type_name'] ?? ''); ?>">
          </div>

          <div>
            <label for="allocation">Default Annual Allocation:</label>
            <input type="number" id="allocation" name="allocation" min="0" max="365" required value="<?php echo $_POST['allocation'] ?? ''; ?>">
          </div>

          <div>
            <label for="carry-forward">Carry Forward Limit:</label>
            <input type="number" id="carry-forward" name="carry_forward" min="0" max="365" value="<?php echo $_POST['carry_forward'] ?? '0'; ?>">
          </div>

          <div>
            <label for="notice">Minimum Notice (Days):</label>
            <input type="number" id="notice" name="notice" min="0" max="90" value="<?php echo $_POST['notice'] ?? '0'; ?>">
          </div>

          <div>
            <label for="documentation">Requires Documentation:</label>
            <select id="documentation" name="documentation">
              <option value="no" <?php echo (isset($_POST['documentation']) && $_POST['documentation'] === 'no') ? 'selected' : ''; ?>>No</option>
              <option value="yes" <?php echo (isset($_POST['documentation']) && $_POST['documentation'] === 'yes') ? 'selected' : ''; ?>>Yes</option>
            </select>
          </div>

          <div>
            <label for="status">Status:</label>
            <select id="status" name="status">
              <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
              <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
            </select>
          </div>

          <div class="grid-span-2 text-center mt-2">
            <button type="submit" name="create_leave_type" class="btn-success">
              <i class="fas fa-plus-circle"></i> Add Leave Type
            </button>
            <button type="reset" class="btn-danger">
              <i class="fas fa-times-circle"></i> Clear Form
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Holidays Section -->
    <div id="holidays-section" class="content-panel" style="display: none;">
      <h3><i class="fas fa-calendar"></i> Holiday Management</h3>

      <!-- Upcoming Holidays -->
      <h4>Upcoming Holidays</h4>
      <table class="data-table">
        <thead>
          <tr>
            <th>Holiday Name</th>
            <th>Date</th>
            <th>Type</th>
            <th>Applies To</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($upcoming_holidays as $holiday): ?>
            <tr>
              <td><?php echo htmlspecialchars($holiday['name']); ?></td>
              <td><?php echo format_date($holiday['date']); ?></td>
              <td>
                <span style="
                  padding: 3px 8px;
                  border-radius: 12px;
                  font-size: 0.85rem;
                  background-color: <?php echo $holiday['type'] === 'public' ? 'var(--info-color-light)' : 'var(--success-color-light)'; ?>;
                  color: white;
                ">
                  <?php echo ucfirst($holiday['type']); ?>
                </span>
              </td>
              <td><?php echo ucfirst($holiday['applies_to']); ?></td>
              <td class="text-center">
                <button class="btn btn-sm" disabled><i class="fas fa-edit"></i> Edit</button>
                <button class="btn btn-sm btn-danger" disabled><i class="fas fa-trash"></i> Delete</button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- Add New Holiday -->
      <div class="card mt-3">
        <div class="card-header"><i class="fas fa-plus-circle"></i> Add New Holiday</div>
        <form method="POST" action="" class="form-grid mt-2">
          <div>
            <label for="holiday-name">Holiday Name:</label>
            <input type="text" id="holiday-name" name="holiday_name" required value="<?php echo htmlspecialchars($_POST['holiday_name'] ?? ''); ?>">
          </div>

          <div>
            <label for="holiday-date">Date:</label>
            <input type="date" id="holiday-date" name="holiday_date" required value="<?php echo $_POST['holiday_date'] ?? ''; ?>">
          </div>

          <div>
            <label for="holiday-type">Type:</label>
            <select id="holiday-type" name="holiday_type">
              <option value="public" <?php echo (isset($_POST['holiday_type']) && $_POST['holiday_type'] === 'public') ? 'selected' : ''; ?>>Public Holiday</option>
              <option value="company" <?php echo (isset($_POST['holiday_type']) && $_POST['holiday_type'] === 'company') ? 'selected' : ''; ?>>Company Holiday</option>
              <option value="optional" <?php echo (isset($_POST['holiday_type']) && $_POST['holiday_type'] === 'optional') ? 'selected' : ''; ?>>Optional Holiday</option>
            </select>
          </div>

          <div>
            <label for="holiday-location">Applies To:</label>
            <select id="holiday-location" name="holiday_location">
              <option value="all" <?php echo (isset($_POST['holiday_location']) && $_POST['holiday_location'] === 'all') ? 'selected' : ''; ?>>All Locations</option>
              <option value="iraq" <?php echo (isset($_POST['holiday_location']) && $_POST['holiday_location'] === 'iraq') ? 'selected' : ''; ?>>Iraq</option>
              <option value="us" <?php echo (isset($_POST['holiday_location']) && $_POST['holiday_location'] === 'us') ? 'selected' : ''; ?>>United States</option>
              <option value="uk" <?php echo (isset($_POST['holiday_location']) && $_POST['holiday_location'] === 'uk') ? 'selected' : ''; ?>>United Kingdom</option>
            </select>
          </div>

          <div class="grid-span-2">
            <label for="holiday-description">Description:</label>
            <textarea id="holiday-description" name="holiday_description" rows="2"><?php echo htmlspecialchars($_POST['holiday_description'] ?? ''); ?></textarea>
          </div>

          <div class="grid-span-2 text-center mt-2">
            <button type="submit" name="create_holiday" class="btn-success">
              <i class="fas fa-plus-circle"></i> Add Holiday
            </button>
            <button type="reset" class="btn-danger">
              <i class="fas fa-times"></i> Clear
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- System Information Section -->
    <div id="system-section" class="content-panel" style="display: none;">
      <h3><i class="fas fa-info-circle"></i> System Information</h3>

      <div style="display: flex; flex-wrap: wrap; gap: var(--spacing-lg);">
        <!-- System Info -->
        <div style="flex: 1; min-width: 380px;">
          <h4><i class="fas fa-server"></i> Server Information</h4>
          <table class="data-table">
            <tr>
              <th>PHP Version:</th>
              <td><?php echo $system_info['php_version']; ?></td>
            </tr>
            <tr>
              <th>Server Software:</th>
              <td><?php echo $system_info['server_software']; ?></td>
            </tr>
            <tr>
              <th>Database Version:</th>
              <td><?php echo $system_info['database_version']; ?></td>
            </tr>
            <tr>
              <th>Total Users:</th>
              <td><?php echo $system_info['total_users']; ?></td>
            </tr>
            <tr>
              <th>Total Leave Records:</th>
              <td><?php echo $system_info['total_leaves']; ?></td>
            </tr>
            <tr>
              <th>Disk Space Used:</th>
              <td><?php echo $system_info['disk_space']; ?></td>
            </tr>
          </table>
        </div>

        <!-- Backup & Maintenance -->
        <div style="flex: 1; min-width: 380px;">
          <h4><i class="fas fa-database"></i> Backup & Maintenance</h4>
          <table class="data-table">
            <tr>
              <th>Last Backup:</th>
              <td><?php echo format_datetime($system_info['last_backup']); ?></td>
            </tr>
            <tr>
              <th>Backup Schedule:</th>
              <td>Daily at 11:00 PM</td>
            </tr>
            <tr>
              <th>Auto Backup:</th>
              <td><span class="status status-approved">Enabled</span></td>
            </tr>
            <tr>
              <th>System Status:</th>
              <td><span class="status status-approved">Operational</span></td>
            </tr>
          </table>

          <div class="text-center mt-3">
            <form method="POST" action="" style="display: inline;">
              <button type="submit" name="backup_database" class="btn-success">
                <i class="fas fa-download"></i> Backup Database Now
              </button>
            </form>
            <button type="button" class="btn" disabled>
              <i class="fas fa-tools"></i> Maintenance Mode
            </button>
          </div>
        </div>
      </div>

      <!-- System License -->
      <div class="card mt-3">
        <div class="card-header"><i class="fas fa-key"></i> System License Information</div>
        <table class="data-table">
          <tr>
            <th>License Type:</th>
            <td>Enterprise</td>
          </tr>
          <tr>
            <th>Licensed To:</th>
            <td>KurdLeave Corporation</td>
          </tr>
          <tr>
            <th>License Key:</th>
            <td>KURD-ENT-2025-XXXX-XXXX</td>
          </tr>
          <tr>
            <th>Expiration Date:</th>
            <td>June 15, 2025 (48 days remaining)</td>
          </tr>
          <tr>
            <th>Licensed Users:</th>
            <td>50 (<?php echo $system_info['total_users']; ?> used, <?php echo 50 - $system_info['total_users']; ?> available)</td>
          </tr>
        </table>
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

    function showSection(sectionName) {
      // Hide all sections
      const sections = ['general-section', 'leave-types-section', 'holidays-section', 'system-section'];
      sections.forEach(section => {
        document.getElementById(section).style.display = 'none';
      });

      // Show selected section
      document.getElementById(sectionName + '-section').style.display = 'block';

      // Scroll to section
      document.getElementById(sectionName + '-section').scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    }
  </script>
</body>
</html>
