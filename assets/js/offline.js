/**
 * OperaSys - Gestión Offline
 * Archivo: assets/js/offline.js
 * Descripción: Sincronización offline con IndexedDB
 */

// ============================================
// REGISTRO DEL SERVICE WORKER
// ============================================
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/operasys/service-worker.js')
            .then(function(registration) {
                console.log('✓ Service Worker registrado:', registration.scope);
            })
            .catch(function(error) {
                console.error('✗ Error al registrar Service Worker:', error);
            });
    });
}

// ============================================
// INDEXEDDB - CONFIGURACIÓN
// ============================================
const DB_NAME = 'OperaSysDB';
const DB_VERSION = 1;
let db;

// Abrir/crear base de datos
function abrirDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);
        
        request.onerror = () => {
            console.error('✗ Error al abrir IndexedDB');
            reject(request.error);
        };
        
        request.onsuccess = () => {
            db = request.result;
            console.log('✓ IndexedDB abierta correctamente');
            resolve(db);
        };
        
        request.onupgradeneeded = function(event) {
            const db = event.target.result;
            
            // Crear object stores
            if (!db.objectStoreNames.contains('reportes_pendientes')) {
                const reportesStore = db.createObjectStore('reportes_pendientes', { 
                    keyPath: 'id', 
                    autoIncrement: true 
                });
                reportesStore.createIndex('fecha', 'fecha', { unique: false });
                reportesStore.createIndex('estado', 'estado', { unique: false });
                console.log('✓ Object Store "reportes_pendientes" creado');
            }
            
            if (!db.objectStoreNames.contains('equipos_cache')) {
                const equiposStore = db.createObjectStore('equipos_cache', { 
                    keyPath: 'id' 
                });
                equiposStore.createIndex('categoria', 'categoria', { unique: false });
                console.log('✓ Object Store "equipos_cache" creado');
            }
            
            if (!db.objectStoreNames.contains('usuarios_cache')) {
                db.createObjectStore('usuarios_cache', { 
                    keyPath: 'id' 
                });
                console.log('✓ Object Store "usuarios_cache" creado');
            }
        };
    });
}

// Inicializar DB al cargar la página
abrirDB();

// ============================================
// GUARDAR REPORTE OFFLINE
// ============================================
async function guardarReporteOffline(formData) {
    try {
        if (!db) await abrirDB();
        
        const reporte = {
            usuario_id: formData.get('usuario_id'),
            equipo_id: formData.get('equipo_id'),
            fecha: formData.get('fecha'),
            hora_inicio: formData.get('hora_inicio'),
            hora_fin: formData.get('hora_fin'),
            actividad: formData.get('actividad'),
            observaciones: formData.get('observaciones'),
            ubicacion: formData.get('ubicacion'),
            estado: 'pendiente',
            fecha_creacion: new Date().toISOString(),
            formData: formData // Guardar FormData completo para enviar después
        };
        
        const tx = db.transaction('reportes_pendientes', 'readwrite');
        const store = tx.objectStore('reportes_pendientes');
        const request = store.add(reporte);
        
        return new Promise((resolve, reject) => {
            request.onsuccess = () => {
                console.log('✓ Reporte guardado offline:', request.result);
                resolve(request.result);
            };
            request.onerror = () => {
                console.error('✗ Error al guardar reporte offline');
                reject(request.error);
            };
        });
        
    } catch (error) {
        console.error('Error en guardarReporteOffline:', error);
        throw error;
    }
}

// ============================================
// OBTENER REPORTES PENDIENTES
// ============================================
async function obtenerReportesPendientes() {
    try {
        if (!db) await abrirDB();
        
        const tx = db.transaction('reportes_pendientes', 'readonly');
        const store = tx.objectStore('reportes_pendientes');
        const request = store.getAll();
        
        return new Promise((resolve, reject) => {
            request.onsuccess = () => {
                console.log('✓ Reportes pendientes obtenidos:', request.result.length);
                resolve(request.result);
            };
            request.onerror = () => {
                console.error('✗ Error al obtener reportes pendientes');
                reject(request.error);
            };
        });
        
    } catch (error) {
        console.error('Error en obtenerReportesPendientes:', error);
        return [];
    }
}

// ============================================
// SINCRONIZAR REPORTES PENDIENTES
// ============================================
async function sincronizarReportes() {
    
    if (!navigator.onLine) {
        console.log('⚠ Sin conexión. Sincronización pospuesta.');
        return { success: false, message: 'Sin conexión a internet' };
    }
    
    try {
        const reportesPendientes = await obtenerReportesPendientes();
        
        if (reportesPendientes.length === 0) {
            console.log('✓ No hay reportes pendientes de sincronizar');
            return { success: true, sincronizados: 0 };
        }
        
        console.log(`🔄 Sincronizando ${reportesPendientes.length} reportes...`);
        
        let sincronizados = 0;
        let errores = 0;
        
        for (const reporte of reportesPendientes) {
            try {
                // Crear FormData
                const formData = new FormData();
                formData.append('action', 'crear');
                formData.append('usuario_id', reporte.usuario_id);
                formData.append('equipo_id', reporte.equipo_id);
                formData.append('fecha', reporte.fecha);
                formData.append('hora_inicio', reporte.hora_inicio);
                if (reporte.hora_fin) formData.append('hora_fin', reporte.hora_fin);
                formData.append('actividad', reporte.actividad);
                if (reporte.observaciones) formData.append('observaciones', reporte.observaciones);
                if (reporte.ubicacion) formData.append('ubicacion', reporte.ubicacion);
                
                // Enviar al servidor
                const response = await fetch('/operasys/api/reportes.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Eliminar de IndexedDB
                    await eliminarReportePendiente(reporte.id);
                    sincronizados++;
                    console.log(`✓ Reporte ${reporte.id} sincronizado`);
                } else {
                    console.error(`✗ Error al sincronizar reporte ${reporte.id}:`, data.message);
                    errores++;
                }
                
            } catch (error) {
                console.error(`✗ Error al sincronizar reporte ${reporte.id}:`, error);
                errores++;
            }
        }
        
        console.log(`✓ Sincronización completada: ${sincronizados} exitosos, ${errores} errores`);
        
        return {
            success: true,
            sincronizados: sincronizados,
            errores: errores
        };
        
    } catch (error) {
        console.error('Error en sincronizarReportes:', error);
        return { success: false, message: error.message };
    }
}

// ============================================
// ELIMINAR REPORTE PENDIENTE
// ============================================
async function eliminarReportePendiente(id) {
    try {
        if (!db) await abrirDB();
        
        const tx = db.transaction('reportes_pendientes', 'readwrite');
        const store = tx.objectStore('reportes_pendientes');
        const request = store.delete(id);
        
        return new Promise((resolve, reject) => {
            request.onsuccess = () => {
                console.log('✓ Reporte pendiente eliminado:', id);
                resolve();
            };
            request.onerror = () => {
                console.error('✗ Error al eliminar reporte pendiente');
                reject(request.error);
            };
        });
        
    } catch (error) {
        console.error('Error en eliminarReportePendiente:', error);
    }
}

// ============================================
// CACHEAR EQUIPOS (Para uso offline)
// ============================================
async function cachearEquipos() {
    try {
        const response = await fetch('/operasys/api/equipos.php?action=listar');
        const data = await response.json();
        
        if (data.success && data.data) {
            if (!db) await abrirDB();
            
            const tx = db.transaction('equipos_cache', 'readwrite');
            const store = tx.objectStore('equipos_cache');
            
            // Limpiar caché anterior
            store.clear();
            
            // Guardar equipos
            data.data.forEach(equipo => {
                store.add({
                    id: equipo[0],
                    categoria: equipo[1],
                    codigo: equipo[2],
                    descripcion: equipo[3]
                });
            });
            
            console.log('✓ Equipos cacheados correctamente');
        }
        
    } catch (error) {
        console.error('Error al cachear equipos:', error);
    }
}

// ============================================
// OBTENER EQUIPOS DESDE CACHÉ
// ============================================
async function obtenerEquiposOffline(categoria) {
    try {
        if (!db) await abrirDB();
        
        const tx = db.transaction('equipos_cache', 'readonly');
        const store = tx.objectStore('equipos_cache');
        const index = store.index('categoria');
        const request = index.getAll(categoria);
        
        return new Promise((resolve, reject) => {
            request.onsuccess = () => {
                console.log('✓ Equipos offline obtenidos:', request.result.length);
                resolve(request.result);
            };
            request.onerror = () => {
                console.error('✗ Error al obtener equipos offline');
                reject(request.error);
            };
        });
        
    } catch (error) {
        console.error('Error en obtenerEquiposOffline:', error);
        return [];
    }
}

// ============================================
// DETECTAR ESTADO DE CONEXIÓN
// ============================================
window.addEventListener('online', function() {
    console.log('✓ Conexión restaurada');
    mostrarNotificacion('Conexión restaurada', 'success');
    
    // Intentar sincronizar automáticamente
    sincronizarReportes().then(result => {
        if (result.sincronizados > 0) {
            mostrarNotificacion(`${result.sincronizados} reportes sincronizados`, 'success');
            
            // Recargar página si estamos en listado de reportes
            if (window.location.pathname.includes('reportes/listar.php')) {
                setTimeout(() => location.reload(), 2000);
            }
        }
    });
    
    // Actualizar caché de equipos
    cachearEquipos();
});

window.addEventListener('offline', function() {
    console.log('⚠ Sin conexión');
    mostrarNotificacion('Sin conexión. Los datos se guardarán localmente.', 'warning');
});

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
// INICIALIZACIÓN
// ============================================
window.addEventListener('load', function() {
    // Cachear equipos al cargar la app
    if (navigator.onLine) {
        cachearEquipos();
    }
    
    // Verificar reportes pendientes
    obtenerReportesPendientes().then(reportes => {
        if (reportes.length > 0) {
            console.log(`⚠ Tienes ${reportes.length} reportes pendientes de sincronizar`);
        }
    });
});

// Exponer funciones globalmente para uso en otros scripts
window.OperaSysOffline = {
    guardarReporteOffline,
    obtenerReportesPendientes,
    sincronizarReportes,
    obtenerEquiposOffline,
    cachearEquipos
};

console.log('✓ Módulo offline cargado');
