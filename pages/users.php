<?php
// pages/users.php
require_once __DIR__ . '/../config/db.php';
$pageTitle = 'User Accounts';
require_once __DIR__ . '/../includes/header.php';
requireRole(['System Administrator']);

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'add') {
    if (strlen($_POST['password']) < 6) {
        $msg = 'danger|Password must be at least 6 characters.';
    } else {
        $exists = $pdo->prepare("SELECT id FROM users WHERE username=?");
        $exists->execute([trim($_POST['username'])]);
        if ($exists->fetch()) {
            $msg = 'danger|Username already exists.';
        } else {
            $hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $pdo->prepare("INSERT INTO users (full_name,username,password,role,status) VALUES (?,?,?,?,'Active')")
                ->execute([trim($_POST['full_name']),trim($_POST['username']),$hash,$_POST['role']]);
            $msg = 'success|User account created!';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'toggle' && $_POST['user_id'] != $user['id']) {
    $cur = $pdo->prepare("SELECT status FROM users WHERE id=?"); $cur->execute([$_POST['user_id']]);
    $row = $cur->fetch();
    $new = $row['status'] === 'Active' ? 'Inactive' : 'Active';
    $pdo->prepare("UPDATE users SET status=? WHERE id=?")->execute([$new,$_POST['user_id']]);
    $msg = 'success|User status updated.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'delete' && $_POST['user_id'] != $user['id']) {
    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$_POST['user_id']]);
    $msg = 'success|User removed.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'reset_pw') {
    if (strlen($_POST['new_password']) < 6) {
        $msg = 'danger|Password must be at least 6 characters.';
    } else {
        $hash = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash,$_POST['user_id']]);
        $msg = 'success|Password reset successfully.';
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY role, full_name")->fetchAll();
$roleBadge = ['System Administrator'=>'b-purple','Finance Officer'=>'b-gold','Church Leader'=>'b-green','Church Member'=>'b-blue','Event Coordinator'=>'b-gray'];
$avC = ['av-g','av-gold','av-b','av-r'];

[$msgType,$msgText] = $msg ? explode('|',$msg,2) : ['',''];
?>
<?php if ($msgText): ?><div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msgText) ?></div><?php endif; ?>

<div class="page-header">
  <h3>User Accounts</h3>
  <button class="btn btn-primary" onclick="openModal('userModal')">+ Add User</button>
</div>

<div class="card">
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>User</th><th>Username</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($users as $i => $u): ?>
          <tr>
            <td>
              <div class="member-cell">
                <div class="av <?= $avC[$i%4] ?>"><?= strtoupper(substr($u['full_name'],0,1).(strpos($u['full_name'],' ')!==false?substr($u['full_name'],strpos($u['full_name'],' ')+1,1):'')) ?></div>
                <span style="font-weight:500"><?= htmlspecialchars($u['full_name']) ?></span>
              </div>
            </td>
            <td style="color:var(--ink-lt);font-family:monospace"><?= htmlspecialchars($u['username']) ?></td>
            <td><span class="badge <?= $roleBadge[$u['role']]??'b-gray' ?>"><?= htmlspecialchars($u['role']) ?></span></td>
            <td><span class="badge <?= $u['status']==='Active'?'b-green':'b-gray' ?>"><?= $u['status'] ?></span></td>
            <td>
              <?php if ($u['id'] != $user['id']): ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn btn-sm"><?= $u['status']==='Active'?'Disable':'Enable' ?></button>
                </form>
                <button class="btn btn-sm btn-gold" onclick="document.getElementById('resetForm<?= $u['id'] ?>').style.display='block'">Reset PW</button>
                <form id="resetForm<?= $u['id'] ?>" method="POST" style="display:none;margin-top:6px">
                  <input type="hidden" name="action" value="reset_pw">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <input type="password" name="new_password" class="form-control" placeholder="New password" style="width:160px;display:inline-block;margin-right:6px">
                  <button type="submit" class="btn btn-sm btn-primary">Set</button>
                </form>
                <form method="POST" style="display:inline" onsubmit="return confirmDelete(this)">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                </form>
              <?php else: ?>
                <span class="text-muted" style="font-size:12px">Current user</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-backdrop" id="userModal">
  <div class="modal">
    <div class="modal-hd"><span class="modal-title">Add User Account</span><button class="modal-x" onclick="closeModal('userModal')">✕</button></div>
    <div class="modal-body">
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="form-row">
          <div class="form-group"><label>Full Name *</label><input name="full_name" class="form-control" required placeholder="Full name"></div>
          <div class="form-group"><label>Username *</label><input name="username" class="form-control" required placeholder="username"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Password *</label><input name="password" type="password" class="form-control" required placeholder="Min 6 characters"></div>
          <div class="form-group"><label>Role *</label>
            <select name="role" class="form-control">
              <option>System Administrator</option><option>Finance Officer</option>
              <option>Church Leader</option><option selected>Church Member</option><option>Event Coordinator</option>
            </select>
          </div>
        </div>
        <div class="modal-ft">
          <button type="button" class="btn" onclick="closeModal('userModal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Create User</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
