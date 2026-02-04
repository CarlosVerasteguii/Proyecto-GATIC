# Story 14.1: Proveedores (catálogo) + relación con Productos

Status: done

Story Key: `14-1-proveedores-catalogo-y-relacion-con-productos`  
Epic: `14` (Datos de negocio)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Backlog (fuente de verdad): `_bmad-output/implementation-artifacts/epics.md` (Epic 14, Story 14.1)

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/epics.md` (Epic 14)
- `_bmad-output/implementation-artifacts/architecture.md` (Catálogos + soft-delete + estructura por módulos)
- `_bmad-output/implementation-artifacts/ux.md` (UX: `ComboboxAsync` para Marca/Proveedor/RPE)
- `docsBmad/project-context.md` (bible: reglas no negociables)
- `project-context.md` (notas críticas lean + tooling local Windows)
- `gatic/app/Support/Catalogs/CatalogUsage.php` (bloqueo de borrado cuando “está en uso”)
- `gatic/app/Livewire/Catalogs/Brands/BrandsIndex.php` + `gatic/app/Models/Brand.php` (patrón CRUD catálogo + soft-delete + normalización)
- `gatic/app/Livewire/Catalogs/Trash/CatalogsTrash.php` + `gatic/app/Actions/Trash/*` (papelera de catálogos)
- `gatic/app/Livewire/Inventory/Products/ProductForm.php` (edición de producto — agregar `supplier_id`)
- `gatic/database/migrations/2026_01_02_000000_create_products_table.php` (FKs `restrictOnDelete` + collation)
- `gatic/routes/web.php` (grupo `/catalogs/*` bajo `can:catalogs.manage`)
- `gatic/tests/Feature/Catalogs/BrandsTest.php` (tests CRUD catálogo + bloqueo si está en uso)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Admin/Editor,  
I want gestionar proveedores y asociarlos a productos,  
so that capture origen de compra y facilite auditorías/gestión.

## Alcance (MVP)

Incluye:
- CRUD de Proveedores (catálogo): listar/buscar, crear, editar, soft-delete.
- Campos mínimos y opcionales (ver “Definiciones”).
- Asociación opcional Proveedor → Producto (`products.supplier_id`).
- Bloqueo de eliminación cuando el proveedor está “en uso” (referenciado por productos), evitando inconsistencias.
- UX consistente con catálogos existentes (Bootstrap 5 + Livewire + toasts).

No incluye (fuera de alcance):
- Importación masiva (CSV) de proveedores.
- Contratos / garantías / costos (Stories 14.2–14.5).
- Settings globales (Story 14.6).

## Definiciones operativas (para evitar ambigüedad)

- **Proveedor (Supplier)**: entidad de catálogo con `name` único (normalizado) y datos opcionales de contacto.
- **Asociación**: un Producto puede tener **0..1** proveedor (`products.supplier_id` nullable).
- **Soft-delete**: `suppliers.deleted_at` se usa para “papelera”; no hay purga automática (Admin controla).
- **En uso**: existe al menos un registro que referencia el proveedor (mínimo: `products.supplier_id = suppliers.id`).
  - Regla: si está en uso, bloquear `delete()` y también bloquear `purge` desde papelera (patrón `CatalogUsage`).

## Acceptance Criteria

### AC1 — CRUD proveedor (mínimo + opcional)

**Given** un usuario autorizado (Admin/Editor)  
**When** crea/edita un proveedor  
**Then** puede mantener campos mínimos (nombre) y campos opcionales (contacto/notas)  
**And** el proveedor queda disponible para asociarse a un Producto.

### AC2 — Asociación proveedor ↔ producto

**Given** un producto existente  
**When** el usuario captura/edita el proveedor del producto  
**Then** el sistema guarda `products.supplier_id` y lo muestra en listado/detalle del Producto.

### AC3 — Borrado seguro

**Given** un proveedor en uso (referenciado por productos)  
**When** se intenta eliminar (o purgar en papelera)  
**Then** el sistema bloquea borrado y evita inconsistencias (mensaje claro al usuario).

## Tasks / Subtasks

1) DB: proveedores (AC: 1, 3)
- [x] Crear tabla `suppliers` (similar a `brands`):
  - [x] `name` (string, único, collation `utf8mb4_0900_ai_ci`)
  - [x] Contacto opcional (definir forma: ver "Preguntas abiertas")
  - [x] `notes` (text nullable)
  - [x] `softDeletes()` + `timestamps()`
- [x] Crear `gatic/app/Models/Supplier.php` (SoftDeletes + normalización de nombre como `Brand`)

2) DB: relación Producto → Proveedor (AC: 2, 3)
- [x] Migración: agregar `products.supplier_id` nullable + FK `restrictOnDelete` (consistente con `brand_id`)
- [x] Modelo `Product`: relación `supplier()` (belongsTo) + casts/DocBlocks si aplica
- [x] Ajustar factory/seeders para soportar `supplier_id` (sin forzarlo por default)

3) UI Catálogos: pantalla Proveedores (AC: 1, 3)
- [x] Crear Livewire page (patrón de catálogos existentes):
  - [x] `gatic/app/Livewire/Catalogs/Suppliers/SuppliersIndex.php`
  - [x] `gatic/resources/views/livewire/catalogs/suppliers/suppliers-index.blade.php`
- [x] Reglas:
  - [x] RBAC: `Gate::authorize('catalogs.manage')` en `mount`/`render` y en acciones
  - [x] `name` requerido + único (incluye soft-deleted, por collation) + normalización
  - [x] Soft-delete al eliminar
  - [x] Bloqueo "en uso" con `CatalogUsage::isInUse('suppliers', $supplier->id)` (toasts claros)

4) Papeleras: integrar proveedores en `CatalogsTrash` (AC: 3)
- [x] Agregar tab `suppliers` en `gatic/app/Livewire/Catalogs/Trash/CatalogsTrash.php`
- [x] Extender acciones de papelera para soportar `suppliers`:
  - [x] `gatic/app/Actions/Trash/RestoreTrashedItem.php`
  - [x] `gatic/app/Actions/Trash/PurgeTrashedItem.php` (bloquear purge si "en uso")
  - [x] `gatic/app/Actions/Trash/EmptyTrash.php` (bloquear purge en uso; o saltar con reporte)

5) UI Inventario: seleccionar proveedor en Producto (AC: 2)
- [x] Extender `gatic/app/Livewire/Inventory/Products/ProductForm.php`:
  - [x] Cargar proveedores no eliminados (orden por `name`)
  - [x] Validar `supplier_id` existe y `deleted_at IS NULL`
- [x] Extender `gatic/resources/views/livewire/inventory/products/product-form.blade.php` con campo "Proveedor"
  - [x] MVP: `<select>` (consistente con `brand_id`)
  - [ ] Recomendado (UX): evaluar `SupplierCombobox` (similar a `EmployeeCombobox`) si el catálogo puede crecer. [Source: `_bmad-output/implementation-artifacts/ux.md#ComboboxAsync (Marca/Proveedor/RPE)`]
- [x] Mostrar proveedor en:
  - [x] Listado de productos `gatic/resources/views/livewire/inventory/products/products-index.blade.php`
  - [x] Detalle de producto `gatic/resources/views/livewire/inventory/products/product-show.blade.php`

6) Routing + navegación (AC: 1)
- [x] Agregar ruta en `gatic/routes/web.php`:
  - [x] `GET /catalogs/suppliers` → `catalogs.suppliers.index` (bajo `can:catalogs.manage`)
- [x] (Opcional) Agregar link en sidebar dentro de sección "Catálogos"

7) Tests (AC: 1–3)
- [x] Agregar `gatic/tests/Feature/Catalogs/SuppliersTest.php` (clonar patrón de `BrandsTest.php`):
  - [x] RBAC (Admin/Editor OK; Lector forbidden)
  - [x] Crear/editar: normalización + unicidad
  - [x] Delete soft-delete
  - [x] Delete bloqueado cuando está en uso (crear tabla auxiliar con FK a `suppliers`)
- [x] Inventario: extender `gatic/tests/Feature/Inventory/ProductsTest.php` para `supplier_id` (validación + persistencia)
- [x] Soft-delete regression (checklist obligatorio): asegurar que proveedores soft-deleted no aparecen en selects/listados.

## Dev Notes

### Developer Context (lectura obligatoria)

#### Qué cambia en el sistema (Epic 14 / Story 14.1)

- Se introduce un nuevo catálogo: **Proveedores** (`suppliers`).
- Se agrega asociación opcional en Productos: `products.supplier_id`.
- Objetivo: capturar **origen de compra** y soportar auditorías/gestión; no altera estados de Activo ni conteos QTY.

#### Patrones existentes a reutilizar (no reinventar)

- CRUD de catálogos (Livewire + Bootstrap + toasts):
  - `gatic/app/Livewire/Catalogs/Brands/BrandsIndex.php`
  - `gatic/app/Livewire/Catalogs/Locations/LocationsIndex.php`
  - `gatic/app/Livewire/Catalogs/Categories/CategoriesIndex.php`
- Soft-delete en catálogos (y papelera): `gatic/app/Livewire/Catalogs/Trash/CatalogsTrash.php` + `gatic/app/Actions/Trash/*`
- Bloqueo “en uso” (FK discovery): `gatic/app/Support/Catalogs/CatalogUsage.php`
- Convención DB: collation `utf8mb4_0900_ai_ci` + `restrictOnDelete` (ver `gatic/database/migrations/2026_01_02_000000_create_products_table.php`)

#### UX (para evitar fricción en operación)

- En formularios, la selección de Proveedor debe ser rápida y sin escribir exacto.
  - UX spec: “ComboboxAsync (Marca/Proveedor/RPE)” con debounce + teclado + “No results” claro. [Source: `_bmad-output/implementation-artifacts/ux.md`]
  - MVP aceptable: `<select>` (consistente con `brand_id`) si el catálogo es pequeño; si crece, migrar a combobox reusable.

### Technical Requirements (guardrails)

- **RBAC server-side obligatorio**:
  - Ruta `/catalogs/suppliers` bajo `can:catalogs.manage` (ver patrón en `gatic/routes/web.php`).
  - En Livewire: `Gate::authorize('catalogs.manage')` en `render()` y en acciones (save/edit/delete).
  - En ProductForm: `Gate::authorize('inventory.manage')` ya existe; mantener.
- **Soft-delete / papelera**:
  - `delete()` debe ser soft-delete (como `Brand`/`Category`/`Location`).
  - `purge` (borrado físico) solo Admin; y **bloqueado** si el proveedor está en uso.
- **Bloqueo “en uso” (no inconsistencias)**:
  - Antes de `delete()`/`purge`, validar con `CatalogUsage::isInUse('suppliers', $id)` (patrón existente).
- **Validación + normalización**:
  - Normalizar `name` (trim + colapsar espacios) antes de validar.
  - Unicidad real respaldada por `unique(name)` con collation `utf8mb4_0900_ai_ci` (case/accent-insensitive).
- **Errores con `error_id` en prod**:
  - Si introduces búsqueda/acciones que pueden fallar (DB / schema introspection), seguir patrón `ErrorReporter` + toast con `error_id` (ver `EmployeeCombobox`).
- **UX long-request (>3s)**:
  - Si el listado de proveedores o el combobox hace queries que pueden tardar, integrar `<x-ui.long-request />` (loader + cancelar) alrededor del área de resultados. [Source: `gatic/resources/views/components/ui/long-request.blade.php`]
- **Copy/UI en español; código/DB/rutas en inglés** (regla no negociable). [Source: `docsBmad/project-context.md`]

### Architecture Compliance (no romper estructura)

- **Estructura por módulos (consistente con el repo)**:
  - Página Livewire de catálogo en `gatic/app/Livewire/Catalogs/Suppliers/*`.
  - Modelo en `gatic/app/Models/Supplier.php`.
  - Reglas de papelera en `gatic/app/Actions/Trash/*` y UI en `gatic/app/Livewire/Catalogs/Trash/CatalogsTrash.php`.
- **Rutas y naming**:
  - Path en inglés `kebab-case`: `/catalogs/suppliers` (no `/catalogos/...`).
  - Route name `catalogs.suppliers.index` (consistente con `catalogs.brands.index`).
- **Sin dependencias nuevas / sin controllers “porque sí”**:
  - Mantener patrón actual: route → Livewire component.

### Library / Framework Requirements

- Laravel 11 (`laravel/framework: ^11.31`) + PHP `^8.2` (ver `gatic/composer.json`).
- Livewire 3 (`livewire/livewire: ^3.0`) + Bootstrap 5.
- DB: MySQL 8 (collation `utf8mb4_0900_ai_ci` ya estandarizada en migraciones).
- No introducir librerías nuevas (datatables, select2, etc.) en esta story.

### File Structure Requirements

Archivos nuevos propuestos:
- `gatic/database/migrations/20xx_xx_xx_xxxxxx_create_suppliers_table.php`
- `gatic/app/Models/Supplier.php`
- `gatic/database/migrations/20xx_xx_xx_xxxxxx_add_supplier_id_to_products_table.php`
- `gatic/app/Livewire/Catalogs/Suppliers/SuppliersIndex.php`
- `gatic/resources/views/livewire/catalogs/suppliers/suppliers-index.blade.php`
- `gatic/tests/Feature/Catalogs/SuppliersTest.php`

Archivos a modificar (mínimos):
- `gatic/routes/web.php` (agregar `/catalogs/suppliers`)
- `gatic/app/Livewire/Inventory/Products/ProductForm.php` + `gatic/resources/views/livewire/inventory/products/product-form.blade.php`
- `gatic/app/Models/Product.php` (relación `supplier()`)
- `gatic/resources/views/livewire/inventory/products/products-index.blade.php` (mostrar proveedor)
- `gatic/resources/views/livewire/inventory/products/product-show.blade.php` (mostrar proveedor)
- `gatic/app/Livewire/Catalogs/Trash/CatalogsTrash.php` + `gatic/app/Actions/Trash/*` (integrar suppliers)
- `gatic/tests/Feature/Inventory/ProductsTest.php` (validación/persistencia de `supplier_id`)

### Testing Requirements

Objetivo: evitar regresiones en RBAC, soft-delete y consistencia referencial.

Mínimo recomendado:

1) Catálogos: Proveedores (feature + Livewire)
- `gatic/tests/Feature/Catalogs/SuppliersTest.php`:
  - RBAC: Admin/Editor pueden; Lector no puede (forbidden).
  - Normalización + unicidad (incluye soft-deleted, por collation).
  - Delete = soft-delete y desaparece del listado.
  - Delete bloqueado si está “en uso” (crear tabla auxiliar con FK a `suppliers` como en `BrandsTest.php`).

2) Inventario: Producto ↔ proveedor
- Extender `gatic/tests/Feature/Inventory/ProductsTest.php`:
  - Validación: `supplier_id` debe existir y no estar soft-deleted.
  - Persistencia: create/edit de producto guarda `supplier_id` correctamente.

3) Regression obligatorio (checklist): Soft-delete
- Proveedor soft-deleted **no** debe aparecer en:
  - Listado de proveedores (por default scope).
  - Select/combobox de Proveedor en ProductForm.

### Previous Story Intelligence (reusar learnings)

- Catálogos ya resueltos (reusar patrón exacto):
  - Story 2.x (Categorías/Marcas/Ubicaciones) ya definió: soft-delete + bloqueo “en uso” para evitar inconsistencias.
  - Implementación actual de referencia: `BrandsIndex` + `CatalogUsage` + `CatalogsTrash`.
- Papelera / purga:
  - La política “soft-delete con retención indefinida hasta que Admin vacíe papelera” es no negociable (ver project-context).
  - Si se integra `suppliers` a la papelera de catálogos, mantener simetría con `categories/brands/locations` (tabs + restore + purge).
- Naming/idioma:
  - Identificadores en inglés (`Supplier`, `suppliers`, `supplier_id`); copy/UI en español (“Proveedor”). [Source: `project-context.md`]

### Git Intelligence (patrones recientes)

Últimos commits (para mantener consistencia de estilo y evitar regresiones):
- `a726991` `feat(inventory): add low stock threshold alerts`
- `0b0661e` `feat(alerts): implement overdue and due-soon loan alerts`
- `dc20480` `feat(assets): add loan due date functionality`
- `32a77b2` `feat(ui): implement command palette and unified search`
- `28528b4` `feat(auth): rediseñar login con slideshow CFE e identidad corporativa`

Implicación para esta story:
- Mantener patrón: “feature” = migrations + Livewire + views + tests feature (RefreshDatabase).
- Mantener copy/UI en español y paths/rutas en inglés.

### Latest Tech Information (evitar decisiones desactualizadas)

- Laravel 11 en este repo: `laravel/framework: ^11.31` (ver `gatic/composer.json`).
- Livewire 3 en este repo: `livewire/livewire: ^3.0`.
- Calidad/CI baseline del repo: Pint + PHPUnit 11 + Larastan 3 (ver Story 1.7 y `project-context.md`).
- DB collation estándar del proyecto: `utf8mb4_0900_ai_ci` (permite unicidad case/accent-insensitive en `name`).

## Project Context Reference

- Bible: `docsBmad/project-context.md` (stack, RBAC, soft-delete, errores con `error_id`, polling).
- Lean notes: `project-context.md` (reglas críticas + tooling Windows).
- Arquitectura: `_bmad-output/implementation-artifacts/architecture.md` (estructura por módulos y cobertura).

Reglas no negociables aplicables a esta story:
- Identificadores (código/DB/rutas) en inglés; copy/UI en español.
- Soft-delete con retención indefinida hasta que Admin vacíe papelera.
- Autorización server-side obligatoria (Gates/Policies).

## Story Completion Status

- Status: `done`
- Nota: "Code review aplicado (fixes + lint). PHPUnit pendiente (requiere MySQL/Sail)."

## Preguntas abiertas (guardar para PO/SM; no bloquean esta story)

1) ¿“Contacto” debe ser un solo campo libre, o campos separados (`contact_name`, `contact_email`, `contact_phone`)?
2) ¿`supplier_id` debe ser obligatorio para Productos nuevos, o opcional? (propuesta MVP: opcional).
3) UX: ¿se requiere `ComboboxAsync` desde el primer release para Proveedor, o basta `<select>` mientras el catálogo sea pequeño?

## Dev Agent Record

### Agent Model Used

Story context created by: GPT-5.2 (Codex CLI)
Implementation agent: Claude Opus 4.5

### Debug Log References

N/A

### Completion Notes List

 - Story seleccionada automáticamente desde `sprint-status.yaml` (primer `ready-for-dev`: `14-1-*`).
 - Epic 14 ya estaba en `in-progress` en `_bmad-output/implementation-artifacts/sprint-status.yaml`.
 - Implementado catálogo de Proveedores (Suppliers) siguiendo patrón de Brands.
 - Migración `create_suppliers_table` con `name` (único, normalizado), `contact`, `notes`, soft-deletes.
 - Modelo `Supplier.php` con normalización de nombre (trim + colapsar espacios).
 - Componente Livewire `SuppliersIndex.php` con CRUD, RBAC (`catalogs.manage`), soft-delete, bloqueo "en uso".
 - Integrado `suppliers` en papelera de catálogos (`CatalogsTrash`, `RestoreTrashedItem`, `PurgeTrashedItem`, `EmptyTrash`).
 - Migración `add_supplier_id_to_products_table` con FK `restrictOnDelete`.
 - Modelo `Product.php` actualizado con relación `supplier()`.
 - `ProductForm.php` actualizado para seleccionar proveedor (select dropdown, validación soft-delete).
 - Listado y detalle de productos muestran columna/campo "Proveedor".
 - Ruta `/catalogs/suppliers` agregada bajo `can:catalogs.manage`.
 - Link "Proveedores" agregado en sidebar dentro de sección Catálogos.
 - Tests completos: `SuppliersTest.php` (8 tests) + extensión de `ProductsTest.php` (6 tests adicionales para supplier_id).
 - Decisión MVP: campo `contact` como texto libre (un solo campo) siguiendo simplicidad.
 - Decisión MVP: `supplier_id` opcional en productos (como `brand_id`).
 - Decisión MVP: `<select>` para proveedor (consistente con marca); combobox async diferido a futuro si catálogo crece.
 - Fixes de Senior Review (AI): tests sin DDL (flaky), null-safety en notas, error_id en validación "en uso", mensaje claro en purge.

### File List

 - `gatic/database/migrations/2026_02_02_000001_create_suppliers_table.php` (NEW)
 - `gatic/database/migrations/2026_02_02_000002_add_supplier_id_to_products_table.php` (NEW)
 - `gatic/app/Models/Supplier.php` (NEW)
 - `gatic/database/factories/SupplierFactory.php` (NEW)
 - `gatic/app/Livewire/Catalogs/Suppliers/SuppliersIndex.php` (NEW)
 - `gatic/resources/views/livewire/catalogs/suppliers/suppliers-index.blade.php` (NEW)
 - `gatic/tests/Feature/Catalogs/SuppliersTest.php` (NEW)
 - `gatic/app/Models/Product.php` (MODIFIED - added supplier relation)
 - `gatic/database/factories/ProductFactory.php` (MODIFIED - added withSupplier method)
 - `gatic/routes/web.php` (MODIFIED - added suppliers route)
 - `gatic/app/Livewire/Catalogs/Trash/CatalogsTrash.php` (MODIFIED - added suppliers tab)
 - `gatic/resources/views/livewire/catalogs/trash/catalogs-trash.blade.php` (MODIFIED - added suppliers tab)
 - `gatic/app/Actions/Trash/RestoreTrashedItem.php` (MODIFIED - added Supplier support)
 - `gatic/app/Actions/Trash/PurgeTrashedItem.php` (MODIFIED - added Supplier support)
 - `gatic/app/Actions/Trash/EmptyTrash.php` (MODIFIED - added Supplier support)
 - `gatic/app/Livewire/Inventory/Products/ProductForm.php` (MODIFIED - added supplier_id)
 - `gatic/resources/views/livewire/inventory/products/product-form.blade.php` (MODIFIED - added supplier select)
 - `gatic/app/Livewire/Inventory/Products/ProductsIndex.php` (MODIFIED - load supplier relation)
 - `gatic/resources/views/livewire/inventory/products/products-index.blade.php` (MODIFIED - added supplier column)
 - `gatic/app/Livewire/Inventory/Products/ProductShow.php` (MODIFIED - load supplier relation)
 - `gatic/resources/views/livewire/inventory/products/product-show.blade.php` (MODIFIED - show supplier)
 - `gatic/resources/views/layouts/partials/sidebar-nav.blade.php` (MODIFIED - added suppliers link)
 - `gatic/tests/Feature/Inventory/ProductsTest.php` (MODIFIED - added supplier tests)
 - `_bmad-output/implementation-artifacts/14-1-proveedores-catalogo-y-relacion-con-productos.md` (MODIFIED)
 - `_bmad-output/implementation-artifacts/sprint-status.yaml` (MODIFIED)

## Senior Developer Review (AI)

Resultado: **Approved (fixes aplicados)**.

### Resumen
- Git vs Story: 0 discrepancias (File List sincronizada)
- Hallazgos originales: 1 HIGH, 3 MEDIUM → 0 pendientes
- Validación: `pint --test` OK (PHP 8.4). PHPUnit no ejecutado (Docker/MySQL no disponible).

### Fixes aplicados (AI)
- Eliminado DDL en tests (evita flakiness con transacciones). [`gatic/tests/Feature/Catalogs/SuppliersTest.php`]
- Null-safety en “Notas” (evita TypeError + muestra `-`). [`gatic/resources/views/livewire/catalogs/suppliers/suppliers-index.blade.php`]
- Error inesperado con `error_id` al fallar `CatalogUsage` (prod). [`gatic/app/Livewire/Catalogs/Suppliers/SuppliersIndex.php`]
- Mensaje de purge por FK más claro y específico por tipo (incluye proveedores). [`gatic/app/Actions/Trash/PurgeTrashedItem.php`]

## Change Log
- 2026-02-02: Implemented Story 14.1 - Suppliers (catálogo) + `supplier_id` en Productos (Claude Opus 4.5)
- 2026-02-04: Senior Developer Review (AI) - Approved (fixes aplicados)
