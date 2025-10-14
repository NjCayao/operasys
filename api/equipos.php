<?php
/**
 * OperaSys - API de Equipos
 * Archivo: api/equipos.php
 * Descripción: CRUD completo de equipos
 */

require_once '../config/database.php';
require_once '../config/config.php';

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];
$userRol = $_SESSION['rol'];

// ============================================
// LISTAR EQUIPOS (Con DataTables)
// ============================================
if ($action === 'listar') {
    
    try {
        $stmt = $pdo->query("
            SELECT 
                id, 
                categoria, 
                codigo, 
                descripcion, 
                estado, 
                DATE_FORMAT(fecha_creacion, '%d/%m/%Y') as fecha_creacion
            FROM equipos 
            ORDER BY id DESC
        ");
        
        $equipos = $stmt->fetchAll();
        
        // Formato para DataTables
        $data = [];
        foreach ($equipos as $equipo) {
            $estadoBadge = $equipo['estado'] == 1 
                ? '<span class="badge badge-success">Activo</span>' 
                : '<span class="badge badge-danger">Inactivo</span>';
            
            // Botones de acción según rol
            $acciones = '';
            if ($userRol === 'admin' || $userRol === 'supervisor') {
                $acciones .= '<a href="editar.php?id=' . $equipo['id'] . '" class="btn btn-sm btn-warning" title="Editar">
                    <i class="fas fa-edit"></i>
                </a> ';
                
                if ($userRol === 'admin') {
                    $acciones .= '<button onclick="eliminarEquipo(' . $equipo['id'] . ', \'' . htmlspecialchars($equipo['codigo']) . '\')" 
                        class="btn btn-sm btn-danger" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>';
                }
            }
            
            $data[] = [
                $equipo['id'],
                $equipo['categoria'],
                '<strong>' . $equipo['codigo'] . '</strong>',
                $equipo['descripcion'] ?? '-',
                $estadoBadge,
                $equipo['fecha_creacion'],
                $acciones
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener equipos']);
    }
}

// ============================================
// CREAR EQUIPO
// ============================================
elseif ($action === 'crear') {
    
    // Verificar permisos
    if ($userRol !== 'admin' && $userRol !== 'supervisor') {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para agregar equipos']);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }
    
    $categoria = trim($_POST['categoria'] ?? '');
    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    // Validaciones
    if (empty($categoria) || empty($codigo)) {
        echo json_encode(['success' => false, 'message' => 'Categoría y código son obligatorios']);
        exit;
    }
    
    if (!preg_match('/^[A-Z0-9]+$/', $codigo)) {
        echo json_encode(['success' => false, 'message' => 'El código solo debe contener letras mayúsculas y números']);
        exit;
    }
    
    try {
        // Verificar si el código ya existe
        $stmt = $pdo->prepare("SELECT id FROM equipos WHERE codigo = ?");
        $stmt->execute([$codigo]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'El código ' . $codigo . ' ya está registrado']);
            exit;
        }
        
        // Insertar equipo
        $stmt = $pdo->prepare("
            INSERT INTO equipos (categoria, codigo, descripcion, estado) 
            VALUES (?, ?, ?, 1)
        ");
        
        if ($stmt->execute([$categoria, $codigo, $descripcion])) {
            
            // Registrar en auditoría
            $equipoId = $pdo->lastInsertId();
            $stmtAudit = $pdo->prepare("
                INSERT INTO auditoria (usuario_id, accion, detalle) 
                VALUES (?, 'crear_equipo', ?)
            ");
            $stmtAudit->execute([$userId, "Equipo creado: $codigo"]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Equipo registrado correctamente',
                'equipo_id' => $equipoId
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al registrar equipo']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
    }
}

// ============================================
// ACTUALIZAR EQUIPO
// ============================================
elseif ($action === 'actualizar') {
    
    // Verificar permisos
    if ($userRol !== 'admin' && $userRol !== 'supervisor') {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para editar equipos']);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }
    
    $equipoId = $_POST['id'] ?? 0;
    $categoria = trim($_POST['categoria'] ?? '');
    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    $descripcion = trim($_POST['descripcion'] ?? '');
    $estado = $_POST['estado'] ?? 1;
    
    // Validaciones
    if (!$equipoId || empty($categoria) || empty($codigo)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }
    
    if (!preg_match('/^[A-Z0-9]+$/', $codigo)) {
        echo json_encode(['success' => false, 'message' => 'El código solo debe contener letras mayúsculas y números']);
        exit;
    }
    
    try {
        // Verificar si el código ya existe en otro equipo
        $stmt = $pdo->prepare("SELECT id FROM equipos WHERE codigo = ? AND id != ?");
        $stmt->execute([$codigo, $equipoId]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'El código ' . $codigo . ' ya está en uso por otro equipo']);
            exit;
        }
        
        // Actualizar equipo
        $stmt = $pdo->prepare("
            UPDATE equipos 
            SET categoria = ?, codigo = ?, descripcion = ?, estado = ? 
            WHERE id = ?
        ");
        
        if ($stmt->execute([$categoria, $codigo, $descripcion, $estado, $equipoId])) {
            
            // Registrar en auditoría
            $stmtAudit = $pdo->prepare("
                INSERT INTO auditoria (usuario_id, accion, detalle) 
                VALUES (?, 'editar_equipo', ?)
            ");
            $stmtAudit->execute([$userId, "Equipo actualizado: $codigo"]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Equipo actualizado correctamente'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar equipo']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
}

// ============================================
// ELIMINAR EQUIPO (Soft delete)
// ============================================
elseif ($action === 'eliminar') {
    
    // Solo admin puede eliminar
    if ($userRol !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Solo el administrador puede eliminar equipos']);
        exit;
    }
    
    $equipoId = $_POST['id'] ?? 0;
    
    if (!$equipoId) {
        echo json_encode(['success' => false, 'message' => 'ID de equipo no válido']);
        exit;
    }
    
    try {
        // Verificar si el equipo tiene reportes asociados
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reportes WHERE equipo_id = ?");
        $stmt->execute([$equipoId]);
        $result = $stmt->fetch();
        
        if ($result['total'] > 0) {
            // No eliminar, solo desactivar
            $stmt = $pdo->prepare("UPDATE equipos SET estado = 0 WHERE id = ?");
            $stmt->execute([$equipoId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'El equipo tiene reportes asociados. Se ha desactivado en lugar de eliminarse.'
            ]);
        } else {
            // Eliminar permanentemente
            $stmt = $pdo->prepare("DELETE FROM equipos WHERE id = ?");
            $stmt->execute([$equipoId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Equipo eliminado correctamente'
            ]);
        }
        
        // Registrar en auditoría
        $stmtAudit = $pdo->prepare("
            INSERT INTO auditoria (usuario_id, accion, detalle) 
            VALUES (?, 'eliminar_equipo', ?)
        ");
        $stmtAudit->execute([$userId, "Equipo ID: $equipoId"]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar equipo']);
    }
}

// ============================================
// BUSCAR POR CATEGORÍA (Para formulario de reportes)
// ============================================
elseif ($action === 'buscar_por_categoria') {
    
    $categoria = $_GET['categoria'] ?? '';
    
    if (empty($categoria)) {
        echo json_encode(['success' => false, 'message' => 'Categoría no especificada']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, codigo, descripcion 
            FROM equipos 
            WHERE categoria = ? AND estado = 1
            ORDER BY codigo ASC
        ");
        $stmt->execute([$categoria]);
        $equipos = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'equipos' => $equipos
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al buscar equipos']);
    }
}

// obtener_equipos_operador
elseif ($action === 'obtener_equipos_operador') {
    // Obtener cargo del usuario
    $stmt = $pdo->prepare("SELECT cargo FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cargo = $stmt->fetch()['cargo'];
    
    // Extraer categoría del cargo "Operador de [Categoría]"
    $categoria = str_replace('Operador de ', '', $cargo);
    
    // Si no es operador, mostrar todos
    if (!str_contains($cargo, 'Operador de')) {
        $stmt = $pdo->query("SELECT * FROM equipos WHERE estado = 1 ORDER BY codigo");
    } else {
        // Si es operador, filtrar por su categoría
        $stmt = $pdo->prepare("SELECT * FROM equipos WHERE categoria = ? AND estado = 1 ORDER BY codigo");
        $stmt->execute([$categoria]);
    }
    
    echo json_encode(['success' => true, 'equipos' => $stmt->fetchAll()]);
}

// ============================================
// ACCIÓN NO VÁLIDA
// ============================================
else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
