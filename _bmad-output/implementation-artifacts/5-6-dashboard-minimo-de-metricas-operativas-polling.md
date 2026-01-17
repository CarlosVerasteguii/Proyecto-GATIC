# Story 5.6: Dashboard mínimo de métricas operativas (polling)

Status: done

Story Key: `5-6-dashboard-minimo-de-metricas-operativas-polling`  
Epic: `5` (Gate 3: Operación diaria de movimientos)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/epics.md` (Epic 5 / Story 5.6)
- `_bmad-output/implementation-artifacts/prd.md` (NFR3: métricas dashboard ~60s; polling visible sin WebSockets)
- `_bmad-output/implementation-artifacts/ux.md` (UX: polling visible, indicador "Actualizado hace Xs", evitar distracciones/stale)
- `_bmad-output/implementation-artifacts/architecture.md` (stack/patrones; `config/gatic.php` para polling/defaults)
- `docsBmad/project-context.md` + `project-context.md` (bible/stack; RBAC server-side; rutas en inglés/UI en español)
- `_bmad-output/implementation-artifacts/1-11-patron-de-polling-base-wire-poll-visible-reutilizable.md` (patrón `<x-ui.poll />` + intervalos)
- `_bmad-output/implementation-artifacts/1-9-componentes-ux-reutilizables-toasts-loaders-cancelar-actualizado-hace-xs.md` (freshness indicator + long-request)
- `gatic/app/Models/Asset.php`, `gatic/app/Models/AssetMovement.php`, `gatic/app/Models/ProductQuantityMovement.php`, `gatic/app/Models/Product.php`
- `gatic/resources/views/dashboard.blade.php`, `gatic/routes/web.php`

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->
## Story

As a usuario interno (Admin/Editor/Lector),
I want ver métricas operativas básicas (préstamos, pendientes de retiro, etc.) actualizadas,
so that priorice acciones del día sin recorrer todo el sistema (NFR3).

## Alcance (MVP)

Incluye:
- Un dashboard mínimo en `/dashboard` con tarjetas/indicadores de métricas operativas.
- Actualización por polling visible (~60s) y sin WebSockets.
- Indicador "Actualizado hace Xs" + refresh manual (sin spamear toasts).
- Acceso a usuarios autenticados y activos (roles internos).

No incluye (fuera de scope):
- Gráficas/tendencias, filtros avanzados o dashboards configurables.
- Infra nueva (Redis) o trabajos async para métricas.
- Notificaciones/alertas automáticas.

## Métricas mínimas (definición operativa)

Estas métricas deben ser consistentes con la semántica de estados definida en `docsBmad/project-context.md` y `App\\Models\\Asset`.

Métricas mínimas a mostrar (MVP):
- Activos Prestados: `assets.status = Prestado`
- Activos Pendientes de Retiro: `assets.status = Pendiente de Retiro`
- Activos Asignados: `assets.status = Asignado`
- Activos No disponibles: `assets.status IN (Asignado, Prestado, Pendiente de Retiro)`
- Movimientos hoy: conteo agregado de `asset_movements` + `product_quantity_movements` creados "hoy"

Notas:
- Si se decide mostrar "Retirados", debe ser explícito que no cuentan como disponibles por defecto.
- Evitar ambigüedad: cada tarjeta debe tener un helper corto ("qué cuenta y qué no").

## Acceptance Criteria

### AC1 - Métricas mínimas visibles (Story 5.6)

**Given** el dashboard habilitado  
**When** el usuario lo abre  
**Then** ve métricas mínimas definidas por el producto  
**And** las métricas son entendibles (título + número + helper) y no requieren navegar para comprender qué significan.

### AC2 - Polling visible ~60s (NFR3, sin WebSockets)

**Given** el dashboard abierto  
**When** el dashboard permanece visible  
**Then** las métricas se actualizan automáticamente por polling aproximadamente cada 60s  
**And** el polling se detiene cuando no está visible (`wire:poll.visible`)  
**And** el intervalo proviene de `config('gatic.ui.polling.metrics_interval_s')` (sin hardcode)  
**And** no se agrega WebSockets/SSE/Echo/Pusher (regla no negociable).

### AC3 - Indicador de frescura + UX estable

**Given** el dashboard con polling  
**When** llega data nueva por polling  
**Then** se actualiza un indicador visible de "Actualizado hace Xs"  
**And** el dashboard no dispara el overlay de "operación lenta" por el polling (si existe `<x-ui.long-request />`, debe excluir el método del poll con `target="..."`).

### AC4 - Performance y degradación elegante

**Given** un dataset moderado y polling recurrente  
**When** el dashboard se recalcula  
**Then** usa queries agregadas eficientes (sin N+1, sin cargar colecciones completas)  
**And** si falla una consulta inesperadamente, se aplica patrón de error en producción (mensaje amigable + ID de error; detalle solo Admin).

## Tasks / Subtasks

1) UI: dashboard mínimo (AC: 1, 2, 3)
- [x] Convertir `/dashboard` a vista contenedor que renderiza un componente Livewire de métricas (Bootstrap 5).
- [x] Renderizar tarjetas (número grande + label + helper) y un "Actualizar ahora".
- [x] Renderizar `<x-ui.freshness-indicator :updated-at="$lastUpdatedAtIso" />`.

2) Livewire: polling visible y estado (AC: 2, 3)
- [x] Crear componente Livewire `App\\Livewire\\Dashboard\\DashboardMetrics`.
- [x] Implementar métodos:
  - `poll()` (invocado por `wire:poll.visible`)
  - `refreshNow()` (refresh manual)
- [x] En la vista, envolver con `<x-ui.poll method="poll" :interval-s="config('gatic.ui.polling.metrics_interval_s')" />`.
- [x] Actualizar `lastUpdatedAtIso` cuando se recalculen métricas.

3) Backend: queries eficientes (AC: 1, 4)
- [x] Conteo por estatus de activos con un `groupBy('status')` o `selectRaw` (sin traer modelos).
- [x] Conteo de movimientos "hoy" con rangos de fecha (`startOfDay`/`endOfDay`) consistentes con zona horaria de la app.
- [x] Mantener consistencia con `SoftDeletes` (no incluir eliminados).

4) Tests mínimos (AC: 2, 3)
- [x] Feature test: `/dashboard` contiene `wire:poll.visible.{interval}s="poll"` cuando polling está enabled.
- [x] Feature test: existe el indicador de frescura (`data-gatic-freshness`).
- [x] (Opcional) Test de conteos: seed mínimo y assert de valores mostrados.
## Dev Notes

### Contexto del producto (por qué existe)

- El objetivo del dashboard es ayudar a la operación diaria a priorizar acciones sin navegar múltiples pantallas.
- La app es intranet/on-prem: simplicidad > sofisticación.
- Near-real-time es por polling (no WebSockets).

### UX / Interacción (reglas importantes)

- Polling sin distracciones: no spamear toasts por updates automáticos.
- Siempre mostrar "cuándo se actualizó" (confianza con polling).
- Si agregas un refresh manual, evita doble-refresh (deshabilitar el botón mientras corre el request manual).
- Si hay cargas manuales que pueden tardar >3s, usar patrón NFR2: skeleton/loader + progreso + cancelar.

### Seguridad (RBAC)

- Dashboard requiere `auth` + `active` (ya está en rutas).
- No exponer datos sensibles; métricas son agregadas.
- Si se agregan links desde tarjetas, proteger rutas destino con `can:` middleware y `Gate::authorize(...)` (defensa en profundidad).

### Reutilización obligatoria (no reinventar)

- Polling: usar `<x-ui.poll />` (no construir atributos `wire:poll*` a mano en vistas).
- Frescura: usar `<x-ui.freshness-indicator />`.
- Operación lenta: usar `<x-ui.long-request target="..." />` y asegurar que NO aplica al método `poll()`.
- Errores: seguir el patrón de "ID de error" en producción.
## Dev Agent Guardrails (técnicos)

- Prohibido: WebSockets/SSE/Echo/Pusher.
- Prohibido: números mágicos en UI (intervalos/thresholds vienen de `config('gatic.ui.*')`).
- Prohibido: queries que cargan colecciones completas en cada poll.
- Requerido: `wire:poll.visible` + intervalo ~60s para métricas (configurable).
- Requerido: compatibilidad con kill-switch global `config('gatic.ui.polling.enabled')` (env `GATIC_UI_POLLING_ENABLED=false`).
- Requerido: UI en español; rutas y nombres de código en inglés.
## Architecture Compliance

- Stack objetivo: Laravel 11 + Livewire 3 + Blade + Bootstrap 5 + MySQL 8.
- Sin Redis en MVP: si una query es costosa, optimizar con índices/queries antes de introducir caching.
- Preferir Livewire-first: dashboard como componente Livewire (estado + polling + rendering).
- Asegurar que las métricas son deterministas y consistentes con la semántica de estados.
## Library / Framework Requirements

- Livewire 3: usar polling con `wire:poll.visible` a través del wrapper `<x-ui.poll />`.
- Eloquent: usar agregaciones (`count`, `groupBy`) y columnas indexables.
- Carbon/Date: usar helpers de Laravel para rangos "hoy" (evitar bugs por timezone).
- Bootstrap 5: tarjetas (`card`), layout responsive desktop-first.

Referencia externa (si necesitas refrescar sintaxis):
- Livewire polling: https://livewire.laravel.com/docs/3.x/wire-poll
## File Structure Requirements (propuesta concreta)

Implementación mínima sugerida:
- Livewire class: `gatic/app/Livewire/Dashboard/DashboardMetrics.php`
- Livewire view: `gatic/resources/views/livewire/dashboard/dashboard-metrics.blade.php`
- Dashboard entry: actualizar `gatic/resources/views/dashboard.blade.php` para renderizar el componente.

Rutas:
- Mantener `/dashboard` con middleware `auth` + `active`.

Componentes a reutilizar:
- `gatic/resources/views/components/ui/poll.blade.php`
- `gatic/resources/views/components/ui/freshness-indicator.blade.php`
- (si aplica) `gatic/resources/views/components/ui/long-request.blade.php`
## Testing Requirements

- Añadir tests en `gatic/tests/Feature` para verificar markup y accesos.

Tests mínimos recomendados:
- Asegurar que `/dashboard` contiene `wire:poll.visible.{interval}s="poll"` cuando `config('gatic.ui.polling.enabled')` es true.
- Asegurar que `/dashboard` contiene el marker de freshness `data-gatic-freshness`.

Notas:
- Usar `User::factory()` con `is_active=true`.
- Seguir patrones de tests existentes (ej. `gatic/tests/Feature/LayoutNavigationTest.php`).
## Previous Story Intelligence (no repetir errores)

- Story 1.11 (polling): usar `<x-ui.poll />` y los intervalos desde `gatic/config/gatic.php`.
- Story 1.9 (UX): freshness indicator + long-request overlay con `target` para excluir polling.
- Epic 5 (5.2–5.5): estados y movimientos ya existen; las métricas deben reflejar esos estados.

Anti-patterns a evitar:
- Hardcodear intervalos en la vista.
- Recalcular métricas con `->get()` y conteos en PHP.
- Mostrar números sin definir qué cuentan.
## Git Intelligence Summary

- Epic 5 tiene implementaciones recientes (movimientos serializados y por cantidad, kardex): los modelos relevantes ya existen.
- `/dashboard` actualmente es una vista mínima: esta story la convierte en dashboard operativo real.

Commits recientes relevantes:
- `feat(inventory): implementar kardex para productos por cantidad (Story 5.5)`
- `feat(movements): implement Story 5.4 (Quantity movements for non-serialized products)`
- `feat(movements): implement Story 5.3 - Loan and return asset`
- `feat(movements): implement Story 5.2 - Asset assignment to Employee`
## Latest Tech Information

- No upgrades: usar las versiones ya fijadas por el repo (`composer.lock`) y el stack definido en `docsBmad/project-context.md`.
- Livewire 3: preferir `wire:poll.visible` para evitar trabajo cuando el dashboard no está visible.
- Mantener compatibilidad con el wrapper `<x-ui.poll />` y el kill-switch global.
## Project Context Reference

- Semántica de inventario/estados y polling: `docsBmad/project-context.md`
- Reglas lean (idioma, rutas/código): `project-context.md`
- Patrones UI reutilizables: `gatic/docs/ui-patterns.md`
## Story Completion Status

- Status: **done**
- Completion note: "Dashboard mínimo implementado con polling visible (~60s), métricas operativas, indicador de frescura, manejo de error con `error_id` y tests estables."

## Senior Developer Review (AI)

Fecha: 2026-01-17  
Veredicto: **Aprobado** (sin HIGH/MEDIUM pendientes)

Fixes aplicados durante el review:
- Factories: `note` ahora siempre es string no-null (DB requiere `text('note')`).
- Factories: `asset_tag` ya no genera colisiones con unique index (cuando se genera, usa patrón con alta entropía).
- DashboardMetrics: manejo de error prod-safe con `ErrorReporter` + toast con `error_id` (en `local/testing` re-lanza).
- Tests: asserts más robustos usando `data-testid` y congelando tiempo con `Carbon::setTestNow`.

Evidencia:
- PHPUnit (Sail): `docker compose -f gatic/compose.yaml exec -T laravel.test php artisan test` ✅
- Pint: `docker compose -f gatic/compose.yaml exec -T laravel.test ./vendor/bin/pint --test` ✅

## Dev Agent Record

### Agent Model Used

Claude Opus 4.5 (Claude Code CLI)

### Debug Log References

- `_bmad/core/tasks/workflow.xml`
- `_bmad/bmm/workflows/4-implementation/dev-story/workflow.yaml`
- `_bmad/bmm/workflows/4-implementation/dev-story/instructions.xml`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `project-context.md` (reglas de PHP, Sail, stack)

### Implementation Plan

1. Creé componente Livewire `DashboardMetrics` con métodos `poll()` y `refreshNow()`.
2. Implementé queries eficientes usando `groupBy('status')` para conteo de activos sin cargar modelos.
3. Vista con `<x-ui.poll>` y `<x-ui.freshness-indicator>` reutilizando componentes existentes.
4. Dashboard con 5 tarjetas Bootstrap: Prestados, Pendientes de Retiro, Asignados, No Disponibles, Movimientos Hoy.
5. Tests de feature para validar polling markup y freshness indicator.
6. Creé factories para modelos que no las tenían (Category, Brand, Location, Product, Employee, Asset, AssetMovement, ProductQuantityMovement).
7. Agregué trait HasFactory a los modelos correspondientes.

### Completion Notes List

- Dashboard operativo con polling visible cada 60s (configurable via `config('gatic.ui.polling.metrics_interval_s')`).
- Métricas consistentes con semántica de estados en `Asset::STATUSES`.
- Queries agregadas eficientes sin N+1 ni cargar colecciones.
- UI en español, código en inglés.
- Botón "Actualizar ahora" con estado de loading.
- Sin WebSockets/SSE (regla cumplida).
- Pint pasa sin errores.
- Tests requieren Docker/Sail para ejecutarse (MySQL).

### File List

**Nuevos:**
- `gatic/app/Livewire/Dashboard/DashboardMetrics.php`
- `gatic/resources/views/livewire/dashboard/dashboard-metrics.blade.php`
- `gatic/tests/Feature/DashboardMetricsTest.php`
- `gatic/database/factories/CategoryFactory.php`
- `gatic/database/factories/BrandFactory.php`
- `gatic/database/factories/LocationFactory.php`
- `gatic/database/factories/ProductFactory.php`
- `gatic/database/factories/EmployeeFactory.php`
- `gatic/database/factories/AssetFactory.php`
- `gatic/database/factories/AssetMovementFactory.php`
- `gatic/database/factories/ProductQuantityMovementFactory.php`

**Modificados:**
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (sync status)
- `gatic/resources/views/dashboard.blade.php` (renderiza componente Livewire)
- `gatic/app/Models/Category.php` (agregado HasFactory)
- `gatic/app/Models/Brand.php` (agregado HasFactory)
- `gatic/app/Models/Location.php` (agregado HasFactory)
- `gatic/app/Models/Product.php` (agregado HasFactory)
- `gatic/app/Models/Employee.php` (agregado HasFactory)
- `gatic/app/Models/Asset.php` (agregado HasFactory)
- `gatic/app/Models/AssetMovement.php` (agregado HasFactory)
- `gatic/app/Models/ProductQuantityMovement.php` (agregado HasFactory)

## Change Log

- 2026-01-16: Implementación completa de Story 5.6 - Dashboard mínimo de métricas operativas con polling visible ~60s.
- 2026-01-17: Code review + fixes (factories no-null, asset_tag unique-safe, error handling con `error_id`, tests estables) + status -> done.
