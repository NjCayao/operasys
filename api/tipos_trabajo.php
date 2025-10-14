<?php
/**
 * OperaSys - API de Tipos de Trabajo
 * Archivo: api/tipos_trabajo.php
 * Descripción: CRUD completo de tipos de trabajo
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
// LISTAR TIPOS DE TRABAJO
// ============================================
if ($action === 'listar') {
    
    try {
        $stmt = $pdo->query("
            SELECT 
                id, 
                nombre, 
                descripcion, 
                estado,
                DATE_FORMAT(fecha_creacion, '%d/%m/%Y') as fecha_creacion
            FROM tipos_trabajo 
            ORDER BY nombre ASC
        ");
        
        $tipos = $stmt->fetchAll();
        
        // Si es para select, devolver simple
        if (isset($_GET['para_select'])) {
            echo json_encode([
                'success' => true,
                'tipos' => $tipos
            ]);
            exit;
        }
        
        // Formato para DataTables
        $data = [];
        foreach ($tipos as $tipo) {
            $estadoBadge = $tipo['estado'] == 1 
                ? '<span class="badge badge-success">Activo</span>' 
                : '<span class="badge badge-danger">Inactivo</span>';
            
            // Botones de acción (solo admin)
            $acciones = '';
            if ($userRol === 'admin') {
                $acciones .= '<button onclick="editarTipo(' . $tipo['id'] . ')" 
                    class="btn btn-sm btn-warning" title="Editar">
                    <i class="fas fa-edit"></i>
                </button> ';
                
                $acciones .= '<button onclick="eliminarTipo(' . $tipo['id'] . ', \'' . htmlspecialchars($tipo['nombre']) . '\')" 
                    class="btn btn-sm btn-danger" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>';
            }
            
            $data[] = [
                $tipo['id'],
                '<strong>' . htmlspecialchars($tipo['nombre']) . '</strong>',
                $tipo['descripcion'] ?? '-',
                $estadoBadge,
                $tipo['fecha_creacion'],
                $acciones
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener tipos de trabajo']);
    }
}

// ============================================
// CREAR TIPO DE TRABAJO
// ============================================
elseif ($action === 'crear') {
    
    // Solo admin
    if ($userRol !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'No tiene permisos']);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }
    
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    if (empty($nombre)) {
        echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio']);
        exit;
    }
    
    try {
        // Verificar si ya existe
        $stmt = $pdo->prepare("SELECT id FROM tipos_trabajo WHERE nombre = ?");
        $stmt->execute([$nombre]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ya existe un tipo de trabajo con ese nombre']);
            exit;
        }
        
        // Insertar
        $stmt = $pdo->prepare("
            INSERT INTO tipos_trabajo (nombre, descripcion) 
            VALUES (?, ?)
        ");
        
        if ($stmt->execute([$nombre, $descripcion])) {
            
            // Registrar en auditoría
            $tipoId = $pdo->lastInsertId();
            $stmtAudit = $pdo->prepare("
                INSERT INTO auditoria (usuario_id, accion, detalle) 
                VALUES (?, 'crear_tipo_trabajo', ?)
            ");
            $stmtAudit->execute([$userId, "Tipo de trabajo creado: $nombre"]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Tipo de trabajo creado correctamente',
                'tipo_id' => $tipoId
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear tipo de trabajo']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
}

// ============================================
// ACTUALIZAR TIPO DE TRABAJO
// ============================================
elseif ($action === 'actualizar') {
    
    // Solo admin
    if ($userRol !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'No tiene permisos']);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }
    
    $tipoId = $_POST['id'] ?? 0;
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $estado = $_POST['estado'] ?? 1;
    
    if (!$tipoId || empty($nombre)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }
    
    try {
        // Verificar si existe otro con el mismo nombre
        $stmt = $pdo->prepare("SELECT id FROM tipos_trabajo WHERE nombre = ? AND id != ?");
        $stmt->execute([$nombre, $tipoId]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ya existe otro tipo de trabajo con ese nombre']);
            exit;
        }
        
        // Actualizar
        $stmt = $pdo->prepare("
            UPDATE tipos_trabajo 
            SET nombre = ?, descripcion = ?, estado = ? 
            WHERE id = ?
        ");
        
        if ($stmt->execute([$nombre, $descripcion, $estado, $tipoId])) {
            
            // Registrar en auditoría
            $stmtAudit = $pdo->prepare("
                INSERT INTO auditoria (usuario_id, accion, detalle) 
                VALUES (?, 'editar_tipo_trabajo', ?)
            ");
            $stmtAudit->execute([$userId, "Tipo de trabajo actualizado: $nombre"]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Tipo de trabajo actualizado correctamente'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
}

// ============================================
// ELIMINAR TIPO DE TRABAJO
// ============================================
elseif ($action === 'eliminar') {
    
    // Solo admin
    if ($userRol !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'No tiene permisos']);
        exit;
    }
    
    $tipoId = $_POST['id'] ?? 0;
    
    if (!$tipoId) {
        echo json_encode(['success' => false, 'message' => 'ID no válido']);
        exit;
    }
    
    try {
        // Verificar si tiene reportes asociados
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total FROM reportes_detalle WHERE tipo_trabajo_id = ?
        ");
        $stmt->execute([$tipoId]);
        $result = $stmt->fetch();
        
        if ($result['total'] > 0) {
            // No eliminar, solo desactivar
            $stmt = $pdo->prepare("UPDATE tipos_trabajo SET estado = 0 WHERE id = ?");
            $stmt->execute([$tipoId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'El tipo de trabajo tiene reportes asociados. Se ha desactivado.'
            ]);
        } else {
            // Eliminar permanentemente
            $stmt = $pdo->prepare("DELETE FROM tipos_trabajo WHERE id = ?");
            $stmt->execute([$tipoId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Tipo de trabajo eliminado correctamente'
            ]);
        }
        
        // Registrar en auditoría
        $stmtAudit = $pdo->prepare("
            INSERT INTO auditoria (usuario_id, accion, detalle) 
            VALUES (?, 'eliminar_tipo_trabajo', ?)
        ");
        $stmtAudit->execute([$userId, "Tipo de trabajo ID: $tipoId"]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
    }
}

// ============================================
// OBTENER UN TIPO PARA EDITAR
// ============================================
elseif ($action === 'obtener') {
    
    $tipoId = $_GET['id'] ?? 0;
    
    if (!$tipoId) {
        echo json_encode(['success' => false, 'message' => 'ID no válido']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM tipos_trabajo WHERE id = ?");
        $stmt->execute([$tipoId]);
        $tipo = $stmt->fetch();
        
        if ($tipo) {
            echo json_encode([
                'success' => true,
                'tipo' => $tipo
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Tipo no encontrado']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener tipo']);
    }
}

// ============================================
// ACCIÓN NO VÁLIDA
// ============================================
else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}