# Patrón: Locks de concurrencia (Tareas Pendientes)

Este patrón evita que dos usuarios procesen la misma `PendingTask` al mismo tiempo.

Fuente de verdad:

- Campos/semántica: `gatic/app/Models/PendingTask.php`
- Claim/release/heartbeat:
  - `gatic/app/Actions/PendingTasks/AcquirePendingTaskLock.php`
  - `gatic/app/Actions/PendingTasks/ReleasePendingTaskLock.php`
  - `gatic/app/Actions/PendingTasks/HeartbeatPendingTaskLock.php`
- Overrides Admin (auditados):
  - `gatic/app/Actions/PendingTasks/ForceClaimPendingTaskLock.php`
  - `gatic/app/Actions/PendingTasks/ForceReleasePendingTaskLock.php`
- UI (modo proceso + idle guard): `gatic/app/Livewire/PendingTasks/PendingTaskShow.php` + `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php`

## Modelo de datos (lock en DB)

En `pending_tasks`:

- `locked_by_user_id`: propietario del lock
- `locked_at`: cuándo se adquirió
- `heartbeat_at`: último heartbeat
- `expires_at`: expiración del lease

Definición de lock activo (MVP): `expires_at > now()` (ver `PendingTask::hasActiveLock()`).

## Flujo normal (usuario)

1) **Claim** al entrar a “Procesar”

- `AcquirePendingTaskLock` corre en transacción y usa `lockForUpdate()` en la fila de `PendingTask`.
- Solo permite lock si el status está en: `ready`, `processing`, `partially_completed`.
- Casos:
  - Si el lock activo es del mismo usuario: renueva lease (idempotente).
  - Si el lock activo es de otro usuario: responde `success=false` con mensaje.
  - Si el lock expiró: permite reclamarlo.

2) **Heartbeat** mientras se procesa

- Front-end llama `$wire.heartbeat()` en intervalo fijo.
- Reglas de envío (idle guard):
  - Solo cuando el tab está visible (`document.visibilityState === 'visible'`).
  - Solo si hubo actividad reciente del usuario (eventos como mouse/teclado/click/scroll).

3) **Release** al salir o finalizar

- `ReleasePendingTaskLock` solo libera si el usuario es el propietario.
- Limpia todos los campos del lock.

## Configuración

Valores en `gatic/config/gatic.php`:

- `gatic.pending_tasks.locks.lease_ttl_s` (lease TTL)
- `gatic.pending_tasks.locks.idle_guard_s` (umbral de “idle”)
- `gatic.ui.polling.locks_heartbeat_interval_s` (intervalo de heartbeat desde UI)

## Overrides Admin (auditados)

Para destrabar operación:

- `ForceReleasePendingTaskLock`: libera el lock sin importar propietario actual (idempotente).
- `ForceClaimPendingTaskLock`: toma (o renueva) el lock para Admin.

Ambos registran auditoría best-effort con:

- `AuditLog::ACTION_LOCK_FORCE_RELEASE`
- `AuditLog::ACTION_LOCK_FORCE_CLAIM`

Nota: la auditoría se persiste vía queue (`AuditRecorder` → `RecordAuditLog`), así que el worker debe estar activo para ver eventos en UI.

## Defensa adicional: idempotencia por renglón

Aunque el lock es por tarea, `FinalizePendingTask` protege cada renglón:

- Cada renglón se procesa en su propia transacción.
- Se hace `lockForUpdate()` sobre la fila de `PendingTaskLine`.
- Si ya está `applied`, se omite (no se reaplica).

Esto reduce el riesgo de doble-aplicación ante concurrencia/refresh/reintentos.

