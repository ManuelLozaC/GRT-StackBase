# Pending - StackBase 2026

> Backlog maestro para convertir este repositorio en un stack base reutilizable, tipado, ordenado, multi-tenant y listo para iniciar nuevos productos sobre una base común.

## 1. Criterio de cierre del stack base

El stack base se considera listo cuando:

- Backend, frontend e infraestructura pueden levantarse desde cero con un flujo documentado y reproducible.
- La arquitectura local con Docker y la arquitectura productiva en DigitalOcean Droplets conservan la misma lógica de despliegue.
- Existe un núcleo multi-tenant seguro y probado.
- Hay convenciones obligatorias de arquitectura, tipado, respuesta API, storage, documentación y testing.
- El frontend deja de ser template/demo y pasa a ser una base corporativa real.
- Existe CI/CD mínimo, calidad automática y documentación suficiente para que otro equipo pueda arrancar un producto sin redefinir fundamentos.

## 2. Definiciones ya cerradas

- [x] Backend oficial: Laravel 12.
- [x] PHP objetivo: 8.3.
- [x] Compatibilidad mínima aceptable temporal: PHP 8.2.
- [x] Desarrollo local: Docker Compose.
- [x] Producción objetivo: DigitalOcean Droplets.
- [x] El diseño del stack debe mantener paridad arquitectónica entre local y producción.

## 3. Estado actual resumido

- Hay base funcional de Laravel en `backend/`.
- Hay un template de Vue/PrimeVue en `frontend/`, pero aún muy cerca del demo original.
- Existe `docker-compose.yml`, pero la configuración todavía no está completa para el estándar planteado.
- Ya aparecen piezas iniciales como `BaseModel`, `MultiTenantable`, Spatie Permission y Swagger.
- La documentación marca una dirección clara, pero varias reglas aún no están aterrizadas en código ni automatizadas.

## 4. Prioridad P0 - Bloqueantes para considerar esto un stack base serio

- [ ] Corregir y normalizar archivos base del repo.
  Estandarizar nombres como `.env.example` frente a `.env-example`, revisar codificación UTF-8 de documentación y limpiar texto corrupto por encoding.

- [ ] Definir el contrato oficial del stack base.
  Documentar explícitamente qué incluye siempre cualquier proyecto nacido desde esta base y qué cosas son opcionales por tipo de producto.

- [ ] Eliminar dependencia de templates/demo como base de negocio.
  Convertir el frontend actual en una base corporativa limpia, sin vistas demo, data demo ni componentes que no aporten al producto base.

- [ ] Completar el arranque reproducible end-to-end.
  Debe existir un único flujo claro para levantar backend, frontend, base de datos, redis, search y documentación local sin pasos ambiguos.

- [ ] Mantener paridad de arquitectura entre local y producción.
  Todo el diseño técnico, networking, envs, storage y servicios debe pensarse para correr en Docker local y desplegarse en Droplets sin rediseñar la solución.

## 5. Prioridad P1 - Núcleo de arquitectura backend

### 5.1 Arquitectura y organización

- [ ] Crear estructura formal para acciones/casos de uso.
  Implementar una capa tipo `Acciones/` o `Application/` y prohibir lógica de negocio en controladores.

- [ ] Crear clases base para acciones, DTOs, requests y recursos API.

- [ ] Definir convención de módulos de negocio.
  Por ejemplo: `Organizaciones`, `Empresas`, `Oficinas`, `Personas`, `Usuarios`, `Adjuntos`, `Auth`, `Permisos`, `Auditoria`.

- [ ] Crear una convención estándar para excepciones de dominio, validación y autorización.

- [ ] Definir una convención para services/providers internos y evitar crecimiento desordenado en `app/`.

### 5.2 Multi-tenancy

- [ ] Formalizar el modelo de multi-tenancy.
  Definir si todo se resuelve por `organizacion_id`, qué excepciones existen y qué modelos no deben llevar tenant scope.

- [ ] Definir si `empresa` y `organizacion` serán la misma entidad o entidades separadas.
  Esta decisión impacta clientes, oficinas, usuarios, datos maestros y permisos.

- [ ] Robustecer `MultiTenantable`.
  Agregar exclusiones explícitas, compatibilidad con consola/jobs/seeders, bypass administrativo controlado y pruebas automáticas.

- [ ] Crear entidad `organizaciones`.
  Incluir migración, modelo, relaciones, jerarquía y soporte para nested sets o la estrategia elegida.

- [ ] Garantizar aislamiento en consultas, escrituras, colas, exportaciones y búsquedas.

- [ ] Definir estrategia para superadmin/global admin sin romper aislamiento.

### 5.3 Modelos base y persistencia

- [ ] Completar `BaseModel`.
  Incluir UUID público, auditoría, convenciones compartidas, casts comunes y helpers reutilizables.

- [ ] Estandarizar columnas base obligatorias.
  `id`, `uuid`, `organizacion_id`, `created_by`, `updated_by`, `deleted_by`, timestamps y soft delete según corresponda.

- [ ] Crear traits reutilizables para UUID, auditoría y storage path.

- [ ] Definir y documentar convención de nombres de tablas, columnas, índices y foreign keys.

- [ ] Diseñar y crear el núcleo de entidades maestras base del stack.
  Como mínimo: `organizaciones/empresas`, `oficinas`, `personas`, `usuarios`, `ciudades` y `paises`, más sus relaciones necesarias.

- [ ] Crear migraciones base reales del dominio mínimo:
  `organizaciones`, `empresas`, `oficinas`, `personas`, `usuarios`, roles/permisos, adjuntos, auditoría y tablas de soporte.

- [ ] Resolver el modelo de estructura laboral interna.
  Diseñar entidades reutilizables para `areas`, `divisiones`, `cargos`, `asignaciones_laborales` y `jefaturas`.

- [ ] Implementar permisos y jerarquías por asignación laboral y por sucursal.
  Un mismo usuario/persona debe poder tener roles, cargo, jefes, aprobadores y permisos distintos en cada oficina/sucursal donde trabaje.

### 5.4 API y contrato JSON

- [ ] Implementar respuesta API estándar global:
  `{ estado, datos, mensaje, meta, errores }`.

- [ ] Crear `ApiResponse`, `ApiResource`, `PaginatedResource` y manejo centralizado de errores.

- [ ] Separar rutas API de rutas web.
  Hoy no existe `routes/api.php` como base explícita del proyecto API-first.

- [ ] Implementar versionado inicial de API.
  Ejemplo: `/api/v1`.

- [ ] Estandarizar paginación, filtros, ordenamiento y búsqueda.

- [ ] Definir convención para códigos de error y mensajes legibles por frontend/IA.

### 5.5 Seguridad y autenticación

- [ ] Instalar y configurar Laravel Sanctum completamente.

- [ ] Implementar login/logout/refresh/me como endpoints base.

- [ ] Implementar recuperación de contraseña, cambio de contraseña y política de primer acceso si se confirma como estándar base.

- [ ] Diseñar bootstrap de roles y permisos iniciales.
  Superadmin, admin de organización y usuario base como mínimo.

- [ ] Integrar políticas, gates y permisos por módulo.

- [ ] Definir estrategia de rate limiting, CORS, headers de seguridad y endurecimiento de producción.

- [ ] Crear seeders idempotentes para instalación nueva.
  Si las tablas base no existen o están vacías, el sistema debe crear automáticamente el superusuario inicial, la empresa/organización base, oficina, persona y catálogos mínimos.

- [ ] Registrar usuario inicial obligatorio del stack.
  Usuario: Manuel Loza, correo `mloza@grt.com.bo`, teléfono `+591 70818566`, rol superusuario/administrador, sexo masculino, fecha de nacimiento `1984-08-02`, empresa `GRT SRL`, ciudad `Santa Cruz de la Sierra`, país `Bolivia`, oficina `TalentHub`, contraseña por defecto `admin1984!`.

### 5.6 Gestión de usuarios y personas

- [ ] Crear CRUD backend de usuarios.
  Alta, edición, activación/desactivación, reseteo de contraseña, asignación de roles, empresa/organización y oficinas.

- [ ] Crear CRUD backend de personas.

- [ ] Definir la relación entre `persona` y `usuario`.

- [ ] Crear CRUD backend de oficinas.

- [ ] Crear CRUD backend de asignaciones laborales.
  Debe permitir asignar una persona/usuario a múltiples oficinas con distinto cargo, área, jefatura, permisos y vigencia.

- [ ] Crear CRUD backend de empresas/clientes si se confirma como entidad separada.

- [ ] Crear CRUD backend de ciudades y países o integrar catálogos geográficos base.

### 5.7 Storage y archivos

- [ ] Configurar DigitalOcean Spaces con driver S3 de extremo a extremo.

- [ ] Implementar generador oficial de rutas:
  `{anio}/{mes}/{entidad}/{id}/{tipo_documento}/{nombre_archivo}`.

- [ ] Prohibir almacenamiento permanente local por convención y por código.

- [ ] Crear servicio base para cargas, descargas firmadas, eliminación y metadatos.

- [ ] Crear módulo `adjuntos` reutilizable para cualquier entidad.

### 5.8 Búsqueda, colas y tiempo real

- [ ] Configurar Redis para colas reales y workers.

- [ ] Definir jobs base, colas nombradas y estrategia de reintentos.

- [ ] Configurar Meilisearch y decidir qué entidades indexan por defecto.

- [ ] Configurar Laravel Reverb o confirmar alternativa oficial de tiempo real.

- [ ] Crear patrón base para reportes pesados y exportaciones asincrónicas.

## 6. Prioridad P1 - Frontend base reutilizable

### 6.1 Limpieza y base de aplicación

- [ ] Eliminar vistas demo, datasets demo y servicios mock del template Sakai.

- [ ] Definir estructura de carpetas del frontend para proyectos reales.
  Ejemplo: `app/`, `modules/`, `shared/`, `layouts/`, `router/`, `stores/`, `services/`.

- [ ] Agregar Pinia como estándar real si va a ser parte del stack.
  Hoy está en la visión del proyecto pero no aparece como dependencia instalada.

- [ ] Crear cliente HTTP base con interceptores, manejo de errores, auth y tenant context.

- [ ] Definir sistema de rutas autenticadas y públicas.

### 6.2 Layouts y experiencia base

- [ ] Implementar `AuthLayout`.

- [ ] Implementar `AppLayout` real para producto empresarial.

- [ ] Crear shell base con sidebar, topbar, breadcrumb, loading global y manejo de permisos.

- [ ] Definir vista inicial de dashboard vacía/reutilizable, no demo.

- [ ] Crear páginas base: login, perfil, acceso denegado, no encontrado y error.

### 6.3 Gestión de acceso y usuarios en frontend

- [ ] Crear pantalla de login conectada al backend.

- [ ] Crear manejo de sesión, usuario autenticado y permisos en frontend.

- [ ] Crear módulo de gestión de usuarios en frontend.
  Listado, creación, edición, activación/desactivación, reseteo de contraseña y asignación de roles/empresa/oficinas.

- [ ] Crear formularios base para personas, usuarios, oficinas y empresas/clientes.

- [ ] Crear interfaz para administrar asignaciones laborales por oficina.
  Un usuario debe poder visualizar y editar sus distintos cargos, jefes, aprobadores y permisos según sucursal.

- [ ] Implementar guards de router por autenticación y permisos.

- [ ] Definir manejo global de sesión expirada y errores 401/403/422/500.

### 6.4 Componentes y diseño de sistema

- [ ] Crear librería mínima de componentes internos.
  Botón, input, select, modal, tabla, paginador, badges, empty states, alerts, loaders y formularios base.

- [ ] Documentar el uso de PrimeVue/Tailwind para evitar estilos ad hoc.

- [ ] Definir tokens visuales.
  Colores, spacing, tipografía, radios, sombras, estados y responsive.

- [ ] Crear convención de formularios con validación consistente.

- [ ] Estandarizar tablas CRUD con filtros, orden, acciones y estados vacíos.

### 6.5 Arquitectura frontend

- [ ] Definir si el frontend se mantiene en JavaScript o migra a TypeScript.
  Para un stack base tipado y reutilizable, TypeScript debería evaluarse como estándar oficial.

- [ ] Definir patrón de módulos y naming en español o convención bilingüe.

- [ ] Crear capa de servicios/API desacoplada de componentes.

## 7. Prioridad P1 - Infraestructura y entorno local

- [ ] Completar `docker/nginx/default.conf`.
  Hoy está vacío y es pieza crítica para servir API/SPA correctamente.

- [ ] Crear Dockerfile del frontend o formalizar estrategia actual.
  Hoy se usa `node:20-alpine` directamente en compose, útil para dev pero no como estándar completo.

- [ ] Agregar healthchecks a servicios clave.

- [ ] Completar variables de entorno documentadas.

- [ ] Crear `.env.example` reales para raíz, backend y frontend con todas las claves necesarias.

- [ ] Asegurar persistencia de logs, base de datos, meilisearch y configuración necesaria.

- [ ] Documentar puertos, dominios locales y flujo de primer arranque.

- [ ] Definir topología de producción en Droplets.
  Resolver si se usará un solo Droplet por proyecto inicialmente o separación posterior de servicios.

- [ ] Definir estrategia de TLS/proxy para producción.
  Confirmar si se usará `nginx-proxy-manager` o Nginx tradicional.

## 8. Prioridad P1 - Calidad, tipado y normas

- [ ] Forzar `declare(strict_types=1);` en PHP donde aplique según el estándar definido.

- [ ] Hacer obligatorio el tipado de parámetros, retornos y propiedades en backend.

- [ ] Configurar Pint y reglas de estilo oficiales del proyecto.

- [ ] Agregar análisis estático al backend.
  Evaluar PHPStan o Larastan como requisito del stack base.

- [ ] Endurecer ESLint/Prettier en frontend.

- [ ] Definir si se incorporará type-checking real en frontend.
  Si se migra a TypeScript, agregar `vue-tsc`.

- [ ] Crear hooks o scripts de calidad previos a commit/push.

- [ ] Documentar convenciones obligatorias de nombres en español y dónde sí se permiten anglicismos técnicos.

## 9. Prioridad P1 - Testing mínimo obligatorio

- [ ] Definir estrategia oficial de testing.
  La documentación menciona Pest/Vitest, pero hoy el backend está con PHPUnit base y el frontend no muestra pruebas.

- [ ] Instalar/configurar Pest si será el estándar real.

- [ ] Agregar pruebas del núcleo multi-tenant.

- [ ] Agregar pruebas de autenticación y autorización.

- [ ] Agregar pruebas de respuesta API estándar.

- [ ] Agregar pruebas de acciones/casos de uso.

- [ ] Agregar pruebas de seeders idempotentes y primer arranque.

- [ ] Agregar pruebas de componentes críticos del frontend.

- [ ] Agregar pruebas de stores/servicios/router guards.

- [ ] Incorporar smoke test de arranque del stack con Docker.

## 10. Prioridad P1 - Documentación y gobernanza

- [ ] Reescribir README raíz como guía real del stack base.
  El README actual comunica la idea general, pero todavía no funciona como onboarding completo.

- [ ] Reemplazar README de backend y frontend que aún están muy cerca de los proyectos originales/template.

- [ ] Consolidar docs del proyecto en un índice claro.

- [ ] Agregar ADRs o decisiones técnicas clave.
  Versiones, multi-tenancy, storage, frontend typing, search, tiempo real, testing, local vs Droplets.

- [ ] Convertir `docs/guia_comentarios.md` en estándar legible y bien formateado.

- [ ] Crear guía de onboarding para nuevos desarrolladores.

- [ ] Crear guía “cómo iniciar un proyecto nuevo sobre esta base”.

- [ ] Documentar definición de terminado para módulos nuevos.

## 11. Prioridad P1 - Swagger y contrato máquina-legible

- [ ] Definir estrategia oficial de documentación OpenAPI.

- [ ] Crear ejemplo base de schemas y endpoints documentados.

- [ ] Integrar generación automática de docs como parte del flujo del proyecto.

- [ ] Asegurar consistencia entre nombres de base de datos, JSON y Swagger.

- [ ] Documentar autenticación, errores, paginación y filtros en OpenAPI.

- [ ] Exponer endpoint o UI de documentación local lista para consumir.

## 12. Prioridad P2 - Dominio base reutilizable

- [ ] Diseñar el dominio mínimo común que todo sistema empresarial suele necesitar.

- [ ] Módulo de organizaciones.

- [ ] Módulo de empresas/clientes.

- [ ] Módulo de oficinas/sucursales.

- [ ] Módulo de personas.

- [ ] Módulo de usuarios.

- [ ] Módulo de roles y permisos.

- [ ] Módulo de ciudades y países o integración con catálogos geográficos base.

- [ ] Módulo de auditoría / activity log.

- [ ] Módulo de parámetros/configuración por organización.

- [ ] Módulo de catálogos maestros reutilizables.

- [ ] Módulo de exportaciones/reportes base.

- [ ] Modelo organizacional para áreas, divisiones, cargos y jefaturas.
  Debe resolverse como una estructura reutilizable para clientes futuros y no como algo acoplado a una sola empresa.

- [ ] Modelo de aprobación y supervisión por contexto operativo.
  El jefe/aprobador de un usuario debe determinarse por su asignación activa en una oficina concreta y no por un rol global único.

## 13. Prioridad P2 - Observabilidad y operación

- [ ] Definir estrategia de logs estructurados.

- [ ] Definir monitoreo mínimo de colas, errores y performance.

- [ ] Agregar configuración de logging por entorno.

- [ ] Definir política de backups para MySQL y Spaces.

- [ ] Documentar recuperación ante fallos del stack base.

## 14. Prioridad P2 - CI/CD y despliegue

- [ ] Crear pipeline de CI para lint, análisis estático, pruebas y build.

- [ ] Crear pipeline de despliegue a entorno objetivo.

- [ ] Definir estrategia de secretos por entorno.

- [ ] Documentar estrategia de ramas, releases y versionado del stack base.

- [ ] Agregar validaciones para evitar merge de código sin calidad mínima.

## 15. Prioridad P3 - Extras valiosos para 2026

- [ ] Definir soporte base para internacionalización.

- [ ] Definir soporte base para feature flags.

- [ ] Evaluar motor de permisos por feature/module en frontend.

- [ ] Evaluar scaffolding automático para nuevos módulos CRUD.

- [ ] Evaluar generación asistida por IA respetando `docs/guia_comentarios.md` y estándares del repo.

## 16. Propuesta de orden de ejecución

1. Cerrar definiciones globales y responder preguntas abiertas del dominio base.
2. Completar infraestructura mínima reproducible: envs, nginx, compose, arranque local y criterio de Droplets.
3. Consolidar núcleo backend: multi-tenancy, auth, usuarios, personas, oficinas, respuesta API y modelos base.
4. Limpiar y reconstruir frontend base: login, layouts, sesión, gestión de usuarios y formularios base.
5. Agregar calidad automática: lint, format, análisis estático, pruebas.
6. Cerrar documentación operativa, Swagger y CI/CD.

## 17. Siguiente entregable recomendado

El siguiente paso ideal es responder `preguntas.md` para cerrar las decisiones que faltan antes de implementar todo el backlog.
