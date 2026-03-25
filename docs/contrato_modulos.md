# Contrato de modulos

> Contrato actual de referencia para crecer `Core Platform + Modules` sin duplicar wiring.

## Objetivo

Cada modulo debe declararse una sola vez en backend y desde esa fuente alimentar:

- registro backend
- activacion y desactivacion
- metadata funcional
- bootstrap de rutas frontend
- bootstrap de menu frontend
- permisos y dependencias declarativas

## Contrato backend actual

La fuente de verdad vive en `backend/config/modules.php` y se expone por `GET /api/v1/modules`.

Campos soportados hoy:

- `name`
- `description`
- `version`
- `enabled`
- `is_demo`
- `provider`
- `dependencies`
- `permissions`
- `settings`
- `features`
- `jobs`
- `webhooks`
- `dashboards`
- `seeders`
- `assets`
- `frontend.navigation`
- `frontend.routes`

## Contrato frontend actual

Frontend ya no define metadata de navegacion como fuente de verdad.

Frontend solo mantiene un registro local de vistas en `frontend/src/modules/<module-key>/registry.js`.

Cada registro local resuelve:

- `viewKey -> component`

La metadata que llega por API declara:

- `path`
- `name`
- `view`
- `meta`
- `menu.label`
- `menu.icon`

## Estado actual

- `demo-platform` ya consume metadata de rutas y menu desde backend/API
- `moduleCatalog` construye menu y rutas desde `GET /api/v1/modules`
- `ModuleRegistry` normaliza metadata modular y bloquea dependencias invalidas
- los `settings` ya son operativos
- los `webhooks` ya tienen base operativa para salidas y recepcion declarativa

## Deuda restante del contrato

- generalizar el contrato para futuros modulos sin wiring adicional
- soportar permisos por modulo de forma operativa, no solo descriptiva
- convertir `seeders`, `assets` y `dashboards` en runtime realmente ejecutable por modulo
- reducir aun mas el registro local frontend a solo vistas realmente necesarias

## Regla documental

El contrato de modulos se considera vigente solo si no contradice:

- [`docs/stackbase.md`](/D:/Desarrollo/GRT-StackBase/docs/stackbase.md)
- [`docs/modelo_dominio.md`](/D:/Desarrollo/GRT-StackBase/docs/modelo_dominio.md)
- [`docs/pendientes.md`](/D:/Desarrollo/GRT-StackBase/docs/pendientes.md)
