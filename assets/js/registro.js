/**
 * OperaSys - JavaScript de Registro
 * Archivo: assets/js/registro.js
 * Descripción: Manejo del formulario de registro de usuarios
 */

document.addEventListener('DOMContentLoaded', function() {
    
    const formRegistro = document.getElementById('formRegistro');
    
    if (formRegistro) {
        formRegistro.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Obtener elementos
            const btnSubmit = this.querySelector('button[type="submit"]');
            const alertMessage = document.getElementById('alertMessage');
            const alertText = document.getElementById('alertText');
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;
            
            // Validar que las contraseñas coincidan
            if (password !== passwordConfirm) {
                alertMessage.classList.remove('alert-success');
                alertMessage.classList.add('alert-danger');
                alertText.textContent = 'Las contraseñas no coinciden';
                alertMessage.style.display = 'block';
                return;
            }
            
            // Deshabilitar botón y mostrar loading
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';
            
            // Ocultar mensaje de error previo
            alertMessage.style.display = 'none';
            
            try {
                // Obtener datos del formulario
                const formData = new FormData(this);
                formData.append('action', 'register');
                
                // Enviar petición al API
                const response = await fetch('../../api/usuarios.php', {
                    method: 'POST',
                    body: formData
                });
                
                // Parsear respuesta JSON
                const data = await response.json();
                
                if (data.success) {
                    // Registro exitoso
                    btnSubmit.innerHTML = '<i class="fas fa-check"></i> ¡Registrado!';
                    btnSubmit.classList.remove('btn-primary');
                    btnSubmit.classList.add('btn-success');
                    
                    // Mostrar mensaje de éxito
                    alertMessage.classList.remove('alert-danger');
                    alertMessage.classList.add('alert-success');
                    alertText.textContent = data.message;
                    alertMessage.style.display = 'block';
                    
                    // Redirigir a captura de firma después de 1.5 segundos
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                    
                } else {
                    // Registro fallido
                    throw new Error(data.message || 'Error al registrar usuario');
                }
                
            } catch (error) {
                // Mostrar mensaje de error
                alertMessage.classList.remove('alert-success');
                alertMessage.classList.add('alert-danger');
                alertText.textContent = error.message;
                alertMessage.style.display = 'block';
                
                // Restaurar botón
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="fas fa-user-plus"></i> Registrarme';
            }
        });
        
        // Validar DNI en tiempo real
        const inputDni = formRegistro.querySelector('input[name="dni"]');
        if (inputDni) {
            inputDni.addEventListener('input', function(e) {
                // Solo permitir números
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }
        
        // Mostrar/ocultar contraseña
        const passwordInputs = formRegistro.querySelectorAll('input[type="password"]');
        passwordInputs.forEach(input => {
            const parent = input.parentElement;
            const icon = parent.querySelector('.input-group-text');
            
            if (icon) {
                icon.style.cursor = 'pointer';
                icon.addEventListener('click', function() {
                    if (input.type === 'password') {
                        input.type = 'text';
                        this.querySelector('i').classList.replace('fa-lock', 'fa-lock-open');
                    } else {
                        input.type = 'password';
                        this.querySelector('i').classList.replace('fa-lock-open', 'fa-lock');
                    }
                });
            }
        });
    }
    
});
