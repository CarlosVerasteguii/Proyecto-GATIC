# Patrones UI (GATIC)

## Toasts globales

- El contenedor global vive en `resources/views/layouts/*.blade.php` via `<x-ui.toast-container />`.
- El JS escucha eventos de Livewire `ui:toast` y también convierte flashes de sesión a toasts.

### Livewire -> Toast

En cualquier componente Livewire:

- Usa el trait `App\Livewire\Concerns\InteractsWithToasts`
- Llama a: `toastSuccess()`, `toastError()`, `toastInfo()`, `toastWarning()`

Ejemplo (conceptual):

```php
$this->toastSuccess('Guardado correctamente.');
```

### Toast con acción ("Deshacer")

Para una acción reversible (best-effort), despacha un toast con `action`:

- `label`: texto del botón (ej. "Deshacer")
- `event`: evento que recibirá el componente (ej. `ui:undo-toggle`)
- `params`: payload serializable

En el componente, atiende el evento con `#[On('ui:undo-toggle')]`.

## Errores inesperados con `error_id`

### Alerta reutilizable + copiar

Usa el componente Blade:

- `<x-ui.error-alert-with-id :error-id="$errorId" />`

Esto renderiza mensaje humano + `error_id` y un botón "Copiar" (sin dependencias nuevas).

### Livewire: evitar modal por defecto en 500

Para requests Livewire que regresan JSON `{ message, error_id }` con status `>= 500`, el frontend:

- evita el modal por defecto (solo cuando existe `error_id`)
- muestra un toast consistente con el `error_id`

## Operaciones lentas (>3s) + Cancelar

### Objetivo

- Mostrar loader/skeleton solo si la operación tarda más de lo normal.
- Permitir "Cancelar" (abort del request) sin limpiar resultados previos.

### Uso

1) Envuelve el contenido que NO quieres perder en un contenedor `position-relative`.
2) Inserta `<x-ui.long-request />` dentro de ese contenedor.

El JS:

- Detecta requests Livewire y, si el componente tiene el overlay, lo muestra después del umbral.
- Al presionar "Cancelar", aborta el request y oculta el overlay.

### Opcional: limitar a una acción específica

Si un mismo componente tiene varias acciones (por ejemplo `wire:poll.visible`), puedes evitar que el overlay aparezca en otras llamadas limitándolo por método:

```blade
<x-ui.long-request target="slowOperation" />
```

- `target` acepta una lista separada por comas (ej. `target="searchUsers,refreshReport"`).
- Si `target` está definido, el overlay solo se activa cuando el request incluye alguno de esos métodos.

## Skeletons

Usa `<x-ui.skeleton />` para placeholders alineados a Bootstrap:

- Líneas: `<x-ui.skeleton :lines="3" />`
- Bloque: `<x-ui.skeleton variant="block" height="6rem" />`

## Polling + "Actualizado hace Xs"

Usa `<x-ui.freshness-indicator :updated-at="$lastUpdatedAtIso" />` y actualiza `lastUpdatedAtIso` cada vez que llegue data nueva (por ejemplo, en el método llamado por `wire:poll.visible`).
