# Story 14.4: Costos y valor del inventario

Status: done

Story Key: `14-4-costos-y-valor-del-inventario`  
Epic: `14` (Datos de negocio: garantías, costos, proveedores, configuración, timeline, dashboard avanzado)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/epics.md` (Epic 14 / Story 14.4)
- `docsBmad/project-context.md` (bible: reglas de UX/performance/stack; semántica de `Retirado`)
- `project-context.md` (reglas lean: idioma, stack, testing, patrones Livewire)
- `_bmad-output/implementation-artifacts/architecture.md` (patrones y restricciones)
- `_bmad-output/implementation-artifacts/ux.md` (UX: loaders/cancelar si >3s; dashboards backoffice)
- `gatic/app/Models/Asset.php` (modelo, estados, SoftDeletes)
- `gatic/app/Livewire/Inventory/Assets/AssetForm.php` + `gatic/resources/views/livewire/inventory/assets/asset-form.blade.php` (captura/edición de activo)
- `gatic/app/Livewire/Inventory/Assets/AssetShow.php` + `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php` (detalle de activo)
- `gatic/app/Livewire/Dashboard/DashboardMetrics.php` + `gatic/resources/views/livewire/dashboard/dashboard-metrics.blade.php` (métricas existentes)
- `gatic/tests/Feature/DashboardMetricsTest.php` (patrón de tests y `data-testid`)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Admin/Editor,  
I want registrar costos de adquisición (y moneda) por Activo serializado,  
so that el Dashboard pueda mostrar el **valor total del inventario** y su **distribución por Categoría/Marca**.

## Acceptance Criteria

### AC1 - Captura y validación de costos por Activo

**Given** un usuario con permiso `inventory.manage`  
**When** crea o edita un Activo serializado  
**Then** puede capturar `acquisition_cost` (y moneda)  
**And** el sistema valida y guarda:
- `acquisition_cost` es numérico, `>= 0`, con **máximo 2 decimales** (formato consistente)
- Moneda fija en MVP: **MXN** (pesos mexicanos)

### AC2 - Visualización en el detalle del Activo

**Given** un Activo con `acquisition_cost` capturado  
**When** el usuario consulta el detalle del Activo  
**Then** el costo se muestra de forma clara (monto + moneda)  
**And** si no hay costo capturado, se muestra `—` (sin errores).

### AC3 - Valor total y distribución en Dashboard (por defecto sin Retirado)

**Given** Activos con `acquisition_cost` registrado  
**When** el usuario abre el Dashboard  
**Then** el Dashboard muestra:
- **Valor total del inventario (Activos)** basado en la suma de `acquisition_cost`
- un desglose (mínimo) por **Categoría** y por **Marca** (ej. top N + “otros”, o tabla completa si es pequeña)

**And** por defecto el cálculo **excluye** Activos con `status = Retirado`  
**And** solo incluye registros no eliminados por soft-delete (global scope)  
**And** si existe un filtro explícito “Incluir retirados”, al activarlo el total cambia (y queda claro en UI).

### AC4 - Performance + UX en consultas agregadas

**Given** que el Dashboard hace polling (~60s)  
**When** recalcula estas métricas  
**Then** usa **queries agregadas** (SUM/GROUP BY) eficientes (sin N+1, sin cargar colecciones completas)  
**And** si una consulta tarda >3s, aplica el patrón UX (>3s): loader/skeleton + opción **Cancelar** (ver `x-ui.long-request`) sin romper la experiencia del polling.

## Tasks / Subtasks

1) DB: agregar campos de costo a `assets` (AC: 1, 3)
- [x] Crear migration para:
  - [x] `assets.acquisition_cost` (DECIMAL(12,2), nullable)
  - [x] `assets.acquisition_currency` (CHAR(3), nullable)
- [x] Definir comportamiento de "sin costo": permitir null (no forzar captura en MVP).

2) Modelo: `Asset` (AC: 1, 2, 3)
- [x] Agregar campos a `$fillable`.
- [x] Agregar casts (`decimal:2` para `acquisition_cost`).

3) UI: captura/edición en `AssetForm` (AC: 1)
- [x] Agregar inputs para costo + moneda en:
  - [x] `gatic/app/Livewire/Inventory/Assets/AssetForm.php` (props + rules + persistencia create/update)
  - [x] `gatic/resources/views/livewire/inventory/assets/asset-form.blade.php` (inputs + mensajes)
- [x] Validación:
  - [x] costo `>= 0` + 2 decimales máximo (regex)
  - [x] moneda dentro de lista permitida (configurable via gatic.php)

4) UI: visualización en `AssetShow` (AC: 2)
- [x] Mostrar "Costo de adquisición" (monto + moneda) en `asset-show.blade.php`.
- [x] Mantener copy en español y formatos consistentes.

5) Dashboard: valor total + distribución por categoría/marca (AC: 3, 4)
- [x] Extender `gatic/app/Livewire/Dashboard/DashboardMetrics.php`:
  - [x] total value = SUM(`assets.acquisition_cost`) para Activos no retirados (default)
  - [x] breakdown por categoría (join `products` → `categories`)
  - [x] breakdown por marca (join `products` → `brands`, considerar `NULL` como "Sin marca")
- [x] Extender `gatic/resources/views/livewire/dashboard/dashboard-metrics.blade.php`:
  - [x] nueva card "Valor total" (con `data-testid`)
  - [x] tablas compactas para distribución (categorías/marcas)
  - [ ] (opcional) toggle "Incluir retirados" - no implementado en MVP, el valor por defecto excluye Retirados
- [x] Performance:
  - [x] queries agregadas (SUM/GROUP BY) usando DB::table() - evita N+1
  - [x] patrón UX (>3s) con Cancelar: `x-ui.long-request` en Dashboard (target `poll,refreshNow`)

6) Config: moneda y opciones (AC: 1, 3)
- [x] Agregar config en `gatic/config/gatic.php` para:
  - [x] `inventory.money.allowed_currencies` (lista: MXN)
  - [x] `inventory.money.default_currency` (MXN)
  - [x] `dashboard.value.top_n` (5 por defecto)

7) Tests (AC: 1–4)
- [x] `gatic/tests/Feature/Inventory/AssetsTest.php`:
  - [x] guardar/editar Activo con costo+moneda (Livewire) y ver persistencia
  - [x] validación: moneda inválida y costo inválido (negativos, más de 2 decimales)
- [x] `gatic/tests/Feature/DashboardMetricsTest.php`:
  - [x] total value excluye `Retirado` por defecto
  - [x] total value excluye soft-deleted por defecto
  - [x] breakdown por categoría/marca coherente
  - [x] manejo de "Sin marca" para productos sin brand_id
- [x] Regresión soft-delete: test que verifica que activos soft-deleted NO entran en sumas

## Dev Notes

### Developer Context (qué existe hoy y qué cambia)

- Ya existe el Dashboard de métricas con polling: `gatic/app/Livewire/Dashboard/DashboardMetrics.php` (Story 5.6).
- Ya existe semántica explícita de “Retirado no cuenta por defecto”:
  - `gatic/app/Models/Asset.php` (`STATUS_RETIRED`)
  - `gatic/app/Livewire/Inventory/Assets/AssetsIndex.php` (si `status=all`, excluye `Retirado`)
  - `gatic/app/Models/README.md` (fuente de verdad de conteos/semántica)
- Ya existe patrón UX para operaciones >3s con Cancelar:
  - `gatic/resources/views/components/ui/long-request.blade.php`
  - `gatic/resources/js/ui/long-request.js`

Esta story agrega:
- Campos de costo/moneda en Activos (DB + modelo + form + show).
- Métrica nueva en Dashboard: valor total y desglose por categoría/marca (sobre Activos).

### DEV AGENT GUARDRAILS (no negociables)

- **No inventar otro “valor” distinto** sin acuerdo: en MVP, el “valor del inventario” debe derivar de `acquisition_cost` (si se requiere “valor estimado” separado, ver preguntas al final).
- **Retirado**:
  - Default: **excluir** `status = Retirado` en el total (y dejarlo explícito en UI).
  - Solo incluir “Retirado” con filtro **explícito**.
- **Soft-delete**:
  - Las sumas/agrupaciones deben excluir registros soft-deleted (por default de Eloquent) y tener **test de regresión**.
- **Performance**:
  - En Dashboard: usar SUM/GROUP BY; evitar cargar modelos; evitar N+1.
  - Polling existe (~60s): asegurar queries razonables. Si el desglose es pesado, considerar limitar top N y/o hacer el desglose bajo demanda (sin romper el requisito de “distribución en dashboard”).
- **UX (>3s)**:
  - Si se identifica que la operación puede tardar >3s, integrar `x-ui.long-request` con `target` para no mostrar overlay en llamadas no relevantes (ej. evitar parpadeo constante en `poll` si se decide).
- **Idiomas**:
  - Identificadores de código/DB/rutas: **inglés**.
  - Copy/UI: **español**.

### Criterios técnicos sugeridos (para evitar bugs típicos)

- DB:
  - `acquisition_cost`: DECIMAL con 2 decimales (evitar float/double).
  - `acquisition_currency`: ISO 4217 (3 letras) con whitelist configurable.
- UI:
  - Input de costo con step `0.01` y validación server-side (no confiar en HTML5).
  - Select de moneda con default; si solo se usará una moneda en MVP, definirlo explícitamente y simplificar UI.
- Dashboard:
  - Total value: SUM de costos (tratar NULL como 0 o excluir NULL explícitamente).
  - Breakdown por categoría/marca: agrupar por `categories.name` / `brands.name` (o por id + nombre).
  - Manejar `brand_id = NULL` como “Sin marca”.

### Project Structure Notes

- DB: `gatic/database/migrations/*add_acquisition_cost_to_assets_table*.php`
- Modelo: `gatic/app/Models/Asset.php`
- Livewire (assets): `gatic/app/Livewire/Inventory/Assets/AssetForm.php`, `gatic/app/Livewire/Inventory/Assets/AssetShow.php`
- Views (assets): `gatic/resources/views/livewire/inventory/assets/asset-form.blade.php`, `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php`
- Livewire (dashboard): `gatic/app/Livewire/Dashboard/DashboardMetrics.php`
- View (dashboard): `gatic/resources/views/livewire/dashboard/dashboard-metrics.blade.php`
- Config: `gatic/config/gatic.php` (si se agrega whitelist/default/Top N)
- Tests: `gatic/tests/Feature/Inventory/AssetsTest.php`, `gatic/tests/Feature/DashboardMetricsTest.php`

### References

- Backlog fuente de verdad: `_bmad-output/implementation-artifacts/epics.md` (Epic 14 / Story 14.4).
- Semántica “Retirado no cuenta”: `docsBmad/project-context.md`, `gatic/app/Models/README.md`.
- Dashboard actual + patrón de polling: `gatic/app/Livewire/Dashboard/DashboardMetrics.php` + Story `_bmad-output/implementation-artifacts/5-6-dashboard-minimo-de-metricas-operativas-polling.md`.
- Patrón UX (>3s) con Cancelar: Story `_bmad-output/implementation-artifacts/1-9-componentes-ux-reutilizables-toasts-loaders-cancelar-actualizado-hace-xs.md` + `gatic/resources/views/components/ui/long-request.blade.php`.

## Testing Requirements

- Feature tests mínimos:
  - Assets: crear/editar con costo+moneda (validación y persistencia).
  - Dashboard: total value excluye `Retirado` por defecto; soft-deleted NO cuenta; breakdown consistente.
- Mantener estilo de tests existentes:
  - `data-testid` en UI para asserts robustos (ver `gatic/tests/Feature/DashboardMetricsTest.php`).
  - Livewire tests con `Livewire::test(...)` (ver `gatic/tests/Feature/Inventory/AssetsTest.php`).

## Previous Story Intelligence

- Story 5.6 (Dashboard): ya existe componente Livewire con polling + botón “Actualizar” + `ErrorReporter`. Reusar patrón y mantener performance.
- Story 1.9 (UX): `x-ui.long-request` ya existe; si el query de valor puede tardar >3s, usar el overlay cancelable con `target` (cuidar polling).
- Story 3.4 (Conteos): patrón claro de “Retirado no cuenta por defecto” + tests con mezcla de estados y soft-delete.
- Story 2.4 (Soft-delete): regresiones típicas al contar/sumar con modelos soft-deletable; cubrir con tests.

## Git Intelligence Summary

Commits recientes relevantes (referencia para patrones y estilo, no para “copiar y pegar”):
- `c0fb7bc` `feat(dashboard): implementar dashboard mínimo...` (base de DashboardMetrics)
- `329707a` `feat(assets): add warranty tracking with alerts` (activos: patrones de migrations + UI)
- `7f7692a` `feat(inventory): add contracts module with asset linking` (relaciones de activos)
- `a726991` `feat(inventory): add low stock threshold alerts` (métricas/dashboard + tests)

## Latest Tech Information

- No forzar upgrades en esta story: usar versiones fijadas por el repo (`composer.lock`, `package-lock.json`) y el stack definido en `docsBmad/project-context.md`.
- Laravel 11 / Livewire 3 / Bootstrap 5: implementar siguiendo patrones existentes del repo (Livewire-first, Bootstrap components, sin WebSockets).

## Project Context Reference

- Fuente de verdad (bible): `docsBmad/project-context.md`.
- Reglas lean: `project-context.md`.
- Arquitectura/patrones: `_bmad-output/implementation-artifacts/architecture.md`.
- UX (>3s, polling, dashboards): `_bmad-output/implementation-artifacts/ux.md` + Story 1.9.

## Open Questions (guardar para el final antes de implementar)

1) ¿“Valor estimado” requiere un campo adicional distinto de `acquisition_cost` (p. ej. `estimated_value`) o en MVP se interpreta como el mismo?
2) ¿Moneda(s) permitida(s) en MVP? (solo una como default vs multi-moneda con whitelist)
3) En el Dashboard: ¿el desglose debe incluir solo Activos con costo capturado, o tratar “sin costo” como 0 y listarlos igual?

## Story Completion Status

- Status: **done**
- Completion note: "Implementación + code review aplicados: costos por Activo, visualización, dashboard de valor (moneda default + advertencia si hay múltiples monedas), breakdown top N + Otros, UX >3s con Cancelar, y tests."

## Dev Agent Record

### Agent Model Used

Claude Opus 4.5 (Claude Code CLI)

### Debug Log References

- `_bmad/core/tasks/workflow.xml`
- `_bmad/bmm/workflows/4-implementation/dev-story/workflow.yaml`
- `_bmad/bmm/workflows/4-implementation/dev-story/instructions.xml`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`

### Implementation Plan

1. **DB Migration**: Agregados campos `acquisition_cost` (DECIMAL 12,2) y `acquisition_currency` (CHAR 3) a tabla `assets`
2. **Model Updates**: Campos en `$fillable` y cast `decimal:2` para costo
3. **Config**: Agregada sección `inventory.money` y `dashboard.value` en `gatic.php`
4. **AssetForm**: Props, validación (>=0, regex 2 decimales, moneda en whitelist), persistencia
5. **AssetShow**: Visualización de costo con formato y símbolo de moneda
6. **Dashboard**: Métricas de valor total + breakdown por categoría/marca usando queries agregadas (DB::table)
7. **Tests**: 8 nuevos tests para Assets + 6 nuevos tests para Dashboard

### Completion Notes List

- Implementación completada siguiendo patrones existentes del proyecto
- Usé `DB::table()` en lugar de `Asset::query()->selectRaw()` para evitar errores de Larastan con propiedades dinámicas
- El toggle "Incluir retirados" no se implementó en MVP - por defecto excluye Retirados (como especificado en project-context.md)
- Moneda (MVP): MXN (pesos mexicanos)
- Queries de breakdown limitadas a top 5 por defecto (configurable via `dashboard.value.top_n`)
- Tests cubren: creación, edición, validación, exclusión de Retirado, exclusión de soft-deleted, breakdown por categoría/marca, manejo de NULL brand
- Fixes post-review: moneda fija MXN (sin multi-moneda), agrega fila "Otros" cuando aplica, integra `x-ui.long-request` en Dashboard, y oculta métricas de valor para usuarios sin `inventory.manage`.

### File List

**Nuevos:**
- `gatic/database/migrations/2026_02_05_041115_add_acquisition_cost_to_assets_table.php`

**Modificados:**
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `gatic/app/Models/Asset.php`
- `gatic/app/Livewire/Inventory/Assets/AssetForm.php`
- `gatic/resources/views/livewire/inventory/assets/asset-form.blade.php`
- `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php`
- `gatic/app/Livewire/Dashboard/DashboardMetrics.php`
- `gatic/resources/views/livewire/dashboard/dashboard-metrics.blade.php`
- `gatic/config/gatic.php`
- `gatic/tests/Feature/Inventory/AssetsTest.php`
- `gatic/tests/Feature/DashboardMetricsTest.php`

### Change Log

- **2026-02-05**: Implementación completa de Story 14.4 - Costos y valor del inventario
  - Migration para campos `acquisition_cost` y `acquisition_currency` en tabla assets
  - Captura/edición de costo en formulario de activos con validación
  - Visualización de costo en detalle de activo
  - Métricas en Dashboard: valor total del inventario + breakdown por categoría/marca
  - Configuración de monedas permitidas y valores por defecto
  - Tests para validación, persistencia, exclusión de Retirado/soft-deleted
- **2026-02-05**: Code review (AI) — fixes aplicados
  - Moneda: MVP single-currency MXN (se elimina multi-moneda)
  - Dashboard: fila "Otros" cuando `top_n` aplica
  - Dashboard: UX >3s con Cancelar (`x-ui.long-request` target `poll,refreshNow`)
  - Seguridad: métricas de valor visibles solo con permiso `inventory.manage`

## Senior Developer Review (AI)

**Fecha:** 2026-02-05  
**Resultado:** ✅ Cambios requeridos aplicados y verificados con tests.

Hallazgos principales (resueltos):
- Moneda: MVP single-currency MXN (sin conversión / sin mezcla de monedas).
- UX/performance: se integra patrón `x-ui.long-request` para operaciones lentas del Dashboard.
- Claridad: breakdown como Top N + fila "Otros".
- Seguridad: se restringe la visualización de métricas de valor a `inventory.manage`.
