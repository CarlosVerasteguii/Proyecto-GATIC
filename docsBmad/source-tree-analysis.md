# GATIC ÔÇö Source Tree Analysis

**Date:** 2025-12-27

## Overview

Este repositorio hoy est├í orientado a **planificaci├│n y ejecuci├│n por Gates** (issues/milestones), con el framework **BMAD** incluido para correr workflows de an├ílisis/planificaci├│n. La base de c├│digo Laravel a├║n no existe en el repo (se crea en Gate 0).

## Complete Directory Structure (alto nivel)

```
.
Ôö£ÔöÇ _bmad/                       # Framework BMAD (agentes, workflows, plantillas)
Ôö£ÔöÇ _bmad-output/                # Artefactos generados por workflows (brainstorming/backlog/status)
Ôö£ÔöÇ docsBmad/                    # Documentaci├│n ÔÇ£document-projectÔÇØ (este folder)
Ôö£ÔöÇ .agent/                      # Config/metadata de herramientas de agente (si aplica)
Ôö£ÔöÇ .claude/                     # Config/metadata de Claude (si aplica)
Ôö£ÔöÇ .cursor/                     # Config/metadata de Cursor (si aplica)
Ôö£ÔöÇ .github/                     # Config GitHub (workflows, templates, etc.)
Ôö£ÔöÇ .vscode/                     # Config VSCode
Ôö£ÔöÇ 03-visual-style-guide.md     # Gu├¡a visual corporativa (restricci├│n dura)
ÔööÔöÇ project-context.md           # Puntero al bible (ver docsBmad/project-context.md)
```

## Critical Directories

### `_bmad/`

**Purpose:** Contiene BMAD Method (agentes como Analyst ÔÇ£MaryÔÇØ, workflows como `document-project`, y tareas core como `workflow.xml`).

**Notas:**

- `/_bmad/bmm/agents/` define personas y men├║s.
- `/_bmad/bmm/workflows/` define workflows (an├ílisis/planificaci├│n/soluci├│n/implementaci├│n).

### `_bmad-output/`

**Purpose:** Salidas de workflows y artefactos de planificaci├│n.

**Archivos relevantes:**

- `_bmad-output/analysis/brainstorming-session-2025-12-25.md` (fuente base de decisiones)
- `_bmad-output/project-planning-artifacts/gatic-backlog.md` (backlog por Gates)
- `_bmad-output/bmm-workflow-status.yaml` (estado del m├®todo BMAD)

### `docsBmad/`

**Purpose:** Documentaci├│n compilada para dar contexto a agentes y servir de ÔÇ£entrypointÔÇØ.

## Notes for Development

- Antes de implementar, seguir el orden de Gates y asegurar CI verde desde Gate 0.
- UI/estilos deben alinearse a `03-visual-style-guide.md` desde el inicio (evita retrabajo).

---

_Documento alineado a la intenci├│n del workflow BMAD `document-project` para un repositorio a├║n sin c├│digo fuente._

