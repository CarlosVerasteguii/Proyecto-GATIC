# Story 6.2: Filtros de inventario por catálogos y estado/disponibilidad

Status: done

Story Key: `6-2-filtros-de-inventario-por-catalogos-y-estado-disponibilidad`  
Epic: `6` (Gate 2: Inventario navegable — búsqueda y filtros)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a usuario interno (Admin/Editor/Lector),
I want filtrar el inventario por categoría, marca, ubicación y estado/disponibilidad,
so that encuentre rápidamente subconjuntos útiles del inventario (FR24).

## Alcance (MVP)

Incluye:
- Filtros en **Inventario > Productos** (`/inventory/products`) por:
  - Categoría
  - Marca
  - Disponibilidad (con / sin disponibles)
- Filtros en **Inventario > Activos** (listado de Activos por Producto serializado: `/inventory/products/{product}/assets`) por:
  - Ubicación
  - Estado del Activo (Disponible/Asignado/Prestado/Pendiente de Retiro/Retirado)
- Persistencia de filtros en URL cuando aplique (query string), para compartir/bookmark sin perder contexto.
- “Limpiar filtros” vuelve al estado inicial (sin filtros) y reinicia paginación.

No incluye (fuera de scope):
- Filtros sobre Tareas Pendientes (MVP: no mezclar inventario con pending-tasks).
- Infra adicional de búsqueda (Scout/Meilisearch/Elastic) ni WebSockets/SSE.

## Acceptance Criteria

### AC1 - RBAC server-side (NFR4)

**Given** un usuario autenticado  
**When** visita `/inventory/products` o `/inventory/products/{product}/assets`  
**Then** el servidor autoriza con `Gate::authorize('inventory.view')`  
**And** usuarios sin permiso reciben 403 (defensa en profundidad).

### AC2 - Filtro por Catálogo: Categoría (FR24)

**Given** el listado de Productos  
**When** el usuario selecciona una Categoría  
**Then** la tabla muestra solo Productos con esa `category_id`  
**And** el filtro puede limpiarse para volver a “Todas”.

### AC3 - Filtro por Catálogo: Marca (FR24)

**Given** el listado de Productos  
**When** el usuario selecciona una Marca  
**Then** la tabla muestra solo Productos con esa `brand_id`  
**And** el filtro puede limpiarse para volver a “Todas”.

### AC4 - Filtro por Disponibilidad (FR24, semántica QTY)

**Given** el listado de Productos con conteos (Story 3.3)  
**When** el usuario selecciona “Con disponibles”  
**Then** el listado muestra solo Productos donde `Disponibles > 0`

**And** cuando selecciona “Sin disponibles”  
**Then** el listado muestra solo Productos donde `Disponibles = 0`

Notas de dominio:
- Serializados: `Disponibles` se calcula con Activos no retirados (`Retirado` no cuenta por defecto).
- Por cantidad: `Disponibles = qty_total` (en MVP, no se resta por movimientos si ya no aplica).

### AC5 - Filtros de Activos por Ubicación y Estado (FR24)

**Given** la vista de Activos de un Producto serializado  
**When** el usuario filtra por Ubicación  
**Then** se listan solo Activos con esa `location_id`

**And** cuando filtra por Estado  
**Then** se listan solo Activos con `assets.status = <estado seleccionado>`  
**And** el filtro soporta el estado `Retirado` (solo cuando se selecciona explícitamente).

### AC6 - Limpieza de filtros y paginación estable (FR24)

**Given** que existen filtros aplicados y el usuario está en una página > 1  
**When** cambia un filtro o presiona “Limpiar”  
**Then** se reinicia la paginación a la primera página  
**And** el estado “sin filtros” reproduce el listado original.

### AC7 - Rendimiento / anti N+1 (NFR1)

**Given** el listado de Productos con filtros activos  
**When** se renderiza la tabla  
**Then** no se introducen N+1 queries (no contar Activos por renglón)  
**And** los filtros de disponibilidad se resuelven con agregados/subqueries en la query principal.

## Tasks / Subtasks

1) Inventario > Productos: estado Livewire + URL (AC: 2-4, 6)
- [x] Agregar propiedades de filtros en `gatic/app/Livewire/Inventory/Products/ProductsIndex.php`:
  - `public ?int $categoryId = null;`
  - `public ?int $brandId = null;`
  - `public string $availability = 'all';` (`all|with_available|without_available`)
  - (Opcional) persistir via `Livewire\\Attributes\\Url` para compartir URL.
- [x] Al actualizar cualquier filtro, resetear paginación (`$this->resetPage()`).
- [x] Usar `config('gatic.ui.pagination.per_page')` en lugar de `15` hardcode.

2) Inventario > Productos: query de filtrado (AC: 2-4, 7)
- [x] Aplicar filtros por `category_id` y `brand_id` (incluyendo opción explícita "Sin marca" si se decide).
- [x] Implementar filtro por disponibilidad sin romper semántica:
  - Serializados: filtrar por `assets_total - assets_unavailable` (excluyendo `Retirado`).
  - Por cantidad: filtrar por `qty_total` (baseline).
- [x] Mantener anti N+1: no iterar por Producto para contar Activos.
- [x] Mantener soft-delete behavior: no considerar `assets.deleted_at` ni catálogos soft-deleted.

3) Inventario > Productos: UI de filtros (AC: 2-4, 6)
- [x] Actualizar `gatic/resources/views/livewire/inventory/products/products-index.blade.php` con:
  - Select de Categoría (opción "Todas")
  - Select de Marca (opción "Todas"; opcional "Sin marca")
  - Select de Disponibilidad (Todas/Con disponibles/Sin disponibles)
  - Botón "Limpiar" cuando haya filtros activos
- [x] Mantener accesibilidad: labels, estados, no depender solo de color.
- [x] (Opcional recomendado) Envolver acciones de filtrado/búsqueda con `<x-ui.long-request>` para UX >3s + Cancelar.

4) Inventario > Activos (por Producto): filtros por ubicación y estado (AC: 5-6)
- [x] Agregar propiedades `public ?int $locationId = null;` y `public string $status = 'all';` a `gatic/app/Livewire/Inventory/Assets/AssetsIndex.php` (persistibles en URL si conviene).
- [x] Aplicar filtros en la query de `Asset::query()`:
  - `location_id = ...` cuando aplique
  - `status = ...` cuando aplique (permitir `Retirado` solo si el usuario lo selecciona)
- [x] Actualizar `gatic/resources/views/livewire/inventory/assets/assets-index.blade.php` para exponer filtros y "Limpiar".

5) Tests (AC: 1-7)
- [x] Extender `gatic/tests/Feature/Inventory/ProductsTest.php` con escenarios:
  - Filtrar por categoría/brand
  - "Con disponibles" y "Sin disponibles" (serializado y cantidad)
  - Reset de paginación al cambiar filtros (al menos smoke: no falla y mantiene resultados)
- [x] Extender `gatic/tests/Feature/Inventory/AssetsTest.php` con escenarios:
  - Filtrar por ubicación y por estado
  - Comportamiento de `Retirado` (solo aparece cuando se filtra explícitamente)

6) Calidad / regresión
- [x] Ejecutar Pint + PHPUnit (idealmente en Sail) y confirmar que no rompe Story 3.3 (conteos) ni Story 6.1 (búsqueda).

## Dev Notes

### Contexto existente (ya implementado)

- Inventario > Productos (`/inventory/products`) ya existe como Livewire:
  - `gatic/app/Livewire/Inventory/Products/ProductsIndex.php`
  - `gatic/resources/views/livewire/inventory/products/products-index.blade.php`
  - Conteos para serializados via subqueries sobre `assets`:
    - `assets_total` excluye `Retirado`
    - `assets_unavailable` usa `Asset::UNAVAILABLE_STATUSES`
  - Productos “por cantidad” usan `products.qty_total` como baseline.
- Inventario > Activos por Producto serializado (`/inventory/products/{product}/assets`) ya existe:
  - `gatic/app/Livewire/Inventory/Assets/AssetsIndex.php`
  - Ya hace `with('location')` y búsqueda por `serial/asset_tag` con `?q=`.
- UX reusable ya existe (Story 1.9) y debe reutilizarse si el filtrado introduce latencia:
  - `gatic/resources/views/components/ui/long-request.blade.php` (umbral >3s + Cancelar)
  - `gatic/resources/views/components/ui/freshness-indicator.blade.php`
  - Toasts globales vía evento Livewire `ui:toast` (para errores esperados/inesperados).

### Guardrails de dominio (para no romper semántica)

- `Retirado` NO cuenta en disponibilidad baseline (solo debe aparecer si el usuario lo filtra explícitamente).
- `Disponibles` y `No disponibles` deben seguir reglas del “bible”:
  - Serializados: `No disponibles = Asignado + Prestado + Pendiente de Retiro` (excluye Retirado).
  - Por cantidad: `Disponibles = qty_total` (MVP no debe reintroducir reglas de movimientos si no aplican).

### Cumplimiento de arquitectura (obligatorio)

- Livewire-first: la pantalla de filtros vive en componentes Livewire existentes (`ProductsIndex`, `AssetsIndex`).
- Autorización server-side: `Gate::authorize('inventory.view')` en `mount()` y/o `render()` (patrón existente).
- Sin WebSockets: no introducir infraestructura nueva; si hay refresco, usar patrones de polling existentes (`wire:poll.visible`) donde aplique.
- Errores inesperados: mantener patrón de `error_id` (logs + UI solo si aplica) sin exponer detalle técnico a roles no Admin.

### UX esperada (desktop-first)

- Filtros rápidos (selects) arriba de la tabla, sin perder scroll ni foco de búsqueda.
- Botón “Limpiar” visible cuando hay filtros activos.
- Copys en español; IDs/rutas/código en inglés.

### Requisitos de framework/librerías

- Laravel: `laravel/framework:^11.31` (sin cambios de versión para esta story).
- Livewire: `livewire/livewire:^3.0`; preferir:
  - `Livewire\\Attributes\\Url` para query string (`?category=...&brand=...&availability=...`).
  - `wire:model.live` con debounce para búsqueda; para selects, `wire:model.live` sin debounce suele ser suficiente.
- Bootstrap 5: usar componentes nativos (form-select, input-group, badges) y mantener consistencia con el layout existente.
- No agregar dependencias nuevas para filtros (ni JS frameworks); si se requiere UI avanzada, usar Offcanvas/Modal Bootstrap.

### Inteligencia de stories previas (para evitar regresiones)

- Story 3.3 definió y ya implementó la semántica de conteos/availability:
  - No “recalcular” disponibilidad de otra forma en filtros; reutilizar la misma semántica (y si se toca la query, mantener asserts existentes).
- Story 6.1 estableció patrones útiles:
  - Persistencia en URL con `#[Url(as: 'q')]` (replicable a filtros).
  - UX de “tarda >3s” con `<x-ui.long-request>`; cuidado con `target=` para no enganchar requests que no existen (lección del code review).
- Code review histórico de Story 3.3:
  - Evitar conteos pesados o duplicados contra `categories` (preferir joins/subqueries correlacionadas ya usadas).
  - No mostrar CTAs inconsistentes (ej. acciones solo cuando aplica: serializados vs cantidad).

### Git Intelligence (contexto rápido)

- Commits recientes relevantes (patrones ya establecidos):
  - `feat(inventory): implementar kardex para productos por cantidad (Story 5.5)`
  - `feat(dashboard): implementar dashboard mínimo de métricas operativas con polling (Story 5.6)`
- Implicación: ya existe infraestructura de polling/config (`config/gatic.php`) y patrones Livewire que deben reutilizarse; esta story no debe “inventar” nuevos estilos de filtros/URL state.

### Latest Tech Information (stack actual del repo)

- Laravel framework (lock): `v11.47.0` (`gatic/composer.lock`)
- Livewire (lock): `v3.7.3` (`gatic/composer.lock`)
- Livewire 3 soporta query string via atributos `#[Url]` (ya usado en `AssetsIndex` y en la búsqueda unificada).

### Edge cases a cubrir

- “Marca” puede ser `NULL` en Producto: decidir si el filtro ofrece opción “Sin marca”.
- Catálogos soft-deleted: no deben aparecer como opciones de filtro.
- Activos: permitir filtrar por `Retirado` pero evitar mezclarlo con conteos baseline.

### Requisitos de pruebas (mínimo)

- Preferir Feature tests (patrón del repo) validando:
  - RBAC: `inventory.view` requerido.
  - Filtrado correcto por categoría/marca/disponibilidad en `/inventory/products`.
  - Filtrado correcto por ubicación/estado en `/inventory/products/{product}/assets`.
- Tests deterministas: `RefreshDatabase`, factories cuando sea posible, y asserts por texto/orden en HTML (evitar regex frágil sobre HTML completo).

### Project Structure Notes

- Componentes Livewire existentes a modificar:
  - `gatic/app/Livewire/Inventory/Products/ProductsIndex.php`
  - `gatic/resources/views/livewire/inventory/products/products-index.blade.php`
  - `gatic/app/Livewire/Inventory/Assets/AssetsIndex.php`
  - `gatic/resources/views/livewire/inventory/assets/assets-index.blade.php`
- Fuentes de datos para options:
  - `gatic/app/Models/Category.php` (solo no eliminadas)
  - `gatic/app/Models/Brand.php` (solo no eliminadas)
  - `gatic/app/Models/Location.php` (solo no eliminadas)
- Si la lógica de filtrado crece, extraer a Action (patrón del repo):
  - `gatic/app/Actions/Inventory/BuildProductsIndexQuery.php` (ejemplo de nombre; mantener coherencia con `app/Actions/Search/*`).
- Tests:
  - `gatic/tests/Feature/Inventory/ProductsTest.php`
  - `gatic/tests/Feature/Inventory/AssetsTest.php`

### References

- Backlog (FR24): `_bmad-output/implementation-artifacts/epics.md` (Epic 6 / Story 6.2)
- PRD: `_bmad-output/implementation-artifacts/prd.md` (Search & Discovery: FR24)
- Arquitectura/patrones: `_bmad-output/implementation-artifacts/architecture.md`
- UX: `_bmad-output/implementation-artifacts/ux.md` (InventoryToolbar, filtros rápidos, drawer)
- Reglas “bible” dominio/stack: `docsBmad/project-context.md`
- Reglas lean para agentes: `project-context.md`
- Implementación existente:
  - `gatic/app/Livewire/Inventory/Products/ProductsIndex.php`
  - `gatic/resources/views/livewire/inventory/products/products-index.blade.php`
  - `gatic/app/Livewire/Inventory/Assets/AssetsIndex.php`
  - `gatic/app/Models/Asset.php` (estados + `UNAVAILABLE_STATUSES`)
  - `gatic/config/gatic.php` (paginación/umbrales UI)

## Dev Agent Record

### Agent Model Used

Claude Opus 4.5 (Claude Code CLI)

### Debug Log References

- `_bmad/core/tasks/workflow.xml`
- `_bmad/bmm/workflows/4-implementation/dev-story/workflow.yaml`

### Completion Notes List

- Status: `done`
- Implementation completed: 2026-01-17
- All acceptance criteria satisfied (AC1-AC7)
- Tests added: 9 new tests (5 for Products, 4 for Assets)
- Full test suite: 298 tests passed (764 assertions)
- Pint: passing
- Larastan: no new errors introduced
- Code review fixes applied: 2026-01-18 (soft-delete counts, long-request UX, availability filter refactor, regression test)

### Implementation Plan

**Products Index Filters:**
- Added `categoryId`, `brandId`, `availability` properties with `#[Url]` attribute for URL persistence
- Implemented availability filter using subqueries (anti N+1) respecting domain semantics:
  - Serialized: `assets_total - assets_unavailable > 0` (excludes Retired)
  - Quantity: `qty_total > 0`
- Added `clearFilters()` and `hasActiveFilters()` methods
- Used `config('gatic.ui.pagination.per_page')` instead of hardcoded value

**Assets Index Filters:**
- Added `locationId`, `status` properties with `#[Url]` attribute
- Default view excludes Retired assets (AC5 semantics)
- Retired only shown when explicitly selected
- Added filter UI with Bootstrap selects and clear button

### File List

- `gatic/app/Livewire/Inventory/Products/ProductsIndex.php` (modified)
- `gatic/resources/views/livewire/inventory/products/products-index.blade.php` (modified)
- `gatic/app/Livewire/Inventory/Assets/AssetsIndex.php` (modified)
- `gatic/resources/views/livewire/inventory/assets/assets-index.blade.php` (modified)
- `gatic/tests/Feature/Inventory/ProductsTest.php` (modified - 6 new tests)
- `gatic/tests/Feature/Inventory/AssetsTest.php` (modified - 4 new tests)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (updated to `done`)

## Senior Developer Review (AI)

Reviewer: Carlos (AI-assisted)  
Date: 2026-01-18

### Outcome

Changes Requested → Fixed (HIGH + MEDIUM).

### Findings Fixed

- HIGH: Conteos para serializados incluían `assets.deleted_at` en `assets_total/assets_unavailable` (inconsistencia con la semántica del dominio).
- HIGH: Tarea marcada como completa pero faltaba `<x-ui.long-request>` en índices de Productos/Activos.
- MEDIUM: Duplicación de subqueries en filtro de disponibilidad (se refactorizó a `HAVING` usando aliases `assets_total/assets_unavailable` para reducir costo y complejidad).
- MEDIUM: Se agregó regresión para asegurar que soft-delete no afecta conteos de disponibilidad en listado de Productos.

## Change Log

- 2026-01-18: Code review (AI) — fixes aplicados (conteos soft-delete, long-request UX, refactor filtro disponibilidad, test de regresión). Status → `done`.
