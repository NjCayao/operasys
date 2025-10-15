<?php
/**
 * OperaSys - Auditoría del Sistema
 * Archivo: modules/admin/auditoria.php
 * Descripción: Registro de todas las actividades del sistema (Solo Admin)
 */

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/auth_check.php';

verificarSesion();
verificarPermiso(['admin']);

// Variables para el layout
$page_title = 'Auditoría del Sistema';
$page_depth = 2;
$use_datatables = true;

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
                        <i class="fas fa-history"></i> Auditoría del Sistema
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Inicio</a></li>
                        <li class="breadcrumb-item active">Auditoría</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">

            <!-- Información -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Registro de Actividades:</strong> 
                        Este módulo muestra todas las acciones realizadas en el sistema 
                        (logins, creación de reportes, modificaciones, etc.)
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card collapsed-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-filter"></i> Filtros de Búsqueda
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form id="formFiltrosAuditoria" class="row">
                        <div class="col-md-3">
                            <label>
                                <i class="fas fa-user"></i> Usuario:
                            </label>
                            <select class="form-control" id="filtro_usuario">
                                <option value="">Todos</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>
                                <i class="fas fa-cog"></i> Acción:
                            </label>
                            <select class="form-control" id="filtro_accion">
                                <option value="">Todas</option>
                                <option value="login">Login</option>
                                <option value="logout">Logout</option>
                                <option value="crear_reporte">Crear Reporte</option>
                                <option value="editar_reporte">Editar Reporte</option>
                                <option value="eliminar_reporte">Eliminar Reporte</option>
                                <option value="crear_usuario">Crear Usuario</option>
                                <option value="editar_usuario">Editar Usuario</option>
                                <option value="crear_equipo">Crear Equipo</option>
                                <option value="editar_equipo">Editar Equipo</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>
                                <i class="fas fa-calendar"></i> Fecha Desde:
                            </label>
                            <input type="date" class="form-control" id="filtro_fecha_desde">
                        </div>
                        <div class="col-md-2">
                            <label>
                                <i class="fas fa-calendar"></i> Fecha Hasta:
                            </label>
                            <input type="date" class="form-control" id="filtro_fecha_hasta">
                        </div>
                        <div class="col-md-2">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-primary btn-block" id="btnAplicarFiltrosAuditoria">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de auditoría -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i> Registro de Actividades
                    </h3>
                </div>
                <div class="card-body">
                    <table id="tablaAuditoria" class="table table-bordered table-striped table-hover table-sm">
                        <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="12%">Fecha y Hora</th>
                                <th width="15%">Usuario</th>
                                <th width="12%">Acción</th>
                                <th>Detalle</th>
                                <th width="12%">IP</th>
                                <th width="10%">Navegador</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables carga los datos aquí -->
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>

<?php
$custom_js_file = 'assets/js/auditoria.js?v=' . ASSETS_VERSION;
include '../../layouts/footer.php';
?>