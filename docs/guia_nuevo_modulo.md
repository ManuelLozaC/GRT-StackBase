# Guia De Nuevo Modulo

## Objetivo
Esta guia define el camino recomendado para crear un modulo de negocio nuevo sobre StackBase sin duplicar capacidades del core.

## Regla principal
- el modulo resuelve negocio
- el core resuelve capacidades transversales
- si una necesidad puede servir a mas de un modulo, primero debe evaluarse si pertenece al core

## Checklist minimo
1. definir `module_key`, nombre, descripcion y version
2. declarar permisos operativos del modulo
3. declarar menus y rutas frontend
4. exponer metadata backend del modulo
5. registrar settings del modulo si hacen falta
6. definir eventos de negocio importantes
7. decidir si el modulo necesita jobs, webhooks, archivos o notificaciones
8. crear una demo funcional o recipe equivalente en `Demo Module` si introduce un patron reusable

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

## Definition Of Done
- metadata backend y frontend registradas
- permisos sembrados
- UI protegida por permisos
- tests basicos del modulo
- documentacion minima del flujo
- smoke manual validado
