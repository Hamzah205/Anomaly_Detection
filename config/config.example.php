<?php
/**
 * PDAM Anomaly Detection System
 * Contoh konfigurasi utama: Python + MySQL/PostgreSQL + Path sistem.
 *
 * CARA PAKAI:
 *   1. Salin file ini menjadi config/config.php
 *   2. Sesuaikan Python path, kredensial database, dsb.
 *
 * config/config.php TIDAK di-commit ke git (lihat .gitignore).
 */

// ===== PYTHON PATH =====
define('PYTHON_PATHS', [
    'C:\\Users\\Ilham\\AppData\\Local\\Python\\pythoncore-3.14-64\\python.exe',
    'C:\\Users\\Ilham\\AppData\\Local\\Python\\bin\\python.exe',
    'python3',
    'python',
]);

// ===== PATH SISTEM =====
define('BASE_DIR',      dirname(__DIR__));
define('DATA_DIR',      BASE_DIR . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR);
define('JSON_DIR',      BASE_DIR . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app'  . DIRECTORY_SEPARATOR);
define('PYTHON_DIR',    BASE_DIR . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'python' . DIRECTORY_SEPARATOR);
define('PYTHON_SCRIPT', PYTHON_DIR . 'analyze.py');


// ===== DATABASE CONFIG =====
// Pilih driver: 'mysql' atau 'pgsql'
define('DB_DRIVER', 'pgsql');     // Ganti ke 'mysql' kalau pakai XAMPP/MySQL
define('DB_HOST', 'localhost');
define('DB_PORT', DB_DRIVER === 'pgsql' ? '5432' : '3306');
define('DB_NAME', 'pdam_anomaly');
define('DB_USER', DB_DRIVER === 'pgsql' ? 'postgres' : 'root');
define('DB_PASS', '');            // Default XAMPP/Laragon: kosong
define('DB_CHARSET', DB_DRIVER === 'pgsql' ? 'utf8' : 'utf8mb4');

// ===== FUNGSI KONEKSI DB =====
function db_connect(): ?PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    try {
        if (DB_DRIVER === 'pgsql') {
            $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME;
        } else {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT
                . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        }
        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
        return $pdo;
    } catch (PDOException $e) {
        // DB tidak tersedia — sistem tetap berjalan (mode guest)
        return null;
    }
}

function db_available(): bool {
    return db_connect() !== null;
}

// ===== FUNGSI DETEKSI PYTHON =====
function detect_python(): ?string {
    foreach (PYTHON_PATHS as $path) {
        $out = shell_exec('"' . $path . '" --version 2>&1');
        if ($out && stripos($out, 'python') !== false) return $path;
    }
    return null;
}

function check_python_deps(string $python_path): bool {
    $out = shell_exec('"' . $python_path . '" -c "import sklearn,scipy,pandas,openpyxl; print(\'ok\')" 2>&1');
    return trim($out) === 'ok';
}

// ===== INISIALISASI DIREKTORI =====
foreach ([DATA_DIR, JSON_DIR, BASE_DIR . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs'] as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
}
