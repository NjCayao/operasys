<?php
/**
 * OperaSys - Crear Reporte Diario
 * Archivo: modules/reportes/crear.php
 * Descripción: Formulario con actividades dinámicas y combustible
 * MODIFICADO: Select de equipos filtrado por categoría del operador
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

verificarSesion();

// Variables para el layout
$page_title = 'Nuevo Reporte Diario';
$page_depth = 2;
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
                    <h1 class="m-0">
                        <i class="fas fa-plus-circle"></i> Nuevo Reporte Diario
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="listar.php">Reportes</a></li>
                        <li class="breadcrumb-item active">Crear</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            
            <!-- PASO 1: Seleccionar Equipo -->
            <div id="paso1" class="row">
                <div class="col-md-8 offset-md-2">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-truck-monster"></i> Paso 1: Seleccionar Equipo
                            </h3>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Seleccione el equipo con el que trabajará hoy. 
                                <strong>La fecha se registrará automáticamente como hoy (<?php echo date('d/m/Y'); ?>)</strong>
                            </p>
                            
                            <?php if ($_SESSION['rol'] === 'operador'): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-filter"></i> 
                                <strong>Filtrado automático:</strong> Solo se muestran los equipos de tu categoría asignada
                            </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label for="equipo_id">
                                    <i class="fas fa-truck-monster"></i> Equipo <span class="text-danger">*</span>
                                </label>
                                <select class="form-control form-control-lg" id="equipo_id" required>
                                    <option value="">Cargando tus equipos...</option>
                                </select>
                                <small class="form-text text-muted" id="infoCategoria"></small>
                            </div>
                            
                            <div id="alertPaso1" class="alert" style="display: none;"></div>
                            
                            <button type="button" id="btnIniciarReporte" class="btn btn-primary btn-lg btn-block">
                                <i class="fas fa-arrow-right"></i> Iniciar Reporte
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PASO 2: Reporte con Actividades (Oculto inicialmente) -->
            <div id="paso2" style="display: none;">
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Reporte iniciado:</strong> 
                            Fecha: <strong><?php echo date('d/m/Y'); ?></strong> | 
                            Equipo: <strong id="equipoSeleccionado"></strong>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        
                        <!-- Botones de Acción -->
                        <div class="mb-3">
                            <button type="button" id="btnAgregarActividad" class="btn btn-success">
                                <i class="fas fa-plus"></i> Agregar Actividad
                            </button>
                            <button type="button" id="btnAgregarCombustible" class="btn btn-info">
                                <i class="fas fa-gas-pump"></i> Registrar Combustible
                            </button>
                        </div>

                        <!-- Card de Actividades -->
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-tasks"></i> Actividades del Día
                                </h3>
                            </div>
                            <div class="card-body">
                                <div id="listaActividades">
                                    <p class="text-muted text-center">
                                        <i class="fas fa-info-circle"></i> 
                                        No hay actividades registradas. Click en "Agregar Actividad" para comenzar.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Card de Combustible -->
                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-gas-pump"></i> Abastecimientos de Combustible
                                </h3>
                            </div>
                            <div class="card-body">
                                <div id="listaCombustible">
                                    <p class="text-muted text-center">
                                        <i class="fas fa-info-circle"></i> 
                                        No hay abastecimientos registrados.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Observaciones Generales -->
                        <div class="card card-secondary">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-comment"></i> Observaciones Generales
                                </h3>
                            </div>
                            <div class="card-body">
                                <textarea class="form-control" 
                                          id="observaciones_generales" 
                                          rows="4"
                                          placeholder="Observaciones generales del día de trabajo (opcional)"></textarea>
                            </div>
                        </div>

                        <!-- Botones Finales -->
                        <div class="mb-3">
                            <button type="button" id="btnGuardarBorrador" class="btn btn-warning btn-lg">
                                <i class="fas fa-save"></i> Guardar Borrador
                            </button>
                            <button type="button" id="btnFinalizarReporte" class="btn btn-success btn-lg">
                                <i class="fas fa-check"></i> Finalizar y Enviar
                            </button>
                            <a href="listar.php" class="btn btn-secondary btn-lg">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<!-- Modal: Agregar Actividad -->
<div class="modal fade" id="modalActividad" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title">
                    <i class="fas fa-plus"></i> Agregar Actividad
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="formActividad">
                <div class="modal-body">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-tasks"></i> Tipo de Trabajo <span class="text-danger">*</span>
                                </label>
                                <select class="form-control" id="tipo_trabajo_id" required>
                                    <option value="">Cargando...</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-tag"></i> Fase de Costo <span class="text-danger">*</span>
                                </label>
                                <select class="form-control" id="fase_costo_id" required>
                                    <option value="">Cargando...</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-tachometer-alt"></i> Horómetro Inicial <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       id="horometro_inicial" 
                                       step="0.1" 
                                       placeholder="Ej: 1584.5"
                                       required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-tachometer-alt"></i> Horómetro Final <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       id="horometro_final" 
                                       step="0.1" 
                                       placeholder="Ej: 1585.9"
                                       required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-comment"></i> Observaciones
                        </label>
                        <textarea class="form-control" 
                                  id="observaciones_actividad" 
                                  rows="2"
                                  placeholder="Detalles adicionales (opcional)"></textarea>
                    </div>

                    <div id="alertActividad" class="alert" style="display: none;"></div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar Actividad
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Agregar Combustible -->
<div class="modal fade" id="modalCombustible" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title">
                    <i class="fas fa-gas-pump"></i> Registrar Combustible
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="formCombustible">
                <div class="modal-body">
                    
                    <div class="form-group">
                        <label>
                            <i class="fas fa-tachometer-alt"></i> Horómetro <span class="text-danger">*</span>
                        </label>
                        <input type="number" 
                               class="form-control" 
                               id="horometro_combustible" 
                               step="0.1" 
                               placeholder="Ej: 1586.5"
                               required>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-gas-pump"></i> Galones <span class="text-danger">*</span>
                        </label>
                        <input type="number" 
                               class="form-control" 
                               id="galones" 
                               step="0.01" 
                               placeholder="Ej: 45.00"
                               required>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-comment"></i> Observaciones
                        </label>
                        <textarea class="form-control" 
                                  id="observaciones_combustible" 
                                  rows="2"
                                  placeholder="Detalles adicionales (opcional)"></textarea>
                    </div>

                    <div id="alertCombustible" class="alert" style="display: none;"></div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-info">
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