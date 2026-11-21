<?php
$mode = $_GET['mode'] ?? 'owner';
$is_staff = ($mode === 'staff');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>You're Already Signed Up! - LivestockPro</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <div class="auth-wrapper">
    <a class="auth-back-btn" href="login.php" title="Back">&larr;</a>
    <div class="auth-card" style="text-align:center;">
      <div style="width:44px;height:44px;border-radius:50%;background:#0a5656;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:14px;">&#10003;</div>
      <h2 class="auth-card-title" style="margin-bottom:10px;">You're Already Signed Up!</h2>
      <?php if ($is_staff): ?>
        <p style="font-size:12px;color:var(--text-secondary);margin-bottom:22px;">
          Your account will be verified by your farm's owner. Log in once confirmation has been sent.
        </p>
      <?php else: ?>
        <p style="font-size:12px;color:var(--text-secondary);margin-bottom:22px;">
          Your farm owner account is ready. Log in to set up your farm and manage your livestock.
        </p>
      <?php endif; ?>
      <a class="btn btn-primary btn-block" href="login.php">BACK TO LOG IN</a>
    </div>
  </div>
</body>
</html>
