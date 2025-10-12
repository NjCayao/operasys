<?php
/**
 * OperaSys - API de Reportes
 * Archivo: api/reportes.php
 * Descripción: CRUD de reportes diarios con soporte offline
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
// CREAR REPORTE
// ============================================
if ($action === 'crear') {
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }
    
    $equipoId = $_POST['equipo_id'] ?? 0;
    $fecha = $_POST['fecha'] ?? '';
    $horaInicio = $_POST['hora_inicio'] ?? '';
    $horaFin = $_POST['hora_fin'] ?? null;
    $actividad = trim($_POST['actividad'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    
    // Validaciones
    if (!$equipoId || empty($fecha) || empty($horaInicio) || empty($actividad)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos. Todos los campos obligatorios deben llenarse.']);
        exit;
    }
    
    // Validar que la fecha no sea futura
    if (strtotime($fecha) > strtotime(date('Y-m-d'))) {
        echo json_encode(['success' => false, 'message' => 'No puede crear reportes con fecha futura']);
        exit;
    }
    
    try {
        // Verificar que el equipo exista y esté activo
        $stmt = $pdo->prepare("SELECT id FROM equipos WHERE id = ? AND estado = 1");
        $stmt->execute([$equipoId]);
        
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Equipo no válido o inactivo']);
            exit;
        }
        
        // Calcular horas trabajadas si hay hora fin
        $horasTrabajadas = null;
        if ($horaFin) {
            $inicio = strtotime("$fecha $horaInicio");
            $fin = strtotime("$fecha $horaFin");
            
            if ($fin < $inicio) {
                echo json_encode(['success' => false, 'message' => 'La hora de fin debe ser posterior a la hora de inicio']);
                exit;
            }
            
            $horasTrabajadas = ($fin - $inicio) / 3600; // Convertir a horas
        }
        
        // Insertar reporte
        $stmt = $pdo->prepare("
            INSERT INTO reportes 
            (usuario_id, equipo_id, fecha, hora_inicio, hora_fin, horas_trabajadas, 
             actividad, observaciones, ubicacion, estado_sinc) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'sincronizado')
        ");
        
        if ($stmt->execute([
            $userId, 
            $equipoId, 
            $fecha, 
            $horaInicio, 
            $horaFin, 
            $horasTrabajadas, 
            $actividad, 
            $observaciones, 
            $ubicacion
        ])) {
            
            $reporteId = $pdo->lastInsertId();
            
            // Registrar en auditoría
            $stmtAudit = $pdo->prepare("
                INSERT INTO auditoria (usuario_id, accion, detalle) 
                VALUES (?, 'crear_reporte', ?)
            ");
            $stmtAudit->execute([$userId, "Reporte ID: $reporteId, Fecha: $fecha"]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Reporte guardado correctamente',
                'reporte_id' => $reporteId
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar reporte']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
    }
}

// ============================================
// LISTAR REPORTES (Del usuario actual)
// ============================================
elseif ($action === 'listar') {
    
    try {
        // Admin y supervisor ven todos, operador solo los suyos
        if ($userRol === 'admin' || $userRol === 'supervisor') {
            $sql = "
                SELECT 
                    r.id,
                    r.fecha,
                    r.hora_inicio,
                    r.hora_fin,
                    r.horas_trabajadas,
                    r.actividad,
                    r.observaciones,
                    r.ubicacion,
                    r.estado_sinc,
                    u.nombre_completo as operador,
                    e.codigo as equipo_codigo,
                    e.categoria as equipo_categoria
                FROM reportes r
                INNER JOIN usuarios u ON r.usuario_id = u.id
                INNER JOIN equipos e ON r.equipo_id = e.id
                ORDER BY r.fecha DESC, r.hora_inicio DESC
            ";
            $stmt = $pdo->query($sql);
        } else {
            $sql = "
                SELECT 
                    r.id,
                    r.fecha,
                    r.hora_inicio,
                    r.hora_fin,
                    r.horas_trabajadas,
                    r.actividad,
                    r.observaciones,
                    r.ubicacion,
                    r.estado_sinc,
                    e.codigo as equipo_codigo,
                    e.categoria as equipo_categoria
                FROM reportes r
                INNER JOIN equipos e ON r.equipo_id = e.id
                WHERE r.usuario_id = ?
                ORDER BY r.fecha DESC, r.hora_inicio DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
        }
        
        $reportes = $stmt->fetchAll();
        
        // Formato para DataTables
        $data = [];
        foreach ($reportes as $reporte) {
            
            $estadoBadge = $reporte['estado_sinc'] === 'sincronizado' 
                ? '<span class="badge badge-success"><i class="fas fa-check"></i> Sincronizado</span>' 
                : '<span class="badge badge-warning"><i class="fas fa-clock"></i> Pendiente</span>';
            
            $horasTexto = $reporte['horas_trabajadas'] 
                ? number_format($reporte['horas_trabajadas'], 1) . ' hrs'
                : '-';
            
            $actividadCorta = strlen($reporte['actividad']) > 50 
                ? substr($reporte['actividad'], 0, 50) . '...'
                : $reporte['actividad'];
            
            // Botones de acción
            $acciones = '<button onclick="verDetalleReporte(' . $reporte['id'] . ')" 
                class="btn btn-sm btn-info" title="Ver detalle">
                <i class="fas fa-eye"></i>
            </button> ';
            
            $acciones .= '<button onclick="descargarPDF(' . $reporte['id'] . ')" 
                class="btn btn-sm btn-danger" title="Descargar PDF">
                <i class="fas fa-file-pdf"></i>
            </button>';
            
            $equipoCompleto = $reporte['equipo_categoria'] . ' ' . $reporte['equipo_codigo'];
            
            $row = [
                $reporte['id'],
                date('d/m/Y', strtotime($reporte['fecha'])),
                $equipoCompleto,
                $reporte['hora_inicio'],
                $reporte['hora_fin'] ?? '-',
                $horasTexto,
                $actividadCorta,
                $estadoBadge,
                $acciones
            ];
            
            // Si es admin/supervisor, agregar operador
            if ($userRol === 'admin' || $userRol === 'supervisor') {
                array_splice($row, 2, 0, [$reporte['operador']]);
            }
            
            $data[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener reportes']);
    }
}

// ============================================
// OBTENER DETALLE DE UN REPORTE
// ============================================
elseif ($action === 'detalle') {
    
    $reporteId = $_GET['id'] ?? 0;
    
    if (!$reporteId) {
        echo json_encode(['success' => false, 'message' => 'ID no válido']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                r.*,
                u.nombre_completo as operador,
                u.cargo as operador_cargo,
                u.firma as operador_firma,
                e.codigo as equipo_codigo,
                e.categoria as equipo_categoria,
                e.descripcion as equipo_descripcion
            FROM reportes r
            INNER JOIN usuarios u ON r.usuario_id = u.id
            INNER JOIN equipos e ON r.equipo_id = e.id
            WHERE r.id = ?
        ");
        $stmt->execute([$reporteId]);
        $reporte = $stmt->fetch();
        
        if ($reporte) {
            // Verificar permisos: solo el dueño o admin/supervisor pueden ver
            if ($reporte['usuario_id'] != $userId && $userRol !== 'admin' && $userRol !== 'supervisor') {
                echo json_encode(['success' => false, 'message' => 'No tienes permisos para ver este reporte']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'reporte' => $reporte
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Reporte no encontrado']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener reporte']);
    }
}

// ============================================
// ESTADÍSTICAS DEL USUARIO
// ============================================
elseif ($action === 'estadisticas') {
    
    try {
        // Total de reportes
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reportes WHERE usuario_id = ?");
        $stmt->execute([$userId]);
        $totalReportes = $stmt->fetch()['total'];
        
        // Reportes hoy
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reportes WHERE usuario_id = ? AND fecha = CURDATE()");
        $stmt->execute([$userId]);
        $reportesHoy = $stmt->fetch()['total'];
        
        // Horas trabajadas este mes
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(horas_trabajadas), 0) as total 
            FROM reportes 
            WHERE usuario_id = ? 
            AND MONTH(fecha) = MONTH(CURDATE()) 
            AND YEAR(fecha) = YEAR(CURDATE())
        ");
        $stmt->execute([$userId]);
        $horasMes = $stmt->fetch()['total'];
        
        // Pendientes de sincronizar
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reportes WHERE usuario_id = ? AND estado_sinc = 'pendiente'");
        $stmt->execute([$userId]);
        $pendientesSinc = $stmt->fetch()['total'];
        
        echo json_encode([
            'success' => true,
            'estadisticas' => [
                'total_reportes' => $totalReportes,
                'reportes_hoy' => $reportesHoy,
                'horas_mes' => round($horasMes, 1),
                'pendientes_sinc' => $pendientesSinc
            ]
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener estadísticas']);
    }
}

// ============================================
// LISTAR TODOS CON FILTROS (Admin/Supervisor)
// ============================================
elseif ($action === 'listar_todos') {
    
    // Verificar permisos
    if ($userRol !== 'admin' && $userRol !== 'supervisor') {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos']);
        exit;
    }
    
    try {
        $where = "1=1";
        $params = [];
        
        // Filtro por operador
        if (!empty($_GET['operador_id'])) {
            $where .= " AND r.usuario_id = :operador_id";
            $params[':operador_id'] = $_GET['operador_id'];
        }
        
        // Filtro por categoría de equipo
        if (!empty($_GET['categoria'])) {
            $where .= " AND e.categoria = :categoria";
            $params[':categoria'] = $_GET['categoria'];
        }
        
        // Filtro por fecha desde
        if (!empty($_GET['fecha_desde'])) {
            $where .= " AND r.fecha >= :fecha_desde";
            $params[':fecha_desde'] = $_GET['fecha_desde'];
        }
        
        // Filtro por fecha hasta
        if (!empty($_GET['fecha_hasta'])) {
            $where .= " AND r.fecha <= :fecha_hasta";
            $params[':fecha_hasta'] = $_GET['fecha_hasta'];
        }
        
        $sql = "
            SELECT 
                r.id,
                r.fecha,
                r.hora_inicio,
                r.hora_fin,
                r.horas_trabajadas,
                r.actividad,
                r.observaciones,
                r.ubicacion,
                r.estado_sinc,
                u.nombre_completo as operador,
                CONCAT(e.categoria, ' - ', e.codigo) as equipo
            FROM reportes r
            INNER JOIN usuarios u ON r.usuario_id = u.id
            INNER JOIN equipos e ON r.equipo_id = e.id
            WHERE $where
            ORDER BY r.fecha DESC, r.hora_inicio DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $reportes = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $reportes,
            'total' => count($reportes)
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener reportes']);
    }
}

// ============================================
// ACCIÓN NO VÁLIDA
// ============================================
else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
