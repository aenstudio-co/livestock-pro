<?php
require_once 'includes/db_connect.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$full_name = trim(($_SESSION['first_name'] ?? 'Juan') . ' ' . ($_SESSION['last_name'] ?? 'Dela Cruz'));
$user_id   = $_SESSION['user_id'];
$role      = strtoupper($_SESSION['user_type'] ?? 'O');
$message   = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
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

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM `expenses` WHERE `expense_id` = :eid")->execute(['eid' => $_GET['delete']]);
    header("Location: expenses.php");
    exit;
}

$total_expenses = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM `expenses`")->fetchColumn();
$records        = $pdo->query("SELECT * FROM `expenses` ORDER BY `expense_id` DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Expenses - LivestockPro</title>
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
        <li><a href="task_schedule.php">Task Schedule</a></li>
        <li><a class="active" href="expenses.php">Expenses</a></li>
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
        <h1 class="page-title">Expenses</h1>
        <div class="page-datetime"><?= date('F d, Y | H:i:s') ?></div>
      </div>

      <?php if ($message !== ""): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <div class="split-grid">
        <div class="card">
          <div class="card-title">Add Expense</div>
          <form method="POST" action="expenses.php">
            <div class="form-grid-2">
              <div class="form-group"><label class="form-label">EXPENSE NAME</label><input class="form-control" type="text" name="category" placeholder="ex: Pesticide Refill" required></div>
              <div class="form-group"><label class="form-label">AMOUNT</label><input class="form-control" type="number" step="0.01" name="amount" placeholder="0.00" required></div>
            </div>
            <div class="form-group"><label class="form-label">NOTES</label><textarea class="form-control" name="expense_description"></textarea></div>
            <button class="btn btn-primary" type="submit" name="add_expense">ADD TO EXPENSES</button>
          </form>
        </div>

        <div class="card">
          <div class="card-title">Expenses Summary</div>
          <div class="kpi-sub">As of <?= date('H:i:s') ?></div>
          <div class="kpi-value" style="margin-top:8px;">&#8369;<?= number_format($total_expenses, 0) ?></div>
          <div class="kpi-sub" style="margin-bottom:16px;">100% more than last month.</div>
          <a class="kpi-link" href="reports.php">View Full Summary &rarr;</a>
        </div>
      </div>

      <div class="card">
        <div class="card-title">Expense Records</div>
        <table class="data-table">
          <thead><tr><th>NO.</th><th>ID.</th><th>NAME</th><th>AMOUNT</th><th>DATE</th><th>NOTES</th><th>ACTIONS</th></tr></thead>
          <tbody>
            <?php foreach ($records as $i => $r): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($r['expense_id']) ?></td>
                <td><?= strtoupper(htmlspecialchars($r['category'])) ?></td>
                <td>&#8369;<?= number_format((float)$r['amount'], 0) ?></td>
                <td><?= htmlspecialchars(substr($r['date_recorded'], 0, 10)) ?></td>
                <td><?= htmlspecialchars($r['expense_description'] ?? '-') ?></td>
                <td>
                  <a class="btn btn-sm btn-edit" href="expenses.php?edit=<?= urlencode($r['expense_id']) ?>">EDIT</a>
                  <a class="btn btn-sm btn-delete" href="expenses.php?delete=<?= urlencode($r['expense_id']) ?>" onclick="return confirm('Delete expense?')">&#128465;</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</body>
</html>
