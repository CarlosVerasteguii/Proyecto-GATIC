# Guía de soporte (Admin) — Auditoría, Papelera, `error_id`

Objetivo: resolver tickets con evidencia y sin filtrar información sensible.

## 1) Si el usuario trae un `error_id`

1. Ir a la pantalla Admin de lookup de errores.
2. Buscar por `error_id`.
3. Revisar:
   - `exception_class`
   - `exception_message` (sanitizado)
   - `stack_trace` (solo Admin)
   - `context` (keys allowlist: request/user)

Buenas prácticas:

- No copiar/pegar stack traces a usuarios finales. Usa el `error_id` como referencia.
- Si el `error_id` no aparece en DB, revisar logs del servidor (persistencia es best-effort).

## 2) Usar auditoría para reconstruir “qué pasó”

1. Abrir el visor Admin de auditoría.
2. Filtrar por:
   - Actor (usuario)
   - Acción (p. ej. `trash.restore`, `attachments.upload`, `pending_tasks.lock.force_release`)
   - Subject type (entidad)
3. Abrir detalle y revisar `context` (IDs relevantes).

## 3) Si “desapareció” un registro: revisar Papelera

1. Ir a Admin → Papelera.
2. Buscar por nombre/serial/RPE según tab.
3. Acciones:
   - **Restaurar** si fue borrado por error.
   - **Purgar** solo si se entiende el impacto (puede fallar por dependencias FK).

## 4) Si un usuario está “atorado” por locks en Tareas Pendientes

Síntomas:

- No puede entrar a “Procesar” porque otro usuario tiene el lock.
- Perdió el lock (expiró) y no puede finalizar.

Acciones Admin:

- Forzar liberar lock (para destrabar a alguien).
- Forzar reclamar lock (para intervenir y terminar/diagnosticar).

Nota: los overrides quedan auditados (best-effort) en `audit_logs`.

## 5) Si hay problemas con adjuntos (subida/descarga)

Checklist:

- Confirmar permisos (`attachments.view` / `attachments.manage` + gate de visibilidad de entidad).
- Verificar existencia del registro `attachments` y que el archivo exista en storage (disk/path).
- Revisar auditoría (`attachments.upload` / `attachments.delete`) para reconstruir cambios.

