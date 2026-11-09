<?php
// pages/reports.php
require_once __DIR__ . '/../config/db.php';
$pageTitle = 'Reports & Analytics';
require_once __DIR__ . '/../includes/header.php';
requireRole(['System Administrator','Finance Officer','Church Leader']);

// Financial breakdown by type
$finBreakdown = $pdo->query("SELECT type, SUM(amount) as total, COUNT(*) as count FROM transactions GROUP BY type ORDER BY total DESC")->fetchAll();
$grandTotal   = array_sum(array_column($finBreakdown,'total')) ?: 1;

// Monthly trend (last 6 months)
$monthlyTrend = $pdo->query("
    SELECT DATE_FORMAT(transaction_date,'%b %Y') as month,
           SUM(amount) as total,
           YEAR(transaction_date)*100+MONTH(transaction_date) as sort_key
    FROM transactions
    WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY YEAR(transaction_date), MONTH(transaction_date)
    ORDER BY sort_key
")->fetchAll();
$trendMax = max(array_column($monthlyTrend,'total') ?: [1]);

// Membership stats
$activeCount   = $pdo->query("SELECT COUNT(*) FROM members WHERE status='Active'")->fetchColumn();
$inactiveCount = $pdo->query("SELECT COUNT(*) FROM members WHERE status='Inactive'")->fetchColumn();
$totalMembers  = $activeCount + $inactiveCount;

// Members by group
$byGroup = $pdo->query("SELECT cg.name, COUNT(m.id) as cnt FROM cell_groups cg LEFT JOIN members m ON m.group_id=cg.id AND m.status='Active' GROUP BY cg.id ORDER BY cnt DESC")->fetchAll();
$maxGroup = max(array_column($byGroup,'cnt') ?: [1]);

// Top contributors
$topContribs = $pdo->query("SELECT CONCAT(m.first_name,' ',m.last_name) as name, SUM(t.amount) as total FROM transactions t JOIN members m ON t.member_id=m.id GROUP BY m.id ORDER BY total DESC LIMIT 8")->fetchAll();
$maxContrib = $topContribs ? $topContribs[0]['total'] : 1;

// Attendance stats
$attTotal   = $pdo->query("SELECT COUNT(*) FROM attendance")->fetchColumn() ?: 1;
$attPresent = $pdo->query("SELECT COUNT(*) FROM attendance WHERE status='Present'")->fetchColumn();
$attRate    = round($attPresent / $attTotal * 100);

// Event attendance breakdown
$eventAtt = $pdo->query("SELECT e.name, COUNT(a.id) as total, SUM(a.status='Present') as present FROM events e LEFT JOIN attendance a ON e.id=a.event_id GROUP BY e.id ORDER BY e.event_date DESC LIMIT 6")->fetchAll();

$typeColors = ['Tithe'=>'var(--forest)','Offering'=>'var(--blue)','Donation'=>'var(--gold)','Pledge'=>'var(--purple)'];
?>

<div class="page-header"><h3>Reports & Analytics</h3></div>

<!-- Row 1: Financial -->
<div class="grid-2">
  <div class="card">
    <div class="card-header"><span class="card-title">Financial Breakdown by Type</span></div>
    <?php foreach ($finBreakdown as $row): ?>
      <div class="bar-wrap">
        <div class="bar-meta">
          <span><?= htmlspecialchars($row['type']) ?> (<?= $row['count'] ?> records)</span>
          <span>UGX <?= number_format($row['total']) ?></span>
        </div>
        <div class="bar-track">
          <div class="bar-fill" style="width:<?= round($row['total']/$grandTotal*100) ?>%;background:<?= $typeColors[$row['type']]??'var(--gold)' ?>"></div>
        </div>
      </div>
    <?php endforeach; ?>
    <div style="margin-top:14px;padding-top:12px;border-top:1px solid var(--border);display:flex;justify-content:space-between">
      <span style="font-size:12px;color:var(--ink-lt)">Grand Total</span>
      <strong style="color:var(--forest)">UGX <?= number_format($grandTotal) ?></strong>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">Monthly Trend (Last 6 Months)</span></div>
    <?php if ($monthlyTrend): ?>
      <?php foreach ($monthlyTrend as $row): ?>
        <div class="bar-wrap">
          <div class="bar-meta">
            <span><?= htmlspecialchars($row['month']) ?></span>
            <span>UGX <?= number_format($row['total']) ?></span>
          </div>
          <div class="bar-track">
            <div class="bar-fill" style="width:<?= round($row['total']/$trendMax*100) ?>%;background:var(--forest)"></div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="text-muted">No transaction data available.</p>
    <?php endif; ?>
  </div>
</div>

<!-- Row 2: Members & Attendance -->
<div class="grid-2">
  <div class="card">
    <div class="card-header"><span class="card-title">Membership Overview</span></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px">
      <div style="background:var(--forest-lt);border-radius:var(--radius);padding:16px;text-align:center">
        <div style="font-size:32px;font-weight:700;color:var(--forest);font-family:'Playfair Display',serif"><?= $activeCount ?></div>
        <span class="badge b-green">Active Members</span>
      </div>
      <div style="background:var(--sand);border-radius:var(--radius);padding:16px;text-align:center">
        <div style="font-size:32px;font-weight:700;color:var(--ink-lt);font-family:'Playfair Display',serif"><?= $inactiveCount ?></div>
        <span class="badge b-gray">Inactive</span>
      </div>
    </div>
    <div class="section-sub">Members by Cell Group</div>
    <?php foreach ($byGroup as $row): ?>
      <div class="bar-wrap">
        <div class="bar-meta">
          <span><?= htmlspecialchars($row['name']) ?></span>
          <span><?= $row['cnt'] ?> members</span>
        </div>
        <div class="bar-track">
          <div class="bar-fill" style="width:<?= $maxGroup?round($row['cnt']/$maxGroup*100):0 ?>%;background:var(--blue)"></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">Attendance Overview</span></div>
    <div style="text-align:center;padding:20px 0 10px">
      <div style="font-family:'Playfair Display',serif;font-size:60px;font-weight:700;color:var(--forest);line-height:1"><?= $attRate ?>%</div>
      <div style="font-size:13px;color:var(--ink-lt);margin-top:6px">Overall Attendance Rate</div>
      <div style="font-size:12px;color:var(--ink-lt);margin-top:4px"><?= $attPresent ?> present out of <?= $attTotal ?> records</div>
    </div>
    <div style="margin-top:14px">
      <div class="section-sub">Per Event</div>
      <?php foreach ($eventAtt as $row): ?>
        <div class="bar-wrap">
          <div class="bar-meta">
            <span><?= htmlspecialchars(substr($row['name'],0,28)) ?></span>
            <span><?= $row['present'] ?>/<?= $row['total']?:'-' ?></span>
          </div>
          <div class="bar-track">
            <div class="bar-fill" style="width:<?= $row['total']?round($row['present']/$row['total']*100):0 ?>%;background:var(--gold)"></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Row 3: Top Contributors -->
<div class="card">
  <div class="card-header"><span class="card-title">Top Contributors (All Time)</span></div>
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>#</th><th>Member</th><th>Total Contributed (UGX)</th><th>Share</th></tr></thead>
      <tbody>
        <?php foreach ($topContribs as $i => $row): ?>
          <tr>
            <td style="font-weight:700;color:var(--ink-lt)"><?= $i+1 ?></td>
            <td style="font-weight:500"><?= htmlspecialchars($row['name']) ?></td>
            <td style="font-weight:600;color:var(--forest)"><?= number_format($row['total']) ?></td>
            <td style="width:200px">
              <div class="bar-track" style="height:10px">
                <div class="bar-fill" style="width:<?= round($row['total']/$maxContrib*100) ?>%;background:var(--gold)"></div>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$topContribs): ?><tr><td colspan="4" class="text-muted" style="text-align:center;padding:20px">No data.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
