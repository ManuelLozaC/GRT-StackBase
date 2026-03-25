# StackBase Frontend

Frontend construido con Vue 3, Vite y PrimeVue.

## Estado actual
- Layout base.
- Login real conectado al backend.
- Registro y flujo basico de recuperacion/reset conectados al backend.
- Helper visual de reset disponible solo cuando backend responde preview en `local/testing`.
- Router modular `core + modules`.
- Pantalla de administracion de modulos.
- Guardas de autenticacion.
- Guard de rutas para modulos deshabilitados.
- Selector de organizacion activa en la topbar.
- Campanita con contador de notificaciones no leidas.
- `Demo Module` con landing, `Notifications Demo`, `Files Demo`, `Jobs Demo` y `Audit Demo`.
- Build de produccion verificado tras saneamiento de conflictos de merge.
- Navegacion principal limpia de demos del template original.
- Branding principal alineado a `GRT StackBase`.
- Toolchain frontend actualizado a `Vite 6.4.1`.
- `Demo Module` ya se bootstrappea desde metadata modular entregada por API.
- Stores consumidos por responsabilidad: sesion, tenant y permisos, sin fachada `authStore`.
- Pantalla de modulos informa dependencias, features y bloqueos operativos antes de togglear.
- El catalogo modular frontend se refresca completo tras togglear para no dejar estados derivados desactualizados.
- `Data Engine` ya consume metadata de recursos y opera CRUD real sobre el recurso demo.
- `Data Engine` ya permite exportar/importar CSV y consultar historial de corridas del recurso activo.
- La administracion de modulos ya permite editar settings persistidos por modulo.

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
npm run lint
npm run build
npm audit --audit-level=moderate
```

## Nota
La administracion de modulos ya esta conectada a autenticacion real y al permiso `modules.manage`. Para ver jobs en cola procesarse realmente en local, el backend necesita un worker activo con `php artisan queue:work --queue=demo`.

El siguiente paso visible en frontend es extender el `Data Engine` con relaciones, custom fields y export/import avanzado, ya sobre una base real y no sobre placeholders.

Validacion reciente:
- `npm run lint` en verde.
- `npm run build` en verde y sin warning por chunks gigantes.
- `npm audit --audit-level=moderate` en verde.
