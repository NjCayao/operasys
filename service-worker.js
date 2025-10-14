/**
 * OperaSys - Service Worker
 * Archivo: service-worker.js
 * Descripción: Gestiona caché y funcionamiento offline
 */

const CACHE_NAME = "operasys-v1.1";
const CACHE_URLS = [
  "/operasys/",
  "/operasys/index.php",
  "/operasys/manifest.json",

  // Módulos de autenticación
  "/operasys/modules/auth/login.php",
  "/operasys/modules/auth/register.php",

  // Módulos de usuarios
  "/operasys/modules/usuarios/firma.php",

  // Módulos de equipos
  "/operasys/modules/equipos/listar.php",
  "/operasys/modules/equipos/agregar.php",

  // Módulos de reportes
  "/operasys/modules/reportes/crear.php",
  "/operasys/modules/reportes/listar.php",

  // CSS
  "/operasys/assets/css/custom.css",

  // JavaScript
  "/operasys/assets/js/app.js",
  "/operasys/assets/js/registro.js",
  "/operasys/assets/js/firma.js",
  "/operasys/assets/js/equipos.js",
  "/operasys/assets/js/reportes.js",
  "/operasys/assets/js/offline.js",

  // AdminLTE (local)
  "/operasys/vendor/adminlte/dist/css/adminlte.min.css",
  "/operasys/vendor/adminlte/dist/js/adminlte.min.js",
  "/operasys/vendor/adminlte/plugins/jquery/jquery.min.js",
  "/operasys/vendor/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js",
  "/operasys/vendor/adminlte/plugins/fontawesome-free/css/all.min.css",
  "/operasys/vendor/adminlte/plugins/datatables/jquery.dataTables.min.js",
  "/operasys/vendor/adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js",
  "/operasys/vendor/adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css",
  "/operasys/vendor/adminlte/plugins/sweetalert2/sweetalert2.min.js",
  "/operasys/vendor/adminlte/plugins/moment/moment.min.js",
];

// ============================================
// INSTALACIÓN DEL SERVICE WORKER
// ============================================
self.addEventListener("install", function (event) {
  console.log("[Service Worker] Instalando...");

  event.waitUntil(
    caches
      .open(CACHE_NAME)
      .then(function (cache) {
        console.log("[Service Worker] Cacheando archivos");
        // Intentar cachear, pero continuar si falla alguno
        return Promise.allSettled(
          CACHE_URLS.map((url) =>
            cache
              .add(url)
              .catch((err) =>
                console.warn("[Service Worker] No se pudo cachear:", url)
              )
          )
        );
      })
      .then(function () {
        console.log("[Service Worker] ✓ Instalado correctamente");
        return self.skipWaiting();
      })
      .catch(function (error) {
        console.error("[Service Worker] ✗ Error al cachear:", error);
      })
  );
});

// ============================================
// ACTIVACIÓN DEL SERVICE WORKER
// ============================================
self.addEventListener("activate", function (event) {
  console.log("[Service Worker] Activando...");

  event.waitUntil(
    caches
      .keys()
      .then(function (cacheNames) {
        return Promise.all(
          cacheNames.map(function (cacheName) {
            if (cacheName !== CACHE_NAME) {
              console.log(
                "[Service Worker] Eliminando caché antigua:",
                cacheName
              );
              return caches.delete(cacheName);
            }
          })
        );
      })
      .then(function () {
        console.log("[Service Worker] ✓ Activado correctamente");
        return self.clients.claim();
      })
  );
});

// ============================================
// INTERCEPCIÓN DE PETICIONES (FETCH)
// ============================================
self.addEventListener("fetch", function (event) {
  const url = new URL(event.request.url);

  // No cachear peticiones a APIs (se manejan con IndexedDB)
  if (url.pathname.includes("/api/")) {
    console.log("[Service Worker] Ignorando API:", url.pathname);
    return;
  }

  // No cachear logout (debe ejecutarse siempre)
  if (url.pathname.includes("/logout.php")) {
    console.log("[Service Worker] Ignorando logout");
    return;
  }

  // Estrategia: Network First para páginas PHP (para que siempre muestre contenido actualizado)
  if (url.pathname.endsWith(".php")) {
    event.respondWith(
      fetch(event.request)
        .then(function (response) {
          // Si la respuesta es buena, actualizar caché
          if (response && response.status === 200) {
            const responseToCache = response.clone();
            caches.open(CACHE_NAME).then(function (cache) {
              if (event.request.method === "GET") {
                cache.put(event.request, responseToCache);
              }
            });
          }
          return response;
        })
        .catch(function (error) {
          console.log(
            "[Service Worker] Error de red, usando caché:",
            url.pathname
          );
          // Si falla la red, usar caché
          return caches.match(event.request);
        })
    );
    return;
  }

  // Estrategia: Cache First para recursos estáticos (CSS, JS, imágenes)
  event.respondWith(
    caches.match(event.request).then(function (response) {
      if (response) {
        console.log("[Service Worker] Sirviendo desde caché:", url.pathname);
        return response;
      }

      console.log("[Service Worker] Solicitando a la red:", url.pathname);
      return fetch(event.request)
        .then(function (response) {
          // Guardar en caché si la respuesta es válida
          if (response && response.status === 200) {
            const responseToCache = response.clone();
            caches.open(CACHE_NAME).then(function (cache) {
              if (event.request.method === "GET") {
                cache.put(event.request, responseToCache);
              }
            });
          }
          return response;
        })
        .catch(function (error) {
          console.error("[Service Worker] Error de red:", error);
        });
    })
  );
});

// ============================================
// SINCRONIZACIÓN EN SEGUNDO PLANO
// ============================================
self.addEventListener("sync", function (event) {
  console.log("[Service Worker] Sincronización en segundo plano:", event.tag);

  if (event.tag === "sync-reportes") {
    event.waitUntil(sincronizarReportes());
  }
});

// Función para sincronizar reportes pendientes
async function sincronizarReportes() {
  try {
    console.log("[Service Worker] Sincronizando reportes pendientes...");

    // Abrir IndexedDB
    const db = await abrirDB();
    const tx = db.transaction("reportes_pendientes", "readonly");
    const store = tx.objectStore("reportes_pendientes");
    const reportes = await getAllFromStore(store);

    console.log("[Service Worker] Reportes pendientes:", reportes.length);

    // Enviar cada reporte al servidor
    for (const reporte of reportes) {
      try {
        const response = await fetch("/operasys/api/reportes.php", {
          method: "POST",
          body: reporte.formData,
        });

        const data = await response.json();

        if (data.success) {
          // Eliminar de IndexedDB
          const txDelete = db.transaction("reportes_pendientes", "readwrite");
          const storeDelete = txDelete.objectStore("reportes_pendientes");
          await storeDelete.delete(reporte.id);

          console.log("[Service Worker] ✓ Reporte sincronizado:", reporte.id);
        }
      } catch (error) {
        console.error(
          "[Service Worker] ✗ Error al sincronizar reporte:",
          error
        );
      }
    }

    console.log("[Service Worker] ✓ Sincronización completada");
  } catch (error) {
    console.error("[Service Worker] Error en sincronización:", error);
  }
}

// Función auxiliar para abrir IndexedDB
function abrirDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open("OperaSysDB", 1);

    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);

    request.onupgradeneeded = function (event) {
      const db = event.target.result;

      if (!db.objectStoreNames.contains("reportes_pendientes")) {
        db.createObjectStore("reportes_pendientes", {
          keyPath: "id",
          autoIncrement: true,
        });
      }
    };
  });
}

// Función auxiliar para obtener todos los registros
function getAllFromStore(store) {
  return new Promise((resolve, reject) => {
    const request = store.getAll();
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

console.log("[Service Worker] Script cargado correctamente");
