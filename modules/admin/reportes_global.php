<?php
/**
 * OperaSys - Reportes Globales
 * Archivo: modules/admin/reportes_global.php
 * Versión: 3.0 - Sistema HT/HP (SIN filtro de partidas)
 * Descripción: Ver todos los reportes del sistema (Admin/Supervisor)
 */

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/auth_check.php';

verificarSesion();
verificarPermiso(['admin', 'supervisor']);

$page_title = 'Reportes Globales';
$page_depth = 2;
$use_datatables = true;
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
                        <i class="fas fa-chart-bar"></i> Reportes Globales - Sistema HT/HP
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Inicio</a></li>
                        <li class="breadcrumb-item active">Reportes Globales</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">

            <!-- Botones de Exportación -->
            <div class="row mb-3">
                <div class="col-12">
                    <button type="button" class="btn btn-success" id="btnExportarExcel">
                        <i class="fas fa-file-excel"></i> Exportar a Excel
                    </button>
                    <button type="button" class="btn btn-danger" id="btnExportarPDF">
                        <i class="fas fa-file-pdf"></i> Exportar a PDF
                    </button>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-filter"></i> Filtros de Búsqueda
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form id="formFiltros" class="row">
                        <div class="col-md-3">
                            <label>
                                <i class="fas fa-user"></i> Operador:
                            </label>
                            <select class="form-control" name="operador_id" id="filtro_operador">
                                <option value="">Todos</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>
                                <i class="fas fa-truck-monster"></i> Categoría:
                            </label>
                            <select class="form-control" name="categoria" id="filtro_categoria">
                                <option value="">Todas</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>
                                <i class="fas fa-calendar"></i> Desde:
                            </label>
                            <input type="date" class="form-control" name="fecha_desde" id="filtro_fecha_desde">
                        </div>
                        <div class="col-md-2">
                            <label>
                                <i class="fas fa-calendar"></i> Hasta:
                            </label>
                            <input type="date" class="form-control" name="fecha_hasta" id="filtro_fecha_hasta">
                        </div>
                        <div class="col-md-2">
                            <label>
                                <i class="fas fa-toggle-on"></i> Estado:
                            </label>
                            <select class="form-control" name="estado" id="filtro_estado">
                                <option value="">Todos</option>
                                <option value="borrador">Borrador</option>
                                <option value="finalizado">Finalizado</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-primary btn-block" id="btnAplicarFiltros">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de reportes -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i> Listado de Reportes
                    </h3>
                </div>
                <div class="card-body">
                    <table id="tablaReportesGlobales"
                        class="table table-bordered table-striped table-hover"
                        data-rol="<?php echo $_SESSION['rol']; ?>">
                        <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="8%">Fecha</th>
                                <th width="15%">Operador</th>
                                <th width="12%">Equipo</th>
                                <th width="8%">H. Motor</th>
                                <th width="8%">HT</th>
                                <th width="8%">HP</th>
                                <th width="8%">Efic.</th>
                                <th width="10%">Combustible</th>
                                <th width="8%">Estado</th>
                                <th width="10%">Acciones</th>
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
$custom_js_file = 'assets/js/reportes_global.js?v=' . ASSETS_VERSION;
include '../../layouts/footer.php';
?>