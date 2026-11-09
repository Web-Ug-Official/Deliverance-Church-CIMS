<?php
// pages/dashboard.php
require_once __DIR__ . '/../config/db.php';
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';

// Stats
$activeMembers  = $pdo->query("SELECT COUNT(*) FROM members WHERE status='Active'")->fetchColumn();
$monthlyTotal   = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE MONTH(transaction_date)=MONTH(CURDATE()) AND YEAR(transaction_date)=YEAR(CURDATE())")->fetchColumn();
$upcomingEvents = $pdo->query("SELECT COUNT(*) FROM events WHERE event_date >= CURDATE()")->fetchColumn();
$pendingPrayers = $pdo->query("SELECT COUNT(*) FROM prayer_requests WHERE status='Pending'")->fetchColumn();

// Monthly financial chart data (last 4 months)
$chartData = $pdo->query("
    SELECT DATE_FORMAT(transaction_date,'%b %Y') as month,
           SUM(amount) as total
    FROM transactions
    WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 4 MONTH)
    GROUP BY YEAR(transaction_date), MONTH(transaction_date)
    ORDER BY transaction_date
")->fetchAll();

$chartMax = max(array_column($chartData, 'total') ?: [1]);

// Recent members
$recentMembers = $pdo->query("SELECT * FROM members ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Announcements
$announcements = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3")->fetchAll();

// Upcoming events
$upcomingList = $pdo->query("SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date LIMIT 4")->fetchAll();

$avColors = ['av-g','av-gold','av-b','av-r'];
?>

<!-- Stats Row -->
<div class="stats-row">
  <div class="stat green" data-icon="👥">
    <div class="stat-label">Active Members</div>
    <div class="stat-val"><?= $activeMembers ?></div>
    <div class="stat-sub">Registered congregation</div>
  </div>
  <div class="stat gold" data-icon="💰">
    <div class="stat-label">This Month</div>
    <div class="stat-val">UGX <?= number_format($monthlyTotal) ?></div>
    <div class="stat-sub">Tithes, Offerings &amp; Donations</div>
  </div>
  <div class="stat blue" data-icon="📅">
    <div class="stat-label">Upcoming Events</div>
    <div class="stat-val"><?= $upcomingEvents ?></div>
    <div class="stat-sub">Scheduled ahead</div>
  </div>
  <div class="stat red" data-icon="🙏">
    <div class="stat-label">Prayer Requests</div>
    <div class="stat-val"><?= $pendingPrayers ?></div>
    <div class="stat-sub">Pending attention</div>
  </div>
</div>

<div class="grid-2">
  <!-- Financial Chart -->
  <div class="card">
    <div class="card-header"><span class="card-title">Monthly Financial Overview</span></div>
    <?php foreach ($chartData as $row): ?>
      <div class="bar-wrap">
        <div class="bar-meta">
          <span><?= htmlspecialchars($row['month']) ?></span>
          <span>UGX <?= number_format($row['total']) ?></span>
        </div>
        <div class="bar-track">
          <div class="bar-fill" style="width:<?= round($row['total'] / $chartMax * 100) ?>%;background:var(--forest)"></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Upcoming Events -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Upcoming Events</span>
      <a href="/cims/pages/events.php" class="btn btn-sm">View All</a>
    </div>
    <?php foreach ($upcomingList as $ev): ?>
      <div style="display:flex;gap:12px;padding:10px 0;border-bottom:1px solid var(--parchment)">
        <div style="background:var(--forest);color:#fff;border-radius:8px;padding:6px 10px;text-align:center;min-width:50px;flex-shrink:0">
          <div style="font-size:18px;font-weight:700;font-family:'Playfair Display',serif"><?= date('d', strtotime($ev['event_date'])) ?></div>
          <div style="font-size:10px;opacity:.8"><?= date('M', strtotime($ev['event_date'])) ?></div>
        </div>
        <div>
          <div style="font-size:13.5px;font-weight:600"><?= htmlspecialchars($ev['name']) ?></div>
          <div style="font-size:12px;color:var(--ink-lt)"><?= date('H:i', strtotime($ev['event_time'])) ?> · <?= htmlspecialchars($ev['location']) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="grid-2">
  <!-- Recent Members -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Recent Members</span>
      <a href="/cims/pages/members.php" class="btn btn-sm">View All</a>
    </div>
    <div class="tbl-wrap">
      <table>
        <thead><tr><th>Member</th><th>Joined</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($recentMembers as $i => $m): ?>
            <tr>
              <td>
                <div class="member-cell">
                  <div class="av <?= $avColors[$i % 4] ?>"><?= strtoupper(substr($m['first_name'],0,1).substr($m['last_name'],0,1)) ?></div>
                  <div class="member-info">
                    <strong><?= htmlspecialchars($m['first_name'].' '.$m['last_name']) ?></strong>
                    <span><?= htmlspecialchars($m['gender']) ?></span>
                  </div>
                </div>
              </td>
              <td><?= htmlspecialchars($m['registration_date']) ?></td>
              <td><span class="badge <?= $m['status']==='Active'?'b-green':'b-gray' ?>"><?= $m['status'] ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Announcements -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Latest Announcements</span>
      <a href="/cims/pages/announcements.php" class="btn btn-sm">View All</a>
    </div>
    <?php foreach ($announcements as $a): ?>
      <div class="notice <?= $a['priority'] ?>">
        <div class="notice-title"><?= htmlspecialchars($a['title']) ?></div>
        <div class="notice-body"><?= htmlspecialchars(substr($a['message'],0,120)) ?>…</div>
        <div class="notice-meta"><?= date('d M Y', strtotime($a['created_at'])) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
