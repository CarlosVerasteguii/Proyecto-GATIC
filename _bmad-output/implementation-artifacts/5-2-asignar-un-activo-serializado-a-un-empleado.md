# Story 5.2: Asignar un Activo serializado a un Empleado

Status: done

Story Key: `5-2-asignar-un-activo-serializado-a-un-empleado`  
Epic: `5` (Gate 3: Operación diaria)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Backlog (fuente de verdad): `_bmad-output/implementation-artifacts/epics.md` (Epic 5, Story 5.2; FR17)

Fuentes:
- `_bmad-output/implementation-artifacts/epics.md` (Epic 5, Story 5.2; FR17)
- `_bmad-output/implementation-artifacts/prd.md` (FR17; Journey “registrar movimiento”; NFR7)
- `_bmad-output/implementation-artifacts/ux.md` (movimientos: mínimo obligatorio Receptor + Nota; microcopy; estados/badges)
- `_bmad-output/implementation-artifacts/architecture.md` (estructura y convenciones; Epic 5: `app/Models/*Movement*.php`, `app/Actions/Movements/*`)
- `docsBmad/project-context.md` (bible: reglas no negociables; estados canónicos; semántica de disponibilidad)
- `project-context.md` (stack; transacciones/locks; reglas críticas para agentes)
- `docsBmad/rbac.md` (gates `inventory.manage`, defensa en profundidad)
- `_bmad-output/implementation-artifacts/5-1-reglas-de-estado-y-transiciones-para-activos-serializados.md` (reuso: `AssetStatusTransitions`)
- `gatic/app/Support/Assets/AssetStatusTransitions.php` (API de reglas de transición)
- `gatic/app/Exceptions/AssetTransitionException.php` (mensajes accionables + `toValidationException()`)
- `gatic/app/Livewire/Ui/EmployeeCombobox.php` + `gatic/resources/views/livewire/ui/employee-combobox.blade.php` (selector reusable)
- `gatic/app/Actions/Employees/SearchEmployees.php` (búsqueda/escape LIKE + límite)
- `gatic/app/Livewire/Inventory/Assets/AssetShow.php` + `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php` (Tenencia actual: placeholder N/A)
- `gatic/app/Livewire/Employees/EmployeeShow.php` + `gatic/resources/views/livewire/employees/employee-show.blade.php` (secciones “Activos asignados/prestados”)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Admin/Editor,  
I want asignar un Activo serializado a un Empleado,  
so that quede responsable claro del equipo (FR17).

## Alcance

Incluye:
- Registrar una asignación (movimiento) de un Activo serializado a un Empleado (RPE).
- Mínimo obligatorio en el movimiento: **Empleado + Nota** (adoption-first).
- Actualizar estado del Activo a `Asignado` y persistir la **tenencia actual** vinculada al Empleado.
- Reflejar la tenencia actual en UI (al menos en el detalle del Activo).
- Defensa en profundidad: autorización server-side + validaciones consistentes.

No incluye (explícitamente fuera de esta historia):
- Flujos de préstamo/devolución (Story 5.3).
- Movimientos por cantidad y kardex (Stories 5.4/5.5).
- Workflow de “desasignar” (aunque las reglas existan, no está en backlog actual).

## Acceptance Criteria

### AC1 - Acceso por rol (defensa en profundidad)

**Given** un usuario autenticado con rol Admin o Editor  
**When** abre el formulario de asignación o ejecuta la acción de asignar  
**Then** el servidor permite la operación

**Given** un usuario autenticado con rol Lector  
**When** intenta ejecutar la acción (URL directa o request Livewire)  
**Then** el servidor bloquea la operación (403 o equivalente)

### AC2 - Asignación exitosa (Disponible → Asignado)

**Given** un Activo en estado `Disponible`  
**When** el usuario lo asigna a un Empleado, captura una **nota obligatoria** y guarda  
**Then** el Activo pasa a estado `Asignado`  
**And** queda registrada la tenencia actual asociada al Empleado

### AC3 - Validación: nota obligatoria

**Given** el formulario de asignación  
**When** el usuario intenta guardar sin nota  
**Then** el sistema bloquea la operación  
**And** muestra un mensaje de validación indicando que la nota es obligatoria

## Tasks / Subtasks

1) Modelo de datos (AC: 2)
- [x] Crear tabla de movimientos de activos serializados (e.g. `asset_movements`) con `asset_id`, `employee_id`, `actor_user_id`, `type`, `note`, timestamps + índices.
- [x] (Recomendado) Persistir tenencia actual en `assets.current_employee_id` (FK a `employees`) para consultas rápidas y para poblar UI.
- [x] Modelos/relaciones mínimas: `AssetMovement` y relaciones en `Asset`/`Employee` (sin romper compatibilidad).

2) Caso de uso transaccional (AC: 2, 3)
- [x] Action en `gatic/app/Actions/Movements/*` que ejecute asignación con `DB::transaction()` + `lockForUpdate()`.
- [x] Reusar `AssetStatusTransitions::assertCanAssign(...)` y convertir `AssetTransitionException` a error de validación.
- [x] Registrar movimiento con nota obligatoria (y actor) en la tabla de movimientos.

3) UI: formulario de asignación (AC: 1-3)
- [x] Exponer un punto de entrada UI (botón "Asignar" desde detalle de Activo o ruta dedicada) solo para `inventory.manage`.
- [x] Reusar `EmployeeCombobox` para seleccionar Empleado y capturar Nota.
- [x] Feedback: validación inline + toast de éxito; errores inesperados con `error_id`.

4) UI: tenencia actual y asociaciones (AC: 2)
- [x] Actualizar `Tenencia actual` en detalle de Activo para mostrar el Empleado cuando aplique.
- [x] Poblar en ficha de Empleado la sección "Activos asignados" (y dejar "Activos prestados" preparado para Story 5.3).

5) Tests (AC: 1-3)
- [x] Feature tests de RBAC + happy path + validación de nota.
- [x] Mantener tests deterministas; usar `RefreshDatabase` cuando aplique.

## Dev Notes

### Reuso (NO reinventar ruedas)

- `AssetStatusTransitions` + `AssetTransitionException` ya existen (Story 5.1): reutilizar, no duplicar reglas.
- `EmployeeCombobox` + `SearchEmployees` ya existen (Story 4.2): reutilizar para seleccionar Empleado.
- Patrón transaccional + `lockForUpdate()` ya existe en `ApplyAssetAdjustment` (Epic 3): copiar el estilo.

### UX (MVP)

- "Adoption-first": movimiento mínimo = Receptor + Nota; evitar fricción (sin pasos innecesarios).
- UI en Bootstrap 5; feedback con toasts; errores inesperados con `error_id`.

### Diseño propuesto (MVP, alineado a este repo)

**Persistencia (mínima y extensible a 5.3):**
- Tabla `asset_movements` (histórico): `asset_id`, `employee_id`, `actor_user_id`, `type` (e.g., `assign`), `note`, timestamps.
- (Recomendado) Columna `assets.current_employee_id` (nullable) como “tenencia actual” para consultas rápidas y para poblar UI.

**Caso de uso “asignar” (recomendado):**
- `DB::transaction()` → `Asset::query()->lockForUpdate()->findOrFail(...)`.
- Validar estado con `AssetStatusTransitions::assertCanAssign($asset->status)` antes de mutar.
- Persistir:
  - `assets.status = Asignado`
  - `assets.current_employee_id = <employee_id>`
  - insertar fila en `asset_movements` con `note` obligatoria y `actor_user_id = auth()->id()`.

**UI (mínimo):**
- Entry point desde detalle del Activo (botón “Asignar” visible solo con `inventory.manage`).
- Formulario Livewire con `EmployeeCombobox` + textarea Nota (obligatoria); al guardar: toast + redirect a detalle del Activo.
- Tenencia actual:
  - si `Asignado/Prestado` y existe `current_employee_id` → mostrar `RPE - Nombre`.
  - si estado indica tenencia pero `current_employee_id` es null (legacy/ajustes) → mostrar warning “Sin tenencia registrada”.

## Requisitos técnicos (guardrails para el dev agent)

- Stack fijo: Laravel 11 + Livewire 3 + Bootstrap 5 + MySQL 8.0. No introducir librerías nuevas para esta story. [Source: `project-context.md`, `docsBmad/project-context.md`]
- Identificadores de código/DB/rutas en inglés; copy/mensajes UI en español. [Source: `project-context.md`]
- Autorización obligatoria server-side en cada entrypoint (ruta + métodos Livewire + Actions). Gate esperado: `inventory.manage`. [Source: `docsBmad/rbac.md`, `project-context.md`]
- Operación crítica atómica: `DB::transaction()` + `lockForUpdate()` sobre el Activo antes de validar y persistir. [Source: `project-context.md`, `gatic/app/Actions/Inventory/Adjustments/ApplyAssetAdjustment.php`]
- Validaciones mínimas:
  - `employee_id`: requerido y existente.
  - `note`: requerida (recomendado `min:5`, `max:1000`).
  - Activo: existe, no soft-deleted, pertenece al Producto serializado correcto y está en `Disponible` para asignar.
- Mensajes accionables: si falla por estado, usar `AssetTransitionException` (mensajes en español) y mapear a `ValidationException` del campo correcto (sin 500). [Source: `gatic/app/Support/Assets/AssetStatusTransitions.php`, `gatic/app/Exceptions/AssetTransitionException.php`]
- Errores inesperados: generar `error_id` y mostrar mensaje amigable (detalle técnico solo Admin). [Source: `gatic/docs/ui-patterns.md`]

## Cumplimiento de arquitectura (guardrails)

- Respetar la separación UI ↔ dominio:
  - UI (Livewire) orquesta y valida input.
  - Dominio/transacción vive en `app/Actions/*` (no duplicar lógica en el componente). [Source: `_bmad-output/implementation-artifacts/architecture.md`]
- Ubicación esperada para Epic 5 (Movimientos):
  - Actions: `gatic/app/Actions/Movements/*`
  - Livewire: `gatic/app/Livewire/Movements/*` (o integrar desde `Inventory/*` pero llamando Actions de Movements)
  - Models: `gatic/app/Models/*Movement*.php` [Source: `_bmad-output/implementation-artifacts/architecture.md`]
- Convenciones:
  - Tablas: `snake_case` + plural; columnas `snake_case`; clases `StudlyCase`. [Source: `_bmad-output/implementation-artifacts/architecture.md`]
  - Rutas/identificadores en inglés (kebab-case); copy UI en español. [Source: `project-context.md`]
- Operaciones críticas (movimientos/estados): transaccionales y con locks; evitar inconsistencias por concurrencia. [Source: `project-context.md`, `_bmad-output/implementation-artifacts/architecture.md`]

## Requisitos de librerías/frameworks (no inventar ruedas)

- Laravel 11: usar Eloquent + migraciones + validación estándar; no crear “helpers globales”. [Source: `project-context.md`]
- Livewire 3: componentes como entrypoint de rutas; autorización también dentro del componente (defensa en profundidad). [Source: `project-context.md`, `docsBmad/rbac.md`]
- Bootstrap 5: UI consistente con `03-visual-style-guide.md`; usar componentes existentes (`x-ui.toast-container`, `<x-ui.long-request />`, etc.). [Source: `project-context.md`, `gatic/docs/ui-patterns.md`]
- RBAC: roles fijos MVP; no introducir paquetes de permisos complejos. [Source: `project-context.md`, `docsBmad/rbac.md`]

### Project Structure Notes (archivos a tocar)

- Nuevas migraciones: `gatic/database/migrations/*_create_asset_movements_table.php` (+ opcional `*_add_current_employee_id_to_assets_table.php`).
- Nuevo modelo: `gatic/app/Models/AssetMovement.php`.
- Nueva Action (caso de uso): `gatic/app/Actions/Movements/Assets/AssignAssetToEmployee.php` (o nombre equivalente).
- Nuevo Livewire (form): `gatic/app/Livewire/Movements/Assets/AssignAssetForm.php` + `gatic/resources/views/livewire/movements/assets/assign-asset-form.blade.php`.
- Rutas: `gatic/routes/web.php` (mantener convención route→Livewire; decidir path/nombre y documentarlo en la story).
- Modificar (tenencia): `gatic/app/Models/Asset.php` + `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php`.
- Modificar (asociaciones): `gatic/app/Livewire/Employees/EmployeeShow.php` + `gatic/resources/views/livewire/employees/employee-show.blade.php`.
- Tests: `gatic/tests/Feature/Movements/*` (RBAC + happy path + validación).

## Testing Requirements

- Feature tests (mínimo):
  - RBAC: Admin/Editor pueden asignar; Lector recibe 403 (ruta + acción Livewire).
  - Happy path: asignación crea movimiento + actualiza `assets.status` a `Asignado` y asocia tenencia actual al Empleado.
  - Validación: nota obligatoria (no persiste cambios si falta).
- Actualizar tests existentes que hoy asumen “tenencia N/A”, por ejemplo: `gatic/tests/Feature/Inventory/AssetsTest.php` (test de “Tenencia actual: N/A”).
- Mantener tests deterministas (sin dependencias externas); usar `RefreshDatabase`.

## Previous Story Intelligence (aprovechar lo ya hecho)

- Reglas de estado (Story 5.1): `AssetStatusTransitions` y `AssetTransitionException` ya establecen el contrato para permitir/bloquear `Asignar` y para mensajes accionables. Reusar tal cual; no duplicar reglas por UI. [Source: `gatic/app/Support/Assets/AssetStatusTransitions.php`, `gatic/app/Exceptions/AssetTransitionException.php`]
- Patrón transaccional: el repo ya usa `DB::transaction()` + `lockForUpdate()` (ver `ApplyAssetAdjustment`). La asignación debe seguir el mismo patrón para evitar condiciones de carrera. [Source: `gatic/app/Actions/Inventory/Adjustments/ApplyAssetAdjustment.php`]
- Selector de Empleado (Story 4.2): `EmployeeCombobox` + `SearchEmployees` ya resuelven UX/teclado, escape LIKE, límite de resultados y RBAC. Reusar para el campo Empleado. [Source: `gatic/app/Livewire/Ui/EmployeeCombobox.php`, `gatic/app/Actions/Employees/SearchEmployees.php`]
- Placeholders a reemplazar:
  - Detalle de Activo hoy muestra “Tenencia actual: N/A” (Story 3.5) → se debe poblar aquí. [Source: `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php`]
  - Ficha de Empleado ya tiene secciones “Activos asignados/prestados” listas para poblar. [Source: `gatic/app/Livewire/Employees/EmployeeShow.php`]

## Git Intelligence Summary (para evitar regresiones)

- Últimos commits relevantes:
  - `0e06547` implementa Story 5.1: agrega `AssetStatusTransitions` + `AssetTransitionException` + tests Unit; actualiza `sprint-status.yaml`.
  - `1f709c6` implementa Story 4.2: `EmployeeCombobox` + `SearchEmployees` (reuso directo para asignación).
  - `d555378` implementa Story 4.3: ficha de Empleado con secciones de activos (listas para poblar).
- Puntos sensibles a regresión:
  - Tests existentes de Inventario asumen “Tenencia actual: N/A” y deberán actualizarse (Story 3.5). [Source: `gatic/tests/Feature/Inventory/AssetsTest.php`]

## Latest Technical Information (web research)

- Livewire 3 (security): reforzar autorización server-side dentro de componentes y acciones para requests Livewire (defensa en profundidad). [Source: https://livewire.laravel.com/docs/3.x/security]
- Livewire 3 (validation/forms): usar validación estándar (`rules()`, `validationAttributes()`, `validate()`) y mostrar errores inline. [Source: https://livewire.laravel.com/docs/3.x/validation, https://livewire.laravel.com/docs/3.x/forms]
- Laravel 11 (DB transactions): operaciones críticas dentro de `DB::transaction()`. [Source: https://laravel.com/docs/11.x/database]
- Laravel 11 (pessimistic locking): usar `lockForUpdate()` para evitar carreras al mutar el Activo. [Source: https://laravel.com/docs/11.x/queries]

## Project Context Reference (leer antes de codear)

- `docsBmad/project-context.md` (bible; si hay conflicto, gana este documento).
- `project-context.md` (reglas críticas para agentes; stack; transacciones; tests).
- `_bmad-output/implementation-artifacts/architecture.md` (estructura, convenciones, mapeo Epic 5).
- `_bmad-output/implementation-artifacts/ux.md` (principios UX para movimientos: mínimo obligatorio, microcopy, estados/badges).
- `docsBmad/rbac.md` (gates y defensa en profundidad).
- `_bmad-output/implementation-artifacts/epics.md` (Epic 5 / Story 5.2; FR17).

### References

- Backlog/AC base: `_bmad-output/implementation-artifacts/epics.md` (Epic 5 / Story 5.2; FR17).
- PRD: `_bmad-output/implementation-artifacts/prd.md` (FR17; NFR7).
- UX: `_bmad-output/implementation-artifacts/ux.md` (movimientos: mínimo obligatorio Receptor + Nota; microcopy; estados/badges).
- Arquitectura: `_bmad-output/implementation-artifacts/architecture.md` (estructura; Epic 5 → Movements; patrones de tests).
- Bible: `docsBmad/project-context.md` (reglas no negociables; estados canónicos; semántica de disponibilidad).
- RBAC: `docsBmad/rbac.md` (gates; defensa en profundidad).
- Reuso (estado/errores): `gatic/app/Support/Assets/AssetStatusTransitions.php`, `gatic/app/Exceptions/AssetTransitionException.php`.
- Reuso (empleados): `gatic/app/Livewire/Ui/EmployeeCombobox.php`, `gatic/app/Actions/Employees/SearchEmployees.php`.
- Patrón transaccional existente: `gatic/app/Actions/Inventory/Adjustments/ApplyAssetAdjustment.php`.
- UI/tests a actualizar: `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php`, `gatic/tests/Feature/Inventory/AssetsTest.php`.

## Story Completion Status

- Status: `done`
- Nota: "Implementación y review completados. Movimientos de asignación implementados con RBAC server-side, transacción + lock, nota obligatoria, UI y tests."

## Senior Developer Review (AI)

### Hallazgos (resueltos)

- [HIGH] Trazabilidad en riesgo: `asset_movements` borraba historial por `cascadeOnDelete()`. Fix: FKs con `restrictOnDelete()` para evitar borrado accidental del historial.
- [MEDIUM] `asset_movements.type` sin enforcement. Fix: columna `enum` con tipos canónicos (`assign|unassign|loan|return`).
- [MEDIUM] `AssignAssetForm` hacía redirect en `mount()` sin cortar ejecución. Fix: `return;` tras `redirectRoute(...)`.
- [MEDIUM] Tests en Sail podían fallar por permisos al compilar Blade en `storage/framework/views`. Fix: `VIEW_COMPILED_PATH=/tmp/views` en `gatic/phpunit.xml`.
- [LOW] Microcopy: acentos faltantes en UI/toasts. Fix: “está”, “Asignación”, “Ocurrió”.

### Evidencia (tests)

- `bash vendor/bin/sail artisan test --filter AssetAssignmentTest` PASS (17 tests, 42 assertions)

## Preguntas (guardar para el final, no bloqueantes)

1) ¿Se aprueba guardar “tenencia actual” en `assets.current_employee_id` (recomendado) o debe derivarse solo desde `asset_movements`?
2) ¿Qué política seguimos para “ajustes Admin” que hoy pueden setear `Asignado/Prestado` sin Empleado? (¿bloquear esos estados en ajustes, o permitirlo y mostrar “Sin tenencia registrada”).
3) ¿Regla mínima de nota: solo requerida, o requerida con `min:5` como `reason` en ajustes?

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `Get-Content _bmad-output/implementation-artifacts/sprint-status.yaml` (auto-discovery: primer story en backlog: `5-2-asignar-un-activo-serializado-a-un-empleado`)
- `Get-Content _bmad-output/implementation-artifacts/epics.md` (Epic 5 / Story 5.2; FR17)
- `Get-Content _bmad-output/implementation-artifacts/prd.md` (FR17; NFR7)
- `Get-Content _bmad-output/implementation-artifacts/ux.md` (movimientos: mínimo obligatorio Receptor + Nota)
- `Get-Content _bmad-output/implementation-artifacts/architecture.md` (estructura; Epic 5 → Movements)
- `Get-Content docsBmad/project-context.md` + `Get-Content project-context.md`
- `git log -10 --oneline` + `git show --name-status -1 0e06547`
- `Get-Content gatic/routes/web.php`
- `Get-Content gatic/app/Support/Assets/AssetStatusTransitions.php` + `Get-Content gatic/app/Exceptions/AssetTransitionException.php`
- `Get-Content gatic/app/Livewire/Ui/EmployeeCombobox.php` + `Get-Content gatic/app/Actions/Employees/SearchEmployees.php`
- `Get-Content gatic/resources/views/livewire/inventory/assets/asset-show.blade.php`
- `Get-Content gatic/tests/Feature/Inventory/AssetsTest.php`
- Web docs: https://livewire.laravel.com/docs/3.x/security, https://livewire.laravel.com/docs/3.x/validation, https://laravel.com/docs/11.x/database, https://laravel.com/docs/11.x/queries

### Completion Notes List

- Story seleccionada automáticamente desde `sprint-status.yaml` (primer `backlog` en `development_status`).
- Guardrails explícitos para evitar errores típicos: RBAC server-side, reuso de reglas de estado, transacciones + locks, UX "receptor + nota", y actualización de tests sensibles.
- Documento marcado `ready-for-dev` para correr `dev-story`.
- **Implementación completada (2026-01-15):**
  - Migraciones: tabla `asset_movements` + columna `current_employee_id` en `assets`
  - Modelo `AssetMovement` con relaciones a Asset, Employee, User
  - Relaciones inversas en `Asset` (currentEmployee, movements) y `Employee` (assignedAssets, loanedAssets, assetMovements)
  - Action transaccional `AssignAssetToEmployee` con validación de estado vía `AssetStatusTransitions`
  - Livewire `AssignAssetForm` con reuso de `EmployeeCombobox`, validación inline y toasts
  - UI actualizada en `AssetShow` con botón "Asignar" y tenencia actual dinámica
  - UI actualizada en `EmployeeShow` con listas de activos asignados/prestados
  - 17 Feature tests cubriendo RBAC, happy path y validaciones
  - Test existente actualizado para reflejar nuevo mensaje de tenencia
  - Pint y PHPStan pasan (archivos de esta story)

### File List

- `_bmad-output/implementation-artifacts/5-2-asignar-un-activo-serializado-a-un-empleado.md` (UPDATED - status + Senior Developer Review (AI))
- `gatic/database/migrations/2026_01_16_000000_create_asset_movements_table.php` (NEW)
- `gatic/database/migrations/2026_01_16_000001_add_current_employee_id_to_assets_table.php` (NEW)
- `gatic/app/Models/AssetMovement.php` (NEW)
- `gatic/app/Actions/Movements/Assets/AssignAssetToEmployee.php` (NEW)
- `gatic/app/Livewire/Movements/Assets/AssignAssetForm.php` (NEW)
- `gatic/resources/views/livewire/movements/assets/assign-asset-form.blade.php` (NEW)
- `gatic/tests/Feature/Movements/AssetAssignmentTest.php` (NEW)
- `gatic/app/Models/Asset.php` (UPDATED - added currentEmployee, movements relations + current_employee_id property)
- `gatic/app/Models/Employee.php` (UPDATED - added assignedAssets, loanedAssets, assetMovements relations)
- `gatic/app/Livewire/Inventory/Assets/AssetShow.php` (UPDATED - eager load currentEmployee)
- `gatic/app/Livewire/Employees/EmployeeShow.php` (UPDATED - eager load assignedAssets, loanedAssets)
- `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php` (UPDATED - tenencia actual + botón Asignar)
- `gatic/resources/views/livewire/employees/employee-show.blade.php` (UPDATED - tablas de activos asignados/prestados)
- `gatic/routes/web.php` (UPDATED - added assign route)
- `gatic/tests/Feature/Inventory/AssetsTest.php` (UPDATED - test tenencia mensaje actualizado)
- `gatic/phpunit.xml` (UPDATED - set VIEW_COMPILED_PATH to avoid Sail Blade compile permission issues)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (UPDATED)
- `git_diff_3_2.txt` (DELETED - temp diff artifact)

### Change Log

- 2026-01-15: Implementación completa de Story 5.2 - Asignación de activos serializados a empleados (FR17)
- 2026-01-16: Senior Developer Review (AI) - fixes aplicados (FKs restrict, enum `type`, return tras redirect, `VIEW_COMPILED_PATH` en tests, microcopy) y story pasa a `done`.
