<?php
/**
 * PDAM Anomaly Detection System — Bootstrap
 * File ini di-require_once di AWAL setiap halaman PHP.
 * Mencegah double-load session, config, dan auth.
 */

// Guard: hanya jalankan satu kali
if (defined('PDAM_BOOTSTRAP_LOADED')) return;
define('PDAM_BOOTSTRAP_LOADED', true);

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('RESOURCES_PATH', BASE_PATH . '/resources');

// Detect BASE_URL (berfungsi untuk akses via subdirectory maupun root)
$__base_script = str_replace('\\', '/', BASE_PATH);
$__base_root   = str_replace('\\', '/', isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '');
if ($__base_root !== '' && strpos($__base_script, $__base_root) === 0) {
    $__base_sub = substr($__base_script, strlen($__base_root));
    define('BASE_URL', rtrim($__base_sub, '/'));
} else {
    define('BASE_URL', '');
}

// Helper URL: url('/dashboard1') menghasilkan path absolut yang benar
function url(string $path = ''): string {
    $path = ($path === '' || $path[0] !== '/') ? '/' . $path : $path;
    return BASE_URL . $path;
}

// Mulai session SEKALI
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Guest ID untuk isolasi session (hanya jika belum ada)
if (empty($_SESSION['guest_id'])) {
    $_SESSION['guest_id'] = bin2hex(random_bytes(8));
}

// Load config (path, DB, Python)
require_once CONFIG_PATH . '/config.php';

// Load auth helper
require_once APP_PATH . '/Http/Controllers/auth.php';
