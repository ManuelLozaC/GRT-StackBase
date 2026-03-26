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
- `demo-platform` ya funciona como sandbox tecnico del core, aunque todavia no cubre el catalogo completo de ejemplos UI esperados para un template base

## Deuda restante del contrato

- generalizar el contrato para futuros modulos sin wiring adicional
- soportar permisos por modulo de forma operativa, no solo descriptiva
- convertir `seeders`, `assets` y `dashboards` en runtime realmente ejecutable por modulo
- reducir aun mas el registro local frontend a solo vistas realmente necesarias
- formalizar el contrato del `Demo Module` para que incluya:
  - demos tecnicas del core
  - demos UI de componentes y patrones
  - ejemplos de wiring recomendados para formularios, validaciones y feedback al usuario

## Regla especial para Demo Module

`demo-platform` debe mostrar ejemplos funcionales de como implementar, reutilizar y combinar capacidades del stack.

Como minimo debe incluir ejemplos de:

- toasts
- confirmaciones
- modals y drawers
- alerts y banners
- tipografia y parrafos
- formularios completos
- inputs de texto
- textareas
- selects
- multiselects
- checkboxes
- radios
- toggles
- datepickers
- input number
- tablas
- filtros
- paginacion
- estados vacios
- skeleton loaders
- validaciones cliente/servidor
- carga de archivos
- acciones async con feedback

## Regla documental

El contrato de modulos se considera vigente solo si no contradice:

- [`docs/stackbase.md`](/D:/Desarrollo/GRT-StackBase/docs/stackbase.md)
- [`docs/modelo_dominio.md`](/D:/Desarrollo/GRT-StackBase/docs/modelo_dominio.md)
- [`docs/pendientes.md`](/D:/Desarrollo/GRT-StackBase/docs/pendientes.md)
