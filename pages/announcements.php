<?php
// pages/announcements.php
require_once __DIR__ . '/../config/db.php';
$pageTitle = 'Announcements';
require_once __DIR__ . '/../includes/header.php';

$msg = '';
$canPost = hasRole(['System Administrator','Church Leader']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'add' && $canPost) {
    $pdo->prepare("INSERT INTO announcements (title,message,priority,posted_by) VALUES (?,?,?,?)")
        ->execute([trim($_POST['title']),trim($_POST['message']),$_POST['priority'],$user['id']]);
    $msg = 'success|Announcement posted!';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'delete' && $canPost) {
    $pdo->prepare("DELETE FROM announcements WHERE id=?")->execute([$_POST['ann_id']]);
    $msg = 'success|Announcement removed.';
}

$list = $pdo->query("SELECT a.*, u.full_name as author FROM announcements a LEFT JOIN users u ON a.posted_by=u.id ORDER BY a.created_at DESC")->fetchAll();
[$msgType, $msgText] = $msg ? explode('|', $msg, 2) : ['',''];
?>
<?php if ($msgText): ?><div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msgText) ?></div><?php endif; ?>

<div class="page-header">
  <h3>Announcements</h3>
  <?php if ($canPost): ?><button class="btn btn-primary" onclick="openModal('annModal')">+ Post Announcement</button><?php endif; ?>
</div>

<?php foreach ($list as $a): ?>
  <div class="notice <?= htmlspecialchars($a['priority']) ?>">
    <div class="notice-title"><?= htmlspecialchars($a['title']) ?></div>
    <div class="notice-body"><?= nl2br(htmlspecialchars($a['message'])) ?></div>
    <div class="notice-meta">
      Posted: <?= date('d M Y, H:i', strtotime($a['created_at'])) ?>
      · By: <?= htmlspecialchars($a['author'] ?? 'Admin') ?>
      <?php if ($canPost): ?>
        <form method="POST" style="display:inline" onsubmit="return confirmDelete(this)">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="ann_id" value="<?= $a['id'] ?>">
          <button type="submit" class="btn btn-sm btn-danger" style="margin-left:8px">Remove</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
<?php if (!$list): ?><p class="text-muted">No announcements yet.</p><?php endif; ?>

<?php if ($canPost): ?>
<div class="modal-backdrop" id="annModal">
  <div class="modal">
    <div class="modal-hd"><span class="modal-title">Post Announcement</span><button class="modal-x" onclick="closeModal('annModal')">✕</button></div>
    <div class="modal-body">
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="form-row">
          <div class="form-group"><label>Title *</label><input name="title" class="form-control" required placeholder="Announcement title"></div>
          <div class="form-group"><label>Priority</label>
            <select name="priority" class="form-control"><option value="normal">Normal</option><option value="info">Info</option><option value="urgent">Urgent</option></select>
          </div>
        </div>
        <div class="form-group"><label>Message *</label><textarea name="message" class="form-control" rows="5" required placeholder="Write your announcement…"></textarea></div>
        <div class="modal-ft"><button type="button" class="btn" onclick="closeModal('annModal')">Cancel</button><button type="submit" class="btn btn-primary">Post</button></div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
