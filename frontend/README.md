# StackBase Frontend

Frontend construido con Vue 3, Vite y PrimeVue.

## Estado actual
- Layout base.
- Login real conectado al backend.
- Registro y flujo basico de recuperacion/reset conectados al backend.
- Router modular `core + modules`.
- Pantalla de administracion de modulos.
- Guardas de autenticacion.
- Guard de rutas para modulos deshabilitados.
- Selector de organizacion activa en la topbar.
- Campanita con contador de notificaciones no leidas.
- `Demo Module` con landing, `Notifications Demo`, `Files Demo`, `Jobs Demo` y `Audit Demo`.
- Build de produccion verificado tras saneamiento de conflictos de merge.
- Navegacion principal limpia de demos del template original.

## Pantallas relevantes hoy
- `/admin/modules`
- `/platform/data-engine`
- `/auth/register`
- `/auth/forgot-password`
- `/auth/reset-password`
- `/demo/platform`
- `/demo/notifications`
- `/demo/files`
- `/demo/jobs`
- `/demo/audit`

## Comandos utiles
```bash
npm install
npm run dev
npm run build
```

## Nota
La administracion de modulos ya esta conectada a autenticacion real y al permiso `modules.manage`. Para ver jobs en cola procesarse realmente en local, el backend necesita un worker activo con `php artisan queue:work --queue=demo`.

El siguiente paso visible en frontend es construir el nuevo motor CRUD del core, ya sin arrastrar el mantenimiento heredado del template.
