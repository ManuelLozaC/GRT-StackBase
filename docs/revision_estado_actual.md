# Revision del estado actual

> Diagnostico ejecutivo del proyecto al 2026-03-25.

## Resumen ejecutivo

El proyecto ya no esta en etapa de arranque. Tiene una base funcional importante y una direccion tecnica clara:

- backend Laravel 12 con API `v1`
- frontend Vue 3 modular
- tenancy base
- RBAC inicial
- Data Engine reutilizable
- settings por ambito
- webhooks, logs, metricas y observabilidad base
- stack local dockerizado

La conclusion principal es esta:

El proyecto ya puede considerarse una plataforma en construccion, pero todavia no puede considerarse un stack base cerrado ni listo para multiplicar productos sin friccion.

Las razones principales son:

- hay deriva documental entre algunos MD viejos y el codigo real
- el bootstrap inicial del dominio no coincide con las decisiones ya cerradas del proyecto
- el modelo tenant/laboral aun no esta formalizado extremo a extremo
- faltan capas de cierre para infraestructura, storage real, pruebas frontend y gobernanza

## Fortalezas reales del estado actual

### Backend

- API versionada en [`backend/routes/api.php`](/D:/Desarrollo/GRT-StackBase/backend/routes/api.php)
- auth real con login, register, reset, `me`, cambio de organizacion e impersonacion
- metadata modular y administracion de modulos
- Data Engine con CRUD, import/export y transfer runs
- settings globales, por organizacion y por usuario
- observabilidad base: logs de seguridad, logs de error, metricas y operations overview
- webhooks salientes y entrantes con trazabilidad

### Frontend

- router modular con guards en [`frontend/src/router/index.js`](/D:/Desarrollo/GRT-StackBase/frontend/src/router/index.js)
- stores separados por responsabilidad
- shell administrativo real
- consumo de metadata modular desde API
- administracion de modulos, settings, seguridad, operaciones y usuarios

### Calidad actual

- existe suite backend no trivial en [`backend/tests`](/D:/Desarrollo/GRT-StackBase/backend/tests)
- hay `lint` y `build` para frontend
- el repo muestra una arquitectura mas consistente que la de un template apenas adaptado

## Hallazgos prioritarios

### P0. El bootstrap de instalacion no representa el dominio acordado

El seeder de instalacion sigue creando un tenant demo y un usuario demo generico, no la base oficial definida para el stack.

Evidencia:
- [`backend/database/seeders/InstalacionBaseSeeder.php:15`](/D:/Desarrollo/GRT-StackBase/backend/database/seeders/InstalacionBaseSeeder.php:15)
- [`backend/database/seeders/InstalacionBaseSeeder.php:25`](/D:/Desarrollo/GRT-StackBase/backend/database/seeders/InstalacionBaseSeeder.php:25)

Impacto:
- el primer arranque no deja el sistema en el estado de negocio esperado
- onboarding, QA y validacion funcional parten de datos falsos
- la documentacion local y la semilla real hoy estan desalineadas

### P0. Existen dos backlogs y uno de ellos ya no refleja la realidad del codigo

`docs/pendientes.md` si esta bastante alineado al estado real, pero `pending.md` raiz conserva supuestos viejos y varias afirmaciones ya no aplican.

Evidencia:
- [`docs/pendientes.md:6`](/D:/Desarrollo/GRT-StackBase/docs/pendientes.md:6)
- [`pending.md:27`](/D:/Desarrollo/GRT-StackBase/pending.md:27)
- [`pending.md:115`](/D:/Desarrollo/GRT-StackBase/pending.md:115)
- [`backend/routes/api.php:24`](/D:/Desarrollo/GRT-StackBase/backend/routes/api.php:24)

Impacto:
- cualquier nuevo integrante puede priorizar mal
- el backlog raiz contradice funcionalidades que ya existen
- se pierde confianza en la documentacion

### P0. Hay problemas de codificacion/encoding en documentacion y compose

Varios archivos muestran texto corrupto (`comÃºn`, `mÃ­nimo`, `CACHÃ‰`), lo que degrada la legibilidad y transmite desorden en un repo que pretende ser base corporativa.

Evidencia:
- [`pending.md:3`](/D:/Desarrollo/GRT-StackBase/pending.md:3)
- [`local.md:14`](/D:/Desarrollo/GRT-StackBase/local.md:14)
- [`docker-compose.yml:48`](/D:/Desarrollo/GRT-StackBase/docker-compose.yml:48)

Impacto:
- mala experiencia de onboarding
- riesgo de copiar configuraciones o instrucciones mal interpretadas
- imagen de producto base aun inmadura

### P1. El modelo tenant y el modelo organizacional ya tienen decisiones clave cerradas, pero aun no estan aplicados extremo a extremo

El proyecto ya tiene `organizaciones`, `empresas`, `sucursales` y `equipos`. La decision documental `organizacion = empresa` ya quedo cerrada, pero todavia falta aterrizarla por completo en el runtime y en el dominio operativo.

Lo que aun debe converger claramente es:

- como se modelan oficinas, areas, divisiones, cargos y jefaturas
- como se resuelve el escenario clave de una persona con diferentes roles/aprobadores por sucursal

Evidencia:
- [`docs/pendientes.md:147`](/D:/Desarrollo/GRT-StackBase/docs/pendientes.md:147)
- [`docs/pendientes.md:150`](/D:/Desarrollo/GRT-StackBase/docs/pendientes.md:150)
- [`backend/app/Traits/MultiTenantable.php:10`](/D:/Desarrollo/GRT-StackBase/backend/app/Traits/MultiTenantable.php:10)

Impacto:
- riesgo alto de construir CRUDs y permisos sobre una base de dominio solo parcialmente aterrizada
- posible retrabajo en migraciones, scopes y UI administrativa

### P1. Multi-tenancy base existe, pero aun no se puede considerar transversalmente cerrada

`MultiTenantable` ya aporta valor, pero todavia opera como capa tecnica base, no como garantia completa de aislamiento. Aun falta cerrar jobs, bypass administrativo controlado, escenarios de consola/seeders y el dominio nuevo que se quiere construir encima.

Evidencia:
- [`backend/app/Traits/MultiTenantable.php:10`](/D:/Desarrollo/GRT-StackBase/backend/app/Traits/MultiTenantable.php:10)
- [`docs/pendientes.md:148`](/D:/Desarrollo/GRT-StackBase/docs/pendientes.md:148)
- [`docs/pendientes.md:150`](/D:/Desarrollo/GRT-StackBase/docs/pendientes.md:150)

Impacto:
- el core parece mas cerrado de lo que realmente esta
- los nuevos modulos podrian nacer con aislamiento inconsistente

### P1. La guia local ya no coincide del todo con el runtime real

`local.md` menciona Swagger en `/api/documentation`, pero la ruta visible en el backend actual es `GET /api/v1/openapi.json`. La guia tambien lista credenciales de Manuel Loza que hoy no salen del seeder vigente.

Evidencia:
- [`local.md:173`](/D:/Desarrollo/GRT-StackBase/local.md:173)
- [`local.md:180`](/D:/Desarrollo/GRT-StackBase/local.md:180)
- [`backend/routes/api.php:24`](/D:/Desarrollo/GRT-StackBase/backend/routes/api.php:24)
- [`backend/database/seeders/InstalacionBaseSeeder.php:25`](/D:/Desarrollo/GRT-StackBase/backend/database/seeders/InstalacionBaseSeeder.php:25)

Impacto:
- primera experiencia local engañosa
- el proyecto parece roto cuando en realidad la documentacion es la que quedo atras

### P2. El frontend no tiene pruebas propias

Hay buena cobertura backend, pero no hay pruebas de primer nivel para router guards, stores, flujos auth, Data Engine o pantallas administrativas del frontend.

Evidencia:
- [`backend/tests`](/D:/Desarrollo/GRT-StackBase/backend/tests)
- `frontend/src` no contiene archivos de prueba propios al momento de esta revision

Impacto:
- alto riesgo de regresiones en shell, permisos y bootstrap modular
- demasiada dependencia en prueba manual

### P2. La infraestructura local funciona, pero todavia esta mas orientada a desarrollo que a paridad operativa

El `docker-compose` es valido para levantar el entorno, pero aun le faltan piezas de stack base maduro:

- healthchecks
- worker dedicado
- estrategia de logs
- mejor separacion entre runtime dev y runtime mas cercano a produccion

Evidencia:
- [`docker-compose.yml:1`](/D:/Desarrollo/GRT-StackBase/docker-compose.yml:1)
- [`docker-compose.yml:79`](/D:/Desarrollo/GRT-StackBase/docker-compose.yml:79)
- [`docker/nginx/default.conf:1`](/D:/Desarrollo/GRT-StackBase/docker/nginx/default.conf:1)

Impacto:
- el entorno local levanta, pero no modela todo lo que hara falta en Droplets

## Consistencia general

### Consistente

- direccion tecnica general
- arquitectura modular core + modules
- API-first
- enfoque tenant-aware
- observabilidad base
- documentacion principal de vision en `README.md` y `docs/stackbase.md`

### Parcialmente consistente

- backlog y roadmap
- bootstrap local
- dominio organizacional
- tenancy transversal
- gestion de usuarios como producto base final

### Inconsistente

- semilla inicial vs decisiones cerradas del negocio
- `pending.md` raiz vs `docs/pendientes.md`
- algunas URLs y credenciales documentadas vs runtime real
- codificacion de texto en varios archivos

## Debilidades estructurales

- falta una sola fuente de verdad para backlog operativo
- falta cerrar el dominio base reutilizable antes de seguir expandiendo features transversales
- el proyecto ya crecio lo suficiente como para necesitar ADRs y criterio formal de terminado
- hay buena plataforma tecnica, pero aun falta bajar eso al stack base empresarial que quieres usar para futuros clientes

## Conclusion

La plataforma va por buen camino y ya tiene mucho mas valor del que suele tener un "starter kit". El principal riesgo hoy no es falta de codigo, sino cierre incompleto del contrato del producto base.

La prioridad correcta no es seguir agregando features en paralelo. La prioridad correcta es cerrar consistencia:

1. fuente unica de verdad documental
2. bootstrap real del dominio base
3. modelo tenant/organizacional/laboral definitivo
4. paridad operativa local -> Droplet
5. plan de pruebas y cierre

El plan propuesto para resolver eso vive en [`docs/plan_trabajo_finalizacion.md`](/D:/Desarrollo/GRT-StackBase/docs/plan_trabajo_finalizacion.md).
