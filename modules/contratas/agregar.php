<?php
/**
 * OperaSys - Agregar Contrata
 * Archivo: modules/admin/contratas/agregar.php
 * Descripción: Formulario para registrar nueva contrata
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar sesión y permisos
verificarSesion();

if ($_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'supervisor') {
    header('Location: listar.php');
    exit;
}

// Variables para el layout
$page_title = 'Agregar Contrata';
$page_depth = 2;
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
                        <i class="fas fa-plus-circle"></i> Agregar Contrata
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="listar.php">Contratas</a></li>
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
                <div class="col-md-10 offset-md-1">
                    
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-building"></i> Datos de la Empresa Subcontratista
                            </h3>
                        </div>
                        
                        <form id="formAgregarContrata" method="POST">
                            <div class="card-body">
                                
                                <div class="row">
                                    <!-- Razón Social -->
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="razon_social">
                                                <i class="fas fa-building"></i> Razón Social <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="razon_social" 
                                                   name="razon_social" 
                                                   placeholder="Ej: CONSTRUCTORA ABC S.A.C."
                                                   required
                                                   maxlength="150">
                                        </div>
                                    </div>

                                    <!-- RUC -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="ruc">
                                                <i class="fas fa-id-card"></i> RUC <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="ruc" 
                                                   name="ruc" 
                                                   placeholder="20123456789"
                                                   required
                                                   maxlength="20"
                                                   pattern="[0-9]+"
                                                   title="Solo números">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <!-- Contacto -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="contacto">
                                                <i class="fas fa-user"></i> Persona de Contacto
                                            </label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="contacto" 
                                                   name="contacto" 
                                                   placeholder="Ej: Juan Pérez"
                                                   maxlength="100">
                                        </div>
                                    </div>

                                    <!-- Teléfono -->
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="telefono">
                                                <i class="fas fa-phone"></i> Teléfono
                                            </label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="telefono" 
                                                   name="telefono" 
                                                   placeholder="987654321"
                                                   maxlength="50">
                                        </div>
                                    </div>

                                    <!-- Email -->
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="email">
                                                <i class="fas fa-envelope"></i> Email
                                            </label>
                                            <input type="email" 
                                                   class="form-control" 
                                                   id="email" 
                                                   name="email" 
                                                   placeholder="contacto@empresa.com"
                                                   maxlength="100">
                                        </div>
                                    </div>
                                </div>

                                <!-- Dirección -->
                                <div class="form-group">
                                    <label for="direccion">
                                        <i class="fas fa-map-marker-alt"></i> Dirección
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="direccion" 
                                           name="direccion" 
                                           placeholder="Ej: Av. Principal 123, Lima"
                                           maxlength="255">
                                </div>

                                <hr>
                                <h5><i class="fas fa-calendar-alt"></i> Vigencia del Contrato (Opcional)</h5>

                                <div class="row">
                                    <!-- Fecha Inicio -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="fecha_inicio_contrato">
                                                <i class="fas fa-calendar-check"></i> Fecha Inicio
                                            </label>
                                            <input type="date" 
                                                   class="form-control" 
                                                   id="fecha_inicio_contrato" 
                                                   name="fecha_inicio_contrato">
                                            <small class="form-text text-muted">Opcional</small>
                                        </div>
                                    </div>

                                    <!-- Fecha Fin -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="fecha_fin_contrato">
                                                <i class="fas fa-calendar-times"></i> Fecha Fin
                                            </label>
                                            <input type="date" 
                                                   class="form-control" 
                                                   id="fecha_fin_contrato" 
                                                   name="fecha_fin_contrato">
                                            <small class="form-text text-muted">Opcional</small>
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
                                    <i class="fas fa-save"></i> Guardar Contrata
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