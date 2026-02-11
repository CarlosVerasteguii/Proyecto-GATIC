# FP-04 — Widget de “Acciones rápidas” en Dashboard

Fecha: 2026-02-11

## Objetivo (MVP)

Tener un widget visible en el **Dashboard** que sirva como “hub” de atajos a acciones frecuentes:

- Abrir modales de **Carga rápida** / **Retiro rápido** (FP-03 ya implementado).
- Navegar a páginas clave (búsqueda, tareas pendientes, creación, etc.).
- Respetar RBAC (Lector = solo lectura).

## Ubicación UI propuesta

- Página: `dashboard` (`/dashboard`).
- Ubicación: **debajo del header** del dashboard y **antes** de la card “Filtros globales”.
- Formato: card Bootstrap con botones en grid responsive (`flex-wrap`), sin afectar las métricas existentes.

## Acciones por rol (MVP)

### Admin / Editor (`@can('inventory.manage')`)

- **Carga rápida** (abre modal Livewire existente)
  - Componente: `<livewire:pending-tasks.quick-stock-in />`
- **Retiro rápido** (abre modal Livewire existente)
  - Componente: `<livewire:pending-tasks.quick-retirement />`
- **Tareas pendientes**
  - Ruta: `pending-tasks.index`
- **Crear producto** (si existe)
  - Ruta: `inventory.products.create`
- **Búsqueda** (lectura)
  - Ruta: `inventory.search`

### Lector (`@can('inventory.view')`)

- **Búsqueda de inventario**
  - Ruta: `inventory.search`
- **Ver activos**
  - Ruta: `inventory.assets.index`
- **Ver productos**
  - Ruta: `inventory.products.index`

## Rutas exactas (Fuente: `php artisan route:list`)

- Dashboard:
  - `dashboard`
- Búsqueda inventario:
  - `inventory.search`
- Productos (listado):
  - `inventory.products.index`
- Activos (listado global):
  - `inventory.assets.index`
- Tareas pendientes (listado):
  - `pending-tasks.index`
- Crear producto:
  - `inventory.products.create`

## Nota sobre “Crear activo”

En el estado actual del repo, la creación de Activo está anidada por producto:

- Ruta: `inventory.products.assets.create` (requiere `{product}`)

Por eso **no** se incluye como atajo directo en el widget (MVP). Alternativa: navegar a `inventory.products.index` y crear el activo desde el producto.

## Notas RBAC

- El widget usa `@can(...)` para mostrar/ocultar acciones.
- La seguridad real permanece en middleware/gates de rutas (`can:inventory.view`, `can:inventory.manage`).
- El componente `pending-tasks.pending-task-opener` debe estar montado en Dashboard para que el toast “Ver tarea” de FP-03 pueda redirigir correctamente.

