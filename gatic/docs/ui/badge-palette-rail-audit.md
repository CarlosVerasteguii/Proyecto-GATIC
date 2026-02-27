# Badge Palette B (Rail) — Auditoría (Fase 1: Diagnóstico)

- Fecha: 2026-02-27
- Scope: **solo auditoría** (sin refactors ni cambios en UI productiva)
- Plan maestro: [`gatic/docs/ui/badge-palette-rail-refactor.md`](./badge-palette-rail-refactor.md)
- Contrato visual (dev): `dev.ui-badges` → `/dev/ui-badges` (solo `local`/`testing`) — [`routes/web.php#L263`](../../routes/web.php#L263)

## Resumen ejecutivo

- Hoy conviven **múltiples familias** de badges/chips/tags: `x-ui.status-badge`, `.ops-status-chip`, `.dash-chip`, badges Admin (Users/Settings) y un volumen alto de `.badge` Bootstrap directo.
- `x-ui.status-badge` está tokenizado (CSS custom properties) y es consistente dentro de Inventario, pero sus **métricas** (radius/size/weight) no alinean con chips/pills actuales.
- `.badge` Bootstrap directo es la **superficie de migración más grande**: aparece en **27 archivos** (incluyendo `livewire/ui/*` reutilizable y pantallas “pesadas” como Dashboard y Pending Task Show).
- Hallazgo de contraste probable: `badge bg-info` sin `text-dark`/`text-bg-info` en Inventario Search (riesgo de texto blanco sobre fondo claro).
- A11y: hay **inconsistencia** en iconografía dentro de badges/chips (varios `<i class="bi ...">` sin `aria-hidden="true"`), mientras que en otros lugares sí se aplica.
- Performance: el refactor propuesto puede y debe ser **solo visual (Blade + CSS)**; no hay acoplamiento necesario a queries. Hotspots: tablas grandes y loops sin `wire:key` (cualquier cambio DOM aquí aumenta el costo de diffs).
- La página `dev.ui-badges` ya concentra gran parte del “contract surface” y es el lugar correcto para validar regresiones visuales sin tocar flujos críticos.

---

## Inventario de usos (coverage completo)

### Tabla resumen (patrones)

| Patrón | Categoría UX | CSS (fuente) | Rutas para ver | Riesgo migración |
|---|---|---|---|---|
| `x-ui.status-badge` (renderiza `.status-badge`) | Estatus entidad (Activo/Asset) | [`resources/sass/_tokens.scss#L114`](../../resources/sass/_tokens.scss#L114), compact: [`resources/sass/_density.scss#L63`](../../resources/sass/_density.scss#L63) | `inventory.assets.index` `/inventory/assets` ([`routes/web.php#L118`](../../routes/web.php#L118)) + `inventory.products.assets.*` ([`routes/web.php#L123`](../../routes/web.php#L123)) + `dev.ui-badges` ([`routes/web.php#L263`](../../routes/web.php#L263)) | **Medio**: componente central (cambio impacta muchas pantallas) pero es 1 punto de control. |
| `.ops-status-chip` (+ `badgeClass()` en Enums) | Estatus flujo/operación (Pending Tasks + líneas) | [`resources/sass/_operations.scss#L126`](../../resources/sass/_operations.scss#L126) | `pending-tasks.index` `/pending-tasks` ([`routes/web.php#L197`](../../routes/web.php#L197)) + `pending-tasks.show` ([`routes/web.php#L227`](../../routes/web.php#L227)) + `dev.ui-badges` ([`routes/web.php#L263`](../../routes/web.php#L263)) | **Medio**: mapeo vive en Enums + markup con sub-elemento `__dot`. |
| `.dash-chip` | KPI / conteos / contexto (toolbar, dashboard) | [`resources/sass/_dashboard.scss#L195`](../../resources/sass/_dashboard.scss#L195) | `dashboard` `/dashboard` ([`routes/web.php#L58`](../../routes/web.php#L58)) + múltiples índices (Catálogos/Empleados/Tareas/Settings) | **Medio**: uso transversal (varios módulos), pero CSS central. |
| `.admin-users-role`, `.admin-users-status` (sobre `.badge.rounded-pill`) | Rol / disponibilidad (Admin → Users) | [`resources/sass/_admin-users.scss#L81`](../../resources/sass/_admin-users.scss#L81) | `admin.users.index` `/admin/users` ([`routes/web.php#L73`](../../routes/web.php#L73)) + `dev.ui-badges` | **Bajo**: acotado a Admin Users. |
| `.admin-settings-summary-badge`, `.admin-settings-summary-pill` | Estado de configuración / KPIs (Admin → Settings) | [`resources/sass/_admin-settings.scss#L117`](../../resources/sass/_admin-settings.scss#L117) | `admin.settings.index` `/admin/settings` ([`routes/web.php#L85`](../../routes/web.php#L85)) + `dev.ui-badges` | **Bajo/Medio**: acotado a Settings, pero con variantes `--custom`. |
| Bootstrap directo: `.badge` + `bg-*` / `border` / `text-dark` / `fs-*` | KPI / tags / estatus local / alertas puntuales | Bootstrap + compact override: [`resources/sass/_density.scss#L57`](../../resources/sass/_density.scss#L57) | Disperso (Inventario, Pending Tasks, Dashboard, Admin Audit, UI components) | **Alto**: alta dispersión + semántica mezclada. |
| Bootstrap directo: `.badge` + `text-bg-*` (incl. dinámicos `text-bg-{{...}}`) | Alertas/estado (stock, deltas, quick tags) | Bootstrap (`text-bg-*`) + compact override | Dashboard + Inventory + Pending Tasks + UI | **Alto**: mezcla de “alerta” vs “metadata” con la misma familia. |
| Bootstrap directo (dev): `bg-*-subtle` + `text-*-emphasis` + `border-*-subtle` | Laboratorio/paletas (solo dev) | Bootstrap (utility classes) | `dev.ui-badges` | **Bajo**: no productivo; sirve como referencia visual. |

---

### Detalle por patrón (paths + 1 línea de contexto)

> Nota: Links relativos asumen lectura desde `gatic/docs/ui/` hacia `gatic/*`.

#### `x-ui.status-badge`

- Definición del componente: [`resources/views/components/ui/status-badge.blade.php`](../../resources/views/components/ui/status-badge.blade.php)
- CSS principal: [`resources/sass/_tokens.scss#L114`](../../resources/sass/_tokens.scss#L114)
- Compact override: [`resources/sass/_density.scss#L63`](../../resources/sass/_density.scss#L63)

Ocurrencias (coverage):

- [`resources/views/components/ui/detail-header.blade.php#L12`](../../resources/views/components/ui/detail-header.blade.php#L12) — `<x-ui.status-badge :status="$asset->status" />`
- [`resources/views/livewire/dev/ui-badges-smoke-test.blade.php#L244`](../../resources/views/livewire/dev/ui-badges-smoke-test.blade.php#L244) — `<x-ui.status-badge :status="Asset::STATUS_AVAILABLE" />`
- [`resources/views/livewire/dev/ui-badges-smoke-test.blade.php#L324`](../../resources/views/livewire/dev/ui-badges-smoke-test.blade.php#L324) — `<x-ui.status-badge :status="Asset::STATUS_AVAILABLE" solid />`
- [`resources/views/livewire/dev/ui-badges-smoke-test.blade.php#L333`](../../resources/views/livewire/dev/ui-badges-smoke-test.blade.php#L333) — `<x-ui.status-badge :status="Asset::STATUS_AVAILABLE" :icon="false" />`
- [`resources/views/livewire/search/inventory-search.blade.php#L138`](../../resources/views/livewire/search/inventory-search.blade.php#L138) — `<x-ui.status-badge :status="$asset->status" />`
- [`resources/views/livewire/inventory/assets/assets-index.blade.php#L118`](../../resources/views/livewire/inventory/assets/assets-index.blade.php#L118) — `<td><x-ui.status-badge :status="$asset->status" /></td>`
- [`resources/views/livewire/inventory/assets/assets-global-index.blade.php#L258`](../../resources/views/livewire/inventory/assets/assets-global-index.blade.php#L258) — `<td><x-ui.status-badge :status="$asset->status" /></td>`
- [`resources/views/livewire/inventory/assets/asset-show.blade.php#L40`](../../resources/views/livewire/inventory/assets/asset-show.blade.php#L40) — `<x-ui.status-badge :status="$asset->status" solid />`
- [`resources/views/livewire/inventory/products/product-show.blade.php#L130`](../../resources/views/livewire/inventory/products/product-show.blade.php#L130) — `<td><x-ui.status-badge :status="$row['status']" /></td>`
- [`resources/views/livewire/inventory/contracts/contract-show.blade.php#L99`](../../resources/views/livewire/inventory/contracts/contract-show.blade.php#L99) — `<x-ui.status-badge :status="$asset->status" />`

Ruta/URL donde se ve (principal):

- `inventory.assets.index` → `/inventory/assets` — [`routes/web.php#L118`](../../routes/web.php#L118)
- `inventory.products.assets.index` → `/inventory/products/{product}/assets` — [`routes/web.php#L123`](../../routes/web.php#L123)
- `inventory.products.assets.show` → `/inventory/products/{product}/assets/{asset}` — [`routes/web.php#L126`](../../routes/web.php#L126)
- `dev.ui-badges` → `/dev/ui-badges` — [`routes/web.php#L263`](../../routes/web.php#L263)

Riesgo de migración: **Medio** (centralizado, pero alto impacto visual; requiere validar `solid`/`icon=false`).

---

#### `.ops-status-chip`

- CSS principal: [`resources/sass/_operations.scss#L126`](../../resources/sass/_operations.scss#L126)
- Variantes por tono: [`resources/sass/_operations.scss#L153`](../../resources/sass/_operations.scss#L153)
- Override dark: [`resources/sass/_operations.scss#L206`](../../resources/sass/_operations.scss#L206)

Fuente de clases (mapeo por estado):

- [`app/Enums/PendingTaskStatus.php#L43`](../../app/Enums/PendingTaskStatus.php#L43) — `self::Draft => 'ops-status-chip ops-status-chip--secondary',`
- [`app/Enums/PendingTaskStatus.php#L44`](../../app/Enums/PendingTaskStatus.php#L44) — `self::Ready => 'ops-status-chip ops-status-chip--info',`
- [`app/Enums/PendingTaskStatus.php#L47`](../../app/Enums/PendingTaskStatus.php#L47) — `self::PartiallyCompleted => 'ops-status-chip ops-status-chip--primary',`
- [`app/Enums/PendingTaskLineStatus.php#L39`](../../app/Enums/PendingTaskLineStatus.php#L39) — `self::Pending => 'ops-status-chip ops-status-chip--secondary',`
- [`app/Enums/PendingTaskLineStatus.php#L42`](../../app/Enums/PendingTaskLineStatus.php#L42) — `self::Error => 'ops-status-chip ops-status-chip--danger',`

Uso (markup) — clase via `badgeClass()`:

- [`resources/views/livewire/pending-tasks/pending-tasks-index.blade.php#L138`](../../resources/views/livewire/pending-tasks/pending-tasks-index.blade.php#L138) — `<span class="{{ $task->status->badgeClass() }}">`
- [`resources/views/livewire/pending-tasks/pending-task-show.blade.php#L27`](../../resources/views/livewire/pending-tasks/pending-task-show.blade.php#L27) — `<span class="{{ $task->status->badgeClass() }}">`
- [`resources/views/livewire/pending-tasks/pending-task-show.blade.php#L487`](../../resources/views/livewire/pending-tasks/pending-task-show.blade.php#L487) — `<span class="{{ $line->line_status->badgeClass() }}">`

Sub-elemento (acento visual):

- [`resources/views/livewire/pending-tasks/pending-tasks-index.blade.php#L139`](../../resources/views/livewire/pending-tasks/pending-tasks-index.blade.php#L139) — `<span class="ops-status-chip__dot" aria-hidden="true"></span>`
- [`resources/views/livewire/pending-tasks/pending-task-show.blade.php#L28`](../../resources/views/livewire/pending-tasks/pending-task-show.blade.php#L28) — `<span class="ops-status-chip__dot" aria-hidden="true"></span>`
- [`resources/views/livewire/pending-tasks/pending-task-show.blade.php#L488`](../../resources/views/livewire/pending-tasks/pending-task-show.blade.php#L488) — `<span class="ops-status-chip__dot" aria-hidden="true"></span>`

Ruta/URL donde se ve:

- `pending-tasks.index` → `/pending-tasks` — [`routes/web.php#L197`](../../routes/web.php#L197)
- `pending-tasks.show` → `/pending-tasks/{pendingTask}` — [`routes/web.php#L227`](../../routes/web.php#L227)
- `dev.ui-badges` → `/dev/ui-badges` — [`routes/web.php#L263`](../../routes/web.php#L263)

Riesgo de migración: **Medio** (acento/dot + clases calculadas en Enums; requiere mapping estable).

---

#### `.dash-chip`

- CSS: [`resources/sass/_dashboard.scss#L195`](../../resources/sass/_dashboard.scss#L195)

Ocurrencias (coverage):

- [`resources/views/livewire/dashboard/dashboard-metrics.blade.php#L543`](../../resources/views/livewire/dashboard/dashboard-metrics.blade.php#L543) — `<span class="dash-chip">Prestados <strong>{{ $assetsLoaned }}</strong></span>`
- [`resources/views/livewire/dashboard/dashboard-metrics.blade.php#L667`](../../resources/views/livewire/dashboard/dashboard-metrics.blade.php#L667) — `<span class="dash-chip">Listas <strong>{{ $pendingTasksReadyCount }}</strong></span>`
- [`resources/views/livewire/pending-tasks/pending-tasks-index.blade.php#L25`](../../resources/views/livewire/pending-tasks/pending-tasks-index.blade.php#L25) — `<span class="dash-chip">`
- [`resources/views/livewire/employees/employees-index.blade.php#L27`](../../resources/views/livewire/employees/employees-index.blade.php#L27) — `<span class="dash-chip">`
- [`resources/views/livewire/catalogs/brands/brands-index.blade.php#L27`](../../resources/views/livewire/catalogs/brands/brands-index.blade.php#L27) — `<span class="dash-chip">`
- [`resources/views/livewire/catalogs/categories/categories-index.blade.php#L27`](../../resources/views/livewire/catalogs/categories/categories-index.blade.php#L27) — `<span class="dash-chip">`
- [`resources/views/livewire/catalogs/locations/locations-index.blade.php#L27`](../../resources/views/livewire/catalogs/locations/locations-index.blade.php#L27) — `<span class="dash-chip">`
- [`resources/views/livewire/catalogs/suppliers/suppliers-index.blade.php#L27`](../../resources/views/livewire/catalogs/suppliers/suppliers-index.blade.php#L27) — `<span class="dash-chip">`
- [`resources/views/livewire/admin/settings/settings-form.blade.php#L25`](../../resources/views/livewire/admin/settings/settings-form.blade.php#L25) — `<span class="dash-chip">`

Ruta/URL donde se ve (principal):

- `dashboard` → `/dashboard` — [`routes/web.php#L58`](../../routes/web.php#L58)
- `pending-tasks.index` → `/pending-tasks` — [`routes/web.php#L197`](../../routes/web.php#L197)
- `employees.index` → `/employees` — [`routes/web.php#L236`](../../routes/web.php#L236)
- `catalogs.*.index` → `/catalogs/...` — [`routes/web.php#L92`](../../routes/web.php#L92)
- `admin.settings.index` → `/admin/settings` — [`routes/web.php#L85`](../../routes/web.php#L85)

Riesgo de migración: **Medio** (alto uso transversal; CSS central).

---

#### `.admin-users-role` / `.admin-users-status`

- CSS: [`resources/sass/_admin-users.scss#L81`](../../resources/sass/_admin-users.scss#L81)

Ocurrencias (coverage):

- [`resources/views/livewire/admin/users/users-index.blade.php#L167`](../../resources/views/livewire/admin/users/users-index.blade.php#L167) — `<span class="badge rounded-pill admin-users-role admin-users-role--{{ $roleClass }}">`
- [`resources/views/livewire/admin/users/users-index.blade.php#L173`](../../resources/views/livewire/admin/users/users-index.blade.php#L173) — `<span class="badge rounded-pill admin-users-status admin-users-status--active">`
- [`resources/views/livewire/admin/users/users-index.blade.php#L178`](../../resources/views/livewire/admin/users/users-index.blade.php#L178) — `<span class="badge rounded-pill admin-users-status admin-users-status--inactive">`
- [`resources/views/livewire/dev/ui-badges-smoke-test.blade.php#L392`](../../resources/views/livewire/dev/ui-badges-smoke-test.blade.php#L392) — `<span class="badge rounded-pill admin-users-role admin-users-role--admin">Admin</span>`

Ruta/URL donde se ve:

- `admin.users.index` → `/admin/users` — [`routes/web.php#L73`](../../routes/web.php#L73)
- `dev.ui-badges` → `/dev/ui-badges` — [`routes/web.php#L263`](../../routes/web.php#L263)

Riesgo de migración: **Bajo** (acotado, CSS en un solo archivo).

---

#### `.admin-settings-summary-badge` / `.admin-settings-summary-pill`

- CSS: [`resources/sass/_admin-settings.scss#L117`](../../resources/sass/_admin-settings.scss#L117)

Ocurrencias (coverage):

- [`resources/views/livewire/admin/settings/settings-form.blade.php#L260`](../../resources/views/livewire/admin/settings/settings-form.blade.php#L260) — `<span class="admin-settings-summary-badge admin-settings-summary-badge--{{ $summaryStatusClass }}">`
- [`resources/views/livewire/admin/settings/settings-form.blade.php#L301`](../../resources/views/livewire/admin/settings/settings-form.blade.php#L301) — `<span class="admin-settings-summary-pill">`
- [`resources/views/livewire/dev/ui-badges-smoke-test.blade.php#L422`](../../resources/views/livewire/dev/ui-badges-smoke-test.blade.php#L422) — `<span class="admin-settings-summary-badge admin-settings-summary-badge--custom">`

Ruta/URL donde se ve:

- `admin.settings.index` → `/admin/settings` — [`routes/web.php#L85`](../../routes/web.php#L85)
- `dev.ui-badges` → `/dev/ui-badges` — [`routes/web.php#L263`](../../routes/web.php#L263)

Riesgo de migración: **Bajo/Medio** (acotado, pero con variante `--custom` + uso KPI).

---

#### Bootstrap directo: `.badge` (y variantes)

- CSS base: Bootstrap 5 (importado en [`resources/sass/app.scss#L8`](../../resources/sass/app.scss#L8))
- Compact override global: [`resources/sass/_density.scss#L57`](../../resources/sass/_density.scss#L57)

Ocurrencias (`class="badge ..."` — 1 línea representativa por archivo):

- [`resources/views/livewire/admin/audit/audit-logs-index.blade.php#L88`](../../resources/views/livewire/admin/audit/audit-logs-index.blade.php#L88) — `<span class="badge bg-secondary">{{ $log->action_label }}</span>`
- [`resources/views/livewire/dashboard/dashboard-metrics.blade.php#L108`](../../resources/views/livewire/dashboard/dashboard-metrics.blade.php#L108) — `<span class="badge bg-primary ms-1">{{ $activeFiltersCount }}</span>`
- [`resources/views/livewire/pending-tasks/pending-tasks-index.blade.php#L133`](../../resources/views/livewire/pending-tasks/pending-tasks-index.blade.php#L133) — `<span class="badge text-bg-info">{{ $quickLabel }}</span>`
- [`resources/views/livewire/pending-tasks/pending-task-show.blade.php#L32`](../../resources/views/livewire/pending-tasks/pending-task-show.blade.php#L32) — `<span class="badge bg-light text-dark border">`
- [`resources/views/livewire/search/inventory-search.blade.php#L90`](../../resources/views/livewire/search/inventory-search.blade.php#L90) — `<span class="badge bg-secondary">{{ $this->assets->count() }}</span>`
- [`resources/views/livewire/inventory/products/products-index.blade.php#L170`](../../resources/views/livewire/inventory/products/products-index.blade.php#L170) — `<span class="badge text-bg-danger ms-2" role="status">Sin disponibles</span>`
- [`resources/views/livewire/inventory/products/product-show.blade.php#L104`](../../resources/views/livewire/inventory/products/product-show.blade.php#L104) — `<span class="badge text-bg-warning ms-2">Stock bajo</span>`
- [`resources/views/livewire/inventory/products/product-kardex.blade.php#L30`](../../resources/views/livewire/inventory/products/product-kardex.blade.php#L30) — `<span class="badge bg-secondary">{{ $entries->total() }} registros</span>`
- [`resources/views/livewire/inventory/contracts/contracts-index.blade.php#L99`](../../resources/views/livewire/inventory/contracts/contracts-index.blade.php#L99) — `<span class="badge bg-secondary">{{ $contract->assets_count }}</span>`
- [`resources/views/livewire/inventory/contracts/contract-show.blade.php#L14`](../../resources/views/livewire/inventory/contracts/contract-show.blade.php#L14) — `<span class="badge bg-primary">{{ $contract->type_label }}</span>`
- [`resources/views/livewire/inventory/contracts/contract-form.blade.php#L156`](../../resources/views/livewire/inventory/contracts/contract-form.blade.php#L156) — `<span class="badge bg-warning text-dark">`
- [`resources/views/livewire/inventory/assets/asset-show.blade.php#L138`](../../resources/views/livewire/inventory/assets/asset-show.blade.php#L138) — `<span class="badge bg-danger ms-1">Vencido</span>`
- [`resources/views/livewire/movements/assets/assign-asset-form.blade.php#L48`](../../resources/views/livewire/movements/assets/assign-asset-form.blade.php#L48) — `<span class="badge bg-success">{{ $asset->status }}</span>`
- [`resources/views/livewire/movements/assets/return-asset-form.blade.php#L30`](../../resources/views/livewire/movements/assets/return-asset-form.blade.php#L30) — `<span class="badge bg-info text-dark">{{ $asset->status }}</span>`
- [`resources/views/livewire/movements/assets/unassign-asset-form.blade.php#L46`](../../resources/views/livewire/movements/assets/unassign-asset-form.blade.php#L46) — `<span class="badge bg-info text-dark">{{ $asset->status }}</span>`
- [`resources/views/livewire/movements/products/quantity-movement-form.blade.php#L27`](../../resources/views/livewire/movements/products/quantity-movement-form.blade.php#L27) — `<span class="badge bg-primary fs-6">{{ $currentStock }}</span>`
- [`resources/views/livewire/ui/employee-combobox.blade.php#L48`](../../resources/views/livewire/ui/employee-combobox.blade.php#L48) — `<span class="badge bg-success">`
- [`resources/views/livewire/ui/product-combobox.blade.php#L69`](../../resources/views/livewire/ui/product-combobox.blade.php#L69) — `<span class="badge bg-primary">`
- [`resources/views/livewire/ui/location-combobox.blade.php#L51`](../../resources/views/livewire/ui/location-combobox.blade.php#L51) — `<span class="badge bg-success">`
- [`resources/views/livewire/ui/supplier-combobox.blade.php#L71`](../../resources/views/livewire/ui/supplier-combobox.blade.php#L71) — `<span class="badge bg-info text-dark">`
- [`resources/views/livewire/ui/attachments-panel.blade.php#L5`](../../resources/views/livewire/ui/attachments-panel.blade.php#L5) — `<span class="badge bg-secondary">{{ $attachments->total() }}</span>`
- [`resources/views/livewire/ui/notes-panel.blade.php#L5`](../../resources/views/livewire/ui/notes-panel.blade.php#L5) — `<span class="badge bg-secondary">{{ $notes->total() }}</span>`
- [`resources/views/livewire/ui/timeline-panel.blade.php#L4`](../../resources/views/livewire/ui/timeline-panel.blade.php#L4) — `<span class="badge bg-secondary">{{ count($events) }}</span>`

Variante adicional: `@class([... 'badge' ...])` (no `class="badge"` literal):

- [`resources/views/livewire/pending-tasks/pending-task-show.blade.php#L895`](../../resources/views/livewire/pending-tasks/pending-task-show.blade.php#L895) — `'badge',`

Variante `text-bg-*` (incluye dinámicos):

- [`resources/views/livewire/dashboard/dashboard-metrics.blade.php#L348`](../../resources/views/livewire/dashboard/dashboard-metrics.blade.php#L348) — `<span class="badge text-bg-{{ $loansOverdueDeltaVariant }}">{{ $loansOverdueDeltaLabel }}</span>`
- [`resources/views/livewire/inventory/products/products-index.blade.php#L170`](../../resources/views/livewire/inventory/products/products-index.blade.php#L170) — `<span class="badge text-bg-danger ms-2" role="status">Sin disponibles</span>`
- [`resources/views/livewire/pending-tasks/pending-tasks-index.blade.php#L133`](../../resources/views/livewire/pending-tasks/pending-tasks-index.blade.php#L133) — `<span class="badge text-bg-info">{{ $quickLabel }}</span>`

Variante `bg-*-subtle` / `text-*-emphasis` / `border-*-subtle` (solo dev):

- [`resources/views/livewire/dev/ui-badges-smoke-test.blade.php#L490`](../../resources/views/livewire/dev/ui-badges-smoke-test.blade.php#L490) — `<span class="badge border border-success-subtle text-success-emphasis bg-success-subtle" role="status">Success</span>`

Riesgo de migración: **Alto** (dispersión + semántica no uniforme).

---

## Auditoría UI/UX/A11y (priorizada por severidad)

### Reglas aplicadas (fuentes)

- Web Interface Guidelines (Vercel): decorativos con `aria-hidden="true"`, feedback/estados para interactivos, semántica HTML, y formato `file:line` para evidencia.
- `ui-ux-pro-max`: contraste mínimo 4.5:1 (texto normal), foco visible, no depender solo de color, consistencia de tipografía/espaciado/radius.

### Crítico

- **Riesgo de contraste (badge info)**: [`resources/views/livewire/search/inventory-search.blade.php#L198`](../../resources/views/livewire/search/inventory-search.blade.php#L198) — `<span class="badge bg-info">Serializado</span>` (no `text-dark`/`text-bg-info`). Impacto: legibilidad baja (texto blanco por default en `.badge` sobre fondo `bg-info` claro).

### Alto

- **Inconsistencia de familia (size/weight/radius)** entre patrones:
  - `.status-badge` usa radius pequeño y peso medio: [`resources/sass/_tokens.scss#L119`](../../resources/sass/_tokens.scss#L119), [`resources/sass/_tokens.scss#L120`](../../resources/sass/_tokens.scss#L120), [`resources/sass/_tokens.scss#L122`](../../resources/sass/_tokens.scss#L122).
  - `.ops-status-chip` es pill y más “fuerte”: [`resources/sass/_operations.scss#L132`](../../resources/sass/_operations.scss#L132), [`resources/sass/_operations.scss#L136`](../../resources/sass/_operations.scss#L136), [`resources/sass/_operations.scss#L138`](../../resources/sass/_operations.scss#L138).
  - `.dash-chip` también pill: [`resources/sass/_dashboard.scss#L200`](../../resources/sass/_dashboard.scss#L200), [`resources/sass/_dashboard.scss#L204`](../../resources/sass/_dashboard.scss#L204).
  Impacto: escaneo inconsistente en tablas/toolbars; se percibe “sin sistema” y dificulta la jerarquía.

- **A11y: iconos decorativos sin `aria-hidden`** (inconsistente con el resto del repo):
  - Locks: [`resources/views/livewire/pending-tasks/pending-task-show.blade.php#L233`](../../resources/views/livewire/pending-tasks/pending-task-show.blade.php#L233), [`resources/views/livewire/pending-tasks/pending-task-show.blade.php#L249`](../../resources/views/livewire/pending-tasks/pending-task-show.blade.php#L249).
  - Badges en comboboxes: [`resources/views/livewire/ui/employee-combobox.blade.php#L49`](../../resources/views/livewire/ui/employee-combobox.blade.php#L49), [`resources/views/livewire/ui/location-combobox.blade.php#L52`](../../resources/views/livewire/ui/location-combobox.blade.php#L52), [`resources/views/livewire/ui/product-combobox.blade.php#L70`](../../resources/views/livewire/ui/product-combobox.blade.php#L70).
  Impacto: ruido en lectores de pantalla y estándar inconsistente (riesgo de regresiones).

- **Semántica de color mezclada (mismo color para conceptos distintos)** con `.badge` Bootstrap directo:
  - Estado actual de Activo en Movements (verde/azul) sin usar `x-ui.status-badge`: [`resources/views/livewire/movements/assets/assign-asset-form.blade.php#L48`](../../resources/views/livewire/movements/assets/assign-asset-form.blade.php#L48), [`resources/views/livewire/movements/assets/return-asset-form.blade.php#L30`](../../resources/views/livewire/movements/assets/return-asset-form.blade.php#L30).
  - “Quick capture” tag (info): [`resources/views/livewire/pending-tasks/pending-tasks-index.blade.php#L133`](../../resources/views/livewire/pending-tasks/pending-tasks-index.blade.php#L133).
  Impacto: el usuario interpreta color como “estado” pero se usa también como “metadata”.

### Medio

- **Badges/chips que parecen botones** (pill + border) cuando están junto a controles reales:
  - CSS `dash-chip`: [`resources/sass/_dashboard.scss#L195`](../../resources/sass/_dashboard.scss#L195)
  - Uso en toolbars: [`resources/views/livewire/employees/employees-index.blade.php#L27`](../../resources/views/livewire/employees/employees-index.blade.php#L27)
  Impacto: affordance ambiguo (parece filtro/tag interactivo pero es estático).

- **Mezcla de variantes en una misma pantalla** (chips custom + badges Bootstrap + status-badge):
  - Pending Task Show combina `.ops-status-chip` + varios `.badge`: [`resources/views/livewire/pending-tasks/pending-task-show.blade.php#L27`](../../resources/views/livewire/pending-tasks/pending-task-show.blade.php#L27), [`resources/views/livewire/pending-tasks/pending-task-show.blade.php#L255`](../../resources/views/livewire/pending-tasks/pending-task-show.blade.php#L255).
  Impacto: inconsistencia visual y “ruido” de estilos.

### Bajo

- `dev.ui-badges` incluye laboratorio de variantes (útil), pero hoy conviven paletas A/B/C en la misma página; mantenerlo intencional como harness y evitar que se “copie” a productivo.

---

## Auditoría performance (riesgo)

- **Este refactor debe ser visual**: los patrones actuales no requieren tocar queries; la mayoría son HTML/Blade + clases/tokens.
- **DOM por badge (aprox.)**:
  - `x-ui.status-badge`: `<span>` + `<i>` + `<span>` (≈3 nodos) — ver definición [`resources/views/components/ui/status-badge.blade.php#L58`](../../resources/views/components/ui/status-badge.blade.php#L58).
  - `.ops-status-chip`: `<span>` + `__dot` + `<span>` (≈3 nodos) — ver [`resources/views/livewire/pending-tasks/pending-tasks-index.blade.php#L138`](../../resources/views/livewire/pending-tasks/pending-tasks-index.blade.php#L138).
  - `.dash-chip`: `<span>` + `<strong>` (≈2 nodos) — ver [`resources/views/livewire/employees/employees-index.blade.php#L27`](../../resources/views/livewire/employees/employees-index.blade.php#L27).
- **Hotspots de render/diff Livewire** (tablas grandes):
  - `inventory/products/assets` index **sin `wire:key` por fila**: [`resources/views/livewire/inventory/assets/assets-index.blade.php#L114`](../../resources/views/livewire/inventory/assets/assets-index.blade.php#L114) (cualquier cambio DOM en filas aumenta costo de diff).
  - `inventory/products` index **sin `wire:key` por fila**: [`resources/views/livewire/inventory/products/products-index.blade.php#L138`](../../resources/views/livewire/inventory/products/products-index.blade.php#L138).
- Recomendación para fases futuras: mantener un solo elemento root por badge y evitar componentes Livewire por celda.

---

## Hotspots para migración (Top 10 por impacto)

1. [`resources/views/livewire/inventory/assets/assets-global-index.blade.php`](../../resources/views/livewire/inventory/assets/assets-global-index.blade.php) — tabla grande con `x-ui.status-badge` (route `inventory.assets.index`).
2. [`resources/views/livewire/inventory/assets/assets-index.blade.php`](../../resources/views/livewire/inventory/assets/assets-index.blade.php) — tabla por producto con `x-ui.status-badge` y **sin `wire:key`** (route `inventory.products.assets.index`).
3. [`resources/views/livewire/inventory/products/products-index.blade.php`](../../resources/views/livewire/inventory/products/products-index.blade.php) — alertas stock con `.badge text-bg-*` y **sin `wire:key`** (route `inventory.products.index`).
4. [`resources/views/livewire/pending-tasks/pending-task-show.blade.php`](../../resources/views/livewire/pending-tasks/pending-task-show.blade.php) — mezcla alta de patrones (ops chip + Bootstrap badges + `@class`).
5. [`resources/views/livewire/pending-tasks/pending-tasks-index.blade.php`](../../resources/views/livewire/pending-tasks/pending-tasks-index.blade.php) — `.ops-status-chip` por fila + quick tag Bootstrap.
6. [`resources/views/livewire/dashboard/dashboard-metrics.blade.php`](../../resources/views/livewire/dashboard/dashboard-metrics.blade.php) — `.dash-chip` + deltas `text-bg-*` (route `dashboard`).
7. [`resources/views/livewire/search/inventory-search.blade.php`](../../resources/views/livewire/search/inventory-search.blade.php) — mix badges + **hallazgo de contraste** (route `inventory.search`).
8. [`resources/views/livewire/ui/employee-combobox.blade.php`](../../resources/views/livewire/ui/employee-combobox.blade.php) — badge reutilizable en múltiples flujos (pending tasks + movements + forms).
9. [`resources/views/livewire/ui/product-combobox.blade.php`](../../resources/views/livewire/ui/product-combobox.blade.php) — badge reutilizable (quick capture + pending task show).
10. [`resources/views/livewire/admin/users/users-index.blade.php`](../../resources/views/livewire/admin/users/users-index.blade.php) — roles/estatus con `.badge.rounded-pill` + CSS dedicado (route `admin.users.index`).

---

## Checklist — Fase 1 (Diagnóstico)

- [x] Inventario de patrones solicitado (componentes/clases + Bootstrap directo).
- [x] Evidencia por archivo (path + 1 línea de contexto) con links.
- [x] CSS/SCSS localizado por patrón (archivo + selector).
- [x] Rutas/URLs mapeadas desde `routes/web.php` cuando aplica.
- [x] Auditoría UI/UX/A11y priorizada por severidad con evidencia.
- [x] Auditoría performance (DOM/Livewire hotspots) sin tocar queries.

