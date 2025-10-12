# OperaSys - Sistema de Reportes de Operación

<div align="center">

![Version](https://img.shields.io/badge/version-1.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)
![PWA](https://img.shields.io/badge/PWA-Ready-blueviolet.svg)

**Sistema web progresivo (PWA) para reportes diarios de maquinaria pesada con firma digital y funcionamiento offline**

[📥 Descargar](#-instalación) | [📖 Documentación](#-módulos) | [🚀 Despliegue](DESPLIEGUE_PRODUCCION.md)

</div>

---

## ✨ Características Principales

🔐 **Autenticación Segura** - Sistema multi-rol (Admin, Supervisor, Operador)  
✍️ **Firma Digital** - Captura con canvas HTML5  
📱 **PWA** - Instalable como app nativa  
🔌 **100% Offline** - Funciona sin internet  
🔄 **Sincronización** - Automática al recuperar conexión  
🚜 **Gestión de Equipos** - CRUD completo de maquinaria  
📄 **Reportes Diarios** - Con ubicación GPS  
📊 **Dashboard** - Estadísticas y gráficos  
📑 **Exportación PDF** - Con firma digital incluida  
🌐 **Responsive** - Móvil, tablet y desktop  

---

## 🛠 Tecnologías

| Backend | Frontend | PWA |
|---------|----------|-----|
| PHP 7.4+ | AdminLTE 3 | Service Worker |
| MySQL 5.7+ | Bootstrap 4 | IndexedDB |
| PDO | Chart.js | Cache API |
| FPDF | DataTables | Manifest.json |

---

## 📋 Requisitos

### Servidor
- Apache 2.4+ o Nginx
- PHP 7.4+ *(recomendado: 8.0+)*
- MySQL 5.7+ o MariaDB 10.3+
- mod_rewrite habilitado

### Extensiones PHP
```
pdo_mysql, mysqli, gd, mbstring, json, session
```

### Cliente
- Navegador moderno (Chrome, Firefox, Safari, Edge)
- JavaScript habilitado

---

## 🚀 Instalación Rápida

### 1️⃣ Base de Datos

```sql
CREATE DATABASE operasys CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE operasys;
SOURCE database/operasys.sql;
```

### 2️⃣ Configuración

Editar `config/database.php`:

```php
$host = 'localhost';
$dbname = 'operasys';
$username = 'root';
$password = '';
```

### 3️⃣ Instalar AdminLTE

📖 Ver: [INSTALACION_ADMINLTE_LOCAL.md](INSTALACION_ADMINLTE_LOCAL.md)

1. Descargar: https://adminlte.io/
2. Copiar a: `vendor/adminlte/`

### 4️⃣ Instalar FPDF

📖 Ver: [INSTALACION_FPDF.md](INSTALACION_FPDF.md)

1. Descargar: http://www.fpdf.org/
2. Copiar a: `vendor/fpdf/`

### 5️⃣ Permisos

```bash
chmod 777 uploads/
chmod 777 uploads/firmas/
```

### 6️⃣ Abrir

```
http://localhost/operasys
```

**Login inicial:**
- Usuario: `admin`
- Contraseña: `12345678`
- DNI: `12345678`

⚠️ Cambiar credenciales después del primer login

---

## 📁 Estructura

```
operasys/
├── api/              # Endpoints REST
├── assets/           # CSS, JS, imágenes
├── config/           # Configuración
├── database/         # SQL
├── errors/           # Páginas de error
├── modules/          # Módulos PHP
├── uploads/          # Archivos subidos
├── vendor/           # Librerías (AdminLTE, FPDF)
├── .htaccess         # Apache config
├── manifest.json     # PWA config
└── service-worker.js # Service Worker
```

---

## 📦 Módulos

| # | Módulo | Descripción |
|---|--------|-------------|
| 1 | Autenticación | Login multi-rol, sesiones |
| 2 | Registro y Firma | Canvas HTML5, firma digital |
| 3 | Gestión de Equipos | CRUD, DataTables, filtros |
| 4 | Reportes Diarios | Formulario, GPS, validaciones |
| 5 | PWA + Offline | Service Worker, IndexedDB, sync |
| 6 | Dashboard | Estadísticas, gráficos Chart.js |
| 7 | Exportación PDF | FPDF, firma en documento |
| 8 | Config Final | .htaccess, optimización, deploy |

---

## 🎯 Uso

### Como Operador

1. **Login** → DNI + contraseña
2. **Registrar firma** (primera vez)
3. **Crear reporte** → Seleccionar equipo, describir actividad
4. **Trabajar offline** → Los datos se sincronizan automáticamente
5. **Descargar PDF** → Con firma digital incluida

### Como Administrador

1. **Dashboard** → Ver estadísticas globales
2. **Gestionar equipos** → Agregar/editar/eliminar
3. **Ver reportes globales** → De todos los operadores
4. **Auditoría** → Revisar actividad del sistema

---

## 📱 PWA - Funcionamiento Offline

### ¿Cómo funciona?

1. **Primera carga** (con internet):
   - Service Worker se instala
   - Cachea todos los archivos
   - Guarda equipos en IndexedDB

2. **Sin internet**:
   - Crear reportes → Se guardan localmente
   - Ver equipos → Desde caché
   - Trabajar normalmente

3. **Recupera internet**:
   - Sincronización automática
   - Reportes se envían al servidor
   - Caché se actualiza

### Instalar como App

**Android:**
- Chrome mostrará "Agregar a inicio"
- Click "Instalar"

**iOS:**
- Safari → Compartir → "Agregar a inicio"

**Windows/Mac:**
- Chrome → Icono de instalación en barra
- Click "Instalar"

---

## 📄 Exportación PDF

Los PDFs incluyen:

- ✅ Información del reporte
- ✅ Datos del operador
- ✅ Equipo utilizado
- ✅ Actividad realizada
- ✅ **Firma digital** (imagen PNG)
- ✅ Ubicación GPS
- ✅ Código de verificación

---

## 🚀 Despliegue en Producción

📖 **Guía completa:** [DESPLIEGUE_PRODUCCION.md](DESPLIEGUE_PRODUCCION.md)

**Incluye:**
- Configuración servidor
- SSL/HTTPS
- Optimización
- Seguridad
- Backup automático
- Monitoreo

---

## 🔒 Seguridad

- ✅ Passwords hasheadas (bcrypt)
- ✅ Sesiones seguras
- ✅ Protección XSS
- ✅ Protección CSRF
- ✅ SQL Injection (PDO prepared statements)
- ✅ Control de permisos por rol
- ✅ Auditoría completa
- ✅ .htaccess optimizado

---

## 📊 Estado del Proyecto

| Módulo | Estado |
|--------|--------|
| Autenticación | ✅ Completo |
| Registro y Firma | ✅ Completo |
| Gestión Equipos | ✅ Completo |
| Reportes | ✅ Completo |
| PWA + Offline | ✅ Completo |
| Dashboard | ✅ Completo |
| Exportación PDF | ✅ Completo |
| Config Final | ✅ Completo |

**Estado: Listo para producción** 🎉

---

## 📖 Documentación

- [INSTALACION_ADMINLTE_LOCAL.md](INSTALACION_ADMINLTE_LOCAL.md) - Instalar AdminLTE
- [INSTALACION_FPDF.md](INSTALACION_FPDF.md) - Instalar FPDF
- [README_MODULO_3.md](README_MODULO_3.md) - Gestión de equipos
- [README_MODULO_5.md](README_MODULO_5.md) - PWA y offline
- [README_MODULO_7.md](README_MODULO_7.md) - Exportación PDF
- [DESPLIEGUE_PRODUCCION.md](DESPLIEGUE_PRODUCCION.md) - Subir a producción

---

## 🐛 Solución de Problemas

### Error de conexión BD
```
Verificar credenciales en config/database.php
Verificar que MySQL esté corriendo
```

### AdminLTE no carga
```
Verificar que esté en vendor/adminlte/
O usar versión CDN (ya incluida)
```

### PDFs no se generan
```
Instalar FPDF según guía
Verificar permisos de archivos
```

### PWA no se instala
```
Requiere HTTPS (excepto localhost)
Verificar manifest.json
```

---

## 📄 Licencia

MIT License - Uso libre

---

## 🤝 Contribuciones

Las contribuciones son bienvenidas:

1. Fork el proyecto
2. Crea tu feature branch
3. Commit tus cambios
4. Push a la branch
5. Abre un Pull Request

---

## 🎉 Créditos

- **AdminLTE** - https://adminlte.io/
- **FPDF** - http://www.fpdf.org/
- **Chart.js** - https://www.chartjs.org/
- **DataTables** - https://datatables.net/
- **SweetAlert2** - https://sweetalert2.github.io/

---

<div align="center">

**Desarrollado con ❤️ para optimizar la gestión de operaciones**

**Versión 1.0** | **© 2025 OperaSys**

[⬆ Volver arriba](#operasys---sistema-de-reportes-de-operación)

</div>
