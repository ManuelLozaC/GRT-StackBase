# ESTANDAR DE DESARROLLO
> Reglas base para mantener coherencia tecnica en StackBase.

## Nomenclatura
- Clases: `PascalCase`
- Metodos y variables: `camelCase`
- Tablas y columnas: `snake_case`
- Keys publicas de modulos: `kebab-case`

## Arquitectura
- El backend es `API-first`.
- El core resuelve lo transversal.
- Los modulos agregan logica de negocio o demos.
- No se agrega una feature generica directamente dentro de un modulo de negocio.

## Respuesta API
Toda respuesta JSON debe seguir este formato:

```json
{
  "estado": "ok|error",
  "datos": {},
  "mensaje": "texto opcional",
  "meta": {},
  "errores": []
}
```

## Reglas para modulos
- Todo modulo debe declararse en el registro de modulos.
- Todo modulo debe poder activarse o desactivarse.
- Todo modulo debe definir sus rutas, menus y dependencias de forma explicita.
- Los modulos deshabilitados no deben ser accesibles desde la UI.

## Regla para demos
- Toda capacidad transversal importante debe tener una demo en `Demo Module`.
- Las demos deben servir para validacion tecnica y QA.
- Una demo visual sin flujo funcional no se considera terminada.

## Storage
Los archivos del usuario deben vivir en storage compatible con S3.

Ruta objetivo:
`{anio}/{mes}/{entidad}/{id}/{tipo}/{archivo}`

## Testing
- Toda pieza nueva del core debe tener al menos pruebas base.
- Los endpoints nuevos deben tener tests feature.
- Las demos funcionales deben tener como minimo verificacion de acceso y flujo principal.

## Documentacion
- Si cambia la arquitectura, se actualizan `docs/roadmap.md`, `docs/pendientes.md` y `docs/stackbase.md`.
- Si cambia el contrato de API, se actualiza `docs/guia_comentarios.md`.
