# ⛪ Deliverance Church CIMS

A church information management system for running the day-to-day life of a congregation: members, cell groups, finances, events, attendance, announcements and prayer requests, with **role-based permissions** and analytics.

**Stack:** PHP 7.4+ · MySQL / MariaDB (PDO) · JavaScript · HTML/CSS · Apache (XAMPP)

<!-- Add screenshots: ![Dashboard](screenshots/dashboard.png) -->

## 🧩 Modules

| Module | Purpose |
|---|---|
| Dashboard | Overview statistics and charts |
| Members | Member records (create, update, delete) |
| Cell groups | Group management |
| Finance | Transaction records |
| Events | Event management |
| Attendance | Attendance tracking |
| Announcements | Church-wide notices |
| Prayer requests | Submit and view requests |
| Reports | Analytics and charts |
| Users / Profile | Account management and member self-profile |

## 🔑 Roles

**System Administrator · Finance Officer · Church Leader · Church Member · Event Coordinator**, each with its own access level (for example, only admins add or delete members, and only admins and finance officers see Finance).

## 🔐 Security

- Passwords stored as **bcrypt** hashes
- **PDO prepared statements** against SQL injection
- Output escaped with `htmlspecialchars()`
- Delete `setup.php` and restrict `config/db.php` before going live

## 🗄️ Database (`cims_db`)

`users` · `cell_groups` · `members` · `transactions` · `events` · `attendance` · `announcements` · `prayer_requests`

## 🚀 Run It Locally (XAMPP)

1. Copy this folder to `C:\xampp\htdocs\cims\` and start **Apache** and **MySQL**.
2. Open `http://localhost/cims/setup.php` to create the database, tables and seed data. If that fails, create `cims_db` in phpMyAdmin and import `sql/cims_database.sql`.
3. Sign in at `http://localhost/cims/` with the seed accounts described in `setup.php` / the SQL file.

## 📁 Structure

```text
CIMS/
├── index.php  logout.php  setup.php
├── config/      ← database connection
├── includes/    ← auth, header, footer
├── pages/       ← one file per module
├── assets/  css/  js/
└── sql/         ← full schema + seed data
```
