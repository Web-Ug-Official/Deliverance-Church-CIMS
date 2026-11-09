<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /cims/index.php');
        exit;
    }
}

function hasRole(array $roles): bool {
    return isLoggedIn() && in_array($_SESSION['role'], $roles, true);
}

function requireRole(array $roles): void {
    requireLogin();
    if (!hasRole($roles)) {
        header('Location: /cims/dashboard.php?error=access_denied');
        exit;
    }
}

function currentUser(): array {
    return [
        'id'   => $_SESSION['user_id']   ?? 0,
        'name' => $_SESSION['user_name'] ?? '',
        'role' => $_SESSION['role']      ?? '',
    ];
}

function logout(): void {
    session_destroy();
    header('Location: /cims/index.php');
    exit;
}

// CSRF helpers
function csrfToken(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf'] ?? $_GET['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}
