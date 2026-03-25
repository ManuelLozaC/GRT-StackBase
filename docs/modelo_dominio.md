# MODELO DE DOMINIO Y DATOS
> Modelo objetivo para una plataforma modular multi-tenant.

## Entidades implementadas hoy
### system_modules
Catalogo persistente de modulos instalados.

Campos base:
- `key`
- `name`
- `description`
- `version`
- `provider`
- `is_enabled`
- `is_demo`

Uso:
- sincronizar modulos declarados en configuracion
- permitir toggles desde administracion
- habilitar o bloquear acceso a modulos

### organizaciones
Tenant operativo base del sistema.

Campos base:
- `nombre`
- `slug`
- `metadata`

Relacionadas con:
- `users.organizacion_activa_id`
- `organizacion_user`

### core_files
Archivo gestionado por el core.

Campos base:
- `uuid`
- `organizacion_id`
- `uploaded_by`
- `disk`
- `path`
- `original_name`
- `mime_type`
- `size_bytes`
- `version`
- `security_token`
- `metadata`

Uso:
- subida y almacenamiento transversal
- descarga directa autenticada
- generacion de signed URLs
- base para versionado y asociacion con entidades futuras

### core_file_downloads
Historial de descargas por usuario y tenant.

Campos base:
- `managed_file_id`
- `organizacion_id`
- `user_id`
- `channel`
- `status`
- `downloaded_at`
- `metadata`

### core_job_runs
Registro transversal de ejecuciones asincronas o inmediatas.

Campos base:
- `uuid`
- `organizacion_id`
- `requested_by`
- `job_key`
- `queue`
- `status`
- `requested_payload`
- `result_payload`
- `attempts`
- `dispatched_at`
- `started_at`
- `finished_at`
- `failed_at`
- `error_message`

### core_audit_logs
Registro transversal de actividad funcional del sistema.

Campos base:
- `organizacion_id`
- `actor_id`
- `event_key`
- `entity_type`
- `entity_key`
- `source_module`
- `summary`
- `context`
- `occurred_at`

### core_notifications
Registro de notificaciones internas por usuario y tenant.

Campos base:
- `uuid`
- `organizacion_id`
- `recipient_id`
- `created_by`
- `channel`
- `level`
- `title`
- `message`
- `action_url`
- `metadata`
- `read_at`

## Entidades core planificadas
### identidad
- usuarios
- roles
- permisos
- sesiones

### tenancy
- organizaciones
- empresas
- sucursales
- equipos
- usuario_empresa

### configuracion
- settings_globales
- settings_por_modulo
- settings_por_tenant
- settings_por_usuario

### archivos
- core_files
- versiones_archivo
- core_file_downloads

### observabilidad
- auditorias
- logs_tecnicos
- eventos_usuario
- core_job_runs
- core_audit_logs
- core_notifications

## Reglas de modelado
- IDs internos numericos.
- IDs publicos `uuid` cuando expongan entidades sensibles.
- Soft delete en entidades funcionales donde aplique.
- Toda entidad core futura debe evaluar impacto multi-tenant.
- Toda capacidad transversal importante debe poder ser ejercitada desde `Demo Module`.

## Relacion entre core y modulos
- El core define servicios e infraestructura.
- Los modulos agregan entidades de negocio.
- Los modulos no deben duplicar tablas o logica transversal del core.
