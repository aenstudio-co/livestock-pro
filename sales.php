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

$lv_stmt = $pdo->prepare("
    SELECT l.livestock_id, l.animal_type, l.breed, l.quantity
    FROM `livestock` l
    JOIN `user` u ON l.user_id COLLATE utf8mb4_unicode_ci = u.user_id COLLATE utf8mb4_unicode_ci
    WHERE LOWER(TRIM(u.business_name)) = LOWER(TRIM(:bname))
    ORDER BY l.livestock_id ASC
");
$lv_stmt->execute(['bname' => $business]);
$livestock_list = $lv_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_sale']) && $role === 'O') {
    $lid       = trim($_POST['livestock_id'] ?? '');
    $qty       = (int)($_POST['quantity_sold'] ?? 0);
    $amount    = (float)($_POST['amount'] ?? 0);
    $date_sold = trim($_POST['date_sold'] ?? '');
    $date_ok   = DateTime::createFromFormat('Y-m-d', $date_sold);
    $final_dt  = ($date_ok && $date_ok->format('Y-m-d') === $date_sold) ? $date_sold : date('Y-m-d');

    if ($lid !== '' && $qty > 0 && $amount > 0) {
        try {
            $pdo->beginTransaction();

            $chk = $pdo->prepare("
                SELECT l.quantity
                FROM `livestock` l
                JOIN `user` u ON l.user_id COLLATE utf8mb4_unicode_ci = u.user_id COLLATE utf8mb4_unicode_ci
                WHERE l.`livestock_id` = :lid
                  AND LOWER(TRIM(u.business_name)) = LOWER(TRIM(:bname))
                FOR UPDATE
            ");
            $chk->execute(['lid' => $lid, 'bname' => $business]);
            $avail_qty = $chk->fetchColumn();

            if ($avail_qty === false) {
                $pdo->rollBack();
                $error = "Selected livestock record not found.";
            } elseif ((int)$avail_qty < $qty) {
                $pdo->rollBack();
                $error = "Insufficient stock. Available quantity is only " . (int)$avail_qty . ".";
            } else {
                $max_num = (int)$pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING(`sales_id`, 5) AS UNSIGNED)), 0) FROM `sales`")->fetchColumn();
                $new_id  = sprintf("SLS-%05d", $max_num + 1);

                $pdo->prepare("
                    INSERT INTO `sales` (`sales_id`, `livestock_id`, `amount`, `quantity_sold`, `date_sold`)
                    VALUES (:sid, :lid, :amt, :qty, :dt)
                ")->execute([
                    'sid' => $new_id,
                    'lid' => $lid,
                    'amt' => $amount,
                    'qty' => $qty,
                    'dt'  => $final_dt
                ]);

                $pdo->prepare("
                    UPDATE `livestock`
                    SET `quantity` = `quantity` - :qty
                    WHERE `livestock_id` = :lid
                ")->execute([
                    'qty' => $qty,
                    'lid' => $lid
                ]);

                $pdo->commit();
                $_SESSION['flash_message'] = "Sale {$new_id} recorded and livestock stock updated.";
                header("Location: sales.php");
                exit;
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Unable to record sale. Please try again.";
        }
    } else {
        $error = "Please select livestock and enter a valid quantity and amount.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_sale']) && $role === 'O') {
    $sid       = trim($_POST['sales_id'] ?? '');
    $lid       = trim($_POST['livestock_id'] ?? '');
    $qty       = (int)($_POST['quantity_sold'] ?? 0);
    $amount    = (float)($_POST['amount'] ?? 0);
    $date_sold = trim($_POST['date_sold'] ?? '');
    $date_ok   = DateTime::createFromFormat('Y-m-d', $date_sold);

    if ($sid !== '' && $lid !== '' && $qty > 0 && $amount > 0 && $date_ok && $date_ok->format('Y-m-d') === $date_sold) {
        try {
            $pdo->beginTransaction();

            $old_stmt = $pdo->prepare("
                SELECT s.livestock_id, s.quantity_sold
                FROM `sales` s
                JOIN `livestock` l ON s.livestock_id COLLATE utf8mb4_unicode_ci = l.livestock_id COLLATE utf8mb4_unicode_ci
                JOIN `user` u ON l.user_id COLLATE utf8mb4_unicode_ci = u.user_id COLLATE utf8mb4_unicode_ci
                WHERE s.`sales_id` = :sid
                  AND LOWER(TRIM(u.business_name)) = LOWER(TRIM(:bname))
                FOR UPDATE
            ");
            $old_stmt->execute(['sid' => $sid, 'bname' => $business]);
            $old_sale = $old_stmt->fetch();

            if (!$old_sale) {
                $pdo->rollBack();
                $error = "Sale record not found.";
            } else {
                $old_lid = $old_sale['livestock_id'];
                $old_qty = (int)$old_sale['quantity_sold'];

                // Restore old quantity first
                $pdo->prepare("UPDATE `livestock` SET `quantity` = `quantity` + :oqty WHERE `livestock_id` = :olid")
                    ->execute(['oqty' => $old_qty, 'olid' => $old_lid]);

                // Check new livestock stock
                $chk = $pdo->prepare("
                    SELECT l.quantity
                    FROM `livestock` l
                    JOIN `user` u ON l.user_id COLLATE utf8mb4_unicode_ci = u.user_id COLLATE utf8mb4_unicode_ci
                    WHERE l.`livestock_id` = :lid
                      AND LOWER(TRIM(u.business_name)) = LOWER(TRIM(:bname))
                    FOR UPDATE
                ");
                $chk->execute(['lid' => $lid, 'bname' => $business]);
                $avail_qty = $chk->fetchColumn();

                if ($avail_qty === false || (int)$avail_qty < $qty) {
                    $pdo->rollBack();
                    $error = "Insufficient stock for the updated quantity. Available: " . (int)$avail_qty . ".";
                } else {
                    // Deduct new quantity
                    $pdo->prepare("UPDATE `livestock` SET `quantity` = `quantity` - :qty WHERE `livestock_id` = :lid")
                        ->execute(['qty' => $qty, 'lid' => $lid]);

                    $pdo->prepare("
                        UPDATE `sales`
                        SET `livestock_id` = :lid,
                            `quantity_sold` = :qty,
                            `amount` = :amt,
                            `date_sold` = :dt
                        WHERE `sales_id` = :sid
                    ")->execute([
                        'lid' => $lid,
                        'qty' => $qty,
                        'amt' => $amount,
                        'dt'  => $date_sold,
                        'sid' => $sid
                    ]);

                    $pdo->commit();
                    $_SESSION['flash_message'] = "Updated {$sid} and adjusted livestock stock.";
                    header("Location: sales.php");
                    exit;
                }
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Unable to update sale. Please try again.";
        }
    } else {
        $error = "Please select livestock, enter a valid quantity and amount, and provide a valid date.";
    }
}

if (isset($_GET['delete']) && $role === 'O') {
    $del_sid = trim($_GET['delete']);
    if ($del_sid !== '') {
        try {
            $pdo->beginTransaction();

            $old_stmt = $pdo->prepare("
                SELECT s.livestock_id, s.quantity_sold
                FROM `sales` s
                JOIN `livestock` l ON s.livestock_id COLLATE utf8mb4_unicode_ci = l.livestock_id COLLATE utf8mb4_unicode_ci
                JOIN `user` u ON l.user_id COLLATE utf8mb4_unicode_ci = u.user_id COLLATE utf8mb4_unicode_ci
                WHERE s.`sales_id` = :sid
                  AND LOWER(TRIM(u.business_name)) = LOWER(TRIM(:bname))
                FOR UPDATE
            ");
            $old_stmt->execute(['sid' => $del_sid, 'bname' => $business]);
            $old_sale = $old_stmt->fetch();

            if ($old_sale) {
                $pdo->prepare("UPDATE `livestock` SET `quantity` = `quantity` + :qty WHERE `livestock_id` = :lid")
                    ->execute(['qty' => (int)$old_sale['quantity_sold'], 'lid' => $old_sale['livestock_id']]);

                $pdo->prepare("DELETE FROM `sales` WHERE `sales_id` = :sid")
                    ->execute(['sid' => $del_sid]);
            }

            $pdo->commit();
            $_SESSION['flash_message'] = "Deleted {$del_sid} and restored livestock stock.";
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }
    }
    header("Location: sales.php");
    exit;
}

$tot_stmt = $pdo->prepare("
    SELECT COALESCE(SUM(s.amount), 0)
    FROM `sales` s
    JOIN `livestock` l ON s.livestock_id COLLATE utf8mb4_unicode_ci = l.livestock_id COLLATE utf8mb4_unicode_ci
    JOIN `user` u ON l.user_id COLLATE utf8mb4_unicode_ci = u.user_id COLLATE utf8mb4_unicode_ci
    WHERE LOWER(TRIM(u.business_name)) = LOWER(TRIM(:bname))
");
$tot_stmt->execute(['bname' => $business]);
$total_sales = (float)$tot_stmt->fetchColumn();

$rec_stmt = $pdo->prepare("
    SELECT s.*, l.animal_type
    FROM `sales` s
    JOIN `livestock` l ON s.livestock_id COLLATE utf8mb4_unicode_ci = l.livestock_id COLLATE utf8mb4_unicode_ci
    JOIN `user` u ON l.user_id COLLATE utf8mb4_unicode_ci = u.user_id COLLATE utf8mb4_unicode_ci
    WHERE LOWER(TRIM(u.business_name)) = LOWER(TRIM(:bname))
    ORDER BY s.sales_id DESC
");
$rec_stmt->execute(['bname' => $business]);
$records = $rec_stmt->fetchAll();

$sbi_stmt = $pdo->prepare("
    SELECT
        COALESCE(l.animal_type, s.livestock_id, 'LIVESTOCK') AS item_label,
        COALESCE(SUM(s.amount), 0) AS total_amt
    FROM `sales` s
    JOIN `livestock` l ON s.livestock_id COLLATE utf8mb4_unicode_ci = l.livestock_id COLLATE utf8mb4_unicode_ci
    JOIN `user` u ON l.user_id COLLATE utf8mb4_unicode_ci = u.user_id COLLATE utf8mb4_unicode_ci
    WHERE LOWER(TRIM(u.business_name)) = LOWER(TRIM(:bname))
    GROUP BY COALESCE(l.animal_type, s.livestock_id, 'LIVESTOCK')
    ORDER BY total_amt DESC
    LIMIT 7
");
$sbi_stmt->execute(['bname' => $business]);
$sales_by_item = $sbi_stmt->fetchAll();

$max_sale_bar = 1;
foreach ($sales_by_item as $sb) {
    if ((float)$sb['total_amt'] > $max_sale_bar) {
        $max_sale_bar = (float)$sb['total_amt'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sales &amp; Income - LivestockPro</title>
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
      max-width: 540px;
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
        <li><a href="livestock.php">Livestock</a></li>
        <li><a href="task_schedule.php">Task Schedule</a></li>
        <li><a href="expenses.php">Expenses</a></li>
        <li><a class="active" href="sales.php">Sales</a></li>
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
        <h1 class="page-title">Sales &amp; Income</h1>
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
            <div class="card-title">Record Sale</div>
            <form method="POST" action="sales.php">
              <div class="form-grid-2">
                <div class="form-group">
                  <label class="form-label">LIVESTOCK</label>
                  <select class="form-control" name="livestock_id" required>
                    <option value="">Select Livestock</option>
                    <?php foreach ($livestock_list as $lv): ?>
                      <option value="<?= htmlspecialchars($lv['livestock_id']) ?>">
                        <?= htmlspecialchars($lv['animal_type'] . ' (' . $lv['breed'] . ') - Stock: ' . (int)$lv['quantity']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">QUANTITY</label>
                  <input class="form-control" type="number" name="quantity_sold" min="1" required>
                </div>
                <div class="form-group">
                  <label class="form-label">AMOUNT</label>
                  <input class="form-control" type="number" step="0.01" name="amount" placeholder="0.00" required>
                </div>
                <div class="form-group">
                  <label class="form-label">DATE</label>
                  <input class="form-control" type="date" name="date_sold" value="<?= date('Y-m-d') ?>">
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">NOTES</label>
                <textarea class="form-control" name="notes"></textarea>
              </div>
              <button class="btn btn-primary" type="submit" name="record_sale">RECORD SALE</button>
            </form>
          </div>

          <div class="card summary-card">
            <div class="summary-card-left">
              <div>
                <div class="card-title">Sales &amp; Income Summary</div>
                <div class="kpi-sub">As of <?= date('H:i:s') ?></div>
              </div>
              <div class="kpi-value">&#8369;<?= number_format($total_sales, 0) ?></div>
              <a class="kpi-link" href="reports.php">View Full Summary &rarr;</a>
            </div>

            <div class="summary-card-right">
              <?php if (empty($sales_by_item)): ?>
                <div class="kpi-sub">No sales data available yet.</div>
              <?php else: ?>
                <div class="simple-bar-chart">
                  <?php foreach ($sales_by_item as $sb): ?>
                    <?php $bar_h = max(18, (int)round(((float)$sb['total_amt'] / $max_sale_bar) * 120)); ?>
                    <div class="simple-bar-item">
                      <div class="simple-bar-value">&#8369;<?= number_format((float)$sb['total_amt'], 0) ?></div>
                      <div class="simple-bar-fill" style="height:<?= $bar_h ?>px;"></div>
                      <div class="simple-bar-name"><?= htmlspecialchars($sb['item_label']) ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-title">Sales Record</div>
        <table class="data-table">
          <thead>
            <tr>
              <th>NO.</th><th>LIVESTOCK SOLD</th><th>QUANTITY</th><th>AMOUNT</th><th>DATE</th>
              <?php if ($role === 'O'): ?><th>ACTIONS</th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($records as $i => $r): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= strtoupper(htmlspecialchars($r['animal_type'] ?? 'LIVESTOCK')) ?></td>
                <td><?= (int)$r['quantity_sold'] ?></td>
                <td>&#8369;<?= number_format((float)$r['amount'], 0) ?></td>
                <td style="white-space:nowrap;"><?= htmlspecialchars($r['date_sold']) ?></td>
                <?php if ($role === 'O'): ?>
                  <td>
                    <button type="button" class="btn btn-sm btn-edit"
                      data-id="<?= htmlspecialchars($r['sales_id']) ?>"
                      data-lid="<?= htmlspecialchars($r['livestock_id']) ?>"
                      data-qty="<?= htmlspecialchars((string)$r['quantity_sold']) ?>"
                      data-amount="<?= htmlspecialchars((string)$r['amount']) ?>"
                      data-date="<?= htmlspecialchars(substr((string)$r['date_sold'], 0, 10)) ?>"
                      onclick="openEditSaleModal(this)">EDIT</button>
                    <a class="btn btn-sm btn-delete" href="sales.php?delete=<?= urlencode($r['sales_id']) ?>" onclick="return confirm('Delete sale?')" title="Delete"><svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg></a>
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
  <div class="modal-backdrop" id="editSaleModal">
    <div class="modal-box">
      <div class="card-title">Edit Sale</div>
      <form method="POST" action="sales.php">
        <input type="hidden" name="sales_id" id="edit_sale_id">
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">LIVESTOCK</label>
            <select class="form-control" name="livestock_id" id="edit_sale_livestock_id" required>
              <?php foreach ($livestock_list as $lv): ?>
                <option value="<?= htmlspecialchars($lv['livestock_id']) ?>">
                  <?= htmlspecialchars($lv['animal_type'] . ' (' . $lv['breed'] . ') - Stock: ' . (int)$lv['quantity']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">QUANTITY</label>
            <input class="form-control" type="number" name="quantity_sold" id="edit_sale_quantity" min="1" required>
          </div>
          <div class="form-group">
            <label class="form-label">AMOUNT</label>
            <input class="form-control" type="number" step="0.01" min="0.01" name="amount" id="edit_sale_amount" required>
          </div>
          <div class="form-group">
            <label class="form-label">DATE</label>
            <input class="form-control" type="date" name="date_sold" id="edit_sale_date" required>
          </div>
        </div>
        <div class="modal-actions">
          <button class="btn btn-primary" type="submit" name="edit_sale">SAVE CHANGES</button>
          <button class="btn" type="button" onclick="closeEditSaleModal()">CANCEL</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openEditSaleModal(btn) {
      document.getElementById('edit_sale_id').value           = btn.dataset.id;
      document.getElementById('edit_sale_livestock_id').value = btn.dataset.lid;
      document.getElementById('edit_sale_quantity').value     = btn.dataset.qty;
      document.getElementById('edit_sale_amount').value       = btn.dataset.amount;
      document.getElementById('edit_sale_date').value         = btn.dataset.date;
      document.getElementById('editSaleModal').classList.add('open');
    }

    function closeEditSaleModal() {
      document.getElementById('editSaleModal').classList.remove('open');
    }

    document.getElementById('editSaleModal').addEventListener('click', function (e) {
      if (e.target === this) closeEditSaleModal();
    });
  </script>
  <?php endif; ?>
</body>
</html>