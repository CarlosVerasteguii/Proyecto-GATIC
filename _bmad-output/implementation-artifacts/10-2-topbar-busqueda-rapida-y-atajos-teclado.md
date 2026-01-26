# Story 10.2: Topbar — búsqueda rápida y atajos de teclado

Status: done

Story Key: `10-2-topbar-busqueda-rapida-y-atajos-teclado`  
Epic: `10` (UI uplift)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Fecha: 2026-01-25

Fuentes (relevantes):
- `_bmad-output/project-planning-artifacts/ux-design-specification.md` (productividad/atajos)
- Código:
  - `gatic/resources/views/layouts/partials/topbar.blade.php`
  - `gatic/resources/js/ui/global-search-shortcuts.js`
  - `gatic/resources/js/app.js`
  - `gatic/routes/web.php` (route `inventory.search`)

<!-- template-output: story_requirements -->

## Story

As a usuario,  
I want buscar rápido desde cualquier pantalla,  
so that reduzca fricción para navegar inventario.

## Acceptance Criteria

- AC1: Existe una barra de búsqueda en topbar (con permisos `inventory.view`).
- AC2: Atajo `/` enfoca la búsqueda.
- AC3: `Esc` limpia o cierra la búsqueda cuando está enfocada.

## Implementación

- Se agregó form de búsqueda en topbar (desktop) y botón de búsqueda (mobile):
  - `gatic/resources/views/layouts/partials/topbar.blade.php`
- Se agregaron atajos de teclado:
  - `gatic/resources/js/ui/global-search-shortcuts.js`
  - `gatic/resources/js/app.js`

