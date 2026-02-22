<!-- template-output: story_header -->
# Story 15.4: Selector de Producto escalable + crear con `returnTo` (sin precargas masivas)

Status: done

Story Key: `15-4-selector-de-producto-escalable-crear-con-returnto-sin-precargas-masivas`  
Epic: `15` (Selectores “creables” (crear desde selección) + UX/A11y + performance)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Fecha: 2026-02-22  
Story ID: `15.4`

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (descubrimiento automático: primer story en `backlog`)
- `gatic/docs/ui/creable-selectors.md` (Fase 5: Producto (C + búsqueda) + mapa de implementación + checklist UX/técnico)
- `_bmad-output/implementation-artifacts/15-3-proveedor-creable-desde-formularios-producto-activo-contrato.md` (patrón combobox server-side + IDs únicos + ErrorReporter)
- `_bmad-output/implementation-artifacts/15-2-catalogos-creables-en-formularios-marca-y-ubicacion.md` (patrón creable + scope: no en filtros)
- `_bmad-output/implementation-artifacts/15-1-employeecombobox-creable-crear-empleado-desde-seleccion.md` (A11y multi-instancia + teclado + guardrails)
- `_bmad-output/implementation-artifacts/architecture.md` (stack/patrones: Livewire 3 + Blade + Bootstrap 5; Actions; RBAC server-side; errores con `error_id`)
- `_bmad-output/implementation-artifacts/ux.md` (A11y/teclado; modales/drawers; loaders)
- `docsBmad/project-context.md` + `project-context.md` (reglas “bible”: idioma, RBAC, sin WebSockets, errores con `error_id`)

Código actual (problema a resolver / puntos de extensión):
- Precarga masiva de productos en `mount()` (NO escala):
  - `gatic/app/Livewire/PendingTasks/QuickStockIn.php` (`loadProducts()`)
  - `gatic/app/Livewire/PendingTasks/QuickRetirement.php` (`loadProducts()`)
  - `gatic/app/Livewire/PendingTasks/PendingTaskShow.php` (`loadProducts()`)
- Formularios/Views donde hoy hay `<select>` de productos:
  - `gatic/resources/views/livewire/pending-tasks/quick-stock-in.blade.php`
  - `gatic/resources/views/livewire/pending-tasks/quick-retirement.blade.php`
  - `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php`
- Patrón existente para `returnTo` seguro (evitar open-redirect):
  - `gatic/app/Livewire/Movements/Assets/LoanAssetForm.php` (`sanitizeReturnTo()`)
  - `gatic/resources/views/livewire/inventory/assets/assets-global-index.blade.php` (construcción `path + query`)
- Pantalla de creación de producto (requiere `can:inventory.manage`):
  - Ruta: `inventory.products.create` (`gatic/routes/web.php`)
  - Componente: `gatic/app/Livewire/Inventory/Products/ProductForm.php` (hoy NO soporta `returnTo`)

<!-- template-output: story_requirements -->
## Story

Como **Admin/Editor**,  
quiero **buscar y seleccionar un Producto** desde un selector escalable (server-side) en flujos de Tareas Pendientes / captura rápida,  
y cuando no exista poder **crearlo en una pantalla dedicada y regresar con `returnTo`** para que quede **autoseleccionado**,  
para **mantener la operación fluida** sin precargar miles de productos ni perder contexto.

## Contexto (Epic 15)

Epic 15 busca implementar el patrón “creable” donde el usuario selecciona entidades existentes:
**buscar → sin resultados → CTA crear → RBAC + guardrails → autoselección**, manteniendo A11y (multi-instancia) y performance.

Esta story corresponde a la **Fase 5 — Producto (C + búsqueda + `returnTo`)** en `gatic/docs/ui/creable-selectors.md`.

## Alcance (MVP)

1) **Selector escalable de Producto (reusable)**
- Implementar `ProductCombobox` (Livewire + Blade + Alpine) con **búsqueda server-side**:
  - mínimo **2 caracteres** para buscar
  - máximo **10 resultados**
  - incluir metadato `is_serialized` (join con `categories`)
  - excluir soft-deleted (`products.deleted_at`, `categories.deleted_at`)
  - navegación por teclado (↑/↓/Enter/Esc) y ARIA combobox/listbox
  - IDs únicos por instancia (multi-instancia segura)

2) **Integración en flujos de Pending Tasks (sin precargas masivas)**
- Reemplazar `<select>` de productos y eliminar la precarga completa (`loadProducts()` + `$products = Product::...->get()->toArray()`):
  - `QuickStockIn` (solo `productMode = existing`)
  - `QuickRetirement` (modo `product_quantity`)
  - `PendingTaskShow` (modal “Agregar renglón” y/o donde aplique `productId`)

3) **Crear Producto vía link + `returnTo` (opción UX C)**
- Cuando el `ProductCombobox` muestre “Sin resultados”:
  - mostrar CTA **“Crear producto”** como opción del dropdown (no modal) que navega a `inventory.products.create` con:
    - `returnTo` (path interno + query; sanitized)
    - `prefill` (texto buscado para prellenar el nombre)
- Al guardar el producto en `ProductForm`:
  - si existe `returnTo`, redirigir a `returnTo` agregando `created_id={productId}`
  - si no hay `returnTo`, mantener comportamiento actual (redirigir a `inventory.products.index`)
- Al volver al flujo (returnTo):
  - el `ProductCombobox` debe leer `created_id` (si está presente) y **autoseleccionar** ese Producto
  - mostrar toast informativo (“Producto creado y seleccionado”)

## Fuera de alcance (NO hacer aquí)

- Hacer “creable” dentro de filtros/listados/reportes.
- Introducir dependencias frontend externas (Select2/TomSelect/React/Vue).
- Cambiar el modelo de unicidad de productos (el nombre NO es único; esta story no impone unicidad).
- Refactors masivos fuera de las superficies indicadas.

<!-- template-output: developer_context_section -->
## Dev Notes (contexto para el agente dev)

### ¿Por qué existe esta story?

Hoy, varios componentes Livewire **precargan TODOS los productos** en `mount()` (join con categorías, orderBy, `get()->toArray()`).
Esto funciona con pocos registros, pero **no escala** (memoria, tiempo de render, payload Livewire más grande, UX lenta).

Esta story pide mover la selección a un patrón consistente con Epic 15:
- **búsqueda server-side con límite**
- componente reusable en `app/Livewire/Ui/*`
- A11y/teclado consistente (ARIA combobox/listbox)
- opción “crear” cuando no hay resultados (**pero Productos via link + `returnTo`**, no modal)

### Patrón existente para comboboxes (reusar, no reinventar)

Ya existen comboboxes con guardrails listos para copiar/adaptar:
- `gatic/app/Livewire/Ui/EmployeeCombobox.php`
- `gatic/app/Livewire/Ui/SupplierCombobox.php`
- `gatic/app/Livewire/Ui/BrandCombobox.php`
- `gatic/app/Livewire/Ui/LocationCombobox.php`

Se espera que `ProductCombobox`:
- use `#[Modelable] public ?int $productId = null;` para integrarse con `wire:model`
- genere IDs DOM únicos por instancia (`$this->getId()` → sufijo)
- tenga min chars + limit de resultados
- maneje errores con `ErrorReporter` + toast con `error_id`

### `returnTo` (seguridad y UX)

`returnTo` se usa en el proyecto como **path interno** (no URL completa) para volver al contexto sin open-redirect.
Patrón de sanitización existente (reusar):
- Debe iniciar con `/` y NO con `//`
- No permitir CR/LF
- Longitud razonable (p.ej. <= 2000)

Ver implementaciones existentes:
- `gatic/app/Livewire/Movements/Assets/LoanAssetForm.php#sanitizeReturnTo()`
- `gatic/app/Livewire/Movements/Assets/ReturnAssetForm.php#sanitizeReturnTo()`

Para construir `returnTo` con query de forma segura, ver:
- `gatic/resources/views/livewire/inventory/assets/assets-global-index.blade.php` (arma `path + query` con `parse_url()`)

### Nota importante: Producto NO tiene unicidad por nombre

En DB, `products.name` **no es único**. Por eso el flujo de regreso debe usar `created_id` (id numérico), no solo texto.
El `prefill` sirve para UX, pero la selección final debe depender de `created_id`.

<!-- template-output: technical_requirements -->
## Acceptance Criteria

### AC1 — Se elimina la precarga masiva de Productos

**Given** estoy en los flujos que requieren seleccionar Producto (QuickStockIn/QuickRetirement/PendingTaskShow)  
**When** se monta el componente/pantalla  
**Then** NO se ejecuta una consulta que cargue *todos* los productos a memoria para renderizar el selector  
**And** el selector funciona con búsqueda server-side (con límite de resultados).

> Señal de éxito: desaparecen `loadProducts()` que hacen `Product::query()->...->get()->toArray()` en esos componentes.

### AC2 — Búsqueda server-side con límites (performance)

**Given** estoy usando `ProductCombobox`  
**When** escribo menos de 2 caracteres  
**Then** no se consulta y se muestra un hint “Escribe al menos 2 caracteres”.

**When** escribo 2+ caracteres  
**Then** se consultan sugerencias con un límite de 10 resultados  
**And** se excluyen productos/categorías soft-deleted.

### AC3 — A11y + teclado (multi-instancia)

**Given** hay múltiples instancias de `ProductCombobox` en una vista o modal  
**Then** los IDs ARIA (`input`, `listbox`, `options`, `aria-activedescendant`) son únicos por instancia.

**Given** el dropdown está abierto  
**When** uso teclado  
**Then** `↑/↓` navega opciones, `Enter` selecciona, `Esc` cierra  
**And** el foco permanece consistente en el input.

### AC4 — CTA “Crear producto” (opción C: link + returnTo)

**Given** la búsqueda no devuelve resultados  
**When** el usuario tiene permiso de gestionar inventario (`inventory.manage`)  
**Then** el dropdown muestra opción “Crear producto …”  
**And** al activarla navega a `inventory.products.create` con:
- `returnTo` (path interno + query, sanitized)
- `prefill` (texto buscado)

### AC5 — `ProductForm` soporta `returnTo` + `created_id`

**Given** abrí `inventory.products.create?returnTo=...&prefill=...`  
**Then** el formulario prellena el nombre (si aplica) sin romper validaciones.

**When** guardo un producto nuevo  
**Then** si existe `returnTo` válido, redirige a ese path agregando `created_id={id}`  
**And** si no existe `returnTo`, conserva el redirect actual a `inventory.products.index`.

### AC6 — Autoselección post-create en `ProductCombobox`

**Given** regreso a `returnTo` con `created_id` en query  
**When** el `ProductCombobox` se monta/hidrata  
**Then** selecciona automáticamente el Producto con ese id  
**And** emite toast “Producto creado y seleccionado”.

### AC7 — Errores inesperados con `error_id`

**Given** ocurre un error inesperado en búsqueda o autoselección (DB, excepciones)  
**Then** se reporta con `App\Support\Errors\ErrorReporter`  
**And** la UI muestra un toast con `error_id` sin romper el flujo.

### AC8 — UX long-request (si las queries pueden tardar >3s)

**Given** el `ProductCombobox` o el flujo post-create puede ejecutar queries que en datos reales tarden >3s  
**When** el usuario interactúa (búsqueda / selección / autoselección)  
**Then** el contenedor relevante integra `<x-ui.long-request />` para loader + Cancelar, sin perder el estado del formulario.

## Tasks / Subtasks

- [x] T1 — `SearchProducts` (server-side search)
  - [x] Crear `gatic/app/Actions/Products/SearchProducts.php` con `min chars = 2`, `limit = 10`, join con `categories`, y exclusión soft-delete (`products.deleted_at`, `categories.deleted_at`).
  - [x] Manejar normalización simple del texto (trim + colapsar espacios) y búsqueda por `products.name` (LIKE) con escape seguro.

- [x] T2 — `ProductCombobox` (UI reusable + A11y)
  - [x] Crear `gatic/app/Livewire/Ui/ProductCombobox.php` (`#[Modelable] public ?int $productId`) y `gatic/resources/views/livewire/ui/product-combobox.blade.php`.
  - [x] Implementar ARIA combobox/listbox + teclado (↑/↓/Enter/Esc) + IDs únicos por instancia.
  - [x] Mostrar hint “Escribe al menos 2 caracteres”.
  - [x] Mostrar CTA “Crear producto …” (link a `inventory.products.create`) con `returnTo` + `prefill` solo si `Gate::allows('inventory.manage')`.
  - [x] Autoselección por `created_id` (si viene en query) + toast “Producto creado y seleccionado”.
  - [x] Errores inesperados: `ErrorReporter` + toast con `error_id`.

- [x] T3 — `ProductForm` soporta `returnTo` + `prefill` + redirect con `created_id`
  - [x] Agregar lectura y sanitización de `returnTo` (reusar patrón de `sanitizeReturnTo()` existente) en `gatic/app/Livewire/Inventory/Products/ProductForm.php`.
  - [x] Soportar `prefill` en create (prellenar `name` sin romper validación/normalización).
  - [x] En create exitoso: si `returnTo` válido, redirigir a ese path agregando `created_id={productId}`; si no, mantener redirect actual.

- [x] T4 — Remover precargas masivas e integrar en Pending Tasks
  - [x] `gatic/app/Livewire/PendingTasks/QuickStockIn.php` + `gatic/resources/views/livewire/pending-tasks/quick-stock-in.blade.php`: reemplazar `<select>` de producto por `ProductCombobox` (solo modo existing) y eliminar `$products`/`loadProducts()`.
  - [x] `gatic/app/Livewire/PendingTasks/QuickRetirement.php` + `gatic/resources/views/livewire/pending-tasks/quick-retirement.blade.php`: reemplazar `<select>` por `ProductCombobox` (modo product_quantity) y eliminar `$products`/`loadProducts()`.
  - [x] `gatic/app/Livewire/PendingTasks/PendingTaskShow.php` + `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php`: reemplazar selector de producto donde aplique y eliminar `$products`/`loadProducts()`.

- [x] T5 — Tests / regresiones
  - [x] Agregar `ProductComboboxTest` (min chars, limit 10, soft-delete, CTA create, multi-instancia IDs, autoselección `created_id`).
  - [x] Agregar/ajustar tests para `ProductForm` (`prefill`, `returnTo` sanitizado, redirect con `created_id`).
  - [x] Asegurar que `gatic/tests/Feature/PendingTasks/Fp03QuickCaptureTest.php` y suite de Pending Tasks sigan pasando (actualizar solo si el cambio afecta comportamiento).

<!-- template-output: architecture_compliance -->
## Architecture Compliance (guardrails obligatorios)

- Stack fijo: **Laravel 11 + Livewire 3 + Blade + Bootstrap 5** (sin migrar stack, sin frameworks JS externos).
- Sin WebSockets: no introducir realtime; este selector debe funcionar con requests Livewire normales.
- Código/DB/rutas: **identificadores en inglés**; copy/UI en **español**.
- Autorización: server-side (Gates/Policies). No confiar solo en ocultar botones.
  - `ProductCombobox`: `Gate::authorize('inventory.manage')` en acciones sensibles.
  - Crear Producto: ya está protegido por ruta `can:inventory.manage` (y debe seguirlo).
- Evitar helpers globales: si se factoriza sanitización `returnTo`, ponerlo en `app/Support/*`.
- Errores: siempre best-effort con `error_id` (toast y/o alert), detalle técnico solo Admin.

<!-- template-output: library_framework_requirements -->
## Library / Framework Requirements

- Livewire 3:
  - usar `#[Modelable]` para enlazar `productId` desde padres (`wire:model`)
  - mantener props públicas pequeñas (no listas gigantes)
- Alpine.js (mínimo) para UX de combobox (teclado/ARIA), alineado a `EmployeeCombobox`.
- Bootstrap 5 + Bootstrap Icons para estilos y affordances.
- Consultas:
  - sugerencias via Action (p.ej. `app/Actions/Products/SearchProducts.php`) con `limit(10)` y filtros de soft-delete.

<!-- template-output: file_structure_requirements -->
## File / Component Map (expected touch points)

### Nuevos (esperados)
- `gatic/app/Livewire/Ui/ProductCombobox.php`
- `gatic/resources/views/livewire/ui/product-combobox.blade.php`
- `gatic/app/Actions/Products/SearchProducts.php`

### Modificaciones (esperadas)
- `gatic/app/Livewire/PendingTasks/QuickStockIn.php` (remover precarga; adaptar `resolveProduct()`)
- `gatic/resources/views/livewire/pending-tasks/quick-stock-in.blade.php` (reemplazar `<select>` por `ProductCombobox`)
- `gatic/app/Livewire/PendingTasks/QuickRetirement.php` (remover precarga)
- `gatic/resources/views/livewire/pending-tasks/quick-retirement.blade.php` (reemplazar `<select>`)
- `gatic/app/Livewire/PendingTasks/PendingTaskShow.php` (remover precarga; usar `ProductCombobox` donde aplique)
- `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php` (integración UI)
- `gatic/app/Livewire/Inventory/Products/ProductForm.php` (agregar `returnTo` + `prefill` + redirect con `created_id`)
- `gatic/resources/views/livewire/inventory/products/product-form.blade.php` (si requiere UI para hint de return)

### Nota de `returnTo`
Si se crea un helper/Support para sanitizar y/o para agregar `created_id` a un path con query, ubicar en:
- `gatic/app/Support/Ui/*` o `gatic/app/Support/Http/*` (evitar helpers globales)

<!-- template-output: testing_requirements -->
## Testing Requirements

### Feature tests (mínimos)

1) **`ProductCombobox` UI/behavior**
- Nuevo: `gatic/tests/Feature/Inventory/ProductComboboxTest.php` (o `tests/Feature/Ui/ProductComboboxTest.php`, según convención del repo).
- Cobertura mínima:
  - hint “min 2 chars”
  - resultados limitados a 10
  - exclusión de soft-deleted
  - render de CTA “Crear producto …” con `returnTo` + `prefill` cuando no hay resultados
  - multi-instancia: IDs únicos (al menos un assert de que cambian con dos instancias)

2) **`ProductForm` return flow**
- Nuevo/extendido: test que valide:
  - `prefill` llena `name`
  - `returnTo` se sanitiza (rechaza URL absoluta / `//` / CRLF)
  - al crear: redirect a `returnTo` con `created_id`
  - si no hay `returnTo`: redirect default `inventory.products.index`

3) **Regresión soft-delete**
- Asegurar que sugerencias y selección post-create no “reviven” productos en papelera (no incluir `onlyTrashed`).

### Notas
- No romper suite existente de Pending Tasks:
  - `gatic/tests/Feature/PendingTasks/Fp03QuickCaptureTest.php` y otras pruebas de `PendingTasks/*`.
- Mantener tests deterministas; usar `RefreshDatabase`.

<!-- template-output: previous_story_intelligence -->
## Previous Story Intelligence (reusar patrones ya probados)

- Story 15.3 (`SupplierCombobox`) estableció un patrón sólido para combobox server-side:
  - `#[Modelable]` para binding
  - `MIN_SEARCH_LENGTH = 2` y `MAX_RESULTS = 10`
  - IDs únicos por instancia usando sufijo desde `$this->getId()`
  - “min chars” vs “sin resultados”
  - errores con `ErrorReporter` + toast con `error_id`
  - separar query a `app/Actions/*/Search*` (testable)

- Story 15.1 (`EmployeeCombobox`) dejó lecciones de A11y/multi-instancia:
  - nunca usar IDs estáticos (colisionan)
  - `Esc` debe cerrar sin “perder foco”
  - la opción “crear” debe ser navegable con teclado

- `returnTo` ya existe y se sanitiza en movimientos de activos.
  - Evitar inventar un patrón distinto; si se refactoriza, hacerlo hacia un `Support` reutilizable.

<!-- template-output: git_intelligence_summary -->
## Git Intelligence (patrones recientes en el repo)

Commits más recientes (contexto):
- `c54373d` feat: implementar selector creable de proveedor (15.3)
- `36070d4` feat(inventory): implement story 15-2 creable brand/location selectors (15.2)
- `cde817e` fix(locks): close QA gaps in story 15.1 (15.1)

Patrones a seguir (observados en estos commits):
- Componentes UI reutilizables en `gatic/app/Livewire/Ui/*` + view en `gatic/resources/views/livewire/ui/*`.
- Lógica de búsqueda/creación en `gatic/app/Actions/*`.
- Tests feature dedicados en `gatic/tests/Feature/*` por componente/alcance.
- A11y multi-instancia e IDs DOM únicos como requisito real (ya hubo fixes QA).

<!-- template-output: latest_tech_information -->
## Latest Tech Information (relevante para no implementar desactualizado)

- Versiones actuales del repo (fuente: `gatic/composer.json` + `gatic/composer.lock`):
  - `laravel/framework`: **v11.47.0** (lock)
  - `livewire/livewire`: **v3.7.10** (lock)
- Seguridad (Livewire v3): existe advisory de ejecución remota en algunas versiones; mantener Livewire actualizado dentro de v3 y **no fijar** versiones antiguas.

Regla para esta story: **NO “upgradear por upgradear”**. Implementar usando APIs existentes de Laravel 11 / Livewire 3 del repo.

<!-- template-output: project_context_reference -->
## Project Context Reference (must-read)

- Bible/reglas: `docsBmad/project-context.md`, `project-context.md`
- Creable selectors (contrato + mapa + checklist): `gatic/docs/ui/creable-selectors.md`
- UX (A11y/teclado/modales/loaders): `_bmad-output/implementation-artifacts/ux.md`
- Arquitectura/patrones (Actions/RBAC/stack): `_bmad-output/implementation-artifacts/architecture.md`

<!-- template-output: story_completion_status -->
## Story Completion Status

- Status: **done**
- Completion note: "Implementación completada: ProductCombobox server-side con CTA crear+returnTo, ProductForm con prefill/redirect `created_id`, integración en Pending Tasks sin precargas masivas. Post-review + re-QA Playwright: AC2/AC7 validados (sin errores JS, hint min 2 chars correcto). Nota: evidencia UI en PendingTaskShow depende de seeds; el componente ya está integrado."

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `_bmad/core/tasks/workflow.xml`
- `_bmad/bmm/workflows/4-implementation/dev-story/workflow.yaml`
- `_bmad/bmm/workflows/4-implementation/dev-story/instructions.xml`

### Completion Notes List

- ✅ Implementado `SearchProducts` para búsqueda server-side de productos (mínimo 2 caracteres, máximo 10 resultados, exclusión de soft-delete, orden por prefijo/contiene, escape LIKE).
- ✅ Creado `ProductCombobox` reusable con `#[Modelable]`, ARIA/teclado, IDs únicos por instancia, CTA crear con `prefill`+`returnTo`, autoselección por `created_id` y manejo de errores con `error_id`.
- ✅ Agregado soporte `returnTo`/`prefill` en `ProductForm` y redirección post-create con `created_id` usando helper reusable `ReturnToPath`.
- ✅ Post-review: `ReturnToPath::browserCurrent()` y fallback por `Referer` para que `returnTo` y `created_id` funcionen también cuando el combobox se monta en requests Livewire (modales).
- ✅ Post-review: evitar consultar sugerencias si el dropdown está cerrado; reset de highlight al cambiar búsqueda para consistencia A11y.
- ✅ Eliminadas precargas masivas de productos en `QuickStockIn`, `QuickRetirement` y `PendingTaskShow`; integración con `ProductCombobox`.
- ✅ Pruebas nuevas/ajustadas: `ProductComboboxTest`, `ProductsTest`, `Fp03QuickCaptureTest`.
- ✅ Validaciones ejecutadas: `php artisan test` (869 passed), `./vendor/bin/pint --test` (PASS), `./vendor/bin/phpstan analyse --no-progress` (falla por errores preexistentes fuera del alcance de esta story).

### File List

- _bmad-output/implementation-artifacts/sprint-status.yaml
- gatic/app/Actions/Products/SearchProducts.php
- gatic/app/Livewire/Ui/ProductCombobox.php
- gatic/app/Support/Ui/ReturnToPath.php
- gatic/app/Livewire/Inventory/Products/ProductForm.php
- gatic/resources/views/livewire/ui/product-combobox.blade.php
- gatic/resources/views/livewire/inventory/products/product-form.blade.php
- gatic/app/Livewire/PendingTasks/QuickStockIn.php
- gatic/resources/views/livewire/pending-tasks/quick-stock-in.blade.php
- gatic/app/Livewire/PendingTasks/QuickRetirement.php
- gatic/resources/views/livewire/pending-tasks/quick-retirement.blade.php
- gatic/app/Livewire/PendingTasks/PendingTaskShow.php
- gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php
- gatic/tests/Feature/Inventory/ProductComboboxTest.php
- gatic/tests/Feature/Inventory/ProductsTest.php
- gatic/tests/Feature/PendingTasks/Fp03QuickCaptureTest.php

### Change Log

- 2026-02-22: Implementada Story 15.4 completa (T1–T5), con selector escalable de productos, flujo crear+returnTo, integración en Pending Tasks, pruebas y validaciones.
- 2026-02-22: Post-code-review: fixes de `returnTo`/`created_id` en requests Livewire (fallback `Referer`), optimización de queries del combobox y mejora A11y de highlight al cambiar búsqueda.

