🗺️ ROADMAP: Construcción del StackBase
Guía de Implementación | Estado: Inicial

🟢 Fase 1: Infraestructura (Docker)
docker-compose.yml: Configuración de servicios (App, DB, Redis, Search).
Dockerfiles: Construcción de imágenes para PHP 8.3 y Node 20.
nginx.conf: Configuración del servidor para manejo de API y SPA.
Persistencia: Mapeo de volúmenes locales para base de datos y logs.

🟡 Fase 2: Estructura Base (Backend)
Laravel 11: Instalación limpia y configuración de archivos .env.
Clases Maestras: Creación de ModeloBase.php y AccionBase.php.
Multi-inquilino: Implementación de Global Scopes por organizacion_id.
Integración Spaces: Configuración del driver S3 para almacenamiento en la nube.

🔵 Fase 3: Seguridad y Datos
Esquema DB: Migraciones de organizaciones, usuarios, perfiles y adjuntos.
Jerarquía: Implementación de Roles y Permisos (Spatie).
Autenticación: Configuración de Laravel Sanctum para tokens de API.
Seeders: Creación del usuario administrador y organizaciones base.

🟠 Fase 4: Interfaz Base (Frontend)
Entorno Vue 3: Inicialización con Vite, Pinia (estado) y Vue Router.
Layouts: Creación de AuthLayout (Login) y AppLayout (Dashboard).
Template UI: Implementación de Sidebar, Navbar y Breadcrumbs con Tailwind.
Componentes: Biblioteca base de Botones, Inputs, Tablas y Modales.
Cliente API: Configuración de Axios con interceptores de seguridad.

🔴 Fase 5: Funcionalidades BI
Procesos: Configuración de colas con Redis para tareas pesadas.
Búsqueda: Sincronización de modelos con Meilisearch.
Reportes: Sistema de exportación masiva (Excel/PDF) hacia Spaces.
Notificaciones: Implementación de tiempo real con Laravel Reverb.

🏁 Fase 6: Calidad y Despliegue
Testing: Pruebas base con Pest (Back) y Vitest (Front).
CI/CD: Configuración de GitHub Actions para despliegue automático.
Documentación: Generación de Swagger para la API.