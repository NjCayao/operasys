<?php
/**
 * OperaSys - API de Autenticación
 * Archivo: api/auth.php
 * Descripción: Maneja login y validación de credenciales
 */

require_once '../config/database.php';
require_once '../config/config.php';

// Establecer cabecera JSON
header('Content-Type: application/json');

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

// Obtener datos del formulario
$dni = trim($_POST['dni'] ?? '');
$password = $_POST['password'] ?? '';

// Validar campos vacíos
if (empty($dni) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Por favor complete todos los campos'
    ]);
    exit;
}

try {
    // Buscar usuario por DNI y que esté activo
    $stmt = $pdo->prepare("
        SELECT id, nombre_completo, dni, cargo, password, rol, firma 
        FROM usuarios 
        WHERE dni = ? AND estado = 1
    ");
    $stmt->execute([$dni]);
    $user = $stmt->fetch();

    // Verificar si el usuario existe
    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'DNI o contraseña incorrectos'
        ]);
        exit;
    }

    // Verificar contraseña
    if (!password_verify($password, $user['password'])) {
        echo json_encode([
            'success' => false,
            'message' => 'DNI o contraseña incorrectos'
        ]);
        exit;
    }

    // Crear variables de sesión
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['nombre'] = $user['nombre_completo'];
    $_SESSION['dni'] = $user['dni'];
    $_SESSION['cargo'] = $user['cargo'];
    $_SESSION['rol'] = $user['rol'];
    $_SESSION['tiene_firma'] = !empty($user['firma']);

    // Registrar en auditoría
    $stmtAudit = $pdo->prepare("
        INSERT INTO auditoria (usuario_id, accion, detalle) 
        VALUES (?, 'login', 'Inicio de sesión exitoso')
    ");
    $stmtAudit->execute([$user['id']]);

    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Inicio de sesión exitoso',
        'redirect' => '../admin/dashboard.php',
        'user' => [
            'nombre' => $user['nombre_completo'],
            'rol' => $user['rol']
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
