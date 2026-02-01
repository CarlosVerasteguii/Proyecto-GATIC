# Story 12.4: Breadcrumbs globales y consistentes

Status: done

Story Key: `12-4-breadcrumbs-globales-y-consistentes`  
Epic: `12` (UX avanzada)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Fecha: 2026-02-01

Fuentes (relevantes):
- `_bmad-output/project-planning-artifacts/epics.md` (Epic 12 / Story 12.4)
- Código:
  - `gatic/resources/views/components/ui/breadcrumbs.blade.php`
  - `gatic/resources/views/components/ui/toolbar.blade.php`
  - `gatic/resources/views/components/ui/detail-header.blade.php`
  - `gatic/resources/views/livewire/inventory/products/products-index.blade.php`
  - `gatic/resources/views/livewire/inventory/products/product-show.blade.php`
  - `gatic/resources/views/livewire/inventory/assets/assets-index.blade.php`
  - `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php`
  - `gatic/resources/views/livewire/search/inventory-search.blade.php`
  - `gatic/resources/views/livewire/employees/employees-index.blade.php`
  - `gatic/resources/views/livewire/employees/employee-show.blade.php`
  - `gatic/resources/views/livewire/pending-tasks/pending-tasks-index.blade.php`
  - `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php`

## Story

As a usuario interno,  
I want breadcrumbs consistentes en pantallas de listado/detalle,  
So that tenga orientación y vuelva rápido al contexto anterior.

## Acceptance Criteria

- AC1: Breadcrumbs en headers de pantallas navegables.
- AC2: Links en todos salvo el ítem actual.
- AC3: Labels claros (UI en español) y accesibles (`aria-label`).

## Implementación

- Se creó `<x-ui.breadcrumbs />` (Bootstrap breadcrumb) y se integró en toolbar/detail-header y vistas principales.

