-- ============================================================
--  DELIVERANCE CHURCH — CIMS Database
--  Import this file into phpMyAdmin or run via MySQL CLI:
--  mysql -u root -p < cims_database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS cims_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cims_db;

-- ── Users (system login accounts) ─────────────────────────
CREATE TABLE users (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  full_name   VARCHAR(100) NOT NULL,
  username    VARCHAR(50)  NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,
  role        ENUM('System Administrator','Finance Officer','Church Leader','Church Member','Event Coordinator') NOT NULL,
  status      ENUM('Active','Inactive') DEFAULT 'Active',
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── Cell Groups ────────────────────────────────────────────
CREATE TABLE cell_groups (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(100) NOT NULL,
  leader_id    INT,
  meeting_day  VARCHAR(20),
  location     VARCHAR(150),
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── Members ───────────────────────────────────────────────
CREATE TABLE members (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  first_name      VARCHAR(60)  NOT NULL,
  last_name       VARCHAR(60)  NOT NULL,
  phone           VARCHAR(30),
  email           VARCHAR(120),
  date_of_birth   DATE,
  gender          ENUM('Male','Female','Other'),
  address         VARCHAR(200),
  cell_group_id   INT,
  status          ENUM('Active','Inactive') DEFAULT 'Active',
  notes           TEXT,
  reg_date        DATE DEFAULT (CURDATE()),
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (cell_group_id) REFERENCES cell_groups(id) ON DELETE SET NULL
);

-- ── Events ────────────────────────────────────────────────
CREATE TABLE events (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(150) NOT NULL,
  event_date  DATE NOT NULL,
  event_time  TIME,
  location    VARCHAR(200),
  category    ENUM('Worship Service','Youth Meeting','Prayer Night','Conference','Outreach','Other') DEFAULT 'Worship Service',
  description TEXT,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── Attendance ────────────────────────────────────────────
CREATE TABLE attendance (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  event_id   INT NOT NULL,
  member_id  INT NOT NULL,
  att_date   DATE NOT NULL,
  status     ENUM('Present','Absent','Excused') DEFAULT 'Present',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id)  REFERENCES events(id)  ON DELETE CASCADE,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_att (event_id, member_id, att_date)
);

-- ── Financial Transactions ────────────────────────────────
CREATE TABLE transactions (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  member_id   INT,
  type        ENUM('Tithe','Offering','Donation','Pledge') NOT NULL,
  amount      DECIMAL(12,2) NOT NULL,
  tx_date     DATE NOT NULL,
  notes       TEXT,
  recorded_by INT,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (member_id)   REFERENCES members(id) ON DELETE SET NULL,
  FOREIGN KEY (recorded_by) REFERENCES users(id)   ON DELETE SET NULL
);

-- ── Announcements ─────────────────────────────────────────
CREATE TABLE announcements (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  title      VARCHAR(200) NOT NULL,
  message    TEXT NOT NULL,
  priority   ENUM('normal','info','urgent') DEFAULT 'normal',
  author_id  INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ── Prayer Requests ───────────────────────────────────────
CREATE TABLE prayer_requests (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  requester   VARCHAR(100) DEFAULT 'Anonymous',
  category    ENUM('Healing','Family','Finances','Guidance','Thanksgiving','Other') DEFAULT 'Other',
  request     TEXT NOT NULL,
  visibility  ENUM('leaders','public') DEFAULT 'leaders',
  status      ENUM('Pending','Prayed For','Answered') DEFAULT 'Pending',
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
--  SEED DATA
-- ============================================================

-- Default admin user (password: admin123)
INSERT INTO users (full_name, username, password, role) VALUES
('System Administrator', 'admin',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator'),
('Mary Nakato',          'finance', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Finance Officer'),
('Pastor John Okello',   'pastor',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Church Leader'),
('Sarah Namukasa',       'sarah',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Church Member'),
('Events Coordinator',   'events',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Event Coordinator');
-- Default password for all: "password"

INSERT INTO cell_groups (name, meeting_day, location) VALUES
('Nagongera East Cell',  'Wednesday', 'Namukasa Home'),
('Tororo Central Cell',  'Thursday',  'Church Hall B'),
('Youth Cell Group',     'Saturday',  'Youth Annex');

INSERT INTO members (first_name,last_name,phone,email,date_of_birth,gender,address,cell_group_id,status,reg_date) VALUES
('Sarah','Namukasa','+256 700 111111','sarah@example.com','1990-03-12','Female','Nagongera',1,'Active','2024-01-15'),
('Peter','Okello','+256 701 222222','peter@example.com','1985-07-22','Male','Tororo',2,'Active','2024-02-03'),
('Grace','Apio','+256 702 333333','grace@example.com','1995-11-05','Female','Nagongera',1,'Active','2024-02-20'),
('James','Wanyama','+256 703 444444','james@example.com','1978-04-18','Male','Tororo',3,'Active','2024-03-10'),
('Ruth','Aciro','+256 704 555555','ruth@example.com','1992-08-30','Female','Nagongera',2,'Active','2024-04-05'),
('David','Mugisha','+256 705 666666','david@example.com','1988-12-14','Male','Busia',3,'Inactive','2024-05-12'),
('Esther','Chemutai','+256 706 777777','esther@example.com','1999-06-08','Female','Nagongera',1,'Active','2024-08-01'),
('Moses','Opio','+256 707 888888','moses@example.com','1975-01-25','Male','Tororo',2,'Active','2024-09-15');

UPDATE cell_groups SET leader_id=1 WHERE id=1;
UPDATE cell_groups SET leader_id=2 WHERE id=2;
UPDATE cell_groups SET leader_id=5 WHERE id=3;

INSERT INTO events (name,event_date,event_time,location,category,description) VALUES
('Sunday Service','2025-05-04','09:00:00','Main Hall','Worship Service','Weekly Sunday worship service'),
('Youth Conference','2025-05-10','10:00:00','Fellowship Hall','Youth Meeting','Annual youth gathering and worship'),
('Prayer Night','2025-05-17','19:00:00','Main Hall','Prayer Night','Monthly all-night prayer vigil'),
('Easter Convention','2025-04-20','09:00:00','Church Grounds','Conference','Easter celebration service'),
('Community Outreach','2025-05-24','08:00:00','Nagongera Market','Outreach','Door-to-door evangelism');

INSERT INTO attendance (event_id,member_id,att_date,status) VALUES
(1,1,'2025-04-27','Present'),(1,2,'2025-04-27','Present'),(1,3,'2025-04-27','Present'),
(1,4,'2025-04-27','Absent'),(4,5,'2025-04-20','Present'),(4,1,'2025-04-20','Present'),
(4,7,'2025-04-20','Present'),(4,8,'2025-04-20','Excused');

INSERT INTO transactions (member_id,type,amount,tx_date,notes,recorded_by) VALUES
(1,'Tithe',150000,'2025-04-01','',2),(2,'Offering',50000,'2025-04-01','',2),
(3,'Tithe',200000,'2025-04-07','',2),(4,'Donation',500000,'2025-04-07','Building fund',2),
(5,'Offering',30000,'2025-04-14','',2),(1,'Tithe',150000,'2025-04-14','',2),
(2,'Tithe',120000,'2025-04-21','',2),(3,'Offering',75000,'2025-04-21','',2),
(7,'Tithe',80000,'2025-04-28','',2),(8,'Pledge',1000000,'2025-04-28','Building pledge',2);

INSERT INTO announcements (title,message,priority,author_id) VALUES
('Building Fund Update','We have raised UGX 3,200,000 towards our new sanctuary. Thank you for your generosity! Target: UGX 20,000,000.','info',1),
('Fasting & Prayer Week','Join us for a week of fasting and prayer starting Monday 5th May. Daily prayer sessions at 6am and 6pm.','urgent',3),
('Welcome New Members','Please warmly welcome our newest members who joined this month. May God bless them in this family.','normal',1);

INSERT INTO prayer_requests (requester,category,request,visibility,status) VALUES
('Anonymous','Healing','Pray for my mother who has been ill for three weeks. Believing God for her complete recovery.','leaders','Pending'),
('James W.','Finances','Seeking God\'s provision for my family during this difficult season. Trusting in His faithfulness.','public','Prayed For'),
('Ruth A.','Thanksgiving','Giving thanks for a successful job interview. God is faithful!','public','Answered');
