# 🗄️ MODELO DE DOMINIO Y BD
> Estructura base para sistemas Multi-Inquilino (Multi-tenant).

## 🔐 1. Jerarquía y Seguridad
* **Organizaciones:** Estructura de árbol (Nested Sets) para jerarquías infinitas.
* **Aislamiento:** Uso de `Global Scopes` en Laravel para filtrar por `organizacion_id` automáticamente.
* **Auditoría:** Todas las tablas incluyen `usuario_id` y `fecha_eliminacion` (SoftDeletes).

## 📊 2. Inteligencia de Negocio (BI)
* **Reportes Asíncronos:** Generación de archivos pesados vía colas de Redis.
* **Tablas de Agregación:** Tablas resumen para dashboards instantáneos sin saturar la BD transaccional.

## 🔑 3. Convenciones de Claves
* **Internas:** `bigIncrements` (IDs numéricos).
* **Públicas:** `UUID` (Para IDs en URLs y seguridad).