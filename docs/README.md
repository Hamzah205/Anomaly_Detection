# PDAM Anomaly Detection System
## Perumdam Tirta Kencana Kota Samarinda

---

## Cara Install di XAMPP Windows

### 1. Copy folder ke htdocs
```
Ekstrak ZIP → Salin folder pdam_system ke:
C:\xampp\htdocs\pdam_sistem\
```

### 2. Setup MySQL Database
Buka phpMyAdmin (http://localhost/phpmyadmin), lalu:
- Klik **Import**
- Pilih file `database.sql` dari folder pdam_sistem
- Klik **Go**

Database `pdam_anomaly` akan otomatis dibuat.

Akun demo sudah tersedia:
- Username: `admin`
- Password: `admin123`

### 3. Sesuaikan config.php (jika perlu)
```php
// MySQL (default XAMPP sudah sesuai)
define('DB_HOST', 'localhost');
define('DB_NAME', 'pdam_anomaly');
define('DB_USER', 'root');
define('DB_PASS', '');   // Default XAMPP: kosong

// Python path (sudah dikonfigurasi untuk PC Anda)
define('PYTHON_PATHS', [
    'C:\\Users\\Ilham\\AppData\\Local\\Python\\pythoncore-3.14-64\\python.exe',
    'C:\\Users\\Ilham\\AppData\\Local\\Python\\bin\\python.exe',
]);
```

### 4. Install library Python
Buka CMD:
```
C:\Users\Ilham\AppData\Local\Python\pythoncore-3.14-64\python.exe -m pip install scikit-learn scipy pandas openpyxl
```

### 5. Jalankan XAMPP
- Start **Apache** + **MySQL** di XAMPP Control Panel
- Buka: http://localhost/pdam_sistem/

---

## Struktur File
```
pdam_sistem/
├── bootstrap.php      <- Load SEKALI per halaman (fix memory error)
├── config.php         <- Konfigurasi Python + MySQL
├── auth.php           <- Fungsi login, session, history
├── api.php            <- Backend API (upload, analyze, dll)
├── database.sql       <- Schema MySQL (import ke phpMyAdmin)
│
├── index.php          <- Dashboard 1: Upload & Parameter
├── dashboard2.php     <- Dashboard 2: Global Analysis
├── dashboard3.php     <- Dashboard 3: Visual Analytics
├── dashboard4.php     <- Dashboard 4: Detail Anomali
├── history.php        <- Dashboard 5: Riwayat (butuh login)
│
├── login.php          <- Halaman login
├── register.php       <- Halaman daftar akun
├── logout.php         <- Proses logout
│
├── includes/
│   └── navbar.php     <- Navbar bersama (tidak ada require di sini)
├── css/style.css
├── js/app.js
├── python/analyze.py
├── assets/logo.jpg
├── data/              <- File Excel upload (auto-dibuat)
└── json/              <- Hasil analisis JSON (auto-dibuat)
```

---

## Perbaikan Memory Error

Error `Fatal error: Allowed memory size exhausted in navbar.php` sudah diperbaiki dengan:
- `bootstrap.php` — di-load **sekali** di awal setiap halaman
- `navbar.php` — **tidak** memanggil require apapun
- Semua file menggunakan `define()` guard untuk mencegah double-load

---

## Alur Penggunaan

| Langkah | Halaman | Keterangan |
|---------|---------|------------|
| 1 | Dashboard 1 | Upload Excel, pilih contamination & mode |
| 2 | Dashboard 2 | Lihat hasil global, ubah filter & re-run |
| 3 | Dashboard 3 | Visualisasi chart + Z-Score |
| 4 | Dashboard 4 | Detail setiap anomali, export PDF/CSV |
| 5 | History | Riwayat analisis (butuh login) |

Login bersifat **opsional** — semua dashboard bisa diakses tanpa login.
