<?php
/**
 * OperaSys - Listado de Contratas
 * Archivo: modules/admin/contratas/listar.php
 * Descripción: Gestión de empresas subcontratistas
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
$page_title = 'Gestión de Contratas';
$page_depth = 2;
$use_datatables = true;
$use_sweetalert = true;
$custom_js_file = 'assets/js/contratas.js?v=' . ASSETS_VERSION;

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
                        <i class="fas fa-handshake"></i> Gestión de Contratas
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>modules/admin/dashboard.php">Inicio</a></li>
                        <li class="breadcrumb-item active">Contratas</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            
            <!-- Botón Agregar Contrata -->
            <?php if ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'supervisor'): ?>
            <div class="row mb-3">
                <div class="col-12">
                    <a href="agregar.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Agregar Contrata
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tarjeta con tabla de contratas -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i> Listado de Empresas Subcontratistas
                    </h3>
                </div>
                <div class="card-body">
                    <table id="tablaContratas" class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Razón Social</th>
                                <th>RUC</th>
                                <th>Contacto</th>
                                <th>Teléfono</th>
                                <th>Equipos</th>
                                <th>Vigencia Contrato</th>
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

<!-- Modal Ver Detalle Contrata -->
<div class="modal fade" id="modalVerContrata" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title">
                    <i class="fas fa-eye"></i> Detalle de Contrata
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="contenidoModalContrata">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-3x"></i>
                    <p>Cargando...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<?php 
include '../../layouts/footer.php'; 
?>