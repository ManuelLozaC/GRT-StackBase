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
- dependencias
- features

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
- Preview de recuperacion expuesto solo en `local/testing` para no mezclar helpers de desarrollo con runtime productivo.
- Integridad base saneada tras resolver conflictos de merge y unificar migraciones clave.
- Limpieza principal de deuda legacy: backend HTTP fuera de `api/v1` retirado y frontend sin vistas del template en la navegacion principal.
- Seeders iniciales y RBAC base alineados sin duplicidad de bootstrap.
- Branding principal del template removido de la shell frontend.
- Estructura laboral heredada retirada del runtime activo.
- Toolchain frontend alineado a una version segura de `Vite` con particion de chunks y auditoria `npm` limpia.
- Base fundacional del core reducida a organizaciones, usuarios, membresias y sesion; los catalogos de ubicacion/personas ya no viven por defecto en el arranque base.
- Organizaciones base y cambio de organizacion activa.
- Core de archivos con upload, descarga directa, signed URL e historial.
- Core de jobs con dispatch, ejecucion inmediata demo y trazabilidad basica.
- Core de auditoria con eventos transversales y consulta demo.
- Core de notificaciones internas con bandeja, lectura y contador basico.
- Base multicanal de notificaciones con preferencias, feature flags y log de entregas por canal.
- Request IDs propagados en header/respuesta API y rate limiting base por tipo de endpoint.
- Security logs tenant-aware y operations overview administrativo para troubleshooting del core.
- Data Engine universal con CRUD base, filtros, busqueda, paginacion, ordenamiento y soft delete sobre recurso demo.
- Data Engine con relaciones y custom fields sobre recursos reales.
- Export/import CSV sobre el Data Engine con historial tenant-aware de corridas.
- Exportaciones `Excel/PDF` y modo `async` sobre el Data Engine, con demo dedicada en el `Demo Module`.
- Settings globales, por organizacion y por usuario con feature flags base y bootstrap frontend.
- Multi-rol, administracion de usuarios e impersonacion con auditoria.
- Estructuras tenant base (`empresas`, `sucursales`, `equipos`) listas para reutilizacion.
- Registro de modulos.
- Persistencia de estado de modulos.
- Admin de modulos.
- Guard de acceso a modulos deshabilitados.
- `Demo Module` con landing y demos funcionales de notificaciones, archivos, jobs y auditoria.
- `Data Engine` con recurso demo operable y transferencias CSV trazables.
- `Demo Module` con demo especifica para transferencias, formatos `CSV / Excel / PDF` y corridas async.
- Metadata modular backend consumida por API para construir rutas y menu del `Demo Module`.
- Frontend modular reducido a registro local de vistas; ya no define metadata duplicada de navegacion.
- Metadata modular backend ampliada con `dependencies`, `permissions`, `settings`, `features` y `frontend.routes`.
- Dependencias modulares basicas bloqueadas para no habilitar/deshabilitar modulos en estados invalidos.
- Stores frontend consumidos directamente por responsabilidad: sesion, tenant y permisos.
- `TenantContext` backend compartido entre request autenticado, jobs, notificaciones internas y descargas base.
- Suite automatizada validando aislamiento por tenant en notificaciones, archivos, descargas y auditoria demo.
- Settings modulares operativos con persistencia, API administrativa y efecto real en el `Demo Module`.
- Banner global, preferencias persistidas y manejo global de errores HTTP ya forman parte del shell.

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
- Las ayudas de desarrollo deben quedar condicionadas por entorno para no contaminar produccion.
- Cada modulo debe poder declararse una sola vez por capa y evitar duplicacion de wiring manual.
- El backend debe ser la fuente de verdad de metadata modular; frontend solo resuelve vistas locales.
- La administracion no debe permitir activar modulos con dependencias rotas ni desactivar modulos protegidos del core.
- La experiencia operativa del core debe ser visible mediante demos funcionales, auditoria y estados reutilizables de UX.
- El runtime debe exponer trazabilidad minima por request y vistas operativas para soporte del tenant activo.
