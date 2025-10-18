<?php
/**
 * OperaSys - API de Equipos V3.1
 * Archivo: api/equipos.php
 * Incluye: consumo_promedio_hr, capacidad_tanque y CONTRATAS
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
                SELECT 
                    e.id, e.codigo, e.descripcion,
                    e.contrata_id, e.tipo_tarifa, e.tarifa_alquiler,
                    c.razon_social as nombre_contrata,
                    cat.nombre as categoria
                FROM equipos e
                LEFT JOIN contratas c ON e.contrata_id = c.id
                LEFT JOIN categorias_equipos cat ON e.categoria_id = cat.id
                WHERE e.estado = 1 
                ORDER BY cat.nombre, e.codigo
            ");
        } 
        // Operador → Solo su categoría
        elseif (strpos($cargo, 'Operador de ') === 0) {
            $categoriaNombre = str_replace('Operador de ', '', $cargo);
            
            $stmt = $pdo->prepare("
                SELECT 
                    e.id, e.codigo, e.descripcion,
                    e.contrata_id, e.tipo_tarifa, e.tarifa_alquiler,
                    c.razon_social as nombre_contrata,
                    cat.nombre as categoria
                FROM equipos e
                LEFT JOIN contratas c ON e.contrata_id = c.id
                LEFT JOIN categorias_equipos cat ON e.categoria_id = cat.id
                WHERE cat.nombre = ? AND e.estado = 1 
                ORDER BY e.codigo
            ");
            $stmt->execute([$categoriaNombre]);
        } 
        else {
            $stmt = $pdo->query("
                SELECT 
                    e.id, e.codigo, e.descripcion,
                    e.contrata_id, e.tipo_tarifa, e.tarifa_alquiler,
                    c.razon_social as nombre_contrata,
                    cat.nombre as categoria
                FROM equipos e
                LEFT JOIN contratas c ON e.contrata_id = c.id
                LEFT JOIN categorias_equipos cat ON e.categoria_id = cat.id
                WHERE e.estado = 1 
                ORDER BY cat.nombre, e.codigo
            ");
        }
        
        $equipos = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'equipos' => $equipos,
            'categoria_operador' => $categoriaNombre ?? 'Todos'
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener equipos']);
    }
}

// ============================================
// LISTAR TODOS (ADMIN) - CON CONTRATAS
// ============================================
elseif ($action === 'listar') {
    
    try {
        $stmt = $pdo->query("
            SELECT 
                e.id, e.codigo, e.descripcion,
                e.contrata_id, e.tipo_tarifa, e.tarifa_alquiler,
                e.estado,
                DATE_FORMAT(e.fecha_creacion, '%d/%m/%Y') as fecha_creacion,
                c.razon_social as nombre_contrata,
                cat.nombre as categoria,
                cat.consumo_default,
                cat.capacidad_default
            FROM equipos e
            LEFT JOIN contratas c ON e.contrata_id = c.id
            LEFT JOIN categorias_equipos cat ON e.categoria_id = cat.id
            ORDER BY cat.nombre, e.codigo
        ");
        
        $equipos = $stmt->fetchAll();
        
        $data = [];
        foreach ($equipos as $equipo) {
            $estadoBadge = $equipo['estado'] == 1 
                ? '<span class="badge badge-success">Activo</span>' 
                : '<span class="badge badge-danger">Inactivo</span>';
            
            // Badge de propiedad
            if ($equipo['contrata_id']) {
                $nombreContrata = htmlspecialchars($equipo['nombre_contrata'] ?? 'Contrata sin nombre');
                $propietarioBadge = '<span class="badge badge-warning">
                    <i class="fas fa-handshake"></i> Alquilado</span><br>
                    <small class="text-muted">' . $nombreContrata . '</small>';
            } else {
                $propietarioBadge = '<span class="badge badge-primary">
                    <i class="fas fa-building"></i> Propio</span>';
            }
            
            // Tarifa
            $tarifa = '-';
            if ($equipo['tarifa_alquiler']) {
                $tarifa = 'S/ ' . number_format($equipo['tarifa_alquiler'], 2) . '/' . $equipo['tipo_tarifa'];
            }
            
            $acciones = '';
            if ($userRol === 'admin') {
                $acciones = '<button onclick="editarEquipo(' . $equipo['id'] . ')" class="btn btn-sm btn-warning">
                    <i class="fas fa-edit"></i></button> ';
                $acciones .= '<button onclick="eliminarEquipo(' . $equipo['id'] . ', \'' . htmlspecialchars($equipo['codigo']) . '\')" class="btn btn-sm btn-danger">
                    <i class="fas fa-trash"></i></button>';
            }
            
            $data[] = [
                $equipo['id'],
                '<strong>' . htmlspecialchars($equipo['categoria'] ?? 'Sin categoría') . '</strong>',
                htmlspecialchars($equipo['codigo'] ?? ''),
                htmlspecialchars($equipo['descripcion'] ?? '-'),
                $propietarioBadge,
                $tarifa,
                number_format($equipo['consumo_default'] ?? 0, 1) . ' gal/hr',
                number_format($equipo['capacidad_default'] ?? 0, 0) . ' gal',
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
// CREAR EQUIPO - CON CONTRATAS
// ============================================
elseif ($action === 'crear') {
    
    if ($userRol !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Sin permisos']);
        exit;
    }
    
    $categoria_id = $_POST['categoria_id'] ?? 0;
    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    // Nuevos campos de contrata
    $contrata_id = !empty($_POST['contrata_id']) ? $_POST['contrata_id'] : null;
    $tipo_tarifa = $_POST['tipo_tarifa'] ?? 'hora';
    $tarifa_alquiler = !empty($_POST['tarifa_alquiler']) ? $_POST['tarifa_alquiler'] : null;
    $observaciones_contrata = trim($_POST['observaciones_contrata'] ?? '');
    
    if (empty($categoria_id) || empty($codigo)) {
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
            INSERT INTO equipos (
                categoria_id, codigo, descripcion,
                contrata_id, tipo_tarifa, tarifa_alquiler, observaciones_contrata
            ) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([
            $categoria_id, $codigo, $descripcion,
            $contrata_id, $tipo_tarifa, $tarifa_alquiler, $observaciones_contrata
        ])) {
            
            $propietario = $contrata_id ? "Contrata ID: $contrata_id" : "Equipo propio";
            $stmtAudit = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion, detalle) VALUES (?, ?, ?)");
            $stmtAudit->execute([$userId, 'crear_equipo', "Equipo creado: $codigo ($propietario)"]);
            
            echo json_encode(['success' => true, 'message' => 'Equipo creado', 'id' => $pdo->lastInsertId()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
}

// ============================================
// ACTUALIZAR EQUIPO - CON CONTRATAS
// ============================================
elseif ($action === 'actualizar') {
    
    if ($userRol !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Sin permisos']);
        exit;
    }
    
    $id = $_POST['id'] ?? 0;
    $categoria_id = $_POST['categoria_id'] ?? 0;
    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    $descripcion = trim($_POST['descripcion'] ?? '');
    $estado = $_POST['estado'] ?? 1;
    
    // Campos de contrata
    $contrata_id = !empty($_POST['contrata_id']) ? $_POST['contrata_id'] : null;
    $tipo_tarifa = $_POST['tipo_tarifa'] ?? 'hora';
    $tarifa_alquiler = !empty($_POST['tarifa_alquiler']) ? $_POST['tarifa_alquiler'] : null;
    $observaciones_contrata = trim($_POST['observaciones_contrata'] ?? '');
    
    if (!$id || empty($categoria_id) || empty($codigo)) {
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
            SET categoria_id = ?, codigo = ?, descripcion = ?, estado = ?,
                contrata_id = ?, tipo_tarifa = ?, tarifa_alquiler = ?, observaciones_contrata = ?
            WHERE id = ?
        ");
        
        if ($stmt->execute([
            $categoria_id, $codigo, $descripcion, $estado,
            $contrata_id, $tipo_tarifa, $tarifa_alquiler, $observaciones_contrata,
            $id
        ])) {
            
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
        $stmt = $pdo->prepare("
            SELECT e.*, c.razon_social as nombre_contrata, cat.nombre as categoria
            FROM equipos e
            LEFT JOIN contratas c ON e.contrata_id = c.id
            LEFT JOIN categorias_equipos cat ON e.categoria_id = cat.id
            WHERE e.id = ?
        ");
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
            SELECT DISTINCT cat.nombre as categoria
            FROM categorias_equipos cat
            WHERE cat.estado = 1 
            ORDER BY cat.nombre ASC
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