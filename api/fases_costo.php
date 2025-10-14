<?php
/**
 * OperaSys - API de Fases de Costo
 * Archivo: api/fases_costo.php
 * Descripción: CRUD completo de fases de costo
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
// LISTAR FASES DE COSTO
// ============================================
if ($action === 'listar') {
    
    try {
        $stmt = $pdo->query("
            SELECT 
                id, 
                codigo,
                descripcion, 
                proyecto,
                estado,
                DATE_FORMAT(fecha_creacion, '%d/%m/%Y') as fecha_creacion
            FROM fases_costo 
            ORDER BY codigo ASC
        ");
        
        $fases = $stmt->fetchAll();
        
        // Si es para select, devolver simple
        if (isset($_GET['para_select'])) {
            echo json_encode([
                'success' => true,
                'fases' => $fases
            ]);
            exit;
        }
        
        // Formato para DataTables
        $data = [];
        foreach ($fases as $fase) {
            $estadoBadge = $fase['estado'] == 1 
                ? '<span class="badge badge-success">Activo</span>' 
                : '<span class="badge badge-danger">Inactivo</span>';
            
            // Botones de acción (solo admin)
            $acciones = '';
            if ($userRol === 'admin') {
                $acciones .= '<button onclick="editarFase(' . $fase['id'] . ')" 
                    class="btn btn-sm btn-warning" title="Editar">
                    <i class="fas fa-edit"></i>
                </button> ';
                
                $acciones .= '<button onclick="eliminarFase(' . $fase['id'] . ', \'' . htmlspecialchars($fase['codigo']) . '\')" 
                    class="btn btn-sm btn-danger" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>';
            }
            
            $data[] = [
                $fase['id'],
                '<strong>' . htmlspecialchars($fase['codigo']) . '</strong>',
                htmlspecialchars($fase['descripcion']),
                $fase['proyecto'] ?? '-',
                $estadoBadge,
                $fase['fecha_creacion'],
                $acciones
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener fases de costo']);
    }
}

// ============================================
// CREAR FASE DE COSTO
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
    
    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    $descripcion = trim($_POST['descripcion'] ?? '');
    $proyecto = trim($_POST['proyecto'] ?? '');
    
    if (empty($codigo) || empty($descripcion)) {
        echo json_encode(['success' => false, 'message' => 'Código y descripción son obligatorios']);
        exit;
    }
    
    try {
        // Verificar si ya existe el código
        $stmt = $pdo->prepare("SELECT id FROM fases_costo WHERE codigo = ?");
        $stmt->execute([$codigo]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ya existe una fase con ese código']);
            exit;
        }
        
        // Insertar
        $stmt = $pdo->prepare("
            INSERT INTO fases_costo (codigo, descripcion, proyecto) 
            VALUES (?, ?, ?)
        ");
        
        if ($stmt->execute([$codigo, $descripcion, $proyecto])) {
            
            // Registrar en auditoría
            $faseId = $pdo->lastInsertId();
            $stmtAudit = $pdo->prepare("
                INSERT INTO auditoria (usuario_id, accion, detalle) 
                VALUES (?, 'crear_fase_costo', ?)
            ");
            $stmtAudit->execute([$userId, "Fase de costo creada: $codigo"]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Fase de costo creada correctamente',
                'fase_id' => $faseId
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear fase']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
}

// ============================================
// ACTUALIZAR FASE DE COSTO
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
    
    $faseId = $_POST['id'] ?? 0;
    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    $descripcion = trim($_POST['descripcion'] ?? '');
    $proyecto = trim($_POST['proyecto'] ?? '');
    $estado = $_POST['estado'] ?? 1;
    
    if (!$faseId || empty($codigo) || empty($descripcion)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }
    
    try {
        // Verificar si existe otro con el mismo código
        $stmt = $pdo->prepare("SELECT id FROM fases_costo WHERE codigo = ? AND id != ?");
        $stmt->execute([$codigo, $faseId]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ya existe otra fase con ese código']);
            exit;
        }
        
        // Actualizar
        $stmt = $pdo->prepare("
            UPDATE fases_costo 
            SET codigo = ?, descripcion = ?, proyecto = ?, estado = ? 
            WHERE id = ?
        ");
        
        if ($stmt->execute([$codigo, $descripcion, $proyecto, $estado, $faseId])) {
            
            // Registrar en auditoría
            $stmtAudit = $pdo->prepare("
                INSERT INTO auditoria (usuario_id, accion, detalle) 
                VALUES (?, 'editar_fase_costo', ?)
            ");
            $stmtAudit->execute([$userId, "Fase de costo actualizada: $codigo"]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Fase de costo actualizada correctamente'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
}

// ============================================
// ELIMINAR FASE DE COSTO
// ============================================
elseif ($action === 'eliminar') {
    
    // Solo admin
    if ($userRol !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'No tiene permisos']);
        exit;
    }
    
    $faseId = $_POST['id'] ?? 0;
    
    if (!$faseId) {
        echo json_encode(['success' => false, 'message' => 'ID no válido']);
        exit;
    }
    
    try {
        // Verificar si tiene reportes asociados
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total FROM reportes_detalle WHERE fase_costo_id = ?
        ");
        $stmt->execute([$faseId]);
        $result = $stmt->fetch();
        
        if ($result['total'] > 0) {
            // No eliminar, solo desactivar
            $stmt = $pdo->prepare("UPDATE fases_costo SET estado = 0 WHERE id = ?");
            $stmt->execute([$faseId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'La fase tiene reportes asociados. Se ha desactivado.'
            ]);
        } else {
            // Eliminar permanentemente
            $stmt = $pdo->prepare("DELETE FROM fases_costo WHERE id = ?");
            $stmt->execute([$faseId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Fase de costo eliminada correctamente'
            ]);
        }
        
        // Registrar en auditoría
        $stmtAudit = $pdo->prepare("
            INSERT INTO auditoria (usuario_id, accion, detalle) 
            VALUES (?, 'eliminar_fase_costo', ?)
        ");
        $stmtAudit->execute([$userId, "Fase de costo ID: $faseId"]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
    }
}

// ============================================
// OBTENER UNA FASE PARA EDITAR
// ============================================
elseif ($action === 'obtener') {
    
    $faseId = $_GET['id'] ?? 0;
    
    if (!$faseId) {
        echo json_encode(['success' => false, 'message' => 'ID no válido']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM fases_costo WHERE id = ?");
        $stmt->execute([$faseId]);
        $fase = $stmt->fetch();
        
        if ($fase) {
            echo json_encode([
                'success' => true,
                'fase' => $fase
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Fase no encontrada']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener fase']);
    }
}

// ============================================
// ACCIÓN NO VÁLIDA
// ============================================
else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}