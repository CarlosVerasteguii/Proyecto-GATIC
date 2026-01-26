# Story 9.1: Diagramas de estado (Mermaid)

Status: done

Story Key: `9-1-diagramas-de-estado-mermaid`  
Epic: `9` (Docs/Operación)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Fecha: 2026-01-25

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/epic-7-retro-2026-01-23.md` (pendientes de documentación)
- `_bmad-output/implementation-artifacts/epic-8-retro-2026-01-25.md` (action items de docs)
- `project-context.md` + `docsBmad/project-context.md` (reglas críticas)
- Código:
  - `gatic/app/Models/Asset.php`
  - `gatic/app/Support/Assets/AssetStatusTransitions.php`
  - `gatic/app/Enums/PendingTaskStatus.php`
  - `gatic/app/Enums/PendingTaskLineStatus.php`
  - `gatic/app/Actions/PendingTasks/FinalizePendingTask.php`

<!-- template-output: story_requirements -->

## Story

As a Admin/Editor,  
I want documentación clara de estados y transiciones,  
so that el equipo pueda razonar sobre operación, UX y edge cases sin “parches”.

## Alcance (MVP)

- Diagramas Mermaid y notas de operación para:
  - Estados de Activos (`Asset`)
  - Estados de Tareas Pendientes (`PendingTask`)
  - Estados de Renglones (`PendingTaskLine`)

## Acceptance Criteria

- AC1: Los diagramas existen en `gatic/docs/state-machines/` y son legibles.
- AC2: Cada doc referencia la “fuente de verdad” en código.
- AC3: Se explican reglas y notas operativas mínimas (bloqueos, exclusiones en conteos, etc.).

## Implementación

Se agregaron los siguientes docs:

- `gatic/docs/state-machines/asset-states.md`
- `gatic/docs/state-machines/pending-task-states.md`
- `gatic/docs/state-machines/pending-task-line-states.md`

