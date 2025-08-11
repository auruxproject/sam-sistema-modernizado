# Guía de Despliegue SAM en EasyPanel

## 🚀 Resumen de Cambios Realizados

Este documento detalla todas las modificaciones realizadas para resolver el error 500 y hacer compatible el Sistema de Administración Municipal (SAM) con EasyPanel y Traefik.

## 📋 Problemas Identificados y Solucionados

### 1. **Configuración de Proxy Headers**
- ✅ Configurado manejo de headers `X-Forwarded-Proto`, `X-Forwarded-For`, `X-Forwarded-Host`
- ✅ Ajustado `.htaccess` para compatibilidad con Traefik
- ✅ Configurado trusted proxies en `index.php`

### 2. **Estructura de Archivos y Directorios**
- ✅ Creado directorio `runtime/` para logs y cache
- ✅ Creado directorio `web/assets/` para recursos
- ✅ Corregidas rutas del autoloader y framework Yii2

### 3. **Configuración de Aplicación**
- ✅ Actualizado `config/web.php` con configuraciones para EasyPanel
- ✅ Creado archivo `.env` con variables de entorno
- ✅ Configurado `composer.json` con dependencias necesarias

### 4. **Health Checks y Monitoreo**
- ✅ Creado `health.php` para health checks de EasyPanel
- ✅ Configurados endpoints `/health`, `/status`, `/ping`
- ✅ Creado `debug.php` para diagnóstico avanzado

### 5. **Controladores y Vistas**
- ✅ Creado `SiteController.php` con funcionalidades básicas
- ✅ Creadas vistas principales (`login.php`, `index.php`, `error.php`)
- ✅ Configurado layout principal `main.php`

### 6. **Assets y Recursos**
- ✅ Creado `AppAsset.php` para gestión de recursos
- ✅ Configurado `site.css` con estilos responsivos
- ✅ Creado `site.js` con funcionalidades JavaScript

## 🔧 Archivos Modificados/Creados

### Archivos de Configuración
- `index.php` - Punto de entrada principal
- `config/web.php` - Configuración de aplicación
- `.htaccess` - Configuración Apache/Traefik
- `.env` - Variables de entorno
- `composer.json` - Dependencias PHP

### Controladores
- `controllers/SiteController.php` - Controlador principal

### Vistas
- `views/layouts/main.php` - Layout principal
- `views/site/index.php` - Página principal
- `views/site/login.php` - Página de login
- `views/site/error.php` - Página de errores

### Assets
- `web/css/site.css` - Estilos CSS
- `web/js/site.js` - Scripts JavaScript
- `assets/AppAsset.php` - Gestión de assets

### Utilidades
- `health.php` - Health check para EasyPanel
- `debug.php` - Script de diagnóstico
- `test.php` - Script de pruebas

## 🐳 Configuración para EasyPanel

### Variables de Entorno Requeridas
```bash
# Configuración básica
APP_ENV=production
APP_DEBUG=false
COOKIE_VALIDATION_KEY=your-unique-key-here

# Base de datos
DB_HOST=your-db-host
DB_PORT=5432
DB_NAME=sam_db
DB_USERNAME=sam_user
DB_PASSWORD=your-secure-password

# EasyPanel específico
TRUSTED_PROXIES=10.0.0.0/8,172.16.0.0/12,192.168.0.0/16
TRUSTED_HOSTS=*.easypanel.host,localhost
FORCE_HTTPS=true
APP_DOMAIN=your-app.easypanel.host
```

### Dockerfile Recomendado
```dockerfile
FROM php:8.1-apache

# Instalar extensiones PHP necesarias
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql zip mbstring

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Copiar aplicación
COPY . /var/www/html/

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html/runtime /var/www/html/web/assets
RUN chmod -R 755 /var/www/html/runtime /var/www/html/web/assets

# Configurar DocumentRoot
ENV APACHE_DOCUMENT_ROOT /var/www/html/web
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

EXPOSE 80
```

### Configuración de EasyPanel
```yaml
# docker-compose.yml para EasyPanel
version: '3.8'
services:
  sam-app:
    build: .
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - DB_HOST=${DB_HOST}
      - DB_NAME=${DB_NAME}
      - DB_USERNAME=${DB_USERNAME}
      - DB_PASSWORD=${DB_PASSWORD}
    volumes:
      - ./runtime:/var/www/html/runtime
      - ./web/assets:/var/www/html/web/assets
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.sam.rule=Host(`your-domain.easypanel.host`)"
      - "traefik.http.services.sam.loadbalancer.server.port=80"
      - "traefik.http.routers.sam.middlewares=sam-headers"
      - "traefik.http.middlewares.sam-headers.headers.customrequestheaders.X-Forwarded-Proto=https"
```

## 🔍 Diagnóstico y Resolución de Problemas

### Scripts de Diagnóstico
1. **`debug.php`** - Diagnóstico completo del sistema
2. **`test.php`** - Verificación de componentes
3. **`health.php`** - Health check para EasyPanel

### Logs de Error
- Logs de aplicación: `runtime/logs/app.log`
- Logs de debug: `runtime/debug.log`
- Logs de Apache: Verificar en EasyPanel

### Comandos de Verificación
```bash
# Verificar permisos
ls -la runtime/ web/assets/

# Verificar configuración PHP
php -m | grep -E "pdo|pgsql|mbstring|openssl"

# Probar conectividad de BD
psql -h $DB_HOST -U $DB_USERNAME -d $DB_NAME -c "SELECT version();"
```

## 🚨 Checklist de Despliegue

### Antes del Despliegue
- [ ] Verificar que todas las extensiones PHP están instaladas
- [ ] Configurar variables de entorno en EasyPanel
- [ ] Verificar conectividad a base de datos
- [ ] Configurar dominio y certificados SSL

### Durante el Despliegue
- [ ] Verificar que los directorios `runtime/` y `web/assets/` tienen permisos de escritura
- [ ] Probar health check: `https://your-domain/health.php`
- [ ] Verificar diagnóstico: `https://your-domain/debug.php`
- [ ] Probar aplicación principal: `https://your-domain/`

### Después del Despliegue
- [ ] Verificar logs de error
- [ ] Probar funcionalidades principales
- [ ] Configurar monitoreo y alertas
- [ ] Realizar backup de configuración

## 📞 Soporte y Contacto

Si persisten los problemas:
1. Revisar logs en `runtime/debug.log`
2. Ejecutar `debug.php` para diagnóstico completo
3. Verificar configuración de EasyPanel/Traefik
4. Contactar soporte técnico con logs específicos

## 🔄 Actualizaciones Futuras

Para futuras actualizaciones:
1. Mantener compatibilidad con EasyPanel
2. Actualizar dependencias en `composer.json`
3. Verificar configuración de proxy headers
4. Probar en entorno de staging antes de producción

---

**Versión:** 1.0  
**Fecha:** $(date +'%Y-%m-%d')  
**Compatible con:** EasyPanel, Traefik, PHP 8.1+, Yii2 2.0+