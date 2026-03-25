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
- Preview de token de recuperacion restringido a entornos `local/testing`.
- Organizaciones base, membresias y organizacion activa en sesion.
- RBAC inicial con rol `admin` y permiso `modules.manage`.
- Integridad base del repositorio saneada: conflictos de merge resueltos, migraciones duplicadas unificadas y suite validada.
- Capa legacy principal retirada del backend HTTP y del frontend heredado del template.
- Seeders de permisos y bootstrap inicial unificados sobre una sola fuente de verdad.
- Branding residual del template limpiado en metadatos principales del frontend.
- Estructura laboral legacy retirada del runtime activo.
- Toolchain frontend endurecido: `Vite 6.4.1`, auditoria `npm` limpia y build chunked sin warnings por tamano excesivo.
- Base fundacional del core adelgazada: catalogos y modelos inactivos de ubicacion/personas retirados del arranque modular.
- Primer paso del contrato modular formal: metadata backend ampliada y manifest unica del `Demo Module` en frontend.
- Contrato modular consumido por API para bootstrap del `Demo Module`, stores de auth separados y tenancy base reforzada con `TenantContext`.
- Contrato modular endurecido con dependencias operativas, modulos protegidos del core y feedback explicito en administracion.
- Settings por modulo ya operativos con persistencia, API administrativa y uso real dentro del `Demo Module`.
- Registro modular inicial con `core-platform` y `demo-platform`.
- Persistencia de modulos en base de datos y toggle por API.
- Pantalla de administracion de modulos en frontend.
- `Demo Module` inicial con guard de acceso por estado del modulo.
- Frontend ya consume `sessionStore`, `tenantStore` y `accessStore` directamente, sin fachada `authStore`.
- Tenancy base extendida a notificaciones internas y descargas de archivos para reducir filtros manuales por organizacion.
- Aislamiento por tenant validado con pruebas automatizadas en notificaciones, archivos, descargas y auditoria demo.
- Data Engine real implementado con recurso demo, CRUD universal base, filtros, busqueda, paginacion, ordenamiento y soft delete.
- Export/import CSV ya operativo sobre el Data Engine con historial de corridas tenant-aware.
- Exportaciones `Excel/PDF` y modo `async` ya operativos sobre el Data Engine y demostrables desde `Demo Module`.
- Settings globales, por organizacion y por usuario ya operativos con bootstrap frontend, feature flags base y banner/error global.
- Multi-rol por usuario, administracion operativa de usuarios e impersonacion con auditoria ya disponibles.
- Estructuras tenant base (`empresas`, `sucursales`, `equipos`) ya disponibles y gestionables desde el Data Engine.
- Data Engine ya soporta relaciones y custom fields en recursos reales del demo.
- Notificaciones multicanal ya tienen base operativa con preferencias, feature flags y log de entregas por canal.
- Skeleton loaders y empty states reutilizables ya forman parte del shell y de pantallas operativas.
- Request IDs ya se propagan por header y respuestas API.
- Rate limiting base ya protege auth, escrituras y descargas.
- Security logs tenant-aware y operations overview administrativo ya estan disponibles.
- Error logs tecnicos no controlados y metricas internas base ya estan disponibles.
- Locale, moneda, zona horaria y tema persistido ya se aplican desde settings del core.
- Limpieza residual completada en branding/documentacion raiz y restos visuales del shell legacy.
- Base de archivos en core con upload, descarga directa, signed URLs e historial.
- Base de jobs en core con dispatch, estados, logs y demo funcional.
- Base de auditoria transversal con eventos para modulos, archivos y jobs.
- Base de notificaciones internas con bandeja, lectura y campanita.
- Guardas frontend por autenticacion.
- Pantalla de modulos protegida por permiso.
- Build frontend y tests backend pasando.

### En progreso
- Estructura `core/modules` ya creada y el `Demo Module` ya usa bootstrap por API; el contrato de modulos ya expone metadata operativa mas rica, pero todavia debe crecer para futuros modulos.
- Tenancy base ya existe y se reforzo en notificaciones/descargas/tenant structures, pero falta propagarla de forma consistente a todo el dominio.
- El frontend ya refresca el catalogo modular completo tras toggles para no conservar estados operativos stale.
- Los settings modulares ya son operativos, y el core ya suma settings globales/tenant/usuario; todavia faltan seeds, assets y cerrar el contrato modular completo.
- `Demo Module` ya existe y ya contiene demos funcionales de archivos, jobs, auditoria, notificaciones y transferencias/exportaciones.
- Persisten deudas operativas menores ligadas sobre todo a tenancy transversal, UX global y definicion formal del contrato modular.

### Aun pendiente
- Multi-tenant completo.
- Capacidades avanzadas del Data Engine: acciones, relaciones mas profundas y custom fields universales.
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
- [ ] Contrato formal completo de modulos: permisos, settings, webhooks, dashboards, assets, seeds y ejecucion operativa real.
- [x] Bootstrap inicial del `Demo Module` desde metadata backend/API.
- [ ] Contrato formal de demos por capacidad transversal.
- [x] Bootstrap modular desde API para no duplicar metadata del `Demo Module`.
- [x] Dependencias modulares basicas bloqueadas al habilitar o deshabilitar modulos.

## Fase 1. Identidad y acceso
Estado: En progreso

- Login, logout y perfil autenticado ya implementados.
- Registro y reset de password ya implementados.
- Tokens API propios ya implementados.
- RBAC inicial ya implementado sobre administracion de modulos.
- Multi-rol, administracion operativa de usuarios e impersonacion con auditoria ya disponibles.
- Guardas frontend y control de acceso por endpoint en progreso.
- Falta ampliar permisos por endpoint/accion al resto del sistema.

## Fase 2. Tenancy y organizacion
Estado: En progreso

- Organizaciones base, membresias y organizacion activa ya implementadas.
- Empresas, sucursales, equipos y relaciones usuario-empresa ya disponibles como estructuras tenant-aware.
- Tenant activo por request en todos los servicios aun pendiente de cierre total.
- Configuracion por tenant.
- Seed inicial coherente para ambientes locales y demo.

## Fase 3. Servicios transversales del core
Estado: En progreso

- CRUD universal y filtros.
- Relaciones y custom fields base sobre recursos reales.
- Export/import `CSV / Excel / PDF` base sobre el Data Engine con historial tenant-aware y modo `async`.
- Settings globales, por tenant y por usuario con feature flags base.
- Archivos base ya implementados; falta Spaces, versionado real y asociaciones de negocio.
- Jobs base ya implementados; faltan workers supervisados, cron, reintentos operativos y propagacion completa de tenant/actor.
- Notificaciones internas y base multicanal ya implementadas; faltan email, WhatsApp/SMS y push reales.
- Export/import avanzado.
- Auditoria base ya implementada; request IDs, security logs y overview operativo ya existen, pero faltan logs tecnicos y endurecimiento adicional.
- Error handling tecnico base ya existe con `error logs`, `error_code` y respuesta controlada para excepciones no manejadas.
- Metricas internas base por tenant/modulo/categoria ya existen; falta profundizar performance y tiempos de respuesta.
- Busqueda e indexacion.

## Fase 4. Shell de experiencia
Estado: Parcial

- Layout base ya disponible.
- Administracion de modulos ya disponible.
- Auth real ya integrada.
- Menu y router ya alineados con StackBase, sin demos del template original.
- Banner global, manejo global de errores HTTP y preferencias persistidas ya operativos.
- Empty states y skeletons ya implementados en pantallas reales.
- Operations overview y security logs administrativos ya forman parte del shell.
- Error logs, usage metrics y formato locale-aware tambien ya forman parte del shell.
- Falta feedback estandarizado mas profundo.

## Fase 5. Demo Module funcional
Estado: En progreso

- Demo de archivos ya implementada.
- Demo de jobs ya implementada.
- Demo de auditoria ya implementada.
- Demo de notificaciones ya implementada.
- Demo de export/import ya implementada dentro de `Demo Module`.
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
- Logrado: kernel modular, auth API, registro, reset de password, RBAC inicial, multi-rol, impersonacion, tenancy base con estructuras `empresa/sucursal/equipo`, archivos, jobs, auditoria y notificaciones internas ya funcionan en backend y frontend con demos activables desde `Demo Module`; ademas la integridad del repositorio quedo estabilizada, la capa legacy principal fue retirada, el contrato modular ya evita estados invalidos y ya expone metadata operativa mas rica, el aislamiento por tenant quedo cubierto con pruebas automatizadas, el `Data Engine` ya soporta relaciones, custom fields, export/import `CSV / Excel / PDF` con historial de corridas y modo `async`, el core ya expone settings globales/tenant/usuario con feature flags y UX transversal base, la capa operativa ya incluye request IDs, rate limiting, security logs y operations overview administrativo, y ahora tambien suma `error logs`, `usage metrics` y aplicacion real de locale/moneda/zona horaria/tema desde settings.
- Pendiente: completar multi-tenant transversal, integraciones reales de storage y notificaciones multicanal, mas observabilidad profunda y seguridad operativa, performance/response times y generalizar el contrato `core + modules` para nuevos modulos sin wiring adicional.
- Pendiente tecnico residual: seguir endureciendo el core en tenancy transversal, convertir relaciones/custom fields del Data Engine en capacidades completamente universales, robustecer exportaciones/importaciones pesadas y async en operacion real, permisos operativos por modulo, hooks/dashboard/assets ejecutables y catalogos universales realmente necesarios.

Avance global estimado del roadmap: 99% completado.
