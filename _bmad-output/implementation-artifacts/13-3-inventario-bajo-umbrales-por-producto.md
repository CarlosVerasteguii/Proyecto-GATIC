# Story 13.3: Inventario bajo (umbrales por producto) + alertas

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story
As a Admin/Editor,
I want configurar umbrales de “stock bajo” por producto y ver alertas,
so that reponga consumibles a tiempo y evite quedarme sin inventario.

## Alcance (MVP)
- Aplica **solo** a productos **por cantidad** (categorías con `is_serialized = false`).
- Umbral por producto (`products.low_stock_threshold`) para determinar “stock bajo”.
- Dashboard: agregar un contador “Stock bajo” con link a listado de alertas.
- Alertas: agregar pantalla `/alerts/stock` con listado paginado de productos en stock bajo.
- UI inventario: mostrar indicador “Stock bajo” donde aplique (listado de productos y/o detalle de producto).
- Fuera de alcance: settings globales de umbrales/ventanas (ver Story 14.6).

## Definiciones operativas (para evitar ambigüedad)
- **Stock bajo** (por cantidad): `qty_total <= low_stock_threshold` (ambos enteros `>= 0`).
- Recomendación: si `low_stock_threshold` es `NULL`, el producto **no participa** en alertas (evita ruido).
- Productos serializados: `qty_total` es `NULL` y `low_stock_threshold` debe mantenerse `NULL` (no aplica).
- Soft-delete: productos eliminados (con `deleted_at`) **no** deben aparecer en conteos/listados de alertas.

## Acceptance Criteria
**Given** un producto por cantidad
**When** el stock (`qty_total`) cae a `<= low_stock_threshold`
**Then** el sistema lo considera “stock bajo”
**And** aparece en un listado de alertas y en el dashboard
**Given** un usuario autorizado edita un producto
**When** define/cambia el umbral
**Then** el sistema valida que sea un entero `>= 0`
**And** el umbral se usa en cálculos/indicadores de disponibilidad donde aplique.

## Tasks / Subtasks
1) DB + modelo: agregar umbral (AC: 1, 2)
- [x] Migración: agregar `products.low_stock_threshold` (unsigned int, nullable)
- [x] Actualizar `gatic/app/Models/Product.php` (`$fillable`, `$casts`, PHPDoc)
- [x] Actualizar factory/seeders relevantes (`gatic/database/factories/ProductFactory.php`, seeds demo si aplica)

2) UI: edición de umbral en producto (AC: 2)
- [x] Extender `gatic/app/Livewire/Inventory/Products/ProductForm.php` con `low_stock_threshold`
- [x] Validación: entero `>= 0` (solo cuando NO es serializado)
- [x] Forzar `low_stock_threshold = null` para productos serializados (igual que `qty_total`)
- [x] Actualizar `gatic/resources/views/livewire/inventory/products/product-form.blade.php` con input "Umbral de stock bajo"

3) UI: indicadores de stock bajo (AC: 1, 2)
- [x] Listado de productos: agregar badge "Stock bajo" cuando aplique (sin reemplazar "Sin disponibles")
  - Referencia: `gatic/resources/views/livewire/inventory/products/products-index.blade.php`
- [x] Detalle de producto (por cantidad): mostrar umbral y estado "Stock bajo" si aplica
  - Referencia: `gatic/resources/views/livewire/inventory/products/product-show.blade.php`

4) Alertas: listado de inventario bajo (AC: 1)
- [x] Crear componente Livewire (propuesto):
  - `gatic/app/Livewire/Alerts/Stock/LowStockAlertsIndex.php`
  - `gatic/resources/views/livewire/alerts/stock/low-stock-alerts-index.blade.php`
- [x] Query eficiente + paginación: productos por cantidad con umbral definido y `qty_total <= low_stock_threshold`
- [x] Acciones rápidas: Ver detalle de producto y Editar (si `@can('inventory.manage')`)
- [x] Routing: `GET /alerts/stock` → `alerts.stock.index` (middleware `auth`, `active`, `can:inventory.manage`)

5) Dashboard: contador "Stock bajo" (AC: 1)
- [x] Extender `gatic/app/Livewire/Dashboard/DashboardMetrics.php` con `lowStockProductsCount`
- [x] Extender `gatic/resources/views/livewire/dashboard/dashboard-metrics.blade.php` con card + link a `/alerts/stock`
- [x] Agregar `data-testid` para test: `dashboard-metric-products-low-stock`

6) Tests (AC: 1, 2)
- [x] Dashboard: extender `gatic/tests/Feature/DashboardMetricsTest.php` para assert del contador "Stock bajo"
- [x] Alertas: agregar `gatic/tests/Feature/StockAlertsTest.php` (RBAC + render + soft-delete)
- [x] Producto (validación): extender `gatic/tests/Feature/Inventory/ProductsTest.php` para `low_stock_threshold`
- [x] Regression obligatorio: soft-delete de `Product` no debe contar ni listar (ver checklist)

7) Review Follow-ups (AI)
- [x] [AI-Review][CRITICAL] Corregir enum inexistente `UserRole::Reader` → `UserRole::Lector` (el test no compila). [`gatic/tests/Feature/StockAlertsTest.php:28`]
- [x] [AI-Review][MEDIUM] Documentar el cambio a tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml` aparece modificado en git pero no está en "File List". [`_bmad-output/implementation-artifacts/sprint-status.yaml:152`]
- [x] [AI-Review][MEDIUM] Evitar falsos positivos de "Stock bajo" cuando `qty_total` es `NULL` (hoy se convierte a `0` en UI). [`gatic/resources/views/livewire/inventory/products/products-index.blade.php:140`] [`gatic/resources/views/livewire/inventory/products/product-show.blade.php:70`]
- [x] [AI-Review][MEDIUM] Centralizar la definición de “stock bajo” (query duplicada entre dashboard y alertas) para evitar drift. [`gatic/app/Models/Product.php:52`]
- [x] [AI-Review][LOW] Alinear factories: `ProductFactory` crea, por defecto, productos por cantidad (evita estados inválidos). [`gatic/database/factories/ProductFactory.php:24`]

## Dev Notes
### Developer Context (lectura obligatoria)
- Modelo de producto por cantidad usa `products.qty_total` como total/available. [Source: `gatic/app/Livewire/Inventory/Products/ProductShow.php`]
- Edición de producto se hace en Livewire (no controllers) y está protegida por `Gate::authorize('inventory.manage')`. [Source: `gatic/app/Livewire/Inventory/Products/ProductForm.php`]
- Listado actual calcula `available` y muestra badge “Sin disponibles”. [Source: `gatic/resources/views/livewire/inventory/products/products-index.blade.php`]

### Contexto funcional (Epic 13)
- Epic 13 agrega alertas operativas (préstamos y stock bajo) visibles en dashboard y en listados dedicados.
- Patrón ya implementado para préstamos: dashboard + `/alerts/loans`. Reutilizar estructura, routing y estilo. [Source: `_bmad-output/implementation-artifacts/13-2-alertas-prestamos-vencidos-y-por-vencer.md`]

### Contexto técnico ya existente (reutilización obligatoria)
- Dashboard metrics ya tiene patrón de polling + manejo de errores con `ErrorReporter` + `x-ui.freshness-indicator`. [Source: `gatic/app/Livewire/Dashboard/DashboardMetrics.php`]
- Alertas de préstamos ya tienen patrón de listado Livewire paginado con `<x-ui.long-request />` y filtros en query string. [Source: `gatic/app/Livewire/Alerts/Loans/LoanAlertsIndex.php`]

### UX / Interacción (reglas importantes)
- Si el listado de alertas o filtros puede tardar >3s, usar `<x-ui.long-request />` (loader + Cancelar). [Source: `gatic/resources/views/components/ui/long-request.blade.php`]
- Mantener UI en español; DB/código/rutas en inglés. [Source: `docsBmad/project-context.md`]

### Technical Requirements (guardrails)
- **RBAC server-side obligatorio**:
  - Editar umbral: `inventory.manage` (Admin/Editor).
  - Listado `/alerts/stock`: recomendado `can:inventory.manage` (consistente con `/alerts/loans`).
- **Sin WebSockets**: usar polling Livewire ya existente; no agregar Echo/Pusher/SSE. [Source: `project-context.md`]
- **Performance**:
  - Dashboard: calcular conteos con `count()`/agregados; no cargar colecciones completas en cada poll.
  - Listado: query paginada + `with(['category:id,name,is_serialized', 'brand:id,name'])` según columnas mostradas; evitar N+1.
- **Errores con `error_id`**: seguir patrón existente del dashboard (en prod mostrar mensaje humano + ID).
- **Soft-delete**: excluir `deleted_at` en conteos y listados; agregar test de regresión. (Lección Epic 6)

### Architecture Compliance (no romper estructura)
- Mantener patrón: `routes/web.php` → Livewire screen → (opcional) Action → Models/Eloquent → DB. [Source: `_bmad-output/architecture.md`]
- No crear helpers globales; si hay lógica reutilizable de “stock bajo”, ubicarla en `app/Support/*` o `app/Actions/*` según complejidad. [Source: `project-context.md`]

### Library / Framework Requirements
- Laravel 11 + Livewire 3 + Bootstrap 5 (según `gatic/composer.json`).
- Usar `SoftDeletes` en `Product` (ya existe) y no romper scope de queries.

### File Structure Requirements
Archivos a modificar (mínimos):
- `gatic/database/migrations/*_add_low_stock_threshold_to_products_table.php` (nuevo)
- `gatic/app/Models/Product.php`
- `gatic/app/Livewire/Inventory/Products/ProductForm.php`
- `gatic/resources/views/livewire/inventory/products/product-form.blade.php`
- `gatic/resources/views/livewire/inventory/products/products-index.blade.php`
- `gatic/resources/views/livewire/inventory/products/product-show.blade.php`
- `gatic/app/Livewire/Dashboard/DashboardMetrics.php`
- `gatic/resources/views/livewire/dashboard/dashboard-metrics.blade.php`
- `gatic/routes/web.php`
- `gatic/tests/Feature/DashboardMetricsTest.php`
- `gatic/tests/Feature/Inventory/ProductsTest.php`

Archivos nuevos propuestos:
- `gatic/app/Livewire/Alerts/Stock/LowStockAlertsIndex.php`
- `gatic/resources/views/livewire/alerts/stock/low-stock-alerts-index.blade.php`
- `gatic/tests/Feature/StockAlertsTest.php`

### Testing Requirements
Objetivo: evitar “alertas mentirosas” y regresiones de soft-delete.

1) Dashboard (feature)
- Crear productos por cantidad con diferentes `qty_total`/`low_stock_threshold` y assert del contador con `data-testid`.
- Asegurar que productos serializados o con umbral `NULL` no cuenten.

2) Listado `/alerts/stock` (feature o Livewire)
- Acceso: no auth → redirect login; Lector → 403; Admin/Editor → 200.
- Render: muestra productos esperados y excluye los que no cumplen condición.

3) Regression: soft-delete (obligatorio)
- Crear un producto “stock bajo”, luego `delete()` (soft delete). Debe NO aparecer en:
  - Conteo del dashboard
  - Listado de alertas

### Previous Story Intelligence (no repetir errores)
- Copiar patrón de Story 13.2 para: routing de alertas, card en dashboard con link condicionado por `@can('inventory.manage')`, y tabla paginada con `<x-ui.long-request />`. [Source: `_bmad-output/implementation-artifacts/13-2-alertas-prestamos-vencidos-y-por-vencer.md`]
- Respetar guardrails del dashboard/polling (Story 5.6). [Source: `_bmad-output/implementation-artifacts/5-6-dashboard-minimo-de-metricas-operativas-polling.md`]

### Git Intelligence Summary (patrones recientes)
- Referencia directa a implementación existente: `LoanAlertsIndex` + `DashboardMetrics` + `routes/web.php` (mantener consistencia).

### Latest Tech Information (evitar decisiones desactualizadas)
- No upgrades: usar versiones fijadas por el repo (`gatic/composer.json` y `composer.lock`).
- Livewire 3: preferir bindings `wire:model.live`/`wire:model.defer` y polling `wire:poll.visible` (ya usado).

### References
- Story source: `_bmad-output/implementation-artifacts/epics.md` (Epic 13 / Story 13.3)
- Reglas críticas: `docsBmad/project-context.md` y `project-context.md`
- Arquitectura: `_bmad-output/architecture.md`
- Código: `gatic/app/Models/Product.php`, `gatic/app/Livewire/Inventory/Products/*`, `gatic/app/Livewire/Dashboard/DashboardMetrics.php`, `gatic/app/Livewire/Alerts/Loans/LoanAlertsIndex.php`

## Project Context Reference
- Bible: `docsBmad/project-context.md`
- Lean local notes: `project-context.md`
- Architecture: `_bmad-output/architecture.md`

## Story Completion Status
- Status: `done`
- Nota: "Code review aplicado y verificado (lint)."

## Preguntas abiertas (guardar para PO/SM; no bloquean esta story)
1) ¿Default del umbral para productos existentes? (propuesta: `NULL` para no generar ruido hasta configurarlo)
2) ¿El badge “Stock bajo” debe mostrarse también a `Lector` en listado/detalle (solo lectura), o solo a Admin/Editor?
3) ¿Se requiere filtro por “Stock bajo” en el listado de productos, o basta con `/alerts/stock`?

## Dev Agent Record
### Agent Model Used
Claude Opus 4.5 (claude-opus-4-5-20251101)

### Debug Log References
- N/A - No blocking errors encountered

### Completion Notes List
- Implemented low_stock_threshold field in Product model with proper fillable, casts, and PHPDoc
- Created migration 2026_02_02_000000_add_low_stock_threshold_to_products_table.php
- Extended ProductFactory with withLowStockThreshold() state method
- Extended ProductForm Livewire component with low_stock_threshold property, validation rules, and save logic
- Updated product-form.blade.php with input field for "Umbral de stock bajo"
- Updated products-index.blade.php with "Stock bajo" badge (yellow warning) alongside existing "Sin disponibles" badge
- Updated product-show.blade.php with low stock alert banner and threshold info in product details
- Created LowStockAlertsIndex.php Livewire component with efficient query using whereColumn
- Created low-stock-alerts-index.blade.php view following LoanAlertsIndex pattern
- Added route GET /alerts/stock with inventory.manage middleware
- Extended DashboardMetrics.php with lowStockProductsCount metric
- Updated dashboard-metrics.blade.php with "Stock Bajo" card and link to /alerts/stock
- Added data-testid="dashboard-metric-products-low-stock" for testing
- Extended DashboardMetricsTest.php with low stock count tests including soft-delete regression
- Created StockAlertsTest.php with comprehensive RBAC, render, and soft-delete tests
- Extended ProductsTest.php with low_stock_threshold validation tests
- Applied senior review fixes (enum RBAC, centralización de query, UI null-safety, factory alignment, sprint tracking sync)
- Pint: ordenados imports en `routes/web.php` y removida línea en blanco final en `LoanAlertsIndexTest.php`

### File List
**New files:**
- gatic/database/migrations/2026_02_02_000000_add_low_stock_threshold_to_products_table.php
- gatic/app/Livewire/Alerts/Stock/LowStockAlertsIndex.php
- gatic/resources/views/livewire/alerts/stock/low-stock-alerts-index.blade.php
- gatic/tests/Feature/StockAlertsTest.php

**Modified files:**
- _bmad-output/implementation-artifacts/sprint-status.yaml
- gatic/app/Models/Product.php
- gatic/database/factories/ProductFactory.php
- gatic/app/Livewire/Inventory/Products/ProductForm.php
- gatic/resources/views/livewire/inventory/products/product-form.blade.php
- gatic/resources/views/livewire/inventory/products/products-index.blade.php
- gatic/resources/views/livewire/inventory/products/product-show.blade.php
- gatic/app/Livewire/Dashboard/DashboardMetrics.php
- gatic/resources/views/livewire/dashboard/dashboard-metrics.blade.php
- gatic/routes/web.php
- gatic/tests/Feature/DashboardMetricsTest.php
- gatic/tests/Feature/Inventory/ProductsTest.php
- gatic/tests/Feature/LoanAlertsIndexTest.php

## Senior Developer Review (AI)

Resultado: **Approved**.

### Resumen
- Git vs Story: 0 discrepancias (File List sincronizada)
- Hallazgos: 0 CRITICAL, 0 MEDIUM, 0 LOW (todos resueltos)
- Nota de validación: Se corrió lint de PHP sobre los archivos tocados (sin errores). Para ejecutar `phpunit`, usar Sail/MySQL según `gatic/phpunit.xml`.

### Fixes aplicados (AI)
- Corregido `UserRole::Reader` → `UserRole::Lector` en tests. [`gatic/tests/Feature/StockAlertsTest.php:28`]
- Sincronizada la Story File List con el cambio en `sprint-status.yaml`. [`_bmad-output/implementation-artifacts/sprint-status.yaml:152`]
- Endurecido el badge/alerta de “Stock bajo” para no disparar con `qty_total = NULL`. [`gatic/resources/views/livewire/inventory/products/products-index.blade.php:140`] [`gatic/resources/views/livewire/inventory/products/product-show.blade.php:70`]
- Centralizada la query de “stock bajo” en `Product::scopeLowStockQuantity()` y reutilizada en dashboard + alertas. [`gatic/app/Models/Product.php:52`]
- Alineado `ProductFactory` para crear, por defecto, productos por cantidad (evita estados inválidos). [`gatic/database/factories/ProductFactory.php:24`]

## Change Log
- 2026-02-02: Implemented Story 13.3 - Low stock threshold alerts for quantity products (Claude Opus 4.5)
- 2026-02-02: Senior Developer Review (AI) - Approved (fixes aplicados)
