<?php

/**
 * OperaSys - Generador de PDF con FPDF
 * Archivo: api/pdf.php
 * Descripción: Genera PDF de reportes finalizados con FPDF
 */

require_once '../config/database.php';
require_once '../config/config.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    die('Sesión no válida. <a href="../modules/auth/login.php">Ir al login</a>');
}

$reporteId = $_GET['id'] ?? 0;

if (!$reporteId) {
    die('ID de reporte no válido');
}

// Verificar si FPDF existe
$fpdfPath = __DIR__ . '/../vendor/fpdf/fpdf.php';
if (!file_exists($fpdfPath)) {
    die('FPDF no está instalado. Descárgalo de http://www.fpdf.org/ y colócalo en vendor/fpdf/fpdf.php');
}

require_once $fpdfPath;

try {
    // Obtener datos del reporte
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            u.nombre_completo as operador,
            u.dni as operador_dni,
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

    if (!$reporte) {
        die('Reporte no encontrado');
    }

    // Verificar permisos
    $userRol = $_SESSION['rol'];
    $userId = $_SESSION['user_id'];

    if ($reporte['usuario_id'] != $userId && $userRol !== 'admin' && $userRol !== 'supervisor') {
        die('No tiene permisos para ver este reporte');
    }

    // Obtener configuración de empresa
    $stmtEmpresa = $pdo->query("SELECT * FROM configuracion_empresa WHERE id = 1");
    $empresa = $stmtEmpresa->fetch();

    // Obtener actividades
    $stmtActividades = $pdo->prepare("
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
    $stmtActividades->execute([$reporteId]);
    $actividades = $stmtActividades->fetchAll();

    // Obtener combustibles
    $stmtCombustible = $pdo->prepare("
        SELECT * FROM reportes_combustible 
        WHERE reporte_id = ?
        ORDER BY fecha_hora ASC
    ");
    $stmtCombustible->execute([$reporteId]);
    $combustibles = $stmtCombustible->fetchAll();

    // Calcular totales
    $totalHoras = 0;
    $totalGalones = 0;
    foreach ($actividades as $act) {
        $totalHoras += $act['horas_trabajadas'];
    }
    foreach ($combustibles as $comb) {
        $totalGalones += $comb['galones'];
    }
} catch (PDOException $e) {
    die('Error al obtener datos: ' . $e->getMessage());
}

// ========== CLASE PDF PERSONALIZADA ==========
class PDF extends FPDF
{
    private $empresa;
    private $reporte;

    function __construct($empresa, $reporte)
    {
        parent::__construct('P', 'mm', 'A4');
        $this->empresa = $empresa;
        $this->reporte = $reporte;
    }

    // Encabezado
    function Header()
    {
        // ===== ENCABEZADO FIJO - OBLIGATORIO =====

        // Título principal FIJO
        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor(46, 134, 171);
        $this->Cell(0, 8, 'OperaSys', 0, 1, 'C');

        // Subtítulo FIJO
        $this->SetFont('Arial', '', 12);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 6, utf8_decode('Reporte Diario de Operaciones'), 0, 1, 'C');

        $this->Ln(2);

        // Línea separadora
        $this->SetDrawColor(46, 134, 171);
        $this->SetLineWidth(0.5);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(3);

        // ===== LOGO (Si existe) - En esquina superior derecha =====
        if (!empty($this->empresa['logo'])) {
            $logoData = $this->empresa['logo'];
            if (preg_match('/^data:image\/(png|jpeg|jpg);base64,(.+)$/', $logoData, $matches)) {
                $ext = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
                $logoDecoded = base64_decode($matches[2]);

                if ($logoDecoded !== false && strlen($logoDecoded) > 0) {
                    $tmpFile = sys_get_temp_dir() . '/logo_operasys_' . time() . '.' . $ext;

                    if (file_put_contents($tmpFile, $logoDecoded)) {
                        try {
                            // Verificar que el archivo existe y es válido
                            if (file_exists($tmpFile) && filesize($tmpFile) > 0) {
                                $this->Image($tmpFile, 170, 8, 30);
                            }
                        } catch (Exception $e) {
                            // Si falla el logo, continuar sin él
                        }
                        @unlink($tmpFile);
                    }
                }
            }
        }

        // ===== DATOS DE EMPRESA (Opcionales) =====

        // Nombre de empresa (si existe)
        if (!empty($this->empresa['nombre_empresa'])) {
            $this->SetFont('Arial', 'B', 10);
            $this->SetTextColor(0, 0, 0);
            $this->Cell(0, 5, utf8_decode($this->empresa['nombre_empresa']), 0, 1, 'C');
        }

        // Datos adicionales (si existen)
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(100, 100, 100);

        $datosEmpresa = [];
        if (!empty($this->empresa['ruc_nit'])) {
            $datosEmpresa[] = 'RUC: ' . $this->empresa['ruc_nit'];
        }
        if (!empty($this->empresa['direccion'])) {
            $datosEmpresa[] = $this->empresa['direccion'];
        }
        if (!empty($this->empresa['telefono'])) {
            $datosEmpresa[] = 'Tel: ' . $this->empresa['telefono'];
        }
        if (!empty($this->empresa['email'])) {
            $datosEmpresa[] = $this->empresa['email'];
        }

        if (!empty($datosEmpresa)) {
            $this->Cell(0, 4, utf8_decode(implode(' | ', $datosEmpresa)), 0, 1, 'C');
        }

        $this->SetTextColor(0, 0, 0);
        $this->Ln(3);
    }

    // Pie de página
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 5, utf8_decode('Generado el ' . date('d/m/Y H:i:s') . ' | Página ' . $this->PageNo()), 0, 0, 'C');
    }

    // Sección de información compacta en 3 columnas
    function InfoSection()
    {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(230, 230, 230);

        $colWidth = 63.33;

        // Columna 1: General
        $this->Cell($colWidth, 6, utf8_decode('GENERAL'), 1, 0, 'C', true);
        $this->Cell($colWidth, 6, utf8_decode('OPERADOR'), 1, 0, 'C', true);
        $this->Cell($colWidth, 6, utf8_decode('EQUIPO'), 1, 1, 'C', true);

        $this->SetFont('Arial', '', 8);

        // Fila 1
        $x = $this->GetX();
        $y = $this->GetY();

        $this->MultiCell($colWidth, 4, utf8_decode(
            "Reporte No: #" . str_pad($this->reporte['id'], 4, '0', STR_PAD_LEFT) . "\n" .
                "Fecha: " . date('d/m/Y', strtotime($this->reporte['fecha'])) . "\n" .
                "Estado: " . ucfirst($this->reporte['estado'])
        ), 1, 'L');

        $y2 = $this->GetY();
        $this->SetXY($x + $colWidth, $y);

        $this->MultiCell($colWidth, 4, utf8_decode(
            "Nombre: " . $this->reporte['operador'] . "\n" .
                "DNI: " . $this->reporte['operador_dni'] . "\n" .
                "Cargo: " . $this->reporte['operador_cargo']
        ), 1, 'L');

        $y3 = $this->GetY();
        $this->SetXY($x + $colWidth * 2, $y);

        $equipo_desc = !empty($this->reporte['equipo_descripcion'])
            ? "\nDesc: " . substr($this->reporte['equipo_descripcion'], 0, 30)
            : '';

        $this->MultiCell($colWidth, 4, utf8_decode(
            "Categoria: " . $this->reporte['equipo_categoria'] . "\n" .
                "Codigo: " . $this->reporte['equipo_codigo'] .
                $equipo_desc
        ), 1, 'L');

        $maxY = max($y2, $y3, $this->GetY());
        $this->SetY($maxY);
        $this->Ln(3);
    }

    // Tabla de actividades
    function TablaActividades($actividades, $totalHoras)
    {
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(46, 134, 171);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 6, utf8_decode('ACTIVIDADES REALIZADAS'), 0, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(1);

        if (empty($actividades)) {
            $this->SetFont('Arial', 'I', 9);
            $this->Cell(0, 6, utf8_decode('No hay actividades registradas'), 1, 1, 'C');
            return;
        }

        // Encabezados de tabla
        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(240, 240, 240);
        $this->Cell(8, 6, utf8_decode('#'), 1, 0, 'C', true);
        $this->Cell(35, 6, utf8_decode('Tipo Trabajo'), 1, 0, 'C', true);
        $this->Cell(40, 6, utf8_decode('Fase Costo'), 1, 0, 'C', true);
        $this->Cell(20, 6, utf8_decode('H. Inicial'), 1, 0, 'C', true);
        $this->Cell(20, 6, utf8_decode('H. Final'), 1, 0, 'C', true);
        $this->Cell(18, 6, utf8_decode('Horas'), 1, 0, 'C', true);
        $this->Cell(49, 6, utf8_decode('Observaciones'), 1, 1, 'C', true);

        // Datos
        $this->SetFont('Arial', '', 7);
        foreach ($actividades as $index => $act) {
            $this->Cell(8, 5, $index + 1, 1, 0, 'C');
            $this->Cell(35, 5, utf8_decode(substr($act['tipo_trabajo'], 0, 25)), 1, 0, 'L');
            $this->Cell(40, 5, utf8_decode($act['fase_codigo'] . ' - ' . substr($act['fase_descripcion'], 0, 15)), 1, 0, 'L');
            $this->Cell(20, 5, number_format($act['horometro_inicial'], 1), 1, 0, 'C');
            $this->Cell(20, 5, number_format($act['horometro_final'], 1), 1, 0, 'C');
            $this->Cell(18, 5, number_format($act['horas_trabajadas'], 2), 1, 0, 'C');
            $this->Cell(49, 5, utf8_decode(substr($act['observaciones'] ?? '-', 0, 35)), 1, 1, 'L');
        }

        // Total
        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(227, 242, 253);
        $this->Cell(123, 5, utf8_decode('TOTAL HORAS TRABAJADAS:'), 1, 0, 'R', true);
        $this->Cell(18, 5, number_format($totalHoras, 2), 1, 0, 'C', true);
        $this->Cell(49, 5, '', 1, 1, 'C', true);

        $this->Ln(3);
    }

    // Tabla de combustible
    function TablaCombustible($combustibles, $totalGalones)
    {
        if (empty($combustibles)) return;

        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(46, 134, 171);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 6, utf8_decode('ABASTECIMIENTO DE COMBUSTIBLE'), 0, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(1);

        // Encabezados
        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(240, 240, 240);
        $this->Cell(10, 6, utf8_decode('#'), 1, 0, 'C', true);
        $this->Cell(30, 6, utf8_decode('Horometro'), 1, 0, 'C', true);
        $this->Cell(30, 6, utf8_decode('Galones'), 1, 0, 'C', true);
        $this->Cell(50, 6, utf8_decode('Fecha/Hora'), 1, 0, 'C', true);
        $this->Cell(70, 6, utf8_decode('Observaciones'), 1, 1, 'C', true);

        // Datos
        $this->SetFont('Arial', '', 8);
        foreach ($combustibles as $index => $comb) {
            $this->Cell(10, 5, $index + 1, 1, 0, 'C');
            $this->Cell(30, 5, number_format($comb['horometro'], 1), 1, 0, 'C');
            $this->Cell(30, 5, number_format($comb['galones'], 2), 1, 0, 'C');
            $this->Cell(50, 5, date('d/m/Y H:i', strtotime($comb['fecha_hora'])), 1, 0, 'C');
            $this->Cell(70, 5, utf8_decode(substr($comb['observaciones'] ?? '-', 0, 50)), 1, 1, 'L');
        }

        // Total
        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(227, 242, 253);
        $this->Cell(40, 5, utf8_decode('TOTAL GALONES:'), 1, 0, 'R', true);
        $this->Cell(30, 5, number_format($totalGalones, 2), 1, 0, 'C', true);
        $this->Cell(120, 5, '', 1, 1, 'C', true);

        $this->Ln(3);
    }

    // Observaciones generales
    function ObservacionesGenerales($observaciones)
    {
        if (empty($observaciones)) return;

        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(46, 134, 171);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 6, utf8_decode('OBSERVACIONES GENERALES'), 0, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(1);

        $this->SetFont('Arial', '', 9);
        $this->SetFillColor(249, 249, 249);
        $this->MultiCell(0, 5, utf8_decode($observaciones), 1, 'J', true);
        $this->Ln(3);
    }

    // Firma del operador
    function Firma($firma, $operador, $dni)
    {
        if (empty($firma)) return;

        $this->Ln(5);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(0, 5, utf8_decode('FIRMA DEL OPERADOR'), 0, 1, 'C');

        // Convertir firma base64 a imagen
        if (preg_match('/^data:image\/(png|jpeg|jpg);base64,(.+)$/', $firma, $matches)) {
            $ext = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
            $firmaDecoded = base64_decode($matches[2]);
            $tmpFile = sys_get_temp_dir() . '/firma_tmp.' . $ext;
            file_put_contents($tmpFile, $firmaDecoded);

            // Centrar firma
            $firmaWidth = 50;
            $x = ($this->GetPageWidth() - $firmaWidth) / 2;
            $this->Image($tmpFile, $x, $this->GetY(), $firmaWidth);
            @unlink($tmpFile);

            $this->Ln(20);
        }

        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 5, utf8_decode($operador), 0, 1, 'C');
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 4, utf8_decode('DNI: ' . $dni), 0, 1, 'C');
    }
}

// ========== GENERAR PDF ==========
$pdf = new PDF($empresa, $reporte);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();

// Información del reporte
$pdf->InfoSection();

// Actividades
$pdf->TablaActividades($actividades, $totalHoras);

// Combustible
$pdf->TablaCombustible($combustibles, $totalGalones);

// Observaciones
$pdf->ObservacionesGenerales($reporte['observaciones_generales']);

// Firma
$pdf->Firma($reporte['operador_firma'], $reporte['operador'], $reporte['operador_dni']);

// Salida del PDF
$filename = 'Reporte_' . str_pad($reporteId, 4, '0', STR_PAD_LEFT) . '_' . date('Ymd', strtotime($reporte['fecha'])) . '.pdf';
$pdf->Output('D', $filename);
