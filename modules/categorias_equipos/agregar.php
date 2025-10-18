<?php
/**
 * OperaSys - Agregar Categoría de Equipo
 * Archivo: modules/categorias_equipos/agregar.php
 * Descripción: Formulario para registrar nueva categoría
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar sesión y permisos
verificarSesion();

// Solo admin puede agregar
if ($_SESSION['rol'] !== 'admin') {
    header('Location: listar.php');
    exit;
}

// Variables para el layout
$page_title = 'Agregar Categoría';
$page_depth = 2;
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
                        <i class="fas fa-plus-circle"></i> Agregar Categoría
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="listar.php">Categorías</a></li>
                        <li class="breadcrumb-item active">Agregar</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8 offset-md-2">
                    
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-tag"></i> Datos de la Categoría
                            </h3>
                        </div>
                        
                        <form id="formAgregarCategoria" method="POST">
                            <div class="card-body">
                                
                                <!-- Nombre -->
                                <div class="form-group">
                                    <label for="nombre">
                                        <i class="fas fa-bookmark"></i> Nombre de la Categoría <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="nombre" 
                                           name="nombre" 
                                           placeholder="Ej: Excavadora, Volquete, etc."
                                           required
                                           maxlength="100">
                                </div>

                                <!-- Descripción -->
                                <div class="form-group">
                                    <label for="descripcion">
                                        <i class="fas fa-info-circle"></i> Descripción
                                    </label>
                                    <textarea class="form-control" 
                                              id="descripcion" 
                                              name="descripcion" 
                                              rows="2"
                                              placeholder="Descripción opcional de la categoría"
                                              maxlength="255"></textarea>
                                </div>

                                <hr>
                                <h5><i class="fas fa-gas-pump"></i> Valores por Defecto</h5>

                                <div class="row">
                                    <!-- Consumo Default -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="consumo_default">
                                                <i class="fas fa-tachometer-alt"></i> Consumo Promedio (gal/hr)
                                            </label>
                                            <input type="number" 
                                                   class="form-control" 
                                                   id="consumo_default" 
                                                   name="consumo_default" 
                                                   step="0.1"
                                                   min="0"
                                                   value="5.0"
                                                   placeholder="5.0">
                                            <small class="form-text text-muted">
                                                Se aplicará automáticamente a nuevos equipos
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Capacidad Default -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="capacidad_default">
                                                <i class="fas fa-fill-drip"></i> Capacidad del Tanque (gal)
                                            </label>
                                            <input type="number" 
                                                   class="form-control" 
                                                   id="capacidad_default" 
                                                   name="capacidad_default" 
                                                   step="1"
                                                   min="0"
                                                   value="100"
                                                   placeholder="100">
                                            <small class="form-text text-muted">
                                                Capacidad estándar para esta categoría
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Alerta -->
                                <div id="alertMessage" class="alert" style="display: none;" role="alert">
                                    <span id="alertText"></span>
                                </div>

                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar Categoría
                                </button>
                                <a href="listar.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </section>
</div>

<script>
// Preview del icono en tiempo real
document.getElementById('icono').addEventListener('input', function() {
    const iconoPreview = document.getElementById('preview-icono');
    iconoPreview.className = 'fas ' + this.value;
});
</script>

<?php 
include '../../layouts/footer.php'; 
?>