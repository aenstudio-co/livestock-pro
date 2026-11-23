<?php
require_once 'includes/db_connect.php';
$role = $_SESSION['signup_role'] ?? '';
if ($role !== 'O' && $role !== 'S') { header("Location: role_select.php"); exit; }

$business_name = ($role === 'O')
    ? ($_SESSION['signup_business_info']['business_name'] ?? '')
    : ($_SESSION['signup_business'] ?? '');

$today = date('Y-m-d');
$maxBirthdate = date('Y-m-d', strtotime('-1 day'));
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $fn = trim($_POST['first_name'] ?? '');
        $ln = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $rawPassword = trim($_POST['password'] ?? '');
        $bday = trim($_POST['birthdate'] ?? '');
        $sex = trim($_POST['sex'] ?? 'Male');
        $cnum = trim($_POST['contact_number'] ?? '');

        if ($bday === '' || $bday >= $today) {
            throw new Exception("Birthdate cannot be today or a future date.");
        }

        $pdo->beginTransaction();
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        $uCount = (int)$pdo->query("SELECT COUNT(*) FROM `user`")->fetchColumn() + 1;
        $aCount = (int)$pdo->query("SELECT COUNT(*) FROM `address`")->fetchColumn() + 1;
        $new_user_id = sprintf("USE-%05d", $uCount);
        $new_addr_id = sprintf("ADD-%05d", $aCount);

        $cleanFirst = preg_replace('/[^a-zA-Z]/', '', $fn);
        $baseUname = ($cleanFirst !== '' ? ucfirst(strtolower($cleanFirst)) : 'User');
        $username = sprintf("%s%02d", $baseUname, $uCount);

        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM `user` WHERE `username` = :uname");
        $suffix = $uCount + 1;
        while (true) {
            $checkStmt->execute(['uname' => $username]);
            if ((int)$checkStmt->fetchColumn() === 0) {
                break;
            }
            $username = sprintf("%s%02d", $baseUname, $suffix);
            $suffix++;
        }

        $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);

        $pdo->prepare("
            INSERT INTO `user` (`user_id`, `business_name`, `first_name`, `last_name`, `email`, `username`, `password`, `birthdate`, `sex`, `contact_number`, `address_id`, `user_type`)
            VALUES (:uid, :bname, :fn, :ln, :email, :uname, :pw, :bday, :sex, :cnum, :aid, :utype)
        ")->execute([
            'uid'   => $new_user_id,
            'bname' => $business_name,
            'fn'    => $fn,
            'ln'    => $ln,
            'email' => $email,
            'uname' => $username,
            'pw'    => $hashedPassword,
            'bday'  => $bday,
            'sex'   => $sex,
            'cnum'  => $cnum,
            'aid'   => $new_addr_id,
            'utype' => $role
        ]);

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

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        $pdo->commit();
        unset($_SESSION['signup_role'], $_SESSION['signup_business'], $_SESSION['signup_business_info']);
        header("Location: signup_done.php?mode=" . ($role === 'S' ? 'staff' : 'owner'));
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            $pdo->rollBack();
        }
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
    <a class="auth-back-btn" href="login.php" title="Back" aria-label="Back">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
      </svg>
    </a>

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
            <div class="form-group"><label class="form-label">PASSWORD</label><input class="form-control" type="password" name="password" required></div>
            <div class="form-group"><label class="form-label">FIRST NAME</label><input class="form-control" type="text" name="first_name" required></div>
            <div class="form-group"><label class="form-label">LAST NAME</label><input class="form-control" type="text" name="last_name" required></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
              <div class="form-group">
                <label class="form-label">BIRTHDATE</label>
                <input class="form-control" type="date" name="birthdate" max="<?= htmlspecialchars($maxBirthdate) ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">SEX</label>
                <div style="display:flex;gap:14px;align-items:center;height:38px;">
                  <label><input type="radio" name="sex" value="Male" checked> Male</label>
                  <label><input type="radio" name="sex" value="Female"> Female</label>
                </div>
              </div>
            </div>
            <div class="form-group"><label class="form-label">CONTACT NUMBER</label><input class="form-control" type="text" name="contact_number" placeholder="0999-999-99999" required></div>
          </div>
          <div>
            <div class="card-title">Address</div>

            <div class="form-group">
              <label class="form-label" for="provinceInput">PROVINCE</label>
              <div class="searchable-select" id="provinceDropdown">
                <input class="form-control searchable-select-input" type="text" id="provinceInput" placeholder="Select province" autocomplete="off" required>
                <input type="hidden" id="provinceValue" name="Province">
                <div class="searchable-select-menu" id="provinceMenu"></div>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="cityInput">MUNICIPALITY / CITY</label>
              <div class="searchable-select" id="cityDropdown">
                <input class="form-control searchable-select-input" type="text" id="cityInput" placeholder="Select municipality / city" autocomplete="off" required disabled>
                <input type="hidden" id="cityValue" name="Municipality_City">
                <div class="searchable-select-menu" id="cityMenu"></div>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="barangayInput">BARANGAY</label>
              <div class="searchable-select" id="barangayDropdown">
                <input class="form-control searchable-select-input" type="text" id="barangayInput" placeholder="Select barangay" autocomplete="off" required disabled>
                <input type="hidden" id="barangayValue" name="Barangay">
                <div class="searchable-select-menu" id="barangayMenu"></div>
              </div>
            </div>

            <div class="form-group"><label class="form-label">ZIP CODE</label><input class="form-control" type="text" name="ZipCode" maxlength="4" placeholder="9999" required></div>
            <div class="form-group"><label class="form-label">STREET</label><input class="form-control" type="text" name="Street" required></div>
            <div class="form-group"><label class="form-label">HOUSE NUMBER</label><input class="form-control" type="text" name="HouseNumber"></div>
          </div>
        </div>
        <button class="btn btn-primary btn-block" type="submit">FINISH</button>
      </form>
    </div>
  </div>

  <script>
    const PSGC_API = 'https://psgc.cloud/api';

    function extractList(payload) {
      if (Array.isArray(payload)) return payload;
      if (payload && Array.isArray(payload.data)) return payload.data;
      return [];
    }

    async function fetchFirstValid(urls) {
      for (const url of urls) {
        try {
          const res = await fetch(url);
          if (!res.ok) continue;
          const json = await res.json();
          const list = extractList(json);
          if (list.length > 0) return list;
        } catch (_) {}
      }
      return [];
    }

    const allDropdowns = [];

    function createSearchableDropdown({ wrapperId, inputId, hiddenId, menuId, onSelect }) {
      const wrapper = document.getElementById(wrapperId);
      const input   = document.getElementById(inputId);
      const hidden  = document.getElementById(hiddenId);
      const menu    = document.getElementById(menuId);
      let items     = [];

      function renderOptions(filterText = '') {
        const q = filterText.trim().toLowerCase();
        const filtered = q
          ? items.filter(item => item.name.toLowerCase().includes(q))
          : items;

        menu.innerHTML = '';
        if (filtered.length === 0) {
          const empty = document.createElement('div');
          empty.className = 'searchable-select-empty';
          empty.textContent = 'No results';
          menu.appendChild(empty);
          return;
        }

        for (const item of filtered) {
          const div = document.createElement('div');
          div.className = 'searchable-select-item';
          div.textContent = item.name;
          div.addEventListener('mousedown', (e) => {
            e.preventDefault();
            input.value = item.name;
            hidden.value = item.name;
            wrapper.classList.remove('open');
            if (onSelect) onSelect(item);
          });
          menu.appendChild(div);
        }
      }

      input.addEventListener('focus', () => {
        if (input.disabled) return;
        allDropdowns.forEach(d => d.close());
        const showAll = hidden.value && input.value === hidden.value;
        renderOptions(showAll ? '' : input.value);
        wrapper.classList.add('open');
      });

      input.addEventListener('input', () => {
        if (input.disabled) return;
        hidden.value = '';
        renderOptions(input.value);
        wrapper.classList.add('open');
      });

      input.addEventListener('blur', () => {
        wrapper.classList.remove('open');
        const exact = items.find(i => i.name.toLowerCase() === input.value.trim().toLowerCase());
        if (exact) {
          input.value = exact.name;
          hidden.value = exact.name;
        } else if (!hidden.value) {
          input.value = '';
        }
      });

      const api = {
        close() {
          wrapper.classList.remove('open');
        },
        setItems(newItems, disabled = false) {
          const seen = new Set();
          items = [];
          for (const item of newItems) {
            if (!item || !item.name || seen.has(item.name)) continue;
            seen.add(item.name);
            items.push(item);
          }
          input.value = '';
          hidden.value = '';
          input.disabled = disabled || items.length === 0;
          wrapper.classList.remove('open');
          renderOptions('');
        },
        reset() {
          items = [];
          input.value = '';
          hidden.value = '';
          input.disabled = true;
          wrapper.classList.remove('open');
          menu.innerHTML = '';
        }
      };

      allDropdowns.push(api);
      return api;
    }

    const barangayDropdown = createSearchableDropdown({
      wrapperId: 'barangayDropdown',
      inputId: 'barangayInput',
      hiddenId: 'barangayValue',
      menuId: 'barangayMenu'
    });

    const cityDropdown = createSearchableDropdown({
      wrapperId: 'cityDropdown',
      inputId: 'cityInput',
      hiddenId: 'cityValue',
      menuId: 'cityMenu',
      onSelect: async (city) => {
        barangayDropdown.reset();
        if (!city.code) return;

        const brgys = await fetchFirstValid([
          `${PSGC_API}/cities-municipalities/${city.code}/barangays`,
          `${PSGC_API}/barangays?city_municipality_code=${encodeURIComponent(city.code)}`
        ]);
        brgys.sort((a, b) => a.name.localeCompare(b.name));
        barangayDropdown.setItems(brgys, false);
      }
    });

    const provinceDropdown = createSearchableDropdown({
      wrapperId: 'provinceDropdown',
      inputId: 'provinceInput',
      hiddenId: 'provinceValue',
      menuId: 'provinceMenu',
      onSelect: async (province) => {
        cityDropdown.reset();
        barangayDropdown.reset();
        if (!province.code) return;

        let combined = [];
        if (province.sourceType === 'ncr') {
          combined = await fetchFirstValid([
            `${PSGC_API}/regions/${province.code}/cities-municipalities`,
            `${PSGC_API}/cities-municipalities?region_code=${encodeURIComponent(province.code)}`
          ]);
        } else {
          const nested = await fetchFirstValid([
            `${PSGC_API}/provinces/${province.code}/cities-municipalities`
          ]);

          if (nested.length > 0) {
            combined = nested;
          } else {
            const [cities, munis] = await Promise.all([
              fetchFirstValid([`${PSGC_API}/provinces/${province.code}/cities`]),
              fetchFirstValid([`${PSGC_API}/provinces/${province.code}/municipalities`])
            ]);
            combined = [...cities, ...munis];
          }

          if (combined.length === 0) {
            combined = await fetchFirstValid([
              `${PSGC_API}/cities-municipalities?province_code=${encodeURIComponent(province.code)}`
            ]);
          }
        }

        combined.sort((a, b) => a.name.localeCompare(b.name));
        cityDropdown.setItems(combined, false);
      }
    });

    async function loadProvinces() {
      provinceDropdown.reset();
      const provinces = await fetchFirstValid([
        `${PSGC_API}/provinces`,
        `${PSGC_API}/v1/provinces`
      ]);

      const list = provinces
        .map(p => ({ ...p, sourceType: 'province' }))
        .concat([{ code: '130000000', name: 'Metro Manila', sourceType: 'ncr' }])
        .sort((a, b) => a.name.localeCompare(b.name));

      provinceDropdown.setItems(list, false);
    }

    loadProvinces();
  </script>
</body>
</html>
