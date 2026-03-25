# GUIA DE COMENTARIOS Y SWAGGER
> Estandar para documentar la API real de StackBase.

## Objetivo
Mantener OpenAPI alineado con el codigo para que frontend, QA e IA puedan consumir el backend sin ambiguedades.

## Que documentar
### Modelos expuestos por API
- propiedades
- tipos
- formatos
- ejemplos
- relaciones relevantes

### Endpoints
- path y metodo
- parametros
- body esperado
- respuesta exitosa
- errores comunes

## Formato de respuesta
Todos los endpoints deben documentar el envelope comun:
- `estado`
- `datos`
- `mensaje`
- `meta`
- `errores`

## Tipos recomendados
| Dato | OpenAPI type | format | Ejemplo |
| :--- | :--- | :--- | :--- |
| ID interno | `integer` | `int64` | `101` |
| UUID | `string` | `uuid` | `"550e8400-e29b..."` |
| Fecha/hora | `string` | `date-time` | `"2026-03-24T21:00:00Z"` |
| Booleano | `boolean` | - | `true` |
| Decimal | `number` | `float` | `1500.50` |

## Ejemplo de endpoint
Para endpoints como `/api/v1/modules` se debe documentar:
- arreglo de modulos
- `key`
- `name`
- `description`
- `version`
- `enabled`
- `is_demo`

## Regla de mantenimiento
- No se documentan endpoints imaginarios.
- Primero existe el endpoint real; despues se documenta.
- Si cambia el contrato JSON, Swagger se actualiza en el mismo cambio.
