# Story 3.4: Detalle de Producto con conteos y desglose por estado

Status: done
<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

Story Key: `3-4-detalle-de-producto-con-conteos-y-desglose-por-estado`  
Epic: `3` (Gate 2: Inventario navegable)

Fuentes:
- `_bmad-output/implementation-artifacts/epics.md` (Story 3.4; FR12)
- `_bmad-output/prd.md` (FR12)
- `_bmad-output/architecture.md` (stack + constraints + UX/polling)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (orden + notas del Epic 3)
- `docsBmad/project-context.md` (bible: semántica QTY + estados)
- `project-context.md` (reglas críticas para agentes)
- `_bmad-output/implementation-artifacts/3-3-listado-de-inventario-productos-con-indicadores-de-disponibilidad.md` (patrones + learnings)

## Story

As a usuario interno (Soporte TI),
I want ver el detalle de un Producto con conteos de disponibilidad y desglose por estado,
so that pueda entender qué unidades están disponibles y por qué (FR12).

## Acceptance Criteria

### AC1 - Acceso por rol (defensa en profundidad)

**Given** un usuario autenticado (Admin/Editor/Lector)  
**When** navega al detalle de un Producto (`/inventory/products/{product}`)  
**Then** puede ver la pantalla

**And** el servidor autoriza el acceso con `Gate::authorize('inventory.view')`.

### AC2 - Conteos de disponibilidad (Total / Disponibles / No disponibles)

**Given** un Producto existente  
**When** el usuario abre el detalle  
**Then** el sistema muestra conteos consistentes con la semántica del dominio:
- `Total`
- `Disponibles`
- `No disponibles`

### AC3 - Desglose por estado para Productos serializados (Activos)

**Given** un Producto cuya Categoría es serializada (`categories.is_serialized=true`)  
**When** el sistema calcula los conteos y el desglose  
**Then** usa Activos (`assets`) NO eliminados (soft-delete) y aplica:
- `Total` = count(Activos) excluyendo `status = Retirado`
- `No disponibles` = count(Activos) con `status ∈ {Asignado, Prestado, Pendiente de Retiro}` (excluyendo `Retirado`)
- `Disponibles` = `Total - No disponibles` (equivalente a count(`Disponible`) bajo el set anterior)

**And** el desglose por estado muestra (al menos) los estados canónicos:
`Disponible`, `Asignado`, `Prestado`, `Pendiente de Retiro`, `Retirado`

**And** `Retirado` se muestra como informativo y NO cuenta en inventario baseline por defecto.

**And** el desglose se basa en el estado actual de los Activos (sin depender de Movimientos en esta épica).

### AC4 - Resumen de stock para Productos por cantidad

**Given** un Producto por cantidad (`categories.is_serialized=false`)  
**When** el usuario abre el detalle  
**Then** ve un resumen de stock actual basado en `products.qty_total` (Epic 3 no tiene movimientos por cantidad aún):
- `Total` = `qty_total`
- `No disponibles` = `0`
- `Disponibles` = `qty_total`

### AC5 - UX baseline

**Given** el detalle de Producto  
**When** el usuario navega desde el listado  
**Then** la pantalla ofrece navegación clara (volver al listado)

**And** si el Producto es serializado, ofrece acceso al listado de Activos (CTA “Activos”).

### AC6 - Rendimiento (anti N+1)

**Given** el detalle de Producto  
**When** se calculan conteos y desglose  
**Then** el sistema evita N+1 queries

**And** obtiene los conteos/desglose mediante aggregates (subqueries o `groupBy`) sin iterar activos en PHP.

## Tasks / Subtasks

1) Routing + pantalla (AC: 1, 5)
- [x] Agregar ruta `GET /inventory/products/{product}` hacia un componente Livewire `Inventory/Products/ProductShow`.
- [x] Asegurar middleware y autorización server-side (`Gate::authorize('inventory.view')`).

2) Query / agregados (AC: 2-4, 6)
- [x] Cargar `Product` con `category` y `brand`.
- [x] Si es serializado: calcular `total`, `unavailable`, `available` siguiendo `Asset::UNAVAILABLE_STATUSES` y exclusión de `Retirado`.
- [x] Si es serializado: obtener desglose `count(*)` por `assets.status` (incluyendo `Retirado` como informativo).
- [x] Si es por cantidad: usar `qty_total` como baseline (sin “no disponibles”).

3) UI (Blade + Bootstrap) (AC: 2-5)
- [x] Renderizar tarjetas/badges con `Total/Disponibles/No disponibles`.
- [x] Para serializados: tabla/stack del desglose por estado (incluye “Retirado” separado y explicado).
- [x] Mantener copy en español; identificadores/código/rutas en inglés.

4) Tests (AC: 1-6)
- [x] Agregar tests de acceso por rol (Admin/Editor/Lector permitido; usuario no autorizado => 403).
- [x] Agregar tests de cálculo: serializado con mezcla de estados (incluye `Retirado` y soft-delete) y por cantidad con `qty_total`.

## Dev Notes

### DEV AGENT GUARDRAILS (no negociables)

- No depender de Movimientos (Epic 5) ni de Empleados/RPE (Epic 4/5). Este detalle es **solo lectura** basada en estado actual.
- Respetar semántica QTY: `No disponibles = Asignado + Prestado + Pendiente de Retiro`; `Retirado` no cuenta en inventario baseline.
- Autorización server-side siempre: `Gate::authorize('inventory.view')` (la UI no sustituye permisos).
- UI/copy en español; identificadores de código/DB/rutas en inglés.
- Mantener patrones existentes (Livewire 3 + Bootstrap 5); evitar controllers salvo “bordes”.

### UX/IA de interfaz (baseline)

- Navegación: botón “Volver” a `/inventory/products`.
- CTA contextual:
  - Serializado: botón “Activos” → `/inventory/products/{product}/assets`.
  - Por cantidad: no mostrar CTA de “Activos”.
- Conteos deben ser legibles (cards o badges) y consistentes con el listado de Productos (Story 3.3).
- Considerar `wire:poll.visible` (~15s) solo si aporta valor (en Epic 3 puede cambiar por edición manual de Activos).

### Edge cases importantes

- Producto soft-deleted: definir comportamiento (404 o mostrar solo si existe y autorizado). Mantenerlo simple: `findOrFail()` por defecto.
- Categoría/Marca faltante (relación null): mostrar `-` sin romper UI.
- `qty_total` null: tratar como `0`.

### Project Structure Notes

- Ruta UI (Livewire-first): `routes/web.php` → `App\\Livewire\\Inventory\\Products\\ProductShow`.
- Vista: `resources/views/livewire/inventory/products/product-show.blade.php`.
- Reusar convenciones existentes en Inventory:
  - Autorización en `mount()` y `render()`
  - Búsquedas con `escapeLike()` cuando aplique
  - Bootstrap “card + table-responsive” como en `ProductsIndex` y `AssetsIndex`.

### References

- Backlog base (FR12): `_bmad-output/implementation-artifacts/epics.md` (Story 3.4)
- Reglas de dominio: `docsBmad/project-context.md` (Semántica QTY + estados)
- Arquitectura/patrones: `_bmad-output/architecture.md` (Livewire-first + Gates + estructura)
- Patrones ya implementados: `_bmad-output/implementation-artifacts/3-3-listado-de-inventario-productos-con-indicadores-de-disponibilidad.md`

## Technical Requirements

- Ruta UI: `GET /inventory/products/{product}` (Livewire component).
- Cargar `Product` con `category` y `brand` (y validar `product` param numérico como patrón actual).
- Serializado:
  - `total` = `count(*)` donde `assets.product_id = products.id` y `assets.status != Retirado`
  - `unavailable` = `count(*)` donde `assets.status ∈ Asset::UNAVAILABLE_STATUSES`
  - `available` = `max(total - unavailable, 0)`
  - Desglose: `groupBy(assets.status)` con conteos por estado (incluir `Retirado` como informativo).
- Por cantidad:
  - `total = qty_total`, `unavailable = 0`, `available = qty_total` (tratando null como 0).
- Evitar iterar activos en PHP; solo aggregates (pocas filas).

## Architecture Compliance

- Stack y estilo: Laravel 11 + Livewire 3 + Blade + Bootstrap 5 (MPA; no SPA).
- Autorización: `Gate::authorize('inventory.view')` (en `mount()` y/o `render()`).
- Sin WebSockets: si se requiere “frescura”, usar `wire:poll.visible` (≈15s) como patrón estándar.
- Sin dependencias nuevas ni integraciones externas para esta historia.
- Mantener reglas de dominio centralizadas (reusar `Asset::UNAVAILABLE_STATUSES` y constantes de estado).

## Library / Framework Requirements

- Laravel: mantener `laravel/framework:^11.x` (repo actual).
- Livewire: mantenerse en `livewire/livewire:^3.x` (repo actual); referencia de “latest” (3.6): `https://laravel-news.com/livewire-36-released`.
- Eloquent aggregates: usar `withCount`/subqueries/groupBy según convenga (relaciones): `https://laravel.com/docs/11.x/eloquent-relationships`.
- Bootstrap: mantener Bootstrap 5 (sin Tailwind como base de UI).

## File Structure Requirements

- Nuevo:
  - `gatic/app/Livewire/Inventory/Products/ProductShow.php`
  - `gatic/resources/views/livewire/inventory/products/product-show.blade.php`
- Modificar:
  - `gatic/routes/web.php` (agregar ruta `inventory.products.show`)
  - `gatic/resources/views/livewire/inventory/products/products-index.blade.php` (opcional: link desde listado a detalle)
  - `gatic/tests/Feature/Inventory/ProductsTest.php` (o nuevo `ProductShowTest.php`) para cubrir acceso + cálculos

## Testing Requirements

- Feature tests (mínimo):
  - RBAC: Admin/Editor/Lector pueden ver detalle; usuario sin permiso => 403.
  - Serializado: mezcla de Activos por estado + soft-delete + `Retirado` (verifica total/available/unavailable y desglose).
  - Por cantidad: `qty_total` (incluye null → 0).
- Comando recomendado (Sail, desde `gatic/`):
  - `vendor\\bin\\sail artisan test --filter ProductsTest`

## Previous Story Intelligence

- Story previa (3.3) ya implementó semántica QTY y patrones anti N+1 para conteos de Activos.
- Reusar:
  - `Asset::UNAVAILABLE_STATUSES` y `Asset::STATUS_RETIRED` para cálculos consistentes.
  - Patrón de UI en Bootstrap (tabla densa + badges) y accesibilidad (“Sin disponibles” no solo color).
- Evitar regresiones: en 3.3 se corrigió que conteos no se calculen para productos “Por cantidad”.

## Git Intelligence Summary

- Commits recientes relevantes:
  - `d2d4fec` / `d9e754c` (indicadores de disponibilidad 3.3) tocaron: `ProductsIndex`, modelos `Product/Asset`, view de listado y tests.
- Mantener el estilo ya establecido:
  - Queries con subqueries/aggregates y joins explícitos cuando se necesite guardrail por categoría.
  - Tests menos frágiles (preferir asserts de texto/orden a regex de HTML).

## Latest Tech Information

- Livewire 3.x continúa activo; referencia de release 3.6 (features JS/actions): `https://laravel-news.com/livewire-36-released`.
- Documentación oficial para relaciones/aggregates: `https://laravel.com/docs/11.x/eloquent-relationships`.

## Project Context Reference

- Fuente de verdad: `docsBmad/project-context.md` (gana ante cualquier contradicción).
- Reglas críticas:
  - Inventario dual (serializado vs cantidad) y semántica QTY.
  - `Retirado` no cuenta en baseline por defecto.
  - Sin WebSockets; polling Livewire cuando aplique.
  - Autorización server-side obligatoria.

## Story Completion Status

- Status: `review`
- Nota de completitud: "Ultimate context engine analysis completed - comprehensive developer guide created"

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

### Implementation Plan

- Implementar ProductShow como componente Livewire con Gate::authorize('inventory.view') en mount() y render().
- Validar product como parámetro numérico (404 si no).
- Cargar Product con category y brand.
- Calcular conteos con aggregates:
  - Serializado: conteos en assets (excluye Retirado para Total; UNAVAILABLE_STATUSES para No disponibles) + desglose groupBy(status) incluyendo Retirado.
  - Por cantidad: usar qty_total (null -> 0).
- UI (Bootstrap): tarjetas para conteos + tabla de desglose; CTA "Activos" si serializado; link "Volver" al listado; link desde el listado al detalle.
- Tests (Feature): RBAC + 403 cuando se deniega inventory.view + cálculos (incluye soft-delete y Retirado).


### Completion Notes List

- Se definió pantalla de detalle de Producto con conteos (Total/Disponibles/No disponibles) y desglose por estado (serializados).
- Se reforzó que el cálculo es por estado actual (sin Movimientos) y que `Retirado` no cuenta en baseline.
- Se listaron cambios esperados (Livewire component + route + view) y cobertura de tests (RBAC + cálculos).

- Implementado inventory.products.show con restricción de parámetro numérico para evitar colisión con /create.
- Agregados conteos (Total/Disponibles/No disponibles) y desglose por estado (incluye Retirado) en el detalle.
- Actualizado el listado de Productos para enlazar al detalle (nombre enlazado + botón "Ver").
- Tests y calidad ejecutados: php artisan test, pint --test, phpstan analyse.

## Senior Developer Review (AI)

Fecha: 2026-01-03

Resultado: **Aprobado (done)** tras corregir issues detectados en el review.

Fixes aplicados:
- Tests: ajuste de `Gate::define('inventory.view')` para aceptar `User $user` (reduce fragilidad/warnings).
- Performance/consistencia: `ProductShow` calcula `total/unavailable/available` desde un solo aggregate `groupBy(status)` (evita discrepancias entre queries).
- UX/copy: se aclara que `Total` excluye `Retirado` (baseline) cuando el Producto es serializado.
- Routing: se agregan constraints numéricos (`whereNumber`) en rutas de inventario para consistencia.

### File List

- `_bmad-output/implementation-artifacts/3-4-detalle-de-producto-con-conteos-y-desglose-por-estado.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `gatic/app/Livewire/Inventory/Products/ProductShow.php`
- `gatic/resources/views/livewire/inventory/products/product-show.blade.php`
- `gatic/resources/views/livewire/inventory/products/products-index.blade.php`
- `gatic/routes/web.php`
- `gatic/tests/Feature/Inventory/ProductsTest.php`

### Change Log

- 2026-01-02: Agregado detalle de Producto (ruta + componente Livewire) con conteos y desglose por estado, y tests asociados.
- 2026-01-03: Code review adversarial (Senior Dev) - fixes aplicados (tests, aggregates, UX copy, route constraints).
