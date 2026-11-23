<?php
require_once 'includes/db_connect.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$full_name = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
if ($full_name === '') { $full_name = 'User'; }
$user_id   = $_SESSION['user_id'];
$role      = strtoupper($_SESSION['user_type'] ?? 'O');
$message   = "";

$staff_rows = $pdo->query("
    SELECT u.user_id, u.first_name, u.last_name
    FROM `staff` s
    JOIN `user` u ON s.user_id COLLATE utf8mb4_unicode_ci = u.user_id COLLATE utf8mb4_unicode_ci
    ORDER BY u.first_name ASC, u.last_name ASC
")->fetchAll();

$staff_map = [];
foreach ($staff_rows as $sr) {
    $staff_map[$sr['user_id']] = trim($sr['first_name'] . ' ' . $sr['last_name']);
}
if (!isset($staff_map[$user_id])) {
    $staff_map[$user_id] = $full_name;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    $tname          = trim($_POST['task_name'] ?? '');
    $sdate          = substr(trim($_POST['schedule_date'] ?? date('Y-m-d')), 0, 10);
    $tdesc          = trim($_POST['task_description'] ?? '');
    $assigned_staff = trim($_POST['assigned_staff'] ?? '');
    $target_user_id = ($assigned_staff !== '') ? $assigned_staff : $user_id;

    if ($tname !== '') {
        $count  = (int)$pdo->query("SELECT COUNT(*) FROM `task_schedule`")->fetchColumn() + 1;
        $new_id = sprintf("TSK-%05d", $count);

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $stmt = $pdo->prepare("UPDATE `task_schedule` SET `task_status` = :st WHERE `task_id` = :tid");
    $stmt->execute(['st' => $_POST['task_status'], 'tid' => $_POST['task_id']]);
    header("Location: task_schedule.php");
    exit;
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM `task_schedule` WHERE `task_id` = :tid");
    $stmt->execute(['tid' => $_GET['delete']]);
    header("Location: task_schedule.php");
    exit;
}

$cnt_pending     = (int)$pdo->query("SELECT COUNT(*) FROM `task_schedule` WHERE `task_status` = 'Pending'")->fetchColumn();
$cnt_in_progress = (int)$pdo->query("SELECT COUNT(*) FROM `task_schedule` WHERE `task_status` = 'In Progress'")->fetchColumn();
$cnt_cancelled   = (int)$pdo->query("SELECT COUNT(*) FROM `task_schedule` WHERE `task_status` = 'Cancelled'")->fetchColumn();

$pending       = $pdo->query("SELECT * FROM `task_schedule` WHERE `task_status` != 'Completed' ORDER BY `task_id` DESC")->fetchAll();
$completed     = $pdo->query("SELECT * FROM `task_schedule` WHERE `task_status` = 'Completed' ORDER BY `task_id` DESC")->fetchAll();
$cnt_completed = count($completed);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Task Schedule - LivestockPro</title>
  <link rel="stylesheet" href="css/style.css">
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
        <li><a href="<?= $role === 'O' ? 'owner_dashboard.php' : 'staff_dashboard.php' ?>">Dashboard</a></li>
        <li><a href="livestock.php">Livestock</a></li>
        <li><a class="active" href="task_schedule.php">Task Schedule</a></li>
        <li><a href="expenses.php">Expenses</a></li>
        <li><a href="sales.php">Sales</a></li>
        <?php if ($role === 'O'): ?>
          <li><a href="reports.php">Reports</a></li>
        <?php endif; ?>
        <li><a href="profile.php">My Profile</a></li>
      </ul>
      <div class="sidebar-user">
        <div class="sidebar-avatar"><?= strtoupper(substr($full_name, 0, 2)) ?></div>
        <div style="flex:1;">
          <div class="sidebar-user-name"><?= htmlspecialchars($full_name) ?></div>
          <div class="sidebar-user-id"><?= htmlspecialchars($user_id) ?> &middot; <a href="logout.php" style="color:rgba(255,255,255,0.75);font-size:10px;">Logout</a></div>
        </div>
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

      <div class="status-holder-grid">
        <div class="status-holder-card">
          <div class="status-holder-label">Pending</div>
          <div class="status-holder-count"><?= $cnt_pending ?></div>
        </div>
        <div class="status-holder-card">
          <div class="status-holder-label">In Progress</div>
          <div class="status-holder-count"><?= $cnt_in_progress ?></div>
        </div>
        <div class="status-holder-card">
          <div class="status-holder-label">Completed</div>
          <div class="status-holder-count"><?= $cnt_completed ?></div>
        </div>
        <div class="status-holder-card">
          <div class="status-holder-label">Cancelled</div>
          <div class="status-holder-count"><?= $cnt_cancelled ?></div>
        </div>
      </div>

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
                $status_val = $t['task_status'] ?? 'Pending';
                if ($status_val === 'In Progress') {
                  $btn_cls = 'is-in-progress';
                } elseif ($status_val === 'Completed') {
                  $btn_cls = 'is-completed';
                } elseif ($status_val === 'Cancelled') {
                  $btn_cls = 'is-cancelled';
                } else {
                  $btn_cls = 'is-pending';
                }
                $assigned_name = $staff_map[$t['user_id']] ?? $t['user_id'];
              ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($t['task_id']) ?></td>
                <td><?= strtoupper(htmlspecialchars($t['task_name'])) ?></td>
                <td><?= strtoupper(htmlspecialchars($t['task_description'] ?? 'SCHEDULED TASK')) ?></td>
                <td style="white-space:nowrap;"><?= htmlspecialchars($t['schedule_date']) ?></td>
                <td><?= htmlspecialchars($assigned_name) ?></td>
                <td>
                  <div class="status-dd">
                    <button type="button" class="status-dd-btn <?= $btn_cls ?>" onclick="toggleStatusMenu(this)">
                      <span><?= strtoupper(htmlspecialchars($status_val)) ?></span>
                      <span>&#9662;</span>
                    </button>
                    <div class="status-dd-menu">
                      <form method="POST" action="task_schedule.php">
                        <input type="hidden" name="task_id" value="<?= htmlspecialchars($t['task_id']) ?>">
                        <input type="hidden" name="task_status" value="Pending">
                        <input type="hidden" name="update_status" value="1">
                        <button type="submit" class="status-dd-opt opt-pending">PENDING</button>
                      </form>
                      <form method="POST" action="task_schedule.php">
                        <input type="hidden" name="task_id" value="<?= htmlspecialchars($t['task_id']) ?>">
                        <input type="hidden" name="task_status" value="In Progress">
                        <input type="hidden" name="update_status" value="1">
                        <button type="submit" class="status-dd-opt opt-in-progress">IN PROGRESS</button>
                      </form>
                      <form method="POST" action="task_schedule.php">
                        <input type="hidden" name="task_id" value="<?= htmlspecialchars($t['task_id']) ?>">
                        <input type="hidden" name="task_status" value="Completed">
                        <input type="hidden" name="update_status" value="1">
                        <button type="submit" class="status-dd-opt opt-completed">COMPLETED</button>
                      </form>
                      <form method="POST" action="task_schedule.php">
                        <input type="hidden" name="task_id" value="<?= htmlspecialchars($t['task_id']) ?>">
                        <input type="hidden" name="task_status" value="Cancelled">
                        <input type="hidden" name="update_status" value="1">
                        <button type="submit" class="status-dd-opt opt-cancelled">CANCELLED</button>
                      </form>
                    </div>
                  </div>
                </td>
                <td><a class="btn btn-sm btn-delete" href="task_schedule.php?delete=<?= urlencode($t['task_id']) ?>" title="Delete"><svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg></a></td>
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
                <td>
                  <div class="status-dd">
                    <button type="button" class="status-dd-btn is-completed" onclick="toggleStatusMenu(this)">
                      <span>COMPLETED</span>
                      <span>&#9662;</span>
                    </button>
                    <div class="status-dd-menu">
                      <form method="POST" action="task_schedule.php">
                        <input type="hidden" name="task_id" value="<?= htmlspecialchars($t['task_id']) ?>">
                        <input type="hidden" name="task_status" value="Pending">
                        <input type="hidden" name="update_status" value="1">
                        <button type="submit" class="status-dd-opt opt-pending">PENDING</button>
                      </form>
                      <form method="POST" action="task_schedule.php">
                        <input type="hidden" name="task_id" value="<?= htmlspecialchars($t['task_id']) ?>">
                        <input type="hidden" name="task_status" value="In Progress">
                        <input type="hidden" name="update_status" value="1">
                        <button type="submit" class="status-dd-opt opt-in-progress">IN PROGRESS</button>
                      </form>
                      <form method="POST" action="task_schedule.php">
                        <input type="hidden" name="task_id" value="<?= htmlspecialchars($t['task_id']) ?>">
                        <input type="hidden" name="task_status" value="Completed">
                        <input type="hidden" name="update_status" value="1">
                        <button type="submit" class="status-dd-opt opt-completed">COMPLETED</button>
                      </form>
                      <form method="POST" action="task_schedule.php">
                        <input type="hidden" name="task_id" value="<?= htmlspecialchars($t['task_id']) ?>">
                        <input type="hidden" name="task_status" value="Cancelled">
                        <input type="hidden" name="update_status" value="1">
                        <button type="submit" class="status-dd-opt opt-cancelled">CANCELLED</button>
                      </form>
                    </div>
                  </div>
                </td>
                <td><a class="btn btn-sm btn-delete" href="task_schedule.php?delete=<?= urlencode($t['task_id']) ?>" title="Delete"><svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg></a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>

  <script>
    function setTask(val) {
      document.getElementById('task_name').value = val;
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
  </script>
</body>
</html>
