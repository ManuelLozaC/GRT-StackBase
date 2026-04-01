# Roadmap StackBase

> Plan maestro para llevar el proyecto desde plataforma avanzada hasta stack base cerrado.

## Vision

StackBase no debe crecer como un sistema unico con features mezcladas. Debe resolver las capacidades transversales una sola vez en el core y permitir instalar multiples modulos de negocio sobre la misma base tecnica.

## Estado actual

Fecha de referencia: `2026-03-26`

### Ya implementado

- Docker Compose con backend, frontend, MySQL, Redis y Meilisearch
- backend Laravel 12 con API `v1`
- auth base: login, logout, `me`, registro y reset
- login por correo o alias
- metadata modular y administracion de modulos
- Data Engine con CRUD, relaciones, custom fields e import/export
- recursos base del dominio ya expuestos en backend por Data Engine
- settings globales, por organizacion y por usuario
- multi-rol, impersonacion y administracion operativa de usuarios
- observabilidad base: request IDs, logs, metricas y operations overview
- webhooks salientes y entrantes
- OpenAPI JSON

### En progreso

- endurecimiento transversal del core ya cerrado
- refinamiento del contrato modular
- evolucion del `Demo Module` como biblioteca viva
- calidad automatizada y operacion continua sobre Droplets

### Siguiente evolucion clave

- seguir refinando `Demo Module` como biblioteca viva, didactica y consistente del stack
- automatizar observabilidad y despliegue continuo sobre la topologia de Droplet
- seguir endureciendo tenancy, auditoria y seguridad en las superficies restantes
- evaluar guardado de filtros y otras mejoras no criticas del Data Engine
- evolucionar el core sin reabrir deuda estructural de la version base
- seguir puliendo feedback optimista/pesimista, fallbacks de UX y recipes reutilizables cuando un modulo real lo justifique
- definir si algun modulo real obliga a encriptar datos funcionales sensibles dentro del dominio
- evaluar PWA solo si el uso movil real del producto lo vuelve rentable

### Reciente

- bootstrap oficial inicial ya implementado con `GRT SRL`, `TalentHub` y `Manuel Loza`
- `DatabaseSeeder` ya usa una sola entrada oficial de instalacion
- pruebas de bootstrap idempotente ya agregadas al backend
- recursos base del dominio ya existen en backend para oficinas, personas y asignaciones laborales
- la pantalla administrativa de usuarios ya permite alta, edicion, activacion y reset de contrasena
- el frontend ya puede gestionar los recursos base del dominio desde Data Engine con acceso rapido y soporte correcto de booleanos y fechas
- pipeline CI operativo ya valida backend Laravel y frontend en GitHub Actions
- la suite frontend ya cubre stores, guards y componentes criticos del shell
- DigitalOcean Spaces ya esta integrado y validado con escritura, lectura y borrado reales
- Docker Compose ya incluye `worker`, `scheduler` y healthchecks base alineados con Droplet
- `demo-platform` ya evoluciono a showcase tecnico + UI con recipes y guia didactica en varias demos
- el `Demo Module` ya tiene una capa didactica y una presentacion visual mas consistente en demos UI y tecnicas clave
- el Data Engine ya usa Meilisearch con reindex manual por recurso en API, UI y Artisan

## Fases vigentes

### Fase 0. Orden documental

- dejar una sola fuente de verdad para backlog y diagnostico
- retirar o reconvertir documentos viejos
- corregir encoding y narrativa desalineada

### Fase 1. Dominio base

- cerrar `organizacion = empresa`
- definir oficinas, personas, cargos, areas, divisiones y asignaciones laborales
- documentar el escenario multi-sucursal con roles distintos por oficina

### Fase 2. Bootstrap oficial

- semilla automatica e idempotente
- Manuel Loza + GRT SRL + TalentHub
- catalogos base de Bolivia

### Fase 3. Tenancy y RBAC

- aislamiento consistente por tenant
- permisos por contexto operativo cuando aplique
- endurecimiento de auth, sesiones y acceso administrativo

### Fase 4. Dominio administrativo base

- CRUDs reales de organizaciones, oficinas, personas, usuarios y asignaciones
- formularios y tablas base en frontend

### Fase 5. Operacion y release

- paridad local -> Droplet
- workers, healthchecks y endurecimiento operativo
- pruebas, endurecimiento operativo y release inicial del stack

### Fase 6. Evolucion guiada

- convertir el `Demo Module` en referencia de implementacion para nuevos proyectos
- automatizar despliegue y observabilidad
- seguir ampliando checks de release sin inflar la base
- seguir endureciendo tenancy y seguridad sin romper la simplicidad del core

### Fase 7. Evolucion opcional no bloqueante

- profundizar recipes del `Demo Module` segun necesidades reales de onboarding
- mejorar feedback optimista/pesimista y fallbacks UX donde los modulos lo pidan
- profundizar exportaciones pesadas async si algun modulo requiere mayor observabilidad o notificacion
- evaluar encriptacion de datos sensibles segun el dominio concreto del siguiente modulo
- evaluar PWA solo si el uso movil recurrente del sistema lo justifica

## Criterio de exito

Un nuevo sistema debe poder ensamblarse con:

- `Core Platform`
- uno o mas `Modules`
- configuracion por tenant
- menus y permisos dinamicos
- bootstrap inicial reproducible

El backlog detallado vive en [`docs/pendientes.md`](/D:/Desarrollo/GRT-StackBase/docs/pendientes.md).
