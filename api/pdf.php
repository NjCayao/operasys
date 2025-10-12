<?php
/**
 * OperaSys - Generación de PDF
 * Archivo: api/pdf.php
 * Descripción: Genera PDF de reportes con firma digital usando FPDF
 */

require_once '../config/database.php';
require_once '../config/config.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    die('Sesión no válida');
}

$reporteId = $_GET['id'] ?? 0;

if (!$reporteId) {
    die('ID de reporte no válido');
}

// Obtener datos del reporte
try {
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            u.nombre_completo as operador,
            u.cargo as operador_cargo,
            u.dni as operador_dni,
            u.firma as operador_firma,
            e.codigo as equipo_codigo,
            e.categoria as equipo_categoria,
            e.descripcion as equipo_descripcion,
            e.marca as equipo_marca,
            e.modelo as equipo_modelo
        FROM reportes r
        INNER JOIN usuarios u ON r.usuario_id = u.id
        INNER JOIN equipos e ON r.equipo_id = e.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reporteId]);
    $reporte = $stmt->fetch();
    
    if (!$reporte) {
        die('Reporte no encontrado');
    }
    
    // Verificar permisos
    $userId = $_SESSION['user_id'];
    $userRol = $_SESSION['rol'];
    
    if ($reporte['usuario_id'] != $userId && $userRol !== 'admin' && $userRol !== 'supervisor') {
        die('No tienes permisos para descargar este reporte');
    }
    
} catch (PDOException $e) {
    die('Error al obtener reporte');
}

// ============================================
// CONFIGURAR FPDF
// ============================================
require_once '../vendor/fpdf/fpdf.php';

class PDF extends FPDF {
    
    // Encabezado
    function Header() {
        // Logo (si existe)
        // $this->Image('logo.png', 10, 6, 30);
        
        // Título
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(46, 134, 171); // Color azul
        $this->Cell(0, 10, 'REPORTE DE OPERACION', 0, 1, 'C');
        
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 5, 'Sistema OperaSys', 0, 1, 'C');
        
        $this->Ln(5);
        
        // Línea divisoria
        $this->SetDrawColor(46, 134, 171);
        $this->SetLineWidth(0.5);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(5);
    }
    
    // Pie de página
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo() . ' - Generado el ' . date('d/m/Y H:i'), 0, 0, 'C');
    }
    
    // Función para crear secciones
    function SeccionTitulo($titulo) {
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(46, 134, 171);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 8, $titulo, 0, 1, 'L', true);
        $this->Ln(2);
    }
    
    // Función para campos de información
    function Campo($etiqueta, $valor) {
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(60, 60, 60);
        $this->Cell(50, 6, $etiqueta . ':', 0, 0);
        
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(0, 0, 0);
        $this->MultiCell(0, 6, $valor);
    }
}

// ============================================
// CREAR PDF
// ============================================
$pdf = new PDF();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 20);

// ============================================
// INFORMACIÓN DEL REPORTE
// ============================================
$pdf->SeccionTitulo('INFORMACION DEL REPORTE');

$pdf->Campo('Reporte N', str_pad($reporte['id'], 6, '0', STR_PAD_LEFT));
$pdf->Campo('Fecha', date('d/m/Y', strtotime($reporte['fecha'])));
$pdf->Campo('Hora Inicio', $reporte['hora_inicio']);
$pdf->Campo('Hora Fin', $reporte['hora_fin'] ?: 'En curso');

if ($reporte['horas_trabajadas']) {
    $pdf->Campo('Horas Trabajadas', number_format($reporte['horas_trabajadas'], 2) . ' hrs');
}

$pdf->Campo('Estado', $reporte['estado_sinc'] === 'sincronizado' ? 'Sincronizado' : 'Pendiente');

$pdf->Ln(5);

// ============================================
// INFORMACIÓN DEL OPERADOR
// ============================================
$pdf->SeccionTitulo('OPERADOR RESPONSABLE');

$pdf->Campo('Nombre', $reporte['operador']);
$pdf->Campo('DNI', $reporte['operador_dni']);
$pdf->Campo('Cargo', $reporte['operador_cargo']);

$pdf->Ln(5);

// ============================================
// INFORMACIÓN DEL EQUIPO
// ============================================
$pdf->SeccionTitulo('EQUIPO UTILIZADO');

$pdf->Campo('Categoria', $reporte['equipo_categoria']);
$pdf->Campo('Codigo', $reporte['equipo_codigo']);
$pdf->Campo('Descripcion', $reporte['equipo_descripcion'] ?: 'N/A');
$pdf->Campo('Marca', $reporte['equipo_marca'] ?: 'N/A');
$pdf->Campo('Modelo', $reporte['equipo_modelo'] ?: 'N/A');

$pdf->Ln(5);

// ============================================
// ACTIVIDAD REALIZADA
// ============================================
$pdf->SeccionTitulo('ACTIVIDAD REALIZADA');

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->MultiCell(0, 6, $reporte['actividad']);

$pdf->Ln(5);

// ============================================
// OBSERVACIONES
// ============================================
if ($reporte['observaciones']) {
    $pdf->SeccionTitulo('OBSERVACIONES');
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->MultiCell(0, 6, $reporte['observaciones']);
    
    $pdf->Ln(5);
}

// ============================================
// UBICACIÓN GPS
// ============================================
if ($reporte['ubicacion']) {
    $pdf->SeccionTitulo('UBICACION GPS');
    
    $pdf->Campo('Coordenadas', $reporte['ubicacion']);
    
    $pdf->Ln(5);
}

// ============================================
// FIRMA DIGITAL
// ============================================
$pdf->Ln(10);
$pdf->SeccionTitulo('FIRMA DEL OPERADOR');

if ($reporte['operador_firma']) {
    // Decodificar firma base64
    $firmaData = $reporte['operador_firma'];
    
    // Eliminar el prefijo data:image/png;base64, si existe
    if (strpos($firmaData, 'data:image/png;base64,') === 0) {
        $firmaData = substr($firmaData, strlen('data:image/png;base64,'));
    }
    
    // Decodificar base64
    $firmaDecoded = base64_decode($firmaData);
    
    // Guardar temporalmente
    $tempFile = tempnam(sys_get_temp_dir(), 'firma_') . '.png';
    file_put_contents($tempFile, $firmaDecoded);
    
    // Insertar imagen de firma
    $pdf->Image($tempFile, 15, $pdf->GetY(), 60, 20);
    
    // Eliminar archivo temporal
    unlink($tempFile);
    
    $pdf->Ln(22);
    
    // Línea de firma
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.2);
    $pdf->Line(15, $pdf->GetY(), 75, $pdf->GetY());
    
    $pdf->Ln(2);
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(60, 60, 60);
    $pdf->Cell(60, 5, $reporte['operador'], 0, 1, 'C');
    $pdf->Cell(60, 5, $reporte['operador_cargo'], 0, 1, 'C');
    $pdf->Cell(60, 5, 'DNI: ' . $reporte['operador_dni'], 0, 1, 'C');
    
} else {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetTextColor(200, 0, 0);
    $pdf->Cell(0, 6, 'El operador no ha registrado su firma digital', 0, 1);
}

// ============================================
// CUADRO DE VALIDACIÓN (Pie del documento)
// ============================================
$pdf->Ln(10);

$pdf->SetDrawColor(180, 180, 180);
$pdf->SetLineWidth(0.2);
$pdf->Rect(10, $pdf->GetY(), 190, 25);

$pdf->SetFont('Arial', 'I', 8);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 5, '', 0, 1);
$pdf->Cell(0, 5, 'Este documento es una representacion digital del reporte de operacion.', 0, 1, 'C');
$pdf->Cell(0, 5, 'Generado automaticamente por OperaSys el ' . date('d/m/Y H:i:s'), 0, 1, 'C');
$pdf->Cell(0, 5, 'Codigo de verificacion: ' . strtoupper(md5($reporte['id'] . $reporte['fecha'])), 0, 1, 'C');

// ============================================
// SALIDA DEL PDF
// ============================================
$nombreArchivo = 'Reporte_' . str_pad($reporte['id'], 6, '0', STR_PAD_LEFT) . '_' . date('Ymd', strtotime($reporte['fecha'])) . '.pdf';

$pdf->Output('D', $nombreArchivo);

// Registrar en auditoría
try {
    $stmtAudit = $pdo->prepare("
        INSERT INTO auditoria (usuario_id, accion, detalle) 
        VALUES (?, 'descargar_pdf', ?)
    ");
    $stmtAudit->execute([$userId, "Reporte ID: $reporteId"]);
} catch (PDOException $e) {
    // Error silencioso en auditoría
}
