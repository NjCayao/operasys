<?php
/**
 * OperaSys - API de Auditoría
 * Archivo: api/auditoria.php
 * Descripción: Consulta de registros de auditoría (solo admin)
 */

require_once '../config/database.php';
require_once '../config/config.php';

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}

// Solo admin puede ver auditoría
if ($_SESSION['rol'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para acceder']);
    exit;
}

$action = $_GET['action'] ?? '';

// ============================================
// LISTAR AUDITORÍA
// ============================================
if ($action === 'listar') {
    
    try {
        // Construir query base
        $sql = "
            SELECT 
                a.id,
                a.accion,
                a.detalle,
                a.ip_address,
                a.user_agent,
                a.fecha,
                u.nombre_completo as usuario_nombre,
                u.rol as usuario_rol,
                u.dni as usuario_dni
            FROM auditoria a
            INNER JOIN usuarios u ON a.usuario_id = u.id
            WHERE 1=1
        ";
        
        $params = [];
        
        // Filtros opcionales
        if (!empty($_GET['usuario_id'])) {
            $sql .= " AND a.usuario_id = ?";
            $params[] = $_GET['usuario_id'];
        }
        
        if (!empty($_GET['accion'])) {
            $sql .= " AND a.accion = ?";
            $params[] = $_GET['accion'];
        }
        
        if (!empty($_GET['fecha_desde'])) {
            $sql .= " AND DATE(a.fecha) >= ?";
            $params[] = $_GET['fecha_desde'];
        }
        
        if (!empty($_GET['fecha_hasta'])) {
            $sql .= " AND DATE(a.fecha) <= ?";
            $params[] = $_GET['fecha_hasta'];
        }
        
        // Ordenar por fecha descendente (más recientes primero)
        $sql .= " ORDER BY a.fecha DESC, a.id DESC";
        
        // Limitar resultados (últimos 1000)
        $sql .= " LIMIT 1000";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $registros = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $registros
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener auditoría: ' . $e->getMessage(),
            'data' => []
        ]);
    }
}

// ============================================
// OBTENER ESTADÍSTICAS DE AUDITORÍA
// ============================================
elseif ($action === 'estadisticas') {
    
    try {
        // Total de registros
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM auditoria");
        $totalRegistros = $stmt->fetch()['total'];
        
        // Registros hoy
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM auditoria WHERE DATE(fecha) = CURDATE()");
        $registrosHoy = $stmt->fetch()['total'];
        
        // Registros esta semana
        $stmt = $pdo->query("
            SELECT COUNT(*) as total 
            FROM auditoria 
            WHERE YEARWEEK(fecha, 1) = YEARWEEK(CURDATE(), 1)
        ");
        $registrosSemana = $stmt->fetch()['total'];
        
        // Registros este mes
        $stmt = $pdo->query("
            SELECT COUNT(*) as total 
            FROM auditoria 
            WHERE YEAR(fecha) = YEAR(CURDATE()) AND MONTH(fecha) = MONTH(CURDATE())
        ");
        $registrosMes = $stmt->fetch()['total'];
        
        // Top 5 acciones más frecuentes
        $stmt = $pdo->query("
            SELECT accion, COUNT(*) as total 
            FROM auditoria 
            GROUP BY accion 
            ORDER BY total DESC 
            LIMIT 5
        ");
        $topAcciones = $stmt->fetchAll();
        
        // Top 5 usuarios más activos
        $stmt = $pdo->query("
            SELECT u.nombre_completo, COUNT(*) as total 
            FROM auditoria a
            INNER JOIN usuarios u ON a.usuario_id = u.id
            GROUP BY a.usuario_id, u.nombre_completo 
            ORDER BY total DESC 
            LIMIT 5
        ");
        $topUsuarios = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'estadisticas' => [
                'total_registros' => $totalRegistros,
                'registros_hoy' => $registrosHoy,
                'registros_semana' => $registrosSemana,
                'registros_mes' => $registrosMes,
                'top_acciones' => $topAcciones,
                'top_usuarios' => $topUsuarios
            ]
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener estadísticas'
        ]);
    }
}

// ============================================
// REGISTRAR NUEVA AUDITORÍA (USO INTERNO)
// ============================================
elseif ($action === 'registrar') {
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }
    
    $usuarioId = $_POST['usuario_id'] ?? $_SESSION['user_id'];
    $accion = $_POST['accion'] ?? '';
    $detalle = $_POST['detalle'] ?? null;
    
    if (empty($accion)) {
        echo json_encode(['success' => false, 'message' => 'Acción requerida']);
        exit;
    }
    
    try {
        // Obtener IP y User Agent
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $pdo->prepare("
            INSERT INTO auditoria (usuario_id, accion, detalle, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$usuarioId, $accion, $detalle, $ipAddress, $userAgent])) {
            echo json_encode([
                'success' => true,
                'message' => 'Registro de auditoría creado',
                'id' => $pdo->lastInsertId()
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al registrar']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
}

// ============================================
// LIMPIAR AUDITORÍA ANTIGUA (Solo Admin)
// ============================================
elseif ($action === 'limpiar_antiguos') {
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }
    
    // Días a conservar (por defecto 90 días)
    $dias = $_POST['dias'] ?? 90;
    
    try {
        $stmt = $pdo->prepare("
            DELETE FROM auditoria 
            WHERE fecha < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        
        $stmt->execute([$dias]);
        $eliminados = $stmt->rowCount();
        
        // Registrar esta acción
        $stmtAudit = $pdo->prepare("
            INSERT INTO auditoria (usuario_id, accion, detalle) 
            VALUES (?, 'limpiar_auditoria', ?)
        ");
        $stmtAudit->execute([
            $_SESSION['user_id'], 
            "Se eliminaron $eliminados registros anteriores a $dias días"
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => "Se eliminaron $eliminados registros antiguos",
            'eliminados' => $eliminados
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al limpiar registros']);
    }
}

// ============================================
// ACCIÓN NO VÁLIDA
// ============================================
else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}