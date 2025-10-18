<?php
/**
 * OperaSys - API de Contratas V3.1
 * Archivo: api/contratas.php
 * Descripción: CRUD de empresas subcontratistas
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
// LISTAR TODAS LAS CONTRATAS
// ============================================
if ($action === 'listar') {
    
    try {
        $stmt = $pdo->query("
            SELECT 
                c.id, 
                c.razon_social, 
                c.ruc, 
                c.contacto, 
                c.telefono, 
                c.email,
                c.fecha_inicio_contrato,
                c.fecha_fin_contrato,
                c.estado,
                DATE_FORMAT(c.fecha_creacion, '%d/%m/%Y') as fecha_creacion,
                COUNT(e.id) as total_equipos
            FROM contratas c
            LEFT JOIN equipos e ON c.id = e.contrata_id AND e.estado = 1
            GROUP BY c.id
            ORDER BY c.razon_social ASC
        ");
        
        $contratas = $stmt->fetchAll();
        
        $data = [];
        foreach ($contratas as $contrata) {
            $estadoBadge = $contrata['estado'] == 1 
                ? '<span class="badge badge-success">Activo</span>' 
                : '<span class="badge badge-danger">Inactivo</span>';
            
            $equiposBadge = '<span class="badge badge-info">' . $contrata['total_equipos'] . ' equipo(s)</span>';
            
            $vigencia = '-';
            if ($contrata['fecha_inicio_contrato'] && $contrata['fecha_fin_contrato']) {
                $inicio = date('d/m/Y', strtotime($contrata['fecha_inicio_contrato']));
                $fin = date('d/m/Y', strtotime($contrata['fecha_fin_contrato']));
                $vigencia = "$inicio - $fin";
                
                // Verificar si está vencido
                if (strtotime($contrata['fecha_fin_contrato']) < time()) {
                    $vigencia .= ' <span class="badge badge-danger"><i class="fas fa-exclamation-triangle"></i> Vencido</span>';
                }
            }
            
            $acciones = '<button onclick="verContrata(' . $contrata['id'] . ')" class="btn btn-sm btn-info" title="Ver detalle">
                <i class="fas fa-eye"></i></button> ';
            
            if ($userRol === 'admin') {
                $acciones .= '<button onclick="editarContrata(' . $contrata['id'] . ')" class="btn btn-sm btn-warning" title="Editar">
                    <i class="fas fa-edit"></i></button> ';
                $acciones .= '<button onclick="eliminarContrata(' . $contrata['id'] . ', \'' . htmlspecialchars($contrata['razon_social']) . '\')" class="btn btn-sm btn-danger" title="Eliminar">
                    <i class="fas fa-trash"></i></button>';
            }
            
            $data[] = [
                $contrata['id'],
                '<strong>' . htmlspecialchars($contrata['razon_social']) . '</strong>',
                htmlspecialchars($contrata['ruc']),
                $contrata['contacto'] ?? '-',
                $contrata['telefono'] ?? '-',
                $equiposBadge,
                $vigencia,
                $estadoBadge,
                $acciones
            ];
        }
        
        echo json_encode(['success' => true, 'data' => $data]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener contratas']);
    }
}

// ============================================
// LISTAR CONTRATAS ACTIVAS (Para dropdowns)
// ============================================
elseif ($action === 'listar_activas') {
    
    try {
        $stmt = $pdo->query("
            SELECT id, razon_social, ruc
            FROM contratas 
            WHERE estado = 1 
            ORDER BY razon_social ASC
        ");
        
        $contratas = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'contratas' => $contratas]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener contratas']);
    }
}

// ============================================
// CREAR CONTRATA
// ============================================
elseif ($action === 'crear') {
    
    if ($userRol !== 'admin' && $userRol !== 'supervisor') {
        echo json_encode(['success' => false, 'message' => 'Sin permisos']);
        exit;
    }
    
    $razon_social = trim($_POST['razon_social'] ?? '');
    $ruc = trim($_POST['ruc'] ?? '');
    $contacto = trim($_POST['contacto'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $fecha_inicio = $_POST['fecha_inicio_contrato'] ?? null;
    $fecha_fin = $_POST['fecha_fin_contrato'] ?? null;
    
    if (empty($razon_social) || empty($ruc)) {
        echo json_encode(['success' => false, 'message' => 'Razón social y RUC son obligatorios']);
        exit;
    }
    
    try {
        // Verificar si ya existe el RUC
        $stmt = $pdo->prepare("SELECT id FROM contratas WHERE ruc = ?");
        $stmt->execute([$ruc]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ya existe una contrata con ese RUC']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO contratas (
                razon_social, ruc, contacto, telefono, email, 
                direccion, fecha_inicio_contrato, fecha_fin_contrato
            ) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([
            $razon_social, $ruc, $contacto, $telefono, $email, 
            $direccion, $fecha_inicio, $fecha_fin
        ])) {
            
            $stmtAudit = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion, detalle) VALUES (?, ?, ?)");
            $stmtAudit->execute([$userId, 'crear_contrata', "Contrata creada: $razon_social (RUC: $ruc)"]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Contrata creada exitosamente', 
                'id' => $pdo->lastInsertId()
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear contrata']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
}

// ============================================
// ACTUALIZAR CONTRATA
// ============================================
elseif ($action === 'actualizar') {
    
    if ($userRol !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Sin permisos']);
        exit;
    }
    
    $id = $_POST['id'] ?? 0;
    $razon_social = trim($_POST['razon_social'] ?? '');
    $ruc = trim($_POST['ruc'] ?? '');
    $contacto = trim($_POST['contacto'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $fecha_inicio = $_POST['fecha_inicio_contrato'] ?? null;
    $fecha_fin = $_POST['fecha_fin_contrato'] ?? null;
    $estado = $_POST['estado'] ?? 1;
    
    if (!$id || empty($razon_social) || empty($ruc)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }
    
    try {
        // Verificar si el RUC ya existe en otra contrata
        $stmt = $pdo->prepare("SELECT id FROM contratas WHERE ruc = ? AND id != ?");
        $stmt->execute([$ruc, $id]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ya existe otra contrata con ese RUC']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            UPDATE contratas 
            SET razon_social = ?, ruc = ?, contacto = ?, telefono = ?, email = ?,
                direccion = ?, fecha_inicio_contrato = ?, fecha_fin_contrato = ?, estado = ?
            WHERE id = ?
        ");
        
        if ($stmt->execute([
            $razon_social, $ruc, $contacto, $telefono, $email,
            $direccion, $fecha_inicio, $fecha_fin, $estado, $id
        ])) {
            
            $stmtAudit = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion, detalle) VALUES (?, ?, ?)");
            $stmtAudit->execute([$userId, 'editar_contrata', "Contrata actualizada: $razon_social (ID: $id)"]);
            
            echo json_encode(['success' => true, 'message' => 'Contrata actualizada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
}

// ============================================
// ELIMINAR CONTRATA
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
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM equipos WHERE contrata_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        if ($result['total'] > 0) {
            // Desactivar en lugar de eliminar
            $stmt = $pdo->prepare("UPDATE contratas SET estado = 0 WHERE id = ?");
            $stmt->execute([$id]);
            
            $stmtAudit = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion, detalle) VALUES (?, ?, ?)");
            $stmtAudit->execute([$userId, 'desactivar_contrata', "Contrata desactivada (ID: $id) - Tiene equipos asociados"]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Contrata desactivada (tiene ' . $result['total'] . ' equipo(s) asociado(s))'
            ]);
        } else {
            // Eliminar completamente si no tiene equipos
            $stmt = $pdo->prepare("DELETE FROM contratas WHERE id = ?");
            $stmt->execute([$id]);
            
            $stmtAudit = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion, detalle) VALUES (?, ?, ?)");
            $stmtAudit->execute([$userId, 'eliminar_contrata', "Contrata eliminada (ID: $id)"]);
            
            echo json_encode(['success' => true, 'message' => 'Contrata eliminada exitosamente']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
    }
}

// ============================================
// OBTENER UNA CONTRATA
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
            FROM contratas c
            LEFT JOIN equipos e ON c.id = e.contrata_id
            WHERE c.id = ?
            GROUP BY c.id
        ");
        $stmt->execute([$id]);
        $contrata = $stmt->fetch();
        
        if ($contrata) {
            echo json_encode(['success' => true, 'contrata' => $contrata]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Contrata no encontrada']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener contrata']);
    }
}

// ============================================
// OBTENER EQUIPOS DE UNA CONTRATA
// ============================================
elseif ($action === 'obtener_equipos') {
    
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID no válido']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                id, categoria, codigo, descripcion, 
                tipo_tarifa, tarifa_alquiler, estado
            FROM equipos 
            WHERE contrata_id = ?
            ORDER BY categoria, codigo
        ");
        $stmt->execute([$id]);
        $equipos = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'equipos' => $equipos]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener equipos']);
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}