# Pendientes StackBase
> Backlog operativo del proyecto, sincronizado con el estado real del codigo.

## Estado resumido
### Ya existe
- [x] API base `v1`.
- [x] Respuesta JSON estandar.
- [x] Login API.
- [x] Logout API.
- [x] Endpoint `me`.
- [x] Registro de usuarios.
- [x] Recuperacion y reset de password.
- [x] Organizaciones base y membresias usuario <-> organizacion.
- [x] Organizacion activa por usuario.
- [x] Cambio de organizacion activa desde API y frontend.
- [x] Base de archivos en el core.
- [x] Demo funcional de archivos con subida, descarga directa, signed URL e historial.
- [x] Base de jobs ejecutables en el core.
- [x] Demo funcional de jobs con cola, modo inmediato, estados y logs basicos.
- [x] Base de auditoria transversal en el core.
- [x] Demo funcional de auditoria para modulos, archivos y jobs.
- [x] Base de notificaciones internas en el core.
- [x] Demo funcional de notificaciones con bandeja y campanita.
- [x] Rol `admin`.
- [x] Permiso `modules.manage`.
- [x] Registro modular inicial en backend.
- [x] Persistencia de modulos en tabla `system_modules`.
- [x] Toggle de modulos desde pantalla administrativa.
- [x] `Demo Module` inicial, protegido por estado del modulo.
- [x] Estructura base `core/modules` en backend y frontend.
- [x] Guardas frontend por autenticacion.
- [x] Proteccion por permiso sobre administracion de modulos.
- [x] Integridad base revisada con tests backend y build frontend en verde.
- [x] Limpieza principal de deuda legacy HTTP/UI del template original.
- [x] Preview de reset password limitado a `local/testing`.
- [x] Seeders RBAC iniciales alineados en una sola fuente de verdad.
- [x] Branding residual del template retirado de metadatos principales.
- [x] Estructura laboral legacy retirada del runtime activo.
- [x] Catalogos y modelos inactivos (`paises/ciudades/oficinas/personas`) retirados de la base fundacional del core.

### Brechas principales
- [ ] RBAC completo.
- [ ] Multi-tenant operativo completo en todo el dominio.
- [ ] CRUD universal conectado a backend.
- [ ] Sistema real de archivos.
- [ ] Extender archivos hacia Spaces, versionado avanzado y descargas pesadas async.
- [ ] Jobs avanzados.
- [ ] Notificaciones avanzadas y multicanal.
- [ ] Auditoria y logs avanzados.
- [ ] Demos funcionales de las capacidades genericas.
- [ ] Definir los catalogos universales reales que el core soportara de forma explicita.

## Regla transversal del proyecto
- [x] Toda funcionalidad generica importante debe vivir en el core.
- [x] Toda funcionalidad generica importante debe tener una demo dentro de `Demo Module`.
- [x] El `Demo Module` debe poder habilitarse o deshabilitarse desde administracion.
- [ ] Cada demo debe ser funcional, no solo visual.

## P0. Kernel de plataforma y modulos
Estado: En progreso

- [x] Estructura `backend/app/Core`.
- [x] Estructura `backend/app/Modules`.
- [x] Estructura `frontend/src/core`.
- [x] Estructura `frontend/src/modules`.
- [x] Registro de modulos instalados.
- [x] Persistencia del estado habilitado/deshabilitado.
- [x] API para listar modulos.
- [x] API para togglear modulos.
- [x] Pantalla de administracion de modulos.
- [x] Route guard frontend para modulos deshabilitados.
- [x] `Demo Module` inicial.
- [ ] Contrato formal de un modulo: permisos, menus, rutas, migraciones, settings, jobs, webhooks, dashboards.
- [ ] Contrato formal de una demo por capacidad transversal.
- [ ] Carga de menus y rutas por modulo desde metadata declarativa.
- [ ] Orden de carga y dependencias entre modulos.

## P1. Identidad y acceso
Estado: En progreso

- [x] Login real.
- [x] Logout real.
- [x] Perfil autenticado (`me`).
- [x] RBAC inicial para administracion de modulos.
- [x] Registro de usuarios.
- [x] Recuperacion y reseteo de password.
- [x] Token auth inicial.
- [ ] Ampliar RBAC por endpoint y accion para mas areas del sistema.
- [ ] Multi-rol por usuario.
- [ ] Impersonacion con auditoria.
- [x] Guardas frontend conectadas a auth real.

## P2. Usuarios, organizaciones y tenancy
Estado: En progreso

- [x] Migracion base de organizaciones.
- [x] Relacion usuario <-> organizacion.
- [x] Tenant activo por usuario autenticado.
- [x] Cambio de tenant activo por API.
- [x] Selector de organizacion activa en topbar.
- [ ] Migraciones de empresas.
- [ ] Migraciones de sucursales.
- [ ] Migraciones de equipos.
- [ ] Unificar concepto organizacion / empresa segun modelo final.
- [ ] Tenant activo por request en todos los servicios backend.
- [ ] Configuracion por tenant.
- [ ] Scope multi-tenant consistente en modelos, jobs, archivos y auditoria.
- [x] Base fundacional de tenancy adelgazada para no mezclar catalogos de ubicacion o personas no usados por el core.

## P3. Configuracion del sistema
Estado: Pendiente

- [ ] Variables globales persistentes.
- [ ] Configuracion por modulo.
- [ ] Feature flags.
- [ ] Parametros dinamicos sin deploy.
- [ ] Configuracion por usuario.
- [ ] UI administrativa de settings.

## P4. Motor de datos y CRUD universal
Estado: Pendiente

- [ ] CRUD generico backend.
- [ ] Paginacion.
- [ ] Ordenamiento.
- [ ] Filtros dinamicos.
- [ ] Busqueda global.
- [ ] Validacion backend estandar.
- [ ] Serializacion uniforme.
- [ ] Campos personalizados.
- [ ] Componentes frontend reutilizables de tabla y formulario.
- [x] Retirar el CRUD historico del template de la navegacion principal.
- [ ] Implementar el nuevo motor CRUD del core con contrato reutilizable para modulos.

## P5. Gestion de archivos
Estado: En progreso

- [x] Base de archivos `core_files`.
- [x] Historial de descargas `core_file_downloads`.
- [x] Asociacion archivo <-> tenant activo.
- [x] Signed URLs iniciales.
- [x] Descarga directa.
- [x] Historial de descargas por usuario.
- [x] Metadatos de archivo iniciales.
- [x] Demo funcional de archivos dentro del `Demo Module`.
- [ ] Subida hacia Spaces.
- [ ] Asociacion archivo <-> entidad de negocio.
- [ ] Cola de descargas pesadas.
- [ ] Versionado de archivos real.
- [ ] Procesamiento async de archivos.

## P6. Notificaciones
Estado: En progreso

- [x] Notificaciones internas.
- [x] Bandeja por usuario.
- [x] Marcado individual de lectura.
- [x] Marcado masivo de lectura.
- [x] Campanita con contador basico.
- [x] Demo funcional de notificaciones dentro del `Demo Module`.
- [ ] Email.
- [ ] SMS / WhatsApp.
- [ ] Push si aplica.
- [ ] Preferencias por usuario.
- [ ] Historial avanzado y reintentos por canal.

## P7. UX transversal
Estado: Parcial

- [x] Toast base.
- [x] Confirmaciones base.
- [ ] Alerts y banners globales.
- [ ] Skeleton loaders.
- [ ] Empty states reales.
- [ ] Manejo global de errores HTTP.
- [ ] Feedback optimista/pesimista estandarizado.

## P8. Jobs y procesos en segundo plano
Estado: En progreso

- [x] Tablas base de jobs.
- [x] Registro transversal de ejecucion `core_job_runs`.
- [x] Dispatch de job demo a cola.
- [x] Modo inmediato para pruebas locales sin worker.
- [x] Logs de ejecucion por job basicos.
- [x] Demo funcional de jobs dentro del `Demo Module`.
- [ ] Workers supervisados.
- [ ] Retries y backoff definidos.
- [ ] Cron jobs.
- [ ] Propagacion de tenant y actor.
- [ ] Reintentos visibles por UI y observabilidad operativa.
- [ ] Demo funcional de jobs con procesamiento realmente asincrono en entorno local dockerizado.

## P9. Exportacion e importacion
Estado: Pendiente

- [ ] Exportar a Excel.
- [ ] Exportar a CSV.
- [ ] Exportar a PDF.
- [ ] Exportaciones pesadas async.
- [ ] Importacion masiva.
- [ ] Validacion previa.
- [ ] Logs de importacion.
- [ ] Demo funcional de export/import dentro del `Demo Module`.

## P10. Busqueda y filtros avanzados
Estado: Pendiente

- [ ] Busqueda global real.
- [ ] Filtros combinables.
- [ ] Guardado de filtros.
- [ ] Integracion real con Meilisearch.
- [ ] Reindexacion.

## P11. Logs, auditoria y trazabilidad
Estado: En progreso

- [x] Actividad de usuario base.
- [x] Audit trail inicial para modulos, archivos y jobs.
- [x] Vista demo de auditoria dentro del `Demo Module`.
- [ ] Logs de errores.
- [ ] Auditoria de impersonacion y cambios de permisos.
- [ ] Vista administrativa completa de logs.
- [ ] Correlation IDs y trazabilidad tecnica mas profunda.

## P12. API e integraciones
Estado: Parcial

- [x] API `v1`.
- [x] Healthcheck.
- [x] Endpoints de modulos.
- [x] Retiro de la capa HTTP legacy no enroutada fuera de `api/v1`.
- [ ] Auth API para terceros.
- [ ] Webhooks salientes.
- [ ] Recepcion de webhooks.
- [ ] Rate limiting.
- [ ] Swagger sincronizado con endpoints reales.

## P13. Seguridad
Estado: Pendiente

- [ ] Sanitizacion de inputs.
- [ ] XSS / CSRF segun canal.
- [ ] Rate limiting por auth/API/descargas.
- [ ] Encriptacion de datos sensibles.
- [ ] Logs de seguridad.
- [ ] Politicas de contrasenas y sesiones.
- [x] Restringir previews sensibles de recuperacion de password a entornos de desarrollo/prueba.

## P14. Internacionalizacion
Estado: Pendiente

- [ ] Formatos globales de fecha.
- [ ] Formatos de moneda.
- [ ] Zona horaria por tenant y usuario.
- [ ] Base para traducciones si se decide multi-idioma.

## P15. Personalizacion UI
Estado: Parcial

- [x] Menus dinamicos segun modulos habilitados.
- [x] Menus dinamicos segun permisos reales en administracion de modulos.
- [ ] Extender menus dinamicos segun permisos al resto del sistema.
- [ ] Tema dark/light persistido.
- [ ] Preferencias de vistas.
- [ ] Columnas dinamicas.

## P16. Higiene tecnica y operativa
Estado: En progreso

- [x] Resolver conflictos de merge abiertos.
- [x] Unificar migraciones base duplicadas.
- [x] Retirar capa HTTP/UI legacy fuera de la arquitectura actual.
- [x] Unificar seeders iniciales de permisos.
- [x] Limpiar branding principal heredado del template.
- [x] Endurecer toolchain frontend a una linea segura de `Vite`.
- [x] Reducir warning de chunk grande en build de Vite con `manualChunks`.
- [x] Revisar y mitigar vulnerabilidades reportadas por `npm audit`.
- [x] Recuperar `lint` frontend en verde.
- [x] Retirar modelos, tablas y seeders inactivos que no pertenecian al contrato modular actual.

## P17. Responsive y soporte movil
Estado: Parcial

- [x] Base responsive del template.
- [ ] Revisar pantallas reales del producto.
- [ ] PWA si aplica.
- [ ] Offline basico solo donde aporte valor.

## P18. Manejo de errores
Estado: Pendiente

- [ ] Catalogo de errores controlados backend.
- [ ] Mensajes amigables frontend.
- [ ] Correlation IDs.
- [ ] Fallbacks de UX.

## P19. Metricas internas
Estado: Pendiente

- [ ] Uso del sistema por tenant.
- [ ] Uso por modulo.
- [ ] Performance y tiempos de respuesta.
- [ ] Eventos clave de usuario.

## Siguiente desarrollo recomendado
1. Scope multi-tenant consistente en modelos, jobs, archivos y auditoria.
2. CRUD universal base del core.
3. Integracion de archivos con Spaces y entidades de negocio.
4. Export/import demo funcional.
5. Definir el set final de catalogos universales y mover cualquier dominio extra a modulos verticales.
