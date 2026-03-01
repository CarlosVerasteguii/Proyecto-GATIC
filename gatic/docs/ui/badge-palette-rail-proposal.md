# Badge Palette B (Rail) — Propuesta (Fase 2)

- Fecha: 2026-02-27
- Scope: **solo propuesta** (sin refactors ni cambios en UI/código productivo)
- Plan maestro: [`badge-palette-rail-refactor.md`](./badge-palette-rail-refactor.md)
- Diagnóstico (Fase 1): [`badge-palette-rail-audit.md`](./badge-palette-rail-audit.md)
- Laboratorio visual (contrato): `GET /dev/ui-badges` (`dev.ui-badges`) — [`routes/web.php#L263`](../../routes/web.php#L263)

## Skills (obligatorio)

- `ui-ux-pro-max`
- `web-design-guidelines`
- `laravel-livewire`
- `laravel-blade`
- `systematic-debugging`
- `performance-profiling`
- `clean-code`
- `laravel-testing`

## Resumen ejecutivo

Objetivo: migrar **toda** la UI de badges/chips/tags a una sola familia visual: **Paleta B (Rail)**, sin tocar queries ni introducir costos de performance (refactor **visual**: Blade + CSS).

Propuesta (concreta):

- Crear un componente canónico **`<x-ui.badge>`** (Blade) y una clase base **`.gatic-badge`** (SCSS) que implementen Rail con **métricas fijas**.
- Convertir patrones existentes a **wrappers/compat** (sin romper APIs actuales):
  - `<x-ui.status-badge>` → wrapper de `<x-ui.badge>` (manteniendo `solid`/`icon`).
  - `.ops-status-chip` / `.dash-chip` / pills Admin → **alias visual** hacia `.gatic-badge` primero (CSS-only) y migración de markup después.
- Reducir la dispersión de Bootstrap directo: `class="badge ..."` aparece en **27 archivos** (audit), con mezcla de semánticas (estatus vs metadata vs alerta). La migración debe ser incremental, priorizando hotspots y componentes reutilizables (`livewire/ui/*`).
- A11y (contract): texto siempre presente; iconos decorativos siempre `aria-hidden="true"`; no depender solo del color (Rail agrega “forma” + texto); evitar badges “con affordance de botón” cuando son estáticos.
- Cobertura: el **audit es source of truth** para usos/hotspots. Esta propuesta no debe dejar “huérfanos”: cualquier uso de `.badge` fuera de la tabla de hotspots se gestiona como *long tail* con tracking (ver “Tabla de migración” + “Retiro de legacy”).

## Spec Rail (tokens/variables/semántica)

### Definición visual (Rail)

**Rail** = texto neutro (no “tintado”) + **acento lateral** (barra/rail) + **borde y fondo sutil** según tono.

Objetivo UX:

- El badge acompaña y ayuda a escaneo; **no compite** con el dato principal.
- Semántica consistente entre módulos: Inventario / Operaciones / Admin / Dashboard.

### Métricas fijas (Rail)

> La intención es que **todas** las categorías (estatus/rol/KPI/tag/alerta) compartan las mismas métricas. Solo se permite `compact` cuando el layout lo exija (ej. `app-compact`).

| Métrica | Valor propuesto | Fuente / alineación actual |
|---|---:|---|
| `font-size` | `0.75rem` | coincide con `.ops-status-chip`, `.dash-chip`, Admin summary |
| `font-weight` | `700` | consistente con Bootstrap `.badge` y chips actuales |
| `line-height` | `1.1` | consistente con chips actuales |
| `padding-y` | `0.20rem` | consistente con `.ops-status-chip`, `.dash-chip` |
| `padding-x` | `0.55rem` | alineado a `.admin-settings-summary-badge` |
| `gap` (icon/rail ↔ texto) | `0.35rem` | consistente con `.ops-status-chip` / Admin summary |
| `radius` | `var(--radius-full)` | pills (estándar actual) |
| `border` | `1px` | consistente con chips actuales |
| **rail width** | `0.25rem` | igual a `.ops-status-chip__dot` |
| **rail height** | `0.90rem` | igual a `.ops-status-chip__dot` |
| **rail radius** | `var(--radius-full)` | consistente |
| bg alpha (light) | `0.08` | `.ops-status-chip` (light) |
| border alpha (light) | `0.22` | `.ops-status-chip` (light) |
| bg alpha (dark) | `0.12` | `.ops-status-chip` (dark) |
| border alpha (dark) | `0.28` | `.ops-status-chip` (dark) |

### Anatomía (DOM) y performance

- **1 root element** por badge (`<span>`). Evitar wrappers adicionales en tablas grandes (Livewire diff).
- Rail idealmente con pseudo-element (`::before`) para **no agregar nodos** (migración futura de `.ops-status-chip__dot`).
- Icono opcional solo cuando aporta (estatus con 5+ estados): `<i class="bi ...">` con `aria-hidden="true"`.
- En tablas/loops grandes: si se toca markup en filas, **agregar/confirmar `wire:key` por fila** antes de cambiar estructura (mitiga costo de diff; el audit marca hotspots sin `wire:key`).
- `x-ui.badge` debe permanecer **Blade-only** (no Livewire) y sin JS/polling.

### Tokens CSS propuestos (nombres y ubicación)

Ubicación sugerida:

- Tokens (CSS custom properties): `resources/sass/_tokens.scss` (coherente con [`design-tokens.md`](./design-tokens.md)).
- Implementación de `.gatic-badge`: nuevo `resources/sass/_badges.scss` **o** sección dedicada en `_tokens.scss` (decidir en PR 1).

Tokens (propuesta):

```txt
--badge-rail-font-size: 0.75rem;
--badge-rail-font-weight: 700;
--badge-rail-line-height: 1.1;
--badge-rail-py: 0.20rem;
--badge-rail-px: 0.55rem;
--badge-rail-gap: 0.35rem;
--badge-rail-radius: var(--radius-full);
--badge-rail-border-width: 1px;
--badge-rail-rail-w: 0.25rem;
--badge-rail-rail-h: 0.90rem;
--badge-rail-bg-alpha: 0.08;
--badge-rail-border-alpha: 0.22;
--badge-rail-bg-alpha-dark: 0.12;
--badge-rail-border-alpha-dark: 0.28;
```

#### Tonos semánticos (Bootstrap)

Se basan en `--bs-*-rgb` para poder calcular `rgba()` sin inventar colores:

- `secondary` → `--bs-secondary-rgb`
- `info` → `--bs-info-rgb`
- `success` → `--bs-success-rgb`
- `warning` → `--bs-warning-rgb`
- `danger` → `--bs-danger-rgb`
- `primary` → `--bs-primary-rgb` (**restringido**, ver reglas; existe hoy en Operaciones)
- `neutral` → `--bs-secondary-rgb` (muy bajo énfasis; mismo “color base” pero con alphas más bajos)

Reglas:

- **Semántica** se usa para estatus de flujo y alertas (no para tags metadata).
- `primary` es **excepción**: hoy se usa en Operaciones para `PendingTaskStatus::PartiallyCompleted`. No usarlo como default; si se decide removerlo, requiere decisión de producto (¿se mapea a `info`/`warning`?).

##### Mapeo canónico (Operaciones)

Este mapeo es el contrato para evitar drift durante la migración (hoy vive en `badgeClass()`; a mediano plazo migrará a `tone()`):

**PendingTaskStatus**

| Estado | Label | Tono Rail |
|---|---|---|
| `Draft` | Borrador | `secondary` |
| `Ready` | Listo | `info` |
| `Processing` | Procesando | `warning` |
| `Completed` | Finalizado | `success` |
| `PartiallyCompleted` | Parcialmente finalizado | `primary` (restringido) |
| `Cancelled` | Cancelado | `danger` |

**PendingTaskLineStatus**

| Estado | Label | Tono Rail |
|---|---|---|
| `Pending` | Pendiente | `secondary` |
| `Processing` | Procesando | `warning` |
| `Applied` | Aplicado | `success` |
| `Error` | Error | `danger` |

#### Tonos de rol (RBAC)

Roles deben tener colores **fijos** en toda la app (no reutilizar estos tonos para estatus):

- `role-admin` → basado en `.admin-users-role--admin` (`#0f766e`)
- `role-editor` → basado en `.admin-users-role--editor` (`#1d4ed8`)
- `role-lector` → basado en `.admin-users-role--lector` (`#475569`)

Propuesta: elevar estos a tokens (preferir formato `RGB` para `rgb(var(--token) / alpha)`):

```txt
--role-admin-rgb: 15 118 110;
--role-editor-rgb: 29 78 216;
--role-lector-rgb: 71 85 105;
```

#### Tonos de estatus de entidad (Activo)

Se preservan los tokens existentes (ver `design-tokens.md` y `resources/sass/_tokens.scss`):

- `status-available`, `status-loaned`, `status-assigned`, `status-pending`, `status-retired`

Regla: `<x-ui.status-badge>` sigue siendo el punto de entrada semántico para estos estados, pero **renderiza Rail**.

### Variantes permitidas (Rail)

- `default` (por defecto): rail + borde + fondo sutil.
- `compact` (solo cuando sea estrictamente necesario):
  - activado por `.app-compact` o `variant="compact"`.
  - reduce `px/py` y/o `font-size` (sin cambiar semántica).
- `solid` (alta prominencia): **compatibilidad** (no para tags/metadata). Solo en casos justificados:
  - headers de detalle (cuando el estatus es protagonista).
  - alertas críticas (pocas y con texto explícito).

### Contrato Rail (invariantes y “no-go”)

Invariantes (no negociables):

- **Semántica**: `neutral` es para metadata/KPI; `success|warning|danger|info|secondary|primary` solo para estatus/alertas (y `primary` es restringido). Los roles (`role-*`) no se reutilizan como estatus.
- **Texto primero**: prohibidos badges “solo icono”. Si hay icono, es adicional y decorativo (`aria-hidden="true"`).
- **No depender solo del color**: el texto debe describir el estado (ej. “Stock bajo”, “Error”, “Prestado”), no solo “rojo/verde”.
- **Dark mode y contraste**: badges (texto ~`0.75rem`) deben mantenerse legibles en light/dark; si un tono no alcanza legibilidad, ajustar (no “forzar” `text-white` por default).
- **Livewire/perf**: 1 root element; rail por pseudo-element; sin wrappers por fila; sin componentes Livewire por celda; sin polling extra.
- **Bootstrap safety**: prohibido override global de `.badge`, `.nav-pills`, `.btn`, `.table`, etc. Solo se permite:
  - `.gatic-badge` como clase nueva, y
  - alias **acotados** por selector (ej. `.ops-status-chip { … }`) sin tocar el estilo base de Bootstrap para el resto del sistema.

Fuera de alcance (para evitar drift):

- Chips/badges **interactivos** (filtros, toggles): deben ser `<button>`/`<a>` con focus/hover y a11y; no usar `.gatic-badge` como si fuera botón. Si aparece un caso real, se diseña un componente distinto (no en este refactor).

## Taxonomía final (y variante Rail por categoría)

| Categoría | Qué comunica | Rail recomendado | Nota de uso |
|---|---|---|---|
| Estatus (Entidad) | estado estable de una entidad (Activo) | `<x-ui.status-badge>` → Rail `default` (tablas) / `solid` (header) | mantener `icon` cuando aporte escaneo |
| Estatus (Flujo) | estados de workflow (Pending Tasks) | `<x-ui.badge tone="secondary/info/warning/success/danger/primary">` Rail `default` | tono viene de Enums (sin inventar colores por módulo; `primary` restringido) |
| KPI / conteos | contexto, contadores, métricas | `<x-ui.badge tone="neutral">` Rail `default` | sin tonos semánticos (evitar confundir con alertas) |
| Rol (RBAC) | Admin/Editor/Lector | `<x-ui.badge tone="role-*">` Rail `default` | color por rol fijo global |
| Disponibilidad | habilitado/inactivo, disponible/no | `<x-ui.badge tone="success/secondary">` Rail `default` | texto explícito (no solo color) |
| Tags (metadata) | etiquetas neutrales (tipo/proveedor/categoría) | `<x-ui.badge tone="neutral" variant="compact">` | bajo énfasis, no usar `warning/danger` |
| Alertas (severidad) | señales fuertes (Vencido/Stock bajo/Error) | `<x-ui.badge tone="warning/danger" variant="solid">` (o temporalmente `text-bg-*`) | usar con moderación, foco en legibilidad/contraste |

## Arquitectura de componentes (propuesta)

### Nuevo canónico: `<x-ui.badge>`

Principios:

- **Blade** (no Livewire): cero impacto de lifecycle, sin polling.
- API estable (pocas props), atributos mergeables (`$attributes->merge()`).
- HTML semántico: por defecto `<span>`, no “parecer botón” si no lo es.

API propuesta (borrador):

- `tone` (string, requerido): `neutral|secondary|info|success|warning|danger|primary|role-admin|role-editor|role-lector|status-*` (si aplica).
- `variant` (string): `default|compact|solid` (default: `default`).
- `icon` (string|false): clase de Bootstrap Icons (opcional).
- `ariaLive` (string|false): `polite` solo si el contenido cambia asíncronamente (evitar por default).

### Wrappers / compatibilidad

1) `<x-ui.status-badge>` (existente)

- Mantiene API (`status`, `solid`, `icon`).
- Internamente mapea status → `tone="status-*"` y `variant`.
- **No debe aumentar DOM** (idealmente sigue siendo 1 root + opcional icon + label).

2) `.ops-status-chip` (existente)

- Corto plazo: alias CSS hacia `.gatic-badge` (manteniendo `badgeClass()` en Enums).
- Mediano plazo: migrar markup a `<x-ui.badge :tone="$task->status->tone()">` y reemplazar `badgeClass()` por un retorno semántico (`tone()`), reduciendo coupling a CSS.

3) `.dash-chip` (existente)

- Corto plazo: alias visual a Rail `neutral`.
- Mediano plazo: migrar a `<x-ui.badge tone="neutral">` y estandarizar patrón “label + strong value”.

4) Pills Admin (existentes)

- `.admin-users-role`, `.admin-users-status`, `.admin-settings-summary-*`:
  - corto plazo: alias visual a `.gatic-badge` (sin cambios de rutas/RBAC).
  - mediano plazo: migrar markup a `<x-ui.badge tone="role-*">` y `<x-ui.badge tone="neutral">`.

### Retiro de legacy (cuándo remover)

Propuesta:

- Mantener compat (`.ops-status-chip`, `.dash-chip`, pills Admin) hasta que:
  1) no existan usos productivos (solo dev/harness),
  2) exista reemplazo directo con `<x-ui.badge>` documentado,
  3) se actualice [`badges.md`](./badges.md) como contrato final.
- Luego: remover CSS legacy en PR de limpieza (con `rg` confirmando 0 usos).

## Tabla de migración (hotspots primero)

> Ordenado por impacto UX + riesgo Livewire (tablas grandes) según auditoría.

| Prioridad | Hotspot (archivo) | Ruta principal | Patrón actual | Categoría | Propuesta Rail | Riesgo |
|---|---|---|---|---|---|---|
| P0 | `resources/views/livewire/inventory/assets/assets-global-index.blade.php` | `inventory.assets.index` | `<x-ui.status-badge>` | Estatus entidad | wrapper `status-badge` → Rail | Medio |
| P0 | `resources/views/livewire/inventory/assets/assets-index.blade.php` | `inventory.products.assets.index` | `<x-ui.status-badge>` (+ sin `wire:key`) | Estatus entidad | **CSS-only primero** (sin cambiar DOM), luego wrapper | Medio |
| P0 | `resources/views/livewire/inventory/products/products-index.blade.php` | `inventory.products.index` | `.badge text-bg-*` (+ sin `wire:key`) | Alertas + metadata | migrar a `<x-ui.badge>` (Rail) sin wrappers extra | Alto |
| P0 | `resources/views/livewire/pending-tasks/pending-task-show.blade.php` | `pending-tasks.show` | `.ops-status-chip` + `.badge` directo | Flujo + tags + alertas | alias `.ops-status-chip` + migración selectiva de `.badge` | Medio/Alto |
| P0 | `resources/views/livewire/pending-tasks/pending-tasks-index.blade.php` | `pending-tasks.index` | `.ops-status-chip` + quick `.badge` | Flujo + tags | alias `.ops-status-chip` + `<x-ui.badge tone="neutral">` para tags | Medio |
| P1 | `resources/views/livewire/dashboard/dashboard-metrics.blade.php` | `dashboard` | `.dash-chip` + `.badge text-bg-*` | KPI + alertas | alias `.dash-chip` + migración de deltas a `<x-ui.badge>` | Medio |
| P1 | `resources/views/livewire/search/inventory-search.blade.php` | `inventory.search` | `.badge bg-info` (contraste) | Tags/metadata | migrar a Rail `neutral` o corregir a `text-bg-info` | Bajo/Medio |
| P1 | `resources/views/livewire/ui/employee-combobox.blade.php` | reutilizable | `.badge` directo | Tags/metadata | `<x-ui.badge tone="neutral" variant="compact">` | Medio |
| P1 | `resources/views/livewire/ui/product-combobox.blade.php` | reutilizable | `.badge` directo | Tags/metadata | `<x-ui.badge tone="neutral" variant="compact">` | Medio |
| P1 | `resources/views/livewire/ui/location-combobox.blade.php` | reutilizable | `.badge` directo (+ iconos sin `aria-hidden`) | Tags/metadata + A11y | `<x-ui.badge tone="neutral" variant="compact">` + fix `aria-hidden` | Medio |
| P2 | `resources/views/livewire/admin/users/users-index.blade.php` | `admin.users.index` | pills Admin + `.badge` | Rol + disponibilidad | `<x-ui.badge tone="role-*">` + `tone="success/secondary"` | Bajo |
| P2 | `resources/views/livewire/movements/assets/assign-asset-form.blade.php` | movimientos | `.badge` directo (semántica mezclada) | Estatus entidad (contexto) | migrar a `<x-ui.status-badge>` o Rail `neutral` según intención | Medio |
| P2 | `resources/views/livewire/movements/assets/return-asset-form.blade.php` | movimientos | `.badge` directo (semántica mezclada) | Estatus entidad (contexto) | migrar a `<x-ui.status-badge>` o Rail `neutral` según intención | Medio |
| P3 | `resources/views/livewire/admin/audit/audit-logs-index.blade.php` | `admin.audit.*` | `.badge bg-*` | Tags/metadata | migrar a Rail `neutral` | Bajo |

## Mapeo “patrón actual → patrón nuevo” (prioridad + riesgo)

| Patrón actual | Superficie | Nuevo patrón (target) | Estrategia | Prioridad | Riesgo |
|---|---|---|---|---|---|
| `<x-ui.status-badge>` (`.status-badge`) | Inventario + UI | `<x-ui.status-badge>` (Rail) sobre `<x-ui.badge>` | wrapper interno (sin API break) | P0 | Medio |
| `.ops-status-chip` + `badgeClass()` | Operaciones | `.ops-status-chip` (Rail) + futuro `<x-ui.badge>` | alias CSS → migración de Enums | P0 | Medio |
| `.dash-chip` | Dashboard + listados | `.dash-chip` (Rail neutral) + futuro `<x-ui.badge>` | alias CSS → migración markup | P1 | Medio |
| Pills Admin (`.admin-users-*`, `.admin-settings-*`) | Admin | `<x-ui.badge tone="role-*">` / `tone="neutral"` | alias CSS → migración markup | P2 | Bajo |
| `class="badge bg-*"` | Disperso | `<x-ui.badge tone="neutral">` o `tone="info"` | reemplazo directo por categoría | P0–P2 | Alto |
| `class="badge text-bg-*"` | Disperso | `<x-ui.badge variant="solid">` (o mantener temporal) | reemplazo gradual (alertas) | P1–P3 | Medio/Alto |

## Plan incremental por PRs (orden por impacto/riesgo)

> Nota: PRs propuestos para Fase 3 (implementación). Aquí solo se define el orden, alcance y criterios de aceptación.

### PR 0 — Preflight performance: `wire:key` en hotspots

Alcance:

- Agregar `wire:key` por fila en los hotspots marcados por el audit (Inventario: `assets-index`, `products-index`).
- Sin cambios visuales esperados (solo estabilidad/perf de diff).

Criterios de aceptación:

- Sin cambios de layout/UI (solo atributo).
- `php artisan test` pasa (y quality gates CI).

### PR 1 — Fundación: `x-ui.badge` + `.gatic-badge` + harness

Alcance:

- Crear `<x-ui.badge>` y estilos Rail (`.gatic-badge`).
- Actualizar `/dev/ui-badges` para mostrar Rail como default (sin tocar flujos productivos).
- Agregar tests de render del componente.

Criterios de aceptación:

- `/dev/ui-badges` cubre tonos/variantes + dark mode.
- Sin cambios visuales fuera de `/dev/*`.
- `php artisan test` pasa (y quality gates CI).

### PR 2 — Wrappers/compat: status + ops + dash + admin pills (CSS-first)

Alcance:

- `<x-ui.status-badge>` renderiza Rail internamente (API igual).
- Alias visual de `.ops-status-chip`, `.dash-chip`, `.admin-users-*`, `.admin-settings-*` a Rail (sin cambiar markup en vistas todavía).

Criterios de aceptación:

- No se agregan nodos DOM por badge en tablas grandes.
- Pending Tasks / Dashboard / Admin se ven consistentes (Rail), sin romper layout.
- Iconos decorativos mantienen `aria-hidden="true"`.

### PR 3 — Correcciones A11y/contraste (hot fixes guiados por auditoría)

Alcance:

- Corregir `badge bg-info` → Rail o `text-bg-info` en `inventory-search` (contraste).
- Estandarizar `aria-hidden="true"` en iconos decorativos detectados (locks + combobox badges, incluyendo `location-combobox`).

Criterios de aceptación:

- Sin regresiones funcionales; solo markup/a11y.
- Contraste legible en light/dark (validación manual en las pantallas mínimas).

### PR 4 — Migración de “UI reutilizable” (alto leverage)

Alcance:

- Migrar `.badge` en `resources/views/livewire/ui/*` a `<x-ui.badge>` según categoría (principalmente `neutral/compact`).

Criterios de aceptación:

- No se introducen estilos ad-hoc por vista: solo `<x-ui.badge>` + tones.
- Smoke manual en flujos que consumen combobox/panels (Pending Tasks + Movements + Inventario).

### PR 5 — Hotspots productivos (top 10) por módulo

Alcance:

- Inventario: `assets-*`, `products-index`, `product-show` (sin tocar queries).
- Operaciones: `pending-task-show`, `pending-tasks-index` (limpiar mezcla de `.badge`).
- Dashboard: `dashboard-metrics` (deltas/alertas y KPI chips).
- Admin: `users-index` (roles/estatus).

Criterios de aceptación:

- 0 usos nuevos de `class="badge ..."` para categorías migradas.
- DOM por badge estable (sin wrappers extras por fila).
- Screens mínimas del plan maestro revisadas sin desalineaciones.

### PR 6 — Limpieza y deprecación

Alcance:

- Remover/archivar CSS legacy no usado.
- Actualizar contrato final en [`badges.md`](./badges.md) + agregar nota de deprecación (si aplica).

Criterios de aceptación:

- `rg` confirma que patrones legacy se usan solo donde se decidió mantenerlos.
- Sin overrides globales de `.badge` Bootstrap (mantener aislado).

## Riesgos y mitigaciones

### Visuales (layout, densidad, jerarquía)

Riesgo:

- Cambios de `font-size/weight/padding` alteran alto de fila en tablas y wrapping.

Mitigación:

- PR 2 como alias CSS-first para controlar impacto.
- Validar en `/dev/ui-badges` + tablas grandes (Inventario/Operaciones) en light/dark.

### Bootstrap side-effects

Riesgo:

- Colisiones con `.badge`, `text-bg-*`, `bg-*` y overrides de `.app-compact`.

Mitigación:

- `.gatic-badge` no depende de `.badge`.
- `compact` se implementa explícitamente o via `.app-compact .gatic-badge` (sin tocar `.badge` global).

### A11y (contraste, iconografía, semántica)

Riesgo:

- Texto blanco por default en `.badge bg-info` (contraste).
- Iconos decorativos anunciados por screen readers si faltan `aria-hidden`.

Mitigación:

- Migrar `bg-*` → Rail o `text-bg-*` (cuando aplique).
- Contract: iconos decorativos siempre `aria-hidden="true"`.
- Evitar usar `role="status"` por default; solo cuando haya updates asíncronos reales (y con `aria-live="polite"`).

### Regresiones en Livewire (diff cost / DOM churn)

Riesgo:

- Cambios DOM en filas de tablas grandes (especialmente donde falta `wire:key`) aumentan costo de diff.

Mitigación:

- Priorizar CSS-only en hotspots con loops grandes.
- Cuando sea necesario cambiar markup, hacerlo con badge de **1 root element** (no wrappers).

## Plan de validación (Fase 3/4) — sin ejecutarlo ahora

### Automatizado (CI / local)

- Pint: `./vendor/bin/pint --test`
- Tests: `php artisan test`
- Larastan: `./vendor/bin/phpstan analyse --no-progress`

Tests a agregar (propuesta):

- Render tests de `<x-ui.badge>`: tonos/variantes, `icon=false`, `aria-hidden`, classes finales.
- Render tests de `<x-ui.status-badge>`: preserva API (`solid`, `icon`) y mapea tonos correctamente.

### Smoke manual (pantallas mínimas)

- `/dev/ui-badges` (contrato)
- `/pending-tasks` y un `show` representativo
- `/inventory/assets` y `/inventory/products`
- `/admin/users`
- `/dashboard`

Checklist de smoke:

- Contraste legible en light/dark (mínimo 4.5:1 para texto normal cuando aplique).
- Badges estáticos no parecen interactivos (sin hover/focus de botón).
- Iconos decorativos no “hablan” en lector de pantalla (`aria-hidden`).
- No hay salto de layout (altura de filas consistente).

## Checklist de aceptación (global)

- [ ] Rail es la familia dominante: `.ops-status-chip`, `.dash-chip`, Admin pills y `<x-ui.status-badge>` se ven consistentes.
- [ ] `class="badge bg-*"` deja de usarse para metadata/estatus; si queda, está justificado (alerta puntual) y documentado.
- [ ] Contraste: no existen casos tipo `badge bg-info` sin texto contrastado o equivalente Rail.
- [ ] A11y: iconos decorativos con `aria-hidden="true"`; badges con texto explícito (no dependen solo del color).
- [ ] Performance: sin wrappers extra por badge en tablas grandes; sin polling/debounce nuevos; sin cambios de queries.
- [ ] Calidad: Pint + PHPUnit + Larastan pasan en CI.
