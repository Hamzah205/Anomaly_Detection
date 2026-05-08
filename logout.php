<?php
require_once __DIR__ . '/bootstrap.php';

// Cleanup guest files sebelum logout (jika ada)
if (is_guest()) {
    cleanup_guest_files();
}

auth_logout();
header('Location: login.php?logged_out=1');
exit;
