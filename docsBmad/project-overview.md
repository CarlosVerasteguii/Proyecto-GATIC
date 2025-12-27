# GATIC ÔÇö Project Overview

**Date:** 2025-12-27
**Type:** Web app (objetivo) + repositorio de planificaci├│n (estado actual)
**Architecture:** Objetivo: monolito Laravel + Livewire

## Executive Summary

GATIC ser├í un sistema de inventario/activos para operaci├│n TI en intranet (onÔÇæprem). El MVP prioriza: (1) Inventario navegable (Productos/Activos), (2) Operaci├│n diaria (asignaciones/pr├®stamos a empleados por RPE), (3) Flujo de ÔÇ£Tareas PendientesÔÇØ con locks de concurrencia y finalizaci├│n parcial, y (4) Cierre de ciclo con auditor├¡a/adjuntos/papelera.

## Project Classification

- **Repository Type:** Planning + BMAD workflows (preÔÇæc├│digo)
- **Project Type(s):** Web app (Laravel)
- **Primary Language(s):** Markdown (hoy) / PHP (objetivo)
- **Architecture Pattern:** Laravel monolith + Livewire (polling)

## Technology Stack Summary (objetivo)

- Laravel 11 + PHP 8.2+
- Livewire 3 + Blade
- Bootstrap 5 (alineado a `03-visual-style-guide.md`) + Vite/NPM
- MySQL 8
- Auth: Breeze (Blade) remaquetado a Bootstrap
- RBAC: roles fijos Admin/Editor/Lector + Policies/Gates (Spatie por validar)
- CI: Pint + PHPUnit + Larastan
- Local: Laravel Sail (Docker)
- Prod: Docker Compose (Nginx + PHP-FPM) (por definir en servidor final)

## Key Features (por Gates)

- Gate 2: Inventario (Productos/Activos) + b├║squeda unificada + detalles
- Gate 3: Empleados (RPE) + estados/acciones + pr├®stamos + stock por cantidad
- Gate 4: Tareas Pendientes (carrito + procesamiento + locks)
- Gate 5: Auditor├¡a + adjuntos + papelera

## Architecture Highlights (decisiones ya tomadas)

- Concurrencia: lock/claim a nivel Tarea Pendiente (heartbeat/TTL/timeout + override Admin).
- UX performance: polling con `wire:poll.visible` (badges 15s, m├®tricas 60s, locks 10s).
- Auditor├¡a ÔÇ£best effortÔÇØ: no bloquea operaci├│n, registra internamente si falla.

## Development Overview

Este repo a├║n no contiene la app Laravel; Gate 0 define el arranque (Sail/Breeze/Livewire/CI/seeders). El plan ejecutable por tareas vive en GitHub Milestones y en `docsBmad/gates-execution.md`.

## Documentation Map

- `docsBmad/index.md` - ├¡ndice principal
- `docsBmad/project-context.md` - bible (reglas/decisiones)
- `docsBmad/gates-execution.md` - ejecuci├│n por Gates
- `docsBmad/source-tree-analysis.md` - estructura del repo

---

_Generado a partir de la sesi├│n de brainstorming y la estructura Gates 0ÔÇô5 en GitHub._

