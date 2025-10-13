<?php
/**
 * OperaSys - Cerrar Sesión
 * Archivo: modules/auth/logout.php
 * Descripción: Destruir sesión y redirigir al login
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir base de datos para auditoría
require_once '../../config/database.php';

// Registrar en auditoría si hay sesión activa
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO auditoria (usuario_id, accion, detalle) 
            VALUES (?, 'logout', 'Cierre de sesión')
        ");
        $stmt->execute([$_SESSION['user_id']]);
    } catch (PDOException $e) {
        // Error silencioso en auditoría
    }
}

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión si existe
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destruir la sesión
session_destroy();

// Redirigir al login con JavaScript para evitar conflicto con Service Worker
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cerrando sesión...</title>
    <!-- AdminLTE CSS LOCAL -->
    <link rel="stylesheet" href="../../vendor/adminlte/dist/css/adminlte.min.css">
    <!-- Font Awesome LOCAL -->
    <link rel="stylesheet" href="../../vendor/adminlte/plugins/fontawesome-free/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/custom.css">
</head>
<body class="hold-transition login-page">
    <div class="login-box">
        <div class="card card-outline card-success">
            <div class="card-header text-center">
                <h1><b>Opera</b>Sys</h1>
            </div>
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-check-circle fa-4x text-success"></i>
                </div>
                <h4>Sesión Cerrada</h4>
                <p class="text-muted">Redirigiendo al login...</p>
                <div class="spinner-border text-primary mt-3" role="status">
                    <span class="sr-only">Cargando...</span>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Redirigir después de 1 segundo
        setTimeout(function() {
            window.location.replace('login.php');
        }, 1000);
    </script>
</body>
</html>