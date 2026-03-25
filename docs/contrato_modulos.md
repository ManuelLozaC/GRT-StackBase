# Contrato de Modulos
> Contrato actual de referencia para crecer `Core Platform + Modules` sin duplicar wiring.

## Objetivo
Cada modulo debe declararse una sola vez y desde esa manifest alimentar:
- registro backend
- activacion/desactivacion
- metadata funcional
- rutas frontend
- menu frontend
- permisos y dependencias declarativas

## Contrato backend actual
La fuente de verdad backend vive en `backend/config/modules.php`.

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

## Contrato frontend actual
La fuente de verdad frontend por modulo vive en `frontend/src/modules/<module-key>/manifest.js`.

Campos soportados hoy:
- `key`
- `name`
- `description`
- `isDemo`
- `navigation.label`
- `routes[]`

Cada entrada de `routes[]` puede declarar:
- `path`
- `name`
- `component`
- `meta`
- `menu.label`
- `menu.icon`

## Estado actual
- `demo-platform` ya usa manifest unica en frontend.
- `frontend/src/modules/index.js` ya compone `moduleRoutes` y `moduleMenu` desde manifests.
- `ModuleRegistry` ya normaliza metadata modular en backend y la conserva al togglear modulos.

## Deuda restante del contrato
- unificar naming y estructura entre manifest frontend y config backend
- exponer metadata modular extendida por API para que frontend no dependa de duplicacion manual
- declarar dependencias entre modulos y bloquear activaciones invalidas
- soportar permisos y settings por modulo de forma operativa, no solo descriptiva
- permitir rutas y menus declarativos para futuros modulos sin wiring manual adicional
