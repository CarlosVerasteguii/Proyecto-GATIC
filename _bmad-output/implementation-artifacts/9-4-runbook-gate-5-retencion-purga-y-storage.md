# Story 9.4: Runbook Gate 5 (retención/purga/storage)

Status: done

Story Key: `9-4-runbook-gate-5-retencion-purga-y-storage`  
Epic: `9` (Docs/Operación)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Fecha: 2026-01-25

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/epic-8-retro-2026-01-25.md` (operación Gate 5)
- Código:
  - `gatic/app/Support/Audit/AuditRecorder.php` + `gatic/app/Jobs/RecordAuditLog.php`
  - `gatic/app/Support/Errors/ErrorReporter.php`
  - `gatic/routes/console.php` (`gatic:purge-error-reports`)
  - `gatic/app/Livewire/Ui/AttachmentsPanel.php`
  - `gatic/app/Http/Controllers/Attachments/DownloadAttachmentController.php`
  - `gatic/app/Actions/Trash/*`

<!-- template-output: story_requirements -->

## Story

As a Admin/DevOps,  
I want un runbook de operación de evidencia/traceabilidad,  
so that podamos mantener el sistema saludable y depurar incidentes con checklist.

## Acceptance Criteria

- AC1: Runbook describe auditoría, error reports, adjuntos y papelera.
- AC2: Incluye checklist para casos típicos (“no aparece auditoría”, “descarga 404”, “purge falla por FK”).
- AC3: Referencia comandos/config relevantes (sin ejemplos con datos sensibles).

## Implementación

- Doc creada: `gatic/docs/ops/gate-5-runbook.md`

