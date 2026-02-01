# QA Full Run — Auditoría de Evidencia (GATIC)

**Fecha:** 2026-01-31  
**Objetivo:** aclarar qué puntos del `QA_FULL_RUN_REPORT.md` están **verificados manualmente** vs **inferidos** (UI presente, schema, o tests).

> Importante: el objetivo original era “sin skips”. Para cumplirlo de forma estricta, los ítems “inferidos” deben repetirse con **interacción real** (crear/editar/confirmar cambios) y registrar evidencia (capturas/logs/DB).

---

## 1) Confirmado con alta evidencia

- **Tests automatizados:** `php artisan test` → **598 passed (1499 assertions)**. (Ejecución completa observada.)
- **Search (server-side):** `EXPLAIN ANALYZE` en `PERF_P1_AFTER.md` confirma FULLTEXT usando `products_name_fulltext`.

---

## 2) Secciones con evidencia incompleta (marcadas PASS pero requieren manual real)

### 3) Catalogs (CRUD)
En el reporte se marca PASS, pero la evidencia escrita es “UI accesible / botones visibles / wire:click existe”.
**Para considerarlo verificado:** crear/editar/eliminar (soft-delete) 1 entidad por catálogo y confirmar resultado en tabla (y/o DB).

### 4) Inventory - Products
Se marca PASS por “/create existe” y “UI soporta ambos tipos”.
**Falta evidencia:** submit real, validaciones, persistencia, y ver reflejado en listado/detalle.

### 5) Inventory - Assets
Se marca PASS por seed (“BD contiene assets…”).
**Falta evidencia:** crear activo desde UI, probar conflicto de unicidad desde UI y ver mensaje.

### 7) Movements (Assign/Loan/Return)
Se marca PASS por “botones visibles” + tests.
**Falta evidencia:** ejecutar movimientos reales, confirmar cambio de estado del asset y vínculo con empleado, y validar transición bloqueada con mensaje.

### 8) Employees
Se marca PASS por “UI disponible / empleado existe”.
**Falta evidencia:** crear o editar empleado desde UI y usarlo en un movimiento real.

### 9) Pending Tasks + Locks (concurrencia)
Se marca PASS pero el propio reporte indica que “requiere interacción simultánea en navegador”.
**Falta evidencia crítica:** abrir 2 sesiones (Editor y Editor2), tomar lock, validar bloqueo en el segundo usuario, y probar override Admin si existe.

### 10) Attachments
Se marca PASS por “UI muestra input file” + tests.
**Falta evidencia:** subir/descargar/eliminar un adjunto en UI y confirmar restricciones del Lector en UI y por URL directa.

### 11) Soft delete / Trash
Se marca PASS principalmente por tests.
**Falta evidencia:** al menos 1 flujo manual completo (soft-delete → papelera → restore → purge) con permisos.

### 12) Error Handling (ID)
Se marca PASS por tests.
**Falta evidencia:** provocar error en UI y observar ID + diferencia Admin vs no-Admin.

---

## 3) Performance UX — evidencia mejorable

El reporte registra 1 muestra por acción (ej. `/login` cold 5.05s).
**Para que sea auditable:** medir p50/p90 (n=10) por rol y guardar método (curl vs DevTools vs perf.log).

Recomendación: ejecutar el flujo con Chrome DevTools (HAR + trace) y correlacionar con `X-Perf-Id` / `gatic/storage/logs/perf.log`.

---

## 4) Próximo paso recomendado (si quieres “sin skips” estricto)

1) Repetir manualmente las secciones 3,4,5,7,8,9,10,11,12 con evidencia.
2) Mantener tests como respaldo, pero no como sustituto del flujo end-to-end.
3) Rehacer Performance UX con p50/p90 y artifacts (HAR/trace).

