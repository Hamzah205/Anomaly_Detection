<?php require_once __DIR__ . '/bootstrap.php';
// No closing PHP tag — prevents "headers already sent"
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
<?php include __DIR__ . '/includes/head_common.php'; ?>
  <title>Visual Analytics — PDAM Anomaly Detection</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <style>
    .chart-2col{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px}
    @media(max-width:700px){.chart-2col{grid-template-columns:1fr}}
    .sev-dot{display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:4px;flex-shrink:0}
    .sev-high{color:var(--danger);font-weight:700}
    .sev-med{color:#FB8C00;font-weight:700}
    .sev-low{color:#F9A825;font-weight:700}

    /* Export Buttons */
    .btn-export-excel, .btn-export-pdf {
      display:inline-flex;align-items:center;gap:8px;padding:9px 18px;border:none;border-radius:10px;
      font-size:13px;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;
      transition:all .25s ease;white-space:nowrap;box-shadow:0 2px 8px rgba(0,0,0,.08);
    }
    .btn-export-excel {
      background:linear-gradient(135deg,#059669,#047857);color:#fff;
    }
    .btn-export-excel:hover {
      background:linear-gradient(135deg,#047857,#065f46);transform:translateY(-2px);
      box-shadow:0 6px 16px rgba(5,150,105,.35);
    }
    .btn-export-pdf {
      background:linear-gradient(135deg,#EF4444,#DC2626);color:#fff;
    }
    .btn-export-pdf:hover {
      background:linear-gradient(135deg,#DC2626,#B91C1C);transform:translateY(-2px);
      box-shadow:0 6px 16px rgba(220,38,38,.35);
    }
    .btn-export-excel:active, .btn-export-pdf:active {
      transform:translateY(0);
    }
    .btn-export-excel svg, .btn-export-pdf svg {
      width:16px;height:16px;flex-shrink:0;
    }
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
    <button class="btn-export-excel" onclick="doExportExcel()">
      <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
      Export Excel
    </button>
    <button class="btn-export-pdf" onclick="doExportPDF()">
      <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
      PDF Grafik + Data
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
      Menampilkan data sesuai filter aktif, termasuk data normal. Untuk data anomali, atribut penyebab (Z-Score) ditampilkan sebagai interpretasi tambahan — bukan penentu status anomali.
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
  const cls = {
    high:'badge-sev badge-high',
    medium:'badge-sev badge-medium',
    low:'badge-sev badge-low',
    normal:'badge-sev badge-normal'
  }[lvl] || '';
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
  // Sekarang ikut filter aktif (fTipe/fSev) — bisa nampilkan data normal juga,
  // bukan cuma anomali seperti sebelumnya.
  const page = zPag.slice(filtered);
  document.getElementById('zBody').innerHTML = page.map(r => {
    // Data normal: tidak ada causes, tampilkan strip "—"
    if (!r.is_anomaly) {
      return `<tr>
        <td>${r.tahun}</td><td style="font-size:11px">${r.bulan}</td>
        <td><strong style="color:${Colors.getGol(r.golongan)}">${r.golongan}</strong></td>
        <td>${sevBadge('normal')}</td>
        <td style="font-size:12px">${Fmt.rp(r.rp)}</td>
        <td style="font-size:12px">${r.m3.toLocaleString('id-ID')}</td>
        <td style="font-size:12px">${Fmt.num(r.rp_per_m3)}</td>
        <td style="font-size:11px;color:var(--text-2)">—</td>
      </tr>`;
    }

    const causes = r.causes || [];
    const isCombo = causes.some(c => c.combination_note);
    const chips = causes.map(c => {
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
  }).join('') || '<tr><td colspan="8" style="text-align:center;padding:20px;color:var(--text-2)">Tidak ada data</td></tr>';

  zPag.renderControls(filtered, 'zPag', renderZTable);
}

// Helper: format causes jadi teks singkat "Main: m3 (+2.48) | Supporting: Rp (+2.39)"
function causesToText(r) {
  if (!r.is_anomaly || !r.causes || !r.causes.length) return '—';
  const labels = ['Main', 'Supporting'];
  return r.causes.map((c, i) => {
    const sign = c.zscore_raw >= 0 ? '+' : '';
    return `${labels[i] || `Cause${i+1}`}: ${c.atribut} (z=${sign}${(c.zscore_raw ?? c.zscore).toFixed?.(2) ?? c.zscore})`;
  }).join(' | ');
}

// ===== EXPORT EXCEL (full data sesuai filter lokal) =====
function doExportExcel() {
  if (!filtered.length) { showAlert('Tidak ada data.','warning'); return; }
  Loading.show('Membuat file Excel...');
  try {
    const rows = filtered.map(r => ({
      'Tahun'         : r.tahun,
      'Bulan'         : r.bulan,
      'Golongan'      : r.golongan,
      'Nama Golongan' : r.nama_golongan,
      'Rp'            : r.rp,
      'M3'            : r.m3,
      'Rp/M3'         : parseFloat(r.rp_per_m3.toFixed(4)),
      'Status'        : r.is_anomaly ? 'Anomali' : 'Normal',
      'Tingkat'       : r.is_anomaly ? (r.anomaly_level || '—') : 'normal',
      'Score'         : parseFloat(r.anomaly_score.toFixed(6)),
      'Penyebab (Z-Score)': causesToText(r),
    }));
    const ws = XLSX.utils.json_to_sheet(rows);
    ws['!cols'] = [
      {wch:6},{wch:12},{wch:9},{wch:28},{wch:18},{wch:10},{wch:12},{wch:10},{wch:10},{wch:14},{wch:40}
    ];
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Visual Analytics');
    XLSX.writeFile(wb, 'visual_analytics_d3_' + new Date().toISOString().slice(0,10) + '.xlsx');
    showAlert(`Excel berhasil dibuat — ${filtered.length} data (sesuai filter aktif).`, 'success');
  } catch(e) {
    showAlert('Export Excel gagal: ' + e.message, 'danger');
  } finally {
    Loading.hide();
  }
}

// ===== EXPORT PDF: Grafik + Tabel Data Lengkap (sesuai filter lokal) =====
async function doExportPDF() {
  if (!filtered.length) { showAlert('Tidak ada data.','warning'); return; }
  Loading.show('Membuat PDF grafik & data...');
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

    // ---- Halaman 1+: Grafik ----
    pdf.setFontSize(13); pdf.setFont('helvetica','bold');
    pdf.text('Visual Analytics — PDAM Anomaly Detection', 14, 12);
    pdf.setFontSize(9); pdf.setFont('helvetica','normal');
    pdf.text('Perumdam Tirta Kencana Samarinda', 14, 18);

    const banner = document.getElementById('filterBanner');
    const filterText = banner.style.display !== 'none' ? banner.textContent : `Semua data — ${filtered.length} baris`;
    pdf.setFontSize(8);
    pdf.text(filterText, 14, 24);
    pdf.text('Dicetak: '+new Date().toLocaleString('id-ID'), pdfW-14, 24, {align:'right'});

    const startY = 28;
    const availH = pdfH - startY - 4;
    const finalH = Math.min(imgH, availH);
    pdf.addImage(imgData, 'JPEG', 0, startY, pdfW, finalH);

    // Multi-page jika grafik terlalu panjang untuk 1 halaman
    if (imgH > availH) {
      let yOffset = availH;
      while (yOffset < imgH) {
        pdf.addPage();
        pdf.addImage(imgData, 'JPEG', 0, -(yOffset * pdfW / canvas.width), pdfW, imgH);
        yOffset += pdfH;
      }
    }

    // ---- Halaman berikutnya: Tabel Data Lengkap (SEMUA baris sesuai filter, auto-paginate) ----
    pdf.addPage();
    pdf.setFontSize(13); pdf.setFont('helvetica','bold');
    pdf.text('Tabel Data Lengkap', 14, 14);
    pdf.setFontSize(8); pdf.setFont('helvetica','normal');
    pdf.text(filterText + ` — total ${filtered.length} baris`, 14, 20);

    const bodyRows = filtered.map(r => [
      r.tahun,
      r.bulan,
      r.golongan,
      r.nama_golongan,
      Fmt.rp(r.rp),
      r.m3.toLocaleString('id-ID'),
      Fmt.num(r.rp_per_m3),
      r.is_anomaly ? 'Anomali' : 'Normal',
      r.is_anomaly ? (r.anomaly_level || '—') : 'normal',
      r.anomaly_score.toFixed(4),
      causesToText(r),
    ]);

    pdf.autoTable({
      startY: 24,
      head: [['Tahun','Bulan','Golongan','Nama Golongan','Rp','M3','Rp/M3','Status','Tingkat','Score','Penyebab (Z-Score)']],
      body: bodyRows,
      styles: { fontSize: 7, cellPadding: 1.5 },
      headStyles: { fillColor: [21, 101, 192], textColor: 255, fontStyle: 'bold' },
      alternateRowStyles: { fillColor: [245, 247, 250] },
      didParseCell: (data) => {
        if (data.section === 'body' && data.column.index === 7 && data.cell.raw === 'Anomali') {
          data.cell.styles.textColor = [198, 40, 40];
          data.cell.styles.fontStyle = 'bold';
        }
      },
      columnStyles: { 10: { cellWidth: 70 } },
      margin: { left: 10, right: 10 },
      theme: 'grid',
    });

    pdf.save('visual_analytics_pdam_' + new Date().toISOString().slice(0,10) + '.pdf');
    showAlert(`PDF berhasil dibuat — grafik + ${filtered.length} data (sesuai filter aktif).`, 'success');
  } catch(e) {
    showAlert('Export PDF gagal: '+e.message,'danger');
  } finally {
    Loading.hide();
  }
}

load();
</script>
</body>
</html>