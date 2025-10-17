<?php
/**
 * OperaSys - Editar Reporte
 * Archivo: modules/reportes/editar.php
 * Versión: 3.0 - Sistema HT/HP (SIN partidas)
 * Descripción: Editar reporte en borrador (operador) o cualquiera (admin)
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
            e.codigo as equipo_codigo,
            e.categoria as equipo_categoria,
            e.id as equipo_id
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

    if ($reporte['usuario_id'] != $_SESSION['user_id'] && $_SESSION['rol'] !== 'admin') {
        header('Location: listar.php?error=sin_permisos');
        exit;
    }

    if ($reporte['estado'] === 'finalizado' && $_SESSION['rol'] !== 'admin') {
        header('Location: ver.php?id=' . $reporteId);
        exit;
    }
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}

$page_title = 'Editar Reporte #' . $reporteId;
$page_depth = 2;
$use_sweetalert = true;
$custom_js_file = 'assets/js/reportes.js?v=' . ASSETS_VERSION;

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
                        <i class="fas fa-edit"></i> Editar Reporte #<?php echo $reporte['id']; ?>
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="listar.php">Reportes</a></li>
                        <li class="breadcrumb-item active">Editar</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">

            <input type="hidden" id="reporte_id" value="<?php echo $reporte['id']; ?>">

            <div class="row mb-3">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Reporte:</strong>
                        Fecha: <strong><?php echo date('d/m/Y', strtotime($reporte['fecha'])); ?></strong> |
                        Equipo: <strong><?php echo htmlspecialchars($reporte['equipo_categoria'] . ' - ' . $reporte['equipo_codigo']); ?></strong> |
                        Horómetro Inicial: <strong><?php echo number_format($reporte['horometro_inicial'], 1); ?></strong> |
                        Estado:
                        <?php if ($reporte['estado'] === 'finalizado'): ?>
                            <span class="badge badge-success">Finalizado</span>
                            <?php if ($_SESSION['rol'] === 'admin'): ?>
                                <small class="text-muted">(Solo admin puede editar)</small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge badge-warning">Borrador</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">

                    <!-- Botones de Acción -->
                    <div class="mb-3">
                        <button type="button" id="btnAgregarHT" class="btn btn-success">
                            <i class="fas fa-tools"></i> Agregar HT
                        </button>
                        <button type="button" id="btnAgregarHP" class="btn btn-warning">
                            <i class="fas fa-pause-circle"></i> Agregar HP
                        </button>
                        <button type="button" id="btnAgregarCombustible" class="btn btn-info">
                            <i class="fas fa-gas-pump"></i> Registrar Combustible
                        </button>
                        <a href="listar.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>

                    <!-- Card de Actividades HT/HP -->
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-tasks"></i> Actividades del Día (HT/HP)
                            </h3>
                        </div>
                        <div class="card-body">
                            <div id="listaActividades">
                                <div class="text-center">
                                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                                    <p class="text-muted">Cargando actividades...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card de Combustible -->
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-gas-pump"></i> Abastecimientos de Combustible
                            </h3>
                        </div>
                        <div class="card-body">
                            <div id="listaCombustible">
                                <div class="text-center">
                                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                                    <p class="text-muted">Cargando abastecimientos...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Horómetro Final -->
                    <div class="card card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-tachometer-alt"></i> Horómetro Final
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="horometro_final">
                                    Horómetro al finalizar el día <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       id="horometro_final" 
                                       step="0.1" 
                                       value="<?php echo $reporte['horometro_final']; ?>"
                                       placeholder="Ej: 1594.2">
                            </div>
                        </div>
                    </div>

                    <!-- Observaciones Generales -->
                    <div class="card card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-comment"></i> Observaciones Generales
                            </h3>
                        </div>
                        <div class="card-body">
                            <textarea class="form-control"
                                id="observaciones_generales"
                                rows="4"
                                placeholder="Observaciones generales del día de trabajo (opcional)"><?php echo htmlspecialchars($reporte['observaciones_generales'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Botones Finales -->
                    <div class="mb-3">
                        <?php if ($reporte['estado'] === 'borrador'): ?>
                            <button type="button" id="btnGuardarCambios" class="btn btn-warning btn-lg">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                            <button type="button" id="btnFinalizarReporte" class="btn btn-success btn-lg">
                                <i class="fas fa-check"></i> Finalizar y Enviar
                            </button>
                            <button type="button" id="btnEliminarReporte" class="btn btn-danger btn-lg" style="display: none;">
                                <i class="fas fa-trash"></i> Eliminar Reporte
                            </button>
                        <?php else: ?>
                            <?php if ($_SESSION['rol'] === 'admin'): ?>
                                <button type="button" id="btnGuardarCambios" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save"></i> Guardar Cambios (Admin)
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                        <a href="listar.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </section>
</div>

<!-- Modales (mismos que crear.php) -->
<!-- Modal HT -->
<div class="modal fade" id="modalHT" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title">
                    <i class="fas fa-tools"></i> Agregar Hora Trabajada (HT)
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="formHT">
                <div class="modal-body">
                    <div class="alert alert-success">
                        <i class="fas fa-info-circle"></i> 
                        <strong>HT:</strong> Horas productivas donde el equipo realiza trabajo efectivo.
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Hora Inicio <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="hora_inicio_ht" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Hora Fin <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="hora_fin_ht" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Actividad <span class="text-danger">*</span></label>
                        <select class="form-control" id="actividad_ht_id" required>
                            <option value="">Cargando...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Observaciones</label>
                        <textarea class="form-control" id="observaciones_ht" rows="2"></textarea>
                    </div>
                    <div id="alertHT" class="alert" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar HT</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal HP -->
<div class="modal fade" id="modalHP" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-pause-circle"></i> Agregar Hora Parada (HP)
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="formHP">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle"></i> 
                        <strong>HP:</strong> Horas no productivas donde el equipo no trabaja.
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Hora Inicio <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="hora_inicio_hp" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Hora Fin <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="hora_fin_hp" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Motivo de Parada <span class="text-danger">*</span></label>
                        <select class="form-control" id="motivo_hp_id" required>
                            <option value="">Cargando...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Observaciones</label>
                        <textarea class="form-control" id="observaciones_hp" rows="2"></textarea>
                    </div>
                    <div id="alertHP" class="alert" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Guardar HP</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Combustible -->
<div class="modal fade" id="modalCombustible" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title">
                    <i class="fas fa-gas-pump"></i> Registrar Combustible
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="formCombustible">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Horómetro <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="horometro_combustible" step="0.1" required>
                    </div>
                    <div class="form-group">
                        <label>Hora de Abastecimiento <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" id="hora_abastecimiento" required>
                    </div>
                    <div class="form-group">
                        <label>Galones <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="galones" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Observaciones</label>
                        <textarea class="form-control" id="observaciones_combustible" rows="2"></textarea>
                    </div>
                    <div id="alertCombustible" class="alert" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include '../../layouts/footer.php';
?>