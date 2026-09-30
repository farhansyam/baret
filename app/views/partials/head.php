<?php
$siteName = setting('singkatan', 'BARET') . ' — ' . setting('nama_organisasi', 'Barisan Relawan Tangguh');
$pageTitle = !empty($title) ? $title . ' | ' . setting('singkatan', 'BARET') : $siteName;
$desc = $description ?? setting('tagline', 'Website resmi Barisan Relawan Tangguh.');
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e(str_limit($desc, 160)) ?>">
<meta name="theme-color" content="#0D0D0D">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e(str_limit($desc, 160)) ?>">
<meta property="og:type" content="website">
<meta property="og:image" content="<?= e(absolute_url('assets/img/logo-320.png')) ?>">
<link rel="icon" type="image/png" href="<?= asset('img/favicon.png') ?>">
<link rel="apple-touch-icon" href="<?= asset('img/apple-touch-icon.png') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,600;0,700;0,800;1,700;1,800&family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<script src="<?= asset('js/app.js') ?>"></script>
