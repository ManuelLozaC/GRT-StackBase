# Eventos De Dominio

## Objetivo

Tener una capa pequena y clara para describir hechos de negocio importantes sin crear un bus complejo o una arquitectura dificil de mantener.

## Piezas base

- [`D:\Desarrollo\GRT-StackBase\backend\app\Core\Domain\Events\DomainEvent.php`](D:\Desarrollo\GRT-StackBase\backend\app\Core\Domain\Events\DomainEvent.php)
- [`D:\Desarrollo\GRT-StackBase\backend\app\Core\Domain\Events\AbstractDomainEvent.php`](D:\Desarrollo\GRT-StackBase\backend\app\Core\Domain\Events\AbstractDomainEvent.php)
- [`D:\Desarrollo\GRT-StackBase\backend\app\Core\Domain\Events\DomainEventBus.php`](D:\Desarrollo\GRT-StackBase\backend\app\Core\Domain\Events\DomainEventBus.php)
- [`D:\Desarrollo\GRT-StackBase\backend\app\Core\Domain\Events\DispatchesDomainEvents.php`](D:\Desarrollo\GRT-StackBase\backend\app\Core\Domain\Events\DispatchesDomainEvents.php)

## Como funciona

1. un servicio o agregado registra un evento
2. el modulo lo despacha por `DomainEventBus`
3. Laravel lo distribuye a listeners normales
4. desde ahi pueden salir notificaciones, jobs, webhooks o auditoria enriquecida

## Ejemplo minimo

```php
use App\Core\Domain\Events\AbstractDomainEvent;

final class LeadAssignedEvent extends AbstractDomainEvent
{
}
```

```php
$event = new LeadAssignedEvent(
    aggregateIdValue: $lead->id,
    payloadValue: ['responsable_id' => $lead->responsable_id],
    contextValue: ['tenant_id' => $lead->organizacion_id, 'actor_id' => auth()->id()],
);

app(\App\Core\Domain\Events\DomainEventBus::class)->dispatch($event);
```

## Que si hacer

- mantener payload pequeno y estable
- usar nombres claros y predecibles
- incluir `tenant_id` y `actor_id` cuando agreguen valor operativo
- usarlo para hechos relevantes de negocio, no para cada setter interno

## Que no hacer

- no convertir esto en event sourcing
- no guardar eventos tecnicos triviales solo por moda
- no meter logica de negocio pesada dentro del evento
- no usarlo como excusa para evitar servicios de aplicacion claros

## Regla de seguridad

Los eventos no abren una vulnerabilidad por si solos porque:

- no exponen endpoints nuevos
- no ejecutan codigo remoto
- no otorgan permisos
- solo estructuran dispatch interno sobre el dispatcher de Laravel

El riesgo aparece si un listener hace acciones sensibles sin validar contexto, permisos o tenant. Por eso los listeners deben seguir las mismas reglas de seguridad del resto del sistema.
