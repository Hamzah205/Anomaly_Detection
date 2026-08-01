# PDAM Anomaly Detection — Laporan Perbaikan Bug (eror.md)

Dokumen ini mencatat semua bug yang ditemukan dan diperbaiki dalam sistem.

---

## BUG #1 — `db_sync.php`: INSERT uploads gagal karena kolom `uuid` tidak diisi

**File:** `db_sync.php`, fungsi `db_sync_results()`
**Dampak:** Fatal. Setiap kali user login dan menjalankan analisis, penyimpanan ke database selalu gagal diam-diam (`db_sync_results` return `null`). Seluruh history tidak tersimpan.

**Penyebab:**
Skema tabel `uploads` mendefinisikan kolom `uuid VARCHAR(36) NOT NULL UNIQUE`, tetapi INSERT di `db_sync.php` tidak menyertakan kolom tersebut:

```php
// SEBELUM (RUSAK):
INSERT INTO uploads
    (user_id, original_name, stored_name, total_rows,
     tahun_min, tahun_max, golongan_list)
VALUES (?, ?, ?, ?, ?, ?, ?)
```

MySQL menolak INSERT karena `uuid` adalah NOT NULL tanpa DEFAULT, sehingga PDO exception ditangkap, transaksi di-rollback, dan fungsi return `null`.

**Perbaikan:**
```php
// SESUDAH (DIPERBAIKI):
INSERT INTO uploads
    (uuid, user_id, original_name, stored_name, total_rows,
     tahun_min, tahun_max, golongan_list)
VALUES (?, ?, ?, ?, ?, ?, ?, ?)

// Dan di execute():
$stmt->execute([
    bin2hex(random_bytes(16)),  // <- uuid unik 32-char hex
    $user_id,
    ...
]);
```

---

## BUG #2 — `db_sync.php`: `db_get_analysis_full()` query kolom `params` yang tidak ada

**File:** `db_sync.php`, fungsi `db_get_analysis_full()`
**Dampak:** Tinggi. Ketika user membuka Dashboard 2, 3, 4 setelah login (data dari DB), query untuk mengambil contamination dan mode analisis selalu gagal karena kolom `params` tidak ada di tabel `analysis_history`.

**Penyebab:**
```php
// SEBELUM (RUSAK):
$stmt = $pdo->prepare('SELECT params FROM analysis_history WHERE upload_id = ? ...');
// ERROR: Column 'params' doesn't exist in table analysis_history
if ($hist && !empty($hist['params'])) {
    $params = json_decode($hist['params'], true);
    ...
}
```

Skema `analysis_history` tidak memiliki kolom `params`. Kolom yang benar adalah `filter_mode` dan `contamination`.

**Perbaikan:**
```php
// SESUDAH (DIPERBAIKI):
$stmt = $pdo->prepare('SELECT filter_mode, contamination FROM analysis_history WHERE upload_id = ? ...');
if ($hist) {
    if (!empty($hist['contamination'])) $contamination = (string)$hist['contamination'];
    if (!empty($hist['filter_mode']))   $mode = $hist['filter_mode'];
}
```

---

## BUG #3 — `auth.php` + `api.php`: `history_save()` return `bool` tapi dipakai untuk link history

**File:** `auth.php` (fungsi `history_save()`), `api.php` (case `analyze`)
**Dampak:** Sedang. Fungsi `db_link_history()` tidak pernah terpanggil dengan benar, menyebabkan `analysis_results.history_id` selalu NULL. History ada tetapi tidak terhubung ke hasil analisis — fitur "Lihat Kembali" (replay dari history) tidak bisa memuat data.

**Penyebab:**
```php
// auth.php — SEBELUM:
function history_save(array $params): bool {
    ...
    return true;  // hanya bool
}

// api.php — SEBELUM:
$hist_saved = history_save($hist_params);
if ($upload_id && $hist_saved) {
    $pdo = db_connect();
    if ($pdo) {
        // SALAH: LAST_INSERT_ID() bisa sudah berubah jika ada query lain
        $last_hist = $pdo->query('SELECT LAST_INSERT_ID() AS id')->fetchColumn();
        if ($last_hist) db_link_history($upload_id, (int)$last_hist);
    }
}
```

**Perbaikan:**
```php
// auth.php — SESUDAH:
function history_save(array $params): int|false {
    ...
    $history_id = (int)$pdo->lastInsertId();
    return $history_id;  // return ID langsung
}

// api.php — SESUDAH:
$hist_id = history_save($hist_params);
if ($upload_id && $hist_id) {
    db_link_history($upload_id, (int)$hist_id);  // clean, no race condition
}
```

---

## BUG #4 — `js/app.js`: `Paginator.slice()` crash pada array kosong

**File:** `js/app.js`, kelas `Paginator`
**Dampak:** Sedang. Jika filter menghasilkan 0 data (tidak ada anomali pada filter tertentu), semua Dashboard yang menggunakan tabel akan crash dengan `TypeError` dan halaman berhenti merespons.

**Penyebab:**
```js
// SEBELUM:
slice(arr) {
    // Tidak ada guard untuk array kosong
    const start = (this.current - 1) * this.pageSize;
    return arr.slice(start, start + this.pageSize);
}
```

Jika `arr` kosong dan `this.current > totalPages()`, `Math.ceil(0/20) = 0`, maka `totalPages = 0` dan `current > 0` → undefined behavior.

**Perbaikan:**
```js
// SESUDAH:
totalPages(arr) {
    if (!arr || arr.length === 0) return 1;
    return Math.max(1, Math.ceil(arr.length / this.pageSize));
}
slice(arr) {
    if (!arr || arr.length === 0) return [];
    const total = this.totalPages(arr);
    if (this.current > total) this.current = total;  // clamp
    const start = (this.current - 1) * this.pageSize;
    return arr.slice(start, start + this.pageSize);
}
```

---

## BUG #5 — `js/app.js`: `showAlert()` tidak menampilkan ikon + tidak ada auto-close

**File:** `js/app.js`, fungsi `showAlert()`
**Dampak:** Minor/UX. Alert muncul tanpa ikon (SVG path tidak dirender) dan tidak pernah hilang otomatis—pengguna harus refresh untuk membersihkan notifikasi lama.

**Penyebab:** Fungsi `showAlert` tidak memiliki mapping ikon per tipe alert, dan tidak ada timer `setTimeout` untuk auto-close.

**Perbaikan:**
- Tambahkan mapping SVG icon untuk setiap tipe (`danger`, `warning`, `success`, `info`)
- Tambahkan parameter `autoClose` (default 6000ms)
- Tambahkan tombol ✕ untuk close manual

---

## BUG #6 — `js/app.js`: `API.call()` crash jika server return HTML error (bukan JSON)

**File:** `js/app.js`, fungsi `API.call()`
**Dampak:** Tinggi dalam kondisi error PHP. Jika PHP menghasilkan warning/notice/fatal error (misalnya saat Python tidak ditemukan), response bukan JSON. `resp.json()` throw SyntaxError yang tidak tertangkap dengan baik, menyebabkan loading spinner tidak hilang dan UI freeze.

**Perbaikan:**
```js
// SESUDAH:
const text = await resp.text();
try {
    return JSON.parse(text);
} catch {
    console.error('API non-JSON response:', text.substring(0, 300));
    return { status: 'error', message: 'Server error: response bukan JSON. Cek log PHP.' };
}
```

---

## PERUBAHAN UI/DESAIN

**File:** `css/style.css`, `includes/navbar.php`, `login.php`

- **Design System v2.0**: Migrasi dari warna warni tidak konsisten ke token CSS yang terstruktur (`--brand-blue`, `--bg-card`, `--border`, dll.)
- **Font baru**: Dari `Plus Jakarta Sans` ke `Outfit` (heading/angka) + `DM Sans` (body) — lebih profesional dan mudah dibaca untuk data
- **Navbar**: Gradient lebih halus, item navigasi dengan ikon konsisten, dark mode lebih dalam
- **Login page**: Layout terpusat dengan gradient background, card shadow lebih dalam
- **Dark mode**: Warna background gelap lebih dalam (`#080E1C`) dan kontras lebih baik
- **Tabel**: Border-left merah pada baris anomali, z-score badge lebih jelas
- **Animasi**: `dropIn` untuk dropdown, `alertIn` untuk alert, hover card dengan `translateY(-1px)`
- **Scrollbar**: Custom scrollbar tipis yang sesuai tema

---

*Dokumen ini dibuat otomatis saat rombak sistem v2.0 — 01 Mei 2026*
