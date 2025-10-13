<?php
/**
 * OperaSys - Página Principal
 * Archivo: index.php
 * Descripción: Redirección según estado de sesión
 */

require_once 'config/config.php';

// Si el usuario ya está autenticado, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Redirigiendo...</title>
    </head>
    <body>
        <script>window.location.replace('modules/admin/dashboard.php');</script>
        <noscript>
            <meta http-equiv="refresh" content="0;url=modules/admin/dashboard.php">
        </noscript>
    </body>
    </html>
    <?php
    exit;
}

// Si no está autenticado, redirigir al login
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirigiendo...</title>
</head>
<body>
    <script>window.location.replace('modules/auth/login.php');</script>
    <noscript>
        <meta http-equiv="refresh" content="0;url=modules/auth/login.php">
    </noscript>
</body>
</html>