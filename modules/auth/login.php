<?php
/**
 * OperaSys - Vista de Login
 * Archivo: modules/auth/login.php
 * Descripción: Formulario de inicio de sesión
 */

require_once '../../config/config.php';

// Si ya está autenticado, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ../admin/dashboard.php');
    exit;
}

// Configuración del layout
$page_title = 'Iniciar Sesión';
$auth_base_path = '../../';
$body_class = 'login-page';
$custom_js_file = 'assets/js/login.js';

include '../../layouts/auth_header.php';
?>

<div class="login-box">
    <div class="card card-outline card-primary">
        <div class="card-header text-center">
            <h1><b>Opera</b>Sys</h1>
            <p class="text-muted">Sistema de Reportes de Operación</p>
        </div>
        <div class="card-body">
            <p class="login-box-msg">Inicia sesión con tu DNI</p>

            <form id="formLogin" method="POST">
                <div class="input-group mb-3">
                    <input type="text" 
                           class="form-control" 
                           name="dni" 
                           placeholder="DNI" 
                           required
                           autocomplete="username">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-id-card"></span>
                        </div>
                    </div>
                </div>

                <div class="input-group mb-3">
                    <input type="password" 
                           class="form-control" 
                           name="password" 
                           placeholder="Contraseña" 
                           required
                           autocomplete="current-password">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-lock"></span>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                        </button>
                    </div>
                </div>
            </form>

            <p class="mb-0 mt-3 text-center">
                <a href="register.php" class="text-center">¿No tienes cuenta? Regístrate</a>
            </p>

            <!-- Mensaje de alerta -->
            <div id="alertMessage" class="alert alert-danger mt-3" style="display: none;" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <span id="alertText"></span>
            </div>
        </div>
    </div>
</div>

<?php include '../../layouts/auth_footer.php'; ?>

<?php 
$custom_js_file = 'assets/js/login.js?v=' . ASSETS_VERSION;
include '../../layouts/auth_footer.php'; 
?>