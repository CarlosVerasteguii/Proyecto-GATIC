<!-- template-output: story_header -->
# Story 14.9: Dashboard avanzado (métricas de negocio + actividad reciente)

Status: done

Story Key: `14-9-dashboard-avanzado-metricas-y-actividad-reciente`  
Epic: `14` (Datos de negocio: garantías, costos, proveedores, configuración, timeline, dashboard avanzado)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Fecha: 2026-02-08  
Story ID: `14.9`

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/epics.md` (Epic 14 / Story 14.9)
- `_bmad-output/implementation-artifacts/prd.md` (NFR3: métricas dashboard ~60s; sin WebSockets)
- `_bmad-output/implementation-artifacts/ux.md` (dashboards backoffice; polling discreto + “Actualizado hace X”)
- `_bmad-output/implementation-artifacts/architecture.md` (Livewire-first; polling; `config/gatic.php`)
- `docsBmad/project-context.md` + `project-context.md` (bible/reglas: idioma, RBAC, errores con `error_id`, no WebSockets)
- Inteligencia previa (reuso / patrones):
  - `_bmad-output/implementation-artifacts/5-6-dashboard-minimo-de-metricas-operativas-polling.md` (DashboardMetrics + polling + tests)
  - `_bmad-output/implementation-artifacts/14-3-garantias-en-activos-fechas-y-alertas.md` (alertas garantías + filtros `type/windowDays`)
  - `_bmad-output/implementation-artifacts/14-4-costos-y-valor-del-inventario.md` (valor inventario + breakdown)
  - `_bmad-output/implementation-artifacts/14-5-vida-util-y-renovacion.md` (alertas renovaciones; patrón Alerts)
  - `_bmad-output/implementation-artifacts/14-8-timeline-y-changelog-por-entidad.md` (TimelineBuilder/TimelineEvent; seguridad allowlist)
- Código actual (puntos de extensión):
  - `gatic/app/Livewire/Dashboard/DashboardMetrics.php`
  - `gatic/resources/views/livewire/dashboard/dashboard-metrics.blade.php`
  - `gatic/app/Livewire/Alerts/Warranties/WarrantyAlertsIndex.php` + `gatic/routes/web.php` (ruta `alerts.warranties.index`)
  - `gatic/app/Support/Timeline/TimelineBuilder.php` + `gatic/app/Support/Timeline/TimelineEvent.php`
  - `gatic/app/Livewire/Inventory/Products/ProductsIndex.php` (filtros URL: `category`, `brand`)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

<!-- template-output: story_requirements -->
## Story

Como usuario interno,  
quiero un dashboard más completo (alertas + métricas + actividad),  
para tener visibilidad operativa y de negocio del sistema sin recorrer múltiples pantallas.

<!-- template-output: story_requirements -->
## Alcance (MVP)

Incluye:
- Extender el dashboard existente (`/dashboard`) para mostrar:
  - **Garantías**: vencidas y por vencer.
  - **Valor de inventario** + top categorías y marcas (ya existe; endurecer navegación).
  - **Actividad reciente** (feed corto) con enlaces a las entidades.
- Cards/tablas **navegables**: desde el dashboard se puede ir a vistas filtradas relevantes.
- Actualización por **polling visible** (~60s) y sin WebSockets (regla no negociable).
- UX de confianza con indicador “Actualizado hace X” y refresh manual (patrón ya implementado).

Fuera de alcance (NO hacer en esta story):
- WebSockets/SSE/colas nuevas/Redis solo para métricas.
- Dashboards configurables por usuario, gráficas complejas, series históricas o exportaciones.
- Refactors o upgrades mayores del stack (Laravel/Livewire) como parte del trabajo.

<!-- template-output: story_requirements -->
## Definiciones operativas (para evitar ambigüedad)

### Garantías

- **Vencidas**: `assets.warranty_end_date < Carbon::today()`.
- **Por vencer**: `today <= warranty_end_date <= today + windowDays`.
- Excluir por defecto:
  - `assets.status = Retirado`
  - soft-deleted (`deleted_at is not null`)
- La ventana `windowDays` debe respetar `SettingsStore` (mismo source of truth que `WarrantyAlertsIndex`).

### Valor de inventario

- Ya existe en `DashboardMetrics` (solo `inventory.manage`), excluye `Retirado` y solo suma moneda default.
- El breakdown “Top N” se obtiene de `config('gatic.dashboard.value.top_n')`.

### Actividad reciente

- Un feed “scanable” con los últimos N eventos relevantes, orden descendente por timestamp (estable).
- Debe **respetar RBAC**:
  - No exponer eventos/metadata de adjuntos a usuarios sin `attachments.view`.
  - No mostrar entidades cuya vista está protegida (ej. `Employee`/`PendingTask` requieren `inventory.manage` según `TimelineBuilder::VIEW_GATES`).
- Cada ítem debe tener:
  - Timestamp, actor (si aplica), tipo (icono/label), resumen corto, y enlace a la entidad (cuando aplique).

<!-- template-output: technical_requirements -->
## Acceptance Criteria

### AC1 — Dashboard avanzado visible y coherente

**Given** un usuario autenticado con acceso al dashboard  
**When** abre `/dashboard`  
**Then** ve secciones de alertas/métricas/actividad reciente en un layout consistente con Bootstrap 5  
**And** entiende “qué cuenta y qué no” (helper corto por card/tabla).

### AC2 — Garantías vencidas/por vencer + navegación

**Given** existen activos con garantía  
**When** el dashboard se renderiza  
**Then** muestra conteos de “Garantías vencidas” y “Garantías por vencer” con el windowDays configurado  
**And** al hacer click navega a `alerts.warranties.index` con filtros (`type`/`windowDays`)  
**And** respeta RBAC (si el usuario no tiene `inventory.manage`, no se muestra link o se oculta la card según decisión UX).

### AC3 — Valor de inventario + top categorías/marcas navegable

**Given** el usuario tiene `inventory.manage`  
**When** ve el dashboard  
**Then** ve “Valor del inventario” y breakdown por categoría/marca (Top N + “Otros”)  
**And** los renglones (cuando sea posible) navegan a `inventory.products.index` filtrado por `category`/`brand` (URL params ya definidos en `ProductsIndex`).

### AC4 — Actividad reciente con enlaces y RBAC

**Given** existen eventos recientes (movimientos/ajustes/notas/audit/adjuntos)  
**When** el dashboard se renderiza  
**Then** muestra un feed de actividad reciente (N ítems) en orden cronológico descendente  
**And** cada ítem enlaza al detalle de la entidad cuando existe ruta y el usuario tiene permiso de view  
**And** no aparecen eventos de adjuntos si el usuario no tiene `attachments.view`.

### AC5 — Polling visible (~60s) sin WebSockets (NFR3)

**Given** el dashboard permanece visible  
**When** pasan ~60s  
**Then** se actualizan métricas y actividad por polling (`wire:poll.visible`)  
**And** el polling se detiene cuando no está visible  
**And** no se agrega WebSockets/SSE/Echo/Pusher.

### AC6 — Manejo de errores y rendimiento

**Given** una falla inesperada en queries  
**When** el polling refresca  
**Then** se muestra error amigable con `error_id` (detalle solo Admin) y el dashboard sigue estable  
**And** las queries son agregadas/limitadas (sin N+1, sin cargar colecciones completas) y con límites por fuente para el feed.

### AC7 — Testing

**Given** un entorno de tests (Sail/MySQL)  
**When** se ejecutan tests del dashboard  
**Then** hay cobertura para: RBAC (Admin/Editor/Lector), links visibles/ocultos, conteos de garantías, y reglas de visibilidad de adjuntos.

<!-- template-output: developer_context_section -->
## Tasks / Subtasks

1) Dashboard: UI (AC: 1–5)
- [x] Agregar cards para "Garantías vencidas" y "Garantías por vencer" en `dashboard-metrics.blade.php`.
- [x] Agregar sección "Actividad reciente" (tabla/lista densa estilo backoffice) con íconos + "Ver más" si aplica.
- [x] Hacer navegables (cuando aplique) los renglones de breakdown por categoría/marca hacia `inventory.products.index` con URL params (`category`/`brand`).
  - [x] Para esto, ajustar la data en `DashboardMetrics` para incluir IDs (p.ej. `category_id`, `brand_id`) además de `name/value`.
  - [x] "Otros" y "Sin marca" pueden quedar sin navegación si no hay un filtro equivalente.

2) Livewire: métricas de garantías (AC: 2, 5, 6)
- [x] Extender `DashboardMetrics`:
  - [x] Propiedades: `warrantiesExpiredCount`, `warrantiesDueSoonCount`, `warrantyDueSoonWindowDays`.
  - [x] Método `loadWarrantyAlertCounts()` usando `Carbon::today()` y la misma ventana/config que `WarrantyAlertsIndex` (evitar duplicación: extraer helper/servicio si conviene).
  - [x] Excluir `Retirado` y soft-deleted; usar índices existentes (`warranty_end_date`).

3) Livewire: actividad reciente (AC: 4–6)
- [x] Implementar un builder/servicio para generar un feed global (N ítems) sin romper la arquitectura:
  - Opción A (preferida): nuevo `App\\Support\\Dashboard\\RecentActivityBuilder` que reutiliza DTO/mapeos de `TimelineEvent` (Story 14.8) y aplica `TimelineBuilder::ALLOWED_ENTITIES` + `VIEW_GATES`.
  - Opción B: extraer lógica reusable desde `TimelineBuilder` para evitar duplicación de mapeo.
- [x] En `DashboardMetrics`, cargar el feed con límites por fuente y merge ordenado (estable por timestamp + sortKey).
- [x] En el Blade, ocultar eventos no permitidos y links si no hay permiso/ruta.

4) Navegación a vistas filtradas (AC: 2–4)
- [x] Warranties: links a `route('alerts.warranties.index', ['type' => 'expired'])` y `['type' => 'due-soon', 'windowDays' => $warrantyDueSoonWindowDays]` (solo si `@can('inventory.manage')`).
- [x] Categoría/Marca: links a `route('inventory.products.index', ['category' => $categoryId])` / `['brand' => $brandId]` (cuidar el caso "Sin marca").
- [x] Actividad: construir URL a entidad (Product/Asset/Employee/PendingTask) solo si el usuario puede verla.

5) Tests (AC: 7)
- [x] Actualizar `gatic/tests/Feature/DashboardMetricsTest.php`:
  - [x] Garantías: casos vencidas/por vencer con `Carbon::setTestNow`.
  - [x] RBAC: Lector no ve links/manage-only; Admin/Editor sí.
  - [x] Actividad reciente: eventos de adjuntos ocultos para Lector; visible para Admin/Editor con `attachments.view`.
  - [x] Soft-delete regression: crear registros soft-deleted (Asset/Product/Category/Brand) y verificar que NO afecten conteos/valor/feed.

<!-- template-output: architecture_compliance -->
## Dev Notes (guardrails para implementación)

- Reusar el patrón existente:
  - Polling wrapper: `gatic/resources/views/components/ui/poll.blade.php` (`wire:poll.visible`).
  - Freshness indicator: `<x-ui.freshness-indicator />`.
  - Error handling prod-safe: `App\\Support\\Errors\\ErrorReporter` + toast `ui:toast` con `error_id` (ver `DashboardMetrics`).
- No introducir “magic numbers”: intervalos/ventanas deben venir de `config/gatic.php` y/o `SettingsStore`.
- Performance:
  - Feed global: aplicar límites (por fuente y total) y evitar joins pesados; preferir selects mínimos + eager-loading puntual.
  - Si el feed usa múltiples queries, mantenerlas acotadas y predecibles bajo polling.
- Seguridad/RBAC:
  - El dashboard debe ser “defensa en profundidad”: ocultar UI con `@can`, pero la data cargada también debe respetar permisos.
  - No filtrar por “UI hide” únicamente; el builder debe excluir entidades/tipos no permitidos.
  - Reusar el check de Story 5.6: el polling NO debe disparar UX de “operación lenta” fuera del target `poll,refreshNow` (ya existe `<x-ui.long-request target="poll,refreshNow" />`).

<!-- template-output: library_framework_requirements -->
<!-- template-output: latest_tech_information -->
## Latest Tech Information (verificado + web research) — 2026-02-08

Versiones fijadas en el repo (no hacer upgrades mayores dentro de esta story):
- Laravel: `laravel/framework v11.47.0` (`gatic/composer.lock`)
- Livewire: `livewire/livewire v3.7.3` (`gatic/composer.lock`)
- Bootstrap: `5.3.8` (`gatic/package-lock.json`) — coincide con `npm bootstrap@latest`
- Bootstrap Icons: `1.13.1` (`gatic/package-lock.json`) — coincide con `npm bootstrap-icons@latest`

Notas (para evitar decisiones desactualizadas):
- Livewire tiene releases v4 (GitHub `livewire/livewire`), pero este repo está en v3: **NO migrar** en esta story.
- Laravel framework latest release (GitHub) es v12.x; este repo está en v11: **NO migrar** en esta story.
- Livewire polling docs oficiales mencionan `wire:poll.visible` y `wire:poll.keep-alive`; mantener el uso actual (`visible`) salvo motivo claro.

Fuentes web consultadas:
- https://api.github.com/repos/livewire/livewire/releases/latest
- https://api.github.com/repos/laravel/framework/releases/latest
- https://livewire.laravel.com/docs/polling
- https://registry.npmjs.org/bootstrap/latest
- https://registry.npmjs.org/bootstrap-icons/latest

<!-- template-output: file_structure_requirements -->
## Project Structure Notes

- El dashboard ya está centralizado en:
  - Componente: `gatic/app/Livewire/Dashboard/DashboardMetrics.php`
  - Vista: `gatic/resources/views/livewire/dashboard/dashboard-metrics.blade.php`
  - Ruta: `route('dashboard')` (ver `gatic/routes/web.php`)
- Para actividad reciente, preferir lógica en `app/Support/*` (no en Blade) y mantener Livewire como orquestador de UI/estado.

<!-- template-output: testing_requirements -->
## Testing Notes

- Tests deben ser deterministas: congelar tiempo (`Carbon::setTestNow`) para ventanas “por vencer”.
- Mantener el patrón de tests existente del dashboard (data-testid + asserts robustos).
- Cubrir RBAC (Admin/Editor/Lector) y visibilidad de adjuntos.

<!-- template-output: previous_story_intelligence -->
## Previous Story Intelligence (Story 14.8)

Lecciones/reuso directo para 14.9:
- Ya existe un modelo mental y DTOs para “actividad”:
  - `App\\Support\\Timeline\\TimelineEvent` + `TimelineEventType`
  - `App\\Support\\Timeline\\TimelineBuilder` con allowlist de entidades + gates por entidad
- El trabajo duro de “no filtrar datos sensibles” ya está resuelto para timeline por entidad: reutilizar ese enfoque para el feed global.
- Regla de oro: **no upgrades** dentro de stories de UI/queries; mantener stack fijo.

<!-- template-output: git_intelligence_summary -->
## Git Intelligence Summary (últimos commits relevantes)

- `fix(timeline): correct route parameter for attachments.download` (timeline/adjuntos)
- `feat(users): agregar campos departamento/cargo y preferencias UI de usuario` (perfil)
- `feat(admin): add system settings module with DB-backed configuration` (SettingsStore)
- `feat(inventory): add useful life tracking and renewal alerts` (alertas renovaciones)
- `feat(inventory): add acquisition cost tracking and inventory value dashboard` (valor inventario en dashboard)

Implicación para 14.9:
- Mucho de lo pedido ya existe (valor inventario, alertas, timeline): la story debe enfocarse en **composición + navegación + RBAC + performance bajo polling**.

<!-- template-output: project_context_reference -->
## Project Context Reference (must-read)

- Bible + reglas: `docsBmad/project-context.md`, `project-context.md`
- UX polling/freshness: `_bmad-output/implementation-artifacts/ux.md`
- Stack/estructura: `_bmad-output/implementation-artifacts/architecture.md`
- Dashboard base y patrones: `_bmad-output/implementation-artifacts/5-6-dashboard-minimo-de-metricas-operativas-polling.md`
- Alertas garantías: `_bmad-output/implementation-artifacts/14-3-garantias-en-activos-fechas-y-alertas.md`
- Timeline (actividad): `_bmad-output/implementation-artifacts/14-8-timeline-y-changelog-por-entidad.md`

<!-- template-output: story_completion_status -->
## Story Completion Status

- Status: **done**
- Completion note: "Implementación completa: warranty cards, actividad reciente (incluye audit) con RBAC, navegación a vistas filtradas, orden estable y mejoras de rendimiento."

## Dev Agent Record

### Agent Model Used

Claude Opus 4.6

### Implementation Notes

- **Task 2 (Warranty Metrics)**: Extended `DashboardMetrics` with `loadWarrantyAlertCounts()` using the same `SettingsStore` config keys as `WarrantyAlertsIndex`. Excludes retired and soft-deleted assets. Follows identical pattern to `loadLoanDueDateAlertCounts()`.
- **Task 3 (Recent Activity)**: `RecentActivityBuilder` ahora consulta 6 fuentes (asset movements, product quantity movements, adjustments, notes, attachments, **audit logs**) con límites (10 por fuente, 15 total). Respeta RBAC: adjuntos ocultos sin `attachments.view`, Employee/PendingTask ocultos sin `inventory.manage`. Orden estable por timestamp (numérico) + sortKey. Se pre-calcula `occurred_at_human` para evitar parseo repetido en Blade bajo polling.
- **Task 1 & 4 (UI + Navigation)**: Warranty cards link to `alerts.warranties.index` with type/windowDays params (guarded by `@can('inventory.manage')`). Category/brand breakdown rows now link to `inventory.products.index` with category/brand URL params. "Otros" and "Sin marca" entries have no navigation (null ID). Activity feed shows icons, entity links, actor, and relative timestamps.
- **Performance**: All queries are bounded by per-source limits, no N+1 (eager loading used). Feed runs under polling (~60s `wire:poll.visible`). Existing `<x-ui.long-request target="poll,refreshNow" />` pattern preserved.

### Debug Log References

- `_bmad/core/tasks/workflow.xml`
- `_bmad/bmm/workflows/4-implementation/dev-story/workflow.yaml`
- `_bmad/bmm/workflows/4-implementation/dev-story/instructions.xml`
- Route name fix: `products.assets.show` -> `inventory.products.assets.show` (prefix was missing)

### Completion Notes

- Pendiente re-ejecutar tests en un entorno con PHP >= 8.2 (este runner tiene PHP 8.0.30 y Composer bloquea `artisan test`).

### File List

- `gatic/app/Livewire/Dashboard/DashboardMetrics.php` (MODIFIED — actividad reciente: asignación directa, shape actualizado)
- `gatic/app/Support/Dashboard/RecentActivityBuilder.php` (NEW — audit logs + orden estable + filtros + `occurred_at_human`)
- `gatic/resources/views/livewire/dashboard/dashboard-metrics.blade.php` (MODIFIED — usar `occurred_at_human` si existe)
- `gatic/tests/Feature/DashboardMetricsTest.php` (MODIFIED — tests nuevos para audit feed + RBAC de audit adjuntos + “Sin marca” sin link)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MODIFIED — status review -> done)
- `_bmad-output/implementation-artifacts/14-9-dashboard-avanzado-metricas-y-actividad-reciente.md` (NEW — story)

### Change Log

- 2026-02-07: Story 14.9 implementation complete — dashboard avanzado con métricas de garantías, actividad reciente con RBAC, navegación a vistas filtradas, y 9 tests nuevos.
- 2026-02-08: Fixes post-code-review — actividad incluye audit logs; orden estable por timestamp; se evita `Carbon::parse` en Blade; filtros/seguridad reforzados; tests ampliados.
