# Patrones De Notificaciones

## Regla base
El codigo de negocio debe apuntar al usuario, no al dispositivo.

## Modelo mental
- negocio:
  - detecta el evento
  - decide el destinatario
  - define mensaje y accion
- core:
  - decide el canal habilitado
  - resuelve preferencias
  - resuelve destinos concretos

## Canales actuales
- `internal`
- `push`
- `email`

## Como notificar
Patron recomendado:

```php
app(\App\Core\Notifications\Services\NotificationCenter::class)->createMultichannel(
    recipient: $responsable,
    title: 'Nuevo lead asignado',
    message: 'Tienes un lead nuevo que requiere atencion.',
    level: 'warning',
    actionUrl: '/leads/123',
    metadata: [
        'source' => 'leads',
        'event' => 'lead.assigned',
        'lead_id' => 123,
    ],
    channels: ['internal', 'push', 'email'],
);
```

## Por que no apuntar al dispositivo
Un usuario puede tener:
- una PC
- otra PC
- un movil
- un navegador nuevo

El canal push ya mapea eso usando `core_push_subscriptions`.

## Recordatorios por SLA
Para reglas como “si pasan 4 horas sin atencion”:
1. crear el evento inicial
2. programar un job o comando scheduler
3. buscar registros vencidos
4. disparar otra notificacion al mismo usuario

## Historial
La bandeja interna vive en:
- `core_notifications`

Las entregas por canal viven en:
- `core_notification_deliveries`

Eso permite:
- ver `queued`, `delivered`, `failed`
- ver destino
- ver detalle del proveedor

## Buenas practicas
- mensajes cortos y accionables
- `actionUrl` cuando exista una pantalla clara a la cual abrir
- `metadata` con identificadores de negocio
- no duplicar eventos triviales o ruidosos
- preferir cola para email y otros canales externos
