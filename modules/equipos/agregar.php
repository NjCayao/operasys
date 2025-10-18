<?php
/**
 * OperaSys - Agregar Equipo V3.2
 * Archivo: modules/equipos/agregar.php
 * Descripción: Formulario para registrar nuevo equipo con categorías dinámicas
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

// Obtener categorías activas desde BD
try {
    $stmt = $pdo->query("SELECT id, nombre, consumo_default, capacidad_default FROM categorias_equipos WHERE estado = 1 ORDER BY orden ASC, nombre ASC");
    $categorias = $stmt->fetchAll();
} catch (PDOException $e) {
    $categorias = [];
}

// Obtener contratas activas
try {
    $stmt = $pdo->query("SELECT id, razon_social FROM contratas WHERE estado = 1 ORDER BY razon_social ASC");
    $contratas = $stmt->fetchAll();
} catch (PDOException $e) {
    $contratas = [];
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
                <div class="col-md-10 offset-md-1">
                    
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-edit"></i> Datos del Equipo
                            </h3>
                        </div>
                        
                        <form id="formAgregarEquipo" method="POST">
                            <div class="card-body">
                                
                                <div class="row">
                                    <!-- Categoría (desde BD) -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="categoria_id">
                                                <i class="fas fa-tag"></i> Categoría <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-control" id="categoria_id" name="categoria_id" required>
                                                <option value="">Seleccionar categoría</option>
                                                <?php foreach ($categorias as $cat): ?>
                                                    <option value="<?php echo $cat['id']; ?>">
                                                        <?php echo htmlspecialchars($cat['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="form-text text-muted">
                                                Define el tipo de equipo
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Código -->
                                    <div class="col-md-6">
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
                                    </div>
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
                                              placeholder="Ej: Excavadora Caterpillar 320D, color amarillo, año 2020"
                                              maxlength="255"></textarea>
                                </div>

                                <hr>
                                <h5><i class="fas fa-handshake"></i> Información de Contrata</h5>

                                <!-- Propietario -->
                                <div class="form-group">
                                    <label for="contrata_id">
                                        <i class="fas fa-building"></i> Propietario del Equipo
                                    </label>
                                    <select class="form-control" id="contrata_id" name="contrata_id">
                                        <option value="">Equipo Propio (Empresa)</option>
                                        <?php foreach ($contratas as $contrata): ?>
                                            <option value="<?php echo $contrata['id']; ?>">
                                                <?php echo htmlspecialchars($contrata['razon_social']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">
                                        Selecciona la contrata si el equipo es alquilado
                                    </small>
                                </div>

                                <!-- Campos de tarifa (solo si es alquilado) -->
                                <div id="camposTarifa" style="display: none;">
                                    <div class="row">
                                        <!-- Tipo de Tarifa -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="tipo_tarifa">
                                                    <i class="fas fa-clock"></i> Tipo de Tarifa
                                                </label>
                                                <select class="form-control" id="tipo_tarifa" name="tipo_tarifa">
                                                    <option value="hora">Por Hora</option>
                                                    <option value="dia">Por Día</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Tarifa Alquiler -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="tarifa_alquiler">
                                                    <i class="fas fa-dollar-sign"></i> Tarifa de Alquiler (S/)
                                                </label>
                                                <input type="number" 
                                                       class="form-control" 
                                                       id="tarifa_alquiler" 
                                                       name="tarifa_alquiler" 
                                                       step="0.01"
                                                       min="0"
                                                       placeholder="Ej: 150.00">
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>&nbsp;</label>
                                                <div class="alert alert-info mb-0 py-2">
                                                    <small><i class="fas fa-info-circle"></i> Monto opcional</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Observaciones Contrata -->
                                    <div class="form-group">
                                        <label for="observaciones_contrata">
                                            <i class="fas fa-comment"></i> Observaciones de Alquiler
                                        </label>
                                        <textarea class="form-control" 
                                                  id="observaciones_contrata" 
                                                  name="observaciones_contrata" 
                                                  rows="2"
                                                  placeholder="Ej: Incluye operador, mantenimiento por cuenta de contrata, etc."
                                                  maxlength="500"></textarea>
                                    </div>
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

<script>
// Mostrar/ocultar campos de tarifa según si es equipo propio o alquilado
document.getElementById('contrata_id').addEventListener('change', function() {
    const camposTarifa = document.getElementById('camposTarifa');
    if (this.value !== '') {
        camposTarifa.style.display = 'block';
    } else {
        camposTarifa.style.display = 'none';
    }
});
</script>

<?php 
include '../../layouts/footer.php'; 
?>