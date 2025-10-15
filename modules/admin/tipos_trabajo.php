<?php

/**
 * OperaSys - Gestión de Tipos de Trabajo
 * Archivo: modules/admin/tipos_trabajo.php
 * Descripción: CRUD de tipos de trabajo (solo admin)
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar sesión y rol admin
require_once '../../includes/auth_check.php';

// Verificar sesión - permitir admin y supervisor
verificarPermiso(['admin', 'supervisor']);

// Variables para el layout
$page_title = 'Tipos de Trabajo';
$page_depth = 2;
$use_datatables = true;
$use_sweetalert = true;
$custom_js_file = 'assets/js/tipos_trabajo.js?v=' . ASSETS_VERSION;

include '../../layouts/header.php';
include '../../layouts/navbar.php';
include '../../layouts/sidebar.php';
?>

<?php
// Variable para ocultar acciones en JavaScript
$es_solo_lectura = ($_SESSION['rol'] === 'supervisor');
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                        <i class="fas fa-tasks"></i> Gestión de Tipos de Trabajo
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Inicio</a></li>
                        <li class="breadcrumb-item active">Tipos de Trabajo</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">

            <!-- Botón Agregar -->
            <?php if ($_SESSION['rol'] === 'admin'): ?>
                <div class="row mb-3">
                    <div class="col-12">
                        <button type="button" class="btn btn-primary" id="btnNuevoTipo">
                            <i class="fas fa-plus"></i> Nuevo Tipo de Trabajo
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tarjeta con tabla -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i> Catálogo de Tipos de Trabajo
                    </h3>
                </div>
                <div class="card-body">
                    <table id="tablaTipos"
                        class="table table-bordered table-striped table-hover"
                        data-rol="<?php echo $_SESSION['rol']; ?>">
                        <thead>
                            <tr>
                                <th width="8%">ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th width="10%">Estado</th>
                                <th width="12%">Fecha Registro</th>
                                <th width="12%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Se llena con DataTables via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>

<!-- Modal: Agregar/Editar Tipo -->
<div class="modal fade" id="modalTipo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">
                    <i class="fas fa-tasks"></i> <span id="tituloModal">Nuevo Tipo de Trabajo</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="formTipo">
                <input type="hidden" id="tipo_id">
                <div class="modal-body">

                    <div class="form-group">
                        <label for="nombre">
                            <i class="fas fa-tasks"></i> Nombre <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                            class="form-control"
                            id="nombre"
                            name="nombre"
                            placeholder="Ej: Excavación"
                            required
                            maxlength="100">
                        <small class="form-text text-muted">
                            Nombre descriptivo del tipo de trabajo
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="descripcion">
                            <i class="fas fa-comment"></i> Descripción
                        </label>
                        <textarea class="form-control"
                            id="descripcion"
                            name="descripcion"
                            rows="3"
                            placeholder="Descripción detallada (opcional)"
                            maxlength="255"></textarea>
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
                            Solo los tipos activos aparecen en los reportes
                        </small>
                    </div>

                    <div id="alertModal" class="alert" style="display: none;"></div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$custom_js_file = 'assets/js/tipos_trabajo.js?v=' . ASSETS_VERSION;
include '../../layouts/footer.php';
?>