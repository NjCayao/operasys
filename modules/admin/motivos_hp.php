<?php
/**
 * OperaSys - Gestión de Motivos HP
 * Archivo: modules/admin/motivos_hp.php
 * Versión: 3.0 - COMPLETO Y CORREGIDO
 */

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/auth_check.php';

verificarPermiso(['admin', 'supervisor']);

$page_title = 'Motivos HP (Horas Paradas)';
$page_depth = 2;
$use_datatables = true;
$use_sweetalert = true;
$custom_js_file = 'assets/js/motivos_hp.js?v=' . ASSETS_VERSION;

include '../../layouts/header.php';
include '../../layouts/navbar.php';
include '../../layouts/sidebar.php';

$es_solo_lectura = ($_SESSION['rol'] === 'supervisor');
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                        <i class="fas fa-pause-circle text-warning"></i> Motivos HP
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Inicio</a></li>
                        <li class="breadcrumb-item">Catálogos</li>
                        <li class="breadcrumb-item active">Motivos HP</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">

            <!-- Info Alert -->
            <div class="alert alert-warning alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <h5><i class="icon fas fa-info-circle"></i> ¿Qué son las HP?</h5>
                <strong>HP (Horas Paradas)</strong> son las horas no productivas donde el equipo no está trabajando por causas justificadas o no justificadas.
            </div>

            <!-- Botón Agregar -->
            <?php if ($_SESSION['rol'] === 'admin'): ?>
                <div class="row mb-3">
                    <div class="col-12">
                        <button type="button" class="btn btn-warning" id="btnNuevoMotivo">
                            <i class="fas fa-plus"></i> Nuevo Motivo HP
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tarjeta con tabla -->
            <div class="card">
                <div class="card-header bg-warning">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i> Catálogo de Motivos de Parada
                    </h3>
                </div>
                <div class="card-body">
                    <table id="tablaMotivos"
                        class="table table-bordered table-striped table-hover"
                        data-rol="<?php echo $_SESSION['rol']; ?>">
                        <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="10%">Código</th>
                                <th width="20%">Nombre</th>
                                <th width="12%">Categoría</th>
                                <th width="10%">Tipo</th>
                                <th width="8%">Orden</th>
                                <th width="8%">Estado</th>
                                <th width="10%">Fecha</th>
                                <th width="10%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>

<!-- Modal: Agregar/Editar Motivo -->
<div class="modal fade" id="modalMotivo" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-pause-circle"></i> <span id="tituloModal">Nuevo Motivo HP</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="formMotivo">
                <input type="hidden" id="motivo_id" name="motivo_id">
                <div class="modal-body">

                    <!-- SECCIÓN: Código y Nombre -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="codigo">
                                    <i class="fas fa-barcode"></i> Código
                                </label>
                                <input type="text"
                                    class="form-control"
                                    id="codigo"
                                    name="codigo"
                                    placeholder="HP-001"
                                    maxlength="20">
                                <small class="form-text text-muted">
                                    Código interno (opcional)
                                </small>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="nombre">
                                    <i class="fas fa-tag"></i> Nombre <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                    class="form-control"
                                    id="nombre"
                                    name="nombre"
                                    placeholder="Ej: Falla mecánica"
                                    required
                                    maxlength="150">
                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN: Descripción -->
                    <div class="form-group">
                        <label for="descripcion">
                            <i class="fas fa-align-left"></i> Descripción
                        </label>
                        <textarea class="form-control"
                            id="descripcion"
                            name="descripcion"
                            rows="2"
                            placeholder="Descripción detallada (opcional)"
                            maxlength="255"></textarea>
                    </div>

                    <!-- SECCIÓN: Categoría -->
                    <div class="form-group">
                        <label for="categoria_parada">
                            <i class="fas fa-layer-group"></i> Categoría <span class="text-danger">*</span>
                        </label>
                        <select class="form-control" id="categoria_parada" name="categoria_parada" required>
                            <option value="operacional">Operacional</option>
                            <option value="mantenimiento">Mantenimiento</option>
                            <option value="climatica">Climática</option>
                            <option value="administrativa">Administrativa</option>
                            <option value="personal">Personal</option>
                        </select>
                        <small class="form-text text-muted">
                            Para análisis estadístico
                        </small>
                    </div>

                    <hr>

                    <!-- SECCIÓN: Orden y Frecuente -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="orden_mostrar">
                                    <i class="fas fa-sort-numeric-down"></i> Orden
                                </label>
                                <input type="number"
                                    class="form-control"
                                    id="orden_mostrar"
                                    name="orden_mostrar"
                                    value="999"
                                    min="1"
                                    max="9999">
                                <small class="form-text text-muted">
                                    Orden en dropdowns
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" 
                                        class="custom-control-input" 
                                        id="es_frecuente" 
                                        name="es_frecuente" 
                                        value="1">
                                    <label class="custom-control-label" for="es_frecuente">
                                        <i class="fas fa-star text-warning"></i> Es Frecuente
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    Aparece en sugerencias
                                </small>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- SECCIÓN: Justificada y Requiere Observación -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tipo de Parada</label>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" 
                                        class="custom-control-input" 
                                        id="es_justificada" 
                                        name="es_justificada" 
                                        value="1" 
                                        checked>
                                    <label class="custom-control-label" for="es_justificada">
                                        <i class="fas fa-check-circle text-info"></i> Es Justificada
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    Parada por causa válida
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Observaciones</label>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" 
                                        class="custom-control-input" 
                                        id="requiere_observacion" 
                                        name="requiere_observacion" 
                                        value="1">
                                    <label class="custom-control-label" for="requiere_observacion">
                                        <i class="fas fa-comment text-primary"></i> Requiere Observación
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    Obliga escribir detalle
                                </small>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- SECCIÓN: Estado (solo al editar) -->
                    <div class="form-group" id="grupoEstado" style="display: none;">
                        <label for="estado">
                            <i class="fas fa-toggle-on"></i> Estado
                        </label>
                        <select class="form-control" id="estado" name="estado">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                        <small class="form-text text-muted">
                            Solo las activas aparecen en reportes
                        </small>
                    </div>

                    <!-- Alerta en Modal -->
                    <div id="alertModal" class="alert" style="display: none;"></div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning">
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