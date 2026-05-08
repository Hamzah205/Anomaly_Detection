# Alur Sistem PDAM Anomaly Detection

Dokumen ini menjelaskan alur kerja lengkap sistem deteksi anomali PDAM dari awal hingga akhir.

---

## 1. Arsitektur Sistem

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Browser   │────▶│  PHP (API)  │────▶│    Python   │────▶│   MySQL     │
│  (Frontend) │◀────│  (Backend)  │◀────│  (ML Model) │◀────│ (Database)  │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
```

| Layer | Fungsi | Teknologi |
|-------|--------|-----------|
| Frontend | UI, Chart, Interaksi | HTML + CSS + JavaScript + Chart.js |
| Backend | Routing, Auth, Database | PHP 8.x + PDO MySQL |
| ML Engine | Deteksi Anomali | Python 3.x + Scikit-Learn + Pandas |
| Storage | Data permanen | MySQL + File Excel/JSON |

---

## 2. Alur Utama (User Flow)

### 2.1 Upload Data

```
User ──▶ Pilih file Excel (.xlsx)
         └── Kolom wajib: Tahun, Bulan, Golongan, Rp, M3, Rp_per_M3
         └── Ukuran max: sesuai konfigurasi server
         
         [Guest] File disimpan di folder data/ sebagai guest_<id>.xlsx
         [User]  File disimpan + record masuk ke tabel uploads
```

**Validasi:**
- Ekstensi harus `.xlsx`
- Kolom minimal: Tahun, Bulan, Golongan, Rp, M3
- Data tidak boleh kosong total

---

### 2.2 Jalankan Analisis

```
User ──▶ Pilih mode & contamination ──▶ Klik "Jalankan Analisis"
         
         Backend menerima parameter:
         ├── mode: near_tahun_per_golongan (default)
         │          near_tahun_near_golongan
         │          multi_tahun_per_golongan
         │          multi_tahun_semua_golongan
         ├── contamination: auto / 0.05 / 0.1 / 0.2 / 0.3
         └── tahun_min, tahun_max (opsional filter)
         
         Backend eksekusi Python analyze.py via command line
         Input: path file Excel + parameter JSON
         Output: file JSON hasil analisis
```

---

### 2.3 Proses ML (Python analyze.py)

```
Langkah 1: LOAD DATA
   └── Baca Excel → DataFrame
   └── Rename & normalisasi kolom
   └── Filter tahun jika diminta

Langkah 2: ISOLATION FOREST (Deteksi Anomali)
   └── Mode grouping sesuai pilihan user:
       ├── per Golongan + Tahun (default)
       ├── per Golongan saja
       └── Global (tanpa grouping)
   └── Fit: Rp, M3, Rp_per_M3 (multivariate)
   └── Output:
       ├── is_anomaly: 1 = anomali, 0 = normal
       ├── anomaly_score: makin negatif = makin aneh
       └── anomaly_level: high / medium / low (dari ranking score)

Langkah 3: Z-SCORE (Interpretasi)
   └── Hitung Z untuk tiap atribut vs grupnya sendiri
   └── Z = (nilai - rata-rata) / standar_deviasi
   └── Ambil TOP-2 atribut dengan |Z| terbesar
   └── Generate penjelasan user-friendly
       ├── Rp: "Tagihan terlalu tinggi/rendah"
       ├── M3: "Volume pemakaian abnormal"
       └── Rp_per_M3: "Tarif tidak wajar"

Langkah 4: OUTPUT JSON
   └── Meta: total, anomali, persentase, mode, contamination, tahun_range
   └── Data: detail per baris (is_anomaly, score, level, causes)
   └── Summary per tahun & golongan
```

---

### 2.4 Sinkronisasi Database

```
[Guest Mode]
   └── Hasil disimpan di file JSON: json/guest_<id>.json
   └── History tidak tersimpan (readonly)
   └── File guest otomatis dihapus setelah 30 hari

[User Login]
   └── Hasil masuk ke MySQL:
       ├── uploads: info file upload
       ├── raw_data: data mentah per baris
       ├── analysis_results: flag anomali + score + level
       └── analysis_history: parameter & ringkasan analisis
```

---

### 2.5 Tampilkan Hasil

| Dashboard | Isi |
|-----------|-----|
| **Dashboard 1** | Upload file, status sistem, panduan |
| **Dashboard 2** | Global analysis, donut chart, bar chart, insight otomatis |
| **Dashboard 3** | Detail anomali per baris, filter multi-kolom, heatmap, export PDF |
| **Dashboard 4** | Tabel anomali lengkap dengan z-score detail & penjelasan |
| **History** | Riwayat analisis user (jika login), replay analisis |

---

## 3. Perbedaan User vs Guest

| Fitur | Guest | User Login |
|-------|-------|------------|
| Upload | Ya (file temporary) | Ya (tersimpan di DB) |
| Analisis | Ya | Ya |
| History | Tidak tersimpan | Tersimpan permanen |
| Replay | Hanya dari session | Dari database kapan saja |
| Data persisten | 30 hari (auto cleanup) | Selamanya |
| Multi-user safe | Tidak (session terpisah) | Ya |

---

## 4. Severity Level

**Bukan dari Z-Score!** Severity berasal dari ranking `anomaly_score` Isolation Forest.

| Level | Definisi | Warna |
|-------|----------|-------|
| **High** | Top 5% paling ekstrem | Merah `#E53935` |
| **Medium** | 15% berikutnya | Orange `#FB8C00` |
| **Low** | Sisanya | Hijau `#43A047` |
| **Normal** | Bukan anomali | — |

---

## 5. Mode Analisis

| Mode | Deskripsi | Kapan Dipakai |
|------|-----------|---------------|
| `near_tahun_per_golongan` | Bandingkan data dalam golongan & tahun yang sama | Default — paling akurat |
| `near_tahun_near_golongan` | Bandingkan semua data tanpa grouping | Lihat anomali global |
| `multi_tahun_per_golongan` | Bandingkan semua tahun dalam satu golongan | Lihat tren jangka panjang |
| `multi_tahun_semua_golongan` | Bandingkan semua data & semua golongan | Overview total |

---

## 6. File Penting

| File | Fungsi |
|------|--------|
| `index.php` | Landing page |
| `login.php` / `register.php` | Autentikasi |
| `dashboard1.php` — `dashboard4.php` | Tampilan hasil |
| `history.php` | Riwayat analisis |
| `api.php` | Endpoint backend (upload, analyze, get_result, load_history) |
| `auth.php` | Fungsi autentikasi & history_save |
| `db_sync.php` | Sinkronisasi hasil ke MySQL |
| `config.php` | Konfigurasi DB, path Python, direktori |
| `bootstrap.php` | Inisialisasi session & guest_id |
| `python/analyze.py` | ML engine utama |

---

## 7. Flowchart Singkat

```
[Start]
   │
   ▼
[Upload Excel] ──▶ [Validasi] ──▶ [Simpan File]
   │
   ▼
[Pilih Parameter]
   │── Mode analisis
   │── Contamination
   │── Tahun range (opsional)
   │
   ▼
[Jalankan Isolation Forest]
   │── Group data sesuai mode
   │── Fit model (Rp, M3, Rp_per_M3)
   │── Predict anomali
   │── Assign severity (high/medium/low)
   │
   ▼
[Hitung Z-Score Causes]
   │── Per atribut vs grup
   │── Ambil top-2 penyimpangan
   │── Generate penjelasan
   │
   ▼
[Sync ke Database] (jika user login)
   │── uploads ──▶ raw_data ──▶ analysis_results
   │── Simpan history record
   │
   ▼
[Tampilkan Dashboard]
   │── Chart & Visualisasi
   │── Tabel detail anomali
   │── Insight otomatis
   │
   ▼
[End / History tersimpan]
```

---

## 8. Catatan Teknis

- **Isolation Forest** menggunakan `n_estimators=100` dan `random_state=42` untuk reprodusibilitas
- **Contamination** validasi: hanya menerima 0 < value ≤ 0.5, selain itu fallback ke `auto`
- **Guest cleanup**: file guest dihapus otomatis jika umur > 30 hari (via cron/manual)
- **Session**: PHP session dengan `guest_id` unik untuk isolasi data guest
- **Security**: CSRF tidak diimplementasi (internal tool), tapi auth & redirect validation ada
