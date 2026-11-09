<?php
// pages/profile.php
require_once __DIR__ . '/../config/db.php';
$pageTitle = 'My Profile';
require_once __DIR__ . '/../includes/header.php';

// Find member record linked to current user's name
$member = $pdo->prepare("SELECT m.*, cg.name as group_name FROM members m LEFT JOIN cell_groups cg ON m.group_id=cg.id WHERE CONCAT(m.first_name,' ',m.last_name) LIKE ? LIMIT 1");
$member->execute(['%'.explode(' ',$user['name'])[0].'%']);
$member = $member->fetch();

if (!$member) {
    // Show generic profile for users without a linked member record
    $member = ['first_name'=>explode(' ',$user['name'])[0],'last_name'=>'','group_name'=>'—','phone'=>'—','email'=>'—','gender'=>'—','address'=>'—','registration_date'=>'—','status'=>'Active','notes'=>''];
}

$initials = strtoupper(substr($member['first_name'],0,1).substr($member['last_name']??'',0,1));

// Giving history
$giving = [];
$attCount = 0;
if (!empty($member['id'])) {
    $giving   = $pdo->prepare("SELECT * FROM transactions WHERE member_id=? ORDER BY transaction_date DESC");
    $giving->execute([$member['id']]); $giving = $giving->fetchAll();
    $attCount = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE member_id=? AND status='Present'");
    $attCount->execute([$member['id']]); $attCount = $attCount->fetchColumn();
}

$totalGiving = array_sum(array_column($giving,'amount'));
$typeBadge = ['Tithe'=>'b-green','Offering'=>'b-blue','Donation'=>'b-gold','Pledge'=>'b-purple'];

// Handle profile update
$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='update' && !empty($member['id'])) {
    $pdo->prepare("UPDATE members SET phone=?,email=?,address=? WHERE id=?")
        ->execute([trim($_POST['phone']),trim($_POST['email']),trim($_POST['address']),$member['id']]);
    $msg = 'success|Profile updated!';
    header('Location: /cims/pages/profile.php?updated=1'); exit;
}
if (isset($_GET['updated'])) $msg = 'success|Profile updated!';

[$msgType,$msgText] = $msg ? explode('|',$msg,2) : ['',''];
?>
<?php if ($msgText): ?><div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msgText) ?></div><?php endif; ?>

<!-- Hero -->
<div class="profile-hero">
  <div class="profile-av"><?= htmlspecialchars($initials) ?></div>
  <div>
    <div class="profile-name"><?= htmlspecialchars($member['first_name'].' '.($member['last_name']??'')) ?></div>
    <div class="profile-role"><?= htmlspecialchars($user['role']) ?> · <?= htmlspecialchars($member['group_name']??'—') ?></div>
    <div class="profile-stats">
      <div class="p-stat"><span><?= count($giving) ?></span><p>Contributions</p></div>
      <div class="p-stat"><span>UGX <?= number_format($totalGiving) ?></span><p>Total Giving</p></div>
      <div class="p-stat"><span><?= $attCount ?></span><p>Attended</p></div>
    </div>
  </div>
</div>

<div class="grid-2">
  <!-- Personal Info -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Personal Information</span>
      <?php if (!empty($member['id'])): ?>
        <button class="btn btn-sm" onclick="openModal('editModal')">Edit</button>
      <?php endif; ?>
    </div>
    <?php foreach ([
      ['Phone',        $member['phone']??'—'],
      ['Email',        $member['email']??'—'],
      ['Gender',       $member['gender']??'—'],
      ['Address',      $member['address']??'—'],
      ['Cell Group',   $member['group_name']??'—'],
      ['Member Since', $member['registration_date']??'—'],
      ['Status',       $member['status']??'—'],
    ] as [$label,$val]): ?>
      <div style="display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--parchment);font-size:13px">
        <span class="text-muted" style="font-weight:500"><?= $label ?></span>
        <span style="font-weight:500"><?= htmlspecialchars($val) ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Giving History -->
  <div class="card">
    <div class="card-header"><span class="card-title">My Giving History</span></div>
    <div style="margin-bottom:12px;font-size:12px;color:var(--ink-lt)">Total: <strong style="color:var(--forest)">UGX <?= number_format($totalGiving) ?></strong></div>
    <?php if ($giving): ?>
      <?php foreach ($giving as $t): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--parchment)">
          <div>
            <div style="font-size:13px;font-weight:500"><?= htmlspecialchars($t['transaction_date']) ?></div>
            <?php if ($t['notes']): ?><div style="font-size:11.5px;color:var(--ink-lt)"><?= htmlspecialchars($t['notes']) ?></div><?php endif; ?>
          </div>
          <div style="text-align:right">
            <span class="badge <?= $typeBadge[$t['type']]??'b-gray' ?>"><?= $t['type'] ?></span>
            <div style="font-size:13px;font-weight:600;margin-top:3px">UGX <?= number_format($t['amount']) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="text-muted" style="font-size:13px">No contributions recorded yet.</p>
    <?php endif; ?>
  </div>
</div>

<!-- Prayer Requests -->
<div class="card">
  <div class="card-header">
    <span class="card-title">My Prayer Requests</span>
    <a href="/cims/pages/prayer.php" class="btn btn-sm btn-gold">🙏 New Request</a>
  </div>
  <?php
    $myPrayers = $pdo->query("SELECT * FROM prayer_requests WHERE visibility='public' ORDER BY created_at DESC LIMIT 5")->fetchAll();
    $statusBadge = ['Pending'=>'b-gold','Prayed For'=>'b-blue','Answered'=>'b-green'];
  ?>
  <?php foreach ($myPrayers as $p): ?>
    <div class="prayer-card">
      <div class="prayer-from"><?= htmlspecialchars($p['category']) ?> · <?= date('d M Y', strtotime($p['created_at'])) ?></div>
      <div class="prayer-text">"<?= htmlspecialchars(substr($p['request'],0,180)) ?>"</div>
      <div class="prayer-foot"><span class="badge <?= $statusBadge[$p['status']] ?>"><?= $p['status'] ?></span></div>
    </div>
  <?php endforeach; ?>
  <?php if (!$myPrayers): ?><p class="text-muted" style="font-size:13px">No prayer requests yet.</p><?php endif; ?>
</div>

<!-- Edit Modal -->
<?php if (!empty($member['id'])): ?>
<div class="modal-backdrop" id="editModal">
  <div class="modal">
    <div class="modal-hd"><span class="modal-title">Update Profile</span><button class="modal-x" onclick="closeModal('editModal')">✕</button></div>
    <div class="modal-body">
      <form method="POST">
        <input type="hidden" name="action" value="update">
        <div class="form-group"><label>Phone</label><input name="phone" class="form-control" value="<?= htmlspecialchars($member['phone']??'') ?>"></div>
        <div class="form-group"><label>Email</label><input name="email" type="email" class="form-control" value="<?= htmlspecialchars($member['email']??'') ?>"></div>
        <div class="form-group"><label>Address</label><input name="address" class="form-control" value="<?= htmlspecialchars($member['address']??'') ?>"></div>
        <div class="modal-ft"><button type="button" class="btn" onclick="closeModal('editModal')">Cancel</button><button type="submit" class="btn btn-primary">Save Changes</button></div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
