<?php
require_once 'includes/db_connect.php';
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = trim($_POST['login_id'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($login_id === '' || $password === '') {
        $error = "Please enter your email/username and password.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM `user` WHERE `username` = :u OR `email` = :e LIMIT 1");
        $stmt->execute(['u' => $login_id, 'e' => $login_id]);
        $user = $stmt->fetch();

        $ok = false;
        if ($user && (password_verify($password, $user['password']) || hash_equals((string)$user['password'], $password))) {
            $ok = true;
        }

        if ($ok) {
            $_SESSION['user_id']       = $user['user_id'];
            $_SESSION['first_name']    = $user['first_name'];
            $_SESSION['last_name']     = $user['last_name'];
            $_SESSION['business_name'] = $user['business_name'];
            $_SESSION['user_type']     = strtoupper(trim($user['user_type']));

            if ($_SESSION['user_type'] === 'O') {
                header("Location: owner_dashboard.php");
            } else {
                header("Location: staff_dashboard.php");
            }
            exit;
        } else {
            $error = "Invalid username/email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - LivestockPro</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-bg">
  <div class="auth-wrapper">
    <div class="auth-brand">
      <h1>LivestockPro</h1>
      <p>Farm Management Tracker</p>
    </div>

    <div class="auth-card">
      <h2 class="auth-card-title">Welcome Back!</h2>

      <?php if ($error !== ""): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="login.php">
        <div class="form-group">
          <label class="form-label" for="login_id">EMAIL OR USERNAME</label>
          <input class="form-control" type="text" id="login_id" name="login_id" placeholder="Enter email or username" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">PASSWORD</label>
          <div class="password-wrap">
            <input class="form-control" type="password" id="password" name="password" placeholder="Enter password" required>
            <button type="button" id="toggleBtn" class="toggle" onclick="togglePassword()" aria-label="Show password">&#128065;<span class="slash"></span></button>
          </div>
        </div>

        <button class="btn btn-primary btn-block" type="submit">LOGIN</button>
      </form>

      <p class="auth-footer-text">
        Don't have an account? <a href="role_select.php">Sign up here.</a>
      </p>
    </div>
  </div>

  <script>
    function togglePassword() {
      const input = document.getElementById("password");
      const btn = document.getElementById("toggleBtn");
      const isHidden = input.type === "password";

      if (isHidden) {
        input.type = "text";
        btn.classList.add("is-visible");
        btn.setAttribute("aria-label", "Hide password");
      } else {
        input.type = "password";
        btn.classList.remove("is-visible");
        btn.setAttribute("aria-label", "Show password");
      }
    }
  </script>
</body>
</html>
