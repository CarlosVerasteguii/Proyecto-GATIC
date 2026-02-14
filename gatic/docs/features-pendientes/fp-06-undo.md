# FP-06 — Deshacer (Undo) en movimientos (Producto final)

## Objetivo

Permitir **deshacer** movimientos de inventario de forma **segura, trazable e idempotente**, con soporte real en UI mediante **toast con acción “Deshacer”** que funciona incluso después de `redirectRoute(..., navigate: true)`.

## Alcance (incluido)

### Movimientos individuales

- **Activos (serializados)**: `assign`, `unassign`, `loan`, `return` (tabla `asset_movements`).
- **Productos (cantidad)**: movimientos `in/out` (tabla `product_quantity_movements`).

### Movimientos masivos

- **Bulk assign** (selección de activos): Undo **all-or-nothing** a nivel **batch**.

## NO-Scope (no incluido)

- “Undo” de **Inventory Adjustments** (son otra clase de operación).
- “Undo” de flujos de **Tareas Pendientes / Quick Capture**.
- UI automation (Playwright/Dusk) como parte del feature (solo smoke opcional).

## UX (toast con acción)

- Tras completar un movimiento exitoso se muestra un toast en español con botón **“Deshacer”**.
- La acción del botón dispara el evento Livewire **`ui:undo-movement`** con `{ token }`.
- Para que sobreviva redirects/navigate, el toast se puede “flashear” vía sesión usando `session('ui_toasts')` (ver `docs/ui-patterns.md`).

## Cómo funciona (modelo mental)

- El servidor crea un **Undo Token** persistente (`undo_tokens`) por una ventana corta (`window_s`).
- El token apunta a:
  - un movimiento individual (`movement_id`), o
  - un batch (`batch_uuid`) para bulk assign.
- Al deshacer, **NO se borran registros**: se crea un **movimiento compensatorio** (trazabilidad completa).

## Reglas de seguridad y consistencia (por qué puede fallar)

### Seguridad (RBAC + actor-binding)

- Solo usuarios con permiso `inventory.manage` pueden deshacer (Gate server-side).
- Recomendado/implementado: **solo el actor original** puede usar el token.
- **Admin override**: un Admin puede deshacer aunque no sea el actor (explícito).

### Validaciones del token

- Token **expirado** ⇒ bloqueado.
- Token **ya usado** ⇒ idempotencia: no cambia nada y responde con mensaje claro.

### Reglas “undoable”

- El movimiento a deshacer debe seguir siendo el **último movimiento relevante** del recurso (asset/product) y el estado actual debe coincidir con el **estado esperado**.
- Si hubo un **Inventory Adjustment** posterior para el mismo recurso, el Undo queda **bloqueado**.

## Mapeo de compensación (qué crea el Undo)

### Assets (`asset_movements`)

- `assign` ↔ crea `unassign`
- `unassign` ↔ crea `assign` (mismo `employee_id`)
- `loan` ↔ crea `return`
- `return` ↔ crea `loan` y **restaura `loan_due_date`** usando el valor capturado en el movimiento `return`

### Product qty (`product_quantity_movements`)

- `in` ↔ crea `out` (y viceversa)
- Ajusta `products.qty_total` de forma consistente y registra el movimiento compensatorio.

### Bulk assign (batch)

- Cada movimiento del lote lleva `asset_movements.batch_uuid`.
- El Undo del batch es **all-or-nothing**:
  - si **algún asset cambió** desde el batch ⇒ no se revierte nada
  - el token queda **sin usar** si la operación falla (para permitir reintento si el estado vuelve a ser consistente)

## Configuración

En `config/gatic.php`:

- `gatic.inventory.undo.window_s` (default: `10`)
  - Env: `GATIC_INVENTORY_UNDO_WINDOW_S`
- `gatic.inventory.undo.token_retention_days` (default: `7`)
  - Env: `GATIC_INVENTORY_UNDO_TOKEN_RETENTION_DAYS`

## Limpieza (pruning)

`UndoToken` implementa `Prunable` para tokens usados/expirados.

Si no se ejecuta `model:prune` en scheduler, se puede correr manualmente:

- `php artisan model:prune --model=App\\Models\\UndoToken`

## Tests

- `tests/Feature/Movements/UndoMovementByTokenTest.php`
- `tests/Feature/Movements/UndoBulkAssignTest.php`

