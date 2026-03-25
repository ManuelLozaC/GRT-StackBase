# Roadmap StackBase

> Plan maestro para llevar el proyecto desde plataforma avanzada hasta stack base cerrado.

## Vision

StackBase no debe crecer como un sistema unico con features mezcladas. Debe resolver las capacidades transversales una sola vez en el core y permitir instalar multiples modulos de negocio sobre la misma base tecnica.

## Estado actual

Fecha de referencia: `2026-03-25`

### Ya implementado

- Docker Compose con backend, frontend, MySQL, Redis y Meilisearch
- backend Laravel 12 con API `v1`
- auth base: login, logout, `me`, registro y reset
- metadata modular y administracion de modulos
- Data Engine con CRUD, relaciones, custom fields e import/export
- settings globales, por organizacion y por usuario
- multi-rol, impersonacion y administracion base de usuarios
- observabilidad base: request IDs, logs, metricas y operations overview
- webhooks salientes y entrantes
- OpenAPI JSON

### En progreso

- tenancy transversal en todo el dominio
- endurecimiento del contrato modular
- cierre del dominio base reutilizable
- bootstrap oficial de instalacion
- integracion real con Spaces

### Pendiente clave

- aplicar en codigo la decision `organizacion = empresa`
- cerrar modelo de oficinas, personas y asignaciones laborales
- reemplazar bootstrap demo por bootstrap oficial
- agregar pruebas frontend y pipeline de calidad
- documentar despliegue real en Droplets

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
- Spaces, workers, healthchecks
- pruebas, CI y release inicial del stack

## Criterio de exito

Un nuevo sistema debe poder ensamblarse con:

- `Core Platform`
- uno o mas `Modules`
- configuracion por tenant
- menus y permisos dinamicos
- bootstrap inicial reproducible

El backlog detallado vive en [`docs/pendientes.md`](/D:/Desarrollo/GRT-StackBase/docs/pendientes.md).
