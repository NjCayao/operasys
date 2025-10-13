<?php

/**
 * OperaSys - Dashboard Principal
 * Archivo: modules/admin/dashboard.php
 * Descripción: Panel de control con estadísticas y gráficos
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

verificarSesion();

$nombreUsuario = $_SESSION['nombre'];
$rolUsuario = $_SESSION['rol'];
$usuarioId = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2E86AB">
    <link rel="manifest" href="../../manifest.json">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>

    <!-- AdminLTE CSS CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <link rel="stylesheet" href="../../vendor/adminlte/plugins/chart.js/Chart.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/custom.css">
</head>

<body class="hold-transition sidebar-mini layout-fixed">
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
                <!-- Estado de conexión -->
                <li class="nav-item">
                    <span class="nav-link" id="estadoConexion">
                        <i class="fas fa-wifi text-success"></i>
                        <span class="d-none d-sm-inline">Online</span>
                    </span>
                </li>

                <li class="nav-item">
                    <span class="nav-link">
                        <i class="fas fa-user"></i> <?php echo $nombreUsuario; ?>
                        <span class="badge badge-info"><?php echo ucfirst($rolUsuario); ?></span>
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
            <a href="../../index.php" class="brand-link text-center">
                <span class="brand-text font-weight-light">
                    <b>Opera</b>Sys
                </span>
            </a>

            <div class="sidebar">
                <!-- User Panel -->
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image">
                        <i class="fas fa-user-circle fa-2x text-white"></i>
                    </div>
                    <div class="info">
                        <a href="#" class="d-block"><?php echo $nombreUsuario; ?></a>
                    </div>
                </div>

                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">

                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link active">
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
                            <a href="../reportes/listar.php" class="nav-link">
                                <i class="nav-icon fas fa-file-alt"></i>
                                <p>Mis Reportes</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="../reportes/crear.php" class="nav-link">
                                <i class="nav-icon fas fa-plus-circle"></i>
                                <p>Nuevo Reporte</p>
                            </a>
                        </li>

                        <?php if ($rolUsuario === 'admin' || $rolUsuario === 'supervisor'): ?>
                            <li class="nav-header">ADMINISTRACIÓN</li>

                            <li class="nav-item">
                                <a href="../usuarios/listar.php" class="nav-link">
                                    <i class="nav-icon fas fa-users"></i>
                                    <p>Usuarios</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="reportes_global.php" class="nav-link">
                                    <i class="nav-icon fas fa-chart-bar"></i>
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
            <!-- Content Header -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </h1>
                        </div>
                        <div class="col-sm-6">
                            <div class="float-sm-right">
                                <button class="btn btn-sm btn-primary" onclick="actualizarDashboard()">
                                    <i class="fas fa-sync-alt"></i> Actualizar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">

                    <!-- Tarjetas de estadísticas -->
                    <div class="row">
                        <!-- Total Reportes -->
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3 id="totalReportes">0</h3>
                                    <p>Total Reportes</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <a href="../reportes/listar.php" class="small-box-footer">
                                    Ver más <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Reportes Hoy -->
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3 id="reportesHoy">0</h3>
                                    <p>Reportes Hoy</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                                <a href="../reportes/crear.php" class="small-box-footer">
                                    Crear nuevo <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Horas Trabajadas -->
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3 id="horasMes">0</h3>
                                    <p>Horas Este Mes</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <a href="#" class="small-box-footer">
                                    Detalle <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Equipos Activos -->
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3 id="equiposActivos">0</h3>
                                    <p>Equipos Activos</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-truck-monster"></i>
                                </div>
                                <a href="../equipos/listar.php" class="small-box-footer">
                                    Ver equipos <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Gráficos -->
                    <div class="row">
                        <!-- Gráfico: Reportes por mes -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-chart-line"></i>
                                        Reportes por Mes (Últimos 6 meses)
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="graficoReportesMes" height="200"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Gráfico: Equipos más usados -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-chart-bar"></i>
                                        Equipos Más Utilizados
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="graficoEquipos" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Últimos reportes -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-list"></i>
                                        Últimos Reportes Creados
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered table-striped" id="tablaUltimosReportes">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Equipo</th>
                                                <th>Horas</th>
                                                <th>Actividad</th>
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Se llena dinámicamente -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($rolUsuario === 'admin'): ?>
                        <!-- Tarjeta de usuarios (solo admin) -->
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="card card-primary">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-users"></i>
                                            Resumen de Usuarios
                                        </h3>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-group">
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Operadores Activos
                                                <span class="badge badge-primary badge-pill" id="totalOperadores">0</span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Supervisores
                                                <span class="badge badge-success badge-pill" id="totalSupervisores">0</span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Administradores
                                                <span class="badge badge-danger badge-pill" id="totalAdmins">0</span>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="card-footer">
                                        <a href="usuarios.php" class="btn btn-primary btn-block">
                                            <i class="fas fa-users-cog"></i> Gestionar Usuarios
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Actividad reciente -->
                            <div class="col-lg-6">
                                <div class="card card-warning">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-history"></i>
                                            Actividad Reciente
                                        </h3>
                                    </div>
                                    <div class="card-body p-0">
                                        <ul class="list-group list-group-flush" id="actividadReciente">
                                            <!-- Se llena dinámicamente -->
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </section>
        </div>

        <!-- Footer -->
        <footer class="main-footer">
            <strong>OperaSys &copy; 2025</strong> - Sistema de Reportes de Operación
            <div class="float-right d-none d-sm-inline-block">
                <b>Versión</b> 1.0
            </div>
        </footer>
    </div>

    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap 4 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <!-- AdminLTE App CDN -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <!-- Offline Support -->
    <script src="../../assets/js/offline.js"></script>
    <!-- Custom JS -->
    <script src="../../assets/js/dashboard.js"></script>
</body>

</html>