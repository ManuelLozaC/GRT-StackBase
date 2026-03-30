# PWA en StackBase

## Objetivo

Este documento explica que implicaria convertir StackBase en una `PWA` futura, sin asumir que deba hacerse ahora.

La recomendacion actual es:

- no implementarla en esta etapa
- dejarla como mejora opcional futura
- prepararla solo si el uso movil o escritorio instalado empieza a aportar valor real

## Que es una PWA

Una `Progressive Web App` es una aplicacion web que puede:

- instalarse en escritorio o movil
- abrirse como si fuera una app
- tener icono y splash screen
- usar `service worker`
- mejorar el soporte de `push`
- cachear algunos recursos

No significa automaticamente:

- app nativa
- funcionamiento offline completo
- sincronizacion compleja sin red
- mejor seguridad por si sola

## Cuando tendria sentido en StackBase

En esta base tendria sentido si se cumple al menos una de estas condiciones:

- los usuarios entran con mucha frecuencia desde movil
- se quiere acceso rapido desde escritorio como app instalada
- se quiere mejorar la experiencia de `push` en navegadores compatibles
- se quiere una experiencia mas cercana a app para modulos operativos concretos

No tendria sentido hacerlo solo por moda o por checklist tecnico.

## Beneficios reales para este proyecto

- acceso mas rapido desde escritorio y movil
- mejor percepcion de producto "instalable"
- mejor soporte de notificaciones `push` en algunos escenarios
- posibilidad de cachear shell y assets estaticos
- mejor experiencia para usuarios repetitivos del sistema

## Lo que NO recomendamos mezclar desde el inicio

Si algun dia se implementa `PWA`, no recomendamos mezclarla inmediatamente con:

- offline de formularios complejos
- sincronizacion diferida multi-entidad
- cache agresiva de APIs autenticadas
- logica de negocio offline

La primera etapa debe ser intencionalmente pequena.

## Alcance recomendado si se hace mas adelante

### Fase 1: PWA basica y segura

- `manifest.webmanifest`
- iconos
- instalacion en navegador
- `service worker` solo para assets y shell
- `no cache` para respuestas API autenticadas
- integracion ordenada con el `push` ya existente

### Fase 2: mejoras de experiencia

- pantalla de instalacion sugerida
- recordatorio contextual para instalar
- mejor integracion movil para modulos que lo justifiquen

### Fase 3: solo si aparece necesidad real

- cache de ciertas vistas de solo lectura
- borradores locales muy acotados
- soporte limitado a conectividad inestable

## Como se implementaria en esta base

## 1. Frontend

La implementacion viviria principalmente en `frontend`.

Piezas principales:

- `manifest.webmanifest`
- iconos en `public`
- registro del `service worker`
- estrategia de cache para assets estaticos
- UI opcional para "instalar aplicacion"

Archivos tipicos que probablemente intervendrian:

- `frontend/public/manifest.webmanifest`
- `frontend/public/icons/*`
- `frontend/src/main.*`
- `frontend/vite.config.*`
- un wrapper del `service worker`

## 2. Service worker

Debe ser minimalista.

Recomendacion fuerte:

- cachear solo `HTML shell`, `JS`, `CSS`, imagenes estaticas y assets controlados
- nunca cachear por defecto `/api/*` autenticada
- no guardar respuestas sensibles del usuario fuera de reglas muy explicitas

Si se usa `Workbox`, conviene una estrategia simple:

- `StaleWhileRevalidate` para assets versionados
- `NetworkOnly` para API autenticada
- `NetworkFirst` solo para ciertas rutas publicas o no sensibles si algun dia existieran

## 3. Integracion con push

StackBase ya tiene `FCM Web Push`.

Una futura `PWA` no cambiaria el modelo central:

- el usuario sigue siendo el sujeto de notificacion
- el navegador/dispositivo sigue registrando su suscripcion
- el core sigue resolviendo a que dispositivos enviar

Lo que mejoraria es la experiencia del cliente instalado.

## 4. Seguridad

La `PWA` no debe degradar seguridad.

Reglas recomendadas:

- mantener `HttpOnly` para autenticacion web
- no volver a guardar sesion en `localStorage`
- no cachear tokens ni respuestas autenticadas sensibles
- no permitir que el `service worker` intercepte mas de lo necesario
- revisar `CSP`, `XSS` y manejo de contenido enriquecido antes de ampliar cache o renderizado

## 5. Impacto en despliegue

Habra que ajustar:

- headers de cache en `nginx`
- versionado de assets
- invalidacion de service worker en releases
- pruebas en `Cloudflare` para no mezclar cache edge con cache del navegador

## Riesgos si se hace mal

- cachear datos privados de usuarios
- dejar una version vieja del frontend controlando sesiones nuevas
- confundir a soporte con bugs de cache
- convertir el shell en algo mas dificil de depurar

Por eso la recomendacion para StackBase es:

- `PWA` si, pero pequena
- offline complejo no
- cache autenticada no

## Criterio de decision recomendado

Antes de implementarla, responder estas preguntas:

1. Hay uso movil o escritorio instalado suficientemente fuerte?
2. El `push` se beneficiaria claramente con una app instalable?
3. El equipo esta listo para soportar `service worker`, cache y debugging asociado?
4. Se puede mantener el alcance pequeno y seguro?

Si la respuesta es "si" a todo, vale la pena entrar.

## Recomendacion final

Para StackBase hoy:

- dejar `PWA` como opcion futura
- no mezclarla con offline
- si se implementa, arrancar con instalacion + assets + shell + `push`
- mantener API autenticada fuera del cache

Esa es la forma mas sana de adaptarla a la base actual sin volverla mas compleja de lo necesario.
