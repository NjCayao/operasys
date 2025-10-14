<?php
/**
 * OperaSys - Listado de Reportes
 * Archivo: modules/reportes/listar.php
 * Descripción: Tabla con todos los reportes del usuario
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

verificarSesion();

// Variables para el layout
$page_title = 'Mis Reportes';
$page_depth = 2;
$use_datatables = true;
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
                    <h1 class="m-0"><i class="fas fa-file-alt"></i> Mis Reportes</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>modules/admin/dashboard.php">Inicio</a></li>
                        <li class="breadcrumb-item active">Reportes</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            
            <!-- Botón Crear Reporte -->
            <div class="row mb-3">
                <div class="col-12">
                    <a href="crear.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus"></i> Nuevo Reporte
                    </a>
                </div>
            </div>

            <!-- Tarjeta con tabla de reportes -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i> Historial de Reportes
                    </h3>
                </div>
                <div class="card-body">
                    <table id="tablaReportes" class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <?php if ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'supervisor'): ?>
                                <th>Operador</th>
                                <?php endif; ?>
                                <th>Equipo</th>
                                <th>Actividades</th>
                                <th>Horas Totales</th>
                                <th>Estado</th>
                                <th>Acciones</th>
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

<?php 
include '../../layouts/footer.php'; 
?>