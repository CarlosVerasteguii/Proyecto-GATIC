# Story 5.3: Prestar y devolver un Activo serializado

Status: done

Story Key: `5-3-prestar-y-devolver-un-activo-serializado`  
Epic: `5` (Gate 3: Operación diaria)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Backlog (fuente de verdad): `_bmad-output/implementation-artifacts/epics.md` (Epic 5, Story 5.3; FR18, FR19)

Fuentes:
- `_bmad-output/implementation-artifacts/epics.md` (Epic 5, Story 5.3; FR18, FR19)
- `_bmad-output/implementation-artifacts/prd.md` (FR18, FR19; Journey “consulta + préstamo/asignación”; NFR7)
- `_bmad-output/implementation-artifacts/ux.md` (movimientos “adoption-first”: mínimo obligatorio Receptor/Empleado + Nota; acciones rápidas desde detalle; patrones de drawer/offcanvas)
- `_bmad-output/implementation-artifacts/architecture.md` (Epic 5 → `app/Livewire/Movements/*`, `app/Actions/Movements/*`; transacciones + `lockForUpdate()`; organización de tests)
- `docsBmad/project-context.md` (bible: roles, estados canónicos, semántica de disponibilidad, reglas no negociables)
- `project-context.md` (reglas críticas para agentes; stack y testing)
- `docsBmad/rbac.md` (gates `inventory.manage`, defensa en profundidad)
- `_bmad-output/implementation-artifacts/5-1-reglas-de-estado-y-transiciones-para-activos-serializados.md` (reuso: `AssetStatusTransitions` y mensajes accionables)
- `_bmad-output/implementation-artifacts/5-2-asignar-un-activo-serializado-a-un-empleado.md` (patrón de Actions + Livewire; `asset_movements`; `current_employee_id`)
- `gatic/app/Support/Assets/AssetStatusTransitions.php` (reglas `assertCanLoan()` / `assertCanReturn()`)
- `gatic/app/Exceptions/AssetTransitionException.php` (mensajes accionables + `toValidationException()`)
- `gatic/app/Models/AssetMovement.php` + `gatic/database/migrations/2026_01_16_000000_create_asset_movements_table.php` (tipos `loan`, `return`)
- `gatic/app/Actions/Movements/Assets/AssignAssetToEmployee.php` + `gatic/app/Livewire/Movements/Assets/AssignAssetForm.php` (patrón de implementación existente a replicar)
- `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php` (botonera de acciones por estado/permiso)
- `gatic/routes/web.php` (rutas Livewire por módulo + middleware `can:inventory.manage`)
- `gatic/tests/Feature/Movements/AssetAssignmentTest.php` (patrón de feature tests para movimientos)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Admin/Editor,  
I want prestar un Activo y registrar su devolución,  
so that exista trazabilidad del préstamo y la operación diaria no dependa de memoria/Excel (FR18, FR19).

## Alcance

Incluye:
- Préstamo de Activos serializados: `Disponible → Prestado`.
- Registro de devolución: `Prestado → Disponible` (según reglas actuales de `AssetStatusTransitions`).
- Mínimo obligatorio en movimientos (adoption-first): **Empleado (RPE) + Nota**.
- Persistir tenencia actual (empleado) mientras esté `Prestado`, y limpiarla al devolver.
- Registrar ambos eventos en `asset_movements` con `type=loan` y `type=return`, incluyendo `actor_user_id`.
- UI consistente con el patrón ya implementado en Story 5.2 (Livewire page/form + toasts + validación inline).
- Defensa en profundidad: RBAC server-side en rutas y componentes Livewire.

No incluye (fuera de scope):
- “Desasignar” (`Asignado → Disponible`) si aún no está en backlog.
- Movimientos por cantidad y kardex (Stories 5.4/5.5).
- Ajustes de inventario (Admin) o cambios de catálogo.
- Cambios mayores de UX (drawer global, re-diseño de badges); solo agregar lo mínimo para prestar/devolver.

## Acceptance Criteria

### AC1 — Acceso por rol (defensa en profundidad)

**Given** un usuario autenticado con rol Admin o Editor  
**When** abre o ejecuta préstamo/devolución (UI o request Livewire)  
**Then** el servidor permite la operación

**Given** un usuario autenticado con rol Lector  
**When** intenta ejecutar préstamo/devolución (URL directa o request Livewire)  
**Then** el servidor bloquea la operación (403 o equivalente)

### AC2 — Préstamo exitoso (Disponible → Prestado)

**Given** un Activo en estado `Disponible`  
**When** el usuario lo presta a un Empleado y captura una **nota obligatoria**  
**Then** el Activo pasa a estado `Prestado`  
**And** `assets.current_employee_id` queda en el Empleado seleccionado  
**And** se crea un registro en `asset_movements` con `type=loan` y la nota asociada

### AC3 — Validación: nota obligatoria en préstamo

**Given** el formulario de préstamo  
**When** el usuario intenta guardar sin nota  
**Then** el sistema bloquea la operación  
**And** muestra un mensaje de validación indicando que la nota es obligatoria

### AC4 — Devolución exitosa (Prestado → Disponible)

**Given** un Activo en estado `Prestado`  
**When** el usuario registra la devolución y captura una **nota obligatoria**  
**Then** el Activo vuelve a estado `Disponible`  
**And** `assets.current_employee_id` queda `null` (tenencia actual vacía)  
**And** se crea un registro en `asset_movements` con `type=return` y la nota asociada

### AC5 — Validación: nota obligatoria en devolución

**Given** el formulario de devolución  
**When** el usuario intenta guardar sin nota  
**Then** el sistema bloquea la operación  
**And** muestra un mensaje de validación indicando que la nota es obligatoria

### AC6 — Reglas de estado y consistencia transaccional (NFR7)

**Given** un Activo en estado `Asignado`  
**When** el usuario intenta prestarlo  
**Then** el sistema bloquea la acción y guía el siguiente paso (reusar mensajes de `AssetTransitionException`)

**Given** un Activo que no está `Prestado`  
**When** el usuario intenta devolverlo  
**Then** el sistema bloquea la acción con mensaje accionable

**Given** dos usuarios intentando prestar/devolver el mismo Activo casi al mismo tiempo  
**When** se procesan las acciones  
**Then** el sistema mantiene integridad (operación atómica + `lockForUpdate()`; sin estados inconsistentes)

## Tasks / Subtasks

1) Caso de uso: préstamo (AC: 1–3, 6)
- [x] Action transaccional en `gatic/app/Actions/Movements/Assets/LoanAssetToEmployee.php`
  - [x] Validar `asset_id` (exists + `deleted_at` null), `employee_id`, `note` (min 5, max 1000), `actor_user_id`
  - [x] `DB::transaction()` + `Asset::lockForUpdate()` y `AssetStatusTransitions::assertCanLoan($asset->status)`
  - [x] Setear `status=Prestado` + `current_employee_id=employee_id`
  - [x] Crear `AssetMovement` con `type=loan`

2) Caso de uso: devolución (AC: 1, 4–6)
- [x] Action transaccional en `gatic/app/Actions/Movements/Assets/ReturnLoanedAsset.php`
  - [x] Validar `asset_id`, `note`, `actor_user_id`
  - [x] `DB::transaction()` + `Asset::lockForUpdate()` y `AssetStatusTransitions::assertCanReturn($asset->status)`
  - [x] Derivar `employee_id` para el movimiento:
    - [x] Si `current_employee_id` existe, usarlo (recomendado: no editable en UI)
    - [x] Si no existe (estado legacy/ajuste manual), requerir seleccionar Empleado en la UI para no perder trazabilidad
  - [x] Setear `status=Disponible` + `current_employee_id=null`
  - [x] Crear `AssetMovement` con `type=return`

3) UI: préstamo (AC: 1–3)
- [x] Ruta `inventory.products.assets.loan` con middleware `can:inventory.manage`
- [x] Livewire `gatic/app/Livewire/Movements/Assets/LoanAssetForm.php` + view `gatic/resources/views/livewire/movements/assets/loan-asset-form.blade.php`
  - [x] Reusar `livewire:ui.employee-combobox` (mismo patrón que asignación)
  - [x] Nota obligatoria con contador `X/1000`
  - [x] Toast success/error y spinner “Prestando…”
  - [x] Redirección a `inventory.products.assets.show`

4) UI: devolución (AC: 1, 4–5)
- [x] Ruta `inventory.products.assets.return` con middleware `can:inventory.manage`
- [x] Livewire `gatic/app/Livewire/Movements/Assets/ReturnAssetForm.php` + view `gatic/resources/views/livewire/movements/assets/return-asset-form.blade.php`
  - [x] Mostrar Empleado actual si existe (bloqueado/no editable) y exigir Nota
  - [x] Fallback: si no hay `currentEmployee`, pedir Empleado con combobox (solo para preservar trazabilidad)
  - [x] Toast success/error y spinner “Devolviendo…”
  - [x] Redirección a `inventory.products.assets.show`

5) Entry points: botones en detalle de Activo (AC: 1, 2, 4)
- [x] En `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php`:
  - [x] Mostrar botón **Prestar** cuando `AssetStatusTransitions::canLoan($asset->status)`
  - [x] Mostrar botón **Devolver** cuando `AssetStatusTransitions::canReturn($asset->status)`
  - [x] Mantener patrón existente (iconos Bootstrap Icons, permisos `@can('inventory.manage')`)

6) Tests (AC: 1–6)
- [x] `gatic/tests/Feature/Movements/AssetLoanTest.php`
  - [x] RBAC: Lector no puede acceder/ejecutar
  - [x] Happy path préstamo: estado + tenencia + movimiento creado
  - [x] Validación nota préstamo (required/min)
  - [x] Bloqueo: prestar cuando `Asignado` (mensaje accionable)
- [x] `gatic/tests/Feature/Movements/AssetReturnTest.php`
  - [x] Happy path devolución: estado + tenencia null + movimiento creado
  - [x] Validación nota devolución (required/min)
  - [x] Bloqueo: devolver cuando no está `Prestado`
- [x] Si se actualizan botones/rutas, añadir/ajustar un smoke test mínimo de navegación (solo si ya existe patrón en tests)

## Dev Notes

### Reusar lo existente (NO reinventar)

- Reglas de estado: NO reescribir lógica; reusar `App\Support\Assets\AssetStatusTransitions` y `App\Exceptions\AssetTransitionException`.
- Persistencia de movimientos: NO crear nuevas tablas; `asset_movements` ya soporta `loan` y `return`.
- Selector de Empleado: reusar `livewire:ui.employee-combobox` (ya existe por FR16/Story 4.2 y se usa en 5.2).
- Patrón de UX y feedback: copiar estructura de `AssignAssetForm` (validación inline, toast, redirect).

### Guardrails de consistencia (evitar regresiones)

- Estados son strings canónicos (`Disponible`, `Asignado`, `Prestado`, `Pendiente de Retiro`, `Retirado`); no renombrarlos.
- `current_employee_id` representa el “holder” actual cuando el estado es `Asignado` o `Prestado`. Al devolver: debe quedar `null`.
- Operaciones críticas SIEMPRE atómicas: `DB::transaction()` + `lockForUpdate()` sobre `assets`.
- No introducir nuevas dependencias (librerías) para esto: ya hay stack y patrones definidos.

### UX mínima (adoption-first)

- Campo obligatorio: Nota (mín. 5, máx. 1000) con microcopy claro.
- Préstamo: Empleado + Nota obligatorios.
- Devolución: Nota obligatoria; Empleado se deriva del holder actual (si falta, exigirlo explícitamente).
- Mantener “de consulta a acción” sin pasos extra: botón directo desde el detalle del Activo.

### Project Structure Notes

- Acciones: `gatic/app/Actions/Movements/Assets/*` (consistente con 5.2).
- UI Livewire: `gatic/app/Livewire/Movements/Assets/*` + views en `gatic/resources/views/livewire/movements/assets/*`.
- Rutas: `gatic/routes/web.php` (grupo `inventory` + middleware `can:inventory.manage`).
- Tests:
  - Feature: `gatic/tests/Feature/Movements/*`
  - Unit: solo si aparece lógica pura adicional (evitar duplicar tests de transiciones ya cubiertos en 5.1).

### References

- Backlog/AC base: `_bmad-output/implementation-artifacts/epics.md` (Epic 5 / Story 5.3; FR18, FR19)
- PRD: `_bmad-output/implementation-artifacts/prd.md` (FR18, FR19; NFR7; Journey “consulta + préstamo/asignación”)
- UX: `_bmad-output/implementation-artifacts/ux.md` (acciones rápidas prestar/devolver; mínimo obligatorio Empleado + Nota; patrones de drawer/offcanvas)
- Arquitectura: `_bmad-output/implementation-artifacts/architecture.md` (Epic 5 mapping; transacciones; file organization; tests)
- Reglas de estado: `gatic/app/Support/Assets/AssetStatusTransitions.php` + `gatic/app/Exceptions/AssetTransitionException.php`
- Patrón existente (base a replicar): `gatic/app/Actions/Movements/Assets/AssignAssetToEmployee.php` + `gatic/app/Livewire/Movements/Assets/AssignAssetForm.php`
- RBAC: `docsBmad/rbac.md` (gate `inventory.manage`)

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `Get-Content _bmad-output/implementation-artifacts/sprint-status.yaml` (auto-discovery: primer story listo: `5-3-prestar-y-devolver-un-activo-serializado`)
- `Get-Content _bmad-output/implementation-artifacts/5-3-prestar-y-devolver-un-activo-serializado.md`
- `Get-Content project-context.md` + `Get-Content docsBmad/project-context.md` + `Get-Content docsBmad/rbac.md`
- `Get-Content gatic/app/Support/Assets/AssetStatusTransitions.php`
- `Get-Content gatic/app/Actions/Movements/Assets/AssignAssetToEmployee.php` (patron base)
- `Get-Content gatic/routes/web.php`
- `docker exec -w /var/www/html gatic-laravel.test-1 php artisan test`
- `docker exec -w /var/www/html gatic-laravel.test-1 ./vendor/bin/pint --test`
- `docker exec -w /var/www/html gatic-laravel.test-1 ./vendor/bin/phpstan analyse`

### Completion Notes List

- Implementado prestamo (`Disponible -> Prestado`) y devolucion (`Prestado -> Disponible`) con trazabilidad en `asset_movements` (`loan`/`return`).
- UI Livewire para prestar/devolver con RBAC server-side, nota obligatoria (min 5) y redirect a detalle.
- Botones "Prestar" y "Devolver" agregados al detalle del Activo segun `AssetStatusTransitions`.
- Tests Feature agregados para RBAC, happy paths, validaciones y bloqueos por estado.
- Hardening post-review:
  - Manejo mas claro de errores de transicion (toast + redirect) en UI de prestamo/devolucion.
  - Return robusto si el `current_employee_id` apunta a un empleado inexistente (fallback a seleccion obligatoria).
  - Contador de caracteres multibyte (`mb_strlen`) en UI.
  - Autorizacion consistente en `EmployeeCombobox::closeDropdown()` + cobertura en test.
- Suite completa OK + Pint OK + PHPStan OK (contenedor Sail).

### File List

- `_bmad-output/implementation-artifacts/5-3-prestar-y-devolver-un-activo-serializado.md` (NEW)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MOD)
- `gatic/app/Actions/Employees/SearchEmployees.php` (MOD)
- `gatic/app/Actions/Movements/Assets/LoanAssetToEmployee.php` (NEW)
- `gatic/app/Actions/Movements/Assets/ReturnLoanedAsset.php` (NEW)
- `gatic/app/Livewire/Movements/Assets/AssignAssetForm.php` (MOD)
- `gatic/app/Livewire/Movements/Assets/LoanAssetForm.php` (NEW)
- `gatic/app/Livewire/Movements/Assets/ReturnAssetForm.php` (NEW)
- `gatic/app/Livewire/Ui/EmployeeCombobox.php` (MOD)
- `gatic/app/Support/Assets/AssetStatusTransitions.php` (MOD)
- `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php` (MOD)
- `gatic/resources/views/livewire/movements/assets/loan-asset-form.blade.php` (NEW)
- `gatic/resources/views/livewire/movements/assets/return-asset-form.blade.php` (NEW)
- `gatic/routes/web.php` (MOD)
- `gatic/tests/Feature/Employees/EmployeeComboboxTest.php` (MOD)
- `gatic/tests/Feature/Employees/EmployeeShowTest.php` (MOD)
- `gatic/tests/Feature/Movements/AssetLoanTest.php` (NEW)
- `gatic/tests/Feature/Movements/AssetReturnTest.php` (NEW)

## Story Completion Status

- Status: `done`
- Nota: "Prestamo y devolucion implementados con RBAC, transacciones + lockForUpdate, trazabilidad (asset_movements), UI Livewire, tests y hardening post-review."


