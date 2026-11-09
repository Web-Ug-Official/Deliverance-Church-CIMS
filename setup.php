<?php
// setup.php — Run this ONCE to create the database and seed data
// Access at: http://localhost/cims/setup.php

$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/sql/cims_database.sql');

    // Split into statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    $count = 0;
    foreach ($statements as $stmt) {
        if ($stmt) {
            $pdo->exec($stmt);
            $count++;
        }
    }

    echo '<!DOCTYPE html><html><head><meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600&display=swap" rel="stylesheet">
    <style>body{font-family:"DM Sans",sans-serif;background:#0F5132;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
    .box{background:#fff;border-radius:16px;padding:40px;max-width:480px;text-align:center}
    h2{color:#0F5132;font-size:22px;margin-bottom:10px} p{color:#444;font-size:14px;line-height:1.6;margin-bottom:8px}
    .btn{display:inline-block;padding:12px 28px;background:#0F5132;color:#fff;border-radius:10px;text-decoration:none;font-weight:600;margin-top:16px;font-size:14px}
    .cross{font-size:44px;color:#0F5132;margin-bottom:10px}</style></head><body>
    <div class="box">
      <div class="cross">✝</div>
      <h2>Setup Complete!</h2>
      <p>The <strong>Deliverance Church CIMS</strong> database has been created successfully.</p>
      <p>Executed <strong>' . $count . ' SQL statements</strong>.</p>
      <p style="background:#d1f0e0;padding:12px;border-radius:8px;margin-top:16px;font-size:13px">
        <strong>Demo login credentials</strong><br>
        Username: <code>admin</code> &nbsp;·&nbsp; Password: <code>password</code><br>
        Other users: mfinance · pjohn · snamukasa · events
      </p>
      <a href="/cims/index.php" class="btn">Go to Login →</a>
    </div></body></html>';

} catch (PDOException $e) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8">
    <style>body{font-family:sans-serif;background:#c0392b;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
    .box{background:#fff;border-radius:16px;padding:40px;max-width:520px}
    h2{color:#c0392b} pre{background:#fdecea;padding:12px;border-radius:8px;font-size:12px;overflow:auto}</style></head><body>
    <div class="box">
      <h2>⚠️ Setup Error</h2>
      <p>Database setup failed. Please check your XAMPP MySQL is running and credentials are correct.</p>
      <pre>' . htmlspecialchars($e->getMessage()) . '</pre>
      <p><strong>Fix:</strong> Open <code>config/db.php</code> and update DB_USER / DB_PASS, then run setup again.</p>
    </div></body></html>';
}
