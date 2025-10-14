<?php
/**
 * OperaSys - Configuración General
 * Archivo: config/config.php
 * Descripción: Configuraciones globales del sistema
 */

// Iniciar sesión
session_start();

// Configuración de la aplicación
define('SITE_URL', 'http://localhost/operasys');
define('SITE_NAME', 'OperaSys');
define('SITE_VERSION', '1.0');

// Versión de assets (cambiar cuando actualices JS/CSS)
define('ASSETS_VERSION', '1.0.9');

// Zona horaria
date_default_timezone_set('America/Lima');

// Configuración de errores (cambiar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Función para verificar si el usuario está autenticado
function verificarSesion() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . SITE_URL . '/modules/auth/login.php');
        exit;
    }
}

// Función para verificar rol de administrador
function verificarAdmin() {
    verificarSesion();
    if ($_SESSION['rol'] !== 'admin') {
        header('Location: ' . SITE_URL . '/modules/admin/dashboard.php');
        exit;
    }
}
