# Story 3.3: Listado de Inventario (Productos) con indicadores de disponibilidad

Status: done

Story Key: `3-3-listado-de-inventario-productos-con-indicadores-de-disponibilidad`  
Epic: `3` (Gate 2: Inventario navegable)

Fuentes:
- `_bmad-output/project-planning-artifacts/epics.md` (Epic 3 / Story 3.3; FR25)
- `_bmad-output/prd.md` (FR25)
- `_bmad-output/architecture.md` (stack + constraints + UX polling)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (orden + notas del Epic 3)
- `docsBmad/project-context.md` (bible: glosario + semántica QTY + estados)
- `project-context.md` (reglas críticas para agentes)
- `_bmad-output/project-planning-artifacts/ux-design-specification.md` (patrones UX: tablas densas, indicadores, polling)
- `_bmad-output/implementation-artifacts/3-1-crear-y-mantener-productos.md` (patrones actuales de Inventory/Products)
- `_bmad-output/implementation-artifacts/3-2-crear-y-mantener-activos-serializados-con-reglas-de-unicidad.md` (estados de `assets` + patrones)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a usuario interno (Soporte TI),
I want ver un listado de Productos con disponibilidad clara (total/disponibles/no disponibles),
so that pueda responder rápido “¿tenemos X?” y operar inventario con confianza (FR25).

## Acceptance Criteria

### AC1 - Acceso por rol (defensa en profundidad)

**Given** un usuario autenticado (Admin/Editor/Lector)  
**When** navega a Inventario > Productos (`/inventory/products`)  
**Then** puede ver el listado con indicadores de disponibilidad

**And** el servidor autoriza el acceso con `Gate::authorize('inventory.view')`.

### AC2 - Indicadores por Producto (FR25)

**Given** un listado de Productos  
**When** el usuario abre el módulo de inventario (Inventario > Productos)  
**Then** cada renglón muestra, como mínimo:
- Total
- Disponibles
- No disponibles

**And** los números son consistentes con la semántica del dominio (ver AC3/AC4).

### AC3 - Cálculo para Productos serializados (Activos)

**Given** un Producto cuya Categoría es serializada (`categories.is_serialized=true`)  
**When** el sistema calcula disponibilidad para ese Producto  
**Then** usa los Activos (`assets`) NO eliminados (soft-delete) y aplica:
- `Total` = count(Activos) excluyendo `status = Retirado`
- `No disponibles` = count(Activos) con `status ∈ {Asignado, Prestado, Pendiente de Retiro}` (excluyendo Retirado)
- `Disponibles` = `Total - No disponibles` (equivalente a count(`Disponible`) bajo el set anterior)

**And** el cálculo NO depende de Movimientos (Epic 5).

### AC4 - Cálculo para Productos por cantidad (Stock agregado)

**Given** un Producto por cantidad (`categories.is_serialized=false`)  
**When** el sistema calcula disponibilidad  
**Then** usa `products.qty_total` como baseline (Epic 3 no tiene salidas/entradas por cantidad aún):
- `Total` = `qty_total`
- `No disponibles` = `0`
- `Disponibles` = `qty_total`

### AC5 - Resalte visual cuando no hay disponibles

**Given** un Producto con `Disponibles = 0` (ya sea serializado o por cantidad)  
**When** el usuario ve el listado  
**Then** ese Producto se resalta visualmente (fila o badge) de forma accesible (no solo color)

**And** el resalte no rompe el modo “tabla densa” (Bootstrap 5).

### AC6 - Rendimiento (anti N+1)

**Given** el listado paginado de Productos  
**When** se renderiza la tabla con conteos por Producto  
**Then** el sistema evita N+1 queries (no contar Activos por renglón)

**And** obtiene los conteos agregados mediante query/aggregates (ej. `withCount`) dentro de la query principal del listado.

## Tasks / Subtasks

1) Dominio / Datos (AC: 2-4, 6)
- [x] Agregar relación `Product::assets()` (`hasMany(Asset::class)`) para habilitar `withCount`.
 - [x] Definir el set "No disponibles" usando constantes de `Asset`:
  - `Asignado`, `Prestado`, `Pendiente de Retiro`
- [x] Definir explícitamente que `Retirado` no cuenta en inventario baseline (ver `docsBmad/project-context.md`).

2) Query de listado (Livewire) (AC: 2-4, 6)
 - [x] Actualizar `gatic/app/Livewire/Inventory/Products/ProductsIndex.php` para traer, por Producto serializado:
  - `assets_total` (excluyendo `Retirado`)
  - `assets_unavailable` (estatus no disponibles)
- [x] Mantener búsqueda por nombre (contiene) y paginación existente.
 - [x] No introducir endpoints/API nuevos (Epic 3).

3) UI (Blade + Bootstrap) (AC: 2, 5)
 - [x] Actualizar `gatic/resources/views/livewire/inventory/products/products-index.blade.php`:
  - columnas: `Total`, `Disponibles`, `No disponibles`
  - mantener CTA "Activos" solo si el Producto es serializado
  - resalte accesible cuando `Disponibles=0` (ej. icono + texto "Sin disponibles" + clase Bootstrap)
- [x] Mantener copy en español y consistencia visual (toasts/patrones existentes).

4) Tests (AC: 1-6)
 - [x] Extender `gatic/tests/Feature/Inventory/ProductsTest.php` con escenarios:
  - Producto por cantidad: `qty_total=10` => Total=10, Disponibles=10, No disponibles=0.
  - Producto serializado con Activos en múltiples estados => conteos correctos (incluyendo exclusión de `Retirado`).
  - Resalte cuando `Disponibles=0` (assert de texto/icono accesible; no depender solo de clases CSS).
  - RBAC sigue funcionando (no cambiar comportamiento de `inventory.view` / `inventory.manage`).

## Dev Notes

### Developer Context (por qué existe esta story)

- Esta story convierte Inventario > Productos en una vista operativa que responde rápido “¿tenemos X?” con números explícitos por Producto (FR25).
- En Epic 3 todavía NO hay Movimientos (Epic 5) ni Empleados (Epic 4), así que los conteos deben derivarse de:
  - `assets.status` (serializados)
  - `products.qty_total` (por cantidad)

### Alcance (DO)

- Mostrar indicadores Total/Disponibles/No disponibles por Producto en el listado existente (`/inventory/products`).
- Resaltar visualmente (accesible) los Productos sin disponibles.
- Implementación eficiente (anti N+1) y consistente con el módulo actual.

### Fuera de alcance (NO hacer en esta story)

- Movimientos / kardex (Epic 5).
- Búsqueda unificada por serial/asset_tag (Epic 6).
- Filtros avanzados por catálogos/estado (Epic 6).
- UI master-detail con expansión de unidades (UX lo sugiere como baseline futuro; no bloquear esta story).

### Technical Requirements (guardrails para el dev agent)

- Stack fijo: Laravel 11 + Livewire 3 + Bootstrap 5; no agregar dependencias ni actualizar versiones.
- Idioma: identificadores (código/DB/rutas) en inglés; copy/UI en español.
- Autorización server-side obligatoria: `Gate::authorize('inventory.view')` en el listado; `inventory.manage` solo para acciones de gestión (ya existente).
- Semántica QTY (bible):
  - No disponibles = `Asignado + Prestado + Pendiente de Retiro`
  - Disponibles = `Total - No disponibles`
  - `Retirado` no cuenta en inventario baseline

### Architecture Compliance

- Mantener estructura del módulo:
  - `gatic/app/Livewire/Inventory/Products/ProductsIndex.php`
  - `gatic/resources/views/livewire/inventory/products/products-index.blade.php`
  - (si se extrae lógica) `gatic/app/Actions/Inventory/*` o `gatic/app/Support/*` (sin helpers globales)
- Evitar queries por renglón; usar aggregates (`withCount`) con relaciones.

### Library / Framework Requirements

- Livewire 3:
  - mantener el patrón actual de `wire:model.live.debounce` para búsqueda
  - si se agrega auto-refresh, usar `wire:poll.visible` (~15s) y evitar resets inesperados de paginación
- Bootstrap 5:
  - resaltes y badges deben ser accesibles (texto + icono, contraste AA)
  - no introducir Tailwind ni librerías UI adicionales

### Previous Story Intelligence (patrones a reutilizar)

- De `3-1`:
  - listado de Productos ya existe (búsqueda por nombre + paginación + RBAC)
  - mantener rutas/nombres (`/inventory/products`, `inventory.products.*`)
- De `3-2`:
  - estatus canónicos de `Asset` ya existen como constantes en `gatic/app/Models/Asset.php`
  - `Retirado` está definido y debe excluirse del inventario baseline

### Git Intelligence Summary

- Cambios recientes relevantes:
  - `feat(inventory): crear y mantener Productos... (3-1)`
  - `feat(inventory): crear y mantener Activos serializados... (3-2)`
- Implicación: construir 3-3 sobre esos módulos existentes, sin reestructurar inventario ni tocar tooling.

### Latest Tech Information (web research)

- Bootstrap: documentación oficial indica que la rama actual 5.x tuvo update “v5.3.8”; este repo usa `bootstrap@^5.2.3` (NO actualizar en esta story).
  - Fuente: `https://getbootstrap.com/docs/versions/`
- Livewire: continúa en releases 3.x (ej. 3.6); mantener el rango de `gatic/composer.json` (`livewire/livewire:^3.0`).
  - Fuente: `https://laravel-news.com/livewire-36-released`
- Laravel 11: documentación oficial vigente (mantener `laravel/framework:^11.x` del repo).
  - Fuente: `https://laravel.com/docs/11.x/releases`

### References

- Backlog (AC base): `_bmad-output/project-planning-artifacts/epics.md` (Epic 3 / Story 3.3)
- Requisitos (FR25): `_bmad-output/prd.md` (“Search & Discovery”)
- Reglas de dominio: `docsBmad/project-context.md` (semántica QTY; estados; `Retirado`)
- Reglas operativas para agentes: `project-context.md`
- UX baseline: `_bmad-output/project-planning-artifacts/ux-design-specification.md` (“Tables & Lists (Inventario baseline)” + “Polling & Freshness”)
- Implementación existente:
  - `gatic/app/Livewire/Inventory/Products/ProductsIndex.php`
  - `gatic/resources/views/livewire/inventory/products/products-index.blade.php`
  - `gatic/app/Models/Product.php`
  - `gatic/app/Models/Asset.php`
  - `gatic/tests/Feature/Inventory/ProductsTest.php`

## Story Completion Status

- Status: `done`
- Nota de completitud: "Ultimate context engine analysis completed - comprehensive developer guide created".
- Siguiente paso recomendado: correr `code-review` para revisión.

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

### Completion Notes List

- ✅ Se agregaron columnas `Total`, `Disponibles`, `No disponibles` en Inventario > Productos con semántica QTY (FR25).
- ✅ Serializados: conteos desde `assets` (excluye `Retirado`; no disponibles = `Asignado` + `Prestado` + `Pendiente de Retiro`).
- ✅ Por cantidad: baseline desde `products.qty_total` (no disponibles = 0).
- ✅ Query anti N+1 con `withCount` usando la relación `Product::assets()`.
- ✅ UI con resalte accesible cuando `Disponibles = 0` ("Sin disponibles").
- ✅ Tests ejecutados (target): `docker compose exec -T laravel.test php artisan test --filter ProductsTest` (PASS).

### File List

- `_bmad-output/implementation-artifacts/3-3-listado-de-inventario-productos-con-indicadores-de-disponibilidad.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `gatic/app/Livewire/Inventory/Products/ProductsIndex.php`
- `gatic/app/Models/Asset.php`
- `gatic/app/Models/Product.php`
- `gatic/resources/views/livewire/inventory/products/products-index.blade.php`
- `gatic/tests/Feature/Inventory/ProductsTest.php`

## Senior Developer Review (AI)

Fecha: 2026-01-02  
Resultado: Approved (después de fixes)

### Findings (y fixes aplicados)

1) [HIGH] CTA "Activos" debía mostrarse solo para Productos serializados, pero se renderizaba también para "Por cantidad" (disabled).
   - Fix: ocultar CTA cuando no es serializado (se muestra `—`).
   - Archivos: `gatic/resources/views/livewire/inventory/products/products-index.blade.php`

2) [MEDIUM] Test frágil: se instanciaba `new ProductForm` y se llamaba `save()` fuera del harness de Livewire.
   - Fix: usar `Livewire::actingAs(...)->test(...)->assertForbidden()` (patrón consistente con `AssetsTest`).
   - Archivos: `gatic/tests/Feature/Inventory/ProductsTest.php`

3) [MEDIUM] Conteos de Activos se calculaban para todos los Productos, incluso los "Por cantidad".
   - Fix: agregar guardrail en los `withCount` para que solo apliquen cuando la categoría del Producto es serializada.
   - Archivos: `gatic/app/Livewire/Inventory/Products/ProductsIndex.php`

### Evidence

- Tests: `docker compose exec -T laravel.test php artisan test --filter ProductsTest` (PASS)

### Re-review (AI) - Ajustes adicionales

Fecha: 2026-01-02  
Resultado: Approved (despu‚s de fixes)

1) [MEDIUM] Query de conteos pesada por subconsultas duplicadas contra `categories` (guardrail repetido).
   - Fix: simplificar conteos con subqueries correlacionadas + `leftJoin` a categor¡as para evitar `whereExists` duplicado.
   - Archivos: `gatic/app/Livewire/Inventory/Products/ProductsIndex.php`

2) [MEDIUM] Tests fr giles por regex sobre HTML completo.
   - Fix: reemplazar por `assertSeeTextInOrder`/`assertSeeText` sobre respuesta HTTP.
   - Archivos: `gatic/tests/Feature/Inventory/ProductsTest.php`

3) [LOW] Accesibilidad: placeholder de acciones para productos por cantidad no anunciaba nada a lectores de pantalla.
   - Fix: agregar texto `visually-hidden` ("Sin acciones aplicables").
   - Archivos: `gatic/resources/views/livewire/inventory/products/products-index.blade.php`

4) [LOW] Limpieza: import no usado en tests.
   - Fix: remover `AuthorizationException`.
   - Archivos: `gatic/tests/Feature/Inventory/ProductsTest.php`

## Change Log

- 2026-01-02: `code-review` (AI) - Fixes aplicados (CTA "Activos" solo serializados, test Livewire auth, guardrail `withCount`); Status -> `done`.
- 2026-01-02: `code-review` (AI) - Ajustes adicionales (query conteos simplificada, tests menos fr giles, accesibilidad en acciones, cleanup imports); Status -> `done`.
