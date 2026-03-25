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

- Backend Laravel 12 API-first con `api/v1`.
- Frontend Vue 3 + Vite + PrimeVue.
- Registro modular con `core-platform` y `demo-platform`.
- Administracion de modulos con activacion/desactivacion, dependencias operativas y settings por modulo.
- `Demo Module` para probar capacidades genericas antes de llevarlas a modulos de negocio.
- Data Engine real con CRUD base tenant-aware sobre un recurso demo y export/import CSV con historial.

## Documentacion principal

- [docs/stackbase.md](./docs/stackbase.md)
- [docs/roadmap.md](./docs/roadmap.md)
- [docs/pendientes.md](./docs/pendientes.md)
- [docs/contrato_modulos.md](./docs/contrato_modulos.md)

## Verificacion rapida

```bash
cd backend
php artisan test

cd ../frontend
npm run lint
npm run build
```

## Siguiente foco

- tenancy transversal completa
- storage real con Spaces
- notificaciones multicanal
- evolucion del Data Engine hacia relaciones, custom fields, Excel/PDF y acciones avanzadas
