<?php
/**
 * OperaSys - Perfil de Usuario
 * Archivo: modules/usuarios/perfil.php
 * Descripción: Ver y editar perfil del usuario
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

verificarSesion();

$userId = $_SESSION['user_id'];

// Obtener datos del usuario
try {
    $stmt = $pdo->prepare("
        SELECT id, nombre_completo, dni, cargo, rol, firma, fecha_creacion 
        FROM usuarios 
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Error al obtener perfil');
}

// Configuración de la página
$page_title = 'Mi Perfil';
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
                    <h1><i class="fas fa-user-circle"></i> Mi Perfil</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../admin/dashboard.php">Inicio</a></li>
                        <li class="breadcrumb-item active">Mi Perfil</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <!-- Información del usuario -->
                <div class="col-md-4">
                    <div class="card card-primary card-outline">
                        <div class="card-body box-profile">
                            <div class="text-center">
                                <i class="fas fa-user-circle fa-5x text-primary"></i>
                            </div>

                            <h3 class="profile-username text-center">
                                <?php echo htmlspecialchars($usuario['nombre_completo']); ?>
                            </h3>

                            <p class="text-muted text-center">
                                <?php echo htmlspecialchars($usuario['cargo']); ?>
                            </p>

                            <ul class="list-group list-group-unbordered mb-3">
                                <li class="list-group-item">
                                    <b>DNI</b>
                                    <span class="float-right"><?php echo htmlspecialchars($usuario['dni']); ?></span>
                                </li>
                                <li class="list-group-item">
                                    <b>Rol</b>
                                    <span class="float-right">
                                        <span class="badge badge-<?php 
                                            echo $usuario['rol'] == 'admin' ? 'danger' : 
                                                ($usuario['rol'] == 'supervisor' ? 'warning' : 'info'); 
                                        ?>">
                                            <?php echo ucfirst($usuario['rol']); ?>
                                        </span>
                                    </span>
                                </li>
                                <li class="list-group-item">
                                    <b>Miembro desde</b>
                                    <span class="float-right">
                                        <?php echo date('d/m/Y', strtotime($usuario['fecha_creacion'])); ?>
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Firma digital -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-signature"></i> Firma Digital
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($usuario['firma'])): ?>
                                <div class="text-center">
                                    <img src="<?php echo $usuario['firma']; ?>" 
                                         alt="Firma" 
                                         class="img-fluid border"
                                         style="max-width: 400px; max-height: 200px;">
                                </div>
                                <div class="text-center mt-3">
                                    <a href="firma.php" class="btn btn-warning">
                                        <i class="fas fa-pen"></i> Actualizar Firma
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>No tienes firma registrada</strong><br>
                                    Necesitas capturar tu firma digital para poder crear reportes.
                                </div>
                                <div class="text-center">
                                    <a href="firma.php" class="btn btn-primary">
                                        <i class="fas fa-signature"></i> Capturar Firma Ahora
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Cambiar contraseña -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-key"></i> Cambiar Contraseña
                            </h3>
                        </div>
                        <form id="formCambiarPassword">
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Contraseña Actual *</label>
                                    <input type="password" 
                                           class="form-control" 
                                           name="password_actual" 
                                           required
                                           minlength="6">
                                </div>
                                <div class="form-group">
                                    <label>Nueva Contraseña *</label>
                                    <input type="password" 
                                           class="form-control" 
                                           name="password_nueva" 
                                           id="password_nueva"
                                           required
                                           minlength="6">
                                </div>
                                <div class="form-group">
                                    <label>Confirmar Nueva Contraseña *</label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password_confirmar"
                                           required
                                           minlength="6">
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Cambiar Contraseña
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php 
$custom_js_file = 'assets/js/perfil.js?v=' . ASSETS_VERSION;
include '../../layouts/footer.php'; 
?>