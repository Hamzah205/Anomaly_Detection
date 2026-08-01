<?php
/**
 * Common <head> partial for pages sharing Outfit + DM Sans + theme init
 * Include inside <head> from root-level files.
 */
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>document.documentElement.setAttribute('data-theme', localStorage.getItem('pdam_theme') || 'light');</script>
<script>window.BASE_URL = '<?= BASE_URL ?>';</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('/public/css/style.css') ?>?v=<?= filemtime(__DIR__ . '/../../public/css/style.css') ?>">
