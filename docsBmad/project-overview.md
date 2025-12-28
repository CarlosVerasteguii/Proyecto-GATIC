# GATIC — Project Overview

**Date:** 2025-12-27
**Type:** Web app (objetivo) + repositorio de planificación (estado actual)
**Architecture:** Objetivo: monolito Laravel + Livewire

## Executive Summary

GATIC será un sistema de inventario/activos para operación TI en intranet (on-prem). El MVP prioriza: (1) Inventario navegable (Productos/Activos), (2) Operación diaria (asignaciones/préstamos a empleados por RPE), (3) Flujo de “Tareas Pendientes” con locks de concurrencia y finalización parcial, y (4) Cierre de ciclo con auditoría/adjuntos/papelera.

## Project Classification

- **Repository Type:** Planning + BMAD workflows (pre-código)
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

- Gate 2: Inventario (Productos/Activos) + búsqueda unificada + detalles
- Gate 3: Empleados (RPE) + estados/acciones + préstamos + stock por cantidad
- Gate 4: Tareas Pendientes (carrito + procesamiento + locks)
- Gate 5: Auditoría + adjuntos + papelera

## Architecture Highlights (decisiones ya tomadas)

- Concurrencia: lock/claim a nivel Tarea Pendiente (heartbeat/TTL/timeout + override Admin).
- UX performance: polling con `wire:poll.visible` (badges 15s, métricas 60s, locks 10s).
- Auditoría “best effort”: no bloquea operación, registra internamente si falla.

## Development Overview

Este repo aún no contiene la app Laravel; Gate 0 define el arranque (Sail/Breeze/Livewire/CI/seeders). El plan ejecutable por tareas vive en GitHub Milestones y en `docsBmad/gates-execution.md`.

## Documentation Map

- `docsBmad/index.md` - índice principal
- `docsBmad/project-context.md` - bible (reglas/decisiones)
- `docsBmad/gates-execution.md` - ejecución por Gates
- `docsBmad/source-tree-analysis.md` - estructura del repo

---

_Generado a partir de la sesión de brainstorming y la estructura Gates 0–5 en GitHub._
