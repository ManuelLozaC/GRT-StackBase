# Release Checklist

## Antes de liberar
- validar `docker compose ps`
- validar `GET /api/v1/health`
- revisar secretos y `.env`
- confirmar dominio/correo/push operativos
- revisar modulo demo habilitado solo donde aporte valor

## Calidad automatica
- `backend`: `php artisan test`
- `backend`: `php artisan route:list`
- `frontend`: `npm run lint`
- `frontend`: `npm run build`

## Smoke tests minimos
- auth:
  - login
  - `me`
- data engine:
  - listar recursos
- push:
  - ver suscripciones del usuario
- email:
  - crear entrega en modo `queued` y dejar que worker la procese

## Operacion
- worker corriendo
- scheduler corriendo
- redis saludable
- base conectada
- storage correcto

## Verificacion funcional corta
- login con usuario seed
- notificacion push
- notificacion email
- una operacion del Data Engine
- settings globales y de usuario

## Post release
- revisar `operations overview`
- revisar `error logs`
- revisar `security logs`
- revisar entregas de email/push
