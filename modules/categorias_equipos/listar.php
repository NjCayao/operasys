<?php
/**
 * OperaSys - Listado de Categorías de Equipos
 * Archivo: modules/categorias_equipos/listar.php
 * Descripción: Gestión de categorías de equipos
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar sesión
verificarSesion();

// Solo admin y supervisor
if ($_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'supervisor') {
    header('Location: ../reportes/listar.php');
    exit;
}

// Variables para el layout
$page_title = 'Categorías de Equipos';
$page_depth = 2;
$use_datatables = true;
$use_sweetalert = true;
$custom_js_file = 'assets/js/categorias_equipos.js?v=' . ASSETS_VERSION;

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
                        <i class="fas fa-tags"></i> Categorías de Equipos
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>modules/admin/dashboard.php">Inicio</a></li>
                        <li class="breadcrumb-item active">Categorías</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            
            <!-- Botón Agregar Categoría (Solo Admin) -->
            <?php if ($_SESSION['rol'] === 'admin'): ?>
            <div class="row mb-3">
                <div class="col-12">
                    <a href="agregar.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Agregar Categoría
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tarjeta con tabla de categorías -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i> Listado de Categorías
                    </h3>
                </div>
                <div class="card-body">
                    <table id="tablaCategorias" class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Consumo Default</th>
                                <th>Capacidad Default</th>
                                <th>Equipos</th>
                                <th>Orden</th>
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