# KurdLeave - Employee Leave Management System

## 🎯 What This System Does (In Simple Terms)

KurdLeave is like a digital assistant for managing employee vacations and time off. Think of it as replacing those old paper forms and email chains with a clean, easy-to-use website where:

- **Employees** can request time off, see their vacation balance, and track their requests
- **Managers** can approve or deny requests from their team members
- **Administrators** can manage the entire system, add new users, and see reports

It's designed to make taking time off as smooth and transparent as possible for everyone involved.

## 🏗️ How It's Built (System Architecture)

This system is built like a well-organized office building with different floors for different purposes:

### 🏢 The Building Structure (File Organization)

```
KurdLeave/
├── 📁 php/              # The engine room - core system functions
│   ├── config.php       # Settings and database connection info
│   ├── database.php     # Database communication tools
│   └── functions.php    # Useful functions used throughout the app
├── 📁 admin/            # Admin control panel pages
├── 📁 user/             # Regular employee pages
├── 📁 database/         # Database setup files
├── 📁 admincss/         # Styling for admin pages
├── 📁 usercss/          # Styling for user pages
└── index.php            # Front door - decides where to send visitors
```

### 👥 Who Can Do What (User Roles)

**🔴 Administrator (Full Access)**

- Manage all users and departments
- Approve/reject any leave request
- View all reports and system statistics
- Configure system settings
- See all activity logs

**🟡 Manager (Department Access)**

- Approve/reject leave requests from their team
- View department reports
- Manage their own leave requests

**🟢 Employee (Personal Access)**

- Submit leave requests
- View their leave history and remaining balance
- Update their personal profile
- Check leave calendar

### 🗄️ How Data Is Stored (Database Structure)

The system uses several connected tables, like filing cabinets that reference each other:

- **Users**: Employee information, roles, departments
- **Departments**: Company departments (IT, HR, Sales, etc.)
- **Leave Types**: Different kinds of leave (vacation, sick, personal)
- **Leaves**: Individual leave requests with status and dates
- **Leave Balances**: How many days each person has left
- **Activity Logs**: Track of who did what when

### � Core System Functions (What The Code Does)

**Authentication System (`php/functions.php`)**

- `login()`: Checks if email/password are correct
- `is_admin()`, `is_manager()`: Determines user permissions
- `require_login()`: Blocks access if not logged in

**Leave Management**

- `get_pending_leaves()`: Find requests waiting for approval
- `calculate_working_days()`: Skip weekends when counting days
- `get_user_leave_balance()`: Check remaining vacation days

**Data Handling (`php/database.php`)**

- `db_fetch()`: Get one record from database
- `db_insert()`: Add new record
- `db_update()`: Modify existing record
- All functions use prepared statements for security

### 🌐 How Pages Work Together

**The Flow:**

1. **index.php** - Front door, decides where to send people
2. **user/login.php** - Login form, validates credentials
3. **admin/admin_dashboard.php** - Admin homepage with statistics
4. **user/home.php** - Employee homepage with personal info

**Modal System:**
Instead of separate pages for every action, we use popup modals:

- View leave details in a popup
- Approve/reject requests inline
- Better user experience, less page reloading
- **Leave Tracking** (`user/my_leaves.html`): Personal leave history with status tracking, document management, and filtering capabilities
- **Admin Leave Management** (`admin/admin_leaves.html`): System-wide leave policy configuration, leave type management, and request review

#### 📅 Calendar & Scheduling

- **Interactive Calendar** (`user/calendar.html`): Multi-view calendar system (month, week, team view) with export capabilities and filtering options
- **Holiday Management**: Public holiday configuration and company-specific holiday calendars

#### 👥 Administrative Features

- **Dashboard** (`admin/admin_dashboard.html`): Executive dashboard with key metrics, recent activity, and quick action items
- **Reports & Analytics** (`admin/admin_reports.html`): Comprehensive reporting system with customizable date ranges and export options
- **System Settings** (`admin/admin_settings.html`): Global system configuration including company details, notification settings, and security policies
- **Activity Logs** (`admin/admin_logs.html`): Complete audit trail with advanced filtering and security monitoring

## 🎨 Design & Technology Stack

### Frontend Technologies

- **HTML5**: Semantic, accessible markup structure
- **CSS3**: Modern styling with CSS Grid, Flexbox, and custom properties
- **JavaScript (ES6+)**: Interactive functionality and dynamic content management
- **Font Awesome**: Professional iconography
- **Responsive Design**: Mobile-first approach ensuring compatibility across all devices

### Styling Architecture

- **CSS Custom Properties**: Consistent design system with centralized color schemes and spacing
- **Component-Based Styling**: Modular CSS with reusable components
- **Professional Color Palette**: Enterprise-grade color scheme with status indicators
- **Typography**: Roboto font family for optimal readability

## 📁 Project Structure

```
KurdLeave/
├── README.md                     # Project documentation
├── layout.sh                     # Directory structure utility script
├── user/                         # Employee-facing interface
│   ├── home.html                # Employee dashboard
│   ├── login.html               # Authentication page
│   ├── apply_leave.html         # Leave application form
│   ├── my_leaves.html           # Leave history and management
│   ├── calendar.html            # Interactive calendar view
│   └── profile.html             # User profile management
├── admin/                        # Administrative interface
│   ├── admin_dashboard.html     # Admin dashboard
│   ├── admin_users.html         # User management
│   ├── admin_leaves.html        # Leave policy and management
│   ├── admin_reports.html       # Analytics and reporting
│   ├── admin_settings.html      # System configuration
│   └── admin_logs.html          # Activity logging and audit
├── usercss/                      # Employee interface styling
│   └── user-styles.css          # Comprehensive user UI styles
└── admincss/                     # Administrative interface styling
    └── admin-styles.css         # Administrative panel styles
```

## ✨ Key Features

### Employee Features

- **Intuitive Leave Application**: Multi-step form with validation, file upload capabilities, and real-time balance checking
- **Leave Balance Tracking**: Visual representation of available, used, and pending leave balances
- **Calendar Integration**: Personal and team calendar views with export to popular calendar applications
- **Document Management**: Secure upload and management of supporting documents (medical certificates, etc.)
- **Notification Preferences**: Customizable email and in-app notification settings

### Manager Features

- **Team Overview**: Comprehensive view of team members' leave schedules and requests
- **Approval Workflow**: Streamlined approval process with commenting and decision tracking
- **Conflict Detection**: Automatic identification of scheduling conflicts and team coverage issues

### Administrative Features

- **User Lifecycle Management**: Complete user onboarding, modification, and offboarding processes
- **Policy Configuration**: Flexible leave policy setup with customizable leave types, accrual rules, and approval workflows
- **Advanced Reporting**: Detailed analytics on leave patterns, departmental trends, and compliance metrics
- **System Monitoring**: Comprehensive activity logging with security alerts and audit trails
- **Integration Capabilities**: Export functionality for payroll systems and HR platforms

## 🚀 Development Status

**Current Phase**: Frontend Development (Complete)

- ✅ Complete UI/UX implementation
- ✅ Responsive design across all screen sizes
- ✅ Role-based navigation and access control simulation
- ✅ Interactive JavaScript functionality
- ✅ Professional styling and branding

**Next Phase**: Backend Integration

- 🔄 Database design and implementation
- 🔄 API development for all CRUD operations
- 🔄 Authentication and authorization system
- 🔄 Email notification system
- 🔄 File upload and document management
- 🔄 Reporting and analytics engine

## 💼 Business Value

KurdLeave addresses critical organizational needs by:

- **Reducing Administrative Overhead**: Automated workflows eliminate manual leave tracking
- **Improving Compliance**: Built-in audit trails and policy enforcement ensure regulatory compliance
- **Enhancing Employee Experience**: Self-service capabilities and transparent processes improve satisfaction
- **Enabling Data-Driven Decisions**: Comprehensive analytics support strategic workforce planning
- **Ensuring Business Continuity**: Advanced scheduling tools prevent coverage gaps and conflicts

## 🎯 Target Audience

- **Small to Medium Enterprises**: Organizations seeking to digitize their leave management processes
- **HR Departments**: Teams requiring efficient tools for policy enforcement and employee management
- **Remote/Hybrid Organizations**: Companies needing transparent, accessible leave management systems
- **Compliance-Focused Industries**: Organizations requiring detailed audit trails and reporting capabilities

---

_This project represents a complete frontend implementation of an enterprise-grade leave management system, ready for backend integration and deployment._

## 🧮 Key Algorithms Explained

### Working Days Calculation

When someone requests leave, we need to figure out how many actual working days they're taking off:

```php
function calculate_working_days($start_date, $end_date) {
    // Create date objects for start and end
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $end = $end->modify('+1 day'); // Include the end date

    $working_days = 0;
    // Loop through each day in the range
    foreach ($period as $date) {
        // Skip weekends (Saturday = 6, Sunday = 0)
        if ($date->format('w') != 0 && $date->format('w') != 6) {
            $working_days++; // Count this as a working day
        }
    }
    return $working_days;
}
```

**Why this matters**: If someone takes off Friday through Monday, they only use 2 vacation days, not 4.

### Leave Balance Updates

When a leave request gets approved, we automatically update the person's remaining balance:

```php
// If approved, subtract days from their balance
if ($status === 'approved') {
    $new_used = $balance['used_days'] + $leave['working_days'];
    $new_remaining = $balance['remaining_days'] - $leave['working_days'];
    // Update their balance (but never go below 0)
    $new_remaining = max(0, $new_remaining);
}
```

### Session Management

The system keeps track of who's logged in using PHP sessions:

```php
// When someone logs in successfully
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_role'] = $user['role'];

// Check if they're still logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}
```

## 🔒 Security Features

**Input Sanitization**: All user input is cleaned to prevent attacks

```php
function sanitize_input($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
```

**Prepared Statements**: Database queries use placeholders to prevent SQL injection

```php
$user = db_fetch("SELECT * FROM users WHERE email = ? AND status = 'active'", [$email]);
```

**Role-Based Access**: Users can only access pages appropriate for their role

```php
function require_admin() {
    require_login(); // Make sure they're logged in first
    if (!is_admin()) {
        redirect('/user/home.php'); // Send them away if not admin
    }
}
```

**Password Hashing**: Passwords are never stored in plain text

```php
// When creating account
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// When logging in
if (password_verify($password, $user['password'])) {
    // Password is correct
}
```

## 🚀 How to Use the System

### For Administrators

1. **Login** with admin credentials at `/user/login.php`
2. **Dashboard** shows key statistics and pending requests
3. **Approve/Reject** leaves by clicking "Review" on any request
4. **Manage Users** through the user management section
5. **View Reports** to track leave usage across the company

### For Employees

1. **Login** with your employee credentials
2. **View Dashboard** to see your leave balance and history
3. **Apply for Leave** using the leave request form
4. **Track Requests** to see approval status
5. **Update Profile** to keep your information current

### For Managers

1. **Login** with manager credentials
2. **Approve Team Requests** from your department
3. **View Team Reports** to track your department's leave usage
4. **Manage Own Leave** just like regular employees

## 🛠️ Technical Implementation Details

### Database Relationships

- **Users** belong to **Departments**
- **Users** have multiple **Leave Balances** (one per leave type per year)
- **Users** submit **Leaves** (requests) that reference **Leave Types**
- **Activity Logs** track all user actions

### Error Handling

The system gracefully handles errors:

- Database connection failures show user-friendly messages
- Invalid login attempts are logged for security
- Missing required fields are clearly highlighted
- System errors are logged for administrator review

### Performance Considerations

- **Connection Pooling**: Single database connection shared across requests
- **Efficient Queries**: Join tables to get all needed data in one query
- **Minimal JavaScript**: Fast page loads with progressive enhancement
- **Responsive Design**: Works well on phones, tablets, and computers

## 📊 Key Features Summary

✅ **Complete Leave Management**: Request, approve, track, and report on all leave
✅ **Role-Based Access**: Different interfaces for different user types
✅ **Real-Time Updates**: Instant feedback on leave request status changes
✅ **Automatic Calculations**: Working days, leave balances, and statistics
✅ **Activity Logging**: Complete audit trail of all system actions
✅ **Modal Interface**: Smooth user experience with popup forms
✅ **Responsive Design**: Works on desktop, tablet, and mobile devices
✅ **Security First**: Input validation, prepared statements, and role checks

## 🎯 The Bottom Line

This system replaces manual leave management with an automated, secure, and user-friendly web application. It reduces administrative overhead, improves transparency, and provides valuable insights into leave patterns across the organization.

The code is written to be maintainable and understandable, with comprehensive comments explaining not just what each function does, but why it's needed and how it fits into the bigger picture.
