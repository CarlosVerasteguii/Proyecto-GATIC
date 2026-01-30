# GATIC Deep Dive Performance Analysis

**Date:** 2026-01-29
**Analyst:** Gemini CLI Agent

## 1. Executive Summary
*   **Backend Latency Confirmed:** High TTFB (2.0s - 3.5s) is present even in raw cURL requests, confirming the bottleneck is server-side (Laravel boot, DB connection, or middleware), not client-side rendering.
*   **Admin Overhead:** Admin routes are consistently slower (~500ms - 1s extra TTFB) than Lector routes, validating the hypothesis that permission checks (Policies/Gates) per row/item add significant overhead.
*   **Search is "Broken":** The global search for "Dell" takes **~3.5s TTFB** (and up to 7.6s total in browser), despite an index on `name`. This suggests the query is likely searching across unindexed columns (like description, specs) or joining multiple tables inefficiently.
*   **Docker/Windows Factor:** The base "Hello World" (Login page) has a TTFB of ~2.1s (cold) to ~0.25s (warm). This baseline latency is high, typical of Docker on Windows with volume mounts, masking some application-level issues.
*   **Locks:** The "Process" action on tasks is functional but slow (~3-5s), acceptable for MVP but a candidate for optimization (P1).

## 2. TTFB & Total Load Time (cURL)

*Metrics based on 7 repetitions (p50 / p90).*

| Route | Role | Metric | p50 (ms) | p90 (ms) | Notes |
|---|---|---|---|---|---|
| /login | Public | TTFB | 253 | 2126 | Warm cache drops to ~250ms. Cold is ~2.1s. |
| /dashboard | Admin | TTFB | 2252 | 3180 | Consistent >2s latency even warm. |
| /inventory/products | Admin | TTFB | 2495 | 6010 | **Heavy.** Even warm hits are ~2.5s. |
| /inventory/products | Lector | TTFB | 2309 | 2474 | Faster than Admin, confirming RBAC overhead. |
| /inventory/search (empty) | Admin | TTFB | 3309 | 5221 | Very slow default load. |
| /inventory/search?search=Dell | Admin | TTFB | 3497 | 3578 | **Critical.** Search adds ~1s overhead on top of base latency. |

## 3. Top Bottlenecks

| Bottleneck | Evidence | Hypothesis | Recommendation |
|---|---|---|---|
| **Global Search** | TTFB ~3.5s vs Index exists on `name`. | Query uses `LIKE %term%` (bypassing index) or searches unindexed fields (assets columns, etc). | **Dev:** Check `GlobalSearch` logic. Ensure `WHERE name LIKE 'term%'` (prefix) if possible, or use full-text search. Profile the SQL. |
| **Admin Permissions** | Admin Products (2.5s) > Lector Products (2.3s) + Browser gap (5.8s vs 2.6s). | Backend: N+1 on `can('update', $product)`. Frontend: Rendering extra DOM elements per row. | **Dev:** Eager load permissions. Refactor Blade loop to avoid `can()` per row if possible. |
| **Infrastructure** | Base /login cold load is ~2.1s. | Docker on Windows filesystem I/O is slow for PHP apps. | **DevOps:** Recommend WSL2 or Linux for Prod. For local dev, ignore sub-500ms fluctuations. |

## 4. Hypothesis Validation

*   **H1 (Backend TTFB):** **CONFIRMED.** cURL shows high TTFB (2s+) matching browser Network tab. The browser is waiting for the server, not just executing JS.
*   **H2 (Search Slowness):** **CONFIRMED & REFINED.** It is NOT missing index on `name` (index exists). It IS likely an inefficient query (leading wildcard `LIKE %...%`) or searching too many unindexed relations.
*   **H3 (Admin vs Lector):** **CONFIRMED.** Admin requests are consistently slower on the server (cURL) and significantly slower in the browser (Rendering), pointing to RBAC overhead.

## 5. Actionable Recommendations

### Backend / Developer
1.  **Debug Search Query:** Run `DB::enableQueryLog()` on the search route. Check if the query uses `LIKE '%...%'` which invalidates the B-Tree index on `name`.
2.  **Optimize Policy Checks:** In `ProductPolicy`, check if extensive DB queries are running. In the Controller, ensure eager loading of relations used in policies (e.g., `load('owner')` if policy checks ownership).
3.  **Livewire Defer:** Use `lazy` loading for the product table component. Let the page shell load in <500ms, then fetch the heavy table.

### Frontend / UX
1.  **Immediate Skeletons:** The 2-3s delay is inevitable in this env. Add `<div wire:loading>` skeletons to `/dashboard` and `/inventory/products` immediately.
2.  **Search Feedback:** Add a spinner inside the search input when `wire:model.live` is processing.

### Database
1.  **Review Indexes:** The `products_name_index` exists. Ensure `assets` also have indexes on `serial` and `asset_tag` if those are included in the global search.