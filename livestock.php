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

$allowed_health = ['Healthy', 'Sick', 'Recovered', 'Quarantined', 'Deceased'];
$allowed_vacc   = ['Vaccinated', 'Unvaccinated', 'Partial'];

// 1. Add livestock (Owner only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_livestock']) && $role === 'O') {
    $animal_type = trim($_POST['animal_type'] ?? '');
    $breed       = trim($_POST['breed'] ?? '');
    $quantity    = (int)($_POST['quantity'] ?? 0);
    $weight      = (float)($_POST['weight'] ?? 0);
    $health      = trim($_POST['health_status'] ?? 'Healthy');
    $vacc_status = trim($_POST['vaccination_status'] ?? 'Vaccinated');

    if (!in_array($health, $allowed_health, true)) { $health = 'Healthy'; }
    if (!in_array($vacc_status, $allowed_vacc, true)) { $vacc_status = 'Vaccinated'; }

    if ($animal_type !== '' && $quantity > 0) {
        $max_num = (int)$pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING(`livestock_id`, 5) AS UNSIGNED)), 0) FROM `livestock`")->fetchColumn();
        $new_id  = sprintf("LIV-%05d", $max_num + 1);

        $stmt = $pdo->prepare("
            INSERT INTO `livestock`
            (`livestock_id`, `user_id`, `animal_type`, `breed`, `quantity`, `weight`, `health_status`, `vaccination_status`, `vaccination_date`, `date_recorded`)
            VALUES (:lid, :uid, :atype, :breed, :qty, :wt, :hs, :vs, :vdate, NOW())
        ");
        $stmt->execute([
            'lid'   => $new_id,
            'uid'   => $user_id,
            'atype' => $animal_type,
            'breed' => $breed !== '' ? $breed : 'Native',
            'qty'   => $quantity,
            'wt'    => $weight,
            'hs'    => $health,
            'vs'    => $vacc_status,
            'vdate' => ($vacc_status === 'Vaccinated') ? date('Y-m-d') : null
        ]);
        $_SESSION['flash_message'] = "Added {$new_id} to stock.";
        header("Location: livestock.php");
        exit;
    } else {
        $error = "Animal type and a quantity of at least 1 are required.";
    }
}

// 2. Edit core livestock record (Owner only - health & vaccination edited via table dropdowns)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_livestock']) && $role === 'O') {
    $lid         = trim($_POST['livestock_id'] ?? '');
    $animal_type = trim($_POST['animal_type'] ?? '');
    $breed       = trim($_POST['breed'] ?? '');
    $quantity    = (int)($_POST['quantity'] ?? 0);
    $weight      = (float)($_POST['weight'] ?? 0);

    if ($lid !== '' && $animal_type !== '' && $quantity > 0) {
        $stmt = $pdo->prepare("
            UPDATE `livestock` l
            JOIN `user` u ON l.user_id COLLATE utf8mb4_unicode_ci = u.user_id COLLATE utf8mb4_unicode_ci
            SET l.`animal_type` = :atype,
                l.`breed` = :breed,
                l.`quantity` = :qty,
                l.`weight` = :wt
            WHERE l.`livestock_id` = :lid
              AND LOWER(TRIM(u.business_name)) = LOWER(TRIM(:bname))
        ");
        $stmt->execute([
            'atype' => $animal_type,
            'breed' => $breed !== '' ? $breed : 'Native',
            'qty'   => $quantity,
            'wt'    => $weight,
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

// 3. Inline dropdown update for health_status or vaccination_status (Owner & Staff)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status_field']) && in_array($role, ['O', 'S'], true)) {
    $lid   = trim($_POST['livestock_id'] ?? '');
    $field = trim($_POST['field'] ?? '');
    $val   = trim($_POST['value'] ?? '');

    if ($lid !== '' && $field === 'health_status' && in_array($val, $allowed_health, true)) {
        $stmt = $pdo->prepare("
            UPDATE `livestock` l
            JOIN `user` u ON l.user_id COLLATE utf8mb4_unicode_ci = u.user_id COLLATE utf8mb4_unicode_ci
            SET l.`health_status` = :val
            WHERE l.`livestock_id` = :lid
              AND LOWER(TRIM(u.business_name)) = LOWER(TRIM(:bname))
        ");
        $stmt->execute(['val' => $val, 'lid' => $lid, 'bname' => $business]);
        $_SESSION['flash_message'] = "Updated health status for {$lid} to {$val}.";
    } elseif ($lid !== '' && $field === 'vaccination_status' && in_array($val, $allowed_vacc, true)) {
        $stmt = $pdo->prepare("
            UPDATE `livestock` l
            JOIN `user` u ON l.user_id COLLATE utf8mb4_unicode_ci = u.user_id COLLATE utf8mb4_unicode_ci
            SET l.`vaccination_status` = :val,
                l.`vaccination_date` = CASE
                    WHEN :val2 = 'Vaccinated' AND l.`vaccination_date` IS NULL THEN CURDATE()
                    ELSE l.`vaccination_date`
                END
            WHERE l.`livestock_id` = :lid
              AND LOWER(TRIM(u.business_name)) = LOWER(TRIM(:bname))
        ");
        $stmt->execute(['val' => $val, 'val2' => $val, 'lid' => $lid, 'bname' => $business]);
        $_SESSION['flash_message'] = "Updated vaccination status for {$lid} to {$val}.";
    } else {
        $error = "Invalid status selection.";
    }
    header("Location: livestock.php");
    exit;
}

// 4. Delete livestock (Owner only)
if (isset($_GET['delete']) && $role === 'O') {
    $del_id = trim($_GET['delete']);
    if ($del_id !== '') {
        $stmt = $pdo->prepare("
            DELETE l FROM `livestock` l
            JOIN `user` u ON l.user_id COLLATE utf8mb4_unicode_ci = u.user_id COLLATE utf8mb4_unicode_ci
            WHERE l.`livestock_id` = :lid
              AND LOWER(TRIM(u.business_name)) = LOWER(TRIM(:bname))
        ");
        $stmt->execute(['lid' => $del_id, 'bname' => $business]);
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

function health_tone_class(string $val): string {
    return match ($val) {
        'Healthy'     => 'tone-healthy',
        'Sick'        => 'tone-sick',
        'Recovered'   => 'tone-recovered',
        'Quarantined' => 'tone-quarantined',
        'Deceased'    => 'tone-deceased',
        default       => 'tone-default',
    };
}

function vacc_tone_class(string $val): string {
    return match ($val) {
        'Vaccinated'   => 'tone-healthy',
        'Partial'      => 'tone-yellow',
        'Unvaccinated' => 'tone-red',
        default        => 'tone-default',
    };
}

function render_health_dropdown(string $lid, string $current_val): void {
    $btn_tone = health_tone_class($current_val);
    $options  = [
        ['Healthy',     'HEALTHY',     health_tone_class('Healthy')],
        ['Sick',        'SICK',        health_tone_class('Sick')],
        ['Recovered',   'RECOVERED',   health_tone_class('Recovered')],
        ['Quarantined', 'QUARANTINED', health_tone_class('Quarantined')],
        ['Deceased',    'DECEASED',    health_tone_class('Deceased')],
    ];
    ?>
    <div class="status-dd">
      <button type="button" class="status-dd-btn <?= $btn_tone ?>" onclick="toggleStatusMenu(this)">
        <span><?= strtoupper(htmlspecialchars($current_val)) ?></span>
        <span class="status-dd-caret" aria-hidden="true">&#9662;</span>
      </button>
      <div class="status-dd-menu">
        <?php foreach ($options as $o): ?>
          <form method="POST" action="livestock.php">
            <input type="hidden" name="livestock_id" value="<?= htmlspecialchars($lid) ?>">
            <input type="hidden" name="field" value="health_status">
            <input type="hidden" name="value" value="<?= $o[0] ?>">
            <input type="hidden" name="update_status_field" value="1">
            <button type="submit" class="status-dd-opt <?= $o[2] ?>"><?= $o[1] ?></button>
          </form>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
}

function render_vacc_dropdown(string $lid, string $current_val): void {
    $btn_tone = vacc_tone_class($current_val);
    $options  = [
        ['Vaccinated',   'VACCINATED',   vacc_tone_class('Vaccinated')],
        ['Partial',      'PARTIAL',      vacc_tone_class('Partial')],
        ['Unvaccinated', 'UNVACCINATED', vacc_tone_class('Unvaccinated')],
    ];
    ?>
    <div class="status-dd">
      <button type="button" class="status-dd-btn <?= $btn_tone ?>" onclick="toggleStatusMenu(this)">
        <span><?= strtoupper(htmlspecialchars($current_val)) ?></span>
        <span class="status-dd-caret" aria-hidden="true">&#9662;</span>
      </button>
      <div class="status-dd-menu">
        <?php foreach ($options as $o): ?>
          <form method="POST" action="livestock.php">
            <input type="hidden" name="livestock_id" value="<?= htmlspecialchars($lid) ?>">
            <input type="hidden" name="field" value="vaccination_status">
            <input type="hidden" name="value" value="<?= $o[0] ?>">
            <input type="hidden" name="update_status_field" value="1">
            <button type="submit" class="status-dd-opt <?= $o[2] ?>"><?= $o[1] ?></button>
          </form>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Livestock - LivestockPro</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    .status-dd {
      display: inline-block;
      position: relative;
    }
    .status-dd-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      min-width: 0;
      height: auto;
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: .4px;
      line-height: 1.2;
      border: none;
      cursor: pointer;
      white-space: nowrap;
    }
    .status-dd-caret {
      font-size: 9px;
      line-height: 1;
      opacity: .85;
      flex-shrink: 0;
    }
    .status-dd-opt {
      display: block;
      width: 100%;
      padding: 6px 12px;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: .4px;
      text-align: center;
      border: none;
      cursor: pointer;
    }

    .status-dd-btn.tone-healthy,
    .status-dd-opt.tone-healthy {
      background: #bff3cd;
      color: #085c2c;
    }
    .status-dd-btn.tone-sick,
    .status-dd-opt.tone-sick {
      background: #fad0d0;
      color: #9c1919;
    }
    .status-dd-btn.tone-recovered,
    .status-dd-opt.tone-recovered {
      background: #f9e087;
      color: #7c4400;
    }
    .status-dd-btn.tone-quarantined,
    .status-dd-opt.tone-quarantined {
      background: #c8defd;
      color: #0f47a8;
    }
    .status-dd-btn.tone-deceased,
    .status-dd-opt.tone-deceased {
      background: #cbd5e1;
      color: #0f172a;
    }
    .status-dd-btn.tone-red,
    .status-dd-opt.tone-red {
      background: #fad0d0;
      color: #9c1919;
    }
    .status-dd-btn.tone-yellow,
    .status-dd-opt.tone-yellow {
      background: #f9e087;
      color: #7c4400;
    }
    .status-dd-btn.tone-default,
    .status-dd-opt.tone-default {
      background: #e6ecf5;
      color: #24344d;
    }

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
    .table-header-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
      margin-bottom: 14px;
    }
    .table-header-bar .card-title {
      margin-bottom: 0;
    }
    .sort-controls {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 12px;
      color: var(--text-secondary);
    }
    .sort-select {
      padding: 6px 10px;
      border: 1px solid var(--border-color, #d0d7d7);
      border-radius: 6px;
      background: var(--bg-card, #ffffff);
      color: var(--text-primary, #1e2525);
      font-size: 12px;
      cursor: pointer;
    }
    table.data-table thead th.sortable {
      cursor: pointer;
      user-select: none;
    }
    table.data-table thead th.sorted-asc::after {
      content: " \2191";
      font-size: 11px;
    }
    table.data-table thead th.sorted-desc::after {
      content: " \2193";
      font-size: 11px;
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

      <?php if ($role === 'O'): ?>
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

          <button class="btn btn-primary" type="submit" name="add_livestock">ADD TO STOCK</button>
        </form>
      </div>
      <?php endif; ?>

      <div class="card">
        <div class="table-header-bar">
          <div class="card-title">Livestock Records</div>
          <div class="sort-controls">
            <label for="livestockSortSelect">Sort By:</label>
            <select id="livestockSortSelect" class="sort-select">
              <option value="">Default</option>
              <option value="1|asc|string">Type (A &rarr; Z)</option>
              <option value="2|asc|string">Breed (A &rarr; Z)</option>
              <option value="3|desc|number">Quantity (High &rarr; Low)</option>
              <option value="4|desc|number">Weight (High &rarr; Low)</option>
              <option value="5|asc|health">Health (Healthy &rarr; Deceased)</option>
            </select>
          </div>
        </div>
        <table class="data-table" id="livestockTable">
          <thead>
            <tr>
              <th>NO.</th>
              <th class="sortable" data-col="1" data-type="string" title="Click to sort">TYPE</th>
              <th class="sortable" data-col="2" data-type="string" title="Click to sort">BREED</th>
              <th class="sortable" data-col="3" data-type="number" title="Click to sort">QUANTITY</th>
              <th class="sortable" data-col="4" data-type="number" title="Click to sort">WEIGHT (KG)</th>
              <th class="sortable" data-col="5" data-type="health" title="Click to sort">HEALTH</th>
              <th class="sortable" data-col="6" data-type="vacc" title="Click to sort">VACCINATION</th>
              <?php if ($role === 'O'): ?><th>ACTIONS</th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($records as $i => $row): ?>
              <?php
                $row_id = (string)$row['livestock_id'];
                $row_hs = (string)($row['health_status'] ?? 'Healthy');
                $row_vs = (string)($row['vaccination_status'] ?? 'Vaccinated');
              ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= strtoupper(htmlspecialchars($row['animal_type'])) ?></td>
                <td><?= strtoupper(htmlspecialchars($row['breed'])) ?></td>
                <td><?= number_format((int)$row['quantity']) ?></td>
                <td><?= number_format((float)$row['weight'], 2) ?></td>
                <td><?php render_health_dropdown($row_id, $row_hs); ?></td>
                <td><?php render_vacc_dropdown($row_id, $row_vs); ?></td>
                <?php if ($role === 'O'): ?>
                  <td>
                    <button type="button" class="btn btn-sm btn-edit"
                      data-id="<?= htmlspecialchars($row_id) ?>"
                      data-type="<?= htmlspecialchars($row['animal_type']) ?>"
                      data-breed="<?= htmlspecialchars($row['breed']) ?>"
                      data-qty="<?= htmlspecialchars((string)$row['quantity']) ?>"
                      data-weight="<?= htmlspecialchars((string)$row['weight']) ?>"
                      onclick="openEditLivestockModal(this)">EDIT</button>
                    <a class="btn btn-sm btn-delete" href="livestock.php?delete=<?= urlencode($row_id) ?>" onclick="return confirm('Delete record?')" title="Delete"><svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg></a>
                  </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>

  <script>
    (function () {
      const table = document.getElementById('livestockTable');
      const select = document.getElementById('livestockSortSelect');
      if (!table || !select) return;

      const tbody = table.tBodies[0];
      const headers = Array.from(table.querySelectorAll('thead th.sortable'));
      const initialRows = Array.from(tbody.querySelectorAll('tr'));

      const healthRank = {
        HEALTHY: 1,
        RECOVERED: 2,
        QUARANTINED: 3,
        SICK: 4,
        DECEASED: 5
      };

      const vaccRank = {
        VACCINATED: 1,
        PARTIAL: 2,
        UNVACCINATED: 3
      };

      const getCellSortText = (cell) => {
        if (!cell) return '';
        const activeBtnText = cell.querySelector('.status-dd-btn span');
        if (activeBtnText) return activeBtnText.textContent.trim();
        return cell.textContent.trim();
      };

      const parseVal = (cell, type) => {
        const raw = getCellSortText(cell);
        const upper = raw.toUpperCase();
        if (type === 'number') {
          const num = parseFloat(raw.replace(/[^0-9.-]/g, ''));
          return Number.isNaN(num) ? -Infinity : num;
        }
        if (type === 'health') {
          return healthRank[upper] ?? 99;
        }
        if (type === 'vacc') {
          return vaccRank[upper] ?? 99;
        }
        return raw.toLowerCase();
      };

      const renumber = () => {
        let n = 1;
        Array.from(tbody.querySelectorAll('tr')).forEach((tr) => {
          if (tr.children[0]) tr.children[0].textContent = n++;
        });
      };

      const applySort = (colIndex, dir, type) => {
        headers.forEach((h) => h.classList.remove('sorted-asc', 'sorted-desc'));

        const rowsToSort = Array.from(tbody.querySelectorAll('tr'));
        if (!dir) {
          initialRows.forEach((tr) => tbody.appendChild(tr));
          renumber();
          select.value = '';
          return;
        }

        rowsToSort.sort((a, b) => {
          const valA = parseVal(a.children[colIndex], type);
          const valB = parseVal(b.children[colIndex], type);
          let cmp = 0;
          if (type === 'number' || type === 'health' || type === 'vacc') {
            cmp = valA - valB;
          } else {
            cmp = String(valA).localeCompare(String(valB), undefined, { numeric: true, sensitivity: 'base' });
          }
          return dir === 'asc' ? cmp : -cmp;
        });

        rowsToSort.forEach((tr) => tbody.appendChild(tr));
        renumber();

        const activeTh = headers.find((h) => Number(h.dataset.col) === Number(colIndex));
        if (activeTh) {
          activeTh.dataset.sortDir = dir;
          activeTh.classList.add(dir === 'asc' ? 'sorted-asc' : 'sorted-desc');
        }
      };

      headers.forEach((th) => {
        th.addEventListener('click', () => {
          const nextDir = th.dataset.sortDir === 'asc' ? 'desc' : 'asc';
          const col = Number(th.dataset.col);
          const type = th.dataset.type || 'string';
          applySort(col, nextDir, type);
          const optVal = `${col}|${nextDir}|${type}`;
          select.value = Array.from(select.options).some((o) => o.value === optVal) ? optVal : '';
        });
      });

      select.addEventListener('change', () => {
        if (!select.value) {
          headers.forEach((h) => delete h.dataset.sortDir);
          applySort(null, null, null);
          return;
        }
        const [col, dir, type] = select.value.split('|');
        applySort(Number(col), dir, type);
      });
    })();

    function toggleStatusMenu(btn) {
      const dd = btn.closest('.status-dd');
      const isOpen = dd.classList.contains('open');
      document.querySelectorAll('.status-dd.open').forEach(el => el.classList.remove('open'));
      if (!isOpen) dd.classList.add('open');
    }

    document.addEventListener('click', function (e) {
      if (!e.target.closest('.status-dd')) {
        document.querySelectorAll('.status-dd.open').forEach(el => el.classList.remove('open'));
      }
    });
  </script>

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
      document.getElementById('edit_livestock_id').value = btn.dataset.id;
      document.getElementById('edit_animal_type').value = btn.dataset.type;
      document.getElementById('edit_breed').value       = btn.dataset.breed;
      document.getElementById('edit_quantity').value    = btn.dataset.qty;
      document.getElementById('edit_weight').value      = btn.dataset.weight;
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