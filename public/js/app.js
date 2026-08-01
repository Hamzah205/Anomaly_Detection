/**
 * PDAM Anomaly System - Shared JS Utilities v2.0
 * Bug fixes:
 *  - Theme persistence & sync across pages
 *  - Paginator edge-case fix (empty array crash)
 *  - showAlert auto-close & accessible icon
 *  - Colors.getGol consistent palette
 *  - API.call handles non-JSON responses gracefully
 */

// ===== THEME =====
const Theme = {
  init() {
    const saved = localStorage.getItem('pdam_theme') || 'light';
    this.apply(saved);
  },
  toggle() {
    const curr = document.documentElement.getAttribute('data-theme') || 'light';
    const next = curr === 'dark' ? 'light' : 'dark';
    this.apply(next);
    localStorage.setItem('pdam_theme', next);
  },
  apply(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    const btn = document.getElementById('themeToggle');
    if (btn) {
      btn.innerHTML = theme === 'dark'
        ? `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg> Terang`
        : `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg> Gelap`;
    }
  }
};

// ===== SIDEBAR (mobile) =====
const Sidebar = {
  open() {
    document.getElementById('sidebarDrawer')?.classList.add('open');
    const ov = document.getElementById('sidebarOverlay');
    if (ov) ov.style.display = 'block';
  },
  close() {
    document.getElementById('sidebarDrawer')?.classList.remove('open');
    const ov = document.getElementById('sidebarOverlay');
    if (ov) ov.style.display = 'none';
  }
};

// ===== LOADING =====
const Loading = {
  show(msg = 'Memproses data...') {
    let el = document.getElementById('loadingOverlay');
    if (!el) {
      el = document.createElement('div');
      el.id = 'loadingOverlay';
      el.className = 'loading-overlay';
      el.innerHTML = `
        <div class="loading-box">
          <div class="spinner"></div>
          <div style="font-family:'Outfit',sans-serif;font-weight:700;font-size:16px;margin-bottom:6px;color:var(--text-1)">${msg}</div>
          <div style="font-size:12px;color:var(--text-2)">Mohon tunggu...</div>
        </div>`;
      document.body.appendChild(el);
    } else {
      el.querySelector('.loading-box div:nth-child(2)').textContent = msg;
      el.style.display = 'flex';
    }
  },
  hide() {
    const el = document.getElementById('loadingOverlay');
    if (el) el.style.display = 'none';
  }
};

// ===== API =====
const API = {
  base: (window.BASE_URL || '') + '/api',
  async call(action, data = {}, method = 'GET') {
    const url = method === 'GET' ? `${this.base}?action=${action}` : this.base;
    const opts = { method };
    if (method === 'POST') {
      if (data instanceof FormData) {
        data.append('action', action);
        opts.body = data;
      } else {
        const fd = new FormData();
        fd.append('action', action);
        Object.entries(data).forEach(([k, v]) => fd.append(k, v));
        opts.body = fd;
      }
    }
    const resp = await fetch(url, opts);
    // BUG FIX: handle non-JSON response gracefully
    const text = await resp.text();
    try {
      return JSON.parse(text);
    } catch {
      console.error('API non-JSON response:', text.substring(0, 300));
      return { status: 'error', message: 'Server error: response bukan JSON. Cek log PHP.' };
    }
  },
  async getResult() { return this.call('get_result'); },
  async analyze(params) {
    const fd = new FormData();
    Object.entries(params).forEach(([k, v]) => { if (v !== null && v !== '') fd.append(k, v); });
    return this.call('analyze', fd, 'POST');
  }
};

// ===== EXPORT (CSV fallback untuk dashboard2) =====
const Export = {
  toExcel(rows, filename = 'export') {
    if (!rows || !rows.length) { showAlert('Tidak ada data untuk diekspor.', 'warning'); return; }
    const headers = Object.keys(rows[0]);
    const escape = (v) => {
      const s = v == null ? '' : String(v);
      if (/[",\n]/.test(s)) return '"' + s.replace(/"/g, '""') + '"';
      return s;
    };
    const csv = [
      headers.map(h => escape(h)).join(','),
      ...rows.map(r => headers.map(h => escape(r[h])).join(','))
    ].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename + '.csv';
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(link.href);
  }
};

// ===== FORMAT =====
const Fmt = {
  rp(val) {
    if (val == null || isNaN(val)) return 'Rp —';
    if (val >= 1e12) return 'Rp ' + (val/1e12).toFixed(2) + ' T';
    if (val >= 1e9)  return 'Rp ' + (val/1e9).toFixed(2)  + ' M';
    if (val >= 1e6)  return 'Rp ' + (val/1e6).toFixed(2)  + ' Jt';
    return 'Rp ' + Math.round(val).toLocaleString('id-ID');
  },
  num(val, dec = 2) {
    if (val == null || isNaN(val)) return '—';
    return Number(val).toLocaleString('id-ID', { minimumFractionDigits: dec, maximumFractionDigits: dec });
  },
  pct(val) {
    if (val == null || isNaN(val)) return '—%';
    return Number(val).toFixed(1) + '%';
  }
};

// ===== COLORS =====
const Colors = {
  _pal: ['#1D6FCF','#059669','#D97706','#7C3AED','#E11D48','#0891B2','#EA580C','#65A30D','#DB2777','#0D9488'],
  _map: {},
  getGol(g) {
    if (!this._map[g]) {
      const keys = Object.keys(this._map);
      this._map[g] = this._pal[keys.length % this._pal.length];
    }
    return this._map[g];
  }
};

// ===== SHOW ALERT =====
// BUG FIX: was missing auto-close timer & icon mapping
function showAlert(msg, type = 'info', autoClose = 6000) {
  const box = document.getElementById('alertBox');
  if (!box) return;

  const icons = {
    danger:  '<path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
    warning: '<path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
    success: '<polyline points="20 6 9 17 4 12"/>',
    info:    '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'
  };

  const el = document.createElement('div');
  el.className = `alert alert-${type}`;
  el.innerHTML = `
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px">${icons[type]||icons.info}</svg>
    <span style="flex:1">${msg}</span>
    <button onclick="this.closest('.alert').remove()" style="background:none;border:none;cursor:pointer;color:inherit;opacity:0.6;padding:0;line-height:0;flex-shrink:0">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>`;

  box.innerHTML = '';
  box.appendChild(el);

  if (autoClose > 0) {
    setTimeout(() => el.remove(), autoClose);
  }
}

// ===== PAGINATOR =====
// BUG FIX: crash when array is empty; page count 0 edge case
class Paginator {
  constructor(pageSize = 20) {
    this.pageSize = pageSize;
    this.current  = 1;
  }
  totalPages(arr) {
    if (!arr || arr.length === 0) return 1;
    return Math.max(1, Math.ceil(arr.length / this.pageSize));
  }
  slice(arr) {
    if (!arr || arr.length === 0) return [];
    const total = this.totalPages(arr);
    if (this.current > total) this.current = total; // clamp
    const start = (this.current - 1) * this.pageSize;
    return arr.slice(start, start + this.pageSize);
  }
  render(arr, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const total = this.totalPages(arr);
    const cur   = this.current;
    let html = '';
    const btn = (label, pg, disabled = false, active = false) =>
      `<button class="pg-btn${active?' active':''}" onclick="pag.current=${pg};renderTable()" ${disabled?'disabled':''}>${label}</button>`;

    html += btn('‹', cur - 1, cur <= 1);
    // Show at most 5 page buttons
    let start = Math.max(1, cur - 2);
    let end   = Math.min(total, start + 4);
    if (end - start < 4) start = Math.max(1, end - 4);
    for (let i = start; i <= end; i++) html += btn(i, i, false, i === cur);
    html += btn('›', cur + 1, cur >= total);

    const paginationBtns = container.querySelector('.pagination-btns');
    const paginationInfo = container.querySelector('.pagination-info');
    if (paginationBtns) paginationBtns.innerHTML = html;
    if (paginationInfo) {
      const from = arr.length ? (cur-1)*this.pageSize+1 : 0;
      const to   = Math.min(cur*this.pageSize, arr.length);
      paginationInfo.textContent = arr.length ? `Menampilkan ${from}–${to} dari ${arr.length} data` : 'Tidak ada data';
    }
  }

  /**
   * renderControls(arr, containerId, callback)
   * Versi generik dari render() — menerima callback sehingga
   * setiap paginator bisa memanggil fungsi render yang berbeda.
   * Digunakan oleh dashboard3, dashboard4, history.
   */
  renderControls(arr, containerId, callback) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const total = this.totalPages(arr);
    const cur   = this.current;

    // Simpan referensi paginator & callback agar onclick bisa memanggil kembali
    if (!Paginator._registry) Paginator._registry = {};
    Paginator._registry[containerId] = { pag: this, cb: callback, arr };

    const btn = (label, pg, disabled = false, active = false) => {
      const escapedId = containerId.replace(/[^a-zA-Z0-9_]/g, '_');
      return `<button class="pg-btn${active ? ' active' : ''}"
        onclick="Paginator._go('${containerId}',${pg})"
        ${disabled ? 'disabled' : ''}>${label}</button>`;
    };

    let html = '';
    html += btn('‹', cur - 1, cur <= 1);
    let start = Math.max(1, cur - 2);
    let end   = Math.min(total, start + 4);
    if (end - start < 4) start = Math.max(1, end - 4);
    for (let i = start; i <= end; i++) html += btn(i, i, false, i === cur);
    html += btn('›', cur + 1, cur >= total);

    const paginationBtns = container.querySelector('.pagination-btns');
    const paginationInfo = container.querySelector('.pagination-info');
    if (paginationBtns) paginationBtns.innerHTML = html;
    if (paginationInfo) {
      const from = arr.length ? (cur - 1) * this.pageSize + 1 : 0;
      const to   = Math.min(cur * this.pageSize, arr.length);
      paginationInfo.textContent = arr.length
        ? `Menampilkan ${from}–${to} dari ${arr.length} data`
        : 'Tidak ada data';
    }
  }

  /** Dipanggil oleh tombol pagination yang di-generate renderControls */
  static _go(containerId, page) {
    const reg = Paginator._registry && Paginator._registry[containerId];
    if (!reg) return;
    reg.pag.current = page;
    reg.cb();
  }
}

// ===== CHART ZOOM (klik grafik untuk membesar) =====
const ChartZoom = {
  open(canvas) {
    if (typeof Chart === 'undefined') return;
    const src = Chart.getChart(canvas);
    if (!src) return;

    const overlay = document.createElement('div');
    overlay.className = 'chart-zoom-overlay';
    overlay.innerHTML = `
      <div class="chart-zoom-box">
        <button class="chart-zoom-close" aria-label="Tutup">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <div class="chart-zoom-canvas"><canvas id="zoomCanvas"></canvas></div>
      </div>`;
    document.body.appendChild(overlay);

    const zCanvas = overlay.querySelector('#zoomCanvas');
    const zoom = new Chart(zCanvas.getContext('2d'), {
      type: src.config.type,
      data: src.config.data,
      options: Object.assign({}, src.config.options, {
        responsive: true, maintainAspectRatio: false, animation: false
      })
    });

    const close = () => { try { zoom.destroy(); } catch(e) {} overlay.remove(); };
    overlay.querySelector('.chart-zoom-close').addEventListener('click', close);
    overlay.addEventListener('click', e => { if (e.target === overlay) close(); });
    document.addEventListener('keydown', function esc(e) { if (e.key === 'Escape') { close(); document.removeEventListener('keydown', esc); } });
  }
};

// ===== INIT =====
document.addEventListener('DOMContentLoaded', () => {
  Theme.init();

  // Hamburger
  document.getElementById('hamburgerBtn')?.addEventListener('click', () => Sidebar.open());
  document.getElementById('sidebarOverlay')?.addEventListener('click', () => Sidebar.close());
  document.getElementById('sidebarClose')?.addEventListener('click', () => Sidebar.close());

  // Theme toggle
  document.getElementById('themeToggle')?.addEventListener('click', () => Theme.toggle());

  // Navbar user dropdown
  const badge = document.getElementById('nbBadge');
  const drop  = document.getElementById('nbDrop');
  if (badge && drop) {
    badge.addEventListener('click', (e) => { e.stopPropagation(); drop.classList.toggle('open'); });
    document.addEventListener('click', () => drop.classList.remove('open'));
  }

  // Chart zoom on click (delegated)
  document.addEventListener('click', (e) => {
    const canvas = e.target.closest('canvas');
    if (canvas && !canvas.closest('.chart-zoom-overlay')) ChartZoom.open(canvas);
  });
});
