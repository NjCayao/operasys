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

$nombreUsuario = $_SESSION['nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Equipo - <?php echo SITE_NAME; ?></title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/custom.css">
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <span class="nav-link"><i class="fas fa-user"></i> <?php echo $nombreUsuario; ?></span>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../auth/logout.php">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </li>
        </ul>
    </nav>

    <!-- Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="../../index.php" class="brand-link">
            <span class="brand-text font-weight-light"><b>Opera</b>Sys</span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column">
                    <li class="nav-item">
                        <a href="../admin/dashboard.php" class="nav-link">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="listar.php" class="nav-link active">
                            <i class="nav-icon fas fa-truck-monster"></i>
                            <p>Equipos</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

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
                                    Modificar datos del equipo: <strong><?php echo htmlspecialchars($equipo['codigo']); ?></strong>
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

    <!-- Footer -->
    <footer class="main-footer">
        <strong>OperaSys &copy; 2025</strong>
    </footer>
</div>

<!-- jQuery CDN -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap 4 CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App CDN -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<!-- Custom JS -->
<script src="../../assets/js/equipos.js"></script>
</body>
</html>
