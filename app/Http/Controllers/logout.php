<?php
require_once __DIR__ . '/../../bootstrap/bootstrap.php';

// Cleanup guest files sebelum logout (jika ada)
if (is_guest()) {
    cleanup_guest_files();
}

auth_logout();
header('Location: ' . url('/login?logged_out=1'));
exit;
