/**
 * OperaSys - Gestión Offline INTEGRADA
 * Archivo: assets/js/offline.js
 * Descripción: Gestión de conexión y sincronización usando IndexedDBManager
 */

// ============================================
// DETECTAR ESTADO DE CONEXIÓN
// ============================================
window.addEventListener('online', async function() {
    console.log('✓ Conexión restaurada');
    mostrarNotificacion('Conexión restaurada', 'success');
    
    try {
        // Sincronizar catálogos
        await window.IndexedDBManager.sincronizarCatalogos();
        console.log('[Offline] Catálogos actualizados');
        
        // Sincronizar reportes pendientes
        const sincronizado = await window.IndexedDBManager.sincronizarReportesPendientes();
        
        if (sincronizado) {
            const pendientes = await window.IndexedDBManager.contarReportesPendientes();
            
            if (pendientes === 0) {
                mostrarNotificacion('Todos los reportes se han sincronizado', 'success');
                
                // Recargar página si estamos en listado de reportes
                if (window.location.pathname.includes('reportes')) {
                    setTimeout(() => location.reload(), 2000);
                }
            } else {
                mostrarNotificacion(`Quedan ${pendientes} reportes por sincronizar`, 'info');
            }
        }
        
    } catch (error) {
        console.error('[Offline] Error en sincronización:', error);
    }
});

window.addEventListener('offline', function() {
    console.log('⚠ Sin conexión');
    mostrarNotificacion('Sin conexión. Los datos se guardarán localmente.', 'warning');
});

// ============================================
// VERIFICAR REPORTES PENDIENTES AL CARGAR
// ============================================
window.addEventListener('indexeddb-ready', async function() {
    try {
        const pendientes = await window.IndexedDBManager.contarReportesPendientes();
        
        if (pendientes > 0) {
            console.log(`⚠ Tienes ${pendientes} reportes pendientes de sincronizar`);
            
            // Mostrar badge en el menú si existe
            const badge = document.querySelector('#menu-reportes-pendientes-badge');
            if (badge) {
                badge.textContent = pendientes;
                badge.style.display = 'inline';
            }
            
            // Si hay internet, intentar sincronizar
            if (navigator.onLine) {
                setTimeout(async () => {
                    await window.IndexedDBManager.sincronizarReportesPendientes();
                }, 3000); // Esperar 3 segundos después de cargar
            }
        }
        
        // Mostrar última sincronización
        const ultimaSync = await window.IndexedDBManager.obtenerUltimaSincronizacion();
        if (ultimaSync) {
            console.log('[Offline] Última sincronización:', ultimaSync.toLocaleString());
        }
        
    } catch (error) {
        console.error('[Offline] Error al verificar reportes pendientes:', error);
    }
});

// ============================================
// FUNCIÓN: GUARDAR REPORTE OFFLINE
// ============================================
async function guardarReporteOffline(reporteData) {
    try {
        const id = await window.IndexedDBManager.guardarReportePendiente(reporteData);
        
        mostrarNotificacion('Reporte guardado offline. Se sincronizará cuando haya conexión.', 'info');
        
        return { success: true, id: id, offline: true };
        
    } catch (error) {
        console.error('[Offline] Error al guardar reporte:', error);
        return { success: false, message: error.message };
    }
}

// ============================================
// FUNCIÓN: OBTENER DATOS OFFLINE
// ============================================

/**
 * Obtener operadores desde IndexedDB
 */
async function obtenerOperadoresOffline() {
    try {
        const operadores = await window.IndexedDBManager.obtenerTodos('usuarios');
        return operadores.filter(u => u.rol === 'operador' && u.estado == 1);
    } catch (error) {
        console.error('[Offline] Error al obtener operadores:', error);
        return [];
    }
}

/**
 * Obtener equipos desde IndexedDB
 */
async function obtenerEquiposOffline(categoria = null) {
    try {
        let equipos = await window.IndexedDBManager.obtenerTodos('equipos');
        equipos = equipos.filter(e => e.estado == 1);
        
        if (categoria) {
            equipos = equipos.filter(e => e.categoria === categoria);
        }
        
        return equipos;
    } catch (error) {
        console.error('[Offline] Error al obtener equipos:', error);
        return [];
    }
}

/**
 * Obtener fases de costo desde IndexedDB
 */
async function obtenerFasesOffline() {
    try {
        const fases = await window.IndexedDBManager.obtenerTodos('fases_costo');
        return fases.filter(f => f.estado == 1);
    } catch (error) {
        console.error('[Offline] Error al obtener fases:', error);
        return [];
    }
}

/**
 * Obtener tipos de trabajo desde IndexedDB
 */
async function obtenerTiposTrabajoOffline() {
    try {
        const tipos = await window.IndexedDBManager.obtenerTodos('tipos_trabajo');
        return tipos.filter(t => t.estado == 1);
    } catch (error) {
        console.error('[Offline] Error al obtener tipos de trabajo:', error);
        return [];
    }
}

// ============================================
// FUNCIÓN: VERIFICAR SI HAY DATOS LOCALES
// ============================================
async function hayDatosLocales() {
    try {
        return await window.IndexedDBManager.hayDatosLocales();
    } catch (error) {
        return false;
    }
}

// ============================================
// FUNCIÓN: SINCRONIZACIÓN MANUAL
// ============================================
async function sincronizarManualmente() {
    if (!navigator.onLine) {
        mostrarNotificacion('Sin conexión a internet', 'error');
        return { success: false };
    }
    
    mostrarNotificacion('Sincronizando...', 'info');
    
    try {
        // Sincronizar catálogos
        await window.IndexedDBManager.sincronizarCatalogos();
        
        // Sincronizar reportes
        await window.IndexedDBManager.sincronizarReportesPendientes();
        
        const pendientes = await window.IndexedDBManager.contarReportesPendientes();
        
        if (pendientes === 0) {
            mostrarNotificacion('Sincronización completada', 'success');
        } else {
            mostrarNotificacion(`Quedan ${pendientes} reportes pendientes`, 'warning');
        }
        
        return { success: true };
        
    } catch (error) {
        console.error('[Offline] Error en sincronización manual:', error);
        mostrarNotificacion('Error al sincronizar', 'error');
        return { success: false };
    }
}

// ============================================
// FUNCIÓN AUXILIAR: MOSTRAR NOTIFICACIÓN
// ============================================
function mostrarNotificacion(mensaje, tipo = 'info') {
    // Si existe Toastr (AdminLTE)
    if (typeof toastr !== 'undefined') {
        toastr[tipo](mensaje);
    } 
    // Si existe SweetAlert2
    else if (typeof Swal !== 'undefined') {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: tipo,
            title: mensaje,
            showConfirmButton: false,
            timer: 3000
        });
    }
    // Fallback: consola
    else {
        console.log(`[${tipo.toUpperCase()}] ${mensaje}`);
    }
}

// ============================================
// EXPORTAR FUNCIONES GLOBALMENTE
// ============================================
window.OperaSysOffline = {
    // Guardar offline
    guardarReporteOffline,
    
    // Obtener datos offline
    obtenerOperadoresOffline,
    obtenerEquiposOffline,
    obtenerFasesOffline,
    obtenerTiposTrabajoOffline,
    
    // Verificaciones
    hayDatosLocales,
    
    // Sincronización
    sincronizarManualmente
};

console.log('✓ Módulo offline cargado');