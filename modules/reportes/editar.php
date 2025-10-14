<?php

/**
 * OperaSys - Editar Reporte
 * Archivo: modules/reportes/editar.php
 * Descripción: Editar reporte en borrador (operador) o cualquiera (admin)
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

    // Verificar permisos
    if ($reporte['usuario_id'] != $_SESSION['user_id'] && $_SESSION['rol'] !== 'admin') {
        header('Location: listar.php?error=sin_permisos');
        exit;
    }

    // Solo admin puede editar reportes finalizados
    if ($reporte['estado'] === 'finalizado' && $_SESSION['rol'] !== 'admin') {
        header('Location: ver.php?id=' . $reporteId);
        exit;
    }
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}

// Variables para el layout
$page_title = 'Editar Reporte #' . $reporteId;
$page_depth = 2;
$use_sweetalert = true;
$custom_js_file = 'assets/js/reportes.js?v=' . ASSETS_VERSION;

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

    <!-- Main content -->
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
                        <button type="button" id="btnAgregarActividad" class="btn btn-success">
                            <i class="fas fa-plus"></i> Agregar Actividad
                        </button>
                        <button type="button" id="btnAgregarCombustible" class="btn btn-info">
                            <i class="fas fa-gas-pump"></i> Registrar Combustible
                        </button>
                        <a href="listar.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>

                    <!-- Card de Actividades -->
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-tasks"></i> Actividades del Día
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

                            <!-- NUEVO: Botón eliminar (solo si NO tiene actividades) -->
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

<!-- Modal: Agregar/Editar Actividad -->
<div class="modal fade" id="modalActividad" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title">
                    <i class="fas fa-plus"></i> <span id="tituloModalActividad">Agregar Actividad</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="formActividad">
                <input type="hidden" id="actividad_id">
                <div class="modal-body">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-tasks"></i> Tipo de Trabajo <span class="text-danger">*</span>
                                </label>
                                <select class="form-control" id="tipo_trabajo_id" required>
                                    <option value="">Cargando...</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-tag"></i> Fase de Costo <span class="text-danger">*</span>
                                </label>
                                <select class="form-control" id="fase_costo_id" required>
                                    <option value="">Cargando...</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-tachometer-alt"></i> Horómetro Inicial <span class="text-danger">*</span>
                                </label>
                                <input type="number"
                                    class="form-control"
                                    id="horometro_inicial"
                                    step="0.1"
                                    placeholder="Ej: 1584.5"
                                    required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-tachometer-alt"></i> Horómetro Final <span class="text-danger">*</span>
                                </label>
                                <input type="number"
                                    class="form-control"
                                    id="horometro_final"
                                    step="0.1"
                                    placeholder="Ej: 1585.9"
                                    required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-comment"></i> Observaciones
                        </label>
                        <textarea class="form-control"
                            id="observaciones_actividad"
                            rows="2"
                            placeholder="Detalles adicionales (opcional)"></textarea>
                    </div>

                    <div id="alertActividad" class="alert" style="display: none;"></div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Agregar Combustible -->
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
                        <label>
                            <i class="fas fa-tachometer-alt"></i> Horómetro <span class="text-danger">*</span>
                        </label>
                        <input type="number"
                            class="form-control"
                            id="horometro_combustible"
                            step="0.1"
                            placeholder="Ej: 1586.5"
                            required>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-gas-pump"></i> Galones <span class="text-danger">*</span>
                        </label>
                        <input type="number"
                            class="form-control"
                            id="galones"
                            step="0.01"
                            placeholder="Ej: 45.00"
                            required>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-comment"></i> Observaciones
                        </label>
                        <textarea class="form-control"
                            id="observaciones_combustible"
                            rows="2"
                            placeholder="Detalles adicionales (opcional)"></textarea>
                    </div>

                    <div id="alertCombustible" class="alert" style="display: none;"></div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include '../../layouts/footer.php';
?>