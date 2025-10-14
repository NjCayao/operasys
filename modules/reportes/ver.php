<?php
/**
 * OperaSys - Ver Detalle de Reporte
 * Archivo: modules/reportes/ver.php
 * Descripción: Página completa con todos los datos del reporte
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
        header('Location: listar.php?error=reporte_no_encontrado');
        exit;
    }
    
    // Verificar permisos: solo el dueño o admin/supervisor pueden ver
    if ($reporte['usuario_id'] != $_SESSION['user_id'] && 
        $_SESSION['rol'] !== 'admin' && 
        $_SESSION['rol'] !== 'supervisor') {
        header('Location: listar.php?error=sin_permisos');
        exit;
    }
    
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}

// Variables para el layout
$page_title = 'Reporte #' . $reporteId;
$page_depth = 2;
$use_sweetalert = true;

// Incluir header
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
                        <li class="breadcrumb-item active">Ver Reporte</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-10 offset-md-1">
                    
                    <!-- Botones de acción -->
                    <div class="mb-3">
                        <a href="listar.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al Listado
                        </a>
                        <a href="../../api/pdf.php?id=<?php echo $reporte['id']; ?>" 
                           class="btn btn-danger" 
                           target="_blank">
                            <i class="fas fa-file-pdf"></i> Descargar PDF
                        </a>
                    </div>

                    <!-- Información del Reporte -->
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle"></i> Información del Reporte
                            </h3>
                            <div class="card-tools">
                                <?php if ($reporte['estado_sinc'] === 'sincronizado'): ?>
                                    <span class="badge badge-success">
                                        <i class="fas fa-check"></i> Sincronizado
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-warning">
                                        <i class="fas fa-clock"></i> Pendiente
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <div class="row">
                                <!-- Columna Izquierda -->
                                <div class="col-md-6">
                                    <dl class="row">
                                        <dt class="col-sm-5">
                                            <i class="fas fa-calendar text-primary"></i> Fecha:
                                        </dt>
                                        <dd class="col-sm-7">
                                            <?php echo date('d/m/Y', strtotime($reporte['fecha'])); ?>
                                        </dd>

                                        <dt class="col-sm-5">
                                            <i class="fas fa-user text-primary"></i> Operador:
                                        </dt>
                                        <dd class="col-sm-7">
                                            <?php echo htmlspecialchars($reporte['operador']); ?>
                                        </dd>

                                        <dt class="col-sm-5">
                                            <i class="fas fa-id-card text-primary"></i> DNI:
                                        </dt>
                                        <dd class="col-sm-7">
                                            <?php echo htmlspecialchars($reporte['operador_dni']); ?>
                                        </dd>

                                        <dt class="col-sm-5">
                                            <i class="fas fa-briefcase text-primary"></i> Cargo:
                                        </dt>
                                        <dd class="col-sm-7">
                                            <?php echo htmlspecialchars($reporte['operador_cargo']); ?>
                                        </dd>
                                    </dl>
                                </div>

                                <!-- Columna Derecha -->
                                <div class="col-md-6">
                                    <dl class="row">
                                        <dt class="col-sm-5">
                                            <i class="fas fa-truck-monster text-success"></i> Equipo:
                                        </dt>
                                        <dd class="col-sm-7">
                                            <?php echo htmlspecialchars($reporte['equipo_categoria']); ?><br>
                                            <strong><?php echo htmlspecialchars($reporte['equipo_codigo']); ?></strong>
                                        </dd>

                                        <dt class="col-sm-5">
                                            <i class="fas fa-clock text-success"></i> Hora Inicio:
                                        </dt>
                                        <dd class="col-sm-7">
                                            <?php echo date('H:i', strtotime($reporte['hora_inicio'])); ?>
                                        </dd>

                                        <dt class="col-sm-5">
                                            <i class="fas fa-clock text-success"></i> Hora Fin:
                                        </dt>
                                        <dd class="col-sm-7">
                                            <?php echo $reporte['hora_fin'] ? date('H:i', strtotime($reporte['hora_fin'])) : '<em>En curso</em>'; ?>
                                        </dd>

                                        <dt class="col-sm-5">
                                            <i class="fas fa-hourglass-half text-success"></i> Horas Trabajadas:
                                        </dt>
                                        <dd class="col-sm-7">
                                            <?php echo $reporte['horas_trabajadas'] ? number_format($reporte['horas_trabajadas'], 1) . ' hrs' : '-'; ?>
                                        </dd>
                                    </dl>
                                </div>
                            </div>

                            <hr>

                            <!-- Actividad Realizada -->
                            <div class="row">
                                <div class="col-12">
                                    <h5><i class="fas fa-tasks text-info"></i> Actividad Realizada:</h5>
                                    <p class="text-justify bg-light p-3 rounded">
                                        <?php echo nl2br(htmlspecialchars($reporte['actividad'])); ?>
                                    </p>
                                </div>
                            </div>

                            <?php if (!empty($reporte['observaciones'])): ?>
                            <!-- Observaciones -->
                            <div class="row">
                                <div class="col-12">
                                    <h5><i class="fas fa-comment text-warning"></i> Observaciones:</h5>
                                    <p class="text-justify bg-light p-3 rounded">
                                        <?php echo nl2br(htmlspecialchars($reporte['observaciones'])); ?>
                                    </p>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($reporte['ubicacion'])): ?>
                            <!-- Ubicación GPS -->
                            <div class="row">
                                <div class="col-12">
                                    <h5><i class="fas fa-map-marker-alt text-danger"></i> Ubicación GPS:</h5>
                                    <p class="bg-light p-3 rounded">
                                        <i class="fas fa-location-arrow"></i> 
                                        <?php echo htmlspecialchars($reporte['ubicacion']); ?>
                                        <a href="https://www.google.com/maps?q=<?php echo urlencode($reporte['ubicacion']); ?>" 
                                           target="_blank" 
                                           class="btn btn-sm btn-outline-primary ml-2">
                                            <i class="fas fa-map"></i> Ver en Google Maps
                                        </a>
                                    </p>
                                </div>
                            </div>
                            <?php endif; ?>

                            <hr>

                            <!-- Firma Digital -->
                            <?php if (!empty($reporte['operador_firma'])): ?>
                            <div class="row">
                                <div class="col-12">
                                    <h5><i class="fas fa-signature text-primary"></i> Firma Digital del Operador:</h5>
                                    <div class="text-center bg-light p-3 rounded">
                                        <img src="<?php echo $reporte['operador_firma']; ?>" 
                                             alt="Firma" 
                                             style="max-width: 300px; border: 1px solid #ddd; padding: 10px; background: white;">
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                        </div>

                        <div class="card-footer">
                            <small class="text-muted">
                                <i class="fas fa-clock"></i> 
                                Registrado el <?php echo date('d/m/Y H:i', strtotime($reporte['fecha_creacion'])); ?>
                            </small>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
</div>


<?php 
$custom_js_file = 'assets/js/editar_usuario.js?v=' . ASSETS_VERSION;
include '../../layouts/footer.php'; 
?>