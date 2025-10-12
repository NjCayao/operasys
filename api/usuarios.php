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
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    // Obtener y validar datos
    $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $cargo = trim($_POST['cargo'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    // Validaciones
    if (empty($nombreCompleto) || empty($dni) || empty($cargo) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres']);
        exit;
    }

    if ($password !== $passwordConfirm) {
        echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden']);
        exit;
    }

    if (!preg_match('/^[0-9]{8,20}$/', $dni)) {
        echo json_encode(['success' => false, 'message' => 'DNI inválido (debe contener 8-20 dígitos)']);
        exit;
    }

    try {
        // Verificar si el DNI ya existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE dni = ?");
        $stmt->execute([$dni]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Este DNI ya está registrado']);
            exit;
        }

        // Hashear contraseña
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Insertar usuario
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nombre_completo, dni, cargo, password, rol, estado) 
            VALUES (?, ?, ?, ?, 'operador', 1)
        ");
        
        if ($stmt->execute([$nombreCompleto, $dni, $cargo, $passwordHash])) {
            $userId = $pdo->lastInsertId();
            
            // Guardar ID temporal para captura de firma
            $_SESSION['temp_user_id'] = $userId;
            
            // Registrar en auditoría
            $stmtAudit = $pdo->prepare("
                INSERT INTO auditoria (usuario_id, accion, detalle) 
                VALUES (?, 'registro', 'Usuario registrado correctamente')
            ");
            $stmtAudit->execute([$userId]);

            echo json_encode([
                'success' => true,
                'message' => 'Registro exitoso. Ahora captura tu firma.',
                'redirect' => '../usuarios/firma.php'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al registrar usuario']);
        }

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
    }
}

// ============================================
// GUARDAR FIRMA DIGITAL
// ============================================
elseif ($action === 'guardar_firma') {
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    // Obtener ID del usuario (temporal o autenticado)
    $userId = $_SESSION['temp_user_id'] ?? $_SESSION['user_id'] ?? null;
    
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
        // Actualizar firma del usuario
        $stmt = $pdo->prepare("UPDATE usuarios SET firma = ? WHERE id = ?");
        
        if ($stmt->execute([$firmaBase64, $userId])) {
            
            // Registrar en auditoría
            $stmtAudit = $pdo->prepare("
                INSERT INTO auditoria (usuario_id, accion, detalle) 
                VALUES (?, 'firma_capturada', 'Firma digital guardada correctamente')
            ");
            $stmtAudit->execute([$userId]);

            // Si era un nuevo registro, eliminar sesión temporal
            $esNuevoRegistro = isset($_SESSION['temp_user_id']);
            if ($esNuevoRegistro) {
                unset($_SESSION['temp_user_id']);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Firma guardada correctamente',
                'redirect' => $esNuevoRegistro ? '../auth/login.php' : '../admin/dashboard.php',
                'es_nuevo_registro' => $esNuevoRegistro
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar firma']);
        }

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
    }
}

// ============================================
// OBTENER DATOS DE USUARIO
// ============================================
elseif ($action === 'obtener') {
    
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
        $stmt = $pdo->query("
            SELECT id, nombre_completo, dni, cargo, rol, estado, 
                   IF(firma IS NOT NULL, 1, 0) as firma
            FROM usuarios
            ORDER BY id DESC
        ");
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
    $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
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
    $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
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
