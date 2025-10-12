<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

// Auditoría
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion) VALUES (?, 'logout')");
        $stmt->execute([$_SESSION['user_id']]);
    } catch (Exception $e) {}
}

// Limpiar sesión
$_SESSION = [];
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}
session_destroy();

// Redirigir
header('Location: login.php');
exit;
