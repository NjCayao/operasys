<?php
/**
 * OperaSys - Gestión de Fases de Costo
 * Archivo: modules/admin/fases_costo.php
 * Descripción: CRUD de fases de costo (solo admin)
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar sesión y rol admin
verificarAdmin();

// Variables para el layout
$page_title = 'Fases de Costo';
$page_depth = 2;
$use_datatables = true;
$use_sweetalert = true;
$custom_js_file = 'assets/js/fases_costo.js?v=' . ASSETS_VERSION;

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
                        <i class="fas fa-tag"></i> Gestión de Fases de Costo
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Inicio</a></li>
                        <li class="breadcrumb-item active">Fases de Costo</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            
            <!-- Botón Agregar -->
            <div class="row mb-3">
                <div class="col-12">
                    <button type="button" class="btn btn-primary" id="btnNuevaFase">
                        <i class="fas fa-plus"></i> Nueva Fase de Costo
                    </button>
                </div>
            </div>

            <!-- Tarjeta con tabla -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i> Catálogo de Fases de Costo
                    </h3>
                </div>
                <div class="card-body">
                    <table id="tablaFases" class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th width="8%">ID</th>
                                <th width="15%">Código</th>
                                <th>Descripción</th>
                                <th width="15%">Proyecto</th>
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

<!-- Modal: Agregar/Editar Fase -->
<div class="modal fade" id="modalFase" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">
                    <i class="fas fa-tag"></i> <span id="tituloModal">Nueva Fase de Costo</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="formFase">
                <input type="hidden" id="fase_id">
                <div class="modal-body">
                    
                    <div class="form-group">
                        <label for="codigo">
                            <i class="fas fa-barcode"></i> Código <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control text-uppercase" 
                               id="codigo" 
                               name="codigo"
                               placeholder="Ej: FC001"
                               required
                               maxlength="20"
                               style="text-transform: uppercase;">
                        <small class="form-text text-muted">
                            Código único de la fase (se convertirá a mayúsculas)
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="descripcion">
                            <i class="fas fa-align-left"></i> Descripción <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" 
                                  id="descripcion" 
                                  name="descripcion"
                                  rows="3"
                                  placeholder="Descripción de la fase de costo"
                                  required
                                  maxlength="255"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="proyecto">
                            <i class="fas fa-folder"></i> Proyecto (Opcional)
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="proyecto" 
                               name="proyecto"
                               placeholder="Ej: Proyecto Minero XYZ"
                               maxlength="100">
                        <small class="form-text text-muted">
                            Proyecto asociado a esta fase
                        </small>
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
                            Solo las fases activas aparecen en los reportes
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
include '../../layouts/footer.php';
?>