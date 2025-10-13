<?php
/**
 * OperaSys - Layout Header para Autenticación
 * Archivo: layouts/auth_header.php
 * Descripción: Header para login/register (sin sidebar)
 */

// Determinar la ruta base
$base_path = isset($auth_base_path) ? $auth_base_path : '../../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'OperaSys'; ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- AdminLTE CSS LOCAL -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>vendor/adminlte/dist/css/adminlte.min.css">
    <!-- Font Awesome LOCAL -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>vendor/adminlte/plugins/fontawesome-free/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/custom.css">
</head>
<body class="hold-transition <?php echo $body_class ?? 'login-page'; ?>">