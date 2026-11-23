<?php
require_once 'includes/db_connect.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['signup_role'] = 'O';
    $_SESSION['signup_business_info'] = [
        'business_name'  => trim($_POST['business_name'] ?? ''),
        'bus_started'    => trim($_POST['bus_started'] ?? ''),
        'TIN_num'        => trim($_POST['TIN_num'] ?? ''),
        'bus_ContactNum' => trim($_POST['bus_ContactNum'] ?? '')
    ];
    header("Location: register_personal.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign up as an Owner - LivestockPro</title>
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
      <h1>Sign up as an Owner</h1>
      <p>Register your business to continue.</p>
    </div>
    <div class="auth-card">
      <h2 class="auth-card-title">Business Information</h2>
      <form method="POST" action="register_owner.php">
        <div class="form-group">
          <label class="form-label" for="business_name">BUSINESS NAME</label>
          <input class="form-control" type="text" id="business_name" name="business_name" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="bus_started">BUSINESS STARTED</label>
          <input class="form-control" type="date" id="bus_started" name="bus_started" max="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="TIN_num">TIN NUMBER</label>
          <input class="form-control" type="text" id="TIN_num" name="TIN_num" placeholder="999-999-999" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="bus_ContactNum">BUSINESS CONTACT NUMBER</label>
          <input class="form-control" type="text" id="bus_ContactNum" name="bus_ContactNum" placeholder="0999-999-99999" required>
        </div>
        <button class="btn btn-primary btn-block" type="submit">NEXT</button>
      </form>
    </div>
  </div>
</body>
</html>
