# FP-03 — Acciones rápidas tipo “carrito” (captura mínima) para Carga/Retiros

Fecha: 2026-02-08

## Objetivo (MVP)

Permitir que **Admin/Editor** registren una intención de operación (“Carga rápida” / “Retiro rápido”) con **datos mínimos** para no frenar la operación diaria, guardándola como **Tarea Pendiente** para completar/procesar después.

## Estado actual (repositorio)

Módulo de Tareas Pendientes:

- Rutas: `pending-tasks.*` protegidas por `can:inventory.manage`.
- Modelo raíz: `App\Models\PendingTask` (`type`, `description`, `status`, `creator_user_id`, locks).
- Renglones: `App\Models\PendingTaskLine` (requiere `product_id`, `employee_id`, `note`, y `line_type` serialized/quantity).
- Flujo actual:
  - Crear tarea (Draft) → agregar renglones → marcar “Listo” → “Procesar” (lock + finalize).
- Invariante actual:
  - `MarkTaskAsReady` exige al menos 1 renglón pendiente.

Limitación: la “captura mínima” de FP-03 NO encaja con `PendingTaskLine` (requiere empleado/nota/producto) y además FP-03 permite **placeholder de producto** (solo nombre), que no existe en `products`.

## Enfoque técnico propuesto (primera versión usable)

### 1) Persistencia

- Reusar `PendingTask` agregando un campo `payload` (JSON, nullable) para guardar la captura mínima normalizada.
- Crear tareas como `PendingTaskStatus::Draft` para no romper el invariante de “Listo ⇒ tiene renglones”.
- `type`:
  - Carga rápida → `PendingTaskType::StockIn`
  - Retiro rápido → `PendingTaskType::Retirement`

Estructura sugerida de `payload` (normalizada):

```json
{
  "schema": "fp03.quick_capture",
  "version": 1,
  "kind": "quick_stock_in",
  "product": {
    "mode": "existing|placeholder",
    "id": 123,
    "name": "Laptop Dell ...",
    "is_serialized": true
  },
  "items": {
    "type": "serialized|quantity",
    "serials": ["ABC123", "ABC124"],
    "quantity": 10
  },
  "reason": "obligatorio en quick_retirement",
  "note": "opcional"
}
```

Para retiro rápido: `kind = quick_retirement` y `items.type` define si es “por seriales” o “producto+cantidad”.

### 2) UI / Entry points

- Agregar botones visibles para `inventory.manage`:
  - Dashboard: `Carga rápida` / `Retiro rápido` (modales).
  - Tareas pendientes (index): mismos botones (modales).

### 3) Livewire (modales)

- Modal “Carga rápida”:
  - Producto existente (select) o placeholder (texto).
  - Serializado sí/no (auto si producto existente).
  - Si serializado: seriales (1 por línea, límite configurable).
  - Si no serializado: cantidad (> 0).
  - Nota opcional.
- Modal “Retiro rápido”:
  - Modo: por seriales o por producto + cantidad.
  - Seriales (1 por línea) o producto (select) + cantidad (> 0).
  - Motivo de retiro (obligatorio).
  - Nota opcional.

Validación server-side:

- Límite de seriales: `config('gatic.pending_tasks.bulk_paste.max_lines')`.
- Cantidad: entero `> 0`.
- Producto existente: debe existir y no estar soft-deleted.
- Consistencia serializado vs producto:
  - Si producto existente es serializado, no permitir modo “por cantidad”.
  - Si producto existente NO es serializado, no permitir modo “seriales”.

### 4) UX y compatibilidad con el módulo actual

- En `PendingTaskShow`, si la tarea tiene `payload.schema = fp03.quick_capture`:
  - Mostrar card “Captura rápida” con el detalle.
  - Ocultar acciones de renglones/procesar (esta versión solo captura).
- Mensajes de éxito/error con toasts Livewire (`ui:toast`).

## Fuera de alcance (explícito)

- Convertir la captura mínima a renglones automáticamente.
- Implementar el “formulario completo” de procesamiento de cargas/retiros.
- Tests y validación final (pendiente por instrucción actual).
