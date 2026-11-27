<?php
require_once 'includes/db_connect.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$full_name    = trim(($_SESSION['first_name'] ?? 'Maria') . ' ' . ($_SESSION['last_name'] ?? 'Clara'));
$user_id      = $_SESSION['user_id'];
$business_raw = trim((string)($_SESSION['business_name'] ?? ''));
$business     = strtoupper($business_raw !== '' ? $business_raw : 'BUSINESS NAME STAFF');

$liv_stmt = $pdo->prepare("
    SELECT COALESCE(SUM(l.quantity), 0)
    FROM `livestock` l
    JOIN `user` u ON l.user_id COLLATE utf8mb4_unicode_ci = u.user_id COLLATE utf8mb4_unicode_ci
    WHERE LOWER(TRIM(u.business_name)) = LOWER(TRIM(:bname))
");
$liv_stmt->execute(['bname' => $business_raw]);
$total_livestock = (int)$liv_stmt->fetchColumn();

$stmt_p = $pdo->prepare("SELECT COUNT(*) FROM `task_schedule` WHERE `user_id` = :uid AND `task_status` = 'Pending'");
$stmt_p->execute(['uid' => $user_id]);
$pending_tasks = (int)$stmt_p->fetchColumn();

$stmt_t = $pdo->prepare("
    SELECT task_id, task_name, task_description, schedule_date
    FROM `task_schedule`
    WHERE `user_id` = :uid AND `task_status` != 'Completed'
    ORDER BY schedule_date ASC, task_id ASC
    LIMIT 5
");
$stmt_t->execute(['uid' => $user_id]);
$tasks = $stmt_t->fetchAll();

$stmt_h = $pdo->prepare("
    SELECT task_id, task_name, task_description, schedule_date
    FROM `task_schedule`
    WHERE `user_id` = :uid AND `task_status` = 'Completed'
    ORDER BY schedule_date DESC, task_id DESC
    LIMIT 5
");
$stmt_h->execute(['uid' => $user_id]);
$recent_history = $stmt_h->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff Dashboard - LivestockPro</title>
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
        <li><a class="active" href="staff_dashboard.php">Dashboard</a></li>
        <li><a href="livestock.php">Livestock</a></li>
        <li><a href="task_schedule.php">Task Schedule</a></li>
        <li><a href="expenses.php">Expenses</a></li>
        <li><a href="sales.php">Sales</a></li>
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
        <div>
          <h1 class="page-title">Dashboard</h1>
          <div class="page-subtitle">Welcome, <?= htmlspecialchars($full_name) ?>!</div>
          <div class="page-meta-tag"><?= htmlspecialchars($business) ?></div>
        </div>
        <div class="page-datetime"><?= date('F d, Y | H:i:s') ?></div>
      </div>

      <div class="kpi-grid-4">
        <div class="kpi-card kpi-card--highlight">
          <div class="kpi-label">Total Livestock</div>
          <div class="kpi-sub">As of <?= date('H:i:s') ?></div>
          <div class="kpi-value"><?= number_format($total_livestock) ?></div>
          <a class="kpi-link" href="livestock.php">View Livestock &rarr;</a>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Pending Tasks</div>
          <div class="kpi-sub">As of <?= date('H:i:s') ?></div>
          <div class="kpi-value"><?= $pending_tasks ?></div>
          <a class="kpi-link" href="task_schedule.php">View Pending Tasks &rarr;</a>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Expenses</div>
          <div class="kpi-sub">As of <?= date('H:i:s') ?></div>
          <div class="kpi-value">10%</div>
          <a class="kpi-link" href="expenses.php">View Total Income &rarr;</a>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Sales Of The Day</div>
          <div class="kpi-sub">As of <?= date('H:i:s') ?></div>
          <div class="kpi-value">10%</div>
          <a class="kpi-link" href="sales.php">View Profit &rarr;</a>
        </div>
      </div>

      <div class="split-grid">
        <div class="card">
          <div class="card-title">Upcoming Tasks</div>
          <table class="data-table">
            <thead><tr><th>NO.</th><th>ID.</th><th>TASK</th><th>DESCRIPTION</th><th>DATE</th></tr></thead>
            <tbody>
              <?php if (empty($tasks)): ?>
                <tr><td colspan="5" style="text-align:center;color:var(--text-muted);">No upcoming tasks assigned to you.</td></tr>
              <?php endif; ?>
              <?php foreach ($tasks as $i => $t): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td><?= htmlspecialchars($t['task_id']) ?></td>
                  <td><?= strtoupper(htmlspecialchars($t['task_name'])) ?></td>
                  <td><?= strtoupper(htmlspecialchars($t['task_description'] ?? 'SCHEDULED TASK')) ?></td>
                  <td style="white-space:nowrap;"><?= htmlspecialchars($t['schedule_date']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="card">
          <div class="card-title">Recent History</div>
          <?php if (empty($recent_history)): ?>
            <div style="font-size:12px;color:var(--text-muted);padding:6px 0;">No completed tasks yet.</div>
          <?php endif; ?>
          <?php foreach ($recent_history as $h): ?>
            <div class="history-row">
              <div class="history-left">
                <div class="history-dot"></div>
                <div>
                  <div style="font-size:12px;font-weight:600;"><?= htmlspecialchars($full_name) ?></div>
                  <div style="font-size:10px;color:var(--text-secondary);"><?= htmlspecialchars($user_id) ?> &middot; <?= htmlspecialchars($h['task_name']) ?></div>
                </div>
              </div>
              <div style="font-size:11px;color:var(--text-secondary);">Finished Tasked</div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </main>
  </div>
</body>
</html>