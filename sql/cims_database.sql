-- ============================================================
-- DELIVERANCE CHURCH INFORMATION MANAGEMENT SYSTEM (CIMS)
-- Database Schema for XAMPP MySQL
-- ============================================================

CREATE DATABASE IF NOT EXISTS cims_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cims_db;

-- ── USERS ──────────────────────────────────────────────────
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('System Administrator','Finance Officer','Church Leader','Church Member','Event Coordinator') NOT NULL DEFAULT 'Church Member',
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── CELL GROUPS ────────────────────────────────────────────
CREATE TABLE cell_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    leader_id INT,
    meeting_day VARCHAR(20),
    meeting_location VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── MEMBERS ────────────────────────────────────────────────
CREATE TABLE members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(60) NOT NULL,
    last_name VARCHAR(60) NOT NULL,
    phone VARCHAR(30),
    email VARCHAR(100),
    date_of_birth DATE,
    gender ENUM('Male','Female','Other'),
    address VARCHAR(200),
    group_id INT,
    registration_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES cell_groups(id) ON DELETE SET NULL
);

ALTER TABLE cell_groups ADD CONSTRAINT fk_leader FOREIGN KEY (leader_id) REFERENCES members(id) ON DELETE SET NULL;

-- ── FINANCIAL TRANSACTIONS ─────────────────────────────────
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    type ENUM('Tithe','Offering','Donation','Pledge') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    transaction_date DATE NOT NULL,
    notes TEXT,
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ── EVENTS ─────────────────────────────────────────────────
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME,
    location VARCHAR(200),
    category ENUM('Worship Service','Youth Meeting','Prayer Night','Conference','Outreach','Other') DEFAULT 'Worship Service',
    description TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ── ATTENDANCE ─────────────────────────────────────────────
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    member_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('Present','Absent','Excused') NOT NULL DEFAULT 'Present',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

-- ── ANNOUNCEMENTS ──────────────────────────────────────────
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('normal','info','urgent') DEFAULT 'normal',
    posted_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ── PRAYER REQUESTS ────────────────────────────────────────
CREATE TABLE prayer_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_name VARCHAR(100) DEFAULT 'Anonymous',
    category ENUM('Healing','Family','Finances','Guidance','Thanksgiving','Other') DEFAULT 'Other',
    request TEXT NOT NULL,
    visibility ENUM('leaders','public') DEFAULT 'leaders',
    status ENUM('Pending','Prayed For','Answered') DEFAULT 'Pending',
    member_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL
);

-- ══════════════════════════════════════════════════════════
-- SEED DATA
-- ══════════════════════════════════════════════════════════

-- Users (password = 'password' bcrypt hashed)
INSERT INTO users (full_name, username, password, role) VALUES
('System Admin',     'admin',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator'),
('Mary Finances',    'mfinance', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Finance Officer'),
('Pastor John',      'pjohn',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Church Leader'),
('Sarah Namukasa',   'snamukasa','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Church Member'),
('Events Team',      'events',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Event Coordinator');

-- Cell Groups (leaders assigned after members inserted)
INSERT INTO cell_groups (name, meeting_day, meeting_location) VALUES
('Nagongera East Cell', 'Wednesday', 'Namukasa Home'),
('Tororo Central Cell',  'Thursday',  'Church Hall B'),
('Youth Cell Group',     'Saturday',  'Youth Annex');

-- Members
INSERT INTO members (first_name, last_name, phone, email, date_of_birth, gender, address, group_id, registration_date, status) VALUES
('Sarah',  'Namukasa',  '+256 700 111111', 'sarah@example.com',  '1990-03-12', 'Female', 'Nagongera', 1, '2024-01-15', 'Active'),
('Peter',  'Okello',    '+256 701 222222', 'peter@example.com',  '1985-07-22', 'Male',   'Tororo',    2, '2024-02-03', 'Active'),
('Grace',  'Apio',      '+256 702 333333', 'grace@example.com',  '1995-11-05', 'Female', 'Nagongera', 1, '2024-02-20', 'Active'),
('James',  'Wanyama',   '+256 703 444444', 'james@example.com',  '1978-04-18', 'Male',   'Tororo',    3, '2024-03-10', 'Active'),
('Ruth',   'Aciro',     '+256 704 555555', 'ruth@example.com',   '1992-08-30', 'Female', 'Nagongera', 2, '2024-04-05', 'Active'),
('David',  'Mugisha',   '+256 705 666666', 'david@example.com',  '1988-12-14', 'Male',   'Busia',     3, '2024-05-12', 'Inactive'),
('Esther', 'Chemutai',  '+256 706 777777', 'esther@example.com', '1999-06-08', 'Female', 'Nagongera', 1, '2024-08-01', 'Active'),
('Moses',  'Opio',      '+256 707 888888', 'moses@example.com',  '1975-01-25', 'Male',   'Tororo',    2, '2024-09-15', 'Active');

-- Update cell group leaders
UPDATE cell_groups SET leader_id = 1 WHERE id = 1;
UPDATE cell_groups SET leader_id = 2 WHERE id = 2;
UPDATE cell_groups SET leader_id = 5 WHERE id = 3;

-- Transactions
INSERT INTO transactions (member_id, type, amount, transaction_date, notes, recorded_by) VALUES
(1, 'Tithe',    150000, '2025-04-01', '',               2),
(2, 'Offering',  50000, '2025-04-01', '',               2),
(3, 'Tithe',    200000, '2025-04-07', '',               2),
(4, 'Donation', 500000, '2025-04-07', 'Building fund',  2),
(5, 'Offering',  30000, '2025-04-14', '',               2),
(1, 'Tithe',    150000, '2025-04-14', '',               2),
(2, 'Tithe',    120000, '2025-04-21', '',               2),
(3, 'Offering',  75000, '2025-04-21', '',               2),
(7, 'Tithe',     80000, '2025-04-28', '',               2),
(8, 'Pledge',  1000000, '2025-04-28', 'Building pledge',2);

-- Events
INSERT INTO events (name, event_date, event_time, location, category, description, created_by) VALUES
('Sunday Service',     '2025-05-04', '09:00:00', 'Main Hall',         'Worship Service', 'Weekly Sunday worship service',           1),
('Youth Conference',   '2025-05-10', '10:00:00', 'Fellowship Hall',   'Youth Meeting',   'Annual youth gathering and worship',      1),
('Prayer Night',       '2025-05-17', '19:00:00', 'Main Hall',         'Prayer Night',    'Monthly all-night prayer vigil',          1),
('Easter Convention',  '2025-04-20', '09:00:00', 'Church Grounds',    'Conference',      'Easter celebration service',              1),
('Community Outreach', '2025-05-24', '08:00:00', 'Nagongera Market',  'Outreach',        'Door-to-door evangelism',                 5);

-- Attendance
INSERT INTO attendance (event_id, member_id, attendance_date, status) VALUES
(4, 1, '2025-04-20', 'Present'),
(4, 2, '2025-04-20', 'Present'),
(4, 3, '2025-04-20', 'Present'),
(4, 4, '2025-04-20', 'Absent'),
(4, 5, '2025-04-20', 'Present'),
(4, 7, '2025-04-20', 'Present'),
(4, 8, '2025-04-20', 'Excused');

-- Announcements
INSERT INTO announcements (title, message, priority, posted_by) VALUES
('Building Fund Update',    'We have raised UGX 3,200,000 towards our new sanctuary. Thank you for your generosity! Target: UGX 20,000,000.',    'info',   3),
('Fasting & Prayer Week',   'Join us for a week of fasting and prayer starting Monday 5th May. Daily prayer sessions at 6am and 6pm.',            'urgent', 3),
('Welcome New Members',     'Please warmly welcome our newest members who joined this month. May God bless them in this family.',                  'normal', 1);

-- Prayer Requests
INSERT INTO prayer_requests (requester_name, category, request, visibility, status, member_id) VALUES
('Anonymous', 'Healing',      'Pray for my mother who has been ill for three weeks. Believing God for her complete recovery.',          'leaders', 'Pending',    NULL),
('James W.',  'Finances',     'Seeking God\'s provision for my family during this difficult season. Trusting in His faithfulness.',     'public',  'Prayed For', 4),
('Ruth A.',   'Thanksgiving', 'Giving thanks for a successful job interview. God is faithful!',                                        'public',  'Answered',   5);
