# STACKBASE: Arquitectura Maestra
> Versión objetivo: 2026

## 1. Resumen Tecnológico
| Capa | Tecnología |
| :--- | :--- |
| **Infraestructura** | Docker para desarrollo local + DigitalOcean Droplets para producción + Spaces S3 |
| **Backend** | Laravel 12 + PHP 8.3 objetivo (compatibilidad aceptable con PHP 8.2) |
| **Frontend** | Vue 3 + Vite |
| **Datos / BI** | MySQL 8 + Meilisearch |
| **Procesos** | Redis + Laravel Reverb |

## 2. Arquitectura de despliegue

### Desarrollo local
1. **Docker Compose** como forma oficial de levantar el stack completo.
2. **`app-server`** para PHP-FPM / Laravel.
3. **`web`** para Nginx como punto de entrada.
4. **`db-mysql`** para persistencia transaccional.
5. **`redis-queue`** para colas, cache y procesos asincrónicos.
6. **`meilisearch-engine`** para búsqueda.
7. **`frontend`** para Vite durante desarrollo.

### Producción
1. **DigitalOcean Droplets** como entorno objetivo de despliegue.
2. **Nginx + PHP-FPM + servicios auxiliares en contenedores** manteniendo la misma arquitectura lógica de local.
3. **DigitalOcean Spaces** para almacenamiento de archivos.
4. **MySQL, Redis y Meilisearch** como servicios persistentes del stack.

## 3. Almacenamiento de Objetos (DigitalOcean Spaces)
- **Cero almacenamiento local permanente:** los archivos de usuario no se guardan de forma persistente en el Droplet.
- **Driver S3:** se utiliza el driver de Laravel compatible con Spaces.
- **Persistencia:** los archivos sobreviven a reinicios, recreación de contenedores y despliegues.

## 4. Ciclo de Vida (CI/CD)
- **GitHub Actions**: validación de estilo, análisis estático, pruebas, build y despliegue al Droplet.

## 5. Núcleo funcional obligatorio del stack base

El stack base debe incluir como mínimo:

- Autenticación con login y gestión de sesión.
- Gestión de usuarios en backend y frontend.
- Multi-tenancy por organización/empresa.
- Soporte para oficinas/sucursales y ubicación base.
- Datos semilla mínimos para primer arranque en instalaciones nuevas.
