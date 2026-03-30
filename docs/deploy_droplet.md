# Despliegue en Droplet

## Objetivo

Mantener la misma arquitectura base entre local y produccion para no redisenar el stack al momento de desplegar.

## Topologia recomendada

- `web`: Nginx publico
- `app`: Laravel PHP-FPM
- `worker`: colas Laravel
- `scheduler`: tareas programadas Laravel
- `db`: MySQL 8
- `redis`: cache, colas y locks
- `search`: Meilisearch
- `spaces`: almacenamiento externo en DigitalOcean Spaces

## Regla operativa

- el Droplet no debe usarse como almacenamiento persistente de adjuntos
- los adjuntos y exportaciones deben salir hacia Spaces
- la base de datos y los volumenes locales solo cubren runtime y servicios del stack

## Estrategia recomendada

### Opcion simple inicial

Un solo Droplet con Docker Compose para:

- `web`
- `app`
- `worker`
- `scheduler`
- `db`
- `redis`
- `search`

Ideal para:

- ambientes internos
- primeras instalaciones
- validacion funcional

### Opcion de crecimiento

Separar mas adelante:

- Droplet app:
  `web`, `app`, `worker`, `scheduler`
- servicio administrado o Droplet dedicado:
  `db`
- servicio administrado o Droplet dedicado:
  `redis`

## Variables criticas de backend

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://tu-dominio`
- `FILESYSTEM_DISK=spaces`
- `FILESYSTEM_FALLBACK_DISK=local`
- `DATA_EXPORTS_DISK=spaces`
- `QUEUE_CONNECTION=redis`
- `CACHE_STORE=redis`
- `SESSION_DRIVER=database`
- `DO_SPACES_KEY`
- `DO_SPACES_SECRET`
- `DO_SPACES_REGION`
- `DO_SPACES_BUCKET`
- `DO_SPACES_ENDPOINT`
- `DO_SPACES_URL`

## Flujo minimo de despliegue

1. Clonar repo y completar `.env` raiz, `backend/.env` y `frontend/.env`.
2. Levantar stack productivo con:
   - `docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build app worker scheduler search db redis`
3. Ejecutar:
   - `docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader`
   - generar `APP_KEY` solo si `backend/.env` todavia no tiene una clave valida
   - `docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan migrate --force`
   - `docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan platform:ensure-bootstrap`
   - `docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan settings:deduplicate`
   - `docker compose -f docker-compose.yml -f docker-compose.prod.yml run --rm frontend sh -c "npm ci && npm run build"`
   - `docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build web`
   - `docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan optimize:clear`
   - `docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan optimize`
   - `docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan data:reindex-search`
4. Verificar salud:
   - `docker compose -f docker-compose.yml -f docker-compose.prod.yml ps`
   - `https://tu-dominio/api/v1/health`
5. Configurar TLS y proxy reverso con Nginx del host o balanceador externo.

## Regla critica de produccion

- no regenerar `APP_KEY` en cada deploy
- no correr `db:seed --force` en cada release
- el bootstrap productivo debe pasar por `platform:ensure-bootstrap`
- el frontend productivo debe servirse desde `dist` compilado por Nginx, no desde `npm run dev`
- la reindexacion debe cubrir todos los recursos buscables, no solo demos

## Automatizacion desde GitHub Actions

El repo ya incluye:

- [`D:\Desarrollo\GRT-StackBase\.github\workflows\deploy-droplet.yml`](D:\Desarrollo\GRT-StackBase\.github\workflows\deploy-droplet.yml)
- [`D:\Desarrollo\GRT-StackBase\.github\workflows\external-health-monitor.yml`](D:\Desarrollo\GRT-StackBase\.github\workflows\external-health-monitor.yml)

Y el script remoto:

- [`D:\Desarrollo\GRT-StackBase\scripts\ops\deploy-droplet.sh`](D:\Desarrollo\GRT-StackBase\scripts\ops\deploy-droplet.sh)

Manual de secrets y variables:

- [`D:\Desarrollo\GRT-StackBase\docs\github_deploy_secrets.md`](D:\Desarrollo\GRT-StackBase\docs\github_deploy_secrets.md)

Con eso el deploy puede ejecutarse directo desde GitHub por `workflow_dispatch`, escribiendo `.env` en el Droplet y validando `healthcheck` al final.

## Checks de salida

- `web`, `app`, `worker` y `scheduler` arriba y sanos
- login funcionando
- colas procesando
- scheduler corriendo
- archivos subiendo a Spaces
- health endpoint respondiendo
- health endpoint con checks `database`, `redis`, `mail`, `queue` y `storage` en `ok`
- smoke tests de release ejecutados antes del corte

## Rotacion y secretos

- mantener las llaves de `Resend`, `Firebase`, `Spaces` y webhooks solo en variables de entorno o secret manager
- rotar credenciales expuestas en desarrollo antes de cualquier despliegue productivo
- preferir subdominios dedicados para envio (`notificaciones@...`) y storage externo antes que disco local del Droplet

## Objetivos de mediano plazo

- backup automatizado de MySQL
- monitoreo externo de healthchecks
- rotacion de logs
- despliegue automatizado por branch o release
