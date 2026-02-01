# Story 12.1: Dark Mode (tema claro/oscuro) + persistencia

Status: done

Story Key: `12-1-dark-mode-tema-claro-oscuro`  
Epic: `12` (UX avanzada)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Fecha: 2026-02-01

Fuentes (relevantes):
- `_bmad-output/project-planning-artifacts/epics.md` (Epic 12 / Story 12.1)
- Código:
  - `gatic/resources/views/layouts/app.blade.php`
  - `gatic/resources/views/layouts/guest.blade.php`
  - `gatic/resources/views/layouts/partials/topbar.blade.php`
  - `gatic/resources/js/ui/theme-toggle.js`
  - `gatic/resources/js/app.js`
  - `gatic/resources/sass/_layout.scss`
  - `gatic/resources/sass/_tokens.scss`

## Story

As a usuario interno,  
I want alternar entre tema claro y oscuro,  
So that reduzca fatiga visual y pueda trabajar cómodo por periodos largos.

## Acceptance Criteria

- AC1: Toggle disponible en cualquier pantalla autenticada.
- AC2: UI consistente (layout, cards, tablas, modals, toasts).
- AC3: Persistencia (mínimo: `localStorage`).
- AC4: Default seguro (usa `prefers-color-scheme` cuando aplica).

## Implementación

- Se habilitó el cambio de tema usando `data-bs-theme` (Bootstrap color modes).
- Se agregó un bootstrapper inline para aplicar tema antes de cargar Vite (evita “flash”).
- Se agregó toggle en topbar y persistencia en `localStorage`.
- Se ajustaron superficies custom (sidebar/drawer/borders) para respetar el tema.

