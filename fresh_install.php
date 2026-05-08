<?php
/**
 * Fresh Install — PDAM Anomaly Detection
 * DROP semua tabel + recreate dari database.sql + hapus semua file upload
 * Akses: http://localhost/x5/fresh_install.php?confirm=yes
 */

require_once __DIR__ . '/bootstrap.php';

header('Content-Type: text/html; charset=utf-8');

// ── KONFIGURASI ──
$is_pgsql = defined('DB_DRIVER') && DB_DRIVER === 'pgsql';
$SQL_FILE = __DIR__ . ($is_pgsql ? '/database_postgres.sql' : '/database.sql');
$DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;
$JSON_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'json' . DIRECTORY_SEPARATOR;

// ── CONFIRMATION PAGE ──
if (($_GET['confirm'] ?? '') !== 'yes') {
    echo '<!DOCTYPE html>
<html>
<head><title>Fresh Install</title></head>
<body style="font-family:sans-serif; padding:40px; max-width:600px; margin:0 auto;">
    <h1 style="color:#d32f2f">🗑️ Fresh Install</h1>
    <p>Perintah ini akan <strong>MENGHAPUS SEMUA DATA DAN FILE</strong>:</p>
    <ul>
        <li>Drop semua tabel database</li>
        <li>Recreate tabel dari <code>database.sql</code></li>
        <li>Hapus semua file Excel di <code>data/</code></li>
        <li>Hapus semua file JSON di <code>json/</code></li>
        <li>Clear session data</li>
    </ul>
    <p style="background:#ffebee; padding:15px; border-radius:8px; color:#c62828;">
        ⚠️ Ini sama seperti install dari NOL. Semua data user, upload, dan hasil analisis akan hilang!
    </p>
    <p>
        <a href="fresh_install.php?confirm=yes"
           style="background:#d32f2f; color:white; padding:12px 24px; text-decoration:none; border-radius:6px; display:inline-block;"
           onclick="return confirm(\'YAKIN install ulang dari NOL? Semua data akan hilang!\')">
            ✗ YA, Install Ulang
        </a>
        &nbsp;&nbsp;
        <a href="index.php" style="background:#eee; color:#333; padding:12px 24px; text-decoration:none; border-radius:6px; display:inline-block;">
            Batal
        </a>
    </p>
</body></html>';
    exit;
}

// ── EXECUTE FRESH INSTALL ──
$pdo = db_connect();
if (!$pdo) {
    die('<h2>❌ Error</h2><p>Database tidak terhubung! Cek config.php</p><a href="index.php">Kembali</a>');
}

if (!file_exists($SQL_FILE)) {
    die('<h2>❌ Error</h2><p>File <code>database.sql</code> tidak ditemukan!</p><a href="index.php">Kembali</a>');
}

$logs = [];
$errors = [];

try {
    // ── 1. DROP semua tabel (kalau ada) ──
    $tables = ['analysis_results','analysis_history','raw_data','uploads','users','audit_log'];
    foreach ($tables as $t) {
        try {
            $dropSql = $is_pgsql ? "DROP TABLE IF EXISTS $t CASCADE" : "DROP TABLE IF EXISTS $t";
            $pdo->exec($dropSql);
            $logs[] = "✓ DROP TABLE $t";
        } catch (PDOException $e) {
            $errors[] = "✗ DROP TABLE $t: " . $e->getMessage();
        }
    }

    // Kalau PostgreSQL, hapus juga tipe ENUM
    if ($is_pgsql) {
        foreach (['user_role','anomaly_level','analysis_status'] as $etype) {
            try {
                $pdo->exec("DROP TYPE IF EXISTS $etype CASCADE");
                $logs[] = "✓ DROP TYPE $etype";
            } catch (PDOException $e) {
                $errors[] = "✗ DROP TYPE $etype: " . $e->getMessage();
            }
        }
    }

    // ── 2. RUN SQL file ──
    $sql = file_get_contents($SQL_FILE);
    $statements = array_filter(array_map('trim', preg_split('/;\s*(\r?\n|$)/', $sql)));

    // MySQL-specific setup
    if (!$is_pgsql) {
        $pdo->exec("SET NAMES utf8mb4");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    }

    foreach ($statements as $stmt) {
        if (empty($stmt)) continue;
        // Skip CREATE DATABASE / USE untuk PostgreSQL (tidak cocok dengan DSN yang sudah specify db)
        if ($is_pgsql && preg_match('/^CREATE\s+DATABASE|^USE\s+/i', $stmt)) {
            continue;
        }
        try {
            $pdo->exec($stmt);
            $first = strtoupper(strtok($stmt, " \n\r\t"));
            if (in_array($first, ['CREATE','INSERT','DROP','USE'])) {
                $logs[] = "✓ $first ... " . substr(str_replace("\n", " ", $stmt), 0, 50) . "...";
            }
        } catch (PDOException $e) {
            $errors[] = "✗ SQL Error: " . $e->getMessage() . "<br><small>" . htmlspecialchars(substr($stmt,0,100)) . "...</small>";
        }
    }

    if (!$is_pgsql) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }

    // ── 3. HAPUS FILE UPLOAD ──
    $deleted_files = 0;
    foreach (glob($DATA_DIR . '*') as $f) {
        if (is_file($f)) {
            @unlink($f);
            $deleted_files++;
        }
    }
    $logs[] = "✓ Hapus $deleted_files file di data/";

    // ── 4. HAPUS FILE JSON ──
    $deleted_json = 0;
    foreach (glob($JSON_DIR . '*') as $f) {
        if (is_file($f)) {
            @unlink($f);
            $deleted_json++;
        }
    }
    $logs[] = "✓ Hapus $deleted_json file di json/";

    // ── 5. CLEAR SESSION ──
    unset($_SESSION['user_last_upload_id']);
    unset($_SESSION['user_last_result']);
    unset($_SESSION['current_upload_path']);
    unset($_SESSION['user_upload_file']);
    unset($_SESSION['last_upload_name']);
    unset($_SESSION['guest_result']);
    $logs[] = "✓ Clear session variables";

} catch (Exception $e) {
    $errors[] = "✗ Fatal: " . $e->getMessage();
}

// ── OUTPUT ──
echo '<!DOCTYPE html>
<html>
<head><title>Fresh Install Selesai</title></head>
<body style="font-family:sans-serif; padding:40px; max-width:700px; margin:0 auto;">
    <h1>✅ Fresh Install Selesai</h1>';

if (!empty($errors)) {
    echo '<div style="background:#ffebee; padding:15px; border-radius:8px; color:#c62828; margin-bottom:20px;">
        <h3>❌ Errors:</h3><ul>';
    foreach ($errors as $e) echo "<li>$e</li>";
    echo '</ul></div>';
}

echo '<div style="background:#e8f5e9; padding:15px; border-radius:8px; margin-bottom:20px;">
    <h3>📋 Log:</h3><ul style="font-size:13px; line-height:1.8;">';
foreach ($logs as $l) echo "<li>$l</li>";
echo '</ul></div>';

echo '<p style="background:#fff3e0; padding:15px; border-radius:8px;">
    <strong>Default login:</strong><br>
    Username: <code>admin</code> | Password: <code>admin123</code><br>
    Username: <code>demo</code> | Password: <code>admin123</code>
</p>

<p style="margin-top:30px;">
    <a href="index.php" style="background:#1976d2; color:white; padding:12px 24px; text-decoration:none; border-radius:6px; display:inline-block;">
        🚀 Mulai Aplikasi
    </a>
    &nbsp;&nbsp;
    <a href="register.php" style="background:#43a047; color:white; padding:12px 24px; text-decoration:none; border-radius:6px; display:inline-block;">
        📝 Buat Akun Baru
    </a>
</p>
</body></html>';
