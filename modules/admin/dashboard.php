<?php

/**
 * OperaSys - Dashboard Principal
 * Archivo: modules/admin/dashboard.php
 * Descripción: Panel de control con estadísticas y gráficos
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

verificarSesion();

// Configuración de la página
$page_title = 'Dashboard';
$page_depth = 2;
$use_chartjs = true;

// Incluir layouts
include '../../layouts/header.php';
include '../../layouts/navbar.php';
include '../../layouts/sidebar.php';
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
    <!-- Content Header -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
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
    </section>

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
                        <a href="../reportes/listar.php" class="small-box-footer">
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
                                        <th>Actividades</th>
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

            <?php if ($_SESSION['rol'] === 'admin'): ?>
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
                                <a href="../usuarios/listar.php" class="btn btn-primary btn-block">
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

<?php
$custom_js_file = 'assets/js/dashboard.js?v=' . ASSETS_VERSION;
include '../../layouts/footer.php';
?>