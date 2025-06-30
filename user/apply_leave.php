<?php
/*
 * APPLY FOR LEAVE PAGE - The Vacation Request Form! 🏖️
 * =====================================================
 *
 * Hey there! This page is where employees come to request time off. Think of it as
 * the digital version of filling out a vacation request form, but much smarter!
 *
 * WHAT THIS PAGE DOES:
 * - 📝 Provides a user-friendly form to request time off
 * - 🔍 Validates all the details (dates, balances, notice periods)
 * - 🧮 Automatically calculates working days (skips weekends)
 * - ✅ Checks if they have enough vacation days left
 * - 📧 Sends the request to their manager for approval
 * - 🛡️ Prevents common mistakes (past dates, insufficient balance, etc.)
 *
 * SMART FEATURES:
 * - Shows their current leave balances so they know what's available
 * - Prevents requesting leave for dates that already passed
 * - Calculates exactly how many work days they'll miss
 * - Checks company policies (minimum notice periods, etc.)
 *
 * It's like having a helpful HR assistant guiding them through the process! 👩‍💼
 */

// user/apply_leave.php - Apply for Leave

require_once '../php/functions.php';

// SECURITY CHECK: Make sure someone is logged in before they can request leave
require_login();

// GET USER INFORMATION: Who is requesting leave?
$user = get_logged_in_user();                                    // Get their profile
$leave_types = get_all_leave_types();                           // Get all available leave types (vacation, sick, etc.)

// GET CURRENT LEAVE BALANCES: How many days do they have available?
$year = date('Y');                                               // Current year
$leave_balances = [];                                            // Container for their balances

// CALCULATE BALANCES FOR EACH LEAVE TYPE: So they know what's available
foreach ($leave_types as $leave_type) {
    $balance = get_user_leave_balance($user['id'], $leave_type['id'], $year);
    if ($balance) {
        $leave_balances[$leave_type['id']] = $balance;           // Store their balance for this leave type
    }
}

// MESSAGE CONTAINERS: For showing success/error messages to the user
$success_message = '';
$error_message = '';

// PROCESS LEAVE REQUEST: Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // COLLECT FORM DATA: What are they requesting?
    $leave_type_id = (int)($_POST['leave_type'] ?? 0);          // What type of leave?
    $start_date = $_POST['start_date'] ?? '';                   // When does it start?
    $end_date = $_POST['end_date'] ?? '';                       // When does it end?
    $reason = sanitize_input($_POST['reason'] ?? '');           // Why do they need time off?
    $contact_info = sanitize_input($_POST['contact_info'] ?? ''); // How to reach them during leave

    // BASIC VALIDATION: Make sure all required fields are filled
    if (empty($leave_type_id) || empty($start_date) || empty($end_date) || empty($reason)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (strtotime($start_date) < strtotime(date('Y-m-d'))) {
        // PREVENT PAST DATES: You can't request leave for yesterday!
        $error_message = 'Start date cannot be in the past.';
    } elseif (strtotime($end_date) < strtotime($start_date)) {
        // LOGICAL DATE CHECK: End date must be after start date
        $error_message = 'End date cannot be before start date.';
    } else {
        // CALCULATE DAYS: How many days are they requesting?
        $total_days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1;  // Total calendar days
        $working_days = calculate_working_days($start_date, $end_date);                       // Working days (excludes weekends)

        // CHECK LEAVE BALANCE: Do they have enough days available?
        $leave_type = get_leave_type_by_id($leave_type_id);
        $balance = $leave_balances[$leave_type_id] ?? null;

        // BALANCE VALIDATION: Make sure they have enough days (except for unpaid leave)
        if ($leave_type['name'] !== 'Unpaid Leave' && $balance && $balance['remaining_days'] < $working_days) {
            $error_message = "Insufficient leave balance. You have {$balance['remaining_days']} days remaining for {$leave_type['name']}.";
        } else {
            // CHECK NOTICE PERIOD: Did they give enough advance notice?
            $notice_days = (strtotime($start_date) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
            if ($notice_days < $leave_type['min_notice_days']) {
                $error_message = "Minimum notice period of {$leave_type['min_notice_days']} days required for {$leave_type['name']}.";
            } else {
                // ALL CHECKS PASSED: Create the leave request
                $leave_data = [
                    'user_id' => $user['id'],
                    'leave_type_id' => $leave_type_id,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'total_days' => $total_days,
                    'working_days' => $working_days,
                    'reason' => $reason,
                    'contact_info' => $contact_info,
                    'status' => 'pending'
                ];

                $leave_id = db_insert('leaves', $leave_data);

                if ($leave_id) {
                    $success_message = "Leave request submitted successfully! Request ID: #L{$leave_id}";
                    log_activity($user['id'], 'Leave Request', "Submitted new leave request ({$leave_type['name']}, {$start_date} to {$end_date})");

                    // Clear form data
                    $_POST = [];
                } else {
                    $error_message = 'Failed to submit leave request. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KurdLeave — Apply for Leave</title>
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
        <td><b><a href="apply_leave.php"><i class="fas fa-plus-circle"></i> Apply Leave</a></b></td>
        <td><a href="my_leaves.php"><i class="fas fa-list-check"></i> My Leaves</a></td>
        <td><a href="calendar.php"><i class="fas fa-calendar"></i> Calendar</a></td>
        <td><a href="profile.php"><i class="fas fa-user"></i> Profile</a></td>
        <td><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></td>
      </tr>
    </table>

    <!-- Apply Leave Form -->
    <div class="content-panel">
      <div class="panel-heading text-center">
        <h2><i class="fas fa-plus-circle"></i> Apply for Leave</h2>
        <p>Submit your leave request for approval</p>
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

      <form action="" method="post" class="form-grid">
        <div>
          <label for="leave-type">Leave Type: <span style="color: red;">*</span></label>
          <select id="leave-type" name="leave_type" required>
            <option value="">-- Select Leave Type --</option>
            <?php foreach ($leave_types as $leave_type): ?>
              <?php
              $balance = $leave_balances[$leave_type['id']] ?? null;
              $remaining = $balance ? $balance['remaining_days'] : $leave_type['default_allocation'];
              $selected = (isset($_POST['leave_type']) && $_POST['leave_type'] == $leave_type['id']) ? 'selected' : '';
              ?>
              <option value="<?php echo $leave_type['id']; ?>" <?php echo $selected; ?>>
                <?php echo htmlspecialchars($leave_type['name']); ?>
                <?php if ($leave_type['name'] !== 'Unpaid Leave'): ?>
                  (<?php echo $remaining; ?> days available)
                <?php endif; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label for="start-date">Start Date: <span style="color: red;">*</span></label>
          <input type="date" id="start-date" name="start_date" required
                 min="<?php echo date('Y-m-d'); ?>"
                 value="<?php echo $_POST['start_date'] ?? ''; ?>">
        </div>

        <div>
          <label for="end-date">End Date: <span style="color: red;">*</span></label>
          <input type="date" id="end-date" name="end_date" required
                 min="<?php echo date('Y-m-d'); ?>"
                 value="<?php echo $_POST['end_date'] ?? ''; ?>">
        </div>

        <div>
          <label>Duration:</label>
          <div style="padding: 10px; background-color: #f8f9fa; border-radius: 4px;">
            <div><strong>Total Days:</strong> <span id="total-days">0</span></div>
            <div><strong>Working Days:</strong> <span id="working-days">0</span></div>
          </div>
        </div>

        <div class="grid-span-2">
          <label for="reason">Reason for Leave: <span style="color: red;">*</span></label>
          <textarea id="reason" name="reason" rows="3" required
                    placeholder="Please provide a reason for your leave request"><?php echo $_POST['reason'] ?? ''; ?></textarea>
        </div>

        <div class="grid-span-2">
          <label for="contact-info">Contact Information During Leave:</label>
          <textarea id="contact-info" name="contact_info" rows="2"
                    placeholder="How can you be contacted in case of emergency? (e.g., phone number, email)"><?php echo $_POST['contact_info'] ?? ''; ?></textarea>
        </div>

        <div class="grid-span-2">
          <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Important Notes:</strong>
            <ul style="margin: 10px 0 0 20px;">
              <li>Leave requests must be submitted at least 7 days in advance for Annual Leave</li>
              <li>Sick Leave can be applied retroactively with proper documentation</li>
              <li>All leave requests require manager approval</li>
              <li>You will receive an email notification once your request is processed</li>
            </ul>
          </div>
        </div>

        <div class="grid-span-2 text-center">
          <button type="submit" class="btn-success">
            <i class="fas fa-paper-plane"></i> Submit Leave Request
          </button>
          <button type="reset" class="btn-danger">
            <i class="fas fa-times"></i> Clear Form
          </button>
        </div>
      </form>
    </div>

    <!-- Current Leave Balances -->
    <div class="content-panel">
      <h2><i class="fas fa-chart-pie"></i> Your Current Leave Balances</h2>

      <div class="stats-container">
        <?php foreach ($leave_types as $leave_type): ?>
          <?php
          $balance = $leave_balances[$leave_type['id']] ?? null;
          $total = $balance ? $balance['total_allocation'] : $leave_type['default_allocation'];
          $used = $balance ? $balance['used_days'] : 0;
          $remaining = $balance ? $balance['remaining_days'] : $total;
          ?>
          <div class="stat-card">
            <div class="stat-label"><?php echo htmlspecialchars($leave_type['name']); ?></div>
            <div class="stat-value">
              <?php if ($leave_type['name'] === 'Unpaid Leave'): ?>
                Unlimited
              <?php else: ?>
                <?php echo $remaining; ?>/<?php echo $total; ?>
              <?php endif; ?>
            </div>
            <small>
              <?php if ($leave_type['name'] === 'Unpaid Leave'): ?>
                No limit
              <?php else: ?>
                <?php echo $used; ?> days used
              <?php endif; ?>
            </small>
          </div>
        <?php endforeach; ?>
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

      // Date calculation
      const startDate = document.getElementById('start-date');
      const endDate = document.getElementById('end-date');
      const totalDaysSpan = document.getElementById('total-days');
      const workingDaysSpan = document.getElementById('working-days');

      function calculateDays() {
        if (startDate.value && endDate.value) {
          const start = new Date(startDate.value);
          const end = new Date(endDate.value);

          if (end >= start) {
            const timeDiff = end.getTime() - start.getTime();
            const totalDays = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;

            // Calculate working days (exclude weekends)
            let workingDays = 0;
            let currentDate = new Date(start);

            while (currentDate <= end) {
              const dayOfWeek = currentDate.getDay();
              if (dayOfWeek !== 0 && dayOfWeek !== 6) { // Not Sunday (0) or Saturday (6)
                workingDays++;
              }
              currentDate.setDate(currentDate.getDate() + 1);
            }

            totalDaysSpan.textContent = totalDays;
            workingDaysSpan.textContent = workingDays;
          } else {
            totalDaysSpan.textContent = '0';
            workingDaysSpan.textContent = '0';
          }
        } else {
          totalDaysSpan.textContent = '0';
          workingDaysSpan.textContent = '0';
        }
      }

      startDate.addEventListener('change', calculateDays);
      endDate.addEventListener('change', calculateDays);

      // Update end date minimum when start date changes
      startDate.addEventListener('change', function() {
        endDate.min = startDate.value;
        if (endDate.value && endDate.value < startDate.value) {
          endDate.value = startDate.value;
        }
        calculateDays();
      });
    });
  </script>
</body>
</html>
