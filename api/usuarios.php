<?php
/**
 * OperaSys - API de Usuarios
 * Archivo: api/usuarios.php
 * Descripción: CRUD de usuarios y gestión de firmas
 */

require_once '../config/database.php';
require_once '../config/config.php';

header('Content-Type: application/json');

// Obtener acción
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ============================================
// REGISTRO DE NUEVO USUARIO
// ============================================
if ($action === 'register') {
    // Auto-registro deshabilitado
    echo json_encode([
        'success' => false, 
        'message' => 'El registro público está deshabilitado. Contacte al administrador.'
    ]);
    exit;
}

// ============================================
// GUARDAR FIRMA DIGITAL
// ============================================
elseif ($action === 'guardar_firma') {
    verificarSesion();
    
    // Obtener ID del usuario (puede ser otro si es admin)
    $userIdEditar = $_POST['user_id'] ?? $_SESSION['user_id'];
    $esAdmin = $_SESSION['rol'] === 'admin';
    
    // Si no es admin, solo puede editar su propia firma
    if (!$esAdmin && $userIdEditar != $_SESSION['user_id']) {
        echo json_encode([
            'success' => false,
            'message' => 'No tienes permisos para editar esta firma'
        ]);
        exit;
    }
    
    $userId = $userIdEditar;

    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
        exit;
    }

    // Obtener firma en base64
    $firmaBase64 = $_POST['firma'] ?? '';

    if (empty($firmaBase64)) {
        echo json_encode(['success' => false, 'message' => 'No se recibió la firma']);
        exit;
    }

    // Validar formato base64
    if (!preg_match('/^data:image\/png;base64,/', $firmaBase64)) {
        echo json_encode(['success' => false, 'message' => 'Formato de firma inválido']);
        exit;
    }

    try {
        // Verificar si tenía firma antes
        $stmtCheck = $pdo->prepare("SELECT firma FROM usuarios WHERE id = ?");
        $stmtCheck->execute([$userId]);
        $teniaFirma = !empty($stmtCheck->fetchColumn());

        // Actualizar firma del usuario
        $stmt = $pdo->prepare("UPDATE usuarios SET firma = ? WHERE id = ?");

        if ($stmt->execute([$firmaBase64, $userId])) {

            // Registrar en auditoría
            $accionAudit = $teniaFirma ? 'firma_actualizada' : 'firma_capturada';
            $detalleAudit = $teniaFirma ? 'Firma digital actualizada' : 'Firma digital guardada correctamente';
            
            $stmtAudit = $pdo->prepare("
                INSERT INTO auditoria (usuario_id, accion, detalle) 
                VALUES (?, ?, ?)
            ");
            $stmtAudit->execute([$userId, $accionAudit, $detalleAudit]);

            echo json_encode([
                'success' => true,
                'message' => 'Firma guardada correctamente',
                'tenia_firma' => $teniaFirma
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar firma']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// CAMBIAR CONTRASEÑA (PERFIL)
// ============================================
elseif ($action === 'cambiar_password') {
    verificarSesion();
    
    $userId = $_SESSION['user_id'];
    $passwordActual = $_POST['password_actual'] ?? '';
    $passwordNueva = $_POST['password_nueva'] ?? '';
    
    // Validaciones
    if (empty($passwordActual) || empty($passwordNueva)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
        exit;
    }
    
    if (strlen($passwordNueva) < 6) {
        echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 6 caracteres']);
        exit;
    }
    
    try {
        // Verificar contraseña actual
        $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
        $stmt->execute([$userId]);
        $usuario = $stmt->fetch();
        
        if (!$usuario || !password_verify($passwordActual, $usuario['password'])) {
            echo json_encode(['success' => false, 'message' => 'La contraseña actual es incorrecta']);
            exit;
        }
        
        // Actualizar contraseña
        $passwordHash = password_hash($passwordNueva, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $stmt->execute([$passwordHash, $userId]);
        
        // Auditoría
        $stmtAudit = $pdo->prepare("
            INSERT INTO auditoria (usuario_id, accion, detalle) 
            VALUES (?, 'cambiar_password', 'Usuario cambió su contraseña')
        ");
        $stmtAudit->execute([$userId]);
        
        echo json_encode(['success' => true, 'message' => 'Contraseña actualizada correctamente']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al cambiar contraseña']);
    }
    exit;
}

// ============================================
// OBTENER DATOS DE USUARIO
// ============================================
elseif ($action === 'obtener_perfil') {
    verificarSesion();

    $userId = $_SESSION['user_id'];

    try {
        $stmt = $pdo->prepare("
            SELECT id, nombre_completo, dni, cargo, rol, firma, fecha_creacion 
            FROM usuarios 
            WHERE id = ? AND estado = 1
        ");
        $stmt->execute([$userId]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            echo json_encode([
                'success' => true,
                'usuario' => $usuario
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
    exit;
}

// ============================================
// OBTENER FIRMA DE USUARIO (SOLO ADMIN)
// ============================================
elseif ($action === 'obtener_firma') {
    // Verificar que sea admin
    if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos']);
        exit;
    }
    
    $id = $_GET['id'] ?? 0;
    
    try {
        $stmt = $pdo->prepare("SELECT id, nombre_completo, firma FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $usuario = $stmt->fetch();
        
        if ($usuario) {
            echo json_encode([
                'success' => true, 
                'data' => [
                    'id' => $usuario['id'],
                    'nombre' => $usuario['nombre_completo'],
                    'firma' => $usuario['firma']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener firma']);
    }
    exit;
}

// ============================================
// GESTIÓN DE USUARIOS (SOLO ADMIN)
// ============================================

// Verificar que sea admin para estas acciones
$adminActions = ['listar', 'crear', 'actualizar', 'toggle_estado', 'obtener'];
if (in_array($action, $adminActions)) {
    if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos']);
        exit;
    }
}

// LISTAR USUARIOS
if ($action === 'listar') {
    try {
        // Verificar si hay filtro por rol
        $filtroRol = $_GET['rol'] ?? '';
        
        if (!empty($filtroRol) && in_array($filtroRol, ['admin', 'supervisor', 'operador'])) {
            // Listar usuarios filtrados por rol
            $stmt = $pdo->prepare("
                SELECT id, nombre_completo, dni, cargo, rol, estado, 
                       IF(firma IS NOT NULL, 1, 0) as firma
                FROM usuarios
                WHERE rol = ?
                ORDER BY id DESC
            ");
            $stmt->execute([$filtroRol]);
        } else {
            // Listar todos los usuarios
            $stmt = $pdo->query("
                SELECT id, nombre_completo, dni, cargo, rol, estado, 
                       IF(firma IS NOT NULL, 1, 0) as firma
                FROM usuarios
                ORDER BY id DESC
            ");
        }

        $usuarios = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $usuarios]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener usuarios']);
    }
    exit;
}

// OBTENER UN USUARIO
if ($action === 'obtener') {
    $id = $_GET['id'] ?? 0;

    try {
        $stmt = $pdo->prepare("SELECT id, nombre_completo, dni, cargo, rol, estado FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            echo json_encode(['success' => true, 'data' => $usuario]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener usuario']);
    }
    exit;
}

// CREAR USUARIO
if ($action === 'crear') {
    $nombreCompleto = ucwords(strtolower(trim($_POST['nombre_completo'] ?? '')));
    $dni = trim($_POST['dni'] ?? '');
    $cargo = trim($_POST['cargo'] ?? '');
    $rol = $_POST['rol'] ?? 'operador';
    $password = $_POST['password'] ?? '';

    // Validaciones
    if (empty($nombreCompleto) || empty($dni) || empty($cargo) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres']);
        exit;
    }

    // Verificar DNI duplicado
    try {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE dni = ?");
        $stmt->execute([$dni]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Este DNI ya está registrado']);
            exit;
        }

        // Crear usuario
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nombre_completo, dni, cargo, password, rol) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$nombreCompleto, $dni, $cargo, $passwordHash, $rol]);

        // Auditoría
        $stmtAudit = $pdo->prepare("
            INSERT INTO auditoria (usuario_id, accion, detalle) 
            VALUES (?, 'crear_usuario', ?)
        ");
        $stmtAudit->execute([$_SESSION['user_id'], "Creó usuario: $nombreCompleto (DNI: $dni)"]);

        echo json_encode(['success' => true, 'message' => 'Usuario creado exitosamente']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al crear usuario']);
    }
    exit;
}

// ACTUALIZAR USUARIO
if ($action === 'actualizar') {
    $id = $_POST['id'] ?? 0;
    $nombreCompleto = ucwords(strtolower(trim($_POST['nombre_completo'] ?? '')));
    $dni = trim($_POST['dni'] ?? '');
    $cargo = trim($_POST['cargo'] ?? '');
    $rol = $_POST['rol'] ?? 'operador';
    $password = $_POST['password'] ?? '';

    // Validaciones
    if (empty($nombreCompleto) || empty($dni) || empty($cargo)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
        exit;
    }

    try {
        // Verificar DNI duplicado (excepto el mismo usuario)
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE dni = ? AND id != ?");
        $stmt->execute([$dni, $id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Este DNI ya está registrado']);
            exit;
        }

        // Actualizar usuario
        if (!empty($password)) {
            // Con nueva contraseña
            if (strlen($password) < 6) {
                echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres']);
                exit;
            }
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("
                UPDATE usuarios 
                SET nombre_completo = ?, dni = ?, cargo = ?, rol = ?, password = ? 
                WHERE id = ?
            ");
            $stmt->execute([$nombreCompleto, $dni, $cargo, $rol, $passwordHash, $id]);
        } else {
            // Sin cambiar contraseña
            $stmt = $pdo->prepare("
                UPDATE usuarios 
                SET nombre_completo = ?, dni = ?, cargo = ?, rol = ? 
                WHERE id = ?
            ");
            $stmt->execute([$nombreCompleto, $dni, $cargo, $rol, $id]);
        }

        // Auditoría
        $stmtAudit = $pdo->prepare("
            INSERT INTO auditoria (usuario_id, accion, detalle) 
            VALUES (?, 'actualizar_usuario', ?)
        ");
        $stmtAudit->execute([$_SESSION['user_id'], "Actualizó usuario ID: $id"]);

        echo json_encode(['success' => true, 'message' => 'Usuario actualizado exitosamente']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar usuario']);
    }
    exit;
}

// ACTIVAR/DESACTIVAR USUARIO
if ($action === 'toggle_estado') {
    $id = $_POST['id'] ?? 0;
    $estado = $_POST['estado'] ?? 1;

    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
        $stmt->execute([$estado, $id]);

        $textoEstado = $estado == 1 ? 'activado' : 'desactivado';

        // Auditoría
        $stmtAudit = $pdo->prepare("
            INSERT INTO auditoria (usuario_id, accion, detalle) 
            VALUES (?, 'cambiar_estado_usuario', ?)
        ");
        $stmtAudit->execute([$_SESSION['user_id'], "Usuario ID $id $textoEstado"]);

        echo json_encode(['success' => true, 'message' => "Usuario $textoEstado exitosamente"]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al cambiar estado']);
    }
    exit;
}

// ============================================
// ACCIÓN NO VÁLIDA
// ============================================
else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}