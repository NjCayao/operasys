<?php
/**
 * OperaSys - Editar Usuario
 * Archivo: modules/usuarios/editar.php
 * Descripción: Formulario para editar datos de usuario (Solo Admin)
 */
require_once '../../config/config.php';
require_once '../../config/database.php';

verificarAdmin(); // Solo administradores

// Obtener ID del usuario a editar
$userId = $_GET['id'] ?? 0;
$filtroRol = $_GET['rol'] ?? '';

if ($userId == 0) {
    header('Location: listar.php');
    exit;
}

// Obtener datos del usuario
try {
    $stmt = $pdo->prepare("SELECT id, nombre_completo, dni, cargo, rol, estado FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        header('Location: listar.php');
        exit;
    }
} catch (PDOException $e) {
    die('Error al obtener usuario');
}

// Extraer categoría si es operador
$categoriaActual = '';
if ($usuario['rol'] === 'operador' && strpos($usuario['cargo'], 'Operador de ') === 0) {
    $categoriaActual = str_replace('Operador de ', '', $usuario['cargo']);
}

// Configuración de la página
$page_title = 'Editar Usuario';
$page_depth = 2;
$use_sweetalert = true;

// Incluir layouts
include '../../layouts/header.php';
include '../../layouts/navbar.php';
include '../../layouts/sidebar.php';
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
    <!-- Content Header -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-user-edit"></i> Editar Usuario</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../admin/dashboard.php">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="listar.php<?php echo !empty($filtroRol) ? '?rol=' . $filtroRol : ''; ?>">Usuarios</a></li>
                        <li class="breadcrumb-item active">Editar</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8 offset-md-2">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-edit"></i> Modificar Datos del Usuario
                            </h3>
                        </div>
                        
                        <form id="formEditarUsuario" method="POST">
                            <div class="card-body">
                                
                                <!-- ID oculto -->
                                <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                                <input type="hidden" name="filtro_rol" value="<?php echo htmlspecialchars($filtroRol); ?>">
                                
                                <!-- Nombre Completo -->
                                <div class="form-group">
                                    <label for="nombre_completo">
                                        <i class="fas fa-user"></i> Nombre Completo *
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="nombre_completo"
                                           name="nombre_completo" 
                                           value="<?php echo htmlspecialchars($usuario['nombre_completo']); ?>"
                                           required
                                           minlength="3"
                                           maxlength="150"
                                           placeholder="Nombres y Apellidos">
                                </div>

                                <!-- DNI -->
                                <div class="form-group">
                                    <label for="dni">
                                        <i class="fas fa-id-card"></i> DNI / Cédula *
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="dni"
                                           name="dni" 
                                           value="<?php echo htmlspecialchars($usuario['dni']); ?>"
                                           required
                                           pattern="[0-9]{8,20}"
                                           maxlength="20"
                                           title="Solo números (8-20 dígitos)"
                                           placeholder="Ingrese DNI o Cédula">
                                    <small class="form-text text-muted">
                                        Solo números, entre 8 y 20 dígitos
                                    </small>
                                </div>

                                <!-- Rol -->
                                <div class="form-group">
                                    <label for="rol">
                                        <i class="fas fa-user-tag"></i> Rol del Sistema *
                                    </label>
                                    <select class="form-control" id="rol" name="rol" required>
                                        <option value="operador" <?php echo $usuario['rol'] == 'operador' ? 'selected' : ''; ?>>Operador</option>
                                        <option value="supervisor" <?php echo $usuario['rol'] == 'supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                                        <option value="admin" <?php echo $usuario['rol'] == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Define los permisos del usuario en el sistema
                                    </small>
                                </div>

                                <!-- Categoría de Equipo (solo para operadores) -->
                                <div class="form-group" id="categoriaContainer" style="<?php echo $usuario['rol'] === 'operador' ? '' : 'display: none;'; ?>">
                                    <label for="categoria_equipo">
                                        <i class="fas fa-truck-monster"></i> Categoría de Equipo *
                                    </label>
                                    <select class="form-control" id="categoria_equipo" name="categoria_equipo">
                                        <option value="">Seleccionar Categoría</option>
                                        <option value="Excavadora" <?php echo $categoriaActual == 'Excavadora' ? 'selected' : ''; ?>>Excavadora</option>
                                        <option value="Volquete" <?php echo $categoriaActual == 'Volquete' ? 'selected' : ''; ?>>Volquete</option>
                                        <option value="Tractor" <?php echo $categoriaActual == 'Tractor' ? 'selected' : ''; ?>>Tractor</option>
                                        <option value="Cargador Frontal" <?php echo $categoriaActual == 'Cargador Frontal' ? 'selected' : ''; ?>>Cargador Frontal</option>
                                        <option value="Rodillo Compactador" <?php echo $categoriaActual == 'Rodillo Compactador' ? 'selected' : ''; ?>>Rodillo Compactador</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Tipo de maquinaria que opera
                                    </small>
                                </div>

                                <!-- Campo oculto para el cargo -->
                                <input type="hidden" name="cargo" id="cargoHidden">

                                <hr>

                                <!-- Cambiar Contraseña (Opcional) -->
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" 
                                               class="custom-control-input" 
                                               id="cambiarPassword">
                                        <label class="custom-control-label" for="cambiarPassword">
                                            <i class="fas fa-key"></i> Cambiar Contraseña
                                        </label>
                                    </div>
                                </div>

                                <!-- Campos de contraseña (ocultos por defecto) -->
                                <div id="passwordFields" style="display: none;">
                                    <div class="form-group">
                                        <label for="password">
                                            <i class="fas fa-lock"></i> Nueva Contraseña
                                        </label>
                                        <input type="password" 
                                               class="form-control" 
                                               id="password"
                                               name="password"
                                               minlength="6"
                                               placeholder="Mínimo 6 caracteres">
                                    </div>

                                    <div class="form-group">
                                        <label for="password_confirm">
                                            <i class="fas fa-lock"></i> Confirmar Nueva Contraseña
                                        </label>
                                        <input type="password" 
                                               class="form-control" 
                                               id="password_confirm"
                                               name="password_confirm"
                                               minlength="6"
                                               placeholder="Repita la contraseña">
                                    </div>
                                </div>

                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar Cambios
                                </button>
                                <a href="listar.php<?php echo !empty($filtroRol) ? '?rol=' . $filtroRol : ''; ?>" class="btn btn-secondary">
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