# Demo Module

> Guia y plan del `demo-platform` como sandbox tecnico y catalogo vivo de ejemplos.

## Objetivo

El `Demo Module` no debe ser solo una coleccion de pruebas del core.

Debe cumplir dos funciones al mismo tiempo:

1. demostrar capacidades tecnicas reales del stack
2. mostrar ejemplos claros de como construir UI y flujos comunes sobre esta base

## Principio rector

Todo lo importante del core debe tener una demo funcional.

Y toda decision UI reutilizable del stack debe tener un ejemplo visible dentro del `Demo Module`.

## Estado actual

Hoy ya existen demos funcionales de:

- UI showcase inicial
- UI feedback
- UI forms
- UI data display
- UI async patterns
- UI layouts
- UI typography/content
- UI advanced inputs
- UI screen recipes
- tutorial guiado para construir un modulo nuevo
- tutorial guiado completo del modulo `Noticias`
- notificaciones
- archivos
- jobs
- auditoria
- transfers

Varias de estas demos ya incluyen una guia integrada con:

- cuando usar el patron
- cuando evitarlo
- wiring recomendado
- notas de implementacion

Ademas, el modulo ya esta organizado en tres capas faciles de entender:

- tutoriales guiados
- capacidades tecnicas del core
- patrones UI y recipes

Eso reduce el costo de onboarding y evita que un desarrollador nuevo se pierda entre demos inconexas.

## Siguiente alcance esperado

El `Demo Module` ya cuenta con:

- demos tecnicas reales del core
- demos UI separadas por categoria
- recipes de pantalla
- capa didactica en varias vistas clave
- una presentacion visual mas consistente que en sus primeras iteraciones

Ademas, cada demo importante debe incluir una capa didactica minima:

- cuando usar el patron
- cuando evitarlo
- wiring recomendado
- notas de implementacion

## Catalogo minimo esperado

### Feedback y estado

- toasts
- alerts
- banners
- confirm dialogs
- modals
- drawers
- loaders
- skeletons
- empty states

### Tipografia y contenido

- titulos
- subtitulos
- parrafos
- bloques informativos
- badges
- tags
- chips

### Formularios

- formulario basico
- formulario seccionado
- validaciones cliente
- validaciones backend
- errores por campo
- errores globales
- acciones guardar/cancelar/reset

### Inputs

- text
- email
- password
- textarea
- number
- select
- multiselect
- checkbox
- radio
- toggle
- datepicker
- file upload

### Datos y navegacion

- tabla base
- tabla con filtros
- tabla con acciones
- paginacion
- tabs
- cards
- breadcrumbs
- toolbar

### Async y operacion

- submit con loading
- accion async con progreso
- polling o refresh manual
- reintentos visibles
- estados success/warning/error

## Regla de implementacion

Cada ejemplo del `Demo Module` debe mostrar:

- componente visible
- caso de uso recomendado
- wiring minimo esperado
- variante con error o validacion cuando aplique

## Estructura recomendada

### Demo tecnico

- `demo.notifications`
- `demo.files`
- `demo.jobs`
- `demo.audit`
- `demo.transfers`

### Demo UI

- `demo.ui.feedback`
- `demo.ui.typography`
- `demo.ui.forms`
- `demo.ui.inputs`
- `demo.ui.data-display`
- `demo.ui.async-patterns`
- `demo.ui.layouts`
- `demo.ui.typography-content`
- `demo.ui.advanced-inputs`
- `demo.ui.screen-recipes`
- `demo.module-tutorial`
- `demo.news-module-tutorial`

## Criterio de calidad

El `Demo Module` debe permitir que un desarrollador nuevo pueda copiar el patron correcto sin ir a buscarlo a un template externo.

Tambien debe sentirse como una sola biblioteca, no como una coleccion accidental de pantallas inconexas.

## Prioridad sugerida

1. conectar mas demos con datos reales del core cuando aporte valor didactico
2. reforzar recipes y patrones completos de pantalla para escenarios administrativos reales
3. pulir microcopy, consistencia visual y criterio de uso entre todas las demos
4. sumar notas de implementacion reutilizable para onboarding tecnico rapido
5. ampliar cobertura automatizada del modulo demo cuando el costo/beneficio lo justifique

## Regla de mantenimiento

El `Demo Module` no debe crecer como una vitrina infinita.

Solo deben entrar:

- patrones realmente reutilizables
- tutoriales que ensenen decisiones de arquitectura
- demos tecnicas del core

No deben entrar:

- experimentos de negocio de un solo modulo
- pantallas que solo duplican lo que ya existe
- variaciones cosmeticas sin valor de aprendizaje
