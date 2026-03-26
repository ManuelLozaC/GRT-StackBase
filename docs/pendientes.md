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
- [x] Login con correo o alias.
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
- [x] Bloqueo operativo basico de dependencias entre modulos al activar/desactivar.
- [x] Retiro de la fachada `authStore`; frontend ya consume stores separados directamente.
- [x] Tenancy mas consistente en notificaciones internas y descargas de archivos base.
- [x] Validacion automatizada de aislamiento por tenant en notificaciones, archivos, descargas y auditoria demo.
- [x] Limpieza de branding residual visible (`README` raiz y restos `SAKAI` del shell/landing).
- [x] Settings operativos por modulo con persistencia, API y UI administrativa.
- [x] Export/import CSV sobre el Data Engine con historial tenant-aware de corridas.
- [x] Exportaciones `Excel/PDF` y modo `async` sobre el Data Engine con demo dentro de `Demo Module`.
- [x] Settings globales, por organizacion y por usuario con bootstrap frontend.
- [x] Feature flags base del core sin tocar codigo.
- [x] Banner global y manejo global de errores HTTP en el shell.
- [x] Preferencias de usuario para tema, formato y notificaciones base.
- [x] Multi-rol por usuario con administracion operativa desde UI/API.
- [x] Impersonacion administrativa con auditoria y restauracion de sesion original.
- [x] Estructuras tenant base: empresas, sucursales y equipos.
- [x] Data Engine extendido con relaciones y custom fields sobre recursos reales.
- [x] Base multicanal de notificaciones con log de entregas por canal.
- [x] Skeleton loaders y empty states reutilizables en pantallas reales.
- [x] Request IDs expuestos en header y respuestas API.
- [x] Rate limiting base para auth, escrituras de datos y descargas.
- [x] Logs de seguridad tenant-aware con vista administrativa.
- [x] Operations overview administrativo para jobs, transfers, notificaciones, archivos, auditoria y seguridad.
- [x] Error logs tecnicos tenant-aware para excepciones no controladas.
- [x] Metricas internas base por tenant, modulo y categoria.
- [x] Aplicacion real de locale, moneda y zona horaria desde settings.
- [x] Tokens API personales para integraciones de terceros.
- [x] Sanitizacion base de inputs API y politicas de password mas estrictas.
- [x] Preferencias persistidas de vistas/columnas para Data Engine.
- [x] Metricas base de performance y tiempo de respuesta API.
- [x] Webhooks salientes tenant-aware con secretos cifrados, entregas auditables y pantalla administrativa.
- [x] Recepcion de webhooks tenant-aware con firma HMAC, receipts auditables y endpoint publico controlado.
- [x] OpenAPI JSON sincronizado con rutas reales del backend.
- [x] Headers de seguridad base para respuestas API.
- [x] Ajustes responsive en pantallas administrativas clave (`modulos`, `operations`, `security`, `API tokens`, `webhooks`).
- [x] Bootstrap oficial inicial con `GRT SRL`, `TalentHub` y `Manuel Loza`.
- [x] Recursos base del dominio expuestos por Data Engine: `organizations`, `offices`, `people`, `divisions`, `areas`, `positions`, `work-assignments`.
- [x] Recursos base del dominio gestionables desde frontend a traves de Data Engine.

### Brechas principales de evolucion

Nota:

- los items abiertos de esta seccion ya no bloquean el cierre de la version base
- funcionan como backlog vivo de endurecimiento y evolucion del stack

- [ ] RBAC completo.
- [ ] Multi-tenant operativo completo en todo el dominio.
- [x] CRUD universal conectado a backend.
- [ ] Sistema real de archivos.
- [ ] Extender archivos hacia Spaces, versionado avanzado y descargas pesadas async.
- [ ] Jobs avanzados.
- [ ] Notificaciones avanzadas y multicanal.
- [ ] Auditoria y logs avanzados.
- [ ] Demos funcionales de las capacidades genericas pendientes.
- [ ] Convertir `Demo Module` en catalogo vivo amplio y curado de UI y patrones reutilizables del stack.
- [ ] Definir los catalogos universales reales que el core soportara de forma explicita.
- [x] Unificar metadata modular backend/frontend para que rutas y menu se consuman por API.
- [x] Pipeline CI base para backend y frontend.
- [x] Arquitectura operativa base alineada entre Docker local y Droplets.

## Regla transversal del proyecto
- [x] Toda funcionalidad generica importante debe vivir en el core.
- [x] Toda funcionalidad generica importante debe tener una demo dentro de `Demo Module`.
- [x] El `Demo Module` debe poder habilitarse o deshabilitarse desde administracion.
- [x] Cada demo debe ser funcional, no solo visual.

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
- [x] Webhooks declarados por modulo y consumidos por UI/API administrativa.
- [x] Metadata modular extendida con `jobs`, `webhooks`, `dashboards`, `seeders` y `assets`.
- [ ] Contrato formal de una demo por capacidad transversal.
- [x] Carga de menus y rutas del `Demo Module` desde manifest declarativa frontend.
- [x] Carga de menus y rutas del `Demo Module` desde metadata declarativa backend/API.
- [x] Orden de carga y dependencias entre modulos para el bootstrap actual.
- [x] Bloqueo operativo de dependencias entre modulos al activar/desactivar.
- [x] Exponer metadata modular extendida por API para bootstrap frontend.
- [x] Soportar permisos operativos declarados por modulo para seed/bootstrap.

## P1. Identidad y acceso
Estado: En progreso

- [x] Login real.
- [x] Logout real.
- [x] Perfil autenticado (`me`).
- [x] RBAC inicial para administracion de modulos.
- [x] Registro de usuarios.
- [x] Login por alias.
- [x] Recuperacion y reseteo de password.
- [x] Token auth inicial.
- [ ] Ampliar RBAC por endpoint y accion para mas areas del sistema.
- [x] Multi-rol por usuario.
- [x] Impersonacion con auditoria.
- [x] Guardas frontend conectadas a auth real.
- [x] Nuevo permiso operativo `settings.manage`.
- [x] Administracion de usuarios con sync de roles e impersonacion.
- [x] Gestion operativa de usuarios con alias, persona vinculada, estado y reset de contrasena.
- [x] Auth expone y mantiene `asignacion_laboral_activa` para preparar permisos por contexto.
- [x] Frontend ya permite visualizar y cambiar la `asignacion_laboral_activa` desde la sesion del usuario.
- [x] El control de permisos base ya puede resolverse desde la `asignacion_laboral_activa`, no solo desde roles globales.

## P2. Usuarios, organizaciones y tenancy
Estado: En progreso

- [x] Migracion base de organizaciones.
- [x] Relacion usuario <-> organizacion.
- [x] Tenant activo por usuario autenticado.
- [x] Cambio de tenant activo por API.
- [x] Selector de organizacion activa en topbar.
- [x] Migraciones de empresas.
- [x] Migraciones de sucursales.
- [x] Migraciones de equipos.
- [x] Migraciones base de `oficinas`, `personas`, `divisiones`, `areas`, `cargos` y `asignaciones_laborales`.
- [ ] Aplicar en codigo y migraciones la decision final `organizacion = empresa`.
- [ ] Tenant activo por request en todos los servicios backend.
- [x] Configuracion por tenant.
- [ ] Scope multi-tenant consistente en modelos, jobs, archivos y auditoria.
- [x] Extender tenancy base a notificaciones y descargas de archivos para reducir filtros manuales por organizacion.
- [x] Cubrir con tests el aislamiento por tenant en archivos, descargas, notificaciones y auditoria demo.
- [x] Cubrir con tests el aislamiento por tenant en el recurso demo del CRUD universal.
- [x] Base fundacional de tenancy adelgazada para no mezclar catalogos de ubicacion o personas no usados por el core.
- [x] `TenantContext` compartido para request autenticado y jobs base.
- [x] CRUD tenant-aware para estructuras `empresa/sucursal/equipo`.
- [x] CRUD base tenant-aware para `oficinas`, `personas`, `divisiones`, `areas`, `cargos` y `asignaciones_laborales` via Data Engine.
- [x] Sincronizacion runtime base `organizacion -> empresa` y `oficina -> sucursal` para reducir divergencia con el legado mientras se completa la convergencia final.
- [x] Etiquetas operativas mas utiles en Data Engine para `personas` y `asignaciones_laborales`, incluyendo jefe y aprobador por contexto.
- [x] Base de contexto laboral por usuario con `asignacion_laboral_activa` y cambio explicito dentro de la organizacion activa.
- [x] Catalogo visible del Data Engine ya prioriza `Empresas` y `Oficinas`, ocultando recursos transicionales del legado.
- [x] Payloads principales de auth y shell frontend ya exponen y muestran `empresa_activa` / `empresas` como lenguaje visible del dominio.
- [x] Auth y frontend ya pueden operar con alias tecnicos `empresa_id` y `active-company` sin romper compatibilidad legacy.
- [x] Settings bootstrap, API administrativa y frontend ya aceptan/consumen alias `company` como capa preferente del dominio.
- [x] Selector visual de contexto laboral activo en topbar y dashboard.
- [x] RBAC contextual inicial reutiliza las mismas claves de permiso del sistema a traves de metadata por asignacion.

## P3. Configuracion del sistema
Estado: En progreso

- [x] Variables globales persistentes.
- [x] Configuracion por modulo.
- [x] Feature flags.
- [ ] Parametros dinamicos sin deploy.
- [x] Configuracion por usuario.
- [x] Configuracion por organizacion.
- [x] UI administrativa de settings.
- [x] Bootstrap frontend de settings y preferencias.

## P4. Motor de datos y CRUD universal
Estado: En progreso

- [x] CRUD generico backend.
- [x] Paginacion.
- [x] Ordenamiento.
- [x] Filtros dinamicos.
- [x] Busqueda global.
- [x] Validacion backend estandar.
- [x] Serializacion uniforme.
- [x] Campos personalizados.
- [x] Componentes frontend reutilizables de tabla y formulario.
- [x] Retirar el CRUD historico del template de la navegacion principal.
- [x] Implementar el nuevo motor CRUD del core con contrato reutilizable para modulos.
- [x] Extender el Data Engine a relaciones y custom fields.
- [ ] Extender el Data Engine a acciones avanzadas, relaciones mas profundas y custom fields realmente universales.

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
- [x] Subida hacia Spaces.
- [x] Fallback controlado `spaces -> local` para desarrollo cuando faltan credenciales.
- [ ] Asociacion archivo <-> entidad de negocio.
- [x] Asociacion base archivo <-> entidad de negocio mediante `resource_key`, `record_id` y `record_label`.
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
- [ ] Email real.
- [ ] SMS / WhatsApp real. // no avanzar de momento
- [ ] Push real si aplica. 
- [x] Preferencias por usuario.
- [x] Historial base de entregas por canal.
- [ ] Reintentos reales por canal e integraciones externas.

## P7. UX transversal
Estado: Parcial

- [x] Toast base.
- [x] Confirmaciones base.
- [x] Alerts y banners globales.
- [x] Skeleton loaders.
- [x] Empty states reales.
- [x] Manejo global de errores HTTP.
- [ ] Feedback optimista/pesimista estandarizado.
- [x] Showcase UI inicial del `Demo Module` con ejemplos de toasts, modals, banners, forms, inputs, datepickers, tablas y estados visuales.

## P8. Jobs y procesos en segundo plano
Estado: En progreso

- [x] Tablas base de jobs.
- [x] Registro transversal de ejecucion `core_job_runs`.
- [x] Dispatch de job demo a cola.
- [x] Modo inmediato para pruebas locales sin worker.
- [x] Logs de ejecucion por job basicos.
- [x] Demo funcional de jobs dentro del `Demo Module`.
- [x] Workers supervisados en Docker Compose base.
- [ ] Retries y backoff definidos.
- [x] Cron jobs base mediante servicio `scheduler`.
- [ ] Propagacion de tenant y actor.
- [ ] Reintentos visibles por UI y observabilidad operativa.
- [ ] Demo funcional de jobs con procesamiento realmente asincrono en entorno local dockerizado.

## P9. Exportacion e importacion
Estado: En progreso

- [x] Exportar a Excel.
- [x] Exportar a CSV.
- [x] Exportar a PDF.
- [x] Exportaciones async base con cola y descarga diferida.
- [ ] Exportaciones pesadas async con worker/observabilidad mas profunda.
- [x] Disk de exportaciones async configurable y listo para converger a Spaces.
- [x] Importacion masiva CSV sobre recurso del Data Engine.
- [x] Validacion previa segun metadata del recurso.
- [x] Logs de importacion y exportacion por corrida.
- [x] Demo funcional de export/import dentro del `Demo Module`.

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
- [x] Security logs administrativos con request ID, actor e IP.
- [x] Vista administrativa de operations overview.
- [x] Logs de errores.
- [x] Auditoria de impersonacion.
- [ ] Auditoria de cambios de permisos.
- [ ] Vista administrativa completa de logs tecnicos y de errores.
- [x] Correlation IDs y trazabilidad tecnica base.
- [x] Historial administrativo de entregas de webhooks salientes.
- [x] Historial administrativo de recepciones de webhooks entrantes.

## P12. API e integraciones
Estado: Parcial

- [x] API `v1`.
- [x] Healthcheck.
- [x] Endpoints de modulos.
- [x] Retiro de la capa HTTP legacy no enroutada fuera de `api/v1`.
- [x] Auth API para terceros.
- [x] Webhooks salientes.
- [x] Recepcion de webhooks.
- [x] Rate limiting base.
- [x] Swagger/OpenAPI sincronizado con endpoints reales.

## P13. Seguridad
Estado: En progreso

- [x] Sanitizacion de inputs.
- [ ] XSS / CSRF segun canal.
- [x] Headers de seguridad base en canal API.
- [x] Rate limiting por auth/API/descargas.
- [ ] Encriptacion de datos sensibles.
- [x] Cifrado de secretos sensibles de webhooks.
- [x] Logs de seguridad.
- [x] Politicas de contrasenas y sesiones.
- [x] Restringir previews sensibles de recuperacion de password a entornos de desarrollo/prueba.

## P14. Internacionalizacion
Estado: En progreso

- [x] Formatos globales de fecha.
- [x] Formatos de moneda.
- [x] Zona horaria por tenant y usuario.
- [ ] Base para traducciones si se decide multi-idioma. // no realizar hasta nuevo aviso

## P15. Personalizacion UI
Estado: Parcial

- [x] Menus dinamicos segun modulos habilitados.
- [x] Menus dinamicos segun permisos reales en administracion de modulos.
- [ ] Extender menus dinamicos segun permisos al resto del sistema.
- [x] Tema dark/light persistido.
- [x] Preferencias de vistas.
- [x] Columnas dinamicas.

## P16. Reorganizacion del core modular
Estado: En progreso

- [x] Retirar catalogos y modelos inactivos del arranque base.
- [x] Manifest unica del `Demo Module` en frontend para evitar duplicacion de rutas/menu.
- [x] Metadata modular backend ampliada con `dependencies`, `permissions`, `settings` y `features`.
- [x] Separar stores de sesion, tenant y permisos.
- [x] Retirar la capa de compatibilidad `authStore` y consumir stores especializados directamente.
- [x] Separar migraciones fundacionales por responsabilidad (`organizaciones`, `users`, `organizacion_user`).
- [x] Depurar `core-menu` para dejar solo shell realmente transversal del producto.
- [x] Convertir bootstrap modular frontend a metadata consumida por API.
- [x] Refrescar catalogo modular completo tras toggles para no dejar estados derivados desfasados en frontend.
- [ ] Reducir mas el shell core a rutas/utilidades estrictamente transversales.

## P17. Higiene tecnica y operativa
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
- [x] Pipeline CI operativo en GitHub Actions para backend y frontend, con timeouts, concurrencia y chequeo base de rutas.

## P18. Responsive y soporte movil
Estado: Parcial

- [x] Base responsive del template.
- [x] Ajustes responsive en pantallas administrativas clave.
- [ ] Revisar resto de pantallas reales del producto.
- [ ] PWA si aplica.
- [ ] Offline basico solo donde aporte valor.

## P19. Manejo de errores
Estado: En progreso

- [x] Catalogo de errores controlados backend.
- [x] Mensajes amigables frontend.
- [x] Correlation IDs.
- [ ] Fallbacks de UX.

## P20. Metricas internas
Estado: En progreso

- [x] Resumen operativo base por tenant.
- [x] Uso del sistema por tenant.
- [x] Uso por modulo.
- [x] Performance y tiempos de respuesta.
- [x] Eventos clave de usuario.

## Siguiente desarrollo recomendado
1. Seguir refinando `Demo Module` como biblioteca viva, didactica y consistente del stack.
2. Ampliar pruebas frontend y cobertura automatica de release.
3. Endurecer tenancy transversal restante en modelos, jobs, auditoria y notificaciones externas.
4. Cerrar versionado de archivos, descargas pesadas async y entidades de negocio restantes.
5. Automatizar observabilidad, backups y despliegue continuo sobre Droplets.

## Objetivos inmediatos desde aqui en adelante
1. Mantener la version base estable mientras el `Demo Module` sigue creciendo como referencia de implementacion.
2. Endurecer tenancy transversal en modelos, jobs, archivos y auditoria.
3. Ampliar pruebas frontend y checks de release sin inflar el core.
4. Mantener visibles los objetivos de mediano plazo: observabilidad, backups, despliegue automatizado y evolucion modular.
5. Seguir limpiando residuales tecnicos sin reabrir deuda estructural de la base.

## Indicador de avance global

- Avance global estimado del proyecto: `100%`
- Trabajo restante estimado para cerrar esta version base: `0%`

Lectura del porcentaje:

- el nucleo de plataforma ya existe y es util
- la version base ya puede considerarse cerrada
- lo que sigue desde aqui corresponde a endurecimiento y evolucion
- este porcentaje debe actualizarse siempre que se cierre un bloque relevante del backlog
