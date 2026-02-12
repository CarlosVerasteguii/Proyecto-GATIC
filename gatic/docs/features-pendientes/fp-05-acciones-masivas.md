# FP-05 — Acciones masivas en tablas (MVP)

## Objetivo

Implementar acciones masivas (bulk actions) para la tabla global de **Activos** en `Inventario → Activos` (`/inventory/assets`).

MVP: seleccionar múltiples activos y ejecutar **“Asignar por lote”** a **un solo empleado** con **una sola nota**.

## Scope MVP (incluido)

- Selección múltiple en la tabla global de activos.
- Barra/toolbar de acciones masivas visible solo cuando hay selección.
- Modal Livewire/Bootstrap para bulk assign con:
  - empleado (`<livewire:ui.employee-combobox wire:model.live="bulkEmployeeId" />`)
  - nota (textarea)
- Backend transaccional **all-or-nothing** con locks (`SELECT ... FOR UPDATE`) y validaciones.
- Respeto estricto de RBAC (ver abajo).
- Tests automatizados (Feature/PHPUnit + Livewire donde aplique).

## NO-Scope (no incluido)

- Validación UI/manual (QA en navegador), Playwright o screenshots.
- Selección “todos los resultados” (cross-page / cross-filter).
- Otras acciones masivas (retiro/prestamo/desasignar), exportación, etc.

## RBAC (duro)

- `inventory.manage` (Admin/Editor):
  - ve checkboxes + barra de bulk + modal
  - puede ejecutar bulk assign
- `inventory.view` (Lector):
  - **no** ve elementos de bulk
  - **no** puede ejecutar métodos Livewire de bulk (debe abortar 403)

## Límite máximo de activos

- Config: `gatic.inventory.bulk_actions.max_assets` (default: 50) en `config/gatic.php`.
- Validación: `asset_ids` debe ser `>= 1` y `<= max_assets` con error claro.

## Comportamiento backend (all-or-nothing)

- Transacción DB con `lockForUpdate()` sobre todos los assets incluidos.
- Validar que todos los `asset_ids` existen y **no** están soft-deleted.
- Si 1 activo no es asignable por estado/transición:
  - no se asigna ninguno
  - no se crean movimientos parciales
- Para cada activo:
  - `AssetStatusTransitions::assertCanAssign($asset->status)`
  - `status => Asignado` + `current_employee_id => employee_id`
  - crear `AssetMovement` `TYPE_ASSIGN` con la misma nota
  - auditoría (best-effort) siguiendo el patrón de `AssignAssetToEmployee`

## Checklist por fases (ANTI-LOOP)

- [x] F0 - Contexto y plan (este doc + leer `project-context.md`)
- [x] F1 - Backend action + config max assets
- [x] F2 - Livewire state + server-side guards (403)
- [x] F3 - UI Blade + modal + `data-testid`
- [x] F4 - Tests automatizados (success, rollback, RBAC)
- [x] F5 - Verificación técnica con Docker Compose (php -v, pint, tests)
- [!] F6 - Commit local (sin push)

## Validación UI manual

Pendiente por el equipo (no incluida en este PR/cambio).
