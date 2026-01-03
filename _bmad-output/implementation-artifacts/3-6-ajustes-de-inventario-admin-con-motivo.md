# Story 3.6: Ajustes de inventario (Admin) con motivo

Status: done

Story Key: `3-6-ajustes-de-inventario-admin-con-motivo`  
Epic: `3` (Gate 2: Inventario navegable)

Fuentes:
- `_bmad-output/implementation-artifacts/epics.md` (Story 3.6; FR14)
- `_bmad-output/implementation-artifacts/prd.md` (FR14; NFR8)
- `_bmad-output/implementation-artifacts/architecture.md` (stack + constraints + patrones de transacciones)
- `_bmad-output/implementation-artifacts/ux.md` (UX: confianza/trazabilidad; tablas densas)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (orden + notas del Epic 3)
- `docsBmad/project-context.md` (bible: roles + semántica QTY + estados + transacciones)
- `project-context.md` (reglas críticas para agentes)
- `_bmad-output/implementation-artifacts/3-5-detalle-de-activo-con-estado-ubicacion-y-tenencia-actual.md` (patrones de rutas/guardrails/UX)
- `_bmad-output/implementation-artifacts/3-4-detalle-de-producto-con-conteos-y-desglose-por-estado.md` (semántica QTY y agregados)
- `_bmad-output/implementation-artifacts/3-3-listado-de-inventario-productos-con-indicadores-de-disponibilidad.md` (semántica QTY establecida)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Admin,
I want realizar ajustes de inventario registrando un motivo,
so that el sistema refleje la realidad física con trazabilidad (FR14).

## Acceptance Criteria

### AC1 - Autorización (Admin-only, server-side)

**Given** un usuario autenticado  
**When** intenta acceder al módulo de ajustes de inventario  
**Then** solo **Admin** puede acceder (403 para Editor/Lector)

**And** la autorización se valida server-side en rutas y en el componente (`Gate::authorize('admin-only')` o equivalente).

### AC2 - Ajuste de Producto por cantidad (baseline `qty_total`)

**Given** un Producto por cantidad (`categories.is_serialized=false`) y NO soft-deleted  
**When** Admin registra un ajuste de inventario del Producto  
**Then** puede establecer un nuevo `qty_total` (entero, `>= 0`)

**And** el sistema requiere `motivo` (texto no vacío) antes de guardar

**And** el cambio queda registrado como ajuste auditable (quién, cuándo, motivo, antes/después)

**And** la operación es transaccional (si falla el registro del ajuste, no se modifica `qty_total`).

### AC3 - Ajuste de Activo serializado (estado/ubicación)

**Given** un Activo serializado existente (NO soft-deleted) y perteneciente a su Producto  
**When** Admin registra un ajuste de inventario del Activo  
**Then** puede:
- cambiar `status` (solo valores de `Asset::STATUSES`)
- cambiar `location_id` (existente y NO soft-deleted)

**And** el sistema requiere `motivo` (texto no vacío)

**And** el cambio queda registrado como ajuste auditable (quién, cuándo, motivo, antes/después)

**And** la operación es transaccional (no deja el Activo en un estado inconsistente).

### AC4 - Guardrails de dominio (no mezclar épicas)

**Given** que estamos en Épica 3 (Gate 2)  
**When** Admin hace un ajuste  
**Then** el sistema NO introduce:
- Empleados/RPE ni tenencia real (Épicas 4/5)
- Movimientos (asignar/prestar/devolver) (Épica 5)
- Kardex por cantidad (Épica 5)

**And** el ajuste es un “baseline correction” con trazabilidad mínima: motivo + actor + timestamp + before/after.

### AC5 - UX baseline (sin fricción, Bootstrap 5)

**Given** un ajuste en pantalla  
**When** Admin intenta guardar  
**Then** ve validación inline clara en español (motivo requerido, números inválidos, etc.)

**And** al completar, el sistema redirige con toast/mensaje de éxito

**And** la navegación “Volver” preserva contexto cuando aplique (`q`/`page`), siguiendo el patrón ya usado en inventario.

### AC6 - Integridad y consistencia

**Given** un ajuste de Producto por cantidad  
**When** Admin guarda el ajuste  
**Then** `qty_total` nunca queda negativo

**And** el estado de inventario queda consistente con la semántica QTY existente (sin recalcular cosas en PHP por renglón).

## Tasks / Subtasks

1) Modelo de datos y persistencia de ajustes (AC: 2-4, 6)
- [x] Crear migración(es) para registrar ajustes:
  - `inventory_adjustments` (header): `id`, `actor_user_id`, `reason`, `created_at`
  - `inventory_adjustment_entries` (líneas): `id`, `inventory_adjustment_id`, `subject_type`, `subject_id`, `product_id` nullable, `asset_id` nullable, `before` JSON, `after` JSON
- [x] Agregar modelos Eloquent (mínimo):
  - `App\\Models\\InventoryAdjustment`
  - `App\\Models\\InventoryAdjustmentEntry`
- [x] Asegurar integridad referencial y soft-delete policy (no ajustar registros eliminados).

2) Casos de uso transaccionales (AC: 2, 3, 6)
- [x] Implementar Actions transaccionales (patrón arquitectura):
  - `app/Actions/Inventory/Adjustments/ApplyProductQuantityAdjustment`
  - `app/Actions/Inventory/Adjustments/ApplyAssetAdjustment`
- [x] En cada Action:
  - validar inputs
  - cargar modelos requeridos (Product/Asset/Location)
  - capturar snapshot before/after (mínimo: campos afectados)
  - ejecutar todo dentro de `DB::transaction()`
  - persistir ajuste + update del modelo objetivo

3) UI Livewire (AC: 1-5)
- [x] Agregar pantallas Admin-only:
  - `Inventory/Adjustments/AdjustmentsIndex` (histórico simple, opcional si reduce scope)
  - `Inventory/Adjustments/ProductAdjustmentForm` (para productos por cantidad)
  - `Inventory/Adjustments/AssetAdjustmentForm` (para activos serializados)
- [x] Formularios deben incluir:
  - campo `motivo` (textarea)
  - preview de before/after (en lectura) antes de confirmar
  - confirmación clara (“Este cambio afecta el baseline del inventario”)
- [x] Integrar accesos desde UI existente:
  - botón “Ajustar inventario” en detalle de Producto (solo Admin y solo por cantidad)
  - botón “Ajustar” en detalle de Activo (solo Admin)

4) Routing + autorización (AC: 1, 5)
- [x] Agregar rutas bajo middleware Admin-only:
  - `GET /inventory/adjustments` (opcional)
  - `GET|POST /inventory/products/{product}/adjust` (solo por cantidad)
  - `GET|POST /inventory/products/{product}/assets/{asset}/adjust` (serializados)
- [x] Aplicar `whereNumber` para `product` y `asset` (consistencia con historias 3.4/3.5).
- [x] En `mount()` y `render()`, ejecutar `Gate::authorize('admin-only')`.

5) Tests (AC: 1-6)
- [x] Feature tests (mínimo) con `RefreshDatabase`:
  - Admin puede ver formularios y guardar ajustes
  - Editor/Lector => 403
  - Producto por cantidad: `qty_total` cambia y se crea registro de ajuste con before/after + motivo
  - Activo serializado: `status`/`location_id` cambian y se crea registro de ajuste con before/after + motivo
  - Invalidaciones: motivo vacío => error; qty negativo => error; status fuera de set => error; soft-deleted => 404/forbidden según patrón

## Dev Notes

### DEV AGENT GUARDRAILS (no negociables)

- Esta story NO implementa Movimientos ni Empleados/RPE: el ajuste es “corrección de baseline” (Épica 3).
- Autorización Admin-only obligatoria server-side (Editor/Lector nunca deben poder ajustar).
- Todo ajuste debe quedar registrado con actor + timestamp + motivo + before/after; si falla el registro, rollback (transacción).
- Respetar semántica QTY existente para inventario:
  - serializados: disponibilidad depende de `assets.status` (y `Retirado` no cuenta baseline)
  - por cantidad: baseline es `products.qty_total` (sin kardex aún)
- No inventar estados: usar `Asset::STATUSES` y `Asset::UNAVAILABLE_STATUSES`.
- No tocar versiones ni dependencias (Laravel/Livewire/Bootstrap) en esta story.

### Developer Context (por qué existe esta story)

En operación real (Soporte TI), el inventario físico puede desfasarse por errores de captura, reposición, retiro, etc. Esta story permite a Admin corregir el baseline de forma controlada y trazable sin introducir todavía el flujo completo de movimientos (Épica 5).

### Epic Context (dependencias y límites)

- Esta story asume que ya existen los módulos base de Inventario (Épica 3): Productos/Activos (historias 3.1–3.5) y sus patrones de rutas/guardrails.
- Depende de entidades existentes:
  - `Product` con `qty_total` (productos por cantidad)
  - `Asset` con `status` + `location_id` (serializados)
- No introduce entidades de operación diaria:
  - Empleados/RPE (Épica 4)
  - Movimientos y kardex (Épica 5)

### Architecture Compliance

- Seguir patrón: Livewire (UI) → Actions (transacciones) → Models/DB.
- Operaciones críticas dentro de `DB::transaction()` (integridad).
- Mantener consistencia con estructura ya usada en inventario: módulos `Inventory/*` y rutas `inventory.*`.

### Library / Framework Requirements

- Laravel 11: mantener documentación oficial vigente; no hacer upgrades por esta story.
- Livewire 3: usar patrones existentes en este repo (autorización en `mount()`/`render()`, redirect con status).
- Bootstrap 5: mantener el rango del repo; UI densa y accesible.

### File Structure Requirements (propuesta concreta)

- `gatic/app/Actions/Inventory/Adjustments/ApplyProductQuantityAdjustment.php`
- `gatic/app/Actions/Inventory/Adjustments/ApplyAssetAdjustment.php`
- `gatic/app/Livewire/Inventory/Adjustments/ProductAdjustmentForm.php`
- `gatic/app/Livewire/Inventory/Adjustments/AssetAdjustmentForm.php`
- `gatic/resources/views/livewire/inventory/adjustments/product-adjustment-form.blade.php`
- `gatic/resources/views/livewire/inventory/adjustments/asset-adjustment-form.blade.php`
- `gatic/app/Models/InventoryAdjustment.php`
- `gatic/app/Models/InventoryAdjustmentEntry.php`
- `gatic/database/migrations/2026_01_03_000000_create_inventory_adjustments_table.php`
- `gatic/database/migrations/2026_01_03_000001_create_inventory_adjustment_entries_table.php`
- `gatic/routes/web.php`
- `gatic/tests/Feature/Inventory/InventoryAdjustmentsTest.php`

## Testing Requirements

- Comando recomendado (Sail, desde `gatic/`):
  - `vendor\\bin\\sail artisan test --filter InventoryAdjustmentsTest`
- Calidad (si existe en scripts):
  - `vendor\\bin\\sail composer pint -- --test`
  - `vendor\\bin\\sail composer larastan` (o `./vendor/bin/phpstan analyse`)

## Previous Story Intelligence (3.5 / 3.4 / 3.3)

- Mantener patrones ya implementados:
  - autorización server-side (`Gate::authorize(...)`) en `mount()` y también en `render()`
  - rutas con `whereNumber` y guardrails 404 cuando IDs no corresponden
  - exclusión de soft-delete en lecturas críticas (`deleted_at IS NULL`)
  - navegación “Volver” preservando contexto con query string (`q`, `page`)
  - UI Bootstrap “tabla densa” y estados con texto (no solo color)
- Reusar constantes de dominio:
  - `Asset::STATUSES`, `Asset::UNAVAILABLE_STATUSES`, `Asset::STATUS_RETIRED`

## Git Intelligence Summary

- Commits recientes relevantes (patrones a imitar):
  - `d7b5cd2` feat(inventory): detalle de Activo (3-5) → guardrails de routing + Gate + UI Bootstrap + tests
  - `bcfb674` feat(inventory): detalle de Producto (3-4) → agregados consistentes + semántica QTY + tests
  - `d2d4fec`/`d9e754c` feat(inventory): listado Productos (3-3) → anti N+1 + UX de indicadores
  - `5248ba8` feat(inventory): Activos serializados (3-2) → estructura `Inventory/Assets/*` + validaciones de unicidad

## Latest Tech Information

- Bootstrap 5: la serie 5.3 sigue recibiendo parches (ej. `v5.3.8`), pero esta story NO debe introducir upgrades de Bootstrap.
  - Fuente: `https://blog.getbootstrap.com/2025/08/25/bootstrap-5-3-8/`
- Livewire 3: la serie 3.x sigue activa (ej. `v3.6`); mantener patrones oficiales y los ya usados en el repo.
  - Fuentes: `https://github.com/livewire/livewire/releases`, `https://laravel-news.com/livewire-36-released`
- Laravel 11: mantener el rango actual del repo y preferir documentación oficial vigente.
  - Fuente: `https://laravel.com/docs/11.x`

## Project Context Reference

- Fuente de verdad: `docsBmad/project-context.md` (gana ante cualquier contradicción).
- Reglas críticas relevantes:
  - Roles MVP: Admin/Editor/Lector; Admin tiene override, pero Editor/Lector no deben ajustar inventario.
  - Semántica QTY: no disponibles = Asignado + Prestado + Pendiente de Retiro; `Retirado` no cuenta baseline.
  - Operaciones críticas: transaccionales (DB) y con validaciones consistentes.
  - Sin WebSockets; preferir patrones Livewire/polling solo cuando aplique (en formularios no es necesario).

### References

- Backlog/AC base: `_bmad-output/implementation-artifacts/epics.md` (Epic 3 / Story 3.6; FR14)
- PRD: `_bmad-output/implementation-artifacts/prd.md` (FR14; NFR8)
- Arquitectura: `_bmad-output/implementation-artifacts/architecture.md` (transacciones; estructura Actions)
- Reglas de dominio (bible): `docsBmad/project-context.md` (roles; estados; semántica QTY)
- Reglas operativas para agentes: `project-context.md`

## Story Completion Status

- Status: `done`
- Nota de completitud: "Revisado y corregido (code-review). Ajustes: historial opcional implementado, navegación preserva contexto, validaciones endurecidas y tests ampliados."

## Dev Agent Record

### Agent Model Used

Claude Sonnet 4 (Copilot CLI)

### Debug Log References

- Pint: Fixed 10 style issues (line endings, imports order, new with parentheses)
- PHPStan: Passed without errors
- Tests: 17 new tests, 136 total tests passing

### Implementation Plan

- Definir storage mínimo de ajustes (migraciones + modelos).
- Implementar Actions transaccionales (producto por cantidad / activo serializado).
- Implementar UI Livewire Admin-only con motivo obligatorio y preview before/after.
- Integrar accesos desde detalle de Producto/Activo.
- Tests feature: RBAC + validaciones + persistencia de ajuste + cambios correctos.

### Completion Notes List

- ✅ Creadas migraciones: `inventory_adjustments` + `inventory_adjustment_entries`
- ✅ Creados modelos: `InventoryAdjustment`, `InventoryAdjustmentEntry` con relaciones
- ✅ Implementadas Actions transaccionales: `ApplyProductQuantityAdjustment`, `ApplyAssetAdjustment`
- ✅ Creados componentes Livewire: `ProductAdjustmentForm`, `AssetAdjustmentForm` con autorización Admin-only
- ✅ Agregadas rutas con middleware `can:admin-only` y `whereNumber`
- ✅ Integrados botones "Ajustar inventario" en ProductShow y "Ajustar" en AssetShow (solo Admin)
- ✅ UI incluye motivo obligatorio, preview before/after, y mensaje de advertencia
- ✅ 17 tests feature creados cubriendo RBAC, validaciones, persistencia y soft-delete handling
- ✅ Pint + Larastan pasando; 136 tests en suite completa sin regresiones

### File List

- `gatic/database/migrations/2026_01_03_000000_create_inventory_adjustments_table.php`
- `gatic/database/migrations/2026_01_03_000001_create_inventory_adjustment_entries_table.php`
- `gatic/database/migrations/2026_01_03_000002_rename_inventory_adjustments_user_id_to_actor_user_id.php`
- `gatic/app/Models/InventoryAdjustment.php`
- `gatic/app/Models/InventoryAdjustmentEntry.php`
- `gatic/app/Actions/Inventory/Adjustments/ApplyProductQuantityAdjustment.php`
- `gatic/app/Actions/Inventory/Adjustments/ApplyAssetAdjustment.php`
- `gatic/app/Livewire/Inventory/Adjustments/AdjustmentsIndex.php`
- `gatic/app/Livewire/Inventory/Adjustments/ProductAdjustmentForm.php`
- `gatic/app/Livewire/Inventory/Adjustments/AssetAdjustmentForm.php`
- `gatic/resources/views/livewire/inventory/adjustments/adjustments-index.blade.php`
- `gatic/resources/views/livewire/inventory/adjustments/product-adjustment-form.blade.php`
- `gatic/resources/views/livewire/inventory/adjustments/asset-adjustment-form.blade.php`
- `gatic/resources/views/livewire/inventory/products/product-show.blade.php` (modified - added adjust button)
- `gatic/resources/views/livewire/inventory/products/products-index.blade.php` (modified - preserve `q`/`page` on links)
- `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php` (modified - added adjust button)
- `gatic/routes/web.php` (modified - added adjustment routes)
- `gatic/tests/Feature/Inventory/InventoryAdjustmentsTest.php`
- `_bmad-output/implementation-artifacts/3-6-ajustes-de-inventario-admin-con-motivo.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`

## Senior Developer Review (AI)

- Fecha: 2026-01-03
- Resultado: Cambios solicitados aplicados.
- Correcciones aplicadas:
  - Se implementó el histórico opcional `/inventory/adjustments` (Admin-only).
  - Se preserva contexto `q`/`page` en los flujos de ajuste (links y redirects).
  - Se endurecieron validaciones: no permitir `locations` soft-deleted y validaciones defensivas dentro de Actions.
  - Se alineó el esquema a `actor_user_id` (en vez de `user_id`) para trazabilidad consistente con la story.
  - Se ampliaron tests (RBAC para histórico + ubicación soft-deleted).

## Change Log

- 2026-01-03: Code review y correcciones aplicadas (histórico, guardrails, navegación, tests).
