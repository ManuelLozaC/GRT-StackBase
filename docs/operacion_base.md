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
- `frontend`
- `db`
- `redis`
- `worker`
- `scheduler`

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

## Limpieza operativa
Comando recomendado para settings duplicados:

```bash
php artisan settings:deduplicate
```

Usarlo despues de pruebas manuales o bootstrap repetido si hubo cambios de settings.
