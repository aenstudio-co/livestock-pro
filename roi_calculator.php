<?php
require_once 'includes/db_connect.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$full_name = trim(($_SESSION['first_name'] ?? 'Juan') . ' ' . ($_SESSION['last_name'] ?? 'Dela Cruz'));
$user_id   = $_SESSION['user_id'];

$total_livestock = (int)$pdo->query("SELECT COALESCE(SUM(quantity), 0) FROM `livestock`")->fetchColumn();
$total_expenses  = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM `expenses`")->fetchColumn();
$total_income    = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM `sales`")->fetchColumn();
$profit          = $total_income - $total_expenses;
$roi             = ($total_expenses > 0) ? (($total_income - $total_expenses) / $total_expenses) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ROI Calculator - LivestockPro</title>
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
        <li><a href="owner_dashboard.php">Dashboard</a></li>
        <li><a href="livestock.php">Livestock</a></li>
        <li><a href="task_schedule.php">Task Schedule</a></li>
        <li><a href="expenses.php">Expenses</a></li>
        <li><a href="sales.php">Sales</a></li>
        <li><a class="active" href="roi_calculator.php">ROI Calculator</a></li>
        <li><a href="reports.php">Reports</a></li>
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
        <h1 class="page-title">ROI Calculator</h1>
        <div class="page-datetime"><?= date('F d, Y | H:i:s') ?></div>
      </div>

      <div class="roi-banner">
        <div>
          <div class="kpi-sub">Return On Investment (ROI)</div>
          <div style="font-size:32px;font-weight:700;">= <?= number_format($roi, 2) ?>%</div>
        </div>
        <div style="background:#fff;border-radius:12px;padding:12px 18px;max-width:340px;">
          <div style="font-size:11px;font-weight:700;">Remarks:</div>
          <div style="font-size:11px;color:var(--text-secondary);">
            <?= $profit >= 0 ? "Your farm is profitable. Great Job!" : "Your expenses currently exceed income." ?>
          </div>
        </div>
      </div>

      <div class="kpi-grid-4">
        <div class="kpi-card"><div class="kpi-label">Total Livestock</div><div class="kpi-sub">As of <?= date('H:i:s') ?></div><div class="kpi-value"><?= number_format($total_livestock) ?></div></div>
        <div class="kpi-card"><div class="kpi-label">Total Expenses</div><div class="kpi-sub">As of <?= date('H:i:s') ?></div><div class="kpi-value">&#8369;<?= number_format($total_expenses, 0) ?></div></div>
        <div class="kpi-card"><div class="kpi-label">Total Income</div><div class="kpi-sub">As of <?= date('H:i:s') ?></div><div class="kpi-value">&#8369;<?= number_format($total_income, 0) ?></div></div>
        <div class="kpi-card"><div class="kpi-label">Profit</div><div class="kpi-sub">As of <?= date('H:i:s') ?></div><div class="kpi-value">&#8369;<?= number_format($profit, 0) ?></div></div>
      </div>

      <div class="card">
        <div class="card-title">How ROI is Calculated?</div>
        <div class="roi-formula-box">ROI = (Income &minus; Expenses) &divide; Expenses &times; 100</div>
        <div class="roi-formula-box">
          ROI = (&#8369;<?= number_format($total_income, 2) ?> &minus; &#8369;<?= number_format($total_expenses, 2) ?>) &divide; &#8369;<?= number_format($total_expenses, 2) ?> &times; 100 = <?= number_format($roi, 2) ?>%
        </div>
      </div>
    </main>
  </div>
</body>
</html>
