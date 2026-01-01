# Story 2.4: Soft-delete y restauración de catálogos

Status: done

Story Key: 2-4-soft-delete-y-restauracion-de-catalogos  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Backlog (fuente de verdad): `_bmad-output/project-planning-artifacts/epics.md` (Story 2.4)  
Fuentes: `_bmad-output/project-planning-artifacts/epics.md` (Story 2.4), `_bmad-output/prd.md` (FR7), `_bmad-output/architecture.md` (Epic 2 + estructura Livewire), `docsBmad/project-context.md` (política de soft-delete), `docsBmad/rbac.md` (gates), `project-context.md` (reglas de implementación), `gatic/docs/ui-patterns.md` (toasts + undo), `_bmad-output/implementation-artifacts/2-1-gestionar-categorias-incluye-serializado-asset-tag.md`, `_bmad-output/implementation-artifacts/2-2-gestionar-marcas.md`, `_bmad-output/implementation-artifacts/2-3-gestionar-ubicaciones.md`.

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Admin/Editor,  
I want eliminar catálogos solo mediante soft-delete (sin borrado físico) y poder restaurarlos,  
so that se mantenga integridad referencial y trazabilidad en el inventario (FR7).

Catálogos incluidos en esta story: **Marcas**, **Categorías**, **Ubicaciones**.

## Decision Log (reglas confirmadas)

- Autorización:
  - CRUD y soft-delete de catálogos: gate `catalogs.manage` (Admin/Editor) con defensa en profundidad (middleware `can:` + `Gate::authorize()` en acciones Livewire).
  - Restauración (papelera): gate `admin-only` (solo Admin) [Source: `docsBmad/rbac.md`].
- Convención de idioma: identificadores de código/DB/rutas en inglés; copy/UI en español [Source: `project-context.md`].
- Soft-delete: borrar = `deleted_at` (retención indefinida; purga/“vaciar papelera” queda para story futura) [Source: `docsBmad/project-context.md`].
- Unicidad: `name` es único (case + acento-insensible) **incluyendo eliminados**; si un catálogo fue eliminado, no se “re-crea”, se **restaura** o se renombra [Source: stories 2.1–2.3].
- Integridad referencial (FR7): antes de permitir soft-delete, el sistema debe bloquear si el catálogo está “en uso” (referenciado por inventario). La verificación debe ser **server-side** y reutilizable (no duplicar lógica por componente).
- UX: si se bloquea la eliminación por “en uso”, mostrar mensaje claro y consistente (toast/alert) sin 500.

## Acceptance Criteria

### AC1 - Acceso por rol (defensa en profundidad)

**Given** un usuario autenticado con rol Admin o Editor  
**When** navega a catálogos (Categorías / Marcas / Ubicaciones)  
**Then** puede listar/crear/editar y ejecutar “Eliminar” (soft-delete) donde aplique

**Given** un usuario autenticado con rol Lector  
**When** intenta acceder a rutas `/catalogs/*` o dispara acciones Livewire de catálogos  
**Then** el servidor bloquea la operación (403 o equivalente)

**Given** un usuario autenticado con rol Editor  
**When** intenta restaurar desde papelera  
**Then** el servidor bloquea (solo Admin puede restaurar)

### AC2 - Soft-delete (sin borrado físico) y ocultamiento

**Given** una Marca/Categoría/Ubicación que NO está en uso (no referenciada)  
**When** Admin/Editor ejecuta “Eliminar”  
**Then** el registro se marca como soft-deleted (se setea `deleted_at`, no se borra físicamente)  
**And** deja de aparecer en listados normales y en selects/listas donde aplique

### AC3 - Bloqueo por “en uso” (FR7)

**Given** una Marca/Categoría/Ubicación referenciada por registros del inventario (en uso)  
**When** Admin/Editor intenta eliminarla (soft-delete)  
**Then** el sistema bloquea la operación  
**And** muestra un mensaje claro indicando que el catálogo está en uso

### AC4 - Restauración (solo Admin)

**Given** un catálogo en soft-delete  
**When** Admin lo restaura desde la “Papelera de catálogos”  
**Then** el catálogo vuelve a estar disponible en el sistema  
**And** se conservan referencias e historial según política definida

## Tasks / Subtasks

1) Papelera de catálogos (UI + ruta) (AC: 1, 4)
- [x] Agregar ruta `GET /catalogs/trash` bajo middleware `auth`, `active`, `can:admin-only`
  - [x] Nombre sugerido: `catalogs.trash.index`
- [x] Implementar componente Livewire `App\Livewire\Catalogs\Trash\CatalogsTrash` y su view Blade
- [x] Mostrar tabs/secciones para: Categorías / Marcas / Ubicaciones
  - [x] Query por tipo: `onlyTrashed()->orderBy('name')` + búsqueda simple por nombre (escapar `%` y `_` como ya se hace en Marcas/Ubicaciones)
- [x] Acción “Restaurar” por fila (solo Admin) que ejecute `restore()` y muestre toast de éxito
  - [x] Confirmación antes de restaurar (patrón consistente con catálogos)

2) Navegación (AC: 1, 4)
- [x] Agregar link “Papelera” (solo Admin) en `gatic/resources/views/layouts/partials/sidebar-nav.blade.php`

3) Reglas de integridad (bloqueo por “en uso”) (AC: 3)
- [x] Implementar verificación server-side reutilizable para determinar si un catálogo está “en uso”
  - [x] Crear `App\Support\Catalogs\CatalogUsage` (o `app/Actions/Catalogs/*`) con métodos explícitos por tipo (brand/category/location)
  - [x] Estrategia recomendada (robusta y future-proof):
    - Si existen tablas de inventario, consultar si hay filas que referencian el `id` del catálogo
    - Si aún no existen (Epic 3 no implementado), retornar `false` sin romper (usar `Schema::hasTable(...)`)
  - [x] Mensaje UX cuando está en uso: “No se puede eliminar: el catálogo está en uso.”

4) Aplicar soft-delete con guardrails en catálogos (AC: 2, 3)
- [x] Marcas: actualizar `delete()` en `App\Livewire\Catalogs\Brands\BrandsIndex` para bloquear si “en uso”
- [x] Ubicaciones: actualizar `delete()` en `App\Livewire\Catalogs\Locations\LocationsIndex` para bloquear si “en uso”
- [x] Categorías:
  - [x] Agregar acción de eliminar (soft-delete) en el listado (actualmente Categorías no expone delete en UI)
  - [x] Aplicar la misma regla de bloqueo por “en uso”
  - [x] Alinear búsqueda con escape de comodines `LIKE` para consistencia (igual que Marcas/Ubicaciones)

5) Tests (mínimos y robustos) (AC: 1–4)
- [x] Papelera:
  - [x] Admin puede ver `/catalogs/trash` (200)
  - [x] Editor/Lector reciben 403
- [x] Restauración:
  - [x] Admin puede restaurar un registro soft-deleted (vuelve a aparecer en el index correspondiente)
  - [x] Editor NO puede restaurar (403 o equivalente)
- [x] Bloqueo por “en uso”:
  - [x] Testear `CatalogUsage` creando tablas mínimas en runtime del test (ej. `Schema::create('products', ...)`) e insertando una fila que referencie el catálogo, para que la eliminación quede bloqueada sin depender de Epic 3
- [x] Regresión: seguir pasando tests existentes de catálogos (2.1–2.3)

## Dev Notes

### Contexto del repo (Git intelligence)

- Modelos existentes con `SoftDeletes`: `App\Models\{Brand,Category,Location}`.
- Marcas y Ubicaciones ya implementan delete (soft-delete) en UI (`BrandsIndex::delete`, `LocationsIndex::delete`), pero sin restauración ni bloqueo por “en uso”.
- Categorías aún no expone eliminación en UI (solo listar/crear/editar).

### Requisitos técnicos (guardrails)

- Stack: Laravel `^11.31`, Livewire `^3.0`, MySQL 8 [Source: `gatic/composer.json`, `project-context.md`].
- Soft deletes (Laravel): usar `onlyTrashed()`, `withTrashed()`, `restore()`; evitar `forceDelete()` en esta story.
- Autorización: usar `Gate::authorize('catalogs.manage')` en acciones de CRUD y `Gate::authorize('admin-only')` para restauración.
- UX:
  - Reusar toasts globales via `App\Livewire\Concerns\InteractsWithToasts` [Source: `gatic/docs/ui-patterns.md`].
  - Cuando se bloquee por “en uso”, mostrar error claro; no permitir que la UI “parezca” que se eliminó.
  - Opcional (nice-to-have): toast con acción “Deshacer” post-eliminación (restauración best-effort para Admin), pero la fuente de verdad debe ser la “Papelera”.

### Estructura de archivos (no improvisar)

- Livewire:
  - `gatic/app/Livewire/Catalogs/Brands/BrandsIndex.php`
  - `gatic/app/Livewire/Catalogs/Categories/*`
  - `gatic/app/Livewire/Catalogs/Locations/LocationsIndex.php`
  - Nuevo: `gatic/app/Livewire/Catalogs/Trash/*`
- Views:
  - `gatic/resources/views/livewire/catalogs/*`
  - Nuevo: `gatic/resources/views/livewire/catalogs/trash/*`
- Rutas:
  - `gatic/routes/web.php`
- Soporte/Acciones (si se implementa el checker reutilizable):
  - `gatic/app/Support/Catalogs/*` o `gatic/app/Actions/Catalogs/*` (alineado a `_bmad-output/architecture.md`)

### Project Structure Notes

- Mantener consistencia con el módulo `Catalogs` (route → Livewire, sin controllers salvo bordes).
- Evitar duplicar lógica de “en uso” dentro de cada componente: centralizar.

### References

- Backlog: `_bmad-output/project-planning-artifacts/epics.md` (Story 2.4).
- FR7: `_bmad-output/prd.md`.
- Arquitectura/estructura: `_bmad-output/architecture.md`.
- Política soft-delete: `docsBmad/project-context.md`, `project-context.md`.
- RBAC: `docsBmad/rbac.md`, `gatic/app/Providers/AuthServiceProvider.php`.
- Patrones UI: `gatic/docs/ui-patterns.md`.
- Implementaciones previas: `_bmad-output/implementation-artifacts/2-1-gestionar-categorias-incluye-serializado-asset-tag.md`, `_bmad-output/implementation-artifacts/2-2-gestionar-marcas.md`, `_bmad-output/implementation-artifacts/2-3-gestionar-ubicaciones.md`.

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

_Contexto usado para crear esta story (paths reales del repo):_

- `_bmad-output/implementation-artifacts/sprint-status.yaml` (auto-discovery: primer `backlog` = `2-4-soft-delete-y-restauracion-de-catalogos`)
- `_bmad-output/project-planning-artifacts/epics.md` (Story 2.4)
- `_bmad-output/prd.md` (FR7)
- `_bmad-output/architecture.md`
- `docsBmad/project-context.md`, `docsBmad/rbac.md`, `project-context.md`
- `gatic/composer.json`, `gatic/routes/web.php`
- `gatic/app/Models/{Brand,Category,Location}.php`
- `gatic/app/Livewire/Catalogs/{Brands/BrandsIndex.php,Locations/LocationsIndex.php,Categories/*}`
- `gatic/docs/ui-patterns.md`

### Completion Notes List

- Story creada en modo “ready-for-dev” con guardrails explícitos para evitar errores típicos: RBAC server-side, soft-delete sin force delete, restauración admin-only, bloqueo por “en uso” (FR7).
- Se definió “Papelera de catálogos” como UI fuente de verdad para restauración (con opción de toast “Deshacer” como nice-to-have).
- Se alineó el alcance con historias previas (2.1–2.3) y con estructura Livewire propuesta en arquitectura.

- Se implemento /catalogs/trash (admin-only) con tabs por tipo y restaurar con confirmacion + toasts.
- Se implemento App\\Support\\Catalogs\\CatalogUsage para bloquear soft-delete cuando el catalogo esta en uso.
- Se agrego eliminar (soft-delete) a Categorias y se alineo busqueda LIKE con escape de comodines.
- Tests: se agregaron casos para papelera/restauracion y bloqueo "en uso"; suite completa verde con docker compose + php artisan test.
- Code review: se endurecio `CatalogUsage` (cache en runtime; sin cache en testing; manejo de fallos sin 500), se manejo duplicado por carrera en Marcas, y se ampliaron tests (restore para categorias/ubicaciones, RBAC Livewire en Marcas/Categorias, cleanup robusto de tablas temporales).
### File List

- `_bmad-output/implementation-artifacts/2-4-soft-delete-y-restauracion-de-catalogos.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `gatic/app/Livewire/Catalogs/Brands/BrandsIndex.php`
- `gatic/app/Livewire/Catalogs/Categories/CategoriesIndex.php`
- `gatic/app/Livewire/Catalogs/Locations/LocationsIndex.php`
- `gatic/app/Livewire/Catalogs/Trash/CatalogsTrash.php`
- `gatic/app/Support/Catalogs/CatalogUsage.php`
- `gatic/resources/views/layouts/partials/sidebar-nav.blade.php`
- `gatic/resources/views/livewire/catalogs/categories/categories-index.blade.php`
- `gatic/resources/views/livewire/catalogs/trash/catalogs-trash.blade.php`
- `gatic/routes/web.php`
- `gatic/tests/Feature/Catalogs/BrandsTest.php`
- `gatic/tests/Feature/Catalogs/CatalogsTrashTest.php`
- `gatic/tests/Feature/Catalogs/CategoriesTest.php`
- `gatic/tests/Feature/Catalogs/LocationsTest.php`

## Change Log

- Se agrego la "Papelera de catalogos" (Admin) para restaurar registros soft-deleted.
- Se agrego bloqueo server-side por integridad referencial ("en uso") al eliminar catalogos, con mensaje UX.
- Se agrego eliminar (soft-delete) para Categorias y se alineo busqueda LIKE con escape de comodines.
- Se agregaron tests para papelera/restauracion y para el bloqueo por "en uso"; regresion completa en verde.
