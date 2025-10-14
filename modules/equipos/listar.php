<?php
/**
 * OperaSys - Listado de Equipos
 * Archivo: modules/equipos/listar.php
 * Descripción: Tabla con todos los equipos y opciones CRUD
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar sesión
verificarSesion();

// Variables para el layout
$page_title = 'Gestión de Equipos';
$page_depth = 2;
$use_datatables = true;
$use_sweetalert = true;
$custom_js_file = 'assets/js/equipos.js?v=' . ASSETS_VERSION;

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
                    <h1 class="m-0"><i class="fas fa-truck-monster"></i> Gestión de Equipos</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>modules/admin/dashboard.php">Inicio</a></li>
                        <li class="breadcrumb-item active">Equipos</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            
            <!-- Botón Agregar Equipo -->
            <?php if ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'supervisor'): ?>
            <div class="row mb-3">
                <div class="col-12">
                    <a href="agregar.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Agregar Equipo
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tarjeta con tabla de equipos -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i> Listado de Equipos
                    </h3>
                </div>
                <div class="card-body">
                    <table id="tablaEquipos" class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Categoría</th>
                                <th>Código</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Fecha Registro</th>
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
// Incluir footer
include '../../layouts/footer.php';
?>

<?php 
include '../../layouts/footer.php'; 
?>