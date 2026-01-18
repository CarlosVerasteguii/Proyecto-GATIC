# Story 6.1: Búsqueda unificada (Productos + Activos) con salto directo por match exacto

Status: done

Story Key: `6-1-busqueda-unificada-productos-activos-con-salto-directo-por-match-exacto`  
Epic: `6` (Gate 2: Inventario navegable — búsqueda y filtros)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/epics.md` (Epic 6 / Story 6.1)
- `_bmad-output/implementation-artifacts/prd.md` (FR23, NFR2, NFR3)
- `_bmad-output/implementation-artifacts/ux.md` (Búsqueda unificada; UX de resultados; cancel/slow >3s; atajos)
- `_bmad-output/implementation-artifacts/architecture.md` (stack/patrones; rutas; `app/Livewire/Search/*`; `config/gatic.php`)
- `docsBmad/project-context.md` + `project-context.md` (bible/stack; RBAC server-side; rutas/código en inglés y UI en español)
- `gatic/app/Livewire/Inventory/Products/ProductsIndex.php` (patrón de búsqueda por nombre)
- `gatic/app/Livewire/Inventory/Assets/AssetsIndex.php` (patrón búsqueda por `serial`/`asset_tag` + query string `q`)
- `gatic/app/Actions/Employees/SearchEmployees.php` + `gatic/app/Livewire/Ui/EmployeeCombobox.php` (patrón de “min chars”, ordenamiento y manejo de errores con `error_id`)
- `gatic/resources/views/components/ui/long-request.blade.php` (UX “tarda >3s” + Cancelar)
- `gatic/routes/web.php` (rutas existentes de inventario/detalle)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a usuario interno (Admin/Editor/Lector),
I want buscar Productos y Activos por nombre, `serial` y `asset_tag`,
so that encuentre rápido lo que necesito y navegue directo al detalle correcto (FR23).

## Alcance (MVP)

Incluye:
- Un buscador unificado accesible desde la UI (desktop-first) que:
  - Encuentra Productos por nombre.
  - Encuentra Activos por `serial` o `asset_tag`.
  - Si hay match exacto por `asset_tag` (único global), permite “salto directo” a detalle de Activo.
  - Si hay match exacto por `serial` y es no ambiguo, permite “salto directo” a detalle de Activo.
- Resultados escaneables con campos mínimos: tipo (Producto/Activo), nombre del producto, `serial`/`asset_tag` (si aplica), estado del Activo.
- Persistencia del término de búsqueda en URL cuando aplique (ej. `?q=`) para compartir/bookmark.

No incluye (fuera de scope):
- Indexadores externos (Scout/Meilisearch/Elastic) o infraestructura adicional.
- Búsqueda sobre Tareas Pendientes (regla explícita MVP: NO mezclar).
- Filtros avanzados (eso es Story 6.2).

## Acceptance Criteria

### AC1 - Buscar por nombre de Producto y navegar al detalle (FR23)

**Given** el buscador unificado disponible  
**When** el usuario busca por nombre de Producto  
**Then** obtiene resultados relevantes de Productos  
**And** puede navegar al detalle del Producto.

### AC2 - Match exacto por `serial`/`asset_tag` prioriza Activo y permite salto directo (FR23)

**Given** el buscador unificado disponible  
**When** el usuario busca por `serial` o `asset_tag` con match exacto  
**Then** el sistema prioriza el Activo correspondiente  
**And** permite navegar directamente al detalle del Activo.

Notas/edge cases:
- `asset_tag` es único global: match exacto debe ser determinista.
- `serial` NO es único global (solo es único por `(product_id, serial)`): si hay más de un match exacto por `serial`, NO hacer salto directo silencioso; mostrar una lista y exigir selección.

### AC3 - RBAC server-side (NFR4)

**Given** un usuario sin permiso `inventory.view`  
**When** intenta usar el buscador unificado o ver resultados  
**Then** el servidor lo bloquea (403/abort)  
**And** la UI no expone acciones de búsqueda a roles no autorizados.

### AC4 - UX de latencia y cancelación (NFR2)

**Given** una búsqueda que tarda `>3s`  
**When** el usuario espera resultados  
**Then** se muestra un loader/skeleton + mensaje de progreso  
**And** existe opción de Cancelar que conserva lo que el usuario ya estaba viendo (sin “pantalla en blanco”).

### AC5 - Sin WebSockets; comportamiento estable con polling existente (NFR3)

**Given** el sistema opera sin WebSockets  
**When** se implementa el buscador unificado  
**Then** no se agrega infraestructura de tiempo real (SSE/WebSockets)  
**And** no se rompe el patrón existente de polling visible (si el buscador vive en topbar, debe evitar re-renderes distractores).

## Tasks / Subtasks

1) Routing + UI entry point (AC: 1, 2, 3)
- [x] Definir el punto de entrada (recomendado): `/inventory/search?q=...` (Livewire) y/o componente en topbar que navega a esa ruta.
- [x] Asegurar autorización server-side `inventory.view` para cualquier endpoint de búsqueda.

2) Search logic (AC: 1, 2)
- [x] Crear Action `App\\Actions\\Search\\SearchInventory` (o equivalente) que reciba `q` y regrese resultados tipados (Productos/Activos) + "best match exact".
- [x] Implementar resolución de match exacto:
  - [x] Primero `asset_tag` exacto usando `Asset::normalizeAssetTag()`.
  - [x] Luego `serial` exacto usando `Asset::normalizeSerial()` y manejo de ambigüedad.
- [x] Implementar búsqueda parcial:
  - [x] Productos por `Product::normalizeName()` + `LIKE` escapado.
  - [x] Activos por `serial`/`asset_tag` (prefix/contains) con `LIKE` escapado.

3) Result list UX (AC: 1, 4)
- [x] UI de resultados con secciones "Activos" y "Productos" (si aplica) y filas clicables.
- [x] Integrar `<x-ui.long-request target=\"search\" />` (o equivalente) para el caso `>3s` + Cancelar.
- [x] Mensajería "sin resultados" y "mínimo de caracteres" (ej. 2) consistente con `EmployeeCombobox`.

4) Tests (AC: 1, 2, 3)
- [x] Feature tests (HTTP o Livewire) que cubran:
  - [x] Bloqueo por RBAC sin `inventory.view`.
  - [x] Match exacto `asset_tag` → navega a `inventory.products.assets.show`.
  - [x] Match exacto `serial` único → navega a detalle; serial ambiguo → lista.
  - [x] Búsqueda por nombre de producto devuelve resultados.

## Dev Notes

### Objetivo técnico

Implementar una búsqueda unificada “operativa” (rápida, escaneable y con salto directo) que se integre al stack actual (Laravel 11 + Livewire 3 + Bootstrap 5) sin inventar infraestructura nueva.

### UX / Comportamiento esperado (muy importante)

- Desktop-first: un campo de búsqueda con resultados rápidos y clicables.
- “Salto directo”:
  - Si `asset_tag` coincide exacto → redirigir inmediatamente al detalle del Activo.
  - Si `serial` coincide exacto y es único (1 match) → redirigir al detalle del Activo.
  - Si `serial` exacto tiene múltiples matches → NO redirigir; mostrar lista y exigir selección.
- No mezclar dominios: NO incluir “Tareas Pendientes” en resultados del buscador de inventario (regla MVP).
- Latencia: si la búsqueda tarda >3s mostrar overlay `<x-ui.long-request />` y permitir Cancelar (conservar estado previo/inputs).

### Patrones existentes a respetar (no reinventar)

- Normalización y LIKE seguro:
  - Producto: `Product::normalizeName()` + `LIKE` con escape (ver `ProductsIndex::escapeLike()`).
  - Activo: `Asset::normalizeSerial()` / `Asset::normalizeAssetTag()` y `LIKE` con escape (ver `AssetsIndex::escapeLike()`).
- Query string: usar `#[Url(as: 'q')]` para persistir/bm (ver `AssetsIndex::$search`).
- Errores: usar `ErrorReporter` + toast con `error_id` (ver `EmployeeCombobox`).

### Project Structure Notes

- Rutas/código/DB: en inglés (kebab-case para paths; dot.case para names). UI/mensajes: en español.
- Componentes sugeridos (alineado a `_bmad-output/implementation-artifacts/architecture.md`):
  - Livewire: `gatic/app/Livewire/Search/*` (p.ej. `InventorySearch` o `UnifiedSearch`)
  - Action: `gatic/app/Actions/Search/*` (p.ej. `SearchInventory`)
  - Views: `gatic/resources/views/livewire/search/*`
- Navegación al detalle:
  - Producto: `inventory.products.show` (`/inventory/products/{product}`)
  - Activo: `inventory.products.assets.show` (`/inventory/products/{product}/assets/{asset}`)

### References

- Requerimientos/AC: `_bmad-output/implementation-artifacts/epics.md#Epic 6` y `_bmad-output/implementation-artifacts/prd.md#Search & Discovery`
- UX “búsqueda unificada” + latencia/cancelar: `_bmad-output/implementation-artifacts/ux.md`
- Guardrails stack/RBAC/no WebSockets: `docsBmad/project-context.md` y `project-context.md`
- Patrones de búsqueda existentes: `gatic/app/Livewire/Inventory/Products/ProductsIndex.php`, `gatic/app/Livewire/Inventory/Assets/AssetsIndex.php`

## Technical Requirements

- Autorización obligatoria: `Gate::authorize('inventory.view')` en cualquier componente/endpoint de búsqueda.
- Normalización:
  - Producto: usar `Product::normalizeName()` para input y para comparación.
  - Activo: usar `Asset::normalizeSerial()` / `Asset::normalizeAssetTag()`; `asset_tag` debe tratarse en mayúsculas (modelo ya lo hace al set).
- `LIKE` seguro: SIEMPRE escapar `%` y `_` (ver patrón `escapeLike()` en componentes existentes).
- Soft-deletes: por default Eloquent excluye `deleted_at`; no usar `withTrashed()` salvo que se pida explícitamente (no está en el alcance).

## Architecture Compliance

- No agregar WebSockets/SSE/Echo/Pusher (regla no negociable).
- Preferir `app/Actions/*` para lógica; mantener el componente Livewire como orquestador (estado/UI).
- Mantener consistencia de rutas bajo `routes/web.php` (prefijos existentes) y naming `inventory.*`.
- No crear helpers globales; si se requiere shared logic, ubicar en `app/Support/*`.

## Library / Framework Requirements

- Livewire 3:
  - Query string con `#[Url(as: 'q')]` (ver docs “URL Query Parameters”).
  - Redirecciones desde componente con `$this->redirectRoute(...)` (ver docs “Redirecting”).
- Laravel 11 + MySQL 8:
  - Para MVP, `LIKE` + buenos índices es suficiente; NO introducir Scout o motores externos en esta story.

## File Structure Requirements

Recomendado (ajustar a convenciones del repo):
- `gatic/app/Livewire/Search/InventorySearch.php` (pantalla de resultados / orquestación)
- `gatic/app/Actions/Search/SearchInventory.php` (lógica de búsqueda)
- `gatic/resources/views/livewire/search/inventory-search.blade.php` (UI)
- `gatic/routes/web.php` (route `/inventory/search`)

## Testing Requirements

- Tests enfocados en comportamiento y seguridad:
  - RBAC: sin `inventory.view` no hay resultados ni acceso.
  - Exact match `asset_tag` redirige.
  - Exact match `serial` redirige solo si es único; si es ambiguo → lista.
  - Búsqueda por nombre de producto retorna resultados relevantes.
- Evitar flaky: usar factories, `RefreshDatabase`, y congelar tiempo solo si se agrega indicador de frescura.

## Git Intelligence Summary

Commits recientes relevantes (patrones y convenciones ya establecidos):
- `feat(dashboard): implementar dashboard mínimo de métricas operativas con polling (Story 5.6)`
- `feat(inventory): implementar kardex para productos por cantidad (Story 5.5)`
- `feat(movements): implement Story 5.4 (Quantity movements for non-serialized products)`
- `feat(movements): implement Story 5.3 - Loan and return asset (FR18, FR19)`

Implicaciones para esta story:
- Ya existen patrones de Livewire + Bootstrap y componentes UX reutilizables (`long-request`, `freshness`, `toasts`) que deben reutilizarse.

## Latest Tech Information

- Livewire 3 (docs oficiales):
  - URL Query Parameters (`#[Url]`) para mantener `q` en la URL.
  - Redirecting: usar `$this->redirect()` / `$this->redirectRoute()` dentro del componente para navegación.
- Laravel 11:
  - Existe `whereFullText` con índices FULLTEXT, pero para este alcance MVP se mantiene `LIKE` (sin migraciones extra) a menos que performance lo exija.

## Project Context Reference

- Bible/stack/reglas: `docsBmad/project-context.md`
- Reglas lean (idioma, rutas, tooling local): `project-context.md`
- Arquitectura/patrones y estructura: `_bmad-output/implementation-artifacts/architecture.md`

## Story Completion Status

- Status: **done**
- Completion note: "Implementación completa: búsqueda unificada con salto directo por match exacto de asset_tag/serial, RBAC server-side, entry point desde la UI (sidebar) y UX de latencia >3s con Cancelar. 15 tests de la story pasan."

## Dev Agent Record

### Agent Model Used

Claude Opus 4.5 (claude-opus-4-5-20251101)

### Debug Log References

 - `_bmad/core/tasks/workflow.xml`
 - `_bmad/bmm/workflows/4-implementation/dev-story/workflow.yaml`
 - `_bmad/bmm/workflows/4-implementation/dev-story/instructions.xml`
 - `_bmad-output/implementation-artifacts/sprint-status.yaml`
 - `project-context.md`

### Implementation Plan

1. Crear ruta `/inventory/search` con middleware `can:inventory.view`
2. Crear componente Livewire `InventorySearch` con `#[Url(as: 'q')]` para persistir query string
3. Crear Action `SearchInventory` con lógica de match exacto y búsqueda parcial
4. UI de resultados con secciones Activos/Productos, `<x-ui.long-request>` y mensajes de estado

### Completion Notes List

 - Implementado componente `App\Livewire\Search\InventorySearch` con autorización server-side `inventory.view`
 - Implementado Action `App\Actions\Search\SearchInventory` con:
   - Match exacto por `asset_tag` (case-insensitive via `normalizeAssetTag`)
   - Match exacto por `serial` con manejo de ambigüedad (múltiples matches → lista)
   - Búsqueda parcial por nombre de producto y serial/asset_tag con LIKE escapado
 - UI de resultados con secciones "Activos" y "Productos", filas clicables, status badges
 - Integrado `<x-ui.long-request />` para UX de latencia >3s + Cancelar
 - Agregado entry point en sidebar: `Inventario > Búsqueda` → `/inventory/search`
 - Mensajes de estado: "mínimo 2 caracteres", "sin resultados", tip inicial
 - Persistencia de `q` en URL via `#[Url(as: 'q')]`
 - 15 feature tests cubriendo RBAC, match exacto, ambigüedad, búsqueda parcial
 - 289 tests totales pasando (0 regresiones)
 - Pint y PHPStan pasan sin errores en archivos nuevos

### File List

 - `gatic/app/Livewire/Search/InventorySearch.php` (nuevo)
 - `gatic/app/Actions/Search/SearchInventory.php` (nuevo)
 - `gatic/resources/views/livewire/search/inventory-search.blade.php` (nuevo)
 - `gatic/resources/views/layouts/partials/sidebar-nav.blade.php` (modificado - link a búsqueda)
 - `gatic/routes/web.php` (modificado - agregada ruta search)
 - `gatic/tests/Feature/Search/InventorySearchTest.php` (nuevo)
 - `_bmad-output/implementation-artifacts/sprint-status.yaml` (modificado)
 - `_bmad-output/implementation-artifacts/6-1-busqueda-unificada-productos-activos-con-salto-directo-por-match-exacto.md` (nuevo)

## Senior Developer Review (AI)

- Fecha: 2026-01-17
- Veredicto: **Aprobado**
- Validación (backend): `docker exec gatic-laravel.test-1 php artisan test --filter InventorySearchTest` (15 passed)

### Fixes aplicados

- UI entry point: agregado link en sidebar a `/inventory/search`.
- UX long-request: removido `target="search"` (no existía método `search()` en el componente), para que el overlay aplique a requests reales del componente.
- Story hygiene: File List corregida (story file es nuevo, no “modificado”).

## Change Log

- 2026-01-17: Implementación completa de Story 6.1 - Búsqueda unificada con salto directo
- 2026-01-17: Code review: fixes de entry point y long-request + status → done
