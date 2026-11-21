<?php
require_once 'includes/db_connect.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$full_name = trim(($_SESSION['first_name'] ?? 'Juan') . ' ' . ($_SESSION['last_name'] ?? 'Dela Cruz'));
$user_id   = $_SESSION['user_id'];
$business  = strtoupper($_SESSION['business_name'] ?? 'BUSINESS NAME OWNER');

$total_livestock = (int)$pdo->query("SELECT COALESCE(SUM(quantity), 0) FROM `livestock`")->fetchColumn();
$total_expenses  = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM `expenses`")->fetchColumn();
$total_income    = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM `sales`")->fetchColumn();
$profit          = $total_income - $total_expenses;

$tasks = $pdo->query("SELECT task_id, task_name, task_description, schedule_date FROM `task_schedule` ORDER BY schedule_date ASC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - LivestockPro</title>
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
        <li><a class="active" href="owner_dashboard.php">Dashboard</a></li>
        <li><a href="livestock.php">Livestock</a></li>
        <li><a href="task_schedule.php">Task Schedule</a></li>
        <li><a href="expenses.php">Expenses</a></li>
        <li><a href="sales.php">Sales</a></li>
        <li><a href="reports.php">Reports</a></li>
      </ul>
      <div class="sidebar-user">
        <div class="sidebar-avatar"><?= strtoupper(substr($full_name, 0, 2)) ?></div>
        <div>
          <div class="sidebar-user-name"><?= htmlspecialchars($full_name) ?></div>
          <div class="sidebar-user-id"><?= htmlspecialchars($user_id) ?></div>
        </div>
      </div>
    </aside>

    <main class="main-content">
      <div class="page-header">
        <div>
          <h1 class="page-title">Dashboard</h1>
          <div class="page-subtitle"> Welcome, <?= htmlspecialchars($full_name) ?>!</div>
          <div class="page-meta-tag"><?= htmlspecialchars($business) ?></div>
        </div>
        <div style="display:flex;align-items:center;gap:12px;">
          <span class="page-datetime"><?= date('F d, Y | H:i:s') ?></span>
        </div>
      </div>

      <div class="kpi-grid-4">
        <div class="kpi-card kpi-card--highlight">
          <div class="kpi-label">Total Livestock</div>
          <div class="kpi-sub">As of <?= date('H:i:s') ?></div>
          <div class="kpi-value"><?= number_format($total_livestock) ?></div>
          <a class="kpi-link" href="livestock.php">View Livestock &rarr;</a>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Total Expenses</div>
          <div class="kpi-sub">As of <?= date('H:i:s') ?></div>
          <div class="kpi-value">&#8369;<?= number_format($total_expenses, 0) ?></div>
          <a class="kpi-link" href="expenses.php">View Total Expenses &rarr;</a>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Total Income</div>
          <div class="kpi-sub">As of <?= date('H:i:s') ?></div>
          <div class="kpi-value">&#8369;<?= number_format($total_income, 0) ?></div>
          <a class="kpi-link" href="sales.php">View Total Income &rarr;</a>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Profit</div>
          <div class="kpi-sub">As of <?= date('H:i:s') ?></div>
          <div class="kpi-value">&#8369;<?= number_format($profit, 0) ?></div>
          <a class="kpi-link" href="reports.php">View Profit &rarr;</a>
        </div>
      </div>

      <div class="split-grid">
        <div class="card">
          <div class="card-title">Upcoming Tasks</div>
          <table class="data-table">
            <thead>
              <tr><th>NO.</th><th>ID.</th><th>TASK</th><th>DESCRIPTION</th><th>DATE</th></tr>
            </thead>
            <tbody>
              <?php foreach ($tasks as $i => $t): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td><?= htmlspecialchars($t['task_id']) ?></td>
                  <td><?= strtoupper(htmlspecialchars($t['task_name'])) ?></td>
                  <td><?= strtoupper(htmlspecialchars($t['task_description'] ?? 'SCHEDULED TASK')) ?></td>
                  <td><?= htmlspecialchars($t['schedule_date']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="card">
          <div class="card-title">Recent History</div>
          <?php foreach ($tasks as $t): ?>
            <div class="history-row">
              <div class="history-left">
                <div class="history-dot"></div>
                <div>
                  <div style="font-size:12px;font-weight:600;"><?= htmlspecialchars($full_name) ?></div>
                  <div style="font-size:10px;color:var(--text-secondary);"><?= htmlspecialchars($user_id) ?></div>
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