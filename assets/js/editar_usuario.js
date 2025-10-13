/**
 * OperaSys - JavaScript de Editar Usuario
 * Archivo: assets/js/editar_usuario.js
 * Descripción: Manejo del formulario de edición de usuario
 */

// Mostrar/ocultar categoría según rol
document.getElementById('rol').addEventListener('change', function() {
    const rol = this.value;
    const categoriaContainer = document.getElementById('categoriaContainer');
    const categoriaSelect = document.getElementById('categoria_equipo');
    
    if (rol === 'operador') {
        categoriaContainer.style.display = 'block';
        categoriaSelect.required = true;
    } else {
        categoriaContainer.style.display = 'none';
        categoriaSelect.required = false;
        categoriaSelect.value = '';
    }
});

// Mostrar/ocultar campos de contraseña
document.getElementById('cambiarPassword').addEventListener('change', function() {
    const passwordFields = document.getElementById('passwordFields');
    const passwordInput = document.getElementById('password');
    const passwordConfirmInput = document.getElementById('password_confirm');
    
    if (this.checked) {
        passwordFields.style.display = 'block';
        passwordInput.required = true;
        passwordConfirmInput.required = true;
    } else {
        passwordFields.style.display = 'none';
        passwordInput.required = false;
        passwordConfirmInput.required = false;
        passwordInput.value = '';
        passwordConfirmInput.value = '';
    }
});

// Validar DNI solo números
document.getElementById('dni').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});

// Enviar formulario
document.getElementById('formEditarUsuario').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const rol = document.getElementById('rol').value;
    const categoria = document.getElementById('categoria_equipo').value;
    const filtroRol = formData.get('filtro_rol');
    
    // Construir cargo según rol
    let cargo = '';
    if (rol === 'operador') {
        if (!categoria) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe seleccionar una categoría de equipo para operadores'
            });
            return;
        }
        cargo = 'Operador de ' + categoria;
    } else if (rol === 'supervisor') {
        cargo = 'Supervisor';
    } else if (rol === 'admin') {
        cargo = 'Administrador';
    }
    
    // Establecer cargo
    document.getElementById('cargoHidden').value = cargo;
    formData.set('cargo', cargo);
    formData.append('action', 'actualizar');
    
    // Validar contraseñas si se está cambiando
    const cambiarPassword = document.getElementById('cambiarPassword').checked;
    if (cambiarPassword) {
        const password = document.getElementById('password').value;
        const passwordConfirm = document.getElementById('password_confirm').value;
        
        if (password !== passwordConfirm) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Las contraseñas no coinciden'
            });
            return;
        }
        
        if (password.length < 6) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'La contraseña debe tener al menos 6 caracteres'
            });
            return;
        }
    }
    
    try {
        const response = await fetch('../../api/usuarios.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: result.message,
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                // Redirigir manteniendo el filtro
                let url = 'listar.php';
                if (filtroRol) {
                    url += '?rol=' + filtroRol;
                }
                window.location.href = url;
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.message
            });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al conectar con el servidor'
        });
    }
});