<?php
// admin/admin_users.php - Admin User Management

require_once '../php/functions.php';

// Require admin access
require_admin();

$success_message = '';
$error_message = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $user_id = (int)($_POST['user_id'] ?? 0);

        switch ($action) {
            case 'activate':
                if (db_update('users', ['status' => 'active'], 'id = ?', [$user_id])) {
                    $success_message = 'User activated successfully.';
                    log_activity($_SESSION['user_id'], 'User Management', "Activated user ID: {$user_id}");
                } else {
                    $error_message = 'Failed to activate user.';
                }
                break;

            case 'deactivate':
                if ($user_id != $_SESSION['user_id']) { // Prevent self-deactivation
                    if (db_update('users', ['status' => 'inactive'], 'id = ?', [$user_id])) {
                        $success_message = 'User deactivated successfully.';
                        log_activity($_SESSION['user_id'], 'User Management', "Deactivated user ID: {$user_id}");
                    } else {
                        $error_message = 'Failed to deactivate user.';
                    }
                } else {
                    $error_message = 'You cannot deactivate your own account.';
                }
                break;

            case 'reset_password':
                $new_password = password_hash('temp123', PASSWORD_DEFAULT);
                if (db_update('users', ['password' => $new_password], 'id = ?', [$user_id])) {
                    $success_message = 'Password reset to: temp123 (user should change this immediately)';
                    log_activity($_SESSION['user_id'], 'Password Reset', "Reset password for user ID: {$user_id}");
                } else {
                    $error_message = 'Failed to reset password.';
                }
                break;
        }
    }

    // Handle new user creation
    if (isset($_POST['create_user'])) {
        $name = sanitize_input($_POST['name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $employee_id = sanitize_input($_POST['employee_id'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $department_id = (int)($_POST['department_id'] ?? 0);
        $manager_id = (int)($_POST['manager_id'] ?? 0) ?: null;
        $role = $_POST['role'] ?? 'employee';
        $join_date = $_POST['join_date'] ?? date('Y-m-d');

        // Validation
        if (empty($name) || empty($email) || empty($employee_id)) {
            $error_message = 'Name, email, and employee ID are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Invalid email format.';
        } else {
            // Check for duplicate email/employee_id
            $existing = db_fetch("SELECT id FROM users WHERE email = ? OR employee_id = ?", [$email, $employee_id]);
            if ($existing) {
                $error_message = 'Email or Employee ID already exists.';
            } else {
                $user_data = [
                    'name' => $name,
                    'email' => $email,
                    'employee_id' => $employee_id,
                    'phone' => $phone,
                    'password' => password_hash('temp123', PASSWORD_DEFAULT),
                    'department_id' => $department_id ?: null,
                    'manager_id' => $manager_id,
                    'role' => $role,
                    'join_date' => $join_date,
                    'status' => 'active'
                ];

                $new_user_id = db_insert('users', $user_data);
                if ($new_user_id) {
                    $success_message = "User created successfully. Temporary password: temp123";
                    log_activity($_SESSION['user_id'], 'User Management', "Created new user: {$name} ({$email})");

                    // Create default leave balances
                    $leave_types = get_all_leave_types();
                    foreach ($leave_types as $leave_type) {
                        db_insert('leave_balances', [
                            'user_id' => $new_user_id,
                            'leave_type_id' => $leave_type['id'],
                            'year' => date('Y'),
                            'total_allocation' => $leave_type['default_allocation'],
                            'used_days' => 0,
                            'remaining_days' => $leave_type['default_allocation']
                        ]);
                    }

                    // Clear form
                    $_POST = [];
                } else {
                    $error_message = 'Failed to create user.';
                }
            }
        }
    }
}

// Get filter parameters
$department_filter = $_GET['department'] ?? 'all';
$role_filter = $_GET['role'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query conditions
$where_conditions = ["1=1"];
$params = [];

if ($department_filter !== 'all') {
    $where_conditions[] = "u.department_id = ?";
    $params[] = $department_filter;
}

if ($role_filter !== 'all') {
    $where_conditions[] = "u.role = ?";
    $params[] = $role_filter;
}

if ($status_filter !== 'all') {
    $where_conditions[] = "u.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR u.employee_id LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = implode(' AND ', $where_conditions);

// Get users
$users = db_fetch_all("
    SELECT u.*, d.name as department_name, m.name as manager_name
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    LEFT JOIN users m ON u.manager_id = m.id
    WHERE {$where_clause}
    ORDER BY u.name
", $params);

// Get departments and managers for dropdowns
$departments = get_all_departments();
$managers = db_fetch_all("SELECT id, name FROM users WHERE role IN ('admin', 'manager') AND status = 'active' ORDER BY name");

// Get statistics
$stats = [
    'total' => db_fetch("SELECT COUNT(*) as count FROM users")['count'],
    'active' => db_fetch("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'],
    'admins' => db_fetch("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND status = 'active'")['count'],
    'managers' => db_fetch("SELECT COUNT(*) as count FROM users WHERE role = 'manager' AND status = 'active'")['count'],
    'employees' => db_fetch("SELECT COUNT(*) as count FROM users WHERE role = 'employee' AND status = 'active'")['count']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KurdLeave — User Management</title>
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
        <td><b><a href="admin_users.php"><i class="fas fa-users"></i> User Management</a></b></td>
        <td><a href="admin_reports.php"><i class="fas fa-chart-bar"></i> Reports</a></td>
        <td><a href="admin_settings.php"><i class="fas fa-cog"></i> System Settings</a></td>
        <td><a href="admin_logs.php"><i class="fas fa-history"></i> Activity Logs</a></td>
        <td><a href="../user/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></td>
      </tr>
    </table>

    <!-- Page Header -->
    <div class="content-panel">
      <div class="panel-heading text-center">
        <h2><i class="fas fa-users"></i> User Management</h2>
        <p>Manage system users and their permissions</p>
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

      <!-- Statistics -->
      <div class="stats-container">
        <div class="stat-card">
          <div class="stat-value"><?php echo $stats['total']; ?></div>
          <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?php echo $stats['active']; ?></div>
          <div class="stat-label">Active Users</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?php echo $stats['admins']; ?></div>
          <div class="stat-label">Administrators</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?php echo $stats['managers']; ?></div>
          <div class="stat-label">Managers</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?php echo $stats['employees']; ?></div>
          <div class="stat-label">Employees</div>
        </div>
      </div>
    </div>

    <!-- Search and Filters -->
    <div class="content-panel">
      <h3><i class="fas fa-search"></i> Find Users</h3>

      <form method="GET" action="" class="form-grid">
        <div>
          <label for="search">Search:</label>
          <input type="text" id="search" name="search" placeholder="Name, email or employee ID" value="<?php echo htmlspecialchars($search); ?>">
        </div>

        <div>
          <label for="department">Department:</label>
          <select id="department" name="department">
            <option value="all" <?php echo $department_filter === 'all' ? 'selected' : ''; ?>>All Departments</option>
            <?php foreach ($departments as $department): ?>
              <option value="<?php echo $department['id']; ?>" <?php echo $department_filter == $department['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($department['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label for="role">Role:</label>
          <select id="role" name="role">
            <option value="all" <?php echo $role_filter === 'all' ? 'selected' : ''; ?>>All Roles</option>
            <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Administrator</option>
            <option value="manager" <?php echo $role_filter === 'manager' ? 'selected' : ''; ?>>Manager</option>
            <option value="employee" <?php echo $role_filter === 'employee' ? 'selected' : ''; ?>>Employee</option>
          </select>
        </div>

        <div>
          <label for="status">Status:</label>
          <select id="status" name="status">
            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
          </select>
        </div>

        <div class="grid-span-2 text-center mt-2">
          <button type="submit" class="btn-success"><i class="fas fa-search"></i> Search</button>
          <a href="admin_users.php" class="btn-danger"><i class="fas fa-times"></i> Clear</a>
          <button type="button" onclick="showCreateUserModal()" class="btn"><i class="fas fa-plus-circle"></i> Add New User</button>
        </div>
      </form>
    </div>

    <!-- User List -->
    <div class="content-panel">
      <h3><i class="fas fa-list"></i> Users (<?php echo count($users); ?> found)</h3>

      <?php if (empty($users)): ?>
        <div class="alert alert-info">
          <i class="fas fa-info-circle"></i> No users found matching your criteria.
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Employee ID</th>
                <th>Department</th>
                <th>Role</th>
                <th>Manager</th>
                <th>Status</th>
                <th>Last Login</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $user): ?>
                <tr>
                  <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                      <div style="
                        width: 40px; height: 40px;
                        background-color: var(--primary-color);
                        color: white;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-weight: bold;
                      ">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                      </div>
                      <div>
                        <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                        <?php if ($user['id'] == $_SESSION['user_id']): ?>
                          <br><small style="color: var(--info-color);">(You)</small>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <td><?php echo htmlspecialchars($user['email']); ?></td>
                  <td><strong><?php echo htmlspecialchars($user['employee_id']); ?></strong></td>
                  <td><?php echo htmlspecialchars($user['department_name'] ?? 'Not assigned'); ?></td>
                  <td>
                    <span style="
                      padding: 3px 8px;
                      border-radius: 12px;
                      font-size: 0.85rem;
                      background-color: <?php echo $user['role'] === 'admin' ? 'var(--danger-color-light)' : ($user['role'] === 'manager' ? 'var(--warning-color-light)' : 'var(--info-color-light)'); ?>;
                      color: white;
                    ">
                      <?php echo ucfirst($user['role']); ?>
                    </span>
                  </td>
                  <td><?php echo htmlspecialchars($user['manager_name'] ?? 'None'); ?></td>
                  <td class="text-center">
                    <span class="status status-<?php echo $user['status'] === 'active' ? 'approved' : ($user['status'] === 'pending' ? 'pending' : 'rejected'); ?>">
                      <?php echo ucfirst($user['status']); ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($user['last_login']): ?>
                      <?php echo format_datetime($user['last_login'], 'M j, Y'); ?><br>
                      <small><?php echo format_datetime($user['last_login'], 'H:i'); ?></small>
                    <?php else: ?>
                      <span class="text-muted">Never</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <div style="display: flex; flex-direction: column; gap: 3px;">
                      <?php if ($user['status'] === 'active'): ?>
                        <button onclick="userAction(<?php echo $user['id']; ?>, 'deactivate')"
                                class="btn btn-sm btn-danger"
                                style="font-size: 0.8rem;"
                                <?php echo $user['id'] == $_SESSION['user_id'] ? 'disabled title="Cannot deactivate yourself"' : ''; ?>>
                          <i class="fas fa-user-times"></i> Deactivate
                        </button>
                      <?php else: ?>
                        <button onclick="userAction(<?php echo $user['id']; ?>, 'activate')"
                                class="btn btn-sm btn-success"
                                style="font-size: 0.8rem;">
                          <i class="fas fa-user-check"></i> Activate
                        </button>
                      <?php endif; ?>

                      <button onclick="userAction(<?php echo $user['id']; ?>, 'reset_password')"
                              class="btn btn-sm"
                              style="font-size: 0.8rem;">
                        <i class="fas fa-key"></i> Reset Pass
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Footer -->
    <div class="footer">
      <p>KurdLeave System &copy; 2025</p>
    </div>
  </div>

  <!-- Create User Modal -->
  <div id="createUserModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; max-width: 600px; width: 90%; max-height: 90%; overflow-y: auto;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3>Create New User</h3>
        <button onclick="closeCreateUserModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
      </div>

      <form method="POST" action="" class="form-grid">
        <div>
          <label for="name">Full Name: <span style="color: red;">*</span></label>
          <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
        </div>

        <div>
          <label for="email">Email Address: <span style="color: red;">*</span></label>
          <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>

        <div>
          <label for="employee_id">Employee ID: <span style="color: red;">*</span></label>
          <input type="text" id="employee_id" name="employee_id" required value="<?php echo htmlspecialchars($_POST['employee_id'] ?? ''); ?>">
        </div>

        <div>
          <label for="phone">Phone Number:</label>
          <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
        </div>

        <div>
          <label for="department_id">Department:</label>
          <select id="department_id" name="department_id">
            <option value="">-- Select Department --</option>
            <?php foreach ($departments as $department): ?>
              <option value="<?php echo $department['id']; ?>" <?php echo (isset($_POST['department_id']) && $_POST['department_id'] == $department['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($department['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label for="manager_id">Manager:</label>
          <select id="manager_id" name="manager_id">
            <option value="">-- Select Manager --</option>
            <?php foreach ($managers as $manager): ?>
              <option value="<?php echo $manager['id']; ?>" <?php echo (isset($_POST['manager_id']) && $_POST['manager_id'] == $manager['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($manager['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label for="role">Role:</label>
          <select id="role" name="role">
            <option value="employee" <?php echo (isset($_POST['role']) && $_POST['role'] === 'employee') ? 'selected' : ''; ?>>Employee</option>
            <option value="manager" <?php echo (isset($_POST['role']) && $_POST['role'] === 'manager') ? 'selected' : ''; ?>>Manager</option>
            <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>>Administrator</option>
          </select>
        </div>

        <div>
          <label for="join_date">Join Date:</label>
          <input type="date" id="join_date" name="join_date" value="<?php echo $_POST['join_date'] ?? date('Y-m-d'); ?>">
        </div>

        <div class="grid-span-2">
          <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Note:</strong> The user will be created with a temporary password: <strong>temp123</strong><br>
            They should change this password upon first login.
          </div>
        </div>

        <div class="grid-span-2 text-center mt-2">
          <button type="submit" name="create_user" class="btn-success">
            <i class="fas fa-user-plus"></i> Create User
          </button>
          <button type="button" onclick="closeCreateUserModal()" class="btn-danger">
            <i class="fas fa-times"></i> Cancel
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- User Action Modal -->
  <div id="actionModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1001;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; max-width: 400px; width: 90%;">
      <h3 id="actionTitle">Confirm Action</h3>
      <p id="actionMessage"></p>
      <form method="POST" action="">
        <input type="hidden" id="actionUserId" name="user_id">
        <input type="hidden" id="actionType" name="action">

        <div style="text-align: center; margin-top: 1rem;">
          <button type="submit" id="confirmActionBtn" class="btn">Confirm</button>
          <button type="button" onclick="closeActionModal()" class="btn btn-danger">Cancel</button>
        </div>
      </form>
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
    function showCreateUserModal() {
      document.getElementById('createUserModal').style.display = 'block';
    }

    function closeCreateUserModal() {
      document.getElementById('createUserModal').style.display = 'none';
    }

    function userAction(userId, action) {
      let title, message, btnText, btnClass;

      switch(action) {
        case 'activate':
          title = 'Activate User';
          message = 'Are you sure you want to activate this user?';
          btnText = 'Activate';
          btnClass = 'btn btn-success';
          break;
        case 'deactivate':
          title = 'Deactivate User';
          message = 'Are you sure you want to deactivate this user? They will no longer be able to access the system.';
          btnText = 'Deactivate';
          btnClass = 'btn btn-danger';
          break;
        case 'reset_password':
          title = 'Reset Password';
          message = 'Are you sure you want to reset this user\'s password to "temp123"?';
          btnText = 'Reset Password';
          btnClass = 'btn btn-warning';
          break;
      }

      document.getElementById('actionTitle').textContent = title;
      document.getElementById('actionMessage').textContent = message;
      document.getElementById('actionUserId').value = userId;
      document.getElementById('actionType').value = action;
      document.getElementById('confirmActionBtn').textContent = btnText;
      document.getElementById('confirmActionBtn').className = btnClass;

      document.getElementById('actionModal').style.display = 'block';
    }

    function closeActionModal() {
      document.getElementById('actionModal').style.display = 'none';
    }

    // Close modals when clicking outside
    document.getElementById('createUserModal').addEventListener('click', function(e) {
      if (e.target === this) closeCreateUserModal();
    });

    document.getElementById('actionModal').addEventListener('click', function(e) {
      if (e.target === this) closeActionModal();
    });
  </script>
</body>
</html>
