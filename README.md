# StackBase

Base reusable para construir multiples sistemas sobre una sola `Core Platform + Modules`.

## Objetivo

Resolver una sola vez las capacidades transversales del producto y reutilizarlas desde modulos plug-in:

- identidad y acceso
- tenancy
- archivos
- jobs
- auditoria
- notificaciones
- data engine / CRUD universal
- shell administrativa

## Estado actual

- backend Laravel 12 API-first con `api/v1`
- frontend Vue 3 + Vite + PrimeVue
- decision de dominio vigente: `organizacion = empresa`
- login por correo o alias
- registro modular con `core-platform` y `demo-platform`
- administracion de modulos con activacion, desactivacion, dependencias y settings
- Data Engine tenant-aware con relaciones, custom fields, duplicado de registros e import/export
- busqueda real con Meilisearch y reindexacion operativa por recurso
- recursos base del dominio ya expuestos en backend por Data Engine
- settings globales, por organizacion y por usuario
- multi-rol, administracion de usuarios, reset de contrasena e impersonacion
- observabilidad base: request IDs, security logs, error logs y metrics
- webhooks salientes y entrantes, API tokens y OpenAPI JSON
- push web real con FCM y email real con Resend
- bootstrap oficial inicial con `GRT SRL`, `TalentHub` y `Manuel Loza`
- pipeline CI operativo con backend `PHP 8.3 + MySQL + Redis` y frontend `Node 20`
- smoke tests de release para auth, Data Engine, healthchecks y canales base
- deploy productivo endurecido: sin regenerar `APP_KEY`, sin `db:seed --force`, con frontend compilado a `dist` y reindexacion completa
- `Demo Module` expandido como biblioteca viva de ejemplos tecnicos, UI y recipes de pantalla

## Fuente de verdad documental

- estado y backlog operativo: [docs/pendientes.md](./docs/pendientes.md)
- diagnostico actual: [docs/revision_estado_actual.md](./docs/revision_estado_actual.md)
- plan de cierre: [docs/plan_trabajo_finalizacion.md](./docs/plan_trabajo_finalizacion.md)
- arquitectura base: [docs/stackbase.md](./docs/stackbase.md)
- dominio vigente: [docs/modelo_dominio.md](./docs/modelo_dominio.md)
- contrato modular: [docs/contrato_modulos.md](./docs/contrato_modulos.md)
- guia de nuevo modulo: [docs/guia_nuevo_modulo.md](./docs/guia_nuevo_modulo.md)
- patrones de notificaciones: [docs/patrones_notificaciones.md](./docs/patrones_notificaciones.md)
- demo module: [docs/demo_module.md](./docs/demo_module.md)
- pipeline CI: [docs/ci.md](./docs/ci.md)
- operacion base: [docs/operacion_base.md](./docs/operacion_base.md)
- release checklist: [docs/release_checklist.md](./docs/release_checklist.md)
- despliegue Droplet: [docs/deploy_droplet.md](./docs/deploy_droplet.md)
- secrets y variables GitHub para deploy: [docs/github_deploy_secrets.md](./docs/github_deploy_secrets.md)
- sugerencias de evolucion no bloqueante: [sugerencias.md](./sugerencias.md)
- decisiones cerradas: [preguntas.md](./preguntas.md)

## Verificacion rapida

```bash
cd backend
php artisan test

cd ../frontend
npm run test:run
npm run lint
npm run build
```

## Siguiente foco

- refinamiento continuo del `Demo Module` como biblioteca viva del stack
- endurecimiento transversal de tenancy, observabilidad y seguridad operativa
- automatizacion progresiva de despliegue, backups y operacion sobre Droplets
- evolucion del core y de los modulos sin inflar la base fundacional
