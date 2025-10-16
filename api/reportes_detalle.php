<?php
/**
 * OperaSys - API de Reportes Detalle (HT/HP)
 * Archivo: api/reportes_detalle.php
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
// AGREGAR ACTIVIDAD (HT o HP)
// ============================================
if ($action === 'agregar') {
    
    $reporteId = $_POST['reporte_id'] ?? 0;
    $tipoHora = $_POST['tipo_hora'] ?? ''; // 'HT' o 'HP'
    $horaInicio = $_POST['hora_inicio'] ?? '';
    $horaFin = $_POST['hora_fin'] ?? '';
    $observaciones = trim($_POST['observaciones'] ?? '');
    
    // Campos para HT
    $actividadHtId = $_POST['actividad_ht_id'] ?? null;
    $partidaId = $_POST['partida_id'] ?? null;
    
    // Campos para HP
    $motivoHpId = $_POST['motivo_hp_id'] ?? null;
    
    // Validaciones básicas
    if (!$reporteId || !$tipoHora || !$horaInicio || !$horaFin) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }
    
    if (!in_array($tipoHora, ['HT', 'HP'])) {
        echo json_encode(['success' => false, 'message' => 'Tipo de hora no válido']);
        exit;
    }
    
    // Validar según tipo
    if ($tipoHora === 'HT' && (!$actividadHtId || !$partidaId)) {
        echo json_encode(['success' => false, 'message' => 'HT requiere actividad y partida']);
        exit;
    }
    
    if ($tipoHora === 'HP' && !$motivoHpId) {
        echo json_encode(['success' => false, 'message' => 'HP requiere motivo de parada']);
        exit;
    }
    
    try {
        // Verificar permisos sobre el reporte
        $stmt = $pdo->prepare("SELECT usuario_id, estado FROM reportes WHERE id = ?");
        $stmt->execute([$reporteId]);
        $reporte = $stmt->fetch();
        
        if (!$reporte) {
            echo json_encode(['success' => false, 'message' => 'Reporte no encontrado']);
            exit;
        }
        
        if ($reporte['estado'] === 'finalizado' && $userRol !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'No puede editar un reporte finalizado']);
            exit;
        }
        
        if ($reporte['usuario_id'] != $userId && $userRol !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Sin permisos']);
            exit;
        }
        
        // Obtener el orden
        $stmt = $pdo->prepare("SELECT COALESCE(MAX(orden), 0) + 1 as nuevo_orden FROM reportes_detalle WHERE reporte_id = ?");
        $stmt->execute([$reporteId]);
        $orden = $stmt->fetch()['nuevo_orden'];
        
        // Insertar actividad
        $stmt = $pdo->prepare("
            INSERT INTO reportes_detalle 
            (reporte_id, tipo_hora, hora_inicio, hora_fin, actividad_ht_id, partida_id, motivo_hp_id, observaciones, orden) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$reporteId, $tipoHora, $horaInicio, $horaFin, $actividadHtId, $partidaId, $motivoHpId, $observaciones, $orden])) {
            echo json_encode([
                'success' => true, 
                'message' => 'Actividad agregada',
                'id' => $pdo->lastInsertId()
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al agregar']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// ============================================
// LISTAR ACTIVIDADES DE UN REPORTE
// ============================================
elseif ($action === 'listar') {
    
    $reporteId = $_GET['reporte_id'] ?? 0;
    
    if (!$reporteId) {
        echo json_encode(['success' => false, 'message' => 'ID de reporte no válido']);
        exit;
    }
    
    try {
        // Obtener HT
        $stmtHT = $pdo->prepare("
            SELECT 
                rd.*,
                aht.nombre as actividad_nombre,
                p.codigo as partida_codigo,
                p.descripcion as partida_descripcion
            FROM reportes_detalle rd
            LEFT JOIN actividades_ht aht ON rd.actividad_ht_id = aht.id
            LEFT JOIN partidas p ON rd.partida_id = p.id
            WHERE rd.reporte_id = ? AND rd.tipo_hora = 'HT'
            ORDER BY rd.orden ASC
        ");
        $stmtHT->execute([$reporteId]);
        $actividadesHT = $stmtHT->fetchAll();
        
        // Obtener HP
        $stmtHP = $pdo->prepare("
            SELECT 
                rd.*,
                mhp.nombre as motivo_nombre,
                mhp.categoria_parada
            FROM reportes_detalle rd
            LEFT JOIN motivos_hp mhp ON rd.motivo_hp_id = mhp.id
            WHERE rd.reporte_id = ? AND rd.tipo_hora = 'HP'
            ORDER BY rd.orden ASC
        ");
        $stmtHP->execute([$reporteId]);
        $actividadesHP = $stmtHP->fetchAll();
        
        // Calcular totales
        $totalHT = array_sum(array_column($actividadesHT, 'horas_transcurridas'));
        $totalHP = array_sum(array_column($actividadesHP, 'horas_transcurridas'));
        
        echo json_encode([
            'success' => true,
            'ht' => $actividadesHT,
            'hp' => $actividadesHP,
            'totales' => [
                'total_ht' => round($totalHT, 2),
                'total_hp' => round($totalHP, 2),
                'total_registrado' => round($totalHT + $totalHP, 2)
            ]
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener actividades']);
    }
}

// ============================================
// ELIMINAR ACTIVIDAD
// ============================================
elseif ($action === 'eliminar') {
    
    $id = $_POST['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID no válido']);
        exit;
    }
    
    try {
        // Verificar permisos
        $stmt = $pdo->prepare("
            SELECT rd.id, r.usuario_id, r.estado 
            FROM reportes_detalle rd
            INNER JOIN reportes r ON rd.reporte_id = r.id
            WHERE rd.id = ?
        ");
        $stmt->execute([$id]);
        $actividad = $stmt->fetch();
        
        if (!$actividad) {
            echo json_encode(['success' => false, 'message' => 'Actividad no encontrada']);
            exit;
        }
        
        if ($actividad['estado'] === 'finalizado' && $userRol !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'No puede eliminar de un reporte finalizado']);
            exit;
        }
        
        if ($actividad['usuario_id'] != $userId && $userRol !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Sin permisos']);
            exit;
        }
        
        // Eliminar
        $stmt = $pdo->prepare("DELETE FROM reportes_detalle WHERE id = ?");
        
        if ($stmt->execute([$id])) {
            echo json_encode(['success' => true, 'message' => 'Actividad eliminada']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
}

// ============================================
// ACTUALIZAR ACTIVIDAD
// ============================================
elseif ($action === 'actualizar') {
    
    $id = $_POST['id'] ?? 0;
    $horaInicio = $_POST['hora_inicio'] ?? '';
    $horaFin = $_POST['hora_fin'] ?? '';
    $observaciones = trim($_POST['observaciones'] ?? '');
    
    // Campos opcionales según tipo
    $actividadHtId = $_POST['actividad_ht_id'] ?? null;
    $partidaId = $_POST['partida_id'] ?? null;
    $motivoHpId = $_POST['motivo_hp_id'] ?? null;
    
    if (!$id || !$horaInicio || !$horaFin) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }
    
    try {
        // Verificar permisos
        $stmt = $pdo->prepare("
            SELECT rd.tipo_hora, r.usuario_id, r.estado 
            FROM reportes_detalle rd
            INNER JOIN reportes r ON rd.reporte_id = r.id
            WHERE rd.id = ?
        ");
        $stmt->execute([$id]);
        $actividad = $stmt->fetch();
        
        if (!$actividad) {
            echo json_encode(['success' => false, 'message' => 'Actividad no encontrada']);
            exit;
        }
        
        if ($actividad['estado'] === 'finalizado' && $userRol !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'No puede editar un reporte finalizado']);
            exit;
        }
        
        if ($actividad['usuario_id'] != $userId && $userRol !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Sin permisos']);
            exit;
        }
        
        // Actualizar según tipo
        if ($actividad['tipo_hora'] === 'HT') {
            $stmt = $pdo->prepare("
                UPDATE reportes_detalle 
                SET hora_inicio = ?, hora_fin = ?, actividad_ht_id = ?, partida_id = ?, observaciones = ?
                WHERE id = ?
            ");
            $stmt->execute([$horaInicio, $horaFin, $actividadHtId, $partidaId, $observaciones, $id]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE reportes_detalle 
                SET hora_inicio = ?, hora_fin = ?, motivo_hp_id = ?, observaciones = ?
                WHERE id = ?
            ");
            $stmt->execute([$horaInicio, $horaFin, $motivoHpId, $observaciones, $id]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Actividad actualizada']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}