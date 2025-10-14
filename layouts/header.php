<?php

/**
 * OperaSys - Layout Header
 * Archivo: layouts/header.php
 * Descripción: Encabezado HTML y CSS (reutilizable)
 */

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . '/modules/auth/login.php');
    exit;
}

// Determinar la ruta base según la ubicación del archivo
$depth = isset($page_depth) ? $page_depth : 2; // Por defecto 2 niveles (modules/admin/)
$base_path = str_repeat('../', $depth);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2E86AB">
    <link rel="manifest" href="/operasys/manifest.json">
    <title><?php echo $page_title ?? 'OperaSys'; ?> - <?php echo SITE_NAME; ?></title>

    <!-- PWA Meta Tags -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="OperaSys">
    <link rel="apple-touch-icon" href="/operasys/assets/images/icons/icon-192x192.png">
    <link rel="shortcut icon" href="/operasys/assets/images/icons/icon-192x192.png" type="image/x-icon">

    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/operasys/service-worker.js')
                    .then(reg => console.log('✓ Service Worker registrado'))
                    .catch(err => console.error('✗ Error SW:', err));
            });
        }
    </script>

    <!-- AdminLTE CSS LOCAL -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>vendor/adminlte/dist/css/adminlte.min.css">
    <!-- Font Awesome LOCAL -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>vendor/adminlte/plugins/fontawesome-free/css/all.min.css">

    <?php if (isset($use_datatables) && $use_datatables): ?>
        <!-- DataTables LOCAL -->
        <link rel="stylesheet" href="<?php echo $base_path; ?>vendor/adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
        <link rel="stylesheet" href="<?php echo $base_path; ?>vendor/adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <?php endif; ?>

    <?php if (isset($use_sweetalert) && $use_sweetalert): ?>
        <!-- SweetAlert2 LOCAL -->
        <link rel="stylesheet" href="<?php echo $base_path; ?>vendor/adminlte/plugins/sweetalert2/sweetalert2.min.css">
    <?php endif; ?>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/custom.css">

    <?php if (isset($extra_css)): ?>
        <!-- CSS Adicional -->
        <?php echo $extra_css; ?>
    <?php endif; ?>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">