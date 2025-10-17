/**
 * OperaSys - IndexedDB Manager
 * Archivo: assets/js/indexeddb.js
 * Versión: 3.0 - Actualizado para HT/HP (SIN partidas)
 * Descripción: Gestiona almacenamiento local para funcionamiento offline
 */

const DB_NAME = 'OperaSysDB';
const DB_VERSION = 3; // Incrementado para V3.0

let db = null;

/**
 * Inicializar y abrir la base de datos IndexedDB
 */
function inicializarDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onerror = () => {
            console.error('[IndexedDB] Error al abrir la base de datos');
            reject(request.error);
        };

        request.onsuccess = () => {
            db = request.result;
            console.log('[IndexedDB] ✓ Base de datos abierta correctamente');
            resolve(db);
        };

        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            console.log('[IndexedDB] Actualizando estructura a V3.0...');

            // Store: Usuarios (Operadores)
            if (!db.objectStoreNames.contains('usuarios')) {
                const usuariosStore = db.createObjectStore('usuarios', { keyPath: 'id' });
                usuariosStore.createIndex('rol', 'rol', { unique: false });
                usuariosStore.createIndex('estado', 'estado', { unique: false });
                console.log('[IndexedDB] ✓ Store "usuarios" creado');
            }

            // Store: Equipos
            if (!db.objectStoreNames.contains('equipos')) {
                const equiposStore = db.createObjectStore('equipos', { keyPath: 'id' });
                equiposStore.createIndex('categoria', 'categoria', { unique: false });
                equiposStore.createIndex('estado', 'estado', { unique: false });
                console.log('[IndexedDB] ✓ Store "equipos" creado');
            }

            // ❌ V3.0: Eliminar stores antiguos
            if (db.objectStoreNames.contains('fases_costo')) {
                db.deleteObjectStore('fases_costo');
                console.log('[IndexedDB] ✓ Store antiguo "fases_costo" eliminado');
            }

            if (db.objectStoreNames.contains('partidas')) {
                db.deleteObjectStore('partidas');
                console.log('[IndexedDB] ✓ Store antiguo "partidas" eliminado');
            }

            if (db.objectStoreNames.contains('tipos_trabajo')) {
                db.deleteObjectStore('tipos_trabajo');
                console.log('[IndexedDB] ✓ Store antiguo "tipos_trabajo" eliminado');
            }

            // 🆕 V3.0: Actividades HT
            if (!db.objectStoreNames.contains('actividades_ht')) {
                const actividadesStore = db.createObjectStore('actividades_ht', { keyPath: 'id' });
                actividadesStore.createIndex('estado', 'estado', { unique: false });
                actividadesStore.createIndex('es_frecuente', 'es_frecuente', { unique: false });
                console.log('[IndexedDB] ✓ Store "actividades_ht" creado');
            }

            // 🆕 V3.0: Motivos HP
            if (!db.objectStoreNames.contains('motivos_hp')) {
                const motivosStore = db.createObjectStore('motivos_hp', { keyPath: 'id' });
                motivosStore.createIndex('estado', 'estado', { unique: false });
                motivosStore.createIndex('es_frecuente', 'es_frecuente', { unique: false });
                motivosStore.createIndex('categoria_parada', 'categoria_parada', { unique: false });
                console.log('[IndexedDB] ✓ Store "motivos_hp" creado');
            }

            // Store: Reportes Pendientes (offline)
            if (!db.objectStoreNames.contains('reportes_pendientes')) {
                const reportesStore = db.createObjectStore('reportes_pendientes', { 
                    keyPath: 'id', 
                    autoIncrement: true 
                });
                reportesStore.createIndex('fecha_creacion', 'fecha_creacion', { unique: false });
                reportesStore.createIndex('sincronizado', 'sincronizado', { unique: false });
                console.log('[IndexedDB] ✓ Store "reportes_pendientes" creado');
            }

            // Store: Metadata (última sincronización)
            if (!db.objectStoreNames.contains('metadata')) {
                db.createObjectStore('metadata', { keyPath: 'clave' });
                console.log('[IndexedDB] ✓ Store "metadata" creado');
            }
        };
    });
}

// ============================================
// FUNCIONES GENÉRICAS
// ============================================

/**
 * Guardar datos en un store
 */
function guardarEnStore(storeName, data) {
    return new Promise((resolve, reject) => {
        if (!db) {
            reject(new Error('Base de datos no inicializada'));
            return;
        }

        const transaction = db.transaction([storeName], 'readwrite');
        const store = transaction.objectStore(storeName);
        const request = store.put(data);

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

/**
 * Obtener todos los datos de un store
 */
function obtenerTodosDeStore(storeName) {
    return new Promise((resolve, reject) => {
        if (!db) {
            reject(new Error('Base de datos no inicializada'));
            return;
        }

        const transaction = db.transaction([storeName], 'readonly');
        const store = transaction.objectStore(storeName);
        const request = store.getAll();

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

/**
 * Obtener un registro por ID
 */
function obtenerPorId(storeName, id) {
    return new Promise((resolve, reject) => {
        if (!db) {
            reject(new Error('Base de datos no inicializada'));
            return;
        }

        const transaction = db.transaction([storeName], 'readonly');
        const store = transaction.objectStore(storeName);
        const request = store.get(id);

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

/**
 * Eliminar un registro por ID
 */
function eliminarDeStore(storeName, id) {
    return new Promise((resolve, reject) => {
        if (!db) {
            reject(new Error('Base de datos no inicializada'));
            return;
        }

        const transaction = db.transaction([storeName], 'readwrite');
        const store = transaction.objectStore(storeName);
        const request = store.delete(id);

        request.onsuccess = () => resolve();
        request.onerror = () => reject(request.error);
    });
}

/**
 * Limpiar todos los datos de un store
 */
function limpiarStore(storeName) {
    return new Promise((resolve, reject) => {
        if (!db) {
            reject(new Error('Base de datos no inicializada'));
            return;
        }

        const transaction = db.transaction([storeName], 'readwrite');
        const store = transaction.objectStore(storeName);
        const request = store.clear();

        request.onsuccess = () => resolve();
        request.onerror = () => reject(request.error);
    });
}

// ============================================
// SINCRONIZACIÓN DE CATÁLOGOS V3.0
// ============================================

/**
 * Sincronizar todos los catálogos desde el servidor
 */
async function sincronizarCatalogos() {
    console.log('[IndexedDB] Iniciando sincronización de catálogos V3.0...');

    try {
        // Sincronizar usuarios (operadores)
        await sincronizarUsuarios();
        
        // Sincronizar equipos
        await sincronizarEquipos();
        
        // 🆕 V3.0: Sincronizar actividades HT
        await sincronizarActividadesHT();
        
        // 🆕 V3.0: Sincronizar motivos HP
        await sincronizarMotivosHP();

        // Guardar fecha de última sincronización
        await guardarEnStore('metadata', {
            clave: 'ultima_sincronizacion',
            valor: new Date().toISOString()
        });

        console.log('[IndexedDB] ✓ Sincronización V3.0 completada');
        return true;
    } catch (error) {
        console.error('[IndexedDB] Error en sincronización:', error);
        return false;
    }
}

/**
 * Sincronizar usuarios desde la API
 */
async function sincronizarUsuarios() {
    try {
        const response = await fetch('../../api/usuarios.php?action=listar');
        const data = await response.json();

        if (data.success) {
            await limpiarStore('usuarios');
            
            for (const usuario of data.data) {
                await guardarEnStore('usuarios', usuario);
            }
            
            console.log('[IndexedDB] ✓ Usuarios sincronizados:', data.data.length);
        }
    } catch (error) {
        console.error('[IndexedDB] Error al sincronizar usuarios:', error);
        throw error;
    }
}

/**
 * Sincronizar equipos desde la API
 */
async function sincronizarEquipos() {
    try {
        const response = await fetch('../../api/equipos.php?action=obtener_equipos_operador');
        const data = await response.json();

        if (data.success) {
            await limpiarStore('equipos');
            
            for (const equipo of data.equipos) {
                await guardarEnStore('equipos', equipo);
            }
            
            console.log('[IndexedDB] ✓ Equipos sincronizados:', data.equipos.length);
        }
    } catch (error) {
        console.error('[IndexedDB] Error al sincronizar equipos:', error);
        throw error;
    }
}

/**
 * 🆕 V3.0: Sincronizar actividades HT
 */
async function sincronizarActividadesHT() {
    try {
        const response = await fetch('../../api/actividades_ht.php?action=listar&para_select=1');
        const data = await response.json();

        if (data.success) {
            await limpiarStore('actividades_ht');
            
            const actividades = data.actividades || [];
            
            for (const actividad of actividades) {
                await guardarEnStore('actividades_ht', actividad);
            }
            
            console.log('[IndexedDB] ✓ Actividades HT sincronizadas:', actividades.length);
        }
    } catch (error) {
        console.error('[IndexedDB] Error al sincronizar actividades HT:', error);
        throw error;
    }
}

/**
 * 🆕 V3.0: Sincronizar motivos HP
 */
async function sincronizarMotivosHP() {
    try {
        const response = await fetch('../../api/motivos_hp.php?action=listar&para_select=1');
        const data = await response.json();

        if (data.success) {
            await limpiarStore('motivos_hp');
            
            const motivos = data.motivos || [];
            
            for (const motivo of motivos) {
                await guardarEnStore('motivos_hp', motivo);
            }
            
            console.log('[IndexedDB] ✓ Motivos HP sincronizados:', motivos.length);
        }
    } catch (error) {
        console.error('[IndexedDB] Error al sincronizar motivos HP:', error);
        throw error;
    }
}

// ============================================
// GESTIÓN DE REPORTES OFFLINE
// ============================================

/**
 * Guardar reporte pendiente (offline)
 */
async function guardarReportePendiente(reporteData) {
    try {
        const reporte = {
            ...reporteData,
            fecha_creacion: new Date().toISOString(),
            sincronizado: false
        };

        const id = await guardarEnStore('reportes_pendientes', reporte);
        console.log('[IndexedDB] ✓ Reporte guardado offline, ID:', id);
        
        // Intentar sincronizar inmediatamente si hay conexión
        if (navigator.onLine) {
            await sincronizarReportesPendientes();
        }
        
        return id;
    } catch (error) {
        console.error('[IndexedDB] Error al guardar reporte offline:', error);
        throw error;
    }
}

/**
 * Sincronizar reportes pendientes con el servidor
 */
async function sincronizarReportesPendientes() {
    try {
        const reportes = await obtenerTodosDeStore('reportes_pendientes');
        const pendientes = reportes.filter(r => !r.sincronizado);

        console.log('[IndexedDB] Reportes pendientes de sincronizar:', pendientes.length);

        for (const reporte of pendientes) {
            try {
                const formData = new FormData();
                formData.append('action', 'crear');
                
                // Agregar datos del reporte al FormData
                Object.keys(reporte).forEach(key => {
                    if (key !== 'id' && key !== 'fecha_creacion' && key !== 'sincronizado') {
                        formData.append(key, reporte[key]);
                    }
                });

                const response = await fetch('../../api/reportes.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // Eliminar de IndexedDB
                    await eliminarDeStore('reportes_pendientes', reporte.id);
                    console.log('[IndexedDB] ✓ Reporte sincronizado:', reporte.id);
                }
            } catch (error) {
                console.error('[IndexedDB] Error al sincronizar reporte:', reporte.id, error);
            }
        }

        return true;
    } catch (error) {
        console.error('[IndexedDB] Error en sincronización de reportes:', error);
        return false;
    }
}

/**
 * Obtener cantidad de reportes pendientes
 */
async function contarReportesPendientes() {
    try {
        const reportes = await obtenerTodosDeStore('reportes_pendientes');
        return reportes.filter(r => !r.sincronizado).length;
    } catch (error) {
        console.error('[IndexedDB] Error al contar reportes pendientes:', error);
        return 0;
    }
}

// ============================================
// VERIFICAR SI HAY DATOS LOCALES
// ============================================

/**
 * Verificar si hay datos en IndexedDB
 */
async function hayDatosLocales() {
    try {
        const usuarios = await obtenerTodosDeStore('usuarios');
        const equipos = await obtenerTodosDeStore('equipos');
        
        return usuarios.length > 0 && equipos.length > 0;
    } catch (error) {
        return false;
    }
}

/**
 * Obtener fecha de última sincronización
 */
async function obtenerUltimaSincronizacion() {
    try {
        const metadata = await obtenerPorId('metadata', 'ultima_sincronizacion');
        return metadata ? new Date(metadata.valor) : null;
    } catch (error) {
        return null;
    }
}

// ============================================
// EXPORTAR FUNCIONES
// ============================================

// Hacer funciones globales
window.IndexedDBManager = {
    inicializar: inicializarDB,
    sincronizarCatalogos,
    sincronizarReportesPendientes,
    guardarReportePendiente,
    contarReportesPendientes,
    hayDatosLocales,
    obtenerUltimaSincronizacion,
    // Funciones genéricas
    obtenerTodos: obtenerTodosDeStore,
    obtenerPorId,
    guardar: guardarEnStore,
    eliminar: eliminarDeStore
};

console.log('[IndexedDB] ✓ Módulo V3.0 cargado (SIN partidas)')