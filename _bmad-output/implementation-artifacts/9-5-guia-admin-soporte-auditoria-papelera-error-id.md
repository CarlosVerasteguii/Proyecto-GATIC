# Story 9.5: Guía Admin/Soporte (auditoría, papelera, error_id)

Status: done

Story Key: `9-5-guia-admin-soporte-auditoria-papelera-error-id`  
Epic: `9` (Docs/Operación)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Fecha: 2026-01-25

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/epic-8-retro-2026-01-25.md` (soporte por `error_id`)
- Código:
  - `gatic/app/Livewire/Admin/Audit/AuditLogsIndex.php`
  - `gatic/app/Livewire/Admin/ErrorReports/ErrorReportsLookup.php`
  - `gatic/app/Livewire/Admin/Trash/TrashIndex.php`
  - `gatic/app/Actions/PendingTasks/Force*PendingTaskLock.php`

<!-- template-output: story_requirements -->

## Story

As a Admin,  
I want una guía corta de soporte para investigar incidentes,  
so that podamos resolver tickets sin exponer detalles sensibles y con trazabilidad.

## Acceptance Criteria

- AC1: La guía describe el flujo por `error_id` y sus límites (best-effort).
- AC2: Incluye uso de auditoría (filtros + contexto) y papelera (restore/purge).
- AC3: Incluye troubleshooting básico de locks y adjuntos.

## Implementación

- Doc creada: `gatic/docs/support/admin-support-guide.md`

