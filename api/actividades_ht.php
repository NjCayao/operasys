<?php
/**
 * OperaSys - API de Actividades HT (Horas Trabajadas)
 * Archivo: api/actividades_ht.php
 */

require_once '../config/database.php';
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];
$userRol = $_SESSION['rol'];

// ============================================
// LISTAR ACTIVIDADES HT
// ============================================
if ($action === 'listar') {
    
    try {
        $stmt = $pdo->query("
            SELECT 
                id, nombre, descripcion, rendimiento_referencial,
                es_frecuente, orden_mostrar, estado,
                DATE_FORMAT(fecha_creacion, '%d/%m/%Y') as fecha_creacion
            FROM actividades_ht 
            ORDER BY orden_mostrar ASC, nombre ASC
        ");
        
        $actividades = $stmt->fetchAll();
        
        // Para select (solo activas y frecuentes)
        if (isset($_GET['para_select'])) {
            $activas = array_filter($actividades, fn($a) => $a['estado'] == 1);
            echo json_encode(['success' => true, 'actividades' => array_values($activas)]);
            exit;
        }
        
        // Para DataTable
        $data = [];
        foreach ($actividades as $act) {
            $estadoBadge = $act['estado'] == 1 
                ? '<span class="badge badge-success">Activo</span>' 
                : '<span class="badge badge-danger">Inactivo</span>';
            
            $frecuenteBadge = $act['es_frecuente'] == 1
                ? '<i class="fas fa-star text-warning"></i>'
                : '';
            
            $acciones = '';
            if ($userRol === 'admin') {
                $acciones = '<button onclick="editarActividad(' . $act['id'] . ')" class="btn btn-sm btn-warning">
                    <i class="fas fa-edit"></i></button> ';
                $acciones .= '<button onclick="eliminarActividad(' . $act['id'] . ', \'' . htmlspecialchars($act['nombre']) . '\')" class="btn btn-sm btn-danger">
                    <i class="fas fa-trash"></i></button>';
            }
            
            $data[] = [
                $act['id'],
                $frecuenteBadge . ' ' . htmlspecialchars($act['nombre']),
                $act['descripcion'] ?? '-',
                $act['rendimiento_referencial'] ?? '-',
                $act['orden_mostrar'],
                $estadoBadge,
                $act['fecha_creacion'],
                $acciones
            ];
        }
        
        echo json_encode(['success' => true, 'data' => $data]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener actividades']);
    }
}

// ============================================
// CREAR ACTIVIDAD HT
// ============================================
elseif ($action === 'crear') {
    
    if ($userRol !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Sin permisos']);
        exit;
    }
    
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $rendimiento = trim($_POST['rendimiento_referencial'] ?? '');
    $esFrecuente = $_POST['es_frecuente'] ?? 0;
    $orden = $_POST['orden_mostrar'] ?? 999;
    
    if (empty($nombre)) {
        echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM actividades_ht WHERE nombre = ?");
        $stmt->execute([$nombre]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ya existe esa actividad']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO actividades_ht (nombre, descripcion, rendimiento_referencial, es_frecuente, orden_mostrar) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$nombre, $descripcion, $rendimiento, $esFrecuente, $orden])) {
            
            $stmtAudit = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion, detalle) VALUES (?, ?, ?)");
            $stmtAudit->execute([$userId, 'crear_actividad_ht', "Actividad HT creada: $nombre"]);
            
            echo json_encode(['success' => true, 'message' => 'Actividad creada', 'id' => $pdo->lastInsertId()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
}

// ============================================
// ACTUALIZAR ACTIVIDAD HT
// ============================================
elseif ($action === 'actualizar') {
    
    if ($userRol !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Sin permisos']);
        exit;
    }
    
    $id = $_POST['id'] ?? 0;
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $rendimiento = trim($_POST['rendimiento_referencial'] ?? '');
    $esFrecuente = $_POST['es_frecuente'] ?? 0;
    $orden = $_POST['orden_mostrar'] ?? 999;
    $estado = $_POST['estado'] ?? 1;
    
    if (!$id || empty($nombre)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM actividades_ht WHERE nombre = ? AND id != ?");
        $stmt->execute([$nombre, $id]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ya existe otra actividad con ese nombre']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            UPDATE actividades_ht 
            SET nombre = ?, descripcion = ?, rendimiento_referencial = ?, 
                es_frecuente = ?, orden_mostrar = ?, estado = ?
            WHERE id = ?
        ");
        
        if ($stmt->execute([$nombre, $descripcion, $rendimiento, $esFrecuente, $orden, $estado, $id])) {
            
            $stmtAudit = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion, detalle) VALUES (?, ?, ?)");
            $stmtAudit->execute([$userId, 'editar_actividad_ht', "Actividad HT actualizada: $nombre"]);
            
            echo json_encode(['success' => true, 'message' => 'Actividad actualizada']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
}

// ============================================
// ELIMINAR ACTIVIDAD HT
// ============================================
elseif ($action === 'eliminar') {
    
    if ($userRol !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Sin permisos']);
        exit;
    }
    
    $id = $_POST['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID no válido']);
        exit;
    }
    
    try {
        // Verificar si tiene reportes asociados
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reportes_detalle WHERE actividad_ht_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        if ($result['total'] > 0) {
            // Solo desactivar
            $stmt = $pdo->prepare("UPDATE actividades_ht SET estado = 0 WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Actividad desactivada (tiene reportes asociados)']);
        } else {
            // Eliminar permanentemente
            $stmt = $pdo->prepare("DELETE FROM actividades_ht WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Actividad eliminada']);
        }
        
        $stmtAudit = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion, detalle) VALUES (?, ?, ?)");
        $stmtAudit->execute([$userId, 'eliminar_actividad_ht', "Actividad HT ID: $id"]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
    }
}

// ============================================
// OBTENER UNA ACTIVIDAD
// ============================================
elseif ($action === 'obtener') {
    
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID no válido']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM actividades_ht WHERE id = ?");
        $stmt->execute([$id]);
        $actividad = $stmt->fetch();
        
        if ($actividad) {
            echo json_encode(['success' => true, 'actividad' => $actividad]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No encontrada']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener']);
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}