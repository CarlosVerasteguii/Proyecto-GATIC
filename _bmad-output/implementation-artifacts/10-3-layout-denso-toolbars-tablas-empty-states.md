# Story 10.3: Layout más denso (tablas/tooling/espaciado)

Status: done

Story Key: `10-3-layout-denso-toolbars-tablas-empty-states`  
Epic: `10` (UI uplift)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Fecha: 2026-01-25

Fuentes (relevantes):
- `_bmad-output/project-planning-artifacts/ux-design-specification.md` (desktop-first/densidad)
- Código:
  - `gatic/resources/sass/_variables.scss` (container widths)
  - Vistas con tablas (`table-sm`) en inventario/admin/catálogos

<!-- template-output: story_requirements -->

## Story

As a usuario,  
I want pantallas más densas y escaneables,  
so that pueda operar más rápido en desktop.

## Acceptance Criteria

- AC1: Contenedores más anchos en pantallas grandes (menos whitespace).
- AC2: Tablas principales usan densidad `table-sm`.

## Implementación

- Se ampliaron límites de `container` para pantallas grandes:
  - `gatic/resources/sass/_variables.scss`
- Se hizo `table-sm` en tablas principales (listas/admin/búsqueda):
  - `gatic/resources/views/livewire/inventory/products/products-index.blade.php`
  - `gatic/resources/views/livewire/inventory/assets/assets-index.blade.php`
  - `gatic/resources/views/livewire/pending-tasks/pending-tasks-index.blade.php`
  - `gatic/resources/views/livewire/search/inventory-search.blade.php`
  - `gatic/resources/views/livewire/admin/trash/trash-index.blade.php`
  - `gatic/resources/views/livewire/admin/users/users-index.blade.php`
  - `gatic/resources/views/livewire/employees/employees-index.blade.php`
  - `gatic/resources/views/livewire/catalogs/*/*-index.blade.php`
  - `gatic/resources/views/livewire/catalogs/trash/catalogs-trash.blade.php`
  - `gatic/resources/views/livewire/inventory/adjustments/adjustments-index.blade.php`

