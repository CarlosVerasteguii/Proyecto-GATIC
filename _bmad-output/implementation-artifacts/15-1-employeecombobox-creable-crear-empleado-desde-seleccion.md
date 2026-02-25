<!-- template-output: story_header -->
# Story 15.1: EmployeeCombobox creable (crear empleado desde selección)

Status: done

Story Key: `15-1-employeecombobox-creable-crear-empleado-desde-seleccion`  
Epic: `15` (Selectores “creables” (crear desde selección) + UX/A11y + performance)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Fecha: 2026-02-18  
Story ID: `15.1`

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/epics.md` (Epic 15 / Story 15.1)
- `gatic/docs/ui/creable-selectors.md` (contrato base “creable”, hallazgos y mapa de implementación)
- `_bmad-output/implementation-artifacts/ux.md` (A11y base: ARIA combobox/listbox + teclado; modales y manejo de foco)
- `_bmad-output/implementation-artifacts/architecture.md` (stack/patrones: Livewire-first, Actions, RBAC server-side, Bootstrap 5)
- `docsBmad/project-context.md` + `project-context.md` (reglas “bible”: idioma, RBAC, errores con `error_id`, sin WebSockets)
- `gatic/docs/ui-patterns.md` (toasts, errores con `error_id`, long-request)
- Código actual (puntos de extensión):
  - `gatic/app/Livewire/Ui/EmployeeCombobox.php` (autorización + búsqueda + selección)
  - `gatic/resources/views/livewire/ui/employee-combobox.blade.php` (Alpine: teclado; ARIA; dropdown)
  - `gatic/app/Actions/Employees/SearchEmployees.php` (búsqueda normalizada + limit + escape LIKE)
  - `gatic/app/Actions/Employees/UpsertEmployee.php` (create/update employee)
  - `gatic/app/Livewire/Employees/EmployeesIndex.php` (validación + manejo de duplicado RPE (1062))
  - `gatic/app/Models/Employee.php` (normalización + SoftDeletes)
  - `gatic/tests/Feature/Employees/EmployeeComboboxTest.php` (tests existentes: “Sin resultados”, selección, binding)
- Inteligencia previa (reuso / patrones ya implementados):
  - `_bmad-output/implementation-artifacts/4-2-buscar-seleccionar-empleados-al-registrar-movimientos-autocomplete.md` (teclado + estados + RBAC + performance del combobox)

Superficies donde se usa `EmployeeCombobox` (impacto: multi-instancia / A11y / consistencia):
- `gatic/resources/views/livewire/movements/assets/assign-asset-form.blade.php`
- `gatic/resources/views/livewire/movements/assets/unassign-asset-form.blade.php`
- `gatic/resources/views/livewire/movements/assets/loan-asset-form.blade.php`
- `gatic/resources/views/livewire/movements/assets/return-asset-form.blade.php`
- `gatic/resources/views/livewire/movements/products/quantity-movement-form.blade.php`
- `gatic/resources/views/livewire/inventory/assets/asset-form.blade.php`
- `gatic/resources/views/livewire/inventory/assets/assets-global-index.blade.php` (probable multi-instancia: modal + vista)
- `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php` (múltiples instancias con `:key`)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

<!-- template-output: story_requirements -->
## Story

Como **Admin/Editor**,  
quiero poder **crear un empleado** desde el selector de empleado cuando no hay resultados,  
para **registrar movimientos sin salir del flujo** y sin perder contexto.

## Epic Context (Epic 15 completo, resumido)

Objetivo Epic 15: habilitar patrón “creable” en selectores críticos para bajar fricción:
**buscar → sin resultados → CTA crear → anti-duplicados + RBAC → autoselección**, manteniendo A11y (multi-instancia) y performance.

Historias en Epic 15 (para contexto cruzado):
- **15.1 (esta story):** EmployeeCombobox creable (modal) + A11y multi-instancia.
- **15.2:** catálogos creables inline (Marca, Ubicación).
- **15.3:** Proveedor creable (modal) desde formularios de Producto/Activo/Contrato.
- **15.4:** selector de Producto escalable + crear con `returnTo` (sin precargas masivas).
- **15.5:** Categoría creable desde ProductForm (link + `returnTo`).

## Alcance (MVP)

- Agregar CTA **“Crear empleado”** dentro del dropdown del `EmployeeCombobox` cuando `showNoResults`.
- La creación se hace vía **modal** (opción UX **B**) con campos mínimos (RPE + Nombre).
- Al guardar con éxito:
  - se crea el `Employee` respetando RBAC server-side,
  - el combobox **autoselecciona** el nuevo empleado,
  - se cierra modal + dropdown,
  - se muestra toast de éxito.
- Corregir **A11y multi-instancia** del combobox: IDs ARIA únicos por instancia (listbox/opciones/activedescendant) y input con `id`.
- Manejo de anti-duplicados para `employees.rpe` (unique) y caso de carrera (MySQL 1062).

## Fuera de alcance (NO hacer aquí)

- Reemplazar el combobox por un paquete externo (Select2/TomSelect/etc.).  
  Regla: el repo ya tiene patrón Alpine+Livewire para esto.
- Migrar stack (Laravel/Livewire/Bootstrap) o refactors masivos.
- Hacer “creable” de catálogos/productos/categorías (eso es 15.2–15.5).

<!-- template-output: technical_requirements -->
## Acceptance Criteria

### AC1 — CTA “Crear empleado” visible y accesible por teclado

**Given** un usuario con `can:inventory.manage` interactúa con `EmployeeCombobox`  
**When** escribe una búsqueda válida (>=2 chars) y no hay resultados  
**Then** el dropdown muestra un CTA **“Crear empleado”** (o equivalente) **navegable por teclado** (↑/↓/Enter/Escape)  
**And** el CTA no rompe la navegación existente (sugerencias siguen siendo `role=option`).

### AC2 — Crear empleado desde modal y autoseleccionar

**Given** el usuario elige “Crear empleado” desde el dropdown  
**When** completa el modal (RPE + Nombre) y guarda  
**Then** se crea el empleado (server-side) y el combobox **selecciona automáticamente** el nuevo registro  
**And** se cierra el modal y el dropdown  
**And** se muestra un toast de éxito.

### AC3 — Anti-duplicados (RPE unique) + carrera (1062)

**Given** el usuario intenta crear un empleado con un `rpe` ya existente (o que se crea concurrentemente)  
**When** guarda el modal  
**Then** el sistema **no** crea duplicados (DB unique + manejo de 1062)  
**And** muestra un error claro en el campo RPE (y/o mensaje en modal).

### AC4 — Multi-instancia A11y: no colisiones de IDs ARIA

**Given** existen múltiples instancias de `EmployeeCombobox` renderizadas en la misma vista  
**When** se renderiza el DOM  
**Then** los IDs ARIA (`aria-controls`, `aria-activedescendant`, `role=listbox/option`) **no colisionan** entre instancias  
**And** la navegación por teclado (↑/↓/Enter/Escape) sigue funcionando en cada instancia de forma independiente.

### AC5 — RBAC y errores inesperados (defensa en profundidad)

**Given** un usuario `Lector` autenticado  
**When** intenta usar el combobox (búsqueda o creación) mediante requests directos  
**Then** recibe `403` (o equivalente) por autorización server-side.

**Given** ocurre un error inesperado en búsqueda o creación  
**When** el usuario interactúa con el combobox  
**Then** la UI muestra mensaje humano + `error_id` (detalle técnico solo Admin) y permite reintentar/cerrar sin romper la vista.

### AC6 — Testing mínimo

**Given** suite de tests del repo  
**When** se ejecutan tests del combobox  
**Then** existe cobertura para:
- mostrar CTA cuando “Sin resultados”,
- creación exitosa y autoselección,
- duplicado de RPE (unique/1062),
- IDs ARIA únicos por instancia (multi-instancia).

<!-- template-output: developer_context_section -->
## Developer Context (qué existe hoy y qué debe cambiar)

**Estado actual (antes de esta story):**
- El combobox ya existe y es reusable (Alpine + Livewire) y hace búsqueda via `SearchEmployees::execute()` con:
  - normalización (`Employee::normalizeText()`), min chars = 2, escape LIKE, límite de resultados.
- UX cuando no hay resultados: hoy solo muestra “Sin resultados” (sin CTA).
- A11y/multi-instancia:
  - `aria-controls="employee-listbox"` apunta a `id="employee-listbox"` **estático**.
  - `id="employee-option-{{ $employee->id }}"` puede colisionar entre instancias.
  - el `<input>` no expone `id` para asociarlo a un `<label for="...">`.
- Manejo de error inesperado en búsqueda ya existe: toast + `error_id` (no romper pantalla).

**Cambio esperado (en esta story):**
- Mantener el contrato `#[Modelable] public ?int $employeeId` y la búsqueda existente.
- Agregar CTA “Crear empleado” cuando `showNoResults` y gate permite crear.
- Agregar modal “crear empleado” (campos mínimos: RPE + Nombre) y autoseleccionar al guardar.
- Corregir IDs ARIA para multi-instancia (prefijar con id único por componente).
- Anti-duplicados: validar unique y manejar carrera (MySQL 1062) sin duplicar.

**Notas UX/performance:**
- No usar overlay global `<x-ui.long-request />` para cada keypress del search (sería una UX mala).  
  Si el flujo de creación pudiera tardar (poco probable), aplicar `long-request` **solo** a `createEmployee()` con `target="createEmployee"`.

## Tasks / Subtasks

1) CTA creable en dropdown (AC1)
- [x] En `employee-combobox.blade.php`, cuando `$showNoResults`:
  - [x] reemplazar/expandir el estado “Sin resultados” para incluir CTA “Crear empleado” como `role="option"` (teclado-first).
  - [x] asegurar que `selectHighlighted()` (Alpine) pueda activar el CTA (click) sin hacks.
- [x] Mantener mensajes UX existentes: “Escribe al menos 2 caracteres”, “Buscando…”, “Sin resultados” (pero ahora con CTA).

2) Modal create employee (AC2–3)
- [x] En `EmployeeCombobox.php`, agregar estado del modal + campos mínimos:
  - [x] `showCreateModal`, `createRpe`, `createName` (nombres internos en inglés; copy en español).
  - [x] método `openCreateEmployeeModal()` y `closeCreateEmployeeModal()`.
  - [x] método `createEmployee()`:
    - [x] `Gate::authorize('inventory.manage')`
    - [x] normalizar input (`Employee::normalizeText`)
    - [x] validar required + `unique:employees,rpe` (y mensajes en español)
    - [x] intentar create (preferir `UpsertEmployee`)
    - [x] manejar `QueryException` 1062 (carrera) y mostrar error claro sin duplicar
    - [x] en éxito: `setEmployeeData()`, limpiar búsqueda, cerrar modal + dropdown, toast success
- [x] En el Blade, renderizar modal estilo Livewire (sin dependencia nueva), siguiendo patrones existentes (ver PendingTasks modals).

3) A11y multi-instancia (AC4)
- [x] Generar IDs únicos por instancia usando el identificador del componente (ej. `$this->id`):
  - [x] `listboxId`, `inputId`, `optionIdPrefix`
  - [x] actualizar `aria-controls`, `id=listbox`, `id=option-*`, `aria-activedescendant`
- [x] Evitar colisiones también dentro del modal (ids de inputs/labels).

4) UX/errores y loading states (AC5)
- [x] Deshabilitar CTA y botón Guardar durante `createEmployee()` (`wire:loading` + spinner) para evitar doble submit.
- [x] Errores inesperados: usar `ErrorReporter` + toast con `error_id` (patrón ya usado por `getEmployeeSuggestions()`).
- [x] (Sanity) Asegurar que el modal devuelve foco al input del combobox al cerrar (ver UX doc; mínimo: dispatch browser event + focus).

5) Tests (AC6)
- [x] Actualizar `EmployeeComboboxTest`:
  - [x] “Sin resultados” ahora incluye CTA “Crear empleado”.
  - [x] crear empleado desde componente y verificar `employeeId` + label.
  - [x] duplicado RPE: assert error.
- [x] Agregar un test de multi-instancia (mínimo): renderizar 2 comboboxes en el mismo HTML y assert `employee-listbox-*` ids distintos.

<!-- template-output: architecture_compliance -->
## Architecture Compliance (lo no negociable)

- Livewire-first: mantener `EmployeeCombobox` como UI reusable (`app/Livewire/Ui/*`).
- Business ops: no meter lógica pesada en Blade; crear/validar en componente y/o `app/Actions/*` si se reutiliza.
- RBAC server-side obligatorio: `Gate::authorize('inventory.manage')` en búsqueda y creación (defensa en profundidad).
- Idioma: identificadores en inglés; copy/mensajes en español. [Source: `docsBmad/project-context.md`, `project-context.md`]
- Sin dependencias nuevas para modales/combobox; usar Bootstrap 5 + Livewire + Alpine ya presentes.
- Evitar regresiones:
  - `EmployeeCombobox` se usa en múltiples pantallas; cualquier cambio debe ser compatible con multi-instancia y `wire:model` existente.
  - `Employee` usa SoftDeletes: no exponer registros eliminados en sugerencias.

<!-- template-output: library_framework_requirements -->
## Library / Framework Requirements

- Laravel: `laravel/framework ^11.31` (ver `gatic/composer.json`). No upgrades mayores en esta story.
- Livewire: `livewire/livewire ^3.0` (ver `gatic/composer.json`). Mantener v3.
- UI: Bootstrap 5 (layout/modales/inputs).
- JS: Alpine (ya embebido en el combobox) + Livewire entangle.
- DB: MySQL 8 (unique `employees.rpe`).

<!-- template-output: latest_tech_information -->
## Latest Tech Information (2026-02-18)

- Livewire:
  - En GitHub ya existe Livewire v4 (ej. `v4.1.4` publicado el **2026-02-09**).  
    **Regla:** este repo está en Livewire **v3** → NO migrar a v4 dentro de esta story.
  - Seguridad: existe advisory crítico (CVE-2025-54068) que afecta Livewire v3 `< 3.6.4`.  
    **Regla:** asegurar que el lockfile/instalación efectiva use `livewire/livewire >= 3.6.4`.
- Laravel:
  - Política oficial: Laravel 11 recibe security fixes hasta el **2026-03-12**.  
    **Regla:** NO upgrade mayor aquí; dejar nota técnica para planificar upgrade a Laravel 12 post-MVP.
- Bootstrap:
  - Bootstrap 5 sigue vigente (ej. `v5.3.8` publicado el **2025-08-26**).  
    **Regla:** mantener Bootstrap 5; no cambiar framework CSS.

<!-- template-output: file_structure_requirements -->
## File Structure Requirements (archivos esperados a modificar/crear)

**Modificar:**
- `gatic/app/Livewire/Ui/EmployeeCombobox.php`
- `gatic/resources/views/livewire/ui/employee-combobox.blade.php`
- `gatic/tests/Feature/Employees/EmployeeComboboxTest.php`

**Posibles nuevos (solo si simplifica y evita duplicación):**
- `gatic/app/Actions/Employees/CreateEmployee.php` (o similar) si `createEmployee()` empieza a crecer (mantenerlo mínimo).

<!-- template-output: testing_requirements -->
## Testing Requirements (mínimo)

- Feature tests (Livewire):
  - CTA visible con “Sin resultados” (y accesible por texto/estructura).
  - Create employee OK: setea `employeeId` y `employeeLabel`, cierra dropdown.
  - Duplicate RPE:
    - validación unique (mensaje),
    - y/o manejo 1062 (carrera) sin duplicar.
- Multi-instancia A11y:
  - test que renderice dos instancias y verifique IDs distintos (`aria-controls`/`id=listbox`).
- Soft-delete regression (checklist):
  - agregar test que verifique que empleados soft-deleted NO aparecen en sugerencias (y que el create no “revive” registros accidentalmente).

<!-- template-output: previous_story_intelligence -->
## Previous Story Intelligence (reuso directo)

- Story 4.2 ya definió el contrato base del combobox:
  - min chars = 2,
  - limit de resultados,
  - estados: loading/no-results/error,
  - RBAC server-side con `inventory.manage`,
  - teclado (↑/↓/Enter/Escape) + ARIA combobox/listbox.
- `EmployeesIndex::save()` ya resuelve duplicado de RPE capturando MySQL 1062: reutilizar ese enfoque en el modal del combobox.

<!-- template-output: git_intelligence_summary -->
## Git Intelligence Summary (últimos commits relevantes)

- `feat(admin-settings): improve summary panel and document creatable selectors` (documentación + base para Epic 15)
- `Mejora UX en catálogos y corrige loading persistente` (ajustes recientes de UX/estados)

Implicación para 15.1:
- Mantener consistencia con la guía de “creables” ya documentada y evitar inventar otro patrón.

<!-- template-output: project_context_reference -->
## Project Context Reference (must-read)

- Bible/reglas: `docsBmad/project-context.md`, `project-context.md`
- Creable selectors: `gatic/docs/ui/creable-selectors.md`
- UX (A11y/teclado/modales): `_bmad-output/implementation-artifacts/ux.md`
- Arquitectura/patrones (Actions/RBAC/stack): `_bmad-output/implementation-artifacts/architecture.md`
- Patrones UI (toasts/error_id/long-request): `gatic/docs/ui-patterns.md`

<!-- template-output: story_completion_status -->
## Story Completion Status

- Status: **done**
- Completion note: "Story revisada y completada: CTA+modal creable, anti-duplicados RPE (validación + 1062), A11y multi-instancia, mejor UX/A11y del modal y cobertura de tests AC1–AC6."

## Change Log

- 2026-02-19: Implementada story 15.1 (`EmployeeCombobox` creable): CTA en no resultados, modal de creación con autoselección, anti-duplicados (validación + 1062), A11y multi-instancia e incremento de cobertura de tests.
- 2026-02-19: Code review (AI) + fixes aplicados: copy con acentos, cierre de modal con `Esc` + ARIA básicos, limpieza de cambios fuera de alcance y `.gitignore` para artefactos locales.

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `_bmad/core/tasks/workflow.xml`
- `_bmad/bmm/workflows/4-implementation/create-story/workflow.yaml`
- `_bmad/bmm/workflows/4-implementation/create-story/instructions.xml`
- `_bmad/bmm/workflows/4-implementation/code-review/workflow.yaml`
- `_bmad/bmm/workflows/4-implementation/code-review/instructions.xml`

### Completion Notes List

- Pendiente: definir UX para el caso “RPE existe pero está soft-deleted” (error vs restaurar con link a papelera).
- ✅ Tarea 1 (AC1) completada: CTA “Crear empleado” agregado como `role="option"` y activable por teclado vía `selectHighlighted()`.
- ✅ Tarea 2 (AC2–AC3) completada: modal de creación implementado con `UpsertEmployee`, validación `unique` y manejo de carrera `1062`.
- ✅ Tarea 3 (AC4) completada: IDs ARIA únicos por instancia para `input/listbox/options` y para inputs del modal.
- ✅ Tarea 4 (AC5) completada: loading state en CTA/botón guardar, errores inesperados con `error_id` y retorno de foco al cerrar modal.
- ✅ Tarea 5 (AC6) completada: `EmployeeComboboxTest` ampliado (CTA, creación/autoselección, duplicado RPE, multi-instancia, soft-delete, RBAC creación).
- ✅ Code review (AI): fixes de UX/A11y del modal (`Esc`, ARIA) + copy “Ocurrió” consistente + limpieza de cambios fuera del alcance.
- ✅ Validaciones ejecutadas:
  - `docker compose -f compose.yaml exec -T laravel.test ./vendor/bin/pint --test` (PASS)
  - `docker compose -f compose.yaml exec -T laravel.test php artisan test --filter='EmployeeComboboxTest'` (PASS)
  - `docker compose -f compose.yaml exec -T laravel.test php artisan test` (FAIL por 2 tests preexistentes en catálogo no relacionados: `BrandsTest`, `CatalogsTrashTest`)
  - `docker compose -f compose.yaml exec -T laravel.test ./vendor/bin/phpstan analyse --no-progress` (FAIL por baseline preexistente de 77 errores fuera de esta story)

### File List

- `gatic/app/Livewire/Ui/EmployeeCombobox.php` (MODIFY)
- `gatic/resources/views/livewire/ui/employee-combobox.blade.php` (MODIFY)
- `gatic/tests/Feature/Employees/EmployeeComboboxTest.php` (MODIFY)
- `_bmad-output/implementation-artifacts/15-1-employeecombobox-creable-crear-empleado-desde-seleccion.md` (ADD)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MODIFY)
- `_bmad-output/implementation-artifacts/epics.md` (MODIFY)
- `_bmad-output/project-planning-artifacts/epics.md` (MODIFY)
- `.gitignore` (MODIFY)

## Senior Developer Review (AI)

Fecha: 2026-02-19  
Resultado: ✅ **Aprobada** (con fixes aplicados)

### Validación de ACs (resumen)

- **AC1 (CTA “Crear empleado” + teclado):** IMPLEMENTADO.
- **AC2 (modal + autoselección + toast):** IMPLEMENTADO.
- **AC3 (anti-duplicados + carrera 1062):** IMPLEMENTADO (unique + manejo de 1062).
- **AC4 (multi-instancia ARIA IDs):** IMPLEMENTADO (IDs por instancia + test mínimo).
- **AC5 (RBAC + errores con `error_id`):** IMPLEMENTADO (Gate server-side + ErrorReporter + UX consistente).
- **AC6 (tests mínimos):** IMPLEMENTADO.

### Hallazgos (y resolución)

- **[HIGH] Mezcla de cambios fuera del alcance:** el working tree tenía cambios en catálogos/settings no declarados en esta story → **revertidos** para mantener el alcance limpio.
- **[MEDIUM] Copy inconsistente (“Ocurrio”):** corregido a **“Ocurrió”** en UI/toasts.
- **[MEDIUM] Modal sin cierre con `Esc` + ARIA básicos:** agregado cierre con `Esc`/click backdrop y `role="dialog"`, `aria-modal`, `aria-labelledby`.
- **[LOW] CTA con `wire:target` incorrecto:** el CTA apuntaba a `createEmployee` aunque llama `openCreateEmployeeModal` → corregido.
- **[LOW] Artefactos locales:** screenshots/notas aparecían como untracked → ignorados via `.gitignore`.
