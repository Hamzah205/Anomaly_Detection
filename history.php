<?php require_once __DIR__ . '/bootstrap.php';
// No closing PHP tag — prevents "headers already sent"
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
<script>document.documentElement.setAttribute("data-theme",localStorage.getItem("pdam_theme")||"light");</script>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Riwayat Analisis — PDAM Anomaly Detection</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    body{font-family:'DM Sans',sans-serif}
    .sum4{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
    @media(max-width:900px){.sum4{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:480px){.sum4{grid-template-columns:1fr}}
    .mode-badge{display:inline-block;background:rgba(21,101,192,.1);color:var(--primary);border:1px solid rgba(21,101,192,.2);border-radius:var(--radius-sm);font-size:10px;font-weight:700;padding:2px 8px;white-space:nowrap}
    .cont-badge{display:inline-block;background:rgba(0,121,107,.1);color:var(--secondary);border:1px solid rgba(0,121,107,.2);border-radius:var(--radius-sm);font-size:10px;font-weight:700;padding:2px 8px}
    .pct-high{color:var(--anomaly-color);font-weight:700}
    .pct-mid{color:var(--warning);font-weight:700}
    .pct-low{color:var(--success);font-weight:700}
    .empty-state{text-align:center;padding:60px 20px;color:var(--text-2)}
    .empty-state svg{margin-bottom:16px;opacity:.35}
    .empty-state h3{font-size:16px;font-weight:700;margin-bottom:6px}
    .empty-state p{font-size:13px}
    .chart-row{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
    @media(max-width:700px){.chart-row{grid-template-columns:1fr}}
    .timeline-dot{width:10px;height:10px;border-radius:50%;background:var(--primary);flex-shrink:0;margin-top:4px}
    .timeline-item{display:flex;gap:12px;padding:12px 0;border-bottom:1px solid var(--border)}
    .timeline-item:last-child{border-bottom:none}
    .replay-btn{background:rgba(21,101,192,.08);color:var(--primary);border:1px solid rgba(21,101,192,.2);border-radius:var(--radius-sm);padding:4px 12px;font-size:11px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .2s;display:inline-flex;align-items:center;gap:5px}
    .replay-btn:hover{background:var(--primary);color:#fff}
  
  /* v2.0 Design Enhancements */
  .card-pdam h2, .card-pdam h3 { font-family: 'Outfit', sans-serif; }
  .stat-num { font-family: 'Outfit', sans-serif; font-weight: 800; letter-spacing: -0.5px; }
  .badge-sev {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px; border-radius: 99px; font-size: 11px; font-weight: 700;
    border: 1px solid transparent; font-family: 'DM Sans', sans-serif;
  }
  .badge-high   { background: rgba(225,29,72,0.1);  color: #B91C1C; border-color: rgba(225,29,72,0.2);  }
  .badge-medium { background: rgba(217,119,6,0.1);  color: #92400E; border-color: rgba(217,119,6,0.2);  }
  .badge-low    { background: rgba(5,150,105,0.1);  color: #065F46; border-color: rgba(5,150,105,0.2);  }
  .badge-normal { background: rgba(8,145,178,0.1);  color: #0C4A6E; border-color: rgba(8,145,178,0.2);  }
  [data-theme="dark"] .badge-high   { color: #FCA5A5; }
  [data-theme="dark"] .badge-medium { color: #FDBA74; }
  [data-theme="dark"] .badge-low    { color: #6EE7B7; }
  [data-theme="dark"] .badge-normal { color: #7DD3FC; }

  .grid-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 14px; margin-bottom: 20px; }
  .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 20px; }
  .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 18px; margin-bottom: 20px; }
  @media (max-width: 768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<?php
// bootstrap already loaded at top

// Jika tidak login: tampilkan pesan, TIDAK redirect (sistem tetap terbuka)
$is_logged_in = auth_check();
$auth_user    = $is_logged_in ? auth_user() : [];
$history      = [];
$stats        = ['total_runs'=>0,'total_anomali'=>0,'avg_pct'=>0,'last_run'=>null];

if ($is_logged_in) {
    $history = history_get($auth_user['id'], 100);
    if ($history) {
        $stats['total_runs']   = count($history);
        $stats['total_anomali']= array_sum(array_column($history, 'jumlah_anomali'));
        $stats['avg_pct']      = round(array_sum(array_column($history, 'persentase')) / count($history), 2);
        $stats['last_run']     = $history[0]['created_at'];
    }
}
?>
<?php include 'includes/navbar.php'; ?>
<div class="main-content">

  <?php if (!$is_logged_in): ?>
  <!-- Guest: info saja, tidak blokir -->
  <div class="page-header">
    <div class="page-badge">Dashboard 5</div>
    <h1 style="font-weight:800">Riwayat Analisis</h1>
  </div>
  <div class="card-pdam" style="max-width:520px;margin:40px auto;text-align:center;padding:48px 32px">
    <svg width="56" height="56" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color:var(--text-2);margin-bottom:16px">
      <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <h2 style="font-size:18px;font-weight:800;margin-bottom:8px">Fitur History</h2>
    <p style="font-size:13px;color:var(--text-2);margin-bottom:24px;line-height:1.6">
      Riwayat analisis hanya tersedia untuk pengguna yang login.<br>
      Anda tetap bisa menggunakan semua dashboard tanpa login.
    </p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
      <a href="login.php" style="background:var(--primary);color:#fff;padding:10px 24px;border-radius:9px;text-decoration:none;font-weight:700;font-size:13px">
        Login Sekarang
      </a>
      <a href="register.php" style="background:var(--bg);color:var(--text-1);border:1.5px solid var(--border);padding:10px 24px;border-radius:9px;text-decoration:none;font-weight:700;font-size:13px">
        Daftar Akun
      </a>
      <a href="index.php" style="background:var(--bg);color:var(--text-2);border:1.5px solid var(--border);padding:10px 24px;border-radius:9px;text-decoration:none;font-weight:600;font-size:13px">
        Kembali ke Dashboard
      </a>
    </div>
  </div>
  <?php else: ?>

  <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px">
    <div>
      <div class="page-badge">Dashboard 5</div>
      <h1 style="font-size:22px;font-weight:800;margin-bottom:4px">Riwayat Analisis</h1>
      <p style="font-size:13px;color:var(--text-2)">
        Histori penggunaan sistem oleh <strong><?= htmlspecialchars($auth_user['username']) ?></strong>
      </p>
    </div>
    <a href="index.php" style="display:inline-flex;align-items:center;gap:7px;background:var(--primary);color:#fff;padding:9px 20px;border-radius:9px;text-decoration:none;font-weight:700;font-size:13px">
      <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
      Analisis Baru
    </a>
  </div>

  <?php if (empty($history)): ?>
  <!-- Empty state -->
  <div class="card-pdam">
    <div class="empty-state">
      <svg width="64" height="64" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <h3>Belum ada riwayat</h3>
      <p>Riwayat akan muncul setelah Anda menjalankan analisis pertama.<br>
         Mulai dari <a href="index.php" style="color:var(--primary);font-weight:700">Dashboard 1</a>.</p>
    </div>
  </div>
  <?php else: ?>

  <!-- Summary Cards -->
  <div class="sum4">
    <div class="card-stat">
      <div class="card-stat-icon" style="background:rgba(21,101,192,.1)">
        <svg width="22" height="22" fill="none" stroke="var(--primary)" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <div><div class="card-stat-label">Total Eksekusi</div>
        <div class="card-stat-value"><?= $stats['total_runs'] ?></div>
        <div class="card-stat-sub">Analisis dijalankan</div></div>
    </div>
    <div class="card-stat">
      <div class="card-stat-icon" style="background:rgba(229,57,53,.1)">
        <svg width="22" height="22" fill="none" stroke="#E53935" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
      </div>
      <div><div class="card-stat-label">Total Anomali</div>
        <div class="card-stat-value" style="color:var(--anomaly-color)"><?= number_format($stats['total_anomali']) ?></div>
        <div class="card-stat-sub">Akumulasi semua run</div></div>
    </div>
    <div class="card-stat">
      <div class="card-stat-icon" style="background:rgba(251,140,0,.1)">
        <svg width="22" height="22" fill="none" stroke="#FB8C00" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
      </div>
      <div><div class="card-stat-label">Rata-rata Anomali</div>
        <div class="card-stat-value" style="color:var(--warning)"><?= $stats['avg_pct'] ?>%</div>
        <div class="card-stat-sub">Per eksekusi</div></div>
    </div>
    <div class="card-stat">
      <div class="card-stat-icon" style="background:rgba(46,125,50,.1)">
        <svg width="22" height="22" fill="none" stroke="var(--success)" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      </div>
      <div><div class="card-stat-label">Eksekusi Terakhir</div>
        <div class="card-stat-value" style="font-size:14px"><?= $stats['last_run'] ? date('d M Y', strtotime($stats['last_run'])) : '—' ?></div>
        <div class="card-stat-sub"><?= $stats['last_run'] ? date('H:i', strtotime($stats['last_run'])) : '' ?></div></div>
    </div>
  </div>

  <!-- Charts -->
  <div class="chart-row">
    <div class="card-pdam">
      <div class="section-title">Tren Persentase Anomali per Eksekusi</div>
      <div style="position:relative;height:200px"><canvas id="cTrend"></canvas></div>
    </div>
    <div class="card-pdam">
      <div class="section-title">Distribusi Mode Analisis</div>
      <div style="position:relative;height:200px"><canvas id="cMode"></canvas></div>
    </div>
  </div>

  <!-- Filter row -->
  <div class="filter-bar" style="margin-bottom:20px">
    <div class="filter-group">
      <label>Filter Mode</label>
      <select id="fMode" onchange="filterTable()">
        <option value="">Semua Mode</option>
        <option value="near_tahun_per_golongan">Near Tahun per Golongan</option>
        <option value="near_tahun_near_golongan">Near Tahun vs Near Golongan</option>
        <option value="multi_tahun_per_golongan">Multi Tahun per Golongan</option>
        <option value="multi_tahun_semua_golongan">Multi Tahun Semua Golongan</option>
      </select>
    </div>
    <div class="filter-group">
      <label>Urutkan</label>
      <select id="fSort" onchange="filterTable()">
        <option value="newest">Terbaru Dulu</option>
        <option value="oldest">Terlama Dulu</option>
        <option value="pct_desc">Anomali Tertinggi</option>
        <option value="pct_asc">Anomali Terendah</option>
      </select>
    </div>
    <button class="btn-export" onclick="exportHistory()" style="background:var(--primary);color:#fff">
      <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Export CSV
    </button>
  </div>

  <!-- History Table -->
  <div class="card-pdam">
    <div class="section-title" style="margin-bottom:14px">Tabel Riwayat Analisis</div>
    <div class="table-wrapper">
      <table class="tbl" id="histTable">
        <thead>
          <tr>
            <th>#</th>
            <th>Waktu</th>
            <th>Mode Analisis</th>
            <th>Contamination</th>
            <th>Tahun</th>
            <th>Total Data</th>
            <th>Anomali</th>
            <th>Persentase</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody id="histBody"></tbody>
      </table>
    </div>
    <div class="pagination-wrap" id="histPag">
      <span class="pagination-info"></span>
      <div class="pagination-btns"></div>
    </div>
  </div>

  <!-- Timeline (5 terbaru) -->
  <div class="card-pdam" style="margin-top:20px">
    <div class="section-title">5 Analisis Terakhir</div>
    <div id="timelineWrap">
      <?php foreach (array_slice($history, 0, 5) as $h): ?>
      <div class="timeline-item">
        <div class="timeline-dot" style="margin-top:5px"></div>
        <div style="flex:1">
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:3px">
            <strong style="font-size:13px"><?= htmlspecialchars(mode_label($h['filter_mode'])) ?></strong>
            <span class="cont-badge"><?= $h['contamination']==='auto'?'Auto':(floatval($h['contamination'])*100).'%' ?></span>
            <?php if ($h['tahun_min'] && $h['tahun_max']): ?>
              <span style="font-size:11px;color:var(--text-2)"><?= $h['tahun_min'] ?>–<?= $h['tahun_max'] ?></span>
            <?php endif ?>
          </div>
          <div style="font-size:12px;color:var(--text-2)">
            <?= date('d M Y H:i', strtotime($h['created_at'])) ?>
            &nbsp;|&nbsp;
            <span style="color:var(--anomaly-color);font-weight:700"><?= $h['jumlah_anomali'] ?> anomali</span>
            dari <?= number_format($h['jumlah_data']) ?> data
            (<?= $h['persentase'] ?>%)
          </div>
        </div>
        <button class="replay-btn" onclick="replayAnalysis('<?= htmlspecialchars($h['filter_mode']) ?>','<?= htmlspecialchars($h['contamination']) ?>',<?= intval($h['tahun_min']) ?>,<?= intval($h['tahun_max']) ?>,<?= intval($h['id']) ?>)">
          <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
          Ulangi
        </button>
      </div>
      <?php endforeach ?>
    </div>
  </div>

  <?php endif ?>
  <?php endif ?>

</div>

<script src="js/app.js"></script>
<script>
// ===== DATA dari PHP =====
const HISTORY = <?= json_encode($history, JSON_UNESCAPED_UNICODE) ?>;
let filtered = [...HISTORY];
const pag = new Paginator(20);

// ===== CHARTS =====
function renderCharts() {
  if (!HISTORY.length) return;

  // Trend chart
  const last20 = [...HISTORY].reverse().slice(-20);
  const cT = new Chart(document.getElementById('cTrend'), {
    type: 'line',
    data: {
      labels: last20.map((_,i) => `#${i+1}`),
      datasets: [{
        label: '% Anomali',
        data: last20.map(h => parseFloat(h.persentase)),
        borderColor: '#E53935', backgroundColor: 'rgba(229,57,53,.1)',
        fill: true, tension: .4, pointRadius: 4
      }]
    },
    options: { responsive:true, maintainAspectRatio:false,
      plugins:{ legend:{position:'top',labels:{font:{size:11},boxWidth:12}} },
      scales:{ x:{grid:{display:false},ticks:{font:{size:10}}},
               y:{grid:{color:'rgba(128,128,128,.08)'},ticks:{font:{size:10}},min:0} } }
  });

  // Mode distribution
  const modeCount = {};
  HISTORY.forEach(h => { modeCount[h.filter_mode] = (modeCount[h.filter_mode]||0)+1; });
  const modeLabels = { near_tahun_per_golongan:'Near Tahun/Gol', near_tahun_near_golongan:'Near Tahun vs Gol',
    multi_tahun_per_golongan:'Multi Tahun/Gol', multi_tahun_semua_golongan:'Multi Tahun Semua' };
  const mKeys = Object.keys(modeCount);
  new Chart(document.getElementById('cMode'), {
    type: 'doughnut',
    data: {
      labels: mKeys.map(k => modeLabels[k]||k),
      datasets:[{ data:mKeys.map(k=>modeCount[k]),
        backgroundColor:['var(--primary)','#FB8C00','#43A047','#E53935'],borderWidth:0,hoverOffset:6 }]
    },
    options:{ responsive:true,maintainAspectRatio:false,cutout:'62%',
      plugins:{legend:{position:'right',labels:{font:{size:10},boxWidth:10}}} }
  });
}

// ===== TABLE =====
function filterTable() {
  const mode = document.getElementById('fMode').value;
  const sort = document.getElementById('fSort').value;
  filtered = HISTORY.filter(h => !mode || h.filter_mode === mode);
  if (sort==='oldest')   filtered.sort((a,b)=>new Date(a.created_at)-new Date(b.created_at));
  else if (sort==='pct_desc') filtered.sort((a,b)=>b.persentase-a.persentase);
  else if (sort==='pct_asc')  filtered.sort((a,b)=>a.persentase-b.persentase);
  else filtered.sort((a,b)=>new Date(b.created_at)-new Date(a.created_at));
  pag.current=1; renderTable();
}

const modeLabels = {
  near_tahun_per_golongan:'Near Tahun/Gol', near_tahun_near_golongan:'Near Tahun vs Gol',
  multi_tahun_per_golongan:'Multi Tahun/Gol', multi_tahun_semua_golongan:'Multi Tahun Semua'
};

function renderTable() {
  const page = pag.slice(filtered);
  const base = (pag.current-1)*pag.pageSize;
  const tbody = document.getElementById('histBody');
  if (!tbody) return;

  tbody.innerHTML = page.map((h,i)=>{
    const num = base+i+1;
    const pct = parseFloat(h.persentase);
    const pctCls = pct>30?'pct-high':pct>15?'pct-mid':'pct-low';
    const tMin = h.tahun_min||'—', tMax = h.tahun_max||'—';
    const waktu = new Date(h.created_at);
    const waktuStr = waktu.toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'})
                   + ' ' + waktu.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'});
    return `<tr>
      <td class="tbl-num" style="font-size:11px;color:var(--text-2)">${num}</td>
      <td style="font-size:12px;white-space:nowrap">${waktuStr}</td>
      <td><span class="mode-badge">${modeLabels[h.filter_mode]||h.filter_mode}</span></td>
      <td><span class="cont-badge">${h.contamination==='auto'?'Auto':(parseFloat(h.contamination)*100)+'%'}</span></td>
      <td style="font-size:12px">${tMin===tMax?(tMin):tMin+' – '+tMax}</td>
      <td style="font-size:12px">${parseInt(h.jumlah_data).toLocaleString('id-ID')}</td>
      <td><span style="font-weight:700;color:var(--anomaly-color)">${parseInt(h.jumlah_anomali).toLocaleString('id-ID')}</span></td>
      <td><span class="${pctCls}">${pct.toFixed(1)}%</span></td>
      <td>
        <button class="replay-btn"
          onclick="replayAnalysis('${h.filter_mode}','${h.contamination}',${h.tahun_min||0},${h.tahun_max||0},${h.id})">
          <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
          Ulangi
        </button>
      </td>
    </tr>`;
  }).join('') || '<tr><td colspan="9" style="text-align:center;padding:20px;color:var(--text-2)">Tidak ada riwayat</td></tr>';

  pag.renderControls(filtered, 'histPag', renderTable);
}

// ===== REPLAY =====
function replayAnalysis(mode, cont, tMin, tMax, historyId) {
  // Jika ada historyId, load data lengkap dari database
  if (historyId) {
    fetch('api.php?action=load_history', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'history_id=' + encodeURIComponent(historyId)
    })
    .then(r => r.json())
    .then(d => {
      if (d.status === 'success' && d.data) {
        // Simpan data lengkap ke sessionStorage untuk ditampilkan langsung di dashboard
        sessionStorage.setItem('pdam_history_data', JSON.stringify(d.data));
        sessionStorage.setItem('pdam_history_id', historyId);
        sessionStorage.setItem('pdam_upload_id', d.upload_id);
        
        // Simpan juga parameter untuk replay jika mau re-analysis
        localStorage.setItem('pdam_replay', JSON.stringify({ 
          mode, contamination:cont, tahun_min:tMin||'', tahun_max:tMax||'' 
        }));
        
        // Redirect ke dashboard4 (detail anomali) dengan data sudah siap
        location.href = 'dashboard4.php?from_history=1&history_id=' + historyId;
      } else {
        alert('Gagal memuat data: ' + (d.message || 'Unknown error'));
      }
    })
    .catch(e => {
      alert('Error: ' + e.message);
    });
  } else {
    // Fallback: hanya set parameter
    localStorage.setItem('pdam_replay', JSON.stringify({ 
      mode, contamination:cont, tahun_min:tMin||'', tahun_max:tMax||'' 
    }));
    location.href = 'dashboard2.php?replay=1';
  }
}

// ===== EXPORT =====
function exportHistory() {
  const rows = [['#','Waktu','Mode','Contamination','Tahun Min','Tahun Max','Total Data','Anomali','Persentase']];
  filtered.forEach((h,i) => {
    rows.push([i+1,h.created_at,modeLabels[h.filter_mode]||h.filter_mode,
      h.contamination,h.tahun_min||'',h.tahun_max||'',h.jumlah_data,h.jumlah_anomali,h.persentase+'%']);
  });
  const csv = rows.map(r=>r.map(v=>`"${v}"`).join(',')).join('\n');
  const a = document.createElement('a');
  a.href = URL.createObjectURL(new Blob(['\uFEFF'+csv],{type:'text/csv;charset=utf-8'}));
  a.download = 'history_analisis_pdam.csv';
  a.click();
}

// ===== INIT =====
if (HISTORY.length) {
  renderCharts();
  filterTable();
}
</script>
</body>
</html>
