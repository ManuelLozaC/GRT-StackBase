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
- `Data Engine` ya consume metadata de recursos y opera CRUD real con relaciones y custom fields sobre recursos demo y estructuras tenant.
- `Data Engine` ya permite exportar/importar CSV y consultar historial de corridas del recurso activo.
- `Demo Module` ya incluye `Transfers Demo` para probar `CSV / Excel / PDF` y modo `async`.
- La administracion de modulos ya permite editar settings persistidos por modulo.
- Ya existe panel de `System Settings`, preferencias del usuario, banner global y manejo global de errores HTTP.
- Ya existe administracion de usuarios con multi-rol e impersonacion.
- Ya existen `Security Logs` y `Operations Overview` para soporte operativo del tenant activo.
- Ya existen `Error Logs` y `Usage Metrics` para observabilidad base.
- Ya existe `System Webhooks` para administrar endpoints salientes, receivers entrantes y revisar entregas/recepciones recientes.
- Fechas y horas visibles del shell ya pueden respetar locale/zona horaria desde settings.
- El usuario ya puede gestionar `API Tokens` y persistir columnas visibles del `Data Engine`.
- El shell ya usa skeleton loaders y empty states reutilizables en pantallas reales.
- Pantallas administrativas clave ya tienen ajustes responsive para tablas anchas y troubleshooting en mobile/tablet.

## Pantallas relevantes hoy
- `/admin/modules`
- `/admin/webhooks`
- `/platform/data-engine`
- `/auth/register`
- `/auth/forgot-password`
- `/auth/reset-password`
- `/account/api-tokens`
- `/demo/platform`
- `/demo/notifications`
- `/demo/files`
- `/demo/jobs`
- `/demo/audit`
- `/demo/transfers`
- `/admin/settings`
- `/admin/users`
- `/admin/operations`
- `/admin/metrics`
- `/admin/security`
- `/admin/errors`
- `/account/preferences`

## Comandos utiles
```bash
npm install
npm run dev
npm run lint
npm run build
npm audit --audit-level=moderate
```

## Nota
La administracion de modulos ya esta conectada a autenticacion real y al permiso `modules.manage`. Para ver jobs y exportaciones async procesarse realmente en local, el backend necesita un worker activo con `php artisan queue:work --queue=data-exports,demo`.

El siguiente paso visible en frontend es profundizar el `Data Engine` en acciones avanzadas/masivas, cerrar feedback optimista/pesimista y seguir bajando integraciones reales de canales externos y observabilidad mas profunda.

Validacion reciente:
- `npm run lint` en verde.
- `npm run build` en verde y sin warning por chunks gigantes.
- `npm audit --audit-level=moderate` en verde.
