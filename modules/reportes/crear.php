<?php
/**
 * OperaSys - Crear Reporte Diario
 * Archivo: modules/reportes/crear.php
 * Descripción: Formulario para registrar reporte de operación
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

verificarSesion();

$nombreUsuario = $_SESSION['nombre'];
$usuarioId = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2E86AB">
    <meta name="description" content="Sistema de reportes de operación offline">
    <link rel="manifest" href="../../manifest.json">
    <link rel="apple-touch-icon" href="../../assets/img/icon-192x192.png">
    <title>Crear Reporte - <?php echo SITE_NAME; ?></title>
    
    <!-- AdminLTE CSS CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tempusdominus Bootstrap 4 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/css/tempusdominus-bootstrap-4.min.css">
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
                        <a href="../equipos/listar.php" class="nav-link">
                            <i class="nav-icon fas fa-truck-monster"></i>
                            <p>Equipos</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="listar.php" class="nav-link active">
                            <i class="nav-icon fas fa-file-alt"></i>
                            <p>Reportes</p>
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
                            <i class="fas fa-plus-circle"></i> Nuevo Reporte Diario
                        </h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="listar.php">Reportes</a></li>
                            <li class="breadcrumb-item active">Crear</li>
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
                        
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Datos del Reporte</h3>
                            </div>
                            
                            <form id="formCrearReporte" method="POST">
                                <input type="hidden" name="usuario_id" value="<?php echo $usuarioId; ?>">
                                
                                <div class="card-body">
                                    
                                    <div class="row">
                                        <!-- Fecha -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="fecha">
                                                    <i class="fas fa-calendar"></i> Fecha <span class="text-danger">*</span>
                                                </label>
                                                <input type="date" 
                                                       class="form-control" 
                                                       id="fecha" 
                                                       name="fecha" 
                                                       value="<?php echo date('Y-m-d'); ?>"
                                                       required>
                                            </div>
                                        </div>

                                        <!-- Hora Inicio -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="hora_inicio">
                                                    <i class="fas fa-clock"></i> Hora Inicio <span class="text-danger">*</span>
                                                </label>
                                                <input type="time" 
                                                       class="form-control" 
                                                       id="hora_inicio" 
                                                       name="hora_inicio" 
                                                       required>
                                            </div>
                                        </div>

                                        <!-- Hora Fin -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="hora_fin">
                                                    <i class="fas fa-clock"></i> Hora Fin
                                                </label>
                                                <input type="time" 
                                                       class="form-control" 
                                                       id="hora_fin" 
                                                       name="hora_fin">
                                                <small class="form-text text-muted">
                                                    Opcional - Las horas trabajadas se calcularán automáticamente
                                                </small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Categoría de Equipo -->
                                    <div class="form-group">
                                        <label for="categoria_equipo">
                                            <i class="fas fa-tag"></i> Categoría de Equipo <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control" id="categoria_equipo" required>
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

                                    <!-- Equipo (se llena dinámicamente) -->
                                    <div class="form-group">
                                        <label for="equipo_id">
                                            <i class="fas fa-truck-monster"></i> Equipo <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control" id="equipo_id" name="equipo_id" required disabled>
                                            <option value="">Primero seleccione una categoría</option>
                                        </select>
                                    </div>

                                    <!-- Actividad Realizada -->
                                    <div class="form-group">
                                        <label for="actividad">
                                            <i class="fas fa-tasks"></i> Actividad Realizada <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="form-control" 
                                                  id="actividad" 
                                                  name="actividad" 
                                                  rows="4"
                                                  placeholder="Describa detalladamente el trabajo realizado durante la jornada..."
                                                  required></textarea>
                                        <small class="form-text text-muted">
                                            Sea específico: zona de trabajo, tipo de material, cantidad aproximada, etc.
                                        </small>
                                    </div>

                                    <!-- Observaciones -->
                                    <div class="form-group">
                                        <label for="observaciones">
                                            <i class="fas fa-comment"></i> Observaciones
                                        </label>
                                        <textarea class="form-control" 
                                                  id="observaciones" 
                                                  name="observaciones" 
                                                  rows="3"
                                                  placeholder="Incidencias, novedades, recomendaciones... (Opcional)"></textarea>
                                    </div>

                                    <!-- Ubicación (GPS) -->
                                    <div class="form-group">
                                        <label for="ubicacion">
                                            <i class="fas fa-map-marker-alt"></i> Ubicación (GPS)
                                        </label>
                                        <div class="input-group">
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="ubicacion" 
                                                   name="ubicacion" 
                                                   placeholder="Latitud, Longitud"
                                                   readonly>
                                            <div class="input-group-append">
                                                <button type="button" 
                                                        class="btn btn-info" 
                                                        id="btnObtenerUbicacion">
                                                    <i class="fas fa-location-arrow"></i> Obtener Ubicación
                                                </button>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">
                                            Opcional - Click para capturar ubicación GPS actual
                                        </small>
                                    </div>

                                    <!-- Alerta -->
                                    <div id="alertMessage" class="alert" style="display: none;" role="alert">
                                        <span id="alertText"></span>
                                    </div>

                                </div>

                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save"></i> Guardar Reporte
                                    </button>
                                    <a href="listar.php" class="btn btn-secondary btn-lg">
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
<!-- Moment.js CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Offline Support -->
<script src="../../assets/js/offline.js"></script>
<!-- Custom JS -->
<script src="../../assets/js/reportes.js"></script>
</body>
</html>
