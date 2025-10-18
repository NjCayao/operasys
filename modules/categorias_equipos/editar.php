<?php
/**
 * OperaSys - Editar Categoría de Equipo
 * Archivo: modules/categorias_equipos/editar.php
 * Descripción: Formulario para modificar categoría existente
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar sesión y permisos
verificarSesion();

// Solo admin puede editar
if ($_SESSION['rol'] !== 'admin') {
    header('Location: listar.php');
    exit;
}

// Obtener ID de la categoría
$categoriaId = $_GET['id'] ?? 0;

if (!$categoriaId) {
    header('Location: listar.php');
    exit;
}

// Obtener datos de la categoría
try {
    $stmt = $pdo->prepare("
        SELECT c.*, COUNT(e.id) as total_equipos
        FROM categorias_equipos c
        LEFT JOIN equipos e ON c.id = e.categoria_id
        WHERE c.id = ?
        GROUP BY c.id
    ");
    $stmt->execute([$categoriaId]);
    $categoria = $stmt->fetch();
    
    if (!$categoria) {
        header('Location: listar.php?error=categoria_no_encontrada');
        exit;
    }
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}

// Variables para el layout
$page_title = 'Editar Categoría';
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
                        <i class="fas fa-edit"></i> Editar Categoría
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="listar.php">Categorías</a></li>
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
                                <i class="fas fa-wrench"></i> Modificar: <strong><?php echo htmlspecialchars($categoria['nombre']); ?></strong>
                            </h3>
                        </div>
                        
                        <form id="formEditarCategoria" method="POST">
                            <input type="hidden" name="id" value="<?php echo $categoria['id']; ?>">
                            
                            <div class="card-body">
                                
                                <?php if ($categoria['total_equipos'] > 0): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> 
                                    <strong>Nota:</strong> Esta categoría tiene <?php echo $categoria['total_equipos']; ?> equipo(s) asociado(s).
                                </div>
                                <?php endif; ?>

                                <!-- Nombre -->
                                <div class="form-group">
                                    <label for="nombre">
                                        <i class="fas fa-bookmark"></i> Nombre de la Categoría <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="nombre" 
                                           name="nombre" 
                                           value="<?php echo htmlspecialchars($categoria['nombre']); ?>"
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
                                              maxlength="255"><?php echo htmlspecialchars($categoria['descripcion'] ?? ''); ?></textarea>
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
                                                   value="<?php echo $categoria['consumo_default']; ?>">
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
                                                   value="<?php echo $categoria['capacidad_default']; ?>">
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <!-- Estado -->
                                <div class="form-group">
                                    <label for="estado">
                                        <i class="fas fa-toggle-on"></i> Estado
                                    </label>
                                    <select class="form-control" id="estado" name="estado">
                                        <option value="1" <?php echo ($categoria['estado'] == 1) ? 'selected' : ''; ?>>Activo</option>
                                        <option value="0" <?php echo ($categoria['estado'] == 0) ? 'selected' : ''; ?>>Inactivo</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Las categorías inactivas no aparecerán en los formularios
                                    </small>
                                </div>

                                <!-- Alerta -->
                                <div id="alertMessage" class="alert" style="display: none;" role="alert">
                                    <span id="alertText"></span>
                                </div>

                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-save"></i> Actualizar Categoría
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