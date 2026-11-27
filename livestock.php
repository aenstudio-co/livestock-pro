<?php
require_once 'includes/db_connect.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$full_name = trim(($_SESSION['first_name'] ?? 'Juan') . ' ' . ($_SESSION['last_name'] ?? 'Dela Cruz'));
$user_id   = $_SESSION['user_id'];
$role      = strtoupper($_SESSION['user_type'] ?? 'O');
$business  = trim((string)($_SESSION['business_name'] ?? ''));
$message   = "";
$error     = "";

if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_livestock'])) {
    $animal_type = trim($_POST['animal_type'] ?? '');
    $breed       = trim($_POST['breed'] ?? '');
    $quantity    = (int)($_POST['quantity'] ?? 0);
    $weight      = (float)($_POST['weight'] ?? 0);
    $health      = trim($_POST['health_status'] ?? 'Healthy');
    $vacc_status = trim($_POST['vaccination_status'] ?? 'Vaccinated');

    if ($animal_type !== '' && $quantity > 0) {
        $max_num = (int)$pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING(`livestock_id`, 5) AS UNSIGNED)), 0) FROM `livestock`")->fetchColumn();
        $new_id  = sprintf("LIV-%05d", $max_num + 1);

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
        $_SESSION['flash_message'] = ($role === 'O') ? "Added {$new_id} to stock." : "Submitted livestock request {$new_id}.";
        header("Location: livestock.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_livestock']) && $role === 'O') {
    $lid         = trim($_POST['livestock_id'] ?? '');
    $animal_type = trim($_POST['animal_type'] ?? '');
    $breed       = trim($_POST['breed'] ?? '');
    $quantity    = (int)($_POST['quantity'] ?? 0);
    $weight      = (float)($_POST['weight'] ?? 0);
    $health      = trim($_POST['health_status'] ?? 'Healthy');
    $vacc_status = trim($_POST['vaccination_status'] ?? 'Vaccinated');

    if ($lid !== '' && $animal_type !== '' && $quantity > 0) {
        $stmt = $pdo->prepare("
            UPDATE `livestock` l
            JOIN `user` u ON l.user_id COLLATE utf8mb4_unicode_ci = u.user_id COLLATE utf8mb4_unicode_ci
            SET l.`animal_type` = :atype,
                l.`breed` = :breed,
                l.`quantity` = :qty,
                l.`weight` = :wt,
                l.`health_status` = :hs,
                l.`vaccination_status` = :vs
            WHERE l.`livestock_id` = :lid
              AND LOWER(TRIM(u.business_name)) = LOWER(TRIM(:bname))
        ");
        $stmt->execute([
            'atype' => $animal_type,
            'breed' => $breed !== '' ? $breed : 'Native',
            'qty'   => $quantity,
            'wt'    => $weight,
            'hs'    => $health,
            'vs'    => $vacc_status,
            'lid'   => $lid,
            'bname' => $business
        ]);
        $_SESSION['flash_message'] = "Updated {$lid}.";
        header("Location: livestock.php");
        exit;
    } else {
        $error = "Animal type and a quantity of at least 1 are required.";
    }
}

if (isset($_GET['delete']) && $role === 'O') {
    $del_id = trim($_GET['delete']);
    if ($del_id !== '') {
        $stmt = $pdo->prepare("
            DELETE l FROM `livestock` l
            JOIN `user` u ON l.user_id COLLATE utf8mb4_unicode_ci = u.user_id COLLATE utf8mb4_unicode_ci
            WHERE l.`livestock_id` = :lid
              AND LOWER(TRIM(u.business_name)) = LOWER(TRIM(:bname))
        ");
        $stmt->execute([
            'lid'   => $del_id,
            'bname' => $business
        ]);
        $_SESSION['flash_message'] = "Deleted {$del_id}.";
    }
    header("Location: livestock.php");
    exit;
}

$rec_stmt = $pdo->prepare("
    SELECT l.*
    FROM `livestock` l
    JOIN `user` u ON l.user_id COLLATE utf8mb4_unicode_ci = u.user_id COLLATE utf8mb4_unicode_ci
    WHERE LOWER(TRIM(u.business_name)) = LOWER(TRIM(:bname))
    ORDER BY l.`livestock_id` DESC
");
$rec_stmt->execute(['bname' => $business]);
$records = $rec_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Livestock - LivestockPro</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    .modal-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      z-index: 1000;
      background: rgba(0, 0, 0, 0.45);
      align-items: center;
      justify-content: center;
      padding: 16px;
    }
    .modal-backdrop.open { display: flex; }
    .modal-box {
      background: var(--bg-card);
      border-radius: var(--radius-lg);
      padding: 24px;
      width: 100%;
      max-width: 560px;
      box-shadow: var(--shadow-modal);
    }
    .modal-actions {
      display: flex;
      gap: 10px;
      margin-top: 12px;
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
        <li><a href="<?= $role === 'O' ? 'owner_dashboard.php' : 'staff_dashboard.php' ?>">Dashboard</a></li>
        <li><a class="active" href="livestock.php">Livestock</a></li>
        <li><a href="task_schedule.php">Task Schedule</a></li>
        <li><a href="expenses.php">Expenses</a></li>
        <li><a href="sales.php">Sales</a></li>
        <?php if ($role === 'O'): ?>
          <li><a href="reports.php">Reports</a></li>
        <?php endif; ?>
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
        <h1 class="page-title">Livestock</h1>
        <div class="page-datetime"><?= date('F d, Y | H:i:s') ?></div>
      </div>

      <?php if ($message !== ""): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>
      <?php if ($error !== ""): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
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
                <option value="Quarantined">Quarantined</option>
                <option value="Deceased">Deceased</option>
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
              <th>NO.</th><th>TYPE</th><th>BREED</th><th>QUANTITY</th><th>WEIGHT (KG)</th><th>HEALTH</th><th>VACCINATION</th>
              <?php if ($role === 'O'): ?><th>ACTIONS</th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($records as $i => $row): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= strtoupper(htmlspecialchars($row['animal_type'])) ?></td>
                <td><?= strtoupper(htmlspecialchars($row['breed'])) ?></td>
                <td><?= number_format((int)$row['quantity']) ?></td>
                <td><?= number_format((float)$row['weight'], 2) ?></td>
                <td><span class="badge badge-healthy"><?= strtoupper(htmlspecialchars($row['health_status'])) ?></span></td>
                <td><?= strtoupper(htmlspecialchars($row['vaccination_status'] ?? 'N/A')) ?></td>
                <?php if ($role === 'O'): ?>
                  <td>
                    <button type="button" class="btn btn-sm btn-edit"
                      data-id="<?= htmlspecialchars($row['livestock_id']) ?>"
                      data-type="<?= htmlspecialchars($row['animal_type']) ?>"
                      data-breed="<?= htmlspecialchars($row['breed']) ?>"
                      data-qty="<?= htmlspecialchars((string)$row['quantity']) ?>"
                      data-weight="<?= htmlspecialchars((string)$row['weight']) ?>"
                      data-health="<?= htmlspecialchars($row['health_status']) ?>"
                      data-vacc="<?= htmlspecialchars($row['vaccination_status'] ?? 'Vaccinated') ?>"
                      onclick="openEditLivestockModal(this)">EDIT</button>
                    <a class="btn btn-sm btn-delete" href="livestock.php?delete=<?= urlencode($row['livestock_id']) ?>" onclick="return confirm('Delete record?')" title="Delete"><svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg></a>
                  </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>

  <?php if ($role === 'O'): ?>
  <div class="modal-backdrop" id="editLivestockModal">
    <div class="modal-box">
      <div class="card-title">Edit Livestock</div>
      <form method="POST" action="livestock.php">
        <input type="hidden" name="livestock_id" id="edit_livestock_id">
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">ANIMAL TYPE</label>
            <select class="form-control" name="animal_type" id="edit_animal_type" required>
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
            <input class="form-control" type="text" name="breed" id="edit_breed" required>
          </div>
          <div class="form-group">
            <label class="form-label">QUANTITY</label>
            <input class="form-control" type="number" name="quantity" id="edit_quantity" min="1" required>
          </div>
          <div class="form-group">
            <label class="form-label">WEIGHT (KG)</label>
            <input class="form-control" type="number" step="0.01" name="weight" id="edit_weight" required>
          </div>
          <div class="form-group">
            <label class="form-label">HEALTH STATUS</label>
            <select class="form-control" name="health_status" id="edit_health_status">
              <option value="Healthy">Healthy</option>
              <option value="Sick">Sick</option>
              <option value="Recovered">Recovered</option>
              <option value="Quarantined">Quarantined</option>
              <option value="Deceased">Deceased</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">VACCINATION STATUS</label>
            <select class="form-control" name="vaccination_status" id="edit_vaccination_status">
              <option value="Vaccinated">Vaccinated</option>
              <option value="Unvaccinated">Unvaccinated</option>
              <option value="Partial">Partial</option>
            </select>
          </div>
        </div>
        <div class="modal-actions">
          <button class="btn btn-primary" type="submit" name="edit_livestock">SAVE CHANGES</button>
          <button class="btn" type="button" onclick="closeEditLivestockModal()">CANCEL</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openEditLivestockModal(btn) {
      document.getElementById('edit_livestock_id').value       = btn.dataset.id;
      document.getElementById('edit_animal_type').value       = btn.dataset.type;
      document.getElementById('edit_breed').value             = btn.dataset.breed;
      document.getElementById('edit_quantity').value          = btn.dataset.qty;
      document.getElementById('edit_weight').value            = btn.dataset.weight;
      document.getElementById('edit_health_status').value     = btn.dataset.health;
      document.getElementById('edit_vaccination_status').value = btn.dataset.vacc;
      document.getElementById('editLivestockModal').classList.add('open');
    }

    function closeEditLivestockModal() {
      document.getElementById('editLivestockModal').classList.remove('open');
    }

    document.getElementById('editLivestockModal').addEventListener('click', function (e) {
      if (e.target === this) closeEditLivestockModal();
    });
  </script>
  <?php endif; ?>
</body>
</html>