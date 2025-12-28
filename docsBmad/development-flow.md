# Flujo de desarrollo (BMAD-first + GitHub opcional)

## Principio

- **BMAD es la fuente de verdad del backlog**: `_bmad-output/project-planning-artifacts/epics.md`.
- **`sprint-status.yaml` es tracking**, no backlog: `_bmad-output/implementation-artifacts/sprint-status.yaml`.
- **1 `story_key` = 1 unidad de trabajo** (dev + QA).

## Sprint planning (epics → sprint-status)

1. Revisa/prioriza epics/stories en `_bmad-output/project-planning-artifacts/epics.md`.
2. Genera/actualiza `_bmad-output/implementation-artifacts/sprint-status.yaml` (idealmente vía `*sprint-planning`, o manual si prefieres control total).
3. Marca qué `story_key` entran al sprint (ej. `todo`/`in_progress` según tu convención).

## Flujo por story (recomendado)

1. Elige un `story_key` en `_bmad-output/implementation-artifacts/sprint-status.yaml`.
2. Corre `*create-story` (con ese `story_key`) para generar el story file en `_bmad-output/implementation-artifacts/<story_key>.md`.
3. Crea rama: `git checkout -b story-<story_key>-<slug>`.
4. Corre `*dev-story` usando ese story file, implementa y commitea normal.
5. Pide `*code-review` (con el story file + diff/PR).
6. Actualiza `_bmad-output/implementation-artifacts/sprint-status.yaml` (estado, notas, links).

## GitHub (solo para visibilidad / seguimiento)

- Opcional: crea una issue por story (o por epic) y **guarda el link** en el story file y/o en `sprint-status.yaml`.
- En PR usa `Refs #NN` si NO quieres autocerrar; usa `Closes #NN` si quieres que se cierre al merge.
