<?php

/**
 * OperaSys - API de Reportes
 * Archivo: api/reportes.php
 * Descripción: CRUD completo de reportes con actividades y combustible
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
// CREAR REPORTE (CABECERA)
// ============================================
if ($action === 'crear') {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $equipoId = $_POST['equipo_id'] ?? 0;
    $fecha = $_POST['fecha'] ?? date('Y-m-d'); // Por defecto hoy

    // Validar equipo
    if (!$equipoId) {
        echo json_encode(['success' => false, 'message' => 'Debe seleccionar un equipo']);
        exit;
    }

    // Validar que el equipo exista y esté activo
    try {
        $stmt = $pdo->prepare("SELECT id FROM equipos WHERE id = ? AND estado = 1");
        $stmt->execute([$equipoId]);

        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Equipo no válido o inactivo']);
            exit;
        }

        // Verificar que no exista ya un reporte para este equipo en esta fecha
        $stmt = $pdo->prepare("
            SELECT id FROM reportes 
            WHERE usuario_id = ? AND equipo_id = ? AND fecha = ?
        ");
        $stmt->execute([$userId, $equipoId, $fecha]);

        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ya existe un reporte para este equipo en esta fecha']);
            exit;
        }

        // Crear reporte
        $stmt = $pdo->prepare("
            INSERT INTO reportes (usuario_id, equipo_id, fecha, estado) 
            VALUES (?, ?, ?, 'borrador')
        ");

        if ($stmt->execute([$userId, $equipoId, $fecha])) {
            $reporteId = $pdo->lastInsertId();

            // Registrar en auditoría
            $stmtAudit = $pdo->prepare("
                INSERT INTO auditoria (usuario_id, accion, detalle) 
                VALUES (?, 'crear_reporte', ?)
            ");
            $stmtAudit->execute([$userId, "Reporte ID: $reporteId creado"]);

            echo json_encode([
                'success' => true,
                'message' => 'Reporte creado correctamente',
                'reporte_id' => $reporteId
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear reporte']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
    }
}

// ============================================
// AGREGAR ACTIVIDAD AL REPORTE
// ============================================
elseif ($action === 'agregar_actividad') {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $reporteId = $_POST['reporte_id'] ?? 0;
    $tipoTrabajoId = $_POST['tipo_trabajo_id'] ?? 0;
    $faseCostoId = $_POST['fase_costo_id'] ?? 0;
    $horometroInicial = $_POST['horometro_inicial'] ?? 0;
    $horometroFinal = $_POST['horometro_final'] ?? 0;
    $observaciones = trim($_POST['observaciones'] ?? '');

    // Validaciones
    if (!$reporteId || !$tipoTrabajoId || !$faseCostoId) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }

    if ($horometroFinal <= $horometroInicial) {
        echo json_encode(['success' => false, 'message' => 'Horómetro final debe ser mayor al inicial']);
        exit;
    }

    try {
        // Verificar que el reporte existe y está en borrador
        $stmt = $pdo->prepare("
            SELECT id, usuario_id, estado FROM reportes WHERE id = ?
        ");
        $stmt->execute([$reporteId]);
        $reporte = $stmt->fetch();

        if (!$reporte) {
            echo json_encode(['success' => false, 'message' => 'Reporte no encontrado']);
            exit;
        }

        // Solo admin puede editar reportes finalizados
        if ($reporte['estado'] === 'finalizado' && $userRol !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'No puede editar un reporte finalizado']);
            exit;
        }

        // Solo el dueño o admin puede agregar actividades
        if ($reporte['usuario_id'] != $userId && $userRol !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'No tiene permisos para editar este reporte']);
            exit;
        }

        // Obtener el último orden
        $stmt = $pdo->prepare("
            SELECT COALESCE(MAX(orden), 0) + 1 as nuevo_orden 
            FROM reportes_detalle 
            WHERE reporte_id = ?
        ");
        $stmt->execute([$reporteId]);
        $orden = $stmt->fetch()['nuevo_orden'];

        // Insertar actividad
        $stmt = $pdo->prepare("
            INSERT INTO reportes_detalle 
            (reporte_id, tipo_trabajo_id, fase_costo_id, horometro_inicial, 
             horometro_final, observaciones, orden) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        if ($stmt->execute([
            $reporteId,
            $tipoTrabajoId,
            $faseCostoId,
            $horometroInicial,
            $horometroFinal,
            $observaciones,
            $orden
        ])) {
            echo json_encode([
                'success' => true,
                'message' => 'Actividad agregada correctamente',
                'actividad_id' => $pdo->lastInsertId()
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al agregar actividad']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
    }
}

// ============================================
// ELIMINAR ACTIVIDAD
// ============================================
elseif ($action === 'eliminar_actividad') {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $actividadId = $_POST['actividad_id'] ?? 0;

    if (!$actividadId) {
        echo json_encode(['success' => false, 'message' => 'ID de actividad no válido']);
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
        $stmt->execute([$actividadId]);
        $actividad = $stmt->fetch();

        if (!$actividad) {
            echo json_encode(['success' => false, 'message' => 'Actividad no encontrada']);
            exit;
        }

        // Solo admin puede eliminar de reportes finalizados
        if ($actividad['estado'] === 'finalizado' && $userRol !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'No puede eliminar de un reporte finalizado']);
            exit;
        }

        // Solo el dueño o admin pueden eliminar
        if ($actividad['usuario_id'] != $userId && $userRol !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'No tiene permisos']);
            exit;
        }

        // Eliminar actividad
        $stmt = $pdo->prepare("DELETE FROM reportes_detalle WHERE id = ?");

        if ($stmt->execute([$actividadId])) {
            echo json_encode([
                'success' => true,
                'message' => 'Actividad eliminada correctamente'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar actividad']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
}

// ============================================
// AGREGAR ABASTECIMIENTO DE COMBUSTIBLE
// ============================================
elseif ($action === 'agregar_combustible') {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $reporteId = $_POST['reporte_id'] ?? 0;
    $horometro = $_POST['horometro'] ?? 0;
    $galones = $_POST['galones'] ?? 0;
    $observaciones = trim($_POST['observaciones'] ?? '');

    // Validaciones
    if (!$reporteId || !$horometro || !$galones) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }

    if ($galones <= 0) {
        echo json_encode(['success' => false, 'message' => 'Galones debe ser mayor a 0']);
        exit;
    }

    try {
        // Verificar permisos (igual que actividad)
        $stmt = $pdo->prepare("
            SELECT id, usuario_id, estado FROM reportes WHERE id = ?
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
            echo json_encode(['success' => false, 'message' => 'No tiene permisos']);
            exit;
        }

        // Insertar combustible
        $stmt = $pdo->prepare("
            INSERT INTO reportes_combustible 
            (reporte_id, horometro, galones, observaciones) 
            VALUES (?, ?, ?, ?)
        ");

        if ($stmt->execute([$reporteId, $horometro, $galones, $observaciones])) {
            echo json_encode([
                'success' => true,
                'message' => 'Abastecimiento registrado correctamente',
                'combustible_id' => $pdo->lastInsertId()
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al registrar combustible']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
}

// ============================================
// ELIMINAR COMBUSTIBLE
// ============================================
elseif ($action === 'eliminar_combustible') {

    $combustibleId = $_POST['combustible_id'] ?? 0;

    if (!$combustibleId) {
        echo json_encode(['success' => false, 'message' => 'ID no válido']);
        exit;
    }

    try {
        // Verificar permisos
        $stmt = $pdo->prepare("
            SELECT rc.id, r.usuario_id, r.estado 
            FROM reportes_combustible rc
            INNER JOIN reportes r ON rc.reporte_id = r.id
            WHERE rc.id = ?
        ");
        $stmt->execute([$combustibleId]);
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
            echo json_encode(['success' => false, 'message' => 'No tiene permisos']);
            exit;
        }

        // Eliminar
        $stmt = $pdo->prepare("DELETE FROM reportes_combustible WHERE id = ?");

        if ($stmt->execute([$combustibleId])) {
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
        // Verificar que el reporte existe y es del usuario
        $stmt = $pdo->prepare("
            SELECT id, usuario_id, estado FROM reportes WHERE id = ?
        ");
        $stmt->execute([$reporteId]);
        $reporte = $stmt->fetch();

        if (!$reporte) {
            echo json_encode(['success' => false, 'message' => 'Reporte no encontrado']);
            exit;
        }

        if ($reporte['usuario_id'] != $userId && $userRol !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'No tiene permisos']);
            exit;
        }

        if ($reporte['estado'] === 'finalizado') {
            echo json_encode(['success' => false, 'message' => 'El reporte ya está finalizado']);
            exit;
        }

        // Verificar que tenga al menos una actividad
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total FROM reportes_detalle WHERE reporte_id = ?
        ");
        $stmt->execute([$reporteId]);

        if ($stmt->fetch()['total'] == 0) {
            echo json_encode(['success' => false, 'message' => 'Debe agregar al menos una actividad antes de finalizar']);
            exit;
        }

        // Finalizar reporte
        $stmt = $pdo->prepare("
            UPDATE reportes 
            SET estado = 'finalizado', 
                fecha_finalizacion = NOW(),
                observaciones_generales = ?
            WHERE id = ?
        ");

        if ($stmt->execute([$observacionesGenerales, $reporteId])) {
            // Registrar en auditoría
            $stmtAudit = $pdo->prepare("
                INSERT INTO auditoria (usuario_id, accion, detalle) 
                VALUES (?, 'finalizar_reporte', ?)
            ");
            $stmtAudit->execute([$userId, "Reporte ID: $reporteId finalizado"]);

            echo json_encode([
                'success' => true,
                'message' => 'Reporte finalizado correctamente'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al finalizar reporte']);
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
        // Admin y supervisor ven todos, operador solo los suyos
        if ($userRol === 'admin' || $userRol === 'supervisor') {
            $sql = "
                SELECT 
                    r.id,
                    r.fecha,
                    r.estado,
                    u.nombre_completo as operador,
                    CONCAT(e.categoria, ' - ', e.codigo) as equipo,
                    COUNT(DISTINCT rd.id) as total_actividades,
                    COALESCE(SUM(rd.horas_trabajadas), 0) as horas_totales
                FROM reportes r
                INNER JOIN usuarios u ON r.usuario_id = u.id
                INNER JOIN equipos e ON r.equipo_id = e.id
                LEFT JOIN reportes_detalle rd ON r.id = rd.reporte_id
                GROUP BY r.id, r.fecha, r.estado, u.nombre_completo, e.categoria, e.codigo
                ORDER BY r.fecha DESC, r.id DESC
            ";
            $stmt = $pdo->query($sql);
        } else {
            $sql = "
                SELECT 
                    r.id,
                    r.fecha,
                    r.estado,
                    CONCAT(e.categoria, ' - ', e.codigo) as equipo,
                    COUNT(DISTINCT rd.id) as total_actividades,
                    COALESCE(SUM(rd.horas_trabajadas), 0) as horas_totales
                FROM reportes r
                INNER JOIN equipos e ON r.equipo_id = e.id
                LEFT JOIN reportes_detalle rd ON r.id = rd.reporte_id
                WHERE r.usuario_id = ?
                GROUP BY r.id, r.fecha, r.estado, e.categoria, e.codigo
                ORDER BY r.fecha DESC, r.id DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
        }
        
        $reportes = $stmt->fetchAll();
        
        // Formato para DataTables
        $data = [];
        foreach ($reportes as $reporte) {
            
            $estadoBadge = $reporte['estado'] === 'finalizado'
                ? '<span class="badge badge-success"><i class="fas fa-check"></i> Finalizado</span>' 
                : '<span class="badge badge-warning"><i class="fas fa-edit"></i> Borrador</span>';
            
            $horasTexto = number_format($reporte['horas_totales'], 1) . ' hrs';
            
            // Botones de acción según rol y estado
            $acciones = '';
            
            if ($reporte['estado'] === 'borrador') {
                // BORRADOR
                if ($userRol === 'operador' || $userRol === 'admin') {
                    $acciones .= '<a href="editar.php?id=' . $reporte['id'] . '" 
                        class="btn btn-sm btn-warning" title="Editar">
                        <i class="fas fa-edit"></i>
                    </a> ';
                }
                if ($userRol === 'supervisor') {
                    $acciones .= '<a href="ver.php?id=' . $reporte['id'] . '" 
                        class="btn btn-sm btn-info" title="Ver">
                        <i class="fas fa-eye"></i>
                    </a> ';
                }
            } else {
                // FINALIZADO
                if ($userRol === 'admin') {
                    $acciones .= '<a href="editar.php?id=' . $reporte['id'] . '" 
                        class="btn btn-sm btn-warning" title="Editar">
                        <i class="fas fa-edit"></i>
                    </a> ';
                }
                
                $acciones .= '<a href="ver.php?id=' . $reporte['id'] . '" 
                    class="btn btn-sm btn-info" title="Ver">
                    <i class="fas fa-eye"></i>
                </a> ';
                
                $acciones .= '<a href="../../api/pdf.php?id=' . $reporte['id'] . '" 
                    target="_blank"
                    class="btn btn-sm btn-danger" title="PDF">
                    <i class="fas fa-file-pdf"></i>
                </a>';
            }
            
            $row = [
                $reporte['id'],
                date('d/m/Y', strtotime($reporte['fecha'])),
                $reporte['equipo'],
                $reporte['total_actividades'],
                $horasTexto,
                $estadoBadge,
                $acciones
            ];
            
            // Si es admin/supervisor, agregar operador en posición 2
            if ($userRol === 'admin' || $userRol === 'supervisor') {
                array_splice($row, 2, 0, [$reporte['operador']]);
            }
            
            $data[] = $row;
        }
        
        // Usar JSON_UNESCAPED_UNICODE para caracteres especiales
        echo json_encode([
            'success' => true,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (PDOException $e) {
        // En caso de error, devolver JSON válido
        echo json_encode([
            'success' => false, 
            'message' => 'Error al obtener reportes: ' . $e->getMessage(),
            'data' => []
        ], JSON_UNESCAPED_UNICODE);
    }
}

// ============================================
// OBTENER DATOS DE UN REPORTE PARA EDITAR
// ============================================
elseif ($action === 'obtener_reporte') {

    $reporteId = $_GET['id'] ?? 0;

    if (!$reporteId) {
        echo json_encode(['success' => false, 'message' => 'ID no válido']);
        exit;
    }

    try {
        // Obtener cabecera del reporte
        $stmt = $pdo->prepare("
            SELECT 
                r.*,
                u.nombre_completo as operador,
                e.codigo as equipo_codigo,
                e.categoria as equipo_categoria,
                e.id as equipo_id
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

        // Verificar permisos
        if ($reporte['usuario_id'] != $userId && $userRol !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'No tiene permisos']);
            exit;
        }

        // Obtener actividades
        $stmt = $pdo->prepare("
            SELECT 
                rd.*,
                tt.nombre as tipo_trabajo,
                fc.codigo as fase_codigo,
                fc.descripcion as fase_descripcion
            FROM reportes_detalle rd
            INNER JOIN tipos_trabajo tt ON rd.tipo_trabajo_id = tt.id
            INNER JOIN fases_costo fc ON rd.fase_costo_id = fc.id
            WHERE rd.reporte_id = ?
            ORDER BY rd.orden ASC
        ");
        $stmt->execute([$reporteId]);
        $actividades = $stmt->fetchAll();

        // Obtener combustibles
        $stmt = $pdo->prepare("
            SELECT * FROM reportes_combustible 
            WHERE reporte_id = ?
            ORDER BY fecha_hora ASC
        ");
        $stmt->execute([$reporteId]);
        $combustibles = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'reporte' => $reporte,
            'actividades' => $actividades,
            'combustibles' => $combustibles
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener reporte']);
    }
}

// ============================================
// ELIMINAR REPORTE (Solo borradores vacíos)
// ============================================
elseif ($action === 'eliminar') {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $reporteId = $_POST['reporte_id'] ?? 0;

    if (!$reporteId) {
        echo json_encode(['success' => false, 'message' => 'ID no válido']);
        exit;
    }

    try {
        // Verificar que el reporte existe
        $stmt = $pdo->prepare("
            SELECT id, usuario_id, estado FROM reportes WHERE id = ?
        ");
        $stmt->execute([$reporteId]);
        $reporte = $stmt->fetch();

        if (!$reporte) {
            echo json_encode(['success' => false, 'message' => 'Reporte no encontrado']);
            exit;
        }

        // Solo el dueño o admin pueden eliminar
        if ($reporte['usuario_id'] != $userId && $userRol !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'No tiene permisos para eliminar este reporte']);
            exit;
        }

        // Solo se pueden eliminar borradores
        if ($reporte['estado'] === 'finalizado' && $userRol !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'No se puede eliminar un reporte finalizado']);
            exit;
        }

        // Verificar que NO tenga actividades
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total FROM reportes_detalle WHERE reporte_id = ?
        ");
        $stmt->execute([$reporteId]);
        $totalActividades = $stmt->fetch()['total'];

        if ($totalActividades > 0) {
            echo json_encode(['success' => false, 'message' => 'No se puede eliminar un reporte con actividades registradas']);
            exit;
        }

        // Eliminar combustibles si hay (no importa si tiene combustible sin actividades)
        $stmt = $pdo->prepare("DELETE FROM reportes_combustible WHERE reporte_id = ?");
        $stmt->execute([$reporteId]);

        // Eliminar reporte
        $stmt = $pdo->prepare("DELETE FROM reportes WHERE id = ?");

        if ($stmt->execute([$reporteId])) {

            // Registrar en auditoría
            $stmtAudit = $pdo->prepare("
                INSERT INTO auditoria (usuario_id, accion, detalle) 
                VALUES (?, 'eliminar_reporte', ?)
            ");
            $stmtAudit->execute([$userId, "Reporte ID: $reporteId eliminado (sin actividades)"]);

            echo json_encode([
                'success' => true,
                'message' => 'Reporte eliminado correctamente'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar reporte']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
    }
}

// ============================================
// ACCIÓN NO VÁLIDA
// ============================================
else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
