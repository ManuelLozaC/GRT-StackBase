# Levantar el proyecto en local

## 1. Requisitos

- Docker Desktop iniciado.
- Puertos libres:
  - `8080` para backend/API
  - `5173` para frontend
  - `3306` para MySQL
  - `7700` para Meilisearch

## 2. Archivos de entorno

### Raíz del proyecto

Archivo: `.env`

Variables usadas por `docker-compose.yml`:

```env
DB_DATABASE=grt_stackbase
DB_USERNAME=grt_user
DB_PASSWORD=secret
MEILI_MASTER_KEY=grt-meili-local
```

También existe `.env.example` con los mismos valores base para referencia.

### Backend

Archivo: `backend/.env`

Debe quedar así para desarrollo local:

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
BCRYPT_ROUNDS=12

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
FILESYSTEM_DISK=spaces
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

- `APP_KEY` se genera con Artisan.
- Si todavía no usarás Spaces, puedes dejar `DO_SPACES_*` vacíos.

### Frontend

Archivo: `frontend/.env`

```env
VITE_API_URL=http://localhost:8080/api/v1
```

También existe `frontend/.env.example`.

## 3. Comandos para levantar el stack

Desde la raíz del proyecto:

```bash
docker compose up -d --build
```

## 4. Inicializar backend

Instalar dependencias:

```bash
docker compose exec app composer install
```

Generar clave de Laravel:

```bash
docker compose exec app php artisan key:generate
```

Ejecutar migraciones y seeders:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

Opcional: limpiar cachés

```bash
docker compose exec app php artisan optimize:clear
```

## 5. Inicializar frontend

El contenedor `frontend` ejecuta `npm install` y `npm run dev -- --host` automáticamente.

Si necesitas reinstalar dependencias:

```bash
docker compose exec frontend npm install
```

## 6. URLs locales

- Frontend: `http://localhost:5173`
- Backend/API: `http://localhost:8080`
- Swagger/OpenAPI: `http://localhost:8080/api/documentation`
- Meilisearch: `http://localhost:7700`

## 7. Credenciales por defecto

Usuario inicial:

- Nombre: `Manuel Loza`
- Alias: `mloza`
- Correo: `mloza@grt.com.bo`
- Contraseña: `admin1984!`
- Organización: `GRT SRL`
- Oficina principal: `TalentHub`
- Rol base: `superusuario`

Puedes iniciar sesión con:

- `mloza`
- `mloza@grt.com.bo`

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

Para borrar volúmenes y comenzar desde cero:

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
VITE_API_URL=http://localhost:8080/api/v1
```

### Reiniciar servicios

```bash
docker compose restart app web
docker compose restart frontend
```
