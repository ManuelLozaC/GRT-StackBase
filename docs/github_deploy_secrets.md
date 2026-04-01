# GitHub Secrets y Variables para Deploy en Droplet

## Objetivo

Dejar el workflow de GitHub Actions listo para desplegar directamente al Droplet por SSH, escribiendo los archivos `.env` necesarios y validando el `healthcheck` publico al final.

Workflow relacionado:

- [`D:\Desarrollo\GRT-StackBase\.github\workflows\deploy-droplet.yml`](D:\Desarrollo\GRT-StackBase\.github\workflows\deploy-droplet.yml)
- [`D:\Desarrollo\GRT-StackBase\.github\workflows\external-health-monitor.yml`](D:\Desarrollo\GRT-StackBase\.github\workflows\external-health-monitor.yml)

Nota operativa actual:

- `External Health Monitor` esta pausado como tarea programada
- hoy solo corre manualmente por `workflow_dispatch`
- esto evita correos automaticos hasta que decidas volver a activar monitoreo externo continuo

## GitHub Secrets requeridos

Crear estos secrets en:

- `Repository Settings -> Secrets and variables -> Actions`

### Conexion SSH al Droplet

- `DEPLOY_HOST`
  IP publica o hostname del Droplet.
- `DEPLOY_PORT`
  Puerto SSH. Normalmente `22`.
- `DEPLOY_USER`
  Usuario remoto que ejecutara el deploy.
- `DEPLOY_SSH_PRIVATE_KEY`
  Clave privada SSH con acceso al Droplet.
- `DEPLOY_APP_PATH`
  Ruta absoluta del repo en el Droplet.
  Ejemplo:
  `/opt/grt-stackbase`

### Archivos de entorno que el workflow escribira en el servidor

- `ROOT_ENV_FILE`
  Contenido completo del archivo `/.env` raiz usado por `docker compose`.
- `BACKEND_ENV_FILE`
  Contenido completo de [`D:\Desarrollo\GRT-StackBase\backend\.env`](D:\Desarrollo\GRT-StackBase\backend\.env)
- `FRONTEND_ENV_FILE`
  Contenido completo de [`D:\Desarrollo\GRT-StackBase\frontend\.env`](D:\Desarrollo\GRT-StackBase\frontend\.env)

Recomendacion:

- copiar el contenido real de cada `.env` como texto multilinea
- no escapar manualmente saltos de linea
- mantener los nombres exactamente como aparecen arriba

## GitHub Variables recomendadas

Crear estas variables en:

- `Repository Settings -> Secrets and variables -> Actions -> Variables`

- `HEALTHCHECK_URL`
  URL publica del healthcheck productivo.
  Ejemplo:
  `https://tu-dominio.com/api/v1/health`

Nota:

- si no defines `HEALTHCHECK_URL`, el workflow `External Health Monitor` ya no falla; se omite con mensaje informativo
- si la defines, el workflow valida el JSON publico y si algun check sale distinto de `ok`, entonces si marcara error

## Contenido minimo sugerido de `ROOT_ENV_FILE`

Este archivo alimenta `docker-compose.yml`.

```env
DB_DATABASE=grt_stackbase
DB_USERNAME=grt_user
DB_PASSWORD=tu_password_seguro
MEILI_MASTER_KEY=tu_meili_master_key
```

## Contenido minimo sugerido de `BACKEND_ENV_FILE`

Usar como base [`D:\Desarrollo\GRT-StackBase\backend\.env.example`](D:\Desarrollo\GRT-StackBase\backend\.env.example) y completar al menos:

```env
APP_NAME=GRT-StackBase
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=grt_stackbase
DB_USERNAME=grt_user
DB_PASSWORD=tu_password_seguro

QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=database

FILESYSTEM_DISK=spaces
FILESYSTEM_FALLBACK_DISK=local
DATA_EXPORTS_DISK=spaces

MAIL_MAILER=resend
MAIL_FROM_ADDRESS=notificaciones@soporte.grt.technology
MAIL_FROM_NAME=GRT-StackBase
RESEND_API_KEY=tu_resend_api_key

DO_SPACES_KEY=tu_spaces_key
DO_SPACES_SECRET=tu_spaces_secret
DO_SPACES_REGION=sfo3
DO_SPACES_BUCKET=grtbucket
DO_SPACES_ENDPOINT=https://sfo3.digitaloceanspaces.com
DO_SPACES_URL=https://grtbucket.sfo3.digitaloceanspaces.com

FIREBASE_PROJECT_ID=grt-stackbase
FIREBASE_CLIENT_EMAIL=firebase-adminsdk-fbsvc@grt-stackbase.iam.gserviceaccount.com
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n"
FIREBASE_TOKEN_URI=https://oauth2.googleapis.com/token

SEARCH_ENGINE=meilisearch
MEILI_HOST=http://search:7700
MEILI_MASTER_KEY=tu_meili_master_key
MEILI_INDEX_PREFIX=grt_stackbase_
MEILI_TIMEOUT_SECONDS=5

FRONTEND_URL=https://tu-dominio.com
SANCTUM_STATEFUL_DOMAINS=tu-dominio.com
```

## Contenido minimo sugerido de `FRONTEND_ENV_FILE`

```env
VITE_APP_NAME=GRT-StackBase
VITE_API_URL=https://tu-dominio.com/api
```

## Preparacion del Droplet

Antes del primer deploy, el Droplet debe tener:

- Docker y Docker Compose instalados
- el repo clonado en la ruta declarada en `DEPLOY_APP_PATH`
- acceso SSH con la clave privada correspondiente
- DNS y TLS del dominio ya resueltos

## Flujo del workflow

`deploy-droplet.yml` hace esto:

1. entra por SSH al Droplet
2. escribe `.env`, `backend/.env` y `frontend/.env`
3. actualiza el repo a la rama/ref solicitada
4. levanta stack productivo con `docker-compose.yml + docker-compose.prod.yml`
5. corre `composer install`
6. genera `APP_KEY` solo si el `backend/.env` todavia no tiene una clave valida
7. corre migraciones y bootstrap seguro:
   - `php artisan migrate --force`
   - `php artisan platform:ensure-bootstrap`
8. ejecuta:
   `settings:deduplicate`
9. compila frontend productivo y publica Nginx
10. ejecuta:
   `optimize:clear` y `optimize`
11. ejecuta:
   `data:reindex-search`
12. valida smoke tests de release
13. valida `route:list`
14. verifica `HEALTHCHECK_URL`

## Regla de seguridad del bootstrap

- no usar el workflow para regenerar `APP_KEY` en cada release
- no usar el workflow para resetear passwords administrativas
- la cuenta bootstrap solo se crea si no existe
- los roles y permisos base si se refrescan en cada deploy para mantener consistencia
- el refresh de RBAC usa el manifest real de modulos/permisos del repositorio, evitando resincronizar contra una cache vieja

## Observabilidad externa base

`external-health-monitor.yml` hoy solo corre manualmente y, cuando `HEALTHCHECK_URL` esta configurado, falla si:

- la URL publica no responde
- algun check del JSON de health sale distinto de `ok`

Si `HEALTHCHECK_URL` no existe todavia en GitHub Variables, el workflow se omite sin marcar error. Eso evita ruido mientras aun no esta listo el entorno productivo.

## Recomendaciones operativas

- usar `Environment: production` en GitHub para proteger el workflow
- restringir quien puede lanzar `workflow_dispatch`
- rotar cualquier secret expuesto en conversaciones o pruebas manuales
- mantener `BACKEND_ENV_FILE` y `ROOT_ENV_FILE` separados
- no reutilizar llaves de desarrollo en produccion
