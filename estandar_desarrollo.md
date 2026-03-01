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
* **Tailwind Only:** Estilos basados puramente en utilidades de TailwindCSS.

## 🤖 3. Guía para IA
* **API First**: El Backend solo entrega JSON.
* **Acciones Desacopladas**: La lógica debe ser independiente del controlador.