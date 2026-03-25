# Revision del estado actual

> Diagnostico ejecutivo del proyecto al 2026-03-25, despues de la limpieza documental principal.

## Resumen ejecutivo

El proyecto ya tiene una base funcional importante y una direccion tecnica clara:

- backend Laravel 12 con API `v1`
- frontend Vue 3 modular
- tenancy base
- RBAC inicial
- Data Engine reutilizable
- settings por ambito
- webhooks, logs, metricas y observabilidad base
- stack local dockerizado

La conclusion principal es esta:

El proyecto ya puede considerarse una plataforma avanzada en construccion, pero todavia no puede considerarse un stack base cerrado ni listo para multiplicar productos sin friccion.

## Lo que ya quedo ordenado

- README, dominio, roadmap y backlog ya apuntan a la misma narrativa
- `pending.md` raiz ya no compite con el backlog operativo
- `preguntas.md` ya fue convertido en registro de decisiones cerradas
- `local.md` ya refleja la situacion real actual del runtime
- la decision `organizacion = empresa` ya quedo documentada

## Fortalezas reales del estado actual

### Backend

- API versionada en [`backend/routes/api.php`](/D:/Desarrollo/GRT-StackBase/backend/routes/api.php)
- auth real con login, register, reset, `me`, cambio de organizacion e impersonacion
- metadata modular y administracion de modulos
- Data Engine con CRUD, import/export y transfer runs
- recursos base del dominio ya gestionables desde Data Engine
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

## Hallazgos prioritarios vigentes

### P0. El bootstrap oficial ya existe, pero todavia es una base inicial y no el dominio completo
La semilla oficial ya crea `GRT SRL`, `TalentHub` y `Manuel Loza`. El pendiente ahora ya no es "tener bootstrap", sino expandir ese bootstrap hacia el dominio completo de personas, oficinas y estructura laboral.

Evidencia:

- [`backend/database/seeders/InstalacionBaseSeeder.php:15`](/D:/Desarrollo/GRT-StackBase/backend/database/seeders/InstalacionBaseSeeder.php:15)
- [`backend/database/seeders/InstalacionBaseSeeder.php:25`](/D:/Desarrollo/GRT-StackBase/backend/database/seeders/InstalacionBaseSeeder.php:25)

Impacto:

- onboarding y QA ya parten de datos base reales
- el siguiente nivel pendiente es enriquecer ese arranque con el resto del dominio base

### P0. El modelo tenant y el modelo organizacional ya tienen decisiones clave cerradas, pero aun no estan aplicados extremo a extremo

La decision documental `organizacion = empresa` ya quedo cerrada, y el runtime ya sumo recursos base reales para oficinas, personas y asignaciones laborales. Aun asi, todavia falta converger por completo hacia ese modelo y endurecerlo en todo el sistema.

Lo que aun debe aterrizarse:

- oficinas
- areas
- divisiones
- cargos
- jefaturas
- asignaciones laborales
- permisos por contexto operativo

Evidencia:

- [`docs/modelo_dominio.md`](/D:/Desarrollo/GRT-StackBase/docs/modelo_dominio.md)
- [`docs/pendientes.md`](/D:/Desarrollo/GRT-StackBase/docs/pendientes.md)
- [`backend/app/Traits/MultiTenantable.php:10`](/D:/Desarrollo/GRT-StackBase/backend/app/Traits/MultiTenantable.php:10)

Impacto:

- riesgo alto de retrabajo si se crean CRUDs antes de cerrar el modelo operativo
- la base sigue fuerte tecnicamente, pero aun no completamente lista como foundation empresarial

### P1. Multi-tenancy base existe, pero aun no se puede considerar transversalmente cerrada

`MultiTenantable` ya aporta valor, pero todavia no es garantia completa de aislamiento en todo el dominio que se quiere construir.

Evidencia:

- [`backend/app/Traits/MultiTenantable.php:10`](/D:/Desarrollo/GRT-StackBase/backend/app/Traits/MultiTenantable.php:10)
- [`docs/pendientes.md:148`](/D:/Desarrollo/GRT-StackBase/docs/pendientes.md:148)
- [`docs/pendientes.md:150`](/D:/Desarrollo/GRT-StackBase/docs/pendientes.md:150)

Impacto:

- los nuevos modulos podrian nacer con aislamiento inconsistente
- jobs, exports, logs y futuros CRUDs aun necesitan cierre formal

### P1. La infraestructura local funciona, pero todavia esta mas orientada a desarrollo que a paridad operativa

El `docker-compose` actual levanta el entorno, pero aun le faltan piezas de stack base maduro:

- healthchecks
- worker dedicado
- estrategia de logs
- mejor separacion entre runtime dev y runtime mas cercano a produccion

Evidencia:

- [`docker-compose.yml:1`](/D:/Desarrollo/GRT-StackBase/docker-compose.yml:1)
- [`docker-compose.yml:79`](/D:/Desarrollo/GRT-StackBase/docker-compose.yml:79)
- [`docker/nginx/default.conf:1`](/D:/Desarrollo/GRT-StackBase/docker/nginx/default.conf:1)

Impacto:

- local funciona
- pero todavia no modela del todo la operacion que se quiere para Droplets

### P1. Queda limpieza residual de encoding y prolijidad

La mayor parte de la documentacion visible ya fue normalizada, pero todavia quedan restos menores de encoding y archivos historicos que conviene depurar.

Evidencia:

- [`docker-compose.yml:48`](/D:/Desarrollo/GRT-StackBase/docker-compose.yml:48)

Impacto:

- no bloquea el desarrollo
- pero sigue restando prolijidad al stack base

### P2. El frontend no tiene pruebas propias

Hay buena cobertura backend, pero no hay pruebas de primer nivel para guards, stores, auth o pantallas administrativas del frontend.

Evidencia:

- [`backend/tests`](/D:/Desarrollo/GRT-StackBase/backend/tests)
- `frontend/src` no contiene archivos de prueba propios al momento de esta revision

Impacto:

- alto riesgo de regresiones en shell, permisos y bootstrap modular
- demasiada dependencia en prueba manual

## Consistencia general

### Consistente

- direccion tecnica general
- arquitectura modular core + modules
- API-first
- enfoque tenant-aware
- documentacion principal de vision, backlog y dominio

### Parcialmente consistente

- bootstrap local
- convergencia final del dominio organizacional
- tenancy transversal
- gestion de usuarios como producto base final

### Inconsistente

- semilla inicial vs decisiones cerradas del negocio
- algunas piezas del runtime vs modelo de dominio ya decidido
- restos menores de encoding en archivos de configuracion

## Conclusion

La plataforma va por buen camino. El principal riesgo hoy ya no es el desorden documental; ese bloque quedo bastante mejor resuelto. El principal riesgo ahora es de cierre funcional:

1. dominio organizacional y laboral definitivo
2. tenancy transversal
3. paridad local -> Droplet
4. pruebas y release

El plan de trabajo para resolver eso vive en [`docs/plan_trabajo_finalizacion.md`](/D:/Desarrollo/GRT-StackBase/docs/plan_trabajo_finalizacion.md).
