# StackBase

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
- integraciones
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
- actuar como catalogo vivo de ejemplos de UI y patrones de implementacion del stack

Debe cubrir dos frentes:

- demos tecnicas del core: archivos, jobs, auditoria, notificaciones, transfers, settings, seguridad
- demos de experiencia/UI: toasts, modals, formularios, inputs, datepickers, tablas, estados vacios, validaciones, banners, confirmaciones y patrones de layout

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

## Decisiones vigentes de dominio

- `organizacion = empresa`
- cada cliente sera un tenant aislado
- una organizacion puede tener multiples oficinas o sucursales
- una persona puede tener multiples asignaciones laborales
- una misma persona puede tener distintos roles, jefes, aprobadores y permisos segun la oficina

## Implementado hoy

- API `v1` base
- login, logout, `me`, registro y reset de password
- organizaciones y organizacion activa
- metadata modular por API
- Data Engine con CRUD, relaciones profundas, `custom_fields`, duplicado, busqueda real con Meilisearch y reindexacion operativa
- settings globales, por organizacion y por usuario
- multi-rol, impersonacion y administracion base de usuarios
- request IDs, rate limiting, logs de seguridad y metricas
- webhooks y OpenAPI JSON

## Principios de crecimiento

- el core no contiene logica de negocio especifica
- los modulos consumen servicios del core
- las funcionalidades genericas importantes deben tener demo
- el `Demo Module` debe comportarse como un mini styleguide operativo del producto
- la documentacion debe reflejar el estado real del codigo
- el backend debe ser la fuente de verdad de metadata modular
- la documentacion del repo debe tener una sola fuente de verdad para backlog, diagnostico y plan de cierre

## Catalogos universales del core

El core solo debe soportar de forma explicita catalogos que probablemente reutilizaran multiples modulos de negocio.

Hoy el contrato visible del core queda en:

- `Empresas`
- `Oficinas`
- `Equipos`
- `Personas`
- `Divisiones`
- `Areas`
- `Cargos`
- `Asignaciones laborales`

La definicion operativa vive tambien en [`backend/config/core_catalogs.php`](/D:/Desarrollo/GRT-StackBase/backend/config/core_catalogs.php).

Regla:

- si una entidad solo describe estructura organizativa o identidad base, puede vivir en el core
- si una entidad necesita workflow, SLA, aprobaciones, tablero o UX propia, debe vivir en un modulo

Ejemplos que no deben entrar al core por defecto:

- `Leads`
- `Noticias`
- `Tickets`
- `Pedidos`
- `Cobranzas`

## Regla de arquitectura del shell core

El shell core debe quedarse pequeno, claro y transversal.

Debe incluir:

- autenticacion
- contexto de empresa
- administracion de usuarios, roles y permisos
- settings del sistema
- modulos
- Data Engine para catalogos universales
- observabilidad tecnica
- documentacion tecnica
- integraciones transversales

No debe absorber:

- bandejas o dashboards de negocio
- workflows especificos
- listados operativos exclusivos de un modulo
- formularios ricos que solo sirven a un dominio particular

Cuando aparezca una duda, la decision correcta por defecto es:

- primero preguntarse si mas de un modulo usaria esa capacidad
- si la respuesta es no, debe nacer fuera del core
