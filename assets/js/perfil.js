/**
 * OperaSys - JavaScript de Perfil
 * Archivo: assets/js/perfil.js
 * Descripción: Cambio de contraseña
 */

document.getElementById('formCambiarPassword').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const passwordNueva = document.getElementById('password_nueva').value;
    const passwordConfirmar = document.getElementById('password_confirmar').value;
    
    // Validar que coincidan
    if (passwordNueva !== passwordConfirmar) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Las contraseñas no coinciden'
        });
        return;
    }
    
    const formData = new FormData(this);
    formData.append('action', 'cambiar_password');
    
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
            });
            this.reset();
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
            text: 'Error al cambiar contraseña'
        });
    }
});