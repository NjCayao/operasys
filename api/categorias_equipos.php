<?php
/**
 * OperaSys - API de Categorías de Equipos V3.2
 * Archivo: api/categorias_equipos.php
 * Descripción: CRUD de categorías de equipos
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
// LISTAR TODAS LAS CATEGORÍAS
// ============================================
if ($action === 'listar') {
    
    try {
        $stmt = $pdo->query("
            SELECT 
                c.id, 
                c.nombre, 
                c.descripcion,
                c.consumo_default,
                c.capacidad_default,
                c.orden,
                c.estado,
                DATE_FORMAT(c.fecha_creacion, '%d/%m/%Y') as fecha_creacion,
                COUNT(e.id) as total_equipos
            FROM categorias_equipos c
            LEFT JOIN equipos e ON c.id = e.categoria_id AND e.estado = 1
            GROUP BY c.id
            ORDER BY c.orden ASC, c.nombre ASC
        ");
        
        $categorias = $stmt->fetchAll();
        
        $data = [];
        foreach ($categorias as $cat) {
            $estadoBadge = $cat['estado'] == 1 
                ? '<span class="badge badge-success">Activo</span>' 
                : '<span class="badge badge-danger">Inactivo</span>';
            
            $equiposBadge = '<span class="badge badge-info">' . $cat['total_equipos'] . ' equipo(s)</span>';
            
            $acciones = '';
            if ($userRol === 'admin') {
                $acciones = '<button onclick="editarCategoria(' . $cat['id'] . ')" class="btn btn-sm btn-warning" title="Editar">
                    <i class="fas fa-edit"></i></button> ';
                $acciones .= '<button onclick="eliminarCategoria(' . $cat['id'] . ', \'' . htmlspecialchars($cat['nombre']) . '\')" class="btn btn-sm btn-danger" title="Eliminar">
                    <i class="fas fa-trash"></i></button>';
            }
            
            $data[] = [
                $cat['id'],                
                '<strong>' . htmlspecialchars($cat['nombre']) . '</strong>',
                $cat['descripcion'] ?? '-',
                number_format($cat['consumo_default'], 1) . ' gal/hr',
                number_format($cat['capacidad_default'], 0) . ' gal',
                $equiposBadge,
                $cat['orden'],
                $estadoBadge,
                $acciones
            ];
        }
        
        echo json_encode(['success' => true, 'data' => $data]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener categorías']);
    }
}

// ============================================
// LISTAR CATEGORÍAS ACTIVAS (Para dropdowns)
// ============================================
elseif ($action === 'listar_activas') {
    
    try {
        $stmt = $pdo->query("
            SELECT id, nombre, consumo_default, capacidad_default
            FROM categorias_equipos 
            WHERE estado = 1 
            ORDER BY orden ASC, nombre ASC
        ");
        
        $categorias = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'categorias' => $categorias]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener categorías']);
    }
}

// ============================================
// CREAR CATEGORÍA
// ============================================
elseif ($action === 'crear') {
    
    if ($userRol !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Sin permisos']);
        exit;
    }
    
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');    
    $consumo_default = $_POST['consumo_default'] ?? 5.0;
    $capacidad_default = $_POST['capacidad_default'] ?? 100;
    $orden = $_POST['orden'] ?? 999;
    
    if (empty($nombre)) {
        echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio']);
        exit;
    }
    
    try {
        // Verificar si ya existe
        $stmt = $pdo->prepare("SELECT id FROM categorias_equipos WHERE nombre = ?");
        $stmt->execute([$nombre]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ya existe una categoría con ese nombre']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO categorias_equipos (
                nombre, descripcion, consumo_default, 
                capacidad_default, orden
            ) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([
            $nombre, $descripcion, $consumo_default, 
            $capacidad_default, $orden
        ])) {
            
            $stmtAudit = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion, detalle) VALUES (?, ?, ?)");
            $stmtAudit->execute([$userId, 'crear_categoria_equipo', "Categoría creada: $nombre"]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Categoría creada exitosamente', 
                'id' => $pdo->lastInsertId()
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear categoría']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
}

// ============================================
// ACTUALIZAR CATEGORÍA
// ============================================
elseif ($action === 'actualizar') {
    
    if ($userRol !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Sin permisos']);
        exit;
    }
    
    $id = $_POST['id'] ?? 0;
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $consumo_default = $_POST['consumo_default'] ?? 5.0;
    $capacidad_default = $_POST['capacidad_default'] ?? 100;
    $orden = $_POST['orden'] ?? 999;
    $estado = $_POST['estado'] ?? 1;
    
    if (!$id || empty($nombre)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }
    
    try {
        // Verificar si el nombre ya existe en otra categoría
        $stmt = $pdo->prepare("SELECT id FROM categorias_equipos WHERE nombre = ? AND id != ?");
        $stmt->execute([$nombre, $id]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ya existe otra categoría con ese nombre']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            UPDATE categorias_equipos 
            SET nombre = ?, descripcion = ?, 
                consumo_default = ?, capacidad_default = ?, 
                orden = ?, estado = ?
            WHERE id = ?
        ");
        
        if ($stmt->execute([
            $nombre, $descripcion,
            $consumo_default, $capacidad_default,
            $orden, $estado, $id
        ])) {
            
            $stmtAudit = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion, detalle) VALUES (?, ?, ?)");
            $stmtAudit->execute([$userId, 'editar_categoria_equipo', "Categoría actualizada: $nombre (ID: $id)"]);
            
            echo json_encode(['success' => true, 'message' => 'Categoría actualizada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
    }
}

// ============================================
// ELIMINAR CATEGORÍA
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
        // Verificar si tiene equipos asociados
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM equipos WHERE categoria_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        if ($result['total'] > 0) {
            // Desactivar en lugar de eliminar
            $stmt = $pdo->prepare("UPDATE categorias_equipos SET estado = 0 WHERE id = ?");
            $stmt->execute([$id]);
            
            $stmtAudit = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion, detalle) VALUES (?, ?, ?)");
            $stmtAudit->execute([$userId, 'desactivar_categoria_equipo', "Categoría desactivada (ID: $id) - Tiene equipos asociados"]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Categoría desactivada (tiene ' . $result['total'] . ' equipo(s) asociado(s))'
            ]);
        } else {
            // Eliminar completamente si no tiene equipos
            $stmt = $pdo->prepare("DELETE FROM categorias_equipos WHERE id = ?");
            $stmt->execute([$id]);
            
            $stmtAudit = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion, detalle) VALUES (?, ?, ?)");
            $stmtAudit->execute([$userId, 'eliminar_categoria_equipo', "Categoría eliminada (ID: $id)"]);
            
            echo json_encode(['success' => true, 'message' => 'Categoría eliminada exitosamente']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
    }
}

// ============================================
// OBTENER UNA CATEGORÍA
// ============================================
elseif ($action === 'obtener') {
    
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID no válido']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, COUNT(e.id) as total_equipos
            FROM categorias_equipos c
            LEFT JOIN equipos e ON c.id = e.categoria_id
            WHERE c.id = ?
            GROUP BY c.id
        ");
        $stmt->execute([$id]);
        $categoria = $stmt->fetch();
        
        if ($categoria) {
            echo json_encode(['success' => true, 'categoria' => $categoria]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Categoría no encontrada']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener categoría']);
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}