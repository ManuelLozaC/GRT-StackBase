# ROADMAP: Construcción del StackBase
Guía de Implementación | Estado: Inicial

## Fase 1: Infraestructura (Docker + Droplets)
- Docker Compose: configuración oficial para desarrollo local.
- Dockerfiles: construcción de imágenes para Laravel 12, PHP 8.3 objetivo y Node 20.
- Nginx: configuración para API y SPA en local y como base de producción.
- Persistencia: volúmenes para base de datos, search y logs donde aplique.
- Producción: criterio de despliegue en DigitalOcean Droplets manteniendo la misma arquitectura lógica del entorno local.

## Fase 2: Estructura Base (Backend)
- Laravel 12: instalación limpia y configuración de archivos `.env`.
- Clases maestras: `ModeloBase.php`, `AccionBase.php`, DTOs, requests y recursos API.
- Multi-inquilino: implementación de global scopes por `organizacion_id`.
- Integración Spaces: configuración del driver S3 para almacenamiento en la nube.

## Fase 3: Seguridad y Datos
- Esquema DB: migraciones de organizaciones, empresas, oficinas, personas, usuarios, perfiles y adjuntos.
- Jerarquía: implementación de roles y permisos con Spatie.
- Autenticación: configuración de Laravel Sanctum para login y sesión API.
- Seeders: creación del superusuario inicial y catálogos base cuando la instalación sea nueva.
- Gestión de usuarios: CRUD base en backend con asignación de empresa, oficina, roles y estado.

## Fase 4: Interfaz Base (Frontend)
- Entorno Vue 3: inicialización con Vite, Pinia y Vue Router.
- Layouts: creación de `AuthLayout` y `AppLayout`.
- Login: pantalla de acceso funcional conectada al backend.
- Gestión de usuarios: listado, alta, edición, activación/desactivación y asignación de roles.
- Template UI: sidebar, navbar, breadcrumbs y componentes base.
- Cliente API: configuración de Axios con interceptores de seguridad.

## Fase 5: Funcionalidades BI
- Procesos: configuración de colas con Redis para tareas pesadas.
- Búsqueda: sincronización de modelos con Meilisearch.
- Reportes: sistema de exportación masiva hacia Spaces.
- Notificaciones: implementación de tiempo real con Laravel Reverb.

## Fase 6: Calidad y Despliegue
- Testing: pruebas base con Pest (back) y Vitest (front) si se confirma como estándar.
- CI/CD: configuración de GitHub Actions para despliegue automático.
- Documentación: generación de Swagger/OpenAPI para la API.
