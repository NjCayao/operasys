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

