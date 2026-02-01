# Story 12.3: Column Manager por tabla + persistencia

Status: done

Story Key: `12-3-column-manager-por-tabla-y-persistencia`  
Epic: `12` (UX avanzada)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Fecha: 2026-02-01

Fuentes (relevantes):
- `_bmad-output/project-planning-artifacts/epics.md` (Epic 12 / Story 12.3)
- Código:
  - `gatic/resources/views/components/ui/column-manager.blade.php`
  - `gatic/resources/js/ui/column-manager.js`
  - `gatic/resources/views/livewire/inventory/products/products-index.blade.php`
  - `gatic/resources/views/livewire/inventory/assets/assets-index.blade.php`
  - `gatic/resources/views/livewire/admin/users/users-index.blade.php`
  - `gatic/resources/views/livewire/pending-tasks/pending-tasks-index.blade.php`
  - `gatic/resources/views/livewire/employees/employees-index.blade.php`

## Story

As a usuario interno,  
I want poder mostrar/ocultar columnas en tablas principales,  
So that adapte la densidad de información a mi forma de trabajo.

## Acceptance Criteria

- AC1: “Columnas” permite toggles sin recargar.
- AC2: Persistencia por tabla (mínimo: `localStorage`).
- AC3: Columnas críticas no se pueden ocultar (fallback seguro).

## Implementación

- Se agregó un dropdown reusable (`<x-ui.column-manager />`) y un motor JS.
- La configuración se persiste por `tableKey` en `localStorage`.
- Se usan `data-column-table` + `data-column-key`/`data-column-required` para mapear columnas.

