<?php
/**
 * Reset Database - PDAM Anomaly Detection
 * Hapus semua data tapi pertahankan struktur tabel
 * Akses via: http://localhost/x5/reset_db.php?confirm=yes
 */

require_once __DIR__ . '/bootstrap.php';

header('Content-Type: text/html; charset=utf-8');

// Safety: require confirmation
if (($_GET['confirm'] ?? '') !== 'yes') {
    echo '<!DOCTYPE html>
<html>
<head><title>Reset Database</title></head>
<body style="font-family:sans-serif; padding:40px; max-width:600px; margin:0 auto;">
    <h1>⚠️ Reset Database</h1>
    <p>Perintah ini akan <strong>menghapus SEMUA data</strong> dari database:</p>
    <ul>
        <li>Semua user (kecuali admin)</li>
        <li>Semua upload</li>
        <li>Semua raw_data</li>
        <li>Semua analysis_results</li>
        <li>Semua analysis_history</li>
    </ul>
    <p><strong>Struktur tabel akan tetap ada.</strong></p>
    <p style="background:#ffebee; padding:15px; border-radius:8px; color:#c62828;">
        ⚠️ Data yang dihapus TIDAK bisa dikembalikan!
    </p>
    <p>
        <a href="reset_db.php?confirm=yes" 
           style="background:#d32f2f; color:white; padding:12px 24px; text-decoration:none; border-radius:6px; display:inline-block;"
           onclick="return confirm(\'Yakin ingin menghapus SEMUA data? Data tidak bisa dikembalikan!\')">
            ✗ YA, Hapus Semua Data
        </a>
        &nbsp;&nbsp;
        <a href="index.php" style="background:#eee; color:#333; padding:12px 24px; text-decoration:none; border-radius:6px; display:inline-block;">
            Batal
        </a>
    </p>
</body></html>';
    exit;
}

// Check if user is logged in as admin (optional safety)
$is_admin = auth_check() && ($_SESSION['username'] ?? '') === 'admin';

$pdo = db_connect();
if (!$pdo) {
    die('<h2>❌ Error</h2><p>Database tidak terhubung!</p><a href="index.php">Kembali</a>');
}

$is_pgsql = defined('DB_DRIVER') && DB_DRIVER === 'pgsql';

try {
    $pdo->beginTransaction();
    
    // Get counts before delete
    $counts = [];
    $tables = ['analysis_results', 'raw_data', 'analysis_history', 'uploads', 'users'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $counts[$table] = $stmt->fetchColumn();
    }
    
    if ($is_pgsql) {
        // PostgreSQL: TRUNCATE dengan CASCADE lebih efisien
        $pdo->exec('TRUNCATE analysis_results, raw_data, analysis_history, uploads CASCADE');
        // PostgreSQL pakai single quote untuk string literal
        $pdo->exec("DELETE FROM users WHERE username <> 'admin'");
    } else {
        // MySQL: DELETE manual urutan child ke parent
        $pdo->exec('DELETE FROM analysis_results');
        $pdo->exec('DELETE FROM raw_data');
        $pdo->exec('DELETE FROM analysis_history');
        $pdo->exec('DELETE FROM uploads');
        $pdo->exec('DELETE FROM users WHERE username != "admin"');
    }
    
    // Reset sequence / auto_increment counters
    if ($is_pgsql) {
        $sequences = [
            'users' => 'users_id_seq',
            'uploads' => 'uploads_id_seq',
            'raw_data' => 'raw_data_id_seq',
            'analysis_history' => 'analysis_history_id_seq',
            'analysis_results' => 'analysis_results_id_seq',
        ];
        foreach ($sequences as $table => $seq) {
            try {
                $pdo->exec("ALTER SEQUENCE $seq RESTART WITH 1");
            } catch (PDOException $e) {
                // Abaikan kalau sequence tidak ada
            }
        }
    } else {
        foreach ($tables as $table) {
            $pdo->exec("ALTER TABLE $table AUTO_INCREMENT = 1");
        }
    }
    
    $pdo->commit();
    
    // Clear session data related to uploads
    unset($_SESSION['user_last_upload_id']);
    unset($_SESSION['user_last_result']);
    unset($_SESSION['current_upload_path']);
    unset($_SESSION['user_upload_file']);
    unset($_SESSION['last_upload_name']);
    
    echo '<!DOCTYPE html>
<html>
<head><title>Reset Berhasil</title></head>
<body style="font-family:sans-serif; padding:40px; max-width:600px; margin:0 auto;">
    <h1>✅ Database Berhasil Direset</h1>
    <p>Semua data telah dihapus. Struktur tabel tetap utuh.</p>
    
    <h3>Data yang dihapus:</h3>
    <table border="1" cellpadding="8" style="border-collapse:collapse; width:100%;">
        <tr style="background:#f5f5f5;">
            <th>Tabel</th>
            <th>Jumlah Record (sebelum)</th>
            <th>Status</th>
        </tr>';
    
    foreach ($counts as $table => $count) {
        $status = $table === 'users' ? 'Keep admin' : '✓ Dihapus';
        echo "<tr>
            <td>$table</td>
            <td style=\"text-align:center;\">$count</td>
            <td>$status</td>
        </tr>";
    }
    
    echo '</table>
    
    <p style="margin-top:30px;">
        <a href="index.php" style="background:#1976d2; color:white; padding:12px 24px; text-decoration:none; border-radius:6px; display:inline-block;">
            🚀 Mulai Analisis Baru
        </a>
        &nbsp;&nbsp;
        <a href="register.php" style="background:#43a047; color:white; padding:12px 24px; text-decoration:none; border-radius:6px; display:inline-block;">
            📝 Buat Akun Baru
        </a>
    </p>
</body></html>';
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo '<h2>❌ Error</h2><p>' . htmlspecialchars($e->getMessage()) . '</p><a href="index.php">Kembali</a>';
}
