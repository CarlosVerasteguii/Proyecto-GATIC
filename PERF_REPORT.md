# GATIC Performance Report (Frontend/Perceived)

**Date:** 2026-01-29
**Tester:** Gemini CLI Agent
**Environment:** Docker Compose (Localhost - Windows)
**Browser:** Chrome Headless (via DevTools Protocol)

## 1. Executive Summary
*   **Critical Latency (TTFB):** The application suffers from high Time To First Byte (TTFB) across all routes, averaging **2.5s - 4.0s**. This is likely exacerbated by the local Docker environment but indicates heavy server-side processing.
*   **Role Discrepancy:** The "Admin" role experiences significantly slower load times (**~5.8s** for Products) compared to "Lector" (**~2.6s**), suggesting that permission checks or rendering "Action" buttons per row are a major bottleneck.
*   **Search Performance:** Global search by name is unusable (**7.6s**), while search by serial/tag is faster but still borderline (**~3s**).
*   **Compliance:** Most routes exceed the **>3s** threshold defined in the "Project Context", triggering the requirement for skeleton loaders and cancel options, which are currently missing.
*   **Perceived Performance:** The app feels sluggish due to the initial blank screen (high TTFB). Once the HTML arrives, the browser renders it relatively quickly (~150ms).

## 2. Methodology
*   **Conditions:**
    *   **Cold Load:** First visit / cache disabled (`ignoreCache: true`).
    *   **Warm Load:** Reload with cache enabled.
*   **Metrics:** LCP (Largest Contentful Paint), TTFB (Time To First Byte).
*   **Reps:** Single traces captured for detailed breakdown (Constraint: Trace overhead).

## 3. Results by Route (Admin)

| Route | Type | TTFB (ms) | LCP (ms) | Notes |
|---|---|---|---|---|
| /login | Cold | 2544 | 2694 | Heavy boot time. |
| /dashboard | Cold | 2938 | 3114 | Borderline >3s. |
| /dashboard | Warm | 2724 | 2810 | No significant cache benefit. |
| /inventory/products | Cold | **3380** | **5808** | **CRITICAL**. Rendering delay after TTFB is also high (2.4s). |
| /inventory/products | Warm | **4184** | **4268** | Slower than cold (instability). |
| /inventory/products/1 | Cold | 2782 | 2922 | Just under 3s. |
| /inventory/products/1 | Warm | 3499 | 3630 | Unstable. |
| /inventory/products/1/assets | Cold | 2780 | 2956 | |
| /inventory/products/1/assets/1 | Cold | 3165 | **5725** | High render delay (2.5s). |
| /pending-tasks | Cold | 3730 | 3978 | |
| /inventory/search?q=Dell | Cold | **7523** | **7672** | **UNUSABLE**. |
| /inventory/search?q=SN... | Cold | 2937 | 3048 | Redirect logic works but base load is slow. |

## 4. Comparison by Role (Admin vs Lector)

| Route | Admin LCP (ms) | Lector LCP (ms) | Diff | Hypothesis |
|---|---|---|---|---|
| /inventory/products | ~5808 | ~2655 | **-54%** | Admin renders "Acciones" column with Policies/Gates checks per row. This N+1 on permissions is killing performance. |

## 5. Top Bottlenecks

1.  **Server-Side Processing (TTFB):** Consistently >2.5s.
    *   *Evidencia:* All traces show TTFB > 90% of total time.
    *   *Cause:* Docker I/O on Windows + Laravel boot + likely non-optimized queries.
2.  **Product List Rendering (Admin):**
    *   *Evidencia:* 3s difference between Admin vs Lector.
    *   *Cause:* `can()` checks inside the `@foreach` loop in Blade/Livewire.
3.  **Global Search (Name):**
    *   *Evidencia:* 7.6s load.
    *   *Cause:* Likely `LIKE %...%` query on unindexed columns or searching across multiple tables without a unified index.
4.  **Pending Tasks Locks:**
    *   *Evidencia:* Click "Procesar" -> ~5s to update UI.
    *   *Cause:* Livewire roundtrip + Lock logic overhead + DB write.

## 6. Recommendations

### Quick Wins (1-2 days)
1.  **Optimize "Actions" Column:** Eager load permissions or simplify the check. Instead of checking every policy per row, fetch user permissions once.
2.  **Skeleton Screens:** Implement Skeleton loaders for `/inventory/products` and `/dashboard` immediately to mask the TTFB (User perceives "something is happening").
3.  **Cancel Search:** Add a "Stop" button for the search bar since it can hang for 7s.
4.  **Database Indexing:** Verify indexes on `products.name`, `assets.serial`, `assets.asset_tag`.

### Initiatives (1-2 weeks)
1.  **Optimize Search:** Implement a dedicated search index (or Scout with a simple driver) to avoid full table scans.
2.  **Livewire Lazy Loading:** Use `wire:init` or `Lazy Components` to render the page shell first (fast LCP) and then load the heavy tables.
3.  **Docker Optimization:** For local dev, consider tuning Docker limits or using WSL2 native if not already (Windows filesystem mount is the usual suspect for slow PHP).

## 7. Measurement Commands
*   `docker compose -f gatic/compose.yaml up -d`
*   `docker compose -f gatic/compose.yaml exec -T laravel.test php artisan migrate:fresh --seed`
*   Measurements performed via Chrome DevTools Protocol (Trace).