# Revision del estado actual

> Diagnostico ejecutivo del proyecto al 2026-03-26, despues del cierre de la version base.

## Resumen ejecutivo

El proyecto ya puede considerarse una version base cerrada para iniciar nuevos sistemas sobre `Core Platform + Modules`.

La base actual ya cumple con lo principal que se definio durante el proyecto:

- backend Laravel 12 API-first
- frontend Vue 3 modular
- arquitectura local con Docker alineada a Droplets
- storage real en DigitalOcean Spaces
- push web real mediante FCM y email real mediante Resend
- historial operativo de entregas con retry manual para `email` y `push`, politica por proveedor y diagnostico operativo mas rico
- versionado real de archivos con historial por grupo, nueva version desde la demo y paquetes async para descargas pesadas
- cobertura RBAC mas fina sobre demo, operaciones, metricas y logs tecnicos
- Data Engine mas profundo con duplicado de registros, busqueda por relaciones y `custom_fields` filtrables/exportables/importables
- busqueda real con Meilisearch y reindex manual por recurso desde API/UI/Artisan
- deploy productivo endurecido: preserva `APP_KEY`, evita reseed destructivo, compila frontend a `dist` y reindexa todos los recursos buscables
- endurecimiento de seguridad base: autenticacion web con cookie `HttpOnly`, login con throttling mas estricto y webhooks con proteccion anti-replay
- endurecimiento adicional del canal web: los metodos mutables autenticados por cookie ya exigen header CSRF, y las URLs dinamicas navegables del frontend pasan por sanitizacion central para evitar esquemas inseguros
- politicas diferenciadas por canal ya formalizadas: `api bearer`, `web cookie`, `webhooks`, `signed URLs` y `push` ya no dependen de una sola regla generica, sino de configuracion central adaptada al riesgo y al tipo de transporte
- `TenantContext` mas expresivo: ya propaga alias de `empresa` y `asignacion_laboral_activa` en runtime, y la navegacion administrativa respeta permisos finos reales en menu y rutas
- convergencia funcional `organizacion = empresa` ya aterrizada en runtime visible: auth, settings y `TenantContext` usan `empresa/company` como lenguaje principal, manteniendo compatibilidad legacy solo donde hace falta
- runtime multiempresa/contexto ya es mas consistente en backend: servicios transversales y jobs restauran/consumen `companyId` como fuente preferente del contexto activo, aunque la persistencia siga usando `organizacion_id` por compatibilidad
- RBAC del core mas maduro: el shell ya separa permisos de `view` y `manage` para `modules`, `settings`, `integrations`, `users` y `roles`, evitando regalar acciones mutables solo por abrir una pantalla
- contrato modular mas estricto: `module settings` ya diferencia lectura (`modules.view`) de mutacion (`modules.manage`), OpenAPI queda como documentacion tecnica autenticada y el catalogo modular evita registrar rutas ambiguas cuando falta `permissionKey` explicita
- Data Engine ya no es una superficie `todo o nada`: separa `access`, `create`, `update`, `delete`, `import`, `export`, `duplicate` y `search.manage`, y el frontend refleja esas capacidades reales por recurso
- demo de jobs mas operativa en local: ya muestra estado de cola, `worker_hint`, auto-poll y pending counts sobre `database queue`
- jobs mas maduros para flujos reales: `core_job_runs` ya conserva politica de retry por tipo (`policy_key`, `max_attempts`, `backoff_schedule`, `retry_exhausted`, `next_retry_in_seconds`, `last_attempt_at`) y la demo expone esa lectura operativa
- suite frontend ya cubre stores, guards y componentes criticos como topbar, login, settings y administracion de usuarios
- shell endurecido con permisos minimos para `demo`, `data engine`, `documentacion tecnica` y `API tokens`
- `ModuleRegistry` ya no escribe metadata por simple lectura del catalogo; la sincronizacion del manifest hacia persistencia ocurre solo en flujos explicitos de bootstrap, listados administrativos y escrituras relacionadas
- vistas administrativas reales de `audit`, `security` y `error logs`, con filtros operativos para soporte e investigacion, mas correlacion por `request_id` y diffs de cambios de permisos/roles
- Data Engine ya recuerda por recurso columnas, busqueda, filtros, orden y tamano de pagina
- catalogos universales del core ya quedaron cerrados de forma explicita y documentada
- la regla de arquitectura del shell core ya quedo formalizada para que nuevos modulos no vuelvan a inflar la base con pantallas de negocio
- el `Demo Module` ya esta curado en tutoriales guiados, capacidades tecnicas del core y patrones UI/reuse
- el frontend ya cuenta con una primitive pequena de feedback para reducir wiring repetido de toasts de exito, warning y error en acciones operativas
- bootstrap oficial inicial
- tenancy base
- RBAC base y permisos contextuales iniciales
- Data Engine reutilizable
- CI operativa

## Validacion de integridad contra requerimientos

### Requerimientos cumplidos

- `Docker local + Droplets`:
  la arquitectura ya fue alineada con `app`, `web`, `worker`, `scheduler`, `db`, `redis` y `search`.
- `Laravel 12`:
  vigente y reflejado en codigo y documentacion.
- `PHP 8.3 como objetivo`:
  vigente en runtime Docker y CI.
- `organizacion = empresa`:
  decision ya documentada y mayormente aterrizada en runtime visible.
- `tenant aislado por cliente`:
  vigente como decision de arquitectura.
- `login + gestion de usuarios frontend/backend`:
  implementados.
- `bootstrap inicial real`:
  implementado con `GRT SRL`, `TalentHub` y `Manuel Loza`.
- `storage en Spaces`:
  implementado y validado con escritura, lectura y borrado reales.
- `CI`:
  operativa en GitHub Actions.
- `Canales reales de notificacion`:
  `push` y `email` ya pueden probarse end-to-end con proveedores reales.

### Requerimientos que quedan como evolucion, no como bloqueo de cierre base

- ampliar pruebas frontend
- seguir refinando experiencias avanzadas de archivos sobre una base ya funcional
- observabilidad, backups y despliegue automatizado

## Fortalezas reales

### Backend

- API versionada y amplia
- auth real con alias, impersonacion y tokens
- Data Engine con import/export, transfers, relaciones mas profundas, duplicado y `custom_fields` reutilizables
- settings por ambito
- webhooks, audit, logs, metrics y operaciones
- superficies administrativas de trazabilidad mas utiles para investigacion del tenant activo
- base de archivos y jobs reusable

### Frontend

- shell administrativo real
- guards y stores separados
- modulo demo activo como sandbox tecnico y biblioteca visual/didactica
- administracion real de modulos, usuarios, settings y operaciones

### Operacion

- Docker Compose ya modela `worker` y `scheduler`
- CI valida backend y frontend, incluyendo smoke tests de release
- Spaces ya esta integrado
- healthcheck operativo ya expone `database`, `redis`, `mail`, `queue` y `storage`
- documentacion operativa ya incluye guia explicita de despliegue seguro con Cloudflare, firewall, secretos y topologia recomendada

## Debilidades vigentes

### P1. Demo Module ya es una fortaleza, y ahora entra en etapa de evolucion, no de deuda

Hoy `demo-platform` ya cubre una porcion importante de lo esperado:

- notificaciones
- archivos
- jobs
- auditoria
- transfers
- showcase UI
- feedback
- forms
- data display
- async patterns
- layouts
- typography/content
- advanced inputs
- screen recipes

El siguiente nivel ya no es "tener demos", sino:

- conectar mas ejemplos con datos reales del core cuando aporte valor
- seguir puliendo microcopy, criterio de uso y notas de implementacion
- mantener coherencia visual y pedagogica a medida que el modulo siga creciendo

### P1. La convergencia `organizacion = empresa` ya puede considerarse cerrada a nivel funcional

La capa visible del sistema ya piensa en `empresa` como concepto principal. El naming `organizacion` queda como alias tecnico de compatibilidad en tablas, relaciones y algunos servicios internos para evitar refactors destructivos sin valor de negocio.

### P1. El runtime tenant/contexto ya quedo razonablemente consistente para la base actual

Los servicios del core y los jobs principales ya restauran y consumen `companyId` como lenguaje preferente del contexto activo. A nivel de storage seguimos usando `organizacion_id`, pero ya no se arrastra ese naming como fuente principal de decision en runtime. Eso baja bastante el riesgo de divergencia entre auth, jobs, archivos, notificaciones, auditoria y data engine.

### P1. La red de pruebas frontend ya cubre el shell critico, pero todavia puede seguir creciendo

La base ya tiene cobertura sobre stores, guards, topbar, login, settings y administracion de usuarios. La siguiente inversion de calidad puede ir a vistas secundarias y flujos visuales mas ricos, ya no al nucleo minimo del shell.

## Consistencia general

### Clara y consistente

- vision del stack
- arquitectura core + modules
- fuente de verdad documental
- estrategia local -> Droplet
- decision de dominio organizacional
- uso de Spaces
- `Demo Module` como biblioteca viva y didactica

### A vigilar en siguientes iteraciones

- refinamiento continuo del Demo Module como biblioteca unificada
- observabilidad mas profunda
- automatizacion de despliegues
- mas pruebas frontend sobre vistas secundarias y flujos visuales enriquecidos

## Conclusion

La version base ya esta cerrada y usable. Desde aqui el proyecto entra en una etapa nueva:

- onboarding de nuevos proyectos
- evolucion guiada del `Demo Module`
- mejoras opcionales y de roadmap sin reabrir deuda fundacional

La deuda base ya esta cerrada. La evolucion futura vive en [`docs/roadmap.md`](/D:/Desarrollo/GRT-StackBase/docs/roadmap.md).
