# 🛠️ ESTÁNDAR DE DESARROLLO
> Reglas de nomenclatura, lógica y comunicación.

## 🔤 1. Nomenclatura (En Español)
* **Clases:** `PascalCase` (Ej: `ServicioFacturacion`).
* **Métodos / Variables:** `camelCase` (Ej: `calcularTotal`).
* **Tablas / Columnas:** `snake_case` y plural (Ej: `usuarios`, `fecha_pago`).
* **Tipado:** Uso obligatorio de `declare(strict_types=1);` y tipos de retorno.

## 🏗️ 2. Patrones de Arquitectura
* **Acciones (`PascalCase`):** Prohibida la lógica en controladores. Cada tarea es una clase única (Ej: `CrearUsuarioAccion`).
* **API JSON:** Formato único de respuesta: `{ estado, datos, mensaje, meta, errores }`.

## 📂 3. Gestión de Archivos (Storage)
Todos los archivos se guardan en **DigitalOcean Spaces** siguiendo esta ruta obligatoria:
`{año}/{mes}/{entidad}/{id}/{tipo_documento}/{nombre_archivo}`

* **Entidades:** `prospecto`, `cliente`, `factura`, etc.
* **Tipos:** `comprobante_pago`, `dni`, `logo`, etc.
* **Ejemplo:** `2024/05/prospecto/150/comprobante_pago/recibo.pdf`

## 🤖 4. Guía para IA
* **API First**: El Backend solo entrega JSON.
* **Descargas Pesadas:** Los Excel/CSV se generan en `/tmp`, se suben a Spaces y se borran del servidor local inmediatamente. Entrega siempre un "Enlace Firmado" temporal.