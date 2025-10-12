/**
 * OperaSys - Service Worker
 * Archivo: service-worker.js
 * Descripción: Gestiona caché y funcionamiento offline
 */

const CACHE_NAME = 'operasys-v1.0';
const CACHE_URLS = [
    '/operasys/',
    '/operasys/index.php',
    '/operasys/manifest.json',
    
    // Módulos de autenticación
    '/operasys/modules/auth/login.php',
    '/operasys/modules/auth/register.php',
    '/operasys/modules/auth/logout.php',
    
    // Módulos de usuarios
    '/operasys/modules/usuarios/firma.php',
    
    // Módulos de equipos
    '/operasys/modules/equipos/listar.php',
    '/operasys/modules/equipos/agregar.php',
    
    // Módulos de reportes
    '/operasys/modules/reportes/crear.php',
    '/operasys/modules/reportes/listar.php',
    
    // CSS
    '/operasys/assets/css/custom.css',
    
    // JavaScript
    '/operasys/assets/js/app.js',
    '/operasys/assets/js/registro.js',
    '/operasys/assets/js/firma.js',
    '/operasys/assets/js/equipos.js',
    '/operasys/assets/js/reportes.js',
    '/operasys/assets/js/offline.js',
    
    // AdminLTE (local)
    '/operasys/vendor/adminlte/dist/css/adminlte.min.css',
    '/operasys/vendor/adminlte/dist/js/adminlte.min.js',
    '/operasys/vendor/adminlte/plugins/jquery/jquery.min.js',
    '/operasys/vendor/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js',
    '/operasys/vendor/adminlte/plugins/fontawesome-free/css/all.min.css',
    '/operasys/vendor/adminlte/plugins/datatables/jquery.dataTables.min.js',
    '/operasys/vendor/adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
    '/operasys/vendor/adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css',
    '/operasys/vendor/adminlte/plugins/sweetalert2/sweetalert2.min.js',
    '/operasys/vendor/adminlte/plugins/moment/moment.min.js'
];

// ============================================
// INSTALACIÓN DEL SERVICE WORKER
// ============================================
self.addEventListener('install', function(event) {
    console.log('[Service Worker] Instalando...');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                console.log('[Service Worker] Cacheando archivos');
                return cache.addAll(CACHE_URLS);
            })
            .then(function() {
                console.log('[Service Worker] ✓ Instalado correctamente');
                return self.skipWaiting();
            })
            .catch(function(error) {
                console.error('[Service Worker] ✗ Error al cachear:', error);
            })
    );
});

// ============================================
// ACTIVACIÓN DEL SERVICE WORKER
// ============================================
self.addEventListener('activate', function(event) {
    console.log('[Service Worker] Activando...');
    
    event.waitUntil(
        caches.keys()
            .then(function(cacheNames) {
                return Promise.all(
                    cacheNames.map(function(cacheName) {
                        if (cacheName !== CACHE_NAME) {
                            console.log('[Service Worker] Eliminando caché antigua:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(function() {
                console.log('[Service Worker] ✓ Activado correctamente');
                return self.clients.claim();
            })
    );
});

// ============================================
// INTERCEPCIÓN DE PETICIONES (FETCH)
// ============================================
self.addEventListener('fetch', function(event) {
    const url = new URL(event.request.url);
    
    // No cachear peticiones a APIs (se manejan con IndexedDB)
    if (url.pathname.includes('/api/')) {
        return;
    }
    
    // Estrategia: Cache First (Caché primero, luego red)
    event.respondWith(
        caches.match(event.request)
            .then(function(response) {
                if (response) {
                    // Encontrado en caché
                    console.log('[Service Worker] Sirviendo desde caché:', event.request.url);
                    return response;
                }
                
                // No está en caché, solicitar a la red
                console.log('[Service Worker] Solicitando a la red:', event.request.url);
                return fetch(event.request)
                    .then(function(response) {
                        // Validar respuesta
                        if (!response || response.status !== 200 || response.type !== 'basic') {
                            return response;
                        }
                        
                        // Clonar respuesta
                        const responseToCache = response.clone();
                        
                        // Guardar en caché para futuras peticiones
                        caches.open(CACHE_NAME)
                            .then(function(cache) {
                                cache.put(event.request, responseToCache);
                            });
                        
                        return response;
                    })
                    .catch(function(error) {
                        console.error('[Service Worker] Error de red:', error);
                        
                        // Si es una página HTML, devolver página offline
                        if (event.request.headers.get('accept').includes('text/html')) {
                            return caches.match('/operasys/offline.html');
                        }
                    });
            })
    );
});

// ============================================
// SINCRONIZACIÓN EN SEGUNDO PLANO
// ============================================
self.addEventListener('sync', function(event) {
    console.log('[Service Worker] Sincronización en segundo plano:', event.tag);
    
    if (event.tag === 'sync-reportes') {
        event.waitUntil(sincronizarReportes());
    }
});

// Función para sincronizar reportes pendientes
async function sincronizarReportes() {
    try {
        console.log('[Service Worker] Sincronizando reportes pendientes...');
        
        // Abrir IndexedDB
        const db = await abrirDB();
        const tx = db.transaction('reportes_pendientes', 'readonly');
        const store = tx.objectStore('reportes_pendientes');
        const reportes = await store.getAll();
        
        console.log('[Service Worker] Reportes pendientes:', reportes.length);
        
        // Enviar cada reporte al servidor
        for (const reporte of reportes) {
            try {
                const response = await fetch('/operasys/api/reportes.php', {
                    method: 'POST',
                    body: reporte.formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Eliminar de IndexedDB
                    const txDelete = db.transaction('reportes_pendientes', 'readwrite');
                    const storeDelete = txDelete.objectStore('reportes_pendientes');
                    await storeDelete.delete(reporte.id);
                    
                    console.log('[Service Worker] ✓ Reporte sincronizado:', reporte.id);
                }
            } catch (error) {
                console.error('[Service Worker] ✗ Error al sincronizar reporte:', error);
            }
        }
        
        console.log('[Service Worker] ✓ Sincronización completada');
        
    } catch (error) {
        console.error('[Service Worker] Error en sincronización:', error);
    }
}

// Función auxiliar para abrir IndexedDB
function abrirDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('OperaSysDB', 1);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);
        
        request.onupgradeneeded = function(event) {
            const db = event.target.result;
            
            if (!db.objectStoreNames.contains('reportes_pendientes')) {
                db.createObjectStore('reportes_pendientes', { keyPath: 'id', autoIncrement: true });
            }
        };
    });
}

// ============================================
// NOTIFICACIONES PUSH (OPCIONAL)
// ============================================
self.addEventListener('push', function(event) {
    console.log('[Service Worker] Notificación push recibida');
    
    const options = {
        body: event.data ? event.data.text() : 'Nueva notificación de OperaSys',
        icon: '/operasys/assets/img/icon-192x192.png',
        badge: '/operasys/assets/img/icon-72x72.png',
        vibrate: [200, 100, 200],
        tag: 'operasys-notification'
    };
    
    event.waitUntil(
        self.registration.showNotification('OperaSys', options)
    );
});

// ============================================
// MANEJO DE CLICKS EN NOTIFICACIONES
// ============================================
self.addEventListener('notificationclick', function(event) {
    console.log('[Service Worker] Click en notificación');
    
    event.notification.close();
    
    event.waitUntil(
        clients.openWindow('/operasys/')
    );
});

console.log('[Service Worker] Script cargado');
