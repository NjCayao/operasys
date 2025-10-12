<?php
/**
 * OperaSys - Registro de Usuario
 * Archivo: modules/auth/register.php
 * Descripción: Formulario para registrar nuevos operadores
 */

require_once '../../config/config.php';

// Si ya está autenticado, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ../admin/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - <?php echo SITE_NAME; ?></title>
    
    <!-- AdminLTE CSS CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/custom.css">
</head>
<body class="hold-transition register-page">
    <div class="register-box">
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <h1><b>Opera</b>Sys</h1>
                <p class="text-muted">Registro de Nuevo Operador</p>
            </div>
            <div class="card-body">
                <p class="login-box-msg">Completa tus datos para registrarte</p>

                <form id="formRegistro" method="POST">
                    <!-- Nombre Completo -->
                    <div class="input-group mb-3">
                        <input type="text" 
                               class="form-control" 
                               name="nombre_completo" 
                               placeholder="Nombres y Apellidos" 
                               required
                               minlength="3"
                               maxlength="150">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user"></span>
                            </div>
                        </div>
                    </div>

                    <!-- DNI -->
                    <div class="input-group mb-3">
                        <input type="text" 
                               class="form-control" 
                               name="dni" 
                               placeholder="DNI o Código Interno" 
                               required
                               pattern="[0-9]{8,20}"
                               title="Ingrese un DNI válido (8-20 dígitos)"
                               maxlength="20">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-id-card"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Cargo -->
                    <div class="input-group mb-3">
                        <select class="form-control" name="cargo" required>
                            <option value="">Seleccionar Cargo</option>
                            <option value="Operador">Operador</option>
                            <option value="Supervisor">Supervisor</option>
                            <option value="Mecánico">Mecánico</option>
                            <option value="Técnico">Técnico</option>
                        </select>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-briefcase"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Contraseña -->
                    <div class="input-group mb-3">
                        <input type="password" 
                               class="form-control" 
                               name="password" 
                               id="password"
                               placeholder="Contraseña" 
                               required
                               minlength="6"
                               title="La contraseña debe tener al menos 6 caracteres">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Confirmar Contraseña -->
                    <div class="input-group mb-3">
                        <input type="password" 
                               class="form-control" 
                               name="password_confirm" 
                               id="password_confirm"
                               placeholder="Confirmar Contraseña" 
                               required
                               minlength="6">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Botón de Registro -->
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-user-plus"></i> Registrarme
                            </button>
                        </div>
                    </div>
                </form>

                <p class="mb-0 mt-3 text-center">
                    <a href="login.php" class="text-center">
                        <i class="fas fa-arrow-left"></i> Ya tengo cuenta, iniciar sesión
                    </a>
                </p>

                <!-- Mensaje de alerta -->
                <div id="alertMessage" class="alert alert-danger mt-3" style="display: none;" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <span id="alertText"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap 4 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App CDN -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <!-- Custom JS -->
    <script src="../../assets/js/registro.js"></script>
</body>
</html>
