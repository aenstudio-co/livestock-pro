<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Join LivestockPro</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-bg">
  <div class="auth-wrapper">
    <a class="auth-back-btn" href="login.php" title="Back" aria-label="Back">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
      </svg>
    </a>

    <div class="auth-brand">
      <h1>Join LivestockPro</h1>
      <p>Select your role to get started</p>
    </div>

    <div class="role-grid">
      <!-- OWNER: person wearing a cowboy hat -->
      <a class="role-card" href="register_owner.php">
        <div class="role-card-icon" aria-hidden="true">
          <svg width="96" height="80" viewBox="0 0 100 84" fill="currentColor">
            <path d="M31 36V18C31 12 39 14 50 16C61 14 69 12 69 18V36Z"/>
            <path d="M3 31C3 27 10 29 17 33C28 38 72 38 83 33C90 29 97 27 97 31C97 41 80 46 50 46C20 46 3 41 3 31Z"/>
            <path d="M36 46H64V52C64 60 58 65 50 65C42 65 36 60 36 52Z"/>
            <path d="M24 84V77C24 71 31 68 40 68H60C69 68 76 71 76 77V84Z"/>
          </svg>
        </div>
        <span class="role-card-btn">OWNER</span>
      </a>

      <!-- STAFF: group of three people -->
      <a class="role-card" href="register_staff.php">
        <div class="role-card-icon" aria-hidden="true">
          <svg width="96" height="80" viewBox="0 0 100 84" fill="currentColor" stroke-linejoin="round">
            <circle cx="24" cy="24" r="10.5"/>
            <path d="M5 62V56C5 47 14 41 24 41C34 41 43 47 43 56V62Z"/>
            <circle cx="76" cy="24" r="10.5"/>
            <path d="M57 62V56C57 47 66 41 76 41C86 41 95 47 95 56V62Z"/>
            <circle cx="50" cy="36" r="12" stroke="#fff" stroke-width="3.5"/>
            <path d="M26 83V72C26 61 37 55 50 55C63 55 74 61 74 72V83Z" stroke="#fff" stroke-width="3.5"/>
          </svg>
        </div>
        <span class="role-card-btn">STAFF</span>
      </a>
    </div>

    <p class="auth-helper-note">You need to register your business first to get started.</p>
  </div>
</body>
</html>