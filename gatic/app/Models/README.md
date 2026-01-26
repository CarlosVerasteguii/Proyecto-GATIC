# Models (Eloquent) — notas de dominio y convenciones

Este documento resume decisiones prácticas del dominio y ayuda a mantener consistencia al tocar modelos/consultas.

## Inventario: semántica de “disponible” (serializado)

Fuente de verdad:

- Estados del activo: `gatic/app/Models/Asset.php`
- “No disponibles”: `Asset::UNAVAILABLE_STATUSES`
- Cálculo actual en UI:
  - `gatic/app/Livewire/Inventory/Products/ProductsIndex.php`
  - `gatic/app/Livewire/Inventory/Products/ProductShow.php`

Regla (MVP):

- `total` = conteo de activos **excluyendo** `Retirado`
- `unavailable` = conteo de activos en `Asignado`, `Prestado`, `Pendiente de Retiro`
- `available` = `total - unavailable` (mínimo 0)

Notas:

- `Retirado` se muestra en desglose, pero no forma parte de los totales operativos.
- En `AssetsIndex`, si el filtro de estado está en `all`, se excluye `Retirado` por defecto.

## Inventario: productos por cantidad

Fuente de verdad:

- Campo: `products.qty_total` (se usa como `total` y `available` en `ProductShow`).

En MVP, los “productos por cantidad” no tienen estado de disponibilidad por pieza; solo se maneja stock agregado.

## Soft deletes (papelera)

Varios modelos usan `SoftDeletes` (p. ej. `Product`, `Asset`, `Employee`, catálogos).

Implicaciones:

- Las consultas normales excluyen registros con `deleted_at`.
- Admin puede restaurar/purgar desde Papelera (ver `gatic/app/Actions/Trash/*`).

## Notas y adjuntos (morph)

Entidades con evidencia:

- `Product`, `Asset`, `Employee` exponen `notes()` y `attachments()` como relaciones morfológicas.

Puntos clave:

- Adjuntos se guardan con ruta UUID en disk `local` (privado).
- Descargas pasan por controller con autorización (no exponer links directos a storage).

## PendingTask: helpers de lock y N+1

- `PendingTask::hasActiveLock()` define el lock activo con `expires_at > now()`.
- `PendingTask::getLinesCountAttribute()` dispara query por acceso: preferir `withCount('lines')` + `lines_count`.

