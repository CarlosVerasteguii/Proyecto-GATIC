# Story 7.4: Locks de concurrencia (claim + estado visible + heartbeat/TTL)

Status: done

Story Key: `7-4-locks-de-concurrencia-claim-estado-visible-heartbeat-ttl`  
Epic: `7` (Gate 4: Tareas Pendientes + locks de concurrencia)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/epics.md` (Epic 7 / Story 7.4; FR29, FR30; NFR9)
- `_bmad-output/implementation-artifacts/PRD.md` (FR29–FR31; NFR9)
- `_bmad-output/implementation-artifacts/ux.md` (Journey 2/3; Locks & Concurrency)
- `_bmad-output/implementation-artifacts/architecture.md` (patrones: Actions/Livewire/config; polling/heartbeat)
- `docsBmad/project-context.md` (bible: política de locks)
- `project-context.md` (stack + toolchain Windows + reglas críticas)
- `_bmad-output/implementation-artifacts/7-3-procesamiento-por-renglon-edicion-estados-y-finalizacion-parcial.md` (puntos de integración en modo “Procesar”)
- Código actual (módulo PendingTasks):
  - `gatic/app/Livewire/PendingTasks/PendingTaskShow.php`
  - `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php`
  - `gatic/app/Models/PendingTask.php` (campos de lock ya existen: `locked_by_user_id`, `locked_at`, `heartbeat_at`, `expires_at`)
  - `gatic/config/gatic.php` (polling + `locks_heartbeat_interval_s`)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Admin/Editor,  
I want que solo un editor procese una Tarea Pendiente a la vez con lock visible,  
so that se eviten conflictos y doble aplicación (FR29, FR30, NFR9).

## Alcance (MVP)

Esta story implementa **locks de concurrencia** para el flujo de **Procesar** en Tareas Pendientes:

Incluye:
- Claim/lock **preventivo** al hacer clic en “Procesar” (antes de editar/aplicar).
- Lock **visible** a terceros: “quién lo tiene” y “desde cuándo”.
- Heartbeat (~10s) + expiración por TTL/timeout según NFR9.
- Idle guard: **no renovar** el lock si no hubo actividad real del usuario ~2 min.
- Manejo de “lock perdido” (expiró o se lo quitaron): pasar a **modo read‑only** con banner y opción de reintentar claim.

No incluye (fuera de scope / otras stories):
- Override Admin “liberar/force‑claim” (Story 7.5 / FR31).
- Notificaciones reales en “Solicitar liberación” (MVP es informativo).
- Auditoría formal de locks (Epic 8). En esta story, mínimo: loguear eventos relevantes.

## Definiciones (para evitar ambigüedad)

- **Lock activo**: `expires_at > now()` y `locked_by_user_id` no es null.
- **Lock expirado**: `expires_at <= now()` (se considera libre para nuevo claim).
- **Owner del lock**: usuario `locked_by_user_id`.
- **locked_at**: timestamp del claim inicial (para “desde cuándo”).
- **heartbeat_at**: último heartbeat recibido (para diagnósticos/UI).
- **expires_at**: fin de lease TTL (renovable por heartbeat si aplica).

## Acceptance Criteria

### AC1 — Claim preventivo y exclusividad (FR29)

**Given** una Tarea Pendiente sin lock activo  
**When** un Editor hace clic en “Procesar”  
**Then** el sistema adquiere un lock/claim para ese Editor  
**And** la pantalla entra en modo “Procesar” (edición por renglón habilitada).

**Given** una Tarea Pendiente con lock activo de otro usuario  
**When** un Editor hace clic en “Procesar”  
**Then** el sistema NO permite entrar a modo “Procesar”  
**And** la pantalla queda en modo solo lectura.

### AC2 — Lock visible a terceros (FR30)

**Given** una Tarea Pendiente con lock activo  
**When** otro usuario abre la tarea (o refresca)  
**Then** ve claramente:
- quién tiene el lock (nombre de usuario)
- desde cuándo (`locked_at`)
- estado: “Bloqueada” / “Libre”

### AC3 — Heartbeat y lease TTL (NFR9)

**Given** un Editor con lock activo y pestaña visible  
**When** permanece trabajando en modo “Procesar”  
**Then** se envía heartbeat aproximadamente cada 10s  
**And** el sistema renueva `expires_at` (lease TTL ~3 min) en cada heartbeat permitido.

### AC4 — Idle guard (NFR9)

**Given** un Editor con lock activo  
**When** NO hubo actividad real del usuario por ~2 min  
**Then** el lock NO se renueva por heartbeat  
**And** expira de forma natural por TTL.

### AC5 — Expiración y recuperación

**Given** un lock expirado (TTL vencido o sin heartbeat)  
**When** un Editor intenta “Procesar”  
**Then** puede adquirir el lock y continuar normalmente.

### AC6 — Lock perdido durante el procesamiento (UX Journey 2)

**Given** un Editor estaba en modo “Procesar”  
**When** el lock se pierde (expira/otro lo reclama en Story 7.5)  
**Then** la UI cambia a **read-only** inmediatamente  
**And** muestra banner “Lock perdido” con acción “Reintentar claim”.

### AC7 — Liberación del lock al finalizar

**Given** un Editor con lock activo finaliza la tarea (o sale del modo Procesar voluntariamente)  
**When** la acción termina OK  
**Then** el sistema libera el lock (deja `locked_by_user_id`, `locked_at`, `heartbeat_at`, `expires_at` en null)  
**And** la UI vuelve al estado consistente (finalizado / parcialmente finalizado).

### AC8 — Seguridad/RBAC (NFR4)

**Given** un usuario sin `inventory.manage`  
**When** intenta adquirir/renovar/liberar locks (por UI o request)  
**Then** el servidor lo rechaza (403) y no cambia el estado del lock.

## Tasks / Subtasks

- [x] Backend: acciones de lock (AC: 1–5,8)
  - [x] Crear Actions en `gatic/app/Actions/PendingTasks/*`:
    - `AcquirePendingTaskLock` (claim atómico)
    - `HeartbeatPendingTaskLock` (renovar lease + actualizar `heartbeat_at`)
    - `ReleasePendingTaskLock` (liberar si el owner es el actor)
  - [x] Claim atómico con transacción + `lockForUpdate()` sobre `pending_tasks`
  - [x] Regla de "lock activo" basada en `expires_at`
  - [x] No permitir locks si la tarea está `completed` o `cancelled`
  - [x] Logging mínimo (info/warn) para: claim ok/deny, heartbeat deny por no-owner/expirado, release

- [x] UI/Livewire: integrar claim antes de "Procesar" (AC: 1,2,6,7)
  - [x] En `gatic/app/Livewire/PendingTasks/PendingTaskShow.php`, modificar `enterProcessMode()`:
    - intentar claim primero
    - si falla: no cambiar status a `processing`, setear estado UI read‑only + toast/banner
    - si ok: entrar en modo procesar como hoy
  - [x] Asegurar que acciones sensibles (validar/editar/finalizar) requieran lock propio activo:
    - si no: bloquear y mostrar mensaje accionable ("No tienes el lock / el lock expiró")

- [x] UI: banner/estado de lock (AC: 2,6)
  - [x] En `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php`, agregar bloque visible con:
    - estado (Libre/Bloqueada)
    - usuario owner (si aplica)
    - "desde cuándo" (`locked_at`)
  - [x] Modo read-only para terceros (deshabilitar botones/inputs de procesar/editar)

- [x] Heartbeat + idle guard (AC: 3,4)
  - [x] Implementar heartbeat solo cuando:
    - pestaña visible (no background)
    - hubo actividad real reciente (~2 min)
  - [x] Preferir JS liviano para trackear actividad y disparar heartbeat (sin spamear requests)
  - [x] Intervalo y thresholds desde config (`config/gatic.php`) y no "magic numbers"

- [x] Tests (mínimos, pero bloqueantes) (AC: 1–5,8)
  - [x] Feature/Action: claim exclusivo (user A obtiene; user B rechaza mientras `expires_at > now`)
  - [x] Feature/Action: heartbeat renueva `expires_at`
  - [x] Feature/Action: lock expirado permite nuevo claim
  - [x] Feature/Action: release solo por owner (otros 403/ValidationException según enfoque)
  - [x] Livewire: un usuario sin lock no puede `finalizeTask` ni `validateLine` (mensaje + no cambios)

## Dev Notes

### Contexto del módulo (ya existe Gate 4 base)

- El módulo PendingTasks ya está implementado para creación/captura/procesamiento parcial (Stories 7.1–7.3).  
  El lock todavía **no** está aplicado en UI/Actions: actualmente `enterProcessMode()` cambia status a `processing` sin claim.
- La tabla `pending_tasks` ya tiene campos de lock: `locked_by_user_id`, `locked_at`, `heartbeat_at`, `expires_at`.  
  Esta story debe **usar esos campos** (no inventar una tabla de locks separada en MVP, salvo que sea estrictamente necesario).

### Guardrails técnicos (no negociables)

- Claim debe ser **atómico** (transacción + row lock) para evitar doble claim en carrera.
- No asumir “tab close” fiable: la liberación es “best effort”; el fallback real es TTL/timeout.
- No romper el flujo de Story 7.3:
  - `finalizeTask`, `validateLine`, edición por renglón deben requerir lock propio activo.
  - Si el lock se pierde, degradar a read-only y guiar al usuario a reintentar.

### Política de locks (fuente de verdad)

Según `docsBmad/project-context.md` / `PRD.md`:
- Claim al hacer clic en “Procesar” (preventivo).
- Timeout rolling ~15 min.
- Lease TTL ~3 min renovado por heartbeat.
- Idle guard: no renovar si no hubo actividad real ~2 min.
- Heartbeat: ~10s (solo visible/activo).

### Recomendación de implementación (con Livewire 3)

Para cumplir idle guard sin sobrecargar el server:
- Trackear “actividad real” en el browser (mousemove/keydown/click/scroll con debounce).
- Enviar heartbeat **solo** si:
  - `document.visibilityState === 'visible'`
  - `Date.now() - lastActivityAt <= idle_guard_ms`
- El heartbeat server-side solo hace:
  - verificar owner + no expirado
  - setear `heartbeat_at = now()`
  - setear `expires_at = now() + lease_ttl`

### Project Structure Notes

- Mantener el patrón:
  - `gatic/app/Actions/PendingTasks/*` (casos de uso)
  - `gatic/app/Livewire/PendingTasks/*` (UI)
  - `gatic/config/gatic.php` para timeouts/polling/defaults
- Identificadores (código/rutas/DB) en inglés; copy/UI en español.

### Librerías / Frameworks (versiones observadas; NO actualizar por esta story)

- Laravel: `laravel/framework` **v11.47.0** (ver `gatic/composer.lock`)
- Livewire: `livewire/livewire` **v3.7.3** (ver `gatic/composer.lock`)
- Bootstrap: **5.2.3** (ver `gatic/package.json`)
- Vite: **6.0.11** (ver `gatic/package.json`)
- PHP objetivo del proyecto: **>= 8.2** (ver `gatic/composer.json`)

### Previous Story Intelligence (7.3)

- La UI debe poder volverse read-only si no hay lock (integración explícita pedida en Story 7.3).
- Evitar “mentir” al usuario: si perdió el lock, bloquear acciones y mostrar banner claro con next step.

### Git Intelligence (contexto reciente)

Commits relevantes:
- `f04553d` feat(gate4): procesamiento por renglón, validación y finalización parcial (Story 7.3)
- `e58c08d` feat(pending-tasks): captura serializado/cantidad (Story 7.2)
- `548be79` feat(gate4): crear tarea pendiente y administrar renglones (Story 7.1)

Archivos de alto impacto para esta story:
- `gatic/app/Livewire/PendingTasks/PendingTaskShow.php`
- `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php`
- `gatic/app/Models/PendingTask.php`
- `gatic/database/migrations/2026_01_18_000000_create_pending_tasks_table.php`
- `gatic/tests/Feature/PendingTasks/*`

### Testing Requirements Summary

- Probar concurrencia determinista (sin sleeps largos): controlar tiempo con `Carbon::setTestNow()`.
- Usar `RefreshDatabase` y factories existentes.
- Cubrir al menos: claim exclusivo, heartbeat renueva, expiración permite claim, RBAC.

### References

- Story 7.4 base: [Source: `_bmad-output/implementation-artifacts/epics.md#Story 7.4`]
- FR29/FR30/NFR9: [Source: `_bmad-output/implementation-artifacts/PRD.md#Non-Functional Requirements`]
- UX lock/read-only/banners: [Source: `_bmad-output/implementation-artifacts/ux.md#Journey 2 - Editor (Soporte): Tarea Pendiente con lock (concurrencia)`]
- Arquitectura/polling/config: [Source: `_bmad-output/implementation-artifacts/architecture.md#Cross-Cutting Concerns Identified`]
- Política de locks (bible): [Source: `docsBmad/project-context.md#Política de Locks (Tareas Pendientes)`]

## Project Context Reference (must-read)

- `docsBmad/project-context.md`:
  - Política de locks: claim preventivo, timeout rolling 15m, TTL 3m, idle guard 2m, heartbeat 10s.
  - Polling sin WebSockets (`wire:poll.visible`) y UX de “Actualizado hace X”.
- `project-context.md`:
  - Identificadores en inglés; copy/UI en español.
  - Gate `inventory.manage` (server-side) para PendingTasks.
- `_bmad-output/implementation-artifacts/ux.md`:
  - Journey 2: lock visible + read-only a terceros + banner “lock perdido”.
  - Journey 3: Admin maneja excepciones (pero override real es Story 7.5).

## Story Completion Status

- Status: **done**
- Completion note: Code review ejecutada; se corrigieron issues HIGH/MEDIUM detectados y se validaron los tests de locks.

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- Auto-discovery: `_bmad-output/implementation-artifacts/sprint-status.yaml` → primera story en `backlog` fue `7-4-locks-de-concurrencia-claim-estado-visible-heartbeat-ttl`.
- Artefactos analizados: `_bmad-output/implementation-artifacts/epics.md`, `_bmad-output/implementation-artifacts/PRD.md`, `_bmad-output/implementation-artifacts/ux.md`, `_bmad-output/implementation-artifacts/architecture.md`, `docsBmad/project-context.md`, `project-context.md`, y el código actual en `gatic/` (módulo PendingTasks).
- Repo versions verificadas localmente: `gatic/composer.lock` (Laravel/Livewire) y `gatic/package.json` (Bootstrap/Vite).

### Completion Notes List

1. Story 7.4 definida como “lock layer” para modo Procesar (Story 7.3) sin override Admin (Story 7.5).
2. Reglas explícitas de lock activo/expirado usando `expires_at` para evitar ambigüedad.
3. Idle guard resuelto con heartbeat condicionado por actividad real del usuario (client-side) + verificación server-side de owner.
4. Plan de tests mínimo definido para evitar regresiones de concurrencia y RBAC.
5. Code review: se reforzó UX de “lock perdido” (mostrar banner + reintentar incluso si el lock expiró y quedó libre), se agregó `Gate::authorize()` en acciones sensibles, y se evitó mass assignment de campos de lock.

### File List

**Nuevos (esperados):**
- `gatic/app/Actions/PendingTasks/AcquirePendingTaskLock.php`
- `gatic/app/Actions/PendingTasks/HeartbeatPendingTaskLock.php`
- `gatic/app/Actions/PendingTasks/ReleasePendingTaskLock.php`
- `gatic/tests/Feature/PendingTasks/PendingTaskLockTest.php`
- `gatic/tests/Feature/PendingTasks/PendingTaskLockLivewireTest.php`

**Modificados (esperados):**
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (sync story status)
- `gatic/app/Livewire/PendingTasks/PendingTaskShow.php`
- `gatic/app/Models/PendingTask.php`
- `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php`
- `gatic/config/gatic.php` (timeouts/thresholds; si faltan)
