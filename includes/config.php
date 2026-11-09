<?php
// ── Database Configuration ─────────────────────────────────
// Edit these values to match your XAMPP MySQL setup
define('DB_HOST',     'localhost');
define('DB_USER',     'root');        // XAMPP default
define('DB_PASS',     '');            // XAMPP default (blank)
define('DB_NAME',     'cims_db');
define('DB_CHARSET',  'utf8mb4');
define('SITE_NAME',   'Deliverance Church CIMS');
define('SITE_URL',    'http://localhost/cims');

// ── Connect ────────────────────────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:30px;color:#c0392b;background:#fdecea;border:1px solid #f5b7b1;border-radius:8px;margin:30px auto;max-width:500px">
                <h2>⚠ Database Connection Error</h2>
                <p>'.$e->getMessage().'</p>
                <p style="margin-top:12px;font-size:13px;color:#666">
                  Make sure XAMPP is running and you have imported <strong>cims_database.sql</strong> into phpMyAdmin.
                </p></div>');
        }
    }
    return $pdo;
}

// ── Session helpers ────────────────────────────────────────
function requireLogin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_id'])) {
        header('Location: '.SITE_URL.'/index.php');
        exit;
    }
}

function currentUser(): array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return $_SESSION['user'] ?? [];
}

function hasRole(array $roles): bool {
    $u = currentUser();
    return in_array($u['role'] ?? '', $roles, true);
}

function fmt(float $n): string {
    return number_format($n, 0, '.', ',');
}
