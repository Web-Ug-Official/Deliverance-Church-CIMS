<?php
// pages/cell_groups.php
require_once __DIR__ . '/../config/db.php';
$pageTitle = 'Cell Groups';
require_once __DIR__ . '/../includes/header.php';
requireRole(['System Administrator','Church Leader']);

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'add') {
    $pdo->prepare("INSERT INTO cell_groups (name,leader_id,meeting_day,meeting_location) VALUES (?,?,?,?)")
        ->execute([trim($_POST['name']),$_POST['leader_id']||null,$_POST['meeting_day'],trim($_POST['meeting_location'])]);
    $msg = 'success|Cell group created!';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'delete') {
    $pdo->prepare("DELETE FROM cell_groups WHERE id=?")->execute([$_POST['group_id']]);
    $msg = 'success|Group deleted.';
}

$groups  = $pdo->query("SELECT cg.*, CONCAT(m.first_name,' ',m.last_name) as leader_name, COUNT(DISTINCT mem.id) as member_count FROM cell_groups cg LEFT JOIN members m ON cg.leader_id=m.id LEFT JOIN members mem ON mem.group_id=cg.id AND mem.status='Active' GROUP BY cg.id ORDER BY cg.name")->fetchAll();
$members_list = $pdo->query("SELECT id, CONCAT(first_name,' ',last_name) AS name FROM members WHERE status='Active' ORDER BY first_name")->fetchAll();

[$msgType,$msgText] = $msg ? explode('|',$msg,2) : ['',''];
$avC = ['av-g','av-gold','av-b','av-r'];
?>
<?php if ($msgText): ?><div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msgText) ?></div><?php endif; ?>

<div class="page-header">
  <h3>Cell Groups</h3>
  <?php if(hasRole(['System Administrator'])): ?><button class="btn btn-primary" onclick="openModal('groupModal')">+ New Group</button><?php endif; ?>
</div>

<div class="grid-3">
  <?php foreach ($groups as $g): ?>
    <div class="group-card">
      <div class="group-name"><?= htmlspecialchars($g['name']) ?></div>
      <div class="group-meta">
        Meets <?= htmlspecialchars($g['meeting_day']??'TBA') ?>s · <?= htmlspecialchars($g['meeting_location']??'TBA') ?>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
        <div style="font-size:13px;color:var(--ink-md)">Leader: <strong><?= htmlspecialchars($g['leader_name']??'—') ?></strong></div>
        <span class="badge b-green"><?= $g['member_count'] ?> members</span>
      </div>
      <?php
        $gMembers = $pdo->prepare("SELECT * FROM members WHERE group_id=? AND status='Active' LIMIT 5");
        $gMembers->execute([$g['id']]);
        $gMembers = $gMembers->fetchAll();
      ?>
      <div style="border-top:1px solid var(--border);padding-top:12px">
        <?php foreach ($gMembers as $j => $gm): ?>
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
            <div class="av <?= $avC[$j%4] ?>" style="width:26px;height:26px;font-size:10px"><?= strtoupper(substr($gm['first_name'],0,1).substr($gm['last_name'],0,1)) ?></div>
            <span style="font-size:12.5px"><?= htmlspecialchars($gm['first_name'].' '.$gm['last_name']) ?></span>
          </div>
        <?php endforeach; ?>
        <?php if ($g['member_count'] > 5): ?><p style="font-size:11px;color:var(--ink-lt)">+<?= $g['member_count']-5 ?> more…</p><?php endif; ?>
      </div>
      <?php if(hasRole(['System Administrator'])): ?>
        <form method="POST" style="margin-top:10px" onsubmit="return confirmDelete(this)">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="group_id" value="<?= $g['id'] ?>">
          <button type="submit" class="btn btn-sm btn-danger">Delete Group</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>

<?php if(hasRole(['System Administrator'])): ?>
<div class="modal-backdrop" id="groupModal">
  <div class="modal">
    <div class="modal-hd"><span class="modal-title">New Cell Group</span><button class="modal-x" onclick="closeModal('groupModal')">✕</button></div>
    <div class="modal-body">
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="form-group"><label>Group Name *</label><input name="name" class="form-control" required placeholder="e.g. Nagongera East Cell"></div>
        <div class="form-row">
          <div class="form-group"><label>Leader</label>
            <select name="leader_id" class="form-control">
              <option value="">— Select Leader —</option>
              <?php foreach($members_list as $m): ?><option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>Meeting Day</label>
            <select name="meeting_day" class="form-control">
              <?php foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d): ?><option><?= $d ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group"><label>Meeting Location</label><input name="meeting_location" class="form-control" placeholder="Location or address"></div>
        <div class="modal-ft"><button type="button" class="btn" onclick="closeModal('groupModal')">Cancel</button><button type="submit" class="btn btn-primary">Create Group</button></div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
