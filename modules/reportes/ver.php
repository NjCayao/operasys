<?php
/**
 * OperaSys - Ver Detalle de Reporte
 * Archivo: modules/reportes/ver.php
 * Versión: 3.0 - Sistema HT/HP (SIN partidas)
 * Descripción: Vista completa del reporte con actividades HT/HP y combustible
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

verificarSesion();

$reporteId = $_GET['id'] ?? 0;

if (!$reporteId) {
    header('Location: listar.php');
    exit;
}

// Obtener datos del reporte
try {
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            u.nombre_completo as operador,
            u.dni as operador_dni,
            u.cargo as operador_cargo,
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
        header('Location: listar.php?error=reporte_no_encontrado');
        exit;
    }
    
    if ($reporte['usuario_id'] != $_SESSION['user_id'] && 
        $_SESSION['rol'] !== 'admin' && 
        $_SESSION['rol'] !== 'supervisor') {
        header('Location: listar.php?error=sin_permisos');
        exit;
    }
    
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
        ORDER BY fecha_hora ASC
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
    
    // Calcular consumo estimado
    $consumoEstimado = $reporte['horas_motor'] * $reporte['consumo_promedio_hr'];
    $diferenciaCombustible = $totalGalones - $consumoEstimado;
    
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}

$page_title = 'Reporte #' . $reporteId;
$page_depth = 2;
$use_sweetalert = true;

include '../../layouts/header.php';
include '../../layouts/navbar.php';
include '../../layouts/sidebar.php';
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                        <i class="fas fa-file-alt"></i> Reporte #<?php echo $reporte['id']; ?>
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="listar.php">Reportes</a></li>
                        <li class="breadcrumb-item active">Ver</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            
            <!-- Botones de acción -->
            <div class="mb-3">
                <a href="listar.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                <?php if ($reporte['estado'] === 'borrador' && $reporte['usuario_id'] == $_SESSION['user_id']): ?>
                <a href="editar.php?id=<?php echo $reporte['id']; ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <?php endif; ?>
                <a href="../../api/pdf.php?id=<?php echo $reporte['id']; ?>" 
                   class="btn btn-danger" 
                   target="_blank">
                    <i class="fas fa-file-pdf"></i> Descargar PDF
                </a>
            </div>

            <div class="row">
                <div class="col-12">
                    
                    <!-- Info General -->
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle"></i> Información General
                            </h3>
                            <div class="card-tools">
                                <?php if ($reporte['estado'] === 'finalizado'): ?>
                                    <span class="badge badge-success">
                                        <i class="fas fa-check"></i> Finalizado
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-warning">
                                        <i class="fas fa-edit"></i> Borrador
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <dl class="row">
                                        <dt class="col-sm-5">Fecha:</dt>
                                        <dd class="col-sm-7">
                                            <?php echo date('d/m/Y', strtotime($reporte['fecha'])); ?>
                                        </dd>
                                        
                                        <dt class="col-sm-5">Operador:</dt>
                                        <dd class="col-sm-7">
                                            <?php echo htmlspecialchars($reporte['operador']); ?>
                                        </dd>
                                        
                                        <dt class="col-sm-5">DNI:</dt>
                                        <dd class="col-sm-7">
                                            <?php echo htmlspecialchars($reporte['operador_dni']); ?>
                                        </dd>
                                        
                                        <dt class="col-sm-5">Cargo:</dt>
                                        <dd class="col-sm-7">
                                            <?php echo htmlspecialchars($reporte['operador_cargo']); ?>
                                        </dd>
                                    </dl>
                                </div>
                                <div class="col-md-6">
                                    <dl class="row">
                                        <dt class="col-sm-5">Equipo:</dt>
                                        <dd class="col-sm-7">
                                            <strong><?php echo htmlspecialchars($reporte['equipo_categoria']); ?></strong><br>
                                            <?php echo htmlspecialchars($reporte['equipo_codigo']); ?>
                                        </dd>
                                        
                                        <dt class="col-sm-5">Horómetro Inicial:</dt>
                                        <dd class="col-sm-7">
                                            <strong><?php echo number_format($reporte['horometro_inicial'], 1); ?></strong>
                                        </dd>
                                        
                                        <dt class="col-sm-5">Horómetro Final:</dt>
                                        <dd class="col-sm-7">
                                            <strong><?php echo number_format($reporte['horometro_final'], 1); ?></strong>
                                        </dd>
                                        
                                        <dt class="col-sm-5">Horas Motor:</dt>
                                        <dd class="col-sm-7">
                                            <strong class="text-primary"><?php echo number_format($reporte['horas_motor'], 2); ?> hrs</strong>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen HT/HP -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3><?php echo number_format($totalHT, 2); ?> hrs</h3>
                                    <p>Total Horas Trabajadas (HT)</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-tools"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3><?php echo number_format($totalHP, 2); ?> hrs</h3>
                                    <p>Total Horas Paradas (HP)</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-pause-circle"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3><?php echo number_format($eficiencia, 1); ?>%</h3>
                                    <p>Eficiencia (HT/Horas Motor)</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actividades HT -->
                    <div class="card card-success">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-tools"></i> Horas Trabajadas (HT)
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($actividadesHT)): ?>
                                <p class="text-muted text-center">No hay horas trabajadas registradas</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th width="5%">#</th>
                                                <th width="12%">Hora Inicio</th>
                                                <th width="12%">Hora Fin</th>
                                                <th width="10%">Horas</th>
                                                <th>Actividad</th>
                                                <th>Observaciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($actividadesHT as $index => $act): ?>
                                            <tr>
                                                <td class="text-center"><?php echo $index + 1; ?></td>
                                                <td class="text-center"><?php echo $act['hora_inicio']; ?></td>
                                                <td class="text-center"><?php echo $act['hora_fin']; ?></td>
                                                <td class="text-center">
                                                    <span class="badge badge-success">
                                                        <?php echo number_format($act['horas_transcurridas'], 2); ?> hrs
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($act['actividad_codigo']): ?>
                                                        <strong><?php echo htmlspecialchars($act['actividad_codigo']); ?></strong> - 
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($act['actividad_nombre']); ?>
                                                </td>
                                                <td>
                                                    <?php echo $act['observaciones'] ? htmlspecialchars($act['observaciones']) : '<em class="text-muted">-</em>'; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="font-weight-bold bg-light">
                                                <td colspan="3" class="text-right">TOTAL HT:</td>
                                                <td class="text-center">
                                                    <span class="badge badge-success">
                                                        <?php echo number_format($totalHT, 2); ?> hrs
                                                    </span>
                                                </td>
                                                <td colspan="2"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Actividades HP -->
                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-pause-circle"></i> Horas Paradas (HP)
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($actividadesHP)): ?>
                                <p class="text-muted text-center">No hay horas paradas registradas</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th width="5%">#</th>
                                                <th width="12%">Hora Inicio</th>
                                                <th width="12%">Hora Fin</th>
                                                <th width="10%">Horas</th>
                                                <th>Motivo</th>
                                                <th width="15%">Categoría</th>
                                                <th>Observaciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($actividadesHP as $index => $act): ?>
                                            <tr>
                                                <td class="text-center"><?php echo $index + 1; ?></td>
                                                <td class="text-center"><?php echo $act['hora_inicio']; ?></td>
                                                <td class="text-center"><?php echo $act['hora_fin']; ?></td>
                                                <td class="text-center">
                                                    <span class="badge badge-warning">
                                                        <?php echo number_format($act['horas_transcurridas'], 2); ?> hrs
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($act['motivo_codigo']): ?>
                                                        <strong><?php echo htmlspecialchars($act['motivo_codigo']); ?></strong> - 
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($act['motivo_nombre']); ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-secondary">
                                                        <?php echo ucfirst($act['categoria_parada']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo $act['observaciones'] ? htmlspecialchars($act['observaciones']) : '<em class="text-muted">-</em>'; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="font-weight-bold bg-light">
                                                <td colspan="3" class="text-right">TOTAL HP:</td>
                                                <td class="text-center">
                                                    <span class="badge badge-warning">
                                                        <?php echo number_format($totalHP, 2); ?> hrs
                                                    </span>
                                                </td>
                                                <td colspan="3"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Combustible -->
                    <?php if (!empty($combustibles)): ?>
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-gas-pump"></i> Control de Combustible
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="info-box bg-light">
                                        <span class="info-box-icon"><i class="fas fa-calculator"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Consumo Estimado</span>
                                            <span class="info-box-number"><?php echo number_format($consumoEstimado, 2); ?> gal</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-box bg-info">
                                        <span class="info-box-icon"><i class="fas fa-gas-pump"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total Abastecido</span>
                                            <span class="info-box-number"><?php echo number_format($totalGalones, 2); ?> gal</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-box <?php echo $diferenciaCombustible >= 0 ? 'bg-success' : 'bg-danger'; ?>">
                                        <span class="info-box-icon"><i class="fas fa-<?php echo $diferenciaCombustible >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Diferencia</span>
                                            <span class="info-box-number"><?php echo number_format($diferenciaCombustible, 2); ?> gal</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th width="10%">#</th>
                                            <th>Horómetro</th>
                                            <th>Hora</th>
                                            <th>Galones</th>
                                            <th>Observaciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($combustibles as $index => $comb): ?>
                                        <tr>
                                            <td class="text-center"><?php echo $index + 1; ?></td>
                                            <td class="text-center"><?php echo number_format($comb['horometro'], 1); ?></td>
                                            <td class="text-center"><?php echo $comb['hora_abastecimiento']; ?></td>
                                            <td class="text-center">
                                                <strong><?php echo number_format($comb['galones'], 2); ?></strong> gal
                                            </td>
                                            <td>
                                                <?php echo $comb['observaciones'] ? htmlspecialchars($comb['observaciones']) : '<em class="text-muted">-</em>'; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Observaciones Generales -->
                    <?php if (!empty($reporte['observaciones_generales'])): ?>
                    <div class="card card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-comment"></i> Observaciones Generales
                            </h3>
                        </div>
                        <div class="card-body">
                            <p class="text-justify">
                                <?php echo nl2br(htmlspecialchars($reporte['observaciones_generales'])); ?>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    </section>
</div>

<?php
include '../../layouts/footer.php';
?>