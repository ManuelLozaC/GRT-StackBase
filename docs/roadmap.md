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
 - Persisten deudas operativas menores ligadas sobre todo a decisiones de alcance del core y a mejoras incrementales de UX/tenancy.

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
- Auth real ya integrada.
- Menu y router ya alineados con StackBase, sin demos del template original.
- Faltan empty states, skeletons, manejo global de errores y preferencias persistidas.

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
- Logrado: kernel modular, auth API, registro, reset de password, RBAC inicial, tenancy base, archivos, jobs, auditoria y notificaciones internas ya funcionan en backend y frontend con demos activables desde `Demo Module`; ademas la integridad del repositorio quedo estabilizada, la capa legacy principal fue retirada y todo quedo verificado con tests/build.
- Pendiente: completar multi-tenant transversal, CRUD universal, export/import, integraciones de storage y notificaciones multicanal, mas observabilidad y seguridad operativa, y cerrar la definicion final de los catalogos base que siguen en evaluacion.
- Pendiente tecnico residual: seguir endureciendo el core en tenancy transversal, CRUD generico y delimitacion final de catalogos como `oficinas/personas`.

Avance global estimado del roadmap: 70% completado.
