<?php
/**
 * OperaSys - Crear Reporte Diario
 * Archivo: modules/reportes/crear.php
 * Versión: 3.0 - Sistema HT/HP (SIN partidas)
 * Descripción: Formulario con actividades HT/HP y combustible
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

verificarSesion();

$page_title = 'Nuevo Reporte Diario';
$page_depth = 2;
$use_sweetalert = true;
$custom_js_file = 'assets/js/reportes.js?v=' . ASSETS_VERSION;
$custom_js_file = 'assets/js/combustible.js?v=' . ASSETS_VERSION;

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
            
            <!-- PASO 1: Seleccionar Equipo y Horómetro Inicial -->
            <div id="paso1" class="row">
                <div class="col-md-8 offset-md-2">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-truck-monster"></i> Paso 1: Datos Iniciales
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 
                                <strong>Fecha automática:</strong> <?php echo date('d/m/Y'); ?>
                            </div>
                            
                            <?php if ($_SESSION['rol'] === 'operador'): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-filter"></i> 
                                Solo se muestran los equipos de tu categoría
                            </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label for="equipo_id">
                                    <i class="fas fa-truck-monster"></i> Equipo <span class="text-danger">*</span>
                                </label>
                                <select class="form-control form-control-lg" id="equipo_id" required>
                                    <option value="">Cargando equipos...</option>
                                </select>
                                <small class="form-text text-muted" id="infoCategoria"></small>
                            </div>

                            <div class="form-group">
                                <label for="horometro_inicial">
                                    <i class="fas fa-tachometer-alt"></i> Horómetro Inicial <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       class="form-control form-control-lg" 
                                       id="horometro_inicial" 
                                       step="0.1" 
                                       placeholder="Ej: 1584.5"
                                       required>
                                <small class="form-text text-muted">
                                    Ingrese el horómetro al inicio del día
                                </small>
                            </div>
                            
                            <div id="alertPaso1" class="alert" style="display: none;"></div>
                            
                            <button type="button" id="btnIniciarReporte" class="btn btn-primary btn-lg btn-block">
                                <i class="fas fa-arrow-right"></i> Iniciar Reporte
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PASO 2: Registrar Actividades HT/HP -->
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
                            <button type="button" id="btnAgregarHT" class="btn btn-success">
                                <i class="fas fa-tools"></i> Agregar Hora Trabajada (HT)
                            </button>
                            <button type="button" id="btnAgregarHP" class="btn btn-warning">
                                <i class="fas fa-pause-circle"></i> Agregar Hora Parada (HP)
                            </button>
                            <button type="button" id="btnAgregarCombustible" class="btn btn-info">
                                <i class="fas fa-gas-pump"></i> Registrar Combustible
                            </button>
                        </div>

                        <!-- Card de Actividades HT/HP -->
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-tasks"></i> Actividades del Día (HT/HP)
                                </h3>
                            </div>
                            <div class="card-body">
                                <div id="listaActividades">
                                    <p class="text-muted text-center">
                                        <i class="fas fa-info-circle"></i> 
                                        No hay actividades registradas. Agregue HT (Horas Trabajadas) o HP (Horas Paradas).
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

                        <!-- Horómetro Final -->
                        <div class="card card-secondary">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-tachometer-alt"></i> Horómetro Final
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="horometro_final">
                                        Horómetro al finalizar el día <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="horometro_final" 
                                           step="0.1" 
                                           placeholder="Ej: 1594.2">
                                    <small class="form-text text-muted">
                                        Debe ser mayor al horómetro inicial
                                    </small>
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

<!-- Modal: Agregar Hora Trabajada (HT) -->
<div class="modal fade" id="modalHT" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title">
                    <i class="fas fa-tools"></i> Agregar Hora Trabajada (HT)
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="formHT">
                <div class="modal-body">
                    
                    <div class="alert alert-success">
                        <i class="fas fa-info-circle"></i> 
                        <strong>HT (Horas Trabajadas):</strong> Horas productivas donde el equipo realiza trabajo efectivo.
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-clock"></i> Hora Inicio <span class="text-danger">*</span>
                                </label>
                                <input type="time" 
                                       class="form-control" 
                                       id="hora_inicio_ht" 
                                       required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-clock"></i> Hora Fin <span class="text-danger">*</span>
                                </label>
                                <input type="time" 
                                       class="form-control" 
                                       id="hora_fin_ht" 
                                       required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-tasks"></i> Actividad <span class="text-danger">*</span>
                        </label>
                        <select class="form-control" id="actividad_ht_id" required>
                            <option value="">Cargando actividades...</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-comment"></i> Observaciones
                        </label>
                        <textarea class="form-control" 
                                  id="observaciones_ht" 
                                  rows="2"
                                  placeholder="Detalles adicionales (opcional)"></textarea>
                    </div>

                    <div id="alertHT" class="alert" style="display: none;"></div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar HT
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Agregar Hora Parada (HP) -->
<div class="modal fade" id="modalHP" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-pause-circle"></i> Agregar Hora Parada (HP)
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="formHP">
                <div class="modal-body">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle"></i> 
                        <strong>HP (Horas Paradas):</strong> Horas no productivas donde el equipo no trabaja.
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-clock"></i> Hora Inicio <span class="text-danger">*</span>
                                </label>
                                <input type="time" 
                                       class="form-control" 
                                       id="hora_inicio_hp" 
                                       required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-clock"></i> Hora Fin <span class="text-danger">*</span>
                                </label>
                                <input type="time" 
                                       class="form-control" 
                                       id="hora_fin_hp" 
                                       required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-exclamation-triangle"></i> Motivo de Parada <span class="text-danger">*</span>
                        </label>
                        <select class="form-control" id="motivo_hp_id" required>
                            <option value="">Cargando motivos...</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-comment"></i> Observaciones
                        </label>
                        <textarea class="form-control" 
                                  id="observaciones_hp" 
                                  rows="2"
                                  placeholder="Detalles adicionales (opcional)"></textarea>
                    </div>

                    <div id="alertHP" class="alert" style="display: none;"></div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Guardar HP
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
                            <i class="fas fa-clock"></i> Hora de Abastecimiento <span class="text-danger">*</span>
                        </label>
                        <input type="time" 
                               class="form-control" 
                               id="hora_abastecimiento" 
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