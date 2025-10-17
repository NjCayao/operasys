<?php
/**
 * OperaSys - API de Motivos HP (Horas Paradas)
 * Archivo: api/motivos_hp.php
 * Versión: 3.0 - FINAL con autoasignación de orden por ID
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
// LISTAR MOTIVOS HP
// ============================================
if ($action === 'listar') {
    
    try {
        $stmt = $pdo->query("
            SELECT 
                id, codigo, nombre, descripcion, categoria_parada,
                es_justificada, requiere_observacion, es_frecuente, 
                orden_mostrar, estado,
                DATE_FORMAT(fecha_creacion, '%d/%m/%Y') as fecha_creacion
            FROM motivos_hp 
            ORDER BY orden_mostrar ASC, nombre ASC
        ");
        
        $motivos = $stmt->fetchAll();
        
        // Para select (solo activos)
        if (isset($_GET['para_select'])) {
            $activos = array_filter($motivos, fn($m) => $m['estado'] == 1);
            echo json_encode(['success' => true, 'motivos' => array_values($activos)]);
            exit;
        }
        
        // Para DataTable
        $data = [];
        foreach ($motivos as $mot) {
            $estadoBadge = $mot['estado'] == 1 
                ? '<span class="badge badge-success">Activo</span>' 
                : '<span class="badge badge-danger">Inactivo</span>';
            
            $frecuenteBadge = $mot['es_frecuente'] == 1
                ? '<i class="fas fa-star text-warning"></i>'
                : '';
            
            $justificadaBadge = $mot['es_justificada'] == 1
                ? '<span class="badge badge-info">Justificada</span>'
                : '<span class="badge badge-warning">No justificada</span>';
            
            $categoriaBadge = [
                'operacional' => '<span class="badge badge-primary">Operacional</span>',
                'mantenimiento' => '<span class="badge badge-danger">Mantenimiento</span>',
                'climatica' => '<span class="badge badge-info">Climática</span>',
                'administrativa' => '<span class="badge badge-secondary">Administrativa</span>',
                'personal' => '<span class="badge badge-success">Personal</span>'
            ][$mot['categoria_parada']] ?? '';
            
            $acciones = '';
            if ($userRol === 'admin') {
                $acciones = '<button onclick="editarMotivo(' . $mot['id'] . ')" class="btn btn-sm btn-warning">
                    <i class="fas fa-edit"></i></button> ';
                $acciones .= '<button onclick="eliminarMotivo(' . $mot['id'] . ', \'' . htmlspecialchars($mot['nombre']) . '\')" class="btn btn-sm btn-danger">
                    <i class="fas fa-trash"></i></button>';
            }
            
            $data[] = [
                $mot['id'],
                $mot['codigo'] ?? '-',
                $frecuenteBadge . ' ' . htmlspecialchars($mot['nombre']),
                $categoriaBadge,
                $justificadaBadge,
                $mot['orden_mostrar'],
                $estadoBadge,
                $mot['fecha_creacion'],
                $acciones
            ];
        }
        
        echo json_encode(['success' => true, 'data' => $data]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener motivos']);
    }
}

// ============================================
// CREAR MOTIVO HP
// ============================================
elseif ($action === 'crear') {
    
    if ($userRol !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Sin permisos']);
        exit;
    }
    
    $codigo = trim($_POST['codigo'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $categoria = $_POST['categoria_parada'] ?? 'operacional';
    $esJustificada = $_POST['es_justificada'] ?? 1;
    $requiereObs = $_POST['requiere_observacion'] ?? 0;
    $esFrecuente = $_POST['es_frecuente'] ?? 0;
    $orden = $_POST['orden_mostrar'] ?? 999;
    
    if (empty($nombre)) {
        echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio']);
        exit;
    }
    
    try {
        // Validar nombre duplicado
        $stmt = $pdo->prepare("SELECT id FROM motivos_hp WHERE nombre = ?");
        $stmt->execute([$nombre]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ya existe ese motivo']);
            exit;
        }
        
        // Validar código duplicado (si se proporciona)
        if (!empty($codigo)) {
            $stmt = $pdo->prepare("SELECT id FROM motivos_hp WHERE codigo = ?");
            $stmt->execute([$codigo]);
            
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Ya existe ese código']);
                exit;
            }
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO motivos_hp 
            (codigo, nombre, descripcion, categoria_parada, es_justificada, requiere_observacion, es_frecuente, orden_mostrar) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$codigo, $nombre, $descripcion, $categoria, $esJustificada, $requiereObs, $esFrecuente, $orden])) {
            
            $nuevo_id = $pdo->lastInsertId();
            
            // 🎯 AUTOASIGNAR ORDEN POR ID si dejó el valor por defecto (999)
            if ($orden == 999) {
                $stmtOrden = $pdo->prepare("UPDATE motivos_hp SET orden_mostrar = ? WHERE id = ?");
                $stmtOrden->execute([$nuevo_id, $nuevo_id]);
            }
            
            $stmtAudit = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion, detalle) VALUES (?, ?, ?)");
            $stmtAudit->execute([$userId, 'crear_motivo_hp', "Motivo HP creado: $nombre"]);
            
            echo json_encode(['success' => true, 'message' => 'Motivo creado', 'id' => $nuevo_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
}

// ============================================
// ACTUALIZAR MOTIVO HP
// ============================================
elseif ($action === 'actualizar') {
    
    if ($userRol !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Sin permisos']);
        exit;
    }
    
    $id = $_POST['id'] ?? 0;
    $codigo = trim($_POST['codigo'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $categoria = $_POST['categoria_parada'] ?? 'operacional';
    $esJustificada = $_POST['es_justificada'] ?? 1;
    $requiereObs = $_POST['requiere_observacion'] ?? 0;
    $esFrecuente = $_POST['es_frecuente'] ?? 0;
    $orden = $_POST['orden_mostrar'] ?? 999;
    $estado = $_POST['estado'] ?? 1;
    
    if (!$id || empty($nombre)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }
    
    try {
        // Validar nombre duplicado
        $stmt = $pdo->prepare("SELECT id FROM motivos_hp WHERE nombre = ? AND id != ?");
        $stmt->execute([$nombre, $id]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ya existe otro motivo con ese nombre']);
            exit;
        }
        
        // Validar código duplicado (si se proporciona)
        if (!empty($codigo)) {
            $stmt = $pdo->prepare("SELECT id FROM motivos_hp WHERE codigo = ? AND id != ?");
            $stmt->execute([$codigo, $id]);
            
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Ya existe otro registro con ese código']);
                exit;
            }
        }
        
        $stmt = $pdo->prepare("
            UPDATE motivos_hp 
            SET codigo = ?, nombre = ?, descripcion = ?, categoria_parada = ?, es_justificada = ?, 
                requiere_observacion = ?, es_frecuente = ?, orden_mostrar = ?, estado = ?
            WHERE id = ?
        ");
        
        if ($stmt->execute([$codigo, $nombre, $descripcion, $categoria, $esJustificada, $requiereObs, $esFrecuente, $orden, $estado, $id])) {
            
            $stmtAudit = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion, detalle) VALUES (?, ?, ?)");
            $stmtAudit->execute([$userId, 'editar_motivo_hp', "Motivo HP actualizado: $nombre"]);
            
            echo json_encode(['success' => true, 'message' => 'Motivo actualizado']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
}

// ============================================
// ELIMINAR MOTIVO HP
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
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reportes_detalle WHERE motivo_hp_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        if ($result['total'] > 0) {
            // Solo desactivar
            $stmt = $pdo->prepare("UPDATE motivos_hp SET estado = 0 WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Motivo desactivado (tiene reportes asociados)']);
        } else {
            // Eliminar permanentemente
            $stmt = $pdo->prepare("DELETE FROM motivos_hp WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Motivo eliminado']);
        }
        
        $stmtAudit = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion, detalle) VALUES (?, ?, ?)");
        $stmtAudit->execute([$userId, 'eliminar_motivo_hp', "Motivo HP ID: $id"]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
    }
}

// ============================================
// OBTENER UN MOTIVO
// ============================================
elseif ($action === 'obtener') {
    
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID no válido']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM motivos_hp WHERE id = ?");
        $stmt->execute([$id]);
        $motivo = $stmt->fetch();
        
        if ($motivo) {
            echo json_encode(['success' => true, 'motivo' => $motivo]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No encontrado']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener']);
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}