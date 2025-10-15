/**
 * OperaSys - IndexedDB Manager
 * Archivo: assets/js/indexeddb.js
 * Descripción: Gestiona almacenamiento local para funcionamiento offline
 */

const DB_NAME = 'OperaSysDB';
const DB_VERSION = 2;

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
            console.log('[IndexedDB] Actualizando estructura...');

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

            // Store: Fases de Costo
            if (!db.objectStoreNames.contains('fases_costo')) {
                const fasesStore = db.createObjectStore('fases_costo', { keyPath: 'id' });
                fasesStore.createIndex('estado', 'estado', { unique: false });
                console.log('[IndexedDB] ✓ Store "fases_costo" creado');
            }

            // Store: Tipos de Trabajo
            if (!db.objectStoreNames.contains('tipos_trabajo')) {
                const tiposStore = db.createObjectStore('tipos_trabajo', { keyPath: 'id' });
                tiposStore.createIndex('estado', 'estado', { unique: false });
                console.log('[IndexedDB] ✓ Store "tipos_trabajo" creado');
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
// SINCRONIZACIÓN DE CATÁLOGOS
// ============================================

/**
 * Sincronizar todos los catálogos desde el servidor
 */
async function sincronizarCatalogos() {
    console.log('[IndexedDB] Iniciando sincronización de catálogos...');

    try {
        // Sincronizar usuarios (operadores)
        await sincronizarUsuarios();
        
        // Sincronizar equipos
        await sincronizarEquipos();
        
        // Sincronizar fases de costo
        await sincronizarFasesCosto();
        
        // Sincronizar tipos de trabajo
        await sincronizarTiposTrabajo();

        // Guardar fecha de última sincronización
        await guardarEnStore('metadata', {
            clave: 'ultima_sincronizacion',
            valor: new Date().toISOString()
        });

        console.log('[IndexedDB] ✓ Sincronización completada');
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
 * Sincronizar fases de costo desde la API
 */
async function sincronizarFasesCosto() {
    try {
        const response = await fetch('../../api/fases_costo.php?action=listar&para_select=1');
        const data = await response.json();

        if (data.success) {
            await limpiarStore('fases_costo');
            
            const fases = data.fases || data.data || [];
            
            for (const fase of fases) {
                await guardarEnStore('fases_costo', fase);
            }
            
            console.log('[IndexedDB] ✓ Fases de costo sincronizadas:', fases.length);
        }
    } catch (error) {
        console.error('[IndexedDB] Error al sincronizar fases:', error);
        throw error;
    }
}

/**
 * Sincronizar tipos de trabajo desde la API
 */
async function sincronizarTiposTrabajo() {
    try {
        const response = await fetch('../../api/tipos_trabajo.php?action=listar');
        const data = await response.json();

        if (data.success) {
            await limpiarStore('tipos_trabajo');
            
            // La API devuelve array de arrays para DataTables, extraer datos
            for (const row of data.data) {
                const tipo = {
                    id: row[0],
                    nombre: row[1],
                    descripcion: row[2],
                    estado: row[3].includes('Activo') ? 1 : 0
                };
                await guardarEnStore('tipos_trabajo', tipo);
            }
            
            console.log('[IndexedDB] ✓ Tipos de trabajo sincronizados:', data.data.length);
        }
    } catch (error) {
        console.error('[IndexedDB] Error al sincronizar tipos de trabajo:', error);
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

console.log('[IndexedDB] ✓ Módulo cargado');