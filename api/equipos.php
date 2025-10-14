<?php
/**
 * OperaSys - API de Equipos
 * Archivo: api/equipos.php
 * Descripción: Gestión de equipos con filtrado por categoría del operador
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
// OBTENER EQUIPOS FILTRADOS POR OPERADOR
// ============================================
if ($action === 'obtener_equipos_operador') {
    
    try {
        // Obtener cargo del usuario actual
        $stmt = $pdo->prepare("SELECT cargo FROM usuarios WHERE id = ?");
        $stmt->execute([$userId]);
        $usuario = $stmt->fetch();
        
        if (!$usuario) {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
            exit;
        }
        
        $cargo = $usuario['cargo'];
        
        // Si es Admin o Supervisor → Mostrar TODOS los equipos
        if ($userRol === 'admin' || $userRol === 'supervisor') {
            $stmt = $pdo->query("
                SELECT id, categoria, codigo, descripcion 
                FROM equipos 
                WHERE estado = 1 
                ORDER BY categoria, codigo
            ");
        } 
        // Si es Operador → Filtrar por su categoría
        elseif (strpos($cargo, 'Operador de ') === 0) {
            // Extraer categoría del cargo: "Operador de Excavadora" → "Excavadora"
            $categoria = str_replace('Operador de ', '', $cargo);
            
            $stmt = $pdo->prepare("
                SELECT id, categoria, codigo, descripcion 
                FROM equipos 
                WHERE categoria = ? AND estado = 1 
                ORDER BY codigo
            ");
            $stmt->execute([$categoria]);
        } 
        // Otro tipo de usuario → Mostrar todos
        else {
            $stmt = $pdo->query("
                SELECT id, categoria, codigo, descripcion 
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
        echo json_encode(['success' => false, 'message' => 'Error al obtener equipos: ' . $e->getMessage()]);
    }
}

// ============================================
// LISTAR TODOS LOS EQUIPOS (ADMIN)
// ============================================
elseif ($action === 'listar') {
    
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
            ORDER BY categoria, codigo
        ");
        
        $equipos = $stmt->fetchAll();
        
        // Formato para DataTables
        $data = [];
        foreach ($equipos as $equipo) {
            $estadoBadge = $equipo['estado'] == 1 
                ? '<span class="badge badge-success">Activo</span>' 
                : '<span class="badge badge-danger">Inactivo</span>';
            
            // Botones de acción (solo admin)
            $acciones = '';
            if ($userRol === 'admin') {
                $acciones .= '<button onclick="editarEquipo(' . $equipo['id'] . ')" 
                    class="btn btn-sm btn-warning" title="Editar">
                    <i class="fas fa-edit"></i>
                </button> ';
                
                $acciones .= '<button onclick="eliminarEquipo(' . $equipo['id'] . ', \'' . htmlspecialchars($equipo['codigo']) . '\')" 
                    class="btn btn-sm btn-danger" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>';
            }
            
            $data[] = [
                $equipo['id'],
                '<strong>' . htmlspecialchars($equipo['categoria']) . '</strong>',
                htmlspecialchars($equipo['codigo']),
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
    
    // Solo admin
    if ($userRol !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'No tiene permisos']);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }
    
    $categoria = trim($_POST['categoria'] ?? '');
    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    if (empty($categoria) || empty($codigo)) {
        echo json_encode(['success' => false, 'message' => 'Categoría y código son obligatorios']);
        exit;
    }
    
    try {
        // Verificar si ya existe el código
        $stmt = $pdo->prepare("SELECT id FROM equipos WHERE codigo = ?");
        $stmt->execute([$codigo]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ya existe un equipo con ese código']);
            exit;
        }
        
        // Insertar
        $stmt = $pdo->prepare("
            INSERT INTO equipos (categoria, codigo, descripcion) 
            VALUES (?, ?, ?)
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
                'message' => 'Equipo creado correctamente',
                'equipo_id' => $equipoId
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear equipo']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
}

// ============================================
// ACTUALIZAR EQUIPO
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
    
    $equipoId = $_POST['id'] ?? 0;
    $categoria = trim($_POST['categoria'] ?? '');
    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    $descripcion = trim($_POST['descripcion'] ?? '');
    $estado = $_POST['estado'] ?? 1;
    
    if (!$equipoId || empty($categoria) || empty($codigo)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }
    
    try {
        // Verificar si existe otro con el mismo código
        $stmt = $pdo->prepare("SELECT id FROM equipos WHERE codigo = ? AND id != ?");
        $stmt->execute([$codigo, $equipoId]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ya existe otro equipo con ese código']);
            exit;
        }
        
        // Actualizar
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
    
    // Solo admin
    if ($userRol !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'No tiene permisos']);
        exit;
    }
    
    $equipoId = $_POST['id'] ?? 0;
    
    if (!$equipoId) {
        echo json_encode(['success' => false, 'message' => 'ID no válido']);
        exit;
    }
    
    try {
        // Verificar si tiene reportes asociados
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total FROM reportes WHERE equipo_id = ?
        ");
        $stmt->execute([$equipoId]);
        $result = $stmt->fetch();
        
        if ($result['total'] > 0) {
            // No eliminar, solo desactivar
            $stmt = $pdo->prepare("UPDATE equipos SET estado = 0 WHERE id = ?");
            $stmt->execute([$equipoId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'El equipo tiene reportes asociados. Se ha desactivado.'
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
        echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
    }
}

// ============================================
// OBTENER UN EQUIPO PARA EDITAR
// ============================================
elseif ($action === 'obtener') {
    
    $equipoId = $_GET['id'] ?? 0;
    
    if (!$equipoId) {
        echo json_encode(['success' => false, 'message' => 'ID no válido']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM equipos WHERE id = ?");
        $stmt->execute([$equipoId]);
        $equipo = $stmt->fetch();
        
        if ($equipo) {
            echo json_encode([
                'success' => true,
                'equipo' => $equipo
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Equipo no encontrado']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener equipo']);
    }
}

// ============================================
// ACCIÓN NO VÁLIDA
// ============================================
else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}