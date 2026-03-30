# Tutorial Modulo Noticias

## Objetivo

Este tutorial no crea el modulo real. Ensena como deberia construirse un modulo `Noticias` sobre StackBase para que el equipo entienda el orden correcto, las decisiones clave y los limites del core.

La idea no es solo seguir pasos. La idea es aprender a pensar un modulo nuevo sin volver la base un conjunto de piezas pegadas sin criterio.

Complemento visual dentro del `Demo Module`:

- [`D:\Desarrollo\GRT-StackBase\frontend\src\views\pages\demo\DemoNewsModuleTutorial.vue`](D:\Desarrollo\GRT-StackBase\frontend\src\views\pages\demo\DemoNewsModuleTutorial.vue)

## Que debe aprender un desarrollador con este ejemplo

- como arrancar un modulo sin desordenar el core
- como separar roles, permisos y estados
- cuando usar Data Engine y cuando no
- como notificar a usuarios sin acoplarse a dispositivos
- como guardar archivos con una ruta segura y predecible en Spaces
- como ensenar dos patrones distintos de exportacion CSV
- como decidir el orden correcto: primero seguridad, despues workflow, despues interfaz

## Resumen funcional del caso

- la pagina de noticias solo es visible para usuarios autenticados con `news.view`
- existen dos roles: `news-author` y `news-editor`
- el `Autor` crea noticias y las envia a revision
- el `Editor` revisa, puede corregir y aprueba o rechaza
- `approved` publica automaticamente
- las noticias se listan con paginacion y filtros
- hay export CSV inmediata y export CSV asincrona con aviso al usuario al terminar

## Como conviene estudiar este tutorial

La forma mas sana de recorrerlo es esta:

1. leer primero seguridad y responsabilidades
2. luego entender estados y transiciones
3. despues revisar Data Engine, archivos, exports y pagina final

Si se empieza por la UI, es facil terminar metiendo logica de negocio en botones, modales o validaciones dispersas.

## Paso a paso recomendado

### 1. Crear el scaffold minimo

Usar:

```bash
php artisan stackbase:make-module News
```

Esto solo debe generar estructura minima, no dominio complejo.

Resultado esperado:

- existe un esqueleto del modulo
- todavia no se tocaron tablas, pantallas ni providers innecesarios
- el equipo ya tiene un punto de partida ordenado

### 2. Declarar roles y permisos

Definir primero seguridad y responsabilidades.

#### `news-author`

- `news.view`
- `news.create`
- `news.edit.own`
- `news.submit`

#### `news-editor`

- `news.view`
- `news.edit.any`
- `news.approve`
- `news.export`

Resultado esperado:

- queda claro quien crea, quien revisa y quien solo puede ver
- la seguridad deja de depender del menu o de la pantalla
- el modulo puede crecer luego sin reescribir permisos desde cero

## 3. Modelar la entidad `noticia`

Campos minimos:

- `titulo`
- `slug`
- `resumen`
- `contenido`
- `imagen_principal_file_id`
- `estado`
- `autor_id`
- `editor_id`
- `fecha_envio_revision`
- `fecha_aprobacion`
- `fecha_publicacion`

Resultado esperado:

- la noticia ya tiene una forma estable
- las fechas ayudan a auditar el flujo editorial
- la imagen principal ya se piensa como archivo del core, no como string suelto

## 4. Estados del workflow

- `draft`
- `submitted`
- `approved`
- `rejected`

Lectura recomendada:

- `draft`: la noticia todavia no entra al circuito editorial
- `submitted`: el autor ya hizo su parte y ahora el editor debe actuar
- `approved`: la noticia queda publicada automaticamente
- `rejected`: vuelve al autor para correccion

Resultado esperado:

- el flujo editorial se entiende con solo mirar los estados
- las reglas no dependen de "si vino desde tal boton"

## 5. Por que este caso si puede vivir en Data Engine

Este ejemplo si entra bien en Data Engine porque:

- la gestion interna sigue siendo administrativa
- los estados son pocos y claros
- el flujo editorial no exige una UI especializada todavia
- filtros, paginacion y exportacion son parte central del caso

No usar este mismo criterio si luego el dominio crece hacia:

- calendario editorial
- comentarios de revision
- versionado de contenido
- campanas
- analitica rica
- tableros o timeline

Resultado esperado:

- el desarrollador aprende a usar Data Engine con criterio
- no se crea una UI especial antes de tiempo
- tampoco se fuerza Data Engine cuando el dominio ya pide otra cosa

## 6. Filtros y exportaciones del ejemplo

Filtros recomendados:

- busqueda por titulo
- filtro por estado
- filtro por autor
- rango de fecha

Exportaciones recomendadas:

- CSV inmediata para volumen chico
- CSV asincrona para volumen grande, con job y notificacion al finalizar

La gracia pedagogica de este tutorial es mostrar ambos patrones.

Resultado esperado:

- el equipo aprende cuando responder rapido y cuando mandar a cola
- se evita usar una sola estrategia para todos los volumenes

## 7. Eventos y notificaciones

### Eventos de dominio sugeridos

- `news.created`
- `news.submitted`
- `news.approved`
- `news.rejected`
- `news.export.requested`
- `news.export.ready`

### Reglas de notificacion

- al pasar a `submitted`, notificar al editor
- al pasar a `approved`, notificar al autor
- al terminar la exportacion async, notificar al usuario solicitante

Regla importante:

- el modulo notifica a usuarios
- el core resuelve si va a internal, email o push

Resultado esperado:

- el modulo sigue simple
- la entrega real queda encapsulada en el core
- no se acopla el dominio a proveedores ni a dispositivos concretos

## 8. Archivos y convencion de ruta en Spaces

Ruta acordada:

```text
stackbase/{env}/{organization_slug}/{module_key}/{entity_key}/{YYYY}/{MM}/{record_id}/{file_category}/{generated_filename}
```

Aplicada a `Noticias`:

- `module_key`: `news`
- `entity_key`: `noticias`
- `file_category` controlado:
  - `cover-image`
  - `attachment`
  - `export`
  - `import`

Regla del tutorial:

- usar `record_id` simple como ejemplo principal
- mencionar `record_id + uuid corto` como mejora futura

Regla de implementacion:

- el modulo no concatena rutas a mano
- el core de archivos debe resolver la ruta final

Resultado esperado:

- los archivos quedan ordenados por entorno, organizacion, modulo y antiguedad
- a futuro sera mucho mas facil limpiar exports o archivos viejos con seguridad
- todos los modulos nuevos heredaran la misma disciplina de almacenamiento

## 9. Errores comunes que este tutorial quiere evitar

- poner la logica de aprobacion en botones de UI
- permitir que cualquier usuario autenticado cree noticias
- exponer la pagina sin `news.view`
- escribir directo a Spaces desde el modulo
- notificar dispositivos en vez de notificar usuarios
- usar Data Engine cuando el dominio ya requiere una UI mucho mas rica

## 10. Checklist final

- el manifest backend y frontend estan completos
- los permisos y roles estan sembrados
- la entidad noticia tiene estados claros
- la gestion interna usa Data Engine con filtros y exportacion
- la pagina autenticada requiere `news.view`
- la portada usa el core de archivos y la ruta estandar en Spaces
- se probaron permisos, transiciones, notificaciones y exportaciones

## Errores de implementacion que este tutorial quiere prevenir

- arrancar por pantallas antes de definir permisos y estados
- mezclar aprobacion editorial con eventos de interfaz
- escribir directo a Spaces desde el modulo
- mandar notificaciones pensando en "el celular" o "la PC" en vez de pensar en el usuario
- tratar export CSV chica y export pesada como si fueran el mismo problema

## Senal de que este tutorial ya no alcanza

Si `Noticias` empieza a pedir calendario editorial, comentarios de revision, metricas de lectura, versionado fuerte de contenido o flujos mas largos, entonces el modulo deja de ser un caso principalmente administrativo y conviene una UI propia en vez de seguir creciendo sobre Data Engine.
