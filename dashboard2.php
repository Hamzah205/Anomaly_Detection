<?php require_once __DIR__ . "/bootstrap.php";
// No closing PHP tag — prevents "headers already sent"
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
<script>document.documentElement.setAttribute("data-theme",localStorage.getItem("pdam_theme")||"light");</script>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Global Analysis — PDAM Anomaly Detection</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    body{font-family:'DM Sans',sans-serif}
    .stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
    @media(max-width:900px){.stat-grid{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:480px){.stat-grid{grid-template-columns:1fr}}
    .mode-tabs{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px}
    .mtab{padding:7px 14px;border-radius:var(--radius-sm);border:1px solid var(--border);background:var(--bg-card);color:var(--text-2);font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;font-family:inherit}
    .mtab.on{background:var(--primary);color:#fff;border-color:var(--primary)}
    .chart2{display:grid;grid-template-columns:280px 1fr;gap:20px;margin-bottom:24px}
    @media(max-width:720px){.chart2{grid-template-columns:1fr}}
    .donut-wrap{display:flex;flex-direction:column;align-items:center}
    .donut-legend{display:flex;gap:16px;margin-top:12px}
    .dleg{display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600}
    .dleg-dot{width:10px;height:10px;border-radius:3px}
    .export-row{display:flex;gap:8px;flex-wrap:wrap}
    .btn-exp{display:flex;align-items:center;gap:6px;padding:8px 16px;border:none;border-radius:var(--radius-sm);font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .2s}
    .btn-exp:hover{opacity:.85;transform:translateY(-1px)}
    .meta-pill{display:inline-flex;align-items:center;gap:6px;background:rgba(21,101,192,.08);color:var(--primary);border:1px solid rgba(21,101,192,.2);border-radius:20px;font-size:11px;font-weight:700;padding:4px 12px}
  
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
  [data-theme="dark"] .badge-medium { color: #FCD34D; }
  [data-theme="dark"] .badge-low    { color: #6EE7B7; }
  [data-theme="dark"] .badge-normal { color: #7DD3FC; }

  .grid-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 14px; margin-bottom: 20px; }
  .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 20px; }
  .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 18px; margin-bottom: 20px; }
  @media (max-width: 768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="main-content">

  <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px">
    <div>
      <div class="page-badge">Dashboard 2 — Pusat Sistem</div>
      <h1 style="font-size:22px;font-weight:800;margin-bottom:4px">Global Analysis</h1>
      <p style="font-size:13px;color:var(--text-2)">Eksekusi &amp; kontrol model. Setiap perubahan filter di sini akan menjalankan ulang Isolation Forest.</p>
    </div>
    <div class="export-row">
      <button class="btn-exp" onclick="exportCSV()" style="background:var(--primary);color:#fff">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Export CSV
      </button>
      <button class="btn-exp" onclick="window.print()" style="background:var(--danger);color:#fff">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg> Print PDF
      </button>
    </div>
  </div>

  <!-- Global Filter Card -->
  <div class="card-pdam" style="margin-bottom:24px">
    <div class="section-title">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
      Global Filter — Eksekusi Ulang Model
    </div>

    <div style="margin-bottom:14px">
      <div style="font-size:10px;font-weight:700;color:var(--text-2);text-transform:uppercase;letter-spacing:.7px;margin-bottom:8px">Mode Analisis</div>
      <div class="mode-tabs" id="modeTabs">
        <button class="mtab on" data-v="near_tahun_per_golongan">Near Tahun per Golongan</button>
        <button class="mtab"    data-v="near_tahun_near_golongan">Near Tahun vs Near Golongan</button>
        <button class="mtab"    data-v="multi_tahun_per_golongan">Multi Tahun per Golongan</button>
        <button class="mtab"    data-v="multi_tahun_semua_golongan">Multi Tahun Semua Golongan</button>
      </div>
    </div>

    <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end">
      <div class="filter-group">
        <label>Contamination</label>
        <select id="gCont">
          <option value="0.05" selected>5% (Default)</option>
          <option value="auto">Auto</option>
          <option value="0.1">10%</option>
          <option value="0.2">20%</option>
          <option value="0.3">30%</option>
        </select>
      </div>
      <div class="filter-group">
        <label>Tahun Awal</label>
        <select id="gTMin">
          <option value="">Semua</option>
          <option value="2021">2021</option><option value="2022">2022</option>
          <option value="2023">2023</option><option value="2024">2024</option>
        </select>
      </div>
      <div class="filter-group">
        <label>Tahun Akhir</label>
        <select id="gTMax">
          <option value="">Semua</option>
          <option value="2021">2021</option><option value="2022">2022</option>
          <option value="2023">2023</option><option value="2024" selected>2024</option>
        </select>
      </div>
      <button class="btn-run" id="btnRun" onclick="runAnalysis()">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
        Jalankan Analisis
      </button>
    </div>
  </div>

  <div id="alertBox" style="margin-bottom:12px"></div>

  <!-- Stat Cards -->
  <div class="stat-grid">
    <div class="card-stat">
      <div class="card-stat-icon" style="background:rgba(21,101,192,.1)">
        <svg width="22" height="22" fill="none" stroke="var(--primary)" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
      </div>
      <div><div class="card-stat-label">Total Data</div><div class="card-stat-value" id="vTotal">—</div><div class="card-stat-sub">Semua record</div></div>
    </div>
    <div class="card-stat">
      <div class="card-stat-icon" style="background:rgba(229,57,53,.1)">
        <svg width="22" height="22" fill="none" stroke="#E53935" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      </div>
      <div><div class="card-stat-label">Total Anomali</div><div class="card-stat-value" id="vAnom" style="color:var(--anomaly-color)">—</div><div class="card-stat-sub">Data tidak normal</div></div>
    </div>
    <div class="card-stat">
      <div class="card-stat-icon" style="background:rgba(251,140,0,.1)">
        <svg width="22" height="22" fill="none" stroke="#FB8C00" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
      </div>
      <div><div class="card-stat-label">Persentase Anomali</div><div class="card-stat-value" id="vPct" style="color:var(--warning)">—</div><div class="card-stat-sub">Dari total data</div></div>
    </div>
    <div class="card-stat">
      <div class="card-stat-icon" style="background:rgba(0,121,107,.1)">
        <svg width="22" height="22" fill="none" stroke="#00796B" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 010 14.14M4.93 4.93a10 10 0 000 14.14"/></svg>
      </div>
      <div><div class="card-stat-label">Contamination</div><div class="card-stat-value" id="vCont" style="color:var(--secondary)">—</div><div class="card-stat-sub">Parameter model</div></div>
    </div>
  </div>

  <!-- Severity Cards -->
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:24px">
    <div class="card-pdam" style="padding:16px;border-left:4px solid var(--danger)">
      <div style="font-size:11px;font-weight:700;color:var(--text-2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">High Severity</div>
      <div style="font-size:28px;font-weight:800;color:var(--danger)" id="vHigh">—</div>
      <div style="font-size:11px;color:var(--text-2)">Sangat menyimpang dari pola</div>
    </div>
    <div class="card-pdam" style="padding:16px;border-left:4px solid #FB8C00">
      <div style="font-size:11px;font-weight:700;color:var(--text-2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Medium Severity</div>
      <div style="font-size:28px;font-weight:800;color:#FB8C00" id="vMed">—</div>
      <div style="font-size:11px;color:var(--text-2)">Cukup menyimpang</div>
    </div>
    <div class="card-pdam" style="padding:16px;border-left:4px solid #F9A825">
      <div style="font-size:11px;font-weight:700;color:var(--text-2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Low Severity</div>
      <div style="font-size:28px;font-weight:800;color:#F9A825" id="vLow">—</div>
      <div style="font-size:11px;color:var(--text-2)">Sedikit menyimpang</div>
    </div>
  </div>

  <!-- Charts row -->
  <div class="chart2">
    <div class="card-pdam">
      <div class="section-title">Distribusi Status</div>
      <div class="donut-wrap">
        <div style="position:relative;width:220px;height:220px">
          <canvas id="cDonut"></canvas>
          <div id="donutCenter" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none">
            <div id="dcPct" style="font-size:26px;font-weight:800;color:var(--anomaly-color)">—</div>
            <div style="font-size:10px;color:var(--text-2);font-weight:600">ANOMALI</div>
          </div>
        </div>
        <div class="donut-legend">
          <div class="dleg"><div class="dleg-dot" style="background:#E53935"></div><span id="ll1">Anomali</span></div>
          <div class="dleg"><div class="dleg-dot" style="background:#43A047"></div><span id="ll2">Normal</span></div>
        </div>
      </div>
    </div>
    <div class="card-pdam">
      <div class="section-title">Anomali per Golongan</div>
      <div style="position:relative;height:220px"><canvas id="cBar"></canvas></div>
    </div>
  </div>

  <!-- Insights -->
  <div class="card-pdam" style="margin-bottom:20px">
    <div class="section-title">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      Insight &amp; Ringkasan
    </div>
    <div id="insightArea"><p style="color:var(--text-2);font-size:13px">Jalankan analisis untuk melihat insight otomatis.</p></div>
  </div>

  <!-- Summary per Tahun -->
  <div class="card-pdam">
    <div class="section-title">Ringkasan Anomali per Tahun</div>
    <div class="table-wrapper">
      <table class="tbl">
        <thead><tr><th>Tahun</th><th>Total Data</th><th>Anomali</th><th>Normal</th><th>Persentase</th><th>Visualisasi</th></tr></thead>
        <tbody id="tTahun"><tr><td colspan="6" style="text-align:center;padding:20px;color:var(--text-2)">Belum ada data</td></tr></tbody>
      </table>
    </div>
    <div style="margin-top:16px">
      <div class="section-title">Ringkasan per Golongan</div>
      <div class="table-wrapper">
        <table class="tbl">
          <thead><tr><th>Golongan</th><th>Nama</th><th>Total</th><th>Anomali</th><th>Normal</th><th>%</th><th>Visualisasi</th></tr></thead>
          <tbody id="tGolongan"><tr><td colspan="7" style="text-align:center;padding:20px;color:var(--text-2)">Belum ada data</td></tr></tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<script src="js/app.js"></script>
<script>
let curData = null;
let cDonut = null, cBar = null;
let activeMode = 'near_tahun_per_golongan';

document.querySelectorAll('.mtab').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.mtab').forEach(b => b.classList.remove('on'));
    btn.classList.add('on');
    activeMode = btn.dataset.v;
  });
});

async function runAnalysis() {
  Loading.show('Menjalankan Isolation Forest...');
  document.getElementById('btnRun').disabled = true;
  try {
    const fd = new FormData();
    ['action','contamination','mode','tahun_min','tahun_max'].forEach(k => {
      const vals = {action:'analyze',contamination:document.getElementById('gCont').value,
        mode:activeMode,tahun_min:document.getElementById('gTMin').value,
        tahun_max:document.getElementById('gTMax').value};
      if(vals[k]) fd.append(k, vals[k]);
    });
    fd.set('action','analyze');
    const d = await (await fetch('api.php',{method:'POST',body:fd})).json();
    if(d.status!=='success') { showAlert('Error: '+d.message,'danger'); return; }
    await loadResult();
  } catch(e) { showAlert('Koneksi error: '+e.message,'danger'); }
  finally { Loading.hide(); document.getElementById('btnRun').disabled=false; }
}

async function loadResult() {
  try {
    const d = await (await fetch('api.php?action=get_result')).json();
    if(d.status!=='success') { showAlert('Belum ada hasil. Jalankan analisis atau upload file di Dashboard 1.','warning'); return; }
    curData = d.data;
    render(d.data);
  } catch(e) { showAlert('Gagal memuat: '+e.message,'danger'); }
}

function render(data) {
  const m = data.meta;
  document.getElementById('vTotal').textContent = m.total.toLocaleString('id-ID');
  document.getElementById('vAnom').textContent  = m.anomali.toLocaleString('id-ID');
  document.getElementById('vPct').textContent   = m.pct_anomali.toFixed(1)+'%';
  document.getElementById('vCont').textContent  = m.contamination==='auto'?'Auto':(parseFloat(m.contamination)*100)+'%';
  // Severity
  const sev = m.severity || {high:0,medium:0,low:0};
  document.getElementById('vHigh').textContent = (sev.high||0).toLocaleString('id-ID');
  document.getElementById('vMed').textContent  = (sev.medium||0).toLocaleString('id-ID');
  document.getElementById('vLow').textContent  = (sev.low||0).toLocaleString('id-ID');
  document.getElementById('dcPct').textContent  = m.pct_anomali.toFixed(1)+'%';
  document.getElementById('ll1').textContent = `Anomali (${m.anomali})`;
  document.getElementById('ll2').textContent = `Normal (${m.normal})`;

  // Donut
  if(cDonut) cDonut.destroy();
  cDonut = new Chart(document.getElementById('cDonut').getContext('2d'), {
    type:'doughnut',
    data:{labels:['Anomali','Normal'],datasets:[{data:[m.anomali,m.normal],backgroundColor:['#E53935','#43A047'],borderWidth:0,hoverOffset:6}]},
    options:{cutout:'70%',responsive:true,maintainAspectRatio:false,
      plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>`${c.label}: ${c.parsed.toLocaleString('id-ID')} (${(c.parsed/m.total*100).toFixed(1)}%)`}}}}
  });

  // Bar
  const gs = data.summary_golongan;
  if(cBar) cBar.destroy();
  cBar = new Chart(document.getElementById('cBar').getContext('2d'), {
    type:'bar',
    data:{labels:gs.map(g=>g.golongan),
      datasets:[
        {label:'Anomali',data:gs.map(g=>g.anomali),backgroundColor:'rgba(229,57,53,.72)',borderRadius:5,borderSkipped:false},
        {label:'Normal', data:gs.map(g=>g.normal), backgroundColor:'rgba(67,160,71,.5)', borderRadius:5,borderSkipped:false}
      ]},
    options:{responsive:true,maintainAspectRatio:false,
      plugins:{legend:{position:'top',labels:{font:{size:11},boxWidth:12}}},
      scales:{x:{grid:{display:false},ticks:{font:{size:11}}},
              y:{grid:{color:'rgba(128,128,128,.08)'},ticks:{font:{size:10},callback:v=>v.toLocaleString('id-ID')}}}}
  });

  // Insights
  const ins = m.insights||[];
  const pct = m.pct_anomali;
  const cls = pct>20?'danger':pct<5?'success':'';
  document.getElementById('insightArea').innerHTML = [
    `<div class="meta-pill" style="margin-bottom:12px">
       Mode: ${m.mode} &nbsp;|&nbsp; Tahun: ${m.tahun_range[0]}–${m.tahun_range[1]}
     </div>`,
    ...ins.map(i=>`<div class="insight-box ${cls}" style="margin-bottom:8px">${i}</div>`)
  ].join('') || '<p style="color:var(--text-2);font-size:13px">Tidak ada insight.</p>';

  // Table Tahun
  document.getElementById('tTahun').innerHTML = data.summary_tahun.map(t=>`<tr>
    <td><strong>${t.tahun}</strong></td>
    <td>${t.total.toLocaleString('id-ID')}</td>
    <td><span style="font-weight:700;color:var(--anomaly-color)">${t.anomali}</span></td>
    <td><span style="font-weight:700;color:var(--normal-color)">${t.normal}</span></td>
    <td><strong>${t.pct.toFixed(1)}%</strong></td>
    <td style="min-width:120px">
      <div class="progress-bar-wrap">
        <div class="progress-bar-fill" style="width:${Math.min(100,t.pct)}%;background:${t.pct>30?'#E53935':t.pct>15?'#FB8C00':'#43A047'}"></div>
      </div>
    </td></tr>`).join('');

  // Table Golongan
  const namaMap = {};
  data.data.forEach(r => { namaMap[r.golongan] = r.nama_golongan; });
  document.getElementById('tGolongan').innerHTML = data.summary_golongan.map(g=>`<tr>
    <td><strong style="color:${Colors.getGol(g.golongan)}">${g.golongan}</strong></td>
    <td style="font-size:12px">${namaMap[g.golongan]||g.golongan}</td>
    <td>${g.total}</td>
    <td><span style="font-weight:700;color:var(--anomaly-color)">${g.anomali}</span></td>
    <td><span style="font-weight:700;color:var(--normal-color)">${g.normal}</span></td>
    <td><strong>${g.pct.toFixed(1)}%</strong></td>
    <td style="min-width:100px">
      <div class="progress-bar-wrap">
        <div class="progress-bar-fill" style="width:${Math.min(100,g.pct)}%;background:${Colors.getGol(g.golongan)}"></div>
      </div>
    </td></tr>`).join('');
}

function showAlert(msg,t='info') {
  document.getElementById('alertBox').innerHTML=`<div class="alert-pdam alert-${t}">${msg}</div>`;
}
function exportCSV() {
  if(!curData) return showAlert('Tidak ada data.','warning');
  Export.toExcel(curData.data,'global_analysis_export');
}

// ===== REPLAY dari History =====
(function() {
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('replay') !== '1') { loadResult(); return; }

  const saved = localStorage.getItem('pdam_replay');
  if (!saved) { loadResult(); return; }

  try {
    const p = JSON.parse(saved);
    localStorage.removeItem('pdam_replay');

    // Set UI ke nilai replay
    if (p.mode) {
      document.querySelectorAll('.mtab').forEach(b => {
        b.classList.toggle('on', b.dataset.v === p.mode);
      });
      activeMode = p.mode;
    }
    if (p.contamination) document.getElementById('gCont').value = p.contamination;
    if (p.tahun_min)     document.getElementById('gTMin').value = p.tahun_min;
    if (p.tahun_max)     document.getElementById('gTMax').value = p.tahun_max;

    // Tampilkan banner replay
    const banner = document.createElement('div');
    banner.className = 'replay-banner';
    banner.innerHTML = `
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <polyline points="1 4 1 10 7 10"/>
        <path d="M3.51 15a9 9 0 102.13-9.36L1 10"/>
      </svg>
      Mengulang analisis dari riwayat: <strong>${p.mode||''}</strong>
      &nbsp;|&nbsp; Contamination: <strong>${p.contamination||'auto'}</strong>
      &nbsp;|&nbsp; Tahun: <strong>${p.tahun_min||'Semua'}–${p.tahun_max||'Semua'}</strong>
    `;
    document.getElementById('alertBox').before(banner);
    runAnalysis();
  } catch(e) {
    loadResult();
  }
})();
</script>
</body>
</html>
