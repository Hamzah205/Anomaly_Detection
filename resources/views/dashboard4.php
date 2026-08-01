  <?php require_once __DIR__ . '/../../bootstrap/bootstrap.php';
  // No closing PHP tag — prevents "headers already sent"
  ?>
  <!DOCTYPE html>
  <html lang="id" data-theme="light">
  <head>
  <?php include __DIR__ . '/head_common.php'; ?>
    <title>Detail Anomali — PDAM Anomaly Detection</title>
    <!-- SheetJS untuk export XLSX -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
      .sum3{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px}
      @media(max-width:600px){.sum3{grid-template-columns:repeat(3,1fr);gap:8px}}

      /* Severity badges */
      .badge-high{background:rgba(198,40,40,.12);color:var(--danger);border:1px solid rgba(198,40,40,.25);font-size:10px;font-weight:700;padding:2px 9px;border-radius:var(--radius-md);white-space:nowrap}
      .badge-med{background:rgba(251,140,0,.12);color:#E65100;border:1px solid rgba(251,140,0,.25);font-size:10px;font-weight:700;padding:2px 9px;border-radius:var(--radius-md);white-space:nowrap}
      .badge-low{background:rgba(249,168,37,.12);color:#F57F17;border:1px solid rgba(249,168,37,.25);font-size:10px;font-weight:700;padding:2px 9px;border-radius:var(--radius-md);white-space:nowrap}

      /* Detail panel */
      .det-wrap{background:rgba(21,101,192,.03);border:1px solid rgba(21,101,192,.12);border-radius:10px;padding:16px 18px;margin:4px 0 10px}
      .cause-block{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px 16px;margin-bottom:10px}
      .cause-block:last-child{margin-bottom:0}
      .cause-header{display:flex;align-items:center;gap:10px;margin-bottom:8px}
      .cause-attr{font-size:13px;font-weight:800;color:var(--text-1)}
      .cause-friendly{font-size:13px;color:var(--text-1);line-height:1.6;margin-bottom:8px}
      .cause-technical{font-size:11px;color:var(--text-2);line-height:1.6;background:rgba(0,0,0,.03);border-radius:var(--radius-sm);padding:8px 10px;font-family:inherit}
      [data-theme=dark] .cause-technical{background:rgba(255,255,255,.04)}
      .z-pill{font-size:12px;font-weight:800;padding:3px 10px;border-radius:20px}
      .z-high{background:rgba(198,40,40,.12);color:var(--danger)}
      .z-med{background:rgba(251,140,0,.12);color:#E65100}
      .z-low{background:rgba(249,168,37,.12);color:#F57F17}

      .expand-btn{background:none;border:none;cursor:pointer;color:var(--primary);font-size:11px;font-weight:700;display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:var(--radius-sm);transition:background .15s;font-family:inherit}
      .expand-btn:hover{background:rgba(21,101,192,.08)}
      .btn-act{display:flex;align-items:center;gap:6px;padding:9px 18px;border:none;border-radius:var(--radius-sm);font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .2s}
      .btn-act:hover{opacity:.88;transform:translateY(-1px)}
      .analysis-card{background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:14px 18px}
      .no-cause-note{font-size:12px;color:var(--text-2);font-style:italic;padding:6px 0}
  </style>
  </head>
  <body>
  <?php include __DIR__ . '/navbar.php'; ?>
  <div class="main-content">

    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px">
      <div>
        <div class="page-badge">Dashboard 4</div>
        <h1 style="font-size:22px;font-weight:800;margin-bottom:4px">Detail Anomali</h1>
        <p style="font-size:13px;color:var(--text-2)">Analisis mendalam setiap anomali dengan penjelasan Z-Score yang mudah dipahami.</p>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button class="btn-act" onclick="doPDF()" style="background:var(--danger);color:#fff">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
          Print PDF
        </button>
        <button class="btn-act" onclick="doXLSX()" style="background:var(--success);color:#fff">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
          Export Excel (.xlsx)
        </button>
      </div>
    </div>

    <!-- Filter -->
    <div class="filter-bar">
      <div class="filter-group">
        <label>Golongan</label>
        <select id="fGol"><option value="">Semua Golongan</option></select>
      </div>
      <div class="filter-group">
        <label>Tahun</label>
        <select id="fTahun"><option value="">Semua Tahun</option></select>
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
      <div class="filter-group">
        <label>Urutkan</label>
        <select id="fSort">
          <option value="score_desc">Score Tertinggi</option>
          <option value="sev_high">Severity: High Dulu</option>
          <option value="tahun_asc">Tahun (Lama–Baru)</option>
          <option value="gol_asc">Golongan A–Z</option>
        </select>
      </div>
      <button class="btn-run" onclick="applyFilter()">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
        Filter
      </button>
    </div>

    <div id="alertBox" style="margin-bottom:12px"></div>

    <!-- Summary -->
    <div class="sum3">
      <div class="card-stat">
        <div class="card-stat-icon" style="background:rgba(229,57,53,.1)">
          <svg width="22" height="22" fill="none" stroke="#E53935" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        </div>
        <div><div class="card-stat-label">Anomali (Filter Aktif)</div>
          <div class="card-stat-value" id="sAnom" style="color:var(--anomaly-color)">—</div>
          <div class="card-stat-sub" id="sAnomSub">—</div></div>
      </div>
      <div class="card-stat">
        <div class="card-stat-icon" style="background:rgba(198,40,40,.1)">
          <svg width="22" height="22" fill="none" stroke="var(--danger)" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
        </div>
        <div><div class="card-stat-label">High Severity</div>
          <div class="card-stat-value" id="sHigh" style="color:var(--danger)">—</div>
          <div class="card-stat-sub">Paling menyimpang</div></div>
      </div>
      <div class="card-stat">
        <div class="card-stat-icon" style="background:rgba(21,101,192,.1)">
          <svg width="22" height="22" fill="none" stroke="var(--primary)" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div><div class="card-stat-label">Tahun Terbanyak</div>
          <div class="card-stat-value" id="sTopTahun" style="color:var(--primary)">—</div>
          <div class="card-stat-sub" id="sTopTahunSub">—</div></div>
      </div>
    </div>

    <!-- Analisis Ringkasan -->
    <div class="card-pdam" style="margin-bottom:20px">
      <div class="section-title">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        Analisis Ringkasan Otomatis
      </div>
      <div id="analysisWrap" class="analysis-card">Muat data terlebih dahulu.</div>
    </div>

    <!-- Detail Table -->
    <div class="card-pdam">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px">
        <div class="section-title" style="margin-bottom:0;flex:1">Tabel Detail Anomali</div>
        <label style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;cursor:pointer;color:var(--text-2)">
          <input type="checkbox" id="chkExp" onchange="renderTable()"> Tampilkan semua penjelasan
        </label>
      </div>
      <div class="table-wrapper">
        <table class="tbl">
          <thead><tr>
            <th>#</th><th>Tahun</th><th>Bulan</th><th>Golongan</th>
            <th>Rp</th><th>M3</th><th>Rp/M3</th>
            <th>Score</th><th>Tingkat</th><th>Penyebab Utama</th><th>Aksi</th>
          </tr></thead>
          <tbody id="detBody"></tbody>
        </table>
      </div>
      <div class="pagination-wrap" id="detPag"><span class="pagination-info"></span><div class="pagination-btns"></div></div>
    </div>

  </div>
  <script src="<?= url('/public/js/app.js') ?>"></script>
  <script>
  let raw=null, allAnom=[], filtered=[];
  const pag = new Paginator(15);

  const SEV_ORDER = {high:3,medium:2,low:1};
  const SEV_LABEL = {high:'High',medium:'Medium',low:'Low'};

  function sevBadge(lvl) {
    const cls={high:'badge-sev badge-high',medium:'badge-med',low:'badge-sev badge-low'}[lvl]||'';
    return `<span class="${cls}">${SEV_LABEL[lvl]||lvl}</span>`;
  }

  async function load() {
    Loading.show('Memuat data anomali...');
    try {
      let d;
      
      // Cek apakah ada data dari history (dari sessionStorage)
      const historyData = sessionStorage.getItem('pdam_history_data');
      if (historyData) {
        // Gunakan data dari history yang sudah di-load
        d = { status: 'success', data: JSON.parse(historyData), source: 'history' };
        // Hapus dari sessionStorage agar tidak terus digunakan
        sessionStorage.removeItem('pdam_history_data');
        console.log('Data loaded from history sessionStorage');
      } else {
        // Ambil dari API (database atau session)
        d = await (await fetch('<?= url('/api') ?>?action=get_result')).json();
      }
      
      if(d.status!=='success'){ showAlert('Belum ada data. Jalankan analisis di Dashboard 1.','warning'); return; }
      raw = d.data;
      allAnom = raw.data.filter(r=>r.is_anomaly);
      filtered = [...allAnom];
      buildFilters();
      renderAll();
    } catch(e){ showAlert('Error: '+e.message,'danger'); }
    finally{ Loading.hide(); }
  }

  function buildFilters(){
    const gols=[...new Set(allAnom.map(r=>r.golongan))].sort();
    const tahuns=[...new Set(allAnom.map(r=>r.tahun))].sort();
    const gs=document.getElementById('fGol');
    gols.forEach(g=>{const nm=allAnom.find(r=>r.golongan===g)?.nama_golongan||g; gs.innerHTML+=`<option value="${g}">${g} — ${nm}</option>`;});
    const ts=document.getElementById('fTahun');
    tahuns.forEach(t=>{ ts.innerHTML+=`<option value="${t}">${t}</option>`; });
  }

  function applyFilter(){
    const g  =document.getElementById('fGol').value;
    const t  =document.getElementById('fTahun').value;
    const sev=document.getElementById('fSev').value;
    const srt=document.getElementById('fSort').value;
    filtered=allAnom.filter(r=>{
      if(g && r.golongan!==g) return false;
      if(t && r.tahun!==parseInt(t)) return false;
      if(sev && r.anomaly_level!==sev) return false;
      return true;
    });
    if(srt==='score_desc')  filtered.sort((a,b)=>a.anomaly_score-b.anomaly_score);
    else if(srt==='sev_high') filtered.sort((a,b)=>(SEV_ORDER[b.anomaly_level]||0)-(SEV_ORDER[a.anomaly_level]||0));
    else if(srt==='tahun_asc') filtered.sort((a,b)=>a.tahun-b.tahun||a.bulan_num-b.bulan_num);
    else if(srt==='gol_asc') filtered.sort((a,b)=>a.golongan.localeCompare(b.golongan));
    pag.current=1; renderAll();
  }

  function renderAll(){ renderStats(); renderAnalysis(); renderTable(); }

  function renderStats(){
    const n=filtered.length, total=raw?.meta?.total||0;
    document.getElementById('sAnom').textContent=n.toLocaleString('id-ID');
    document.getElementById('sAnomSub').textContent=`${total?(n/total*100).toFixed(1):0}% dari ${total} data`;
    const hi=filtered.filter(r=>r.anomaly_level==='high').length;
    document.getElementById('sHigh').textContent=hi.toLocaleString('id-ID');
    const byT={};filtered.forEach(r=>{byT[r.tahun]=(byT[r.tahun]||0)+1;});
    const topT=Object.entries(byT).sort((a,b)=>b[1]-a[1])[0];
    if(topT){ document.getElementById('sTopTahun').textContent=topT[0]; document.getElementById('sTopTahunSub').textContent=topT[1]+' kasus'; }
  }

  function renderAnalysis(){
    const d=filtered;
    if(!d.length){document.getElementById('analysisWrap').textContent='Tidak ada anomali untuk dianalisis.';return;}
    const byG={},byT={},attrC={};
    d.forEach(r=>{
      byG[r.golongan]=(byG[r.golongan]||0)+1;
      byT[r.tahun]=(byT[r.tahun]||0)+1;
      (r.causes||[]).forEach(c=>{attrC[c.atribut]=(attrC[c.atribut]||0)+1;});
    });
    const sG=Object.entries(byG).sort((a,b)=>b[1]-a[1]);
    const sT=Object.entries(byT).sort((a,b)=>b[1]-a[1]);
    const sA=Object.entries(attrC).filter(([,v])=>v>0).sort((a,b)=>b[1]-a[1]);
    const hiPct=(filtered.filter(r=>r.anomaly_level==='high').length/d.length*100).toFixed(1);
    document.getElementById('analysisWrap').innerHTML=`<div style="display:grid;gap:10px">
      <div class="insight-box danger">
        <strong>Golongan dengan anomali terbanyak:</strong>
        ${sG.slice(0,3).map(([g,c])=>`<strong style="color:${Colors.getGol(g)}">${g}</strong> (${c})`).join(', ')}
      </div>
      <div class="insight-box">
        <strong>Tahun tertinggi:</strong> ${sT.slice(0,3).map(([t,c])=>`${t} (${c} kasus)`).join(', ')}
        &nbsp;&nbsp;<strong>High severity:</strong> ${hiPct}% dari anomali filter ini
      </div>
      ${sA.length?`<div class="insight-box"><strong>Atribut penyebab dominan:</strong> ${sA.map(([a,c])=>`<strong>${a}</strong> (${c} data)`).join(' | ')}</div>`:''}
    </div>`;
  }

  function renderTable(){
    const expand=document.getElementById('chkExp').checked;
    const page=pag.slice(filtered);
    const base=(pag.current-1)*pag.pageSize;
    let html='';
    page.forEach((r,i)=>{
      const num=base+i+1;
      const causes=r.causes||[];
      const topCause=causes[0];
      const topChip=topCause?`<span class="${topCause.zscore>=3?'zscore-high':topCause.zscore>=2?'zscore-med':'zscore-low'} zscore-tag">${topCause.atribut} z=${topCause.zscore_raw?.toFixed(2)||topCause.zscore}</span>`:'<span style="font-size:10px;color:var(--text-2)">—</span>';
      html+=`<tr class="row-anomaly" id="r${num}">
        <td style="font-size:10px;color:var(--text-2)">${num}</td>
        <td>${r.tahun}</td>
        <td style="font-size:11px">${r.bulan}</td>
        <td><strong style="color:${Colors.getGol(r.golongan)}">${r.golongan}</strong>
            <div style="font-size:10px;color:var(--text-2)">${r.nama_golongan}</div></td>
        <td style="font-size:12px;white-space:nowrap">${Fmt.rp(r.rp)}</td>
        <td style="font-size:12px">${r.m3.toLocaleString('id-ID')}</td>
        <td style="font-size:12px">${Fmt.num(r.rp_per_m3)}</td>
        <td><span style="font-size:11px;font-weight:700;color:var(--score-color)">${r.anomaly_score.toFixed(4)}</span></td>
        <td>${sevBadge(r.anomaly_level)}</td>
        <td>${topChip}</td>
        <td>
          <button class="expand-btn" id="ebtn${num}"
            onclick="toggleDetail(${num}, this)">
            <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
            Detail
          </button>
        </td>
      </tr>`;
      if(expand && causes.length>0){
        html+=`<tr class="row-anomaly"><td colspan="11" style="padding:0 12px 12px">${buildDetailHTML(r)}</td></tr>`;
      }
    });
    document.getElementById('detBody').innerHTML=html||'<tr><td colspan="11" style="text-align:center;padding:20px;color:var(--text-2)">Tidak ada anomali</td></tr>';
    pag.renderControls(filtered,'detPag',renderTable);
  }

  // ===== BANGUN HTML DETAIL Z-SCORE (user-friendly) =====
  function buildDetailHTML(r){
    const causes=r.causes||[];
    if(!causes.length){
      return `<div class="det-wrap"><p class="no-cause-note">
        Belum ada data interpretasi Z-Score untuk baris ini.
        Status anomali ditentukan oleh Isolation Forest — bukan Z-Score.
      </p></div>`;
    }

    // Cek apakah ini anomali kombinasi (semua |Z| < 2.0)
    const isCombo = causes.every(c => c.zscore < 2.0) || (causes[0] && causes[0].combination_note);

    const mainCause=causes[0];
    let html=`<div class="det-wrap">
      <div style="font-size:12px;font-weight:700;color:var(--primary);margin-bottom:10px;display:flex;align-items:center;gap:8px">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        Analisis Penyebab Anomali — ${r.golongan} | ${r.bulan} ${r.tahun} | ${sevBadge(r.anomaly_level)}
      </div>`;

    // Banner kombinasi — tampilkan jika tidak ada atribut yang sangat ekstrem
    if(isCombo){
      html+=`<div style="margin-bottom:10px;padding:9px 12px;background:rgba(8,145,178,.07);border:1px solid rgba(8,145,178,.25);border-radius:var(--radius-sm);font-size:12px;color:#0369a1">
        <strong>⚠ Anomali Kombinasi:</strong>
        Tidak ada satu atribut yang sangat ekstrem (|Z| &lt; 2.0 untuk semua atribut).
        Isolation Forest mendeteksi anomali ini dari <strong>kombinasi pola keseluruhan</strong> yang tidak lazim.
        Z-Score di bawah tetap menunjukkan atribut dengan penyimpangan <em>relatif terbesar</em>.
      </div>`;
    }

    causes.forEach((c,idx)=>{
      const zAbs=c.zscore;
      const zRaw=c.zscore_raw||c.zscore;
      const zCls=zAbs>=3?'z-high':zAbs>=2?'z-med':'z-low';
      const sevZ=c.severity_z||'sedang';
      const isMain=idx===0;

      html+=`<div class="cause-block">
        <div class="cause-header">
          ${isMain?'<span style="font-size:10px;background:var(--primary);color:#fff;padding:1px 8px;border-radius:10px;font-weight:700">Penyebab Utama</span>':'<span style="font-size:10px;color:var(--text-2);font-weight:600">Penyebab Tambahan</span>'}
          <span class="cause-attr">${c.attr_full||c.atribut}</span>
          <span class="z-pill ${zCls}">Z = ${zRaw>=0?'+':''}${(typeof zRaw==='number'?zRaw:zAbs).toFixed(3)}</span>
          <span style="font-size:10px;color:var(--text-2)">Anomali ${sevZ}</span>
        </div>
        <div class="cause-friendly">${c.penjelasan||c.keterangan}</div>
        <div class="cause-technical">
          <strong style="color:var(--text-1)">Penjelasan Teknis:</strong><br>
          ${c.teknis||`Nilai berada ${c.arah||''} rata-rata dengan Z = (X − μ) / σ = (${c.nilai?.toLocaleString('id-ID',{maximumFractionDigits:2})} − ${c.rata_rata?.toLocaleString('id-ID',{maximumFractionDigits:2})}) / ${c.std?.toLocaleString('id-ID',{maximumFractionDigits:2})} = ${(typeof zRaw==='number'?zRaw:zAbs).toFixed(3)}`}
        </div>
        <div style="margin-top:8px;display:flex;gap:16px;font-size:11px;flex-wrap:wrap">
          <span>Nilai aktual: <strong>${c.nilai?.toLocaleString('id-ID',{maximumFractionDigits:2})}</strong></span>
          <span>Rata-rata grup: <strong>${c.rata_rata?.toLocaleString('id-ID',{maximumFractionDigits:2})}</strong></span>
          <span>Std. deviasi: <strong>${c.std?.toLocaleString('id-ID',{maximumFractionDigits:2})}</strong></span>
          <span style="color:${zAbs>=3?'var(--danger)':zAbs>=2?'#FB8C00':'#F9A825'}">|Z| = ${zAbs.toFixed(3)} → ${zAbs>=3?'Penyimpangan tinggi (|Z| &ge; 3)':zAbs>=2?'Penyimpangan sedang (2 &le; |Z| &lt; 3)':'Penyimpangan ringan (|Z| &lt; 2)'}</span>
        </div>
      </div>`;
    });

    // Interpretasi keseluruhan
    html+=`<div style="margin-top:10px;padding:10px 12px;background:rgba(21,101,192,.05);border-radius:var(--radius-sm);font-size:12px;color:var(--text-2)">
      <strong style="color:var(--text-1)">Catatan Interpretasi:</strong>
      Status anomali ditentukan oleh <strong>Isolation Forest</strong> (Anomaly Score = <strong>${r.anomaly_score.toFixed(6)}</strong>).
      Semakin negatif nilainya, semakin jauh data dari pola normal.
      <strong>Z-Score hanya untuk interpretasi</strong> — menjelaskan atribut mana yang paling menyimpang, bukan penentu anomali.
      Rumus: <strong>Z = (X &minus; &mu;) / &sigma;</strong>
      di mana X = nilai data, &mu; = rata-rata grup (golongan &amp; tahun), &sigma; = standar deviasi grup.
    </div>
    </div>`;
    return html;
  }

  let openRows={};
  function toggleDetail(num, btn){
    const existing=document.getElementById('det'+num);
    if(existing){
      existing.remove();
      btn.innerHTML='<svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg> Detail';
      delete openRows[num]; return;
    }
    const row=document.getElementById('r'+num);
    if(!row) return;
    const r=filtered[(pag.current-1)*pag.pageSize + num - (pag.current-1)*pag.pageSize - 1];
    const idx=parseInt(num)-1-(pag.current-1)*pag.pageSize;
    const record=pag.slice(filtered)[idx];
    if(!record) return;
    const tr=document.createElement('tr');
    tr.id='det'+num; tr.className='row-anomaly';
    tr.innerHTML=`<td colspan="11" style="padding:0 12px 12px">${buildDetailHTML(record)}</td>`;
    row.insertAdjacentElement('afterend',tr);
    btn.innerHTML='<svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg> Tutup';
    openRows[num]=true;
  }

  // ===== EXPORT XLSX (SheetJS) — diperbaiki =====
  function doXLSX() {
    if (!filtered.length) { showAlert('Tidak ada data untuk diekspor.', 'warning'); return; }
    Loading.show('Membuat file Excel...');

    try {
      const wb = XLSX.utils.book_new();

      // ── Sheet 1: Data Anomali ──
      const dataRows = filtered.map(r => {
        const c0 = r.causes && r.causes.length ? r.causes[0] : null;
        const allCauses = (r.causes || []).map(c =>
          `${c.atribut} (z=${typeof c.zscore_raw === 'number' ? c.zscore_raw.toFixed(3) : c.zscore})`
        ).join('; ');
        const penjelasan = c0 ? c0.penjelasan : 'Anomali terdeteksi berdasarkan pola kombinasi (Isolation Forest)';
        const teknis     = c0 ? c0.teknis     : '-';
        return {
          'Tahun'              : r.tahun,
          'Bulan'              : r.bulan,
          'Golongan'           : r.golongan,
          'Nama Golongan'      : r.nama_golongan,
          'Rp'                 : r.rp,
          'm3'                 : r.m3,
          'Rp/m3'              : parseFloat(r.rp_per_m3.toFixed(4)),
          'Anomaly Score'      : parseFloat(r.anomaly_score.toFixed(6)),
          'Tingkat Anomali'    : SEV_LABEL[r.anomaly_level] || r.anomaly_level,
          'Atribut Penyebab'   : allCauses || '-',
          'Penjelasan Singkat' : penjelasan,
          'Penjelasan Teknis'  : teknis,
        };
      });

      const ws1 = XLSX.utils.json_to_sheet(dataRows);

      // Set lebar kolom
      ws1['!cols'] = [
        {wch:6},   // Tahun
        {wch:12},  // Bulan
        {wch:9},   // Golongan
        {wch:28},  // Nama Golongan
        {wch:18},  // Rp
        {wch:10},  // m3
        {wch:12},  // Rp/m3
        {wch:15},  // Anomaly Score
        {wch:14},  // Tingkat
        {wch:35},  // Atribut
        {wch:70},  // Penjelasan
        {wch:90},  // Teknis
      ];

      XLSX.utils.book_append_sheet(wb, ws1, 'Detail Anomali');

      // ── Sheet 2: Ringkasan per Golongan ──
      const byGol = {};
      filtered.forEach(r => {
        if (!byGol[r.golongan]) byGol[r.golongan] = {nama:r.nama_golongan, total:0, high:0, medium:0, low:0};
        byGol[r.golongan].total++;
        if (byGol[r.golongan][r.anomaly_level] !== undefined) byGol[r.golongan][r.anomaly_level]++;
      });
      const sumRows = Object.entries(byGol).sort((a,b)=>b[1].total-a[1].total).map(([gol, v]) => ({
        'Golongan'    : gol,
        'Nama'        : v.nama,
        'Total Anomali': v.total,
        'High'        : v.high,
        'Medium'      : v.medium,
        'Low'         : v.low,
      }));
      const ws2 = XLSX.utils.json_to_sheet(sumRows);
      ws2['!cols'] = [{wch:10},{wch:28},{wch:14},{wch:8},{wch:10},{wch:8}];
      XLSX.utils.book_append_sheet(wb, ws2, 'Ringkasan Golongan');

      // ── Sheet 3: Ringkasan per Tahun ──
      const byTahun = {};
      filtered.forEach(r => {
        if (!byTahun[r.tahun]) byTahun[r.tahun] = {total:0, high:0, medium:0, low:0};
        byTahun[r.tahun].total++;
        if (byTahun[r.tahun][r.anomaly_level] !== undefined) byTahun[r.tahun][r.anomaly_level]++;
      });
      const tahunRows = Object.entries(byTahun).sort((a,b)=>a[0]-b[0]).map(([tahun, v]) => ({
        'Tahun'        : parseInt(tahun),
        'Total Anomali': v.total,
        'High'         : v.high,
        'Medium'       : v.medium,
        'Low'          : v.low,
      }));
      const ws3 = XLSX.utils.json_to_sheet(tahunRows);
      ws3['!cols'] = [{wch:8},{wch:14},{wch:8},{wch:10},{wch:8}];
      XLSX.utils.book_append_sheet(wb, ws3, 'Ringkasan Tahun');

      // ── Info sheet ──
      const infoData = [
        ['Sistem', 'PDAM Anomaly Detection — Perumdam Tirta Kencana Samarinda'],
        ['Diekspor', new Date().toLocaleString('id-ID')],
        ['Total Anomali', filtered.length],
        ['Filter Aktif', document.getElementById('fGol').value || 'Semua Golongan'],
        ['Filter Tahun', document.getElementById('fTahun').value || 'Semua Tahun'],
        ['Filter Tingkat', document.getElementById('fSev').value || 'Semua Tingkat'],
        ['Keterangan Z-Score', '|Z| >= 3: Anomali Tinggi | 2 <= |Z| < 3: Anomali Sedang | |Z| < 2: Normal'],
        ['Keterangan Tingkat', 'High = sangat menyimpang | Medium = cukup menyimpang | Low = sedikit menyimpang'],
      ];
      const ws4 = XLSX.utils.aoa_to_sheet(infoData);
      ws4['!cols'] = [{wch:20},{wch:70}];
      XLSX.utils.book_append_sheet(wb, ws4, 'Informasi');

      XLSX.writeFile(wb, 'detail_anomali_pdam_' + new Date().toISOString().slice(0,10) + '.xlsx');
    } catch(e) {
      showAlert('Export Excel gagal: ' + e.message, 'danger');
    } finally {
      Loading.hide();
    }
  }

  // ===== PRINT PDF =====
  function doPDF(){
    const w=window.open('','_blank');
    const rows=filtered.map((r,i)=>{
      const c=r.causes&&r.causes.length?r.causes[0]:null;
      return `<tr style="${i%2?'background:#fafafa':''}">
        <td>${i+1}</td><td>${r.tahun}</td><td>${r.bulan}</td>
        <td>${r.golongan}</td><td style="font-size:10px">${r.nama_golongan}</td>
        <td>Rp ${r.rp.toLocaleString('id-ID')}</td>
        <td>${r.m3.toLocaleString('id-ID')}</td>
        <td>${r.rp_per_m3.toFixed(2)}</td>
        <td>${r.anomaly_score.toFixed(4)}</td>
        <td><strong>${SEV_LABEL[r.anomaly_level]||r.anomaly_level}</strong></td>
        <td style="font-size:9px">${c?c.penjelasan:'—'}</td>
      </tr>`;
    }).join('');
    w.document.write(`<!DOCTYPE html><html><head><title>Detail Anomali PDAM Samarinda</title>
    <style>body{font-family:Arial,sans-serif;font-size:11px;padding:20px}
    h1{color:var(--primary);font-size:14px;margin-bottom:4px}p{font-size:10px;color:#666;margin-bottom:10px}
    table{width:100%;border-collapse:collapse}th{background:var(--primary);color:#fff;padding:6px 7px;text-align:left;font-size:10px}
    td{padding:5px 7px;border-bottom:1px solid #eee;vertical-align:top}
    @media print{@page{size:landscape}}</style></head><body>
    <h1>Laporan Detail Anomali — Perumdam Tirta Kencana Samarinda</h1>
    <p>Dicetak: ${new Date().toLocaleString('id-ID')} | Total: ${filtered.length} anomali</p>
    <table><thead><tr><th>#</th><th>Tahun</th><th>Bulan</th><th>Gol</th><th>Nama</th>
    <th>Rp</th><th>M3</th><th>Rp/M3</th><th>Score</th><th>Tingkat</th><th>Penjelasan</th></tr></thead>
    <tbody>${rows}</tbody></table></body></html>`);
    w.document.close(); w.print();
  }

  load();
  </script>
  </body>
  </html>

