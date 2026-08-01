<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
<?php include __DIR__ . '/head_common.php'; ?>
  <title>Daftar Akun — PDAM Anomaly Detection</title>
  <style>
    body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:var(--bg)}
    .wrap{width:100%;max-width:440px;padding:20px}
    .card{background:var(--bg-card);border:1px solid var(--border);border-radius:16px;padding:36px 32px;box-shadow:var(--shadow-xl)}
    .logo{display:flex;flex-direction:column;align-items:center;gap:8px;margin-bottom:28px;text-align:center}
    .logo img{height:68px;width:68px;object-fit:cover;border-radius:16px;border:2px solid var(--border);box-shadow:var(--shadow-md)}
    .logo h1{font-size:17px;font-weight:800;color:var(--text-1);margin:0}
    .logo p{font-size:12px;color:var(--text-2);margin:0}
    .fg{margin-bottom:14px}
    .fg label{display:block;font-size:12px;font-weight:700;color:var(--text-2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px}
    .fg input{width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:9px;background:var(--bg);color:var(--text-1);font-size:14px;font-family:'DM Sans',sans-serif;outline:none;transition:border-color .2s;box-sizing:border-box}
    .fg input:focus{border-color:var(--primary)}
    .fg input.err-inp{border-color:#C62828}
    .inp-hint{font-size:11px;color:var(--text-2);margin-top:4px}
    .btn-reg{width:100%;padding:13px;background:var(--primary);color:#fff;border:none;border-radius:9px;font-size:14px;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;transition:all .2s;margin-top:4px}
    .btn-reg:hover{background:var(--brand-blue-dark);transform:translateY(-1px)}
    .btn-reg:disabled{opacity:.6;cursor:not-allowed;transform:none}
    .err-box{background:rgba(229,57,53,.08);border:1px solid rgba(229,57,53,.3);border-radius:8px;padding:10px 14px;font-size:13px;color:#C62828;margin-bottom:16px;display:flex;align-items:center;gap:8px}
    .db-warn{background:rgba(245,127,23,.08);border:1px solid rgba(245,127,23,.3);border-radius:8px;padding:10px 14px;font-size:12px;color:#E65100;margin-bottom:16px}
    .link-login{text-align:center;font-size:12px;color:var(--text-2);margin-top:20px}
    .link-login a{color:var(--primary);font-weight:700;text-decoration:none}
    .link-login a:hover{text-decoration:underline}
    .link-back{text-align:center;font-size:12px;color:var(--text-2);margin-top:12px}
    .link-back a{color:var(--text-2);text-decoration:none}
    .link-back a:hover{color:var(--primary);text-decoration:underline}
    .pw-wrap{position:relative}
    .pw-wrap input{padding-right:40px}
    .pw-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-2);padding:0;line-height:0}
    .strength-bar{height:4px;border-radius:2px;background:var(--border);margin-top:6px;overflow:hidden}
    .strength-fill{height:100%;border-radius:2px;transition:width .3s,background .3s;width:0}
    .strength-label{font-size:10px;margin-top:3px;font-weight:600}
  </style>
</head>
<body>
<?php
require_once __DIR__ . '/../../bootstrap/bootstrap.php';
if (auth_check()) { header('Location: ' . url('/dashboard1')); exit; }

$error  = '';
$db_ok  = db_available();
$fields = ['username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']  ?? '';
    $confirm  = $_POST['confirm']   ?? '';
    $fields   = ['username' => $username, 'email' => $email];

    if ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok';
    } elseif (!$db_ok) {
        $error = 'Database tidak tersedia. Setup MySQL terlebih dahulu.';
    } else {
        $result = auth_register($username, $email, $password);
        if ($result['ok']) {
            header('Location: ' . url('/login?registered=1'));
            exit;
        }
        $error = $result['message'];
    }
}
?>
<div class="wrap">
  <div class="card">

    <div class="logo">
      <img src="<?= url('/public/assets/logo.jpg') ?>" alt="Logo PDAM">
      <h1>Buat Akun Baru</h1>
      <p>PDAM Anomaly Detection System</p>
    </div>

    <?php if (!$db_ok): ?>
    <div class="db-warn">
      <strong>Database tidak tersedia.</strong> Pastikan MySQL XAMPP sudah aktif dan
      database <code>pdam_anomaly</code> sudah diimport dari <code>database.sql</code>.
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="err-box">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" id="regForm">
      <div class="fg">
        <label>Username</label>
        <input type="text" name="username" id="uname"
               value="<?= htmlspecialchars($fields['username']) ?>"
               placeholder="min. 3 karakter" autocomplete="username"
               maxlength="50" required>
        <div class="inp-hint">3–50 karakter, tanpa spasi</div>
      </div>

      <div class="fg">
        <label>Email</label>
        <input type="email" name="email"
               value="<?= htmlspecialchars($fields['email']) ?>"
               placeholder="contoh@email.com" autocomplete="email" required>
      </div>

      <div class="fg">
        <label>Password</label>
        <div class="pw-wrap">
          <input type="password" name="password" id="pwInp"
                 placeholder="min. 6 karakter" autocomplete="new-password"
                 oninput="checkStrength(this.value)" required>
          <button type="button" class="pw-toggle" onclick="togglePw('pwInp','eye1')">
            <svg id="eye1" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
        <div class="strength-bar"><div class="strength-fill" id="sBar"></div></div>
        <div class="strength-label" id="sLabel" style="color:var(--text-2)"></div>
      </div>

      <div class="fg">
        <label>Konfirmasi Password</label>
        <div class="pw-wrap">
          <input type="password" name="confirm" id="cfInp"
                 placeholder="Ulangi password" autocomplete="new-password" required>
          <button type="button" class="pw-toggle" onclick="togglePw('cfInp','eye2')">
            <svg id="eye2" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
        <div id="matchMsg" style="font-size:11px;margin-top:4px"></div>
      </div>

      <button type="submit" class="btn-reg" id="btnReg" <?= !$db_ok ? 'disabled' : '' ?>>
        Daftar Sekarang
      </button>
    </form>

    <div class="link-login">
      Sudah punya akun? <a href="<?= url('/login') ?>">Masuk di sini</a>
      &nbsp;|&nbsp;
      <a href="<?= url('/dashboard1') ?>">Lanjut sebagai Guest</a>
    </div>
    
    <div class="link-back">
      <a href="<?= url('/') ?>">&larr; Kembali ke Beranda</a>
    </div>

  </div>
</div>

<script>
function togglePw(id, iconId) {
  const inp = document.getElementById(id);
  const ico = document.getElementById(iconId);
  if (inp.type === 'password') {
    inp.type = 'text';
    ico.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
  } else {
    inp.type = 'password';
    ico.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
  }
}

function checkStrength(v) {
  const bar = document.getElementById('sBar');
  const lbl = document.getElementById('sLabel');
  let score = 0;
  if (v.length >= 6)  score++;
  if (v.length >= 10) score++;
  if (/[A-Z]/.test(v)) score++;
  if (/[0-9]/.test(v)) score++;
  if (/[^a-zA-Z0-9]/.test(v)) score++;
  const levels = [
    {w:'0%',  c:'transparent', l:''},
    {w:'25%', c:'#C62828',     l:'Lemah'},
    {w:'50%', c:'#FB8C00',     l:'Cukup'},
    {w:'75%', c:'#F9A825',     l:'Baik'},
    {w:'100%',c:'#43A047',     l:'Kuat'},
  ];
  const lvl = levels[Math.min(score, 4)];
  bar.style.width = lvl.w; bar.style.background = lvl.c;
  lbl.textContent = lvl.l; lbl.style.color = lvl.c;
}

document.getElementById('cfInp').addEventListener('input', function() {
  const msg = document.getElementById('matchMsg');
  if (this.value === document.getElementById('pwInp').value) {
    msg.textContent = 'Password cocok'; msg.style.color = '#2E7D32';
  } else {
    msg.textContent = 'Password tidak cocok'; msg.style.color = '#C62828';
  }
});

document.getElementById('regForm').addEventListener('submit', () => {
  document.getElementById('btnReg').disabled = true;
  document.getElementById('btnReg').textContent = 'Mendaftar...';
});
</script>
</body>
</html>
