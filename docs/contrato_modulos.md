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
- `demo-platform` ya consume metadata de rutas y menu desde backend/API.
- `frontend/src/modules/registry.js` solo resuelve componentes locales por `viewKey`.
- `moduleCatalog` ya construye menu y rutas desde la respuesta de `GET /api/v1/modules`.
- `ModuleRegistry` ya normaliza metadata modular en backend, conserva metadata al togglear y bloquea dependencias invalidas.
- La metadata expuesta ya incluye estado operativo basico: `dependency_status`, `blocking_dependents`, `can_enable`, `can_disable` e `is_protected`.
- Los `settings` ya son operativos: se persisten, se administran por API/UI y pueden afectar comportamiento real del modulo.
- La metadata backend ya expone piezas operativas del contrato (`jobs`, `webhooks`, `dashboards`, `seeders`, `assets`) aunque varias siguen siendo descriptivas y no ejecutables por runtime.
- Los `webhooks` ya son operativos para salidas: cada modulo puede declarar eventos disponibles y el shell administrativo consume ese catalogo sin wiring manual adicional.
- El shell administrativo ya consume esta metadata junto con vistas core de `System Modules`, `Security Logs` y `Operations Overview` sin reintroducir wiring legacy.

## Deuda restante del contrato
- generalizar el contrato para futuros modulos sin wiring adicional
- soportar permisos por modulo de forma operativa, no solo descriptiva
- convertir `seeders`, `assets`, `dashboards` y recepcion de `hooks/webhooks` en runtime realmente ejecutable por modulo
- reducir aun mas el registro local frontend a solo vistas realmente necesarias
