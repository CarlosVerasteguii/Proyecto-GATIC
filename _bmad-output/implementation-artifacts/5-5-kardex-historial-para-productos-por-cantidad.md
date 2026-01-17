# Story 5.5: Kardex/historial para productos por cantidad

Status: done

Story Key: `5-5-kardex-historial-para-productos-por-cantidad`
Epic: `5` (Gate 3: Operación diaria de movimientos)
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`
Backlog (fuente de verdad): `_bmad-output/implementation-artifacts/epics.md` (Epic 5, Story 5.5; FR22)

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/epics.md` (Epic 5 / Story 5.5; FR22)
- `_bmad-output/implementation-artifacts/prd.md` (FR22; NFR7)
- `_bmad-output/implementation-artifacts/ux.md` (patrones de tablas, filtros, UX de auditoría)
- `_bmad-output/implementation-artifacts/architecture.md` (patrones: Actions, Livewire, Tests; Epic 5 mapping)
- `docsBmad/project-context.md` (bible: inventario dual + trazabilidad; reglas no negociables)
- `project-context.md` (stack; RBAC; testing; rutas en inglés/UI en español)
- `docsBmad/rbac.md` + `gatic/app/Providers/AuthServiceProvider.php` (gates `inventory.view`/`inventory.manage`)
- Story previa (contexto): `_bmad-output/implementation-artifacts/5-4-movimientos-por-cantidad-vinculados-a-producto-y-empleado.md`
- Código existente:
  - `gatic/app/Models/ProductQuantityMovement.php`
  - `gatic/app/Actions/Movements/Products/RegisterProductQuantityMovement.php`
  - `gatic/app/Models/InventoryAdjustment.php`
  - `gatic/app/Models/InventoryAdjustmentEntry.php`
  - `gatic/app/Actions/Inventory/Adjustments/ApplyProductQuantityAdjustment.php`
  - `gatic/routes/web.php` (rutas de inventario y movimientos)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a usuario interno (Admin/Editor/Lector),
I want consultar el kardex de movimientos por cantidad,
so that pueda auditar entradas/salidas/ajustes de stock (FR22).

## Alcance

Incluye:
- Solo aplica a **Productos por cantidad** (Categoría con `is_serialized=false`).
- Consultar historial/kardex del **Producto** (desde su detalle) con lista **cronológica** (más reciente primero por defecto).
- Incluir, en una sola vista (MVP), al menos:
  - Movimientos por cantidad (`product_quantity_movements`) de tipo **Salida**/**Entrada**
  - Ajustes administrativos de inventario (`inventory_adjustment_entries` para `Product::class`) como tipo **Ajuste**
- Campos visibles mínimos por fila (FR22):
  - Tipo (Salida/Entrada/Ajuste)
  - Cantidad (con signo o etiqueta clara)
  - Fecha/hora
  - Usuario actor (usuario del sistema)
  - Empleado receptor (si aplica; en movimientos por cantidad)
- UX adoption-first: tabla responsive, paginación y estado vacío útil.
- Defensa en profundidad: ruta protegida con `can:inventory.view` y `Gate::authorize('inventory.view')` en el componente Livewire.

No incluye (fuera de scope):
- Costos, valuación, "kardex contable".
- Multi-almacén/ubicaciones por stock (solo stock agregado por producto).
- Exportación (PDF/Excel) y filtros avanzados (podrían ir en story futura).

## Acceptance Criteria

### AC1 - Kardex cronológico (FR22)

**Given** un Producto por cantidad con movimientos registrados
**When** el usuario consulta su historial/kardex
**Then** ve una lista cronológica de movimientos
**And** cada movimiento muestra tipo, cantidad, fecha, usuario actor y Empleado receptor (si aplica)

### AC2 - Ajustes incluidos como "Ajuste" (FR22)

**Given** un Producto por cantidad con al menos un ajuste administrativo (`inventory_adjustments`)
**When** el usuario consulta el kardex del producto
**Then** el kardex incluye también los ajustes como entradas "Ajuste"
**And** cada ajuste muestra actor, fecha y el cambio de stock (delta o before→after)

### AC3 - Acceso por rol (RBAC)

**Given** un usuario Admin, Editor o Lector
**When** abre el kardex del producto
**Then** el servidor permite la operación

**Given** un usuario sin `inventory.view`
**When** intenta acceder por URL directa
**Then** el servidor bloquea (403 o equivalente)

## Tasks / Subtasks

1) UI (AC: 1, 2, 3)
- [x] Agregar un punto de entrada desde el detalle del producto por cantidad:
  - opción A (recomendada): botón "Ver kardex" que navega a ruta dedicada
  - opción B (MVP): sección "Kardex" embebida en el mismo `ProductShow` (tabla + paginación)
- [x] Implementar componente Livewire para mostrar el kardex con paginación:
  - Tabla con columnas: `Fecha`, `Tipo`, `Cantidad`, `Actor`, `Empleado`, `Nota/Motivo`
  - Estado vacío con mensaje accionable (p.ej. "Sin movimientos aún")

2) Backend/query (AC: 1, 2)
- [x] Construir fuente de datos del kardex:
  - Movimientos por cantidad: `ProductQuantityMovement::query()->where('product_id', $productId)`
  - Ajustes: `InventoryAdjustmentEntry::query()->where('subject_type', Product::class)->where('subject_id', $productId)`
- [x] Normalizar filas a un formato común (DTO/array) y ordenar por `created_at` desc
- [x] Eager load para evitar N+1:
  - movimientos: `actorUser`, `employee`
  - ajustes: `adjustment.actor`

3) Seguridad y reglas (AC: 3)
- [x] Rutas bajo `can:inventory.view` y validación server-side en Livewire (`Gate::authorize('inventory.view')`)
- [x] Respetar "Empleado (RPE) != Usuario" en render (mostrar ambos cuando aplique)

4) Tests (AC: 1, 2, 3)
- [x] Feature tests para:
  - Admin/Editor/Lector pueden ver kardex
  - Usuario sin permiso no puede (403)
  - Kardex incluye movimientos y ajustes y está ordenado cronológicamente

## Dev Notes

### Contexto existente (por qué esto es fácil de romper)

- El stock por cantidad ya se modifica en dos vías distintas:
  - Movimientos operativos: `gatic/app/Actions/Movements/Products/RegisterProductQuantityMovement.php` → crea `product_quantity_movements`
  - Ajustes administrativos: `gatic/app/Actions/Inventory/Adjustments/ApplyProductQuantityAdjustment.php` → crea `inventory_adjustments` + `inventory_adjustment_entries`
- Si el kardex solo muestra `product_quantity_movements`, el usuario no podrá auditar cambios relevantes (FR22 menciona "ajustes").

### Requisitos técnicos (guardrails)

- Identificadores de código/rutas/DB en inglés; copy/UI en español (`project-context.md`).
- Autorización obligatoria server-side:
  - Kardex: `inventory.view` (debe incluir Lector).
  - Registrar movimientos: `inventory.manage` (NO incluye Lector; ya existe).
- Rendimiento: evitar N+1 con `->with(...)` y limitar/paginar (tabla puede crecer).
- Orden/cronología: usar `created_at` como fuente de verdad; definir claramente "más reciente primero".
- Datos: en ajustes, calcular delta con `after.qty_total - before.qty_total` (si existe).
- Errores: en caso de excepción, reportar con `ErrorReporter` y toast con `error_id` (patrón existente en Livewire).

### Cumplimiento de arquitectura (patrones del repo)

- Livewire route → componente (sin controller salvo bordes).
- Separación por módulos consistente con lo existente:
  - movimientos por cantidad viven en `App\\Livewire\\Movements\\Products\\*`
  - vistas en `resources/views/livewire/...`
- Mantener transacciones/locks donde aplique: esta story es read-only, pero debe respetar consistencia (no inventar "stock calculado").

### Librerías y versiones (para evitar deriva)

- Laravel: `laravel/framework` instalado `v11.47.0` (`gatic/composer.lock`).
- Livewire: `livewire/livewire` instalado `v3.7.3` (`gatic/composer.lock`).
- UI: Bootstrap 5 (no Tailwind) (`project-context.md`).

### Requisitos de estructura de archivos (sugerencia concreta)

- Componente Livewire (nuevo):
  - `gatic/app/Livewire/Inventory/Products/ProductKardex.php` (o `gatic/app/Livewire/Movements/Products/ProductKardex.php` si se prefiere agrupar por "Movements").
- Vista:
  - `gatic/resources/views/livewire/inventory/products/product-kardex.blade.php` (o path equivalente al namespace elegido).
- Ruta (nueva) bajo `can:inventory.view`:
  - `GET /inventory/products/{product}/kardex` → `inventory.products.kardex`
- Entrada desde detalle:
  - `gatic/resources/views/livewire/inventory/products/product-show.blade.php` (agregar botón/links solo para productos por cantidad)

### Requisitos de testing

- Tests nuevos en `gatic/tests/Feature/Inventory/*` o `gatic/tests/Feature/Movements/*` (consistencia con el módulo elegido).
- Cubrir:
  - RBAC (Admin/Editor/Lector) para ver kardex
  - Integración de dos fuentes (movimientos + ajustes)
  - Orden cronológico determinista (usar timestamps controlados)

### Inteligencia de story previa (5.4) — no repetir errores

- Ya existe tabla `product_quantity_movements` con FKs `restrictOnDelete()` (no cambiar ni degradar trazabilidad).
- Ya existe helper de lock para productos por cantidad (`LockQuantityProduct`) y patrón de validación en Actions; para kardex, solo lectura (no duplicar lógica de stock).

### Git intelligence (contexto mínimo)

- Implementación base de movimientos por cantidad está en commit `78a1432` (Story 5.4).

### Latest tech information (mínimo viable)

- No introducir librerías nuevas para tablas/paginación; usar herramientas nativas de Laravel/Livewire.
- Mantener compatibilidad con Livewire 3 (paginación Livewire, layouts `#[Layout('layouts.app')]`).

## Project Context Reference

Reglas críticas (fuente de verdad):
- `docsBmad/project-context.md` (inventario dual; trazabilidad; UX adoption-first).
- `project-context.md` (stack, RBAC, testing, rutas en inglés/UI en español).
- `docsBmad/rbac.md` + `gatic/app/Providers/AuthServiceProvider.php` (gates).
- `_bmad-output/implementation-artifacts/5-4-movimientos-por-cantidad-vinculados-a-producto-y-empleado.md` (patrones y decisiones ya tomadas).

## Story Completion Status

- Status: `done`
- Nota: "Code review completado. Kardex combina movimientos + ajustes, orden cronológico determinista y paginación sin romper el retorno al listado. Tests y Pint verificados."
- Fecha: 2026-01-17

## Dev Agent Record

### Agent Model Used

Claude Opus 4.5

### Debug Log References

- Story auto-descubierta desde `_bmad-output/implementation-artifacts/sprint-status.yaml`
- Contexto de código leído desde `gatic/` (modelos/acciones/rutas/tests de movimientos y ajustes)
- Implementación siguió ciclo RED-GREEN-REFACTOR

### Implementation Plan

1. Escribir tests primero (RED phase) - ProductKardexTest.php
2. Implementar componente Livewire ProductKardex.php (GREEN phase)
3. Crear vista Blade product-kardex.blade.php
4. Agregar ruta en web.php bajo `can:inventory.view`
5. Agregar botón "Ver kardex" en ProductShow para productos por cantidad
6. Correr Pint y PHPStan para validar calidad

### Completion Notes List

- FR22 cubierto explícitamente incluyendo ajustes administrativos como parte del kardex.
- RBAC alineado a `inventory.view` (incluye Lector) para consulta; `inventory.manage` queda solo para registrar.
- Implementación usa opción A: botón "Ver kardex" que navega a ruta dedicada `/inventory/products/{product}/kardex`
- Kardex combina dos fuentes de datos: `ProductQuantityMovement` y `InventoryAdjustmentEntry`
- Orden cronológico: más reciente primero (sortByDesc('date'))
- Paginación manual de colección combinada (15 items por página)
- Eager loading implementado para evitar N+1: `actorUser`, `employee`, `adjustment.actor`
- Solo disponible para productos por cantidad (is_serialized=false), devuelve 404 para serializados
- UI Bootstrap 5 con badges de colores para tipos (Salida=danger, Entrada=success, Ajuste=warning)
- Estado vacío con mensaje "Sin movimientos aún"
- Pint: ✅ PASS
- Tests: ✅ PASS (review) - `php artisan test`
- PHPStan: ✅ No errors

### File List

**Nuevos:**
- `gatic/app/Livewire/Inventory/Products/ProductKardex.php`
- `gatic/resources/views/livewire/inventory/products/product-kardex.blade.php`
- `gatic/tests/Feature/Inventory/ProductKardexTest.php`

**Modificados:**
- `gatic/routes/web.php` (agregada ruta `inventory.products.kardex`)
- `gatic/resources/views/livewire/inventory/products/product-show.blade.php` (agregado botón "Ver kardex")
- `gatic/app/Actions/Inventory/Products/LockQuantityProduct.php` (fix: single_blank_line_at_eof por Pint)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (sync status Story 5.5)
- `_bmad-output/implementation-artifacts/5-5-kardex-historial-para-productos-por-cantidad.md` (review notes)

## Senior Developer Review (AI)

### Resumen

- Resultado: **Aprobado con correcciones (aplicadas)**
- ACs verificados: AC1, AC2, AC3 (vía tests + revisión de implementación)
- Tests: `php artisan test` PASS (267 tests, 674 assertions)
- Pint: `./vendor/bin/pint --test` PASS

### Hallazgos (corregidos)

- [HIGH] **Merge del kardex explotaba en runtime**: `merge()` estaba corriendo sobre `Eloquent\Collection` con items normalizados como arrays y terminaba llamando `getKey()` (fatal) cuando había ajustes/movimientos. Fix: convertir a `Collection` base antes del merge. Evidencia: `gatic/app/Livewire/Inventory/Products/ProductKardex.php:83`, `gatic/app/Livewire/Inventory/Products/ProductKardex.php:92`.
- [HIGH] **Orden cronológico y tests no deterministas**: el orden dependía de comparar objetos `Carbon` y los tests intentaban setear `created_at` por mass assignment (se ignoraba). Fix: sort por timestamp y tests con `travelTo()`. Evidencia: `gatic/app/Livewire/Inventory/Products/ProductKardex.php:97`, `gatic/tests/Feature/Inventory/ProductKardexTest.php:100`, `gatic/tests/Feature/Inventory/ProductKardexTest.php:197`.
- [MEDIUM] **Retorno al listado perdía contexto**: el botón "Ver kardex" no propagaba `q/page`; además, el paginador usaba `page` y podía pisar el `page` del listado. Fix: pasar `$returnQuery` y usar `kardex_page`. Evidencia: `gatic/resources/views/livewire/inventory/products/product-show.blade.php:25`, `gatic/app/Livewire/Inventory/Products/ProductKardex.php:20`.
- [MEDIUM] **Manejo de errores en prod**: si truena la carga del kardex en producción, ahora se reporta con `ErrorReporter` y se muestra toast; en local/testing se deja explotar para debug. Evidencia: `gatic/app/Livewire/Inventory/Products/ProductKardex.php:47`.
- [LOW] **Hardening de relaciones**: null-safe en actor para evitar fatal si hay datos inconsistentes. Evidencia: `gatic/app/Livewire/Inventory/Products/ProductKardex.php:121`, `gatic/app/Livewire/Inventory/Products/ProductKardex.php:145`.

## Change Log

| Fecha | Cambio |
|-------|--------|
| 2026-01-16 | Implementación completa de Story 5.5 - Kardex para productos por cantidad |
| 2026-01-17 | Senior Developer Review (AI) - fixes aplicados (merge seguro, orden determinista, retorno con querystring, tests estabilizados) y story pasa a `done` |
