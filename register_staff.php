<?php
require_once 'includes/db_connect.php';
$businesses = $pdo->query("
    SELECT DISTINCT TRIM(u.business_name) AS business_name
    FROM `owner` o
    JOIN `user` u ON o.user_id = u.user_id
    WHERE u.business_name IS NOT NULL
      AND TRIM(u.business_name) != ''
      AND LOWER(TRIM(u.business_name)) != 'juan\'s farm'
    ORDER BY business_name ASC
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['signup_role'] = 'S';
    $_SESSION['signup_business'] = trim($_POST['business_name'] ?? '');
    header("Location: register_personal.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign up as a Staff - LivestockPro</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <div class="auth-wrapper">
    <a class="auth-back-btn" href="role_select.php" title="Back" aria-label="Back">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
      </svg>
    </a>

    <div class="auth-brand">
      <h1>Sign up as a Staff</h1>
      <p>Select a registered business to continue.</p>
    </div>
    <div class="auth-card">
      <form method="POST" action="register_staff.php">
        <div class="form-group">
          <input class="form-control" type="text" id="searchFarm" placeholder="Search Farm..." onkeyup="filterFarms()">
        </div>
        <div class="form-label">RECOMMENDED</div>
        <div class="business-list" id="farmList">
          <?php if (empty($businesses)): ?>
            <div style="font-size:12px;color:var(--text-muted);padding:8px 0;">No registered businesses found in the database.</div>
          <?php else: ?>
            <?php foreach ($businesses as $b): ?>
              <label class="business-option farm-item">
                <input type="radio" name="business_name" value="<?= htmlspecialchars($b['business_name']) ?>" required>
                <span><?= htmlspecialchars($b['business_name']) ?></span>
              </label>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <button class="btn btn-primary btn-block" type="submit">CONTINUE</button>
      </form>
    </div>
  </div>
  <script>
    function filterFarms() {
      const q = document.getElementById('searchFarm').value.toLowerCase();
      document.querySelectorAll('#farmList .farm-item').forEach(el => {
        el.style.display = el.textContent.toLowerCase().includes(q) ? 'flex' : 'none';
      });
    }
  </script>
</body>
</html>
