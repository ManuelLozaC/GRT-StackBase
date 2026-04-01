# Operacion Base

## Healthchecks
Usar:
- `GET /api/v1/health`

Actualmente expone checks de:
- database
- redis
- mail
- queue
- storage

## Workers
Servicios esperados en Docker:
- `app`
- `web`
- `db`
- `redis`
- `worker`
- `scheduler`
- `search`

Nota:

- en produccion el servicio `frontend` no se expone como Vite dev server
- se usa solo para compilar `dist` durante el deploy y Nginx sirve el resultado final

## Cola
La cola se usa para:
- jobs demo
- exportaciones async
- correo asincrono

## Backups
Recomendacion base para Droplets:
- dump diario de MySQL
- retencion minima de 7 dias
- copia a Spaces
- restauracion probada al menos una vez por ambiente

## Restore
Procedimiento minimo:
1. restaurar dump de base
2. validar `.env`
3. correr `php artisan optimize:clear`
4. validar `/api/v1/health`
5. validar login y modulo demo

## Secretos
Reglas:
- no commitear credenciales reales
- usar `.env`
- rotar claves expuestas en conversaciones, logs o pruebas manuales
- separar credenciales local/staging/produccion

## Logs y observabilidad
Superficies clave:
- `security logs`
- `error logs`
- `operations overview`
- historial de entregas de notificaciones
- historial de webhooks salientes y entrantes

Observabilidad externa base:

- workflow programado de GitHub Actions contra `HEALTHCHECK_URL`
- validacion del JSON publico de `/api/v1/health`
- falla visible en GitHub si algun check deja de responder en `ok`

## Limpieza operativa
Comando recomendado para settings duplicados:

```bash
php artisan settings:deduplicate
```

Usarlo despues de pruebas manuales o bootstrap repetido si hubo cambios de settings.

## Deploy semiautomatizado

El flujo recomendado vive en:

- [`D:\Desarrollo\GRT-StackBase\.github\workflows\deploy-droplet.yml`](D:\Desarrollo\GRT-StackBase\.github\workflows\deploy-droplet.yml)

Y usa:

- [`D:\Desarrollo\GRT-StackBase\scripts\ops\deploy-droplet.sh`](D:\Desarrollo\GRT-StackBase\scripts\ops\deploy-droplet.sh)
- [`D:\Desarrollo\GRT-StackBase\docs\github_deploy_secrets.md`](D:\Desarrollo\GRT-StackBase\docs\github_deploy_secrets.md)

Reglas del flujo actual:

- preserva `APP_KEY` existente
- corre `migrate --force`, no `migrate:fresh`
- evita `db:seed --force` en cada release
- usa `platform:ensure-bootstrap` para garantizar bootstrap sin resetear credenciales
- ese comando tambien refresca roles y permisos base leyendo el manifest actual del proyecto, incluso si existia cache vieja de configuracion
- compila frontend con `docker-compose.prod.yml`
- reindexa todos los recursos con busqueda activa mediante `data:reindex-search`
