<?php
/**
 * PDAM Anomaly Detection System
 * Cleanup script untuk file guest yang expired
 * 
 * Jalankan via cron atau manual:
 * php cleanup_guest.php [max_age_hours]
 */

require_once __DIR__ . '/../../bootstrap/bootstrap.php';

$max_age_hours = isset($argv[1]) ? (int)$argv[1] : 24; // Default 24 jam
$max_age_seconds = $max_age_hours * 3600;

$now = time();
$cleaned = 0;
$errors = [];

// Cleanup guest Excel files
$guest_data_files = glob(DATA_DIR . 'guest_*.xlsx');
foreach ($guest_data_files as $file) {
    $age = $now - filemtime($file);
    if ($age > $max_age_seconds) {
        if (@unlink($file)) {
            $cleaned++;
            echo "[OK] Deleted: " . basename($file) . " (age: " . round($age/3600, 1) . "h)\n";
        } else {
            $errors[] = "Failed to delete: " . basename($file);
        }
    }
}

// Cleanup guest JSON files
$guest_json_files = glob(JSON_DIR . 'guest_*.json');
foreach ($guest_json_files as $file) {
    $age = $now - filemtime($file);
    if ($age > $max_age_seconds) {
        if (@unlink($file)) {
            $cleaned++;
            echo "[OK] Deleted: " . basename($file) . " (age: " . round($age/3600, 1) . "h)\n";
        } else {
            $errors[] = "Failed to delete: " . basename($file);
        }
    }
}

// Cleanup old user files (keep last 30 days)
$user_max_age = 30 * 24 * 3600; // 30 hari
$user_files = glob(DATA_DIR . 'user_*.xlsx');
$user_cleaned = 0;
foreach ($user_files as $file) {
    $age = $now - filemtime($file);
    if ($age > $user_max_age) {
        if (@unlink($file)) {
            $user_cleaned++;
            echo "[OK] Deleted old user file: " . basename($file) . " (age: " . round($age/86400, 1) . "d)\n";
        }
    }
}

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "CLEANUP SUMMARY\n";
echo str_repeat("=", 50) . "\n";
echo "Guest files cleaned: {$cleaned}\n";
echo "Old user files cleaned: {$user_cleaned}\n";
echo "Max age (guest): {$max_age_hours} hours\n";
echo "Max age (user): 30 days\n";

if (!empty($errors)) {
    echo "\nErrors:\n";
    foreach ($errors as $err) {
        echo "  - {$err}\n";
    }
    exit(1);
}

echo "\nDone.\n";
exit(0);
