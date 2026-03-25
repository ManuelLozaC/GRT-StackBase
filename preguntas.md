# Preguntas Pendientes - StackBase 2026

> Responde debajo de cada punto o reemplaza `PENDIENTE` por tu decisión.

## 1. Modelo organizacional base

### 1.1 Empresa vs organización

- Decisión: `PENDIENTE`
- Pregunta: ¿quieres que `empresa` y `organizacion` sean la misma entidad lógica del sistema base, o prefieres separar:
  `organizacion` = tenant del sistema
  `empresa` = razón social o entidad comercial dentro del tenant?
- Respuesta: Que sea una sola organizacion

### 1.2 Cliente del sistema

- Decisión: `PENDIENTE`
- Pregunta: cuando un cliente use un sistema construido con este stack, ¿cada cliente será normalmente un tenant independiente completo?
- Respuesta: no tneitneod, elabora mejora, explica

### 1.3 Oficina / sucursal

- Decisión: `PENDIENTE`
- Pregunta: ¿una empresa puede tener múltiples oficinas/sucursales y un usuario puede pertenecer a una sola oficina principal o a varias?
- Respuesta: puede pertenecer a varias y tener roles/permisos distintos en cada una

## 2. Personas, usuarios y datos base

### 2.1 Persona vs usuario

- Decisión: `PENDIENTE`
- Pregunta: ¿quieres manejar `persona` separada de `usuario`?
  Sugerencia: sí, porque permite que una persona exista aunque aún no tenga acceso al sistema.
- Respuesta: Si, seguramente crearemos sistemas que manejen led, clientes, ventas y vendra util

### 2.2 Campos obligatorios de persona

- Decisión: `PENDIENTE`
- Pregunta: además de nombre, sexo, fecha de nacimiento, teléfono, correo y ciudad/país, ¿qué otros campos deben ser base?
  Posibles: apellido paterno, apellido materno, documento, complemento, dirección, foto, estado civil.
- Respuesta:si, apellido paterno, apellido materno, carnet (ci/dni/documento identidad), direccion, la foto tambien, pero todos los archivos externos al sistema donde seguardarian? no me queda claro. dame sugerencias y revisamos esta pregunta


### 2.3 Usuario inicial

- Decisión: `PENDIENTE`
- Pregunta: confirmo estos datos iniciales para semilla automática:
  Nombre completo: Manuel Loza
  Correo: `mloza@grt.com.bo`
  Teléfono: `+591 70818566`
  Rol: superusuario/administrador
  Sexo: masculino
  Fecha nacimiento: `1984-08-02`
  Empresa: `GRT SRL`
  Ciudad: `Santa Cruz de la Sierra`
  País: `Bolivia`
  Oficina: `TalentHub`
  Contraseña por defecto: `admin1984!`
- Respuesta: correcto

## 3. Estructura laboral interna

### 3.1 Áreas y divisiones

- Decisión: `PENDIENTE`
- Pregunta: ¿quieres que el stack base incluya desde el inicio estas entidades?
  `areas`, `divisiones`, `cargos`, `dependencias_jerarquicas`
- Respuesta: correcto + jefaturas

### 3.2 Jefaturas

- Decisión: `PENDIENTE`
- Pregunta: para modelar jefes sugiero esta estructura:
  `area` pertenece a una empresa
  `cargo` define el puesto
  `asignacion_laboral` relaciona persona + oficina + área + cargo
  `jefe_asignacion_id` apunta a la asignación superior inmediata
  ¿te parece bien esa base?
- Respuesta: correcto

### 3.3 Historial laboral

- Decisión: `PENDIENTE`
- Pregunta: ¿debe existir historial de cambios de área/cargo/jefe desde el stack base o basta con la asignación vigente?
- Respuesta: debe haber log de cambios con fechas y quien hizo los cambios, estos logs deben poder ser limpiados por el admin para reducir espacio de BD

## 4. Multi-tenancy y alcance

### 4.1 Superusuario global

- Decisión: `PENDIENTE`
- Pregunta: ¿el superusuario debe poder ver y administrar todos los tenants/clientes del sistema?
- Respuesta: si

### 4.2 Administración por tenant

- Decisión: `PENDIENTE`
- Pregunta: ¿cada tenant tendrá sus propios administradores con alcance solo a su empresa/organización?
- Respuesta: si pero no seran administradores, podemos tener jefes, gerentes, encargados de sistemas, etc, administradores solo seremos los de GRT que taendemos a todos los clientes

## 5. Autenticación y acceso

### 5.1 Login

- Decisión: `PENDIENTE`
- Pregunta: ¿el login base será con correo + contraseña únicamente, o también quieres usuario/alias?
- Respuesta: correo o usuario/alias  

### 5.2 Recuperación de acceso

- Decisión: `PENDIENTE`
- Pregunta: ¿quieres incluir desde la base recuperación por correo, cambio de contraseña obligatorio al primer ingreso, expiración de contraseña o doble factor?
- Respuesta: si a todo menos doble factor por ahora pero dejemos las bases establecidas

### 5.3 Sesiones

- Decisión: `PENDIENTE`
- Pregunta: ¿prefieres tokens API con Sanctum para SPA y sesión persistente por dispositivo?
- Respuesta: si

## 6. Catálogos base

### 6.1 Geografía

- Decisión: `PENDIENTE`
- Pregunta: ¿quieres cargar solo Bolivia al inicio o una base más amplia de países/ciudades?
- Respuesta: solo Bolivia por ahora

### 6.2 Datos comunes

- Decisión: `PENDIENTE`
- Pregunta: ¿qué catálogos deben existir sí o sí desde el día 1?
  Posibles: países, ciudades, tipos de documento, géneros, estados, monedas, idiomas.
- Respuesta: todos esos

## 7. Frontend y tecnología

### 7.1 JavaScript vs TypeScript

- Decisión: `PENDIENTE`
- Pregunta: para que el stack quede más tipado y mantenible, ¿apruebas migrar el frontend base a TypeScript?
- Respuesta: si

### 7.2 Idioma de naming

- Decisión: `PENDIENTE`
- Pregunta: ¿quieres naming 100% en español en dominio y código de aplicación, dejando solo anglicismos técnicos inevitables?
- Respuesta: si, correcto y establecer reglas de naming, comentarios, codigo, etc, etc paraque el agente de desarrollo siempre sepa como desarrollar y sea consistente

## 8. Producción en Droplets

### 8.1 Topología

- Decisión: `PENDIENTE`
- Pregunta: ¿piensas usar un solo Droplet por proyecto al inicio o una separación posterior entre app/db/search?
- Respuesta: un solo droplet por ahora  con la opcion de separar la BD a un servicio de bases solido y seguro 

### 8.2 TLS y proxy

- Decisión: `PENDIENTE`
- Pregunta: ¿quieres mantener `nginx-proxy-manager` en la arquitectura de producción o prefieres Nginx tradicional con configuración manual?
- Respuesta: lo que sea mas eficien

### 8.3 Base de datos administrada

- Decisión: `PENDIENTE`
- Pregunta: ¿MySQL vivirá dentro del Droplet con Docker o contemplas usar un servicio administrado más adelante?
- Respuesta: por ahora vivira dentro del droplet pero quiero poder ponerlo en un servicio administrado luego

## 9. Seeders y comportamiento de primer arranque

### 9.1 Momento de creación automática

- Decisión: `PENDIENTE`
- Pregunta: cuando el sistema se levante desde cero, ¿quieres que la creación de datos base ocurra automáticamente dentro del flujo de arranque o mediante un comando de instalación explícito?
- Respuesta: que ocurra automaticamente

### 9.2 Idempotencia

- Decisión: `PENDIENTE`
- Pregunta: confirmo que si las tablas ya existen o los datos base ya están cargados, el proceso no debe duplicar registros.
- Respuesta: si, confirma, no debes duplicar datos ni borrar datos existentes

## 10. Alcance inicial del stack

### 10.1 CRUDs obligatorios

- Decisión: `PENDIENTE`
- Pregunta: además de login y usuarios, ¿quieres dejar funcional desde la primera versión estos CRUDs base?
  `empresas/organizaciones`, `oficinas`, `personas`, `roles/permisos`, `areas/divisiones/cargos`
-  Respuesta:  si por favor

### 10.2 Qué queda dentro y qué queda fuera

- Decisión: `PENDIENTE`
- Pregunta: ¿hay algo que definitivamente no quieras dentro del stack base para no sobredimensionarlo?
- Respuesta: como que? no me queda claro
