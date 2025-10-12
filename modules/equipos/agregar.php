<?php
/**
 * OperaSys - Agregar Equipo
 * Archivo: modules/equipos/agregar.php
 * Descripción: Formulario para registrar nuevo equipo
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar sesión y permisos
verificarSesion();

// Solo admin y supervisor pueden agregar equipos
if ($_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'supervisor') {
    header('Location: listar.php');
    exit;
}

$nombreUsuario = $_SESSION['nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Equipo - <?php echo SITE_NAME; ?></title>
    
    <!-- AdminLTE CSS CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
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
                <span class="nav-link">
                    <i class="fas fa-user"></i> <?php echo $nombreUsuario; ?>
                </span>
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
                            <i class="fas fa-plus-circle"></i> Agregar Equipo
                        </h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="listar.php">Equipos</a></li>
                            <li class="breadcrumb-item active">Agregar</li>
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
                        
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Datos del Equipo</h3>
                            </div>
                            
                            <form id="formAgregarEquipo" method="POST">
                                <div class="card-body">
                                    
                                    <!-- Categoría -->
                                    <div class="form-group">
                                        <label for="categoria">
                                            <i class="fas fa-tag"></i> Categoría <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control" id="categoria" name="categoria" required>
                                            <option value="">Seleccionar categoría</option>
                                            <option value="Excavadora">Excavadora</option>
                                            <option value="Volquete">Volquete</option>
                                            <option value="Tractor">Tractor</option>
                                            <option value="Motoniveladora">Motoniveladora</option>
                                            <option value="Cargador">Cargador</option>
                                            <option value="Retroexcavadora">Retroexcavadora</option>
                                            <option value="Bulldozer">Bulldozer</option>
                                            <option value="Compactadora">Compactadora</option>
                                            <option value="Grúa">Grúa</option>
                                            <option value="Otro">Otro</option>
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
                                               placeholder="Ej: EX001, VOL002, TRA003"
                                               required
                                               maxlength="20"
                                               pattern="[A-Z0-9]+"
                                               title="Solo letras mayúsculas y números, sin espacios">
                                        <small class="form-text text-muted">
                                            Solo letras mayúsculas y números (Ej: EX001)
                                        </small>
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
                                                  placeholder="Ej: Excavadora Caterpillar 320D, color amarillo, año 2020"
                                                  maxlength="255"></textarea>
                                        <small class="form-text text-muted">
                                            Información adicional sobre el equipo (opcional)
                                        </small>
                                    </div>

                                    <!-- Alerta -->
                                    <div id="alertMessage" class="alert" style="display: none;" role="alert">
                                        <span id="alertText"></span>
                                    </div>

                                </div>

                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Guardar Equipo
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
