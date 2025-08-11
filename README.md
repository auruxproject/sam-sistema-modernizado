# 🏛️ Sistema de Administración Municipal (SAM) - Versión Modernizada

[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE.md)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![Yii Framework](https://img.shields.io/badge/Yii-2.0%2B-green.svg)](https://www.yiiframework.com)
[![EasyPanel Compatible](https://img.shields.io/badge/EasyPanel-Compatible-orange.svg)](https://easypanel.io)

## 📋 Descripción

Sistema integral de administración municipal modernizado y optimizado para despliegue en contenedores. Compatible con **EasyPanel**, **Traefik**, y entornos de nube modernos.

### 🚀 Características Principales

- **🏗️ Arquitectura Modular**: Tres módulos principales (SAM, SAMSEG, SAMTRIB)
- **🐳 Containerizado**: Listo para Docker y EasyPanel
- **🔒 Seguro**: Configuración de seguridad moderna con HTTPS
- **📱 Responsivo**: Interfaz adaptable a dispositivos móviles
- **⚡ Optimizado**: Rendimiento mejorado y caching inteligente
- **🔍 Monitoreo**: Health checks y diagnósticos integrados

## 🛠️ Tecnologías

- **Backend**: PHP 8.1+, Yii Framework 2.0
- **Base de Datos**: PostgreSQL
- **Frontend**: Bootstrap 4, jQuery
- **Contenedores**: Docker, EasyPanel
- **Proxy**: Traefik compatible
- **Cache**: Redis (opcional)

## 📦 Módulos del Sistema

### 🏛️ SAM (Sistema de Administración Municipal)
- Gestión de expedientes
- Administración de personal
- Control de inventarios
- Reportes y estadísticas

### 🛡️ SAMSEG (Seguridad)
- Control de acceso
- Gestión de usuarios
- Auditoría de sistema
- Perfiles y permisos

### 💰 SAMTRIB (Tributario)
- Gestión tributaria
- Liquidación de impuestos
- Control de contribuyentes
- Facturación electrónica

## 🚀 Instalación Rápida

### Usando EasyPanel (Recomendado)

1. **Clonar el repositorio**:
   ```bash
   git clone https://github.com/auruxproject/sam-sistema-modernizado.git
   cd sam-sistema-modernizado
   ```

2. **Configurar variables de entorno**:
   ```bash
   cp .env.example .env
   # Editar .env con tus configuraciones
   ```

3. **Desplegar en EasyPanel**:
   - Crear nueva aplicación en EasyPanel
   - Conectar repositorio GitHub
   - Configurar variables de entorno
   - Desplegar automáticamente

### Usando Docker

```bash
# Construir imagen
docker build -t sam-sistema .

# Ejecutar contenedor
docker run -d \
  --name sam-app \
  -p 80:80 \
  -e DB_HOST=your-db-host \
  -e DB_NAME=sam_db \
  -e DB_USERNAME=sam_user \
  -e DB_PASSWORD=your-password \
  sam-sistema
```

## ⚙️ Configuración

### Variables de Entorno Principales

```bash
# Aplicación
APP_ENV=production
APP_DEBUG=false
COOKIE_VALIDATION_KEY=your-unique-key

# Base de Datos
DB_HOST=localhost
DB_PORT=5432
DB_NAME=sam_db
DB_USERNAME=sam_user
DB_PASSWORD=secure-password

# EasyPanel/Traefik
TRUSTED_PROXIES=10.0.0.0/8,172.16.0.0/12,192.168.0.0/16
TRUSTED_HOSTS=*.easypanel.host,localhost
FORCE_HTTPS=true
```

## 🔍 Health Checks y Diagnóstico

El sistema incluye herramientas de diagnóstico integradas:

- **`/health.php`** - Health check para EasyPanel
- **`/debug.php`** - Diagnóstico completo del sistema
- **`/test.php`** - Verificación de componentes

### Endpoints de Monitoreo

- `GET /health` - Estado general del sistema
- `GET /status` - Estado detallado
- `GET /ping` - Verificación básica de conectividad

## 📁 Estructura del Proyecto

```
sam-sistema-modernizado/
├── 📁 config/           # Configuraciones
├── 📁 controllers/      # Controladores MVC
├── 📁 views/           # Vistas y layouts
├── 📁 web/             # Assets públicos
├── 📁 runtime/         # Archivos temporales
├── 📁 sam/             # Módulo SAM
├── 📁 samseg/          # Módulo SAMSEG
├── 📁 samtrib/         # Módulo SAMTRIB
├── 📁 db/              # Scripts de base de datos
├── 📄 health.php       # Health check
├── 📄 debug.php        # Diagnóstico
├── 📄 Dockerfile       # Configuración Docker
└── 📄 docker-compose.yml
```

## 🔧 Desarrollo

### Requisitos

- PHP 8.1 o superior
- PostgreSQL 12+
- Composer
- Node.js (para assets)

### Configuración Local

```bash
# Instalar dependencias
composer install

# Configurar base de datos
psql -U postgres -c "CREATE DATABASE sam_db;"
psql -U postgres sam_db < db/a-pgbackup_sam_logico-2019-05.sql

# Configurar permisos
chmod -R 755 runtime/ web/assets/

# Servidor de desarrollo
php -S localhost:8000 -t web
```

## 🚀 Despliegue en Producción

### Lista de Verificación

- [ ] ✅ Variables de entorno configuradas
- [ ] ✅ Base de datos PostgreSQL disponible
- [ ] ✅ Certificados SSL configurados
- [ ] ✅ Permisos de directorios correctos
- [ ] ✅ Health checks funcionando
- [ ] ✅ Logs configurados
- [ ] ✅ Backup programado

### Monitoreo Post-Despliegue

1. Verificar health check: `https://tu-dominio/health.php`
2. Revisar diagnóstico: `https://tu-dominio/debug.php`
3. Comprobar logs de aplicación
4. Validar conectividad de base de datos

## 📊 Características Técnicas

### Seguridad
- 🔒 HTTPS obligatorio
- 🛡️ Headers de seguridad configurados
- 🔐 Validación CSRF
- 🚫 Protección XSS
- 🔑 Gestión segura de sesiones

### Rendimiento
- ⚡ Cache de aplicación
- 🗜️ Compresión gzip
- 📦 Assets minificados
- 🔄 Lazy loading
- 📈 Optimización de consultas

### Compatibilidad
- 🐳 Docker nativo
- ☁️ EasyPanel optimizado
- 🔀 Traefik compatible
- 📱 Responsive design
- 🌐 Multi-idioma

## 🤝 Contribución

1. Fork el proyecto
2. Crear rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

## 📝 Changelog

### v2.0.0 (2024-01-08)
- ✨ Modernización completa del sistema
- 🐳 Compatibilidad con EasyPanel y Docker
- 🔒 Mejoras de seguridad
- 📱 Interfaz responsiva
- 🔍 Health checks integrados
- ⚡ Optimizaciones de rendimiento

## 📄 Licencia

Este proyecto está licenciado bajo la Licencia MIT - ver el archivo [LICENSE.md](LICENSE.md) para detalles.

## 🆘 Soporte

- 📧 **Email**: soporte@municipio.gov
- 📖 **Documentación**: [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)
- 🐛 **Issues**: [GitHub Issues](https://github.com/auruxproject/sam-sistema-modernizado/issues)
- 💬 **Discusiones**: [GitHub Discussions](https://github.com/auruxproject/sam-sistema-modernizado/discussions)

## 🏆 Créditos

Desarrollado con ❤️ para la modernización de la administración pública municipal.

---

**Sistema de Administración Municipal (SAM)** - Transformando la gestión municipal con tecnología moderna.

## ISURGOB - Información Histórica


### Subsistemas
---
Est� integrado por los siguientes SubSistemas:

- ISURGOB Tributario

El Sistema de Gesti�n Tributaria es un complejo y avanzado conjunto de sistemas de informaci�n, totalmente integrados, dise�ados para la gesti�n y administraci�n, que funciona como una base integradora de gesti�n.

Se basa en el concepto de Contribuyente �nico y Cuenta Corriente �nica.

Entre los m�dulos que se incluyen podemos mencionar: administraci�n de Contribuyentes y Objetos Imponibles, administraci�n de Cuenta Corriente �nica, emisi�n de Tasas peri�dicas, eventuales, Generaci�n de Declaraciones Juradas de Actividades Comerciales, Manejo de Agentes de Retenci�n y Percepci�n, generaci�n de Facilidades y Planes de Pago, gesti�n de Cobranza on-line y off-line, Cumplimiento Fiscal, Ejecuci�n Judicial y Servicios al Ciudadano.

A su vez permite la generaci�n de diferentes reportes y comprobantes para el pago de las diferentes tasas y contribuciones que operan en el Organismo en base a las normativas vigentes.
(https://github.com/isurgob/isurgob/tree/master/docs/samtrib.gif)

- ISURGOB Seguridad

El Subsistema de Seguridad es el encargado de gestionar los usuarios, perfiles y permisos de acceso a los distintos Subsistemas. Adem�s permite manejar los m�dulos de cada Subsistema. Tambi�n dispone de algunas auditor�as, en especial en lo que se refiere a control de acceso, accesos fallidos, blanqueos de clave, control de accesos m�ltiples, etc. 
F�sicamente de ubica en una carpeta distinta del Subsistema Tributario, aunque comparte algunas librer�as comunes de todos los Subsistemas. 
El Subsistema Tributario posee todos los m�dulos detallados de la Administraci�n Tributaria. De acuerdo a los permisos del usuario definidos en el Subsistema de Seguridad se habilitan las opciones disponibles.
(https://github.com/isurgob/isurgob/tree/master/docs/samseg.gif)

### Caracter�sticas del Sistema
---
La arquitectura de la soluci�n posee las siguientes caracter�sticas:
- Arquitectura Web Enabled
- Patr�n de dise�o MVC
- Interoperabilidad
- Escalabilidad, Confiabilidad y Fiabilidad
- Alto grado de Parametrizaci�n
- Integrac�n con otros sistemas mediante el uso de WebServices


### Servicios
---
Que es lo que hacemos y lo que brindamos
- M�ltiples canales de acceso
- Apoyo a la Infraestructura necesaria
- Mejora en la recaudaci�n
- Sistema intuitivo y din�mico
- Ajustes totalmente parametrizables
- Entrenamiento y capacitaci�n.


### Gu�a de instalaci�n
---
1. Prerequisitos
-   Sistema Operativo de Servidor: preferentemente Linux Server kernel versi�n 4.4 o superior, distribuciones recomendadas Debian/Ubuntu
-	Base de Datos: PostgreSQL 9.4 o superior
-	Servidor Web: Apache 2.4 o superior
-	Lenguaje: PHP 7.0
-   Librer�as PHP adicionales: 
    -   php-mbstring
	-   php-xml
	-   php-mcrypt
	-   php-gd
	-   php-zip
	-   pdo-pgsql
-   Ejemplo: $ sudo -E apt-get -yq --no-install-suggests --no-install-recommends install php7.0-xml php7.0-mbstring php7.0-mcrypt php7.0-gd php7.0-zip php7.0-pgsql

2. Instalaci�n de la Base de Datos
-   Soporte para PostgreSQL.

Para instalar las bases de datos es necesario cargar los scripts en las herramientas espec�ficas de las bases de datos como psql o PgAdmin

 -  Instalaci�n en PostgreSQL
 -  Abrir los script ubicados en la carpeta "db".
 -  Ejecutar mediante psql. El fichero Readme.txt contiene las instrucciones espec�ficas.

3. Descarga e Instalaci�n del C�digo
-   Descarga de c�digo desde el Repositorio de github
    -   $ wget https://github.com/isurgob/isurgob/archive/master.zip
-   Descomprimir el c�digo del paso anterior en el directorio de publicaci�n de Apache
    -   $ cd /var/www/html o similar seg�n la distriuci�n a utilizar.
	-   $ unzip master.zip.

4. Puesta en Marcha
Una vez instalado, tipear en su navegador Web http://ip_dns/sam.
Primeros Pasos en la imagen: (https://github.com/isurgob/isurgob/tree/master/docs/ISURGOB-Instala.gif)

5. Configuraci�n Inicial
En forma previa a la utilizaci�n de los m�dulos del Sistema, es necesario precargar los datos auxiliares y de configuraci�n en funci�n de las normativas propias del organismo.
Cada municipio tendr� su propia reglamentaci�n en materia de Tributos, Tasas, Contribuciones, Resoluciones y dem�s.
Asimismo, las tipolog�as y categorizaciones respecto de los Objetos Imponibles y otros m�dulos son propias de cada organismo en particular y es importante su ingreso desde el �rea de configuraciones, cito en el encabezado de la p�gina.
Accesos desde la barra superior del Sistema: (https://github.com/isurgob/isurgob/tree/master/docs/sam-config.jpg)

6. Vinculaci�n de ISURGOB Tributario con el GIS

-   Web Service: La vinculaci�n con otros sistemas se podr� llevar a cabo por medio de WebService.
-   Nomenclatura: La clave un�voca de todo Registro Gr�fico se centra en la nomenclatura parcelaria.
-   Solicitud de Informaci�n: A partir de la identificaci�n de una parcela en el GIS, se podr� invocar una funci�n definida en el WebService de ISURGob Tributario para recuperar informaci�n alfanum�rica a modo de consulta.
-   Desde el Formulario de consulta de inmueble, se dispondr� de link que permite el acceso al GIS. El acceso se realiza mediante URL, la cual se configura dentro del M�dulo de Configuraci�n. En la URL se env�a como par�metro la Nomenclatura del inmueble a localizar.
-   Los m�todos que se proveen son los siguientes:
    a)	Alta, Baja y modificaci�n unitaria de inmuebles:
    b)	Actualizaci�n de Valuaciones: Permitir� actualizar la informaci�n asociada a las valuaciones de inmuebles y mejoras, incluyendo todos los elementos necesarios, tales como: superficie, zona, coeficiente, valor b�sico, categor�a, etc. Este m�todo ser� invocado por el Sistema de Catastro, ante un proceso de reval�o, ya sea parcial o total.
    c)	Sem�foro de deuda: Consistir� en un sem�foro que indicar� si se pueden realizar gestiones sobre un inmueble en el Sistema de Catastro, de acuerdo al estado de deuda del mismo, teniendo en cuenta los par�metros para determinar la misma. El Sistema Comarcal incluir� las llamadas a este �sem�foro� cuando se inicien tr�mites que cambien el estado del inmueble (Planos de obra, declaraciones de mejoras, etc.)
-   Funciones y Aspectos T�cnicos: (https://github.com/isurgob/isurgob/tree/master/docs/InterfacesGIS.pdf)


### Arquitectura
---
El modelo se enmarca en por lo menos dos principios de la gesti�n p�blica de la calidad: principio de continuidad en la prestaci�n de servicios, que propone que los servicios p�blicos se prestar�n de manera regular e ininterrumpida, previendo las medidas necesarias para evitar o minimizar los perjuicios que pudieran ocasionarle al ciudadano en las posibles suspensiones del servicio. Y el principio de evaluaci�n permanente y mejora continua que propone que una gesti�n p�blica de calidad es aquella que contempla la evaluaci�n permanente, interna y externa, orientada a la identificaci�n de oportunidades para la mejora continua de los procesos, servicios y prestaciones p�blicas centrados en el servicio al ciudadano y para resultados, proporcionando insumos para la adecuada rendici�n de cuentas.
- Patr�n de Dise�o MVC
- Entorno Visual
- Usabilidad y Accesibilidad
- Ayuda en l�nea
- Interoperabilidad
- Escalabilidad y extensibilidad
- Alto grado de Parametrizaci�n


### Tecnolog�as
---
Para obtener las ventajas competitivas de la soluci�n multiprop�sito deseada, es b�sico contar con las herramientas de tecnolog�a necesarias.
-	Arquitectura Web Enabled
-	Base de Datos: PostgreSQL como servidor de Base de Datos Relacional
-	Servidor Web: Apache 2.4
-	Librer�as para interfase: BootStrap
-	Lenguaje de Desarrollo: PHP 7.0
-	Framework Yii 2.0
Las PC clientes, deber�n disponer de un navegador Web de �ltima generaci�n.


### Autor/es
---
- Gabriel Martinez (gabrielmart@gmail.com)
- Sandra Martinez (sandracmart@gmail.com)
- ISUR


### Informaci�n adicional
---
Se deber� contar con un Servidor de Base de Datos y Aplicaciones, preferentemente en Linux.
Se podr� migrar la informaci�n existente actualmente.


### Licencia 
---
[LICENCIA](https://github.com/isurgob/isurgob/blob/master/LICENSE.md)


## Limitaci�n de responsabilidades

ISUR, los autores mencionados, y el BID no ser�n responsables, bajo circunstancia alguna, de da�o ni indemnizaci�n, moral o patrimonial; directo o indirecto; accesorio o especial; o por v�a de consecuencia, previsto o imprevisto, que pudiese surgir:

i. Bajo cualquier teor�a de responsabilidad, ya sea por contrato, infracci�n de derechos de propiedad intelectual, negligencia o bajo cualquier otra teor�a; y/o

ii. A ra�z del uso de la Herramienta Digital, incluyendo, pero sin limitaci�n de potenciales defectos en la Herramienta Digital, o la p�rdida o inexactitud de los datos de cualquier tipo. Lo anterior incluye los gastos o da�os asociados a fallas de comunicaci�n y/o fallas de funcionamiento de computadoras, vinculados con la utilizaci�n de la Herramienta Digital.
