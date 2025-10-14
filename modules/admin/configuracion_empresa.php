<?php

/**
 * OperaSys - Configuración de Empresa
 * Archivo: modules/admin/configuracion_empresa.php
 * Descripción: Gestión de datos de la empresa (solo admin)
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

verificarSesion();

// Solo admin puede acceder
if ($_SESSION['rol'] !== 'admin') {
    header('Location: ../reportes/listar.php');
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

// Obtener configuración actual
try {
    $stmt = $pdo->query("SELECT * FROM configuracion_empresa WHERE id = 1");
    $config = $stmt->fetch();

    // Si no existe, crear registro
    if (!$config) {
        $pdo->exec("INSERT INTO configuracion_empresa (id, nombre_empresa) VALUES (1, 'OperaSys')");
        $config = $stmt->fetch();
    }
} catch (PDOException $e) {
    $mensaje = 'Error al cargar configuración: ' . $e->getMessage();
    $tipo_mensaje = 'error';
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_empresa = trim($_POST['nombre_empresa'] ?? 'OperaSys');
    $ruc_nit = trim($_POST['ruc_nit'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $logo_base64 = $config['logo'] ?? null;

    // Procesar logo si se subió
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/png', 'image/jpeg', 'image/jpg'];
        $fileType = $_FILES['logo']['type'];
        $fileSize = $_FILES['logo']['size'];

        // Validar tipo y tamaño
        if (!in_array($fileType, $allowed)) {
            $mensaje = 'Solo se permiten archivos PNG o JPG';
            $tipo_mensaje = 'error';
        } elseif ($fileSize > 500000) { // 500KB máximo
            $mensaje = 'El logo es muy grande. Máximo 500KB';
            $tipo_mensaje = 'error';
        } else {
            // Leer y convertir a base64
            $imageData = file_get_contents($_FILES['logo']['tmp_name']);

            // Verificar que la imagen es válida
            $imageInfo = getimagesizefromstring($imageData);
            if ($imageInfo === false) {
                $mensaje = 'El archivo no es una imagen válida';
                $tipo_mensaje = 'error';
            } else {
                // Redimensionar si es muy grande
                $img = imagecreatefromstring($imageData);
                if ($img !== false) {
                    $width = imagesx($img);
                    $height = imagesy($img);

                    // Si es muy grande, redimensionar
                    if ($width > 400 || $height > 200) {
                        $ratio = min(400 / $width, 200 / $height);
                        $newWidth = (int)($width * $ratio);
                        $newHeight = (int)($height * $ratio);

                        $newImg = imagecreatetruecolor($newWidth, $newHeight);

                        // Preservar transparencia para PNG
                        if ($fileType === 'image/png') {
                            imagealphablending($newImg, false);
                            imagesavealpha($newImg, true);
                        }

                        imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                        // Convertir a base64
                        ob_start();
                        if ($fileType === 'image/png') {
                            imagepng($newImg, null, 9);
                            $ext = 'png';
                        } else {
                            imagejpeg($newImg, null, 85);
                            $ext = 'jpeg';
                        }
                        $imageData = ob_get_clean();

                        imagedestroy($img);
                        imagedestroy($newImg);
                    }

                    $logo_base64 = 'data:image/' . ($fileType === 'image/png' ? 'png' : 'jpeg') . ';base64,' . base64_encode($imageData);
                } else {
                    $mensaje = 'Error al procesar la imagen';
                    $tipo_mensaje = 'error';
                }
            }
        }
    }

    // Actualizar en BD
    if (empty($mensaje)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE configuracion_empresa 
                SET nombre_empresa = ?, 
                    ruc_nit = ?, 
                    direccion = ?, 
                    telefono = ?, 
                    email = ?,
                    logo = ?
                WHERE id = 1
            ");
            $stmt->execute([
                $nombre_empresa,
                $ruc_nit ?: null,
                $direccion ?: null,
                $telefono ?: null,
                $email ?: null,
                $logo_base64
            ]);

            $mensaje = 'Configuración actualizada correctamente';
            $tipo_mensaje = 'success';

            // Recargar datos
            $config = $pdo->query("SELECT * FROM configuracion_empresa WHERE id = 1")->fetch();
        } catch (PDOException $e) {
            $mensaje = 'Error al guardar: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}

$page_title = 'Configuración de Empresa';
$page_depth = 2;
$use_sweetalert = true;

include '../../layouts/header.php';
include '../../layouts/navbar.php';
include '../../layouts/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                        <i class="fas fa-building"></i> Configuración de Empresa
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Admin</a></li>
                        <li class="breadcrumb-item active">Configuración</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">

            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-edit"></i> Datos de la Empresa
                            </h3>
                        </div>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="card-body">

                                <div class="form-group">
                                    <label for="nombre_empresa">
                                        <i class="fas fa-building text-primary"></i> Nombre de la Empresa *
                                    </label>
                                    <input type="text"
                                        class="form-control"
                                        id="nombre_empresa"
                                        name="nombre_empresa"
                                        value="<?php echo htmlspecialchars($config['nombre_empresa'] ?? 'OperaSys'); ?>"
                                        required>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="ruc_nit">
                                                <i class="fas fa-id-card text-info"></i> RUC / NIT
                                            </label>
                                            <input type="text"
                                                class="form-control"
                                                id="ruc_nit"
                                                name="ruc_nit"
                                                value="<?php echo htmlspecialchars($config['ruc_nit'] ?? ''); ?>"
                                                placeholder="Ej: 20123456789">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="telefono">
                                                <i class="fas fa-phone text-success"></i> Teléfono
                                            </label>
                                            <input type="text"
                                                class="form-control"
                                                id="telefono"
                                                name="telefono"
                                                value="<?php echo htmlspecialchars($config['telefono'] ?? ''); ?>"
                                                placeholder="Ej: +51 999 888 777">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="direccion">
                                        <i class="fas fa-map-marker-alt text-danger"></i> Dirección
                                    </label>
                                    <input type="text"
                                        class="form-control"
                                        id="direccion"
                                        name="direccion"
                                        value="<?php echo htmlspecialchars($config['direccion'] ?? ''); ?>"
                                        placeholder="Ej: Av. Principal 123, Lima">
                                </div>

                                <div class="form-group">
                                    <label for="email">
                                        <i class="fas fa-envelope text-warning"></i> Email
                                    </label>
                                    <input type="email"
                                        class="form-control"
                                        id="email"
                                        name="email"
                                        value="<?php echo htmlspecialchars($config['email'] ?? ''); ?>"
                                        placeholder="contacto@empresa.com">
                                </div>

                                <div class="form-group">
                                    <label for="logo">
                                        <i class="fas fa-image text-purple"></i> Logo de la Empresa
                                    </label>
                                    <div class="custom-file">
                                        <input type="file"
                                            class="custom-file-input"
                                            id="logo"
                                            name="logo"
                                            accept="image/png,image/jpeg,image/jpg">
                                        <label class="custom-file-label" for="logo">
                                            Seleccionar logo (PNG/JPG, máx 1MB)
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        Recomendado: 200x80px, fondo transparente
                                    </small>
                                </div>

                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-eye"></i> Vista Previa PDF
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3" style="border: 2px solid #ddd; padding: 15px; background: #f9f9f9; position: relative;">
                                <h4 style="color: #2E86AB; font-weight: bold; margin: 0;">OperaSys</h4>
                                <p style="margin: 5px 0; font-size: 12px;">Reporte Diario de Operaciones</p>
                                <hr style="border-top: 2px solid #2E86AB; margin: 10px 0;">

                                <?php if (!empty($config['logo'])): ?>
                                    <div class="mt-2">
                                        <img src="<?php echo htmlspecialchars($config['logo']); ?>"
                                            alt="Logo actual"
                                            style="max-width: 150px; border: 1px solid #ddd; padding: 5px;"
                                            onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22%3EError%3C/text%3E%3C/svg%3E'">
                                        <br>
                                        <small class="text-success">✓ Logo actual guardado</small>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($config['nombre_empresa'])): ?>
                                    <p style="margin: 5px 0; font-weight: bold; font-size: 11px;">
                                        <?php echo htmlspecialchars($config['nombre_empresa']); ?>
                                    </p>
                                <?php endif; ?>

                                <small class="text-muted" style="font-size: 9px; display: block;">
                                    <?php
                                    $datos = [];
                                    if ($config['ruc_nit']) $datos[] = 'RUC: ' . $config['ruc_nit'];
                                    if ($config['direccion']) $datos[] = $config['direccion'];
                                    if ($config['telefono']) $datos[] = 'Tel: ' . $config['telefono'];
                                    if ($config['email']) $datos[] = $config['email'];
                                    echo htmlspecialchars(implode(' | ', $datos) ?: 'Sin datos');
                                    ?>
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle"></i> Información
                            </h3>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> Estos datos aparecen en los PDFs</li>
                                <li><i class="fas fa-check text-success"></i> Solo admin puede editarlos</li>
                                <li><i class="fas fa-check text-success"></i> Logo opcional</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<script>
    // Preview de imagen seleccionada
    document.getElementById('logo').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name || 'Seleccionar logo';
        const label = document.querySelector('.custom-file-label');
        label.textContent = fileName;
    });
</script>

<?php
include '../../layouts/footer.php';
?>