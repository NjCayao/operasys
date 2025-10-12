/**
 * OperaSys - JavaScript de Firma Digital
 * Archivo: assets/js/firma.js
 * Descripción: Captura de firma usando Canvas HTML5 con soporte táctil y mouse
 */

document.addEventListener('DOMContentLoaded', function() {
    
    const canvas = document.getElementById('canvasFirma');
    const btnLimpiar = document.getElementById('btnLimpiar');
    const btnGuardar = document.getElementById('btnGuardar');
    const alertMessage = document.getElementById('alertMessage');
    const alertText = document.getElementById('alertText');
    
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    let dibujando = false;
    let firmaVacia = true;
    
    // Configuración del canvas
    ctx.strokeStyle = '#000000';
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    
    // ============================================
    // FUNCIONES DE DIBUJO
    // ============================================
    
    function obtenerPosicion(e) {
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        
        if (e.touches && e.touches.length > 0) {
            // Touch (móvil)
            return {
                x: (e.touches[0].clientX - rect.left) * scaleX,
                y: (e.touches[0].clientY - rect.top) * scaleY
            };
        } else {
            // Mouse (PC)
            return {
                x: (e.clientX - rect.left) * scaleX,
                y: (e.clientY - rect.top) * scaleY
            };
        }
    }
    
    function iniciarDibujo(e) {
        e.preventDefault();
        dibujando = true;
        firmaVacia = false;
        
        const pos = obtenerPosicion(e);
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
    }
    
    function dibujar(e) {
        e.preventDefault();
        
        if (!dibujando) return;
        
        const pos = obtenerPosicion(e);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
    }
    
    function detenerDibujo(e) {
        e.preventDefault();
        
        if (dibujando) {
            dibujando = false;
            ctx.beginPath();
        }
    }
    
    // ============================================
    // EVENTOS DE MOUSE
    // ============================================
    canvas.addEventListener('mousedown', iniciarDibujo);
    canvas.addEventListener('mousemove', dibujar);
    canvas.addEventListener('mouseup', detenerDibujo);
    canvas.addEventListener('mouseout', detenerDibujo);
    
    // ============================================
    // EVENTOS TÁCTILES (MÓVIL/TABLET)
    // ============================================
    canvas.addEventListener('touchstart', iniciarDibujo);
    canvas.addEventListener('touchmove', dibujar);
    canvas.addEventListener('touchend', detenerDibujo);
    canvas.addEventListener('touchcancel', detenerDibujo);
    
    // ============================================
    // BOTÓN LIMPIAR
    // ============================================
    btnLimpiar.addEventListener('click', function() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        firmaVacia = true;
        alertMessage.style.display = 'none';
        console.log('✓ Firma limpiada');
    });
    
    // ============================================
    // BOTÓN GUARDAR
    // ============================================
    btnGuardar.addEventListener('click', async function() {
        
        // Validar que haya firma
        if (firmaVacia) {
            alertMessage.classList.remove('alert-success');
            alertMessage.classList.add('alert-danger');
            alertText.textContent = 'Por favor, dibuja tu firma antes de guardar';
            alertMessage.style.display = 'block';
            return;
        }
        
        // Deshabilitar botón
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        alertMessage.style.display = 'none';
        
        try {
            // Convertir canvas a base64
            const firmaBase64 = canvas.toDataURL('image/png');
            
            // Validar tamaño (máximo 500KB)
            const tamanioKB = Math.round((firmaBase64.length * 3) / 4 / 1024);
            if (tamanioKB > 500) {
                throw new Error('La firma es muy grande. Por favor, dibújala más simple.');
            }
            
            console.log('📝 Tamaño de la firma:', tamanioKB + 'KB');
            
            // Preparar datos
            const formData = new FormData();
            formData.append('action', 'guardar_firma');
            formData.append('firma', firmaBase64);
            
            // Enviar al API
            const response = await fetch('../../api/usuarios.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Éxito
                btnGuardar.innerHTML = '<i class="fas fa-check"></i> ¡Guardada!';
                btnGuardar.classList.remove('btn-success');
                btnGuardar.classList.add('btn-primary');
                
                alertMessage.classList.remove('alert-danger');
                alertMessage.classList.add('alert-success');
                alertText.textContent = data.message;
                alertMessage.style.display = 'block';
                
                console.log('✓ Firma guardada exitosamente');
                
                // Redirigir después de 2 segundos
                setTimeout(() => {
                    if (data.es_nuevo_registro) {
                        // Si es nuevo registro, ir al login con mensaje
                        window.location.href = data.redirect + '?registro=exitoso';
                    } else {
                        // Si actualizó firma, volver al dashboard
                        window.location.href = data.redirect;
                    }
                }, 2000);
                
            } else {
                throw new Error(data.message || 'Error al guardar firma');
            }
            
        } catch (error) {
            console.error('❌ Error:', error);
            
            // Mostrar error
            alertMessage.classList.remove('alert-success');
            alertMessage.classList.add('alert-danger');
            alertText.textContent = error.message;
            alertMessage.style.display = 'block';
            
            // Restaurar botón
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="fas fa-save"></i> Guardar Firma';
        }
    });
    
    // ============================================
    // PREVENIR SCROLL EN MÓVILES AL DIBUJAR
    // ============================================
    canvas.addEventListener('touchstart', function(e) {
        if (e.target === canvas) {
            e.preventDefault();
        }
    }, { passive: false });
    
    canvas.addEventListener('touchmove', function(e) {
        if (e.target === canvas) {
            e.preventDefault();
        }
    }, { passive: false });
    
    console.log('✓ Canvas de firma inicializado');
    
});
