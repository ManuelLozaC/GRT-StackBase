<<<<<<< HEAD
# ROADMAP StackBase
> Plan maestro para convertir el proyecto en una `Core Platform` reutilizable con modulos plug-in y un `Demo Module` administrable.

## Vision
StackBase no debe crecer como un sistema unico con features mezcladas. Debe resolver las capacidades transversales una sola vez en el core y permitir instalar multiples modulos de negocio sobre la misma base tecnica.

## Estado actual
Fecha de referencia: `2026-03-25`

### Ya implementado
- Docker Compose con backend, frontend, MySQL, Redis y Meilisearch.
- Backend Laravel 12 con API versionada base.
- Respuesta JSON estandar `{ estado, datos, mensaje, meta, errores }`.
- Autenticacion API inicial con `login / logout / me`.
- Registro y recuperacion/reset de password ya implementados.
- Organizaciones base, membresias y organizacion activa en sesion.
- RBAC inicial con rol `admin` y permiso `modules.manage`.
- Registro modular inicial con `core-platform` y `demo-platform`.
- Persistencia de modulos en base de datos y toggle por API.
- Pantalla de administracion de modulos en frontend.
- `Demo Module` inicial con guard de acceso por estado del modulo.
- Base de archivos en core con upload, descarga directa, signed URLs e historial.
- Base de jobs en core con dispatch, estados, logs y demo funcional.
- Base de auditoria transversal con eventos para modulos, archivos y jobs.
- Base de notificaciones internas con bandeja, lectura y campanita.
- Guardas frontend por autenticacion.
- Pantalla de modulos protegida por permiso.
- Build frontend y tests backend pasando.

### En progreso
- Estructura `core/modules` ya creada, pero el contrato de modulos todavia debe crecer.
- Tenancy base ya existe, pero falta propagarla de forma consistente a modelos, jobs, archivos y auditoria.
- `Demo Module` ya existe y ya contiene demos funcionales de archivos, jobs, auditoria y notificaciones. Sigue pendiente export/import.

### Aun pendiente
- Multi-tenant completo.
- CRUD universal real.
- Archivos, notificaciones, jobs avanzados, auditoria y seguridad.

## Fases
## Fase 0. Kernel modular
Estado: En progreso

- [x] Separacion inicial `backend/app/Core`, `backend/app/Modules`, `frontend/src/core`, `frontend/src/modules`.
- [x] API base `v1`.
- [x] Registro de modulos instalados.
- [x] Persistencia del estado de modulos.
- [x] Pantalla de administracion para habilitar o deshabilitar modulos.
- [x] `Demo Module` inicial habilitable desde administracion.
- [ ] Contrato formal completo de modulos: permisos, settings, webhooks, dashboards, assets, seeds.
- [ ] Contrato formal de demos por capacidad transversal.

## Fase 1. Identidad y acceso
Estado: En progreso

- Login, logout y perfil autenticado ya implementados.
- Registro y reset de password ya implementados.
- Tokens API propios ya implementados.
- RBAC inicial ya implementado sobre administracion de modulos.
- RBAC con multiples roles por usuario pendiente de ampliar.
- Guardas frontend y control de acceso por endpoint en progreso.
- Impersonacion admin -> usuario con auditoria.

## Fase 2. Tenancy y organizacion
Estado: En progreso

- Organizaciones base, membresias y organizacion activa ya implementadas.
- Empresas, sucursales, equipos y relaciones usuario-empresa.
- Tenant activo por request en todos los servicios aun pendiente.
- Configuracion por tenant.
- Seed inicial coherente para ambientes locales y demo.

## Fase 3. Servicios transversales del core
Estado: En progreso

- CRUD universal y filtros.
- Archivos base ya implementados; falta Spaces, versionado real y asociaciones de negocio.
- Jobs base ya implementados; faltan workers supervisados, cron, reintentos operativos y propagacion completa de tenant/actor.
- Notificaciones internas base ya implementadas; faltan email, WhatsApp/SMS, push y preferencias por usuario.
- Export/import.
- Auditoria base ya implementada; faltan logs tecnicos, correlation IDs, vistas operativas y seguridad avanzada.
- Busqueda e indexacion.

## Fase 4. Shell de experiencia
Estado: Parcial

- Layout base ya disponible.
- Administracion de modulos ya disponible.
- Falta auth real, empty states, skeletons, manejo global de errores y preferencias persistidas.

## Fase 5. Demo Module funcional
Estado: En progreso

- Demo de archivos ya implementada.
- Demo de jobs ya implementada.
- Demo de auditoria ya implementada.
- Demo de notificaciones ya implementada.
- Demo de export/import.
- Demo de auditoria y logs.

Cada demo debe permitir validar la capacidad tecnica antes de usarla en modulos de negocio.

## Fase 6. Primer modulo vertical
Estado: Pendiente

- Elegir un modulo piloto.
- Construirlo usando solo servicios del core.
- Ajustar contratos modulares con evidencia real, no teorica.

## Criterio de exito
Un nuevo sistema debe poder ensamblarse con:
- `Core Platform`
- uno o mas `Modules`
- configuracion por tenant
- menus y permisos dinamicos
- `Demo Module` como banco de pruebas tecnico

El backlog detallado vive en `docs/pendientes.md`.

## Resumen actual
- Logrado: kernel modular, auth API, registro, reset de password, RBAC inicial, tenancy base, archivos, jobs, auditoria y notificaciones internas ya funcionan en backend y frontend con demos activables desde `Demo Module`.
- Pendiente: completar multi-tenant transversal, CRUD universal, export/import, integraciones de storage y notificaciones multicanal, mas observabilidad y seguridad operativa.

Avance global estimado del roadmap: 50% completado.
=======
# ROADMAP: Construcción del StackBase
Guía de Implementación | Estado: Inicial

## Fase 1: Infraestructura (Docker + Droplets)
- Docker Compose: configuración oficial para desarrollo local.
- Dockerfiles: construcción de imágenes para Laravel 12, PHP 8.3 objetivo y Node 20.
- Nginx: configuración para API y SPA en local y como base de producción.
- Persistencia: volúmenes para base de datos, search y logs donde aplique.
- Producción: criterio de despliegue en DigitalOcean Droplets manteniendo la misma arquitectura lógica del entorno local.

## Fase 2: Estructura Base (Backend)
- Laravel 12: instalación limpia y configuración de archivos `.env`.
- Clases maestras: `ModeloBase.php`, `AccionBase.php`, DTOs, requests y recursos API.
- Multi-inquilino: implementación de global scopes por `organizacion_id`.
- Integración Spaces: configuración del driver S3 para almacenamiento en la nube.

## Fase 3: Seguridad y Datos
- Esquema DB: migraciones de organizaciones, empresas, oficinas, personas, usuarios, perfiles y adjuntos.
- Jerarquía: implementación de roles y permisos con Spatie.
- Autenticación: configuración de Laravel Sanctum para login y sesión API.
- Seeders: creación del superusuario inicial y catálogos base cuando la instalación sea nueva.
- Gestión de usuarios: CRUD base en backend con asignación de empresa, oficina, roles y estado.

## Fase 4: Interfaz Base (Frontend)
- Entorno Vue 3: inicialización con Vite, Pinia y Vue Router.
- Layouts: creación de `AuthLayout` y `AppLayout`.
- Login: pantalla de acceso funcional conectada al backend.
- Gestión de usuarios: listado, alta, edición, activación/desactivación y asignación de roles.
- Template UI: sidebar, navbar, breadcrumbs y componentes base.
- Cliente API: configuración de Axios con interceptores de seguridad.

## Fase 5: Funcionalidades BI
- Procesos: configuración de colas con Redis para tareas pesadas.
- Búsqueda: sincronización de modelos con Meilisearch.
- Reportes: sistema de exportación masiva hacia Spaces.
- Notificaciones: implementación de tiempo real con Laravel Reverb.

## Fase 6: Calidad y Despliegue
- Testing: pruebas base con Pest (back) y Vitest (front) si se confirma como estándar.
- CI/CD: configuración de GitHub Actions para despliegue automático.
- Documentación: generación de Swagger/OpenAPI para la API.
>>>>>>> 7e73f0a9cd3fbae4dc50a3da8e769c2a38178ab3
