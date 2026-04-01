# Modelo de dominio y datos

> Modelo vigente de referencia para el stack base.

## Decisiones cerradas

- `organizacion = empresa`
- cada cliente de GRT sera un tenant aislado
- `persona` y `usuario` son entidades separadas
- una organizacion puede tener multiples oficinas o sucursales
- una persona o usuario puede tener multiples asignaciones laborales
- una misma persona puede tener cargos, jefes, aprobadores y permisos distintos por oficina

## Nucleo de dominio objetivo

### Tenancy

- `organizaciones`
  tenant principal del sistema y razon social operativa
- `oficinas`
  sedes o sucursales que pertenecen a una organizacion
- `equipos`
  agrupaciones operativas dentro de la organizacion cuando aplique

### Personas e identidad

- `personas`
  entidad humana base, tenga o no acceso al sistema
- `usuarios`
  credenciales y acceso digital asociados opcionalmente a una persona
- `roles`
- `permisos`
- `sesiones` o `tokens`

### Estructura laboral

- `divisiones`
- `areas`
- `cargos`
- `asignaciones_laborales`
- `historial_asignaciones` o equivalente auditable

La entidad clave sera `asignacion_laboral`, porque es la que permite modelar:

- oficina de trabajo
- cargo
- area
- division
- jefe inmediato
- aprobador
- vigencia
- permisos operativos por contexto

## Escenario clave que el modelo debe satisfacer

Una misma persona puede ser:

- ejecutiva de ventas en sucursal 1
- gerente o jefa en sucursal 3

Eso implica que el stack debe soportar, por cada asignacion laboral:

- rol operativo distinto
- jefe distinto
- aprobador distinto
- permisos distintos
- vigencia distinta

## Entidades implementadas hoy

### `system_modules`

Catalogo persistente de modulos instalados.

### `organizaciones`

Tenant operativo actual del sistema.

Relacionadas con:

- `users.organizacion_activa_id`
- `organizacion_user`

### `core_files`

Archivo gestionado por el core.

### `core_file_downloads`

Historial de descargas por usuario y tenant.

### `core_job_runs`

Registro transversal de ejecuciones asincronas o inmediatas.

### `core_audit_logs`

Registro transversal de actividad funcional del sistema.

### `core_notifications`

Registro de notificaciones internas por usuario y tenant.

## Entidades ya presentes de forma parcial o tecnica

- `users`
- `organizacion_user`
- estructuras tenant base como `empresas`, `sucursales` y `equipos` en el runtime actual

Nota:
La documentacion del dominio ya asume la decision final `organizacion = empresa`, pero el codigo todavia debe converger por completo hacia ese modelo.

## Campos base esperados

### Persona

- nombres
- apellido_paterno
- apellido_materno
- documento_identidad
- telefono
- correo
- direccion
- sexo
- fecha_nacimiento
- ciudad_id
- pais_id
- foto_archivo_id o referencia equivalente

### Usuario

- persona_id
- alias
- email
- password
- organizacion_activa_id
- primer_acceso_pendiente
- expira_password_en
- estado

### Oficina

- organizacion_id
- nombre
- codigo
- ciudad_id
- direccion
- telefono
- estado

### Asignacion laboral

- organizacion_id
- oficina_id
- persona_id
- usuario_id
- division_id
- area_id
- cargo_id
- jefe_asignacion_id
- aprobador_asignacion_id
- es_principal
- fecha_inicio
- fecha_fin
- estado
- metadata

## Catalogos universales del core

Antes de crear un modulo nuevo, StackBase ya da por cerrados estos catalogos como parte del dominio transversal:

- `Empresas`
- `Oficinas`
- `Equipos`
- `Personas`
- `Divisiones`
- `Areas`
- `Cargos`
- `Asignaciones laborales`

Todos ellos son transversales porque sirven como estructura base del cliente y no imponen un flujo de negocio especifico.

La definicion operativa tambien vive en [`backend/config/core_catalogs.php`](/D:/Desarrollo/GRT-StackBase/backend/config/core_catalogs.php).

No deben entrar al core por defecto:

- `Leads`
- `Noticias`
- `Tickets`
- `Pedidos`
- `Oportunidades`
- cualquier entidad que necesite flujo, SLA, aprobaciones o UX propia

## Reglas de modelado

- IDs internos numericos
- IDs publicos `uuid` en entidades sensibles o expuestas externamente
- soft delete donde aporte valor funcional
- toda entidad del dominio debe evaluar impacto multi-tenant
- toda decision transversal relevante debe quedar documentada antes de crecer nuevas features
- los archivos externos permanentes deben vivir en DigitalOcean Spaces

## Relacion entre core y modulos

- el core define servicios, infraestructura y dominio base reutilizable
- los modulos agregan negocio especifico
- los modulos no deben duplicar tenancy, auth, archivos, observabilidad ni estructuras basicas del dominio
