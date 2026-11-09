<?php
// index.php — Login page
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: /cims/pages/dashboard.php');
    exit;
}

require_once __DIR__ . '/config/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'Active' LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['csrf']      = bin2hex(random_bytes(32));
            header('Location: /cims/pages/dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In — Deliverance Church CIMS</title>
<link rel="stylesheet" href="/cims/assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="login-wrap">
  <div class="login-box">
    <div class="login-cross">✝</div>
    <div class="login-title">Deliverance Church</div>
    <div class="login-sub">Church Information Management System</div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" class="form-control" placeholder="Enter username"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px; padding:12px;">
        Sign In to CIMS
      </button>
    </form>

    <div style="margin-top:20px; padding:14px; background:var(--sand); border-radius:10px; font-size:12px; color:var(--ink-lt);">
      <strong>Demo Credentials (password: <code>password</code>)</strong><br>
      admin · mfinance · pjohn · snamukasa · events
    </div>
  </div>
</div>
<link rel="stylesheet" href="/cims/assets/css/style.css">
</body>
</html>
