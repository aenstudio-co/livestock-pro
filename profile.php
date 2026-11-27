<?php
require_once 'includes/db_connect.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id  = $_SESSION['user_id'];
$role     = strtoupper($_SESSION['user_type'] ?? 'O');
$business = $_SESSION['business_name'] ?? 'Business Name';
$message  = "";
$error    = "";

if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

$stmt_u = $pdo->prepare("
    SELECT u.*, a.Province, a.Municipality_City, a.Barangay, a.ZipCode
    FROM `user` u
    LEFT JOIN `address` a ON u.address_id = a.address_id
    WHERE u.`user_id` = :uid
    LIMIT 1
");
$stmt_u->execute(['uid' => $user_id]);
$u_row = $stmt_u->fetch() ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $fn  = trim($_POST['first_name'] ?? '');
    $ln  = trim($_POST['last_name'] ?? '');
    $cn  = trim($_POST['contact_number'] ?? '');
    $em  = trim($_POST['email'] ?? '');
    $prv = trim($_POST['province'] ?? '');
    $cty = trim($_POST['municipality_city'] ?? '');
    $brg = trim($_POST['barangay'] ?? '');
    $zip = trim($_POST['zip_code'] ?? '');

    if ($fn === '' || $ln === '') {
        $error = "First name and last name are required.";
    } elseif ($em !== '' && !preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/i', $em)) {
        $error = "Email address must end with @gmail.com.";
    } else {
        $pdo->prepare("
            UPDATE `user`
            SET `first_name` = :fn, `last_name` = :ln, `contact_number` = :cn, `email` = :em
            WHERE `user_id` = :uid
        ")->execute(['fn' => $fn, 'ln' => $ln, 'cn' => $cn, 'em' => $em, 'uid' => $user_id]);

        $addr_id = trim((string)($u_row['address_id'] ?? ''));
        if ($addr_id !== '') {
            $pdo->prepare("
                UPDATE `address`
                SET `Province` = :prv, `Municipality_City` = :cty, `Barangay` = :brg, `ZipCode` = :zip
                WHERE `address_id` = :aid
            ")->execute([
                'prv' => $prv,
                'cty' => $cty,
                'brg' => $brg,
                'zip' => $zip,
                'aid' => $addr_id
            ]);
        }

        $_SESSION['first_name'] = $fn;
        $_SESSION['last_name']  = $ln;
        $message = "Profile information updated.";

        $stmt_u->execute(['uid' => $user_id]);
        $u_row = $stmt_u->fetch() ?: $u_row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manage_account'])) {
    $uname    = trim($_POST['username'] ?? '');
    $new_pass = trim($_POST['new_password'] ?? '');

    if ($uname === '') {
        $error = "Username cannot be empty.";
    } else {
        if ($new_pass !== '') {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE `user` SET `username` = :un, `password` = :pw WHERE `user_id` = :uid")
                ->execute(['un' => $uname, 'pw' => $hashed, 'uid' => $user_id]);
            $message = "Account username and password updated.";
        } else {
            $pdo->prepare("UPDATE `user` SET `username` = :un WHERE `user_id` = :uid")
                ->execute(['un' => $uname, 'uid' => $user_id]);
            $message = "Account username updated.";
        }
        $stmt_u->execute(['uid' => $user_id]);
        $u_row = $stmt_u->fetch() ?: $u_row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_capital']) && $role === 'O') {
    $cap_amount = (float)($_POST['capital_amount'] ?? 0);
    $cap_date   = trim($_POST['capital_date'] ?? '');
    $cap_desc   = trim($_POST['capital_description'] ?? '');

    if ($cap_date === '') {
        $cap_date = date('Y-m-d');
    }

    $date_ok = DateTime::createFromFormat('Y-m-d', $cap_date);
    if ($cap_amount <= 0) {
        $error = "Capital amount must be greater than zero.";
    } elseif (!$date_ok || $date_ok->format('Y-m-d') !== $cap_date) {
        $error = "Please enter a valid date.";
    } else {
        $max_num = (int)$pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING(cap_id, 5) AS UNSIGNED)), 0) FROM `capital`")->fetchColumn();
        $new_cap_id = 'CAP-' . str_pad((string)($max_num + 1), 5, '0', STR_PAD_LEFT);

        $stmt_cap = $pdo->prepare("INSERT INTO `capital` (`cap_id`, `user_id`, `cap_description`, `cap_amount`, `date_invested`) VALUES (:cid, :uid, :ds, :amt, :dt)");
        $stmt_cap->bindValue(':cid', $new_cap_id, PDO::PARAM_STR);
        $stmt_cap->bindValue(':uid', $user_id, PDO::PARAM_STR);
        $stmt_cap->bindValue(':ds', $cap_desc, PDO::PARAM_STR);
        $stmt_cap->bindValue(':amt', $cap_amount);
        $stmt_cap->bindValue(':dt', $cap_date !== '' ? $cap_date : null, $cap_date !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt_cap->execute();

        $_SESSION['flash_message'] = "Capital added successfully.";
        header("Location: profile.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_staff_uid']) && $role === 'O') {
    $target_uid = trim($_POST['approve_staff_uid'] ?? '');
    if ($target_uid !== '') {
        $checkStaff = $pdo->prepare("
            SELECT s.user_id
            FROM `staff` s
            INNER JOIN `user` u ON s.user_id = u.user_id
            WHERE s.user_id = :uid
              AND u.user_type = 'S'
              AND LOWER(TRIM(u.business_name)) = LOWER(TRIM(:bname))
            LIMIT 1
        ");
        $checkStaff->execute(['uid' => $target_uid, 'bname' => $business]);
        if ($checkStaff->fetch()) {
            $hiredDate = date('Y-m-d');
            $stmt_app = $pdo->prepare("UPDATE `staff` SET `staff_status` = 'Approved', `date_hired` = :dhired WHERE `user_id` = :uid");
            $stmt_app->bindValue(':dhired', $hiredDate !== '' ? $hiredDate : null, $hiredDate !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt_app->bindValue(':uid', $target_uid, PDO::PARAM_STR);
            $stmt_app->execute();

            $_SESSION['flash_message'] = "Staff account approved.";
            header("Location: profile.php");
            exit;
        }
    }
}

$stmt_u = $pdo->prepare("
    SELECT u.*, a.Province, a.Municipality_City, a.Barangay, a.ZipCode
    FROM `user` u
    LEFT JOIN `address` a ON u.address_id = a.address_id
    WHERE u.`user_id` = :uid
    LIMIT 1
");
$stmt_u->execute(['uid' => $user_id]);
$u_row = $stmt_u->fetch() ?: [];
$full_name = trim(($u_row['first_name'] ?? $_SESSION['first_name'] ?? '') . ' ' . ($u_row['last_name'] ?? $_SESSION['last_name'] ?? ''));
if ($full_name === '') {
    $full_name = $user_id;
}

$total_income   = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM `sales`")->fetchColumn();
$capital_amount = (float)$pdo->query("SELECT COALESCE(SUM(cap_amount), 0) FROM `capital`")->fetchColumn();

if ($role === 'O') {
    $pending_tasks      = (int)$pdo->query("SELECT COUNT(*) FROM `task_schedule` WHERE `task_status` = 'Pending'")->fetchColumn();
    $in_progress_tasks  = (int)$pdo->query("SELECT COUNT(*) FROM `task_schedule` WHERE `task_status` = 'In Progress'")->fetchColumn();
    $completed_tasks    = (int)$pdo->query("SELECT COUNT(*) FROM `task_schedule` WHERE `task_status` = 'Completed'")->fetchColumn();
    $staff_task_history = [];
} else {
    $stmt_p = $pdo->prepare("SELECT COUNT(*) FROM `task_schedule` WHERE `user_id` = :uid AND `task_status` = 'Pending'");
    $stmt_p->execute(['uid' => $user_id]);
    $pending_tasks = (int)$stmt_p->fetchColumn();

    $stmt_ip = $pdo->prepare("SELECT COUNT(*) FROM `task_schedule` WHERE `user_id` = :uid AND `task_status` = 'In Progress'");
    $stmt_ip->execute(['uid' => $user_id]);
    $in_progress_tasks = (int)$stmt_ip->fetchColumn();

    $stmt_c = $pdo->prepare("SELECT COUNT(*) FROM `task_schedule` WHERE `user_id` = :uid AND `task_status` = 'Completed'");
    $stmt_c->execute(['uid' => $user_id]);
    $completed_tasks = (int)$stmt_c->fetchColumn();

    $stmt_th = $pdo->prepare("
        SELECT task_id, task_name, task_description, schedule_date
        FROM `task_schedule`
        WHERE `user_id` = :uid
        ORDER BY schedule_date DESC, task_id DESC
        LIMIT 8
    ");
    $stmt_th->execute(['uid' => $user_id]);
    $staff_task_history = $stmt_th->fetchAll();
}

$investment_history = $pdo->query("
    SELECT cap_id, cap_description, cap_amount, date_invested
    FROM `capital`
    ORDER BY date_invested DESC, cap_id DESC
    LIMIT 6
")->fetchAll();

$staff_rows = $pdo->query("
    SELECT s.user_id, u.first_name, u.last_name, s.date_hired, s.staff_status
    FROM `staff` s
    JOIN `user` u ON s.user_id = u.user_id
    ORDER BY s.user_id ASC
")->fetchAll();

$pending_staff_stmt = $pdo->prepare("
    SELECT s.user_id, u.first_name, u.last_name, u.username, u.business_name
    FROM `staff` s
    INNER JOIN `user` u ON s.user_id = u.user_id
    WHERE u.user_type = 'S'
      AND LOWER(TRIM(u.business_name)) = LOWER(TRIM(:bname))
      AND LOWER(TRIM(COALESCE(s.staff_status, 'pending'))) = 'pending'
    ORDER BY s.user_id ASC
");
$pending_staff_stmt->execute(['bname' => $business]);
$pending_staff_rows = $pending_staff_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile - LivestockPro</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    .staff-hero-stats {
      display: flex;
      align-items: center;
      gap: 32px;
    }
    .staff-hero-stat {
      padding-left: 24px;
      border-left: 1px solid var(--border-subtle);
      text-align: center;
    }
    .form-divider {
      border: none;
      border-top: 1px solid var(--border-subtle);
      margin: 16px 0 18px;
    }
    .profile-col-stack {
      display: flex;
      flex-direction: column;
      gap: 18px;
      min-width: 0;
    }
    .profile-col-stack > .card {
      margin-bottom: 0;
    }
    .staff-approval-empty {
      font-size: 13px;
      color: var(--text-secondary);
    }
    .btn-approve-staff {
      padding: 5px 12px;
      font-size: 10px;
    }
    @media (max-width: 960px) {
      .staff-hero-stats {
        flex-wrap: wrap;
        gap: 18px;
      }
      .staff-hero-stat {
        padding-left: 0;
        border-left: none;
        text-align: left;
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
        <li><a href="<?= $role === 'O' ? 'owner_dashboard.php' : 'staff_dashboard.php' ?>">Dashboard</a></li>
        <li><a href="livestock.php">Livestock</a></li>
        <li><a href="task_schedule.php">Task Schedule</a></li>
        <li><a href="expenses.php">Expenses</a></li>
        <li><a href="sales.php">Sales</a></li>
        <?php if ($role === 'O'): ?>
          <li><a href="reports.php">Reports</a></li>
        <?php endif; ?>
      </ul>
      <div class="sidebar-user-wrap">
        <a class="sidebar-user-link active" href="profile.php">
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
        <h1 class="page-title">My Profile</h1>
        <div class="page-datetime"><?= date('F d, Y | H:i:s') ?></div>
      </div>

      <?php if ($message !== ""): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>
      <?php if ($error !== ""): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <div class="profile-hero">
        <?php if ($role === 'O'): ?>
          <div style="display:flex;align-items:center;gap:14px;">
            <div class="sidebar-avatar" style="width:52px;height:52px;font-size:18px;"><?= strtoupper(substr($full_name, 0, 2)) ?></div>
            <div>
              <div style="font-size:18px;font-weight:700;"><?= htmlspecialchars($full_name) ?></div>
              <div style="font-size:12px;color:var(--text-secondary);">Owner of <?= htmlspecialchars($business) ?></div>
            </div>
          </div>

          <div style="display:flex;gap:32px;align-items:center;">
            <div>
              <div style="font-size:22px;font-weight:700;">&#8369;<?= number_format($capital_amount, 0) ?></div>
              <div style="font-size:10px;color:var(--text-secondary);">CAPITAL</div>
            </div>
            <div>
              <div style="font-size:22px;font-weight:700;">&#8369;<?= number_format($total_income, 0) ?></div>
              <div style="font-size:10px;color:var(--text-secondary);">TOTAL INCOME</div>
            </div>
          </div>
        <?php else: ?>
          <div>
            <div style="font-size:26px;font-weight:700;line-height:1.15;"><?= htmlspecialchars($full_name) ?></div>
            <div style="font-size:12px;font-weight:600;color:var(--text-primary);margin-top:4px;">Staff of <?= htmlspecialchars($business) ?></div>
          </div>

          <div class="staff-hero-stats">
            <div class="staff-hero-stat">
              <div style="font-size:26px;font-weight:700;line-height:1.15;"><?= $completed_tasks ?></div>
              <div style="font-size:10px;font-weight:600;color:var(--text-secondary);margin-top:4px;">TOTAL TASKS COMPLETED</div>
            </div>
            <div class="staff-hero-stat">
              <div style="font-size:26px;font-weight:700;line-height:1.15;"><?= $in_progress_tasks ?></div>
              <div style="font-size:10px;font-weight:600;color:var(--text-secondary);margin-top:4px;">IN PROGRESS TASKS</div>
            </div>
            <div class="staff-hero-stat">
              <div style="font-size:26px;font-weight:700;line-height:1.15;"><?= $pending_tasks ?></div>
              <div style="font-size:10px;font-weight:600;color:var(--text-secondary);margin-top:4px;">PENDING TASKS</div>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <div class="split-grid">
        <div class="profile-col-stack">
          <div class="card">
            <div class="card-title">Edit Personal Information</div>
            <form method="POST" action="profile.php">
              <div class="form-grid-2">
                <div class="form-group">
                  <label class="form-label">FARM NAME</label>
                  <input class="form-control" type="text" value="<?= htmlspecialchars($business) ?>" readonly>
                </div>
                <div class="form-group">
                  <label class="form-label">CONTACT NUMBER</label>
                  <input class="form-control" type="text" name="contact_number" placeholder="09XX-XXX-XXXX" value="<?= htmlspecialchars($u_row['contact_number'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">FIRST NAME</label>
                  <input class="form-control" type="text" name="first_name" value="<?= htmlspecialchars($u_row['first_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">LAST NAME</label>
                  <input class="form-control" type="text" name="last_name" value="<?= htmlspecialchars($u_row['last_name'] ?? '') ?>">
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">EMAIL</label>
                <input class="form-control" type="email" name="email" pattern="^[a-zA-Z0-9._%+-]+@gmail\.com$" title="Email must end with @gmail.com" value="<?= htmlspecialchars($u_row['email'] ?? '') ?>">
              </div>

              <hr class="form-divider">

              <div class="form-grid-2">
                <div class="form-group">
                  <label class="form-label">PROVINCE</label>
                  <input class="form-control" type="text" name="province" placeholder="Enter province" value="<?= htmlspecialchars($u_row['Province'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">MUNICIPALITY/CITY</label>
                  <input class="form-control" type="text" name="municipality_city" placeholder="Enter municipality or city" value="<?= htmlspecialchars($u_row['Municipality_City'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">BARANGAY</label>
                  <input class="form-control" type="text" name="barangay" placeholder="Enter barangay" value="<?= htmlspecialchars($u_row['Barangay'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">ZIP CODE</label>
                  <input class="form-control" type="text" name="zip_code" maxlength="4" placeholder="Enter zip code" value="<?= htmlspecialchars($u_row['ZipCode'] ?? '') ?>">
                </div>
              </div>

              <button class="btn btn-primary" type="submit" name="save_profile">SAVE CHANGES</button>
            </form>
          </div>

          <div class="card">
            <div class="card-title">Manage Account</div>
            <form method="POST" action="profile.php">
              <div class="form-grid-2">
                <div class="form-group">
                  <label class="form-label">USERNAME</label>
                  <input class="form-control" type="text" name="username" value="<?= htmlspecialchars($u_row['username'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                  <label class="form-label">NEW PASSWORD (OPTIONAL)</label>
                  <input class="form-control" type="password" name="new_password" placeholder="Leave blank to keep current">
                </div>
              </div>
              <button class="btn btn-primary" type="submit" name="manage_account">UPDATE ACCOUNT</button>
            </form>
          </div>

          <?php if ($role === 'O'): ?>
            <div class="card">
              <div class="card-title">Staff List</div>
              <table class="data-table">
                <thead><tr><th>USERNAME</th><th>NAME</th><th>DATE STARTED</th></tr></thead>
                <tbody>
                  <?php foreach ($staff_rows as $sr): ?>
                    <?php
                      $stStatus = strtolower(trim((string)($sr['staff_status'] ?? 'pending')));
                      $dhRaw = trim((string)($sr['date_hired'] ?? ''));
                      $dhDisplay = ($stStatus === 'approved' && $dhRaw !== '' && strtotime($dhRaw) !== false)
                          ? date('n/j/Y', strtotime($dhRaw))
                          : 'Pending';
                    ?>
                    <tr>
                      <td><?= htmlspecialchars($sr['user_id']) ?></td>
                      <td><?= strtoupper(htmlspecialchars($sr['first_name'] . ' ' . $sr['last_name'])) ?></td>
                      <td style="white-space:nowrap;"><?= htmlspecialchars($dhDisplay) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

        <div class="profile-col-stack">
          <?php if ($role === 'O'): ?>
            <div class="card">
              <div class="card-title">Add Capital</div>
              <form method="POST" action="profile.php">
                <div class="form-group">
                  <label class="form-label">AMOUNT</label>
                  <input class="form-control" type="number" name="capital_amount" min="0.01" step="0.01" placeholder="0.00" required>
                </div>
                <div class="form-group">
                  <label class="form-label">DATE</label>
                  <input class="form-control" type="date" name="capital_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                  <label class="form-label">DESCRIPTION</label>
                  <textarea class="form-control" name="capital_description" rows="3" style="resize:vertical;min-height:70px;font-family:inherit;" placeholder="Optional note about this capital"></textarea>
                </div>
                <button class="btn btn-primary" type="submit" name="add_capital">ADD CAPITAL</button>
              </form>
            </div>

            <div class="card">
              <div class="card-title">Investment History</div>
              <table class="data-table">
                <thead>
                  <tr><th>ID.</th><th>DESCRIPTION</th><th>AMOUNT</th><th>DATE</th></tr>
                </thead>
                <tbody>
                  <?php if (empty($investment_history)): ?>
                    <tr><td colspan="4" style="text-align:center;color:var(--text-muted);">No capital added yet.</td></tr>
                  <?php endif; ?>
                  <?php foreach ($investment_history as $inv): ?>
                    <tr>
                      <td><?= htmlspecialchars($inv['cap_id']) ?></td>
                      <td><?= htmlspecialchars(($inv['cap_description'] ?? '') !== '' ? mb_strimwidth($inv['cap_description'], 0, 24, '...') : '-') ?></td>
                      <td>&#8369;<?= number_format((float)$inv['cap_amount'], 0) ?></td>
                      <td style="white-space:nowrap;"><?= htmlspecialchars(substr((string)($inv['date_invested'] ?? ''), 0, 10)) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <div class="card staff-approval-card">
              <div class="card-title">Pending Staff Approvals</div>
              <?php if (empty($pending_staff_rows)): ?>
                <div class="staff-approval-empty">No pending staff requests for <?= htmlspecialchars($business) ?>.</div>
              <?php else: ?>
                <table class="data-table">
                  <thead>
                    <tr><th>USERNAME</th><th>NAME</th><th>BUSINESS</th><th>ACTION</th></tr>
                  </thead>
                  <tbody>
                    <?php foreach ($pending_staff_rows as $psr): ?>
                      <tr>
                        <td><?= htmlspecialchars($psr['username'] ?: $psr['user_id']) ?></td>
                        <td><?= strtoupper(htmlspecialchars($psr['first_name'] . ' ' . $psr['last_name'])) ?></td>
                        <td><?= htmlspecialchars($psr['business_name']) ?></td>
                        <td>
                          <form method="POST" action="profile.php" style="display:inline;">
                            <input type="hidden" name="approve_staff_uid" value="<?= htmlspecialchars($psr['user_id']) ?>">
                            <button class="btn btn-primary btn-approve-staff" type="submit">Approve</button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <div class="card">
              <div class="card-title">Task History</div>
              <table class="data-table">
                <thead>
                  <tr><th>ID</th><th>DESCRIPTION</th><th>DATE</th></tr>
                </thead>
                <tbody>
                  <?php if (empty($staff_task_history)): ?>
                    <tr><td colspan="3" style="text-align:center;color:var(--text-muted);">No tasks assigned to you yet.</td></tr>
                  <?php endif; ?>
                  <?php foreach ($staff_task_history as $th): ?>
                    <?php
                      $desc_txt = trim((string)($th['task_description'] ?? ''));
                      if ($desc_txt === '') {
                          $desc_txt = (string)($th['task_name'] ?? '');
                      }
                    ?>
                    <tr>
                      <td><?= htmlspecialchars($th['task_id']) ?></td>
                      <td><?= strtoupper(htmlspecialchars($desc_txt)) ?></td>
                      <td style="white-space:nowrap;"><?= htmlspecialchars(date('n/j/Y', strtotime($th['schedule_date']))) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <div class="card">
              <div class="card-title">Staff List</div>
              <table class="data-table">
                <thead><tr><th>USERNAME</th><th>NAME</th><th>DATE STARTED</th></tr></thead>
                <tbody>
                  <?php foreach ($staff_rows as $sr): ?>
                    <?php
                      $stStatus = strtolower(trim((string)($sr['staff_status'] ?? 'pending')));
                      $dhRaw = trim((string)($sr['date_hired'] ?? ''));
                      $dhDisplay = ($stStatus === 'approved' && $dhRaw !== '' && strtotime($dhRaw) !== false)
                          ? date('n/j/Y', strtotime($dhRaw))
                          : 'Pending';
                    ?>
                    <tr>
                      <td><?= htmlspecialchars($sr['user_id']) ?></td>
                      <td><?= strtoupper(htmlspecialchars($sr['first_name'] . ' ' . $sr['last_name'])) ?></td>
                      <td style="white-space:nowrap;"><?= htmlspecialchars($dhDisplay) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>
</body>
</html>