# Features pendientes (backlog local)

Este folder es para documentar ideas/pendientes antes de meterlas al backlog formal (BMAD / issues).

## Lista priorizada

### FP-01 — Acciones rápidas en búsqueda (Activos)

- **Objetivo:** poder ejecutar movimientos (Asignar / Prestar / Devolver / Desasignar) desde los resultados de búsqueda, sin navegar primero al detalle.
- **Dónde:** `Inventario → Búsqueda → Activos`.
- **Notas:** debe respetar RBAC (`inventory.manage`) y los estados permitidos.
- **Estado:** ✅ implementado.

### FP-02 — Mejor “volver a” (returnTo) en movimientos

- **Objetivo:** cuando entro a un movimiento desde listados/búsqueda, al guardar regresar al contexto anterior (misma búsqueda/filtros/página).
- **Notas:** `returnTo` debe ser **seguro** (solo paths relativos) para evitar open-redirect.
- **Estado:** ✅ implementado (cuando el link incluye `returnTo`).

### FP-03 — Acciones rápidas tipo “carrito” (captura mínima) para Carga/Retiros

- **Objetivo:** permitir crear tareas pendientes con **mínimos datos** (sin interrumpir operación diaria).
- **Inspiración:** el repo anterior tenía “Carga Rápida / Retiro Rápido” (modal + placeholders + procesamiento posterior).

### FP-04 — Widget de “Acciones rápidas” en Dashboard

- **Objetivo:** atajos visibles para: crear producto, crear activo, abrir búsqueda, iniciar carga rápida, etc.

### FP-05 — Acciones masivas en tablas

- **Objetivo:** seleccionar múltiples activos y ejecutar un movimiento (ej. marcar Pendiente de Retiro / asignar por lote).

### FP-06 — “Deshacer” en movimientos (Undo)

- **Objetivo:** después de un movimiento exitoso, mostrar toast con botón **Deshacer** (en ventana corta de tiempo).
- **Notas:** ya existe patrón de UI para undo; falta aplicarlo a movimientos de inventario.
