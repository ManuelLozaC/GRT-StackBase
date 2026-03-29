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

1. Clonar repo y completar `.env` raiz y `backend/.env`.
2. Levantar stack con `docker compose up -d --build`.
3. Ejecutar:
   - `docker compose exec app composer install --no-interaction --prefer-dist`
   - `docker compose exec app php artisan key:generate`
   - `docker compose exec app php artisan migrate --force`
   - `docker compose exec app php artisan db:seed --force`
   - `docker compose exec app php artisan settings:deduplicate`
   - `docker compose exec app php artisan optimize:clear`
   - `docker compose exec app php artisan optimize`
4. Verificar salud:
   - `docker compose ps`
   - `https://tu-dominio/api/v1/health`
5. Configurar TLS y proxy reverso con Nginx del host o balanceador externo.

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
