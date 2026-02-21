# Story 15.2: Catálogos creables en formularios (Marca y Ubicación)

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Admin/Editor,
I want crear Marca y Ubicación desde sus selectores en formularios transaccionales cuando no hay resultados,
so that complete altas/ediciones sin ir a Catálogos, reduzca fricción operativa y evite duplicados.

## Acceptance Criteria

1. **Given** un usuario autorizado está en un formulario con selector de Marca o Ubicación
   **When** busca y no existen resultados
   **Then** el selector muestra CTA “Crear ‘{search}’” (inline en dropdown) y es accesible por teclado
   **And** al crear, el selector autoselecciona el registro y muestra un toast de éxito.

2. **Given** la entidad ya existe (por normalización/unicidad) o existe un duplicado concurrente
   **When** el usuario intenta crear desde el CTA
   **Then** el sistema selecciona el existente (o resuelve 1062) en lugar de duplicar.

3. **Given** el usuario está en una pantalla de filtros/listados/reportes
   **When** no hay resultados en un selector de Marca o Ubicación
   **Then** no se muestra CTA de crear dentro del filtro
   **And** la creación se mantiene solo en formularios transaccionales.

## Tasks / Subtasks

- [x] Implementar selectores “creables” para `Brand` y `Location` (AC: 1, 2)
- [x] Reemplazar selects actuales por el nuevo patrón en:
  - [x] `ProductForm` (marca) (AC: 1, 2)
  - [x] `AssetForm` (ubicación) (AC: 1, 2)
  - [x] `PendingTaskShow` quick process (ubicación para crear activos) (AC: 1, 2)
- [x] A11y/teclado (ARIA combobox/listbox): ↑/↓/Enter/Escape + foco consistente (AC: 1)
- [x] Anti-duplicados: normalización + pre-check + `QueryException` 1062 => re-selección (AC: 2)
- [x] SoftDeletes: si existe pero está en papelera, error claro y CTA “Ir a Papelera” (AC: 2)
- [x] Validar RBAC server-side:
  - [x] CTA visible solo si `Gate::allows('catalogs.manage')`
  - [x] Create siempre con `Gate::authorize('catalogs.manage')` (AC: 1, 2)
- [x] Asegurar que NO se use el patrón en filtros/listados/reportes (AC: 3)
- [x] Agregar/ajustar tests (AC: 1, 2, 3)

## Dev Notes

### Contexto y alcance

- Objetivo UX: reducir fricción operativa al capturar/editar sin salir del flujo (patrón “no hay resultados -> crear -> autoseleccionar”).  
- Alcance: solo catálogos simples *name-only* (Marca, Ubicación) y solo en formularios transaccionales.  
- No alcance: habilitar “crear” dentro de filtros/listados/reportes (debe permanecer deshabilitado).

### Guardrails (evitar errores típicos de agente dev)

- No introducir nuevas dependencias frontend (no Select2, no React/Vue). Mantener Blade + Livewire 3 + Bootstrap 5.
- No asumir permisos: `inventory.manage` no implica `catalogs.manage`. Mostrar CTA y crear solo con `catalogs.manage`.
- No duplicar lógica anti-duplicados: normalización + `unique` + resolver duplicado concurrente (MySQL 1062).
- No romper SoftDeletes: si el nombre ya existe pero está eliminado, la DB puede bloquear por `unique`; UX debe guiar a “Papelera” (restauración admin/editor).
- Mantener copy UI en español; identificadores de código/rutas en inglés.

### Componentes/lugares a tocar (orientativo)

- UI reusable (preferido): `gatic/app/Livewire/Ui/*` + `gatic/resources/views/livewire/ui/*`
- Formularios existentes:
  - `gatic/app/Livewire/Inventory/Products/ProductForm.php` + `gatic/resources/views/livewire/inventory/products/product-form.blade.php`
  - `gatic/app/Livewire/Inventory/Assets/AssetForm.php` + `gatic/resources/views/livewire/inventory/assets/asset-form.blade.php`
  - `gatic/app/Livewire/PendingTasks/PendingTaskShow.php` + `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php`

### Requisitos técnicos (DEV AGENT GUARDRAILS)

- Selector tipo combobox/listbox (no `<select>` puro) para soportar búsqueda + “Sin resultados” + CTA inline.
- Teclado:
  - `↑/↓` recorre opciones (incluyendo “Crear ‘{search}’” si aplica).
  - `Enter` selecciona opción resaltada.
  - `Esc` cierra dropdown y limpia `aria-activedescendant`.
- Estados:
  - Mientras crea: deshabilitar CTA/opciones y mostrar spinner (`wire:loading`).
  - En éxito: autoselección del registro creado + toast success.
- Operaciones lentas:
  - Si la búsqueda/creación puede tardar >3s en datos reales, integrar `<x-ui.long-request target="..."/>` en el contenedor del formulario (ver `gatic/docs/ui-patterns.md`) para loader + Cancelar sin perder estado previo.
- Errores:
  - Validación: error inline claro.
  - Inesperado: reportar con `ErrorReporter` y mostrar `error_id` (toast y, si aplica, `<x-ui.error-alert-with-id />`).
- Normalización obligatoria antes de buscar/crear: `Brand::normalizeName()` / `Location::normalizeName()`.

### Cumplimiento de arquitectura

- Livewire-first: el componente UI controla estado+acciones; mantener JS mínimo (solo Alpine/atributos como en `EmployeeCombobox`).
- Autorización:
  - Formularios ya usan `Gate::authorize('inventory.manage')`, pero el “crear catálogo” debe ser `Gate::authorize('catalogs.manage')`.
  - CTA visible solo si `Gate::allows('catalogs.manage')`.
- SoftDeletes:
  - Sugerencias deben excluir `deleted_at` (consistencia con el resto del sistema).
  - Si `unique` bloquea por un registro en papelera, mostrar mensaje “Existe en papelera” y dirigir a restauración (no auto-restaurar en silencio).
- Anti-duplicados race-safe:
  - Pre-check exacto por nombre normalizado.
  - `try/catch QueryException` driver code 1062 => reconsultar y seleccionar existente.

### Requisitos de librerías/framework (no desviarse)

- Backend: Laravel 11 (ver `gatic/composer.json`).
- Livewire 3 (en repo: `livewire/livewire` v3.7.10 en `gatic/composer.lock`).
- UI: Bootstrap 5 (en repo: `bootstrap` ^5.2.3 en `gatic/package.json`) + `bootstrap-icons`.
- No agregar plugins externos de selects/autocomplete.

### Requisitos de estructura de archivos (sugerencia de diseño)

- Preferido: dos componentes UI pequeños (evita genéricos difíciles de tipar):
  - `gatic/app/Livewire/Ui/BrandCombobox.php` + `gatic/resources/views/livewire/ui/brand-combobox.blade.php`
  - `gatic/app/Livewire/Ui/LocationCombobox.php` + `gatic/resources/views/livewire/ui/location-combobox.blade.php`
- Reusar patrones existentes:
  - Búsqueda tipoahead con escape LIKE (ver `gatic/app/Actions/Employees/SearchEmployees.php`).
  - Create + 1062 handling (ver `gatic/app/Livewire/Catalogs/Brands/BrandsIndex.php` y `.../LocationsIndex.php`).
- Si se comparte lógica, extraer a `gatic/app/Actions/Catalogs/*` (evitar helpers globales).

### Requisitos de testing

- Tests Livewire feature para cada combobox (patrón: `gatic/tests/Feature/Employees/EmployeeComboboxTest.php`):
  - RBAC: Admin/Editor ok; Lector no puede ejecutar acciones.
  - “Sin resultados” muestra CTA “Crear ‘{search}’” solo con `catalogs.manage`.
  - Crear: persiste, autoselecciona, cierra dropdown, emite toast.
  - Duplicado (existente o 1062): no duplica; selecciona existente y muestra toast informativo (o no-op con selección).
  - Soft-deleted conflict: mensaje claro y sin crear duplicado.
- Ajustar tests existentes si cambian los formularios:
  - `gatic/tests/Feature/Inventory/ProductsTest.php` (marca).
  - `gatic/tests/Feature/Inventory/AssetsTest.php` (ubicación).

### Inteligencia de historias previas (Story 15.1)

- Ya existe un patrón “creable” completo en `EmployeeCombobox` (modal + A11y multi-instancia + toasts + `error_id`).
- Reusar el enfoque de IDs únicos por instancia y la UX de teclado como baseline.

### Inteligencia de git (referencias rápidas)

- `cde817e` fix(locks): QA gaps en Story 15.1 (A11y IDs, errores).
- `d21f33c` feat(catalogs): UX en catálogos (incluye creación/duplicados).

### Información técnica verificada en repo (evitar desalineación)

- `gatic/composer.json`: `laravel/framework` `^11.31`, `livewire/livewire` `^3.0`.
- `gatic/composer.lock`: `livewire/livewire` `v3.7.10`.
- `gatic/package.json`: `bootstrap` `^5.2.3`, `bootstrap-icons` `^1.11.3`.

### Project Structure Notes

- La app Laravel vive en `gatic/` (no tocar root para código de app).
- Seguir el patrón “Livewire-first”: rutas -> componentes Livewire; controllers solo bordes (descargas/JSON puntual).
- Reusar patrones existentes:
  - Toasts: `App\Livewire\Concerns\InteractsWithToasts` + `ui:toast` (ver `gatic/docs/ui-patterns.md`)
  - Errores inesperados: `App\Support\Errors\ErrorReporter` + `error_id` (ver `gatic/docs/ui-patterns.md`)
  - Combobox ARIA/teclado: referencia directa `EmployeeCombobox` (ver `gatic/app/Livewire/Ui/EmployeeCombobox.php` y su Blade)

### References

- Requerimientos funcionales: `_bmad-output/implementation-artifacts/epics.md` (Epic 15, Story 15.2).
- Diseño/arquitectura del patrón: `gatic/docs/ui/creable-selectors.md` (contrato “creable”, A11y, anti-duplicados, SoftDeletes).
- Invariantes del proyecto: `docsBmad/project-context.md`, `project-context.md`, `gatic/docs/agent-enforcement.md`.
- Patrones UI (toasts/error_id/long-request): `gatic/docs/ui-patterns.md`.
- Gates/RBAC: `gatic/app/Providers/AuthServiceProvider.php` (`catalogs.manage`, `inventory.manage`).
- Implementaciones existentes para comparar:
  - `gatic/app/Livewire/Ui/EmployeeCombobox.php`, `gatic/resources/views/livewire/ui/employee-combobox.blade.php`, `gatic/tests/Feature/Employees/EmployeeComboboxTest.php`
  - `gatic/app/Livewire/Catalogs/Brands/BrandsIndex.php`, `gatic/app/Livewire/Catalogs/Locations/LocationsIndex.php`

### Project Context Reference (resumen ejecutivo)

- Stack objetivo y restricciones: Laravel 11 + Livewire 3 + Bootstrap 5; sin WebSockets; polling cuando aplique; copy UI en español.
- Autorización server-side obligatoria (Gates/Policies); roles fijos: Admin/Editor/Lector.
- Soft-delete con retención indefinida; papelera solo Admin/Editor para restaurar/purgar.

### Story Completion Status

- Estado objetivo de esta story: `done`.
- Archivo de story: `_bmad-output/implementation-artifacts/15-2-catalogos-creables-en-formularios-marca-y-ubicacion.md`.
- Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml` debe marcar `15-2-catalogos-creables-en-formularios-marca-y-ubicacion: done`.

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- N/A (create-story workflow)

### Completion Notes List

- Story 15.2 seleccionada automáticamente desde `sprint-status.yaml` (primer backlog).
- Contexto consolidado desde `epics.md` + docs `gatic/docs/ui/creable-selectors.md` + código existente (`EmployeeCombobox`, `BrandsIndex`, `LocationsIndex`).
- Versiones confirmadas localmente en `gatic/composer.json`, `gatic/composer.lock`, `gatic/package.json`.
- Implementados `BrandCombobox` y `LocationCombobox` (Livewire) con búsqueda server-side, A11y/teclado (ARIA combobox/listbox) y creación inline condicionada por `catalogs.manage`.
- Integrados los nuevos selectores en `ProductForm` (marca), `AssetForm` (ubicación) y `PendingTaskShow` (quick process), eliminando precargas de catálogos.
- Anti-duplicados race-safe: normalización + pre-check exacto + manejo de MySQL 1062 para re-seleccionar el existente.
- SoftDeletes: si existe en papelera, se bloquea creación y se muestra CTA “Ir a Papelera”; `catalogs/trash` habilitado para Admin/Editor vía `catalogs.manage`.
- Code review (AI) aplicado: resaltado visual + `aria-selected` en opciones (teclado), tests para AC3 (no CTA en filtros) y test determinista del caso 1062 (carrera).
- Copy: corrección de acentos en mensajes de captura rápida (ubicación/rápida).
- Higiene repo: `.gitignore` actualizado para ignorar artefactos locales (screenshots/`artifacts/`).
- Validaciones ejecutadas (Docker):
  - `docker compose -f compose.yaml exec -T laravel.test ./vendor/bin/pint --test` (PASS)
  - `docker compose -f compose.yaml exec -T laravel.test php artisan test --filter='(BrandComboboxTest|LocationComboboxTest|CreableSelectorsScopeTest|CatalogsTrashTest)'` (PASS)
  - `docker compose -f compose.yaml exec -T laravel.test ./vendor/bin/phpstan analyse --no-progress` (FAIL baseline preexistente: 77 errores fuera del alcance de esta story)

### File List

- `.gitignore` (MODIFY)
- `_bmad-output/implementation-artifacts/15-2-catalogos-creables-en-formularios-marca-y-ubicacion.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `gatic/app/Actions/PendingTasks/ProcessQuickCapturePendingTask.php` (MODIFY)
- `gatic/app/Livewire/Catalogs/Trash/CatalogsTrash.php` (MODIFY)
- `gatic/app/Livewire/Inventory/Assets/AssetForm.php` (MODIFY)
- `gatic/app/Livewire/Inventory/Products/ProductForm.php` (MODIFY)
- `gatic/app/Livewire/PendingTasks/PendingTaskShow.php` (MODIFY)
- `gatic/app/Livewire/Ui/BrandCombobox.php` (ADD)
- `gatic/app/Livewire/Ui/LocationCombobox.php` (ADD)
- `gatic/resources/views/livewire/inventory/assets/asset-form.blade.php` (MODIFY)
- `gatic/resources/views/livewire/inventory/products/product-form.blade.php` (MODIFY)
- `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php` (MODIFY)
- `gatic/resources/views/livewire/ui/brand-combobox.blade.php` (ADD)
- `gatic/resources/views/livewire/ui/location-combobox.blade.php` (ADD)
- `gatic/routes/web.php` (MODIFY)
- `gatic/tests/Feature/Catalogs/BrandComboboxTest.php` (ADD)
- `gatic/tests/Feature/Catalogs/CatalogsTrashTest.php` (MODIFY)
- `gatic/tests/Feature/Catalogs/CreableSelectorsScopeTest.php` (ADD)
- `gatic/tests/Feature/Catalogs/LocationComboboxTest.php` (ADD)

### Change Log

- 2026-02-20: Selectores “creables” para Marca/Ubicación en formularios (con RBAC, anti-duplicados y SoftDeletes) + tests + ajustes de calidad (Pint/PHPStan).
- 2026-02-21: Code review (AI) + fixes aplicados: A11y resaltado/`aria-selected`, tests AC3 + 1062 determinista, copy con acentos, higiene `.gitignore`.

## Senior Developer Review (AI)

Fecha: 2026-02-21  
Resultado: ✅ **Aprobada** (con fixes aplicados)

### Validación de ACs (resumen)

- **AC1 (CTA “Crear …” + teclado + autoselección + toast):** IMPLEMENTADO (y corregido resaltado/`aria-selected` para no “navegar a ciegas”).
- **AC2 (anti-duplicados + 1062 + SoftDeletes con “Ir a Papelera”):** IMPLEMENTADO (con test determinista para 1062).
- **AC3 (no crear en filtros/listados/reportes):** IMPLEMENTADO (con test que asegura que los filtros no renderizan CTAs `*-option-create-*`).

### Hallazgos (y resolución)

- **[HIGH] A11y incompleta:** faltaba highlight visual y `aria-selected` en opciones de `BrandCombobox`/`LocationCombobox` → **corregido**.
- **[HIGH] Tests AC3 inexistentes:** no había prueba que protegiera “no CTA en filtros” → **agregado** `CreableSelectorsScopeTest`.
- **[MEDIUM] Falta de prueba para carrera 1062:** se agregó un test determinista usando evento `creating` para forzar 1062 y validar reselección.
- **[LOW] Copy “ubicacion/rapida”:** corregido a **“ubicación/rápida”** en validaciones/mensajes.
- **[MEDIUM] Artefactos locales untracked:** se agregó `.gitignore` para ignorar `artifacts/` y screenshots locales.
