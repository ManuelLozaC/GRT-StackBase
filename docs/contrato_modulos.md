# Contrato de Modulos
> Contrato actual de referencia para crecer `Core Platform + Modules` sin duplicar wiring.

## Objetivo
Cada modulo debe declararse una sola vez en backend y desde esa fuente alimentar:
- registro backend
- activacion/desactivacion
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
- `demo-platform` ya consume metadata de rutas y menu desde backend/API.
- `frontend/src/modules/registry.js` solo resuelve componentes locales por `viewKey`.
- `moduleCatalog` ya construye menu y rutas desde la respuesta de `GET /api/v1/modules`.
- `ModuleRegistry` ya normaliza metadata modular en backend y la conserva al togglear modulos.

## Deuda restante del contrato
- generalizar el contrato para futuros modulos sin wiring adicional
- declarar dependencias entre modulos y bloquear activaciones invalidas
- soportar permisos y settings por modulo de forma operativa, no solo descriptiva
- reducir aun mas el registro local frontend a solo vistas realmente necesarias
