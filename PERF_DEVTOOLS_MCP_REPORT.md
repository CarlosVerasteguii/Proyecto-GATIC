# PERF_DEVTOOLS_MCP_REPORT (Chrome DevTools MCP)

Fecha: 2026-02-01 (local)  
App: `http://localhost:8080` (Docker)  
Herramientas: **Chrome DevTools MCP** (sin `curl`) + JS Performance API

## 0) Preflight (MCP)

- `list_pages`: OK
- `new_page` → `https://developers.chrome.com`: OK
- Smoke trace (reload + autostop): `perf-artifacts/devtools-mcp/traces/smoke.json.gz`

## 1) Setup app

Comandos ejecutados:

```bash
docker compose -f gatic/compose.yaml up -d
docker compose -f gatic/compose.yaml exec -T laravel.test php artisan migrate:fresh --seed
```

Config para evitar overhead de logging:

- `gatic/.env`: `PERF_LOG=0`

Usuarios (password=`password`):

- `admin@gatic.local`
- `lector@gatic.local`
- `editor@gatic.local`
- `editor2@gatic.local`

## 2) Configuración de medición

- CPU throttling: none (DevTools)
- Network throttling: none (DevTools)
- Viewport: default (no override)
- Warm reloads: `navigate_page` type=reload con `ignoreCache=true` donde aplica
- Aislamiento de sesiones (cookies) por hostname loopback:
  - Admin: `http://localhost:8080`
  - Lector: `http://127.0.0.1:8080`
  - Editor: `http://127.0.0.2:8080`
  - Editor2: `http://127.0.0.3:8080`

## 3) Metodología

- Por escenario con n=10:
  - 1 warmup (no cuenta)
  - 10 repeticiones medidas
  - p50/p90 por **nearest-rank**:
    - `rank(p) = ceil(p * n)` (1-index)
    - p50 = elemento `ceil(0.5*n)`, p90 = elemento `ceil(0.9*n)` del vector ordenado
- Tiempos de navegación (A–D): `PerformanceNavigationTiming` via `evaluate_script`:

```js
() => {
  const nav = performance.getEntriesByType('navigation')[0];
  if (!nav) return { error: 'no navigation entry' };
  return {
    url: location.href,
    ttfb_ms: Math.round(nav.responseStart - nav.requestStart),
    total_ms: Math.round(nav.loadEventEnd - nav.startTime),
    domcontentloaded_ms: Math.round(nav.domContentLoadedEventEnd - nav.startTime),
    transfer_bytes: nav.transferSize ?? null,
  };
}
```

- Livewire (E, F): `PerformanceResourceTiming` del último `/livewire/update` tras la acción:

```js
() => {
  const r = performance.getEntriesByType('resource')
    .filter(e => String(e.name).includes('/livewire/update'))
    .slice(-1)[0];
  if (!r) return { error: 'no livewire resource entry' };
  return {
    name: r.name,
    ttfb_ms: Math.round(r.responseStart - r.requestStart),
    total_ms: Math.round(r.duration),
    transfer_bytes: r.transferSize ?? null,
  };
}
```

## 4) Resultados (p50/p90)

| Escenario | n | ttfb p50 (ms) | ttfb p90 (ms) | total p50 (ms) | total p90 (ms) | CSV |
|---|---:|---:|---:|---:|---:|---|
| A) `/login` COLD | 1 | 4681 | 4681 | 4866 | 4866 | `perf-artifacts/devtools-mcp/csv/A_login_cold.csv` |
| A) `/login` WARM | 10 | 2231 | 5319 | 2635 | 7347 | `perf-artifacts/devtools-mcp/csv/A_login_warm.csv` |
| B) `/inventory/products` Admin | 10 | 2275 | 4557 | 2607 | 6304 | `perf-artifacts/devtools-mcp/csv/B_products_admin.csv` |
| C) `/inventory/products` Lector | 10 | 2265 | 4494 | 2592 | 5986 | `perf-artifacts/devtools-mcp/csv/C_products_lector.csv` |
| D) `/inventory/search?q=Laptop` Admin | 10 | 1876 | 4647 | 2358 | 6353 | `perf-artifacts/devtools-mcp/csv/D_search_qs_admin.csv` |
| E) Livewire search submit (Admin) | 10 | 1978 | 5840 | 1988 | 5859 | `perf-artifacts/devtools-mcp/csv/E_livewire_search_submit.csv` |
| F) Livewire “Procesar” (Editor) | 10 | 2848 | 3269 | 2862 | 3283 | `perf-artifacts/devtools-mcp/csv/F_livewire_procesar.csv` |
| G) Locks concurrencia (Editor vs Editor2) | 1 | — | — | — | — | — |

## 5) Evidencia / Artefactos por escenario

### A) `/login` (localhost)

- CSV: `perf-artifacts/devtools-mcp/csv/A_login_cold.csv`, `perf-artifacts/devtools-mcp/csv/A_login_warm.csv`
- Traces: `perf-artifacts/devtools-mcp/traces/A_login_cold_run1.json.gz`, `perf-artifacts/devtools-mcp/traces/A_login_warm_run1.json.gz`, `perf-artifacts/devtools-mcp/traces/A_login_warm_run2.json.gz`, `perf-artifacts/devtools-mcp/traces/A_login_warm_run3.json.gz`
- Snapshot: `perf-artifacts/devtools-mcp/snapshots/A_login_cold.md`, `perf-artifacts/devtools-mcp/snapshots/A_login_warm_final.md`
- Screenshot: `perf-artifacts/devtools-mcp/screens/A_login_cold.png`, `perf-artifacts/devtools-mcp/screens/A_login_warm_final.png`

### B) `/inventory/products` Admin (localhost)

- CSV: `perf-artifacts/devtools-mcp/csv/B_products_admin.csv`
- Traces: `perf-artifacts/devtools-mcp/traces/B_products_admin_run1.json.gz`, `perf-artifacts/devtools-mcp/traces/B_products_admin_run2.json.gz`, `perf-artifacts/devtools-mcp/traces/B_products_admin_run3.json.gz`
- Headers (document, cookie/set-cookie redacted): `perf-artifacts/devtools-mcp/headers/B_run1.txt`, `perf-artifacts/devtools-mcp/headers/B_run2.txt`, `perf-artifacts/devtools-mcp/headers/B_run3.txt`
- Snapshot: `perf-artifacts/devtools-mcp/snapshots/B_products_admin_final.md`
- Screenshot: `perf-artifacts/devtools-mcp/screens/B_products_admin_final.png`

### C) `/inventory/products` Lector (127.0.0.1)

- CSV: `perf-artifacts/devtools-mcp/csv/C_products_lector.csv`
- Traces: `perf-artifacts/devtools-mcp/traces/C_products_lector_run1.json.gz`, `perf-artifacts/devtools-mcp/traces/C_products_lector_run2.json.gz`, `perf-artifacts/devtools-mcp/traces/C_products_lector_run3.json.gz`
- Snapshot: `perf-artifacts/devtools-mcp/snapshots/C_products_lector_final.md`
- Screenshot: `perf-artifacts/devtools-mcp/screens/C_products_lector_final.png`

### D) `/inventory/search?q=Laptop` Admin (localhost)

- CSV: `perf-artifacts/devtools-mcp/csv/D_search_qs_admin.csv`
- Traces: `perf-artifacts/devtools-mcp/traces/D_search_qs_admin_run1.json.gz`, `perf-artifacts/devtools-mcp/traces/D_search_qs_admin_run2.json.gz`, `perf-artifacts/devtools-mcp/traces/D_search_qs_admin_run3.json.gz`
- Snapshot: `perf-artifacts/devtools-mcp/snapshots/D_search_qs_admin_final.md`
- Screenshot: `perf-artifacts/devtools-mcp/screens/D_search_qs_admin_final.png`

### E) Livewire: InventorySearch submit (Admin, localhost)

- CSV: `perf-artifacts/devtools-mcp/csv/E_livewire_search_submit.csv`
- Traces: `perf-artifacts/devtools-mcp/traces/E_livewire_search_run1.json.gz`, `perf-artifacts/devtools-mcp/traces/E_livewire_search_run2.json.gz`, `perf-artifacts/devtools-mcp/traces/E_livewire_search_run3.json.gz`
- Snapshot: `perf-artifacts/devtools-mcp/snapshots/E_livewire_search_final.md`
- Screenshot: `perf-artifacts/devtools-mcp/screens/E_livewire_search_final.png`
- Nota de criterio de `wait_for`: se usó el texto visible **“Laptop Dell Latitude 5540”** como condición estable de “resultado cargado”.

### F) Livewire: PendingTask “Procesar” (Editor, 127.0.0.2)

- CSV: `perf-artifacts/devtools-mcp/csv/F_livewire_procesar.csv`
- Traces: `perf-artifacts/devtools-mcp/traces/F_livewire_procesar_run1.json.gz`, `perf-artifacts/devtools-mcp/traces/F_livewire_procesar_run2.json.gz`, `perf-artifacts/devtools-mcp/traces/F_livewire_procesar_run3.json.gz`
- Snapshot: `perf-artifacts/devtools-mcp/snapshots/F_livewire_procesar_final.md`
- Screenshot: `perf-artifacts/devtools-mcp/screens/F_livewire_procesar_final.png`

### G) Concurrencia de locks (Editor vs Editor2)

Evidencia (1 corrida, muy documentada):

- n=1 por instrucción del escenario (no se calcula p50/p90 ni CSV)
- Traces:
  - `perf-artifacts/devtools-mcp/traces/G_lock_editor_acquire_run1.json.gz`
  - `perf-artifacts/devtools-mcp/traces/G_lock_editor2_view_run1.json.gz`
  - `perf-artifacts/devtools-mcp/traces/G_lock_editor_release_run1.json.gz`
- Page 1 (Editor, lock holder):
  - Snapshot: `perf-artifacts/devtools-mcp/snapshots/G_lock_page1_editor.md`
  - Screenshot: `perf-artifacts/devtools-mcp/screens/G_lock_page1_editor.png`
- Page 2 (Editor2, bloqueado):
  - Snapshot: `perf-artifacts/devtools-mcp/snapshots/G_lock_page2_editor2.md`
  - Screenshot: `perf-artifacts/devtools-mcp/screens/G_lock_page2_editor2.png`
  - Headers (document, cookie/set-cookie redacted): `perf-artifacts/devtools-mcp/headers/G_page2_document.txt`

Qué vio el usuario 2 (Editor2) exactamente:

- Texto: **“Bloqueada por Editor User”**
- Texto: **“Solo lectura”**
- Botón: **“Procesar”** en estado **disabled** con descripción **“Bloqueada por otro usuario”**

## 6) Console

- Archivo: `perf-artifacts/devtools-mcp/console/console.json`
- Resultado: sin mensajes de consola en las páginas capturadas.

## 7) Top 5 hallazgos (basados en datos)

1. `A_login_warm` muestra cola alta: p90 total = **7347 ms**, vs p50 total = **2635 ms**.
2. `B_products_admin` y `C_products_lector` son muy similares en p50/p90 (TTFB y total), sugiriendo poca diferencia por rol en esta vista.
3. `D_search_qs_admin` mejora p50 vs `B_products_admin` (TTFB 1876 vs 2275; total 2358 vs 2607), pero mantiene p90 elevado (total 6353).
4. Livewire search submit (E) tiene alta variabilidad (p90 total 5859 ms vs p50 total 1988 ms).
5. Livewire “Procesar” (F) concentra su cola alta (p90 total 3283 ms) pero tiene outliers muy bajos (p.ej. 302–446 ms en el CSV), indicando variación fuerte entre runs.

## 8) Limitaciones reales (errores de tool)

Durante la captura se observaron timeouts de MCP (resuelto reiniciando Chrome con debugging):

```
tool call error: tool call failed for `chrome-devtools/take_screenshot`

Caused by:
    0: timed out awaiting tools/call after 60s
    1: deadline has elapsed
```

En el mismo evento, otros tools también devolvieron timeout:

```
tool call error: tool call failed for `chrome-devtools/list_pages`

Caused by:
    0: timed out awaiting tools/call after 60s
    1: deadline has elapsed
```

Recuperación aplicada (Windows):

```powershell
Get-Process chrome | Stop-Process -Force
"C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe" --remote-debugging-port=9222 --user-data-dir="%TEMP%\\chrome-profile-mcp"
```
