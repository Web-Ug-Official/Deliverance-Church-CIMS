<?php
// pages/finance.php
require_once __DIR__ . '/../config/db.php';
$pageTitle = 'Financial Management';
require_once __DIR__ . '/../includes/header.php';
requireRole(['System Administrator','Finance Officer']);

$msg = '';

// Add Transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'add') {
    $pdo->prepare("INSERT INTO transactions (member_id,type,amount,transaction_date,notes,recorded_by) VALUES (?,?,?,?,?,?)")
        ->execute([$_POST['member_id'],$_POST['type'],$_POST['amount'],$_POST['tx_date'],trim($_POST['notes']),$user['id']]);
    $msg = 'success|Transaction recorded successfully!';
}

// Delete Transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'delete') {
    $pdo->prepare("DELETE FROM transactions WHERE id=?")->execute([$_POST['tx_id']]);
    $msg = 'success|Transaction removed.';
}

// Summary totals
$totals = $pdo->query("SELECT type, SUM(amount) as total FROM transactions GROUP BY type")->fetchAll(PDO::FETCH_KEY_PAIR);
$grandTotal = array_sum($totals);

// Filter
$typeFilter = $_GET['type'] ?? 'all';
$where = '1=1'; $params = [];
if ($typeFilter !== 'all') { $where .= ' AND t.type=?'; $params[] = $typeFilter; }

$transactions = $pdo->prepare("SELECT t.*, CONCAT(m.first_name,' ',m.last_name) AS member_name FROM transactions t JOIN members m ON t.member_id=m.id WHERE $where ORDER BY t.transaction_date DESC, t.id DESC");
$transactions->execute($params);
$transactions = $transactions->fetchAll();

$members = $pdo->query("SELECT id, CONCAT(first_name,' ',last_name) AS name FROM members WHERE status='Active' ORDER BY first_name")->fetchAll();

[$msgType, $msgText] = $msg ? explode('|', $msg, 2) : ['',''];
?>

<?php if ($msgText): ?><div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msgText) ?></div><?php endif; ?>

<div class="page-header">
  <h3>Financial Management</h3>
  <button class="btn btn-primary" onclick="openModal('txModal')">+ Record Transaction</button>
</div>

<!-- Summary Cards -->
<div class="stats-row">
  <div class="stat green" data-icon="⛪">
    <div class="stat-label">Tithes</div>
    <div class="stat-val" style="font-size:20px">UGX <?= number_format($totals['Tithe']??0) ?></div>
    <div class="stat-sub">All time</div>
  </div>
  <div class="stat gold" data-icon="🤲">
    <div class="stat-label">Offerings</div>
    <div class="stat-val" style="font-size:20px">UGX <?= number_format($totals['Offering']??0) ?></div>
    <div class="stat-sub">All time</div>
  </div>
  <div class="stat blue" data-icon="💝">
    <div class="stat-label">Donations</div>
    <div class="stat-val" style="font-size:20px">UGX <?= number_format($totals['Donation']??0) ?></div>
    <div class="stat-sub">All time</div>
  </div>
  <div class="stat red" data-icon="🤝">
    <div class="stat-label">Pledges</div>
    <div class="stat-val" style="font-size:20px">UGX <?= number_format($totals['Pledge']??0) ?></div>
    <div class="stat-sub">All time</div>
  </div>
</div>

<div class="card" style="margin-bottom:14px;padding:14px 20px;display:flex;align-items:center;justify-content:space-between">
  <div><strong>Grand Total Collected:</strong> <span style="font-family:'Playfair Display',serif;font-size:18px;color:var(--forest)">UGX <?= number_format($grandTotal) ?></span></div>
</div>

<!-- Transactions Table -->
<div class="card">
  <div class="card-header">
    <span class="card-title">Transaction History</span>
    <form method="GET" style="display:flex;gap:8px">
      <select name="type" class="form-control" style="padding:6px 10px;width:auto">
        <option value="all" <?= $typeFilter==='all'?'selected':'' ?>>All Types</option>
        <?php foreach(['Tithe','Offering','Donation','Pledge'] as $t): ?>
          <option value="<?= $t ?>" <?= $typeFilter===$t?'selected':'' ?>><?= $t ?>s</option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-sm btn-primary">Filter</button>
    </form>
  </div>
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Date</th><th>Member</th><th>Type</th><th>Amount (UGX)</th><th>Notes</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($transactions as $t): ?>
          <tr>
            <td><?= htmlspecialchars($t['transaction_date']) ?></td>
            <td><?= htmlspecialchars($t['member_name']) ?></td>
            <td>
              <span class="badge <?= ['Tithe'=>'b-green','Offering'=>'b-blue','Donation'=>'b-gold','Pledge'=>'b-purple'][$t['type']]??'b-gray' ?>">
                <?= $t['type'] ?>
              </span>
            </td>
            <td style="font-weight:600"><?= number_format($t['amount']) ?></td>
            <td style="color:var(--ink-lt)"><?= htmlspecialchars($t['notes']??'—') ?></td>
            <td>
              <form method="POST" style="display:inline" onsubmit="return confirmDelete(this)">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="tx_id" value="<?= $t['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$transactions): ?>
          <tr><td colspan="6" style="text-align:center;color:var(--ink-lt);padding:24px">No transactions found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Transaction Modal -->
<div class="modal-backdrop" id="txModal">
  <div class="modal">
    <div class="modal-hd">
      <span class="modal-title">Record Transaction</span>
      <button class="modal-x" onclick="closeModal('txModal')">✕</button>
    </div>
    <div class="modal-body">
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="form-row">
          <div class="form-group"><label>Member *</label>
            <select name="member_id" class="form-control" required>
              <option value="">— Select Member —</option>
              <?php foreach($members as $m): ?>
                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>Type *</label>
            <select name="type" class="form-control" required>
              <option>Tithe</option><option>Offering</option><option>Donation</option><option>Pledge</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Amount (UGX) *</label><input name="amount" type="number" min="1" class="form-control" required placeholder="150000"></div>
          <div class="form-group"><label>Date *</label><input name="tx_date" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
        </div>
        <div class="form-group"><label>Notes</label><input name="notes" class="form-control" placeholder="Optional notes"></div>
        <div class="modal-ft">
          <button type="button" class="btn" onclick="closeModal('txModal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Record</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
