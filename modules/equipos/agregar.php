<?php
/**
 * OperaSys - Agregar Equipo
 * Archivo: modules/equipos/agregar.php
 * Descripción: Formulario para registrar nuevo equipo
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar sesión y permisos
verificarSesion();

// Solo admin y supervisor pueden agregar equipos
if ($_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'supervisor') {
    header('Location: listar.php');
    exit;
}

// Variables para el layout
$page_title = 'Agregar Equipo';
$page_depth = 2;
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
                    <h1 class="m-0">
                        <i class="fas fa-plus-circle"></i> Agregar Equipo
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="listar.php">Equipos</a></li>
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
                                <i class="fas fa-edit"></i> Datos del Equipo
                            </h3>
                        </div>
                        
                        <form id="formAgregarEquipo" method="POST">
                            <div class="card-body">
                                
                                <!-- Categoría -->
                                <div class="form-group">
                                    <label for="categoria">
                                        <i class="fas fa-tag"></i> Categoría <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control" id="categoria" name="categoria" required>
                                        <option value="">Seleccionar categoría</option>
                                        <option value="Excavadora">Excavadora</option>
                                        <option value="Volquete">Volquete</option>
                                        <option value="Tractor">Tractor</option>
                                        <option value="Motoniveladora">Motoniveladora</option>
                                        <option value="Cargador">Cargador</option>
                                        <option value="Retroexcavadora">Retroexcavadora</option>
                                        <option value="Bulldozer">Bulldozer</option>
                                        <option value="Compactadora">Compactadora</option>
                                        <option value="Grúa">Grúa</option>
                                        <option value="Otro">Otro</option>
                                    </select>
                                </div>

                                <!-- Código -->
                                <div class="form-group">
                                    <label for="codigo">
                                        <i class="fas fa-barcode"></i> Código del Equipo <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="codigo" 
                                           name="codigo" 
                                           placeholder="Ej: EX001, VOL002, TRA003"
                                           required
                                           maxlength="20"
                                           pattern="[A-Z0-9]+"
                                           title="Solo letras mayúsculas y números, sin espacios">
                                    <small class="form-text text-muted">
                                        Solo letras mayúsculas y números (Ej: EX001)
                                    </small>
                                </div>

                                <!-- Descripción -->
                                <div class="form-group">
                                    <label for="descripcion">
                                        <i class="fas fa-info-circle"></i> Descripción
                                    </label>
                                    <textarea class="form-control" 
                                              id="descripcion" 
                                              name="descripcion" 
                                              rows="3"
                                              placeholder="Ej: Excavadora Caterpillar 320D, color amarillo, año 2020"
                                              maxlength="255"></textarea>
                                    <small class="form-text text-muted">
                                        Información adicional sobre el equipo (opcional)
                                    </small>
                                </div>

                                <!-- Alerta -->
                                <div id="alertMessage" class="alert" style="display: none;" role="alert">
                                    <span id="alertText"></span>
                                </div>

                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar Equipo
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

<?php 
include '../../layouts/footer.php'; 
?>