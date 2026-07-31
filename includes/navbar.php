<?php
/**
 * Shared Navbar + Sidebar v2.0
 * JANGAN require/include apapun di sini.
 * bootstrap.php sudah di-load oleh halaman pemanggil.
 */

$is_logged_in = function_exists('auth_check') ? auth_check() : false;
$auth_user    = $is_logged_in ? auth_user() : [];
$current      = basename($_SERVER['PHP_SELF']);

$pages = [
  'dashboard1.php' => ['label'=>'Upload & Parameter',
    'icon'=>'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
  'dashboard2.php' => ['label'=>'Global Analysis',
    'icon'=>'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
  'dashboard3.php' => ['label'=>'Visual Analytics',
    'icon'=>'M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z'],
  'dashboard4.php' => ['label'=>'Detail Anomali',
    'icon'=>'M10 21h7a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v11m0 5l4.879-4.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242z'],
];
if ($is_logged_in) {
    $pages['history.php'] = ['label'=>'History',
        'icon'=>'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'];
}
?>
<nav class="navbar-pdam">
  <button class="btn-hamburger" id="hamburgerBtn" aria-label="Menu">
    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
  </button>
  <a href="dashboard1.php" class="navbar-brand">
    <img src="assets/logo.jpg" alt="Logo PDAM" class="navbar-logo">
    <div class="navbar-title">
      Anomaly Detection
      <small>Perumdam Tirta Kencana Samarinda</small>
    </div>
  </a>
  <div class="navbar-spacer"></div>
  <ul class="navbar-nav-items">
    <li>
      <a href="index.php" class="<?= $current === 'index.php' ? 'active' : '' ?>">
        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
        Beranda
      </a>
    </li>
    <?php foreach ($pages as $file => $info): ?>
    <li>
      <a href="<?= $file ?>" class="<?= $current === $file ? 'active' : '' ?>">
        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="<?= $info['icon'] ?>"/>
        </svg>
        <?= $info['label'] ?>
      </a>
    </li>
    <?php endforeach ?>
  </ul>

  <button class="btn-theme-toggle" id="themeToggle" title="Toggle Dark Mode">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
    </svg>
    Gelap
  </button>

  <?php if ($is_logged_in): ?>
  <div class="nb-user" id="nbUser">
    <div class="nb-badge" id="nbBadge">
      <div class="nb-avatar"><?= strtoupper(substr($auth_user['username'], 0, 1)) ?></div>
      <span class="nb-name"><?= htmlspecialchars($auth_user['username']) ?></span>
      <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <polyline points="6 9 12 15 18 9"/>
      </svg>
    </div>
    <div class="nb-drop" id="nbDrop">
      <div class="nb-drop-info">
        <div style="font-weight:700;font-size:13px;color:var(--text-1)"><?= htmlspecialchars($auth_user['username']) ?></div>
        <div style="font-size:11px;color:var(--text-2)"><?= htmlspecialchars($auth_user['email']) ?></div>
      </div>
      <div style="height:1px;background:var(--border)"></div>
      <a href="history.php" class="nb-drop-item">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Riwayat Analisis
      </a>
      <a href="logout.php" class="nb-drop-item nb-drop-danger">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
          <polyline points="16 17 21 12 16 7"/>
          <line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
        Logout
      </a>
    </div>
  </div>
  <?php else: ?>
  <a href="login.php" class="nb-login-btn">
    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/>
      <polyline points="10 17 15 12 10 7"/>
      <line x1="15" y1="12" x2="3" y2="12"/>
    </svg>
    Login
  </a>
  <?php endif ?>
</nav>

<!-- Mobile Sidebar -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="Sidebar.close()"></div>
<div class="sidebar-drawer" id="sidebarDrawer">
  <button class="sidebar-close" id="sidebarClose" onclick="Sidebar.close()">
    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
    </svg>
  </button>
  <div style="margin-bottom:8px;padding-bottom:12px;border-bottom:1px solid rgba(255,255,255,.1);display:flex;align-items:center;gap:10px">
    <img src="assets/logo.jpg" alt="Logo" style="height:28px;border-radius:6px">
    <span style="color:#fff;font-size:13px;font-weight:700;font-family:'Outfit',sans-serif">PDAM Samarinda</span>
  </div>

  <?php if ($is_logged_in): ?>
  <div style="background:rgba(255,255,255,.07);border-radius:8px;padding:10px 12px;margin-bottom:8px;display:flex;align-items:center;gap:9px">
    <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#06B6D4,#3B8EE8);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:12px;color:#fff;font-family:'Outfit',sans-serif;flex-shrink:0">
      <?= strtoupper(substr($auth_user['username'], 0, 1)) ?>
    </div>
    <div>
      <div style="color:#fff;font-size:12px;font-weight:700"><?= htmlspecialchars($auth_user['username']) ?></div>
      <div style="color:rgba(255,255,255,.45);font-size:10px">User Login</div>
    </div>
  </div>
  <?php else: ?>
  <a href="login.php" style="background:rgba(255,255,255,.1);border-radius:8px;padding:9px 12px;margin-bottom:8px;color:#fff;text-decoration:none;font-size:12px;font-weight:600;display:flex;align-items:center;gap:8px;border:1px solid rgba(255,255,255,.12)">
    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/>
      <polyline points="10 17 15 12 10 7"/>
      <line x1="15" y1="12" x2="3" y2="12"/>
    </svg>
    Login untuk fitur History
  </a>
  <?php endif ?>

  <a href="index.php" class="<?= $current === 'index.php' ? 'active' : '' ?>">
    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
    </svg>
    Beranda
  </a>
  <?php foreach ($pages as $file => $info): ?>
  <a href="<?= $file ?>" class="<?= $current === $file ? 'active' : '' ?>">
    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path d="<?= $info['icon'] ?>"/>
    </svg>
    <?= $info['label'] ?>
  </a>
  <?php endforeach ?>

  <?php if ($is_logged_in): ?>
  <div style="margin-top:auto;padding-top:12px;border-top:1px solid rgba(255,255,255,.1)">
    <a href="logout.php" style="color:rgba(255,110,110,.9);display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;text-decoration:none;padding:8px 12px;border-radius:8px;transition:background .2s"
       onmouseover="this.style.background='rgba(229,57,53,.18)'"
       onmouseout="this.style.background='transparent'">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
        <polyline points="16 17 21 12 16 7"/>
        <line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
      Logout
    </a>
  </div>
  <?php endif ?>
</div>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
