# OperaSys - Guía de Despliegue en Producción

## 📋 Lista de Verificación Pre-Despliegue

Antes de subir a producción, completar esta lista:

- [ ] Base de datos configurada
- [ ] AdminLTE instalado localmente
- [ ] FPDF instalado
- [ ] Usuarios de prueba creados
- [ ] Equipos cargados
- [ ] Reportes de prueba creados
- [ ] PDFs generándose correctamente
- [ ] PWA instalable
- [ ] Modo offline probado
- [ ] Sincronización funcionando

---

## 🚀 Despliegue en Servidor Web

### 1. Requisitos del Servidor

**Servidor Web:**
- Apache 2.4+ o Nginx
- PHP 7.4+ (Recomendado: PHP 8.0+)
- MySQL 5.7+ o MariaDB 10.3+

**Extensiones PHP Requeridas:**
```bash
php -m | grep -E 'pdo|mysqli|gd|mbstring|json|session'
```

Debe mostrar:
- `pdo_mysql`
- `mysqli`
- `gd` (para firmas)
- `mbstring`
- `json`
- `session`

**Instalar extensiones faltantes:**
```bash
# Ubuntu/Debian
sudo apt install php-mysql php-gd php-mbstring php-json

# CentOS/RHEL
sudo yum install php-mysql php-gd php-mbstring php-json
```

---

### 2. Subir Archivos al Servidor

**Opción A: FTP/SFTP**
1. Comprimir carpeta `operasys/`
2. Subir ZIP al servidor
3. Extraer en directorio web (`/var/www/html/` o `/public_html/`)

**Opción B: Git**
```bash
# En el servidor
cd /var/www/html/
git clone https://tu-repositorio.git operasys
cd operasys
```

**Opción C: Panel de Control (cPanel, Plesk)**
1. Usar administrador de archivos
2. Subir y extraer ZIP

---

### 3. Configurar Base de Datos

**Crear base de datos:**
```sql
CREATE DATABASE operasys CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**Crear usuario:**
```sql
CREATE USER 'operasys_user'@'localhost' IDENTIFIED BY 'contraseña_segura';
GRANT ALL PRIVILEGES ON operasys.* TO 'operasys_user'@'localhost';
FLUSH PRIVILEGES;
```

**Importar estructura:**
```bash
mysql -u operasys_user -p operasys < database/operasys.sql
```

O desde phpMyAdmin:
1. Seleccionar base de datos `operasys`
2. Click en "Importar"
3. Seleccionar archivo `operasys.sql`
4. Click "Continuar"

---

### 4. Configurar Archivo de Conexión

Editar `config/database.php`:

```php
$host = 'localhost';
$dbname = 'operasys';
$username = 'operasys_user';
$password = 'tu_contraseña_segura';
$charset = 'utf8mb4';
```

---

### 5. Configurar Permisos de Archivos

```bash
# Cambiar propietario (ajustar según servidor)
sudo chown -R www-data:www-data /var/www/html/operasys

# Permisos de carpetas
sudo find /var/www/html/operasys -type d -exec chmod 755 {} \;

# Permisos de archivos
sudo find /var/www/html/operasys -type f -exec chmod 644 {} \;

# Carpeta de uploads (escritura)
sudo chmod 777 uploads/
sudo chmod 777 uploads/firmas/

# Logs (si existen)
sudo mkdir -p logs/
sudo chmod 777 logs/
```

---

### 6. Configurar .htaccess

Editar `.htaccess` y ajustar rutas:

```apache
RewriteBase /operasys/

# Si está en raíz del dominio:
# RewriteBase /
```

**Habilitar mod_rewrite en Apache:**
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

### 7. Configurar Variables de Entorno

Copiar archivo de ejemplo:
```bash
cp .env.example .env
```

Editar `.env`:
```ini
SITE_URL=https://tudominio.com/operasys
DEBUG_MODE=false
SECRET_KEY=genera_clave_segura_aqui
SESSION_TIMEOUT=7200
```

**Generar clave secreta:**
```bash
php -r "echo bin2hex(random_bytes(32));"
```

---

### 8. Configurar HTTPS (SSL)

**Opción A: Let's Encrypt (Gratis)**
```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d tudominio.com
```

**Opción B: SSL de hosting**
- Ir al panel de control
- Buscar "SSL/TLS"
- Instalar certificado

**Forzar HTTPS en .htaccess:**
Descomentar líneas 68-70:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

### 9. Configurar Manifest.json para PWA

Editar `manifest.json`:

```json
{
  "start_url": "https://tudominio.com/operasys/index.php",
  "scope": "https://tudominio.com/operasys/"
}
```

---

### 10. Configurar Service Worker

Editar `service-worker.js` línea 9:

```javascript
const CACHE_URLS = [
    'https://tudominio.com/operasys/',
    'https://tudominio.com/operasys/index.php',
    // ... resto de URLs con dominio completo
];
```

---

## 🔒 Seguridad en Producción

### 1. Deshabilitar Errores de PHP

Editar `php.ini` o `.htaccess`:
```ini
php_flag display_errors Off
php_flag log_errors On
php_value error_log /var/www/html/operasys/logs/php_error.log
```

### 2. Proteger Archivos Sensibles

Verificar que `.htaccess` proteja:
- `config/`
- `*.sql`
- `*.md`
- `.env`
- `.git/`

### 3. Cambiar Credenciales por Defecto

```sql
-- Cambiar contraseña del admin
UPDATE usuarios 
SET password = '$2y$10$nueva_contraseña_hasheada' 
WHERE username = 'admin';
```

O desde la app:
1. Login como admin
2. Ir a perfil
3. Cambiar contraseña

### 4. Configurar Firewall

```bash
# UFW (Ubuntu)
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### 5. Limitar Intentos de Login

Ya implementado en `api/auth.php` (3 intentos).

### 6. Backup Automático

Crear script de backup:
```bash
#!/bin/bash
# backup.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/operasys"
DB_NAME="operasys"
DB_USER="operasys_user"
DB_PASS="tu_contraseña"

# Crear directorio
mkdir -p $BACKUP_DIR

# Backup de BD
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/db_$DATE.sql

# Backup de archivos
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/html/operasys

# Limpiar backups antiguos (más de 30 días)
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete

echo "Backup completado: $DATE"
```

**Automatizar con cron:**
```bash
sudo crontab -e

# Backup diario a las 2 AM
0 2 * * * /root/backup.sh
```

---

## ⚡ Optimización

### 1. Habilitar OPcache (PHP)

Editar `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

### 2. Optimizar MySQL

Editar `my.cnf`:
```ini
[mysqld]
innodb_buffer_pool_size=256M
query_cache_size=64M
max_connections=100
```

### 3. Minificar CSS y JS (Opcional)

Usar herramientas online:
- CSS: https://cssminifier.com/
- JS: https://javascript-minifier.com/

### 4. Optimizar Imágenes

Comprimir iconos PNG:
```bash
# Ubuntu
sudo apt install optipng
optipng -o7 assets/img/*.png
```

---

## 📊 Monitoreo

### 1. Logs del Sistema

```bash
# Ver logs de Apache
tail -f /var/log/apache2/error.log

# Ver logs de PHP
tail -f /var/www/html/operasys/logs/php_error.log

# Ver logs de MySQL
tail -f /var/log/mysql/error.log
```

### 2. Auditoría de OperaSys

```sql
-- Ver últimas acciones
SELECT * FROM auditoria 
ORDER BY fecha DESC 
LIMIT 50;
```

### 3. Monitoreo de Espacio

```bash
# Espacio en disco
df -h

# Tamaño de la BD
du -sh /var/lib/mysql/operasys/

# Tamaño de uploads
du -sh /var/www/html/operasys/uploads/
```

---

## 🧪 Pruebas Post-Despliegue

- [ ] Abrir: `https://tudominio.com/operasys`
- [ ] Login funciona
- [ ] Dashboard carga correctamente
- [ ] Crear reporte
- [ ] Descargar PDF
- [ ] Probar modo offline
- [ ] Instalar PWA en móvil
- [ ] Verificar sincronización
- [ ] Probar en diferentes navegadores
- [ ] Probar en diferentes dispositivos

---

## 🆘 Solución de Problemas

### Error 500 - Internal Server Error
**Causa:** Error de PHP o permisos  
**Solución:** Revisar logs de Apache/PHP

### Página en blanco
**Causa:** Error fatal de PHP  
**Solución:** Activar `display_errors` temporalmente

### No conecta a BD
**Causa:** Credenciales incorrectas  
**Solución:** Verificar `config/database.php`

### PDFs no se generan
**Causa:** FPDF no instalado  
**Solución:** Copiar archivos de FPDF

### PWA no se instala
**Causa:** Manifest.json mal configurado  
**Solución:** Verificar URLs completas con HTTPS

### Service Worker no funciona
**Causa:** Requiere HTTPS  
**Solución:** Instalar certificado SSL

---

## 📞 Soporte

En caso de problemas:
1. Revisar logs del servidor
2. Revisar tabla `auditoria` en BD
3. Probar en local primero
4. Verificar permisos de archivos

---

✅ **¡Despliegue completado!**

Tu sistema OperaSys ahora está en producción.
