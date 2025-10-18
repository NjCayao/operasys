<?php
/**
 * OperaSys - Editar Contrata
 * Archivo: modules/admin/contratas/editar.php
 * Descripción: Formulario para modificar contrata existente
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar sesión y permisos
verificarSesion();

if ($_SESSION['rol'] !== 'admin') {
    header('Location: listar.php');
    exit;
}

// Obtener ID de la contrata
$contrataId = $_GET['id'] ?? 0;

if (!$contrataId) {
    header('Location: listar.php');
    exit;
}

// Obtener datos de la contrata
try {
    $stmt = $pdo->prepare("SELECT * FROM contratas WHERE id = ?");
    $stmt->execute([$contrataId]);
    $contrata = $stmt->fetch();
    
    if (!$contrata) {
        header('Location: listar.php?error=contrata_no_encontrada');
        exit;
    }
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}

// Variables para el layout
$page_title = 'Editar Contrata';
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
                        <i class="fas fa-edit"></i> Editar Contrata
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="listar.php">Contratas</a></li>
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
                                <i class="fas fa-wrench"></i> Modificar datos: <strong><?php echo htmlspecialchars($contrata['razon_social']); ?></strong>
                            </h3>
                        </div>
                        
                        <form id="formEditarContrata" method="POST">
                            <input type="hidden" name="id" value="<?php echo $contrata['id']; ?>">
                            
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
                                                   value="<?php echo htmlspecialchars($contrata['razon_social']); ?>"
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
                                                   value="<?php echo htmlspecialchars($contrata['ruc']); ?>"
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
                                                   value="<?php echo htmlspecialchars($contrata['contacto'] ?? ''); ?>"
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
                                                   value="<?php echo htmlspecialchars($contrata['telefono'] ?? ''); ?>"
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
                                                   value="<?php echo htmlspecialchars($contrata['email'] ?? ''); ?>"
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
                                           value="<?php echo htmlspecialchars($contrata['direccion'] ?? ''); ?>"
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
                                                   name="fecha_inicio_contrato"
                                                   value="<?php echo $contrata['fecha_inicio_contrato'] ?? ''; ?>">
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
                                                   name="fecha_fin_contrato"
                                                   value="<?php echo $contrata['fecha_fin_contrato'] ?? ''; ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Estado -->
                                <div class="form-group">
                                    <label for="estado">
                                        <i class="fas fa-toggle-on"></i> Estado
                                    </label>
                                    <select class="form-control" id="estado" name="estado">
                                        <option value="1" <?php echo ($contrata['estado'] == 1) ? 'selected' : ''; ?>>Activo</option>
                                        <option value="0" <?php echo ($contrata['estado'] == 0) ? 'selected' : ''; ?>>Inactivo</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Las contratas inactivas no aparecerán en los formularios
                                    </small>
                                </div>

                                <!-- Alerta -->
                                <div id="alertMessage" class="alert" style="display: none;" role="alert">
                                    <span id="alertText"></span>
                                </div>

                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-save"></i> Actualizar Contrata
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