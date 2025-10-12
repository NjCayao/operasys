/**
 * OperaSys - JavaScript Principal
 * Archivo: assets/js/app.js
 * Descripción: Manejo del formulario de login y funciones generales
 */

// Esperar a que el DOM esté cargado
document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // MANEJO DEL FORMULARIO DE LOGIN
    // ============================================
    const formLogin = document.getElementById('formLogin');
    
    if (formLogin) {
        formLogin.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Obtener elementos
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
                    btnSubmit.innerHTML = '<i class="fas fa-check"></i> ¡Éxito!';
                    btnSubmit.classList.remove('btn-primary');
                    btnSubmit.classList.add('btn-success');
                    
                    // Mostrar mensaje de éxito
                    alertMessage.classList.remove('alert-danger');
                    alertMessage.classList.add('alert-success');
                    alertText.textContent = data.message || '¡Bienvenido!';
                    alertMessage.style.display = 'block';
                    
                    // Redirigir después de 1 segundo
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                    
                } else {
                    // Login fallido
                    throw new Error(data.message || 'Error al iniciar sesión');
                }
                
            } catch (error) {
                // Mostrar mensaje de error
                alertMessage.classList.remove('alert-success');
                alertMessage.classList.add('alert-danger');
                alertText.textContent = error.message;
                alertMessage.style.display = 'block';
                
                // Restaurar botón
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="fas fa-sign-in-alt"></i> Iniciar Sesión';
            }
        });
    }
    
    // ============================================
    // FUNCIÓN PARA MOSTRAR/OCULTAR CONTRASEÑA
    // ============================================
    const togglePassword = document.querySelectorAll('.toggle-password');
    
    togglePassword.forEach(function(element) {
        element.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
    
    // ============================================
    // FUNCIONES AUXILIARES
    // ============================================
    
    /**
     * Mostrar notificación toast
     * @param {string} message - Mensaje a mostrar
     * @param {string} type - Tipo: success, error, warning, info
     */
    window.showToast = function(message, type = 'info') {
        // Si existe toastr (AdminLTE)
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
        } else {
            // Fallback: alert simple
            alert(message);
        }
    };
    
    /**
     * Confirmar acción
     * @param {string} message - Mensaje de confirmación
     * @returns {boolean}
     */
    window.confirmar = function(message) {
        return confirm(message);
    };
    
});

// ============================================
// MANEJO DE ESTADO ONLINE/OFFLINE
// ============================================
window.addEventListener('online', function() {
    console.log('✅ Conexión restaurada');
    if (typeof showToast !== 'undefined') {
        showToast('Conexión a internet restaurada', 'success');
    }
});

window.addEventListener('offline', function() {
    console.log('❌ Sin conexión');
    if (typeof showToast !== 'undefined') {
        showToast('Sin conexión a internet. Los datos se guardarán localmente.', 'warning');
    }
});
