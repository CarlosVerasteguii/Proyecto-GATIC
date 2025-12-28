# GATIC — Source Tree Analysis

**Date:** 2025-12-27

## Overview

Este repositorio hoy está orientado a **planificación y ejecución por Gates** (issues/milestones), con el framework **BMAD** incluido para correr workflows de análisis/planificación. La base de código Laravel aún no existe en el repo (se crea en Gate 0).

## Complete Directory Structure (alto nivel)

```
.
├── _bmad/                       # Framework BMAD (agentes, workflows, plantillas)
├── _bmad-output/                # Artefactos generados por workflows (brainstorming/backlog/status)
├── docsBmad/                    # Documentación “document-project” (este folder)
├── .agent/                      # Config/metadata de herramientas de agente (si aplica)
├── .claude/                     # Config/metadata de Claude (si aplica)
├── .cursor/                     # Config/metadata de Cursor (si aplica)
├── .github/                     # Config GitHub (workflows, templates, etc.)
├── .vscode/                     # Config VSCode
├── 03-visual-style-guide.md     # Guía visual corporativa (referencia de colores/branding; desactualizada)
└── project-context.md           # Puntero al bible (ver docsBmad/project-context.md)
```

## Critical Directories

### `_bmad/`

**Purpose:** Contiene BMAD Method (agentes como Analyst “Mary”, workflows como `document-project`, y tareas core como `workflow.xml`).

**Notas:**

- `/_bmad/bmm/agents/` define personas y menús.
- `/_bmad/bmm/workflows/` define workflows (análisis/planificación/solución/implementación).

### `_bmad-output/`

**Purpose:** Salidas de workflows y artefactos de planificación.

**Archivos relevantes:**

- `_bmad-output/analysis/brainstorming-session-2025-12-25.md` (fuente base de decisiones)
- `_bmad-output/project-planning-artifacts/gatic-backlog.md` (backlog por Gates)
- `_bmad-output/bmm-workflow-status.yaml` (estado del método BMAD)

### `docsBmad/`

**Purpose:** Documentación compilada para dar contexto a agentes y servir de “entrypoint”.

## Notes for Development

- Antes de implementar, seguir el orden de Gates y asegurar CI verde desde Gate 0.
- UI/estilos deben tomar `03-visual-style-guide.md` como referencia de colores/branding desde el inicio (evita retrabajo), sin tratarlo como catálogo rígido.

---

_Documento alineado a la intención del workflow BMAD `document-project` para un repositorio aún sin código fuente._
