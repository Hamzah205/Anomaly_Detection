# SISTEM DETEKSI ANOMALI PDAM
## Dokumentasi Teknis Lengkap (TA.md) — v2.0

**Nama Sistem:** PDAM Anomaly Detection  
**Instansi:** Perumdam Tirta Kencana Kota Samarinda  
**Algoritma:** Isolation Forest + Z-Score Analysis  
**Platform:** PHP (XAMPP/Windows) + Python 3 + MySQL  
**Versi Dokumen:** 2.0 — 01 Mei 2026

---

## DAFTAR ISI

1. Pendahuluan & Tujuan
2. Arsitektur Sistem
3. Struktur File & Komponen
4. Alur Sistem (Flowchart)
5. Penjelasan Fitur Lengkap
6. Algoritma & Metode
7. Database Schema
8. API Reference
9. Panduan Instalasi
10. Troubleshooting

---

## 1. PENDAHULUAN & TUJUAN

### 1.1 Latar Belakang
PDAM (Perusahaan Daerah Air Minum) memiliki ribuan pelanggan dengan data tagihan bulanan. Pengecekan manual untuk menemukan tagihan atau pemakaian yang tidak wajar sangat memakan waktu dan rentan terlewat.

Sistem ini hadir untuk:
- **Mendeteksi anomali secara otomatis** menggunakan Machine Learning (Isolation Forest)
- **Memberikan penjelasan yang mudah dipahami** (Z-Score per atribut, bukan hanya flag merah)
- **Menyimpan riwayat analisis** untuk perbandingan antar periode
- **Mendukung multi-user** dengan isolasi data per session

### 1.2 Masalah yang Diselesaikan

| Masalah | Solusi Sistem |
|---------|---------------|
| Manual checking lambat (ribuan baris) | Analisis otomatis <30 detik via Python |
| Sulit melihat pola tidak wajar | Visualisasi chart + badge severity |
| Tidak ada history perbandingan | Database MySQL + Dashboard History |
| Data tersebar, tidak terstruktur | Upload Excel langsung ke sistem |
| Petugas tidak paham Z-Score | Penjelasan bahasa Indonesia per anomali |

### 1.3 Mode Pengguna

| Mode | Deskripsi | Penyimpanan | History |
|------|-----------|-------------|---------|
| **Guest** | Langsung pakai tanpa daftar | Session (30 menit) | ❌ |
| **User Login** | Daftar akun, login | Database MySQL | ✅ |

---

## 2. ARSITEKTUR SISTEM

### 2.1 Diagram Komponen

```
┌────────────────────────────────────────────────────────┐
│                     BROWSER (User)                     │
│  Dashboard 1 → 2 → 3 → 4 → History                   │
│  (HTML + CSS + JavaScript + Chart.js + SheetJS)        │
└────────────────┬───────────────────────────────────────┘
                 │ HTTP Request (fetch/AJAX)
                 ▼
┌────────────────────────────────────────────────────────┐
│                   PHP LAYER (XAMPP Apache)             │
│                                                        │
│  bootstrap.php  ──▶  config.php                        │
│       │              auth.php                          │
│       │              db_sync.php                       │
│       ▼                                                │
│  api.php  ──── switch($action) ────────────────────    │
│  ├── upload    (simpan Excel ke /data/)                │
│  ├── analyze   (panggil Python via proc_open)          │
│  ├── get_result (ambil dari session/DB)                │
│  ├── load_history (ambil data historis dari DB)        │
│  └── check     (status Python & dependencies)          │
└────────────────┬───────────────────────────────────────┘
                 │ proc_open (subprocess)
                 ▼
┌────────────────────────────────────────────────────────┐
│               PYTHON LAYER (analyze.py)                │
│                                                        │
│  Input:  Excel file (.xlsx) via --file argument        │
│  Proses: pandas → Isolation Forest → Z-Score           │
│  Output: JSON ke stdout (User) / file (Guest)          │
│                                                        │
│  Libraries: scikit-learn, scipy, pandas, openpyxl      │
└────────────────┬───────────────────────────────────────┘
                 │ PDO/MySQL
                 ▼
┌────────────────────────────────────────────────────────┐
│               MySQL DATABASE (pdam_anomaly)             │
│                                                        │
│  users             — akun pengguna                     │
│  uploads           — metadata file Excel               │
│  raw_data          — data mentah dari Excel            │
│  analysis_results  — hasil Isolation Forest per baris  │
│  analysis_history  — log setiap kali analisis dijalankan│
│  audit_log         — aktivitas sistem (opsional)       │
└────────────────────────────────────────────────────────┘
```

### 2.2 Stack Teknologi

| Layer | Teknologi | Versi |
|-------|-----------|-------|
| Web Server | Apache (XAMPP) | ≥ 2.4 |
| Backend | PHP | ≥ 8.0 |
| Database | MySQL | ≥ 5.7 |
| ML Engine | Python | ≥ 3.9 |
| ML Library | scikit-learn | ≥ 1.0 |
| Data Processing | pandas, openpyxl | latest |
| Frontend Charts | Chart.js | 4.4.0 |
| Export Excel | SheetJS (xlsx) | 0.18.5 |
| Font | Outfit + DM Sans | Google Fonts |

---

## 3. STRUKTUR FILE & KOMPONEN

```
pdam_sistem/
│
├── bootstrap.php          ← Entry point semua halaman (session + config + auth)
├── config.php             ← Konstanta: path Python, path direktori, config MySQL
├── auth.php               ← Fungsi: login, register, logout, history_save, history_get
├── api.php                ← REST-like API handler (action-based)
├── db_sync.php            ← Sinkronisasi hasil Python → MySQL
├── database.sql           ← Schema & seed data MySQL (import ke phpMyAdmin)
│
├── index.php              ← Landing page (redirect ke dashboard1)
├── dashboard1.php         ← [D1] Upload Excel & pilih parameter analisis
├── dashboard2.php         ← [D2] Global Analysis — eksekusi & kontrol model
├── dashboard3.php         ← [D3] Visual Analytics — chart interaktif
├── dashboard4.php         ← [D4] Detail Anomali — tabel lengkap + penjelasan
├── history.php            ← [D5] Riwayat analisis (khusus user login)
│
├── login.php              ← Halaman login
├── register.php           ← Halaman registrasi akun
├── logout.php             ← Proses logout + clear session
├── cleanup_guest.php      ← Cleanup file guest expired
│
├── includes/
│   └── navbar.php         ← Navbar + sidebar mobile (shared)
│
├── css/
│   └── style.css          ← Design system utama (CSS variables + komponen)
│
├── js/
│   └── app.js             ← Shared utilities: Theme, API, Loading, Paginator, Fmt
│
├── python/
│   └── analyze.py         ← Script ML: Isolation Forest + Z-Score
│
├── assets/
│   └── logo.jpg           ← Logo PDAM Samarinda
│
├── data/                  ← Upload Excel (auto-dibuat, gitignored)
│   ├── guest_{id}.xlsx    ← File Excel guest (hapus setelah 30 menit)
│   └── user_{id}_{ts}.xlsx ← File Excel user (permanen)
│
├── json/                  ← Hasil analisis JSON (auto-dibuat)
│   └── guest_{id}.json    ← Hasil analisis guest (hapus setelah 30 menit)
│
└── logs/                  ← PHP error logs (auto-dibuat)
```

---

## 4. ALUR SISTEM (FLOWCHART)

### 4.1 Alur Guest Mode (Tanpa Login)

```
START
  │
  ▼
Buka Dashboard 1 (dashboard1.php)
  │ Session guest_id dibuat otomatis
  ▼
Upload File Excel (.xlsx/.xls)
  │ POST api.php?action=upload
  │ Simpan ke: data/guest_{id}.xlsx
  ▼
Pilih Parameter:
  │  - Contamination (5%, 10%, 20%, 30%, Auto)
  │  - Mode Analisis (4 pilihan)
  │  - Filter Tahun (opsional)
  ▼
Klik "Jalankan Analisis"
  │ POST api.php?action=analyze
  ▼
PHP → proc_open → python/analyze.py
  │  Args: --file, --contamination, --mode
  │  Output: JSON ke data/json/guest_{id}.json
  ▼
[Berhasil?]──No──▶ Tampilkan error + hint pip install
  │ Yes
  ▼
Redirect ke Dashboard 2 (Global Analysis)
  │ fetch api.php?action=get_result
  │ Baca dari: json/guest_{id}.json
  ▼
Tampilkan:
  │  - Total data, jumlah anomali, persentase
  │  - Donut chart (anomali vs normal)
  │  - Bar chart per golongan
  ▼
Pindah ke Dashboard 3 (Visual Analytics)
  │  - Line chart trend per tahun
  │  - Z-Score heatmap
  ▼
Pindah ke Dashboard 4 (Detail Anomali)
  │  - Tabel semua anomali + severity badge
  │  - Expand detail: penyebab per atribut + Z-Score
  │  - Export PDF / Excel
  ▼
END ← data hilang jika session berakhir (30 menit idle)
```

### 4.2 Alur User Mode (Login)

```
START
  │
  ▼
Login (login.php)
  │ POST auth_login() → session user_id
  ▼
Dashboard 1 — Upload & Parameter
  │ POST api.php?action=upload
  │ Simpan ke: data/user_{id}_{timestamp}.xlsx
  ▼
Jalankan Analisis
  │ POST api.php?action=analyze
  │ Python → stdout (JSON)
  ▼
PHP terima JSON hasil Python
  │
  ├─▶ db_sync_results()  ←── Simpan ke MySQL:
  │     INSERT uploads (uuid, metadata...)
  │     INSERT raw_data (per baris Excel)
  │     INSERT analysis_results (hasil IF per baris)
  │
  ├─▶ history_save()  ←── INSERT analysis_history
  │     (mode, contamination, total, anomali, %)
  │
  └─▶ db_link_history()  ←── UPDATE analysis_results.history_id
  │
  ▼
Session: user_last_result = parsed JSON
  │
  ▼
Dashboard 2, 3, 4 — sama seperti Guest
  │ Tapi: get_result ambil dari session atau DB
  ▼
Dashboard 5 — History
  │ Tampilkan semua analisis yang pernah dijalankan
  │ - Sortir by tanggal
  │ - Filter by mode/contamination
  ▼
Klik "Lihat Kembali" (Replay)
  │ POST api.php?action=load_history
  │ Ambil data dari DB (upload_id → raw_data + results)
  │ Simpan ke sessionStorage
  │ Redirect ke Dashboard 4
  ▼
END ← data tersimpan permanen di database
```

### 4.3 Flowchart Python (analyze.py)

```
START — analyze.py
  │ Args: --file, --contamination, --mode, --tahun_min, --tahun_max
  ▼
Baca Excel dengan pandas (openpyxl engine)
  │ Kolom: Tahun, Bulan, Golongan, Nama Golongan, Rp, M3
  ▼
Preprocessing:
  │  - Hitung Rp_per_M3 = Rp / M3
  │  - Filter tahun jika --tahun_min/max
  │  - Encode golongan sebagai integer
  ▼
Pilih Mode:
  ├── near_tahun_per_golongan     → IF per golongan, 2 tahun terakhir
  ├── near_tahun_near_golongan    → IF semua, 2 tahun, normalisasi golongan
  ├── multi_tahun_per_golongan    → IF per golongan, semua tahun
  └── multi_tahun_semua_golongan  → IF semua data sekaligus
  ▼
IsolationForest (scikit-learn):
  │  n_estimators=100, contamination=X, random_state=42
  │  Features: [rp, m3, rp_per_m3]
  ▼
predict() → -1 (anomali) / 1 (normal)
score_samples() → anomaly_score (semakin negatif = semakin anomali)
  ▼
Z-Score per atribut (scipy.stats.zscore):
  │  Hitung Z-Score untuk rp, m3, rp_per_m3
  │  Tentukan "penyebab" anomali = atribut dengan |zscore| tertinggi
  ▼
Severity Classification:
  │  anomaly_score < -0.15 → high
  │  anomaly_score < -0.10 → medium
  │  else                  → low
  ▼
Build JSON response:
  │  { meta: {...}, summary_golongan: [...], data: [...] }
  ▼
Output:
  │  User mode  → print ke stdout (PHP tangkap)
  └─ Guest mode → tulis ke json/guest_{id}.json
  │
END
```

---

## 5. PENJELASAN FITUR LENGKAP

### 5.1 Dashboard 1 — Upload & Parameter

**Tujuan:** Entry point analisis. User upload data Excel dan tentukan parameter sebelum menjalankan model.

**Fitur:**
- **Drag & Drop Upload**: File `.xlsx` atau `.xls`, maks 20MB. Mendukung klik untuk browse.
- **System Check Bar**: Menampilkan status Python (terdeteksi/tidak), versi Python, status dependensi (scikit-learn, pandas, dll), dan mode session (guest/user).
- **Stepper UI**: Progress visual 4 langkah (Upload → Contamination → Mode → Selesai).
- **Parameter Contamination**: Estimasi berapa persen data yang diperkirakan anomali. Pilihan: 5% (default), 10%, 20%, 30%, Auto.
- **Mode Analisis**: 4 mode yang mengatur bagaimana Isolation Forest dikelompokkan (lihat bagian Algoritma).
- **Filter Tahun**: Batasi analisis ke rentang tahun tertentu (opsional).
- **Tombol Install Deps**: Jika Python ditemukan tapi library belum ada, tombol ini jalankan `pip install` otomatis.

**Alur Data:**
```
User pilih file → JS validasi → POST /api.php?action=upload → PHP simpan file → 
User klik Analisis → POST /api.php?action=analyze → PHP panggil Python → 
Python output JSON → PHP simpan/parse → Redirect ke Dashboard 2
```

---

### 5.2 Dashboard 2 — Global Analysis

**Tujuan:** Pusat kontrol analisis. User bisa mengubah parameter dan menjalankan ulang model tanpa kembali ke Dashboard 1.

**Fitur:**
- **4 Tab Mode**: Pilih mode Isolation Forest langsung dari dashboard.
- **Re-run Analysis**: Setiap klik "Jalankan Ulang Model" akan memanggil Python lagi dengan parameter baru.
- **Summary Cards**: Total data, jumlah anomali, persentase anomali, contamination aktif.
- **Severity Breakdown**: High / Medium / Low count.
- **Donut Chart**: Proporsi anomali vs normal.
- **Bar Chart per Golongan**: Perbandingan anomali dan normal tiap kelompok pelanggan.
- **Export CSV**: Download semua data anomali sebagai file CSV.
- **Print PDF**: Print view yang dioptimalkan (navbar & filter tersembunyi).

**Mode Analisis yang Tersedia:**

| Mode | Deskripsi | Kapan Digunakan |
|------|-----------|-----------------|
| Near Tahun per Golongan | IF dijalankan per golongan, hanya 2 tahun terakhir | Data terbaru, lihat anomali per jenis pelanggan |
| Near Tahun Near Golongan | IF semua golongan, 2 tahun, dengan normalisasi | Bandingkan antar golongan di periode sama |
| Multi Tahun per Golongan | IF per golongan, semua tahun | Trend jangka panjang per golongan |
| Multi Tahun Semua Golongan | IF semua data sekaligus | Deteksi anomali global, pola lintas golongan |

---

### 5.3 Dashboard 3 — Visual Analytics

**Tujuan:** Eksplorasi visual data anomali melalui berbagai jenis chart.

**Fitur:**
- **Line Chart Trend**: Jumlah anomali per tahun per golongan (interaktif, bisa toggle golongan).
- **Scatter Plot Z-Score**: Plot titik data dengan Z-Score sebagai koordinat, warna menunjukkan severity.
- **Heatmap Golongan × Tahun**: Grid warna-coded yang langsung menunjukkan konsentrasi anomali.
- **Filter Golongan**: Tampilkan/sembunyikan golongan tertentu dari chart.
- **Export PNG**: Download chart sebagai gambar.

---

### 5.4 Dashboard 4 — Detail Anomali

**Tujuan:** Analisis mendalam setiap record anomali. Dirancang untuk petugas operasional yang perlu tindak lanjut.

**Fitur:**
- **Tabel Lengkap**: Semua baris anomali dengan kolom: Tahun, Bulan, Golongan, Rp, M3, Rp/M3, Score, Severity, Penyebab Utama.
- **Filter Multi-Kolom**: Filter by golongan, tahun, severity level, dan sorting.
- **Expand Detail**: Klik tombol "Detail" untuk melihat breakdown Z-Score per atribut (Rp, M3, Rp/M3) dengan penjelasan dalam Bahasa Indonesia.
- **Tampilkan Semua Penjelasan**: Checkbox untuk expand semua baris sekaligus.
- **Summary Card**: Total anomali (dengan filter), jumlah High severity, tahun dengan anomali terbanyak.
- **Analisis Ringkasan Otomatis**: Teks otomatis yang merangkum: golongan paling bermasalah, tahun tertinggi, atribut penyebab dominan.
- **Export Excel (.xlsx)**: Export tabel anomali ke file Excel menggunakan SheetJS (berjalan di browser, tanpa server).
- **Print PDF**: Print view teroptimasi.

**Format Penjelasan Anomali:**
Setiap anomali menampilkan penyebab per atribut:
```
Rp (Tagihan) — Z-Score: 3.42 [HIGH]
  "Tagihan pelanggan ini jauh lebih tinggi dari rata-rata golongan 
   yang sama di bulan tersebut (3.42 standar deviasi di atas normal)"
  
  Detail teknis: nilai=1.234.567, mean=456.789, std=228.394
```

---

### 5.5 Dashboard 5 — Riwayat Analisis (History)

**Tujuan:** Melihat dan memutar ulang semua analisis yang pernah dijalankan (khusus user login).

**Fitur:**
- **Timeline Analisis**: Semua run dengan timestamp, mode, contamination, total data, total anomali, persentase.
- **Summary 4 Kartu**: Total run, total anomali kumulatif, rata-rata persentase, tanggal run terakhir.
- **Bar Chart Trend**: Persentase anomali per run (trend analisis dari waktu ke waktu).
- **Timeline Visual**: Daftar run dengan warna persentase (merah tinggi, hijau rendah).
- **Tombol "Lihat Kembali"**: Muat ulang data analisis historis ke Dashboard 4 tanpa re-run Python.
- **Export History CSV**: Download daftar riwayat sebagai file CSV.
- **Filter History**: Filter by mode, contamination, atau rentang tanggal.

**Mekanisme Replay:**
```
Klik "Lihat Kembali" (history_id)
→ POST api.php?action=load_history {history_id}
→ PHP query: raw_data + analysis_results JOIN (by upload_id)
→ Build JSON format sama dengan hasil Python baru
→ Simpan ke sessionStorage + session PHP
→ Redirect ke Dashboard 4 ?from_history=1
→ Dashboard 4 baca dari sessionStorage (langsung, tanpa re-run Python)
```

---

### 5.6 Autentikasi & Session

**Registrasi:**
- Username 3–50 karakter, unik
- Email valid, unik
- Password min. 6 karakter (disimpan dengan `password_hash()` bcrypt cost-10)

**Login:**
- Bisa menggunakan email atau username
- Session regenerate setelah login sukses (anti session fixation)
- Redirect whitelist mencegah open redirect

**Guest Session:**
- Dibuat otomatis saat pertama kali buka sistem
- `guest_id` = `bin2hex(random_bytes(8))` (16 karakter hex unik)
- File guest dihapus otomatis setelah 30 menit idle (probabilistic cleanup 10% per request)

**Dark Mode:**
- Disimpan di `localStorage('pdam_theme')` — persisten antar session browser
- Toggle tersedia di navbar semua halaman

---

## 6. ALGORITMA & METODE

### 6.1 Isolation Forest

**Prinsip:** Algoritma anomaly detection berbasis tree. Data anomali lebih mudah "diisolasi" (memerlukan lebih sedikit pemisahan) daripada data normal.

**Parameter yang digunakan:**
```python
IsolationForest(
    n_estimators=100,        # jumlah tree
    contamination=X,         # proporsi anomali (dari input user)
    random_state=42,         # reproducible
    max_features=1.0         # semua fitur
)
```

**Fitur input:**
- `rp` — total tagihan (Rupiah)
- `m3` — total pemakaian air (meter kubik)
- `rp_per_m3` — tarif efektif per m3 (derived feature)

**Output:**
- `predict()` → -1 (anomali) atau 1 (normal)
- `score_samples()` → anomaly score (semakin negatif = semakin anomali)

### 6.2 Z-Score Analysis

Digunakan untuk menjelaskan **mengapa** data dianggap anomali, per atribut.

```
Z = (X - μ) / σ

Di mana:
  X  = nilai data point
  μ  = rata-rata kelompok (golongan + bulan/tahun)
  σ  = standar deviasi kelompok
```

**Klasifikasi Z-Score:**
- `|Z| ≥ 3` → HIGH (sangat menyimpang)
- `|Z| ≥ 2` → MEDIUM (menyimpang signifikan)
- `|Z| ≥ 1` → LOW (sedikit menyimpang)

**Penyebab Utama:** Atribut dengan `|Z-Score|` tertinggi ditandai sebagai penyebab utama anomali.

### 6.3 Severity Classification

```
IF anomaly_score < -0.15 → HIGH
IF anomaly_score < -0.10 → MEDIUM
ELSE                     → LOW
```

Threshold ini dikalibrasi untuk data PDAM dan bisa disesuaikan di `analyze.py`.

---

## 7. DATABASE SCHEMA

### Tabel `users`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | INT UNSIGNED PK | Auto increment |
| username | VARCHAR(50) UNIQUE | Username unik |
| email | VARCHAR(150) UNIQUE | Email unik |
| password | VARCHAR(255) | bcrypt hash |
| role | ENUM('admin','user') | Role pengguna |
| is_active | TINYINT(1) | Soft delete |

### Tabel `uploads`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | INT UNSIGNED PK | Auto increment |
| uuid | VARCHAR(36) UNIQUE NOT NULL | ID unik (hex 32 char) |
| user_id | INT UNSIGNED FK | Pemilik upload |
| original_name | VARCHAR(255) | Nama file asli |
| stored_name | VARCHAR(255) | Nama file di server |
| total_rows | INT UNSIGNED | Jumlah baris data |
| tahun_min / max | SMALLINT | Rentang tahun |
| golongan_list | TEXT | JSON array golongan |

### Tabel `raw_data`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | BIGINT UNSIGNED PK | Auto increment |
| upload_id | INT UNSIGNED FK | Parent upload |
| tahun | SMALLINT | Tahun data |
| bulan | VARCHAR(20) | Nama bulan |
| bulan_num | TINYINT | Nomor bulan (1-12) |
| golongan | VARCHAR(10) | Kode golongan |
| nama_golongan | VARCHAR(100) | Nama golongan |
| rp | BIGINT | Tagihan (Rupiah) |
| m3 | INT | Pemakaian (M3) |
| rp_per_m3 | DECIMAL(15,4) | Tarif per M3 |
| UNIQUE | (upload_id, tahun, bulan_num, golongan) | Cegah duplikasi |

### Tabel `analysis_results`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | BIGINT UNSIGNED PK | Auto increment |
| raw_data_id | BIGINT UNSIGNED FK | Referensi ke raw_data |
| upload_id | INT UNSIGNED FK | Referensi ke upload |
| history_id | INT UNSIGNED FK | Referensi ke history run |
| is_anomaly | TINYINT(1) | 1 = anomali |
| anomaly_score | DECIMAL(10,6) | Skor Isolation Forest |
| anomaly_level | ENUM('normal','low','medium','high') | Severity |
| causes_json | JSON | Detail Z-Score per atribut |

### Tabel `analysis_history`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | INT UNSIGNED PK | Auto increment |
| user_id | INT UNSIGNED FK | Siapa yang run |
| upload_id | INT UNSIGNED FK | Upload yang dianalisis |
| filter_mode | VARCHAR(60) | Mode analisis |
| contamination | DECIMAL(4,2) | Nilai contamination |
| tahun_min / max | SMALLINT | Filter tahun |
| jumlah_data | INT UNSIGNED | Total baris |
| jumlah_anomali | INT UNSIGNED | Total anomali |
| persentase | DECIMAL(5,2) | % anomali |
| status | ENUM | running/completed/failed |
| filename | VARCHAR(255) | Nama file Excel |

---

## 8. API REFERENCE

Semua request melalui `api.php`. Format response: JSON.

### GET api.php?action=check
Cek status sistem (Python, dependencies, session).

**Response:**
```json
{
  "status": "success",
  "python": "C:\\...\\python.exe",
  "python_ver": "Python 3.14.0",
  "deps_ok": true,
  "is_login": false,
  "has_file": false,
  "has_result": false,
  "php_ver": "8.2.0"
}
```

### POST api.php?action=upload
Upload file Excel.

**Body:** `multipart/form-data`, field `excel` = file.
**Response:** `{ status, filename, size, mode }`

### POST api.php?action=analyze
Jalankan analisis Isolation Forest.

**Body:** `contamination`, `mode`, `tahun_min` (opsional), `tahun_max` (opsional)
**Response:** `{ status, result: {meta, summary_golongan, data}, mode, upload_id, db_synced }`

### GET api.php?action=get_result
Ambil hasil analisis terakhir (dari session atau DB).

**Response:** `{ status, data: {meta, summary_golongan, data}, source }`

### POST api.php?action=load_history
Muat data analisis historis dari database.

**Body:** `history_id`
**Response:** `{ status, upload_id, history_id, data: {meta, summary_golongan, data} }`

### POST api.php?action=install_deps
Install Python dependencies via pip.

**Response:** `{ status, output, message }`

---

## 9. PANDUAN INSTALASI

### Prasyarat
- XAMPP (Apache + MySQL) untuk Windows
- Python 3.9+ terinstall di PC
- Browser modern (Chrome/Firefox/Edge)

### Langkah Instalasi

**1. Copy folder ke htdocs:**
```
Ekstrak ZIP → Salin folder pdam_sistem ke:
C:\xampp\htdocs\pdam_sistem\
```

**2. Setup MySQL Database:**
- Buka http://localhost/phpmyadmin
- Klik **Import** → pilih `database.sql` → klik **Go**
- Database `pdam_anomaly` dibuat otomatis dengan akun demo:
  - Username: `admin` / Password: `admin123`
  - Username: `demo`  / Password: `admin123`

**3. Sesuaikan config.php:**
```php
// Python path — sesuaikan dengan lokasi Python di PC Anda
define('PYTHON_PATHS', [
    'C:\\Users\\NamaAnda\\AppData\\Local\\Python\\pythoncore-3.14-64\\python.exe',
    'python3',
    'python',
]);

// MySQL (default XAMPP sudah sesuai)
define('DB_HOST', 'localhost');
define('DB_NAME', 'pdam_anomaly');
define('DB_USER', 'root');
define('DB_PASS', '');  // Default XAMPP: kosong
```

**4. Install library Python:**
Buka CMD (Run as Administrator):
```cmd
python.exe -m pip install scikit-learn scipy pandas openpyxl
```

**5. Jalankan XAMPP:**
- Start **Apache** dan **MySQL** di XAMPP Control Panel
- Buka: http://localhost/pdam_sistem/

**6. Verifikasi:**
- Buka Dashboard 1 → lihat System Check Bar
- Semua chip harus hijau (✅ Python OK, ✅ Deps OK)

---

## 10. TROUBLESHOOTING

### Python tidak terdeteksi
**Gejala:** System Check Bar menampilkan "Python: Tidak Ditemukan"
**Solusi:**
1. Pastikan Python terinstall: buka CMD → ketik `python --version`
2. Tambahkan path Python ke `config.php` di array `PYTHON_PATHS`
3. Atau tambahkan Python ke System PATH Windows

### Database tidak tersedia
**Gejala:** Halaman login menampilkan peringatan database, history tidak bisa dibuka
**Solusi:**
1. Pastikan MySQL berjalan di XAMPP Control Panel
2. Pastikan database `pdam_anomaly` sudah di-import dari `database.sql`
3. Cek `DB_PASS` di `config.php` (default XAMPP = kosong)

### Analisis gagal / Python error
**Gejala:** Alert merah "Python error: ..." setelah klik Jalankan Analisis
**Solusi:**
1. Klik tombol "Install Dependencies" di Dashboard 1
2. Atau manual: `python -m pip install scikit-learn scipy pandas openpyxl`
3. Pastikan file Excel memiliki kolom: Tahun, Bulan, Golongan, Rp, M3

### Hasil tidak muncul setelah analisis
**Gejala:** Dashboard 2 menampilkan "Belum ada hasil"
**Solusi:**
1. Pastikan analisis selesai (tidak ada error di Dashboard 1)
2. Untuk Guest: cek apakah file `json/guest_{id}.json` ada di server
3. Untuk User Login: cek `error_log PHP` untuk pesan dari `db_sync_results`

### Memory Error PHP
**Gejala:** `Fatal error: Allowed memory size exhausted in navbar.php`
**Solusi:** Sudah diperbaiki dengan sistem `bootstrap.php` yang hanya di-load sekali per request. Pastikan tidak ada file yang memanggil `require` auth.php atau config.php langsung (selalu gunakan `bootstrap.php`).

### History tidak muncul / Replay gagal
**Gejala:** History kosong meskipun sudah pernah analisis
**Penyebab Umum:** Bug `uuid` di `db_sync.php` (sudah diperbaiki di v2.0)
**Solusi:** Gunakan versi terbaru sistem (v2.0). Jalankan ulang analisis untuk menghasilkan history baru.

---

*Dokumentasi ini dihasilkan untuk sistem PDAM Anomaly Detection v2.0*  
*Perumdam Tirta Kencana Kota Samarinda — 01 Mei 2026*
