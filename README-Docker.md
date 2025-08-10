# ISURGOB - Configuración Docker

Este proyecto ha sido dockerizado para facilitar su despliegue y ejecución.

## Requisitos Previos

- Docker Desktop instalado en tu sistema
- Docker Compose (incluido con Docker Desktop)

## Instalación y Ejecución

### 1. Clonar o descargar el proyecto
```bash
git clone <url-del-repositorio>
cd isurgob
```

### 2. Construir y ejecutar los contenedores
```bash
docker-compose up -d
```

Este comando:
- Construirá la imagen de la aplicación web
- Descargará la imagen de PostgreSQL
- Creará y ejecutará ambos contenedores
- Inicializará la base de datos con los scripts SQL

### 3. Acceder a la aplicación
Una vez que los contenedores estén ejecutándose, puedes acceder a:
- **Aplicación web**: http://localhost:8080
- **Base de datos PostgreSQL**: localhost:5432
  - Usuario: admin
  - Contraseña: admin
  - Base de datos: sam

## Comandos Útiles

### Ver el estado de los contenedores
```bash
docker-compose ps
```

### Ver los logs de la aplicación
```bash
docker-compose logs web
```

### Ver los logs de la base de datos
```bash
docker-compose logs db
```

### Detener los contenedores
```bash
docker-compose down
```

### Detener y eliminar volúmenes (¡CUIDADO! Esto eliminará los datos)
```bash
docker-compose down -v
```

### Reconstruir la imagen de la aplicación
```bash
docker-compose build web
docker-compose up -d
```

## Estructura de Archivos Docker

- `Dockerfile`: Define cómo construir la imagen de la aplicación web
- `docker-compose.yml`: Orquesta los servicios (web + base de datos)
- `.dockerignore`: Excluye archivos innecesarios del contexto de construcción
- `README-Docker.md`: Este archivo con instrucciones

## Despliegue en la Nube

Para desplegar en servicios como AWS, Google Cloud, o Azure:

1. Sube los archivos `Dockerfile` y `docker-compose.yml` a tu repositorio
2. Configura las variables de entorno según el proveedor
3. Usa los servicios de contenedores del proveedor (ECS, Cloud Run, Container Instances)

## Solución de Problemas

### La aplicación no se conecta a la base de datos
- Verifica que ambos contenedores estén ejecutándose: `docker-compose ps`
- Revisa los logs: `docker-compose logs`

### Error de permisos
- En Linux/Mac, asegúrate de que Docker tenga permisos para acceder al directorio

### Puerto ya en uso
- Cambia el puerto en `docker-compose.yml` de `8080:80` a `8081:80` (o cualquier puerto libre)

## Configuración de Producción

Para producción, considera:
- Cambiar las contraseñas por defecto
- Usar variables de entorno para credenciales
- Configurar HTTPS
- Implementar backups de la base de datos
- Usar un proxy reverso (nginx)