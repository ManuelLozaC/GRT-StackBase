# 🚀 StackBase Enterprise v2.0

Soporte base para sistemas empresariales multi-inquilino (Multi-tenant) diseñado para alta escalabilidad, con procesamiento en segundo plano y una arquitectura de frontend ultra rápida.

## 🛠️ Tech Stack Core

### Backend (API)
- **Framework:** Laravel 11 (PHP 8.3).
- **Seguridad:** Laravel Sanctum (Auth API) & Spatie Permissions (RBAC).
- **Procesamiento:** Redis para colas (Queues) y tareas pesadas en segundo plano.
- **Arquitectura:** Modelos abstractos (`BaseModel`) con Soft Deletes y Auditoría.

### Frontend (SPA)
- **Tooling:** **Vite** - Servidor de desarrollo de última generación con Hot Module Replacement (HMR).
- **Framework:** Vue 3 (Composition API).
- **UI Kit:** Sakai Vue (PrimeVue 4) + Tailwind CSS 4.
- **Estado:** Pinia para gestión de estado global.

### Infraestructura (DevOps)
- **Docker:** Orquestación completa (App, Web, DB, Redis).
- **Servidor Web:** Nginx optimizado para SPA y API.
- **Base de Datos:** MySQL 8.0.

## 🔑 Características Principales

### 1. Multi-Tenancy Nativo
Aislamiento de datos a nivel de base de datos mediante el trait `MultiTenantable`. El sistema aplica un **Global Scope** automático basado en el `organizacion_id` del usuario autenticado, garantizando que ninguna organización pueda acceder a datos ajenos.

### 2. Seguridad Avanzada
- **Autenticación:** Tokens persistentes con Sanctum.
- **Autorización:** Control de acceso basado en roles (RBAC).
- **Protección de Datos:** Todos los modelos de negocio extienden de `BaseModel`, heredando aislamiento tenant y eliminación lógica (Soft Deletes).

### 3. Rendimiento con Vite & Redis
- **Vite:** Configurado con `usePolling: true` para sincronización perfecta en entornos Docker sobre Windows.
- **Redis:** Gestión de colas para envío de correos, reportes pesados y procesamiento asíncrono.

## 🚀 Instalación y Despliegue

1. **Clonar con Submódulos (Crítico para Assets):**
   ```bash
   git clone --recursive [URL_DEL_REPOSITORIO]

2. **Configurar Entorno:**
   ```bash
   cp .env.example .env

3. **Levantar Infraestructura:**
   ```bash
   docker compose up -d --build

4. **Inicializar Backend:**
   ```bash
   docker exec -it pj-backend composer install
   docker exec -it pj-backend php artisan migrate --seed

5. **Inicializar Frontend:**
   ```bash
   docker exec -it pj-frontend npm install
   Acceso: http://localhost:5173
