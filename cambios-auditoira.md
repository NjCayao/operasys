# 📋 CHANGELOG - FASE 1: AUTENTICACIÓN
✅ Cambios Realizados:
Arquitectura:

✅ Creada carpeta layouts/ para componentes reutilizables
✅ Implementado sistema de layouts (header/footer separados)

Archivos Creados:

✅ layouts/auth_header.php - Header para páginas de autenticación
✅ layouts/auth_footer.php - Footer para páginas de autenticación
✅ assets/js/login.js - Lógica del formulario de login

Archivos Corregidos:

✅ modules/auth/login.php - Migrado a layouts, CDN → LOCAL
✅ modules/auth/register.php - Migrado a layouts, CDN → LOCAL
✅ api/usuarios.php - Nombres capitalizados automáticamente (ucwords)

Mejoras:

✅ Eliminados todos los CDN → 100% recursos locales
✅ Código más mantenible y reutilizable
✅ Validación DNI solo números en tiempo real
✅ Feedback visual en botones (loading, success, error)

Estado:

✅ Login funcional
✅ Registro funcional
✅ Logout funcional
✅ Sistema mantiene funcionalidad offline

# 📋 CHANGELOG - FASE 2: GESTIÓN DE USUARIOS Y FIRMA
✅ Cambios Realizados:
Sistema de Usuarios:

✅ Eliminado auto-registro público (solo admin crea usuarios)
✅ Sistema de cargos dinámico según rol:

Operador → "Operador de [Categoría de Equipo]"
Supervisor → "Supervisor"
Admin → "Administrador"


✅ Menú desplegable en sidebar para filtrar usuarios por rol
✅ Filtros persistentes al editar/guardar usuarios

Archivos Eliminados:

❌ modules/auth/register.php
❌ assets/js/registro.js

Archivos Creados:

✅ modules/usuarios/perfil.php - Perfil de usuario con firma
✅ assets/js/perfil.js - Cambio de contraseña
✅ assets/js/editar_usuario.js - Edición de usuarios

Archivos Corregidos:

✅ modules/auth/login.php - Sin enlace de registro
✅ modules/usuarios/listar.php - Modal con categorías dinámicas, filtros por rol
✅ modules/usuarios/editar.php - Campos dinámicos según rol
✅ modules/usuarios/firma.php - Sistema de captura de firma
✅ api/usuarios.php - Construcción automática de cargos, filtros por rol
✅ api/auth.php - Redirección a firma si no tiene
✅ layouts/sidebar.php - Menú desplegable de usuarios
✅ assets/js/usuarios.js - Lógica de categorías y filtros
✅ assets/js/firma.js - Canvas para firma digital

Sistema de Firma Digital:

✅ Firma obligatoria en primer login
✅ Usuario captura su propia firma (una sola vez)
❌ Usuario NO puede editar su firma
✅ Solo admin puede editar/actualizar firmas de usuarios
✅ Canvas HTML5 con soporte táctil (móvil)
✅ Validación de firma vacía

Versionado:

✅ ASSETS_VERSION = 1.0.2 para cache-busting

# 📋 CHANGELOG - Módulo 4: Reportes
Versión 2.0.0 - 14/10/2025
🔄 CAMBIO MAYOR: Rediseño completo del sistema de reportes
ANTES: Reporte = 1 actividad por día
AHORA: Reporte = Múltiples actividades + abastecimientos de combustible

✨ NUEVAS FUNCIONALIDADES:
1. Sistema de Horómetros

Cambio de "horas inicio/fin" a "horómetro inicial/final"
Cálculo automático de horas trabajadas por actividad
Validación: horómetro final > horómetro inicial

2. Múltiples Actividades por Día

Un reporte puede tener N actividades
Cada actividad tiene: tipo de trabajo, fase de costo, horómetros
Agregar/eliminar actividades dinámicamente

3. Control de Combustible

Registro de abastecimientos durante el día
Captura: horómetro, galones, observaciones

4. Estados del Reporte

Borrador: Editable por el operador
Finalizado: Solo lectura (excepto admin)
Fecha bloqueada (solo admin puede cambiar)

5. Catálogos Administrativos

Tipos de Trabajo (Acarreo, Excavación, etc.)
Fases de Costo (FC001, FC025, etc.)
CRUD completo para admin


🗄️ BASE DE DATOS:
Nuevas Tablas:
✅ tipos_trabajo
✅ fases_costo
✅ reportes (rediseñada)
✅ reportes_detalle
✅ reportes_combustible
Eliminadas:
❌ Campos: hora_inicio, hora_fin, actividad, observaciones, ubicacion

📂 ARCHIVOS NUEVOS:
APIs:

api/reportes.php (reescrito completamente)
api/tipos_trabajo.php (nuevo)
api/fases_costo.php (nuevo)

Frontend:

modules/reportes/crear.php (rediseñado con modales)
modules/reportes/editar.php (nuevo)
modules/reportes/listar.php (actualizado)
modules/reportes/ver.php (pendiente)

Admin:

modules/admin/tipos_trabajo.php (pendiente)
modules/admin/fases_costo.php (pendiente)

JavaScript:

assets/js/reportes.js (pendiente - reescritura completa)


🔧 CORRECCIONES:
Migración a Layouts:

✅ Eliminados CDN
✅ Usa header.php, navbar.php, sidebar.php, footer.php
✅ Footer con versionado: ?v=' . ASSETS_VERSION

Permisos:

Operador: Solo sus reportes, solo borradores
Admin: Todos los reportes, puede editar finalizados
Supervisor: Ver todos (sin editar)

📋 CHANGELOG - Módulo de Reportes
✅ Archivos Generados (6 nuevos):
PHP (3):

✅ modules/reportes/ver.php - Vista detallada con actividades y combustible
✅ modules/admin/tipos_trabajo.php - CRUD catálogo tipos de trabajo
✅ modules/admin/fases_costo.php - CRUD catálogo fases de costo

JavaScript (3):

✅ assets/js/reportes.js - Lógica completa (crear, actividades, combustible)
✅ assets/js/tipos_trabajo.js - CRUD tipos de trabajo
✅ assets/js/fases_costo.js - CRUD fases de costo

📦 Funcionalidades:
Reportes:

✅ Sistema de actividades múltiples con horómetros
✅ Registro de combustible múltiple
✅ Crear/editar/finalizar reportes
✅ Vista detallada con totales
✅ DataTables con español embebido

Catálogos (Admin only):

✅ CRUD completo tipos de trabajo
✅ CRUD completo fases de costo
✅ Desactivar en lugar de eliminar si tiene reportes
✅ Validación de duplicados

1. Filtro de Equipos por Categoría

Operadores ven solo equipos de su categoría
Admin/Supervisor ven todos los equipos

2. Eliminar Reportes Vacíos

Botón para eliminar reportes sin actividades
Previene errores al seleccionar equipo equivocado

3. PDF Funcional

Generación de PDF con toda la información
Botón imprimir/guardar integrado
Diseño compacto para ahorrar papel (3 columnas arriba)

4. Permisos por Rol

Admin: Editar, ver, eliminar, descargar PDF (todo)
Supervisor: Ver y descargar PDF (solo lectura)
Operador: Editar borradores propios, ver finalizados, descargar PDF

5. Fixes

Error modal actividad (función duplicada)
Error DataTable equipos (reinitialize)
Error DataTable reportes (columnas por rol)
Botones de acción visibles para admin/supervisor

Módulo 7: Exportación PDF

✅ Migración de HTML+print a FPDF real
✅ Encabezado fijo: "OperaSys - Reporte Diario de Operaciones" (hardcodeado)
✅ Logo de empresa en esquina superior derecha
✅ Tabla SQL configuracion_empresa creada
✅ Módulo admin configuracion_empresa.php para gestionar datos de empresa
✅ PDFs con secciones: Info reporte, Actividades, Combustible, Observaciones, Firma
✅ Diseño profesional A4, 100% offline

Módulo 8: PWA

✅ manifest.json configurado con ID y rutas absolutas
✅ Service Worker corregido (error POST cache solucionado)
✅ Iconos PWA en rutas correctas (/assets/images/icons/)
✅ Meta tags PWA agregados en header.php
✅ Botón de instalación en sidebar
✅ Script pwa-install.js implementado
✅ App instalable y funcional ✅
✅ IndexedDB para modo offline
✅ Sincronización automática de reportes pendientes

Correcciones técnicas:

🔧 .htaccess corregido (permitir acceso interno a config.php)
🔧 Rutas absolutas para recursos PWA (evitar problemas con $base_path)
🔧 FPDF instalado correctamente (vendor/fpdf/)
🔧 Logo empresa en base64 (BD, no archivos físicos)
🔧 Sidebar actualizado con enlace "Config. Empresa"

# 