<?php
/**
 * PDAM Anomaly Detection System — Bootstrap
 * File ini di-require_once di AWAL setiap halaman PHP.
 * Mencegah double-load session, config, dan auth.
 */

// Guard: hanya jalankan satu kali
if (defined('PDAM_BOOTSTRAP_LOADED')) return;
define('PDAM_BOOTSTRAP_LOADED', true);

// Mulai session SEKALI
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Guest ID untuk isolasi session (hanya jika belum ada)
if (empty($_SESSION['guest_id'])) {
    $_SESSION['guest_id'] = bin2hex(random_bytes(8));
}

// Load config (path, DB, Python)
require_once __DIR__ . '/config.php';

// Load auth helper
require_once __DIR__ . '/auth.php';
