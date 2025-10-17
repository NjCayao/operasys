<?php
/**
 * OperaSys - API de Reportes Globales
 * Archivo: api/reportes_global.php
 * Versión: 3.0 - Sistema HT/HP con horómetros y combustible (SIN partidas)
 * Descripción: Consulta y exportación de reportes globales (Admin/Supervisor)
 * USA: SimpleXLSXGen para Excel
 */

require_once '../config/database.php';
require_once '../config/config.php';

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}

// Solo admin y supervisor pueden acceder
$userRol = $_SESSION['rol'];
if ($userRol !== 'admin' && $userRol !== 'supervisor') {
    echo json_encode(['success' => false, 'message' => 'No tiene permisos']);
    exit;
}

$action = $_GET['action'] ?? '';

// ============================================
// LISTAR REPORTES GLOBALES
// ============================================
if ($action === 'listar') {
    
    try {
        $sql = "
            SELECT 
                r.id,
                r.fecha,
                r.estado,
                r.horometro_inicial,
                r.horometro_final,
                r.horas_motor,
                r.total_abastecido,
                u.nombre_completo as operador,
                u.dni as operador_dni,
                e.codigo as equipo_codigo,
                e.categoria as equipo_categoria,
                e.descripcion as equipo_descripcion,
                e.consumo_promedio_hr,
                COUNT(DISTINCT rd.id) as total_actividades,
                COUNT(DISTINCT CASE WHEN rd.tipo_hora = 'HT' THEN rd.id END) as total_ht_actividades,
                COUNT(DISTINCT CASE WHEN rd.tipo_hora = 'HP' THEN rd.id END) as total_hp_actividades,
                COALESCE(SUM(CASE WHEN rd.tipo_hora = 'HT' THEN rd.horas_transcurridas ELSE 0 END), 0) as total_horas_ht,
                COALESCE(SUM(CASE WHEN rd.tipo_hora = 'HP' THEN rd.horas_transcurridas ELSE 0 END), 0) as total_horas_hp,
                COUNT(DISTINCT rc.id) as total_combustible,
                COALESCE(SUM(rc.galones), 0) as total_galones,
                (e.consumo_promedio_hr * r.horas_motor) as consumo_estimado
            FROM reportes r
            INNER JOIN usuarios u ON r.usuario_id = u.id
            INNER JOIN equipos e ON r.equipo_id = e.id
            LEFT JOIN reportes_detalle rd ON r.id = rd.reporte_id
            LEFT JOIN reportes_combustible rc ON r.id = rc.reporte_id
            WHERE 1=1
        ";
        
        $params = [];
        
        // Aplicar filtros
        if (!empty($_GET['operador_id'])) {
            $sql .= " AND r.usuario_id = ?";
            $params[] = $_GET['operador_id'];
        }
        
        if (!empty($_GET['categoria'])) {
            $sql .= " AND e.categoria = ?";
            $params[] = $_GET['categoria'];
        }
        
        if (!empty($_GET['fecha_desde'])) {
            $sql .= " AND r.fecha >= ?";
            $params[] = $_GET['fecha_desde'];
        }
        
        if (!empty($_GET['fecha_hasta'])) {
            $sql .= " AND r.fecha <= ?";
            $params[] = $_GET['fecha_hasta'];
        }
        
        if (!empty($_GET['estado'])) {
            $sql .= " AND r.estado = ?";
            $params[] = $_GET['estado'];
        }
        
        $sql .= " GROUP BY r.id, r.fecha, r.estado, r.horometro_inicial, r.horometro_final, r.horas_motor, r.total_abastecido, u.nombre_completo, u.dni, e.codigo, e.categoria, e.descripcion, e.consumo_promedio_hr";
        $sql .= " ORDER BY r.fecha DESC, r.id DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $reportes = $stmt->fetchAll();
        
        // Calcular eficiencia y diferencia de combustible
        foreach ($reportes as &$reporte) {
            // Eficiencia: (HT / Horas Motor) * 100
            if ($reporte['horas_motor'] > 0) {
                $reporte['eficiencia'] = round(($reporte['total_horas_ht'] / $reporte['horas_motor']) * 100, 1);
            } else {
                $reporte['eficiencia'] = 0;
            }
            
            // Diferencia combustible: Abastecido - Estimado
            $reporte['diferencia_combustible'] = $reporte['total_galones'] - $reporte['consumo_estimado'];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $reportes
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener reportes: ' . $e->getMessage(),
            'data' => []
        ]);
    }
}

// ============================================
// EXPORTAR A EXCEL (SimpleXLSXGen)
// ============================================
elseif ($action === 'exportar_excel') {
    
    try {
        require_once '../vendor/SimpleXLSXGen.php';
        
        // Obtener datos con filtros
        $sql = "
            SELECT 
                r.id,
                r.fecha,
                r.estado,
                r.horometro_inicial,
                r.horometro_final,
                r.horas_motor,
                r.total_abastecido,
                u.nombre_completo as operador,
                u.dni as operador_dni,
                e.codigo as equipo_codigo,
                e.categoria as equipo_categoria,
                e.consumo_promedio_hr,
                COUNT(DISTINCT rd.id) as total_actividades,
                COALESCE(SUM(CASE WHEN rd.tipo_hora = 'HT' THEN rd.horas_transcurridas ELSE 0 END), 0) as total_horas_ht,
                COALESCE(SUM(CASE WHEN rd.tipo_hora = 'HP' THEN rd.horas_transcurridas ELSE 0 END), 0) as total_horas_hp,
                (e.consumo_promedio_hr * r.horas_motor) as consumo_estimado,
                r.observaciones_generales
            FROM reportes r
            INNER JOIN usuarios u ON r.usuario_id = u.id
            INNER JOIN equipos e ON r.equipo_id = e.id
            LEFT JOIN reportes_detalle rd ON r.id = rd.reporte_id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($_GET['operador_id'])) {
            $sql .= " AND r.usuario_id = ?";
            $params[] = $_GET['operador_id'];
        }
        
        if (!empty($_GET['categoria'])) {
            $sql .= " AND e.categoria = ?";
            $params[] = $_GET['categoria'];
        }
        
        if (!empty($_GET['fecha_desde'])) {
            $sql .= " AND r.fecha >= ?";
            $params[] = $_GET['fecha_desde'];
        }
        
        if (!empty($_GET['fecha_hasta'])) {
            $sql .= " AND r.fecha <= ?";
            $params[] = $_GET['fecha_hasta'];
        }
        
        $sql .= " GROUP BY r.id";
        $sql .= " ORDER BY r.fecha DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $reportes = $stmt->fetchAll();
        
        // Preparar datos para Excel
        $datos = [];
        
        // Título
        $datos[] = ['<center><b>REPORTE GLOBAL DE OPERACIONES - SISTEMA HT/HP</b></center>'];
        $datos[] = ['<center>Generado el: ' . date('d/m/Y H:i:s') . '</center>'];
        $datos[] = []; // Fila vacía
        
        // Encabezados
        $datos[] = [
            '<b>ID</b>',
            '<b>Fecha</b>',
            '<b>Operador</b>',
            '<b>DNI</b>',
            '<b>Equipo</b>',
            '<b>Categoría</b>',
            '<b>Horómetro Inicial</b>',
            '<b>Horómetro Final</b>',
            '<b>Horas Motor</b>',
            '<b>Total HT (hrs)</b>',
            '<b>Total HP (hrs)</b>',
            '<b>Eficiencia (%)</b>',
            '<b>Consumo Est. (gal)</b>',
            '<b>Abastecido (gal)</b>',
            '<b>Diferencia (gal)</b>',
            '<b>Estado</b>',
            '<b>Observaciones</b>'
        ];
        
        // Datos
        foreach ($reportes as $reporte) {
            $eficiencia = $reporte['horas_motor'] > 0 
                ? round(($reporte['total_horas_ht'] / $reporte['horas_motor']) * 100, 1) 
                : 0;
            
            $diferencia = $reporte['total_abastecido'] - $reporte['consumo_estimado'];
            
            $datos[] = [
                $reporte['id'],
                date('d/m/Y', strtotime($reporte['fecha'])),
                $reporte['operador'],
                $reporte['operador_dni'],
                $reporte['equipo_codigo'],
                $reporte['equipo_categoria'],
                number_format($reporte['horometro_inicial'], 1),
                number_format($reporte['horometro_final'], 1),
                number_format($reporte['horas_motor'], 2),
                number_format($reporte['total_horas_ht'], 2),
                number_format($reporte['total_horas_hp'], 2),
                $eficiencia . '%',
                number_format($reporte['consumo_estimado'], 1),
                number_format($reporte['total_abastecido'], 1),
                number_format($diferencia, 1),
                ucfirst($reporte['estado']),
                $reporte['observaciones_generales'] ?? ''
            ];
        }
        
        // Generar Excel
        $xlsx = Shuchkin\SimpleXLSXGen::fromArray($datos);
        $filename = 'Reportes_Global_' . date('Y-m-d_His') . '.xlsx';
        $xlsx->downloadAs($filename);
        exit;
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al generar Excel: ' . $e->getMessage()]);
    }
}

// ============================================
// EXPORTAR PDF GLOBAL
// ============================================
elseif ($action === 'exportar_pdf') {
    
    try {
        require_once '../vendor/fpdf/fpdf.php';
        
        // Obtener datos con filtros
        $sql = "
            SELECT 
                r.id,
                r.fecha,
                r.estado,
                r.horas_motor,
                u.nombre_completo as operador,
                e.codigo as equipo_codigo,
                e.categoria as equipo_categoria,
                COUNT(DISTINCT rd.id) as total_actividades,
                COALESCE(SUM(CASE WHEN rd.tipo_hora = 'HT' THEN rd.horas_transcurridas ELSE 0 END), 0) as total_horas_ht,
                COALESCE(SUM(CASE WHEN rd.tipo_hora = 'HP' THEN rd.horas_transcurridas ELSE 0 END), 0) as total_horas_hp,
                r.total_abastecido
            FROM reportes r
            INNER JOIN usuarios u ON r.usuario_id = u.id
            INNER JOIN equipos e ON r.equipo_id = e.id
            LEFT JOIN reportes_detalle rd ON r.id = rd.reporte_id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($_GET['operador_id'])) {
            $sql .= " AND r.usuario_id = ?";
            $params[] = $_GET['operador_id'];
        }
        
        if (!empty($_GET['categoria'])) {
            $sql .= " AND e.categoria = ?";
            $params[] = $_GET['categoria'];
        }
        
        if (!empty($_GET['fecha_desde'])) {
            $sql .= " AND r.fecha >= ?";
            $params[] = $_GET['fecha_desde'];
        }
        
        if (!empty($_GET['fecha_hasta'])) {
            $sql .= " AND r.fecha <= ?";
            $params[] = $_GET['fecha_hasta'];
        }
        
        $sql .= " GROUP BY r.id";
        $sql .= " ORDER BY r.fecha DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $reportes = $stmt->fetchAll();
        
        // Crear PDF
        $pdf = new FPDF('L', 'mm', 'A4'); // Horizontal
        $pdf->AddPage();
        
        // Título
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'REPORTE GLOBAL DE OPERACIONES - SISTEMA HT/HP', 0, 1, 'C');
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, 'Generado el: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
        $pdf->Ln(5);
        
        // Encabezados de tabla
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(68, 114, 196);
        $pdf->SetTextColor(255, 255, 255);
        
        $pdf->Cell(15, 8, 'ID', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Fecha', 1, 0, 'C', true);
        $pdf->Cell(50, 8, 'Operador', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Equipo', 1, 0, 'C', true);
        $pdf->Cell(40, 8, utf8_decode('Categoría'), 1, 0, 'C', true);
        $pdf->Cell(20, 8, 'H. Motor', 1, 0, 'C', true);
        $pdf->Cell(20, 8, 'HT', 1, 0, 'C', true);
        $pdf->Cell(20, 8, 'HP', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Efic. %', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Estado', 1, 1, 'C', true);
        
        // Datos
        $pdf->SetFont('Arial', '', 7);
        $pdf->SetTextColor(0, 0, 0);
        
        $totalHorasMotor = 0;
        $totalHT = 0;
        $totalHP = 0;
        
        foreach ($reportes as $reporte) {
            $eficiencia = $reporte['horas_motor'] > 0 
                ? round(($reporte['total_horas_ht'] / $reporte['horas_motor']) * 100, 1) 
                : 0;
            
            $pdf->Cell(15, 6, $reporte['id'], 1, 0, 'C');
            $pdf->Cell(25, 6, date('d/m/Y', strtotime($reporte['fecha'])), 1, 0, 'C');
            $pdf->Cell(50, 6, utf8_decode(substr($reporte['operador'], 0, 25)), 1, 0, 'L');
            $pdf->Cell(30, 6, $reporte['equipo_codigo'], 1, 0, 'C');
            $pdf->Cell(40, 6, utf8_decode($reporte['equipo_categoria']), 1, 0, 'L');
            $pdf->Cell(20, 6, number_format($reporte['horas_motor'], 1), 1, 0, 'C');
            $pdf->Cell(20, 6, number_format($reporte['total_horas_ht'], 1), 1, 0, 'C');
            $pdf->Cell(20, 6, number_format($reporte['total_horas_hp'], 1), 1, 0, 'C');
            $pdf->Cell(25, 6, $eficiencia . '%', 1, 0, 'C');
            $pdf->Cell(25, 6, utf8_decode(ucfirst($reporte['estado'])), 1, 1, 'C');
            
            $totalHorasMotor += $reporte['horas_motor'];
            $totalHT += $reporte['total_horas_ht'];
            $totalHP += $reporte['total_horas_hp'];
        }
        
        // Totales
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(180, 6, 'TOTALES:', 1, 0, 'R');
        $pdf->Cell(20, 6, number_format($totalHorasMotor, 1), 1, 0, 'C');
        $pdf->Cell(20, 6, number_format($totalHT, 1), 1, 0, 'C');
        $pdf->Cell(20, 6, number_format($totalHP, 1), 1, 0, 'C');
        $pdf->Cell(25, 6, '', 1, 0, 'C');
        $pdf->Cell(25, 6, '', 1, 1, 'C');
        
        $pdf->Output('D', 'Reportes_Global_' . date('Y-m-d') . '.pdf');
        exit;
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al generar PDF: ' . $e->getMessage()]);
    }
}

// ============================================
// ACCIÓN NO VÁLIDA
// ============================================
else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}