<?php
require_once 'includes/db_connect.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$full_name = trim(($_SESSION['first_name'] ?? 'Juan') . ' ' . ($_SESSION['last_name'] ?? 'Dela Cruz'));
$user_id   = $_SESSION['user_id'];
$role      = strtoupper($_SESSION['user_type'] ?? 'O');
$business  = $_SESSION['business_name'] ?? 'Business Name';
$message   = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $fn = trim($_POST['first_name'] ?? '');
    $ln = trim($_POST['last_name'] ?? '');
    $cn = trim($_POST['contact_number'] ?? '');
    $em = trim($_POST['email'] ?? '');

    $pdo->prepare("UPDATE `user` SET `first_name` = :fn, `last_name` = :ln, `contact_number` = :cn, `email` = :em WHERE `user_id` = :uid")
        ->execute(['fn' => $fn, 'ln' => $ln, 'cn' => $cn, 'em' => $em, 'uid' => $user_id]);

    $_SESSION['first_name'] = $fn;
    $_SESSION['last_name']  = $ln;
    $full_name = trim($fn . ' ' . $ln);
    $message = "Profile changes saved.";
}

$stmt_u = $pdo->prepare("SELECT * FROM `user` WHERE `user_id` = :uid LIMIT 1");
$stmt_u->execute(['uid' => $user_id]);
$u_row = $stmt_u->fetch() ?: [];

$staff_rows = $pdo->query("
    SELECT s.user_id, u.first_name, u.last_name, s.date_hired
    FROM `staff` s
    JOIN `user` u ON s.user_id = u.user_id
    ORDER BY s.user_id ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile - LivestockPro</title>
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
        <li><a href="sales.php">Sales</a></li>
        <li><a class="active" href="profile.php">My Profile</a></li>
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
        <h1 class="page-title">My Profile</h1>
        <div class="page-datetime"><?= date('F d, Y | H:i:s') ?></div>
      </div>

      <?php if ($message !== ""): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <div class="profile-hero">
        <div style="display:flex;align-items:center;gap:14px;">
          <div class="sidebar-avatar" style="width:52px;height:52px;font-size:18px;"><?= strtoupper(substr($full_name, 0, 2)) ?></div>
          <div>
            <div style="font-size:18px;font-weight:700;"><?= htmlspecialchars($full_name) ?></div>
            <div style="font-size:12px;color:var(--text-secondary);"><?= $role === 'O' ? 'Owner of ' : 'Staff of ' ?><?= htmlspecialchars($business) ?></div>
            <div style="font-size:10px;color:var(--text-muted);">Member since <?= htmlspecialchars($u_row['birthdate'] ?? '2021-01-01') ?></div>
          </div>
        </div>
        <div style="display:flex;gap:32px;align-items:center;">
          <div>
            <div style="font-size:22px;font-weight:700;">&#8369;60.k</div>
            <div style="font-size:10px;color:var(--text-secondary);">TOTAL INCOME</div>
          </div>
          <div>
            <div style="font-size:22px;font-weight:700;">4</div>
            <div style="font-size:10px;color:var(--text-secondary);">PENDING TASKS</div>
          </div>
        </div>
      </div>

      <div class="split-grid">
        <div class="card">
          <div class="card-title">Edit Personal Information</div>
          <form method="POST" action="profile.php">
            <div class="form-grid-2">
              <div class="form-group"><label class="form-label">FARM NAME</label><input class="form-control" type="text" value="<?= htmlspecialchars($business) ?>" readonly></div>
              <div class="form-group"><label class="form-label">CONTACT NUMBER</label><input class="form-control" type="text" name="contact_number" value="<?= htmlspecialchars($u_row['contact_number'] ?? '') ?>"></div>
              <div class="form-group"><label class="form-label">FIRST NAME</label><input class="form-control" type="text" name="first_name" value="<?= htmlspecialchars($u_row['first_name'] ?? '') ?>"></div>
              <div class="form-group"><label class="form-label">LAST NAME</label><input class="form-control" type="text" name="last_name" value="<?= htmlspecialchars($u_row['last_name'] ?? '') ?>"></div>
            </div>
            <div class="form-group"><label class="form-label">ABOUT / BIO</label><textarea class="form-control" placeholder="Tell something about yourself..."></textarea></div>
            <div class="form-group"><label class="form-label">EMAIL</label><input class="form-control" type="email" name="email" value="<?= htmlspecialchars($u_row['email'] ?? '') ?>"></div>
            <button class="btn btn-primary" type="submit" name="save_profile">SAVE CHANGES</button>
          </form>
        </div>

        <div class="card">
          <div class="card-title">Staff List</div>
          <table class="data-table">
            <thead><tr><th>USERNAME</th><th>NAME</th><th>DATE STARTED</th></tr></thead>
            <tbody>
              <?php foreach ($staff_rows as $sr): ?>
                <tr>
                  <td><?= htmlspecialchars($sr['user_id']) ?></td>
                  <td><?= strtoupper(htmlspecialchars($sr['first_name'] . ' ' . $sr['last_name'])) ?></td>
                  <td><?= htmlspecialchars(date('n/j/Y', strtotime($sr['date_hired']))) ?></td>
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
