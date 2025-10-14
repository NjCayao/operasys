<?php
/**
 * OperaSys - Editar Equipo
 * Archivo: modules/equipos/editar.php
 * Descripción: Formulario para modificar equipo existente
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
    $stmt = $pdo->prepare("SELECT * FROM equipos WHERE id = ?");
    $stmt->execute([$equipoId]);
    $equipo = $stmt->fetch();
    
    if (!$equipo) {
        header('Location: listar.php?error=equipo_no_encontrado');
        exit;
    }
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
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
                <div class="col-md-8 offset-md-2">
                    
                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-wrench"></i> Modificar datos del equipo: <strong><?php echo htmlspecialchars($equipo['codigo']); ?></strong>
                            </h3>
                        </div>
                        
                        <form id="formEditarEquipo" method="POST">
                            <input type="hidden" name="id" value="<?php echo $equipo['id']; ?>">
                            
                            <div class="card-body">
                                
                                <!-- Categoría -->
                                <div class="form-group">
                                    <label for="categoria">
                                        <i class="fas fa-tag"></i> Categoría <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control" id="categoria" name="categoria" required>
                                        <option value="">Seleccionar categoría</option>
                                        <?php
                                        $categorias = ['Excavadora', 'Volquete', 'Tractor', 'Motoniveladora', 
                                                      'Cargador', 'Retroexcavadora', 'Bulldozer', 'Compactadora', 
                                                      'Grúa', 'Otro'];
                                        foreach ($categorias as $cat) {
                                            $selected = ($equipo['categoria'] === $cat) ? 'selected' : '';
                                            echo "<option value='$cat' $selected>$cat</option>";
                                        }
                                        ?>
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
                                           value="<?php echo htmlspecialchars($equipo['codigo']); ?>"
                                           required
                                           maxlength="20"
                                           pattern="[A-Z0-9]+"
                                           title="Solo letras mayúsculas y números, sin espacios">
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
                                              maxlength="255"><?php echo htmlspecialchars($equipo['descripcion'] ?? ''); ?></textarea>
                                </div>

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

<?php 
$custom_js_file = 'assets/js/editar_usuario.js?v=' . ASSETS_VERSION;
include '../../layouts/footer.php'; 
?>