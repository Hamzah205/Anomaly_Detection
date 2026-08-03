# PDAM Anomaly Detection System

> Sistem Deteksi Anomali PDAM menggunakan **Isolation Forest** + **Z-Score Analysis**.
> Instansi: **Perumdam Tirta Kencana Kota Samarinda**

---

## Tentang Proyek

Aplikasi web untuk mendeteksi data tagihan/pemakaian pelanggan PDAM yang tidak wajar secara
otomatis menggunakan Machine Learning. Dirancang agar petugas dapat menemukan anomali tanpa
harus memeriksa ribuan baris Excel secara manual.

- **Algoritma:** Isolation Forest (Python/scikit-learn) + Z-Score Analysis
- **Fitur Kunci:** Severity level (High/Medium/Low), 4 mode analisis, multi-user safe,
  guest & user mode, auto-cleanup file guest, riwayat analisis.

---

## Arsitektur & Stack

| Layer | Teknologi |
|-------|-----------|
| Web Server | Apache (Laragon / XAMPP) |
| Backend | Vanilla PHP ≥ 8.0 (tanpa framework, include-based) |
| Database | PostgreSQL (default) / MySQL |
| ML Engine | Python ≥ 3.9 (scikit-learn, pandas, openpyxl, scipy) |
| Frontend | HTML + CSS + JavaScript + Chart.js + SheetJS |

**Struktur berpola Laravel-like** (bukan framework Laravel): `public/` sebagai document root,
controller di `app/Http/Controllers/`, model penghubung di `app/Models/`, view di
`resources/views/`, API di `routes/`, config di `config/`.

---

## Struktur Folder

```
Anomaly_Detection/
│
├── public/                  ← Document root (pasang web server di sini)
│   ├── index.php            ← Front controller (entry point semua request)
│   ├── router.php           ← Router sederhana (path -> file view/action)
│   ├── .htaccess            ← Rewrite, security headers, blokir file sensitif
│   ├── css/style.css        ← Design system utama
│   ├── js/app.js            ← Utilities bersama (Theme, API, Loading, Paginator)
│   └── assets/logo.jpg      ← Logo PDAM
│
├── bootstrap/
│   └── bootstrap.php        ← Load SEKALI per request (path, BASE_URL, session, config, auth)
│
├── config/
│   ├── config.example.php   ← Contoh konfigurasi (salin jadi config.php)
│   └── config.php           ← KONFIGURASI LOKAL — TIDAK di-commit (berisi kredensial DB)
│
├── routes/
│   ├── api.php              ← Backend API handler (action-based: check, upload, analyze, dll)
│   └── index.php            ← Proteksi akses langsung
│
├── app/
│   ├── Http/Controllers/    ← auth.php, logout.php
│   ├── Models/db_sync.php   ← Sinkronisasi hasil Python -> database
│   └── Views/               ← (view dipindah ke resources/views)
│
├── resources/views/         ← Semua halaman
│   ├── index.php            ← Landing page
│   ├── login.php / register.php
│   ├── navbar.php / head_common.php    ← Komponen bersama
│   ├── dashboard1.php       ← [D1] Upload Excel & parameter
│   ├── dashboard2.php       ← [D2] Global Analysis (summary + chart)
│   ├── dashboard3.php       ← [D3] Visual Analytics
│   ├── dashboard4.php       ← [D4] Detail Anomali + penjelasan Z-Score
│   └── history.php          ← [D5] Riwayat analisis (khusus login)
│
├── scripts/
│   ├── python/analyze.py    ← Script ML (Isolation Forest + Z-Score)
│   └── utils/               ← cleanup_guest.php, fresh_install.php, reset_db.php, run_cleanup.bat
│
├── database/
│   ├── database.sql         ← Schema + seed MySQL
│   └── database_postgres.sql ← Schema PostgreSQL
│
├── storage/                 ← Data runtime (GITIGNORE)
│   ├── data/                ← File Excel upload (guest/user, per-session)
│   ├── app/                 ← Hasil analisis JSON guest
│   └── logs/                ← PHP error logs
│
├── docs/                    ← Dokumentasi (ALGORITMA.md, ALUR_SISTEM.md, TA.md, LARAGON_MIGRASI.md)
├── bootstrap/               ← (lihat bootstrap/bootstrap.php)
├── AGENTS.md                ← Aturan keamanan sekuriti untuk agent / kolaborator
└── README.md
```

---

## Alur Penggunaan

| Mode | Deskripsi | Penyimpanan | History |
|------|-----------|-------------|---------|
| **Guest** | Langsung pakai tanpa daftar | Session + `storage/` (30 menit) | ❌ |
| **User Login** | Daftar akun, login | Database | ✅ |

**Alur singkat:**

1. Buka **Dashboard 1** → upload file Excel (kolom: `Tahun, Bulan, Golongan, Rp, M3`)
2. Pilih **contamination** & mode analisis
3. Klik **Jalankan Analisis** → PHP memanggil `analyze.py`
4. Lihat hasil di **Dashboard 2 → 3 → 4** (summary, chart, detail & penjelasan)
5. Pada user login: hasil tersimpan di **Dashboard 5 — History** (bisa replay)

### Mode Analisis

| Mode | Deskripsi | Kapan Digunakan |
|------|-----------|-----------------|
| `near_tahun_per_golongan` | IF per golongan, 2 tahun terakhir | Data terbaru per jenis pelanggan |
| `near_tahun_near_golongan` | IF semua golongan, 2 tahun | Banding antar golongan sesama periode |
| `multi_tahun_per_golongan` | IF per golongan, semua tahun | Trend jangka panjang per golongan |
| `multi_tahun_semua_golongan` | IF semua data sekaligus | Deteksi anomali global |

---

## Instalasi

### Prasyarat
- PHP ≥ 8.0 + Apache (disarankan **Laragon** atau XAMPP)
- **PostgreSQL** (default) *atau* **MySQL**
- Python ≥ 3.9

### Langkah

1. **Salin & konfigurasi config**
   ```powershell
   Copy-Item config\config.example.php config\config.php
   ```
   Edit `config/config.php`: path Python (`PYTHON_PATHS`), driver database
   (`DB_DRIVER` = `'pgsql'` atau `'mysql'`), dan kredensial DB.

2. **Import database** (pilih sesuai driver):
   - PostgreSQL → `database/database_postgres.sql`
   - MySQL → `database/database.sql`

   Akun demo: `admin` / `admin123` (dan `demo` / `admin123`).

3. **Install library Python**
   ```bash
   pip install scikit-learn scipy pandas openpyxl numpy
   ```

4. **Jalankan web server** dengan document root mengarah ke folder `public/`:
   ```
   http://localhost/Anomaly_Detection/public/
   ```
   (URL via subdirectory/root dideteksi otomatis oleh `BASE_URL` di `bootstrap.php`.)

5. **Verifikasi**: Buka Dashboard → System Check Bar harus hijau (Python OK, Deps OK).

---

## API

Semua request lewat `routes/api.php` (diakses via `public/`). Format respons: JSON.

| Action | Method | Deskripsi |
|--------|--------|-----------|
| `check` | GET | Status Python, dependensi, session, file ter-upload |
| `upload` | POST | Upload file Excel (multipart, field `excel`) |
| `analyze` | POST | Jalankan Isolation Forest (params: `contamination`, `mode`, `tahun_min/max`) |
| `get_result` | GET | Ambil hasil analisis terakhir (session/DB) |
| `load_history` | POST | Muat data analisis historis dari database |
| `install_deps` | POST | Install dependensi Python via pip |
| `cleanup_guest` | POST | Bersihkan file guest milik session |
| `get_history` | GET | Daftar riwayat user (login) |

---

## Algoritma

**Isolation Forest** (sklearn) dengan `n_estimators=100`, `random_state=42`,
fitur `[rp, m3, rp_per_m3]`. Output `predict()` (-1 = anomali) dan `score_samples()`.

**Z-Score** dihitung per atribut `Z=(X-μ)/σ` untuk menjelaskan mengapa suatu data dianggap
anomali; atribu dengan |Z| tertinggi ditandai sebagai penyebab utama.

**Severity:**
```
score < -0.15  → HIGH
score < -0.10  → MEDIUM
selainnya      → LOW
```

Detail algoritma & alur lebih lengkap: `docs/ALGORITMA.md`, `docs/ALUR_SISTEM.md`, `docs/TA.md`.

---

## Keamanan & Perlindungan File

- `config/config.php` dan isi `storage/` **tidak di-commit** ke git (lihat `.gitignore`).
- File `.env`, `config.php` dan `.htaccess` diblokir di `public/.htaccess`.
- **Tidak pernah membuka/membaca `.env`** — semua konfigurasi via `config/config.php`.

> ⚠️ Dokumentasi lengkap & troubleshooting: `docs/TA.md`, `docs/LARAGON_MIGRASI.md` (panduan pindah ke Laragon + PostgreSQL).