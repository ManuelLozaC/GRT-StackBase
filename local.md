# Levantar el proyecto en local

## 1. Requisitos

- Docker Desktop iniciado
- puertos libres:
  - `8080` para backend/API
  - `5173` para frontend
  - `3306` para MySQL
  - `7700` para Meilisearch

## 2. Archivos de entorno

### Raiz del proyecto

Archivo: `.env`

Variables usadas por `docker-compose.yml`:

```env
DB_DATABASE=grt_stackbase
DB_USERNAME=grt_user
DB_PASSWORD=secret
MEILI_MASTER_KEY=grt-meili-local
```

Tambien existe `.env.example` con los mismos valores base para referencia.

### Backend

Archivo: `backend/.env`

Debe quedar asi para desarrollo local:

```env
APP_NAME=GRT-StackBase
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8080

APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_ES

APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=10
CORE_METRICS_ENABLED=false
CORE_HTTP_METRICS_ENABLED=false
CORE_SECURITY_LOGS_ENABLED=true
CORE_SECURITY_INFO_LOGS_ENABLED=false
CORE_AUDIT_LOGS_ENABLED=true

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=grt_stackbase
DB_USERNAME=grt_user
DB_PASSWORD=secret

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=localhost

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
FILESYSTEM_FALLBACK_DISK=local
DATA_EXPORTS_DISK=local
QUEUE_CONNECTION=redis
CACHE_STORE=redis

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="soporte@grt.com.bo"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=nyc3
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false
AWS_ENDPOINT=
AWS_URL=

DO_SPACES_KEY=
DO_SPACES_SECRET=
DO_SPACES_REGION=nyc3
DO_SPACES_BUCKET=
DO_SPACES_ENDPOINT=
DO_SPACES_URL=

SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173,localhost:8080
FRONTEND_URL=http://localhost:5173

MEILI_HOST=http://search:7700
MEILI_MASTER_KEY=grt-meili-local

VITE_APP_NAME="${APP_NAME}"
```

Notas:

- `APP_KEY` se genera con Artisan
- para local simple, el valor recomendado sigue siendo `FILESYSTEM_DISK=local`
- si quieres trabajar con el bucket real, usa `FILESYSTEM_DISK=spaces` y `DATA_EXPORTS_DISK=spaces`
- si `FILESYSTEM_DISK=spaces` pero faltan credenciales, el stack hace fallback automatico a `local`

### Frontend

Archivo: `frontend/.env`

```env
VITE_API_URL=http://localhost:8080/api
```

Tambien existe `frontend/.env.example`.

## 3. Comandos para levantar el stack

Desde la raiz del proyecto:

```bash
docker compose up -d --build
```

Notas de performance local:

- `vendor`, `storage` y `node_modules` usan volumenes Docker para reducir el costo de I/O sobre Windows.
- si trabajas con el repo en un disco/ruta de Windows, Docker Desktop puede seguir sintiendose notablemente mas lento que WSL2 nativo.
- para la mejor experiencia local, conviene trabajar desde el filesystem de WSL2 y no desde un bind mount de Windows.

Servicios incluidos en esta version base:

- `app`
- `worker`
- `scheduler`
- `web`
- `db`
- `redis`
- `search`
- `frontend`

## 4. Inicializar backend

```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan optimize
```

## 5. Inicializar frontend

El contenedor `frontend` ejecuta `npm install` y `npm run dev -- --host` automaticamente.

Si necesitas reinstalar dependencias:

```bash
docker compose exec frontend npm install
```

## 6. URLs locales

- frontend: `http://localhost:5173`
- backend/API: `http://localhost:8080`
- OpenAPI JSON: `http://localhost:8080/api/v1/openapi.json`
- Meilisearch: `http://localhost:7700`

## 7. Credenciales por defecto

Bootstrap oficial actual:

- organizacion: `GRT SRL`
- oficina principal: `TalentHub`
- usuario: `Manuel Loza`
- alias: `mloza`
- correo: `mloza@grt.com.bo`
- contrasena: `admin1984!`

Nota:
La semilla actual ya crea la base oficial del stack. El siguiente bloque pendiente es ampliar esa base hacia personas, oficinas y asignaciones laborales completas.

Puedes iniciar sesion con:

- `mloza@grt.com.bo`
- `mloza`

Verificacion rapida por API:

```bash
curl -X POST http://localhost:8080/api/v1/auth/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"mloza@grt.com.bo","password":"admin1984!","device_name":"frontend"}'
```

## 8. Validaciones recomendadas

Backend:

```bash
docker compose exec app php artisan route:list
docker compose exec app php artisan test
```

Frontend:

```bash
docker compose exec frontend npm run lint
docker compose exec frontend npm run build
```

## 9. Apagar el proyecto

```bash
docker compose down
```

Para borrar volumenes y comenzar desde cero:

```bash
docker compose down -v
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate:fresh --seed
```

## 10. Problemas comunes

### Laravel dice que falta `APP_KEY`

```bash
docker compose exec app php artisan key:generate
```

### El frontend no conecta a la API

Revisa `frontend/.env`:

```env
VITE_API_URL=http://localhost:8080/api
```

### Las credenciales iniciales no funcionan

Primero verifica si el bootstrap realmente fue seedado:

```bash
docker compose exec app php artisan db:seed --force
```

Si quieres dejar la base exactamente desde cero:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

Validacion directa:

```bash
docker compose exec db mysql -N -B -ugrt_user -psecret -D grt_stackbase -e "SELECT id,name,alias,email,activo FROM users;"
```

Debes ver al menos este usuario:

- alias: `mloza`
- correo: `mloza@grt.com.bo`
- contrasena: `admin1984!`

### Reiniciar servicios

```bash
docker compose restart app web
docker compose restart frontend
```

Para reiniciar procesos de backend en segundo plano:

```bash
docker compose restart worker scheduler
```

### Activar Spaces en local o staging

Completa estas variables en `backend/.env`:

```env
FILESYSTEM_DISK=spaces
DATA_EXPORTS_DISK=spaces
DO_SPACES_KEY=tu_key
DO_SPACES_SECRET=tu_secret
DO_SPACES_REGION=sfo3
DO_SPACES_BUCKET=grtbucket
DO_SPACES_ENDPOINT=https://sfo3.digitaloceanspaces.com
DO_SPACES_URL=https://grtbucket.sfo3.digitaloceanspaces.com
```

Luego reinicia `app`:

```bash
docker compose restart app
```

Validacion recomendada:

```bash
docker compose exec app php artisan config:clear
```

Estado actual:

- el proyecto ya fue validado con escritura, lectura y borrado real sobre DigitalOcean Spaces
- el stack local ya incluye `worker` y `scheduler`, alineado con la topologia objetivo de Droplet
