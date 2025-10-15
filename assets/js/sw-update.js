/**
 * OperaSys - Detector de actualizaciones
 * Notifica al usuario cuando hay nueva versión
 */

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/operasys/service-worker.js').then(reg => {
        
        // Detectar cuando hay actualización disponible
        reg.addEventListener('updatefound', () => {
            const newWorker = reg.installing;
            
            newWorker.addEventListener('statechange', () => {
                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                    // Hay nueva versión disponible
                    mostrarNotificacionActualizacion();
                }
            });
        });
        
        // Verificar actualizaciones cada hora
        setInterval(() => {
            reg.update();
        }, 60 * 60 * 1000);
    });
}

function mostrarNotificacionActualizacion() {
    // SweetAlert si existe
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '🔄 Actualización disponible',
            text: 'Hay una nueva versión de OperaSys. ¿Recargar ahora?',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Actualizar',
            cancelButtonText: 'Después'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.reload();
            }
        });
    } 
    // Toastr si existe
    else if (typeof toastr !== 'undefined') {
        toastr.info('Nueva versión disponible. <a href="#" onclick="location.reload()">Actualizar</a>', 
                    'Actualización disponible', 
                    { timeOut: 0, extendedTimeOut: 0, closeButton: true });
    }
    // Fallback
    else {
        if (confirm('🔄 Nueva versión disponible. ¿Recargar ahora?')) {
            window.location.reload();
        }
    }
}

console.log('✓ Detector de actualizaciones cargado');