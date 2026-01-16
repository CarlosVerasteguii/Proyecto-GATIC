# Story 5.4: Movimientos por cantidad vinculados a Producto y Empleado

Status: done

Story Key: `5-4-movimientos-por-cantidad-vinculados-a-producto-y-empleado`  
Epic: `5` (Gate 3: Operación diaria de movimientos)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Backlog (fuente de verdad): `_bmad-output/implementation-artifacts/epics.md` (Epic 5, Story 5.4; FR21)

Fuentes:
- `_bmad-output/implementation-artifacts/epics.md` (Epic 5 / Story 5.4; FR21)
- `_bmad-output/implementation-artifacts/prd.md` (FR21; NFR7)
- `_bmad-output/implementation-artifacts/ux.md` (movimientos adoption-first: mínimo obligatorio Receptor/Empleado + Nota; "qué cambió")
- `_bmad-output/implementation-artifacts/architecture.md` (Epic 5 mapping; transacciones + `lockForUpdate()`; estructura `app/Actions/*` + Livewire)
- `docsBmad/project-context.md` (bible: inventario dual; reglas no negociables)
- `project-context.md` (stack; reglas críticas para agentes; testing)
- `docsBmad/rbac.md` (gates `inventory.manage`, defensa en profundidad)
- `gatic/app/Models/Product.php` + `gatic/app/Models/Category.php` (producto por cantidad vs serializado)
- `gatic/app/Actions/Inventory/Adjustments/ApplyProductQuantityAdjustment.php` (patrón transaccional que actualiza `products.qty_total`)
- `gatic/app/Livewire/Inventory/Products/ProductShow.php` + `gatic/resources/views/livewire/inventory/products/product-show.blade.php` (UI actual para productos por cantidad)
- `gatic/app/Livewire/Ui/EmployeeCombobox.php` (selector reusable de Empleado)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Admin/Editor,  
I want registrar salidas/entradas por cantidad vinculadas a Producto y Empleado,  
so that el stock y la responsabilidad queden claros (FR21).

## Alcance

Incluye:
- Solo aplica a **Productos por cantidad** (Categoría con `is_serialized=false`).
- Registrar un movimiento de **Salida** (disminuye stock) o **Entrada/Devolución** (aumenta stock).
- Campos mínimos (adoption-first): **Empleado (RPE) + Nota** obligatorios.
- Actualizar `products.qty_total` de forma **atómica** (transacción + `lockForUpdate()`), evitando stock negativo.
- Persistir registro de movimiento por cantidad (para trazabilidad inmediata y para habilitar Kardex en Story 5.5).
- UI mínima: punto de entrada desde detalle de Producto por cantidad + formulario Livewire con validación inline + toast “qué cambió”.
- Defensa en profundidad: rutas protegidas con `can:inventory.manage` + `Gate::authorize('inventory.manage')` en Livewire.

No incluye (fuera de scope):
- Kardex/historial UI (Story 5.5) más allá de capturar datos base.
- Inventario multi-ubicación, costos, ni “existencias por almacén”.
- Movimientos de Activos serializados (ya cubiertos por Stories 5.2/5.3).
- Ajustes administrativos de inventario (ya existe `Ajustar inventario` admin-only).

## Acceptance Criteria

### AC1 - Salida por cantidad (stock nunca negativo)

**Given** un Producto por cantidad con stock disponible  
**When** el usuario registra una salida por cantidad a un Empleado y captura una nota obligatoria  
**Then** el stock disminuye en la cantidad registrada  
**And** el sistema evita que el stock quede negativo

### AC2 - Validación: nota obligatoria en salida

**Given** el formulario de salida por cantidad  
**When** el usuario intenta guardar sin nota  
**Then** el sistema bloquea la operación  
**And** muestra un mensaje de validación indicando que la nota es obligatoria

### AC3 - Entrada/Devolución por cantidad (stock aumenta)

**Given** una salida previa registrada  
**When** el usuario registra una devolución/entrada y captura una nota obligatoria  
**Then** el stock aumenta en la cantidad registrada  
**And** el movimiento queda asociado al Empleado

### AC4 - Validación: nota obligatoria en entrada

**Given** el formulario de devolución/entrada por cantidad  
**When** el usuario intenta guardar sin nota  
**Then** el sistema bloquea la operación  
**And** muestra un mensaje de validación indicando que la nota es obligatoria

### AC5 - Acceso por rol (defensa en profundidad)

**Given** un usuario Admin o Editor  
**When** abre o ejecuta el registro de movimiento por cantidad  
**Then** el servidor permite la operación

**Given** un usuario Lector  
**When** intenta acceder por URL directa o ejecutar la acción Livewire  
**Then** el servidor bloquea (403 o equivalente)

## Tasks / Subtasks

1) Modelo de datos (AC: 1, 3)
- [x] Crear tabla de movimientos por cantidad (e.g. `product_quantity_movements`) con:
  - `product_id`, `employee_id`, `actor_user_id`
  - `direction` (enum: `out`|`in`)
  - `qty` (unsigned int, min 1)
  - `qty_before`, `qty_after` (unsigned int) para trazabilidad y base para kardex
  - `note` (text)
  - timestamps + índices (`product_id, created_at`, `employee_id, created_at`, `direction`)
- [x] Crear modelo `ProductQuantityMovement` con relaciones a `Product`, `Employee`, `User`.

2) Caso de uso transaccional (AC: 1–4)
- [x] Action en `gatic/app/Actions/Movements/Products/*` que ejecute:
  - Validaciones (`employee_id`, `product_id`, `direction`, `qty`, `note`, `actor_user_id`).
  - Cargar Producto con `lockForUpdate()`.
  - Asegurar que el Producto sea por cantidad (`category.is_serialized=false`).
  - Calcular `qty_before` y `qty_after`.
  - Para `out`: bloquear si `qty_before < qty` con error de validación accionable.
  - Actualizar `products.qty_total` y crear movimiento en la misma transacción.

3) UI Livewire (AC: 1–5)
- [x] Agregar ruta protegida bajo `inventory.manage` (p.ej. `/inventory/products/{product}/movements/quantity`).
- [x] Livewire form para registrar movimiento (radio Salida/Entrada, cantidad, empleado, nota).
- [x] Reusar `EmployeeCombobox`.
- [x] Feedback: validación inline + toast "qué cambió" (cantidad antes→después) y redirect a `inventory.products.show`.

4) Integración en UI existente
- [x] En `gatic/resources/views/livewire/inventory/products/product-show.blade.php` (solo no serializado): botón "Registrar movimiento".

5) Tests (AC: 1–5)
- [x] Feature tests para RBAC, happy path (out/in), validación de nota, y bloqueo por stock insuficiente.



## Dev Notes

### Semántica de inventario por cantidad (baseline)

- Para productos por cantidad, el stock “baseline” se representa por `products.qty_total`.
- Los **ajustes Admin** ya existen (`Ajustar inventario`) y setean el baseline a un valor exacto (correcciones).
- Esta story agrega **movimientos operativos** (Salida/Entrada) que modifican `qty_total` incrementalmente y dejan trazabilidad.

### UX mínima (adoption-first)

- Mantener el flujo “consultar → actuar” sin perder contexto: desde el detalle del Producto (por cantidad) abrir el formulario.
- Solo 2 obligatorios: **Empleado + Nota** (y cantidad como dato operativo). Evitar campos “nice to have”.
- Confirmación post-acción debe decir **qué cambió**: `qty_before → qty_after`.

### Reuso / No reinventar

- Reusar el patrón transaccional de `gatic/app/Actions/Inventory/Adjustments/ApplyProductQuantityAdjustment.php` (validar → `DB::transaction()` → `lockForUpdate()` → guardar).
- Reusar el patrón RBAC/Livewire existente en movimientos de Activos (Stories 5.2/5.3): `Gate::authorize('inventory.manage')`, validación inline, toasts.



## Dev Agent Guardrails (No Negociables)

- Identificadores de código/DB/rutas en inglés; copy/UI en español.
- No usar controllers para pantallas; usar Livewire (controllers solo “bordes”).
- Siempre `authorize + validate` antes de mutaciones.
- Operaciones críticas sobre stock: siempre en transacción y con lock (`lockForUpdate()`), sin excepciones.
- No permitir stock negativo; el error debe ser de validación accionable (no 500).
- No romper el flujo existente de `Ajustar inventario` (admin-only).



## Architecture Compliance

- Ubicación de código (según `_bmad-output/implementation-artifacts/architecture.md`):
  - `gatic/app/Actions/Movements/Products/*` (caso de uso transaccional)
  - `gatic/app/Models/*Movement*.php` (modelo de movimientos)
  - `gatic/app/Livewire/Movements/*` (UI)
  - `gatic/routes/web.php` bajo grupo `inventory.manage`
- DB:
  - Asegurar consistencia con `DB::transaction()`.
  - Bloqueo: `Product::query()->lockForUpdate()->findOrFail(...)`.
- Polling/WebSockets: no aplica aquí; evitar complejidad.



## Library / Framework Requirements

Permanecer en el stack actual del repo (no upgrades sin story explícita):
- Laravel `v11.47.0` (lock: `gatic/composer.lock`).
- Livewire `v3.7.3` (lock: `gatic/composer.lock`).
- Bootstrap `5.2.3` (npm: `gatic/package.json`).

Web research (para evitar conocimiento desactualizado):
- Laravel 11 release notes: https://laravel.com/docs/11.x/releases
- Changelog framework 11.x: https://github.com/laravel/framework/blob/11.x/CHANGELOG.md
- Livewire 3.x docs (upgrade): https://livewire.laravel.com/docs/3.x/upgrading
- Bootstrap: referencia 5.2.3 https://blog.getbootstrap.com/2022/11/22/bootstrap-5-2-3/ (nota: existe Bootstrap 5.3.x; no migrar en esta story).



## File Structure Requirements (Propuesta concreta)

Nuevos archivos esperados (pueden ajustarse a naming final, pero mantener patrón):
- `gatic/database/migrations/YYYY_MM_DD_HHMMSS_create_product_quantity_movements_table.php`
- `gatic/app/Models/ProductQuantityMovement.php`
- `gatic/app/Actions/Movements/Products/RegisterProductQuantityMovement.php`
- `gatic/app/Livewire/Movements/Products/QuantityMovementForm.php`
- `gatic/resources/views/livewire/movements/products/quantity-movement-form.blade.php`

Archivos a modificar:
- `gatic/routes/web.php` (ruta bajo `inventory.manage`).
- `gatic/resources/views/livewire/inventory/products/product-show.blade.php` (botón para productos por cantidad).



## Testing Requirements

- Feature tests en `gatic/tests/Feature/Movements/*` (patrón existente: `AssetLoanTest`, `AssetReturnTest`).
- Casos mínimos:
  - Admin y Editor: acceso OK a la ruta y ejecución Livewire.
  - Lector: 403 por ruta y `assertForbidden()` en Livewire.
  - Salida happy path: `qty_total` disminuye y se inserta movimiento.
  - Entrada happy path: `qty_total` aumenta y se inserta movimiento.
  - Nota requerida: valida y no modifica stock.
  - Stock insuficiente: error de validación accionable y sin cambios en DB.



## Previous Story Intelligence (Epic 5)

Aprendizajes a reusar (de 5.2/5.3):
- “Mínimo obligatorio” consistente: `note` `min:5` `max:1000` + mensajes claros.
- Action transaccional + `lockForUpdate()` para integridad.
- Livewire: bloquear por Gate en `mount()` y en acciones públicas.
- UX: toasts claros (“qué cambió”) y redirect al detalle.

Referencias:
- `_bmad-output/implementation-artifacts/5-2-asignar-un-activo-serializado-a-un-empleado.md`
- `_bmad-output/implementation-artifacts/5-3-prestar-y-devolver-un-activo-serializado.md`



## Git Intelligence Summary

Commits recientes relevantes (patrones a replicar):
- `6157f42` feat(movements): implement Story 5.3 (Loan/Return asset)
- `0da981f` feat(movements): implement Story 5.2 (Assign asset)
- `0e06547` feat(inventory): implement Story 5.1 (Asset state transitions)

Patrón dominante:
- `Actions/*` para transacciones
- Livewire para pantallas
- Tests feature por flujo



## Latest Tech Information (Relevante para esta story)

- Laravel 11: seguir prácticas actuales de validación/DB transactions (`DB::transaction`, `lockForUpdate`).
- Livewire 3: usar el enfoque actual de componentes + testing con `Livewire::test()`.
- Bootstrap: mantener 5.2.3 para consistencia visual; no introducir dependencias nuevas.



## Project Context Reference

Reglas críticas (fuente de verdad):
- `docsBmad/project-context.md` (inventario dual: serializado vs cantidad; restricciones no negociables).
- `project-context.md` (stack, RBAC, testing, rutas en inglés/UI en español).
- `docsBmad/rbac.md` (gates: `inventory.manage` vs `inventory.view`).
- `_bmad-output/implementation-artifacts/architecture.md` (estructura y límites; Actions/Livewire/Tests).
- `_bmad-output/implementation-artifacts/ux.md` (adoption-first; confirmar “qué cambió”).



## Story Completion Status

- Status: `done`
- Nota: "Implementación completa de movimientos por cantidad (FR21). Incluye migración, modelo, action transaccional, componente Livewire y 21 feature tests."
- Fecha: 2026-01-16

## Senior Developer Review (AI)

### Hallazgos (resueltos)

- [HIGH] FKs en `product_quantity_movements` estaban con `cascadeOnDelete()` (riesgo de perder trazabilidad). Fix: `restrictOnDelete()` alineado con `asset_movements`.
- [MEDIUM] Duplicación/deriva potencial en obtención+validación de "producto por cantidad" (ajustes vs movimientos). Fix: helper compartido `LockQuantityProduct` usado por ambos.
- [MEDIUM] `qty_total` `null` se trataba como `0` (silencioso) en movimientos. Fix: validación explícita con error accionable.
- [MEDIUM] AC4 (nota obligatoria en entrada) no estaba cubierto explícitamente. Fix: nuevo test `test_note_is_required_for_incoming_movement`.
- [LOW] Contador de caracteres usaba `strlen()` (no multibyte). Fix: `mb_strlen()`.

### Evidencia (tests)

- Nota de entorno: este repo requiere PHP 8.2+ (ver `docsBmad/project-context.md`). En esta máquina el PHP nativo es 8.0.x, por eso la validación se corrió en Docker/Sail.
- `docker compose -f gatic/compose.yaml up -d`
- `docker compose -f gatic/compose.yaml exec -T laravel.test php artisan test` PASS (255 tests, 653 assertions)
- `docker compose -f gatic/compose.yaml down`

## Change Log

- 2026-01-16: Implementación completa de Story 5.4 - Movimientos por cantidad vinculados a Producto y Empleado (FR21)
- 2026-01-16: Senior Developer Review (AI) - fixes aplicados (FKs restrict, helper compartido, validación `qty_total` nulo, test AC4, `mb_strlen`) y story pasa a `done`.

## Dev Agent Record

### Agent Model Used

Claude Opus 4.5 (Claude Code CLI)

### Debug Log References

- Carga de contexto desde `sprint-status.yaml`, story file, `project-context.md`
- Analisis de patrones existentes en `AssignAssetToEmployee.php`, `AssignAssetForm.php`
- Validación en Docker/Sail: `docker compose -f gatic/compose.yaml exec -T laravel.test php artisan test` PASS (255 tests, 653 assertions)

### Implementation Plan

1. Crear migracion `create_product_quantity_movements_table` con indices para trazabilidad
2. Crear modelo `ProductQuantityMovement` con relaciones a Product, Employee, User
3. Crear action `RegisterProductQuantityMovement` con patron transaccional + lockForUpdate
4. Crear componente Livewire `QuantityMovementForm` con validacion inline
5. Agregar ruta `/inventory/products/{product}/movements/quantity` bajo `inventory.manage`
6. Integrar boton "Registrar movimiento" en product-show para productos no serializados
7. Crear 21 feature tests cubriendo RBAC, happy paths, validaciones y edge cases

### Completion Notes List

- Story seleccionada automaticamente desde `sprint-status.yaml`
- Implementacion siguiendo patrones de Stories 5.2/5.3 (Actions + Livewire + Tests)
- Validacion de stock no negativo con error accionable (no 500)
- Toast de confirmacion con "que cambio": `qty_before -> qty_after`
- Tests verificados en Docker/Sail: `docker compose -f gatic/compose.yaml exec -T laravel.test php artisan test` PASS (255 tests, 653 assertions)

### File List

**Nuevos:**
- `gatic/database/migrations/2026_01_16_000001_create_product_quantity_movements_table.php` (NEW)
- `gatic/app/Models/ProductQuantityMovement.php` (NEW)
- `gatic/app/Actions/Movements/Products/RegisterProductQuantityMovement.php` (NEW)
- `gatic/app/Actions/Inventory/Products/LockQuantityProduct.php` (NEW - helper compartido)
- `gatic/app/Livewire/Movements/Products/QuantityMovementForm.php` (NEW)
- `gatic/resources/views/livewire/movements/products/quantity-movement-form.blade.php` (NEW)
- `gatic/tests/Feature/Movements/ProductQuantityMovementTest.php` (NEW)

**Modificados:**
- `gatic/routes/web.php` (MODIFIED - nueva ruta)
- `gatic/resources/views/livewire/inventory/products/product-show.blade.php` (MODIFIED - boton registrar movimiento)
- `gatic/app/Actions/Inventory/Adjustments/ApplyProductQuantityAdjustment.php` (MODIFIED - shared helper + validación consistente)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MODIFIED - status in-progress -> done)
- `_bmad-output/implementation-artifacts/5-4-movimientos-por-cantidad-vinculados-a-producto-y-empleado.md` (MODIFIED - tareas completadas)

