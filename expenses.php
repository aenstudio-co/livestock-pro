<?php
require_once 'includes/db_connect.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$full_name = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
if ($full_name === '') { $full_name = 'User'; }
$user_id   = $_SESSION['user_id'];
$role      = strtoupper($_SESSION['user_type'] ?? 'O');
$message   = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense']) && $role === 'O') {
    $name   = trim($_POST['category'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $notes  = trim($_POST['expense_description'] ?? '');

    if ($name !== '' && $amount > 0) {
        $count  = (int)$pdo->query("SELECT COUNT(*) FROM `expenses`")->fetchColumn() + 1;
        $new_id = sprintf("EXP-%05d", $count);
        $pdo->prepare("
            INSERT INTO `expenses` (`expense_id`, `user_id`, `amount`, `category`, `expense_description`, `date_recorded`)
            VALUES (:eid, :uid, :amt, :cat, :desc, NOW())
        ")->execute([
            'eid'  => $new_id,
            'uid'  => $user_id,
            'amt'  => $amount,
            'cat'  => $name,
            'desc' => $notes ?: null
        ]);
        $message = "Expense {$new_id} recorded.";
    }
}

if (isset($_GET['delete']) && $role === 'O') {
    $pdo->prepare("DELETE FROM `expenses` WHERE `expense_id` = :eid")->execute(['eid' => $_GET['delete']]);
    header("Location: expenses.php");
    exit;
}

$total_expenses = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM `expenses`")->fetchColumn();
$records        = $pdo->query("SELECT * FROM `expenses` ORDER BY `expense_id` DESC")->fetchAll();
$by_exp_cat     = $pdo->query("
    SELECT category, COALESCE(SUM(amount), 0) AS total_amt
    FROM `expenses`
    GROUP BY category
    ORDER BY total_amt DESC
    LIMIT 7
")->fetchAll();
$max_exp_bar = 1;
foreach ($by_exp_cat as $c) {
    if ((float)$c['total_amt'] > $max_exp_bar) {
        $max_exp_bar = (float)$c['total_amt'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Expenses - LivestockPro</title>
  <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>">
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
        <li><a href="task_schedule.php">Task Schedule</a></li>
        <li><a class="active" href="expenses.php">Expenses</a></li>
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
        <h1 class="page-title">Expenses</h1>
        <div class="page-datetime"><?= date('F d, Y | H:i:s') ?></div>
      </div>

      <?php if ($message !== ""): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <?php if ($role === 'O'): ?>
        <div class="split-grid split-grid--summary">
          <div class="card">
            <div class="card-title">Add Expense</div>
            <form method="POST" action="expenses.php">
              <div class="form-grid-2">
                <div class="form-group">
                  <label class="form-label">EXPENSE NAME</label>
                  <input class="form-control" type="text" name="category" placeholder="ex: Pesticide Refill" required>
                </div>
                <div class="form-group">
                  <label class="form-label">AMOUNT</label>
                  <input class="form-control" type="number" step="0.01" name="amount" placeholder="0.00" required>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">NOTES</label>
                <textarea class="form-control" name="expense_description"></textarea>
              </div>
              <button class="btn btn-primary" type="submit" name="add_expense">ADD TO EXPENSES</button>
            </form>
          </div>

          <div class="card summary-card">
            <div class="summary-card-left">
              <div>
                <div class="card-title">Expenses Summary</div>
                <div class="kpi-sub">As of <?= date('H:i:s') ?></div>
              </div>
              <div class="kpi-value">&#8369;<?= number_format($total_expenses, 0) ?></div>
              <a class="kpi-link" href="reports.php">View Full Summary &rarr;</a>
            </div>

            <div class="summary-card-right">
              <?php if (empty($by_exp_cat)): ?>
                <div class="kpi-sub">No expense data available yet.</div>
              <?php else: ?>
                <div class="simple-bar-chart">
                  <?php foreach ($by_exp_cat as $cat): ?>
                    <?php $bar_h = max(18, (int)round(((float)$cat['total_amt'] / $max_exp_bar) * 120)); ?>
                    <div class="simple-bar-item">
                      <div class="simple-bar-value">&#8369;<?= number_format((float)$cat['total_amt'], 0) ?></div>
                      <div class="simple-bar-fill" style="height:<?= $bar_h ?>px;"></div>
                      <div class="simple-bar-name"><?= htmlspecialchars($cat['category']) ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-title">Expense Records</div>
        <table class="data-table">
          <thead>
            <tr>
              <th>NO.</th><th>ID.</th><th>NAME</th><th>AMOUNT</th><th>DATE</th><th>NOTES</th>
              <?php if ($role === 'O'): ?><th>ACTIONS</th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($records as $i => $r): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($r['expense_id']) ?></td>
                <td><?= strtoupper(htmlspecialchars($r['category'])) ?></td>
                <td>&#8369;<?= number_format((float)$r['amount'], 0) ?></td>
                <td style="white-space:nowrap;"><?= htmlspecialchars(substr($r['date_recorded'], 0, 10)) ?></td>
                <td><?= htmlspecialchars($r['expense_description'] ?? '-') ?></td>
                <?php if ($role === 'O'): ?>
                  <td>
                    <a class="btn btn-sm btn-edit" href="expenses.php?edit=<?= urlencode($r['expense_id']) ?>">EDIT</a>
                    <a class="btn btn-sm btn-delete" href="expenses.php?delete=<?= urlencode($r['expense_id']) ?>" onclick="return confirm('Delete expense?')" title="Delete"><svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg></a>
                  </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</body>
</html>