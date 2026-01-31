# PERF Fix Plan — GATIC (Measure → Analyze → Optimize)

**Fecha:** 2026-01-30  
**Stack:** Laravel 11 + Livewire 3 + MySQL 8.0 (Docker Compose)  
**Contexto UX (bible):** si algo tarda `>3s`, debe haber loader/skeleton + progreso + opción de cancelar. (ver `docsBmad/project-context.md` / NFR2)

Este documento **NO implementa cambios**: consolida evidencia + root causes + plan técnico priorizado.

**Recordatorios de producción:** ver `docs-prod/PERF_PROD_REMINDERS.md`.

---

## 0) Resumen ejecutivo (5 bullets)

- El problema dominante hoy es **TTFB alto y muy variable** por **boot/autoload** en Docker+Windows (repo en OneDrive + bind mount): `/login` cold ~3.9s; perfiles Xdebug muestran que una gran porción del tiempo está en `vendor/composer/ClassLoader.php` (carga de clases/archivos).
- La búsqueda por nombre se vuelve “inutilizable” por un **multiplicador**: `wire:model.live` dispara varios requests por tipeo y cada request ejecuta SQL con `LIKE '%term%'` (table scans). Medición real: escribir “Dell” (3 updates) llega a **p90 ~6.2s**.
- Hay issues de app que escalan mal aunque el dataset demo sea pequeño: **serial exacto** hoy escanea `assets` (no hay índice en `assets.serial`), y `/inventory/products` usa **subqueries correlacionadas + paginate COUNT**.
- El gap Admin vs Lector en `/inventory/products` se explica por una mezcla de (a) **más UI/acciones** y checks en el render del Admin y (b) **spikes** del server que se sienten más cuando hay más HTML/DOM; además la query base necesita rework para escala.
- Plan P0/P1 prioriza: (P0) cortar requests por tecla + ajustar SQL/índices mínimos + reducir costo de `/inventory/products` y “Procesar”; (P1) FULLTEXT/Scout y telemetría por etapa. (Recordatorios de prod en `docs-prod/PERF_PROD_REMINDERS.md`.)

## 1) Top 5 problemas (evidencia + hipótesis)

> Fuente primaria de performance: `PERF_REPORT.md` + `PERF_DEEP_DIVE.md`.  
> Confirmaciones adicionales: mediciones locales con `curl` + llamadas Livewire reales a `/livewire/update` (2026-01-30).

| Ruta / Acción | p50/p90 **TTFB** | p50/p90 **total** | Rol | Evidencia | Hipótesis inicial |
|---|---:|---:|---|---|---|
| `/login` (cold/warm baseline) | **Cold:** ~3.88s (1x) / **Warm:** ~0.22s / ~1.73s | **Warm total:** ~0.22s / ~1.73s | Público | `PERF_REPORT.md` (TTFB ~2.54s cold). Local: tras `docker compose restart laravel.test`, `curl` a `/login` ~3.88s; warm p50 ~223ms, p90 ~1.73s. | Bottleneck base es **server-side** (autoload/boot PHP+Laravel) con **varianza alta** (spikes). |
| **Búsqueda por nombre** (UX real por tipeo) `Livewire update search="De"→"Del"→"Dell"` | Por request: ~0.23–0.26s / ~1.81–1.96s | **Secuencia (3 requests)**: **~0.89s / ~6.22s** | Admin/Lector | Local (Livewire real): 3 updates p90 suman ~6.2s. `PERF_REPORT.md`: `/inventory/search?q=Dell` TTFB ~7.52s. | **Amplificación por Livewire**: `wire:model.live` dispara múltiples requests + SQL `LIKE '%term%'` fuerza scans. Spikes de TTFB multiplicados por número de requests. |
| `/inventory/products` (Admin vs Lector) | Admin más “spiky” (p90 ~2–3s+). | LCP reportado: Admin ~5.8s vs Lector ~2.6s (browser). | Admin vs Lector | `PERF_REPORT.md` (gap LCP). `PERF_DEEP_DIVE.md` confirma Admin > Lector en TTFB. Local interleaved: Admin tiene más spikes. | Admin renderiza más UI (Acciones) + más checks `@can`/render + más HTML/DOM; y cualquier spike de boot pega más. Además query actual escala mal (subqueries + paginate COUNT). |
| Locks “Procesar” (Pending Tasks) `enterProcessMode` | ~0.41s / ~3.24s | ~0.41s / ~3.24s | Editor/Admin | Local (Livewire real): `enterProcessMode` p90 ~3.24s. `PERF_REPORT.md`: click “Procesar” ~5s en UI. | Request Livewire **re-render** grande + lock TX + latencia base (autoload/boot) ⇒ acción se siente lenta. |
| `/inventory/products/{p}/assets/{a}` (detalle Activo) | TTFB alto + render delay alto | LCP ~5.7s (render delay ~2.5s) | Admin | `PERF_REPORT.md`: `/inventory/products/1/assets/1` LCP ~5.7s. | Vista incluye paneles (notes/attachments) + más componentes/DOM; sumado a TTFB base alto. |

---

## 2) Dónde se origina el costo (código: paths + líneas aprox)

### 2.1 SearchInventory / Livewire Search

1) **Acción de búsqueda (SQL “caro”)**  
`gatic/app/Actions/Search/SearchInventory.php`
- `searchProductsByName()` usa `LIKE "%term%"` (leading wildcard) → no usa B-Tree index. (~L112–L128)
- `searchAssetsBySerialOrTag()` usa `serial LIKE "%term%" OR asset_tag LIKE "%term%"` → scan. (~L135–L152)
- `findExactAssetsBySerial()` hace `WHERE serial = ?` **sin** index usable (porque el índice actual es `(product_id, serial)`); en escala será scan. (~L92–L105)

2) **Componente Livewire (amplificación por requests)**  
`gatic/app/Livewire/Search/InventorySearch.php`
- `#[Url(as: 'q')] public string $search` (~L20–L22)
- `updatedSearch()` llama `performSearch()` en cada update (~L48–L51)

3) **Blade (dispara requests por tecla)**  
`gatic/resources/views/livewire/search/inventory-search.blade.php`
- `<input ... wire:model.live.debounce.300ms="search" />` (~L17–L24)

### 2.2 Productos listado

1) **Query**  
`gatic/app/Livewire/Inventory/Products/ProductsIndex.php`
- `LIKE "%q%"` en `products.name` (leading wildcard). (~L95–L97)
- Subqueries correlacionadas para conteos (`assets_total`, `assets_unavailable`). (~L82–L93)
- `paginate()` dispara COUNT extra. (~L121)

2) **Blade + RBAC en loop**  
`gatic/resources/views/livewire/inventory/products/products-index.blade.php`
- Search input: `wire:model.live.debounce.300ms="search"` (~L22–L28)
- Filtros: `wire:model.live="categoryId/brandId/availability"` (~L34–L71)
- `@can('inventory.manage')` dentro de `@forelse ($products as $product)` (Acciones por fila). (~L150–L154)

### 2.3 Gates / RBAC

`gatic/app/Providers/AuthServiceProvider.php`
- `Gate::before(...)` Admin override (~L26–L28)
- `inventory.view`, `inventory.manage`, etc. (~L49–L57)

### 2.4 Locks (Pending Tasks)

**Livewire UI**
- `gatic/app/Livewire/PendingTasks/PendingTaskShow.php`
  - `enterProcessMode()` → Acquire lock + `loadTask()` + re-render (~L473–L524)
  - `loadTask()` eager loads `lines.product`, `lines.employee` (~L153–L169)
- `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php`
  - Botón `wire:click="enterProcessMode"` (“Procesar”). (~L236–L243)

**Lock actions**
- `gatic/app/Actions/PendingTasks/AcquirePendingTaskLock.php`
  - `DB::transaction()` + `lockForUpdate()` en `pending_tasks` (~L31–L37)
- `gatic/app/Actions/PendingTasks/HeartbeatPendingTaskLock.php`
  - `lockForUpdate()` en heartbeat (~L28–L33)

---

## 3) SQL real + planes de ejecución (EXPLAIN / EXPLAIN ANALYZE)

### 3.1 Búsqueda por nombre (confirmado: no usa índices)

**SQL (capturado vía `DB::listen` en tinker):**
```sql
select * from `products`
where name like ? escape '\\' and `products`.`deleted_at` is null
order by `name` asc
limit 20
-- bindings: ["%Dell%"]
```

**EXPLAIN ANALYZE (MySQL 8.0.43):**  
Resultado: **Table scan** en `products` (no usa `products.name` index por leading wildcard).

Checklist detectado:
- ✅ `LIKE '%term%'` → no access predicate (scan).

### 3.2 Búsqueda por serial (confirmado: falta índice usable)

**SQL exact serial:**
```sql
select * from `assets`
where `serial` = ? and `assets`.`deleted_at` is null
order by `serial` asc
-- bindings: ["SN-DEMO-001"]
```

**EXPLAIN ANALYZE:**  
Resultado: **Table scan** en `assets`.  
Causa: el índice actual es `UNIQUE(product_id, serial)` y el query no filtra `product_id`. (regla de **leftmost prefix**)

Checklist detectado:
- ✅ Índice compuesto no sirve para `WHERE serial = ?` si `product_id` es el primer campo.

### 3.3 Productos index (confirmado: subqueries correlacionadas + COUNT de paginate)

**SQL principal (capturado):**
```sql
select `products`.*,
  `categories_for_counts`.`is_serialized` as `category_is_serialized`,
  (select count(*) from `assets`
    where `assets`.`product_id` = `products`.`id`
      and `assets`.`status` != ?
      and `categories_for_counts`.`is_serialized` = ?
      and `assets`.`deleted_at` is null) as `assets_total`,
  (select count(*) from `assets`
    where `assets`.`product_id` = `products`.`id`
      and `assets`.`status` in (?, ?, ?)
      and `categories_for_counts`.`is_serialized` = ?
      and `assets`.`deleted_at` is null) as `assets_unavailable`
from `products`
left join `categories` as `categories_for_counts`
  on `categories_for_counts`.`id` = `products`.`category_id`
 and `categories_for_counts`.`deleted_at` is null
where `products`.`deleted_at` is null
order by `products`.`name` asc
limit 15 offset 0
```

**COUNT extra (paginate):**
```sql
select count(*) as aggregate
from `products`
left join `categories` as `categories_for_counts`
  on `categories_for_counts`.`id` = `products`.`category_id`
 and `categories_for_counts`.`deleted_at` is null
where `products`.`deleted_at` is null
```

**EXPLAIN ANALYZE (observación clave):**
- aparecen subqueries “dependent” (correlacionadas) → se evalúan **por fila** (escala O(N) sobre productos).
- con datos demo el planner elige scans (tabla pequeña), pero con datos reales esto se vuelve crítico.

Checklist detectado:
- ✅ subqueries correlacionadas por fila
- ✅ `paginate()` → COUNT adicional
- ✅ `LIKE '%q%'` en filtro de búsqueda dentro de `/inventory/products`

---

## 4) ¿Infra o App? (Respuestas obligatorias A–E)

### A) ¿Cuello de botella principal: infra o app?

**Respuesta:** Hoy es **principalmente infraestructura + boot/autoload**, con issues de app que **garantizan mal performance a escala**.

**Evidencia (Xdebug profile de requests lentos):**
- `/login` (slow): ~3.80s totales con ~78% en `vendor/composer/ClassLoader.php` (autoload) y ~6% PHP internal; framework ~5%. (perfil local 2026-01-30)
- `/inventory/products` (slow): ~2.38s totales con ~66% en `vendor/composer/ClassLoader.php` + ~13% PHP internal + ~13% framework. Render de views y Livewire es porcentaje bajo en esa corrida.

Interpretación:
- La app está pagando muchísimo en **carga de clases/archivos** (bind mounts Windows/OneDrive + modo dev + sin caches/OPcache “prod-like”).
- Aun así, la **app tiene queries que fuerzan scans** (`LIKE '%term%'`) y conteos por subqueries correlacionadas que serán cuellos reales con tablas grandes (aunque hoy el dataset demo sea chico).

### B) ¿Por qué búsqueda por nombre se va a 7s+?

**Respuesta (confirmada por medición + SQL/EXPLAIN):** por **amplificación de Livewire** + SQL que no usa índices.

1) **Livewire dispara múltiples requests por tipeo**  
`wire:model.live.debounce.300ms` (search) ⇒ al escribir “Dell” se ejecutan ~3 updates (`De`, `Del`, `Dell`) serializados.  
Medición real: **p90 secuencia ~6.2s** (3 requests × p90 ~1.8–2.0s).  
Fuente doc Livewire: `wire:model`/modificadores (`.live`, `debounce`, `.blur`) y `wire:init`/lazy.  
- https://livewire.laravel.com/docs/3.x/forms  
- https://livewire.laravel.com/docs/3.x/wire-init

2) **SQL fuerza scans (índices no usados)**  
Código: `SearchInventory::searchProductsByName()` y `searchAssetsBySerialOrTag()` usan `LIKE "%term%"`.  
EXPLAIN ANALYZE confirma `Table scan` en `products` y `assets`.  
Referencia (concepto): leading wildcard evita uso eficiente de B-Tree.  
- https://use-the-index-luke.com/sql/where-clause/searching-for-ranges/like-performance-tuning  
- https://dev.mysql.com/blog-archive/mysql-explain-analyze/

Adicional: “serial exacto” también escanea porque falta índice en `assets.serial` (índice actual `(product_id, serial)` no ayuda por **leftmost prefix**).  
- https://dev.mysql.com/doc/refman/8.0/en/mysql-indexes.html

### C) ¿Por qué Admin tarda mucho más que Lector en `/inventory/products`?

**Respuesta (más probable, basada en código + medición):**

1) **Admin renderiza más UI por fila (columna Acciones)**  
En `products-index.blade.php` hay `@can('inventory.manage')` dentro del loop; Admin/Editor ven “Editar” por fila (más HTML/DOM y más trabajo de render).  
`PERF_REPORT.md` reporta que el gap fuerte aparece en LCP (render) además de TTFB.

2) **Admin sufre más “spikes” de server**  
En mediciones intercaladas (Admin->Lector), Admin presenta spikes frecuentes de TTFB mientras Lector suele permanecer en ~300–450ms (con spikes ocasionales). Esto sugiere que parte del gap es **varianza del entorno** + trabajo extra de render.

3) **Query actual escala mal (independiente del rol)**  
Subqueries correlacionadas + COUNT de paginate afectan a ambos, pero cualquier extra de UI/gates del Admin hace más visible el problema.

Acción de diagnóstico recomendada (sin adivinar): comparar en server el **tamaño HTML** + **tiempo de render** para Admin vs Lector y cuantificar % de diferencia en (a) autoload/boot, (b) render de Blade, (c) queries.

### D) ¿Qué parte exacta de TTFB se va en boot, DB connect, queries, render, middleware?

**Respuesta (lo que sí está “medido” hoy):** En requests lentos, la mayor parte del tiempo se va a **boot/autoload**, no a queries.

Ejemplo real (request lento a `/inventory/products`, ~2.38s total):
- **autoload/vendor (composer + vendor no-framework):** ~72%
- **Laravel framework:** ~13%
- **PHP internal:** ~13%
- **Livewire:** ~1.5%
- **compiled views:** ~0.2%

Esto indica que, en este entorno, el cuello primario está en **cargar clases/archivos** y no en ejecutar SQL.

**Qué falta para “exactitud por sub-etapas” en TODAS las rutas:**  
un perfil por ruta (p50 y p90) y/o instrumentación de `Server-Timing` (boot vs queries vs view). Con Xdebug profiling ya se puede, pero hay que automatizarlo por endpoint y percentil.

### E) Solución MVP-friendly vs robusta (por problema)

Se detalla en el plan (P0/P1) con dos opciones por problema (Quick Win vs Robusta).

---

## 5) Plan técnico priorizado

### P0 (1–2 días) — Quick Wins (alto impacto / bajo riesgo)

#### P0.1 — ~~Infra: mover filesystem a WSL2~~ ⛔ NO VIABLE

> [!CAUTION]
> **NO IMPLEMENTAR:** Requiere WSL2 + distribución Linux (~3-5GB mínimo). No hay espacio disponible en disco.

~~**Qué cambiaría (área):** mover el repo fuera de OneDrive y/o correrlo dentro de WSL2 (filesystem Linux) y bind-mount desde ahí.~~

**Por qué funciona (y fuente):** Docker Desktop recomienda almacenar código dentro del filesystem Linux para maximizar performance de bind mounts en WSL2.  
- https://docs.docker.com/desktop/wsl/best-practices/  
- https://docker.com/blog/docker-desktop-wsl-2-best-practices

**Impacto esperado:** baja fuerte de p90 en TTFB “base” (cold/warm) y menos spikes en Livewire (se siente en búsqueda/locks).

**Cómo validar:** repetir p50/p90 en `/login` (cold+warm) + Livewire search sequence + `enterProcessMode`.

#### P0.2 — Search UX: eliminar “requests por tecla” (control de frecuencia)
**Qué cambiaría (área):**
- `gatic/resources/views/livewire/search/inventory-search.blade.php` (cambiar `.live` → submit explícito / `.blur` / debounce alto)
- `gatic/app/Livewire/Search/InventorySearch.php` (evitar `updatedSearch()` como trigger principal)

**Por qué funciona (y fuente):** `.live` fuerza roundtrip por tipeo; en v3 puedes controlar cuándo manda request (`.blur`, `debounce`, submit).  
- https://livewire.laravel.com/docs/3.x/forms

**Impacto esperado:** bajar el p90 “tipeo→resultado estable” (hoy ~6s por 3 requests) hacia p90 de 1 request o menor número de requests.

**Cómo validar:** medir `/livewire/update` para `"De"→"Del"→"Dell"` antes/después (p90 sumado).

**Implementado (2026-01-30):**
- Archivos:
  - `gatic/resources/views/livewire/search/inventory-search.blade.php`
  - `gatic/app/Livewire/Search/InventorySearch.php`
- Cambio: input pasa a `wire:model.defer` + submit explícito (`submitSearch`) + botón “Limpiar”; se elimina `updatedSearch()` como trigger principal.
- Impacto medido (ver `PERF_P0_BASELINE.md` / `PERF_P0_AFTER.md`):
  - Antes (3 requests por tipeo “De→Del→Dell”, **sum 3 updates**): Total p50/p90 **2700ms / 8210ms**
  - Después (**1 request por búsqueda** via `submitSearch`, term=`Dell`): Total p50/p90 **213ms / 1765ms**
- Confirmación UX: ya no existe “multiplicador por tecla”; el request ocurre en Enter/botón.

#### P0.3 — Search SQL + índices mínimos (hacerlo index-friendly)
**Qué cambiaría (área):**
- `gatic/app/Actions/Search/SearchInventory.php`: evitar `LIKE '%term%'` (usar prefix + tokenización) para `products.name`, `assets.serial`, `assets.asset_tag`.
- Migración nueva: agregar `INDEX assets(serial)` (y evaluar `INDEX assets(product_id, status, deleted_at)` si los conteos crecen).

**Por qué funciona (y fuentes):**
- `LIKE '%term%'` evita uso eficiente de índices B-Tree; prefix `term%` permite range scan.  
  - https://use-the-index-luke.com/sql/where-clause/searching-for-ranges/like-performance-tuning
- Índice compuesto `(product_id, serial)` no sirve para `WHERE serial = ?` sin `product_id` (leftmost prefix).  
  - https://dev.mysql.com/doc/refman/8.0/en/mysql-indexes.html

**Impacto esperado:** en datasets reales, la búsqueda deja de hacer scans de tablas grandes; además reduce DB work por request Livewire.

**Cómo validar:** `EXPLAIN ANALYZE` debe cambiar de `Table scan` a `Index range/lookup` para búsquedas típicas; medir p50/p90 de `/livewire/update` search.

**Implementado (2026-01-30):**
- Archivos:
  - `gatic/app/Actions/Search/SearchInventory.php`
  - `gatic/database/migrations/2026_01_30_000000_add_index_to_assets_serial.php`
- Cambio SQL:
  - `products.name`: `LIKE "%term%"` → patrón sin leading wildcard (`token1%token2%...%`)
  - `assets.serial` / `assets.asset_tag`: `LIKE "%term%"` → `LIKE "term%"`
- EXPLAIN (ver `PERF_P0_BASELINE.md` / `PERF_P0_AFTER.md`):
  - `products.name LIKE '%Dell%'` → **Table scan**
  - `products.name LIKE 'Dell%'` → **Index range scan** (`products_name_index`)
  - `assets.serial = 'SN-DEMO-001'` antes → **Table scan**; después → **Index lookup** (`assets_serial_index`)
  - `assets serial/tag LIKE '%Dell%'` → **Table scan**; después (prefix) → **Index range scans** (serial + `assets_asset_tag_unique`)

#### P0.4 — `/inventory/products`: recortar costo de listados + gap Admin/Lector
**Qué cambiaría (área):**
- `gatic/app/Livewire/Inventory/Products/ProductsIndex.php`: evaluar `simplePaginate()` (si UX acepta) y evitar `LIKE '%q%'`.
- `gatic/resources/views/livewire/inventory/products/products-index.blade.php`: evitar `@can(...)` dentro del loop (precomputar bool una vez) y reducir DOM de “Acciones” (dropdown).

**Por qué funciona (y fuentes):**
- `paginate()` hace query extra `COUNT(*)`; `simplePaginate()` evita ese costo cuando no necesitas total.  
  - https://laravel.com/docs/12.x/pagination
- `@can` usa Gates/Policies (check runtime); reducir llamadas repetidas dentro de loops evita trabajo repetitivo.  
  - https://laravel.com/docs/11.x/authorization

**Impacto esperado:** menos tiempo de render y menos variación visible (Admin). En datasets grandes, menos presión por COUNT y filtros.

**Cómo validar:** medir p50/p90 en `/inventory/products` Admin vs Lector (TTFB + size) y LCP (browser) antes/después.

**Implementado (2026-01-30):**
- Archivos:
  - `gatic/app/Livewire/Inventory/Products/ProductsIndex.php`
  - `gatic/resources/views/livewire/inventory/products/products-index.blade.php`
- Cambios:
  - Search input: `wire:model.live.debounce` → `wire:model.defer` + submit (`applySearch`) para evitar requests por tecla.
  - Query: `paginate()` → `simplePaginate()` (elimina COUNT extra) + search `LIKE` sin leading wildcard.
  - Blade: `$canManageInventory` precomputado; `@can` removido del loop; “Acciones” en dropdown (menos DOM por fila).
- Impacto medido (ver `PERF_P0_BASELINE.md` / `PERF_P0_AFTER.md`):
  - `/inventory/products` Admin Total p50/p90: **2889ms / 3242ms** → **316ms / 1931ms**
  - `/inventory/products` Lector Total p50/p90: **2802ms / 3732ms** → **299ms / 2005ms**
  - Query log: COUNT de `paginate()` desaparece (solo SELECT con `limit 16`).

#### P0.5 — Locks “Procesar”: feedback inmediato + carga diferida
**Qué cambiaría (área):**
- `gatic/app/Livewire/PendingTasks/PendingTaskShow.php` + `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php`: entrar a modo proceso rápido y cargar partes pesadas con `wire:init`/lazy.

**Por qué funciona (y fuentes):**
- `wire:init`/lazy permiten renderizar un shell rápido y cargar datos después, reduciendo TTFB perceptual en acciones caras.  
  - https://livewire.laravel.com/docs/3.x/wire-init  
  - https://livewire.laravel.com/docs/3.x/lazy

**Impacto esperado:** bajar p90 de click “Procesar” (hoy ~3–5s) y mejorar UX de locks (cumple NFR2).

**Cómo validar:** medir p50/p90 de `PendingTaskShow::enterProcessMode` vía `/livewire/update` y validar que la UI muestra progreso/cancel.

**Implementado (2026-01-30):**
- Archivos:
  - `gatic/app/Livewire/PendingTasks/PendingTaskShow.php`
  - `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php`
- Cambios:
  - Feedback inmediato: spinner/disable en botón “Procesar” con `wire:loading` (target `enterProcessMode`).
  - NFR2: overlay `<x-ui.long-request>` aplica a `enterProcessMode` e `initProcessModeUi` (con cancelar).
  - Carga diferida: al entrar a modo proceso se muestra shell + skeleton; la tabla pesada se renderiza en un segundo request (`wire:init="initProcessModeUi"`).
  - `enterProcessMode`: se evita `loadTask()` en path exitoso; se sincroniza lock en el modelo en memoria.
- Impacto medido (ver `PERF_P0_BASELINE.md` / `PERF_P0_AFTER.md`):
  - `enterProcessMode` Total p50/p90: **752ms / 2816ms** → **309ms / 1756ms**
  - Interacción completa “Procesar” = `enterProcessMode` + `initProcessModeUi`: Total p50/p90 **573ms / 2396ms**

---

### P1 (1–2 semanas) — Iniciativas (más esfuerzo, performance sostenida)

#### P1.1 — (Prod) Caches + OPcache (movido)
Ver `docs-prod/PERF_PROD_REMINDERS.md`.

#### P1.2 — Rediseño de búsqueda “correcto” (calidad + performance)
**Opción A (robusta sin servicios externos): MySQL FULLTEXT**
- **Qué cambiaría:** FULLTEXT index en `products.name` (y campos relevantes) + query `MATCH() AGAINST()` (Boolean Mode con `*` para prefijos).
- **Por qué funciona:** FULLTEXT está diseñado para búsqueda textual y evita scans por `LIKE '%term%'`.
- **Fuentes:**  
  - https://dev.mysql.com/doc/refman/8.0/en/fulltext-search.html  
  - https://dev.mysql.com/doc/refman/8.0/en/fulltext-boolean.html

**Opción B (robusta con servicio): Laravel Scout + Meilisearch**
- **Qué cambiaría:** usar Scout con driver Meilisearch y indexar `products`/`assets` con settings (searchable/synonyms).
- **Por qué funciona:** motor de búsqueda dedicado (relevancia, tolerancia, ranking) y latencia estable.
- **Fuentes:**  
  - https://laravel.com/docs/master/scout  
  - https://www.meilisearch.com/docs/guides/laravel_scout

**Impacto esperado:** búsqueda por nombre estable en p90 (sin multiplicador de scans), mejor relevancia.

**Cómo validar:** test A/B con dataset real: p50/p90 de búsqueda + precisión (top-5 contiene el resultado esperado).

#### P1.3 — Rework de `/inventory/products` para escala
**Qué cambiaría (área):**
- Reemplazar subqueries correlacionadas por agregados (joinSub / GROUP BY) o materializar conteos (denormalización).
- Añadir índices que soporten filtros/conteos (`assets(product_id, status, deleted_at)`).

**Por qué funciona (y fuentes):**
- Evitas O(N) subqueries por producto y reduces reads; y puedes verificar con `EXPLAIN`/`EXPLAIN ANALYZE`.  
  - https://dev.mysql.com/blog-archive/mysql-explain-analyze/  
  - https://dev.mysql.com/doc/refman/8.0/en/explain-output.html

**Impacto esperado:** en inventarios reales (muchos productos y assets), la lista pasa de “crece con N” a “costo estable por página”.

**Cómo validar:** `EXPLAIN ANALYZE` antes/después + medir p50/p90 `/inventory/products` con dataset grande; comparar costo de COUNT (si se mantiene paginate).

#### P1.4 — Telemetría de performance “first-class”
**Qué cambiaría (área):**
- Instrumentar por request: boot, middleware, DB connect, tiempo total de queries, render (Blade/Livewire) y tamaño de respuesta.
- Registrar slow queries y planes (cuando excedan umbral), asociado a request-id.

**Por qué funciona:** convierte “se siente lento” en datos accionables y evita optimización a ciegas (Measure → Analyze → Optimize).

**Cómo validar:** dashboard mínimo de p50/p90 por ruta + top queries por tiempo; regresión detectable por PR (staging).

---

## 6) Riesgos y mitigación

- **Cambiar semantics de búsqueda** (prefix vs contains): Mitigar con tokenización index-friendly + hint de UI (“comenzar desde el inicio”) y plan P1.2 (FULLTEXT) para recuperar búsqueda robusta.
- **FULLTEXT**: stopwords/min token size; validar idioma/cadenas (ver docs MySQL FULLTEXT).
- **Índices nuevos**: en tablas grandes, crear en ventana de mantenimiento y medir impacto en writes.
- **simplePaginate()**: cambia UX de paginación (sin total / sin último). Mitigar: revertir a `paginate()` si se necesita total, o implementar conteos/UX alternativos.
- **Procesar (2 requests)**: `enterProcessMode` + request diferido (`wire:init`) implica 2 roundtrips; si el segundo se cancela/falla, el usuario queda con skeleton. Mitigar: botón “Reintentar” + overlay NFR2 con cancelar.
- **Mover a WSL2**: ajustar workflow (paths, IDE, git); documentar setup para el equipo.

---

## 7) Checklist de validación (antes/después)

**Métricas obligatorias**
- `curl` p50/p90 `time_starttransfer` y `time_total`:
  - `/login` (cold 1x + warm 10x)
  - `/inventory/products` (Admin/Lector intercalado 10x)
  - `/pending-tasks`
- Livewire:
  - `/livewire/update` `InventorySearch` updates `"De"→"Del"→"Dell"` (p50/p90 de secuencia)
  - `/livewire/update` `PendingTaskShow::enterProcessMode` (p50/p90)
- DB:
  - `EXPLAIN ANALYZE` para queries críticas (búsqueda y products index) verificando `key`/scan.

**Criterio de éxito**
- Búsqueda por nombre: p90 “tipeo→resultado estable” <3s o, si >3s, feedback UX correcto (skeleton + progreso + cancelar) según NFR2.
