# GATIC Documentation Index

**Type:** Planning + BMAD scaffolding (pre-code)
**Primary Language:** Markdown (target: PHP/Laravel)
**Architecture:** Laravel 11 monolith + Livewire 3
**Last Updated:** 2026-01-23

## Project Overview

Este repo hoy contiene artefactos de planificacion (brainstorming/backlog) y el framework BMAD para ejecutar workflows. El objetivo es un MVP de inventario/activos para intranet TI con trazabilidad, locks de concurrencia en "Tareas Pendientes" y UX consistente con la guia visual corporativa.

## Core Docs (start here)

- [Project Context Bible](./project-context.md) - Reglas, decisiones, alcance MVP y principios no negociables
- [Development Flow](./development-flow.md) - Flujo recomendado BMAD-first: `story_key` -> branch -> PR -> merge (GitHub opcional)
- [Gates 0-5 Execution Plan](./gates-execution.md) - Vista opcional para “GitHub bonito” (Milestones/Project); el backlog vive en BMAD
- [Project Overview](./project-overview.md) - Resumen ejecutivo + stack objetivo
- [Source Tree Analysis](./source-tree-analysis.md) - Estructura del repo y donde vive cada artefacto
- [PO Acceptance (Sign-off)](./process/po-acceptance.md) - Proceso de aceptación por épica (evita arrastres)
- [Audit Use Cases](./product/audit-use-cases.md) - Qué auditar (MVP) y qué NO auditar
- [Dev Pre-flight Checklist](./checklists/dev-preflight.md) - Checklist antes de implementar stories

## Sources

- Brainstorming (fuente base): `../_bmad-output/analysis/brainstorming-session-2025-12-25.md`
- Backlog derivado: `../_bmad-output/project-planning-artifacts/gatic-backlog.md`
- Guia visual corporativa: `../03-visual-style-guide.md`
- GitHub Milestones: Gate 0-5 (repo `CarlosVerasteguii/Proyecto-GATIC`)
- GitHub Project: "GATI-C" (Project v2 #3)

---

_Documentacion compilada siguiendo la intencion del workflow BMAD `document-project`, adaptada a un repositorio aun sin base de codigo._
