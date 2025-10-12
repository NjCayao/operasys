<?php
/**
 * OperaSys - Página Principal
 * Archivo: index.php
 * Descripción: Redirección según estado de sesión
 */

require_once 'config/config.php';

// Si el usuario ya está autenticado, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: modules/admin/dashboard.php');
    exit;
}

// Si no está autenticado, redirigir al login
header('Location: modules/auth/login.php');
exit;
