<?php
/**
 * OperaSys - Layout Navbar
 * Archivo: layouts/navbar.php
 * Descripción: Barra de navegación superior (reutilizable)
 */

$nombreUsuario = $_SESSION['nombre'] ?? 'Usuario';
$rolUsuario = $_SESSION['rol'] ?? 'operador';

// Determinar color del badge según rol
$rolBadgeColor = [
    'admin' => 'danger',
    'supervisor' => 'warning',
    'operador' => 'info'
];
$badgeColor = $rolBadgeColor[$rolUsuario] ?? 'secondary';
?>

<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars"></i>
            </a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="<?php echo $base_path; ?>modules/admin/dashboard.php" class="nav-link">
                <i class="fas fa-home"></i> Inicio
            </a>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <!-- Estado de conexión -->
        <li class="nav-item">
            <span class="nav-link" id="estadoConexion">
                <i class="fas fa-wifi text-success"></i>
                <span class="d-none d-sm-inline">Online</span>
            </span>
        </li>
        
        <!-- Usuario actual -->
        <li class="nav-item">
            <span class="nav-link">
                <i class="fas fa-user"></i> <?php echo htmlspecialchars($nombreUsuario); ?>
                <span class="badge badge-<?php echo $badgeColor; ?>"><?php echo ucfirst($rolUsuario); ?></span>
            </span>
        </li>
        
        <!-- Logout -->
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $base_path; ?>modules/auth/logout.php" title="Cerrar Sesión">
                <i class="fas fa-sign-out-alt"></i>
                <span class="d-none d-sm-inline">Salir</span>
            </a>
        </li>
    </ul>
</nav>