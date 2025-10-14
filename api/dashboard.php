<?php

/**
 * OperaSys - API del Dashboard
 * Archivo: api/dashboard.php
 * Descripción: Estadísticas y datos para el panel principal
 */

require_once '../config/database.php';
require_once '../config/config.php';

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}

$action = $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];
$userRol = $_SESSION['rol'];

// ============================================
// ESTADÍSTICAS GENERALES
// ============================================
if ($action === 'estadisticas') {

    try {
        $estadisticas = [];

        // Determinar si es admin/supervisor (ve todos) u operador (solo suyos)
        if ($userRol === 'admin' || $userRol === 'supervisor') {
            // Total de reportes (TODOS)
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM reportes");
            $estadisticas['total_reportes'] = $stmt->fetch()['total'];

            // Reportes hoy (TODOS)
            $stmt = $pdo->query("
                SELECT COUNT(*) as total 
                FROM reportes 
                WHERE fecha = CURDATE()
            ");
            $estadisticas['reportes_hoy'] = $stmt->fetch()['total'];

            // Horas trabajadas este mes (TODOS)
            $stmt = $pdo->query("
                SELECT COALESCE(SUM(rd.horas_trabajadas), 0) as total 
                FROM reportes r
                INNER JOIN reportes_detalle rd ON r.id = rd.reporte_id
                WHERE MONTH(r.fecha) = MONTH(CURDATE()) 
                AND YEAR(r.fecha) = YEAR(CURDATE())
            ");
            $estadisticas['horas_mes'] = round($stmt->fetchColumn(), 1);
        } else {
            // Total de reportes del usuario
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reportes WHERE usuario_id = ?");
            $stmt->execute([$userId]);
            $estadisticas['total_reportes'] = $stmt->fetch()['total'];

            // Reportes hoy
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total 
                FROM reportes 
                WHERE usuario_id = ? AND fecha = CURDATE()
            ");
            $stmt->execute([$userId]);
            $estadisticas['reportes_hoy'] = $stmt->fetch()['total'];

            // Horas trabajadas este mes
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(rd.horas_trabajadas), 0) as total 
                FROM reportes r
                INNER JOIN reportes_detalle rd ON r.id = rd.reporte_id
                WHERE r.usuario_id = ? 
                AND MONTH(r.fecha) = MONTH(CURDATE()) 
                AND YEAR(r.fecha) = YEAR(CURDATE())
            ");
            $stmt->execute([$userId]);
            $estadisticas['horas_mes'] = round($stmt->fetchColumn(), 1);
        }

        // Equipos activos (todos los roles ven lo mismo)
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM equipos WHERE estado = 1");
        $estadisticas['equipos_activos'] = $stmt->fetch()['total'];

        // Si es admin, agregar estadísticas globales de usuarios
        if ($userRol === 'admin') {
            // Total usuarios por rol
            $stmt = $pdo->query("SELECT rol, COUNT(*) as total FROM usuarios WHERE estado = 1 GROUP BY rol");
            $usuarios = $stmt->fetchAll();

            foreach ($usuarios as $u) {
                $estadisticas['usuarios_' . $u['rol']] = $u['total'];
            }
        }

        echo json_encode([
            'success' => true,
            'estadisticas' => $estadisticas
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener estadísticas: ' . $e->getMessage()]);
    }
}

// ============================================
// REPORTES POR MES (Últimos 6 meses)
// ============================================
elseif ($action === 'reportes_mes') {

    try {
        // Consulta adaptada para todos los roles
        if ($userRol === 'admin' || $userRol === 'supervisor') {
            // Admin/Supervisor ve todos los reportes
            $sql = "
                SELECT 
                    DATE_FORMAT(fecha, '%Y-%m') as mes,
                    COUNT(*) as total
                FROM reportes
                WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(fecha, '%Y-%m')
                ORDER BY mes ASC
            ";
            $stmt = $pdo->query($sql);
        } else {
            // Operador ve solo sus reportes
            $sql = "
                SELECT 
                    DATE_FORMAT(fecha, '%Y-%m') as mes,
                    COUNT(*) as total
                FROM reportes
                WHERE usuario_id = ?
                AND fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(fecha, '%Y-%m')
                ORDER BY mes ASC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
        }

        $datos = $stmt->fetchAll();

        // Formatear datos para Chart.js
        $meses = [];
        $totales = [];

        foreach ($datos as $dato) {
            // Convertir 2025-01 a "Enero 2025"
            $fecha = DateTime::createFromFormat('Y-m', $dato['mes']);
            $nombreMes = [
                '01' => 'Enero',
                '02' => 'Febrero',
                '03' => 'Marzo',
                '04' => 'Abril',
                '05' => 'Mayo',
                '06' => 'Junio',
                '07' => 'Julio',
                '08' => 'Agosto',
                '09' => 'Septiembre',
                '10' => 'Octubre',
                '11' => 'Noviembre',
                '12' => 'Diciembre'
            ];
            $partes = explode('-', $dato['mes']);
            $meses[] = $nombreMes[$partes[1]] . ' ' . $partes[0];
            $totales[] = (int)$dato['total'];
        }

        echo json_encode([
            'success' => true,
            'labels' => $meses,
            'data' => $totales
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener datos: ' . $e->getMessage()]);
    }
}

// ============================================
// EQUIPOS MÁS UTILIZADOS
// ============================================
elseif ($action === 'equipos_mas_usados') {

    try {
        // Consulta adaptada para todos los roles
        if ($userRol === 'admin' || $userRol === 'supervisor') {
            // Admin/Supervisor ve todos los equipos
            $sql = "
                SELECT 
                    e.codigo,
                    e.categoria,
                    COUNT(r.id) as total_usos
                FROM reportes r
                INNER JOIN equipos e ON r.equipo_id = e.id
                WHERE r.fecha >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
                GROUP BY e.id, e.codigo, e.categoria
                ORDER BY total_usos DESC
                LIMIT 5
            ";
            $stmt = $pdo->query($sql);
        } else {
            // Operador ve solo sus equipos
            $sql = "
                SELECT 
                    e.codigo,
                    e.categoria,
                    COUNT(r.id) as total_usos
                FROM reportes r
                INNER JOIN equipos e ON r.equipo_id = e.id
                WHERE r.usuario_id = ?
                AND r.fecha >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
                GROUP BY e.id, e.codigo, e.categoria
                ORDER BY total_usos DESC
                LIMIT 5
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
        }

        $datos = $stmt->fetchAll();

        $equipos = [];
        $usos = [];

        foreach ($datos as $dato) {
            $equipos[] = $dato['categoria'] . ' ' . $dato['codigo'];
            $usos[] = (int)$dato['total_usos'];
        }

        echo json_encode([
            'success' => true,
            'labels' => $equipos,
            'data' => $usos
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener datos: ' . $e->getMessage()]);
    }
}

// ============================================
// ÚLTIMOS REPORTES
// ============================================
elseif ($action === 'ultimos_reportes') {

    try {
        // Consulta adaptada para todos los roles
        if ($userRol === 'admin' || $userRol === 'supervisor') {
            // Admin/Supervisor ve todos los reportes
            $sql = "
                SELECT 
                    r.id,
                    r.fecha,
                    r.estado,
                    (SELECT COUNT(*) FROM reportes_detalle WHERE reporte_id = r.id) as num_actividades,
                    e.codigo as equipo_codigo,
                    e.categoria as equipo_categoria,
                    u.nombre_completo as operador_nombre
                FROM reportes r
                INNER JOIN equipos e ON r.equipo_id = e.id
                INNER JOIN usuarios u ON r.usuario_id = u.id
                ORDER BY r.fecha DESC, r.id DESC
                LIMIT 10
            ";
            $stmt = $pdo->query($sql);
        } else {
            // Operador ve solo sus reportes
            $sql = "
                SELECT 
                    r.id,
                    r.fecha,
                    r.estado,
                    (SELECT COUNT(*) FROM reportes_detalle WHERE reporte_id = r.id) as num_actividades,
                    e.codigo as equipo_codigo,
                    e.categoria as equipo_categoria
                FROM reportes r
                INNER JOIN equipos e ON r.equipo_id = e.id
                WHERE r.usuario_id = ?
                ORDER BY r.fecha DESC, r.id DESC
                LIMIT 10
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
        }

        $reportes = $stmt->fetchAll();

        $datos = [];
        foreach ($reportes as $r) {
            $estadoBadge = $r['estado'] === 'finalizado'
                ? '<span class="badge badge-success">Finalizado</span>'
                : '<span class="badge badge-warning">Borrador</span>';

            // Calcular horas totales del reporte
            $stmtHoras = $pdo->prepare("
                SELECT COALESCE(SUM(horas_trabajadas), 0) as total 
                FROM reportes_detalle 
                WHERE reporte_id = ?
            ");
            $stmtHoras->execute([$r['id']]);
            $horasTrabajadas = $stmtHoras->fetchColumn();

            $horasTexto = $horasTrabajadas > 0
                ? number_format($horasTrabajadas, 1) . ' hrs'
                : '0 hrs';

            $actividadesTexto = $r['num_actividades'] . ' actividad(es)';

            $fila = [
                'fecha' => date('d/m/Y', strtotime($r['fecha'])),
                'equipo' => $r['equipo_categoria'] . ' ' . $r['equipo_codigo'],
                'horas' => $horasTexto,
                'actividades' => $actividadesTexto,
                'estado' => $estadoBadge,
                'id' => $r['id']
            ];

            // Si es admin/supervisor, agregar nombre del operador
            if ($userRol === 'admin' || $userRol === 'supervisor') {
                $fila['operador'] = $r['operador_nombre'];
            }

            $datos[] = $fila;
        }

        echo json_encode([
            'success' => true,
            'reportes' => $datos
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener reportes: ' . $e->getMessage()]);
    }
}

// ============================================
// ACTIVIDAD RECIENTE (Solo Admin)
// ============================================
elseif ($action === 'actividad_reciente') {

    if ($userRol !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
        exit;
    }

    try {
        $stmt = $pdo->query("
            SELECT 
                a.accion,
                a.detalle,
                a.fecha,
                u.nombre_completo
            FROM auditoria a
            INNER JOIN usuarios u ON a.usuario_id = u.id
            ORDER BY a.fecha DESC
            LIMIT 3
        ");
        $actividades = $stmt->fetchAll();

        $datos = [];
        foreach ($actividades as $act) {
            // Iconos según acción
            $icono = 'fa-circle';
            $color = 'info';

            switch ($act['accion']) {
                case 'login':
                    $icono = 'fa-sign-in-alt';
                    $color = 'success';
                    break;
                case 'logout':
                    $icono = 'fa-sign-out-alt';
                    $color = 'secondary';
                    break;
                case 'crear_reporte':
                    $icono = 'fa-file-alt';
                    $color = 'primary';
                    break;
                case 'finalizar_reporte':
                    $icono = 'fa-check-circle';
                    $color = 'success';
                    break;
                case 'crear_equipo':
                    $icono = 'fa-plus';
                    $color = 'success';
                    break;
                case 'editar_equipo':
                    $icono = 'fa-edit';
                    $color = 'warning';
                    break;
                case 'eliminar_equipo':
                    $icono = 'fa-trash';
                    $color = 'danger';
                    break;
                case 'crear_usuario':
                    $icono = 'fa-user-plus';
                    $color = 'info';
                    break;
                case 'actualizar_usuario':
                    $icono = 'fa-user-edit';
                    $color = 'warning';
                    break;
                case 'firma_capturada':
                case 'firma_actualizada':
                    $icono = 'fa-signature';
                    $color = 'info';
                    break;
            }

            $tiempoTranscurrido = calcularTiempoTranscurrido($act['fecha']);

            $datos[] = [
                'icono' => $icono,
                'color' => $color,
                'usuario' => $act['nombre_completo'],
                'accion' => $act['accion'],
                'detalle' => $act['detalle'],
                'tiempo' => $tiempoTranscurrido
            ];
        }

        echo json_encode([
            'success' => true,
            'actividades' => $datos
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener actividad: ' . $e->getMessage()]);
    }
}

// ============================================
// ACCIÓN NO VÁLIDA
// ============================================
else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}

// ============================================
// FUNCIÓN AUXILIAR: CALCULAR TIEMPO TRANSCURRIDO
// ============================================
function calcularTiempoTranscurrido($fecha)
{
    $ahora = new DateTime();
    $entonces = new DateTime($fecha);
    $diferencia = $ahora->diff($entonces);

    if ($diferencia->days > 0) {
        return 'Hace ' . $diferencia->days . ' día' . ($diferencia->days > 1 ? 's' : '');
    } elseif ($diferencia->h > 0) {
        return 'Hace ' . $diferencia->h . ' hora' . ($diferencia->h > 1 ? 's' : '');
    } elseif ($diferencia->i > 0) {
        return 'Hace ' . $diferencia->i . ' minuto' . ($diferencia->i > 1 ? 's' : '');
    } else {
        return 'Justo ahora';
    }
}
