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
- Registro, recuperacion y reset de password.
- Integridad base saneada tras resolver conflictos de merge y unificar migraciones clave.
- Limpieza principal de deuda legacy: backend HTTP fuera de `api/v1` retirado y frontend sin vistas del template en la navegacion principal.
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
- La deuda legacy fuera del core debe reducirse de forma explicita hasta converger en la arquitectura modular.
