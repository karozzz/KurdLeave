-- KurdLeave Database Setup
-- This file creates all the tables we need for our leave management system
-- Think of it as building the filing cabinet structure before we put any files in it

-- These tables work together like this:
-- 1. Users (employees) belong to Departments
-- 2. Users have Leave_Balances for different Leave_Types each year
-- 3. Users submit Leaves (requests) which get approved/rejected
-- 4. Activity_Logs track what everyone does in the system

CREATE DATABASE IF NOT EXISTS kurdleave;
USE kurdleave;

-- DEPARTMENTS TABLE - Different departments in the company (IT, HR, Sales, etc.)
CREATE TABLE departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,           -- Like "Engineering" or "Human Resources"
    description TEXT,                     -- What this department does
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- LEAVE TYPES TABLE - Different kinds of leave (vacation, sick leave, personal days)
CREATE TABLE leave_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,            -- Like "Annual Leave" or "Sick Leave"
    default_allocation INT DEFAULT 0,     -- How many days per year do people get?
    carry_forward_limit INT DEFAULT 0,    -- How many unused days can carry over to next year?
    min_notice_days INT DEFAULT 0,        -- How many days notice required?
    requires_documentation BOOLEAN DEFAULT FALSE, -- Do they need a doctor's note?
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- USERS TABLE - All the employees in the system
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id VARCHAR(20) UNIQUE NOT NULL, -- Their employee number
    name VARCHAR(100) NOT NULL,              -- Their full name
    email VARCHAR(100) UNIQUE NOT NULL,      -- Their email address (used for login)
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    emergency_contact VARCHAR(100),
    emergency_phone VARCHAR(20),
    department_id INT,
    manager_id INT,
    role ENUM('admin', 'manager', 'employee') DEFAULT 'employee',
    join_date DATE,
    status ENUM('active', 'inactive', 'pending') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (manager_id) REFERENCES users(id)
);

-- Leave Balances Table
CREATE TABLE leave_balances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    year YEAR NOT NULL,
    total_allocation INT DEFAULT 0,
    used_days DECIMAL(4,1) DEFAULT 0,
    remaining_days DECIMAL(4,1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id),
    UNIQUE KEY unique_user_leave_year (user_id, leave_type_id, year)
);

-- Leaves Table
CREATE TABLE leaves (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days DECIMAL(4,1) NOT NULL,
    working_days DECIMAL(4,1) NOT NULL,
    reason TEXT,
    contact_info TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    manager_comments TEXT,
    admin_comments TEXT,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- Holidays Table
CREATE TABLE holidays (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    type ENUM('public', 'company', 'optional') DEFAULT 'public',
    applies_to VARCHAR(50) DEFAULT 'all',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Activity Logs Table
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
