<?php require_once __DIR__ . '/bootstrap.php';
// Process login and redirects BEFORE any output
if (auth_check()) { header('Location: dashboard1.php'); exit; }

$error = ''; $success = ''; $db_ok = db_available();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($login) || empty($password)) {
        $error = 'Email/username dan password wajib diisi';
    } else {
        $result = auth_login($login, $password);
        if ($result['ok']) {
            $redirect = $_GET['redirect'] ?? 'dashboard1.php';
            $allowed  = ['index.php','dashboard1.php','dashboard2.php','dashboard3.php','dashboard4.php','history.php'];
            if (!in_array($redirect, $allowed)) $redirect = 'dashboard1.php';
            header('Location: ' . $redirect); exit;
        } else {
            $error = $result['message'];
        }
    }
}
if (isset($_GET['registered'])) $success = 'Registrasi berhasil! Silakan login.';
if (isset($_GET['logged_out'])) $success = 'Anda telah logout. Sampai jumpa!';
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
<?php include __DIR__ . '/includes/head_common.php'; ?>
  <title>Login — PDAM Anomaly Detection</title>
  <style>
    body {
      display: flex; align-items: center; justify-content: center;
      min-height: 100vh; padding: 20px;
      background: var(--bg);
    }
    .login-bg {
      position: fixed; inset: 0; z-index: 0;
      background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 50%, #EDE9FE 100%);
    }
    [data-theme="dark"] .login-bg {
      background: linear-gradient(135deg, #060C1A 0%, #080E1C 50%, #0C0A1E 100%);
    }
    .login-wrap { width: 100%; max-width: 420px; z-index: 1; position: relative; }
    .login-card {
      background: var(--bg-card); border: 1px solid var(--border);
      border-radius: 20px; padding: 36px 32px; box-shadow: var(--shadow-xl);
    }
    .login-logo { display: flex; flex-direction: column; align-items: center; gap: 10px; margin-bottom: 28px; text-align: center; }
    .login-logo-img {
      width: 68px; height: 68px; border-radius: 16px; object-fit: cover;
      border: 2px solid var(--border); box-shadow: var(--shadow-md);
    }
    .login-logo h1 { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 800; color: var(--text-1); margin: 0; letter-spacing: -0.3px; }
    .login-logo p  { font-size: 12px; color: var(--text-2); margin: 0; }

    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 11px; font-weight: 700; color: var(--text-2); text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 6px; }
    .form-group input {
      width: 100%; padding: 11px 14px; border: 1.5px solid var(--border);
      border-radius: var(--radius-sm); background: var(--bg); color: var(--text-1);
      font-size: 14px; font-family: 'DM Sans', sans-serif; outline: none;
      transition: border-color .2s, box-shadow .2s; box-sizing: border-box;
    }
    .form-group input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99,102,241,0.14); }

    .pw-wrap { position: relative; }
    .pw-wrap input { padding-right: 42px; }
    .pw-toggle { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:var(--text-3); padding:0; line-height:0; transition:color .2s; }
    .pw-toggle:hover { color: var(--text-2); }

    .btn-login {
      width: 100%; padding: 13px; background: var(--primary); color: #fff; border: none;
      border-radius: var(--radius-sm); font-size: 14px; font-weight: 700; cursor: pointer;
      font-family: 'DM Sans', sans-serif; transition: all .2s; margin-top: 4px;
      box-shadow: 0 4px 14px rgba(99,102,241,0.35);
    }
    .btn-login:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: 0 6px 18px rgba(99,102,241,0.4); }
    .btn-login:disabled { opacity:.6; cursor:not-allowed; transform:none; box-shadow:none; }

    .divider { text-align:center; font-size:12px; color:var(--text-3); margin:20px 0; position:relative; }
    .divider::before { content:''; position:absolute; top:50%; left:0; right:0; height:1px; background:var(--border); z-index:0; }
    .divider span { background:var(--bg-card); padding:0 12px; position:relative; z-index:1; }

    .btn-guest {
      width: 100%; padding: 11px; background: var(--bg); color: var(--text-1);
      border: 1.5px solid var(--border); border-radius: var(--radius-sm);
      font-size: 13px; font-weight: 600; cursor: pointer; font-family: 'DM Sans', sans-serif;
      transition: all .2s; display: flex; align-items: center; justify-content: center; gap: 7px;
    }
    .btn-guest:hover { border-color: var(--primary); color: var(--primary); background: rgba(99,102,241,0.05); }

    .link-register { text-align:center; font-size:12px; color:var(--text-2); margin-top:20px; }
    .link-register a { color: var(--primary); font-weight: 700; text-decoration: none; }
    .link-register a:hover { text-decoration: underline; }

    .msg-box { border-radius: var(--radius-sm); padding: 10px 14px; font-size: 13px; margin-bottom: 16px; display: flex; align-items: flex-start; gap: 8px; border: 1px solid transparent; }
    .msg-err { background: rgba(220,38,38,0.08); border-color: rgba(220,38,38,0.25); color: #B91C1C; }
    .msg-ok  { background: rgba(5,150,105,0.08);  border-color: rgba(5,150,105,0.25);  color: #065F46; }
    .db-warn { background: rgba(217,119,6,0.08);  border-color: rgba(217,119,6,0.2);   border-radius: var(--radius-sm); padding: 10px 14px; font-size: 12px; color: #92400E; margin-bottom: 16px; }
    [data-theme="dark"] .msg-err { color: #FCA5A5; }
    [data-theme="dark"] .msg-ok  { color: #6EE7B7; }
    [data-theme="dark"] .db-warn { color: #FCD34D; }
  </style>
</head>
<body>
<div class="login-bg"></div>
<div class="login-wrap">
  <div class="login-card">

    <div class="login-logo">
      <img src="assets/logo.jpg" alt="Logo PDAM" class="login-logo-img">
      <h1>PDAM Anomaly Detection</h1>
      <p>Perumdam Tirta Kencana Kota Samarinda</p>
    </div>

    <?php if (!$db_ok): ?>
    <div class="db-warn">
      <strong>⚠ Database tidak tersedia.</strong> MySQL belum dikonfigurasi atau belum berjalan.
      Anda tetap bisa menggunakan sistem sebagai Guest.
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="msg-box msg-err">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="msg-box msg-ok">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px"><polyline points="20 6 9 17 4 12"/></svg>
      <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <form method="POST" id="loginForm">
      <div class="form-group">
        <label>Email atau Username</label>
        <input type="text" name="login" id="loginInput"
               value="<?= htmlspecialchars($_POST['login'] ?? '') ?>"
               placeholder="contoh@email.com atau username"
               autocomplete="username" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <div class="pw-wrap">
          <input type="password" name="password" id="pwInput"
                 placeholder="Masukkan password" autocomplete="current-password" required>
          <button type="button" class="pw-toggle" onclick="togglePw()" title="Tampilkan / sembunyikan">
            <svg id="eyeIcon" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
      </div>
      <button type="submit" class="btn-login" id="btnLogin">Masuk ke Sistem</button>
    </form>

    <div class="divider"><span>atau lanjutkan tanpa akun</span></div>

    <button class="btn-guest" onclick="location.href='dashboard1.php'">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Lanjutkan sebagai Guest
    </button>

    <div class="link-register">
      Belum punya akun? <a href="register.php">Daftar sekarang</a>
    </div>
  </div>
</div>

<script>
function togglePw() {
  const inp = document.getElementById('pwInput');
  const ico = document.getElementById('eyeIcon');
  if (inp.type === 'password') {
    inp.type = 'text';
    ico.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
  } else {
    inp.type = 'password';
    ico.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
  }
}

document.getElementById('loginForm').addEventListener('submit', () => {
  const btn = document.getElementById('btnLogin');
  btn.disabled = true;
  btn.textContent = 'Memproses...';
});
</script>
</body>
</html>
