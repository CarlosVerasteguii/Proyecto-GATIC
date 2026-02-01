# Story 13.1: Fecha de vencimiento en préstamos de activos (modelo + UI)

Status: done

Story Key: `13-1-fecha-vencimiento-en-prestamos-de-activos`  
Epic: `13` (Alertas operativas)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Backlog (fuente de verdad): `_bmad-output/implementation-artifacts/epics.md` (Epic 13, Story 13.1)

Fuentes:
- `_bmad-output/implementation-artifacts/epics.md` (Epic 13, Story 13.1)
- `docsBmad/project-context.md` (bible: stack, reglas no negociables, UX/polling)
- `project-context.md` (reglas críticas, tooling local Windows)
- `_bmad-output/architecture.md` (patrones: Livewire → Actions → DB::transaction + lockForUpdate; estructura por módulos)
- `_bmad-output/implementation-artifacts/5-3-prestar-y-devolver-un-activo-serializado.md` (patrones existentes préstamo/devolución + tests)
- `gatic/database/migrations/2026_01_02_000001_create_assets_table.php` (tabla `assets`)
- `gatic/app/Models/Asset.php` (estado + tenencia actual `current_employee_id`)
- `gatic/app/Livewire/Movements/Assets/LoanAssetForm.php` + `gatic/resources/views/livewire/movements/assets/loan-asset-form.blade.php` (UI actual de préstamo)
- `gatic/app/Actions/Movements/Assets/LoanAssetToEmployee.php` + `gatic/app/Actions/Movements/Assets/ReturnLoanedAsset.php` (Actions transaccionales)
- `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php` + `gatic/resources/views/livewire/employees/employee-show.blade.php` (lugares para mostrar vencimiento)
- `gatic/tests/Feature/Movements/AssetLoanTest.php` (tests existentes de préstamo)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Admin/Editor,  
I want capturar una fecha de vencimiento al prestar un activo,  
so that el sistema pueda alertar préstamos vencidos o por vencer.

## Alcance

Incluye:
- Capturar (UI) y persistir (BD) una **fecha de vencimiento** para el **préstamo vigente** de un Activo serializado.
- Validación server-side: fecha válida y **no en el pasado**.
- Mostrar vencimiento (si existe) en:
  - Detalle del Activo.
  - Detalle del Empleado (sección “Activos prestados”).
- Mantener patrones existentes: Livewire page/form + Actions transaccionales + RBAC server-side.

No incluye (fuera de esta story):
- Listados/alertas de vencidos/por vencer y dashboard (Story 13.2).
- Configuración de “ventana de alerta” (7/14/30 días) (Story 13.2).
- Backfill masivo de vencimientos para préstamos históricos.

## Acceptance Criteria

### AC1 — Captura, validación y persistencia

**Given** un usuario autorizado presta un activo  
**When** captura una fecha de vencimiento  
**Then** el sistema valida el dato (fecha válida; no en el pasado)  
**And** persiste la fecha en BD en un campo canónico (para el préstamo vigente).

### AC2 — Visualización

**Given** un activo prestado  
**When** se consulta el detalle del activo o del empleado  
**Then** se muestra la fecha de vencimiento (si existe) de forma clara.

## Dev Notes (contexto para implementar sin desastres)

### Contexto actual (ya existe préstamo/devolución)

- El flujo de préstamo ya existe (Story 5.3) con:
  - UI: `gatic/app/Livewire/Movements/Assets/LoanAssetForm.php` + view `gatic/resources/views/livewire/movements/assets/loan-asset-form.blade.php`
  - Action: `gatic/app/Actions/Movements/Assets/LoanAssetToEmployee.php`
  - Persistencia de tenencia actual: `assets.current_employee_id` + `assets.status = Prestado`
  - Trazabilidad: `asset_movements` (`type=loan` / `type=return`)
- El detalle del Activo ya muestra “Tenencia actual” y el detalle del Empleado ya lista “Activos prestados”.

### Decisión de diseño (campo canónico)

- Para evitar duplicación y facilitar consultas futuras (Story 13.2), el campo canónico recomendado es:
  - `assets.loan_due_date` (tipo `date`, nullable).
- Razón: la app ya trata `assets.current_employee_id` como “tenencia actual”; el vencimiento del préstamo vigente vive naturalmente en el mismo agregado (Asset).
- El vencimiento debe **limpiarse** al devolver el activo (al pasar a `Disponible`).

### Guardrails de compatibilidad

- Mantener valores canónicos de estado (NO renombrar): `Disponible`, `Asignado`, `Prestado`, `Pendiente de Retiro`, `Retirado` (`gatic/app/Models/Asset.php`).
- Identificadores/campos en inglés (`loan_due_date`), copy/UI en español (labels, mensajes).
- Defensa en profundidad:
  - Gate `inventory.manage` en rutas y en Livewire (ya existe en `LoanAssetForm`).
  - Validación server-side en Action (no confiar solo en `<input type="date">`).

## Tasks / Subtasks

1) Modelo de datos (AC: 1)
- [x] Migración: agregar `loan_due_date` (DATE, nullable) a `assets` (`gatic/database/migrations/*`).
- [x] Modelo `Asset`: agregar `loan_due_date` a `$fillable` y cast a `date` (o `immutable_date`) según convención local.

2) Caso de uso: préstamo (AC: 1)
- [x] `LoanAssetToEmployee`:
  - [x] Aceptar `loan_due_date` como input (string `YYYY-MM-DD` o `null`).
  - [x] Validar: si viene, debe ser `date` y `after_or_equal:today`.
  - [x] Persistir en `assets.loan_due_date` dentro de la transacción (junto con status + current_employee_id).
  - [x] (Opcional) Incluir `loan_due_date` en `AuditRecorder::record(...context...)` para trazabilidad.
- [x] Nota de compatibilidad: el tipo de tarea pendiente `PendingTaskType::Loan` reutiliza esta Action y hoy no captura vencimiento.
  - Mantener `loan_due_date` como **nullable** por ahora para no romper Epic 7.
  - En Story 13.2 (alertas) decidir si se vuelve obligatorio y extender UI/flows que presten (incluyendo Pending Tasks) para capturarlo.

3) Caso de uso: devolución (AC: 2, guardrail)
- [x] `ReturnLoanedAsset`: al devolver, limpiar `assets.loan_due_date = null` dentro de la transacción.

4) UI (AC: 1, 2)
- [x] `LoanAssetForm` + `loan-asset-form.blade.php`:
  - [x] Agregar campo "Fecha de vencimiento" (input `type="date"`) con microcopy claro.
  - [x] Validación Livewire: fecha requerida solo si la UX lo define así; mínimo: si existe, no en el pasado.
  - [x] Pasar el valor a la Action.
- [x] Detalle del Activo (`gatic/resources/views/livewire/inventory/assets/asset-show.blade.php`):
  - [x] Si estado `Prestado` y `loan_due_date` existe, mostrar "Vence: {fecha}".
- [x] Detalle del Empleado (`gatic/resources/views/livewire/employees/employee-show.blade.php`):
  - [x] Añadir columna "Vence" (o "Vencimiento") en tabla de "Activos prestados" y mostrar la fecha si existe.

5) Testing (AC: 1, 2)
- [x] `gatic/tests/Feature/Movements/AssetLoanTest.php`:
  - [x] Caso feliz: setear due date válido y afirmar `assets.loan_due_date` persistido.
  - [x] Validación: due date en pasado → error y NO cambia estado a `Prestado`.
- [x] Tests de UI:
  - [x] Asset show: cuando un asset está `Prestado` y tiene `loan_due_date`, el HTML muestra el vencimiento.
- [x] Employee show: el listado de activos prestados muestra el vencimiento cuando exista.

## Architecture Compliance

- Transacciones: mantener `DB::transaction()` + `lockForUpdate()` en movimientos (patrón ya usado en `LoanAssetToEmployee` / `ReturnLoanedAsset`).  
- No inventar servicios globales: seguir patrón `app/Actions/*` y reusar Actions existentes.  
- RBAC: no confiar en la UI; reforzar en Gate server-side (`inventory.manage`).  
- Auditoría: best-effort (no bloquear operación si falla el log) y evitar side-effects fuera de transacción principal.  

## Library / Framework Requirements

- Laravel (proyecto): `laravel/framework v11.47.0` (ver `gatic/composer.lock`).
- Livewire (proyecto): `livewire/livewire v3.7.3` (ver `gatic/composer.lock`).
- Bootstrap (UI): `bootstrap ^5.2.3` (ver `gatic/package.json`).

Regla: implementar con el stack existente; NO introducir librerías nuevas para “date pickers” o similares.

## File Structure Requirements

- Migraciones: `gatic/database/migrations/*` (nueva migración para `assets.loan_due_date`).
- Modelos: `gatic/app/Models/Asset.php` (fillable + casts).
- Actions: `gatic/app/Actions/Movements/Assets/LoanAssetToEmployee.php`, `gatic/app/Actions/Movements/Assets/ReturnLoanedAsset.php`.
- Livewire (UI): `gatic/app/Livewire/Movements/Assets/LoanAssetForm.php` + view `gatic/resources/views/livewire/movements/assets/loan-asset-form.blade.php`.
- Vistas de detalle:
  - Activo: `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php`
  - Empleado: `gatic/resources/views/livewire/employees/employee-show.blade.php`
- Tests: `gatic/tests/Feature/Movements/AssetLoanTest.php` (y donde aplique para UI).

## Testing Requirements

- Usar `RefreshDatabase` (patrón existente en `AssetLoanTest`).
- Cubrir:
  - Persistencia de `assets.loan_due_date` (happy path).
  - Validación server-side: fecha inválida / en el pasado.
  - Render en vistas (Asset show / Employee show) mostrando “Vence” solo cuando aplique.
- Evitar flaky tests por timezone:
  - Preferir fechas relativas (`today()`, `tomorrow()`) y/o strings `Y-m-d`.

## Previous Story Intelligence

- Reusar el patrón ya probado en Story 5.3:
  - UI Livewire con toasts, validación inline, redirect al detalle.
  - Action transaccional + `lockForUpdate()` para cambios de estado/tenencia.
- Hardening existente a NO romper:
  - `ReturnLoanedAsset` tolera `current_employee_id` inválido (fallback a selección); al limpiar vencimiento, mantener ese comportamiento.
  - Evitar introducir dependencias nuevas o “helpers globales”.

## Git Intelligence Summary

- Implementación base de préstamo/devolución (Story 5.3): commit `6157f42` (toca `LoanAssetForm`, `LoanAssetToEmployee`, views y tests).

## Latest Tech Information (para evitar docs equivocadas)

- Al implementar validaciones/Livewire, usar la documentación de:
  - Laravel 11 (validación, migraciones, casts).
  - Livewire 3 (wire:model en inputs, validación y actions).
- No planear upgrades de framework dentro de esta story; el objetivo es extender el flujo existente sin regresiones.

## Project Context Reference

- Fuente de verdad: `docsBmad/project-context.md` (si hay conflicto con otros docs, gana el bible).
- Reglas críticas relevantes:
  - Roles MVP: Admin/Editor/Lector; Lector no ejecuta movimientos.
  - UI en español; identificadores/campos en inglés.
  - Sin WebSockets: polling Livewire solo cuando aplique (no necesario para este form).
  - Auditoría best-effort; errores con `error_id` en prod (no romper patrón actual de toasts).

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `Get-Content _bmad/core/tasks/workflow.xml`
- `Get-Content _bmad/bmm/workflows/4-implementation/create-story/workflow.yaml`
- `Get-Content _bmad/bmm/config.yaml`
- `Get-Content _bmad-output/implementation-artifacts/sprint-status.yaml` (auto-discovery: primer backlog `13-1-fecha-vencimiento-en-prestamos-de-activos`)
- `Get-Content _bmad-output/implementation-artifacts/epics.md` (Epic 13 / Story 13.1)
- `Get-Content _bmad-output/architecture.md` + `Get-Content docsBmad/project-context.md` + `Get-Content project-context.md`
- `Get-Content gatic/app/Actions/Movements/Assets/LoanAssetToEmployee.php` + `ReturnLoanedAsset.php`
- `Get-Content gatic/app/Livewire/Movements/Assets/LoanAssetForm.php` + `loan-asset-form.blade.php`
- `Get-Content gatic/resources/views/livewire/inventory/assets/asset-show.blade.php` + `employee-show.blade.php`
- `Get-Content gatic/tests/Feature/Movements/AssetLoanTest.php`

### Completion Notes List

- Story seleccionada automáticamente desde `sprint-status.yaml` (primer `backlog` en `development_status`).
- Documento actualizado y orientado a minimizar regresiones: reuso de patrones de Story 5.3 + guardrails (RBAC, transacciones, naming).
- ✅ Implementación completada siguiendo red-green-refactor TDD
- ✅ Campo `loan_due_date` agregado a modelo Asset con validación server-side (nullable, no en pasado)
- ✅ Actions actualizadas: `LoanAssetToEmployee` acepta y valida, `ReturnLoanedAsset` limpia el campo
- ✅ UI extendida: formulario de préstamo captura fecha, vistas de detalle muestran vencimiento
- ✅ Tests agregados para validación de fecha en pasado, persistencia correcta, y limpieza en devolución
- ✅ Code review: tests de UI reales (Activo/Empleado), índice en `loan_due_date`, validación endurecida a `date_format:Y-m-d`, y ajuste de vista para mostrar vencimiento aunque falte `currentEmployee`

### File List

- `_bmad-output/implementation-artifacts/13-1-fecha-vencimiento-en-prestamos-de-activos.md` (MODIFIED)
- `gatic/database/migrations/2026_02_01_000000_add_loan_due_date_to_assets_table.php` (NEW)
- `gatic/app/Models/Asset.php` (MODIFIED)
- `gatic/app/Actions/Movements/Assets/LoanAssetToEmployee.php` (MODIFIED)
- `gatic/app/Actions/Movements/Assets/ReturnLoanedAsset.php` (MODIFIED)
- `gatic/app/Livewire/Movements/Assets/LoanAssetForm.php` (MODIFIED)
- `gatic/resources/views/livewire/movements/assets/loan-asset-form.blade.php` (MODIFIED)
- `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php` (MODIFIED)
- `gatic/resources/views/livewire/employees/employee-show.blade.php` (MODIFIED)
- `gatic/tests/Feature/Movements/AssetLoanTest.php` (MODIFIED)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MODIFIED)

### Change Log

- **2026-02-01**: Story implementada completamente. Agregado campo `loan_due_date` nullable en tabla `assets`, actualizado modelo con cast `immutable_date`, extendidas Actions de préstamo/devolución con validación server-side (fecha no en pasado), actualizada UI del formulario de préstamo y vistas de detalle (activo y empleado) para mostrar vencimiento. Tests agregados para validación, persistencia y limpieza en devolución.
- **2026-02-01**: Code review: corregidos claims falsos agregando tests reales de UI para "Vence/Vencimiento", reforzada validación a `date_format:Y-m-d`, agregado índice a `assets.loan_due_date`, y ajustada vista de Activo para mostrar vencimiento aunque falte el empleado actual. Story y sprint tracking sincronizados a `done`.

## Senior Developer Review (AI)

Fecha: 2026-02-01  
Resultado: ✅ Aprobado (después de fixes)

### Hallazgos (resueltos)

- [HIGH] Claims de "Tests de UI" marcados como hechos, pero no existían asserts de HTML. Solución: tests nuevos verificando "Vence/Vencimiento" y la fecha en vistas de Activo y Empleado.
- [HIGH] Status inconsistente (`ready-for-dev` arriba vs `review` abajo). Solución: status unificado a `done`.
- [MEDIUM] Archivo cambiado en git no documentado (sprint-status). Solución: agregado a File List.
- [MEDIUM] Falta de índice en `loan_due_date` (necesario para Story 13.2). Solución: índice agregado en migración.
- [MEDIUM] Validación “fecha válida” demasiado permisiva. Solución: se endurece a `date_format:Y-m-d` + `after_or_equal:today` en Action y Livewire.
- [MEDIUM] Edge case: vencimiento no se mostraba si faltaba el empleado actual. Solución: la vista muestra vencimiento aunque `currentEmployee` sea null.

## Story Completion Status

- Status: `done`
- Nota: "ACs implementados, review completado y issues corregidos (incluye tests de UI reales)."
