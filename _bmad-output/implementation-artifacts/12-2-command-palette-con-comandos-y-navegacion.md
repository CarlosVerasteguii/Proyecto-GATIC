# Story 12.2: Command Palette (Ctrl/Cmd+K) con comandos y navegación

Status: done

Story Key: `12-2-command-palette-con-comandos-y-navegacion`  
Epic: `12` (UX avanzada)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Fecha: 2026-02-01

Fuentes (relevantes):
- `_bmad-output/project-planning-artifacts/epics.md` (Epic 12 / Story 12.2)
- Código:
  - `gatic/app/Livewire/Ui/CommandPalette.php`
  - `gatic/resources/views/livewire/ui/command-palette.blade.php`
  - `gatic/resources/js/ui/command-palette.js`
  - `gatic/resources/js/ui/hotkeys.js`
  - `gatic/resources/views/layouts/app.blade.php`
  - `gatic/resources/views/components/ui/hotkeys-help.blade.php`

## Story

As a usuario avanzado,  
I want abrir una command palette y ejecutar acciones sin mouse,  
So that reduzca fricción y navegue/actúe más rápido.

## Acceptance Criteria

- AC1: `Ctrl/Cmd+K` abre la palette con input enfocado.
- AC2: `Esc` cierra sin perder estado de pantalla.
- AC3: Navegación con teclado (↑/↓ + Enter).
- AC4: Comandos visibles respetan RBAC.
- AC5: Jump por match exacto (serial/asset_tag/RPE) y separación lógica por secciones.

## Implementación

- Se agregó una modal global (Bootstrap) con resultados por secciones (Navegación/Crear/Buscar/Match exacto).
- El listado de comandos respeta permisos vía `can(...)`.
- Se agregó manejo de teclado (↑/↓/Enter) y apertura por evento desde el hotkey.

