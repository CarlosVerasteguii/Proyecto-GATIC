# Story 3.1: Crear y mantener Productos

Status: done

Story Key: `3-1-crear-y-mantener-productos`  
Epic: `3` (Gate 2: Inventario navegable)

Fuentes:
- `_bmad-output/project-planning-artifacts/epics.md` (Epic 3 / Story 3.1; FR8, FR9)
- `_bmad-output/prd.md` (FR8, FR9)
- `_bmad-output/architecture.md` (stack + estructura + mapeo Epic 3)
- `docsBmad/project-context.md` (bible: glosario + reglas críticas)
- `project-context.md` (reglas resumidas para agentes)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (orden + notas del Epic 3)
- `_bmad-output/implementation-artifacts/2-1-gestionar-categorias-incluye-serializado-asset-tag.md` (catálogos + collation + flags)
- `_bmad-output/implementation-artifacts/2-2-gestionar-marcas.md` (catálogos + collation)
- `_bmad-output/implementation-artifacts/2-3-gestionar-ubicaciones.md` (catálogos + collation)
- `_bmad-output/implementation-artifacts/2-4-soft-delete-y-restauracion-de-catalogos.md` (soft-delete + `CatalogUsage` vía FKs)

<!-- Nota: Validación es opcional. Correr validate-create-story para quality check antes de dev-story. -->

## Story

As a Admin/Editor,
I want crear y mantener Productos (asignando Categoría y Marca) y, cuando aplique, capturar stock total para productos por cantidad,
so that el inventario sea navegable y consistente para operar y consultar disponibilidad en historias posteriores (FR8, FR9).

## Acceptance Criteria

### AC1 - Acceso por rol (defensa en profundidad)

**Given** un usuario autenticado con rol Admin o Editor  
**When** navega a Inventario > Productos (`/inventory/products`)  
**Then** puede ver el listado

**And** puede crear/editar Productos (rutas de create/edit + acciones Livewire protegidas server-side).

**Given** un usuario autenticado con rol Lector  
**When** navega al listado `/inventory/products`  
**Then** puede ver el listado (solo lectura)

**And** si intenta crear/editar Productos (rutas `/inventory/products/create`, `/inventory/products/{id}/edit` o acciones Livewire)  
**Then** el servidor bloquea (403 o equivalente).

### AC2 - Crear Producto con Categoría y Marca

**Given** el formulario de creación de Producto  
**When** el usuario guarda con datos válidos  
**Then** el Producto se crea con:
- `name` normalizado (trim + colapsar espacios)
- `category_id` requerido (FK real a `categories.id`)
- `brand_id` opcional (FK real a `brands.id`)

**And** la UI muestra un toast de éxito (“Producto creado.”) y redirige al listado.

### AC3 - Tipo de Producto derivado por Categoría (FR9)

**Given** una Categoría con `is_serialized = true`  
**When** se crea un Producto en esa Categoría  
**Then** el Producto se considera **serializado**

**And** el formulario lo muestra como “Serializado” (solo lectura).

**Given** una Categoría con `is_serialized = false`  
**When** se crea un Producto en esa Categoría  
**Then** el Producto se considera **por cantidad**

**And** el formulario permite capturar `qty_total` (stock total) como entero `>= 0`.

### AC4 - Validaciones de stock por cantidad

**Given** un Producto por cantidad (Categoría `is_serialized=false`)  
**When** se guarda  
**Then** `qty_total` es requerido y debe ser entero `>= 0`.

**Given** un Producto serializado (Categoría `is_serialized=true`)  
**When** se guarda  
**Then** `qty_total` no aplica y se persiste como `NULL` (o se ignora si se envía desde UI).

### AC5 - Ubicación NO se captura en Producto serializado

**Given** un Producto serializado  
**When** se crea/edita el Producto  
**Then** NO existe campo de “ubicación operativa” en el Producto (la ubicación se captura a nivel de Activo en historias posteriores).

### AC6 - Categoría inmutable al editar (guardrail)

**Given** un Producto existente  
**When** el usuario abre el formulario de edición  
**Then** la Categoría no puede cambiarse (para evitar cambiar el modo serializado vs cantidad).

### AC7 - Listado funcional

**Given** el listado de Productos  
**When** existen Productos  
**Then** la tabla muestra (mínimo): Nombre, Categoría, Marca, Tipo (Serializado/Por cantidad), Stock total (solo por cantidad)

**And** existe búsqueda simple por `name` (contiene) con escape de comodines `LIKE` (`%`, `_`), siguiendo el patrón de Catálogos.

## Tasks / Subtasks

1) Data model (DB) (AC: 2-7)
- [x] Crear migración `products` con:
  - [x] `id`
  - [x] `name` (string, requerido) con collation `utf8mb4_0900_ai_ci`
  - [x] `category_id` (FK a `categories.id`, requerido)
  - [x] `brand_id` (FK a `brands.id`, nullable)
  - [x] `qty_total` (unsigned int, nullable; solo aplica para “por cantidad”)
  - [x] `deleted_at` + timestamps (soft-delete)
- [x] Definir charset/collation consistente (`utf8mb4` / `utf8mb4_0900_ai_ci`)
- [x] Crear índices para `category_id`, `brand_id` y `name`
- [x] Usar FKs reales para que `CatalogUsage` pueda bloquear deletes de catálogos “en uso” vía `information_schema` [Source: `gatic/app/Support/Catalogs/CatalogUsage.php`]

2) Dominio (AC: 2-7)
- [x] Crear `gatic/app/Models/Product.php` con `SoftDeletes`
- [x] Normalizar `name` (igual que `Brand/Category/Location`: trim + colapsar espacios)
- [x] Definir relaciones:
  - [x] `category()` -> `belongsTo(Category::class)`
  - [x] `brand()` -> `belongsTo(Brand::class)` (nullable)

3) Autorización / RBAC (AC: 1)
- [x] Agregar gates:
  - [x] `inventory.view` (Admin/Editor/Lector)
  - [x] `inventory.manage` (Admin/Editor)
- [x] Implementar en `gatic/app/Providers/AuthServiceProvider.php`
- [x] Documentar en `docsBmad/rbac.md`

4) Rutas (AC: 1)
- [x] Agregar rutas bajo middleware `auth`, `active`:
  - [x] `GET /inventory/products` -> `inventory.products.index` (gate `inventory.view`)
  - [x] `GET /inventory/products/create` -> `inventory.products.create` (gate `inventory.manage`)
  - [x] `GET /inventory/products/{product}/edit` -> `inventory.products.edit` (gate `inventory.manage`)

5) UI (Livewire + Bootstrap) (AC: 2-7)
- [x] Implementar módulo Livewire `Inventory/Products`:
  - [x] `gatic/app/Livewire/Inventory/Products/ProductsIndex.php` (listado + búsqueda + paginación)
  - [x] `gatic/app/Livewire/Inventory/Products/ProductForm.php` (create/edit)
  - [x] Views:
    - [x] `gatic/resources/views/livewire/inventory/products/products-index.blade.php`
    - [x] `gatic/resources/views/livewire/inventory/products/product-form.blade.php`
  - [x] Reusar patrones UX existentes (NO reinventar):
  - [x] Toasts: flash de sesión (`with('status', ...)`) consumido por `<x-ui.toast-container />` [Source: `gatic/docs/ui-patterns.md`]
  - [x] Operaciones lentas + cancelar: `<x-ui.long-request target=\"save\" />` (y evitar que aplique al polling)

6) Navegación (AC: 1)
- [x] Agregar link “Inventario > Productos” en `gatic/resources/views/layouts/partials/sidebar-nav.blade.php`
  - [x] Visible para `inventory.view`

7) Tests (Feature) (AC: 1-7)
- [x] Crear `gatic/tests/Feature/Inventory/ProductsTest.php`
- [x] Cobertura mínima:
  - [x] RBAC: Admin/Editor pueden ver index y create/edit; Lector solo index (y 403 en create/edit)
  - [x] Crear producto por cantidad requiere `qty_total`
  - [x] Crear producto serializado fuerza `qty_total = NULL`
  - [x] Categoría inmutable al editar (no se puede cambiar)

## Dev Notes

### Contexto actual (lo que ya existe)

- Stack y reglas: ver `project-context.md` y `_bmad-output/architecture.md` (Laravel 11 + Livewire 3 + Bootstrap 5; sin WebSockets; polling donde aplique).
- Cat logos ya implementados (Epic 2): Categor¡as/Marcas/Ubicaciones + soft-delete + papelera + bloqueo por “en uso” (v¡a `CatalogUsage`).
  - Patrones a reusar (NO reinventar): `gatic/app/Livewire/Catalogs/*`, `gatic/docs/ui-patterns.md`.
- A£n NO existe el m¢dulo de Inventario (`app/Livewire/Inventory/*`) ni `Product`/`products` table.
- Historias relacionadas (Epic 3, orden en `sprint-status.yaml`):
  - `3-2-crear-y-mantener-activos-serializados-con-reglas-de-unicidad` (depende de este CRUD de Productos + flags en Categor¡as)
  - `3-3-listado-de-inventario-productos-con-indicadores-de-disponibilidad`
  - `3-4-detalle-de-producto-con-conteos-y-desglose-por-estado`
  - `3-5-detalle-de-activo-con-estado-ubicacion-y-tenencia-actual`
  - `3-6-ajustes-de-inventario-admin-con-motivo`

### Reglas de dominio que NO se deben violar

- **Producto** = entidad de cat logo/modelo (ej. “Laptop Dell X”).  
- **Activo** = unidad f¡sica (solo para productos serializados; se implementa en historias posteriores).  
- La **serializaci¢n** se define por `Category.is_serialized` (FR9):
  - Si serializado: el inventario real se maneja con Activos; en Epic 3 la “tenencia” se muestra como **N/A** (nota de sprint).
  - Si por cantidad: el Producto debe almacenar un **stock total** (en esta story) para poder operar inventario por cantidad en Epics 4/5.
- La ubicaci¢n operativa **NO** vive en Producto serializado (vive en Activo).

### Guardrails para evitar errores t¡picos de agente

- Identificadores de c¢digo/DB/rutas en **ingl‚s**; copy/UI en **espa¤ol** (ver `project-context.md`).
- Autorizaci¢n **server-side** obligatoria: middleware `can:` en rutas + `Gate::authorize()` dentro de acciones Livewire.
- No agregar dependencias nuevas para esta story (no se requiere).
- B£squedas `LIKE`: escapar `%` y `_` (patr¢n existente en Cat logos).

## Technical Requirements

- PHP/Laravel: respetar el stack actual (Laravel 11 en `gatic/`; no mover estructura).
- UI: Livewire 3 + Blade + Bootstrap 5; usar `#[Layout('layouts.app')]` como en Cat logos.
- Persistencia: MySQL 8; migraciones con `utf8mb4` + `utf8mb4_0900_ai_ci` (case+acento-insensitive) consistente con Cat logos.
- Soft-delete: usar `SoftDeletes` (pol¡tica de retenci¢n indefinida; sin purga en esta story).
- UX/feedback: toast de éxito vía flash de sesión (`with('status', ...)`); para operaciones lentas usar `<x-ui.long-request />` según `gatic/docs/ui-patterns.md`.

## Architecture Compliance

- Respetar la estructura mapeada para Epic 3 en `_bmad-output/architecture.md`:
  - Modelos: `gatic/app/Models/Product.php`
  - UI: `gatic/app/Livewire/Inventory/*` + views en `gatic/resources/views/livewire/inventory/*`
- Convenci¢n de rutas:
  - Paths: `kebab-case` en ingl‚s (ej. `/inventory/products`)
  - Names: `dot.case` por m¢dulo (ej. `inventory.products.index`)
- Sin controllers salvo “bordes” (no se requieren en esta story).
- Mantener integridad referencial: FKs reales a cat logos para habilitar FR7 (bloqueo “en uso”) sin duplicar l¢gica.

## Library / Framework Requirements

- Laravel 11 (seguir lo fijado por `gatic/composer.json` + `gatic/composer.lock`; NO subir major/minor en esta story).
- Livewire 3 (componentes con `Livewire\\Component`; patrones existentes en `gatic/app/Livewire/Catalogs/*`).
- Bootstrap 5 (UI; seguir componentes Blade/partials existentes).
- Sin dependencias nuevas salvo que se justifique por AC (no se anticipan).

## File Structure Requirements

- Modelo:
  - `gatic/app/Models/Product.php`
- Migración:
  - `gatic/database/migrations/*_create_products_table.php`
- Livewire:
  - `gatic/app/Livewire/Inventory/Products/ProductsIndex.php`
  - `gatic/app/Livewire/Inventory/Products/ProductForm.php`
- Views:
  - `gatic/resources/views/livewire/inventory/products/products-index.blade.php`
  - `gatic/resources/views/livewire/inventory/products/product-form.blade.php`
- Rutas:
  - `gatic/routes/web.php`
- Navegación:
  - `gatic/resources/views/layouts/partials/sidebar-nav.blade.php`
- Tests:
  - `gatic/tests/Feature/Inventory/ProductsTest.php`

## Testing Requirements

- Seguir patr¢n existente de tests (ver `gatic/tests/Feature/Catalogs/CategoriesTest.php`):
  - `RefreshDatabase`
  - `Livewire::actingAs(...)` para probar `save()`/acciones server-side
  - asserts de `assertForbidden()` en rutas y `AuthorizationException` en acciones Livewire (defensa en profundidad)
- Casos m¡nimos obligatorios:
  - RBAC:
    - Admin/Editor: index (200) + create/edit (200)
    - Lector: index (200) y create/edit (403)
  - Validaci¢n por tipo:
    - Categor¡a no-serializada: `qty_total` requerido, entero `>= 0`
    - Categor¡a serializada: `qty_total` queda `NULL` aunque el usuario intente setearlo
  - Inmutabilidad:
    - En edit, no se permite cambiar `category_id` (y se mantiene el valor original)

## Git Intelligence Summary

- El repo `gatic/` ya consolid¢ patrones para m¢dulos Livewire-first:
  - Rutas declarativas en `gatic/routes/web.php` con grupos `auth`, `active`, `can:*`.
  - Componentes Livewire con `Gate::authorize(...)` en `mount()`, `render()` y acciones (defensa en profundidad).
  - Normalizaci¢n de `name` en modelos (`Brand/Category/Location`) y escape de `LIKE` en listados.
- Commits recientes relevantes (referencia de estilo/patrones):
  - `feat(catalogs): add trash restore and in-use delete guard` (uso de `CatalogUsage`)
  - `feat(catalogs): CRUD ...` (estructura de carpetas, tests, toasts)

## Latest Tech Information

- Laravel 11: mantener el proyecto dentro de 11.x (ej. la documentaci¢n oficial es `laravel.com/docs/11.x`). Si se actualiza, solo hacerlo con upgrades “patch” compatibles y revisando `composer.lock`.
  - Referencia externa (verificar al momento): `laravelversions.com` lista la “Latest Patch Release” para 11.x.
- Livewire 3: mantener v3.x (la home `livewire.laravel.com` y `laravel-livewire.com` apuntan a v3). No se requiere ning£n feature nuevo de Livewire para esta story.
- No hay integraciones externas nuevas en esta story: evitar decisiones de librer¡as “extra”.

## Project Context Reference

- Reglas cr¡ticas (source of truth):
  - `docsBmad/project-context.md` (bible; glosario Producto/Activo, sem¡ntica QTY, polling, locks, soft-delete)
  - `project-context.md` (resumen operativo para agentes; idioma, stack, reglas “dont-miss”)
- Arquitectura:
  - `_bmad-output/architecture.md` (estructura de carpetas y convenciones para Epic 3)
- Backlog:
  - `_bmad-output/project-planning-artifacts/epics.md` (Epic 3 / Story 3.1)
  - `_bmad-output/implementation-artifacts/sprint-status.yaml` (orden de stories y notas del Epic 3)
- Patrones de UI y consistencia:
  - `gatic/docs/ui-patterns.md` (toasts, long-request/cancel, polling wrappers)
  - `gatic/app/Livewire/Catalogs/*` (referencia de implementaci¢n real)

## Story Completion Status

- Status: `done`
- Nota de completitud: “Ultimate context engine analysis completed - comprehensive developer guide created”.
- Siguiente paso recomendado: correr `code-review` para revisión (idealmente con un LLM distinto).

### Project Structure Notes

- Estructura propuesta alineada a `_bmad-output/architecture.md` (Epic 3):
  - `app/Models/Product.php`
  - `app/Livewire/Inventory/Products/*`
  - Views en `resources/views/livewire/inventory/products/*`
- Convenciones consistentes con el repo:
  - rutas en ingl‚s (`/inventory/products`) + names `inventory.products.*`
  - UI/copy en espa¤ol, sin mezclar identificadores
- No se detectan conflictos con m¢dulos existentes (Catalogs/Admin). Esta story agrega un m¢dulo nuevo (Inventory) sin tocar Cat logos.

### References

- Backlog (AC base): `_bmad-output/project-planning-artifacts/epics.md` (Story 3.1)
- Requisitos (FR8/FR9): `_bmad-output/prd.md` (secci¢n “Inventory: Products & Assets”)
- Arquitectura/estructura: `_bmad-output/architecture.md` (secci¢n “File organization patterns” + mapeo Epic 3)
- Reglas de dominio: `docsBmad/project-context.md` (Producto vs Activo; sem¡ntica QTY; “sin WebSockets”)
- Reglas operativas para agentes: `project-context.md`
- Patrones UI (toasts/long-request/polling): `gatic/docs/ui-patterns.md`
- Ejemplos de implementaci¢n: `gatic/app/Livewire/Catalogs/Categories/*`, `gatic/tests/Feature/Catalogs/CategoriesTest.php`

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `docker compose -f gatic/compose.yaml exec -T laravel.test php artisan test`
- `docker compose -f gatic/compose.yaml exec -T laravel.test ./vendor/bin/pint --test`
- `docker compose -f gatic/compose.yaml exec -T laravel.test ./vendor/bin/phpstan analyse`

### Completion Notes List

- Migración `products` con FKs reales a `categories`/`brands`, collation MySQL 8 consistente y soft-delete.
- Módulo de Productos (listar/crear/editar) con Livewire + Bootstrap; tipo derivado por Categoría y `qty_total` solo para productos por cantidad.
- RBAC con gates `inventory.view` y `inventory.manage` aplicado en rutas y acciones Livewire (defensa en profundidad).
- Tests de feature para RBAC + reglas `qty_total`/inmutabilidad; suite completa + Pint + PHPStan en verde.
- Code review fixes: `ProductForm` precarga catálogos en `mount()`, autoriza hooks y agrega test de búsqueda con escape de comodines.

### File List

- `_bmad-output/implementation-artifacts/3-1-crear-y-mantener-productos.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `docsBmad/rbac.md`
- `gatic/app/Livewire/Inventory/Products/ProductForm.php`
- `gatic/app/Livewire/Inventory/Products/ProductsIndex.php`
- `gatic/app/Models/Product.php`
- `gatic/app/Providers/AuthServiceProvider.php`
- `gatic/database/migrations/2026_01_02_000000_create_products_table.php`
- `gatic/resources/views/layouts/partials/sidebar-nav.blade.php`
- `gatic/resources/views/livewire/inventory/products/product-form.blade.php`
- `gatic/resources/views/livewire/inventory/products/products-index.blade.php`
- `gatic/routes/web.php`
- `gatic/tests/Feature/Inventory/ProductsTest.php`

### Change Log

- 2026-01-02: Implementado módulo de Productos (Epic 3 / Story 3.1): migración + modelo + Livewire + rutas + navegación + tests.
- 2026-01-02: Senior Developer Review (AI): corregidos issues de performance/seguridad en `ProductForm`, documentación de toasts, y test de búsqueda con escape de `%`/`_`.

## Senior Developer Review (AI)

- Reviewer: Carlos
- Date: 2026-01-02
- Outcome: Changes applied (HIGH/MEDIUM)

### Fixes Applied

- Documentación: se ajusta la referencia de toasts a flash de sesión (`with('status', ...)`) consumido por `<x-ui.toast-container />`.
- `ProductForm`: evita queries en `render()` precargando `categories`/`brands` en `mount()` y calcula `categoryIsSerialized` sin query en `updatedCategoryId()`.
- Seguridad: `updatedCategoryId()` ahora aplica `Gate::authorize('inventory.manage')`.
- Tests: se agrega cobertura de AC7 para búsqueda con escape de `%` y `_`.
