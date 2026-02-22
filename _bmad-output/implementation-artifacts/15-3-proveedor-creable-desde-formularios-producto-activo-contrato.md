<!-- template-output: story_header -->
# Story 15.3: Proveedor creable (crear proveedor desde selección)

Status: done

Story Key: `15-3-proveedor-creable-desde-formularios-producto-activo-contrato`  
Epic: `15` (Selectores “creables” (crear desde selección) + UX/A11y + anti-duplicados + performance)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Fecha: 2026-02-21  
Story ID: `15.3`

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (descubrimiento de siguiente story en backlog)
- `gatic/docs/ui/creable-selectors.md` (contrato base “creable”, hallazgos y mapa de implementación; Fase 3 = Proveedor (B))
- `_bmad-output/implementation-artifacts/15-2-catalogos-creables-en-formularios-marca-y-ubicacion.md` (patrón creable (A) ya implementado + guardrails RBAC/SoftDeletes/1062)
- `_bmad-output/implementation-artifacts/15-1-employeecombobox-creable-crear-empleado-desde-seleccion.md` (patrón creable (B) con modal + A11y multi-instancia)
- `_bmad-output/implementation-artifacts/architecture.md` (stack/patrones: Livewire 3 + Blade + Bootstrap 5; Actions; RBAC server-side; errores con `error_id`)
- `docsBmad/project-context.md` + `project-context.md` (reglas “bible”: idioma, RBAC, sin WebSockets, errores con `error_id`)
- Código actual:
  - `gatic/app/Models/Supplier.php` (SoftDeletes + `normalizeName`)
  - `gatic/app/Livewire/Catalogs/Suppliers/SuppliersIndex.php` (CRUD de proveedor + `catalogs.manage` + 1062)
  - `gatic/resources/views/livewire/catalogs/suppliers/suppliers-index.blade.php` (UI actual del catálogo)
  - `gatic/app/Livewire/Inventory/Products/ProductForm.php` + `gatic/resources/views/livewire/inventory/products/product-form.blade.php` (selector actual `supplier_id`)
  - `gatic/app/Livewire/Inventory/Contracts/ContractForm.php` + `gatic/resources/views/livewire/inventory/contracts/contract-form.blade.php` (selector actual `supplier_id`)
  - `gatic/app/Livewire/Inventory/Assets/AssetForm.php` + `gatic/resources/views/livewire/inventory/assets/asset-form.blade.php` (selector actual `warrantySupplierId`)
  - `gatic/app/Livewire/Inventory/Contracts/ContractsIndex.php` (filtro de proveedores: NO debe ser creable)

Superficies donde se selecciona Proveedor hoy (impacto):
- `gatic/resources/views/livewire/inventory/products/product-form.blade.php` (`supplier_id`)
- `gatic/resources/views/livewire/inventory/contracts/contract-form.blade.php` (`supplier_id`)
- `gatic/resources/views/livewire/inventory/assets/asset-form.blade.php` (`warrantySupplierId`)
- `gatic/resources/views/livewire/inventory/contracts/contracts-index.blade.php` (filtros; no creable)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

<!-- template-output: story_requirements -->
## Story

Como **Admin/Editor**,  
quiero **crear un proveedor** desde el selector de proveedor cuando no hay resultados (sin salir del formulario),  
para **completar altas/ediciones sin fricción** y evitar capturas incompletas o duplicadas.

## Epic Context (Epic 15, resumido)

Objetivo Epic 15: habilitar patrón “creable” en selectores críticos para bajar fricción:
**buscar → sin resultados → CTA crear → anti-duplicados + RBAC → autoselección**, manteniendo A11y (multi-instancia) y performance.

Historias relacionadas (para contexto cruzado):
- 15.1: `EmployeeCombobox` creable (modal) + A11y multi-instancia.
- 15.2: catálogos creables inline (Marca, Ubicación) + SoftDeletes + 1062.
- 15.3 (esta story): **Proveedor creable (modal)** desde formularios de Producto/Contrato/Activo.

## Alcance (MVP)

- Convertir los selectores actuales de proveedor a un **combobox con búsqueda server-side**.
- Al no encontrar resultados:
  - mostrar CTA **“Crear proveedor”** (solo si el usuario tiene `catalogs.manage`),
  - abrir un **modal** (UX opción B) para capturar campos mínimos.
- Al guardar con éxito:
  - crear el `Supplier` respetando RBAC server-side,
  - **autoseleccionar** el proveedor recién creado en el combobox,
  - cerrar modal y dropdown,
  - mostrar toast de éxito.
- Anti-duplicados:
  - normalización de `name`,
  - pre-check exacto,
  - manejo race-safe de MySQL 1062 para re-selección del existente.
- SoftDeletes:
  - si existe exacto en Papelera, bloquear creación y mostrar CTA “Ir a Papelera”.

Formularios transaccionales a cubrir (impacto directo):
- Producto: `supplier_id` en `ProductForm`.
- Contrato: `supplier_id` en `ContractForm`.
- Activo (garantía): `warrantySupplierId` en `AssetForm`.

## Fuera de alcance (NO hacer aquí)

- Hacer “creable” dentro de filtros/listados/reportes (ej. `ContractsIndex` filtro de proveedor).
- Reemplazar el catálogo de proveedores (`SuppliersIndex`); solo puede refactorizarse si es necesario para reusar Actions/validaciones.
- Introducir dependencias frontend (Select2/TomSelect/React/Vue). Mantener Blade + Livewire 3 + Bootstrap 5.

<!-- template-output: technical_requirements -->
## Acceptance Criteria

### AC1 — Selector de proveedor con búsqueda (formularios transaccionales)

**Given** un usuario autorizado está en un formulario transaccional que permite seleccionar proveedor  
**When** escribe al menos 2 caracteres en el campo de proveedor  
**Then** el sistema muestra sugerencias (máx. 10) y permite seleccionar por mouse o teclado (↑/↓/Enter)  
**And** `Esc` cierra el dropdown y reinicia `aria-activedescendant`.

Aplicaciones mínimas:
- Producto: `ProductForm` (`supplier_id`)
- Contrato: `ContractForm` (`supplier_id`)
- Activo (garantía): `AssetForm` (`warrantySupplierId`)

### AC2 — CTA “Crear proveedor” cuando no hay resultados (modal, opción B)

**Given** el usuario escribe un término con 2+ caracteres y no hay resultados  
**When** el usuario tiene permiso `catalogs.manage`  
**Then** el dropdown muestra un CTA **“Crear “{search}””** como opción navegable por teclado  
**And** al activarlo abre un modal “Crear proveedor” con campos mínimos.

**And** si el usuario NO tiene `catalogs.manage`, el CTA NO aparece (solo “Sin resultados”).

### AC3 — Crear proveedor (RBAC server-side + validación)

**Given** el usuario abre el modal “Crear proveedor”  
**When** guarda con `name` válido  
**Then** el sistema crea un `Supplier` aplicando normalización (`Supplier::normalizeName`)  
**And** valida:
- `name`: requerido, max 255, único
- `contact`: opcional, max 255
- `notes`: opcional, max 1000

**And** el servidor autoriza la creación con `Gate::authorize('catalogs.manage')` (no solo UI).

### AC4 — Autoselección + UX post-create

**Given** se crea exitosamente el proveedor desde el modal  
**When** el modal se cierra  
**Then** el combobox autoselecciona el proveedor creado  
**And** el formulario mantiene el estado (no pierde inputs ya capturados)  
**And** se muestra un toast de éxito.

### AC5 — Anti-duplicados: exact match + carrera MySQL 1062

**Given** el usuario intenta crear “{search}” desde el modal/CTA  
**When** ya existe un proveedor con el mismo nombre normalizado  
**Then** el sistema selecciona el existente y NO duplica  
**And** muestra toast informativo (“Se seleccionó el existente”).

**Given** dos usuarios crean el mismo proveedor concurrentemente  
**When** ocurre error de DB MySQL 1062 (duplicate key)  
**Then** el sistema recupera el registro existente y lo selecciona (race-safe)  
**And** no deja el combobox en estado inconsistente.

### AC6 — SoftDeletes: “existe en Papelera”

**Given** el usuario escribe un nombre cuyo match exacto está soft-deleted  
**When** el dropdown está en “Sin resultados”  
**Then** se muestra mensaje “Existe en Papelera” y CTA “Ir a Papelera” (tab `suppliers`)  
**And** NO se permite crear un duplicado.

### AC7 — Scope: no creable en filtros/listados/reportes

**Given** el usuario está en un listado con filtro de proveedor (ej. `ContractsIndex`)  
**When** el filtro no encuentra resultados o está vacío  
**Then** NO aparece CTA de crear (se mantiene `<select>` simple para filtros).

### AC8 — Errores inesperados con `error_id`

**Given** ocurre una excepción inesperada durante búsqueda o creación  
**When** el sistema está en ambiente productivo  
**Then** se reporta vía `ErrorReporter` y se muestra toast con `error_id`  
**And** la UI queda operable (dropdown puede reintentar).

## Tasks / Subtasks

- [x] Implementar `SupplierCombobox` (AC: 1–6, 8)
- [x] (Recomendado) Extraer lógica a Actions `SearchSuppliers` / `UpsertSupplier` para reuso y testabilidad (AC: 1, 3, 5, 8)
- [x] Integrar combobox en Producto (`supplier_id`) y eliminar precarga de proveedores en `ProductForm` (AC: 1–4)
- [x] Integrar combobox en Contrato (`supplier_id`) y eliminar precarga de proveedores en `ContractForm` (AC: 1–4, 7)
- [x] Integrar combobox en Activo/Garantía (`warrantySupplierId`) y eliminar precarga de proveedores en `AssetForm` (AC: 1–4)
- [x] Asegurar scope: `ContractsIndex` (filtro) se mantiene como `<select>` sin CTA creable (AC: 7)
- [x] Agregar tests `SupplierComboboxTest` + ajuste a `CreableSelectorsScopeTest` (AC: 1–8)

<!-- template-output: developer_context_section -->
## Dev Notes

### Contexto actual (antes de tocar código)

- Hoy, los formularios transaccionales usan `<select>` y **precargan todos los proveedores** en `mount()`:
  - `ProductForm` (`$suppliers` → `<select wire:model.defer="supplier_id">`)
  - `ContractForm` (`$suppliers` → `<select wire:model="supplier_id">`)
  - `AssetForm` (`$suppliers` → `<select wire:model.defer="warrantySupplierId">`)
- El catálogo de proveedores ya existe y valida unicidad + normalización:
  - `gatic/app/Models/Supplier.php` (`normalizeName`, SoftDeletes)
  - `gatic/app/Livewire/Catalogs/Suppliers/SuppliersIndex.php` (CRUD + 1062)

### Enfoque recomendado (consistente con Epic 15)

- Implementar `SupplierCombobox` como **componente Livewire reusable** similar a:
  - Inline creable (A): `BrandCombobox`, `LocationCombobox` (búsqueda + CTA crear + SoftDeletes + 1062).
  - Modal creable (B): `EmployeeCombobox` (CTA en dropdown abre modal + autoselección + foco + `error_id`).
- Para Proveedor (tiene campos opcionales), usar **modal** (opción UX B):
  - CTA en “Sin resultados” abre modal “Crear proveedor”.
  - Modal: `name` requerido; `contact` y `notes` opcionales.

### Guardrails (errores típicos a evitar)

- No agregar JS frameworks ni plugins de select (mantener Livewire + Alpine mínimo como en combobox actuales).
- No mezclar permisos:
  - Usar el combobox (selección/búsqueda) requiere `Gate::authorize('inventory.manage')` porque vive en formularios de inventario.
  - Crear proveedor requiere `Gate::authorize('catalogs.manage')`.
  - CTA visible solo si `Gate::allows('catalogs.manage')`.
- No duplicar proveedores:
  - Normalizar `name` antes de buscar/crear (`Supplier::normalizeName`).
  - Pre-check exacto; si existe, seleccionar.
  - Manejar `QueryException` 1062 para re-seleccionar el existente (race-safe).
- SoftDeletes:
  - Excluir soft-deleted de sugerencias.
  - Si match exacto está en Papelera, bloquear creación y ofrecer CTA a Papelera (`catalogs.trash.index`, tab `suppliers`).
- A11y multi-instancia:
  - IDs únicos por instancia para `input/listbox/options/activedescendant` (ver patrón `BrandCombobox`/`EmployeeCombobox`).
  - CTA “Crear …” debe ser `role="option"` y navegable por teclado.
- Errores inesperados:
  - Reportar con `ErrorReporter` y mostrar `error_id` (toast) como en combobox actuales.

### Detalle de implementación (orientativo)

- Nuevo componente:
  - `gatic/app/Livewire/Ui/SupplierCombobox.php`
  - `gatic/resources/views/livewire/ui/supplier-combobox.blade.php`
  - Usar `#[Modelable] public ?int $supplierId = null;`
  - Props/estado sugeridos: `supplierLabel`, `search`, `showDropdown`, `showCreateModal`, `createName`, `createContact`, `createNotes`, `errorId`, `createErrorId`, `inputId` (opcional).
- Search:
  - MIN_SEARCH_LENGTH=2, MAX_RESULTS=10.
  - Query con `LIKE ... escape '\\'` + ranking prefix-first (ver `BrandCombobox::getSuggestions()` o `SearchEmployees`).
- Modal create:
  - Abrir modal desde CTA; prellenar `createName` con el search normalizado (si aplica).
  - Guardar con loading state (`wire:loading`) y deshabilitar botones.
  - Cerrar modal y devolver foco al input del combobox.

### Integración en formularios

- Reemplazar los `<select>` en:
  - `gatic/resources/views/livewire/inventory/products/product-form.blade.php` (campo proveedor)
  - `gatic/resources/views/livewire/inventory/contracts/contract-form.blade.php` (campo proveedor)
  - `gatic/resources/views/livewire/inventory/assets/asset-form.blade.php` (garantía → proveedor)
- Idealmente eliminar precarga `$suppliers` en `mount()` de:
  - `gatic/app/Livewire/Inventory/Products/ProductForm.php`
  - `gatic/app/Livewire/Inventory/Contracts/ContractForm.php`
  - `gatic/app/Livewire/Inventory/Assets/AssetForm.php`
  para que el combobox sea el único dueño de la búsqueda/selección.

<!-- template-output: architecture_compliance -->
### Cumplimiento de arquitectura

- UI: Blade + Livewire 3 + Bootstrap 5 (MPA), sin SPA, sin WebSockets.
- Autorización: siempre server-side (Gates/Policies). CTA condicionado por `Gate::allows`, acciones protegidas con `Gate::authorize`.
- Errores inesperados: UX humana + `error_id` (detalle técnico solo Admin) usando `App\Support\Errors\ErrorReporter`.
- Concurrencia: anti-duplicados race-safe (MySQL 1062) como en `BrandCombobox`/`LocationCombobox`.
- SoftDeletes: respetar `deleted_at` en sugerencias y validaciones (`Rule::exists(...)->whereNull('deleted_at')`).

<!-- template-output: library_framework_requirements -->
### Stack/Librerías (no negociable)

- Laravel 11 + PHP 8.2+ + MySQL 8.
- Livewire 3 (MPA), Blade, Bootstrap 5.
- JavaScript: Alpine (solo lo mínimo, siguiendo patrón de `BrandCombobox`/`EmployeeCombobox`).
- No introducir librerías externas de selects/autocomplete.

<!-- template-output: file_structure_requirements -->
### File Structure Requirements

Agregar/modificar siguiendo convenciones existentes:

- UI component:
  - `gatic/app/Livewire/Ui/SupplierCombobox.php` (ADD)
  - `gatic/resources/views/livewire/ui/supplier-combobox.blade.php` (ADD)
- (Recomendado) Actions para no duplicar lógica:
  - `gatic/app/Actions/Suppliers/SearchSuppliers.php` (ADD)
  - `gatic/app/Actions/Suppliers/UpsertSupplier.php` (ADD)
- Formularios a integrar:
  - `gatic/resources/views/livewire/inventory/products/product-form.blade.php` (MODIFY)
  - `gatic/app/Livewire/Inventory/Products/ProductForm.php` (MODIFY)
  - `gatic/resources/views/livewire/inventory/contracts/contract-form.blade.php` (MODIFY)
  - `gatic/app/Livewire/Inventory/Contracts/ContractForm.php` (MODIFY)
  - `gatic/resources/views/livewire/inventory/assets/asset-form.blade.php` (MODIFY)
  - `gatic/app/Livewire/Inventory/Assets/AssetForm.php` (MODIFY)

Notas:
- Identificadores (clases, métodos, variables, rutas) en inglés.
- Copy/UI en español.

<!-- template-output: testing_requirements -->
### Testing Requirements

Agregar tests de regresión y permisos (Feature tests):

- `gatic/tests/Feature/Catalogs/SupplierComboboxTest.php`:
  - search muestra resultados (admin/editor)
  - “Sin resultados” muestra CTA crear solo con `catalogs.manage`
  - crear proveedor desde modal autoselecciona (y dispara toast)
  - exact match existente: selecciona existente, no duplica
  - carrera 1062: re-selecciona existente (test determinista via hook `creating`)
  - soft-deleted exact match: muestra “Existe en Papelera” + “Ir a Papelera” y no permite crear
  - `Lector` no puede ejecutar acciones del componente (AuthorizationException)
- Scope:
  - extender `gatic/tests/Feature/Catalogs/CreableSelectorsScopeTest.php` o agregar test nuevo que asegure que `ContractsIndex` **no renderiza** CTAs/IDs de “supplier-option-create-” (filtro se mantiene `<select>`).

<!-- template-output: previous_story_intelligence -->
## Inteligencia de historias previas (reuso)

- Reusar patrón de IDs únicos + ARIA combobox/listbox:
  - `BrandCombobox`/`LocationCombobox`: `listboxId`, `optionIdPrefix`, `createOptionId`, `trashOptionId`.
  - `EmployeeCombobox`: IDs únicos también para modal (title/input IDs) y retorno de foco al cerrar.
- Reusar anti-duplicados:
  - normalización + pre-check exacto
  - manejo `QueryException` 1062 → re-selección del existente
- Reusar SoftDeletes UX:
  - si exact match está en papelera: bloquear create y ofrecer “Ir a Papelera”.
- Reusar error handling:
  - `ErrorReporter` + toast con `error_id` (no filtrar stacktrace al usuario no Admin).
- Reusar check UX “long-request”:
  - si alguna operación del combobox (create/search) puede tardar >3s en datos reales, integrar loader/cancel (ver `gatic/resources/views/components/ui/long-request.blade.php`).

<!-- template-output: git_intelligence_summary -->
## Git Intelligence Summary

- `Supplier` ya existe como catálogo con SoftDeletes y nombre normalizado (`gatic/app/Models/Supplier.php`).
- Hay CRUD y validación de unicidad en `gatic/app/Livewire/Catalogs/Suppliers/SuppliersIndex.php` bajo `can:catalogs.manage`.
- Formularios transaccionales precargan `suppliers` en `mount()` y renderizan `<select>` (candidato claro para reemplazar por combobox).
- Hay patrón probado de combobox creable:
  - Inline create: `gatic/app/Livewire/Ui/BrandCombobox.php`, `gatic/app/Livewire/Ui/LocationCombobox.php`
  - Modal create: `gatic/app/Livewire/Ui/EmployeeCombobox.php`
  - Tests: `gatic/tests/Feature/Catalogs/BrandComboboxTest.php`, `gatic/tests/Feature/Catalogs/LocationComboboxTest.php`, `gatic/tests/Feature/Catalogs/CreableSelectorsScopeTest.php`

<!-- template-output: latest_tech_information -->
## Latest Technical Information

- **Versiones reales del proyecto:** tomar como fuente `gatic/composer.json`, `gatic/composer.lock` y `gatic/package.json` (no asumir versiones).
- **Livewire 3 (Modelable):** el proyecto ya usa `#[Modelable]` para comboboxes (`BrandCombobox`, `EmployeeCombobox`); mantener el mismo patrón para binding (`wire:model`).
- **A11y combobox/listbox:** seguir el patrón ya implementado en `BrandCombobox`/`LocationCombobox` (ARIA roles, `aria-controls`, `aria-activedescendant`, teclado ↑/↓/Enter/Esc) alineado a WAI-ARIA Authoring Practices (Combobox with listbox popup).
- **MySQL duplicate key:** el manejo de error 1062 ya está estandarizado en historias 15.1/15.2 (re-selección del existente). Reusar el mismo enfoque.

<!-- template-output: project_context_reference -->
## Project Context Reference (must-read)

- Bible/reglas: `docsBmad/project-context.md`, `project-context.md`
- Creable selectors: `gatic/docs/ui/creable-selectors.md`
- Arquitectura/patrones: `_bmad-output/implementation-artifacts/architecture.md`
- UI patterns: `gatic/docs/ui-patterns.md`
- Implementaciones previas:
  - `_bmad-output/implementation-artifacts/15-1-employeecombobox-creable-crear-empleado-desde-seleccion.md`
  - `_bmad-output/implementation-artifacts/15-2-catalogos-creables-en-formularios-marca-y-ubicacion.md`

<!-- template-output: story_completion_status -->
## Story Completion Status

- Status: **done**
- Completion note: "Implementación + code review completados: `SupplierCombobox` modal creable integrado en formularios transaccionales, con Actions reutilizables, anti-duplicados (incluye 1062), SoftDeletes, RBAC server-side, `error_id`, mejoras UX (Papelera en modal + mensaje min-chars) y cobertura de tests."

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `_bmad/core/tasks/workflow.xml`
- `_bmad/bmm/workflows/4-implementation/dev-story/workflow.yaml`
- `_bmad/bmm/workflows/4-implementation/dev-story/instructions.xml`
- `docker compose -f compose.yaml exec -T laravel.test ./vendor/bin/pint --test`
- `docker compose -f compose.yaml exec -T laravel.test php artisan test`
- `docker compose -f compose.yaml exec -T laravel.test php artisan test --filter SupplierComboboxTest`
- `docker compose -f compose.yaml exec -T laravel.test ./vendor/bin/phpstan analyse --no-progress app/Actions/Suppliers app/Livewire/Ui/SupplierCombobox.php`

### Completion Notes List
- Implementado `SupplierCombobox` con búsqueda server-side (2+ chars, máx. 10), navegación de teclado (↑/↓/Enter/Esc), ARIA multi-instancia y CTA “Crear”.
- Implementado flujo modal de creación de proveedor con campos mínimos (`name`, `contact`, `notes`), y autorización server-side `Gate::authorize('catalogs.manage')`.
- Implementado anti-duplicados completo: pre-check exacto, recuperación race-safe en MySQL 1062 y bloqueo por match exacto en Papelera con CTA “Ir a Papelera”.
- Implementado manejo de excepciones con `ErrorReporter` + `error_id` en búsqueda y creación, manteniendo la UI operable para reintentos.
- Extraída lógica reusable a `SearchSuppliers` y `UpsertSupplier` para reducir duplicación y mejorar testabilidad.
- Integrados los 3 formularios transaccionales (`ProductForm`, `ContractForm`, `AssetForm`) con `SupplierCombobox`, eliminando precarga masiva de proveedores en `mount()`.
- Validado alcance: `ContractsIndex` se mantiene con filtro `<select>` sin CTA creable.
- Code review (AI) + fixes aplicados: CTA a Papelera dentro del modal si el nombre existe soft-deleted, y dropdown con hint de min chars.
- Validaciones ejecutadas: tests dirigidos de la historia + suite completa (`854` tests pass) + Pint en verde + PHPStan (targets) en verde.

### File List
- `.gitignore` (MODIFY)
- `_bmad-output/implementation-artifacts/15-1-employeecombobox-creable-crear-empleado-desde-seleccion.md` (ADD)
- `_bmad-output/implementation-artifacts/15-3-proveedor-creable-desde-formularios-producto-activo-contrato.md` (ADD)
- `gatic/app/Livewire/Ui/SupplierCombobox.php` (ADD)
- `gatic/resources/views/livewire/ui/supplier-combobox.blade.php` (ADD)
- `gatic/app/Actions/Suppliers/SearchSuppliers.php` (ADD)
- `gatic/app/Actions/Suppliers/UpsertSupplier.php` (ADD)
- `gatic/app/Livewire/Inventory/Products/ProductForm.php` (MODIFY)
- `gatic/resources/views/livewire/inventory/products/product-form.blade.php` (MODIFY)
- `gatic/app/Livewire/Inventory/Contracts/ContractForm.php` (MODIFY)
- `gatic/resources/views/livewire/inventory/contracts/contract-form.blade.php` (MODIFY)
- `gatic/app/Livewire/Inventory/Assets/AssetForm.php` (MODIFY)
- `gatic/resources/views/livewire/inventory/assets/asset-form.blade.php` (MODIFY)
- `gatic/tests/Feature/Catalogs/SupplierComboboxTest.php` (ADD)
- `gatic/tests/Feature/Catalogs/CreableSelectorsScopeTest.php` (MODIFY)
- `gatic/tests/Feature/Inventory/ProductsTest.php` (MODIFY)
- `gatic/tests/Feature/Inventory/ContractsTest.php` (MODIFY)
- `gatic/tests/Feature/Inventory/AssetsTest.php` (MODIFY)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MODIFY: `15-3-...` → `done`)

## Senior Developer Review (AI)

Fecha: 2026-02-21  
Resultado: ✅ **Aprobada** (fixes aplicados)

### Validación de ACs (resumen)

- **AC1 (búsqueda + teclado + max 10):** IMPLEMENTADO.
- **AC2 (CTA “Crear” solo con `catalogs.manage` + modal):** IMPLEMENTADO.
- **AC3 (RBAC server-side + normalización + límites):** IMPLEMENTADO.
- **AC4 (autoselección + toast):** IMPLEMENTADO.
- **AC5 (anti-duplicados + carrera 1062):** IMPLEMENTADO.
- **AC6 (SoftDeletes: “Existe en Papelera” + CTA):** IMPLEMENTADO.
- **AC7 (scope: no creable en filtros):** IMPLEMENTADO.
- **AC8 (errores inesperados con `error_id`):** IMPLEMENTADO.

### Hallazgos (y resolución)

- **[HIGH] Tracking inconsistente (story vs `sprint-status.yaml`):** corregido (sync a `done`).
- **[MEDIUM] Archivos cambiados no documentados (incl. `.gitignore` y story files):** File List actualizada.
- **[MEDIUM] Caso “existe en Papelera” desde modal:** agregado CTA “Ir a Papelera” en modal + error inline.
- **[MEDIUM] Dropdown podía abrirse vacío:** se muestra hint de “Escribe al menos 2 caracteres”.
- **[LOW] `Esc` cerraba dropdown pero quitaba foco del input:** se mantiene el foco en el input.

## Change Log

- 2026-02-21: Se implementa `SupplierCombobox` modal creable + Actions `SearchSuppliers`/`UpsertSupplier`.
- 2026-02-21: Se integran formularios de Producto/Contrato/Activo con combobox y se elimina precarga de proveedores.
- 2026-02-21: Se amplía cobertura de pruebas para combobox de proveedor y scope de filtros sin creable en `ContractsIndex`.
- 2026-02-21: Code review (AI) + fixes aplicados (tracking sync + UX “Papelera en modal” + hint min chars).
