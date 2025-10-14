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

                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="<?php echo $base_path; ?>modules/admin/dashboard.php"
                        class="nav-link <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <!-- Equipos -->
                <li class="nav-item">
                    <a href="<?php echo $base_path; ?>modules/equipos/listar.php"
                        class="nav-link <?php echo $current_page == 'listar' && strpos($_SERVER['PHP_SELF'], 'equipos') !== false ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-truck-monster"></i>
                        <p>Equipos</p>
                    </a>
                </li>

                <!-- Mis Reportes -->
                <li class="nav-item">
                    <a href="<?php echo $base_path; ?>modules/reportes/listar.php"
                        class="nav-link <?php echo $current_page == 'listar' && strpos($_SERVER['PHP_SELF'], 'reportes') !== false ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-file-alt"></i>
                        <p>Mis Reportes</p>
                    </a>
                </li>

                <!-- Nuevo Reporte -->
                <li class="nav-item">
                    <a href="<?php echo $base_path; ?>modules/reportes/crear.php"
                        class="nav-link <?php echo $current_page == 'crear' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-plus-circle"></i>
                        <p>Nuevo Reporte</p>
                    </a>
                </li>

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

                        <!-- Catálogos (Solo Admin) -->
                        <li class="nav-item has-treeview">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-cogs"></i>
                                <p>
                                    Catálogos
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="<?php echo $base_path; ?>modules/admin/tipos_trabajo.php"
                                        class="nav-link <?php echo $current_page == 'tipos_trabajo' ? 'active' : ''; ?>">
                                        <i class="fas fa-tasks nav-icon"></i>
                                        <p>Tipos de Trabajo</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?php echo $base_path; ?>modules/admin/fases_costo.php"
                                        class="nav-link <?php echo $current_page == 'fases_costo' ? 'active' : ''; ?>">
                                        <i class="fas fa-tag nav-icon"></i>
                                        <p>Fases de Costo</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>

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
                    <?php endif; ?>

                <?php endif; ?>

            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>