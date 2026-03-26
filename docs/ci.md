# CI

## Objetivo

Validar automaticamente que el stack base siga siendo instalable y verificable en cada cambio importante.

## Pipeline actual

Archivo:

- `.github/workflows/ci.yml`

Jobs incluidos:

1. `Backend Tests`
   - PHP `8.3`
   - MySQL `8.0`
   - Redis `7`
   - `composer install`
   - `php artisan migrate --force`
   - `php artisan route:list`
   - `php artisan test`

2. `Frontend Quality`
   - Node `20`
   - `npm ci`
   - `npm run lint`
   - `npm run build`

## Endurecimientos operativos ya aplicados

- disparo por `push`, `pull_request` y `workflow_dispatch`
- cancelacion automatica de corridas viejas sobre la misma rama
- permisos minimos (`contents: read`)
- `timeout` por job para evitar pipelines colgados
- entorno backend centralizado a nivel de job para reducir drift entre pasos

## Alcance actual

- valida backend Laravel con dependencias reales
- valida frontend con lint y build
- usa `FILESYSTEM_DISK=local` en CI para no depender aun de credenciales de Spaces
- valida que el arbol de rutas backend siga resolviendo correctamente antes de correr tests

## Pendiente para la siguiente iteracion

- agregar checks separados por dominio si el tiempo de pipeline crece demasiado
- decidir si se agrega generacion/verificacion de OpenAPI como chequeo adicional
- integrar validacion real de Spaces en un entorno con secretos configurados
