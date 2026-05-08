<?php require_once __DIR__ . '/bootstrap.php';
// No closing PHP tag — prevents "headers already sent"
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
<script>document.documentElement.setAttribute("data-theme",localStorage.getItem("pdam_theme")||"light");</script>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Visual Analytics — PDAM Anomaly Detection</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <style>
    body{font-family:'DM Sans',sans-serif}
    .chart-2col{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px}
    @media(max-width:700px){.chart-2col{grid-template-columns:1fr}}
    .sev-dot{display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:4px;flex-shrink:0}
    .sev-high{color:var(--danger);font-weight:700}
    .sev-med{color:#FB8C00;font-weight:700}
    .sev-low{color:#F9A825;font-weight:700}
    .badge-high{background:rgba(198,40,40,.12);color:var(--danger);border:1px solid rgba(198,40,40,.25);font-size:10px;font-weight:700;padding:2px 8px;border-radius:var(--radius-md);white-space:nowrap}
    .badge-med{background:rgba(251,140,0,.12);color:#E65100;border:1px solid rgba(251,140,0,.25);font-size:10px;font-weight:700;padding:2px 8px;border-radius:var(--radius-md);white-space:nowrap}
    .badge-low{background:rgba(249,168,37,.12);color:#F57F17;border:1px solid rgba(249,168,37,.25);font-size:10px;font-weight:700;padding:2px 8px;border-radius:var(--radius-md);white-space:nowrap}
  
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

  <div class="page-header">
    <div class="page-badge">Dashboard 3</div>
    <h1 style="font-weight:800">Visual Analytics</h1>
    <p style="font-size:13px;color:var(--text-2)">Visualisasi distribusi &amp; tren anomali. Filter lokal — tidak menjalankan ulang model.</p>
  </div>

  <!-- Filter Bar -->
  <div class="filter-bar">
    <div class="filter-group">
      <label>Golongan</label>
      <select id="fGol"><option value="">Semua Golongan</option></select>
    </div>
    <div class="filter-group">
      <label>Tahun Awal</label>
      <select id="fTMin"><option value="">Semua</option></select>
    </div>
    <div class="filter-group">
      <label>Tahun Akhir</label>
      <select id="fTMax"><option value="">Semua</option></select>
    </div>
    <div class="filter-group">
      <label>Tipe Data</label>
      <select id="fTipe">
        <option value="">Semua Data</option>
        <option value="1">Hanya Anomali</option>
        <option value="0">Hanya Normal</option>
      </select>
    </div>
    <div class="filter-group">
      <label>Tingkat Anomali</label>
      <select id="fSev">
        <option value="">Semua Tingkat</option>
        <option value="high">High</option>
        <option value="medium">Medium</option>
        <option value="low">Low</option>
      </select>
    </div>
    <button class="btn-run" onclick="applyFilter()">
      <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
      Filter
    </button>
    <button class="btn-export" onclick="doExportCSV()">
      <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      CSV
    </button>
    <button class="btn-export" onclick="doExportPDF()" style="background:var(--danger)">
      <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      PDF Grafik
    </button>
  </div>

  <div id="alertBox" style="margin-bottom:12px"></div>

  <!-- Filter Info Banner -->
  <div id="filterBanner" style="display:none;background:rgba(21,101,192,.06);border:1px solid rgba(21,101,192,.2);border-radius:10px;padding:10px 16px;margin-bottom:16px;font-size:12px;color:var(--primary);font-weight:600"></div>

  <!-- Charts Section (id=chartsSection for PDF export) -->
  <div id="chartsSection">
    <div class="chart-2col">
      <div class="card-pdam" id="cCardTahun">
        <div class="section-title">Tren Anomali per Tahun</div>
        <div style="position:relative;height:210px"><canvas id="cTahun"></canvas></div>
      </div>
      <div class="card-pdam" id="cCardGol">
        <div class="section-title">Distribusi Anomali per Golongan</div>
        <div style="position:relative;height:210px"><canvas id="cGol"></canvas></div>
      </div>
    </div>
    <div class="chart-2col">
      <div class="card-pdam" id="cCardScore">
        <div class="section-title">Intensitas Anomaly Score per Bulan</div>
        <div style="position:relative;height:200px"><canvas id="cScore"></canvas></div>
      </div>
      <div class="card-pdam" id="cCardSev">
        <div class="section-title">Distribusi Severity Level</div>
        <div style="position:relative;height:200px"><canvas id="cSev"></canvas></div>
      </div>
    </div>
    <!-- Heatmap -->
    <div class="card-pdam" style="margin-bottom:20px">
      <div class="section-title">Heatmap Anomali: Golongan x Tahun</div>
      <div style="overflow-x:auto" id="heatmapWrap"></div>
    </div>
  </div>

  <!-- Z-Score Table -->
  <div class="card-pdam" style="margin-bottom:20px">
    <div class="section-title">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M2 20h.01M7 20v-4M12 20v-8M17 20V8M22 4v16"/></svg>
      Z-Score Atribut Penyebab Anomali
    </div>
    <p style="font-size:12px;color:var(--text-2);margin-bottom:12px">
      Ditampilkan untuk <strong>semua data anomali</strong> (terdeteksi oleh Isolation Forest). Z-Score hanya untuk interpretasi — bukan penentu anomali. Top-2 atribut dengan penyimpangan terbesar ditampilkan per baris.
    </p>
    <div class="table-wrapper">
      <table class="tbl">
        <thead><tr><th>Tahun</th><th>Bulan</th><th>Golongan</th><th>Tingkat</th><th>Rp</th><th>M3</th><th>Rp/M3</th><th>Atribut Penyebab (Z-Score)</th></tr></thead>
        <tbody id="zBody"><tr><td colspan="8" style="text-align:center;padding:20px;color:var(--text-2)">Muat data...</td></tr></tbody>
      </table>
    </div>
    <div class="pagination-wrap" id="zPag"><span class="pagination-info"></span><div class="pagination-btns"></div></div>
  </div>

  <!-- Full Data Table -->
  <div class="card-pdam">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px">
      <div class="section-title" style="margin-bottom:0;flex:1">Tabel Data Lengkap</div>
      <div style="font-size:12px;color:var(--text-2)" id="tableInfo"></div>
    </div>
    <div class="table-wrapper">
      <table class="tbl">
        <thead><tr><th>Tahun</th><th>Bulan</th><th>Golongan</th><th>Nama Golongan</th><th>Rp</th><th>M3</th><th>Rp/M3</th><th>Status</th><th>Tingkat</th><th>Score</th></tr></thead>
        <tbody id="mainBody"></tbody>
      </table>
    </div>
    <div class="pagination-wrap" id="mainPag"><span class="pagination-info"></span><div class="pagination-btns"></div></div>
  </div>

</div>

<script src="js/app.js"></script>
<script>
let raw = null, filtered = [];
let cT=null, cG=null, cS=null, cSev=null;
const mPag = new Paginator(25);
const zPag = new Paginator(15);

const SEV_COLOR = { high:'var(--danger)', medium:'#FB8C00', low:'#F9A825', normal:'#43A047' };
const SEV_LABEL = { high:'High', medium:'Medium', low:'Low', normal:'Normal' };

function sevBadge(lvl) {
  const cls = {high:'badge-sev badge-high', medium:'badge-sev badge-medium', low:'badge-sev badge-low'}[lvl] || '';
  return `<span class="${cls}">${SEV_LABEL[lvl]||lvl}</span>`;
}

async function load() {
  Loading.show('Memuat data...');
  try {
    const d = await (await fetch('api.php?action=get_result')).json();
    if (d.status !== 'success') { showAlert('Belum ada data. Jalankan analisis di Dashboard 1.','warning'); return; }
    raw = d.data;
    buildFilters();
    filtered = [...raw.data];
    renderAll();
  } catch(e) { showAlert('Error: '+e.message,'danger'); }
  finally { Loading.hide(); }
}

function buildFilters() {
  const gols   = [...new Set(raw.data.map(r=>r.golongan))].sort();
  const tahuns = [...new Set(raw.data.map(r=>r.tahun))].sort();
  const gs = document.getElementById('fGol');
  gols.forEach(g => { const nm=raw.data.find(r=>r.golongan===g)?.nama_golongan||g; gs.innerHTML+=`<option value="${g}">${g} — ${nm}</option>`; });
  const mn=document.getElementById('fTMin'), mx=document.getElementById('fTMax');
  tahuns.forEach(t => { mn.innerHTML+=`<option value="${t}">${t}</option>`; mx.innerHTML+=`<option value="${t}">${t}</option>`; });
  if (tahuns.length) mx.value = tahuns[tahuns.length-1];
}

function applyFilter() {
  const g   = document.getElementById('fGol').value;
  const t1  = parseInt(document.getElementById('fTMin').value)||0;
  const t2  = parseInt(document.getElementById('fTMax').value)||9999;
  const tip = document.getElementById('fTipe').value;
  const sev = document.getElementById('fSev').value;
  filtered = raw.data.filter(r => {
    if (g   && r.golongan !== g)            return false;
    if (t1  && r.tahun < t1)                return false;
    if (t2<9999 && r.tahun > t2)            return false;
    if (tip !== '' && r.is_anomaly !== parseInt(tip)) return false;
    if (sev && r.anomaly_level !== sev)     return false;
    return true;
  });
  mPag.current = 1; zPag.current = 1;
  // Banner
  const parts = [];
  if (g)   parts.push('Golongan: '+g);
  if (t1)  parts.push('Tahun: '+(t1||'Semua')+' – '+(t2===9999?'Semua':t2));
  if (tip !== '') parts.push(tip==='1'?'Hanya Anomali':'Hanya Normal');
  if (sev) parts.push('Tingkat: '+SEV_LABEL[sev]);
  const banner = document.getElementById('filterBanner');
  if (parts.length) { banner.style.display='block'; banner.textContent='Filter aktif: '+parts.join(' | ')+' — '+filtered.length+' data'; }
  else { banner.style.display='none'; }
  renderAll();
}

function renderAll() { renderCharts(); renderHeatmap(); renderMainTable(); renderZTable(); }

function renderCharts() {
  const data = filtered;

  // 1. Tren per tahun (stacked anomali per severity)
  const byT = {};
  data.forEach(r => {
    if (!byT[r.tahun]) byT[r.tahun] = {high:0,medium:0,low:0,normal:0};
    byT[r.tahun][r.anomaly_level]++;
  });
  const tK = Object.keys(byT).sort();
  if (cT) cT.destroy();
  cT = new Chart(document.getElementById('cTahun'), {
    type:'bar',
    data:{ labels:tK, datasets:[
      {label:'High',  data:tK.map(t=>byT[t].high),   backgroundColor:'rgba(198,40,40,.8)', borderRadius:3},
      {label:'Medium',data:tK.map(t=>byT[t].medium), backgroundColor:'rgba(251,140,0,.75)',borderRadius:3},
      {label:'Low',   data:tK.map(t=>byT[t].low),    backgroundColor:'rgba(249,168,37,.7)',borderRadius:3},
      {label:'Normal',data:tK.map(t=>byT[t].normal), backgroundColor:'rgba(67,160,71,.5)', borderRadius:3},
    ]},
    options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'top',labels:{font:{size:10},boxWidth:10}}},
      scales:{ x:{stacked:true,grid:{display:false},ticks:{font:{size:11}}},
               y:{stacked:true,grid:{color:'rgba(128,128,128,.08)'},ticks:{font:{size:10}}}}}
  });

  // 2. Distribusi per golongan (anomali saja)
  const byG = {};
  data.filter(r=>r.is_anomaly).forEach(r => { byG[r.golongan] = (byG[r.golongan]||0)+1; });
  const gK = Object.keys(byG).sort();
  if (cG) cG.destroy();
  cG = new Chart(document.getElementById('cGol'), {
    type:'bar',
    data:{ labels:gK, datasets:[{label:'Anomali', data:gK.map(g=>byG[g]),
      backgroundColor:gK.map(g=>Colors.getGol(g)+'BB'), borderRadius:4}]},
    options:{ responsive:true, maintainAspectRatio:false, indexAxis:'y',
      plugins:{legend:{display:false}},
      scales:{ x:{grid:{color:'rgba(128,128,128,.08)'},ticks:{font:{size:10}}},
               y:{grid:{display:false},ticks:{font:{size:11}}}}}
  });

  // 3. Score line
  const anom = data.filter(r=>r.is_anomaly);
  const byM = {};
  anom.forEach(r => {
    const k=`${r.tahun}-${String(r.bulan_num).padStart(2,'0')}`;
    if (!byM[k]) byM[k]={sum:0,cnt:0,lbl:`${r.bulan.slice(0,3)} ${r.tahun}`};
    byM[k].sum+=Math.abs(r.anomaly_score); byM[k].cnt++;
  });
  const mK = Object.keys(byM).sort();
  if (cS) cS.destroy();
  cS = new Chart(document.getElementById('cScore'), {
    type:'line',
    data:{ labels:mK.map(k=>byM[k].lbl), datasets:[{label:'Rata-rata |Score|',
      data:mK.map(k=>(byM[k].sum/byM[k].cnt).toFixed(4)),
      borderColor:'#FB8C00', backgroundColor:'rgba(251,140,0,.1)',
      fill:true, tension:.4, pointRadius:3}]},
    options:{ responsive:true, maintainAspectRatio:false,
      plugins:{legend:{position:'top',labels:{font:{size:10},boxWidth:10}}},
      scales:{ x:{grid:{display:false},ticks:{font:{size:9},maxTicksLimit:10}},
               y:{grid:{color:'rgba(128,128,128,.08)'},ticks:{font:{size:10}}}}}
  });

  // 4. Severity donut
  const sevCnt = {high:0,medium:0,low:0};
  data.filter(r=>r.is_anomaly).forEach(r => { if(sevCnt[r.anomaly_level]!==undefined) sevCnt[r.anomaly_level]++; });
  if (cSev) cSev.destroy();
  cSev = new Chart(document.getElementById('cSev'), {
    type:'doughnut',
    data:{ labels:['High','Medium','Low'],
      datasets:[{data:[sevCnt.high,sevCnt.medium,sevCnt.low],
        backgroundColor:['#E53935','#FB8C00','#43A047'], borderWidth:0, hoverOffset:6}]},
    options:{ responsive:true, maintainAspectRatio:false, cutout:'62%',
      plugins:{ legend:{position:'right',labels:{font:{size:11},boxWidth:10}},
        tooltip:{callbacks:{label:c=>`${c.label}: ${c.parsed} anomali`}}}}
  });
}

function renderHeatmap() {
  const gols   = [...new Set(filtered.map(r=>r.golongan))].sort();
  const tahuns = [...new Set(filtered.map(r=>r.tahun))].sort();
  const mat={};
  filtered.forEach(r=>{const k=`${r.golongan}_${r.tahun}`;if(!mat[k])mat[k]={tot:0,high:0,med:0,low:0};
    if(r.is_anomaly){mat[k].tot++;if(r.anomaly_level==='high')mat[k].high++;
    else if(r.anomaly_level==='medium')mat[k].med++;else mat[k].low++;}});
  const maxV=Math.max(...Object.values(mat).map(v=>v.tot),1);
  let h=`<table class="tbl"><thead><tr><th>Golongan</th>${tahuns.map(t=>`<th style="text-align:center">${t}</th>`).join('')}<th style="text-align:center">Total</th></tr></thead><tbody>`;
  gols.forEach(g=>{
    const rowTot=tahuns.reduce((s,t)=>s+(mat[`${g}_${t}`]?.tot||0),0);
    h+=`<tr><td><strong style="color:${Colors.getGol(g)}">${g}</strong></td>`;
    tahuns.forEach(t=>{
      const v=mat[`${g}_${t}`];
      const tot=v?.tot||0; const p=tot/maxV;
      const bg=tot===0?'transparent':`rgba(229,57,53,${.1+p*.8})`;
      const fg=p>.6?'#fff':'var(--text-1)';
      const tip=tot?`H:${v.high} M:${v.med} L:${v.low}`:'';
      h+=`<td style="background:${bg};color:${fg};text-align:center;font-weight:${tot?700:400};font-size:12px" title="${tip}">${tot||'—'}</td>`;
    });
    h+=`<td style="text-align:center;font-weight:700;color:var(--anomaly-color)">${rowTot||'—'}</td></tr>`;
  });
  h+='</tbody></table>';
  document.getElementById('heatmapWrap').innerHTML=h;
}

function renderMainTable() {
  const page=mPag.slice(filtered);
  document.getElementById('tableInfo').textContent=`${filtered.length} data ditampilkan`;
  document.getElementById('mainBody').innerHTML=page.map(r=>`<tr class="${r.is_anomaly?'row-anomaly':''}">
    <td>${r.tahun}</td><td style="font-size:12px">${r.bulan}</td>
    <td><strong style="color:${Colors.getGol(r.golongan)}">${r.golongan}</strong></td>
    <td style="font-size:11px">${r.nama_golongan}</td>
    <td style="font-size:12px">${Fmt.rp(r.rp)}</td>
    <td style="font-size:12px">${r.m3.toLocaleString('id-ID')}</td>
    <td style="font-size:12px">${Fmt.num(r.rp_per_m3)}</td>
    <td>${r.is_anomaly?'<span class="badge-anomaly">Anomali</span>':'<span class="badge-sev badge-normal">Normal</span>'}</td>
    <td>${r.is_anomaly?sevBadge(r.anomaly_level):'<span style="font-size:11px;color:var(--text-2)">—</span>'}</td>
    <td style="font-size:11px;color:var(--text-2)">${r.anomaly_score.toFixed(4)}</td>
  </tr>`).join('')||'<tr><td colspan="10" style="text-align:center;padding:20px;color:var(--text-2)">Tidak ada data</td></tr>';
  mPag.renderControls(filtered,'mainPag',renderMainTable);
}

function renderZTable() {
  // Tampilkan SEMUA anomali yang punya causes (tidak filter berdasarkan nilai Z)
  // Isolation Forest sudah menentukan anomali; Z-Score hanya untuk interpretasi
  const anom = filtered.filter(r => r.is_anomaly && r.causes && r.causes.length > 0);
  const page = zPag.slice(anom);
  document.getElementById('zBody').innerHTML = page.map(r => {
    const isCombo = r.causes.some(c => c.combination_note);
    const chips = r.causes.map(c => {
      // Label severity hanya untuk pewarnaan — bukan threshold filter
      const cls = c.zscore >= 3 ? 'zscore-high' : c.zscore >= 2 ? 'zscore-med' : 'zscore-low';
      return `<span class="zscore-tag ${cls}" title="${c.penjelasan}">${c.atribut} z=${c.zscore >= 0 ? '+' : ''}${c.zscore_raw?.toFixed(2) || c.zscore}</span>`;
    }).join('');
    const comboNote = isCombo
      ? `<span class="zscore-tag" style="background:rgba(8,145,178,.12);color:#0369a1;border:1px solid rgba(8,145,178,.25);font-size:10px" title="Tidak ada satu atribut yang sangat ekstrem. Anomali terjadi karena kombinasi pola keseluruhan yang tidak lazim (ditentukan oleh Isolation Forest).">⚠ kombinasi</span>`
      : '';
    return `<tr class="row-anomaly">
      <td>${r.tahun}</td><td style="font-size:11px">${r.bulan}</td>
      <td><strong style="color:${Colors.getGol(r.golongan)}">${r.golongan}</strong></td>
      <td>${sevBadge(r.anomaly_level)}</td>
      <td style="font-size:12px">${Fmt.rp(r.rp)}</td>
      <td style="font-size:12px">${r.m3.toLocaleString('id-ID')}</td>
      <td style="font-size:12px">${Fmt.num(r.rp_per_m3)}</td>
      <td>${chips}${comboNote}</td>
    </tr>`;
  }).join('') || '<tr><td colspan="8" style="text-align:center;padding:20px;color:var(--text-2)">Tidak ada data anomali</td></tr>';
  zPag.renderControls(anom, 'zPag', renderZTable);
}

// ===== EXPORT CSV =====
function doExportCSV() {
  if (!filtered.length) { showAlert('Tidak ada data.','warning'); return; }
  Export.toExcel(filtered,'visual_analytics_d3');
}

// ===== EXPORT PDF GRAFIK =====
async function doExportPDF() {
  Loading.show('Membuat PDF grafik...');
  try {
    const { jsPDF } = window.jspdf;
    const section   = document.getElementById('chartsSection');
    const canvas    = await html2canvas(section, { scale:1.5, useCORS:true, backgroundColor: document.documentElement.getAttribute('data-theme')==='dark'?'#0A0E1A':'#F5F7FA' });
    const imgData   = canvas.toDataURL('image/jpeg', 0.92);
    const pdf       = new jsPDF({ orientation:'landscape', unit:'mm', format:'a4' });
    const pdfW      = pdf.internal.pageSize.getWidth();
    const pdfH      = pdf.internal.pageSize.getHeight();
    const ratio     = canvas.height / canvas.width;
    const imgH      = pdfW * ratio;

    // Header
    pdf.setFontSize(13); pdf.setFont('helvetica','bold');
    pdf.text('Visual Analytics — PDAM Anomaly Detection', 14, 12);
    pdf.setFontSize(9); pdf.setFont('helvetica','normal');
    pdf.text('Perumdam Tirta Kencana Samarinda', 14, 18);

    // Filter info
    const banner = document.getElementById('filterBanner');
    if (banner.style.display !== 'none') {
      pdf.setFontSize(8);
      pdf.text(banner.textContent, 14, 24);
    }
    pdf.text('Dicetak: '+new Date().toLocaleString('id-ID'), pdfW-14, 24, {align:'right'});

    const startY = 28;
    const availH = pdfH - startY - 4;
    const finalH = Math.min(imgH, availH);
    pdf.addImage(imgData, 'JPEG', 0, startY, pdfW, finalH);

    // Multi-page jika grafik terlalu panjang
    if (imgH > availH) {
      let yOffset = availH;
      while (yOffset < imgH) {
        pdf.addPage();
        pdf.addImage(imgData, 'JPEG', 0, -(yOffset * pdfW / canvas.width), pdfW, imgH);
        yOffset += pdfH;
      }
    }

    pdf.save('visual_analytics_pdam.pdf');
  } catch(e) {
    showAlert('Export PDF gagal: '+e.message,'danger');
  } finally {
    Loading.hide();
  }
}

function showAlert(m,t) { document.getElementById('alertBox').innerHTML=`<div class="alert-pdam alert-${t}">${m}</div>`; }

load();
</script>
</body>
</html>
