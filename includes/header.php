<?php
// includes/header.php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$user = currentUser();

// Role-based navigation
$navItems = [
    'System Administrator' => [
        ['section' => 'Overview'],
        ['page' => 'dashboard',      'icon' => '⊞', 'label' => 'Dashboard'],
        ['section' => 'People'],
        ['page' => 'members',        'icon' => '👥', 'label' => 'Members'],
        ['page' => 'cell_groups',    'icon' => '🏘️', 'label' => 'Cell Groups'],
        ['section' => 'Ministry'],
        ['page' => 'events',         'icon' => '📅', 'label' => 'Events'],
        ['page' => 'attendance',     'icon' => '✅', 'label' => 'Attendance'],
        ['page' => 'announcements',  'icon' => '📢', 'label' => 'Announcements'],
        ['page' => 'prayer',         'icon' => '🙏', 'label' => 'Prayer Requests'],
        ['section' => 'Finance & Admin'],
        ['page' => 'finance',        'icon' => '💰', 'label' => 'Finance'],
        ['page' => 'reports',        'icon' => '📊', 'label' => 'Reports'],
        ['page' => 'users',          'icon' => '🔐', 'label' => 'User Accounts'],
    ],
    'Finance Officer' => [
        ['page' => 'dashboard',      'icon' => '⊞', 'label' => 'Dashboard'],
        ['page' => 'finance',        'icon' => '💰', 'label' => 'Finance'],
        ['page' => 'reports',        'icon' => '📊', 'label' => 'Reports'],
        ['page' => 'members',        'icon' => '👥', 'label' => 'Members'],
    ],
    'Church Leader' => [
        ['page' => 'dashboard',      'icon' => '⊞', 'label' => 'Dashboard'],
        ['page' => 'members',        'icon' => '👥', 'label' => 'Members'],
        ['page' => 'cell_groups',    'icon' => '🏘️', 'label' => 'Cell Groups'],
        ['page' => 'events',         'icon' => '📅', 'label' => 'Events'],
        ['page' => 'attendance',     'icon' => '✅', 'label' => 'Attendance'],
        ['page' => 'announcements',  'icon' => '📢', 'label' => 'Announcements'],
        ['page' => 'prayer',         'icon' => '🙏', 'label' => 'Prayer Requests'],
        ['page' => 'reports',        'icon' => '📊', 'label' => 'Reports'],
    ],
    'Church Member' => [
        ['page' => 'profile',        'icon' => '👤', 'label' => 'My Profile'],
        ['page' => 'events',         'icon' => '📅', 'label' => 'Events'],
        ['page' => 'announcements',  'icon' => '📢', 'label' => 'Announcements'],
        ['page' => 'prayer',         'icon' => '🙏', 'label' => 'Prayer Requests'],
    ],
    'Event Coordinator' => [
        ['page' => 'dashboard',      'icon' => '⊞', 'label' => 'Dashboard'],
        ['page' => 'events',         'icon' => '📅', 'label' => 'Events'],
        ['page' => 'attendance',     'icon' => '✅', 'label' => 'Attendance'],
        ['page' => 'announcements',  'icon' => '📢', 'label' => 'Announcements'],
    ],
];

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$nav = $navItems[$user['role']] ?? [];
$initials = strtoupper(substr($user['name'], 0, 1) . (strpos($user['name'], ' ') !== false ? substr($user['name'], strpos($user['name'], ' ') + 1, 1) : ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'CIMS') ?> — Deliverance Church</title>
<link rel="stylesheet" href="/cims/assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="app">
  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sb-logo">
      <div class="sb-cross">✝</div>
      <div>
        <h1>Deliverance Church</h1>
        <p>CIMS v2.0 · Nagongera</p>
      </div>
    </div>
    <div class="sb-user">
      <div class="sb-avatar"><?= htmlspecialchars($initials) ?></div>
      <div class="sb-uinfo">
        <p><?= htmlspecialchars($user['name']) ?></p>
        <span><?= htmlspecialchars($user['role']) ?></span>
      </div>
    </div>
    <nav class="sb-nav">
      <?php foreach ($nav as $item): ?>
        <?php if (isset($item['section'])): ?>
          <div class="sb-section"><?= htmlspecialchars($item['section']) ?></div>
        <?php else: ?>
          <a href="/cims/pages/<?= $item['page'] ?>.php"
             class="sb-item <?= $currentPage === $item['page'] ? 'active' : '' ?>">
            <span class="icon"><?= $item['icon'] ?></span>
            <?= htmlspecialchars($item['label']) ?>
          </a>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>
    <a href="/cims/logout.php" class="sb-logout">↩ Sign Out</a>
  </aside>

  <!-- MAIN -->
  <div class="main">
    <header class="topbar">
      <div class="topbar-left">
        <h2><?= htmlspecialchars($pageTitle ?? '') ?></h2>
        <p><?= date('l, d F Y') ?></p>
      </div>
      <div class="topbar-right">
        <span class="badge b-role"><?= htmlspecialchars($user['role']) ?></span>
      </div>
    </header>
    <div class="content">
