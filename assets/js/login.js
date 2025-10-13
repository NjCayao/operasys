/**
 * OperaSys - JavaScript de Login
 * Archivo: assets/js/login.js
 * Descripción: Manejo del formulario de inicio de sesión
 */

document.addEventListener('DOMContentLoaded', function() {
    
    const formLogin = document.getElementById('formLogin');
    
    if (formLogin) {
        formLogin.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btnSubmit = this.querySelector('button[type="submit"]');
            const alertMessage = document.getElementById('alertMessage');
            const alertText = document.getElementById('alertText');
            
            // Deshabilitar botón y mostrar loading
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Iniciando...';
            
            // Ocultar mensaje de error previo
            alertMessage.style.display = 'none';
            
            try {
                // Obtener datos del formulario
                const formData = new FormData(this);
                
                // Enviar petición al API
                const response = await fetch('../../api/auth.php', {
                    method: 'POST',
                    body: formData
                });
                
                // Parsear respuesta JSON
                const data = await response.json();
                
                if (data.success) {
                    // Login exitoso
                    btnSubmit.innerHTML = '<i class="fas fa-check"></i> ¡Bienvenido!';
                    btnSubmit.classList.remove('btn-primary');
                    btnSubmit.classList.add('btn-success');
                    
                    // Redirigir al dashboard
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 500);
                    
                } else {
                    // Login fallido
                    throw new Error(data.message || 'Error al iniciar sesión');
                }
                
            } catch (error) {
                // Mostrar mensaje de error
                alertMessage.classList.add('alert-danger');
                alertText.textContent = error.message;
                alertMessage.style.display = 'block';
                
                // Restaurar botón
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="fas fa-sign-in-alt"></i> Iniciar Sesión';
            }
        });
        
        // Validar DNI en tiempo real (solo números)
        const inputDni = formLogin.querySelector('input[name="dni"]');
        if (inputDni) {
            inputDni.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }
    }
    
});