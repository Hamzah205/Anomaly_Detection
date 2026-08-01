<?php require_once __DIR__ . '/../../bootstrap/bootstrap.php';
// No closing PHP tag — prevents "headers already sent"
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
<?php include __DIR__ . '/head_common.php'; ?>
  <title>Upload & Parameter — PDAM Anomaly Detection</title>
  <style>
    .hero{background:linear-gradient(135deg,#0D47A1,var(--primary) 50%,#1976D2);border-radius:16px;padding:28px 32px;margin-bottom:24px;display:flex;align-items:center;gap:20px;color:#fff;position:relative;overflow:hidden}
    .hero::before{content:'';position:absolute;right:-40px;top:-40px;width:200px;height:200px;border-radius:50%;background:rgba(255,255,255,.06)}
    .hero-logo{width:68px;height:68px;object-fit:contain;filter:drop-shadow(0 4px 10px rgba(0,0,0,.35));flex-shrink:0;z-index:1;background:#fff;border-radius:14px;padding:6px}
    .hero-text{z-index:1}.hero-text h2{font-size:20px;font-weight:800;margin-bottom:4px}
    .hero-text p{font-size:12px;opacity:.85;line-height:1.6}
    .sys-bar{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);padding:14px 18px;margin-bottom:20px;display:flex;flex-wrap:wrap;gap:10px;align-items:center}
    .chip{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;border:1px solid transparent}
    .chip-ok{background:rgba(46,125,50,.08);color:var(--success);border-color:rgba(46,125,50,.25)}
    .chip-err{background:rgba(229,57,53,.08);color:var(--danger);border-color:rgba(229,57,53,.25)}
    .chip-warn{background:rgba(245,127,23,.08);color:#E65100;border-color:rgba(245,127,23,.25)}
    .chip-info{background:rgba(21,101,192,.08);color:var(--primary);border-color:rgba(21,101,192,.2)}
    .chip-dot{width:7px;height:7px;border-radius:50%;background:currentColor}
    .steps{display:flex;border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden;margin-bottom:24px;background:var(--bg-card)}
    .step{flex:1;padding:13px 16px;display:flex;align-items:center;gap:9px;border-right:1px solid var(--border)}
    .step:last-child{border-right:none}
    .snum{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:12px;flex-shrink:0;background:var(--bg);color:var(--text-2);border:2px solid var(--border);transition:all .2s}
    .slbl{font-size:11px;font-weight:600;color:var(--text-2);transition:color .2s}
    .step.active .snum{background:var(--primary);color:#fff;border-color:var(--primary)}
    .step.active .slbl{color:var(--text-1)}
    .step.done .snum{background:var(--success);color:#fff;border-color:var(--success)}
    .step.done .slbl{color:var(--text-1)}
    .opt-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
    @media(max-width:580px){
      .opt-grid{grid-template-columns:1fr}
      .hero{flex-direction:column;text-align:center;padding:20px}
      .hero-logo{width:64px;height:64px;margin-bottom:10px;padding:5px;border-radius:12px}
      .hero-text h2{font-size:16px}
      .hero-text p{font-size:11px}
      .steps{flex-direction:row;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none}
      .steps::-webkit-scrollbar{display:none}
      .step{flex:0 0 auto;min-width:110px;padding:11px 14px;border-right:1px solid var(--border);border-bottom:none;flex-direction:column;text-align:center;gap:6px}
      .step:last-child{border-right:none}
      .sys-bar{flex-direction:row;flex-wrap:wrap;align-items:center;justify-content:flex-start;padding:12px 14px}
      .chip{font-size:10px;padding:3px 8px}
    }
    @media(max-width:768px){
      .main-content{padding:calc(var(--navbar-height) + 16px) 16px 24px}
      .card-pdam{padding:16px}
      .opt{padding:12px}
      .ot{font-size:12px}
      .od{font-size:10px}
      .drop{padding:30px 16px}
      .btn-row{flex-direction:column;width:100%}
      .btn-go,.btn-ghost{width:100%;justify-content:center}
    }
    .opt{border:2px solid var(--border);border-radius:10px;padding:14px 16px;cursor:pointer;transition:all .2s;display:flex;align-items:flex-start;gap:10px}
    .opt:hover{border-color:var(--brand-blue-light);transform:translateY(-1px)}
    .opt.sel{border-color:var(--primary);background:rgba(21,101,192,.05)}
    .opt input{accent-color:var(--primary);margin-top:3px;flex-shrink:0}
    .ot{font-size:13px;font-weight:700;margin-bottom:3px}
    .od{font-size:11px;color:var(--text-2);line-height:1.5}
    .drop{border:2px dashed var(--border);border-radius:14px;padding:40px 24px;text-align:center;cursor:pointer;transition:all .25s;background:var(--bg)}
    .drop:hover,.drop.drag{border-color:var(--primary);background:rgba(21,101,192,.03)}
    .fchip{display:flex;align-items:center;gap:12px;background:rgba(46,125,50,.07);border:1px solid rgba(46,125,50,.25);border-radius:10px;padding:12px 16px;margin-top:12px}
    .btn-go{background:var(--primary);color:#fff;border:none;border-radius:10px;padding:13px 32px;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:8px;transition:all .2s;font-family:inherit;box-shadow:0 4px 14px rgba(21,101,192,.3)}
    .btn-go:hover{background:var(--brand-blue-dark);transform:translateY(-1px)}
    .btn-go:disabled{opacity:.6;cursor:not-allowed;transform:none}
    .btn-ghost{background:var(--bg-card);color:var(--text-1);border:1px solid var(--border);border-radius:10px;padding:13px 24px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;transition:all .2s;font-family:inherit}
    .btn-ghost:hover{border-color:var(--primary);color:var(--primary)}
    code{background:rgba(0,0,0,.07);border-radius:4px;padding:2px 6px;font-size:11px;font-family:monospace}
    [data-theme=dark] code{background:rgba(255,255,255,.1)}
</style>
</head>
<body>
<?php include __DIR__ . '/navbar.php'; ?>
<div class="main-content">
<div style="max-width:780px;margin:0 auto">

  <!-- Hero -->
  <div class="hero">
    <img src="<?= url('/public/assets/logo.jpg') ?>" class="hero-logo" alt="Logo PDAM">
    <div class="hero-text">
      <h2>Sistem Deteksi Anomali PDAM</h2>
      <p>Perumdam Tirta Kencana Kota Samarinda<br>
         Isolation Forest + Z-Score Analysis &nbsp;|&nbsp; Dashboard 1 dari 4</p>
    </div>
  </div>

  <!-- System Check Bar -->
  <div class="sys-bar" id="sysBar">
    <span style="font-size:11px;font-weight:700;color:var(--text-2)">CEK SISTEM</span>
    <span class="chip chip-info"><span class="chip-dot"></span>Memuat...</span>
  </div>

  <!-- Steps -->
  <div class="steps">
    <div class="step active" id="s1"><div class="snum">1</div><div class="slbl">Upload Excel</div></div>
    <div class="step" id="s2"><div class="snum">2</div><div class="slbl">Contamination</div></div>
    <div class="step" id="s3"><div class="snum">3</div><div class="slbl">Mode Analisis</div></div>
    <div class="step" id="s4"><div class="snum">4</div><div class="slbl">Selesai</div></div>
  </div>

  <div id="alertBox"></div>

  <!-- Upload -->
  <div class="card-pdam" style="margin-bottom:20px">
    <div class="section-title">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
      Upload File Excel
    </div>
    <div class="drop" id="dropZone">
      <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color:var(--text-2);margin-bottom:10px">
        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/>
        <line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 12 15 15"/>
      </svg>
      <h3 style="font-size:15px;font-weight:700;margin-bottom:6px">Seret &amp; Lepas file di sini</h3>
      <p style="font-size:12px;color:var(--text-2)">atau klik untuk memilih file .xlsx / .xls (maks 20MB)</p>
      <input type="file" id="fileInput" accept=".xlsx,.xls" style="display:none">
    </div>
    <div id="fChip" style="display:none" class="fchip">
      <svg width="20" height="20" fill="none" stroke="var(--success)" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      <div style="flex:1">
        <div id="fName" style="font-weight:700;font-size:13px"></div>
        <div id="fSize" style="font-size:11px;color:var(--text-2)"></div>
      </div>
      <button onclick="clearFile()" style="background:none;border:none;cursor:pointer;color:var(--text-2)">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
  </div>

  <!-- Contamination -->
  <div class="card-pdam" style="margin-bottom:20px">
    <div class="section-title">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 010 14.14M4.93 4.93a10 10 0 000 14.14"/></svg>
      Parameter Contamination
    </div>
    <p style="font-size:12px;color:var(--text-2);margin-bottom:14px">Estimasi proporsi anomali dalam dataset. <strong>Auto</strong> direkomendasikan.</p>
    <div class="opt-grid" id="cOpts">
      <label class="opt sel"><input type="radio" name="cont" value="0.05" checked>
        <div><div class="ot">5% Kontaminasi (Default)</div><div class="od">Cocok untuk data PDAM — estimasi ~5% anomali, lebih sensitif terhadap penyimpangan kecil</div></div></label>
      <label class="opt"><input type="radio" name="cont" value="auto">
        <div><div class="ot">Auto</div><div class="od">Model menentukan threshold secara adaptif</div></div></label>
      <label class="opt"><input type="radio" name="cont" value="0.1">
        <div><div class="ot">10% Kontaminasi</div><div class="od">Anomali diperkirakan &le;10% data</div></div></label>
      <label class="opt"><input type="radio" name="cont" value="0.2">
        <div><div class="ot">20% Kontaminasi</div><div class="od">Variasi sedang, ~20% tidak normal</div></div></label>
      <label class="opt"><input type="radio" name="cont" value="0.3">
        <div><div class="ot">30% Kontaminasi</div><div class="od">Banyak pola tidak normal diperkirakan</div></div></label>
    </div>
  </div>

  <!-- Mode -->
  <div class="card-pdam" style="margin-bottom:24px">
    <div class="section-title">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
      Mode Analisis Default
    </div>
    <p style="font-size:12px;color:var(--text-2);margin-bottom:14px">Dapat diubah kapan saja dari Dashboard 2 (Global Filter).</p>
    <div style="display:flex;flex-direction:column;gap:10px" id="mOpts">
      <label class="opt sel"><input type="radio" name="mode" value="multi_tahun_semua_golongan" checked>
  <div><div class="ot">Mode Global (Default)</div>
    <div class="od">Analisis dilakukan terhadap seluruh data dari semua tahun dan semua golongan sekaligus tanpa pembatasan apapun.</div></div></label>
<label class="opt"><input type="radio" name="mode" value="multi_tahun_per_golongan">
  <div><div class="ot">Mode Golongan</div>
    <div class="od">Analisis dilakukan pada seluruh data historis (semua tahun) dalam golongan yang sama — setiap golongan dianalisis secara terpisah.</div></div></label>
  </div>

  <!-- Buttons -->
  <div style="display:flex;gap:12px;justify-content:flex-end;flex-wrap:wrap" class="btn-row">
    <button class="btn-ghost" onclick="location.href='<?= url('/dashboard2') ?>'">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
      Lihat Dashboard
    </button>
    <button class="btn-go" id="btnRun" onclick="runAnalysis()">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
      Jalankan Analisis
    </button>
  </div>

</div></div>

<script src="<?= url('/public/js/app.js') ?>"></script>
<script>
let selFile = null;
const dz = document.getElementById('dropZone');
const fi = document.getElementById('fileInput');

dz.onclick = () => fi.click();
dz.ondragover = e => { e.preventDefault(); dz.classList.add('drag'); };
dz.ondragleave = () => dz.classList.remove('drag');
dz.ondrop = e => { e.preventDefault(); dz.classList.remove('drag'); if(e.dataTransfer.files[0]) setF(e.dataTransfer.files[0]); };
fi.onchange = () => { if(fi.files[0]) setF(fi.files[0]); };

function setF(f) {
  selFile = f;
  document.getElementById('fName').textContent = f.name;
  document.getElementById('fSize').textContent = (f.size/1024).toFixed(1)+' KB';
  document.getElementById('fChip').style.display='flex';
  dz.style.display='none';
  st(1,'done'); st(2,'active');
}
function clearFile() {
  selFile=null; fi.value='';
  document.getElementById('fChip').style.display='none';
  dz.style.display='block';
  st(1,'active'); st(2,'');
}
function st(n,c) {
  const el=document.getElementById('s'+n);
  if(el) el.className='step'+(c?' '+c:'');
}
function alert2(msg,t='info') {
  const ic={success:'<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>',
    danger:'<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
    info:'<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
    warning:'<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>'};
  document.getElementById('alertBox').innerHTML=`<div class="alert-pdam alert-${t}" style="margin-bottom:16px">${ic[t]||''} ${msg}</div>`;
}

// Opt card selection
['cOpts','mOpts'].forEach(id => {
  document.getElementById(id).querySelectorAll('.opt').forEach(c => {
    c.addEventListener('click', () => {
      document.getElementById(id).querySelectorAll('.opt').forEach(x=>x.classList.remove('sel'));
      c.classList.add('sel');
    });
  });
});

async function checkSys() {
  try {
    const d = await (await fetch('<?= url('/api') ?>?action=check')).json();
    const bar = document.getElementById('sysBar');
    let h = '<span style="font-size:11px;font-weight:700;color:var(--text-2)">CEK SISTEM</span>';
    h += d.python
      ? `<span class="chip chip-ok"><span class="chip-dot"></span>Python: ${(d.python_ver||'OK').replace('Python ','')}</span>`
      : `<span class="chip chip-err"><span class="chip-dot"></span>Python: Tidak Ditemukan</span>`;
    h += d.deps_ok
      ? `<span class="chip chip-ok"><span class="chip-dot"></span>Libraries: OK</span>`
      : `<span class="chip chip-warn"><span class="chip-dot"></span>Libraries: Belum Install
          <button onclick="installDeps()" style="margin-left:6px;background:#E65100;color:#fff;border:none;border-radius:5px;padding:2px 8px;font-size:10px;cursor:pointer;font-weight:700">Install Sekarang</button>
         </span>`;
    h += `<span class="chip chip-info"><span class="chip-dot"></span>PHP ${d.php_ver}</span>`;
    if (d.has_file)   h += `<span class="chip chip-ok"><span class="chip-dot"></span>File Excel: Tersedia</span>`;
    if (d.has_result) h += `<span class="chip chip-ok"><span class="chip-dot"></span>Hasil Analisis: Ada</span>`;
    bar.innerHTML = h;

    if (!d.python) {
      alert2(`Python tidak ditemukan di path berikut. Buka <code>config.php</code> dan sesuaikan:<br>
        <code>C:\\Users\\Ilham\\AppData\\Local\\Python\\pythoncore-3.14-64\\python.exe</code><br>
        <code>C:\\Users\\Ilham\\AppData\\Local\\Python\\bin\\python.exe</code>`,'danger');
    } else if (!d.deps_ok) {
      alert2(`Library scikit-learn, scipy, pandas, openpyxl belum terinstall.<br>
        Klik tombol <strong>Install Sekarang</strong> di atas, atau jalankan di CMD:<br>
        <code>${d.python} -m pip install scikit-learn scipy pandas openpyxl</code>`,'warning');
    } else if (d.has_result) {
      alert2(`Hasil analisis sebelumnya tersedia.
        <a href="<?= url('/dashboard2') ?>" style="font-weight:700;background:var(--primary);color:#fff;padding:3px 12px;border-radius:var(--radius-sm);text-decoration:none;margin-left:6px">Buka Dashboard 2 &rarr;</a>`,'success');
      ['s1','s2','s3','s4'].forEach((s,i)=>{ const el=document.getElementById(s); if(el) el.className='step done'; });
    } else if (d.has_file) {
      alert2('File Excel sudah ada di server. Langsung klik <strong>Jalankan Analisis</strong>.','info');
      st(1,'done'); st(2,'active');
    }
  } catch(e) {
    document.getElementById('sysBar').innerHTML=`<span class="chip chip-err"><span class="chip-dot"></span>Server PHP tidak merespons — Pastikan XAMPP Apache sudah berjalan</span>`;
  }
}

async function installDeps() {
  Loading.show('Menginstall library Python (scikit-learn, scipy, pandas, openpyxl)...\nProses ini bisa memakan 1-3 menit.');
  try {
    const d = await (await fetch('<?= url('/api') ?>?action=install_deps')).json();
    Loading.hide();
    alert2('Instalasi selesai. Memuat ulang status...','success');
    setTimeout(checkSys, 1500);
  } catch(e) { Loading.hide(); alert2('Instalasi gagal: '+e.message,'danger'); }
}

async function runAnalysis() {
  const btn = document.getElementById('btnRun');
  if (selFile) {
    Loading.show('Mengupload file Excel...');
    const fd = new FormData();
    fd.append('action','upload'); fd.append('excel',selFile);
    try {
      const d = await (await fetch('<?= url('/api') ?>',{method:'POST',body:fd})).json();
      if (d.status!=='success') { Loading.hide(); alert2('Upload gagal: '+d.message,'danger'); return; }
    } catch(e) { Loading.hide(); alert2('Upload error: '+e.message,'danger'); return; }
  }

  Loading.show('Menjalankan Isolation Forest...\nProses analisis 528 baris data (~10–30 detik)');
  btn.disabled = true;
  st(3,'active');

  const fd = new FormData();
  fd.append('action','analyze');
  fd.append('contamination', document.querySelector('[name=cont]:checked')?.value||'auto');
  fd.append('mode', document.querySelector('[name=mode]:checked')?.value||'near_tahun_per_golongan');

  try {
    const d = await (await fetch('<?= url('/api') ?>',{method:'POST',body:fd})).json();
    if (d.status!=='success') {
      alert2(`Analisis gagal: ${d.message}<br><small>${d.hint||''}</small>`,'danger');
      st(3,''); return;
    }
    ['s1','s2','s3','s4'].forEach(s=>{ const el=document.getElementById(s); if(el) el.className='step done'; });
    alert2(`<strong>Analisis selesai!</strong> Ditemukan
      <strong style="color:var(--anomaly-color)">${d.result.anomali} anomali</strong>
      dari ${d.result.total} data.
      <a href="<?= url('/dashboard2') ?>" style="margin-left:8px;background:var(--primary);color:#fff;padding:4px 14px;border-radius:var(--radius-sm);text-decoration:none;font-weight:700;font-size:12px">Lihat Hasil &rarr;</a>`,
      'success');
  } catch(e) {
    alert2('Error: '+e.message,'danger'); st(3,'');
  } finally { Loading.hide(); btn.disabled=false; }
}

checkSys();
</script>
</body>
</html>
