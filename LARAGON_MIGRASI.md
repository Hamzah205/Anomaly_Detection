# Panduan Pindah ke Laragon + PostgreSQL

> Sistem sekarang **support PostgreSQL** sebagai database utama.

## Langkah 1: Install Laragon

1. Download: https://laragon.org/download/
2. Install di `C:\laragon` (default)
3. Jalankan Laragon

## Langkah 2: Install PostgreSQL

Laragon **tidak include** PostgreSQL. Install manual:

1. Download: https://www.postgresql.org/download/windows/
2. Install → catat **password** superuser (postgres)
3. Buka **pgAdmin 4** atau **psql**
4. Buat database:
```sql
CREATE DATABASE pdam_anomaly;
```

## Langkah 3: Copy Project

```
C:\xampp\htdocs\x5   →   C:\laragon\www\x5
```

## Langkah 4: Edit Config

Buka `C:\laragon\www\x5\config.php`, pastikan ini:

```php
define('DB_DRIVER', 'pgsql');
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'pdam_anomaly');
define('DB_USER', 'postgres');
define('DB_PASS', 'password_anda');  // password yang dibuat saat install PostgreSQL
```

## Langkah 5: Rebuild Database (Auto)

Buka browser:
```
http://localhost/x5/fresh_install.php?confirm=yes
```

Klik **"YA, Install Ulang"**

**Yang terjadi otomatis:**
- Drop tabel & tipe ENUM lama
- Recreate tabel dari `database_postgres.sql`
- Hapus file Excel & JSON bekas
- Clear session

## Langkah 6: Test

```
http://localhost/x5
```

Login: `admin` / `admin123`

---

## Catatan PostgreSQL

| Setting | Nilai |
|---------|-------|
| Host | `localhost` |
| Port | `5432` |
| User | `postgres` (default) |
| Password | sesuai saat install |

**Python** tidak include di Laragon. Install manual dari python.org kalau belum ada.

**Library Python** yang perlu diinstall:
```bash
pip install scikit-learn pandas openpyxl numpy
```
