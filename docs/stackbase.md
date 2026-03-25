<<<<<<< HEAD
# STACKBASE
> Arquitectura maestra del proyecto.

## Objetivo
Construir una plataforma base reutilizable para multiples sistemas, donde las capacidades genericas vivan en el core y las necesidades de negocio entren como modulos plug-in.

## Arquitectura general
### Core Platform
Resuelve capacidades compartidas:
- identidad y acceso
- tenancy
- configuracion
- archivos
- jobs
- notificaciones
- auditoria
- seguridad
- API base
- UX transversal

### Modules
Cada modulo puede declarar:
- rutas
- pantallas
- menus
- permisos
- migraciones
- settings
- jobs
- dashboards

### Demo Module
Modulo especial orientado a pruebas tecnicas del core.

Su objetivo es:
- validar capacidades genericas antes de usarlas en negocio
- servir para QA tecnico
- ayudar al onboarding
- poder activarse o desactivarse desde administracion

## Stack tecnologico actual
| Capa | Tecnologia |
| :--- | :--- |
| Infraestructura | Docker Compose |
| Backend | PHP 8.3 + Laravel 12 |
| Frontend | Vue 3 + Vite + PrimeVue |
| Base de datos | MySQL 8 |
| Cache / Jobs | Redis |
| Busqueda | Meilisearch |
| Storage | S3 compatible / DigitalOcean Spaces |
| Documentacion API | L5 Swagger |

## Implementado hoy
- API `v1` base.
- Healthcheck.
- Login, logout y `me`.
- Organizaciones base y cambio de organizacion activa.
- Core de archivos con upload, descarga directa, signed URL e historial.
- Core de jobs con dispatch, ejecucion inmediata demo y trazabilidad basica.
- Core de auditoria con eventos transversales y consulta demo.
- Core de notificaciones internas con bandeja, lectura y contador basico.
- Registro de modulos.
- Persistencia de estado de modulos.
- Admin de modulos.
- Guard de acceso a modulos deshabilitados.
- `Demo Module` con landing y demos funcionales de notificaciones, archivos, jobs y auditoria.

## Contenedores previstos
- `app`: backend Laravel
- `web`: nginx
- `db`: MySQL
- `redis`: colas y cache
- `search`: Meilisearch
- `frontend`: Vite dev server

## Principios de crecimiento
- El core no contiene logica de negocio especifica.
- Los modulos consumen servicios del core.
- Las funcionalidades genericas importantes deben tener demo.
- La documentacion debe reflejar estado real del codigo.
=======
# STACKBASE: Arquitectura Maestra
> Versión objetivo: 2026

## 1. Resumen Tecnológico
| Capa | Tecnología |
| :--- | :--- |
| **Infraestructura** | Docker para desarrollo local + DigitalOcean Droplets para producción + Spaces S3 |
| **Backend** | Laravel 12 + PHP 8.3 objetivo (compatibilidad aceptable con PHP 8.2) |
| **Frontend** | Vue 3 + Vite |
| **Datos / BI** | MySQL 8 + Meilisearch |
| **Procesos** | Redis + Laravel Reverb |

## 2. Arquitectura de despliegue

### Desarrollo local
1. **Docker Compose** como forma oficial de levantar el stack completo.
2. **`app-server`** para PHP-FPM / Laravel.
3. **`web`** para Nginx como punto de entrada.
4. **`db-mysql`** para persistencia transaccional.
5. **`redis-queue`** para colas, cache y procesos asincrónicos.
6. **`meilisearch-engine`** para búsqueda.
7. **`frontend`** para Vite durante desarrollo.

### Producción
1. **DigitalOcean Droplets** como entorno objetivo de despliegue.
2. **Nginx + PHP-FPM + servicios auxiliares en contenedores** manteniendo la misma arquitectura lógica de local.
3. **DigitalOcean Spaces** para almacenamiento de archivos.
4. **MySQL, Redis y Meilisearch** como servicios persistentes del stack.

## 3. Almacenamiento de Objetos (DigitalOcean Spaces)
- **Cero almacenamiento local permanente:** los archivos de usuario no se guardan de forma persistente en el Droplet.
- **Driver S3:** se utiliza el driver de Laravel compatible con Spaces.
- **Persistencia:** los archivos sobreviven a reinicios, recreación de contenedores y despliegues.

## 4. Ciclo de Vida (CI/CD)
- **GitHub Actions**: validación de estilo, análisis estático, pruebas, build y despliegue al Droplet.

## 5. Núcleo funcional obligatorio del stack base

El stack base debe incluir como mínimo:

- Autenticación con login y gestión de sesión.
- Gestión de usuarios en backend y frontend.
- Multi-tenancy por organización/empresa.
- Soporte para oficinas/sucursales y ubicación base.
- Datos semilla mínimos para primer arranque en instalaciones nuevas.
>>>>>>> 7e73f0a9cd3fbae4dc50a3da8e769c2a38178ab3
