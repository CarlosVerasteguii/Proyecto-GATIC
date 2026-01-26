# Story 9.3: Documentar scopes/helpers y conteos de inventario

Status: done

Story Key: `9-3-documentar-scopes-y-conteos-de-inventario`  
Epic: `9` (Docs/Operación)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Fecha: 2026-01-25

Fuentes (relevantes):
- `project-context.md` + `docsBmad/project-context.md` (semántica de disponibilidad)
- Código:
  - `gatic/app/Models/Asset.php` (`STATUSES`, `UNAVAILABLE_STATUSES`)
  - `gatic/app/Livewire/Inventory/Products/ProductsIndex.php` (conteos en listados)
  - `gatic/app/Livewire/Inventory/Products/ProductShow.php` (desglose por estado)
  - `gatic/app/Livewire/Inventory/Assets/AssetsIndex.php` (exclusión de retirados por default)

<!-- template-output: story_requirements -->

## Story

As a Developer,  
I want documentación de la semántica de conteos y decisiones del dominio,  
so that evitemos duplicación y errores al ajustar queries.

## Acceptance Criteria

- AC1: Existe un README de modelos con semántica de “total/available/unavailable”.
- AC2: Se documentan consideraciones de soft-delete y N+1 relevantes.
- AC3: Se referencia dónde vive el cálculo actual en UI.

## Implementación

- Doc creada: `gatic/app/Models/README.md`

