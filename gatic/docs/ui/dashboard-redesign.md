# Dashboard GATIC: Analisis y Redisenio (Fases 1-3)

## 0) Nota de stack (importante)

Aunque el requerimiento menciona "Filament", en este repositorio el dashboard actual esta implementado con **Laravel + Livewire + Blade + Bootstrap** (no existe `app/Filament/*`).

Mockup visual (HTML auto-contenido, no integrado): `artifacts/gatic-dashboard-mockup.html`.

## 1) Exploracion del codigo (dashboard actual)

### Entrada / "controlador"

- Ruta: `/dashboard` retorna la vista `dashboard` (closure, no controller dedicado).
  - `gatic/routes/web.php:52`
- Vista: monta el componente Livewire del dashboard.
  - `gatic/resources/views/dashboard.blade.php:1`

### Componente principal (widgets/cards)

- Componente Livewire: orquesta queries + polling y expone propiedades a la vista.
  - `gatic/app/Livewire/Dashboard/DashboardMetrics.php:1`
- Vista Blade del componente: define cards/tablas, colores Bootstrap y links a drill-down (cuando aplica).
  - `gatic/resources/views/livewire/dashboard/dashboard-metrics.blade.php:1`

### Fuentes de datos (modelos / soporte)

- Modelos directamente usados por las metricas:
  - `gatic/app/Models/Asset.php:1` (estatus, prestamos, garantias, reemplazo)
  - `gatic/app/Models/AssetMovement.php:1` (movimientos de activos)
  - `gatic/app/Models/Product.php:1` (stock por cantidad, umbrales)
  - `gatic/app/Models/ProductQuantityMovement.php:1` (movimientos por cantidad)
- Feed de Actividad Reciente (agrega multiples fuentes, con RBAC):
  - `gatic/app/Support/Dashboard/RecentActivityBuilder.php:1`
- Configuracion/parametrizacion (ventanas "por vencer", moneda, polling):
  - `gatic/app/Support/Settings/SettingsStore.php:1`
  - `gatic/config/gatic.php:1`
- RBAC (roles y gates que afectan visibilidad/acciones en dashboard):
  - `gatic/app/Providers/AuthServiceProvider.php:1`

## 2) Metricas actuales (inventario)

> Columnas: **Metrica** = el nombre funcional del widget/card. **Color actual** = clases Bootstrap (`border-*`, `text-*`). **Link a detalle** = existe CTA/drill-down desde el widget.

| Metrica | Dato que muestra | Modelo/Query origen | Color actual | Tiene tendencia? | Tiene link a detalle? |
|---|---|---|---|---|---|
| Activos Prestados | Conteo de `Asset` con `status = Prestado` | `DashboardMetrics::loadAssetStatusCounts()` agrupa `assets` por `status` | Warning (`border-warning`, `text-warning`) | No | Si -> `inventory.assets.index?status=Prestado` |
| Vencidos (prestamos) | Prestados con `loan_due_date < hoy` | `DashboardMetrics::loadLoanDueDateAlertCounts()` sobre `Asset` | Danger (`border-danger`, `text-danger`) | No | Si (solo `inventory.manage`) -> `alerts.loans.index?type=overdue` |
| Por vencer (prestamos) | Prestados con `loan_due_date` entre hoy y hoy+N | Igual que arriba (N configurable) | Warning | No | Si (solo `inventory.manage`) -> `alerts.loans.index?type=due-soon&windowDays=N` |
| Garantias Vencidas | Activos con `warranty_end_date < hoy` (excluye retirados) | `DashboardMetrics::loadWarrantyAlertCounts()` sobre `Asset` | Danger | No | Si (solo `inventory.manage`) -> `alerts.warranties.index?type=expired` |
| Garantias Por Vencer | Activos con `warranty_end_date` entre hoy y hoy+N | Igual que arriba (N configurable) | Warning | No | Si (solo `inventory.manage`) -> `alerts.warranties.index?type=due-soon&windowDays=N` |
| Pendientes de Retiro | Conteo `Asset` con `status = Pendiente de Retiro` | `loadAssetStatusCounts()` | Danger | No | Si -> `inventory.assets.index?status=Pendiente%20de%20Retiro` |
| Activos Asignados | Conteo `Asset` con `status = Asignado` | `loadAssetStatusCounts()` | Primary (`border-primary`, `text-primary`) | No | Si -> `inventory.assets.index?status=Asignado` |
| Activos No Disponibles | Asignados + Prestados + Pendientes de Retiro | Derivado (suma de 3 propiedades) | Secondary (`border-secondary`, `text-secondary`) | No | Si -> `inventory.assets.index?status=unavailable` |
| Movimientos Hoy | Conteo de movimientos de hoy (activos + cantidad) | `AssetMovement` + `ProductQuantityMovement` por `created_at` | Info (`border-info`, `text-info`) | No | No |
| Stock Bajo | Productos por cantidad con `qty_total <= low_stock_threshold` (solo no-serializados) | `Product::lowStockQuantity()->count()` | Warning | No | Si (solo `inventory.manage`) -> `alerts.stock.index` |
| Valor del Inventario | Suma `assets.acquisition_cost` (solo moneda default, excluye retirados) | `DashboardMetrics::loadInventoryValue()` con `DB::table()->join()` | Success (`border-success`, `text-success`) | No | No (solo visible con `inventory.manage`) |
| Valor por Categoria (Top N) | Tabla Top N por valor (con "Otros") | `loadInventoryValue()` -> `groupBy(categories)` | Neutro (card default) | No | Si (fila) -> `inventory.products.index?category=<id>` |
| Valor por Marca (Top N) | Tabla Top N por valor (con "Otros" / "Sin marca") | `loadInventoryValue()` -> `leftJoin(brands)` | Neutro | No | Si (fila) -> `inventory.products.index?brand=<id>` |
| Actividad Reciente | Tabla de ultimos eventos cross-modulo (limite 15) | `RecentActivityBuilder::build()` | Neutro | No | Si (cuando `route != null`) |

## 3) Contexto de negocio (inferido del codigo)

### 3.1 Que tipo de usuario ve este dashboard?

- Cualquier usuario **autenticado y activo** puede entrar a `/dashboard`.
- El contenido es mas "accionable" para `inventory.manage` (Admin/Editor):
  - Ve valor del inventario + distribuciones, y aparecen botones "Ver lista" en alertas.
- El rol `Lector` puede ver metricas globales, pero sin drill-down a listas de alertas y sin valor monetario (por gates).

### 3.2 Proposito principal del sistema GATIC

- Gestion de inventario/activos para CFE:
  - Productos (serializados y por cantidad)
  - Activos individuales (serial, estatus, ubicacion, empleado actual)
  - Movimientos (asignar, prestar, devolver, entradas/salidas por cantidad)
  - Alertas operativas (prestamos, garantias, stock, reemplazos)
  - Evidencia y trazabilidad (auditoria, notas, adjuntos, ajustes, tareas pendientes)

### 3.3 KPIs mas criticos (segun lo que el dashboard ya prioriza)

- Riesgo/alerta: **prestamos vencidos**, **prestamos por vencer**, **garantias vencidas/por vencer**, **stock bajo**
- Operacion: **movimientos de hoy**, **actividad reciente**
- Disponibilidad: **no disponibles** (asignados + prestados + pendientes de retiro)
- (Admin/Editor) Valor: **valor total del inventario** y su distribucion por categoria/marca

### 3.4 Metricas que faltan pero que los modelos ya permiten

- Renovaciones / reemplazo (ya existe modulo de alertas): `expected_replacement_date` (overdue / due-soon).
- Estado completo de activos: disponibles, retirados, total (hoy solo muestra 3 estados + derivado).
- Calidad de datos: activos sin `location_id`, sin `acquisition_cost`, sin `warranty_end_date`, etc.
- Tareas pendientes: conteo por `status` (Draft/Ready/Processing/etc) para priorizar operacion.
- Contratos por vencer: `Contract.end_date` (si se vuelve KPI relevante).

### 3.5 Acciones desde el dashboard actual

- "Actualizar" (refresh manual) + polling automatico.
- Drill-down (solo Admin/Editor) a listas de alertas:
  - Prestamos (vencidos / por vencer)
  - Garantias (vencidas / por vencer)
  - Stock bajo
- Drill-down (Admin/Editor) desde distribucion por categoria/marca hacia `inventory.products.index` filtrado.
- Drill-down desde Actividad Reciente hacia entidades (producto/activo/empleado/tarea) cuando la ruta aplica y el gate lo permite.

## 4) Diagnostico UX (skills aplicadas: ui-ux-pro-max + page-cro)

### 4.1 Hallazgos principales (hoy)

- No hay **jerarquia explicita por "urgencia"**: todo se presenta como una grilla uniforme.
- Varias metricas son informativas pero **no accionables** (sin drill-down) aunque el sistema si tiene pantallas relevantes.
- Falta **contexto comparativo** (tendencia/delta) y **tiempo** (por ejemplo, "Movimientos Hoy" no dice contra que compararlo).
- "Valor por *" esta en tabla: funcional, pero no optimizada para lectura rapida (ranking visual).
- Para rol `Lector`, el dashboard muestra alertas sin ofrecer flujo de accion (por permisos). Esto puede ser deseable, pero conviene explicitar "que hacer" o ajustar permisos de lectura.

### 4.2 Page Conversion Readiness & Impact Index (adaptado a dashboard)

Interpretacion: "conversion" = **detectar riesgo y tomar una accion** (ir a la lista/entidad y resolver).

- Value Proposition Clarity: 18/25
- Conversion Goal Focus: 11/20
- Traffic-Message Match: 13/15
- Trust & Credibility Signals: 10/15 (bien: indicador de frescura; falta: cobertura/calidad de datos)
- Friction & UX Barriers: 9/15 (falta filtros y drill-down consistente)
- Objection Handling: 5/10 (no hay "siguiente paso" sugerido por metrica)

Total estimado: **66/100** (Readiness: baja). El mayor impacto viene de: jerarquia + accionabilidad + filtros.

## 5) Design System propuesto (skills: ui-ux-pro-max + frontend-design)

> Nota: el script `ui-ux-pro-max/scripts/search.py` no existe en este repo (solo esta `SKILL.md`), asi que el design system se deriva manualmente aplicando las reglas del skill y respetando los tokens actuales.

### 5.1 Direccion estetica (Frontend Design)

- Nombre: **"Industrial Command Center (CFE)"**
- Ancla de diferenciacion (memorable): **"Alert Rail"**: una franja superior que resume riesgo (overdue, expiring, low stock) con chips/mini barras y CTA directo.
- DFII (1-5): Impact 4, Fit 5, Feasibility 5, Performance 4, Consistency Risk 2 -> **DFII = 16 (excelente)**.

### 5.2 Paleta y variables CSS (dashboard)

Reusar tokens existentes en `resources/sass/_tokens.scss` y agregar solo lo necesario:

```css
:root {
  /* Dashboard surfaces */
  --dash-bg: var(--bs-body-bg);
  --dash-surface: var(--gatic-surface);
  --dash-surface-2: var(--bs-tertiary-bg);
  --dash-border: var(--bs-border-color);

  /* Emphasis */
  --dash-accent: var(--cfe-green);
  --dash-accent-bg: var(--cfe-green-bg);

  /* KPI helpers */
  --dash-kpi-number: var(--bs-body-color);
  --dash-kpi-muted: var(--bs-secondary-color);
  --dash-kpi-radius: var(--radius-md);
  --dash-kpi-shadow: var(--shadow-sm);
}
```

Semantica: usar `--color-danger|warning|success|info` para estados, no colores "inventados".

### 5.3 Tipografia

- Mantener **Nunito** (ya cargada) para continuidad.
- KPIs numericos: activar `font-variant-numeric: tabular-nums;` y reducir el uso de `display-4` (demasiado dominante en mobile).
- Titulos de seccion: 14-16px, uppercase ligero (ya hay patron en `detail-header-kpi`).

### 5.4 Espaciado, bordes, sombras, motion

- Espaciado: conservar escala `--space-*` (8px grid) y gutters de Bootstrap.
- Bordes: cards con borde neutro + **accent bar** (izquierda) por severidad, en lugar de solo `border-*`.
- Sombras: `--shadow-sm` por default; `--shadow-md` al hover (solo desktop).
- Motion: 150-200ms, respetando `prefers-reduced-motion`. Evitar animaciones decorativas.

## 6) Nuevo dashboard (propuesta de layout + jerarquia visual)

### 6.1 Desktop (12 columnas)

1. **Alert Rail (arriba)**: Overdue loans, warranties expired, low stock, renewals overdue (CTA a listas).
2. **KPIs operativos** (fila 2): movimientos hoy, activos no disponibles, pendientes de retiro, tareas pendientes (si aplica).
3. **Tendencias (charts)** (fila 3): movimientos ultimos 30 dias + backlog de alertas (line/stacked bar).
4. **Valor y distribucion** (fila 4, solo `inventory.manage`): total value + ranking visual (bars) por categoria/marca.
5. **Actividad Reciente** (full width): con filtro por tipo (opcional).

### 6.2 Tablet

- 2 columnas: Alert Rail -> KPIs -> charts -> tablas.

### 6.3 Mobile

- 1 columna: Alert Rail compacta (chips) -> KPIs compactos -> 1 chart por vez -> actividad reciente colapsable.

## 7) Widgets propuestos (nuevos o mejorados)

| Widget | Tipo | Datos | Visualizacion | Prioridad |
|---|---|---|---|---|
| Alert Rail | Nuevo | prestamos vencidos/por vencer, garantias vencidas/por vencer, stock bajo, reemplazos vencidos/por vencer | Chips + mini barras + CTA | P0 |
| Renovaciones (reemplazo) | Nuevo | `expected_replacement_date` overdue/due-soon | KPI card + link a `alerts.renewals.index` | P0 |
| Tareas pendientes | Nuevo | conteo por `PendingTaskStatus` (Draft/Ready/Processing) | KPI card + link a `pending-tasks.index` | P0 |
| Activos Disponibles / Total | Nuevo | `status = Disponible` + total assets | KPI compacta + stacked bar por estado | P1 |
| Movimientos (30 dias) | Nuevo | `AssetMovement` por tipo + `ProductQuantityMovement` in/out | Stacked bar diaria/semanal | P1 |
| Stock bajo (Top 10) | Mejora | productos mas criticos (qty vs threshold) | Bullet bars (qty, threshold) | P1 |
| Valor por categoria/marca | Mejora | ya existe | Cambiar tabla por bar chart + tabla compacta | P1 |
| Calidad de datos | Nuevo | assets sin campos clave (ubicacion, costo, garantia) | Lista/tabla compacta | P2 |

## 8) Interactividad

- Filtros:
  - Rango de fechas (7/30/90) para charts.
  - Ubicacion (si se requiere) para assets/alertas.
  - Categoria/Marca (para valor/stock).
- Drill-down consistente:
  - KPI -> pantalla lista filtrada (ideal: crear una lista global de activos; si no, enlazar a alertas existentes).
  - Charts -> click en serie/barra filtra la lista (deep-link con query params).
- Acciones rapidas (solo `inventory.manage`):
  - "Crear tarea pendiente"
  - "Crear producto"
  - "Ir a stock bajo / vencidos" (atajos)

## 9) Plan de implementacion (archivos a crear/modificar)

### 9.1 P0 (accionabilidad y cobertura)

- Modificar:
  - `gatic/app/Livewire/Dashboard/DashboardMetrics.php:1` (agregar contadores: renewals, pending tasks; preparar datasets para charts)
  - `gatic/resources/views/livewire/dashboard/dashboard-metrics.blade.php:1` (nueva jerarquia, Alert Rail, KPIs compactos)
- Reusar:
  - rutas existentes de alertas (`alerts.*`) y tareas pendientes.

### 9.2 P1 (componentizacion + charts)

- Crear componentes Blade:
  - `gatic/resources/views/components/ui/kpi-card.blade.php` (valor, label, ayuda, variant, delta, CTA)
  - `gatic/resources/views/components/ui/section-card.blade.php` (header + slot + actions)
  - `gatic/resources/views/components/ui/mini-bar.blade.php` (para Alert Rail)
- Integrar charts:
  - Opcion A (rapida): Chart.js via CDN en el dashboard (solo en esa vista).
  - Opcion B (pro): instalar Chart.js por npm y cargar via Vite en `resources/js`.

### 9.3 P2 (drill-down completo)

- Crear pagina/lista global de activos con filtros por status (si se vuelve necesario para drill-down de "Asignados/Prestados/etc"):
  - `gatic/app/Livewire/Inventory/Assets/AssetsGlobalIndex.php`
  - `gatic/resources/views/livewire/inventory/assets/assets-global-index.blade.php`
  - `gatic/routes/web.php` (nueva ruta `inventory.assets.index`)

## 10) Componentes Blade reutilizables (propuestos)

- `<x-ui.kpi-card>`:
  - Props: `label`, `value`, `variant`, `hint`, `delta`, `deltaLabel`, `href`, `icon`.
  - Slots: `footer` (CTA secundaria / metadata).
- `<x-ui.section-card>`:
  - Slots: `title`, `actions`, default slot (body).
- `<x-ui.alert-rail>` (opcional, podria ser composicion de `kpi-card` + `mini-bar`).

## 11) Notas sobre lime-echart / Tailwind

- `lime-echart` esta orientado a **UniApp**; para Laravel/Blade no aporta implementacion directa. Si se prefiere ECharts en web, se usaria la libreria ECharts JS "vanilla" (no el plugin lime-echart).
- `tailwind-css-patterns`: el proyecto usa Bootstrap, pero los patrones de grid/responsive aplican conceptualmente (ej: `row-cols-*`, `g-*`, layout mobile-first).
