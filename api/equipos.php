<?php
/**
 * OperaSys - API de Equipos V3.0
 * Archivo: api/equipos.php
 * Incluye: consumo_promedio_hr y capacidad_tanque
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
// OBTENER EQUIPOS FILTRADOS POR OPERADOR
// ============================================
if ($action === 'obtener_equipos_operador') {
    
    try {
        $stmt = $pdo->prepare("SELECT cargo FROM usuarios WHERE id = ?");
        $stmt->execute([$userId]);
        $usuario = $stmt->fetch();
        
        if (!$usuario) {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
            exit;
        }
        
        $cargo = $usuario['cargo'];
        
        // Admin/Supervisor → Todos
        if ($userRol === 'admin' || $userRol === 'supervisor') {
            $stmt = $pdo->query("
                SELECT id, categoria, codigo, descripcion, consumo_promedio_hr, capacidad_tanque
                FROM equipos 
                WHERE estado = 1 
                ORDER BY categoria, codigo
            ");
        } 
        // Operador → Solo su categoría
        elseif (strpos($cargo, 'Operador de ') === 0) {
            $categoria = str_replace('Operador de ', '', $cargo);
            
            $stmt = $pdo->prepare("
                SELECT id, categoria, codigo, descripcion, consumo_promedio_hr, capacidad_tanque
                FROM equipos 
                WHERE categoria = ? AND estado = 1 
                ORDER BY codigo
            ");
            $stmt->execute([$categoria]);
        } 
        else {
            $stmt = $pdo->query("
                SELECT id, categoria, codigo, descripcion, consumo_promedio_hr, capacidad_tanque
                FROM equipos 
                WHERE estado = 1 
                ORDER BY categoria, codigo
            ");
        }
        
        $equipos = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'equipos' => $equipos,
            'categoria_operador' => $categoria ?? 'Todos'
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener equipos']);
    }
}

// ============================================
// LISTAR TODOS (ADMIN)
// ============================================
elseif ($action === 'listar') {
    
    try {
        $stmt = $pdo->query("
            SELECT 
                id, categoria, codigo, descripcion,
                consumo_promedio_hr, capacidad_tanque, estado,
                DATE_FORMAT(fecha_creacion, '%d/%m/%Y') as fecha_creacion
            FROM equipos 
            ORDER BY categoria, codigo
        ");
        
        $equipos = $stmt->fetchAll();
        
        $data = [];
        foreach ($equipos as $equipo) {
            $estadoBadge = $equipo['estado'] == 1 
                ? '<span class="badge badge-success">Activo</span>' 
                : '<span class="badge badge-danger">Inactivo</span>';
            
            $acciones = '';
            if ($userRol === 'admin') {
                $acciones = '<button onclick="editarEquipo(' . $equipo['id'] . ')" class="btn btn-sm btn-warning">
                    <i class="fas fa-edit"></i></button> ';
                $acciones .= '<button onclick="eliminarEquipo(' . $equipo['id'] . ', \'' . htmlspecialchars($equipo['codigo']) . '\')" class="btn btn-sm btn-danger">
                    <i class="fas fa-trash"></i></button>';
            }
            
            $data[] = [
                $equipo['id'],
                '<strong>' . htmlspecialchars($equipo['categoria']) . '</strong>',
                htmlspecialchars($equipo['codigo']),
                $equipo['descripcion'] ?? '-',
                number_format($equipo['consumo_promedio_hr'], 1) . ' gal/hr',
                number_format($equipo['capacidad_tanque'], 0) . ' gal',
                $estadoBadge,
                $equipo['fecha_creacion'],
                $acciones
            ];
        }
        
        echo json_encode(['success' => true, 'data' => $data]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener equipos']);
    }
}

// ============================================
// CREAR EQUIPO
// ============================================
elseif ($action === 'crear') {
    
    if ($userRol !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Sin permisos']);
        exit;
    }
    
    $categoria = trim($_POST['categoria'] ?? '');
    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    $descripcion = trim($_POST['descripcion'] ?? '');
    $consumo = $_POST['consumo_promedio_hr'] ?? 5.0;
    $capacidad = $_POST['capacidad_tanque'] ?? 100;
    
    if (empty($categoria) || empty($codigo)) {
        echo json_encode(['success' => false, 'message' => 'Categoría y código son obligatorios']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM equipos WHERE codigo = ?");
        $stmt->execute([$codigo]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ya existe un equipo con ese código']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO equipos (categoria, codigo, descripcion, consumo_promedio_hr, capacidad_tanque) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$categoria, $codigo, $descripcion, $consumo, $capacidad])) {
            
            $stmtAudit = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion, detalle) VALUES (?, ?, ?)");
            $stmtAudit->execute([$userId, 'crear_equipo', "Equipo creado: $codigo"]);
            
            echo json_encode(['success' => true, 'message' => 'Equipo creado', 'id' => $pdo->lastInsertId()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
}

// ============================================
// ACTUALIZAR EQUIPO
// ============================================
elseif ($action === 'actualizar') {
    
    if ($userRol !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Sin permisos']);
        exit;
    }
    
    $id = $_POST['id'] ?? 0;
    $categoria = trim($_POST['categoria'] ?? '');
    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    $descripcion = trim($_POST['descripcion'] ?? '');
    $consumo = $_POST['consumo_promedio_hr'] ?? 5.0;
    $capacidad = $_POST['capacidad_tanque'] ?? 100;
    $estado = $_POST['estado'] ?? 1;
    
    if (!$id || empty($categoria) || empty($codigo)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM equipos WHERE codigo = ? AND id != ?");
        $stmt->execute([$codigo, $id]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ya existe otro equipo con ese código']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            UPDATE equipos 
            SET categoria = ?, codigo = ?, descripcion = ?, 
                consumo_promedio_hr = ?, capacidad_tanque = ?, estado = ?
            WHERE id = ?
        ");
        
        if ($stmt->execute([$categoria, $codigo, $descripcion, $consumo, $capacidad, $estado, $id])) {
            
            $stmtAudit = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion, detalle) VALUES (?, ?, ?)");
            $stmtAudit->execute([$userId, 'editar_equipo', "Equipo actualizado: $codigo"]);
            
            echo json_encode(['success' => true, 'message' => 'Equipo actualizado']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
}

// ============================================
// 🆕 V3.0: ACTUALIZAR CONSUMO (Solo admin)
// ============================================
elseif ($action === 'actualizar_consumo') {
    
    if ($userRol !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Sin permisos']);
        exit;
    }
    
    $id = $_POST['id'] ?? 0;
    $consumo = $_POST['consumo_promedio_hr'] ?? null;
    $capacidad = $_POST['capacidad_tanque'] ?? null;
    
    if (!$id || ($consumo === null && $capacidad === null)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }
    
    try {
        // Obtener datos actuales
        $stmt = $pdo->prepare("SELECT codigo, consumo_promedio_hr, capacidad_tanque FROM equipos WHERE id = ?");
        $stmt->execute([$id]);
        $equipo = $stmt->fetch();
        
        if (!$equipo) {
            echo json_encode(['success' => false, 'message' => 'Equipo no encontrado']);
            exit;
        }
        
        // Actualizar solo los campos proporcionados
        $nuevoConsumo = $consumo !== null ? $consumo : $equipo['consumo_promedio_hr'];
        $nuevaCapacidad = $capacidad !== null ? $capacidad : $equipo['capacidad_tanque'];
        
        $stmt = $pdo->prepare("
            UPDATE equipos 
            SET consumo_promedio_hr = ?, capacidad_tanque = ?
            WHERE id = ?
        ");
        
        if ($stmt->execute([$nuevoConsumo, $nuevaCapacidad, $id])) {
            
            $detalle = "Consumo actualizado: {$equipo['codigo']} - {$nuevoConsumo} gal/hr, {$nuevaCapacidad} gal";
            $stmtAudit = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion, detalle) VALUES (?, ?, ?)");
            $stmtAudit->execute([$userId, 'actualizar_consumo_equipo', $detalle]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Consumo actualizado',
                'consumo_promedio_hr' => $nuevoConsumo,
                'capacidad_tanque' => $nuevaCapacidad
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
}

// ============================================
// ELIMINAR EQUIPO
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
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reportes WHERE equipo_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        if ($result['total'] > 0) {
            $stmt = $pdo->prepare("UPDATE equipos SET estado = 0 WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Equipo desactivado (tiene reportes asociados)']);
        } else {
            $stmt = $pdo->prepare("DELETE FROM equipos WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Equipo eliminado']);
        }
        
        $stmtAudit = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion, detalle) VALUES (?, ?, ?)");
        $stmtAudit->execute([$userId, 'eliminar_equipo', "Equipo ID: $id"]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
    }
}

// ============================================
// OBTENER UN EQUIPO
// ============================================
elseif ($action === 'obtener') {
    
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID no válido']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM equipos WHERE id = ?");
        $stmt->execute([$id]);
        $equipo = $stmt->fetch();
        
        if ($equipo) {
            echo json_encode(['success' => true, 'equipo' => $equipo]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No encontrado']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener']);
    }
}

// ============================================
// OBTENER CATEGORÍAS
// ============================================
elseif ($action === 'obtener_categorias') {
    
    try {
        $stmt = $pdo->query("
            SELECT DISTINCT categoria 
            FROM equipos 
            WHERE estado = 1 
            ORDER BY categoria ASC
        ");
        
        $categorias = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $categorias]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener categorías']);
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}