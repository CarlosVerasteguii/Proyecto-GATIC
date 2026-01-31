# PERF P0 Baseline (Before) — 2026-01-30

Scope: **Fase 1 = P0 (Quick Wins)**. Este archivo contiene **solo baseline (MEDIR ANTES)**.

## 1) Setup (repro)

```powershell
docker compose -f gatic/compose.yaml up -d
docker compose -f gatic/compose.yaml exec -T laravel.test php artisan migrate:fresh --seed
```

App: `http://localhost:8080` (APP_PORT=8080).

Usuarios seeded:
- Admin: `admin@gatic.local` / `password`
- Lector: `lector@gatic.local` / `password`

## 2) Metodologia (Measure → Analyze)

### Percentiles
- `p50/p90`: **nearest-rank** (n=10; p90 = 9th valor ordenado cuando n=10).
- Unidades: ms (convertido desde segundos de `curl`).

### HTTP (cURL)
- Comando base:
  - `TTFB` = `time_starttransfer`
  - `Total` = `time_total`

### Livewire (medicion real /livewire/update)
- Se extrae `wire:snapshot` + `data-csrf` desde el HTML, y se POSTea a `/livewire/update`.
- Se hace **1 warmup** (no contado) y luego **n=10**.
- `InventorySearch`: se mide la **secuencia** `"De" -> "Del" -> "Dell"` como **suma de 3 updates** por repeticion.
- `PendingTaskShow`: se mide `enterProcessMode` (1 request). Para dejar el sistema listo para la siguiente repeticion se llama `exitProcessMode` (no incluido en las metricas).

## 3) Resultados — HTTP (cURL)

### /login
- Cold (n=1, despues de `docker compose -f gatic/compose.yaml restart laravel.test`):
  - TTFB: **6593ms**
  - Total: **6607ms**
- Warm (n=10):
  - TTFB p50/p90: **272ms / 2088ms**
  - Total p50/p90: **273ms / 2097ms**

### /inventory/products (Admin vs Lector; interleaved; n=10 por rol)

Admin:
- TTFB p50/p90: **2882ms / 3233ms**
- Total p50/p90: **2889ms / 3242ms**

Lector:
- TTFB p50/p90: **2793ms / 3721ms**
- Total p50/p90: **2802ms / 3732ms**

### /pending-tasks (Admin; n=10)
- TTFB p50/p90: **2772ms / 2926ms**
- Total p50/p90: **2781ms / 2934ms**

## 4) Resultados — Livewire

### InventorySearch — secuencia "De" -> "Del" -> "Dell" (suma 3 updates; n=10)
- TTFB p50/p90: **2692ms / 8176ms**
- Total p50/p90: **2700ms / 8210ms**

### PendingTaskShow — enterProcessMode (n=10)
- TTFB p50/p90: **750ms / 2804ms**
- Total p50/p90: **752ms / 2816ms**

## 5) DB — SQL real (captura via query log)

### 5.1 SearchInventory::execute("Dell")

```sql
select * from `assets` where `asset_tag` = ? and `assets`.`deleted_at` is null limit 1
-- bindings: ["DELL"]

select * from `assets` where `serial` = ? and `assets`.`deleted_at` is null order by `serial` asc
-- bindings: ["Dell"]

select * from `products` where name like ? escape '\\' and `products`.`deleted_at` is null order by `name` asc limit 20
-- bindings: ["%Dell%"]

select * from `assets` where (serial like ? escape '\\' or asset_tag like ? escape '\\') and `assets`.`deleted_at` is null order by `serial` asc limit 20
-- bindings: ["%Dell%","%Dell%"]
```

### 5.2 ProductsIndex (sin filtros; paginate)

```sql
select count(*) as aggregate
from `products`
left join `categories` as `categories_for_counts`
  on `categories_for_counts`.`id` = `products`.`category_id`
 and `categories_for_counts`.`deleted_at` is null
where `products`.`deleted_at` is null

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
-- bindings: ["Retirado", true, "Asignado", "Prestado", "Pendiente de Retiro", true]
```

## 6) DB — EXPLAIN ANALYZE (MySQL 8.x local)

### 6.1 products.name LIKE "%Dell%" (leading wildcard)

```sql
EXPLAIN ANALYZE
select * from products
where name like '%Dell%' escape '\\' and products.deleted_at is null
order by name asc
limit 20;
```

Output:

```text
*************************** 1. row ***************************
EXPLAIN: -> Limit: 20 row(s)  (cost=0.35 rows=1) (actual time=0.0794..0.0796 rows=1 loops=1)
    -> Sort: products.`name`, limit input to 20 row(s) per chunk  (cost=0.35 rows=1) (actual time=0.0788..0.0788 rows=1 loops=1)
        -> Filter: ((products.`name` like '%Dell%' escape '\\') and (products.deleted_at is null))  (cost=0.35 rows=1) (actual time=0.036..0.0414 rows=1 loops=1)
            -> Table scan on products  (cost=0.35 rows=1) (actual time=0.0315..0.0367 rows=1 loops=1)
```

### 6.2 assets.serial exact (sin indice usable; leftmost prefix)

```sql
EXPLAIN ANALYZE
select * from assets
where serial = 'SN-DEMO-001' and assets.deleted_at is null
order by serial asc;
```

Output:

```text
*************************** 1. row ***************************
EXPLAIN: -> Filter: ((assets.`serial` = 'SN-DEMO-001') and (assets.deleted_at is null))  (cost=0.75 rows=1) (actual time=0.0312..0.0376 rows=1 loops=1)
    -> Table scan on assets  (cost=0.75 rows=5) (actual time=0.026..0.0311 rows=5 loops=1)
```

### 6.3 assets serial/tag LIKE "%Dell%" (leading wildcard)

```sql
EXPLAIN ANALYZE
select * from assets
where (serial like '%Dell%' escape '\\' or asset_tag like '%Dell%' escape '\\')
  and assets.deleted_at is null
order by serial asc
limit 20;
```

Output:

```text
*************************** 1. row ***************************
EXPLAIN: -> Limit: 20 row(s)  (cost=0.75 rows=5) (actual time=0.098..0.098 rows=0 loops=1)
    -> Sort: assets.`serial`, limit input to 20 row(s) per chunk  (cost=0.75 rows=5) (actual time=0.0973..0.0973 rows=0 loops=1)
        -> Filter: (((assets.`serial` like '%Dell%' escape '\\') or (assets.asset_tag like '%Dell%' escape '\\')) and (assets.deleted_at is null))  (cost=0.75 rows=5) (actual time=0.0927..0.0927 rows=0 loops=1)
            -> Table scan on assets  (cost=0.75 rows=5) (actual time=0.0819..0.0873 rows=5 loops=1)
```

### 6.4 ProductsIndex query (select + COUNT)

Select (con subqueries dependientes):

```text
*************************** 1. row ***************************
EXPLAIN: -> Limit: 15 row(s)  (cost=0.7 rows=1) (actual time=0.0959..0.0965 rows=1 loops=1)
    -> Nested loop left join  (cost=0.7 rows=1) (actual time=0.0949..0.0954 rows=1 loops=1)
        -> Sort: products.`name`  (cost=0.35 rows=1) (actual time=0.0648..0.0648 rows=1 loops=1)
            -> Filter: (products.deleted_at is null)  (cost=0.35 rows=1) (actual time=0.0281..0.0325 rows=1 loops=1)
                -> Table scan on products  (cost=0.35 rows=1) (actual time=0.0269..0.0313 rows=1 loops=1)
        -> Filter: (categories_for_counts.deleted_at is null)  (cost=0.35 rows=1) (actual time=0.0291..0.0293 rows=1 loops=1)
            -> Single-row index lookup on categories_for_counts using PRIMARY (id=products.category_id)  (cost=0.35 rows=1) (actual time=0.0122..0.0123 rows=1 loops=1)
-> Select #2 (subquery in projection; dependent)
    -> Aggregate: count(0)  (cost=0.85 rows=1) (actual time=0.0262..0.0263 rows=1 loops=1)
        -> Filter: ((assets.product_id = products.id) and (assets.`status` <> 'Retirado') and (categories_for_counts.is_serialized = 1) and (assets.deleted_at is null))  (cost=0.75 rows=1) (actual time=0.0173..0.0229 rows=4 loops=1)
            -> Table scan on assets  (cost=0.75 rows=5) (actual time=0.013..0.0169 rows=5 loops=1)
-> Select #3 (subquery in projection; dependent)
    -> Aggregate: count(0)  (cost=0.85 rows=1) (actual time=0.0545..0.0546 rows=1 loops=1)
        -> Filter: ((assets.product_id = products.id) and (assets.`status` in ('Asignado','Prestado','Pendiente de Retiro')) and (categories_for_counts.is_serialized = 1) and (assets.deleted_at is null))  (cost=0.75 rows=1) (actual time=0.0503..0.0535 rows=3 loops=1)
            -> Table scan on assets  (cost=0.75 rows=5) (actual time=0.0463..0.0487 rows=5 loops=1)
```

COUNT extra (paginate):

```text
*************************** 1. row ***************************
EXPLAIN: -> Aggregate: count(0)  (cost=0.8 rows=1) (actual time=0.0675..0.0675 rows=1 loops=1)
    -> Nested loop left join  (cost=0.7 rows=1) (actual time=0.0608..0.0646 rows=1 loops=1)
        -> Filter: (products.deleted_at is null)  (cost=0.35 rows=1) (actual time=0.0355..0.039 rows=1 loops=1)
            -> Table scan on products  (cost=0.35 rows=1) (actual time=0.0347..0.038 rows=1 loops=1)
        -> Filter: (categories_for_counts.deleted_at is null)  (cost=0.35 rows=1) (actual time=0.0243..0.0244 rows=1 loops=1)
            -> Single-row index lookup on categories_for_counts using PRIMARY (id=products.category_id)  (cost=0.35 rows=1) (actual time=0.0104..0.0104 rows=1 loops=1)
```

Notes:
- El dataset demo es chico (planner elige scans), pero el punto aqui es **confirmar el plan** y dejar evidencia para comparar despues:
  - `LIKE '%term%'` => no usa indice B-Tree
  - `UNIQUE(product_id, serial)` no ayuda a `WHERE serial = ?` (regla leftmost prefix)
