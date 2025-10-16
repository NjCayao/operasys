<?php

/**
 * OperaSys - Layout Sidebar
 * Archivo: layouts/sidebar.php
 * Descripción: Menú lateral dinámico según rol (reutilizable)
 */

$nombreUsuario = $_SESSION['nombre'] ?? 'Usuario';
$rolUsuario = $_SESSION['rol'] ?? 'operador';

// Determinar qué página está activa
$current_page = basename($_SERVER['PHP_SELF'], '.php');

$es_solo_lectura = ($_SESSION['rol'] === 'supervisor');
?>

<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="<?php echo $base_path; ?>index.php" class="brand-link text-center">
        <span class="brand-text font-weight-light"><b>Opera</b>Sys</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <i class="fas fa-user-circle fa-2x text-white"></i>
            </div>
            <div class="info">
                <a href="<?php echo $base_path; ?>modules/usuarios/perfil.php" class="d-block">
                    <?php echo htmlspecialchars($nombreUsuario); ?>
                </a>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                <?php if ($rolUsuario === 'admin' || $rolUsuario === 'supervisor'): ?>
                    <!-- Dashboard (Solo Admin y Supervisor) -->
                    <li class="nav-item">
                        <a href="<?php echo $base_path; ?>modules/admin/dashboard.php"
                            class="nav-link <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Nuevo Reporte (Todos los roles) -->
                <li class="nav-item">
                    <a href="<?php echo $base_path; ?>modules/reportes/crear.php"
                        class="nav-link <?php echo $current_page == 'crear' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-plus-circle"></i>
                        <p>Nuevo Reporte</p>
                    </a>
                </li>

                <!-- Mis Reportes (Todos los roles) -->
                <li class="nav-item">
                    <a href="<?php echo $base_path; ?>modules/reportes/listar.php"
                        class="nav-link <?php echo $current_page == 'listar' && strpos($_SERVER['PHP_SELF'], 'reportes') !== false ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-file-alt"></i>
                        <p>Mis Reportes</p>
                    </a>
                </li>

                <?php if ($rolUsuario === 'admin' || $rolUsuario === 'supervisor'): ?>
                    <!-- Equipos (Solo Admin y Supervisor) -->
                    <li class="nav-item">
                        <a href="<?php echo $base_path; ?>modules/equipos/listar.php"
                            class="nav-link <?php echo $current_page == 'listar' && strpos($_SERVER['PHP_SELF'], 'equipos') !== false ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-truck-monster"></i>
                            <p>Equipos</p>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($rolUsuario === 'admin' || $rolUsuario === 'supervisor'): ?>
                    <!-- Sección Administración -->
                    <li class="nav-header">ADMINISTRACIÓN</li>

                    <?php if ($rolUsuario === 'admin'): ?>
                        <!-- Usuarios con Desplegable (Solo Admin) -->
                        <li class="nav-item has-treeview <?php echo (strpos($_SERVER['PHP_SELF'], 'usuarios') !== false) ? 'menu-open' : ''; ?>">
                            <a href="#" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'usuarios') !== false) ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-users"></i>
                                <p>
                                    Usuarios
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="<?php echo $base_path; ?>modules/usuarios/listar.php?rol=operador"
                                        class="nav-link <?php echo (isset($_GET['rol']) && $_GET['rol'] == 'operador') ? 'active' : ''; ?>">
                                        <i class="fas fa-hard-hat nav-icon"></i>
                                        <p>Operadores</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?php echo $base_path; ?>modules/usuarios/listar.php?rol=supervisor"
                                        class="nav-link <?php echo (isset($_GET['rol']) && $_GET['rol'] == 'supervisor') ? 'active' : ''; ?>">
                                        <i class="fas fa-user-tie nav-icon"></i>
                                        <p>Supervisores</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?php echo $base_path; ?>modules/usuarios/listar.php?rol=admin"
                                        class="nav-link <?php echo (isset($_GET['rol']) && $_GET['rol'] == 'admin') ? 'active' : ''; ?>">
                                        <i class="fas fa-crown nav-icon"></i>
                                        <p>Administradores</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <!-- Catálogos (Admin y Supervisor) -->
                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-cogs"></i>
                            <p>
                                Planillas de Control
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?php echo $base_path; ?>modules/admin/actividades_ht.php"
                                    class="nav-link <?php echo $current_page == 'actividades_ht' ? 'active' : ''; ?>">
                                    <i class="fas fa-tasks nav-icon text-success"></i>
                                    <p>Horas Trabajadas (HT)</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo $base_path; ?>modules/admin/motivos_hp.php"
                                    class="nav-link <?php echo $current_page == 'motivos_hp' ? 'active' : ''; ?>">
                                    <i class="fas fa-pause-circle nav-icon text-warning"></i>
                                    <p>Horas Paradas (HP)</p>
                                </a>
                            </li>                            
                        </ul>
                    </li>

                    <!-- Reportes Globales (Admin y Supervisor) -->
                    <li class="nav-item">
                        <a href="<?php echo $base_path; ?>modules/admin/reportes_global.php"
                            class="nav-link <?php echo $current_page == 'reportes_global' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-chart-bar"></i>
                            <p>Reportes Globales</p>
                        </a>
                    </li>

                    <?php if ($rolUsuario === 'admin'): ?>
                        <!-- Auditoría (Solo Admin) -->
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>modules/admin/auditoria.php"
                                class="nav-link <?php echo $current_page == 'auditoria' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-history"></i>
                                <p>Auditoría</p>
                            </a>
                        </li>

                        <!-- Configuración Empresa (Solo Admin) -->
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>modules/admin/configuracion_empresa.php"
                                class="nav-link <?php echo $current_page == 'configuracion_empresa' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-building"></i>
                                <p>Configuración Empresa</p>
                            </a>
                        </li>
                    <?php endif; ?>

                <?php endif; ?>
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->

    <!-- Botón PWA en Sidebar -->
    <div class="mt-3 px-3" id="sidebarInstallPWA" style="display: none;">
        <button id="btnInstallPWA" class="btn btn-info btn-sm btn-block">
            <i class="fas fa-download"></i> Instalar App
        </button>
    </div>
</aside>