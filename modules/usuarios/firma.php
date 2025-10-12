<?php
/**
 * OperaSys - Captura de Firma Digital
 * Archivo: modules/usuarios/firma.php
 * Descripción: Canvas HTML5 para dibujar y guardar firma
 */

require_once '../../config/config.php';

// Verificar que haya un usuario temporal en sesión (recién registrado)
// o que sea un usuario autenticado que quiere actualizar su firma
if (!isset($_SESSION['temp_user_id']) && !isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$usuarioId = $_SESSION['temp_user_id'] ?? $_SESSION['user_id'];
$esNuevoRegistro = isset($_SESSION['temp_user_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma Digital - <?php echo SITE_NAME; ?></title>
    
    <!-- AdminLTE CSS CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <style>
        #canvasFirma {
            border: 2px solid #2E86AB;
            border-radius: 5px;
            cursor: crosshair;
            touch-action: none;
            background-color: white;
        }
        .firma-container {
            max-width: 600px;
            margin: 0 auto;
        }
    </style>
</head>
<body class="hold-transition">
    <div class="container mt-5">
        <div class="firma-container">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-signature"></i> Captura tu Firma Digital
                    </h3>
                </div>
                <div class="card-body">
                    <?php if ($esNuevoRegistro): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>¡Último paso!</strong> Dibuja tu firma en el recuadro. Esta firma aparecerá en todos tus reportes.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Estás actualizando tu firma. La nueva firma reemplazará a la anterior.
                        </div>
                    <?php endif; ?>

                    <p class="text-muted mb-3">
                        <i class="fas fa-hand-pointer"></i> 
                        Usa tu dedo (móvil) o mouse (PC) para dibujar tu firma.
                    </p>

                    <!-- Canvas para dibujar -->
                    <div class="text-center mb-3">
                        <canvas id="canvasFirma" width="550" height="250"></canvas>
                    </div>

                    <!-- Botones -->
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <button type="button" id="btnLimpiar" class="btn btn-warning btn-block">
                                <i class="fas fa-eraser"></i> Limpiar
                            </button>
                        </div>
                        <div class="col-md-6 mb-2">
                            <button type="button" id="btnGuardar" class="btn btn-success btn-block">
                                <i class="fas fa-save"></i> Guardar Firma
                            </button>
                        </div>
                    </div>

                    <?php if (!$esNuevoRegistro): ?>
                    <div class="row mt-2">
                        <div class="col-12">
                            <a href="../admin/dashboard.php" class="btn btn-secondary btn-block">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Mensaje de alerta -->
                    <div id="alertMessage" class="alert mt-3" style="display: none;" role="alert">
                        <span id="alertText"></span>
                    </div>
                </div>
            </div>

            <!-- Instrucciones -->
            <div class="card mt-3">
                <div class="card-body">
                    <h5><i class="fas fa-lightbulb"></i> Consejos:</h5>
                    <ul class="mb-0">
                        <li>Dibuja tu firma de forma clara y legible</li>
                        <li>Si te equivocas, presiona "Limpiar" y vuelve a intentar</li>
                        <li>La firma será visible en todos tus reportes</li>
                        <li>Puedes cambiar tu firma más adelante desde tu perfil</li>
                    </ul>
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
    <!-- Custom JS - Firma -->
    <script src="../../assets/js/firma.js"></script>
</body>
</html>
