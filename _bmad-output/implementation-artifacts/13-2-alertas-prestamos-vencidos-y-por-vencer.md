# Story 13.2: Alertas de préstamos vencidos / por vencer (listas + dashboard)

Status: done

Story Key: `13-2-alertas-prestamos-vencidos-y-por-vencer`  
Epic: `13` (Alertas operativas)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Backlog (fuente de verdad): `_bmad-output/implementation-artifacts/epics.md` (Epic 13, Story 13.2)

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/epics.md` (Epic 13: Stories 13.1–13.3)
- `_bmad-output/implementation-artifacts/13-1-fecha-vencimiento-en-prestamos-de-activos.md` (campo canónico `assets.loan_due_date`, validación y learnings)
- `_bmad-output/implementation-artifacts/5-6-dashboard-minimo-de-metricas-operativas-polling.md` (patrones dashboard + polling + tests)
- `_bmad-output/implementation-artifacts/architecture.md` (estructura por módulos; Livewire → Actions → DB; reglas de performance/errores)
- `_bmad-output/implementation-artifacts/ux.md` + `_bmad-output/implementation-artifacts/1-9-componentes-ux-reutilizables-toasts-loaders-cancelar-actualizado-hace-xs.md` (UX: frescura, polling visible, long-request)
- `docsBmad/project-context.md` (bible: reglas no negociables)
- `project-context.md` (reglas críticas adicionales + tooling local Windows)
- `gatic/config/gatic.php` (intervalos polling + umbrales UX)
- `gatic/routes/web.php` + `gatic/resources/views/livewire/dashboard/dashboard-metrics.blade.php` + `gatic/app/Livewire/Dashboard/DashboardMetrics.php` (dashboard actual)
- `gatic/app/Models/Asset.php` + `gatic/app/Models/Employee.php` (soft-delete + relaciones + campo `loan_due_date`)
- `gatic/docs/ui-patterns.md` (patrones: `<x-ui.poll />`, `<x-ui.long-request />`, `error_id`)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Admin/Editor,  
I want ver préstamos vencidos y por vencer en un solo lugar,  
so that priorice devoluciones y reduzca incidencias.

## Alcance (MVP)

Incluye:
- Contadores en dashboard: **“Vencidos”** y **“Por vencer”** (ventana configurable, ej. 7/14/30 días).
- Navegación desde cada contador hacia un **listado de alertas** ya filtrado (vencidos vs por vencer).
- Listado de alertas con información mínima para operar:
  - Activo (producto + serial + asset_tag si aplica)
  - Empleado (RPE + nombre si existe)
  - Fecha de vencimiento
  - Días vencidos / restantes
  - Acciones rápidas: **Ver detalle** / **Devolver** (si aplica y con permisos)

No incluye (fuera de alcance):
- Notificaciones push/email/SMS.
- Jobs/cron para “generar alertas”; todo se calcula on-demand con queries.
- UX de configuración avanzada por usuario (preferir defaults + opciones acotadas).

## Definiciones operativas (para evitar ambigüedad)

- **Vencido**: `loan_due_date < hoy` (hoy = `today()` en timezone de la app).
- **Por vencer**: `hoy <= loan_due_date <= hoy + ventana_días` (incluye los que vencen hoy).
- Solo cuentan préstamos vigentes: `assets.status = Prestado` y `assets.loan_due_date IS NOT NULL`.
  - Préstamos sin vencimiento (`loan_due_date = null`) **NO** entran en estas alertas (opcional: mostrar un contador “Sin vencimiento” en story futura).

## Acceptance Criteria

### AC1 — Dashboard: contadores + deep links

**Given** existen activos prestados con fecha de vencimiento  
**When** el usuario entra al dashboard  
**Then** ve contadores de “Vencidos” y “Por vencer” (ventana configurable, ej. 7/14/30 días)  
**And** puede navegar a un listado filtrado desde cada contador.

### AC2 — Listado: datos mínimos + acciones rápidas

**Given** el usuario está en el listado de alertas  
**When** revisa un préstamo  
**Then** ve activo, empleado, fecha de vencimiento, días vencidos/restantes  
**And** tiene acciones rápidas (ver detalle / devolver si aplica y tiene permisos).

## Tasks / Subtasks

1) Dashboard: métricas de alertas (AC: 1)
- [x] Extender `gatic/app/Livewire/Dashboard/DashboardMetrics.php` para calcular:
  - [x] `loansOverdueCount`
  - [x] `loansDueSoonCount` (según ventana configurada)
- [x] Extender `gatic/resources/views/livewire/dashboard/dashboard-metrics.blade.php` con 2 tarjetas nuevas:
  - [x] “Vencidos”
  - [x] “Por vencer”
  - [x] Cada tarjeta debe tener link al listado filtrado (route nueva de alertas).

2) Alertas: listado filtrable (AC: 2)
- [x] Crear componente Livewire (propuesto):
  - [x] `gatic/app/Livewire/Alerts/Loans/LoanAlertsIndex.php`
  - [x] `gatic/resources/views/livewire/alerts/loans/loan-alerts-index.blade.php`
- [x] Soportar filtro mínimo por query string:
  - [x] `type=overdue|due-soon`
  - [x] `windowDays=7|14|30` (si aplica)
- [x] Query eficiente (sin N+1) + paginación (`config('gatic.ui.pagination.per_page')`).
- [x] Renderizar tabla densa (Bootstrap 5) con acciones rápidas:
  - [x] Ver Activo (route existente `inventory.products.assets.show`)
  - [x] Devolver (si `@can('inventory.manage')` y el activo sigue `Prestado`) → route existente `inventory.products.assets.return`

3) Routing + acceso (AC: 1, 2)
- [x] Agregar rutas en `gatic/routes/web.php` (path en inglés):
  - [x] `GET /alerts/loans` → `alerts.loans.index`
  - [x] Middleware: `auth`, `active`, y gate (ver sección RBAC abajo).

4) Configuración mínima (AC: 1)
- [x] Definir default de ventana y opciones permitidas (propuesto en `gatic/config/gatic.php`).

5) Tests (AC: 1, 2)
- [x] Agregar/ajustar tests feature (ver “Testing requirements”).

## Dev Notes

### Developer Context (lectura obligatoria)

#### Contexto funcional (Epic 13)

- Esta story convierte el “dato” creado en Story 13.1 (vencimiento) en **señales operativas accionables**:
  - Dashboard: contadores + deep links
  - Listado: tabla operativa + acciones rápidas
- Próxima story del epic (13.3) también vive en “alertas” pero para **inventario bajo**: evita diseñar algo tan específico que impida reutilizar UI/patrón de alertas.

#### Contexto técnico ya existente (Story 13.1)

- Campo canónico (ya existe): `assets.loan_due_date` (`DATE`, nullable) + índice `loan_due_date`.
  - Migración: `gatic/database/migrations/2026_02_01_000000_add_loan_due_date_to_assets_table.php`.
  - Modelo: `gatic/app/Models/Asset.php` con cast `immutable_date`.
- Fuente de verdad del “préstamo vigente” también vive en `assets`:
  - `assets.status = Prestado`
  - `assets.current_employee_id` (tenencia actual)
- Flujo de préstamo/devolución (ya existe):
  - Préstamo: `gatic/app/Actions/Movements/Assets/LoanAssetToEmployee.php` (valida `loan_due_date` formato `Y-m-d` y no en pasado).
  - Devolución: `gatic/app/Actions/Movements/Assets/ReturnLoanedAsset.php` (limpia `loan_due_date`).
  - UI préstamo: `gatic/app/Livewire/Movements/Assets/LoanAssetForm.php` + `gatic/resources/views/livewire/movements/assets/loan-asset-form.blade.php`.
  - UI detalle: `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php` y `gatic/resources/views/livewire/employees/employee-show.blade.php` muestran vencimiento.

#### Dashboard actual (Story 5.6) — dónde colgar los contadores

- Route: `GET /dashboard` → `gatic/resources/views/dashboard.blade.php` → `<livewire:dashboard.dashboard-metrics />`.
- Componente: `gatic/app/Livewire/Dashboard/DashboardMetrics.php`
  - Polling: `<x-ui.poll method="poll" :interval-s="config('gatic.ui.polling.metrics_interval_s')">`
  - UX frescura: `<x-ui.freshness-indicator :updated-at="$lastUpdatedAtIso" />`
  - Error handling prod-safe: `ErrorReporter` + toast con `error_id`
  - Tests existentes: `gatic/tests/Feature/DashboardMetricsTest.php` (usa `data-testid="dashboard-metric-*"` y `Carbon::setTestNow()`).

#### Datos a mostrar en el listado (AC2) — mínimo para operar

Para cada registro (alerta):
- Activo: producto, serial, asset_tag (si aplica)
- Empleado: RPE + nombre (si existe; puede ser null si el empleado fue soft-deleted)
- `loan_due_date` en formato `d/m/Y`
- Días vencidos/restantes (ver “Definiciones” arriba)
- Acciones rápidas:
  - Ver Activo (siempre)
  - Devolver (solo si sigue `Prestado` y el usuario tiene `inventory.manage`)

#### Query guidance (no reinventar, no N+1)

Filtro base (solo alertables):
- `status = Asset::STATUS_LOANED`
- `loan_due_date IS NOT NULL`

Vencidos:
- `loan_due_date < today()`

Por vencer:
- `today() <= loan_due_date <= today()->addDays(windowDays)`

Recomendación:
- Para el listado: `Asset::query()->with(['product', 'currentEmployee'])->where(...)->orderBy('loan_due_date')->paginate(...)`.
- Para el dashboard: evitar cargar modelos; usar `count()` agregados y mantener queries simples (si hace falta, refactorizar a Action reutilizable para no duplicar lógica entre dashboard y listado).

#### Edge cases (no romper UX)

- Empleado soft-deleted: `asset->currentEmployee` será null (global scope). Mostrar `—` y NO romper tabla.
- Producto soft-deleted: `asset->product` puede ser null. Mostrar `—`.
- Activo cambió de estado entre “ver alerta” y “abrir”: degradar con mensaje claro (por ejemplo, si ya no está `Prestado`, el botón “Devolver” no aplica).
- `loan_due_date` nulo: no entra en alertas (pero puede existir préstamo legacy).

### Technical Requirements (guardrails)

- **RBAC server-side obligatorio**:
  - Route del listado debe estar protegido con middleware `can:inventory.manage` (recomendado) o `can:inventory.view` + “Devolver” condicionado (definir decisión; ver “Preguntas abiertas”).
  - Dentro del componente Livewire, reforzar con `Gate::authorize(...)` en `mount()` y `render()` (patrón existente en forms de movimientos).
- **Sin WebSockets**: no agregar Echo/Pusher/SSE. Si se requiere “actualidad”, usar `wire:poll.visible` y siempre mostrar frescura (patrón `x-ui.poll` + `x-ui.freshness-indicator`). [Source: `docsBmad/project-context.md`]
- **Performance**:
  - Dashboard: queries agregadas (`count()`) sin cargar modelos; evitar N+1.
  - Listado: `with(['product', 'currentEmployee'])` + paginación. Nunca cargar colecciones completas.
  - Considerar índice compuesto si el query planner lo requiere: `(status, loan_due_date)` (solo si hay evidencia de performance; ya existe índice en `loan_due_date`).
- **UX de operación lenta (>3s)**:
  - Si el listado o filtros pueden tardar, envolver resultados con `<x-ui.long-request target="loadAlerts" />` (y evitar que el polling lo dispare si lo agregas). [Source: `gatic/docs/ui-patterns.md`]
- **Errores con `error_id`**:
  - Dashboard: seguir patrón existente (`ErrorReporter` + toast + `x-ui.error-alert-with-id`).
  - Listado: mismo patrón. En `local/testing` re-lanzar; en `prod` capturar y mostrar mensaje humano.
- **Fechas y zona horaria**:
  - Usar `today()`/`now()` (Carbon) consistente con timezone de app (no hardcode UTC).
  - Para cálculos de “días”, preferir `CarbonImmutable`/`Carbon` diff en PHP para no depender de SQL dialect si no es necesario.
- **Copy/UI en español; código/DB/rutas en inglés** (regla no negociable).

### Architecture Compliance (no romper estructura)

- **Estructura por módulos (referencia arquitectura)**:
  - `app/Livewire/<Module>/*` para páginas/componentes.
  - `app/Actions/<Module>/*` para casos de uso (especialmente writes / transacciones).
  - `config/gatic.php` para defaults/intervalos/constantes operativas (sin números mágicos en UI).
- **Ruta y naming**:
  - Path en inglés `kebab-case`: `/alerts/loans` (no `/alertas/...`).
  - Route name `dot.case`: `alerts.loans.index`.
  - Mantener consistencia con `inventory.*`, `pending-tasks.*`, etc. [Source: `project-context.md`]
- **Acciones existentes**:
  - “Devolver” debe navegar al flujo existente (no duplicar lógica): `inventory.products.assets.return`.
  - No crear una “devolución inline” en el listado en esta story (evita inventar UI + errores transaccionales; si se quiere, hacerlo en una story futura con spec).
- **Compatibilidad con SoftDeletes**:
  - `Asset`, `Employee`, `Product` usan SoftDeletes. Las queries Eloquent ya excluyen `deleted_at` por defecto; agregar tests para evitar regresiones.

### Library / Framework Requirements

- **Laravel 11 (Eloquent)**:
  - Reusar convenciones del repo (`SoftDeletes`, casts, factories).
  - Queries: `whereDate`, `whereBetween`, `orderBy`, `paginate`.
- **Livewire 3**:
  - Preferir página Livewire por route (igual que módulos existentes).
  - Usar query string para filtros simples (type/windowDays) y mantener navegación shareable.
  - Si usas polling, preferir `wire:poll.visible` via `<x-ui.poll />`.
- **Bootstrap 5**:
  - Tabla densa `table-sm` + `table-hover` (patrón usado en `employee-show`).
  - Cards/metric tiles en dashboard alineadas al estilo actual.
- **No dependencias nuevas**:
  - No introducir librerías de charts, datatables, etc. Mantener MVP simple y performante.

### File Structure Requirements

Archivos a modificar (mínimos):
- `gatic/app/Livewire/Dashboard/DashboardMetrics.php` (agregar 2 métricas nuevas + lógica)
- `gatic/resources/views/livewire/dashboard/dashboard-metrics.blade.php` (2 cards nuevas + links)
- `gatic/routes/web.php` (nueva ruta `/alerts/loans`)
- `gatic/config/gatic.php` (defaults de ventana de alertas, si aplica)
- `gatic/tests/Feature/DashboardMetricsTest.php` (tests de nuevas métricas)

Archivos nuevos propuestos:
- `gatic/app/Livewire/Alerts/Loans/LoanAlertsIndex.php`
- `gatic/resources/views/livewire/alerts/loans/loan-alerts-index.blade.php`
- `gatic/tests/Feature/LoanAlertsTest.php` (o equivalente) para listado/filters/RBAC

Opcional (si se decide agregar entrada en menú):
- `gatic/resources/views/layouts/partials/sidebar-nav.blade.php` (sección “Operaciones”: “Alertas”)

### Testing Requirements

Objetivo: evitar regresiones y “contadores mentirosos”.

Recomendado (mínimo):

1) Dashboard (feature)
- Extender `gatic/tests/Feature/DashboardMetricsTest.php`:
  - Congelar tiempo con `Carbon::setTestNow(...)`.
  - Crear activos `Prestado` con `loan_due_date`:
    - 1 vencido (ayer)
    - 2 por vencer (hoy+3, hoy+7)
    - 1 fuera de ventana (hoy+60)
    - 1 sin vencimiento (null)
    - 1 no prestado (Disponible) con vencimiento (debe NO contar)
  - Assert en HTML usando `data-testid` nuevos (agregar en la vista) para:
    - `dashboard-metric-loans-overdue`
    - `dashboard-metric-loans-due-soon`

2) Listado de alertas (feature o Livewire)
- Nuevo test `LoanAlertsTest`:
  - Acceso:
    - No auth → redirect a login
    - Lector (si no se permite) → 403
    - Admin/Editor → 200
  - Filtros:
    - `type=overdue` retorna solo vencidos
    - `type=due-soon&windowDays=7` retorna solo por vencer en ventana
  - Render:
    - Muestra columnas mínimas y acciones (ver/devolver condicional).

3) Regression: Soft-delete (obligatorio por checklist)
- Crear un `Asset` vencido y luego `delete()` (soft delete). Debe NO aparecer en:
  - Conteos de dashboard
  - Listado de alertas

Notas:
- Mantener tests deterministas; evitar dependencias externas. [Source: `project-context.md`]

### Previous Story Intelligence (Epic 13 / Story 13.1)

Lo que NO debes olvidar al implementar alertas:

- Campo canónico: `assets.loan_due_date` (NO inventar otra fuente; no derivar de `asset_movements`).
- Estado canónico del préstamo vigente: `assets.status = Prestado` + `assets.current_employee_id`.
- Limpieza obligatoria al devolver:
  - `ReturnLoanedAsset` ya hace `loan_due_date = null`; las alertas deben reflejarlo automáticamente (si el activo ya no está prestado, desaparece).
- Validación ya establecida (reutilizable mentalmente):
  - Formato `Y-m-d`, no en pasado (ver `LoanAssetToEmployee` / `LoanAssetForm`).
- Edge case ya cubierto:
  - Empleado desaparece (soft-delete) → vistas deben degradar sin crashear. (Ver tests en `gatic/tests/Feature/Movements/AssetLoanTest.php`.)

### Git Intelligence (patrones recientes)

Últimos commits (referencia rápida):
- `dc20480` `feat(assets): add loan due date functionality` (base de Epic 13)
- `32a77b2` `feat(ui): implement command palette and unified search`
- `28528b4` `feat(auth): rediseñar login con slideshow CFE e identidad corporativa`
- `cff81d9` `chore: add performance analysis artifacts and report`
- `edaa91c` `docs(perf): agregar evidencia y resumen de P1`

Insight accionable:
- El repo sigue convención `type(scope): message` (ver `COMMIT_CONVENTIONS.md`).
- Para esta story, espera tocar principalmente dashboard + rutas + nuevo módulo Livewire de alertas; evita commits gigantes mezclando refactors no relacionados.

### Latest Tech Information (evitar decisiones desactualizadas)

- **Laravel**:
  - El proyecto está en Laravel 11 (`gatic/composer.json` requiere `laravel/framework: ^11.31`).
  - Laravel 12 es la versión major vigente; Laravel 11 entra en ventana de soporte corto (seguridad) y termina soporte de seguridad el **2026-03-12**. Planear upgrade a 12 como iniciativa separada (NO mezclar con esta story).
- **Livewire**:
  - El proyecto usa Livewire 3 (`livewire/livewire: ^3.0`).
  - Livewire 4 ya existe; no migrar aquí sin planificación (breaking changes posibles). Mantenerte en 3.x para esta implementación.
- **Regla práctica para esta story**:
  - No hacer upgrades “porque sí”. Implementar con las versiones fijadas por `composer.lock` y solo documentar si hay algún cambio necesario.

### References

- Mantener “código/DB/rutas en inglés; UI en español”. [Source: `docsBmad/project-context.md`]
- Polling visible (sin WebSockets) + frescura (“Actualizado hace Xs”). [Source: `gatic/docs/ui-patterns.md`]
- Long request UX (>3s): `<x-ui.long-request target="..."/>`. [Source: `gatic/resources/views/components/ui/long-request.blade.php`]

## Project Context Reference

- Reglas no negociables (polling, errores con `error_id`, roles, soft-delete): `docsBmad/project-context.md`
- Reglas lean + tooling local Windows: `project-context.md`
- Arquitectura + estructura por módulos: `_bmad-output/implementation-artifacts/architecture.md`
- Patrones UI reutilizables: `gatic/docs/ui-patterns.md` y `gatic/resources/views/components/ui/*`

## Story Completion Status

- Status: `done`
- Nota: "Code review aplicado; issues HIGH/MEDIUM resueltos; suite de tests pasando."

## Preguntas abiertas (guardar para PO/SM; no bloquean esta story)

1) Acceso:
- ¿El listado `/alerts/loans` debe ser solo `inventory.manage` (Admin/Editor) o también `inventory.view` (Lector) sin acción “Devolver”?

2) Ventana “por vencer”:
- ¿Default de la ventana? (propuesta: 7 días)  
- ¿Opciones permitidas exactas? (propuesta: 7/14/30 como en AC)

3) Préstamos sin vencimiento:
- ¿Deseamos mostrar un contador “Sin vencimiento” (para higiene operativa) en una story futura?

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `_bmad/core/tasks/workflow.xml`
- `_bmad/bmm/workflows/4-implementation/dev-story/workflow.yaml`
- `_bmad/bmm/workflows/4-implementation/dev-story/instructions.xml`
- `_bmad/bmm/workflows/4-implementation/create-story/workflow.yaml`
- `_bmad/bmm/workflows/4-implementation/create-story/instructions.xml`
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (auto-discovery: primer backlog `13-2-alertas-prestamos-vencidos-y-por-vencer`)
- `_bmad-output/implementation-artifacts/epics.md` (Epic 13 / Story 13.2)
- `_bmad-output/implementation-artifacts/13-1-fecha-vencimiento-en-prestamos-de-activos.md`
- `_bmad-output/implementation-artifacts/5-6-dashboard-minimo-de-metricas-operativas-polling.md`
- `_bmad-output/implementation-artifacts/architecture.md` + `docsBmad/project-context.md` + `project-context.md`
- `gatic/composer.json` + `gatic/config/gatic.php`
- `gatic/routes/web.php`
- `gatic/app/Livewire/Dashboard/DashboardMetrics.php` + `gatic/resources/views/livewire/dashboard/dashboard-metrics.blade.php`
- `gatic/app/Livewire/Alerts/Loans/LoanAlertsIndex.php` + `gatic/resources/views/livewire/alerts/loans/loan-alerts-index.blade.php`
- `gatic/tests/Feature/DashboardMetricsTest.php`
- `gatic/tests/Feature/LoanAlertsIndexTest.php`
- `gatic/app/Models/Asset.php` + `gatic/app/Models/Employee.php`
- `gatic/docs/ui-patterns.md` + `gatic/resources/views/components/ui/*`

### Implementation Plan

- Dashboard: agregar métricas `loansOverdueCount` y `loansDueSoonCount` (ventana por config) y tarjetas con deep links al listado.
- Alertas: crear `/alerts/loans` con filtros por query string (`type`, `windowDays`) y tabla densa con acciones rápidas.
- Config: exponer defaults/opciones de ventana en `config('gatic.alerts.loans.*')`.
- Tests: extender `DashboardMetricsTest` y agregar `LoanAlertsIndexTest`.

### Completion Notes List

- Story seleccionada automáticamente desde `sprint-status.yaml` (primer `backlog`).
- Documento escrito para minimizar regresiones: reusa `loan_due_date` canónico (Story 13.1) y patrón dashboard/polling (Story 5.6).
- Incluye guardrails explícitos de RBAC, performance, UX long-request y estrategia de tests (incluye soft-delete regression).
- ✅ Dashboard extendido con contadores “Vencidos” y “Por vencer” + deep links al listado filtrado.
- ✅ Listado `/alerts/loans` (Livewire) con filtros `type=overdue|due-soon` + `windowDays=7|14|30`, query eficiente y paginación.
- ✅ Configuración mínima agregada en `gatic/config/gatic.php` para ventana default/opciones.
- ✅ Tests ejecutados en Sail (PHP 8.4 + MySQL): `php artisan test` (suite completa) + filtros para los tests nuevos.

### File List

- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MODIFIED)
- `_bmad-output/implementation-artifacts/13-2-alertas-prestamos-vencidos-y-por-vencer.md` (NEW)
- `gatic/.gitignore` (MODIFIED)
- `gatic/app/Actions/Movements/Assets/ReturnLoanedAsset.php` (MODIFIED)
- `gatic/app/Livewire/Dashboard/DashboardMetrics.php` (MODIFIED)
- `gatic/app/Livewire/Movements/Assets/LoanAssetForm.php` (MODIFIED)
- `gatic/app/Livewire/Movements/Assets/ReturnAssetForm.php` (MODIFIED)
- `gatic/app/Models/Asset.php` (MODIFIED)
- `gatic/resources/views/livewire/dashboard/dashboard-metrics.blade.php` (MODIFIED)
- `gatic/resources/views/livewire/employees/employee-show.blade.php` (MODIFIED)
- `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php` (MODIFIED)
- `gatic/app/Livewire/Alerts/Loans/LoanAlertsIndex.php` (NEW)
- `gatic/resources/views/livewire/alerts/loans/loan-alerts-index.blade.php` (NEW)
- `gatic/resources/views/livewire/movements/assets/loan-asset-form.blade.php` (MODIFIED)
- `gatic/resources/views/livewire/movements/assets/return-asset-form.blade.php` (MODIFIED)
- `gatic/routes/web.php` (MODIFIED)
- `gatic/config/gatic.php` (MODIFIED)
- `gatic/tests/Feature/DashboardMetricsTest.php` (MODIFIED)
- `gatic/tests/Feature/Movements/AssetLoanTest.php` (MODIFIED)
- `gatic/tests/Feature/Movements/AssetReturnTest.php` (MODIFIED)
- `gatic/tests/Feature/Seeders/SeederSanityTest.php` (MODIFIED)
- `gatic/tests/Feature/LoanAlertsIndexTest.php` (NEW)

## Senior Developer Review (AI)

Fecha: **2026-02-02**  
Resultado: **Aprobado (fixes aplicados)**  

Resumen de fixes aplicados:
- Sincronizada la story con la realidad de git (File List completa).
- `LoanAlertsIndex`: removido estado privado no hidratado por Livewire; defaults/opciones siempre desde `config()`; query de vencidos optimizada.
- Dashboard: query de vencidos optimizada (usa comparación directa sobre `loan_due_date`).
- UX: acción “Devolver” desde alertas conserva contexto (`returnTo`) y “Cancelar” regresa al listado.
- Copy: acentos corregidos en “Préstamo/Devolución/Ubicación/Ocurrió”.
- PHPUnit: migrado `@group seeder` a atributo `#[Group('seeder')]` (sin warning).

Validación:
- Sail (PHP 8.4 + MySQL): `php artisan test` (suite completa) ✅

### Change Log

- **2026-02-02**: Implementadas alertas operativas de préstamos: métricas en dashboard (vencidos/por vencer) con deep links; nuevo listado `/alerts/loans` con filtros `type`/`windowDays`, query sin N+1 y paginación; configuración mínima para ventana (default + opciones); tests feature agregados/extendidos y suite completa pasando. Status actualizado a `review`.
- **2026-02-02**: Code review: corregidas discrepancias story↔git (File List), fix Livewire (state privado), optimización de queries de vencidos, retorno a alertas tras “Devolver”, copy (acentos) y cleanup de warning PHPUnit. Status actualizado a `done`.
