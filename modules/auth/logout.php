<?php
/**
 * OperaSys - Cerrar Sesión
 * Archivo: modules/auth/logout.php
 * Descripción: Destruir sesión y redirigir al login
 */

// Incluir configuración (ya inicia la sesión)
require_once '../../config/config.php';
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

// Redirigir al login
header('Location: login.php');
exit;
