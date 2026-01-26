# Story 9.2: Documentar patrón de locks de concurrencia

Status: done

Story Key: `9-2-documentar-patron-de-locks-de-concurrencia`  
Epic: `9` (Docs/Operación)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Fecha: 2026-01-25

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/epic-7-retro-2026-01-23.md` (locks + follow-through)
- `_bmad-output/implementation-artifacts/epic-8-retro-2026-01-25.md` (hardening)
- `project-context.md` + `docsBmad/project-context.md` (reglas críticas: TTL/heartbeat/idle guard)
- Código:
  - `gatic/app/Models/PendingTask.php`
  - `gatic/app/Actions/PendingTasks/*PendingTaskLock.php`
  - `gatic/app/Livewire/PendingTasks/PendingTaskShow.php`
  - `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php`
  - `gatic/config/gatic.php`

<!-- template-output: story_requirements -->

## Story

As a Developer,  
I want una referencia única del patrón de locks,  
so that podamos mantener concurrencia segura y depurar bloqueos sin guesswork.

## Acceptance Criteria

- AC1: La doc describe claim/heartbeat/release y su semántica de “lock activo”.
- AC2: Incluye overrides Admin y nota de auditoría best-effort.
- AC3: Incluye la configuración relevante (keys) y el idle guard en UI.
- AC4: Menciona la defensa adicional de idempotencia por renglón en finalización.

## Implementación

- Doc creada: `gatic/docs/patterns/concurrency-locks.md`

