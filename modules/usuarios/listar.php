<?php

/**
 * OperaSys - Listado de Usuarios
 * Archivo: modules/usuarios/listar.php
 * Descripción: Tabla con todos los usuarios del sistema (Solo Admin)
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

verificarAdmin(); // Solo administradores

// Obtener filtro de rol si existe
$filtroRol = $_GET['rol'] ?? '';
$tituloFiltro = '';

switch ($filtroRol) {
    case 'admin':
        $tituloFiltro = ' - Administradores';
        break;
    case 'supervisor':
        $tituloFiltro = ' - Supervisores';
        break;
    case 'operador':
        $tituloFiltro = ' - Operadores';
        break;
    default:
        $tituloFiltro = '';
}

// Configuración de la página
$page_title = 'Gestión de Usuarios';
$page_depth = 2;
$use_datatables = true;
$use_sweetalert = true;

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
                    <h1><i class="fas fa-users"></i> Gestión de Usuarios<?php echo $tituloFiltro; ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../admin/dashboard.php">Inicio</a></li>
                        <li class="breadcrumb-item active">Usuarios</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-list"></i> Lista de Usuarios Registrados
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalCrearUsuario">
                                    <i class="fas fa-user-plus"></i> Nuevo Usuario
                                </button>
                            </div>
                        </div>

                        <div class="card-body">
                            <table id="tablaUsuarios" class="table table-bordered table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nombre Completo</th>
                                        <th>DNI</th>
                                        <th>Cargo</th>
                                        <th>Rol</th>
                                        <th>Firma</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Se llena dinámicamente con AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal: Crear Usuario -->
<div class="modal fade" id="modalCrearUsuario" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus"></i> Crear Nuevo Usuario
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <form id="formCrearUsuario">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-user"></i> Nombre Completo *</label>
                                <input type="text"
                                    class="form-control"
                                    name="nombre_completo"
                                    required
                                    minlength="3"
                                    maxlength="150"
                                    placeholder="Nombres y Apellidos">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-id-card"></i> DNI / Cédula *</label>
                                <input type="text"
                                    class="form-control"
                                    name="dni"
                                    id="modalDni"
                                    required
                                    pattern="[0-9]{8,20}"
                                    maxlength="20"
                                    placeholder="Solo números">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-user-tag"></i> Rol del Sistema *</label>
                                <select class="form-control" name="rol" id="modalRol" required>
                                    <option value="">Seleccionar Rol</option>
                                    <option value="operador">Operador</option>
                                    <option value="supervisor">Supervisor</option>
                                    <option value="admin">Administrador</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6" id="modalCategoriaContainer" style="display: none;">
                            <div class="form-group">
                                <label><i class="fas fa-truck-monster"></i> Categoría de Equipo *</label>
                                <select class="form-control" name="categoria_equipo" id="modalCategoria">
                                    <option value="">Seleccionar Categoría</option>
                                    <option value="Excavadora">Excavadora</option>
                                    <option value="Volquete">Volquete</option>
                                    <option value="Tractor">Tractor</option>
                                    <option value="Cargador Frontal">Cargador Frontal</option>
                                    <option value="Rodillo Compactador">Rodillo Compactador</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-lock"></i> Contraseña *</label>
                                <input type="password"
                                    class="form-control"
                                    name="password"
                                    id="modalPassword"
                                    required
                                    minlength="6"
                                    placeholder="Mínimo 6 caracteres">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-lock"></i> Confirmar Contraseña *</label>
                                <input type="password"
                                    class="form-control"
                                    id="modalPasswordConfirm"
                                    required
                                    minlength="6"
                                    placeholder="Repita la contraseña">
                            </div>
                        </div>
                    </div>

                    <!-- Campo oculto para el cargo generado -->
                    <input type="hidden" name="cargo" id="modalCargoHidden">

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Ver Firma -->
<div class="modal fade" id="modalVerFirma" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title">
                    <i class="fas fa-signature"></i> Firma Digital
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="imagenFirma" src="" alt="Firma" class="img-fluid border" style="max-height: 300px;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning" id="btnEditarFirma">
                    <i class="fas fa-edit"></i> Editar Firma
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php
$custom_js_file = 'assets/js/usuarios.js?v=' . ASSETS_VERSION;
include '../../layouts/footer.php';
?>