# Story 7.5: Admin puede liberar/forzar reclamo de lock

Status: done

Story Key: `7-5-admin-puede-liberar-forzar-reclamo-de-lock`  
Epic: `7` (Gate 4: Tareas Pendientes + locks de concurrencia)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/epics.md` (Epic 7 / Story 7.5; FR31)
- `_bmad-output/implementation-artifacts/prd.md` (FR31; NFR8; NFR9)
- `_bmad-output/implementation-artifacts/ux.md` (Journey 2/3; Locks & Concurrency)
- `_bmad-output/implementation-artifacts/architecture.md` (patrones: Actions/Livewire/config; concurrencia/locks)
- `docsBmad/project-context.md` (bible: política de locks; override Admin)
- `project-context.md` (reglas críticas para agentes + testing)
- `_bmad-output/implementation-artifacts/7-4-locks-de-concurrencia-claim-estado-visible-heartbeat-ttl.md` (capa de locks ya implementada; integración)
- Código actual (módulo PendingTasks):
  - `gatic/app/Livewire/PendingTasks/PendingTaskShow.php`
  - `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php`
  - `gatic/app/Models/PendingTask.php`
  - `gatic/app/Actions/PendingTasks/*PendingTaskLock*.php`
  - `gatic/app/Providers/AuthServiceProvider.php` (Gate `admin-only`, `inventory.manage`)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Admin,  
I want liberar o forzar el reclamo de un lock en Tareas Pendientes,  
so that pueda destrabar la operación cuando un Editor se queda bloqueado (FR31).

## Alcance (MVP)

Esta story agrega el **override Admin** sobre locks de Tareas Pendientes (Story 7.4 ya implementó claim/heartbeat/TTL).

Incluye:
- Acciones **Admin-only** sobre una tarea bloqueada por otro usuario:
  - **Forzar liberación**: elimina el lock (deja libre).
  - **Forzar reclamo**: Admin toma el lock (pasa a ser owner).
- Confirmación explícita (UI) antes de ejecutar override (acción destructiva sobre el flujo de otro usuario).
- Auditoría **best-effort** de la acción (NFR8): registrar evento estructurado (mínimo en logs) sin bloquear la operación.
- Integración con el comportamiento existente de “lock perdido” (Story 7.4): si un Editor pierde el lock por override, su UI debe quedar read-only con banner y “Reintentar claim”.

No incluye (fuera de scope / Epic 8):
- Auditoría consultable (UI/tabla) como módulo completo.
- Notificaciones reales al Editor (email/push). “Solicitar liberación” sigue siendo informativo.
- Cambios de modelo “genérico” de locks multi-recurso (solo PendingTask).

## Definiciones (para evitar ambigüedad)

- **Lock activo:** `expires_at > now()` y `locked_by_user_id` no es null.
- **Forzar liberación:** setear `locked_by_user_id/locked_at/heartbeat_at/expires_at` a null (libre).
- **Forzar reclamo:** setear owner a Admin y **reiniciar lease** (`locked_at/heartbeat_at=now()`, `expires_at=now()+TTL`).
- **Auditar:** registrar un evento “override lock” con actor, tarea, owner previo/nuevo, timestamp y motivo (mínimo en logs; idealmente extensible a Epic 8).

## Acceptance Criteria

### AC1 — Admin puede forzar liberación (FR31)

**Given** una Tarea Pendiente con lock activo por otro usuario  
**When** Admin ejecuta “Forzar liberación” y confirma  
**Then** el lock se elimina y la tarea queda **Libre**  
**And** el evento queda auditado (best effort; no bloqueante).

### AC2 — Admin puede forzar reclamo (FR31)

**Given** una Tarea Pendiente con lock activo por otro usuario  
**When** Admin ejecuta “Forzar reclamo” y confirma  
**Then** Admin se convierte en owner del lock y el lease se reinicia (TTL)  
**And** el evento queda auditado (best effort; no bloqueante).

### AC3 — Seguridad/RBAC (NFR4)

**Given** un usuario no-Admin (Editor/Lector)  
**When** intenta ejecutar “Forzar liberación” o “Forzar reclamo”  
**Then** el servidor bloquea la operación (403/deny)  
**And** la UI no muestra acciones de override.

### AC4 — UX consistente con Journey 2/3

**Given** un usuario abre una tarea bloqueada  
**When** el lock es de otro usuario  
**Then** se muestra claramente quién tiene el lock y desde cuándo  
**And** si el usuario es Admin, se muestran acciones de override con confirmación.

### AC5 — Compatibilidad con Story 7.4 (lock perdido)

**Given** un Editor está en modo “Procesar” con lock activo  
**When** Admin ejecuta override (liberar o force-claim)  
**Then** el Editor pierde el lock y su UI cambia a **read-only** con banner “Lock perdido”  
**And** el Editor ve una acción “Reintentar claim”.

### AC6 — Idempotencia y mensajes claros

**Given** una tarea sin lock activo  
**When** Admin intenta “Forzar liberación”  
**Then** el sistema responde con mensaje “No hay lock que liberar” (sin error).

**Given** Admin ya es owner del lock  
**When** ejecuta “Forzar reclamo”  
**Then** el sistema renueva el lease y responde OK (sin cambiar owner).

## Tasks / Subtasks

- [x] UI: acciones Admin en detalle de Tarea (AC1–AC4)
  - [x] En `PendingTaskShow`/blade: mostrar botones "Forzar liberación" y "Forzar reclamo" solo para Admin cuando el lock es de otro usuario
  - [x] Confirmación (modal o confirm inline) antes de ejecutar override
  - [x] Estados/feedback: toast éxito/error; refrescar estado del lock en pantalla
- [x] Backend: Actions de override (AC1–AC3, AC6)
  - [x] `ForceReleasePendingTaskLock` (transacción + `lockForUpdate`, limpiar campos)
  - [x] `ForceClaimPendingTaskLock` (transacción + `lockForUpdate`, setear owner=Admin + TTL)
  - [x] Autorización server-side: `Gate::authorize('admin-only')` (o equivalente) en métodos Livewire que ejecuten override
- [x] Auditoría best-effort (AC1–AC2, NFR8)
  - [x] Registrar `Log::info(...)` estructurado con: actor, acción, task_id, owner previo/nuevo, timestamps
  - [x] No bloquear el override si "auditar" falla (atrapar excepción y loguear warning)
- [x] Tests (AC3, AC5, AC6)
  - [x] Feature tests para RBAC + override + efectos en lock (sin sleeps; usar `Carbon::setTestNow()`)
  - [x] Test de "Editor pierde lock" (simular heartbeat/owner mismatch → lockLost)

## Dev Notes

### Contexto actual (ya existe en el repo)

- Locks base (Story 7.4) ya están implementados con:
  - Campos en `PendingTask`: `locked_by_user_id`, `locked_at`, `heartbeat_at`, `expires_at`
  - Actions existentes:
    - `App\\Actions\\PendingTasks\\AcquirePendingTaskLock`
    - `App\\Actions\\PendingTasks\\HeartbeatPendingTaskLock`
    - `App\\Actions\\PendingTasks\\ReleasePendingTaskLock`
  - UI existente:
    - Banner de lock (quién / desde cuándo) en `pending-task-show.blade.php`
    - Banner “Lock perdido” + “Reintentar claim”
    - Heartbeat con idle guard en JS (Livewire `$wire.heartbeat()`)

Esta story NO debe reimplementar la capa base; solo agregar el override Admin.

### Flujos UX esperados (Journey 2/3)

- Cuando **hay lock de otro usuario**:
  - Para Editor/Lector: solo lectura (ya existe), sin acciones de override.
  - Para Admin: mostrar:
    - **Forzar liberación** (desbloquear)
    - **Forzar reclamo** (tomar control)
  - Ambos deben pedir confirmación (riesgo de interrumpir a un Editor).
- Cuando Admin ejecuta override:
  - Cualquier Editor que esté procesando debe terminar en estado seguro (read-only) con banner “lock perdido”.

### Guardrails anti-desastre (no romper concurrencia)

- Todas las operaciones de override deben ser **atómicas** (transacción + `lockForUpdate()`).
- Nunca actualizar locks con `update([...])` sin lock de fila; evitar condiciones de carrera.
- No tocar el estado funcional de la tarea (Draft/Ready/Processing/etc.) como efecto colateral, salvo que exista una razón explícita (no la hay en MVP).
- Mantener consistencia con la definición de “lock activo” (usar `expires_at > now()`).

### Edge cases a cubrir

- Lock expirado: tratar como libre; “Forzar liberación” es idempotente.
- Admin ya es owner: “Forzar reclamo” renueva lease y responde OK.
- Soft-delete: no operar sobre tareas soft-deleted (validar existencia y estado).
- Auditoría: best-effort; nunca bloquear el override por fallo al registrar.

### Long-request

- Estas acciones son O(1) (un update transaccional) y no deberían pasar el umbral `>3s`, por lo que **no** requieren integrar loaders especiales más allá de deshabilitar botones mientras ejecuta.

## Technical Requirements (Guardrails)

### Autorización (NFR4)

- Acción Admin-only: exigir server-side `Gate::authorize('admin-only')` en los métodos Livewire de override.
- La UI puede ocultar botones, pero nunca reemplaza autorización.

### Integridad y concurrencia

- Implementar override con `DB::transaction()` + `PendingTask::lockForUpdate()` para que “force-release/force-claim” sea determinista.
- No introducir `sleep()` ni dependencias de timing en runtime.
- Mantener la definición única de “lock activo”: `expires_at > now()`.
- TTL debe salir de config: `config('gatic.pending_tasks.locks.lease_ttl_s', 180)`.

### Auditoría best-effort (NFR8)

- Registrar evento estructurado (mínimo `Log::info`) con:
  - `actor_user_id`, `actor_name`, `action` (`force_release`/`force_claim`)
  - `pending_task_id`
  - `previous_locked_by_user_id`, `previous_locked_at`, `previous_expires_at`
  - `new_locked_by_user_id`, `new_locked_at`, `new_expires_at`
  - `reason` (texto libre opcional si se captura en UI)
- Si falla auditoría, continuar: atrapar excepción y `Log::warning` (no bloquear override).

### Errores y mensajes

- Mensajes a usuario (toasts) en español, concisos y accionables.
- En fallos inesperados: usar el patrón existente de “unexpected error” del módulo (no exponer stack).

## Cumplimiento de arquitectura

- Mantener el flujo: **Livewire → Actions → Models/DB**.
  - `PendingTaskShow` solo orquesta UI + autorización + toasts.
  - La lógica de override vive en `app/Actions/PendingTasks/*`.
- No crear “helpers globales”; si se requiere reutilización, preferir `app/Actions/*` o `app/Support/*`.
- No agregar endpoints API/Controllers para esto (no se necesitan).
- No introducir un sistema de locks genérico multi-recurso en esta story (evitar scope creep); el lock es de `PendingTask`.
- Centralizar números en `config/gatic.php` (TTL/intervalos ya existen); no hardcodear.

## Librerías / Frameworks (requerimientos)

Versiones verificadas localmente (no actualizar en esta story):
- Laravel: `laravel/framework` **v11.47.0** (`gatic/composer.lock`)
- Livewire: `livewire/livewire` **v3.7.3** (`gatic/composer.lock`)
- Bootstrap: **5.2.3** (`gatic/package.json`)
- Vite: **6.0.11** (`gatic/package.json`)
- PHP objetivo del proyecto: **>= 8.2** (`gatic/composer.json` / contexto)

Reglas:
- No introducir paquetes nuevos para “auditoría” en esta story.
- No cambiar versiones de dependencias (evitar regresiones).
### Project Structure Notes

- Mantener el módulo PendingTasks:
  - Livewire: `gatic/app/Livewire/PendingTasks/*`
  - Views: `gatic/resources/views/livewire/pending-tasks/*`
  - Actions: `gatic/app/Actions/PendingTasks/*`
  - Tests: `gatic/tests/Feature/PendingTasks/*`
- Naming (código/DB): inglés; copy/UI: español.

**Archivos a crear (esperados):**
- `gatic/app/Actions/PendingTasks/ForceReleasePendingTaskLock.php`
- `gatic/app/Actions/PendingTasks/ForceClaimPendingTaskLock.php`

**Archivos a modificar (esperados):**
- `gatic/app/Livewire/PendingTasks/PendingTaskShow.php` (métodos Livewire admin-only + toasts + refresh del task)
- `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php` (botones + confirmación)
- `gatic/tests/Feature/PendingTasks/PendingTaskLockOverrideTest.php` (RBAC + force-release/force-claim)

**Opcional (solo si ya existe patrón en el repo):**
- Reutilizar el patrón de confirm modal Bootstrap usado en otras vistas (no inventar uno nuevo).

## Testing Requirements Summary

- Enfoque: Feature tests deterministas (sin sleeps).
- Helpers recomendados: `Carbon::setTestNow()`, `RefreshDatabase`.
- Casos mínimos:
  - Admin puede `force_release` un lock activo de Editor (campos quedan null).
  - Admin puede `force_claim` un lock activo de Editor (owner pasa a Admin, TTL reiniciado).
  - Editor/Lector NO pueden ejecutar override (deny server-side).
  - Idempotencia: force-release sin lock responde OK sin error; force-claim cuando ya es owner renueva TTL.
  - “Lock perdido”: si owner cambia, el heartbeat del Editor falla (owner mismatch) y el componente debe terminar en read-only (validar a nivel Action/estado; UI E2E es opcional).

## Previous Story Intelligence (7.4)

- La base de locks ya define “lock activo” con `expires_at > now()` y usa Actions transaccionales.
- La UI ya tiene banner de “Lock perdido” + botón “Reintentar claim”; el override Admin debe apoyarse en ese flujo (no inventar otro).
- El heartbeat actual limpia locks expirados cuando corresponde; el override Admin debe ser compatible con esa limpieza (no dejar estados intermedios).

## Git Intelligence (contexto reciente)

- `ad1c78a` feat(locks): implementación de Story 7.4 (claim/heartbeat/TTL + UI).
- `82f6d68` docs(locks): ajuste de reglas de contexto (nota: el código usa TTL 3m; no hay timeout 15m).

## Latest Tech Information (en este repo)

- No hay integraciones externas ni APIs públicas para esta story.
- Seguir versiones fijadas en `composer.lock`/`package.json` (ver sección de librerías). No hacer upgrades aquí.

### References

- Story base: [Source: `_bmad-output/implementation-artifacts/epics.md#Story 7.5`]
- FR31 + NFR8/NFR9: [Source: `_bmad-output/implementation-artifacts/prd.md#Pending Tasks & Concurrency Locks`], [Source: `_bmad-output/implementation-artifacts/prd.md#Non-Functional Requirements`]
- UX Journey: [Source: `_bmad-output/implementation-artifacts/ux.md#Journey 2 - Editor (Soporte): Tarea Pendiente con lock (concurrencia)`], [Source: `_bmad-output/implementation-artifacts/ux.md#Journey 3 - Admin: Gobernanza + excepciones (locks + error_id)`]
- Locks bible: [Source: `docsBmad/project-context.md#Política de Locks (Tareas Pendientes)`]
- Arquitectura/patrones: [Source: `_bmad-output/implementation-artifacts/architecture.md#Requirements to Structure Mapping`], [Source: `_bmad-output/implementation-artifacts/architecture.md#Cross-Cutting Concerns Identified`]
- Implementación existente (Story 7.4): [Source: `_bmad-output/implementation-artifacts/7-4-locks-de-concurrencia-claim-estado-visible-heartbeat-ttl.md`]

## Project Context Reference (must-read)

- `docsBmad/project-context.md`
  - Política de locks: claim preventivo, TTL 3m, heartbeat 10s, idle guard 2m, override Admin.
  - Auditoría best-effort (no bloqueante).
- `project-context.md`
  - Código/DB/paths en inglés; UI/mensajes en español.
  - Testing determinista + reglas de tooling Windows/Sail.
- `_bmad-output/implementation-artifacts/ux.md`
  - Journey 2/3 para locks; confirmaciones y estado seguro.
- `_bmad-output/implementation-artifacts/architecture.md`
  - Patrones: Actions/Livewire/config + estructura por módulos.

## Story Completion Status

- Status: **done**
- Completion note: Override Admin de locks (force-release/force-claim) implementado con RBAC server-side (`Gate::authorize('admin-only')`), auditoría best-effort **no bloqueante**, confirmación en UI, y 21 tests deterministas pasando.

## Dev Agent Record

### Agent Model Used

Claude Opus 4.5

### Implementation Summary

Implemented Admin override functionality for pending task locks following Story 7.4 patterns:

1. **ForceReleasePendingTaskLock Action**: Clears all lock fields regardless of owner, idempotent behavior when no lock exists
2. **ForceClaimPendingTaskLock Action**: Takes ownership of lock or creates one, renewals when admin already owns
3. **Livewire Integration**: Methods `forceReleaseLock()` and `forceClaimLock()` with `Gate::authorize('admin-only')`
4. **UI**: Admin-only buttons in Lock Status Banner with `wire:confirm` dialogs
5. **Audit**: Best-effort structured logging via `Log::info('PendingTaskLockOverride', [...])`

### Test Coverage (21 tests, 73 assertions)

- Force release: clears lock, idempotent, audit logs
- Force claim: takes lock, renewals, creates on free task, audit logs
- Editor loses lock: heartbeat fails after admin override, isLockedBy returns false
- UX lock perdido: banner + “Reintentar claim” tras override (vía heartbeat)
- RBAC: Admin can access, Editor/Lector cannot
- UI visibility: Admin sees buttons only when lock is from another user

### Completion Notes

1. Override Actions use `DB::transaction()` + `lockForUpdate()` for atomic operations (no race conditions)
2. Audit es best-effort y **no bloqueante**: cualquier falla de logging no detiene el override (incluye casos idempotentes)
3. UI buttons use `wire:confirm` for inline confirmation (consistent with existing codebase patterns)
4. `isAdmin()` reutiliza `User::isAdmin()` (evita duplicación de reglas)
5. All tests are deterministic (no sleeps) using `Carbon::setTestNow()` where needed

### File List

**Created:**
- `gatic/app/Actions/PendingTasks/ForceReleasePendingTaskLock.php`
- `gatic/app/Actions/PendingTasks/ForceClaimPendingTaskLock.php`
- `gatic/tests/Feature/PendingTasks/PendingTaskLockOverrideTest.php`

**Modified:**
- `docsBmad/project-context.md` (alinear política de locks: TTL 3m en vez de 15m)
- `gatic/app/Actions/PendingTasks/ForceReleasePendingTaskLock.php` (auditoría best-effort no bloqueante + evento estructurado también en idempotencia)
- `gatic/app/Actions/PendingTasks/ForceClaimPendingTaskLock.php` (auditoría best-effort no bloqueante)
- `gatic/app/Livewire/PendingTasks/PendingTaskShow.php` (métodos admin-only + manejo explícito de `ValidationException`)
- `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php` (acciones Admin + copy/acentos)
- `gatic/tests/Feature/PendingTasks/PendingTaskLockOverrideTest.php` (cobertura UX: banner “Lock perdido” + “Reintentar claim”)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (status: done)
- `_bmad-output/implementation-artifacts/7-5-admin-puede-liberar-forzar-reclamo-de-lock.md` (this file - status: done)

## Senior Developer Review (AI)

Fecha: 2026-01-22  
Resultado: **Approved (después de fixes)**  

Hallazgos corregidos (HIGH/MEDIUM):
- Auditoría best-effort no bloqueante: evitar que fallas de logging impidan overrides; evento estructurado también cuando no hay lock.
- Documentación alineada: File List incluye docs actualizados; referencia de sprint-status corregida.
- UX/consistencia: copy en español con acentos y tests cubriendo banner “Lock perdido” + “Reintentar claim”.

## Change Log

- 2026-01-22: Code review (AI) + fixes aplicados; Story marcada como **done** y sprint tracking sincronizado.
