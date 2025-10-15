<?php
/**
 * OperaSys - Control de Acceso
 * Archivo: includes/auth_check.php
 * Descripción: Verifica permisos de acceso según rol y página
 */

// Verificar que la sesión esté iniciada
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . '/modules/auth/login.php');
    exit;
}

/**
 * Verificar si el usuario tiene permiso para acceder a una página
 * @param array $roles_permitidos Array con roles permitidos ['admin', 'supervisor', 'operador']
 */
function verificarPermiso($roles_permitidos = []) {
    $rol_usuario = $_SESSION['rol'] ?? 'operador';
    
    if (!in_array($rol_usuario, $roles_permitidos)) {
        // Redirigir según rol a su página permitida
        switch ($rol_usuario) {
            case 'admin':
            case 'supervisor':
                header('Location: ' . SITE_URL . '/modules/admin/dashboard.php');
                break;
            case 'operador':
                header('Location: ' . SITE_URL . '/modules/reportes/listar.php');
                break;
            default:
                header('Location: ' . SITE_URL . '/modules/auth/login.php');
                break;
        }
        exit;
    }
}

/**
 * Verificar si el usuario puede ver un reporte específico
 * @param int $reporte_usuario_id ID del usuario dueño del reporte
 * @return bool
 */
function puedeVerReporte($reporte_usuario_id) {
    $rol = $_SESSION['rol'] ?? 'operador';
    $user_id = $_SESSION['user_id'] ?? 0;
    
    // Admin y Supervisor pueden ver todos los reportes
    if ($rol === 'admin' || $rol === 'supervisor') {
        return true;
    }
    
    // Operador solo puede ver sus propios reportes
    return ($user_id == $reporte_usuario_id);
}

/**
 * Verificar si el usuario puede editar un reporte
 * @param int $reporte_usuario_id ID del usuario dueño del reporte
 * @param string $estado Estado del reporte (borrador/finalizado)
 * @return bool
 */
function puedeEditarReporte($reporte_usuario_id, $estado) {
    $rol = $_SESSION['rol'] ?? 'operador';
    $user_id = $_SESSION['user_id'] ?? 0;
    
    // Admin puede editar cualquier reporte
    if ($rol === 'admin') {
        return true;
    }
    
    // Supervisor NO puede editar
    if ($rol === 'supervisor') {
        return false;
    }
    
    // Operador solo puede editar sus borradores
    if ($rol === 'operador') {
        return ($user_id == $reporte_usuario_id && $estado === 'borrador');
    }
    
    return false;
}

/**
 * Verificar si el usuario puede eliminar un reporte
 * @return bool
 */
function puedeEliminarReporte() {
    return ($_SESSION['rol'] ?? 'operador') === 'admin';
}

/**
 * Verificar si el usuario puede gestionar equipos (agregar/editar/eliminar)
 * @return bool
 */
function puedeGestionarEquipos() {
    $rol = $_SESSION['rol'] ?? 'operador';
    return ($rol === 'admin' || $rol === 'supervisor');
}

/**
 * Verificar si el usuario puede gestionar usuarios
 * @return bool
 */
function puedeGestionarUsuarios() {
    return ($_SESSION['rol'] ?? 'operador') === 'admin';
}

/**
 * Verificar si el usuario puede ver auditoría
 * @return bool
 */
function puedeVerAuditoria() {
    return ($_SESSION['rol'] ?? 'operador') === 'admin';
}

/**
 * Obtener página de inicio según rol
 * @param string $rol
 * @return string URL de la página de inicio
 */
function getPaginaInicio($rol) {
    switch ($rol) {
        case 'admin':
        case 'supervisor':
            return SITE_URL . '/modules/admin/dashboard.php';
        case 'operador':
            return SITE_URL . '/modules/reportes/listar.php';
        default:
            return SITE_URL . '/modules/auth/login.php';
    }
}