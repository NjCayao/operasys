<?php
/**
 * OperaSys - API de Reportes V3.0
 * Archivo: api/reportes.php
 * Sistema HT/HP con horómetros y combustible (SIN partidas)
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
// CREAR REPORTE
// ============================================
if ($action === 'crear') {

    $equipoId = $_POST['equipo_id'] ?? 0;
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $horometroInicial = $_POST['horometro_inicial'] ?? 0;

    if (!$equipoId) {
        echo json_encode(['success' => false, 'message' => 'Seleccione un equipo']);
        exit;
    }
    
    if ($horometroInicial <= 0) {
        echo json_encode(['success' => false, 'message' => 'Horómetro inicial es obligatorio']);
        exit;
    }

    try {
        // Verificar equipo activo
        $stmt = $pdo->prepare("SELECT id FROM equipos WHERE id = ? AND estado = 1");
        $stmt->execute([$equipoId]);

        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Equipo no válido']);
            exit;
        }

        // Verificar duplicado
        $stmt = $pdo->prepare("SELECT id FROM reportes WHERE usuario_id = ? AND equipo_id = ? AND fecha = ?");
        $stmt->execute([$userId, $equipoId, $fecha]);

        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ya existe un reporte para este equipo hoy']);
            exit;
        }

        // Crear reporte
        $stmt = $pdo->prepare("
            INSERT INTO reportes (usuario_id, equipo_id, fecha, horometro_inicial, estado) 
            VALUES (?, ?, ?, ?, 'borrador')
        ");

        if ($stmt->execute([$userId, $equipoId, $fecha, $horometroInicial])) {
            $reporteId = $pdo->lastInsertId();

            $stmtAudit = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion, detalle) VALUES (?, ?, ?)");
            $stmtAudit->execute([$userId, 'crear_reporte', "Reporte ID: $reporteId"]);

            echo json_encode(['success' => true, 'message' => 'Reporte creado', 'reporte_id' => $reporteId]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// ============================================
// ACTUALIZAR HORÓMETRO FINAL
// ============================================
elseif ($action === 'actualizar_horometro') {
    
    $reporteId = $_POST['reporte_id'] ?? 0;
    $horometroFinal = $_POST['horometro_final'] ?? 0;
    
    if (!$reporteId || !$horometroFinal) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT horometro_inicial, usuario_id, estado FROM reportes WHERE id = ?");
        $stmt->execute([$reporteId]);
        $reporte = $stmt->fetch();
        
        if (!$reporte) {
            echo json_encode(['success' => false, 'message' => 'Reporte no encontrado']);
            exit;
        }
        
        if ($horometroFinal <= $reporte['horometro_inicial']) {
            echo json_encode(['success' => false, 'message' => 'Horómetro final debe ser mayor al inicial']);
            exit;
        }
        
        $horasMotor = $horometroFinal - $reporte['horometro_inicial'];
        
        if ($horasMotor > 24) {
            echo json_encode(['success' => false, 'message' => 'Las horas motor no pueden superar 24 horas']);
            exit;
        }
        
        if ($reporte['usuario_id'] != $userId && $userRol !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Sin permisos']);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE reportes SET horometro_final = ? WHERE id = ?");
        
        if ($stmt->execute([$horometroFinal, $reporteId])) {
            echo json_encode([
                'success' => true, 
                'message' => 'Horómetro actualizado',
                'horas_motor' => round($horasMotor, 2)
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
}

// ============================================
// AGREGAR COMBUSTIBLE
// ============================================
elseif ($action === 'agregar_combustible') {

    $reporteId = $_POST['reporte_id'] ?? 0;
    $horometro = $_POST['horometro'] ?? 0;
    $horaAbastecimiento = $_POST['hora_abastecimiento'] ?? date('H:i');
    $galones = $_POST['galones'] ?? 0;
    $observaciones = trim($_POST['observaciones'] ?? '');

    if (!$reporteId || !$galones) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }

    if ($galones <= 0) {
        echo json_encode(['success' => false, 'message' => 'Galones debe ser mayor a 0']);
        exit;
    }

    try {
        // Verificar permisos y obtener capacidad tanque
        $stmt = $pdo->prepare("
            SELECT r.usuario_id, r.estado, r.horometro_inicial, r.horometro_final, e.capacidad_tanque
            FROM reportes r
            INNER JOIN equipos e ON r.equipo_id = e.id
            WHERE r.id = ?
        ");
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
        
        // Validar horómetro dentro del rango
        if ($horometro < $reporte['horometro_inicial'] || ($reporte['horometro_final'] > 0 && $horometro > $reporte['horometro_final'])) {
            echo json_encode(['success' => false, 'message' => 'Horómetro fuera del rango del reporte']);
            exit;
        }
        
        // Validar que no exceda capacidad del tanque
        if ($galones > $reporte['capacidad_tanque']) {
            echo json_encode(['success' => false, 'message' => 'Galones excede capacidad del tanque (' . $reporte['capacidad_tanque'] . ' gal)']);
            exit;
        }

        // Insertar combustible
        $stmt = $pdo->prepare("
            INSERT INTO reportes_combustible (reporte_id, horometro, hora_abastecimiento, galones, observaciones) 
            VALUES (?, ?, ?, ?, ?)
        ");

        if ($stmt->execute([$reporteId, $horometro, $horaAbastecimiento, $galones, $observaciones])) {
            echo json_encode(['success' => true, 'message' => 'Abastecimiento registrado', 'id' => $pdo->lastInsertId()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al registrar']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
}

// ============================================
// ELIMINAR COMBUSTIBLE
// ============================================
elseif ($action === 'eliminar_combustible') {

    $id = $_POST['id'] ?? 0;

    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID no válido']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT rc.id, r.usuario_id, r.estado 
            FROM reportes_combustible rc
            INNER JOIN reportes r ON rc.reporte_id = r.id
            WHERE rc.id = ?
        ");
        $stmt->execute([$id]);
        $combustible = $stmt->fetch();

        if (!$combustible) {
            echo json_encode(['success' => false, 'message' => 'Registro no encontrado']);
            exit;
        }

        if ($combustible['estado'] === 'finalizado' && $userRol !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'No puede eliminar de un reporte finalizado']);
            exit;
        }

        if ($combustible['usuario_id'] != $userId && $userRol !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Sin permisos']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM reportes_combustible WHERE id = ?");

        if ($stmt->execute([$id])) {
            echo json_encode(['success' => true, 'message' => 'Registro eliminado']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
}

// ============================================
// FINALIZAR REPORTE
// ============================================
elseif ($action === 'finalizar') {

    $reporteId = $_POST['reporte_id'] ?? 0;
    $observacionesGenerales = trim($_POST['observaciones_generales'] ?? '');

    if (!$reporteId) {
        echo json_encode(['success' => false, 'message' => 'ID no válido']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT usuario_id, estado, horometro_final FROM reportes WHERE id = ?");
        $stmt->execute([$reporteId]);
        $reporte = $stmt->fetch();

        if (!$reporte) {
            echo json_encode(['success' => false, 'message' => 'Reporte no encontrado']);
            exit;
        }

        if ($reporte['usuario_id'] != $userId && $userRol !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Sin permisos']);
            exit;
        }

        if ($reporte['estado'] === 'finalizado') {
            echo json_encode(['success' => false, 'message' => 'El reporte ya está finalizado']);
            exit;
        }
        
        if ($reporte['horometro_final'] == 0) {
            echo json_encode(['success' => false, 'message' => 'Debe ingresar el horómetro final antes de finalizar']);
            exit;
        }

        // Verificar que tenga actividades
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reportes_detalle WHERE reporte_id = ?");
        $stmt->execute([$reporteId]);

        if ($stmt->fetch()['total'] == 0) {
            echo json_encode(['success' => false, 'message' => 'Debe agregar al menos una actividad (HT o HP)']);
            exit;
        }

        // Finalizar
        $stmt = $pdo->prepare("
            UPDATE reportes 
            SET estado = 'finalizado', fecha_finalizacion = NOW(), observaciones_generales = ?
            WHERE id = ?
        ");

        if ($stmt->execute([$observacionesGenerales, $reporteId])) {
            $stmtAudit = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion, detalle) VALUES (?, ?, ?)");
            $stmtAudit->execute([$userId, 'finalizar_reporte', "Reporte ID: $reporteId"]);

            echo json_encode(['success' => true, 'message' => 'Reporte finalizado']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al finalizar']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
}

// ============================================
// LISTAR REPORTES
// ============================================
elseif ($action === 'listar') {
    
    try {
        if ($userRol === 'admin' || $userRol === 'supervisor') {
            $sql = "
                SELECT 
                    r.id, r.fecha, r.estado, r.horas_motor,
                    u.nombre_completo as operador,
                    CONCAT(e.categoria, ' - ', e.codigo) as equipo,
                    COUNT(DISTINCT CASE WHEN rd.tipo_hora = 'HT' THEN rd.id END) as total_ht,
                    COUNT(DISTINCT CASE WHEN rd.tipo_hora = 'HP' THEN rd.id END) as total_hp,
                    COALESCE(SUM(CASE WHEN rd.tipo_hora = 'HT' THEN rd.horas_transcurridas ELSE 0 END), 0) as horas_ht,
                    COALESCE(SUM(CASE WHEN rd.tipo_hora = 'HP' THEN rd.horas_transcurridas ELSE 0 END), 0) as horas_hp,
                    r.total_abastecido,
                    e.consumo_promedio_hr * r.horas_motor as consumo_estimado
                FROM reportes r
                INNER JOIN usuarios u ON r.usuario_id = u.id
                INNER JOIN equipos e ON r.equipo_id = e.id
                LEFT JOIN reportes_detalle rd ON r.id = rd.reporte_id
                GROUP BY r.id
                ORDER BY r.fecha DESC, r.id DESC
            ";
            $stmt = $pdo->query($sql);
        } else {
            $sql = "
                SELECT 
                    r.id, r.fecha, r.estado, r.horas_motor,
                    CONCAT(e.categoria, ' - ', e.codigo) as equipo,
                    COUNT(DISTINCT CASE WHEN rd.tipo_hora = 'HT' THEN rd.id END) as total_ht,
                    COUNT(DISTINCT CASE WHEN rd.tipo_hora = 'HP' THEN rd.id END) as total_hp,
                    COALESCE(SUM(CASE WHEN rd.tipo_hora = 'HT' THEN rd.horas_transcurridas ELSE 0 END), 0) as horas_ht,
                    COALESCE(SUM(CASE WHEN rd.tipo_hora = 'HP' THEN rd.horas_transcurridas ELSE 0 END), 0) as horas_hp,
                    r.total_abastecido,
                    e.consumo_promedio_hr * r.horas_motor as consumo_estimado
                FROM reportes r
                INNER JOIN equipos e ON r.equipo_id = e.id
                LEFT JOIN reportes_detalle rd ON r.id = rd.reporte_id
                WHERE r.usuario_id = ?
                GROUP BY r.id
                ORDER BY r.fecha DESC, r.id DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
        }
        
        $reportes = $stmt->fetchAll();
        
        $data = [];
        foreach ($reportes as $rep) {
            
            $estadoBadge = $rep['estado'] === 'finalizado'
                ? '<span class="badge badge-success"><i class="fas fa-check"></i> Finalizado</span>' 
                : '<span class="badge badge-warning"><i class="fas fa-edit"></i> Borrador</span>';
            
            $eficiencia = $rep['horas_motor'] > 0 
                ? round(($rep['horas_ht'] / $rep['horas_motor']) * 100, 1) . '%'
                : '-';
            
            $acciones = '';
            
            if ($rep['estado'] === 'borrador') {
                if ($userRol !== 'supervisor') {
                    $acciones .= '<a href="editar.php?id=' . $rep['id'] . '" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i></a> ';
                }
            } else {
                if ($userRol === 'admin') {
                    $acciones .= '<a href="editar.php?id=' . $rep['id'] . '" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i></a> ';
                }
            }
            
            $acciones .= '<a href="ver.php?id=' . $rep['id'] . '" class="btn btn-sm btn-info">
                <i class="fas fa-eye"></i></a> ';
            
            $row = [
                $rep['id'],
                date('d/m/Y', strtotime($rep['fecha'])),
                $rep['equipo'],
                number_format($rep['horas_motor'] ?? 0, 1) . ' hrs',
                number_format($rep['horas_ht'], 1) . ' hrs',
                number_format($rep['horas_hp'], 1) . ' hrs',
                $eficiencia,
                $estadoBadge,
                $acciones
            ];
            
            if ($userRol === 'admin' || $userRol === 'supervisor') {
                array_splice($row, 2, 0, [$rep['operador']]);
            }
            
            $data[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener reportes', 'data' => []], JSON_UNESCAPED_UNICODE);
    }
}

// ============================================
// OBTENER REPORTE COMPLETO
// ============================================
elseif ($action === 'obtener') {

    $reporteId = $_GET['id'] ?? 0;

    if (!$reporteId) {
        echo json_encode(['success' => false, 'message' => 'ID no válido']);
        exit;
    }

    try {
        // Cabecera
        $stmt = $pdo->prepare("
            SELECT 
                r.*,
                u.nombre_completo as operador,
                e.codigo as equipo_codigo,
                e.categoria as equipo_categoria,
                e.consumo_promedio_hr,
                e.capacidad_tanque
            FROM reportes r
            INNER JOIN usuarios u ON r.usuario_id = u.id
            INNER JOIN equipos e ON r.equipo_id = e.id
            WHERE r.id = ?
        ");
        $stmt->execute([$reporteId]);
        $reporte = $stmt->fetch();

        if (!$reporte) {
            echo json_encode(['success' => false, 'message' => 'Reporte no encontrado']);
            exit;
        }

        // Calcular consumo estimado
        $consumoEstimado = $reporte['horas_motor'] * $reporte['consumo_promedio_hr'];
        $diferenciaCombustible = $reporte['total_abastecido'] - $consumoEstimado;

        echo json_encode([
            'success' => true,
            'reporte' => $reporte,
            'consumo_estimado' => round($consumoEstimado, 2),
            'diferencia_combustible' => round($diferenciaCombustible, 2)
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener reporte']);
    }
}

// ============================================
// ELIMINAR REPORTE (Solo borradores vacíos)
// ============================================
elseif ($action === 'eliminar') {

    $reporteId = $_POST['reporte_id'] ?? 0;

    if (!$reporteId) {
        echo json_encode(['success' => false, 'message' => 'ID no válido']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT usuario_id, estado FROM reportes WHERE id = ?");
        $stmt->execute([$reporteId]);
        $reporte = $stmt->fetch();

        if (!$reporte) {
            echo json_encode(['success' => false, 'message' => 'Reporte no encontrado']);
            exit;
        }

        if ($reporte['usuario_id'] != $userId && $userRol !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Sin permisos']);
            exit;
        }

        if ($reporte['estado'] === 'finalizado' && $userRol !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'No se puede eliminar un reporte finalizado']);
            exit;
        }

        // Verificar que no tenga actividades
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reportes_detalle WHERE reporte_id = ?");
        $stmt->execute([$reporteId]);

        if ($stmt->fetch()['total'] > 0) {
            echo json_encode(['success' => false, 'message' => 'No se puede eliminar un reporte con actividades']);
            exit;
        }

        // Eliminar combustible
        $stmt = $pdo->prepare("DELETE FROM reportes_combustible WHERE reporte_id = ?");
        $stmt->execute([$reporteId]);

        // Eliminar reporte
        $stmt = $pdo->prepare("DELETE FROM reportes WHERE id = ?");

        if ($stmt->execute([$reporteId])) {
            $stmtAudit = $pdo->prepare("INSERT INTO auditoria (usuario_id, accion, detalle) VALUES (?, ?, ?)");
            $stmtAudit->execute([$userId, 'eliminar_reporte', "Reporte ID: $reporteId (sin actividades)"]);

            echo json_encode(['success' => true, 'message' => 'Reporte eliminado']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}