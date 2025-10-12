<?php
/**
 * OperaSys - Gestión de Usuarios
 * Archivo: modules/admin/usuarios.php
 * Descripción: CRUD completo de usuarios (solo admin)
 */

require_once '../../config/database.php';
require_once '../../config/config.php';

// Verificar sesión y rol
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$nombreUsuario = $_SESSION['nombre'];
$rolUsuario = $_SESSION['rol'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - OperaSys</title>
    
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
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
                    <span class="badge badge-danger">Admin</span>
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
                    <li class="nav-header">ADMINISTRACIÓN</li>
                    <li class="nav-item">
                        <a href="usuarios.php" class="nav-link active">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Gestión de Usuarios</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="reportes_global.php" class="nav-link">
                            <i class="nav-icon fas fa-chart-line"></i>
                            <p>Reportes Globales</p>
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
                            <i class="fas fa-users"></i> Gestión de Usuarios
                        </h1>
                    </div>
                    <div class="col-sm-6">
                        <button class="btn btn-success float-sm-right" data-toggle="modal" data-target="#modalNuevoUsuario">
                            <i class="fas fa-plus"></i> Nuevo Usuario
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                
                <!-- Tabla de usuarios -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list"></i> Lista de Usuarios
                        </h3>
                    </div>
                    <div class="card-body">
                        <table id="tablaUsuarios" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>DNI</th>
                                    <th>Cargo</th>
                                    <th>Rol</th>
                                    <th>Firma</th>
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
        <strong>OperaSys &copy; 2025</strong> - Sistema de Reportes de Operación
        <div class="float-right d-none d-sm-inline-block">
            <b>Versión</b> 1.0
        </div>
    </footer>
</div>

<!-- Modal Nuevo Usuario -->
<div class="modal fade" id="modalNuevoUsuario">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h4 class="modal-title">
                    <i class="fas fa-user-plus"></i> Nuevo Usuario
                </h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="formNuevoUsuario">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nombre Completo *</label>
                        <input type="text" class="form-control" name="nombre_completo" required>
                    </div>
                    <div class="form-group">
                        <label>DNI *</label>
                        <input type="text" class="form-control" name="dni" required maxlength="20">
                    </div>
                    <div class="form-group">
                        <label>Cargo *</label>
                        <select class="form-control" name="cargo" required>
                            <option value="">Seleccionar...</option>
                            <option value="Operador de Excavadora">Operador de Excavadora</option>
                            <option value="Operador de Volquete">Operador de Volquete</option>
                            <option value="Operador de Tractor">Operador de Tractor</option>
                            <option value="Operador de Cargador">Operador de Cargador</option>
                            <option value="Operador de Rodillo">Operador de Rodillo</option>
                            <option value="Supervisor">Supervisor</option>
                            <option value="Administrador">Administrador</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Rol *</label>
                        <select class="form-control" name="rol" required>
                            <option value="operador">Operador</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Contraseña *</label>
                        <input type="password" class="form-control" name="password" required minlength="6">
                        <small class="text-muted">Mínimo 6 caracteres</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Usuario -->
<div class="modal fade" id="modalEditarUsuario">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h4 class="modal-title">
                    <i class="fas fa-edit"></i> Editar Usuario
                </h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="formEditarUsuario">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nombre Completo *</label>
                        <input type="text" class="form-control" name="nombre_completo" id="edit_nombre" required>
                    </div>
                    <div class="form-group">
                        <label>DNI *</label>
                        <input type="text" class="form-control" name="dni" id="edit_dni" required maxlength="20">
                    </div>
                    <div class="form-group">
                        <label>Cargo *</label>
                        <select class="form-control" name="cargo" id="edit_cargo" required>
                            <option value="Operador de Excavadora">Operador de Excavadora</option>
                            <option value="Operador de Volquete">Operador de Volquete</option>
                            <option value="Operador de Tractor">Operador de Tractor</option>
                            <option value="Operador de Cargador">Operador de Cargador</option>
                            <option value="Operador de Rodillo">Operador de Rodillo</option>
                            <option value="Supervisor">Supervisor</option>
                            <option value="Administrador">Administrador</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Rol *</label>
                        <select class="form-control" name="rol" id="edit_rol" required>
                            <option value="operador">Operador</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nueva Contraseña</label>
                        <input type="password" class="form-control" name="password" minlength="6">
                        <small class="text-muted">Dejar en blanco para mantener la actual</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    cargarUsuarios();
    
    // Nuevo usuario
    $('#formNuevoUsuario').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '../../api/usuarios.php?action=crear',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire('¡Éxito!', response.message, 'success');
                    $('#modalNuevoUsuario').modal('hide');
                    $('#formNuevoUsuario')[0].reset();
                    cargarUsuarios();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            }
        });
    });
    
    // Editar usuario
    $('#formEditarUsuario').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '../../api/usuarios.php?action=actualizar',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire('¡Éxito!', response.message, 'success');
                    $('#modalEditarUsuario').modal('hide');
                    cargarUsuarios();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            }
        });
    });
});

function cargarUsuarios() {
    $.ajax({
        url: '../../api/usuarios.php?action=listar',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let html = '';
                response.data.forEach(function(user) {
                    let rolBadge = user.rol === 'admin' ? 'danger' : (user.rol === 'supervisor' ? 'warning' : 'info');
                    let estadoBadge = user.estado == 1 ? 'success' : 'secondary';
                    let estadoTexto = user.estado == 1 ? 'Activo' : 'Inactivo';
                    let firmaIcono = user.firma ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>';
                    
                    html += `<tr>
                        <td>${user.id}</td>
                        <td>${user.nombre_completo}</td>
                        <td>${user.dni}</td>
                        <td>${user.cargo}</td>
                        <td><span class="badge badge-${rolBadge}">${user.rol}</span></td>
                        <td class="text-center">${firmaIcono}</td>
                        <td><span class="badge badge-${estadoBadge}">${estadoTexto}</span></td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editarUsuario(${user.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-${user.estado == 1 ? 'warning' : 'success'}" onclick="toggleEstado(${user.id}, ${user.estado})">
                                <i class="fas fa-${user.estado == 1 ? 'ban' : 'check'}"></i>
                            </button>
                        </td>
                    </tr>`;
                });
                
                $('#tablaUsuarios tbody').html(html);
                
                if ($.fn.DataTable.isDataTable('#tablaUsuarios')) {
                    $('#tablaUsuarios').DataTable().destroy();
                }
                
                $('#tablaUsuarios').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                    },
                    order: [[0, 'desc']]
                });
            }
        }
    });
}

function editarUsuario(id) {
    $.ajax({
        url: `../../api/usuarios.php?action=obtener&id=${id}`,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let user = response.data;
                $('#edit_id').val(user.id);
                $('#edit_nombre').val(user.nombre_completo);
                $('#edit_dni').val(user.dni);
                $('#edit_cargo').val(user.cargo);
                $('#edit_rol').val(user.rol);
                $('#modalEditarUsuario').modal('show');
            }
        }
    });
}

function toggleEstado(id, estadoActual) {
    let nuevoEstado = estadoActual == 1 ? 0 : 1;
    let texto = nuevoEstado == 1 ? 'activar' : 'desactivar';
    
    Swal.fire({
        title: `¿${texto.charAt(0).toUpperCase() + texto.slice(1)} usuario?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí',
        cancelButtonText: 'No'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../../api/usuarios.php?action=toggle_estado',
                method: 'POST',
                data: { id: id, estado: nuevoEstado },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('¡Éxito!', response.message, 'success');
                        cargarUsuarios();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                }
            });
        }
    });
}
</script>

</body>
</html>
