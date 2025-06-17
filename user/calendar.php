<?php
// user/calendar.php - Team Calendar

require_once '../php/functions.php';

// Require login
require_login();

// Get current user data
$user = get_logged_in_user();

// Get current month and year
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Ensure valid month/year
if ($month < 1 || $month > 12) $month = date('n');
if ($year < 2020 || $year > 2030) $year = date('Y');

// Get team members (same department)
$team_members = [];
if ($user['department_id']) {
    $team_members = db_fetch_all("
        SELECT id, name, employee_id
        FROM users
        WHERE department_id = ? AND status = 'active' AND id != ?
        ORDER BY name
    ", [$user['department_id'], $user['id']]);
}

// Add current user to the list
array_unshift($team_members, [
    'id' => $user['id'],
    'name' => $user['name'] . ' (Me)',
    'employee_id' => $user['employee_id']
]);

// Get all team member IDs
$team_ids = array_column($team_members, 'id');

// Get leave data for the month
$start_date = date('Y-m-01', mktime(0, 0, 0, $month, 1, $year));
$end_date = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));

$leaves = [];
if (!empty($team_ids)) {
    $placeholders = str_repeat('?,', count($team_ids) - 1) . '?';
    $leaves = db_fetch_all("
        SELECT l.*, u.name as user_name, lt.name as leave_type_name, lt.id as leave_type_id
        FROM leaves l
        JOIN users u ON l.user_id = u.id
        JOIN leave_types lt ON l.leave_type_id = lt.id
        WHERE l.user_id IN ({$placeholders})
        AND l.status = 'approved'
        AND (
            (l.start_date <= ? AND l.end_date >= ?) OR
            (l.start_date BETWEEN ? AND ?) OR
            (l.end_date BETWEEN ? AND ?)
        )
        ORDER BY l.start_date
    ", array_merge($team_ids, [$end_date, $start_date, $start_date, $end_date, $start_date, $end_date]));
}

// Get holidays for the month
$holidays = db_fetch_all("
    SELECT * FROM holidays
    WHERE date BETWEEN ? AND ?
    ORDER BY date
", [$start_date, $end_date]);

// Create calendar data structure
$calendar_days = [];
$first_day = date('w', mktime(0, 0, 0, $month, 1, $year)); // 0 = Sunday
$days_in_month = date('t', mktime(0, 0, 0, $month, 1, $year));

// Add previous month days to fill the first week
for ($i = $first_day - 1; $i >= 0; $i--) {
    $prev_day = date('j', mktime(0, 0, 0, $month, 0 - $i, $year));
    $calendar_days[] = [
        'day' => $prev_day,
        'current_month' => false,
        'date' => date('Y-m-d', mktime(0, 0, 0, $month, 0 - $i, $year))
    ];
}

// Add current month days
for ($day = 1; $day <= $days_in_month; $day++) {
    $calendar_days[] = [
        'day' => $day,
        'current_month' => true,
        'date' => date('Y-m-d', mktime(0, 0, 0, $month, $day, $year))
    ];
}

// Add next month days to fill the last week
$remaining_days = 42 - count($calendar_days); // 6 weeks * 7 days
for ($day = 1; $day <= $remaining_days; $day++) {
    $calendar_days[] = [
        'day' => $day,
        'current_month' => false,
        'date' => date('Y-m-d', mktime(0, 0, 0, $month + 1, $day, $year))
    ];
}

// Navigation dates
$prev_month = $month == 1 ? 12 : $month - 1;
$prev_year = $month == 1 ? $year - 1 : $year;
$next_month = $month == 12 ? 1 : $month + 1;
$next_year = $month == 12 ? $year + 1 : $year;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KurdLeave — Team Calendar</title>
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
        <td><a href="my_leaves.php"><i class="fas fa-list-check"></i> My Leaves</a></td>
        <td><b><a href="calendar.php"><i class="fas fa-calendar"></i> Calendar</a></b></td>
        <td><a href="profile.php"><i class="fas fa-user"></i> Profile</a></td>
        <td><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></td>
      </tr>
    </table>

    <!-- Calendar Header -->
    <div class="content-panel">
      <div class="panel-heading text-center">
        <h2><i class="fas fa-calendar"></i> Team Calendar</h2>
        <p>View your team's leave schedule</p>
      </div>

      <!-- Calendar Navigation -->
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="btn">
          <i class="fas fa-chevron-left"></i> Previous
        </a>

        <h3 style="margin: 0;"><?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></h3>

        <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="btn">
          Next <i class="fas fa-chevron-right"></i>
        </a>
      </div>

      <!-- Calendar Filters -->
      <div class="calendar-filters">
        <div class="calendar-filter filter-annual active">
          <span style="background-color: #27ae60;"></span> Annual Leave
        </div>
        <div class="calendar-filter filter-sick">
          <span style="background-color: #e74c3c;"></span> Sick Leave
        </div>
        <div class="calendar-filter filter-personal">
          <span style="background-color: #f39c12;"></span> Personal Days
        </div>
        <div class="calendar-filter filter-holiday">
          <span style="background-color: #3498db;"></span> Holidays
        </div>
      </div>
    </div>

    <!-- Calendar Grid -->
    <div class="content-panel">
      <div class="calendar-container">
        <table style="width: 100%; border-collapse: collapse;">
          <thead>
            <tr>
              <th style="padding: 15px; text-align: center; background-color: var(--primary-color); color: white;">Sunday</th>
              <th style="padding: 15px; text-align: center; background-color: var(--primary-color); color: white;">Monday</th>
              <th style="padding: 15px; text-align: center; background-color: var(--primary-color); color: white;">Tuesday</th>
              <th style="padding: 15px; text-align: center; background-color: var(--primary-color); color: white;">Wednesday</th>
              <th style="padding: 15px; text-align: center; background-color: var(--primary-color); color: white;">Thursday</th>
              <th style="padding: 15px; text-align: center; background-color: var(--primary-color); color: white;">Friday</th>
              <th style="padding: 15px; text-align: center; background-color: var(--primary-color); color: white;">Saturday</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $week_count = 0;
            for ($i = 0; $i < count($calendar_days); $i += 7):
              $week_count++;
            ?>
              <tr>
                <?php for ($j = 0; $j < 7; $j++): ?>
                  <?php
                  $day_index = $i + $j;
                  if ($day_index < count($calendar_days)):
                    $day_data = $calendar_days[$day_index];
                    $is_today = $day_data['date'] === date('Y-m-d');
                    $is_weekend = $j == 0 || $j == 6; // Sunday or Saturday

                    // Get events for this day
                    $day_leaves = array_filter($leaves, function($leave) use ($day_data) {
                      return $day_data['date'] >= $leave['start_date'] && $day_data['date'] <= $leave['end_date'];
                    });

                    $day_holidays = array_filter($holidays, function($holiday) use ($day_data) {
                      return $holiday['date'] === $day_data['date'];
                    });
                  ?>
                    <td style="
                      height: 120px;
                      vertical-align: top;
                      padding: 5px;
                      border: 1px solid #ddd;
                      background-color: <?php echo !$day_data['current_month'] ? '#f9f9f9' : ($is_today ? '#e3f2fd' : 'white'); ?>;
                      <?php echo $is_weekend ? 'background-color: #fafafa;' : ''; ?>
                    ">
                      <div style="
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 5px;
                      ">
                        <span style="
                          font-weight: bold;
                          color: <?php echo !$day_data['current_month'] ? '#ccc' : ($is_today ? '#1976d2' : '#333'); ?>;
                        ">
                          <?php echo $day_data['day']; ?>
                        </span>
                        <?php if ($is_today): ?>
                          <span style="
                            background-color: #1976d2;
                            color: white;
                            padding: 2px 6px;
                            border-radius: 10px;
                            font-size: 0.7rem;
                          ">Today</span>
                        <?php endif; ?>
                      </div>

                      <!-- Holidays -->
                      <?php foreach ($day_holidays as $holiday): ?>
                        <div class="calendar-event holiday" style="
                          background-color: rgba(52, 152, 219, 0.2);
                          border-left: 3px solid #3498db;
                          margin: 2px 0;
                          padding: 2px 4px;
                          font-size: 0.75rem;
                          border-radius: 3px;
                        ">
                          🎉 <?php echo htmlspecialchars($holiday['name']); ?>
                        </div>
                      <?php endforeach; ?>

                      <!-- Leave Events -->
                      <?php foreach ($day_leaves as $leave): ?>
                        <?php
                        $color = '';
                        $icon = '';
                        switch ($leave['leave_type_id']) {
                          case 1: // Annual Leave
                            $color = 'rgba(39, 174, 96, 0.2)';
                            $border_color = '#27ae60';
                            $icon = '🏖️';
                            break;
                          case 2: // Sick Leave
                            $color = 'rgba(231, 76, 60, 0.2)';
                            $border_color = '#e74c3c';
                            $icon = '🤒';
                            break;
                          default: // Other leaves
                            $color = 'rgba(243, 156, 18, 0.2)';
                            $border_color = '#f39c12';
                            $icon = '📅';
                        }
                        ?>
                        <div class="calendar-event" style="
                          background-color: <?php echo $color; ?>;
                          border-left: 3px solid <?php echo $border_color; ?>;
                          margin: 2px 0;
                          padding: 2px 4px;
                          font-size: 0.75rem;
                          border-radius: 3px;
                          cursor: pointer;
                        " onclick="showLeaveDetails(<?php echo htmlspecialchars(json_encode($leave)); ?>)">
                          <?php echo $icon; ?> <?php echo htmlspecialchars($leave['user_name']); ?>
                          <?php if ($leave['user_id'] == $user['id']): ?>
                            <strong>(Me)</strong>
                          <?php endif; ?>
                        </div>
                      <?php endforeach; ?>
                    </td>
                  <?php else: ?>
                    <td></td>
                  <?php endif; ?>
                <?php endfor; ?>
              </tr>
            <?php endfor; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Team Members -->
    <div class="content-panel">
      <h3><i class="fas fa-users"></i> Team Members</h3>

      <div class="stats-container">
        <?php foreach ($team_members as $member): ?>
          <?php
          // Count leaves for this member this month
          $member_leaves = array_filter($leaves, function($leave) use ($member) {
            return $leave['user_id'] == $member['id'];
          });
          ?>
          <div class="stat-card">
            <div class="stat-label">
              <?php echo htmlspecialchars($member['name']); ?>
              <br><small><?php echo htmlspecialchars($member['employee_id']); ?></small>
            </div>
            <div class="stat-value"><?php echo count($member_leaves); ?></div>
            <small>leave days this month</small>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Legend -->
    <div class="content-panel">
      <h3><i class="fas fa-info-circle"></i> Calendar Legend</h3>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <div>
          <h4>Leave Types</h4>
          <div style="margin-bottom: 8px;">
            🏖️ <span style="background-color: rgba(39, 174, 96, 0.2); padding: 2px 8px; border-radius: 3px;">Annual Leave</span>
          </div>
          <div style="margin-bottom: 8px;">
            🤒 <span style="background-color: rgba(231, 76, 60, 0.2); padding: 2px 8px; border-radius: 3px;">Sick Leave</span>
          </div>
          <div style="margin-bottom: 8px;">
            📅 <span style="background-color: rgba(243, 156, 18, 0.2); padding: 2px 8px; border-radius: 3px;">Other Leave</span>
          </div>
        </div>
        <div>
          <h4>Special Days</h4>
          <div style="margin-bottom: 8px;">
            🎉 <span style="background-color: rgba(52, 152, 219, 0.2); padding: 2px 8px; border-radius: 3px;">Company Holidays</span>
          </div>
          <div style="margin-bottom: 8px;">
            📅 <span style="background-color: #e3f2fd; padding: 2px 8px; border-radius: 3px;">Today</span>
          </div>
          <div style="margin-bottom: 8px;">
            📅 <span style="background-color: #fafafa; padding: 2px 8px; border-radius: 3px;">Weekends</span>
          </div>
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
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 id="modalTitle">Leave Details</h3>
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

      // Calendar filter functionality
      const filters = document.querySelectorAll('.calendar-filter');
      filters.forEach(filter => {
        filter.addEventListener('click', function() {
          this.classList.toggle('active');

          // Get filter type
          const filterType = this.classList.contains('filter-annual') ? 'annual' :
                           this.classList.contains('filter-sick') ? 'sick' :
                           this.classList.contains('filter-personal') ? 'personal' : 'holiday';

          // Toggle visibility of events
          const events = document.querySelectorAll('.calendar-event');
          events.forEach(event => {
            if (event.classList.contains(filterType) ||
                (filterType === 'annual' && event.textContent.includes('🏖️')) ||
                (filterType === 'sick' && event.textContent.includes('🤒')) ||
                (filterType === 'personal' && event.textContent.includes('📅') && !event.textContent.includes('🎉')) ||
                (filterType === 'holiday' && event.textContent.includes('🎉'))) {
              event.style.display = this.classList.contains('active') ? 'block' : 'none';
            }
          });
        });
      });
    });

    function showLeaveDetails(leave) {
      document.getElementById('modalTitle').textContent = leave.user_name + "'s Leave";
      document.getElementById('modalContent').innerHTML = `
        <table style="width: 100%; border-collapse: collapse;">
          <tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Employee:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">${leave.user_name}</td></tr>
          <tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Leave Type:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">${leave.leave_type_name}</td></tr>
          <tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Start Date:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">${leave.start_date}</td></tr>
          <tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>End Date:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">${leave.end_date}</td></tr>
          <tr><td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Duration:</strong></td><td style="padding: 8px; border-bottom: 1px solid #eee;">${leave.working_days} working days</td></tr>
          <tr><td style="padding: 8px;"><strong>Reason:</strong></td><td style="padding: 8px;">${leave.reason || 'No reason provided'}</td></tr>
        </table>
        <div style="margin-top: 1rem; text-align: center;">
          <button onclick="closeModal()" class="btn">Close</button>
        </div>
      `;
      document.getElementById('leaveModal').style.display = 'block';
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
