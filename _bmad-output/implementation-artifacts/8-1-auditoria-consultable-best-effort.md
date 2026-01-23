# Story 8.1: Auditoría consultable (best effort)

Status: ready-for-dev

Story Key: 8-1-auditoria-consultable-best-effort  
Tracking: _bmad-output/implementation-artifacts/sprint-status.yaml  
Gate: 5 (Trazabilidad)  

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Admin,  
I want que el sistema registre auditoría de acciones clave y sea consultable,  
so that exista trazabilidad sin bloquear la operación (FR32, NFR8).

## Alcance (MVP)

Esta story implementa un **módulo de auditoría consultable** (cross-cutting) alineado a arquitectura:

- Persistir eventos de auditoría (tabla dedicada).
- Registrar eventos en acciones clave de forma **best-effort** (si falla NO bloquea operación).
- UI Admin para consultar auditoría con filtros básicos + detalle.

## Fuera de alcance (NO hacer aquí)

- Notas manuales (Story 8.2), Adjuntos (8.3), Papelera (8.4), Error ID consultable E2E (8.5).
- Reemplazar historiales de dominio existentes (p.ej. `AssetMovement`, `ProductQuantityMovement`, `InventoryAdjustment*`); la auditoría es un **feed transversal** con metadata y links, no un duplicado del historial.

## Acceptance Criteria

### AC1 — Se registra un evento de auditoría por acción auditable (FR32)

**Given** una acción auditable (ej. préstamo/asignación/ajuste/lock override)  
**When** ocurre la acción  
**Then** se registra un evento de auditoría con **actor**, **acción**, **entidad (tipo+id)** y **timestamp**  
**And** el evento incluye contexto mínimo para diagnóstico (p.ej. ids relevantes y un resumen).

### AC2 — Auditoría best-effort (no bloqueante) (NFR8)

**Given** una acción auditable que debe registrar auditoría  
**When** el registro de auditoría falla (por excepción/timeout/error inesperado)  
**Then** la operación principal **NO** se bloquea ni se revierte por el fallo de auditoría  
**And** el fallo queda registrado internamente (logs) con contexto suficiente para soporte.

### AC3 — Auditoría consultable por Admin (FR32, NFR4)

**Given** un Admin autenticado  
**When** abre el módulo de Auditoría  
**Then** puede ver una lista paginada ordenada por más reciente  
**And** puede filtrar por: rango de fecha, actor, acción y tipo de entidad  
**And** puede abrir el detalle de un evento (contexto JSON / campos expandibles).

### AC4 — Seguridad/RBAC (NFR4, NFR5)

**Given** un Editor o Lector  
**When** intenta acceder al módulo de Auditoría  
**Then** el servidor lo bloquea (deny/403)  
**And** la UI no muestra enlaces/acciones de auditoría para roles no autorizados.

### AC5 — Cobertura mínima instrumentada (anti-ambigüedad)

**Given** que la auditoría es transversal  
**Then** como mínimo deben emitirse eventos al completar exitosamente:
- **Lock override** (Story 7.5): force-release / force-claim.
- **Ajustes de inventario** (FR14): `ApplyProductQuantityAdjustment`, `ApplyAssetAdjustment`.
- **Movimientos** (FR17–FR22): al menos 1 de cada tipo:
  - serializado: assign/loan/return (`AssignAssetToEmployee`, `LoanAssetToEmployee`, `ReturnLoanedAsset`)
  - por cantidad: `RegisterProductQuantityMovement`

## Tasks / Subtasks

- [ ] Modelo y persistencia de auditoría (AC: 1,2)
  - [ ] Crear migración `audit_logs` con índices para consulta (created_at, actor_user_id, action, subject_type+subject_id)
  - [ ] Crear `App\\Models\\AuditLog` (casts de `context` a array)
  - [ ] Definir convención estable de `action` (strings constantes / enum ligera)
- [ ] Registro best-effort vía job (AC: 2)
  - [ ] Crear `App\\Jobs\\RecordAuditLog` (queue `database`, no-blocking, swallow exceptions)
  - [ ] Crear helper/servicio `App\\Support\\Audit\\AuditRecorder` para estandarizar payload y dispatch (`->afterCommit()` cuando aplique)
- [ ] Instrumentación de acciones clave (AC: 1,5)
  - [ ] PendingTasks: integrar auditoría persistente en `ForceReleasePendingTaskLock` y `ForceClaimPendingTaskLock` (mantener fallback a `Log::info` como last resort)
  - [ ] Inventory adjustments: emitir evento tras crear `InventoryAdjustment` (+ subject_type/id de `InventoryAdjustmentEntry`)
  - [ ] Movements: emitir evento tras crear `AssetMovement` / `ProductQuantityMovement` (subject_type/id = movimiento; incluir `asset_id/product_id/employee_id`)
- [ ] UI Admin: Auditoría consultable (AC: 3,4)
  - [ ] Ruta protegida con `can:admin-only` (p.ej. `/admin/audit`)
  - [ ] Livewire `App\\Livewire\\Admin\\AuditLogsIndex` con filtros, paginación y vista detalle
  - [ ] UX: tabla densa, “Actualizado hace X”, y si consulta puede tardar >3s aplicar `<x-ui.long-request />`
- [ ] Pruebas (AC: 1–5)
  - [ ] Feature tests: RBAC (Editor/Lector bloqueados), Admin ve lista
  - [ ] Tests de dispatch/registro: acciones instrumentadas emiten job (usar `Queue::fake()`), y/o persisten registros en DB
  - [ ] Regression: si alguna query toca soft-delete indirectamente, agregar test de exclusión `deleted_at` (checklist)

## Dev Notes

### Contexto actual (antes de tocar código)

- Ya existen “historiales” por dominio:
  - Movimientos: `AssetMovement`, `ProductQuantityMovement`.
  - Ajustes: `InventoryAdjustment` + `InventoryAdjustmentEntry` (con `before/after` + actor).
- Ya existe auditoría best-effort mínima en logs para overrides de locks (Story 7.5):
  - `Log::info('PendingTaskLockOverride', [...])` (y nunca debe bloquear la operación).
- Esta story NO debe duplicar historiales existentes; debe crear un **feed transversal**:
  - “Qué pasó, quién lo hizo, sobre qué entidad, cuándo”, con contexto mínimo y links a módulos existentes.

### Guardrails (lo que NO se negocia)

- **Best-effort real (NFR8):**
  - La auditoría nunca debe lanzar una excepción que tumbe la operación principal.
  - Cualquier excepción en auditoría se atrapa y se registra (log).
- **No agregar magic numbers**: si se introduce polling/intervalos, centralizar en `config/gatic.php`.
- **RBAC server-side**: módulo de auditoría Admin-only con `can:admin-only` + `Gate::authorize`/`authorize`.
- **Performance/UX**:
  - Lista paginada y con índices (evitar full scans).
  - Si una consulta puede tardar >3s, integrar `<x-ui.long-request />` (checklist).

### Project Structure Notes

- Ubicación sugerida (alineado a `architecture.md`):
  - Modelo: `gatic/app/Models/AuditLog.php`
  - Job: `gatic/app/Jobs/RecordAuditLog.php`
  - Soporte/servicio: `gatic/app/Support/Audit/*`
  - UI Admin: `gatic/app/Livewire/Admin/*` + vistas en `gatic/resources/views/livewire/admin/*`
  - Rutas: `gatic/routes/web.php` (protegidas con `can:admin-only`)

### Requisitos técnicos (DEV AGENT GUARDRAILS)

#### Esquema recomendado (mínimo viable)

Tabla `audit_logs` (MySQL 8):
- `id`
- `created_at` (timestamp)
- `actor_user_id` (FK users, nullable si aplica)
- `action` (string, index)
- `subject_type` (string, index)
- `subject_id` (unsignedBigInt, index)
- `context` (json nullable) — solo metadata necesaria (ids, before/after *solo si no existe ya en el dominio*)

Índices mínimos:
- `created_at`
- `actor_user_id, created_at`
- `action, created_at`
- `subject_type, subject_id, created_at`

#### Registro best-effort (patrón)

- Preferir **job en queue `database`** (arquitectura) para desacoplar.
- Disparar auditoría **después de commit** cuando la acción corre en transacción:
  - `RecordAuditLog::dispatch(...)->afterCommit()` (evita registrar eventos de transacciones que luego rollback).
- El job debe:
  - Validar payload básico (sin reventar).
  - Atrapar cualquier excepción y hacer `Log::warning(...)` (sin rethrow).

#### Convenciones de `action` (evitar ambigüedad)

Usar strings estables y legibles, por ejemplo:
- `pending_tasks.lock.force_release`
- `pending_tasks.lock.force_claim`
- `inventory.adjustment.apply`
- `movements.asset.assign` / `movements.asset.loan` / `movements.asset.return`
- `movements.product_qty.register`

### Testing Requirements (resumen)

- Tests deterministas (sin `sleep`): usar `Carbon::setTestNow()` cuando aplique.
- Verificar **no-blocking**:
  - El flujo principal pasa aunque el job falle (simular excepciones en recorder/job y afirmar que la acción retorna OK).
- Verificar instrumentación mínima:
  - `Queue::fake()` y `Queue::assertPushed(RecordAuditLog::class, fn($job) => ...)`.
- Verificar RBAC:
  - Editor/Lector no acceden a `/admin/audit` (403).

### References

- Backlog (Epic 8 / Story 8.1): [Source: `_bmad-output/implementation-artifacts/epics.md#Epic 8 / Story 8.1`]
- FR32 / NFR8: [Source: `_bmad-output/implementation-artifacts/prd.md#Traceability, Attachments & Trash`], [Source: `_bmad-output/implementation-artifacts/prd.md#NFR8`]
- Arquitectura (Job `RecordAuditLog`, estructura, queue database): [Source: `_bmad-output/implementation-artifacts/architecture.md#Integration Points`], [Source: `_bmad-output/implementation-artifacts/architecture.md#Requirements to Structure Mapping`]
- Bible (auditoría best-effort + roles): [Source: `docsBmad/project-context.md#Decisiones de UX y Operación`], [Source: `docsBmad/project-context.md#Usuarios y Roles`]
- Learnings: auditoría best-effort ya usada en locks: [Source: `_bmad-output/implementation-artifacts/7-5-admin-puede-liberar-forzar-reclamo-de-lock.md#Audit`]

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- Stack actual (repo): Laravel `v11.47.0`, Livewire `v3.7.3` (ver `gatic/composer.lock`).
- Web check (2026-01-22): Livewire latest patch observado `v3.7.5` (ver releases).

### Completion Notes List

- Story creada con contexto de arquitectura + patrones existentes (Actions/Livewire/Jobs).
  - Nota: esta story está en estado `ready-for-dev`; implementación ocurre en `dev-story`.

### File List

- `_bmad-output/implementation-artifacts/8-1-auditoria-consultable-best-effort.md` (this file)

## Project Context Reference (must-read)

- `docsBmad/product/audit-use-cases.md`
  - Use cases MUST y “qué NO auditar” (para mantener el scope de 8.1 controlado).
- `docsBmad/checklists/dev-preflight.md`
  - Checklist para evitar olvidos recurrentes (RBAC, long-request UX, tests deterministas).
- `docsBmad/project-context.md`
  - Auditoría best-effort (no bloqueante) y stack base (Laravel 11 + Livewire 3 + Bootstrap 5 + queue `database`).
- `docsBmad/rbac.md`
  - Gate `admin-only` y reglas de aplicación server-side.
- `_bmad-output/implementation-artifacts/architecture.md`
  - Patrones de estructura (`app/Actions`, `app/Livewire`, `app/Jobs`, `app/Support`) y punto de integración “Audit → Job”.
- `_bmad-output/implementation-artifacts/ux.md`
  - Reglas de UX para long requests (`>3s` → loader + cancelar) y densidad desktop-first.
- `_bmad-output/implementation-artifacts/7-5-admin-puede-liberar-forzar-reclamo-de-lock.md`
  - Ejemplo existente de auditoría best-effort (logs) a reutilizar/elevar a persistente.

## Story Completion Status

- Status: **ready-for-dev**
- Completion note: **Ultimate context engine analysis completed — comprehensive developer guide created**
