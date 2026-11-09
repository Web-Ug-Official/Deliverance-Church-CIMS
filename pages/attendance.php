<?php
// pages/attendance.php
require_once __DIR__ . '/../config/db.php';
$pageTitle = 'Attendance Records';
require_once __DIR__ . '/../includes/header.php';
requireRole(['System Administrator','Church Leader','Event Coordinator']);

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'add') {
    $pdo->prepare("INSERT INTO attendance (event_id,member_id,attendance_date,status) VALUES (?,?,?,?)")
        ->execute([$_POST['event_id'],$_POST['member_id'],$_POST['att_date'],$_POST['status']]);
    $msg = 'success|Attendance recorded!';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'delete') {
    $pdo->prepare("DELETE FROM attendance WHERE id=?")->execute([$_POST['att_id']]);
    $msg = 'success|Record removed.';
}

$total   = $pdo->query("SELECT COUNT(*) FROM attendance")->fetchColumn();
$present = $pdo->query("SELECT COUNT(*) FROM attendance WHERE status='Present'")->fetchColumn();
$absent  = $pdo->query("SELECT COUNT(*) FROM attendance WHERE status='Absent'")->fetchColumn();
$excused = $pdo->query("SELECT COUNT(*) FROM attendance WHERE status='Excused'")->fetchColumn();

$records = $pdo->query("SELECT a.*, e.name as event_name, CONCAT(m.first_name,' ',m.last_name) as member_name FROM attendance a JOIN events e ON a.event_id=e.id JOIN members m ON a.member_id=m.id ORDER BY a.attendance_date DESC, a.id DESC LIMIT 100")->fetchAll();
$events  = $pdo->query("SELECT * FROM events ORDER BY event_date DESC")->fetchAll();
$members = $pdo->query("SELECT id, CONCAT(first_name,' ',last_name) AS name FROM members WHERE status='Active' ORDER BY first_name")->fetchAll();

[$msgType, $msgText] = $msg ? explode('|', $msg, 2) : ['',''];
?>
<?php if ($msgText): ?><div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msgText) ?></div><?php endif; ?>

<div class="page-header">
  <h3>Attendance Records</h3>
  <button class="btn btn-primary" onclick="openModal('attModal')">+ Record Attendance</button>
</div>

<div class="grid-3" style="margin-bottom:20px">
  <div style="background:var(--forest-lt);border-radius:var(--radius);padding:16px;text-align:center">
    <div style="font-size:28px;font-weight:700;color:var(--forest);font-family:'Playfair Display',serif"><?= $present ?></div>
    <span class="badge b-green">Present</span>
  </div>
  <div style="background:var(--red-lt);border-radius:var(--radius);padding:16px;text-align:center">
    <div style="font-size:28px;font-weight:700;color:var(--red);font-family:'Playfair Display',serif"><?= $absent ?></div>
    <span class="badge b-red">Absent</span>
  </div>
  <div style="background:var(--gold-lt);border-radius:var(--radius);padding:16px;text-align:center">
    <div style="font-size:28px;font-weight:700;color:var(--gold);font-family:'Playfair Display',serif"><?= $excused ?></div>
    <span class="badge b-gold">Excused</span>
  </div>
</div>

<div class="card">
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Event</th><th>Member</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($records as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['event_name']) ?></td>
            <td><?= htmlspecialchars($r['member_name']) ?></td>
            <td><?= $r['attendance_date'] ?></td>
            <td><span class="badge <?= ['Present'=>'b-green','Absent'=>'b-red','Excused'=>'b-gold'][$r['status']]??'b-gray' ?>"><?= $r['status'] ?></span></td>
            <td>
              <form method="POST" style="display:inline" onsubmit="return confirmDelete(this)">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="att_id" value="<?= $r['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">Remove</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-backdrop" id="attModal">
  <div class="modal">
    <div class="modal-hd"><span class="modal-title">Record Attendance</span><button class="modal-x" onclick="closeModal('attModal')">✕</button></div>
    <div class="modal-body">
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="form-row">
          <div class="form-group"><label>Event *</label>
            <select name="event_id" class="form-control" required>
              <?php foreach($events as $e): ?><option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['name']) ?> (<?= $e['event_date'] ?>)</option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>Member *</label>
            <select name="member_id" class="form-control" required>
              <?php foreach($members as $m): ?><option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Date *</label><input name="att_date" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
          <div class="form-group"><label>Status *</label>
            <select name="status" class="form-control"><option>Present</option><option>Absent</option><option>Excused</option></select>
          </div>
        </div>
        <div class="modal-ft">
          <button type="button" class="btn" onclick="closeModal('attModal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
