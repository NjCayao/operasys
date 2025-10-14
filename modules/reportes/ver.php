<?php
/**
 * OperaSys - Ver Detalle de Reporte
 * Archivo: modules/reportes/ver.php
 * Descripción: Vista completa del reporte con actividades y combustible
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

verificarSesion();

// Obtener ID del reporte
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
            e.descripcion as equipo_descripcion
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
    
    // Verificar permisos
    if ($reporte['usuario_id'] != $_SESSION['user_id'] && 
        $_SESSION['rol'] !== 'admin' && 
        $_SESSION['rol'] !== 'supervisor') {
        header('Location: listar.php?error=sin_permisos');
        exit;
    }
    
    // Obtener actividades del reporte
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
    die('Error: ' . $e->getMessage());
}

// Variables para el layout
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

    <!-- Main content -->
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
                    
                    <!-- Info General del Reporte -->
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
                                        
                                        <dt class="col-sm-5">Total Actividades:</dt>
                                        <dd class="col-sm-7">
                                            <span class="badge badge-info"><?php echo count($actividades); ?></span>
                                        </dd>
                                        
                                        <dt class="col-sm-5">Horas Trabajadas:</dt>
                                        <dd class="col-sm-7">
                                            <strong><?php echo number_format($totalHoras, 1); ?> hrs</strong>
                                        </dd>
                                        
                                        <dt class="col-sm-5">Total Combustible:</dt>
                                        <dd class="col-sm-7">
                                            <strong><?php echo number_format($totalGalones, 2); ?> gal</strong>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actividades -->
                    <div class="card card-success">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-tasks"></i> Actividades Realizadas
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($actividades)): ?>
                                <p class="text-muted text-center">No hay actividades registradas</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th width="5%">#</th>
                                                <th>Tipo de Trabajo</th>
                                                <th>Fase de Costo</th>
                                                <th width="12%">Horómetro Inicial</th>
                                                <th width="12%">Horómetro Final</th>
                                                <th width="10%">Horas</th>
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
                                                    <small class="text-muted"><?php echo htmlspecialchars($act['fase_descripcion']); ?></small>
                                                </td>
                                                <td class="text-center"><?php echo number_format($act['horometro_inicial'], 1); ?></td>
                                                <td class="text-center"><?php echo number_format($act['horometro_final'], 1); ?></td>
                                                <td class="text-center">
                                                    <span class="badge badge-info">
                                                        <?php echo number_format($act['horas_trabajadas'], 2); ?> hrs
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo $act['observaciones'] ? htmlspecialchars($act['observaciones']) : '<em class="text-muted">Sin observaciones</em>'; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="font-weight-bold bg-light">
                                                <td colspan="5" class="text-right">TOTAL HORAS:</td>
                                                <td class="text-center">
                                                    <span class="badge badge-success">
                                                        <?php echo number_format($totalHoras, 2); ?> hrs
                                                    </span>
                                                </td>
                                                <td></td>
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
                                <i class="fas fa-gas-pump"></i> Abastecimientos de Combustible
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th width="10%">#</th>
                                            <th>Horómetro</th>
                                            <th>Galones</th>
                                            <th>Fecha/Hora</th>
                                            <th>Observaciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($combustibles as $index => $comb): ?>
                                        <tr>
                                            <td class="text-center"><?php echo $index + 1; ?></td>
                                            <td class="text-center"><?php echo number_format($comb['horometro'], 1); ?></td>
                                            <td class="text-center">
                                                <strong><?php echo number_format($comb['galones'], 2); ?></strong> gal
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($comb['fecha_hora'])); ?></td>
                                            <td>
                                                <?php echo $comb['observaciones'] ? htmlspecialchars($comb['observaciones']) : '<em class="text-muted">Sin observaciones</em>'; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="font-weight-bold bg-light">
                                            <td colspan="2" class="text-right">TOTAL GALONES:</td>
                                            <td class="text-center">
                                                <span class="badge badge-info">
                                                    <?php echo number_format($totalGalones, 2); ?> gal
                                                </span>
                                            </td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tfoot>
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
$custom_js_file = 'assets/js/reportes.js?v=' . ASSETS_VERSION;
include '../../layouts/footer.php';
?>