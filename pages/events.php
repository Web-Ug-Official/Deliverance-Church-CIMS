<?php
// pages/events.php
require_once __DIR__ . '/../config/db.php';
$pageTitle = 'Events & Schedules';
require_once __DIR__ . '/../includes/header.php';

$msg = '';
$canEdit = hasRole(['System Administrator','Event Coordinator']);

// Add Event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'add' && $canEdit) {
    $pdo->prepare("INSERT INTO events (name,event_date,event_time,location,category,description,created_by) VALUES (?,?,?,?,?,?,?)")
        ->execute([trim($_POST['name']),$_POST['event_date'],$_POST['event_time'],trim($_POST['location']),$_POST['category'],trim($_POST['description']),$user['id']]);
    $msg = 'success|Event created!';
}

// Delete Event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'delete' && $canEdit) {
    $pdo->prepare("DELETE FROM events WHERE id=?")->execute([$_POST['event_id']]);
    $msg = 'success|Event deleted.';
}

$upcoming = $pdo->query("SELECT e.*, COUNT(a.id) as att_count, SUM(a.status='Present') as present_count FROM events e LEFT JOIN attendance a ON e.id=a.event_id WHERE e.event_date >= CURDATE() GROUP BY e.id ORDER BY e.event_date")->fetchAll();
$past     = $pdo->query("SELECT e.*, COUNT(a.id) as att_count, SUM(a.status='Present') as present_count FROM events e LEFT JOIN attendance a ON e.id=a.event_id WHERE e.event_date < CURDATE() GROUP BY e.id ORDER BY e.event_date DESC LIMIT 10")->fetchAll();

$catBadge = ['Worship Service'=>'b-green','Youth Meeting'=>'b-blue','Prayer Night'=>'b-purple','Conference'=>'b-gold','Outreach'=>'b-red','Other'=>'b-gray'];

[$msgType, $msgText] = $msg ? explode('|', $msg, 2) : ['',''];
?>

<?php if ($msgText): ?><div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msgText) ?></div><?php endif; ?>

<div class="page-header">
  <h3>Events & Schedules</h3>
  <?php if ($canEdit): ?><button class="btn btn-primary" onclick="openModal('eventModal')">+ New Event</button><?php endif; ?>
</div>

<div class="section-sub">Upcoming (<?= count($upcoming) ?>)</div>
<?php if ($upcoming): ?>
  <?php foreach ($upcoming as $ev): ?>
    <div class="event-card">
      <div class="event-date">
        <?= date('D, d M Y', strtotime($ev['event_date'])) ?> at <?= date('H:i', strtotime($ev['event_time'])) ?>
        &nbsp;<span class="badge <?= $catBadge[$ev['category']]??'b-gray' ?>"><?= $ev['category'] ?></span>
      </div>
      <div class="event-name"><?= htmlspecialchars($ev['name']) ?></div>
      <div class="event-loc">📍 <?= htmlspecialchars($ev['location']) ?></div>
      <?php if ($ev['description']): ?><p style="font-size:12.5px;color:var(--ink-lt);margin-top:6px"><?= htmlspecialchars($ev['description']) ?></p><?php endif; ?>
      <div class="event-foot">
        <?php if ($ev['att_count'] > 0): ?><span class="badge b-gray">👥 <?= $ev['present_count'] ?>/<?= $ev['att_count'] ?> attended</span><?php endif; ?>
        <?php if ($canEdit): ?>
          <form method="POST" style="display:inline" onsubmit="return confirmDelete(this)">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <p class="text-muted mb-2">No upcoming events.</p>
<?php endif; ?>

<?php if ($past): ?>
  <div class="section-sub" style="margin-top:20px">Past Events</div>
  <?php foreach ($past as $ev): ?>
    <div class="event-card" style="opacity:.75">
      <div class="event-date">
        <?= date('D, d M Y', strtotime($ev['event_date'])) ?>
        &nbsp;<span class="badge b-gray">Past</span>
        &nbsp;<span class="badge <?= $catBadge[$ev['category']]??'b-gray' ?>"><?= $ev['category'] ?></span>
      </div>
      <div class="event-name"><?= htmlspecialchars($ev['name']) ?></div>
      <div class="event-loc">📍 <?= htmlspecialchars($ev['location']) ?><?= $ev['att_count'] > 0 ? ' · '.htmlspecialchars($ev['present_count']).' attended' : '' ?></div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<!-- Add Event Modal -->
<?php if ($canEdit): ?>
<div class="modal-backdrop" id="eventModal">
  <div class="modal">
    <div class="modal-hd">
      <span class="modal-title">Create Event</span>
      <button class="modal-x" onclick="closeModal('eventModal')">✕</button>
    </div>
    <div class="modal-body">
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="form-group"><label>Event Name *</label><input name="name" class="form-control" required placeholder="Sunday Service"></div>
        <div class="form-row">
          <div class="form-group"><label>Date *</label><input name="event_date" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
          <div class="form-group"><label>Time</label><input name="event_time" type="time" class="form-control" value="09:00"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Location</label><input name="location" class="form-control" placeholder="Main Hall"></div>
          <div class="form-group"><label>Category</label>
            <select name="category" class="form-control">
              <option>Worship Service</option><option>Youth Meeting</option><option>Prayer Night</option>
              <option>Conference</option><option>Outreach</option><option>Other</option>
            </select>
          </div>
        </div>
        <div class="form-group"><label>Description</label><textarea name="description" class="form-control" placeholder="Event details…"></textarea></div>
        <div class="modal-ft">
          <button type="button" class="btn" onclick="closeModal('eventModal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Event</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
