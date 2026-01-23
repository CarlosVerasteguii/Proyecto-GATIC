# Casos de uso de auditoría (MVP)

**Objetivo:** definir qué preguntas debe contestar la auditoría (Gate 5 / Epic 8) y qué eventos se deben registrar **sin sobre-auditar**.

## Principios (no negociables)

- **Best effort (no bloqueante):** si falla registrar auditoría, la operación principal **NO** debe fallar. El fallo se registra en logs.
- **Feed transversal:** auditoría NO reemplaza historiales de dominio (p.ej. movimientos/kardex/ajustes). Es un “timeline” consultable con links y contexto mínimo.
- **Contexto mínimo y seguro:** `context` es una allowlist de campos. Prohibido guardar secretos, dumps, payloads grandes o PII innecesaria.
- **Acceso restringido:** consultar auditoría es **Admin-only** (server-side).

---

## Preguntas que la auditoría debe responder (MUST)

### Operación (día a día)

1. ¿Quién ejecutó esta acción y cuándo? (actor + timestamp)
2. ¿Sobre qué entidad ocurrió? (tipo + id)
3. ¿Qué acción fue exactamente? (acción estable)
4. ¿Cuáles fueron los IDs relevantes para investigar rápido? (p.ej. `asset_id`, `product_id`, `employee_id`)

### Incidentes / soporte

1. Para un error reportado por el usuario, ¿qué acciones ocurrieron justo antes/después?
2. ¿Quién forzó un lock (force-claim/force-release) y a quién se lo quitó?

### Control / cumplimiento interno (ligero)

1. ¿Quién hizo ajustes de inventario y con qué motivo/nota?
2. ¿Quién asignó/prestó/devolvió un activo y a qué empleado?

---

## Eventos mínimos a auditar (MVP para Story 8.1)

> Convención: `area.subarea.verb` (strings estables)

### Pending Tasks (locks override)

- `pending_tasks.lock.force_release`
- `pending_tasks.lock.force_claim`

### Inventario (ajustes)

- `inventory.adjustment.apply`

### Movimientos (serializados)

- `movements.asset.assign`
- `movements.asset.loan`
- `movements.asset.return`

### Movimientos (por cantidad)

- `movements.product_qty.register`

---

## Esquema mínimo del evento (recomendado)

Campos (persistencia):

- `created_at`
- `actor_user_id` (nullable si aplica)
- `action` (string)
- `subject_type` (string)
- `subject_id` (int)
- `context` (json nullable)

### Allowlist de `context` (MVP)

Solo se permite guardar:

- `pending_task_id`
- `previous_locked_by_user_id`, `new_locked_by_user_id`
- `asset_id`, `product_id`, `employee_id`
- `inventory_adjustment_id` (si existe un modelo raíz)
- `movement_id` (si el subject no es el movimiento)
- `summary` (string corto, sin datos sensibles)
- `reason` (string corto; si no existe, omitir)

### Prohibido en `context`

- contraseñas/tokens/headers
- contenido de archivos (adjuntos)
- dumps completos de “before/after”
- emails/PII si no es estrictamente necesario para soporte

---

## Reglas de consulta (UI)

Filtros MUST (para que sea usable):

- rango de fecha (`from/to`)
- actor (usuario)
- acción
- tipo de entidad (`subject_type`)

Opcionales (si salen “gratis”):

- `subject_id` exacto
- búsqueda simple por texto sobre `summary` (si existe)

Orden:

- más reciente primero (`created_at desc`)

---

## Fallback operativo (best effort)

Si falla persistencia/queue/DB:

- La operación principal continúa.
- Se emite log estructurado (warning) con:
  - `action`, `subject_type`, `subject_id`
  - `actor_user_id` (si existe)
  - `error_id` (si aplica)

---

## Alcance explícito (NO hacer en 8.1)

- Auditoría de lecturas/consultas (solo acciones mutantes).
- Auditoría de adjuntos (se verá en Epic 8.3 si aplica).
- “Before/after” completo de entidades (solo IDs + resumen).

