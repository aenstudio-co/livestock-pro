<?php
require_once 'includes/db_connect.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$full_name = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
if ($full_name === '') { $full_name = 'User'; }
$user_id   = $_SESSION['user_id'];
$role      = strtoupper($_SESSION['user_type'] ?? 'O');
$business  = trim((string)($_SESSION['business_name'] ?? ''));
$message   = "";
$error     = "";

if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense']) && $role === 'O') {
    $name   = trim($_POST['category'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $notes  = trim($_POST['expense_description'] ?? '');

    if ($name !== '' && $amount > 0) {
        $max_num = (int)$pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING(`expense_id`, 5) AS UNSIGNED)), 0) FROM `expenses`")->fetchColumn();
        $new_id  = sprintf("EXP-%05d", $max_num + 1);
        $pdo->prepare("
            INSERT INTO `expenses` (`expense_id`, `user_id`, `amount`, `category`, `expense_description`, `date_recorded`)
            VALUES (:eid, :uid, :amt, :cat, :desc, NOW())
        ")->execute([
            'eid'  => $new_id,
            'uid'  => $user_id,
            'amt'  => $amount,
            'cat'  => $name,
            'desc' => $notes !== '' ? $notes : null
        ]);
        $_SESSION['flash_message'] = "Expense {$new_id} recorded.";
        header("Location: expenses.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_expense']) && $role === 'O') {
    $eid    = trim($_POST['expense_id'] ?? '');
    $name   = trim($_POST['category'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $notes  = trim($_POST['expense_description'] ?? '');

    if ($eid !== '' && $name !== '' && $amount > 0) {
        $pdo->prepare("
            UPDATE `expenses` e
            JOIN `user` u ON e.user_id COLLATE utf8mb4_unicode_ci = u.user_id COLLATE utf8mb4_unicode_ci
            SET e.`category` = :cat,
                e.`amount` = :amt,
                e.`expense_description` = :desc
            WHERE e.`expense_id` = :eid
              AND LOWER(TRIM(u.business_name)) = LOWER(TRIM(:bname))
        ")->execute([
            'cat'   => $name,
            'amt'   => $amount,
            'desc'  => $notes !== '' ? $notes : null,
            'eid'   => $eid,
            'bname' => $business
        ]);
        $_SESSION['flash_message'] = "Updated {$eid}.";
        header("Location: expenses.php");
        exit;
    } else {
        $error = "Expense name and an amount greater than zero are required.";
    }
}

if (isset($_GET['delete']) && $role === 'O') {
    $pdo->prepare("
        DELETE e FROM `expenses` e
        JOIN `user` u ON e.user_id COLLATE utf8mb4_unicode_ci = u.user_id COLLATE utf8mb4_unicode_ci
        WHERE e.`expense_id` = :eid
          AND LOWER(TRIM(u.business_name)) = LOWER(TRIM(:bname))
    ")->execute([
        'eid'   => $_GET['delete'],
        'bname' => $business
    ]);
    header("Location: expenses.php");
    exit;
}

$tot_stmt = $pdo->prepare("
    SELECT COALESCE(SUM(e.amount), 0)
    FROM `expenses` e
    JOIN `user` u ON e.user_id COLLATE utf8mb4_unicode_ci = u.user_id COLLATE utf8mb4_unicode_ci
    WHERE LOWER(TRIM(u.business_name)) = LOWER(TRIM(:bname))
");
$tot_stmt->execute(['bname' => $business]);
$total_expenses = (float)$tot_stmt->fetchColumn();

$rec_stmt = $pdo->prepare("
    SELECT e.*
    FROM `expenses` e
    JOIN `user` u ON e.user_id COLLATE utf8mb4_unicode_ci = u.user_id COLLATE utf8mb4_unicode_ci
    WHERE LOWER(TRIM(u.business_name)) = LOWER(TRIM(:bname))
    ORDER BY e.`expense_id` DESC
");
$rec_stmt->execute(['bname' => $business]);
$records = $rec_stmt->fetchAll();

$cat_stmt = $pdo->prepare("
    SELECT e.category, COALESCE(SUM(e.amount), 0) AS total_amt
    FROM `expenses` e
    JOIN `user` u ON e.user_id COLLATE utf8mb4_unicode_ci = u.user_id COLLATE utf8mb4_unicode_ci
    WHERE LOWER(TRIM(u.business_name)) = LOWER(TRIM(:bname))
    GROUP BY e.category
    ORDER BY total_amt DESC
    LIMIT 7
");
$cat_stmt->execute(['bname' => $business]);
$by_exp_cat = $cat_stmt->fetchAll();

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
      max-width: 520px;
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
        <li><a href="livestock.php">Livestock</a></li>
        <li><a href="task_schedule.php">Task Schedule</a></li>
        <li><a class="active" href="expenses.php">Expenses</a></li>
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
        <h1 class="page-title">Expenses</h1>
        <div class="page-datetime"><?= date('F d, Y | H:i:s') ?></div>
      </div>

      <?php if ($message !== ""): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>
      <?php if ($error !== ""): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
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
        <div class="table-header-bar">
          <div class="card-title">Expense Records</div>
          <div class="sort-controls">
            <label for="expensesSortSelect">Sort By:</label>
            <select id="expensesSortSelect" class="sort-select">
              <option value="">Default</option>
              <option value="3|desc|date">Date (Newest First)</option>
              <option value="2|desc|number">Amount (High &rarr; Low)</option>
              <option value="1|asc|string">Name (A &rarr; Z)</option>
            </select>
          </div>
        </div>
        <table class="data-table" id="expensesTable">
          <thead>
            <tr>
              <th>NO.</th>
              <th class="sortable" data-col="1" data-type="string" title="Click to sort">NAME</th>
              <th class="sortable" data-col="2" data-type="number" title="Click to sort">AMOUNT</th>
              <th class="sortable" data-col="3" data-type="date" title="Click to sort">DATE</th>
              <th>NOTES</th>
              <?php if ($role === 'O'): ?><th>ACTIONS</th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($records as $i => $r): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= strtoupper(htmlspecialchars($r['category'])) ?></td>
                <td>&#8369;<?= number_format((float)$r['amount'], 0) ?></td>
                <td style="white-space:nowrap;"><?= htmlspecialchars(substr($r['date_recorded'], 0, 10)) ?></td>
                <td><?= htmlspecialchars($r['expense_description'] ?? '-') ?></td>
                <?php if ($role === 'O'): ?>
                  <td>
                    <button type="button" class="btn btn-sm btn-edit"
                      data-id="<?= htmlspecialchars($r['expense_id']) ?>"
                      data-category="<?= htmlspecialchars($r['category']) ?>"
                      data-amount="<?= htmlspecialchars((string)$r['amount']) ?>"
                      data-desc="<?= htmlspecialchars($r['expense_description'] ?? '') ?>"
                      onclick="openEditExpenseModal(this)">EDIT</button>
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

  <script>
    (function () {
      const table = document.getElementById('expensesTable');
      const select = document.getElementById('expensesSortSelect');
      if (!table || !select) return;

      const tbody = table.tBodies[0];
      const headers = Array.from(table.querySelectorAll('thead th.sortable'));
      const initialRows = Array.from(tbody.querySelectorAll('tr'));

      const parseVal = (cell, type) => {
        const raw = (cell ? cell.textContent : '').trim();
        if (type === 'number') {
          const num = parseFloat(raw.replace(/[^0-9.-]/g, ''));
          return Number.isNaN(num) ? -Infinity : num;
        }
        if (type === 'date') {
          const ts = Date.parse(raw);
          return Number.isNaN(ts) ? -Infinity : ts;
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
          if (type === 'number' || type === 'date') {
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
  </script>

  <?php if ($role === 'O'): ?>
  <div class="modal-backdrop" id="editExpenseModal">
    <div class="modal-box">
      <div class="card-title">Edit Expense</div>
      <form method="POST" action="expenses.php">
        <input type="hidden" name="expense_id" id="edit_expense_id">
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">EXPENSE NAME</label>
            <input class="form-control" type="text" name="category" id="edit_expense_category" required>
          </div>
          <div class="form-group">
            <label class="form-label">AMOUNT</label>
            <input class="form-control" type="number" step="0.01" min="0.01" name="amount" id="edit_expense_amount" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">NOTES</label>
          <textarea class="form-control" name="expense_description" id="edit_expense_description"></textarea>
        </div>
        <div class="modal-actions">
          <button class="btn btn-primary" type="submit" name="edit_expense">SAVE CHANGES</button>
          <button class="btn" type="button" onclick="closeEditExpenseModal()">CANCEL</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openEditExpenseModal(btn) {
      document.getElementById('edit_expense_id').value          = btn.dataset.id;
      document.getElementById('edit_expense_category').value    = btn.dataset.category;
      document.getElementById('edit_expense_amount').value      = btn.dataset.amount;
      document.getElementById('edit_expense_description').value = btn.dataset.desc;
      document.getElementById('editExpenseModal').classList.add('open');
    }

    function closeEditExpenseModal() {
      document.getElementById('editExpenseModal').classList.remove('open');
    }

    document.getElementById('editExpenseModal').addEventListener('click', function (e) {
      if (e.target === this) closeEditExpenseModal();
    });
  </script>
  <?php endif; ?>
</body>
</html>