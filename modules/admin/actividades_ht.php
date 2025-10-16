<?php
/**
 * OperaSys - Gestión de Actividades HT
 * Archivo: modules/admin/actividades_ht.php
 */

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/auth_check.php';

verificarPermiso(['admin', 'supervisor']);

$page_title = 'Actividades HT (Horas Trabajadas)';
$page_depth = 2;
$use_datatables = true;
$use_sweetalert = true;
$custom_js_file = 'assets/js/actividades_ht.js?v=' . ASSETS_VERSION;

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
                        <i class="fas fa-tasks text-success"></i> Actividades HT
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Inicio</a></li>
                        <li class="breadcrumb-item">Catálogos</li>
                        <li class="breadcrumb-item active">Actividades HT</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">

            <!-- Info Alert -->
            <div class="alert alert-info alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <h5><i class="icon fas fa-info-circle"></i> ¿Qué son las HT?</h5>
                <strong>HT (Horas Trabajadas)</strong> son las horas productivas donde el equipo realiza trabajo efectivo que genera avance físico en la obra.
            </div>

            <!-- Botón Agregar -->
            <?php if ($_SESSION['rol'] === 'admin'): ?>
                <div class="row mb-3">
                    <div class="col-12">
                        <button type="button" class="btn btn-success" id="btnNuevaActividad">
                            <i class="fas fa-plus"></i> Nueva Actividad HT
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tarjeta con tabla -->
            <div class="card">
                <div class="card-header bg-success">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i> Catálogo de Actividades Productivas
                    </h3>
                </div>
                <div class="card-body">
                    <table id="tablaActividades"
                        class="table table-bordered table-striped table-hover"
                        data-rol="<?php echo $_SESSION['rol']; ?>">
                        <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="10%">Código</th>
                                <th width="20%">Nombre</th>
                                <th width="20%">Descripción</th>
                                <th width="12%">Rendimiento Ref.</th>
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

<!-- Modal: Agregar/Editar Actividad -->
<div class="modal fade" id="modalActividad" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title">
                    <i class="fas fa-tasks"></i> <span id="tituloModal">Nueva Actividad HT</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="formActividad">
                <input type="hidden" id="actividad_id">
                <div class="modal-body">

                    <div class="form-group">
                        <label for="nombre">
                            <i class="fas fa-tag"></i> Nombre <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                            class="form-control"
                            id="nombre"
                            name="nombre"
                            placeholder="Ej: Excavación de plataforma"
                            required
                            maxlength="150">
                        <small class="form-text text-muted">
                            Nombre descriptivo de la actividad productiva
                        </small>
                    </div>

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

                    <div class="form-group">
                        <label for="rendimiento_referencial">
                            <i class="fas fa-tachometer-alt"></i> Rendimiento Referencial
                        </label>
                        <input type="text"
                            class="form-control"
                            id="rendimiento_referencial"
                            name="rendimiento_referencial"
                            placeholder="Ej: 80 m³/hr, 200 m²/hr"
                            maxlength="50">
                        <small class="form-text text-muted">
                            Rendimiento esperado (opcional)
                        </small>
                    </div>

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
                                <div class="custom-control custom-switch mt-4">
                                    <input type="checkbox" class="custom-control-input" id="es_frecuente" name="es_frecuente" value="1">
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

                    <div id="alertModal" class="alert" style="display: none;"></div>

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

<?php
include '../../layouts/footer.php';
?>