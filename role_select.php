<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Join LivestockPro</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
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
      <a class="role-card" href="register_owner.php">
        <div class="role-card-icon" aria-hidden="true">
          <svg width="46" height="46" viewBox="0 0 24 24" fill="currentColor">
            <circle cx="12" cy="7.5" r="3.5"></circle>
            <path d="M5.5 19.5c0-3.2 2.9-5.5 6.5-5.5s6.5 2.3 6.5 5.5v1H5.5v-1z"></path>
            <path d="M6.5 4.2h11l-1.2-2H7.7l-1.2 2z"></path>
          </svg>
        </div>
        <span class="role-card-btn">OWNER</span>
      </a>

      <a class="role-card" href="register_staff.php">
        <div class="role-card-icon" aria-hidden="true">
          <svg width="46" height="46" viewBox="0 0 24 24" fill="currentColor">
            <circle cx="12" cy="7" r="3"></circle>
            <path d="M6.5 18.5c0-2.6 2.5-4.5 5.5-4.5s5.5 1.9 5.5 4.5v1h-11v-1z"></path>
            <circle cx="6.2" cy="9" r="2.2"></circle>
            <path d="M2 18.5c0-2 1.8-3.5 4.2-3.8-.7.9-1.2 2.1-1.2 3.4v1.4H2v-1z"></path>
            <circle cx="17.8" cy="9" r="2.2"></circle>
            <path d="M22 18.5c0-2-1.8-3.5-4.2-3.8.7.9 1.2 2.1 1.2 3.4v1.4H22v-1z"></path>
          </svg>
        </div>
        <span class="role-card-btn">STAFF</span>
      </a>
    </div>

    <p class="auth-helper-note">You need to register your business first to get started.</p>
  </div>
</body>
</html>
