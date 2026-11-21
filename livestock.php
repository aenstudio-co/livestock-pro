<?php
require_once 'includes/db_connect.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$full_name = trim(($_SESSION['first_name'] ?? 'Juan') . ' ' . ($_SESSION['last_name'] ?? 'Dela Cruz'));
$user_id   = $_SESSION['user_id'];
$role      = strtoupper($_SESSION['user_type'] ?? 'O');
$message   = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_livestock'])) {
    $animal_type = trim($_POST['animal_type'] ?? '');
    $breed       = trim($_POST['breed'] ?? '');
    $quantity    = (int)($_POST['quantity'] ?? 0);
    $weight      = (float)($_POST['weight'] ?? 0);
    $health      = trim($_POST['health_status'] ?? 'Healthy');
    $vacc_status = trim($_POST['vaccination_status'] ?? 'Vaccinated');

    if ($animal_type !== '' && $quantity > 0) {
        $count  = (int)$pdo->query("SELECT COUNT(*) FROM `livestock`")->fetchColumn() + 1;
        $new_id = sprintf("LIV-%05d", $count);

        $stmt = $pdo->prepare("
            INSERT INTO `livestock`
            (`livestock_id`, `user_id`, `animal_type`, `breed`, `quantity`, `weight`, `health_status`, `vaccination_status`, `vaccination_date`, `date_recorded`)
            VALUES (:lid, :uid, :atype, :breed, :qty, :wt, :hs, :vs, CURDATE(), NOW())
        ");
        $stmt->execute([
            'lid'   => $new_id,
            'uid'   => $user_id,
            'atype' => $animal_type,
            'breed' => $breed !== '' ? $breed : 'Native',
            'qty'   => $quantity,
            'wt'    => $weight,
            'hs'    => $health,
            'vs'    => $vacc_status
        ]);
        $message = ($role === 'O') ? "Added {$new_id} to stock." : "Submitted livestock request {$new_id}.";
    }
}

if (isset($_GET['delete']) && $role === 'O') {
    $stmt = $pdo->prepare("DELETE FROM `livestock` WHERE `livestock_id` = :lid");
    $stmt->execute(['lid' => $_GET['delete']]);
    header("Location: livestock.php");
    exit;
}

$records = $pdo->query("SELECT * FROM `livestock` ORDER BY `livestock_id` DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Livestock - LivestockPro</title>
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
        <li><a href="<?= $role === 'O' ? 'owner_dashboard.php' : 'staff_dashboard.php' ?>">Dashboard</a></li>
        <li><a class="active" href="livestock.php">Livestock</a></li>
        <li><a href="task_schedule.php">Task Schedule</a></li>
        <li><a href="expenses.php">Expenses</a></li>
        <li><a href="sales.php">Sales</a></li>
        <?php if ($role === 'O'): ?>
          <li><a href="reports.php">Reports</a></li>
        <?php endif; ?>
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
        <h1 class="page-title">Livestock</h1>
        <div class="page-datetime"><?= date('F d, Y | H:i:s') ?></div>
      </div>

      <?php if ($message !== ""): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <div class="card">
        <div class="card-title">Add Livestock</div>
        <form method="POST" action="livestock.php">
          <div class="form-grid-4">
            <div class="form-group">
              <label class="form-label">SELECT ANIMAL</label>
              <select class="form-control" name="animal_type" required>
                <option value="">Select Animal</option>
                <option value="Carabao">Carabao</option>
                <option value="Cattle">Cattle</option>
                <option value="Swine">Swine</option>
                <option value="Goat">Goat</option>
                <option value="Chicken">Chicken</option>
                <option value="Duck">Duck</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">BREED</label>
              <input class="form-control" type="text" name="breed" required>
            </div>
            <div class="form-group">
              <label class="form-label">QUANTITY</label>
              <input class="form-control" type="number" name="quantity" min="1" required>
            </div>
            <div class="form-group" style="grid-row: span 2; margin-bottom:0;">
              <label class="form-label">NOTES</label>
              <textarea class="form-control" name="notes"></textarea>
            </div>
            <div class="form-group">
              <label class="form-label">WEIGHT (KG)</label>
              <input class="form-control" type="number" step="0.01" name="weight" required>
            </div>
            <div class="form-group">
              <label class="form-label">HEALTH STATUS</label>
              <select class="form-control" name="health_status">
                <option value="Healthy">Healthy</option>
                <option value="Sick">Sick</option>
                <option value="Recovered">Recovered</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">VACCINATION STATUS</label>
              <select class="form-control" name="vaccination_status">
                <option value="Vaccinated">Vaccinated</option>
                <option value="Unvaccinated">Unvaccinated</option>
                <option value="Partial">Partial</option>
              </select>
            </div>
          </div>

          <button class="btn btn-primary" type="submit" name="add_livestock">
            <?= $role === 'O' ? 'ADD TO STOCK' : 'SUBMIT REQUEST' ?>
          </button>
        </form>
      </div>

      <div class="card">
        <div class="card-title">Livestock Records</div>
        <table class="data-table">
          <thead>
            <tr>
              <th>NO.</th><th>ID.</th><th>TYPE</th><th>BREED</th><th>QUANTITY</th><th>WEIGHT (KG)</th><th>HEALTH</th><th>VACCINATION</th>
              <?php if ($role === 'O'): ?><th>ACTIONS</th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($records as $i => $row): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($row['livestock_id']) ?></td>
                <td><?= strtoupper(htmlspecialchars($row['animal_type'])) ?></td>
                <td><?= strtoupper(htmlspecialchars($row['breed'])) ?></td>
                <td><?= number_format((int)$row['quantity']) ?></td>
                <td><?= number_format((float)$row['weight'], 2) ?></td>
                <td><span class="badge badge-healthy"><?= strtoupper(htmlspecialchars($row['health_status'])) ?></span></td>
                <td><?= strtoupper(htmlspecialchars($row['vaccination_status'] ?? 'N/A')) ?></td>
                <?php if ($role === 'O'): ?>
                  <td>
                    <a class="btn btn-sm btn-edit" href="livestock.php?edit=<?= urlencode($row['livestock_id']) ?>">EDIT</a>
                    <a class="btn btn-sm btn-delete" href="livestock.php?delete=<?= urlencode($row['livestock_id']) ?>" onclick="return confirm('Delete record?')">&#128465;</a>
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