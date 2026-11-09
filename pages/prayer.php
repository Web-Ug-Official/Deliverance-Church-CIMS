<?php
// pages/prayer.php
require_once __DIR__ . '/../config/db.php';
$pageTitle = 'Prayer Requests';
require_once __DIR__ . '/../includes/header.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'add') {
    $pdo->prepare("INSERT INTO prayer_requests (requester_name,category,request,visibility) VALUES (?,?,?,?)")
        ->execute([trim($_POST['req_name'])||'Anonymous',$_POST['category'],trim($_POST['request']),$_POST['visibility']]);
    $msg = 'success|Prayer request submitted. God bless you! 🙏';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'update_status') {
    requireRole(['System Administrator','Church Leader']);
    $pdo->prepare("UPDATE prayer_requests SET status=? WHERE id=?")->execute([$_POST['new_status'],$_POST['pr_id']]);
    $msg = 'success|Status updated.';
}

$isLeader = hasRole(['System Administrator','Church Leader']);
$where = $isLeader ? '1=1' : "visibility='public'";
$list = $pdo->query("SELECT * FROM prayer_requests WHERE $where ORDER BY created_at DESC")->fetchAll();

$statusBadge = ['Pending'=>'b-gold','Prayed For'=>'b-blue','Answered'=>'b-green'];
[$msgType, $msgText] = $msg ? explode('|', $msg, 2) : ['',''];
?>
<?php if ($msgText): ?><div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msgText) ?></div><?php endif; ?>

<div class="page-header">
  <h3>Prayer Requests</h3>
  <button class="btn btn-gold" onclick="openModal('prayerModal')">🙏 Submit Request</button>
</div>

<?php foreach ($list as $p): ?>
  <div class="prayer-card">
    <div class="prayer-from">🙏 <?= htmlspecialchars($p['requester_name']) ?> · <?= htmlspecialchars($p['category']) ?> · <?= date('d M Y', strtotime($p['created_at'])) ?></div>
    <div class="prayer-text">"<?= nl2br(htmlspecialchars($p['request'])) ?>"</div>
    <div class="prayer-foot">
      <span class="badge <?= $statusBadge[$p['status']]??'b-gray' ?>"><?= $p['status'] ?></span>
      <?php if ($isLeader && $p['status'] !== 'Answered'): ?>
        <form method="POST" style="display:inline">
          <input type="hidden" name="action" value="update_status">
          <input type="hidden" name="pr_id" value="<?= $p['id'] ?>">
          <input type="hidden" name="new_status" value="<?= $p['status']==='Pending'?'Prayed For':'Answered' ?>">
          <button type="submit" class="btn btn-sm"><?= $p['status']==='Pending'?'Mark Prayed For':'Mark Answered' ?></button>
        </form>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
<?php if (!$list): ?><p class="text-muted">No prayer requests found.</p><?php endif; ?>

<div class="modal-backdrop" id="prayerModal">
  <div class="modal">
    <div class="modal-hd"><span class="modal-title">Submit Prayer Request</span><button class="modal-x" onclick="closeModal('prayerModal')">✕</button></div>
    <div class="modal-body">
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="form-row">
          <div class="form-group"><label>Your Name</label><input name="req_name" class="form-control" placeholder="Anonymous (optional)"></div>
          <div class="form-group"><label>Category</label>
            <select name="category" class="form-control">
              <option>Healing</option><option>Family</option><option>Finances</option><option>Guidance</option><option>Thanksgiving</option><option>Other</option>
            </select>
          </div>
        </div>
        <div class="form-group"><label>Prayer Request *</label><textarea name="request" class="form-control" rows="4" required placeholder="Share your prayer request…"></textarea></div>
        <div class="form-group"><label>Visibility</label>
          <select name="visibility" class="form-control">
            <option value="leaders">Pastors &amp; Leaders Only</option>
            <option value="public">Whole Congregation</option>
          </select>
        </div>
        <div class="modal-ft"><button type="button" class="btn" onclick="closeModal('prayerModal')">Cancel</button><button type="submit" class="btn btn-gold">🙏 Submit</button></div>
      </form>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
