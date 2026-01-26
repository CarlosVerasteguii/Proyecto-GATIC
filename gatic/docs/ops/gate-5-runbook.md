# Runbook — Gate 5 (Trazabilidad y evidencia)

Este runbook cubre operación y mantenimiento de las piezas “transversales” del sistema:

- Auditoría (`audit_logs`)
- Reportes de error (`error_reports` + `error_id`)
- Adjuntos (DB + storage privado)
- Papelera (soft-delete, restore, purge)

## Auditoría (Audit Logs)

Fuente de verdad:

- Modelo: `gatic/app/Models/AuditLog.php`
- Recorder best-effort: `gatic/app/Support/Audit/AuditRecorder.php`
- Job: `gatic/app/Jobs/RecordAuditLog.php`
- UI Admin: `gatic/app/Livewire/Admin/Audit/AuditLogsIndex.php`

Puntos operativos:

- `AuditRecorder::record()` despacha un job `afterCommit()`. Si el queue worker no está corriendo, los eventos pueden quedar encolados y la UI no verá logs nuevos.
- Los eventos se “sanitizan” (allowlist de keys) para evitar sobre-auditar datos sensibles.

Checklist rápido si “no aparece auditoría”:

- Verificar que el worker del queue está activo.
- Revisar la tabla `jobs` (si está creciendo, hay backlog).
- Revisar logs de la app por warnings de `AuditRecorder`/`RecordAuditLog`.

## Reportes de error (`error_id`)

Fuente de verdad:

- Reporter: `gatic/app/Support/Errors/ErrorReporter.php`
- Modelo/tabla: `gatic/app/Models/ErrorReport.php` (`error_reports`)
- UI Admin: `gatic/app/Livewire/Admin/ErrorReports/ErrorReportsLookup.php`
- Purga: `gatic/routes/console.php` (`gatic:purge-error-reports`)

Operación:

- El `error_id` se genera (ULID) y se registra en DB de forma best-effort.
- Si el persist falla, el sistema aún devuelve `error_id` y lo registra en logs.

Purga:

- Comando: `php artisan gatic:purge-error-reports`
- Retención configurable vía `GATIC_ERROR_REPORTS_RETENTION_DAYS` / `gatic.errors.reporting.retention_days`.

## Adjuntos (DB + storage privado)

Fuente de verdad:

- Modelo: `gatic/app/Models/Attachment.php`
- UI reusable: `gatic/app/Livewire/Ui/AttachmentsPanel.php`
- Descarga segura: `gatic/app/Http/Controllers/Attachments/DownloadAttachmentController.php`

Puntos operativos:

- Los archivos se guardan en disk `local` (storage privado), bajo ruta tipo: `storage/app/attachments/{Type}/{id}/{uuid}`.
- La descarga SIEMPRE pasa por un endpoint autenticado/autorizado (no hay links públicos).

Si un usuario reporta “descarga 404”:

- Verificar que el registro `attachments` exista y que `Storage::disk($attachment->disk)->exists($attachment->path)` sea verdadero.
- Si el archivo está perdido, el controller devuelve 404 y loguea warning (es señal de drift en storage).

## Verificación de storage privado (entorno real)

Checklist recomendado antes de “uso operativo”:

- Confirmar que `gatic/config/filesystems.php` disk `local` apunta a `storage/app/private`.
- Confirmar que el web server **no** expone `storage/app/private` como ruta pública.
- Validar RBAC:
  - Usuario sin permisos: no puede descargar adjuntos (403/404 según aplique).
  - Usuario con permisos: puede descargar vía endpoint autenticado.
- Validar que no existan links directos públicos a archivos (todo pasa por controller).

## Papelera (soft-delete / restore / purge)

Fuente de verdad:

- Acciones: `gatic/app/Actions/Trash/*`
- UI Admin: `gatic/app/Livewire/Admin/Trash/TrashIndex.php`

Reglas:

- `restore`: restaura registros soft-deleted (puede fallar por duplicados).
- `purge`: hace `forceDelete()` y puede fallar por constraints FK.
- `emptyTrash`: intenta purgar en lote (best-effort) y reporta cuántos fallaron.

Notas operativas:

- Si `purge` falla por FK, el mensaje indica dependencias (historial/movimientos/etc.).
- Las operaciones de papelera generan auditoría best-effort (`trash.*`), por lo que el queue worker impacta visibilidad.
