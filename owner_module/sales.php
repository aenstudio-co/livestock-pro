<?php
require_once 'includes/db_connect.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$full_name = trim(($_SESSION['first_name'] ?? 'Juan') . ' ' . ($_SESSION['last_name'] ?? 'Dela Cruz'));
$user_id   = $_SESSION['user_id'];
$role      = strtoupper($_SESSION['user_type'] ?? 'O');
$message   = "";

$livestock_list = $pdo->query("SELECT livestock_id, animal_type, breed FROM `livestock` ORDER BY livestock_id ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_sale'])) {
    $lid    = trim($_POST['livestock_id'] ?? '');
    $qty    = (int)($_POST['quantity_sold'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);

    if ($lid !== '' && $qty > 0 && $amount > 0) {
        $count  = (int)$pdo->query("SELECT COUNT(*) FROM `sales`")->fetchColumn() + 1;
        $new_id = sprintf("SLS-%05d", $count);
        $pdo->prepare("
            INSERT INTO `sales` (`sales_id`, `livestock_id`, `amount`, `quantity_sold`, `date_sold`)
            VALUES (:sid, :lid, :amt, :qty, CURDATE())
        ")->execute([
            'sid' => $new_id,
            'lid' => $lid,
            'amt' => $amount,
            'qty' => $qty
        ]);
        $message = "Sale {$new_id} recorded.";
    }
}

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM `sales` WHERE `sales_id` = :sid")->execute(['sid' => $_GET['delete']]);
    header("Location: sales.php");
    exit;
}

$total_sales = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM `sales`")->fetchColumn();
$records     = $pdo->query("
    SELECT s.*, l.animal_type
    FROM `sales` s
    LEFT JOIN `livestock` l ON s.livestock_id = l.livestock_id
    ORDER BY s.sales_id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sales & Income - LivestockPro</title>
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
        <li><a href="expenses.php">Expenses</a></li>
        <li><a class="active" href="sales.php">Sales</a></li>
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
        <h1 class="page-title">Sales &amp; Income</h1>
        <div class="page-datetime"><?= date('F d, Y | H:i:s') ?></div>
      </div>

      <?php if ($message !== ""): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <div class="split-grid">
        <div class="card">
          <div class="card-title">Record Sale</div>
          <form method="POST" action="sales.php">
            <div class="form-grid-2">
              <div class="form-group">
                <label class="form-label">LIVESTOCK</label>
                <select class="form-control" name="livestock_id" required>
                  <option value="">Select Livestock</option>
                  <?php foreach ($livestock_list as $lv): ?>
                    <option value="<?= htmlspecialchars($lv['livestock_id']) ?>">
                      <?= htmlspecialchars($lv['livestock_id'] . ' - ' . $lv['animal_type'] . ' (' . $lv['breed'] . ')') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group"><label class="form-label">QUANTITY</label><input class="form-control" type="number" name="quantity_sold" min="1" required></div>
              <div class="form-group"><label class="form-label">AMOUNT</label><input class="form-control" type="number" step="0.01" name="amount" placeholder="0.00" required></div>
              <div class="form-group"><label class="form-label">DATE</label><input class="form-control" type="date" name="date_sold" value="<?= date('Y-m-d') ?>"></div>
            </div>
            <div class="form-group"><label class="form-label">NOTES</label><textarea class="form-control" name="notes"></textarea></div>
            <button class="btn btn-primary" type="submit" name="record_sale">ADD TO EXPENSES</button>
          </form>
        </div>

        <div class="card">
          <div class="card-title">Sales &amp; Income Summary</div>
          <div class="kpi-sub">As of <?= date('H:i:s') ?></div>
          <div class="kpi-value" style="margin-top:8px;">&#8369;<?= number_format($total_sales, 0) ?></div>
          <a class="kpi-link" href="reports.php">View Full Summary &rarr;</a>
        </div>
      </div>

      <div class="card">
        <div class="card-title">Sales Record</div>
        <table class="data-table">
          <thead><tr><th>NO.</th><th>ID.</th><th>LIVESTOCK SOLD</th><th>ID</th><th>QUANTITY</th><th>AMOUNT</th><th>DATE</th><th>ACTIONS</th></tr></thead>
          <tbody>
            <?php foreach ($records as $i => $r): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($r['sales_id']) ?></td>
                <td><?= strtoupper(htmlspecialchars($r['animal_type'] ?? 'LIVESTOCK')) ?></td>
                <td><?= htmlspecialchars($r['livestock_id']) ?></td>
                <td><?= (int)$r['quantity_sold'] ?></td>
                <td>&#8369;<?= number_format((float)$r['amount'], 0) ?></td>
                <td><?= htmlspecialchars($r['date_sold']) ?></td>
                <td>
                  <a class="btn btn-sm btn-edit" href="sales.php?edit=<?= urlencode($r['sales_id']) ?>">EDIT</a>
                  <a class="btn btn-sm btn-delete" href="sales.php?delete=<?= urlencode($r['sales_id']) ?>" onclick="return confirm('Delete sale?')">&#128465;</a>
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
