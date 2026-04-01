# Guia De Nuevo Modulo

## Objetivo
Esta guia define el camino recomendado para crear un modulo de negocio nuevo sobre StackBase sin duplicar capacidades del core.

Complemento visual dentro del `Demo Module`:

- [`D:\Desarrollo\GRT-StackBase\frontend\src\views\pages\demo\DemoModuleTutorial.vue`](D:\Desarrollo\GRT-StackBase\frontend\src\views\pages\demo\DemoModuleTutorial.vue)
- [`D:\Desarrollo\GRT-StackBase\frontend\src\views\pages\demo\DemoNewsModuleTutorial.vue`](D:\Desarrollo\GRT-StackBase\frontend\src\views\pages\demo\DemoNewsModuleTutorial.vue)

Caso guiado detallado:

- [`D:\Desarrollo\GRT-StackBase\docs\tutorial_modulo_noticias.md`](D:\Desarrollo\GRT-StackBase\docs\tutorial_modulo_noticias.md)

## Regla principal
- el modulo resuelve negocio
- el core resuelve capacidades transversales
- si una necesidad puede servir a mas de un modulo, primero debe evaluarse si pertenece al core

## Antes de crear el modulo

Haz dos preguntas simples:

1. esta entidad ya existe como catalogo universal del core
2. esta pantalla o flujo podria servir a mas de un modulo

Si la respuesta a la primera es si, reutiliza el catalogo del core.

Si la respuesta a la segunda es no, no la metas al shell core.

Catalogos universales ya cerrados en la base:

- `Empresas`
- `Oficinas`
- `Equipos`
- `Personas`
- `Divisiones`
- `Areas`
- `Cargos`
- `Asignaciones laborales`

Ejemplos que deben nacer como modulo y no como catalogo universal:

- `Leads`
- `Noticias`
- `Tickets`
- `Pedidos`
- `Cobros`
- `Aprobaciones` de un negocio especifico

## Checklist minimo
1. definir `module_key`, nombre, descripcion y version
2. declarar permisos operativos del modulo
3. declarar menus y rutas frontend
4. exponer metadata backend del modulo
5. registrar settings del modulo si hacen falta
6. definir eventos de negocio importantes
7. decidir si el modulo necesita jobs, webhooks, archivos o notificaciones
8. crear una demo funcional o recipe equivalente en `Demo Module` si introduce un patron reusable

## Scaffolding recomendado

StackBase ya incluye dos comandos pequenos y controlados para acelerar el arranque sin meter magia:

```bash
php artisan stackbase:make-module Leads
php artisan stackbase:make-data-resource leads lead-card "App\\Modules\\Leads\\Models\\LeadCard" --search
```

### Que genera `stackbase:make-module`

- `backend/app/Modules/<Modulo>/<Modulo>ServiceProvider.php`
- `backend/app/Modules/<Modulo>/module.php`
- `frontend/src/modules/<modulo>/registry.js` si la ruta frontend existe en el entorno
- `docs/modules/<modulo>.md` si la ruta de docs existe en el entorno

### Que genera `stackbase:make-data-resource`

- `backend/app/Modules/<Modulo>/DataResources/<resource>.php`

### Limites deliberados

- no crea migraciones
- no inventa modelos de negocio
- no toca archivos gigantes del core manualmente
- no registra relaciones complejas por su cuenta
- no reemplaza analisis de negocio ni modelado de UX

La idea es acelerar estructura repetitiva, no esconder arquitectura.

## Estructura recomendada
- backend:
  - `backend/app/Modules/<Modulo>`
  - providers, services, policies, actions o queries propias del modulo
- frontend:
  - `frontend/src/modules/<modulo>`
  - registry, routes, pages y componentes propios

## Registro backend
El modulo debe aparecer en la metadata modular del backend para que el shell pueda:
- listarlo
- activarlo/desactivarlo
- mostrar sus settings
- conocer sus permisos, jobs, webhooks y dashboards

## Registro frontend
El modulo debe exponer una registry declarativa para:
- rutas
- menu
- pantallas
- accesos visibles segun permisos

Regla importante:

- si el modulo tiene mas de un permiso, cada ruta debe declarar `meta.permissionKey` de forma explicita
- no dependas de inferencias por nombre del modulo
- el catalogo modular ya omite rutas y menus ambiguos cuando falta ese permiso explicito

## Permisos
Todo modulo nuevo debe declarar al menos:
- permiso de lectura/listado
- permiso de administracion/configuracion si aplica
- permisos de acciones sensibles o de aprobacion

Recomendacion:
- usar nombres consistentes como `leads.view`, `leads.manage`, `leads.assign`, `leads.approve`

## Notificaciones
El modulo no debe decidir a que dispositivo mandar una notificacion.

Debe:
- decidir a que usuario notificar
- construir `title`, `message`, `actionUrl`
- declarar `metadata` de negocio
- dejar que el core resuelva canales y destinos

## Eventos de dominio

El modulo puede emitir eventos pequenos y predecibles usando la libreria base:

- [`D:\Desarrollo\GRT-StackBase\backend\app\Core\Domain\Events\DomainEvent.php`](D:\Desarrollo\GRT-StackBase\backend\app\Core\Domain\Events\DomainEvent.php)
- [`D:\Desarrollo\GRT-StackBase\backend\app\Core\Domain\Events\AbstractDomainEvent.php`](D:\Desarrollo\GRT-StackBase\backend\app\Core\Domain\Events\AbstractDomainEvent.php)
- [`D:\Desarrollo\GRT-StackBase\backend\app\Core\Domain\Events\DomainEventBus.php`](D:\Desarrollo\GRT-StackBase\backend\app\Core\Domain\Events\DomainEventBus.php)
- [`D:\Desarrollo\GRT-StackBase\backend\app\Core\Domain\Events\DispatchesDomainEvents.php`](D:\Desarrollo\GRT-StackBase\backend\app\Core\Domain\Events\DispatchesDomainEvents.php)

Patron recomendado:

- el agregado o servicio de negocio registra el evento
- el modulo lo despacha por `DomainEventBus`
- listeners, jobs, notificaciones o webhooks reaccionan despues

Eso mantiene negocio y efectos secundarios mejor separados.

## Jobs
Usar jobs cuando:
- la tarea sea lenta
- haya retries
- dependa de SLA
- haga integraciones externas

El job debe propagar:
- tenant/empresa
- actor si aplica
- identificadores de negocio

## Webhooks
Usar webhooks cuando:
- el modulo necesite avisar a sistemas externos
- el modulo reciba eventos desde terceros

Patron recomendado:
- payload pequeno y estable
- `request_id`
- `occurred_at`
- `event`
- `data`

## Archivos
Si el modulo adjunta archivos:
- usar el servicio de archivos del core
- asociar `resource_key`, `record_id` y `record_label`
- evitar que el modulo escriba directo en disco o en Spaces

## Settings
Si el modulo necesita configuracion:
- declararla como settings de modulo
- no hardcodear reglas en frontend o backend si deben cambiar sin deploy

## Pantallas
Antes de inventar una UX nueva:
- revisar `Demo Module`
- copiar un patron ya curado
- mantener consistencia con headers, acciones, dialogos y formularios del shell

Regla adicional del shell:

- si la pantalla es tecnica, transversal o administrativa del sistema, puede vivir en el core
- si la pantalla existe para operar un dominio de negocio, debe vivir en el modulo

## Definition Of Done
- metadata backend y frontend registradas
- permisos sembrados
- UI protegida por permisos
- tests basicos del modulo
- documentacion minima del flujo
- smoke manual validado
