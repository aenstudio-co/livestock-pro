<?php
require_once 'includes/db_connect.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$full_name = trim(($_SESSION['first_name'] ?? 'Juan') . ' ' . ($_SESSION['last_name'] ?? 'Dela Cruz'));
$user_id   = $_SESSION['user_id'];
$role      = strtoupper($_SESSION['user_type'] ?? 'O');
$tab       = $_GET['tab'] ?? 'Pending';
$message   = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    $tname = trim($_POST['task_name'] ?? '');
    $sdate = substr(trim($_POST['schedule_date'] ?? date('Y-m-d')), 0, 10);
    $tdesc = trim($_POST['task_description'] ?? '');
    if ($tname !== '') {
        $count  = (int)$pdo->query("SELECT COUNT(*) FROM `task_schedule`")->fetchColumn() + 1;
        $new_id = sprintf("TSK-%05d", $count);
        $pdo->prepare("
            INSERT INTO `task_schedule` (`task_id`, `user_id`, `task_name`, `task_description`, `schedule_date`, `task_status`)
            VALUES (:tid, :uid, :tname, :tdesc, :sdate, 'Pending')
        ")->execute([
            'tid'   => $new_id,
            'uid'   => $user_id,
            'tname' => $tname,
            'tdesc' => $tdesc ?: null,
            'sdate' => $sdate
        ]);
        $message = "Task {$new_id} added.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $pdo->prepare("UPDATE `task_schedule` SET `task_status` = :st WHERE `task_id` = :tid")
        ->execute(['st' => $_POST['task_status'], 'tid' => $_POST['task_id']]);
    header("Location: task_schedule.php");
    exit;
}

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM `task_schedule` WHERE `task_id` = :tid")->execute(['tid' => $_GET['delete']]);
    header("Location: task_schedule.php");
    exit;
}

$pending   = $pdo->query("SELECT * FROM `task_schedule` WHERE `task_status` != 'Completed' ORDER BY `task_id` DESC")->fetchAll();
$completed = $pdo->query("SELECT * FROM `task_schedule` WHERE `task_status` = 'Completed' ORDER BY `task_id` DESC")->fetchAll();

$cnt_progress  = (int)$pdo->query("SELECT COUNT(*) FROM `task_schedule` WHERE `task_status` = 'In Progress'")->fetchColumn();
$cnt_cancelled = (int)$pdo->query("SELECT COUNT(*) FROM `task_schedule` WHERE `task_status` = 'Cancelled'")->fetchColumn();
$cnt_pending   = (int)$pdo->query("SELECT COUNT(*) FROM `task_schedule` WHERE `task_status` = 'Pending'")->fetchColumn();
$cnt_completed = (int)$pdo->query("SELECT COUNT(*) FROM `task_schedule` WHERE `task_status` = 'Completed'")->fetchColumn();

$stmt_tab = $pdo->prepare("SELECT * FROM `task_schedule` WHERE `task_status` = :st ORDER BY `task_id` DESC");
$stmt_tab->execute(['st' => $tab]);
$filtered_tasks = $stmt_tab->fetchAll();
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
        <div class="sidebar-brand-title">&#128004; LivestockPro</div>
        <div class="sidebar-brand-sub">Farm Management Tracker</div>
      </div>
      <ul class="sidebar-nav">
        <li><a href="staff_dashboard.php">Dashboard</a></li>
        <li><a href="livestock.php">Livestock</a></li>
        <li><a class="active" href="task_schedule.php">Task Schedule</a></li>
        <li><a href="expenses.php">Expenses</a></li>
        <li><a href="sales.php">Sales</a></li>
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

      <?php if ($role === 'O'): ?>
        <div class="card">
          <div class="card-title">Add Task</div>
          <form method="POST" action="task_schedule.php">
            <div class="form-grid-2">
              <div class="form-group"><label class="form-label">TASK NAME *</label><input class="form-control" type="text" id="task_name" name="task_name" required></div>
              <div class="form-group"><label class="form-label">SCHEDULE TIME</label><input class="form-control" type="datetime-local" name="schedule_date" value="2026-12-13T01:00"></div>
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
                  <option value="<?= htmlspecialchars($user_id) ?>"><?= htmlspecialchars($user_id . ' - ' . $full_name) ?></option>
                </select>
              </div>
              <div class="form-group"><label class="form-label">DESCRIPTION</label><textarea class="form-control" name="task_description"></textarea></div>
            </div>
            <button class="btn btn-primary" type="submit" name="add_task">&#10003; ADD TASK</button>
          </form>
        </div>

        <div class="card">
          <div class="card-title">Pending Tasks (<?= count($pending) ?>)</div>
          <table class="data-table">
            <thead><tr><th>NO.</th><th>ID.</th><th>NAME</th><th>DESCRIPTION</th><th>DATE</th><th>STAFF ASSIGNED</th><th>STATUS</th><th>ACTIONS</th></tr></thead>
            <tbody>
              <?php foreach ($pending as $i => $t): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td><?= htmlspecialchars($t['task_id']) ?></td>
                  <td><?= strtoupper(htmlspecialchars($t['task_name'])) ?></td>
                  <td><?= strtoupper(htmlspecialchars($t['task_description'] ?? 'SCHEDULED TASK')) ?></td>
                  <td><?= htmlspecialchars($t['schedule_date']) ?></td>
                  <td><?= htmlspecialchars($full_name) ?></td>
                  <td>
                    <form method="POST" action="task_schedule.php" style="display:inline;">
                      <input type="hidden" name="task_id" value="<?= htmlspecialchars($t['task_id']) ?>">
                      <select name="task_status" class="badge badge-pending" onchange="this.form.submit()" style="border:none;cursor:pointer;">
                        <option value="Pending" <?= $t['task_status'] === 'Pending' ? 'selected' : '' ?>>PENDING</option>
                        <option value="In Progress" <?= $t['task_status'] === 'In Progress' ? 'selected' : '' ?>>IN PROGRESS</option>
                        <option value="Completed">COMPLETED</option>
                      </select>
                      <input type="hidden" name="update_status" value="1">
                    </form>
                  </td>
                  <td><a class="btn btn-sm btn-delete" href="task_schedule.php?delete=<?= urlencode($t['task_id']) ?>">&#128465;</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="card">
          <div class="card-title">Task Completed (<?= count($completed) ?>)</div>
          <table class="data-table">
            <thead><tr><th>NO.</th><th>ID.</th><th>NAME</th><th>DESCRIPTION</th><th>DATE</th><th>STAFF ASSIGNED</th><th>STATUS</th><th>ACTIONS</th></tr></thead>
            <tbody>
              <?php foreach ($completed as $i => $t): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td><?= htmlspecialchars($t['task_id']) ?></td>
                  <td><?= strtoupper(htmlspecialchars($t['task_name'])) ?></td>
                  <td><?= strtoupper(htmlspecialchars($t['task_description'] ?? 'SCHEDULED TASK')) ?></td>
                  <td><?= htmlspecialchars($t['schedule_date']) ?></td>
                  <td><?= htmlspecialchars($full_name) ?></td>
                  <td><span class="badge badge-completed">COMPLETED</span></td>
                  <td><a class="btn btn-sm btn-delete" href="task_schedule.php?delete=<?= urlencode($t['task_id']) ?>">&#128465;</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="status-tabs">
          <a class="status-tab <?= $tab === 'In Progress' ? 'active' : '' ?>" href="task_schedule.php?tab=In+Progress">&#9203; <?= $cnt_progress ?> IN PROGRESS</a>
          <a class="status-tab <?= $tab === 'Cancelled' ? 'active' : '' ?>" href="task_schedule.php?tab=Cancelled">&#10006; <?= $cnt_cancelled ?> CANCELLED</a>
          <a class="status-tab <?= $tab === 'Pending' ? 'active' : '' ?>" href="task_schedule.php?tab=Pending">&#128337; <?= $cnt_pending ?> PENDING</a>
          <a class="status-tab <?= $tab === 'Completed' ? 'active' : '' ?>" href="task_schedule.php?tab=Completed">&#10004; <?= $cnt_completed ?> COMPLETED</a>
        </div>
        <div class="card">
          <div class="card-title"><?= htmlspecialchars($tab) ?> Tasks (<?= count($filtered_tasks) ?>)</div>
          <table class="data-table">
            <thead><tr><th>NO.</th><th>ID.</th><th>NAME</th><th>DESCRIPTION</th><th>DATE</th><th>STATUS</th></tr></thead>
            <tbody>
              <?php foreach ($filtered_tasks as $i => $t): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td><?= htmlspecialchars($t['task_id']) ?></td>
                  <td><?= strtoupper(htmlspecialchars($t['task_name'])) ?></td>
                  <td><?= strtoupper(htmlspecialchars($t['task_description'] ?? 'SCHEDULED TASK')) ?></td>
                  <td><?= htmlspecialchars($t['schedule_date']) ?></td>
                  <td><span class="badge badge-pending"><?= strtoupper(htmlspecialchars($t['task_status'])) ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </main>
  </div>
  <script>function setTask(val) { document.getElementById('task_name').value = val; }</script>
</body>
</html>
