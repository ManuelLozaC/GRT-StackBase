# Decisiones cerradas

> Registro consolidado de decisiones tomadas para StackBase.

## Dominio organizacional

- `organizacion = empresa`
- cada cliente de GRT sera un tenant aislado del sistema
- una organizacion puede tener multiples oficinas o sucursales
- una persona puede pertenecer a varias oficinas y tener roles o permisos distintos en cada una

## Personas y usuarios

- `persona` y `usuario` son entidades separadas
- el dominio base debe incluir:
  - nombres
  - apellido paterno
  - apellido materno
  - documento de identidad
  - telefono
  - correo
  - direccion
  - sexo
  - fecha de nacimiento
  - ciudad
  - pais
  - foto
- los archivos externos permanentes deben vivir en DigitalOcean Spaces

## Usuario inicial obligatorio

- nombre completo: `Manuel Loza`
- correo: `mloza@grt.com.bo`
- telefono: `+591 70818566`
- rol: `superusuario/administrador`
- sexo: `masculino`
- fecha de nacimiento: `1984-08-02`
- organizacion: `GRT SRL`
- ciudad: `Santa Cruz de la Sierra`
- pais: `Bolivia`
- oficina: `TalentHub`
- contrasena por defecto: `admin1984!`

## Estructura laboral

- el stack base debe incluir:
  - `areas`
  - `divisiones`
  - `cargos`
  - `jefaturas`
  - `asignaciones_laborales`
- la base aceptada es:
  - `asignacion_laboral` relaciona persona o usuario con oficina, area y cargo
  - `jefe_asignacion_id` apunta a la asignacion superior inmediata
- debe existir historial de cambios con fechas y actor
- los logs historicos podran limpiarse por administracion para reducir espacio

## Acceso y administracion

- el superusuario global de GRT puede ver y administrar todos los tenants
- los clientes no tendran administradores globales; tendran jefes, gerentes, encargados u otros roles operativos dentro de su tenant
- el login base debe aceptar correo o usuario/alias
- el stack debe incluir:
  - recuperacion por correo
  - cambio obligatorio de contrasena al primer ingreso
  - expiracion de contrasena
  - base preparada para doble factor, pero no implementacion completa inicial
- se usara Sanctum para SPA y sesion por dispositivo

## Catalogos base

- geografia inicial: solo Bolivia
- catalogos base obligatorios:
  - paises
  - ciudades
  - tipos de documento
  - generos
  - estados
  - monedas
  - idiomas

## Frontend y estandares

- el frontend base migrara a TypeScript
- el dominio y el codigo de aplicacion deben usar naming en espanol
- solo se permiten anglicismos tecnicos inevitables
- se deben establecer reglas formales de naming, comentarios y consistencia para desarrollo asistido

## Infraestructura

- desarrollo local: Docker Compose
- produccion inicial: un solo Droplet
- debe quedar abierta la opcion de mover la base de datos a un servicio administrado mas adelante
- se usara Nginx tradicional como proxy y TLS
- los archivos se almacenaran en DigitalOcean Spaces

## Primer arranque

- la creacion de datos base debe ocurrir automaticamente
- el bootstrap debe ser idempotente
- no debe duplicar ni borrar datos existentes

## Alcance inicial obligatorio

- login
- usuarios
- organizaciones
- oficinas
- personas
- roles y permisos
- areas
- divisiones
- cargos
- asignaciones laborales
