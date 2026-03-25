# StackBase Backend

Backend API-first construido con Laravel 12.

## Estado actual
- API `v1` inicial.
- Healthcheck.
- Login, logout y `me`.
- Registro y reset de password.
- Preview del token de reset disponible solo en `local/testing`.
- Organizaciones y organizacion activa por usuario.
- Migraciones base unificadas y verificadas en test suite.
- Base fundacional adelgazada para no sembrar catalogos de ubicacion/personas que no forman parte del core actual.
- Solo se mantiene activa la capa HTTP alineada a `api/v1`; el API legacy previo fue retirado.
- Seeders iniciales de permisos alineados en un solo flujo (`RolePermissionSeeder`).
- Metadata modular ampliada en `config/modules.php` con dependencias, permisos, settings y features.
- Registro de modulos.
- Persistencia de modulos en `system_modules`.
- Toggle de modulos por API.
- Base de archivos demo con upload, descarga e historial.
- Base de jobs demo con cola, modo inmediato y logs basicos.
- Base de auditoria transversal para modulos, archivos y jobs.
- Base de notificaciones internas con bandeja y lectura.
- `core-platform` y `demo-platform` declarados.

## Endpoints base actuales
- `GET /api/v1/health`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/register`
- `POST /api/v1/auth/forgot-password`
- `POST /api/v1/auth/reset-password`
- `GET /api/v1/auth/me`
- `POST /api/v1/auth/logout`
- `PATCH /api/v1/auth/active-organization`
- `GET /api/v1/demo/files`
- `POST /api/v1/demo/files`
- `GET /api/v1/demo/files/downloads`
- `GET /api/v1/demo/files/{file}/download`
- `POST /api/v1/demo/files/{file}/temporary-link`
- `GET /api/v1/demo/jobs`
- `POST /api/v1/demo/jobs`
- `GET /api/v1/demo/audit`
- `GET /api/v1/notifications`
- `PATCH /api/v1/notifications/{notification}/read`
- `POST /api/v1/notifications/read-all`
- `POST /api/v1/demo/notifications`
- `GET /api/v1/modules`
- `PATCH /api/v1/modules/{moduleKey}`

## Comandos utiles
```bash
php artisan migrate
php artisan test
php artisan queue:work --queue=demo
php artisan config:clear
```

## Verificacion reciente
- `php artisan test` pasando con 24 tests.
- Integridad de migraciones corregida para evitar duplicados y desalineacion del esquema base.
- Arbol HTTP legacy no enroutado eliminado para reducir deriva arquitectonica.
- Bootstrap RBAC inicial sin duplicidad de seeders.
- Esquema fundacional reducido a entidades realmente usadas por el core modular actual.

## Documentacion relacionada
- `docs/stackbase.md`
- `docs/roadmap.md`
- `docs/pendientes.md`
- `docs/contrato_modulos.md`
- `docs/guia_comentarios.md`
