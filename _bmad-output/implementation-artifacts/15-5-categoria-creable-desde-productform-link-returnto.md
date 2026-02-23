<!-- template-output: story_header -->
# Story 15.5: Categoría creable desde ProductForm (link + `returnTo`)

Status: done

Story Key: `15-5-categoria-creable-desde-productform-link-returnto`  
Epic: `15` (Selectores “creables” (crear desde selección) + UX/A11y + performance)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Fecha: 2026-02-22  
Story ID: `15.5`

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (descubrimiento automático: primer story en `backlog`)
- `gatic/docs/ui/creable-selectors.md` (Fase 6: Categoría (C) + contrato `returnTo`)
- `_bmad-output/implementation-artifacts/15-4-selector-de-producto-escalable-crear-con-returnto-sin-precargas-masivas.md` (patrón `ReturnToPath` + `created_id`)
- `gatic/routes/web.php` (rutas `inventory.products.create` y `catalogs.categories.create`)
- `gatic/app/Livewire/Inventory/Products/ProductForm.php` + `gatic/resources/views/livewire/inventory/products/product-form.blade.php` (selector actual de categoría)
- `gatic/app/Livewire/Catalogs/Categories/CategoryForm.php` + `gatic/resources/views/livewire/catalogs/categories/category-form.blade.php` (pantalla dedicada de categoría)
- `gatic/app/Support/Ui/ReturnToPath.php` (sanitización `returnTo` + merge de query)
- `_bmad-output/implementation-artifacts/architecture.md` (stack/patrones: Livewire 3 + Blade + Bootstrap 5; RBAC server-side; sin WebSockets)
- `_bmad-output/implementation-artifacts/ux.md` (A11y, loaders, copy y feedback)
- `docsBmad/project-context.md` + `project-context.md` (reglas “bible”: idioma, RBAC, sin WebSockets, errores prod con `error_id`)

Código actual (problema a resolver / puntos de extensión):
- `ProductForm` precarga categorías y solo permite seleccionar existente:
  - `gatic/app/Livewire/Inventory/Products/ProductForm.php` (carga `$categories` en `mount()`)
  - `gatic/resources/views/livewire/inventory/products/product-form.blade.php` (`<select wire:model.live="category_id">`)
- La pantalla de creación/edición de categorías existe, pero al guardar siempre vuelve a índice:
  - Ruta: `catalogs.categories.create` (`gatic/routes/web.php`)
  - Componente: `gatic/app/Livewire/Catalogs/Categories/CategoryForm.php`
  - Vista: `gatic/resources/views/livewire/catalogs/categories/category-form.blade.php`
- Ya existe helper de `returnTo` seguro (reusar, no reinventar):
  - `gatic/app/Support/Ui/ReturnToPath.php`

<!-- template-output: story_requirements -->
## Story

Como **Admin/Editor**,  
quiero **crear una Categoría** desde el formulario de Producto cuando no exista (usando un link seguro + `returnTo`),  
y al regresar que la categoría nueva quede **autoseleccionada**,  
para **mantener el flujo** de alta de Producto sin perder contexto y sin romper RBAC.

## Contexto (Epic 15)

Epic 15 busca estandarizar el patrón “creable”:
**buscar/seleccionar → sin resultados → CTA crear → RBAC + anti-duplicados → autoselección**,
manteniendo consistencia UX y evitando errores típicos.

Esta story corresponde a **Fase 6 — Categoría (C)** en `gatic/docs/ui/creable-selectors.md`.

## Alcance (MVP)

1) **CTA “Crear categoría” desde `ProductForm` (link + `returnTo`)**
- En `inventory.products.create` (modo crear):
  - agregar un link/CTA “Crear categoría” cerca del `<select>` de categoría
  - visible solo si `Gate::allows('catalogs.manage')`
  - navega a `catalogs.categories.create` con:
    - `returnTo` = URL interna actual de `ProductForm` (path + query)
    - excluir `created_id` al construir `returnTo` para evitar loops

2) **`CategoryForm` soporta `returnTo`**
- Si se abre con `?returnTo=...` válido:
  - mostrar hint “Al guardar volverás al formulario anterior y la categoría quedará seleccionada”
  - `Volver`/`Cancelar` regresan a `returnTo`
- Al crear con éxito:
  - si existe `returnTo` válido, redirigir a `returnTo` agregando `created_id={categoryId}`
  - si NO hay `returnTo`, mantener redirect actual a `catalogs.categories.index`

3) **Autoselección post-create en `ProductForm`**
- Al volver a `ProductForm` con `created_id` en query:
  - seleccionar automáticamente esa categoría (`category_id`)
  - recalcular `categoryIsSerialized` y limpiar `qty_total/low_stock_threshold` si corresponde
  - mostrar feedback (“Categoría creada y seleccionada”)

4) **Guardrails: seguridad, soft-delete, regresiones**
- `returnTo` debe ser un **path interno** sanitizado (sin open-redirect; sin CR/LF; longitud razonable)
- Si `created_id` apunta a una categoría soft-deleted o inexistente:
  - NO seleccionar y mostrar mensaje claro (sin 500)
- Unicidad de categorías:
  - `categories.name` es UNIQUE incluyendo soft-deleted
  - si existe en Papelera, la UX debe guiar a restaurar (no re-crear)

## Fuera de alcance (NO hacer aquí)

- Convertir el selector de categoría a combobox async (por ahora se mantiene `<select>`).
- Hacer “creable” desde filtros/listados/reportes.
- Persistir el draft del `ProductForm` al navegar (sin localStorage/session draft en esta story).
- Cambiar reglas de unicidad/soft-delete de `Category`.

<!-- template-output: developer_context_section -->
## Dev Notes (contexto para el agente dev)

### ¿Por qué existe esta story?

Hoy, al crear un Producto, la categoría se selecciona desde un `<select>` con categorías precargadas.
Si el usuario necesita una categoría nueva (y Categoría no es “name-only”: incluye `is_serialized`, `requires_asset_tag` y `default_useful_life_months`),
se ve obligado a salir del flujo y luego volver manualmente, perdiendo contexto.

Esta story aplica la opción UX **(C) Link + `returnTo`** (según `gatic/docs/ui/creable-selectors.md`) para:
- abrir la pantalla dedicada de creación de categoría
- regresar al `ProductForm`
- autoseleccionar la categoría recién creada

### Patrón a reusar: `ReturnToPath` + `created_id`

Este repo ya tiene un patrón “C” implementado en Story 15.4.
Reusar el helper y el contrato existente:
- Sanitizar: `ReturnToPath::sanitize()`
- Construir `returnTo` actual (path + query): `ReturnToPath::current([...])`
- Agregar query sin romper encoding: `ReturnToPath::withQuery($path, ['created_id' => ...])`

### `returnTo` anidado (caso real)

Un flujo típico puede ser:
1. Pending Task → abrir `inventory.products.create?returnTo=/pending-tasks?...&prefill=...`
2. Dentro de `ProductForm`, el usuario hace clic en “Crear categoría”
3. `catalogs.categories.create?returnTo=/inventory/products/create?returnTo=...&prefill=...`
4. Al guardar categoría → regresar a `ProductForm` con `created_id=<categoryId>`

Regla: el `returnTo` hacia `CategoryForm` debe apuntar al **URL del `ProductForm` actual** (incluyendo query),
para no perder el `returnTo` original (y así permitir que, después de crear el Producto, se regrese al flujo inicial).

### Soft-delete + unique en categorías

- `categories.name` es UNIQUE en DB **incluyendo** registros soft-deleted (ver `gatic/database/migrations/2025_12_31_000002_create_categories_table.php`).
- Si el usuario intenta crear una categoría con nombre que existe en Papelera:
  - el create debe fallar con mensaje claro
  - la UX debe guiar a restaurar desde `catalogs.trash.index`

### Errores y feedback

- Evitar 500: usar validación + mensajes claros.
- Para fallos inesperados, seguir patrón de errores del proyecto (`error_id`) y no filtrar detalle técnico a no-Admin.

<!-- template-output: technical_requirements -->
## Acceptance Criteria

### AC1 — CTA “Crear categoría” visible y con RBAC correcto

**Given** estoy en `inventory.products.create`  
**When** tengo permiso `catalogs.manage`  
**Then** veo un CTA “Crear categoría” junto al selector de categoría.

**Given** NO tengo `catalogs.manage`  
**Then** NO veo el CTA y el servidor seguiría bloqueando `/catalogs/*` (defensa en profundidad).

### AC2 — Link seguro con `returnTo` interno (sin open-redirect)

**Given** estoy en `ProductForm` (modo crear)  
**When** hago clic en “Crear categoría”  
**Then** navego a `catalogs.categories.create` con `returnTo` como **path interno** (no URL absoluta)  
**And** el `returnTo` excluye `created_id` para evitar loops.

### AC3 — `CategoryForm` respeta `returnTo` en create

**Given** abrí `catalogs.categories.create?returnTo=/inventory/products/create?...`  
**When** cancelo / vuelvo  
**Then** regreso a `returnTo`.

**When** guardo una categoría nueva  
**Then** si `returnTo` es válido, redirige a `returnTo` agregando `created_id={categoryId}`  
**And** si `returnTo` no existe o es inválido, mantiene el comportamiento actual (redirigir a `catalogs.categories.index`).

### AC4 — Autoselección post-create en `ProductForm`

**Given** regreso a `ProductForm` con `created_id`  
**When** el componente se monta  
**Then** `category_id` se setea automáticamente a esa categoría (si existe y NO está soft-deleted)  
**And** el campo “Tipo” se actualiza según `is_serialized`  
**And** se muestra feedback “Categoría creada y seleccionada”.

### AC5 — Guardrails soft-delete / duplicados

**Given** una categoría existe pero está soft-deleted  
**When** intento crear otra con el mismo nombre  
**Then** el sistema bloquea por unique y muestra un mensaje que guíe a restaurar desde Papelera.

**Given** `created_id` apunta a una categoría inexistente o soft-deleted  
**Then** `ProductForm` NO la selecciona y muestra warning (sin 500).

### AC6 — UX long-request (si aplica)

**Given** la operación `save` puede tardar >3s (datos reales)  
**Then** se mantiene el patrón `<x-ui.long-request target="save" />` (ya existe en `ProductForm` y `CategoryForm`).

## Tasks / Subtasks

- [x] T1 — `CategoryForm` + vista soportan `returnTo` (AC3)
  - [x] Leer `returnTo` desde query en `mount()` y sanitizar con `ReturnToPath::sanitize()`.
  - [x] En create exitoso: si `returnTo` válido, `redirect()->to(ReturnToPath::withQuery(returnTo, ['created_id' => $id]))`.
  - [x] Ajustar `Volver`/`Cancelar` y copy informativo en la vista para respetar `returnTo`.

- [x] T2 — CTA “Crear categoría” en `ProductForm` (AC1, AC2)
  - [x] Mostrar CTA solo si `Gate::allows('catalogs.manage')`.
  - [x] Link a `route('catalogs.categories.create', ['returnTo' => ReturnToPath::current(['created_id'])])` (o equivalente desde el componente).

- [x] T3 — `ProductForm` autoselección por `created_id` (AC4, AC5)
  - [x] Leer `created_id` en `mount()` (modo crear), validar int y verificar existencia con `whereNull('deleted_at')`.
  - [x] Setear `category_id` y recalcular `categoryIsSerialized` (y limpiar qty/threshold si cambió a serializado).
  - [x] Mostrar feedback de éxito (toast o alert inline, consistente con el repo).

- [x] T4 — Tests / regresiones (AC5)
  - [x] `gatic/tests/Feature/Catalogs/CategoriesTest.php`: create con `returnTo` redirige con `created_id`; invalid `returnTo` cae a index.
  - [x] `gatic/tests/Feature/Inventory/ProductsTest.php`: `ProductForm` autoselecciona categoría con `created_id`.
  - [x] Regresión soft-delete: `created_id` apuntando a categoría soft-deleted NO se selecciona.

<!-- template-output: architecture_compliance -->
## Architecture Compliance (guardrails obligatorios)

- Stack fijo: **Laravel 11 + Livewire 3 + Blade + Bootstrap 5**.
- Sin WebSockets: no introducir realtime; si se requiere actualización, usar patrones existentes (polling cuando aplique).
- Identificadores (código/DB/rutas) en inglés; copy/UI en español.
- Autorización server-side obligatoria:
  - `ProductForm`: ya requiere `inventory.manage`.
  - `CategoryForm`: requiere `catalogs.manage` (middleware + `Gate::authorize()` en componente).
- Evitar helpers globales; reusar `App\Support\Ui\ReturnToPath`.
- Errores inesperados: UX humana + `error_id`; detalle técnico solo Admin.

<!-- template-output: library_framework_requirements -->
## Library / Framework Requirements

- Livewire 3 + Blade (MPA): mantener la implementación en Livewire; no introducir SPA.
- Bootstrap 5 + Bootstrap Icons (consistencia UI).
- `ReturnToPath`:
  - usar `sanitize/current/withQuery` para evitar open-redirect y bugs de query.
- No agregar librerías externas (Select2/TomSelect/React/Vue) para esto.

<!-- template-output: file_structure_requirements -->
## File / Component Map (expected touch points)

### Modificaciones (esperadas)
- `gatic/app/Livewire/Inventory/Products/ProductForm.php` (consumir `created_id`, recalcular tipo, feedback)
- `gatic/resources/views/livewire/inventory/products/product-form.blade.php` (CTA “Crear categoría” + hint)
- `gatic/app/Livewire/Catalogs/Categories/CategoryForm.php` (soporte `returnTo` en create)
- `gatic/resources/views/livewire/catalogs/categories/category-form.blade.php` (volver/cancelar a `returnTo` + copy)
- `gatic/tests/Feature/Catalogs/CategoriesTest.php` (returnTo redirect)
- `gatic/tests/Feature/Inventory/ProductsTest.php` (autoselección por `created_id` + soft-delete)

### Reuso (no crear duplicados)
- `gatic/app/Support/Ui/ReturnToPath.php`

<!-- template-output: testing_requirements -->
## Testing Requirements

### Feature tests (mínimos)

1) **`CategoryForm` return flow**
- En `gatic/tests/Feature/Catalogs/CategoriesTest.php`:
  - create con `returnTo` válido redirige a `returnTo` con `created_id={categoryId}`
  - create con `returnTo` inválido (URL absoluta / `//` / CRLF) mantiene redirect a `catalogs.categories.index`
  - (opcional) cancelar/vinculo “Volver” usa `returnTo` cuando exista

2) **`ProductForm` autoselección de categoría**
- En `gatic/tests/Feature/Inventory/ProductsTest.php`:
  - `Livewire::withQueryParams(['created_id' => $category->id])` preselecciona `category_id`
  - `created_id` apuntando a soft-deleted NO selecciona y muestra feedback seguro

3) **Regresión soft-delete**
- Asegurar que la carga de categorías para el select excluye `deleted_at` (ya existe filtro; el test debe capturarlo explícitamente).

### Notas
- Mantener tests deterministas; usar `RefreshDatabase`.

<!-- template-output: previous_story_intelligence -->
## Previous Story Intelligence (reusar patrones ya probados)

- Story 15.4 implementó el patrón `returnTo` + `created_id` y dejó el helper reutilizable:
  - `gatic/app/Support/Ui/ReturnToPath.php`
  - Tests existentes de `ProductForm` para `returnTo/prefill` en `gatic/tests/Feature/Inventory/ProductsTest.php`

- Catálogos (Epic 2) ya establecieron reglas clave para Categorías:
  - `Category::normalizeName()` + unique en DB (`categories.name`) incluso con soft-delete
  - UX esperada cuando existe en Papelera: restaurar, no duplicar

<!-- template-output: git_intelligence_summary -->
## Git Intelligence (patrones recientes en el repo)

Commits más recientes (contexto):
- `50a8d71` feat(inventory): complete story 15.4 product combobox flow
- `c54373d` feat: implementar selector creable de proveedor en formularios inventario
- `36070d4` feat(inventory): implement story 15-2 creable brand/location selectors
- `cde817e` fix(locks): close QA gaps in story 15.1 (errors, aria, locale)
- `d21f33c` feat(catalogs): armoniza jerarquia y UX en vistas de catalogos

Patrones a seguir (observados):
- Helpers reutilizables en `gatic/app/Support/*` (p.ej. `ReturnToPath`).
- Livewire components como unidad principal (route → Livewire).
- Feature tests específicos para cada flujo (`tests/Feature/...`).
- Guardrails explícitos para A11y/RBAC/soft-delete.

<!-- template-output: latest_tech_information -->
## Latest Tech Information (relevante para no implementar desactualizado)

- Versiones actuales del repo (fuente: `gatic/composer.lock`):
  - `laravel/framework`: **v11.47.0**
  - `livewire/livewire`: **v3.7.10**

Notas:
- **No “upgradear por upgradear” en esta story.** Implementar con APIs existentes de Laravel 11 / Livewire 3 del repo.
- Laravel 11 tiene ventana de seguridad cercana a fin de soporte; planear upgrade mayor fuera de esta story si aplica.
- Antes de implementar, correr `composer audit` para revisar advisories en dependencias.

<!-- template-output: project_context_reference -->
## Project Context Reference (must-read)

- Bible/reglas: `docsBmad/project-context.md`, `project-context.md`
- Creable selectors (contrato + fases + checklist): `gatic/docs/ui/creable-selectors.md`
- UX (feedback, A11y, loaders): `_bmad-output/implementation-artifacts/ux.md`
- Arquitectura/patrones (Actions/RBAC/stack/errores): `_bmad-output/implementation-artifacts/architecture.md`
- Story previa relevante: `_bmad-output/implementation-artifacts/15-4-selector-de-producto-escalable-crear-con-returnto-sin-precargas-masivas.md`

<!-- template-output: story_completion_status -->
## Story Completion Status

- Status: **done**
- Completion note: "Code review adversarial aplicado; ACs validados y gaps corregidos (copy AC5 + tests adicionales)."

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `_bmad/core/tasks/workflow.xml`
- `_bmad/bmm/workflows/4-implementation/code-review/workflow.yaml`
- `_bmad/bmm/workflows/4-implementation/code-review/instructions.xml`
- `_bmad/bmm/workflows/4-implementation/code-review/checklist.md`
- `_bmad/bmm/workflows/4-implementation/create-story/workflow.yaml`
- `_bmad/bmm/workflows/4-implementation/create-story/instructions.xml`
- `_bmad/bmm/workflows/4-implementation/create-story/checklist.md`
- `_bmad/bmm/workflows/4-implementation/dev-story/workflow.yaml`
- `_bmad/bmm/workflows/4-implementation/dev-story/instructions.xml`
- `_bmad/bmm/workflows/4-implementation/dev-story/checklist.md`

### Completion Notes List

- ✅ `CategoryForm`: copy AC5 mejorado cuando el duplicado existe en Papelera (guía a restaurar).
- ✅ Tests: `returnTo` inválido cubre URL absoluta, `//` y CRLF.
- ✅ Tests: `ProductForm` oculta CTA “Crear categoría” si `catalogs.manage` es denegado.
- ✅ `.gitignore` ignora artefactos locales `.playwright-cli/`.
- ✅ Validaciones ejecutadas (Docker): `php artisan test` (PASS), `./vendor/bin/pint --test` (PASS).
- ⚠️ `./vendor/bin/phpstan analyse` falla con errores preexistentes en el repo (no relacionados a esta story).
- ✅ Tracking actualizado en `_bmad-output/implementation-artifacts/sprint-status.yaml` → `done`.

### File List

- _bmad-output/implementation-artifacts/15-5-categoria-creable-desde-productform-link-returnto.md
- _bmad-output/implementation-artifacts/sprint-status.yaml
- .gitignore
- gatic/app/Livewire/Catalogs/Categories/CategoryForm.php
- gatic/resources/views/livewire/catalogs/categories/category-form.blade.php
- gatic/app/Livewire/Inventory/Products/ProductForm.php
- gatic/resources/views/livewire/inventory/products/product-form.blade.php
- gatic/tests/Feature/Catalogs/CategoriesTest.php
- gatic/tests/Feature/Inventory/ProductsTest.php
