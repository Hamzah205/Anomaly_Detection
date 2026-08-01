<?php
/**
 * PDAM Anomaly Detection System — Auth Helper
 * Jangan di-require langsung; gunakan bootstrap.php
 */

// Guard: hanya jalankan satu kali
if (defined('PDAM_AUTH_LOADED')) return;
define('PDAM_AUTH_LOADED', true);

// config.php harus sudah di-load sebelumnya via bootstrap.php
// Tapi untuk keamanan, load jika belum ada
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../../config/config.php';
}

// ===== CEK STATUS LOGIN =====
function auth_check(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['username']);
}

function auth_user(): array {
    if (!auth_check()) return [];
    return [
        'id'       => (int)$_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email'    => $_SESSION['user_email'] ?? '',
    ];
}

// ===== LOGIN =====
function auth_login(string $login, string $password): array {
    $pdo = db_connect();
    if (!$pdo) return ['ok' => false, 'message' => 'Database tidak tersedia'];

    $stmt = $pdo->prepare(
        'SELECT id, username, email, password
         FROM users
         WHERE email = :a OR username = :b
         LIMIT 1'
    );
    $stmt->execute([':a' => $login, ':b' => $login]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['ok' => false, 'message' => 'Email/username atau password salah'];
    }

    // Regenerate session ID SEBELUM mengisi session — mencegah session fixation
    // dan mencegah "headers already sent" error karena dipanggil setelah output
    session_regenerate_id(true);

    $_SESSION['user_id']    = $user['id'];
    $_SESSION['username']   = $user['username'];
    $_SESSION['user_email'] = $user['email'];

    return ['ok' => true, 'username' => $user['username']];
}

// ===== REGISTER =====
function auth_register(string $username, string $email, string $password): array {
    $pdo = db_connect();
    if (!$pdo) return ['ok' => false, 'message' => 'Database tidak tersedia'];

    $username = trim($username);
    $email    = trim($email);

    if (mb_strlen($username) < 3 || mb_strlen($username) > 50) {
        return ['ok' => false, 'message' => 'Username harus 3–50 karakter'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'message' => 'Format email tidak valid'];
    }
    if (mb_strlen($password) < 6) {
        return ['ok' => false, 'message' => 'Password minimal 6 karakter'];
    }

    $chk = $pdo->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
    $chk->execute([$email, $username]);
    if ($chk->fetch()) {
        return ['ok' => false, 'message' => 'Username atau email sudah terdaftar'];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    $ins  = $pdo->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
    $ins->execute([$username, $email, $hash]);

    return ['ok' => true, 'message' => 'Registrasi berhasil'];
}

// ===== LOGOUT =====
function auth_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ===== SIMPAN HISTORY =====
function history_save(array $params): int|false {
    if (!auth_check()) return false;
    $pdo = db_connect();
    if (!$pdo) return false;

    // Debug logging
    error_log('history_save: upload_id=' . ($params['upload_id'] ?? 'NULL') . ', mode=' . ($params['mode'] ?? 'unknown'));

    $is_pgsql = defined('DB_DRIVER') && DB_DRIVER === 'pgsql';

    try {
        if ($is_pgsql) {
            $stmt = $pdo->prepare('
                INSERT INTO analysis_history
                    (user_id, upload_id, filter_mode, contamination, tahun_min, tahun_max,
                     golongan_filter, jumlah_data, jumlah_anomali, persentase, filename)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                RETURNING id
            ');
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO analysis_history
                    (user_id, upload_id, filter_mode, contamination, tahun_min, tahun_max,
                     golongan_filter, jumlah_data, jumlah_anomali, persentase, filename)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
        }
        $upload_id = !empty($params['upload_id']) ? (int)$params['upload_id'] : null;
        $stmt->execute([
            (int)$_SESSION['user_id'],
            $upload_id,
            $params['mode']          ?? 'near_tahun_per_golongan',
            $params['contamination'] ?? 'auto',
            !empty($params['tahun_min']) ? (int)$params['tahun_min'] : null,
            !empty($params['tahun_max']) ? (int)$params['tahun_max'] : null,
            $params['golongan']      ?? null,
            (int)($params['total']   ?? 0),
            (int)($params['anomali'] ?? 0),
            round((float)($params['pct'] ?? 0), 2),
            $params['filename']      ?? null,
        ]);

        if ($is_pgsql) {
            $history_id = (int)$stmt->fetchColumn();
        } else {
            $history_id = (int)$pdo->lastInsertId();
        }
        error_log('history_save: success, history_id=' . $history_id . ', upload_id=' . ($upload_id ?? 'NULL'));
        return $history_id;  // BUG FIX: return int ID not bool, for db_link_history
    } catch (PDOException $e) {
        error_log('history_save error: ' . $e->getMessage());
        return false;  // Returns false on error
    }
}

// ===== AMBIL HISTORY USER =====
function history_get(int $user_id, int $limit = 100): array {
    $pdo = db_connect();
    if (!$pdo) return [];
    $stmt = $pdo->prepare(
        'SELECT * FROM analysis_history
         WHERE user_id = ?
         ORDER BY created_at DESC
         LIMIT ?'
    );
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll();
}

// ===== GUEST MODE HELPERS =====
function is_guest(): bool {
    return empty($_SESSION['user_id']);
}

function get_session_id(): string {
    if (is_guest()) {
        return $_SESSION['guest_id'] ?? 'unknown';
    }
    return 'user_' . ($_SESSION['user_id'] ?? '0');
}

function get_guest_json_path(): string {
    return JSON_DIR . 'guest_' . ($_SESSION['guest_id'] ?? 'temp') . '.json';
}

function get_user_upload_path(int $user_id): string {
    $timestamp = date('Ymd_His');
    return DATA_DIR . "user_{$user_id}_{$timestamp}.xlsx";
}

/**
 * Hapus semua file guest yang expired (default 30 menit)
 * Safe: hanya hapus file guest_, tidak kena file user_
 */
function cleanup_guest_files(int $max_age_minutes = 30): array {
    $max_age = $max_age_minutes * 60; // convert ke detik
    $now = time();
    $deleted = ['excel' => 0, 'json' => 0, 'errors' => []];
    
    // Hapus guest Excel files
    $excel_files = glob(DATA_DIR . 'guest_*.xlsx');
    foreach ($excel_files as $file) {
        if (($now - filemtime($file)) > $max_age) {
            if (@unlink($file)) {
                $deleted['excel']++;
            } else {
                $deleted['errors'][] = basename($file);
            }
        }
    }
    
    // Hapus guest JSON files
    $json_files = glob(JSON_DIR . 'guest_*.json');
    foreach ($json_files as $file) {
        if (($now - filemtime($file)) > $max_age) {
            if (@unlink($file)) {
                $deleted['json']++;
            } else {
                $deleted['errors'][] = basename($file);
            }
        }
    }
    
    return $deleted;
}

// ===== LABEL MODE =====
function mode_label(string $mode): string {
    $map = [
        'near_tahun_per_golongan'    => 'Near Tahun per Golongan',
        'near_tahun_near_golongan'   => 'Near Tahun vs Near Golongan',
        'multi_tahun_per_golongan'   => 'Multi Tahun per Golongan',
        'multi_tahun_semua_golongan' => 'Multi Tahun Semua Golongan',
    ];
    return $map[$mode] ?? $mode;
}
