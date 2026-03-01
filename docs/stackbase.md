# 🚀 STACKBASE: Arquitectura Maestra
> **Entidad:** Premio Joven | **Versión:** 1.0

## 📑 1. Resumen Tecnológico
| Capa | Tecnología |
| :--- | :--- |
| **Infraestructura** | DigitalOcean (Droplet + Spaces S3) |
| **Backend** | PHP 8.3 + Laravel 11 |
| **Frontend** | Vue.js 3 + Vite |
| **Datos / BI** | MySQL 8 + Meilisearch |
| **Procesos** | Redis + Laravel Reverb |

## 🏗️ 2. Arquitectura de Contenedores (Docker)
1. **`nginx-proxy-manager`**: Gestión de dominios y SSL.
2. **`app-server`**: Procesamiento PHP-FPM.
3. **`db-mysql`**: Almacenamiento persistente.
4. **`redis-queue`**: Tareas en segundo plano.
5. **`meilisearch-engine`**: Motor de búsqueda de alta velocidad.

## 📁 3. Almacenamiento de Objetos (DigitalOcean Spaces)
* **Cero Almacenamiento Local:** Los archivos de usuario NO se guardan en el Droplet.
* **Driver S3:** Se utiliza el driver de Laravel para Spaces.
* **Persistencia:** Los archivos sobreviven a la eliminación o reinicio de los contenedores.

## 🔄 4. Ciclo de Vida (CI/CD)
* **GitHub Actions**: Validación de estilo -> Pruebas (Pest/Vitest) -> Despliegue automático al Droplet.