<?php
/**
 * OperaSys - Captura de Firma Digital
 * Archivo: modules/usuarios/firma.php
 * Descripción: Canvas HTML5 para capturar firma del usuario
 */

require_once '../../config/config.php';

// Verificar que haya sesión activa
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$nombreUsuario = $_SESSION['nombre'];
$esAdmin = $_SESSION['rol'] === 'admin';

// Verificar si es edición de otro usuario (solo admin)
$userIdEditar = $_GET['user_id'] ?? $userId;

// Si no es admin y está intentando editar a otro, redirigir
if (!$esAdmin && $userIdEditar != $userId) {
    header('Location: ../admin/dashboard.php');
    exit;
}

// Verificar si ya tiene firma
require_once '../../config/database.php';
$stmt = $pdo->prepare("SELECT firma, nombre_completo FROM usuarios WHERE id = ?");
$stmt->execute([$userIdEditar]);
$usuario = $stmt->fetch();
$tieneFirma = !empty($usuario['firma']);

// Si no es admin y ya tiene firma, redirigir (no puede actualizar)
if (!$esAdmin && $tieneFirma) {
    header('Location: perfil.php');
    exit;
}

// Nombre del usuario a editar (si es admin editando a otro)
$nombreEditar = ($userIdEditar != $userId) ? $usuario['nombre_completo'] : $nombreUsuario;

// Configuración del layout
$page_title = 'Captura de Firma';
$auth_base_path = '../../';
$body_class = 'register-page';
$custom_js_file = 'assets/js/firma.js?v=' . ASSETS_VERSION;

include '../../layouts/auth_header.php';
?>

<div class="register-box" style="width: 500px;">
    <div class="card card-outline card-primary">
        <div class="card-header text-center">
            <h1><b>Opera</b>Sys</h1>
            <p class="text-muted">Captura de Firma Digital</p>
        </div>
        <div class="card-body">

            <?php if (!$tieneFirma): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>
                        <?php if ($userIdEditar == $userId): ?>
                            Bienvenido, <?php echo htmlspecialchars($nombreUsuario); ?>!
                        <?php else: ?>
                            Capturando firma de: <?php echo htmlspecialchars($nombreEditar); ?>
                        <?php endif; ?>
                    </strong><br>
                    <?php if ($userIdEditar == $userId): ?>
                        Para comenzar a usar el sistema, necesitas registrar tu firma digital.
                    <?php else: ?>
                        Registra la firma digital del usuario.
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-pen"></i>
                    <strong>Actualizar Firma de: <?php echo htmlspecialchars($nombreEditar); ?></strong><br>
                    Actualiza la firma digital del usuario.
                </div>
            <?php endif; ?>

            <p class="text-center mb-3">
                <i class="fas fa-signature"></i>
                Dibuja tu firma en el recuadro usando el mouse o tu dedo (en móvil)
            </p>

            <!-- Canvas para la firma -->
            <div class="text-center mb-3">
                <canvas id="canvasFirma"
                    width="450"
                    height="200"
                    style="border: 2px solid #007bff; border-radius: 5px; cursor: crosshair; background: #fff;">
                </canvas>
            </div>

            <!-- Botones de acción -->
            <div class="row mb-3">
                <div class="col-6">
                    <button type="button"
                        class="btn btn-warning btn-block"
                        id="btnLimpiar">
                        <i class="fas fa-eraser"></i> Limpiar
                    </button>
                </div>
                <div class="col-6">
                    <button type="button"
                        class="btn btn-success btn-block"
                        id="btnGuardar">
                        <i class="fas fa-save"></i> Guardar Firma
                    </button>
                </div>
            </div>

            <!-- Instrucciones -->
            <div class="alert alert-info">
                <small>
                    <i class="fas fa-info-circle"></i>
                    <strong>Instrucciones:</strong>
                    <ul class="mb-0">
                        <li>Dibuja tu firma con el mouse o el dedo</li>
                        <li>Si te equivocas, presiona "Limpiar" y vuelve a intentar</li>
                        <li>Cuando estés satisfecho, presiona "Guardar Firma"</li>
                    </ul>
                </small>
            </div>

            <!-- Mensaje de alerta -->
            <div id="alertMessage" class="alert" style="display: none;" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <span id="alertText"></span>
            </div>

            <?php if ($userIdEditar != $userId): ?>
                <!-- Si es admin editando a otro, mostrar botón cancelar -->
                <div class="text-center mt-3">
                    <a href="listar.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Campos ocultos -->
<input type="hidden" id="tieneFirma" value="<?php echo $tieneFirma ? '1' : '0'; ?>">
<input type="hidden" id="userIdEditar" value="<?php echo $userIdEditar; ?>">
<input type="hidden" id="esAdmin" value="<?php echo $esAdmin ? '1' : '0'; ?>">
<input type="hidden" id="userIdSesion" value="<?php echo $userId; ?>">

<?php include '../../layouts/auth_footer.php'; ?>