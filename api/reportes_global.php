<?php
/**
 * OperaSys - API de Reportes Globales
 * Archivo: api/reportes_global.php
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
                u.nombre_completo as operador,
                u.dni as operador_dni,
                e.codigo as equipo_codigo,
                e.categoria as equipo_categoria,
                e.descripcion as equipo_descripcion,
                COUNT(DISTINCT rd.id) as total_actividades,
                COALESCE(SUM(rd.horas_trabajadas), 0) as total_horas,
                GROUP_CONCAT(DISTINCT fc.codigo ORDER BY fc.codigo SEPARATOR ', ') as fases_usadas,
                COUNT(DISTINCT rc.id) as total_combustible,
                COALESCE(SUM(rc.galones), 0) as total_galones
            FROM reportes r
            INNER JOIN usuarios u ON r.usuario_id = u.id
            INNER JOIN equipos e ON r.equipo_id = e.id
            LEFT JOIN reportes_detalle rd ON r.id = rd.reporte_id
            LEFT JOIN fases_costo fc ON rd.fase_costo_id = fc.id
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
        
        if (!empty($_GET['fase_costo_id'])) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM reportes_detalle rd2 
                WHERE rd2.reporte_id = r.id 
                AND rd2.fase_costo_id = ?
            )";
            $params[] = $_GET['fase_costo_id'];
        }
        
        $sql .= " GROUP BY r.id, r.fecha, r.estado, u.nombre_completo, u.dni, e.codigo, e.categoria, e.descripcion";
        $sql .= " ORDER BY r.fecha DESC, r.id DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $reportes = $stmt->fetchAll();
        
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
                u.nombre_completo as operador,
                u.dni as operador_dni,
                e.codigo as equipo_codigo,
                e.categoria as equipo_categoria,
                COUNT(DISTINCT rd.id) as total_actividades,
                COALESCE(SUM(rd.horas_trabajadas), 0) as total_horas,
                GROUP_CONCAT(DISTINCT fc.codigo ORDER BY fc.codigo SEPARATOR ', ') as fases_usadas,
                COUNT(DISTINCT rc.id) as total_combustible,
                COALESCE(SUM(rc.galones), 0) as total_galones,
                r.observaciones_generales
            FROM reportes r
            INNER JOIN usuarios u ON r.usuario_id = u.id
            INNER JOIN equipos e ON r.equipo_id = e.id
            LEFT JOIN reportes_detalle rd ON r.id = rd.reporte_id
            LEFT JOIN fases_costo fc ON rd.fase_costo_id = fc.id
            LEFT JOIN reportes_combustible rc ON r.id = rc.reporte_id
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
        $datos[] = ['<center><b>REPORTE GLOBAL DE OPERACIONES</b></center>'];
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
            '<b>Fases Usadas</b>',
            '<b>Actividades</b>',
            '<b>Horas Totales</b>',
            '<b>Combustible (gal)</b>',
            '<b>Estado</b>',
            '<b>Observaciones</b>'
        ];
        
        // Datos
        foreach ($reportes as $reporte) {
            $datos[] = [
                $reporte['id'],
                date('d/m/Y', strtotime($reporte['fecha'])),
                $reporte['operador'],
                $reporte['operador_dni'],
                $reporte['equipo_codigo'],
                $reporte['equipo_categoria'],
                $reporte['fases_usadas'] ?? 'N/A',
                $reporte['total_actividades'],
                number_format($reporte['total_horas'], 2),
                $reporte['total_galones'] ?? '0',
                ucfirst($reporte['estado']),
                $reporte['observaciones_generales'] ?? ''
            ];
        }
        
        // Generar Excel
        $xlsx = Shuchkin\SimpleXLSXGen::fromArray($datos);
        $filename = 'Reportes_' . date('Y-m-d_His') . '.xlsx';
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
                u.nombre_completo as operador,
                e.codigo as equipo_codigo,
                e.categoria as equipo_categoria,
                COUNT(DISTINCT rd.id) as total_actividades,
                COALESCE(SUM(rd.horas_trabajadas), 0) as total_horas
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
        $pdf->Cell(0, 10, 'REPORTE GLOBAL DE OPERACIONES', 0, 1, 'C');
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, 'Generado el: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
        $pdf->Ln(5);
        
        // Encabezados de tabla
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(68, 114, 196);
        $pdf->SetTextColor(255, 255, 255);
        
        $pdf->Cell(15, 8, 'ID', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Fecha', 1, 0, 'C', true);
        $pdf->Cell(60, 8, 'Operador', 1, 0, 'C', true);
        $pdf->Cell(35, 8, 'Equipo', 1, 0, 'C', true);
        $pdf->Cell(45, 8, utf8_decode('Categoría'), 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Actividades', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Horas', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Estado', 1, 1, 'C', true);
        
        // Datos
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0);
        
        $totalHoras = 0;
        foreach ($reportes as $reporte) {
            $pdf->Cell(15, 6, $reporte['id'], 1, 0, 'C');
            $pdf->Cell(25, 6, date('d/m/Y', strtotime($reporte['fecha'])), 1, 0, 'C');
            $pdf->Cell(60, 6, utf8_decode(substr($reporte['operador'], 0, 30)), 1, 0, 'L');
            $pdf->Cell(35, 6, $reporte['equipo_codigo'], 1, 0, 'C');
            $pdf->Cell(45, 6, utf8_decode($reporte['equipo_categoria']), 1, 0, 'L');
            $pdf->Cell(25, 6, $reporte['total_actividades'], 1, 0, 'C');
            $pdf->Cell(25, 6, number_format($reporte['total_horas'], 1), 1, 0, 'C');
            $pdf->Cell(25, 6, utf8_decode(ucfirst($reporte['estado'])), 1, 1, 'C');
            
            $totalHoras += $reporte['total_horas'];
        }
        
        // Totales
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(205, 6, 'TOTAL HORAS:', 1, 0, 'R');
        $pdf->Cell(25, 6, number_format($totalHoras, 1), 1, 0, 'C');
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