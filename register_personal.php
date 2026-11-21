<?php
require_once 'includes/db_connect.php';
$role = $_SESSION['signup_role'] ?? '';
if ($role !== 'O' && $role !== 'S') { header("Location: role_select.php"); exit; }

$business_name = ($role === 'O')
    ? ($_SESSION['signup_business_info']['business_name'] ?? '')
    : ($_SESSION['signup_business'] ?? '');

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        $uCount = (int)$pdo->query("SELECT COUNT(*) FROM `user`")->fetchColumn() + 1;
        $aCount = (int)$pdo->query("SELECT COUNT(*) FROM `address`")->fetchColumn() + 1;
        $new_user_id = sprintf("USE-%05d", $uCount);
        $new_addr_id = sprintf("ADD-%05d", $aCount);

        $pdo->prepare("
            INSERT INTO `address` (`address_id`, `user_id`, `HouseNumber`, `Street`, `Barangay`, `Municipality_City`, `Province`, `ZipCode`)
            VALUES (:aid, :uid, :hn, :st, :brgy, :muni, :prov, :zip)
        ")->execute([
            'aid'  => $new_addr_id,
            'uid'  => $new_user_id,
            'hn'   => trim($_POST['HouseNumber'] ?? '') ?: null,
            'st'   => trim($_POST['Street'] ?? ''),
            'brgy' => trim($_POST['Barangay'] ?? ''),
            'muni' => trim($_POST['Municipality_City'] ?? ''),
            'prov' => trim($_POST['Province'] ?? ''),
            'zip'  => trim($_POST['ZipCode'] ?? '')
        ]);

        $pdo->prepare("
            INSERT INTO `user` (`user_id`, `business_name`, `first_name`, `last_name`, `email`, `username`, `password`, `birthdate`, `sex`, `contact_number`, `address_id`, `user_type`)
            VALUES (:uid, :bname, :fn, :ln, :email, :uname, :pw, :bday, :sex, :cnum, :aid, :utype)
        ")->execute([
            'uid'   => $new_user_id,
            'bname' => $business_name,
            'fn'    => trim($_POST['first_name'] ?? ''),
            'ln'    => trim($_POST['last_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'uname' => trim($_POST['username'] ?? ''),
            'pw'    => password_hash(trim($_POST['password'] ?? ''), PASSWORD_DEFAULT),
            'bday'  => trim($_POST['birthdate'] ?? ''),
            'sex'   => trim($_POST['sex'] ?? 'Male'),
            'cnum'  => trim($_POST['contact_number'] ?? ''),
            'aid'   => $new_addr_id,
            'utype' => $role
        ]);

        if ($role === 'O') {
            $binfo = $_SESSION['signup_business_info'];
            $pdo->prepare("
                INSERT INTO `owner` (`user_id`, `address_id`, `bus_started`, `TIN_num`, `bus_ContactNum`)
                VALUES (:uid, :aid, :bstart, :tin, :bnum)
            ")->execute([
                'uid'    => $new_user_id,
                'aid'    => $new_addr_id,
                'bstart' => $binfo['bus_started'],
                'tin'    => $binfo['TIN_num'],
                'bnum'   => $binfo['bus_ContactNum']
            ]);
        } else {
            $pdo->prepare("INSERT INTO `staff` (`user_id`, `date_hired`) VALUES (:uid, :dhired)")
                ->execute(['uid' => $new_user_id, 'dhired' => date('Y-m-d')]);
        }

        $pdo->commit();
        unset($_SESSION['signup_role'], $_SESSION['signup_business'], $_SESSION['signup_business_info']);
        header("Location: signup_done.php?mode=" . ($role === 'S' ? 'staff' : 'owner'));
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Registration error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Personal Information - LivestockPro</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <div class="auth-wrapper">
    <a class="auth-back-btn" href="role_select.php" title="Back">&larr;</a>
    <div class="auth-brand">
      <h1>Personal Information</h1>
      <p><?= htmlspecialchars($business_name) ?></p>
    </div>
    <div class="auth-card" style="max-width:650px;">
      <?php if ($error !== ""): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="POST" action="register_personal.php">
        <div class="form-grid-2">
          <div>
            <div class="card-title">Personal</div>
            <div class="form-group"><label class="form-label">EMAIL</label><input class="form-control" type="email" name="email" required></div>
            <div class="form-group"><label class="form-label">USERNAME</label><input class="form-control" type="text" name="username" required></div>
            <div class="form-group"><label class="form-label">PASSWORD</label><input class="form-control" type="password" name="password" required></div>
            <div class="form-group"><label class="form-label">FIRST NAME</label><input class="form-control" type="text" name="first_name" required></div>
            <div class="form-group"><label class="form-label">LAST NAME</label><input class="form-control" type="text" name="last_name" required></div>
            <div class="form-group"><label class="form-label">BIRTHDATE</label><input class="form-control" type="date" name="birthdate" required></div>
            <div class="form-group">
              <label class="form-label">SEX</label>
              <div style="display:flex;gap:14px;align-items:center;height:38px;">
                <label><input type="radio" name="sex" value="Male" checked> Male</label>
                <label><input type="radio" name="sex" value="Female"> Female</label>
              </div>
            </div>
            <div class="form-group"><label class="form-label">CONTACT NUMBER</label><input class="form-control" type="text" name="contact_number" required></div>
          </div>
          <div>
            <div class="card-title">Address</div>
            <div class="form-group"><label class="form-label">PROVINCE</label><input class="form-control" type="text" name="Province" required></div>
            <div class="form-group"><label class="form-label">MUNICIPALITY / CITY</label><input class="form-control" type="text" name="Municipality_City" required></div>
            <div class="form-group"><label class="form-label">BARANGAY</label><input class="form-control" type="text" name="Barangay" required></div>
            <div class="form-group"><label class="form-label">ZIP CODE</label><input class="form-control" type="text" name="ZipCode" maxlength="4" required></div>
            <div class="form-group"><label class="form-label">STREET</label><input class="form-control" type="text" name="Street" required></div>
            <div class="form-group"><label class="form-label">HOUSE NUMBER</label><input class="form-control" type="text" name="HouseNumber"></div>
          </div>
        </div>
        <button class="btn btn-primary btn-block" type="submit">I'M READY! FINISH</button>
      </form>
    </div>
  </div>
</body>
</html>
