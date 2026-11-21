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

$sales_by_item = $pdo->query("
    SELECT
        COALESCE(l.breed, l.animal_type, 'LIVESTOCK') AS item_name,
        COALESCE(SUM(s.quantity_sold), 0) AS total_qty,
        COALESCE(SUM(s.amount), 0) AS total_amt,
        CASE WHEN COALESCE(SUM(s.quantity_sold), 0) > 0
             THEN COALESCE(SUM(s.amount), 0) / SUM(s.quantity_sold)
             ELSE 0 END AS avg_price
    FROM `sales` s
    LEFT JOIN `livestock` l ON s.livestock_id = l.livestock_id
    GROUP BY l.livestock_id, l.breed, l.animal_type
    ORDER BY total_amt DESC
    LIMIT 6
")->fetchAll();

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
  <style>
    .report-roi-box {
      background-color: #cfe0e0;
      border-radius: 14px;
      padding: 20px 24px;
      margin-bottom: 18px;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
    }
    .report-roi-pills {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      align-items: center;
    }
    .report-roi-pill {
      background: #ffffff;
      border-radius: 10px;
      padding: 10px 16px;
      font-size: 12px;
      font-weight: 600;
      color: #1e2525;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    .roi-calc-split {
      display: grid;
      grid-template-columns: 1.15fr 1fr;
      gap: 20px;
      align-items: center;
    }
    .roi-formula-dark {
      background-color: #074646;
      color: #ffffff;
      border-radius: 8px;
      padding: 11px 16px;
      font-family: monospace;
      font-size: 13px;
      font-weight: 700;
      display: inline-block;
      margin-top: 10px;
    }
    .mini-chart-panel {
      background-color: #eef2f2;
      border-radius: 12px;
      padding: 18px 20px;
      min-height: 170px;
      display: flex;
      align-items: flex-end;
      justify-content: space-around;
      gap: 16px;
    }
    .mini-bar-col {
      display: flex;
      flex-direction: column;
      align-items: center;
      flex: 1;
    }
    .mini-bar-val {
      font-size: 10px;
      font-weight: 600;
      color: #1e2525;
      margin-bottom: 6px;
      white-space: nowrap;
    }
    .mini-bar {
      width: 56px;
      max-width: 100%;
      border-radius: 6px 6px 0 0;
      min-height: 24px;
    }
    .mini-bar-label {
      font-size: 9px;
      font-weight: 700;
      color: #5e6a6a;
      margin-top: 8px;
      text-align: center;
      text-transform: uppercase;
    }
    .sales-summary-top {
      display: flex;
      align-items: baseline;
      gap: 14px;
      margin-bottom: 14px;
    }
    .sales-summary-badge {
      background-color: #cfe0e0;
      color: #1e2525;
      font-size: 22px;
      font-weight: 700;
      padding: 6px 14px;
      border-radius: 8px;
    }
    .sales-summary-grid {
      display: grid;
      grid-template-columns: 1.55fr 1fr;
      gap: 20px;
      align-items: start;
    }
    @media (max-width: 960px) {
      .roi-calc-split, .sales-summary-grid {
        grid-template-columns: 1fr;
      }
    }
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
        <li><a href="owner_dashboard.php">Dashboard</a></li>
        <li><a href="livestock.php">Livestock</a></li>
        <li><a href="task_schedule.php">Task Schedule</a></li>
        <li><a href="expenses.php">Expenses</a></li>
        <li><a href="sales.php">Sales</a></li>
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
          <div class="page-meta-tag">FARMER: <?= htmlspecialchars($full_name) ?> | GENERATED: <?= date('F d, Y \a\t g:i A') ?></div>
        </div>
        <div class="page-datetime"><?= date('F d, Y | H:i:s') ?></div>
      </div>

      <div class="kpi-grid-4">
        <div class="kpi-card">
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
          <a class="kpi-link" href="#roi-section">View Profit &rarr;</a>
        </div>
      </div>

      <div id="roi-section" class="report-roi-box">
        <div>
          <div class="kpi-sub" style="font-size:11px;font-weight:600;color:#1e2525;">Return On Investment (ROI)</div>
          <div style="font-size:32px;font-weight:700;line-height:1.15;margin:4px 0;">= <?= number_format($roi, 2) ?>%</div>
          <div style="font-size:11px;font-style:italic;color:#1e2525;">
            <strong>Remarks:</strong> <?= $profit >= 0 ? "Your farm is profitable. Great Job!" : "Your expenses currently exceed income." ?>
          </div>
        </div>

        <div class="report-roi-pills">
          <div class="report-roi-pill">
            <svg class="ui-icon ui-icon--teal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <rect x="3" y="3" width="18" height="18" rx="4"/>
              <path d="m9 12 2 2 4-4"/>
            </svg>
            <?= $profit >= 0 ? "Profitable" : "Net Loss" ?>
          </div>

          <div class="report-roi-pill">
            <svg class="ui-icon ui-icon--teal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M12 13V5"/>
              <path d="m8 9 4-4 4 4"/>
              <path d="M9 13h6l2 7H7l2-7Z"/>
            </svg>
            <?= $exp_count ?> Expense Record(s)
          </div>

          <div class="report-roi-pill">
            <svg class="ui-icon ui-icon--teal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M9 6h6l2 5v9H7v-9l2-5Z"/>
              <path d="M12 11v6"/>
              <path d="M14 13h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3H10"/>
            </svg>
            <?= $sls_count ?> Income Record(s)
          </div>
        </div>
      </div>

      <div class="card">
        <div class="roi-calc-split">
          <div>
            <div class="card-title" style="margin-bottom:8px;display:flex;align-items:center;gap:6px;"><svg class="ui-icon ui-icon--teal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="16" y2="12"/><line x1="12" x2="12.01" y1="8" y2="8"/></svg> How ROI is Calculated?</div>
            <div style="font-size:12px;color:#1e2525;line-height:1.6;">
              ROI = (Income &minus; Expenses) &divide; Expenses &times; 100<br>
              ROI = (&#8369;<?= number_format($total_income, 0) ?> &minus; &#8369;<?= number_format($total_expenses, 0) ?>) &divide; &#8369;<?= number_format($total_expenses, 0) ?> &times; 100
            </div>
            <div class="roi-formula-dark">ROI = <?= number_format($roi, 2) ?>%</div>
          </div>

          <div class="mini-chart-panel">
            <?php
              $max_val = max($total_expenses, $total_income, abs($profit), 1);
              $exp_h   = max(24, (int)round(($total_expenses / $max_val) * 105));
              $inc_h   = max(24, (int)round(($total_income / $max_val) * 105));
              $prf_h   = max(24, (int)round((abs($profit) / $max_val) * 105));
            ?>
            <div class="mini-bar-col">
              <div class="mini-bar-val">&#8369;<?= number_format($total_expenses / 1000, 1) ?>k</div>
              <div class="mini-bar" style="height:<?= $exp_h ?>px;background:#689393;"></div>
              <div class="mini-bar-label">Total Expenses</div>
            </div>
            <div class="mini-bar-col">
              <div class="mini-bar-val">&#8369;<?= number_format($total_income / 1000, 1) ?>k</div>
              <div class="mini-bar" style="height:<?= $inc_h ?>px;background:#3a7373;"></div>
              <div class="mini-bar-label">Total Income</div>
            </div>
            <div class="mini-bar-col">
              <div class="mini-bar-val">&#8369;<?= number_format($profit / 1000, 1) ?>k</div>
              <div class="mini-bar" style="height:<?= $prf_h ?>px;background:#074646;"></div>
              <div class="mini-bar-label">Profit / Loss</div>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-title" style="display:flex;align-items:center;gap:6px;"><svg class="ui-icon ui-icon--teal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="16" y2="12"/><line x1="12" x2="12.01" y1="8" y2="8"/></svg> Total Sales &amp; Income Summary</div>
        <div class="sales-summary-top">
          <span class="sales-summary-badge">&#8369;<?= number_format($total_income, 0) ?></span>
          <span style="font-size:12px;color:var(--text-secondary);">Updated from recorded sales &amp; expenses.</span>
        </div>

        <div class="sales-summary-grid">
          <table class="data-table">
            <thead>
              <tr>
                <th>NO.</th>
                <th>ITEM</th>
                <th>TOTAL QUANTITY SOLD</th>
                <th>TOTAL AMOUNT</th>
                <th>AVERAGE PRICE</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($sales_by_item as $i => $srow): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td><?= strtoupper(htmlspecialchars($srow['item_name'])) ?></td>
                  <td><?= (int)$srow['total_qty'] ?></td>
                  <td>&#8369;<?= number_format((float)$srow['total_amt'], 0) ?></td>
                  <td>&#8369;<?= number_format((float)$srow['avg_price'], 0) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

          <div class="mini-chart-panel" style="min-height:190px;">
            <?php
              $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
              $heights = [45, 70, 55, 85, 40, 95];
              foreach ($months as $idx => $m):
            ?>
              <div class="mini-bar-col">
                <div class="mini-bar" style="width:26px;height:<?= $heights[$idx] ?>px;background:#0a5656;"></div>
                <div class="mini-bar-label"><?= $m ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="split-grid">
        <div class="card">
          <div class="card-title">Livestock Summary (By Type)</div>
          <table class="data-table">
            <thead>
              <tr><th>NO.</th><th>ANIMAL TYPE</th><th>QUANTITY</th></tr>
            </thead>
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
            <thead>
              <tr><th>NO.</th><th>EXPENSES</th><th>QUANTITY</th><th>TOTAL</th></tr>
            </thead>
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