# RBAC (MVP) - GATIC

Fuente de verdad: `docsBmad/project-context.md` (roles fijos) + implementación en `gatic/app/Providers/AuthServiceProvider.php`.

## Roles

- **Admin**: acceso total (override server-side vía `Gate::before`).
- **Editor**: operación (inventario/activos) sin acciones admin-only.
- **Lector**: solo lectura (sin acciones destructivas ni adjuntos en MVP).

## Gates (nombres y alcance)

| Gate | Admin | Editor | Lector | Uso previsto |
| --- | --- | --- | --- | --- |
| `admin-only` | Sí | No | No | Acciones exclusivas de Admin |
| `users.manage` | Sí | No | No | Gestión de usuarios (Story 1.6) |
| `catalogs.manage` | Sí | Sí | No | CRUD de catálogos (Epic 2) |
| `inventory.view` | Sí | Sí | Sí | Ver módulos de inventario (Epic 3+) |
| `inventory.manage` | Sí | Sí | No | Crear/editar inventario (Epic 3+) |
| `attachments.manage` | Sí | Sí | No | Alta/baja de adjuntos (Epic 8) |
| `attachments.view` | Sí | Sí | No | Ver/descargar adjuntos (Epic 8) |

## Reglas de aplicación

- **Siempre server-side**: usar `can:` middleware en rutas y `Gate::authorize(...)`/`$this->authorize(...)` en acciones Livewire.
- **UI como defensa en profundidad**: ocultar/inhabilitar acciones con `@can(...)`, pero nunca confiar solo en la UI.
