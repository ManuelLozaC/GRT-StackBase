# Plan de trabajo para finalizar el proyecto

> Plan operativo para llevar StackBase desde "plataforma avanzada en construccion" hasta "stack base listo para iniciar nuevos productos".

## Objetivo de cierre

El proyecto se considera finalizado cuando cumpla estas condiciones:

- se levanta desde cero en local con Docker sin pasos ambiguos
- puede desplegarse en Droplets sin rediseñar arquitectura
- tiene bootstrap real del dominio base y credenciales iniciales correctas
- tenancy y RBAC estan cerrados para el dominio base
- storage real usa DigitalOcean Spaces
- existe una sola fuente de verdad documental
- backend y frontend tienen validaciones automaticas suficientes para evitar regresiones criticas

## Principios de ejecucion

- no abrir nuevos frentes si el contrato base del dominio sigue abierto
- todo cambio funcional debe reflejarse en documentacion y seeders
- local y produccion deben mantener la misma logica de servicios
- el core debe cerrarse antes de ampliar modulos de negocio
- una tarea no se marca terminada si no tiene validacion y documentacion minima

## Fase 0. Orden y fuente de verdad

### Objetivo

Eliminar contradicciones del repo y dejar una base confiable para ejecutar el resto del trabajo.

### Tareas

- dejar `docs/pendientes.md` como backlog maestro operativo
- archivar o reconvertir `pending.md` raiz para que no compita con el backlog real
- corregir encoding a UTF-8 en docs y archivos de configuracion visibles
- normalizar archivos de entorno (`.env.example` y nombres duplicados)
- actualizar `local.md` para que coincida con rutas, credenciales y runtime reales
- agregar indice documental corto en `README.md` o en `docs/`

### Criterio de salida

- no hay dos documentos activos que se contradigan
- cualquier nuevo integrante puede entender estado real, backlog real y flujo local sin interpretar

## Fase 1. Cierre del dominio base reutilizable

### Objetivo

Definir y aterrizar el contrato minimo que todo nuevo sistema heredara desde este stack.

### Tareas

- aplicar en codigo y migraciones la decision `organizacion = empresa`
- definir modelo base de:
  - organizaciones/tenants
  - empresas cliente
  - sucursales/oficinas
  - personas
  - usuarios
  - areas/divisiones
  - cargos
  - asignaciones laborales
  - jefaturas y aprobadores por contexto operativo
- documentar escenario clave:
  - un usuario puede tener diferentes cargos, jefes, aprobadores y permisos por sucursal
- decidir que catalogos viven en el core y cuales son opcionales
- crear ADR de dominio organizacional y tenancy

### Entregables

- modelo documentado en `docs/modelo_dominio.md`
- migraciones definitivas del dominio base
- relaciones Eloquent y contratos JSON asociados

### Criterio de salida

- ya no quedan dudas de modelado que bloqueen CRUDs ni permisos

## Fase 2. Bootstrap real de instalacion

### Objetivo

Garantizar que una instalacion nueva deja el sistema listo para usar y validar.

### Tareas

- rehacer `InstalacionBaseSeeder` para crear:
  - organizacion/empresa base oficial
  - oficina `TalentHub`
  - persona `Manuel Loza`
  - usuario inicial con password segura por defecto
  - roles/permisos base
  - membresias y organizacion activa
- asegurar idempotencia real del bootstrap
- alinear `local.md`, `README.md` y credenciales de primer acceso
- agregar pruebas de seeders y primer arranque

### Criterio de salida

- `migrate:fresh --seed` deja el stack en un estado verificable y consistente con la documentacion

Estado actual:

- base oficial inicial ya implementada
- pendiente: enriquecer bootstrap con el dominio completo de personas, oficinas y estructura laboral

## Fase 3. Tenancy y RBAC completos para el dominio base

### Objetivo

Cerrar el nucleo de seguridad y aislamiento sobre el modelo real.

### Tareas

- extender tenancy a todas las entidades del dominio base
- definir bypass administrativo controlado
- cubrir jobs, imports/exports, webhooks y logs con contexto tenant
- ampliar permisos por endpoint y por accion
- soportar permisos por asignacion laboral/sucursal cuando aplique
- definir como conviven:
  - roles globales
  - roles por organizacion
  - permisos por asignacion laboral
  - aprobadores/jefaturas
- agregar pruebas de aislamiento y permisos sobre escenarios reales

### Criterio de salida

- no existe accion sensible del dominio base que dependa de supuestos manuales en lugar de tenancy/RBAC formales

## Fase 4. Gestion base de usuarios y estructura organizacional

### Objetivo

Cerrar el primer paquete de negocio reutilizable que todo sistema podra usar encima del core.

### Tareas backend

- CRUD de organizaciones/empresas si aplica
- CRUD de sucursales/oficinas
- CRUD de personas
- CRUD de usuarios
- CRUD de areas/divisiones/cargos
- CRUD de asignaciones laborales
- endpoints para activar/desactivar usuarios, resetear password y cambiar contexto operativo

### Tareas frontend

- vistas administrativas para las entidades base
- formularios y tablas consistentes
- gestion de asignaciones por oficina/sucursal
- visualizacion clara de jefe, aprobador, cargo, area y permisos efectivos

### Criterio de salida

- ya se puede administrar el dominio base sin tocar BD manualmente

## Fase 5. Infraestructura operativa local y Droplets

### Objetivo

Cerrar la arquitectura para que local y produccion compartan la misma logica.

### Tareas

- completar compose con healthchecks
- definir servicio worker para colas
- revisar necesidad de servicio scheduler
- formalizar imagen/runtime del frontend segun entorno
- documentar topologia de Droplet:
  - `web`
  - `app`
  - `worker`
  - `db`
  - `redis`
  - `search`
- documentar proxy/TLS con Nginx tradicional
- documentar estrategia de secrets y backups

### Criterio de salida

- existe un documento claro de despliegue y una topologia estable para pasar de local a Droplet

## Fase 6. Storage real, colas y servicios transversales

### Objetivo

Cerrar los servicios genericos que el stack promete como parte del core.

### Tareas

- integrar DigitalOcean Spaces de extremo a extremo
- definir convencion oficial de paths y metadatos de adjuntos
- asociar archivos a entidades reales
- endurecer flujos async pesados
- formalizar workers, retries y backoff
- revisar Meilisearch y decidir alcance inicial real
- evaluar si tiempo real entra en esta primera version o se difiere con ADR

### Criterio de salida

- archivos y procesos async funcionan con la misma filosofia operativa que usara produccion

## Fase 7. Calidad automatica y pruebas

### Objetivo

Reducir riesgo de regresion antes del cierre.

### Tareas

- consolidar backend:
  - tests de auth
  - tests de tenancy
  - tests de seeders
  - tests de RBAC
  - tests de dominio base
- incorporar pruebas frontend:
  - stores
  - router guards
  - flujos auth
  - pantallas administrativas criticas
- agregar analisis estatico backend
- definir type checking frontend si se decide TypeScript o mantener JS endurecido
- endurecer pipeline CI ya operativo para lint, test y build

### Criterio de salida

- existe red de seguridad automatica para los flujos base del stack

## Fase 8. Cierre documental y release 1.0 del stack

### Objetivo

Dejar el proyecto listo para ser usado por otro equipo como base seria.

### Tareas

- reescribir `README.md` como onboarding real
- actualizar `docs/stackbase.md`, `docs/roadmap.md` y `docs/modelo_dominio.md`
- formalizar `docs/contrato_modulos.md`
- crear ADRs clave:
  - tenancy
  - dominio organizacional
  - storage
  - frontend typing
  - estrategia de testing
  - despliegue en Droplets
- definir checklist de "nuevo proyecto sobre StackBase"
- versionar release inicial del stack

### Criterio de salida

- una persona nueva puede levantar, entender y extender la base sin depender del autor original

## Orden recomendado de ejecucion

1. Fase 0
2. Fase 1
3. Fase 2
4. Fase 3
5. Fase 4
6. Fase 5
7. Fase 6
8. Fase 7
9. Fase 8

## Ruta critica

La ruta critica real del proyecto es:

1. consistencia documental
2. dominio base definitivo
3. bootstrap oficial
4. tenancy + RBAC cerrados
5. CRUDs base del dominio
6. operacion local/Droplet
7. pruebas y release

Si esa ruta se respeta, el resto de capacidades del core deja de sentirse experimental y pasa a comportarse como stack base serio.

## Recomendacion practica

El siguiente ciclo de trabajo deberia concentrarse solo en estas cuatro metas:

1. corregir docs/encoding/fuente unica de backlog
2. rehacer el bootstrap oficial con Manuel Loza y la estructura base real
3. cerrar el modelo de organizaciones, sucursales y asignaciones laborales
4. completar CRUDs backend/frontend del dominio base

Ese bloque es el que mas reduce riesgo y el que mas acerca el proyecto a una version utilizable para clientes reales.

Estado actualizado:

- CI base ya esta operativo en GitHub Actions
- el siguiente cierre de calidad ya no es "tener CI", sino ampliar pruebas frontend y checks de release
