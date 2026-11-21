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

$exp_count = (int)$pdo->query("SELECT COUNT(*) FROM `expenses`")->fetchColumn();
$sls_count = (int)$pdo->query("SELECT COUNT(*) FROM `sales`")->fetchColumn();

$by_type = $pdo->query("
    SELECT animal_type, COALESCE(SUM(quantity), 0) AS qty
    FROM `livestock`
    GROUP BY animal_type
    ORDER BY qty DESC
")->fetchAll();

$by_exp_cat = $pdo->query("
    SELECT category, COUNT(*) AS cnt, COALESCE(SUM(amount), 0) AS total_amt
    FROM `expenses`
    GROUP BY category
    ORDER BY total_amt DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Report - LivestockPro</title>
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
        <li><a href="roi_calculator.php">ROI Calculator</a></li>
        <li><a class="active" href="reports.php">Reports</a></li>
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
        <div>
          <h1 class="page-title">Report</h1>
          <div class="page-subtitle">Livestock Farm Summary Report</div>
          <div class="page-meta-tag">FARMER: <?= htmlspecialchars($full_name) ?> | GENERATED: <?= date('F d, Y 	 g:i A') ?></div>
        </div>
        <div class="page-datetime"><?= date('F d, Y | H:i:s') ?></div>
      </div>

      <div class="kpi-grid-4">
        <div class="kpi-card"><div class="kpi-label">Total Livestock</div><div class="kpi-sub">As of <?= date('H:i:s') ?></div><div class="kpi-value"><?= number_format($total_livestock) ?></div><a class="kpi-link" href="livestock.php">View Livestock &rarr;</a></div>
        <div class="kpi-card"><div class="kpi-label">Total Expenses</div><div class="kpi-sub">As of <?= date('H:i:s') ?></div><div class="kpi-value">&#8369;<?= number_format($total_expenses, 0) ?></div><a class="kpi-link" href="expenses.php">View Total Expenses &rarr;</a></div>
        <div class="kpi-card"><div class="kpi-label">Total Income</div><div class="kpi-sub">As of <?= date('H:i:s') ?></div><div class="kpi-value">&#8369;<?= number_format($total_income, 0) ?></div><a class="kpi-link" href="sales.php">View Total Income &rarr;</a></div>
        <div class="kpi-card"><div class="kpi-label">Profit</div><div class="kpi-sub">As of <?= date('H:i:s') ?></div><div class="kpi-value">&#8369;<?= number_format($profit, 0) ?></div><a class="kpi-link" href="roi_calculator.php">View Profit &rarr;</a></div>
      </div>

      <div class="roi-banner">
        <div>
          <div class="kpi-sub">Return On Investment (ROI)</div>
          <div style="font-size:28px;font-weight:700;">= <?= number_format($roi, 2) ?>%</div>
        </div>
        <div style="display:flex;gap:12px;">
          <div style="background:#fff;padding:10px 16px;border-radius:10px;font-size:12px;font-weight:600;"><?= $exp_count ?> Expense Record(s)</div>
          <div style="background:#fff;padding:10px 16px;border-radius:10px;font-size:12px;font-weight:600;"><?= $sls_count ?> Income Record(s)</div>
        </div>
      </div>

      <div class="split-grid">
        <div class="card">
          <div class="card-title">Livestock Summary (By Type)</div>
          <table class="data-table">
            <thead><tr><th>NO.</th><th>ANIMAL TYPE</th><th>QUANTITY</th></tr></thead>
            <tbody>
              <?php foreach ($by_type as $i => $row): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td><?= strtoupper(htmlspecialchars($row['animal_type'])) ?></td>
                  <td><?= number_format((int)$row['qty']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="card">
          <div class="card-title">Top Expenses Category</div>
          <table class="data-table">
            <thead><tr><th>NO.</th><th>EXPENSES</th><th>QUANTITY</th><th>TOTAL</th></tr></thead>
            <tbody>
              <?php foreach ($by_exp_cat as $i => $row): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td><?= strtoupper(htmlspecialchars($row['category'])) ?></td>
                  <td><?= (int)$row['cnt'] ?></td>
                  <td>&#8369;<?= number_format((float)$row['total_amt'], 2) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
