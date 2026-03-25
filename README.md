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
- Data Engine tenant-aware con relaciones, custom fields e import/export
- recursos base del dominio ya expuestos en backend por Data Engine
- settings globales, por organizacion y por usuario
- multi-rol, administracion de usuarios e impersonacion
- observabilidad base: request IDs, security logs, error logs y metrics
- webhooks salientes y entrantes, API tokens y OpenAPI JSON
- bootstrap oficial inicial con `GRT SRL`, `TalentHub` y `Manuel Loza`

## Fuente de verdad documental

- estado y backlog operativo: [docs/pendientes.md](./docs/pendientes.md)
- diagnostico actual: [docs/revision_estado_actual.md](./docs/revision_estado_actual.md)
- plan de cierre: [docs/plan_trabajo_finalizacion.md](./docs/plan_trabajo_finalizacion.md)
- arquitectura base: [docs/stackbase.md](./docs/stackbase.md)
- dominio vigente: [docs/modelo_dominio.md](./docs/modelo_dominio.md)
- contrato modular: [docs/contrato_modulos.md](./docs/contrato_modulos.md)
- decisiones cerradas: [preguntas.md](./preguntas.md)

## Verificacion rapida

```bash
cd backend
php artisan test

cd ../frontend
npm run lint
npm run build
```

## Siguiente foco

- orden documental y fuente unica de verdad
- cierre del modelo organizacional y laboral
- convergencia total de `organizacion = empresa`
- tenancy transversal completa
- storage real con Spaces
