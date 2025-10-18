<?php
/**
 * OperaSys - Editar Equipo V3.2
 * Archivo: modules/equipos/editar.php
 * Descripción: Formulario para modificar equipo existente con categorías dinámicas
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar sesión y permisos
verificarSesion();

if ($_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'supervisor') {
    header('Location: listar.php');
    exit;
}

// Obtener ID del equipo
$equipoId = $_GET['id'] ?? 0;

if (!$equipoId) {
    header('Location: listar.php');
    exit;
}

// Obtener datos del equipo
try {
    $stmt = $pdo->prepare("
        SELECT e.*, c.razon_social as nombre_contrata, cat.nombre as categoria_nombre
        FROM equipos e
        LEFT JOIN contratas c ON e.contrata_id = c.id
        LEFT JOIN categorias_equipos cat ON e.categoria_id = cat.id
        WHERE e.id = ?
    ");
    $stmt->execute([$equipoId]);
    $equipo = $stmt->fetch();
    
    if (!$equipo) {
        header('Location: listar.php?error=equipo_no_encontrado');
        exit;
    }
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}

// Obtener categorías activas desde BD
try {
    $stmt = $pdo->query("SELECT id, nombre FROM categorias_equipos WHERE estado = 1 ORDER BY orden ASC, nombre ASC");
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
$page_title = 'Editar Equipo';
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
                        <i class="fas fa-edit"></i> Editar Equipo
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="listar.php">Equipos</a></li>
                        <li class="breadcrumb-item active">Editar</li>
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
                    
                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-wrench"></i> Modificar datos del equipo: <strong><?php echo htmlspecialchars($equipo['codigo']); ?></strong>
                            </h3>
                        </div>
                        
                        <form id="formEditarEquipo" method="POST">
                            <input type="hidden" name="id" value="<?php echo $equipo['id']; ?>">
                            
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
                                                    <option value="<?php echo $cat['id']; ?>"
                                                            <?php echo ($equipo['categoria_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($cat['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
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
                                                   value="<?php echo htmlspecialchars($equipo['codigo']); ?>"
                                                   required
                                                   maxlength="20"
                                                   pattern="[A-Z0-9]+"
                                                   title="Solo letras mayúsculas y números, sin espacios">
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
                                              maxlength="255"><?php echo htmlspecialchars($equipo['descripcion'] ?? ''); ?></textarea>
                                </div>

                                <hr>
                                <h5><i class="fas fa-handshake"></i> Información de Contrata</h5>

                                <!-- Propietario -->
                                <div class="form-group">
                                    <label for="contrata_id">
                                        <i class="fas fa-building"></i> Propietario del Equipo
                                    </label>
                                    <select class="form-control" id="contrata_id" name="contrata_id">
                                        <option value="" <?php echo empty($equipo['contrata_id']) ? 'selected' : ''; ?>>
                                            Equipo Propio (Empresa)
                                        </option>
                                        <?php foreach ($contratas as $contrata): ?>
                                            <option value="<?php echo $contrata['id']; ?>"
                                                    <?php echo ($equipo['contrata_id'] == $contrata['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($contrata['razon_social']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Campos de tarifa -->
                                <div id="camposTarifa" style="display: <?php echo !empty($equipo['contrata_id']) ? 'block' : 'none'; ?>;">
                                    <div class="row">
                                        <!-- Tipo de Tarifa -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="tipo_tarifa">
                                                    <i class="fas fa-clock"></i> Tipo de Tarifa
                                                </label>
                                                <select class="form-control" id="tipo_tarifa" name="tipo_tarifa">
                                                    <option value="hora" <?php echo ($equipo['tipo_tarifa'] == 'hora') ? 'selected' : ''; ?>>Por Hora</option>
                                                    <option value="dia" <?php echo ($equipo['tipo_tarifa'] == 'dia') ? 'selected' : ''; ?>>Por Día</option>
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
                                                       value="<?php echo $equipo['tarifa_alquiler'] ?? ''; ?>"
                                                       placeholder="Opcional">
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
                                                  maxlength="500"><?php echo htmlspecialchars($equipo['observaciones_contrata'] ?? ''); ?></textarea>
                                    </div>
                                </div>

                                <hr>

                                <!-- Estado -->
                                <div class="form-group">
                                    <label for="estado">
                                        <i class="fas fa-toggle-on"></i> Estado
                                    </label>
                                    <select class="form-control" id="estado" name="estado">
                                        <option value="1" <?php echo ($equipo['estado'] == 1) ? 'selected' : ''; ?>>Activo</option>
                                        <option value="0" <?php echo ($equipo['estado'] == 0) ? 'selected' : ''; ?>>Inactivo</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Los equipos inactivos no aparecerán en los formularios de reportes
                                    </small>
                                </div>

                                <!-- Alerta -->
                                <div id="alertMessage" class="alert" style="display: none;" role="alert">
                                    <span id="alertText"></span>
                                </div>

                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-save"></i> Actualizar Equipo
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