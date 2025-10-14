<?php

/**
 * OperaSys - Generador de PDF
 * Archivo: api/pdf.php
 * Descripción: Genera PDF de reportes finalizados
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

// Configurar headers para PDF
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte #<?php echo $reporte['id']; ?> - <?php echo date('d/m/Y', strtotime($reporte['fecha'])); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #2E86AB;
            padding-bottom: 15px;
        }

        .header h1 {
            color: #2E86AB;
            font-size: 24pt;
            margin-bottom: 5px;
        }

        .header h2 {
            color: #666;
            font-size: 14pt;
            font-weight: normal;
        }

        .info-section {
            margin-bottom: 20px;
        }

        .info-section h3 {
            background-color: #2E86AB;
            color: white;
            padding: 8px 12px;
            font-size: 12pt;
            margin-bottom: 10px;
        }

        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .info-row {
            display: table-row;
        }

        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 30%;
            padding: 5px 10px;
            border-bottom: 1px solid #ddd;
        }

        .info-value {
            display: table-cell;
            padding: 5px 10px;
            border-bottom: 1px solid #ddd;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table th {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 10pt;
        }

        table td {
            border: 1px solid #ddd;
            padding: 8px;
            font-size: 10pt;
        }

        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .total-row {
            background-color: #e3f2fd !important;
            font-weight: bold;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 9pt;
            font-weight: bold;
        }

        .badge-success {
            background-color: #28a745;
            color: white;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #333;
        }

        .badge-info {
            background-color: #17a2b8;
            color: white;
        }

        .observaciones {
            border: 1px solid #ddd;
            padding: 12px;
            background-color: #f9f9f9;
            margin-top: 10px;
            white-space: pre-wrap;
        }

        .firma-section {
            margin-top: 40px;
            text-align: center;
        }

        .firma-img {
            max-width: 200px;
            max-height: 80px;
            border: 1px solid #ddd;
            padding: 5px;
            margin-bottom: 5px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9pt;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        @media print {
            body {
                padding: 10px;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>

    <!-- Header -->
    <div class="header">
        <h1>OperaSys</h1>
        <h2>Reporte Diario de Operaciones</h2>
    </div>

    <!-- Información General -->
    <div class="info-section">
        <h3>📋 Información del Reporte</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 33.33%; padding: 8px; border: 1px solid #ddd; vertical-align: top;">
                    <strong style="color: #2E86AB;">📋 General</strong><br>
                    <strong>Reporte N°:</strong> #<?php echo str_pad($reporte['id'], 4, '0', STR_PAD_LEFT); ?><br>
                    <strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($reporte['fecha'])); ?><br>
                    <strong>Estado:</strong>
                    <?php if ($reporte['estado'] === 'finalizado'): ?>
                        <span class="badge badge-success">Finalizado</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Borrador</span>
                    <?php endif; ?>
                </td>
                <td style="width: 33.33%; padding: 8px; border: 1px solid #ddd; vertical-align: top;">
                    <strong style="color: #2E86AB;">👤 Operador</strong><br>
                    <strong>Nombre:</strong> <?php echo htmlspecialchars($reporte['operador']); ?><br>
                    <strong>DNI:</strong> <?php echo htmlspecialchars($reporte['operador_dni']); ?><br>
                    <strong>Cargo:</strong> <?php echo htmlspecialchars($reporte['operador_cargo']); ?>
                </td>
                <td style="width: 33.33%; padding: 8px; border: 1px solid #ddd; vertical-align: top;">
                    <strong style="color: #2E86AB;">🚜 Equipo</strong><br>
                    <strong>Categoría:</strong> <?php echo htmlspecialchars($reporte['equipo_categoria']); ?><br>
                    <strong>Código:</strong> <?php echo htmlspecialchars($reporte['equipo_codigo']); ?>
                    <?php if ($reporte['equipo_descripcion']): ?>
                        <br><strong>Descripción:</strong> <?php echo htmlspecialchars($reporte['equipo_descripcion']); ?>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>

    <!-- Actividades -->
    <div class="info-section">
        <h3>📊 Actividades Realizadas</h3>
        <?php if (empty($actividades)): ?>
            <p style="text-align: center; color: #999;">No hay actividades registradas</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="20%">Tipo de Trabajo</th>
                        <th width="25%">Fase de Costo</th>
                        <th width="12%" class="text-center">H. Inicial</th>
                        <th width="12%" class="text-center">H. Final</th>
                        <th width="10%" class="text-center">Horas</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($actividades as $index => $act): ?>
                        <tr>
                            <td class="text-center"><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($act['tipo_trabajo']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($act['fase_codigo']); ?></strong><br>
                                <small style="color: #666;"><?php echo htmlspecialchars($act['fase_descripcion']); ?></small>
                            </td>
                            <td class="text-center"><?php echo number_format($act['horometro_inicial'], 1); ?></td>
                            <td class="text-center"><?php echo number_format($act['horometro_final'], 1); ?></td>
                            <td class="text-center">
                                <span class="badge badge-info"><?php echo number_format($act['horas_trabajadas'], 2); ?> hrs</span>
                            </td>
                            <td><?php echo $act['observaciones'] ? htmlspecialchars($act['observaciones']) : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="5" class="text-right">TOTAL HORAS TRABAJADAS:</td>
                        <td class="text-center">
                            <span class="badge badge-success"><?php echo number_format($totalHoras, 2); ?> hrs</span>
                        </td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Combustible -->
    <?php if (!empty($combustibles)): ?>
        <div class="info-section">
            <h3>⛽ Abastecimiento de Combustible</h3>
            <table>
                <thead>
                    <tr>
                        <th width="8%">#</th>
                        <th width="18%" class="text-center">Horómetro</th>
                        <th width="18%" class="text-center">Galones</th>
                        <th width="25%">Fecha/Hora</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($combustibles as $index => $comb): ?>
                        <tr>
                            <td class="text-center"><?php echo $index + 1; ?></td>
                            <td class="text-center"><?php echo number_format($comb['horometro'], 1); ?></td>
                            <td class="text-center"><strong><?php echo number_format($comb['galones'], 2); ?></strong> gal</td>
                            <td><?php echo date('d/m/Y H:i', strtotime($comb['fecha_hora'])); ?></td>
                            <td><?php echo $comb['observaciones'] ? htmlspecialchars($comb['observaciones']) : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="2" class="text-right">TOTAL GALONES:</td>
                        <td class="text-center">
                            <span class="badge badge-info"><?php echo number_format($totalGalones, 2); ?> gal</span>
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Observaciones Generales -->
    <?php if (!empty($reporte['observaciones_generales'])): ?>
        <div class="info-section">
            <h3>💬 Observaciones Generales</h3>
            <div class="observaciones">
                <?php echo nl2br(htmlspecialchars($reporte['observaciones_generales'])); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Firma del Operador -->
    <?php if ($reporte['operador_firma']): ?>
        <div class="firma-section">
            <p><strong>Firma del Operador:</strong></p>
            <img src="<?php echo $reporte['operador_firma']; ?>" alt="Firma" class="firma-img">
            <p><?php echo htmlspecialchars($reporte['operador']); ?></p>
            <p style="font-size: 9pt; color: #666;">DNI: <?php echo htmlspecialchars($reporte['operador_dni']); ?></p>
        </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="footer">
        <p><strong>OperaSys</strong> - Sistema de Reportes de Operaciones</p>
        <p>Generado el: <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>

    <!-- Botones de acción (no se imprimen) -->
    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background-color: #2E86AB; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 12pt;">
            🖨️ Imprimir / Guardar PDF
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background-color: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 12pt; margin-left: 10px;">
            ❌ Cerrar
        </button>
    </div>

</body>

</html>