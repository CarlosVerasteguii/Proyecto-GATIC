# Story 5.1: Reglas de estado y transiciones para activos serializados

Status: done

Story Key: `5-1-reglas-de-estado-y-transiciones-para-activos-serializados`  
Epic: `5` (Gate 3: Operación diaria)

Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Backlog (fuente de verdad): `_bmad-output/implementation-artifacts/epics.md` (Epic 5, Story 5.1; FR20)

Fuentes:
- `_bmad-output/implementation-artifacts/epics.md` (Epic 5, Story 5.1; FR20)
- `_bmad-output/implementation-artifacts/prd.md` (FR20; NFR7)
- `_bmad-output/implementation-artifacts/ux.md` (estados/badges; acciones habilitadas por estado; microcopy de validación)
- `_bmad-output/implementation-artifacts/architecture.md` (Actions transaccionales; `app/Enums/*` / `app/Actions/*`; patrones de tests)
- `docsBmad/project-context.md` (bible: estados canónicos; semántica de disponibilidad; reglas críticas)
- `project-context.md` (reglas críticas para agentes; semántica de estados/disponibilidad)
- `docsBmad/rbac.md` (gates `inventory.manage`, defensa en profundidad)
- `gatic/app/Models/Asset.php` (estados actuales en DB y constantes usadas por UI/tests)
- `gatic/app/Actions/Inventory/Adjustments/ApplyAssetAdjustment.php` (patrón transaccional + `lockForUpdate()`)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Admin/Editor,  
I want que el sistema valide transiciones de estado para evitar acciones en conflicto,  
so that el inventario no quede inconsistente (FR20, NFR7).

## Alcance

Incluye:
- Definir una **fuente única de verdad** para reglas de transición de estado de Activos serializados.
- Validaciones con mensajes accionables (en español) cuando se intenta ejecutar una acción incompatible con el estado actual.
- Estructura lista para reusarse por las próximas historias de Epic 5 (asignar/prestar/devolver) sin reinventar reglas.

No incluye (scope explícitamente fuera):
- Implementación completa de flujos UI de asignar/prestar/devolver (Stories 5.2/5.3).
- Tenencia real en UI (se habilita con las historias de movimientos).
- Movimientos por cantidad y kardex (Stories 5.4/5.5).

## Acceptance Criteria

### AC1 - Bloqueo: “Asignado no se presta”

**Given** un Activo en estado Asignado  
**When** el usuario intenta prestarlo  
**Then** el sistema bloquea la acción  
**And** muestra el motivo (debe desasignar primero).

### AC2 - Bloqueo: “Prestado no se reasigna”

**Given** un Activo en estado Prestado  
**When** el usuario intenta asignarlo a otra persona  
**Then** el sistema bloquea la acción  
**And** obliga a devolución/cambio válido antes de reasignar.

## Dev Notes (contexto y guardrails)

### Estados existentes (NO romper compatibilidad)

- Hoy el estado vive como `assets.status` (string) y se usa ampliamente vía constantes en `gatic/app/Models/Asset.php`.
- No cambiar los labels/valores (`Disponible`, `Asignado`, `Prestado`, `Pendiente de Retiro`, `Retirado`) sin plan de migración; hay UI, queries y tests que dependen de ellos.
- Semántica QTY (bible): **No disponibles** = `Asignado + Prestado + Pendiente de Retiro`; `Retirado` no cuenta baseline.

### Qué significa “validar transiciones” en este repo

La validación debe ser:
- **Reusable**: una sola implementación central (para reusar en 5.2/5.3 y futuras acciones).
- **Determinista y transaccional**: las acciones que cambian estado deben usar `DB::transaction()` + `lockForUpdate()` (patrón ya usado).
- **Accionable para usuario**: mensajes en español indicando qué hacer (ej. “Debe desasignar primero” / “Debe devolver primero”).
- **Defensa en profundidad**: gate server-side (`inventory.manage`) además de UI.

### Matriz mínima de compatibilidad (para guiar el diseño)

Acciones previstas en Epic 5 (serializados):
- `Asignar` (Disponible → Asignado)
- `Desasignar` (Asignado → Disponible) *(nota: hoy no hay story explícita, pero el copy de AC1 asume este “paso previo” o equivalente)*
- `Prestar` (Disponible → Prestado)
- `Devolver` (Prestado → Disponible)
- `Marcar pendiente de retiro` (Disponible → Pendiente de Retiro) *(si aplica en MVP)*
- `Retirar` (Pendiente de Retiro → Retirado) *(probablemente Admin-only / ajuste, definir)*

Reglas mínimas para evitar inconsistencias obvias:
- No permitir `Prestar` si estado actual es `Asignado` (AC1).
- No permitir `Asignar` si estado actual es `Prestado` (AC2).
- No permitir acciones de operación diaria (`Asignar/Prestar/Devolver/Desasignar`) sobre `Retirado`.
- `Pendiente de Retiro` se considera “no disponible”: normalmente bloquear `Asignar/Prestar` y requerir resolver retiro/rollback (definir comportamiento exacto).

### Riesgo existente: ajustes Admin vs movimientos

El módulo de ajustes (Epic 3) permite setear cualquier `Asset::STATUSES` sin capturar tenencia. En Epic 5, **la tenencia se vuelve real** (Empleado RPE y trazabilidad). Decidir y documentar:
- Si ajustes pueden seguir seteando `Asignado/Prestado` como “corrección baseline” (con tenencia desconocida), o
- Si se restringen esas opciones en ajustes cuando ya exista tenencia/movimientos.

No bloquear este story por esa decisión, pero dejar el guardrail explícito para no crear inconsistencias silenciosas.

## DEV AGENT GUARDRAILS (requisitos técnicos)

- No inventar nuevas librerías para “state machine”. Mantenerlo simple y legible.
- Evitar “helpers globales”: ubicar reglas en `app/Support/*` o `app/Enums/*` (según patrón existente) y que se consuman desde Actions.
- Mensajes/labels UI en español; nombres de clases/paths en inglés.
- Operaciones de cambio de estado deben estar listas para auditarse en el futuro (Epic 8), pero sin meter auditoría aquí.

## Architecture Compliance

- Casos de uso transaccionales viven en `gatic/app/Actions/*` (ej. futuros `app/Actions/Movements/*`), con `DB::transaction()` y `lockForUpdate()` al mutar estado.
- Reglas/estados preferentemente en `gatic/app/Enums/*` o `gatic/app/Support/*` (sin lógica de dominio repartida en Livewire).
- Autorización: `inventory.manage` (Admin/Editor) para acciones; `inventory.view` solo lectura.
- Sin WebSockets: si más adelante hay UI que muestre badges de estado en listas, usar `wire:poll.visible` (~15s) según bible.

## Library / Framework Requirements

- Backend: Laravel 11 (PHP 8.2+) y Eloquent; evitar soluciones externas para transiciones.
- UI: Blade + Livewire 3 + Bootstrap 5; las validaciones deben mostrarse como mensajes claros (y no “500s”) cuando sea error esperado de negocio.
- Base de datos: MySQL 8; reforzar integridad con transacciones/locks (no confiar en “solo UI”).

## File Structure Requirements

Recomendación (mínima, sin refactor grande):
- Regla central: `gatic/app/Support/Assets/AssetStatusTransitions.php` (o equivalente)
  - API sugerida:
    - `canAssign(string $currentStatus): bool`
    - `canLoan(string $currentStatus): bool`
    - `canReturn(string $currentStatus): bool`
    - `canUnassign(string $currentStatus): bool`
    - `assertCanX(...)` que lance excepción con mensaje accionable (para UI).
- Integración (próximas stories): nuevas Actions en `gatic/app/Actions/Movements/*` consumen estas reglas antes de mutar estado.

No mover/renombrar los estados existentes en `Asset.php` en este story.

## Testing Requirements

- Agregar unit tests para la matriz de transiciones (rápidos, sin DB) en `gatic/tests/Unit/Assets/*`.
- Al menos cubrir:
  - `Asignado` → bloquear `Prestar` con mensaje “debe desasignar primero”.
  - `Prestado` → bloquear `Asignar` con mensaje “debe devolver primero”.
  - `Retirado` → bloquear acciones de operación diaria con mensaje claro.
  - Casos felices mínimos para `Disponible` (permitir `Asignar` y `Prestar`).
- Mantener mensajes en español (como los verá el usuario) y verificar que sean accionables (no genéricos).

## Tasks / Subtasks

1) Definir reglas de transición (AC: 1-2)
- [x] Acordar la lista de acciones soportadas por la “regla central” (mínimo: asignar, prestar, devolver, desasignar).
- [x] Implementar la matriz mínima de permitido/bloqueado por estado (`Disponible/Asignado/Prestado/Pendiente de Retiro/Retirado`).
- [x] Definir mensajes en español por bloqueo (copy accionable, consistente).

2) API de consumo (lista para 5.2/5.3)
- [x] Proveer métodos `can*` (para habilitar/deshabilitar UI) y `assertCan*` (para bloquear server-side).
- [x] Definir cómo se reporta el error a Livewire (excepción de validación vs excepción de dominio capturada y convertida a toast).

3) Tests (AC: 1-2)
- [x] Unit tests cubriendo bloqueos y casos felices mínimos.
- [x] (Opcional) Feature test smoke mínimo que garantice que el módulo no introduce regresiones en inventario (solo si hay un punto real de integración en este story).

## Project Context Reference

- Fuente de verdad: `docsBmad/project-context.md`.
- Reglas críticas relevantes:
  - Empleado (RPE) != Usuario del sistema.
  - Semántica de estados: `Disponible`, `Asignado`, `Prestado`, `Pendiente de Retiro`, `Retirado`.
  - No disponibles = `Asignado + Prestado + Pendiente de Retiro`; `Retirado` no cuenta baseline por defecto.
  - Operaciones críticas transaccionales y con validaciones consistentes.

### References

- Backlog/AC base: `_bmad-output/implementation-artifacts/epics.md` (Epic 5 / Story 5.1; FR20)
- PRD: `_bmad-output/implementation-artifacts/prd.md` (FR20; NFR7)
- UX: `_bmad-output/implementation-artifacts/ux.md` (acciones habilitadas por estado; microcopy)
- Arquitectura: `_bmad-output/implementation-artifacts/architecture.md` (Actions transaccionales; estructura)
- RBAC: `docsBmad/rbac.md` (gate `inventory.manage`)

## Story Completion Status

- Status: `done`
- Nota de completitud: "Implementación completa. `AssetStatusTransitions` provee API central para reglas de estado + mensajes accionables. Tests Unit verificados con PHP 8.4: `AssetStatusTransitionsTest` PASS."

## Senior Developer Review (AI)

### Hallazgos (resueltos)

- [HIGH] Mensajes de error poco accionables (casos `alreadyAssigned` / `alreadyLoaned`). Fix: mensajes guían el siguiente paso.
- [MEDIUM] `getBlockingReason()` trataba acciones desconocidas como permitidas (`null`). Fix: devuelve motivo claro ("Acción inválida").
- [MEDIUM] Validación de tests: el PATH de PHP 8.0 no sirve para este repo. Fix: ejecutar con `C:\Users\carlo\.tools\php84\php.exe`.
- [MEDIUM] Claims de la story sobre cantidad de tests/pases no eran verificables/precisos. Fix: se actualiza evidencia a comando y salida real.
- [LOW] Mensaje genérico para transición inválida. Fix: prefijo "Transición inválida" para diferenciarlo de bloqueos por estado.

### Evidencia (tests)

- `C:\Users\carlo\.tools\php84\php.exe artisan test --testsuite=Unit --filter AssetStatusTransitionsTest` PASS (21 tests, 47 assertions)

## Change Log

- 2026-01-15: Senior Developer Review (AI) - fixes aplicados (mensajes, `getBlockingReason`, evidencia de tests con PHP 8.4) y story pasa a `done`.

## Preguntas (guardar para el final, no bloqueantes)

1) ¿“Desasignar” existe como acción explícita en MVP (Asignado → Disponible), o se modela como “retiro de tenencia” con movimiento/auditoría?
2) ¿Qué comportamiento se espera para `Pendiente de Retiro` en Epic 5 (bloquear todo, o permitir ciertos flujos)?
3) ¿Ajustes Admin puede seguir seteando `Asignado/Prestado` sin tenencia (legacy/baseline correction) o se restringe cuando ya existan movimientos?

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `Get-Content _bmad-output/implementation-artifacts/sprint-status.yaml` (auto-discovery: primer story en backlog: `5-1-reglas-de-estado-y-transiciones-para-activos-serializados`)
- `Get-Content _bmad-output/implementation-artifacts/epics.md` (Epic 5, Story 5.1)
- `Get-Content _bmad-output/implementation-artifacts/prd.md` (FR20; NFR7)
- `Get-Content _bmad-output/implementation-artifacts/ux.md` (acciones habilitadas por estado)
- `Get-Content _bmad-output/implementation-artifacts/architecture.md` (estructura y patrones)
- `Get-Content docsBmad/project-context.md` + `Get-Content project-context.md`
- `Get-Content docsBmad/rbac.md`
- `Get-Content gatic/app/Models/Asset.php` + `Get-Content gatic/app/Actions/Inventory/Adjustments/ApplyAssetAdjustment.php`

### Completion Notes List

- Story seleccionada automáticamente desde `sprint-status.yaml` (primer `backlog` en `development_status`).
- Guardrails explícitos para prevenir inconsistencias: reglas centralizadas, transacciones + locks, RBAC server-side, mensajes accionables.
- Implementado `AssetStatusTransitions` con métodos `can*` y `assertCan*` para validar transiciones.
- Creado `AssetTransitionException` con mensajes accionables en español (AC1: "Debe desasignarlo primero", AC2: "Debe devolverlo primero") + helper `toValidationException()` para uso en Livewire/Laravel.
- Hardening: `getBlockingReason()` devuelve motivo para acciones desconocidas (no las trata como permitidas).
- Tests Unit verificados con PHP 8.4: `AssetStatusTransitionsTest` PASS (21 tests).

### File List

- `gatic/app/Support/Assets/AssetStatusTransitions.php` (NEW)
- `gatic/app/Exceptions/AssetTransitionException.php` (NEW)
- `gatic/tests/Unit/Assets/AssetStatusTransitionsTest.php` (NEW)
- `_bmad-output/implementation-artifacts/5-1-reglas-de-estado-y-transiciones-para-activos-serializados.md` (NEW)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (UPDATED)
