<?php
require_once 'includes/db_connect.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$full_name = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
if ($full_name === '') { $full_name = 'User'; }
$user_id   = $_SESSION['user_id'];
$role      = strtoupper($_SESSION['user_type'] ?? 'O');
$business  = trim((string)($_SESSION['business_name'] ?? ''));
$is_owner  = ($role === 'O');
$message   = "";

$allowed_status = ['Pending', 'In Progress', 'Completed', 'Cancelled'];

$staff_stmt = $pdo->prepare("
    SELECT u.user_id, u.first_name, u.last_name
    FROM `staff` s
    JOIN `user` u ON s.user_id COLLATE utf8mb4_unicode_ci = u.user_id COLLATE utf8mb4_unicode_ci
    WHERE LOWER(TRIM(u.business_name)) = LOWER(TRIM(:bname))
      AND LOWER(TRIM(COALESCE(s.staff_status, 'pending'))) = 'approved'
    ORDER BY u.first_name ASC, u.last_name ASC
");
$staff_stmt->execute(['bname' => $business]);
$staff_rows = $staff_stmt->fetchAll();

$all_users_stmt = $pdo->prepare("
    SELECT user_id, first_name, last_name
    FROM `user`
    WHERE LOWER(TRIM(business_name)) = LOWER(TRIM(:bname))
");
$all_users_stmt->execute(['bname' => $business]);
$staff_map = [];
foreach ($all_users_stmt->fetchAll() as $ur) {
    $staff_map[$ur['user_id']] = trim($ur['first_name'] . ' ' . $ur['last_name']);
}
if (!isset($staff_map[$user_id])) {
    $staff_map[$user_id] = $full_name;
}

if ($is_owner && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    $tname          = trim($_POST['task_name'] ?? '');
    $sdate          = substr(trim($_POST['schedule_date'] ?? date('Y-m-d')), 0, 10);
    $tdesc          = trim($_POST['task_description'] ?? '');
    $assigned_staff = trim($_POST['assigned_staff'] ?? '');
    $target_user_id = ($assigned_staff !== '') ? $assigned_staff : $user_id;

    if ($tname !== '') {
        $max    = (int)$pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING(`task_id`, 5) AS UNSIGNED)), 0) FROM `task_schedule`")->fetchColumn();
        $new_id = sprintf("TSK-%05d", $max + 1);

        $stmt = $pdo->prepare("
            INSERT INTO `task_schedule` (`task_id`, `user_id`, `task_name`, `task_description`, `schedule_date`, `task_status`)
            VALUES (:tid, :uid, :tname, :tdesc, :sdate, 'Pending')
        ");
        $stmt->execute([
            'tid'   => $new_id,
            'uid'   => $target_user_id,
            'tname' => $tname,
            'tdesc' => $tdesc !== '' ? $tdesc : null,
            'sdate' => $sdate
        ]);
        $message = "Task {$new_id} added.";
    }
}

if ($is_owner && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_task'])) {
    $tid   = trim($_POST['task_id'] ?? '');
    $tname = trim($_POST['task_name'] ?? '');
    $tdesc = trim($_POST['task_description'] ?? '');
    $sdate = substr(trim($_POST['schedule_date'] ?? ''), 0, 10);
    $uid   = trim($_POST['assigned_staff'] ?? '');

    if ($tid !== '' && $tname !== '' && $sdate !== '' && $uid !== '') {
        $stmt = $pdo->prepare("
            UPDATE `task_schedule`
            SET `task_name` = :tname, `task_description` = :tdesc, `schedule_date` = :sdate, `user_id` = :uid
            WHERE `task_id` = :tid
        ");
        $stmt->execute([
            'tname' => $tname,
            'tdesc' => $tdesc !== '' ? $tdesc : null,
            'sdate' => $sdate,
            'uid'   => $uid,
            'tid'   => $tid
        ]);
    }
    header("Location: task_schedule.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['task_status'] ?? '';
    $tid        = $_POST['task_id'] ?? '';

    if (in_array($new_status, $allowed_status, true) && $tid !== '') {
        if ($is_owner) {
            $stmt = $pdo->prepare("UPDATE `task_schedule` SET `task_status` = :st WHERE `task_id` = :tid");
            $stmt->execute(['st' => $new_status, 'tid' => $tid]);
        } else {
            $stmt = $pdo->prepare("UPDATE `task_schedule` SET `task_status` = :st WHERE `task_id` = :tid AND `user_id` = :uid");
            $stmt->execute(['st' => $new_status, 'tid' => $tid, 'uid' => $user_id]);
        }
    }
    header("Location: task_schedule.php");
    exit;
}

if ($is_owner && isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM `task_schedule` WHERE `task_id` = :tid");
    $stmt->execute(['tid' => $_GET['delete']]);
    header("Location: task_schedule.php");
    exit;
}

if ($is_owner) {
    $stmt = $pdo->prepare("
        SELECT ts.*
        FROM `task_schedule` ts
        JOIN `user` u ON ts.user_id COLLATE utf8mb4_unicode_ci = u.user_id COLLATE utf8mb4_unicode_ci
        WHERE LOWER(TRIM(u.business_name)) = LOWER(TRIM(:bname))
          AND ts.`task_status` != 'Completed'
        ORDER BY ts.`task_id` DESC
    ");
    $stmt->execute(['bname' => $business]);
    $pending = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT ts.*
        FROM `task_schedule` ts
        JOIN `user` u ON ts.user_id COLLATE utf8mb4_unicode_ci = u.user_id COLLATE utf8mb4_unicode_ci
        WHERE LOWER(TRIM(u.business_name)) = LOWER(TRIM(:bname))
          AND ts.`task_status` = 'Completed'
        ORDER BY ts.`task_id` DESC
    ");
    $stmt->execute(['bname' => $business]);
    $completed = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT * FROM `task_schedule` WHERE `user_id` = :uid AND `task_status` != 'Completed' ORDER BY `task_id` DESC");
    $stmt->execute(['uid' => $user_id]);
    $pending = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM `task_schedule` WHERE `user_id` = :uid AND `task_status` = 'Completed' ORDER BY `task_id` DESC");
    $stmt->execute(['uid' => $user_id]);
    $completed = $stmt->fetchAll();
}

$cnt_pending = $cnt_in_progress = $cnt_cancelled = 0;
foreach ($pending as $t) {
    $s = $t['task_status'] ?? 'Pending';
    if ($s === 'In Progress')    { $cnt_in_progress++; }
    elseif ($s === 'Cancelled')  { $cnt_cancelled++; }
    else                         { $cnt_pending++; }
}
$cnt_completed = count($completed);

function render_status_dropdown(array $t, string $status_val): void {
    if ($status_val === 'In Progress')      { $btn_cls = 'is-in-progress'; }
    elseif ($status_val === 'Completed')    { $btn_cls = 'is-completed'; }
    elseif ($status_val === 'Cancelled')    { $btn_cls = 'is-cancelled'; }
    else                                    { $btn_cls = 'is-pending'; }

    $options = [
        ['Pending',     'PENDING',     'opt-pending'],
        ['In Progress', 'IN PROGRESS', 'opt-in-progress'],
        ['Completed',   'COMPLETED',   'opt-completed'],
        ['Cancelled',   'CANCELLED',   'opt-cancelled'],
    ];
    ?>
    <div class="status-dd">
      <button type="button" class="status-dd-btn <?= $btn_cls ?>" onclick="toggleStatusMenu(this)">
        <span><?= strtoupper(htmlspecialchars($status_val)) ?></span>
        <span>&#9662;</span>
      </button>
      <div class="status-dd-menu">
        <?php foreach ($options as $o): ?>
          <form method="POST" action="task_schedule.php">
            <input type="hidden" name="task_id" value="<?= htmlspecialchars($t['task_id']) ?>">
            <input type="hidden" name="task_status" value="<?= $o[0] ?>">
            <input type="hidden" name="update_status" value="1">
            <button type="submit" class="status-dd-opt <?= $o[2] ?>"><?= $o[1] ?></button>
          </form>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Task Schedule - LivestockPro</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    .staff-status-bar {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      background: #cfe3e3;
      border-radius: 14px;
      margin-bottom: 20px;
      overflow: hidden;
    }
    .staff-status-item {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      padding: 20px 12px;
      color: #0b4a4a;
      font-weight: 700;
      font-size: 13px;
      letter-spacing: .3px;
      text-transform: uppercase;
      border-right: 1px solid rgba(11, 74, 74, 0.18);
    }
    .staff-status-item:last-child { border-right: none; }
    .staff-status-item svg { width: 26px; height: 26px; flex-shrink: 0; }
    @media (max-width: 900px) {
      .staff-status-bar { grid-template-columns: repeat(2, 1fr); }
    }

    .actions-cell { display: flex; gap: 6px; justify-content: center; align-items: center; }
    .btn-edit {
      background: #e3e7e7; color: #3d4b4b; border: none; cursor: pointer;
      font-size: 11px; font-weight: 700; letter-spacing: .4px;
      padding: 6px 12px; border-radius: 6px; text-decoration: none;
    }
    .btn-edit:hover { background: #d2d9d9; }
    .modal-backdrop {
      display: none; position: fixed; inset: 0; z-index: 1000;
      background: rgba(0, 0, 0, 0.45); align-items: center; justify-content: center;
    }
    .modal-backdrop.open { display: flex; }
    .modal-box {
      background: #fff; border-radius: 14px; padding: 24px;
      width: 100%; max-width: 640px; max-height: 90vh; overflow-y: auto;
    }
    .modal-actions { display: flex; gap: 10px; margin-top: 12px; }
  </style>
</head>
<body>
  <div class="app-shell">
    <aside class="sidebar">
      <div class="sidebar-brand">
        <div class="sidebar-brand-row">
          <img src="assets/logo.png" alt="LivestockPro Logo" class="sidebar-logo-img">
          <div>
            <div class="sidebar-brand-title">LivestockPro</div>
            <div class="sidebar-brand-sub">Farm Management Tracker</div>
          </div>
        </div>
      </div>
      <ul class="sidebar-nav">
        <li><a href="<?= $is_owner ? 'owner_dashboard.php' : 'staff_dashboard.php' ?>">Dashboard</a></li>
        <li><a href="livestock.php">Livestock</a></li>
        <li><a class="active" href="task_schedule.php">Task Schedule</a></li>
        <li><a href="expenses.php">Expenses</a></li>
        <li><a href="sales.php">Sales</a></li>
        <?php if ($is_owner): ?>
          <li><a href="reports.php">Reports</a></li>
        <?php endif; ?>
      </ul>
      <div class="sidebar-user-wrap">
        <a class="sidebar-user-link" href="profile.php">
          <div class="sidebar-avatar"><?= strtoupper(substr($full_name, 0, 2)) ?></div>
          <div style="flex:1;">
            <div class="sidebar-user-name"><?= htmlspecialchars($full_name) ?></div>
            <div class="sidebar-user-id"><?= htmlspecialchars($user_id) ?></div>
          </div>
        </a>
        <a class="sidebar-logout-link" href="logout.php">Logout</a>
      </div>
    </aside>

    <main class="main-content">
      <div class="page-header">
        <h1 class="page-title">Task Schedule</h1>
        <div class="page-datetime"><?= date('F d, Y | H:i:s') ?></div>
      </div>

      <?php if ($message !== ""): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <div class="staff-status-bar">
        <div class="staff-status-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="17" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><circle cx="12" cy="15.5" r="2.5"/><polyline points="12 14.3 12 15.5 12.9 16"/></svg>
          <span><?= $cnt_pending ?> Pending</span>
        </div>
        <div class="staff-status-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke-dasharray="3 3"/><polyline points="9 12 11 14 15 10"/></svg>
          <span><?= $cnt_in_progress ?> In Progress</span>
        </div>
        <div class="staff-status-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="17" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><polyline points="9 15.5 11 17.5 15 13.5"/></svg>
          <span><?= $cnt_completed ?> Completed</span>
        </div>
        <div class="staff-status-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="17" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="10" y1="14" x2="14" y2="18"/><line x1="14" y1="14" x2="10" y2="18"/></svg>
          <span><?= $cnt_cancelled ?> Cancelled</span>
        </div>
      </div>

      <?php if ($is_owner): ?>
      <div class="card">
        <div class="card-title">Add Task</div>
        <form method="POST" action="task_schedule.php">
          <div class="form-grid-2">
            <div class="form-group">
              <label class="form-label">TASK NAME *</label>
              <input class="form-control" type="text" id="task_name" name="task_name" required>
            </div>
            <div class="form-group">
              <label class="form-label">SCHEDULE TIME</label>
              <input class="form-control" type="datetime-local" name="schedule_date" value="<?= date('Y-m-d\TH:i') ?>">
            </div>
          </div>

          <div class="form-label">QUICK ADD</div>
          <div class="quick-chips">
            <button type="button" class="chip-btn" onclick="setTask('Feed Animals')">Feed Animals</button>
            <button type="button" class="chip-btn" onclick="setTask('Vaccination')">Vaccination</button>
            <button type="button" class="chip-btn" onclick="setTask('Clean Pen')">Clean Pen</button>
            <button type="button" class="chip-btn" onclick="setTask('Weigh Livestock')">Weigh Livestock</button>
            <button type="button" class="chip-btn" onclick="setTask('Give Medicine')">Give Medicine</button>
          </div>

          <div class="form-grid-2">
            <div class="form-group">
              <label class="form-label">ASSIGN STAFF</label>
              <select class="form-control" name="assigned_staff">
                <option value="<?= htmlspecialchars($user_id) ?>"><?= htmlspecialchars($user_id . ' - ' . $full_name . ' (Me)') ?></option>
                <?php foreach ($staff_rows as $sr): ?>
                  <?php if ($sr['user_id'] !== $user_id): ?>
                    <?php $s_label = $sr['user_id'] . ' - ' . trim($sr['first_name'] . ' ' . $sr['last_name']); ?>
                    <option value="<?= htmlspecialchars($sr['user_id']) ?>"><?= htmlspecialchars($s_label) ?></option>
                  <?php endif; ?>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">DESCRIPTION</label>
              <textarea class="form-control" name="task_description"></textarea>
            </div>
          </div>

          <button class="btn btn-primary" type="submit" name="add_task"><svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg> ADD TASK</button>
        </form>
      </div>

      <div class="card">
        <div class="card-title">Pending Tasks (<?= count($pending) ?>)</div>
        <table class="data-table">
          <thead>
            <tr><th>NO.</th><th>ID.</th><th>NAME</th><th>DESCRIPTION</th><th>DATE</th><th>STAFF ASSIGNED</th><th>STATUS</th><th>ACTIONS</th></tr>
          </thead>
          <tbody>
            <?php foreach ($pending as $i => $t): ?>
              <?php
                $status_val    = $t['task_status'] ?? 'Pending';
                $assigned_name = $staff_map[$t['user_id']] ?? $t['user_id'];
              ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($t['task_id']) ?></td>
                <td><?= strtoupper(htmlspecialchars($t['task_name'])) ?></td>
                <td><?= strtoupper(htmlspecialchars($t['task_description'] ?? 'SCHEDULED TASK')) ?></td>
                <td style="white-space:nowrap;"><?= htmlspecialchars($t['schedule_date']) ?></td>
                <td><?= htmlspecialchars($assigned_name) ?></td>
                <td><?php render_status_dropdown($t, $status_val); ?></td>
                <td>
                  <div class="actions-cell">
                    <button type="button" class="btn-edit"
                      data-id="<?= htmlspecialchars($t['task_id']) ?>"
                      data-name="<?= htmlspecialchars($t['task_name']) ?>"
                      data-desc="<?= htmlspecialchars($t['task_description'] ?? '') ?>"
                      data-date="<?= htmlspecialchars(substr($t['schedule_date'], 0, 10)) ?>"
                      data-staff="<?= htmlspecialchars($t['user_id']) ?>"
                      onclick="openEditModal(this)">EDIT</button>
                    <a class="btn btn-sm btn-delete" href="task_schedule.php?delete=<?= urlencode($t['task_id']) ?>" title="Delete" onclick="return confirm('Delete this task?');"><svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg></a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="card">
        <div class="card-title">Task Completed (<?= count($completed) ?>)</div>
        <table class="data-table">
          <thead>
            <tr><th>NO.</th><th>ID.</th><th>NAME</th><th>DESCRIPTION</th><th>DATE</th><th>STAFF ASSIGNED</th><th>STATUS</th><th>ACTIONS</th></tr>
          </thead>
          <tbody>
            <?php foreach ($completed as $i => $t): ?>
              <?php $assigned_name = $staff_map[$t['user_id']] ?? $t['user_id']; ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($t['task_id']) ?></td>
                <td><?= strtoupper(htmlspecialchars($t['task_name'])) ?></td>
                <td><?= strtoupper(htmlspecialchars($t['task_description'] ?? 'SCHEDULED TASK')) ?></td>
                <td style="white-space:nowrap;"><?= htmlspecialchars($t['schedule_date']) ?></td>
                <td><?= htmlspecialchars($assigned_name) ?></td>
                <td><?php render_status_dropdown($t, 'Completed'); ?></td>
                <td>
                  <div class="actions-cell">
                    <a class="btn btn-sm btn-delete" href="task_schedule.php?delete=<?= urlencode($t['task_id']) ?>" title="Delete" onclick="return confirm('Delete this task?');"><svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg></a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="modal-backdrop" id="editModal">
        <div class="modal-box">
          <div class="card-title">Edit Task</div>
          <form method="POST" action="task_schedule.php">
            <input type="hidden" name="task_id" id="edit_task_id">
            <div class="form-grid-2">
              <div class="form-group">
                <label class="form-label">TASK NAME *</label>
                <input class="form-control" type="text" name="task_name" id="edit_task_name" required>
              </div>
              <div class="form-group">
                <label class="form-label">SCHEDULE DATE</label>
                <input class="form-control" type="date" name="schedule_date" id="edit_schedule_date" required>
              </div>
            </div>
            <div class="form-grid-2">
              <div class="form-group">
                <label class="form-label">ASSIGN STAFF</label>
                <select class="form-control" name="assigned_staff" id="edit_assigned_staff">
                  <option value="<?= htmlspecialchars($user_id) ?>"><?= htmlspecialchars($user_id . ' - ' . $full_name . ' (Me)') ?></option>
                  <?php foreach ($staff_rows as $sr): ?>
                    <?php if ($sr['user_id'] !== $user_id): ?>
                      <option value="<?= htmlspecialchars($sr['user_id']) ?>"><?= htmlspecialchars($sr['user_id'] . ' - ' . trim($sr['first_name'] . ' ' . $sr['last_name'])) ?></option>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">DESCRIPTION</label>
                <textarea class="form-control" name="task_description" id="edit_task_description"></textarea>
              </div>
            </div>
            <div class="modal-actions">
              <button class="btn btn-primary" type="submit" name="edit_task">SAVE CHANGES</button>
              <button class="btn" type="button" onclick="closeEditModal()">CANCEL</button>
            </div>
          </form>
        </div>
      </div>

      <?php else: ?>
      <div class="card">
        <div class="card-title">Assigned Tasks (<?= count($pending) ?>)</div>
        <table class="data-table">
          <thead>
            <tr><th>NO.</th><th>ID.</th><th>NAME</th><th>DESCRIPTION</th><th>DATE</th><th>STAFF ASSIGNED</th><th>STATUS</th></tr>
          </thead>
          <tbody>
            <?php if (count($pending) === 0): ?>
              <tr><td colspan="7" style="text-align:center;">No tasks assigned to you.</td></tr>
            <?php endif; ?>
            <?php foreach ($pending as $i => $t): ?>
              <?php
                $status_val    = $t['task_status'] ?? 'Pending';
                $assigned_name = $staff_map[$t['user_id']] ?? $t['user_id'];
              ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($t['task_id']) ?></td>
                <td><?= strtoupper(htmlspecialchars($t['task_name'])) ?></td>
                <td><?= strtoupper(htmlspecialchars($t['task_description'] ?? 'SCHEDULED TASK')) ?></td>
                <td style="white-space:nowrap;"><?= htmlspecialchars($t['schedule_date']) ?></td>
                <td><?= htmlspecialchars($assigned_name) ?></td>
                <td><?php render_status_dropdown($t, $status_val); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="card">
        <div class="card-title">Task Completed (<?= count($completed) ?>)</div>
        <table class="data-table">
          <thead>
            <tr><th>NO.</th><th>ID.</th><th>NAME</th><th>DESCRIPTION</th><th>DATE</th><th>STAFF ASSIGNED</th><th>STATUS</th></tr>
          </thead>
          <tbody>
            <?php if (count($completed) === 0): ?>
              <tr><td colspan="7" style="text-align:center;">No completed tasks yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($completed as $i => $t): ?>
              <?php $assigned_name = $staff_map[$t['user_id']] ?? $t['user_id']; ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($t['task_id']) ?></td>
                <td><?= strtoupper(htmlspecialchars($t['task_name'])) ?></td>
                <td><?= strtoupper(htmlspecialchars($t['task_description'] ?? 'SCHEDULED TASK')) ?></td>
                <td style="white-space:nowrap;"><?= htmlspecialchars($t['schedule_date']) ?></td>
                <td><?= htmlspecialchars($assigned_name) ?></td>
                <td><?php render_status_dropdown($t, 'Completed'); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php endif; ?>
    </main>
  </div>

  <script>
    function setTask(val) {
      var el = document.getElementById('task_name');
      if (el) el.value = val;
    }

    function toggleStatusMenu(btn) {
      const dd = btn.closest('.status-dd');
      const isOpen = dd.classList.contains('open');
      document.querySelectorAll('.status-dd.open').forEach(el => el.classList.remove('open'));
      if (!isOpen) dd.classList.add('open');
    }

    document.addEventListener('click', function (e) {
      if (!e.target.closest('.status-dd')) {
        document.querySelectorAll('.status-dd.open').forEach(el => el.classList.remove('open'));
      }
    });

    <?php if ($is_owner): ?>
    function openEditModal(btn) {
      document.getElementById('edit_task_id').value          = btn.dataset.id;
      document.getElementById('edit_task_name').value        = btn.dataset.name;
      document.getElementById('edit_task_description').value = btn.dataset.desc;
      document.getElementById('edit_schedule_date').value    = btn.dataset.date;
      document.getElementById('edit_assigned_staff').value   = btn.dataset.staff;
      document.getElementById('editModal').classList.add('open');
    }
    function closeEditModal() {
      document.getElementById('editModal').classList.remove('open');
    }
    document.getElementById('editModal').addEventListener('click', function (e) {
      if (e.target === this) closeEditModal();
    });
    <?php endif; ?>
  </script>
</body>
</html>