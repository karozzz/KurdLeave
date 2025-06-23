
USE kurdleave;


INSERT INTO departments (name, description) VALUES
('Engineering', 'Software development and technical operations'),
('Human Resources', 'Employee management and organizational development'),
('Finance', 'Financial planning and accounting'),
('Sales', 'Sales and customer relations'),
('Marketing', 'Marketing and brand management');

-- Insert Leave Types
INSERT INTO leave_types (name, default_allocation, carry_forward_limit, min_notice_days, requires_documentation, status) VALUES
('Annual Leave', 20, 5, 7, FALSE, 'active'),
('Sick Leave', 12, 0, 0, TRUE, 'active'),
('Personal Days', 4, 0, 1, FALSE, 'active'),
('Bereavement Leave', 5, 0, 0, FALSE, 'active'),
('Study Leave', 10, 0, 14, TRUE, 'active'),
('Parental Leave', 90, 0, 30, TRUE, 'active'),
('Unpaid Leave', 0, 0, 14, FALSE, 'active');



INSERT INTO users (employee_id, name, email, password, department_id, role, join_date, status) VALUES
('ADM001', 'System Administrator', 'admin@gmail.com', '$2y$10$X1tR6MoxEF/S13v4Pr6YkuBri2M/tHKisJpNkJX91sDYKqkv7/vCK', 2, 'admin', '2023-01-01', 'active');

INSERT INTO users (employee_id, name, email, password, phone, emergency_contact, emergency_phone, department_id, manager_id, role, join_date, status) VALUES
('EMP010', 'Rawa Dara', 'rawa@gmail.com', '$2y$10$X1tR6MoxEF/S13v4Pr6YkuBri2M/tHKisJpNkJX91sDYKqkv7/vCK', '555-123-4567', 'Aland Fryad', '555-987-6543', 1, NULL, x'manager', '2023-01-15', 'active'),
('EMP023', 'Michael Brown', 'michael@gmail.com', '$2y$10$X1tR6MoxEF/S13v4Pr6YkuBri2M/tHKisJpNkJX91sDYKqkv7/vCK', '555-234-5678', 'Emergency Contact', '555-876-5432', 1, 2, 'employee', '2023-02-01', 'active'),
('EMP015', 'Karoz Rebaz', 'karoz@gmail.com', '$2y$10$X1tR6MoxEF/S13v4Pr6YkuBri2M/tHKisJpNkJX91sDYKqkv7/vCK', '555-345-6789', 'Family Contact', '555-765-4321', 1, 2, 'employee', '2023-02-15', 'active'),
('EMP030', 'Jane Smith', 'jane.smith@gmail.com', '$2y$10$X1tR6MoxEF/S13v4Pr6YkuBri2M/tHKisJpNkJX91sDYKqkv7/vCK', '555-456-7890', 'Emergency', '555-654-3210', 2, NULL, 'manager', '2023-01-10', 'active'),
('EMP040', 'David Miller', 'david@gmail.com', '$2y$10$X1tR6MoxEF/S13v4Pr6YkuBri2M/tHKisJpNkJX91sDYKqkv7/vCK', '555-567-8901', 'Contact', '555-543-2109', 3, NULL, 'manager', '2023-01-20', 'active'),
('EMP050', 'Jennifer Wilson', 'jennifer@gmail.com', '$2y$10$X1tR6MoxEF/S13v4Pr6YkuBri2M/tHKisJpNkJX91sDYKqkv7/vCK', '555-678-9012', 'Emergency', '555-432-1098', 4, NULL, 'manager', '2023-01-25', 'active'),
('EMP025', 'Aland Fryad', 'aland@gmail.com', '$2y$10$X1tR6MoxEF/S13v4Pr6YkuBri2M/tHKisJpNkJX91sDYKqkv7/vCK', '555-789-0123', 'Contact', '555-321-0987', 4, 6, 'employee', '2023-04-28', 'pending');

-- Insert Leave Balances for 2025
INSERT INTO leave_balances (user_id, leave_type_id, year, total_allocation, used_days, remaining_days) VALUES
-- Rawa Dara (EMP010)
(2, 1, 2025, 20, 5, 15),
(2, 2, 2025, 12, 2, 10),
(2, 3, 2025, 4, 1, 3),
-- Michael Brown (EMP023)
(3, 1, 2025, 20, 5, 15),
(3, 2, 2025, 12, 2, 10),
(3, 3, 2025, 4, 0, 4),
-- Karoz Rebaz (EMP015)
(4, 1, 2025, 20, 3, 17),
(4, 2, 2025, 12, 1, 11),
(4, 3, 2025, 4, 0, 4);

-- Insert Sample Leave Requests
INSERT INTO leaves (user_id, leave_type_id, start_date, end_date, total_days, working_days, reason, contact_info, status, approved_by, approved_at) VALUES
(3, 1, '2025-05-10', '2025-05-15', 6, 4, 'Family vacation', 'Via email only for emergencies. Personal email: michael.brown@personalemail.com', 'approved', 2, '2025-04-27 09:10:00'),
(2, 1, '2025-02-12', '2025-02-16', 5, 5, 'Personal time off', 'Available via phone for urgent matters', 'approved', 1, '2025-02-10 14:30:00'),
(2, 2, '2025-01-08', '2025-01-09', 2, 2, 'Flu symptoms', 'Resting at home', 'approved', 1, '2025-01-08 10:15:00');

-- Insert Holidays
INSERT INTO holidays (name, date, type, applies_to, description) VALUES
('Memorial Day', '2025-05-26', 'public', 'all', 'National Memorial Day'),
('Independence Day', '2025-07-04', 'public', 'all', 'Independence Day celebration'),
('Labor Day', '2025-09-01', 'public', 'all', 'International Labor Day'),
('Company Foundation Day', '2025-10-15', 'company', 'all', 'Anniversary of company founding'),
('Thanksgiving Day', '2025-11-27', 'public', 'all', 'Thanksgiving celebration');

-- Insert Activity Logs
INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES
(4, 'Login', 'User login successful', '192.168.1.105'),
(2, 'Leave Approval', 'Approved leave request #L125 for Michael Brown', '192.168.1.87'),
(3, 'Leave Request', 'Submitted new leave request (Annual Leave, May 10-15, 2025)', '192.168.1.92'),
(1, 'User Management', 'Created new user: Aland Fryad (aland@gmail.com)', '192.168.1.100'),
(1, 'Login', 'Administrator login successful', '192.168.1.100');
