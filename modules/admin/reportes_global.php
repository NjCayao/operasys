<?php

/**
 * OperaSys - Reportes Globales
 * Archivo: modules/admin/reportes_global.php
 * Descripción: Ver todos los reportes del sistema (Admin/Supervisor)
 */

require_once '../../config/database.php';
require_once '../../config/config.php';

// Verificar sesión y rol (admin o supervisor)
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$rolUsuario = $_SESSION['rol'];
if ($rolUsuario !== 'admin' && $rolUsuario !== 'supervisor') {
    header('Location: ../reportes/listar.php');
    exit;
}

$nombreUsuario = $_SESSION['nombre'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes Globales - OperaSys</title>

    <!-- AdminLTE CSS LOCAL -->
    <link rel="stylesheet" href="../../vendor/adminlte/dist/css/adminlte.min.css">
    <!-- Font Awesome LOCAL -->
    <link rel="stylesheet" href="../../vendor/adminlte/plugins/fontawesome-free/css/all.min.css">
    <!-- DataTables LOCAL -->
    <link rel="stylesheet" href="../../vendor/adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
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
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-home"></i> Inicio
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <span class="nav-link">
                        <i class="fas fa-user"></i> <?php echo $nombreUsuario; ?>
                        <span class="badge badge-<?php echo $rolUsuario === 'admin' ? 'danger' : 'warning'; ?>">
                            <?php echo ucfirst($rolUsuario); ?>
                        </span>
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
            <a href="dashboard.php" class="brand-link text-center">
                <span class="brand-text font-weight-light"><b>Opera</b>Sys</span>
            </a>

            <div class="sidebar">
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image">
                        <i class="fas fa-user-circle fa-2x text-white"></i>
                    </div>
                    <div class="info">
                        <a href="#" class="d-block"><?php echo $nombreUsuario; ?></a>
                    </div>
                </div>

                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="../equipos/listar.php" class="nav-link">
                                <i class="nav-icon fas fa-truck"></i>
                                <p>Equipos</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="../reportes/listar.php" class="nav-link">
                                <i class="nav-icon fas fa-file-alt"></i>
                                <p>Mis Reportes</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="../reportes/crear.php" class="nav-link">
                                <i class="nav-icon fas fa-plus-circle"></i>
                                <p>Crear Reporte</p>
                            </a>
                        </li>
                        <?php if ($rolUsuario === 'admin' || $rolUsuario === 'supervisor'): ?>
                            <li class="nav-header">ADMINISTRACIÓN</li>
                            <?php if ($rolUsuario === 'admin'): ?>
                                <li class="nav-item">
                                    <a href="../usuarios/listar.php" class="nav-link">
                                        <i class="nav-icon fas fa-users"></i>
                                        <p>Gestión de Usuarios</p>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li class="nav-item">
                                <a href="reportes_global.php" class="nav-link active">
                                    <i class="nav-icon fas fa-chart-line"></i>
                                    <p>Reportes Globales</p>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-12">
                            <h1 class="m-0">
                                <i class="fas fa-chart-line"></i> Reportes Globales
                            </h1>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">

                    <!-- Filtros -->
                    <div class="card collapsed-card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-filter"></i> Filtros
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="formFiltros" class="row">
                                <div class="col-md-3">
                                    <label>Operador:</label>
                                    <select class="form-control" name="operador_id" id="filtro_operador">
                                        <option value="">Todos</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>Categoría Equipo:</label>
                                    <select class="form-control" name="categoria" id="filtro_categoria">
                                        <option value="">Todas</option>
                                        <option value="Excavadora">Excavadora</option>
                                        <option value="Volquete">Volquete</option>
                                        <option value="Tractor">Tractor</option>
                                        <option value="Cargador Frontal">Cargador Frontal</option>
                                        <option value="Rodillo Compactador">Rodillo Compactador</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>Fecha Desde:</label>
                                    <input type="date" class="form-control" name="fecha_desde" id="filtro_fecha_desde">
                                </div>
                                <div class="col-md-2">
                                    <label>Fecha Hasta:</label>
                                    <input type="date" class="form-control" name="fecha_hasta" id="filtro_fecha_hasta">
                                </div>
                                <div class="col-md-2">
                                    <label>&nbsp;</label>
                                    <button type="button" class="btn btn-primary btn-block" onclick="aplicarFiltros()">
                                        <i class="fas fa-search"></i> Buscar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tabla de reportes -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-list"></i> Todos los Reportes
                            </h3>
                        </div>
                        <div class="card-body">
                            <table id="tablaReportes" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Fecha</th>
                                        <th>Operador</th>
                                        <th>Equipo</th>
                                        <th>Horas</th>
                                        <th>Actividad</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Se carga con JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </section>
        </div>

        <!-- Footer -->
        <footer class="main-footer">
            <strong>OperaSys &copy; 2025</strong>
            <div class="float-right d-none d-sm-inline-block">
                <b>Versión</b> 1.0
            </div>
        </footer>
    </div>

    <!-- Scripts -->
    <!-- jQuery LOCAL -->
    <script src="../../vendor/adminlte/plugins/jquery/jquery.min.js"></script>
    <!-- Bootstrap 4 LOCAL -->
    <script src="../../vendor/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables LOCAL -->
    <script src="../../vendor/adminlte/plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="../../vendor/adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
    <!-- AdminLTE App LOCAL -->
    <script src="../../vendor/adminlte/dist/js/adminlte.min.js"></script>
    <!-- SweetAlert2 LOCAL -->
    <script src="../../vendor/adminlte/plugins/sweetalert2/sweetalert2.all.min.js"></script>

    <script>
        $(document).ready(function() {
            cargarOperadores();
            cargarReportes();
        });

        function cargarOperadores() {
            $.ajax({
                url: '../../api/usuarios.php?action=listar',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        let html = '<option value="">Todos</option>';
                        response.data.forEach(function(user) {
                            html += `<option value="${user.id}">${user.nombre_completo}</option>`;
                        });
                        $('#filtro_operador').html(html);
                    }
                }
            });
        }

        function cargarReportes(filtros = {}) {
            $.ajax({
                url: '../../api/reportes.php?action=listar_todos',
                method: 'GET',
                data: filtros,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        let html = '';
                        response.data.forEach(function(reporte) {
                            let estadoBadge = reporte.estado_sinc === 'sincronizado' ? 'success' : 'warning';
                            let estadoTexto = reporte.estado_sinc === 'sincronizado' ? 'Sincronizado' : 'Pendiente';
                            let actividadCorta = reporte.actividad.length > 50 ?
                                reporte.actividad.substring(0, 50) + '...' :
                                reporte.actividad;

                            html += `<tr>
                        <td>${reporte.id}</td>
                        <td>${reporte.fecha}</td>
                        <td>${reporte.operador}</td>
                        <td><span class="badge badge-info">${reporte.equipo}</span></td>
                        <td>${reporte.horas_trabajadas || 'N/A'} hrs</td>
                        <td title="${reporte.actividad}">${actividadCorta}</td>
                        <td><span class="badge badge-${estadoBadge}">${estadoTexto}</span></td>
                        <td>
                            <a href="../../api/pdf.php?id=${reporte.id}" class="btn btn-sm btn-danger" title="Descargar PDF">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                        </td>
                    </tr>`;
                        });

                        $('#tablaReportes tbody').html(html);

                        if ($.fn.DataTable.isDataTable('#tablaReportes')) {
                            $('#tablaReportes').DataTable().destroy();
                        }

                        $('#tablaReportes').DataTable({
                            language: {
                                url: '../../vendor/adminlte/plugins/datatables/es-ES.json'
                            },
                            order: [
                                [0, 'desc']
                            ]
                        });
                    }
                }
            });
        }

        function aplicarFiltros() {
            let filtros = {
                operador_id: $('#filtro_operador').val(),
                categoria: $('#filtro_categoria').val(),
                fecha_desde: $('#filtro_fecha_desde').val(),
                fecha_hasta: $('#filtro_fecha_hasta').val()
            };
            cargarReportes(filtros);
        }
    </script>

</body>

</html>