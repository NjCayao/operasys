<?php
/**
 * OperaSys - Generador de PDF con FPDF
 * Archivo: api/pdf.php
 * Versión: 3.0 - Sistema HT/HP (SIN partidas)
 * Descripción: Genera PDF de reportes finalizados con FPDF
 */

require_once '../config/database.php';
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    die('Sesión no válida. <a href="../modules/auth/login.php">Ir al login</a>');
}

$reporteId = $_GET['id'] ?? 0;

if (!$reporteId) {
    die('ID de reporte no válido');
}

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
            e.descripcion as equipo_descripcion,
            e.consumo_promedio_hr
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

    // Obtener actividades HT
    $stmtHT = $pdo->prepare("
        SELECT 
            rd.*,
            aht.codigo as actividad_codigo,
            aht.nombre as actividad_nombre
        FROM reportes_detalle rd
        INNER JOIN actividades_ht aht ON rd.actividad_ht_id = aht.id
        WHERE rd.reporte_id = ? AND rd.tipo_hora = 'HT'
        ORDER BY rd.orden ASC
    ");
    $stmtHT->execute([$reporteId]);
    $actividadesHT = $stmtHT->fetchAll();

    // Obtener actividades HP
    $stmtHP = $pdo->prepare("
        SELECT 
            rd.*,
            mhp.codigo as motivo_codigo,
            mhp.nombre as motivo_nombre,
            mhp.categoria_parada
        FROM reportes_detalle rd
        INNER JOIN motivos_hp mhp ON rd.motivo_hp_id = mhp.id
        WHERE rd.reporte_id = ? AND rd.tipo_hora = 'HP'
        ORDER BY rd.orden ASC
    ");
    $stmtHP->execute([$reporteId]);
    $actividadesHP = $stmtHP->fetchAll();

    // Obtener combustibles
    $stmtCombustible = $pdo->prepare("
        SELECT * FROM reportes_combustible 
        WHERE reporte_id = ?
        ORDER BY hora_abastecimiento ASC
    ");
    $stmtCombustible->execute([$reporteId]);
    $combustibles = $stmtCombustible->fetchAll();

    // Calcular totales
    $totalHT = 0;
    $totalHP = 0;
    $totalGalones = 0;
    
    foreach ($actividadesHT as $act) {
        $totalHT += $act['horas_transcurridas'];
    }
    foreach ($actividadesHP as $act) {
        $totalHP += $act['horas_transcurridas'];
    }
    foreach ($combustibles as $comb) {
        $totalGalones += $comb['galones'];
    }

    // Calcular eficiencia
    $eficiencia = 0;
    if ($reporte['horas_motor'] > 0) {
        $eficiencia = ($totalHT / $reporte['horas_motor']) * 100;
    }

    // Calcular consumo
    $consumoEstimado = $reporte['horas_motor'] * $reporte['consumo_promedio_hr'];
    $diferenciaCombustible = $totalGalones - $consumoEstimado;

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

    function Header()
    {
        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor(46, 134, 171);
        $this->Cell(0, 8, 'OperaSys - Sistema HT/HP', 0, 1, 'C');

        $this->SetFont('Arial', '', 12);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 6, utf8_decode('Reporte Diario de Operaciones'), 0, 1, 'C');

        $this->Ln(2);

        $this->SetDrawColor(46, 134, 171);
        $this->SetLineWidth(0.5);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(3);

        // Logo
        if (!empty($this->empresa['logo'])) {
            $logoData = $this->empresa['logo'];
            if (preg_match('/^data:image\/(png|jpeg|jpg);base64,(.+)$/', $logoData, $matches)) {
                $ext = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
                $logoDecoded = base64_decode($matches[2]);

                if ($logoDecoded !== false && strlen($logoDecoded) > 0) {
                    $tmpFile = sys_get_temp_dir() . '/logo_operasys_' . time() . '.' . $ext;

                    if (file_put_contents($tmpFile, $logoDecoded)) {
                        try {
                            if (file_exists($tmpFile) && filesize($tmpFile) > 0) {
                                $this->Image($tmpFile, 170, 8, 30);
                            }
                        } catch (Exception $e) {
                        }
                        @unlink($tmpFile);
                    }
                }
            }
        }

        if (!empty($this->empresa['nombre_empresa'])) {
            $this->SetFont('Arial', 'B', 10);
            $this->SetTextColor(0, 0, 0);
            $this->Cell(0, 5, utf8_decode($this->empresa['nombre_empresa']), 0, 1, 'C');
        }

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

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 5, utf8_decode('Generado el ' . date('d/m/Y H:i:s') . ' | Página ' . $this->PageNo()), 0, 0, 'C');
    }

    function InfoSection()
    {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(230, 230, 230);

        $colWidth = 63.33;

        $this->Cell($colWidth, 6, utf8_decode('GENERAL'), 1, 0, 'C', true);
        $this->Cell($colWidth, 6, utf8_decode('OPERADOR'), 1, 0, 'C', true);
        $this->Cell($colWidth, 6, utf8_decode('EQUIPO'), 1, 1, 'C', true);

        $this->SetFont('Arial', '', 8);

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

    function HorometrosSection()
    {
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(46, 134, 171);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 6, utf8_decode('HORÓMETROS Y HORAS MOTOR'), 0, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(1);

        $this->SetFont('Arial', '', 9);
        $this->SetFillColor(249, 249, 249);
        
        $colWidth = 63.33;
        
        $this->Cell($colWidth, 6, utf8_decode('Horómetro Inicial'), 1, 0, 'L', true);
        $this->Cell($colWidth, 6, utf8_decode('Horómetro Final'), 1, 0, 'L', true);
        $this->Cell($colWidth, 6, utf8_decode('Horas Motor'), 1, 1, 'L', true);
        
        $this->SetFont('Arial', 'B', 10);
        $this->Cell($colWidth, 6, number_format($this->reporte['horometro_inicial'], 1), 1, 0, 'C');
        $this->Cell($colWidth, 6, number_format($this->reporte['horometro_final'], 1), 1, 0, 'C');
        $this->Cell($colWidth, 6, number_format($this->reporte['horas_motor'], 2) . ' hrs', 1, 1, 'C');
        
        $this->Ln(3);
    }

    function TablaHT($actividadesHT, $totalHT, $eficiencia)
    {
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(40, 167, 69);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 6, utf8_decode('HORAS TRABAJADAS (HT) - Eficiencia: ' . number_format($eficiencia, 1) . '%'), 0, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(1);

        if (empty($actividadesHT)) {
            $this->SetFont('Arial', 'I', 9);
            $this->Cell(0, 6, utf8_decode('No hay horas trabajadas registradas'), 1, 1, 'C');
            return;
        }

        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(240, 240, 240);
        $this->Cell(8, 6, utf8_decode('#'), 1, 0, 'C', true);
        $this->Cell(25, 6, utf8_decode('Hora Inicio'), 1, 0, 'C', true);
        $this->Cell(25, 6, utf8_decode('Hora Fin'), 1, 0, 'C', true);
        $this->Cell(18, 6, utf8_decode('Horas'), 1, 0, 'C', true);
        $this->Cell(60, 6, utf8_decode('Actividad'), 1, 0, 'C', true);
        $this->Cell(54, 6, utf8_decode('Observaciones'), 1, 1, 'C', true);

        $this->SetFont('Arial', '', 7);
        foreach ($actividadesHT as $index => $act) {
            $actividad = !empty($act['actividad_codigo']) 
                ? $act['actividad_codigo'] . ' - ' . $act['actividad_nombre']
                : $act['actividad_nombre'];
            
            $this->Cell(8, 5, $index + 1, 1, 0, 'C');
            $this->Cell(25, 5, $act['hora_inicio'], 1, 0, 'C');
            $this->Cell(25, 5, $act['hora_fin'], 1, 0, 'C');
            $this->Cell(18, 5, number_format($act['horas_transcurridas'], 2), 1, 0, 'C');
            $this->Cell(60, 5, utf8_decode(substr($actividad, 0, 40)), 1, 0, 'L');
            $this->Cell(54, 5, utf8_decode(substr($act['observaciones'] ?? '-', 0, 30)), 1, 1, 'L');
        }

        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(212, 237, 218);
        $this->Cell(76, 5, utf8_decode('TOTAL HT:'), 1, 0, 'R', true);
        $this->Cell(18, 5, number_format($totalHT, 2) . ' hrs', 1, 0, 'C', true);
        $this->Cell(96, 5, '', 1, 1, 'C', true);

        $this->Ln(3);
    }

    function TablaHP($actividadesHP, $totalHP)
    {
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(255, 193, 7);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 6, utf8_decode('HORAS PARADAS (HP)'), 0, 1, 'L', true);
        $this->Ln(1);

        if (empty($actividadesHP)) {
            $this->SetFont('Arial', 'I', 9);
            $this->Cell(0, 6, utf8_decode('No hay horas paradas registradas'), 1, 1, 'C');
            $this->Ln(3);
            return;
        }

        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(240, 240, 240);
        $this->Cell(8, 6, utf8_decode('#'), 1, 0, 'C', true);
        $this->Cell(25, 6, utf8_decode('Hora Inicio'), 1, 0, 'C', true);
        $this->Cell(25, 6, utf8_decode('Hora Fin'), 1, 0, 'C', true);
        $this->Cell(18, 6, utf8_decode('Horas'), 1, 0, 'C', true);
        $this->Cell(55, 6, utf8_decode('Motivo'), 1, 0, 'C', true);
        $this->Cell(30, 6, utf8_decode('Categoría'), 1, 0, 'C', true);
        $this->Cell(29, 6, utf8_decode('Observaciones'), 1, 1, 'C', true);

        $this->SetFont('Arial', '', 7);
        foreach ($actividadesHP as $index => $act) {
            $motivo = !empty($act['motivo_codigo']) 
                ? $act['motivo_codigo'] . ' - ' . $act['motivo_nombre']
                : $act['motivo_nombre'];
            
            $this->Cell(8, 5, $index + 1, 1, 0, 'C');
            $this->Cell(25, 5, $act['hora_inicio'], 1, 0, 'C');
            $this->Cell(25, 5, $act['hora_fin'], 1, 0, 'C');
            $this->Cell(18, 5, number_format($act['horas_transcurridas'], 2), 1, 0, 'C');
            $this->Cell(55, 5, utf8_decode(substr($motivo, 0, 35)), 1, 0, 'L');
            $this->Cell(30, 5, utf8_decode(ucfirst($act['categoria_parada'])), 1, 0, 'C');
            $this->Cell(29, 5, utf8_decode(substr($act['observaciones'] ?? '-', 0, 18)), 1, 1, 'L');
        }

        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(255, 243, 205);
        $this->Cell(76, 5, utf8_decode('TOTAL HP:'), 1, 0, 'R', true);
        $this->Cell(18, 5, number_format($totalHP, 2) . ' hrs', 1, 0, 'C', true);
        $this->Cell(96, 5, '', 1, 1, 'C', true);

        $this->Ln(3);
    }

    function TablaCombustible($combustibles, $totalGalones, $consumoEstimado, $diferencia)
    {
        if (empty($combustibles)) return;

        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(23, 162, 184);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 6, utf8_decode('CONTROL DE COMBUSTIBLE'), 0, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(1);

        // Resumen
        $this->SetFont('Arial', '', 9);
        $this->SetFillColor(249, 249, 249);
        
        $colWidth = 63.33;
        
        $this->Cell($colWidth, 6, utf8_decode('Consumo Estimado'), 1, 0, 'L', true);
        $this->Cell($colWidth, 6, utf8_decode('Total Abastecido'), 1, 0, 'L', true);
        $this->Cell($colWidth, 6, utf8_decode('Diferencia'), 1, 1, 'L', true);
        
        $this->SetFont('Arial', 'B', 9);
        $this->Cell($colWidth, 6, number_format($consumoEstimado, 2) . ' gal', 1, 0, 'C');
        $this->Cell($colWidth, 6, number_format($totalGalones, 2) . ' gal', 1, 0, 'C');
        
        $colorDif = $diferencia >= 0 ? array(212, 237, 218) : array(248, 215, 218);
        $this->SetFillColor($colorDif[0], $colorDif[1], $colorDif[2]);
        $signoDif = $diferencia >= 0 ? '+' : '';
        $this->Cell($colWidth, 6, $signoDif . number_format($diferencia, 2) . ' gal', 1, 1, 'C', true);
        
        $this->Ln(2);

        // Detalle
        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(240, 240, 240);
        $this->Cell(10, 6, utf8_decode('#'), 1, 0, 'C', true);
        $this->Cell(30, 6, utf8_decode('Horómetro'), 1, 0, 'C', true);
        $this->Cell(25, 6, utf8_decode('Hora'), 1, 0, 'C', true);
        $this->Cell(30, 6, utf8_decode('Galones'), 1, 0, 'C', true);
        $this->Cell(95, 6, utf8_decode('Observaciones'), 1, 1, 'C', true);

        $this->SetFont('Arial', '', 8);
        foreach ($combustibles as $index => $comb) {
            $this->Cell(10, 5, $index + 1, 1, 0, 'C');
            $this->Cell(30, 5, number_format($comb['horometro'], 1), 1, 0, 'C');
            $this->Cell(25, 5, $comb['hora_abastecimiento'], 1, 0, 'C');
            $this->Cell(30, 5, number_format($comb['galones'], 2), 1, 0, 'C');
            $this->Cell(95, 5, utf8_decode(substr($comb['observaciones'] ?? '-', 0, 65)), 1, 1, 'L');
        }

        $this->Ln(3);
    }

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

    function Firma($firma, $operador, $dni)
    {
        if (empty($firma)) return;

        $this->Ln(5);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(0, 5, utf8_decode('FIRMA DEL OPERADOR'), 0, 1, 'C');

        if (preg_match('/^data:image\/(png|jpeg|jpg);base64,(.+)$/', $firma, $matches)) {
            $ext = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
            $firmaDecoded = base64_decode($matches[2]);
            $tmpFile = sys_get_temp_dir() . '/firma_tmp.' . $ext;
            file_put_contents($tmpFile, $firmaDecoded);

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

// Horómetros
$pdf->HorometrosSection();

// Horas Trabajadas (HT)
$pdf->TablaHT($actividadesHT, $totalHT, $eficiencia);

// Horas Paradas (HP)
$pdf->TablaHP($actividadesHP, $totalHP);

// Combustible
$pdf->TablaCombustible($combustibles, $totalGalones, $consumoEstimado, $diferenciaCombustible);

// Observaciones
$pdf->ObservacionesGenerales($reporte['observaciones_generales']);

// Firma
$pdf->Firma($reporte['operador_firma'], $reporte['operador'], $reporte['operador_dni']);

// Salida del PDF
$filename = 'Reporte_' . str_pad($reporteId, 4, '0', STR_PAD_LEFT) . '_' . date('Ymd', strtotime($reporte['fecha'])) . '.pdf';
$pdf->Output('D', $filename);