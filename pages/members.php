<?php
// pages/members.php
require_once __DIR__ . '/../config/db.php';
$pageTitle = 'Member Registry';
require_once __DIR__ . '/../includes/header.php';
requireRole(['System Administrator','Finance Officer','Church Leader']);

$msg = '';

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $stmt = $pdo->prepare("INSERT INTO members (first_name,last_name,phone,email,date_of_birth,gender,address,group_id,registration_date,status,notes) VALUES (?,?,?,?,?,?,?,?,CURDATE(),'Active',?)");
    $stmt->execute([
        trim($_POST['first_name']),
        trim($_POST['last_name']),
        trim($_POST['phone']),
        trim($_POST['email']),
        $_POST['dob'] ?: null,
        $_POST['gender'],
        trim($_POST['address']),
        $_POST['group_id'] ?: null,
        trim($_POST['notes']),
    ]);
    $msg = 'success|Member added successfully!';
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $pdo->prepare("DELETE FROM members WHERE id=?")->execute([$_POST['member_id']]);
    $msg = 'success|Member removed.';
}

// Handle Toggle Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    $cur = $pdo->prepare("SELECT status FROM members WHERE id=?");
    $cur->execute([$_POST['member_id']]);
    $row = $cur->fetch();
    $new = $row['status'] === 'Active' ? 'Inactive' : 'Active';
    $pdo->prepare("UPDATE members SET status=? WHERE id=?")->execute([$new, $_POST['member_id']]);
    $msg = 'success|Member status updated.';
}

$search  = trim($_GET['q'] ?? '');
$status  = $_GET['status'] ?? 'all';

$where = '1=1';
$params = [];
if ($search) { $where .= ' AND (m.first_name LIKE ? OR m.last_name LIKE ? OR m.phone LIKE ? OR m.email LIKE ?)'; $s="%$search%"; $params = array_merge($params,[$s,$s,$s,$s]); }
if ($status !== 'all') { $where .= ' AND m.status = ?'; $params[] = $status; }

$members = $pdo->prepare("SELECT m.*, cg.name AS group_name FROM members m LEFT JOIN cell_groups cg ON m.group_id=cg.id WHERE $where ORDER BY m.first_name");
$members->execute($params);
$members = $members->fetchAll();

$groups = $pdo->query("SELECT * FROM cell_groups ORDER BY name")->fetchAll();
$avColors = ['av-g','av-gold','av-b','av-r'];

[$msgType, $msgText] = $msg ? explode('|', $msg, 2) : ['',''];
?>

<?php if ($msgText): ?><div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msgText) ?></div><?php endif; ?>

<div class="page-header">
  <h3>Member Registry</h3>
  <button class="btn btn-primary" onclick="openModal('memberModal')">+ Add Member</button>
</div>

<!-- Search & Filter -->
<form method="GET" class="search-row">
  <input type="text" name="q" class="form-control" placeholder="Search name, phone, email…" value="<?= htmlspecialchars($search) ?>">
  <select name="status" class="form-control" style="max-width:160px">
    <option value="all" <?= $status==='all'?'selected':'' ?>>All Status</option>
    <option value="Active" <?= $status==='Active'?'selected':'' ?>>Active</option>
    <option value="Inactive" <?= $status==='Inactive'?'selected':'' ?>>Inactive</option>
  </select>
  <button type="submit" class="btn btn-primary btn-sm">Search</button>
  <?php if($search||$status!=='all'): ?><a href="/cims/pages/members.php" class="btn btn-sm">Clear</a><?php endif; ?>
</form>

<div class="card">
  <div class="tbl-wrap">
    <table>
      <thead>
        <tr><th>Member</th><th>Contact</th><th>Cell Group</th><th>Reg. Date</th><th>Status</th>
        <?php if(hasRole(['System Administrator'])): ?><th>Actions</th><?php endif; ?></tr>
      </thead>
      <tbody>
        <?php foreach ($members as $i => $m): ?>
          <tr>
            <td>
              <div class="member-cell">
                <div class="av <?= $avColors[$i%4] ?>"><?= strtoupper(substr($m['first_name'],0,1).substr($m['last_name'],0,1)) ?></div>
                <div class="member-info">
                  <strong><?= htmlspecialchars($m['first_name'].' '.$m['last_name']) ?></strong>
                  <span><?= htmlspecialchars($m['gender']??'') ?> · <?= htmlspecialchars($m['address']??'') ?></span>
                </div>
              </div>
            </td>
            <td>
              <div><?= htmlspecialchars($m['phone']??'—') ?></div>
              <div style="font-size:11.5px;color:var(--ink-lt)"><?= htmlspecialchars($m['email']??'—') ?></div>
            </td>
            <td><span class="badge b-green"><?= htmlspecialchars($m['group_name']??'—') ?></span></td>
            <td><?= htmlspecialchars($m['registration_date']) ?></td>
            <td><span class="badge <?= $m['status']==='Active'?'b-green':'b-gray' ?>"><?= $m['status'] ?></span></td>
            <?php if(hasRole(['System Administrator'])): ?>
              <td>
                <form method="POST" style="display:inline" onsubmit="return confirmDelete(this)">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="member_id" value="<?= $m['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                </form>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="member_id" value="<?= $m['id'] ?>">
                  <button type="submit" class="btn btn-sm"><?= $m['status']==='Active'?'Deactivate':'Activate' ?></button>
                </form>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
        <?php if (!$members): ?>
          <tr><td colspan="6" style="text-align:center;color:var(--ink-lt);padding:24px">No members found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Member Modal -->
<div class="modal-backdrop" id="memberModal">
  <div class="modal">
    <div class="modal-hd">
      <span class="modal-title">Add Church Member</span>
      <button class="modal-x" onclick="closeModal('memberModal')">✕</button>
    </div>
    <div class="modal-body">
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="form-row">
          <div class="form-group"><label>First Name *</label><input name="first_name" class="form-control" required placeholder="Sarah"></div>
          <div class="form-group"><label>Last Name *</label><input name="last_name" class="form-control" required placeholder="Namukasa"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Phone</label><input name="phone" class="form-control" placeholder="+256 700 000000"></div>
          <div class="form-group"><label>Email</label><input name="email" type="email" class="form-control" placeholder="email@example.com"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Date of Birth</label><input name="dob" type="date" class="form-control"></div>
          <div class="form-group"><label>Gender</label>
            <select name="gender" class="form-control">
              <option>Female</option><option>Male</option><option>Other</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Address</label><input name="address" class="form-control" placeholder="Nagongera, Tororo"></div>
          <div class="form-group"><label>Cell Group</label>
            <select name="group_id" class="form-control">
              <option value="">— Select Group —</option>
              <?php foreach($groups as $g): ?>
                <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" placeholder="Optional notes…"></textarea></div>
        <div class="modal-ft">
          <button type="button" class="btn" onclick="closeModal('memberModal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Member</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
