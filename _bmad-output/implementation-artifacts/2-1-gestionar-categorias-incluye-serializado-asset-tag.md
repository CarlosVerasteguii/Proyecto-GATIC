# Story 2.1: Gestionar Categorías (incluye serializado/asset_tag)

Status: done

Fuentes: `_bmad-output/prd.md` (FR4, FR7), `_bmad-output/architecture.md` (Epic 2 mapping), `docsBmad/project-context.md` (glosario y reglas), `docsBmad/rbac.md` (gate `catalogs.manage`).

## Objetivo

As a Admin/Editor,
I want crear y mantener Categorías indicando si son serializadas y si requieren `asset_tag`,
So that el sistema aplique reglas correctas de inventario (FR4).

## Alcance (IN)

- CRUD base de Categorías: **crear, listar, editar**.
- Campos funcionales: `name`, `is_serialized`, `requires_asset_tag`.
- Permisos server-side con `can:catalogs.manage` (Admin/Editor).
- Validaciones robustas (incluye consistencia de flags y unicidad de nombre).
- Tests mínimos que cubran permisos + validaciones.

## Fuera de alcance (OUT)

- Soft-delete / restauración desde UI (esto vive en **Story 2.4**), aunque el modelo puede incluir `deleted_at` desde ya.
- Reglas de "no eliminar si está referenciado" (FR7) completas: dependen de entidades de Epic 3+; se implementan de forma integral en Story 2.4.
- Importación masiva / merge de categorías / historial de cambios.

## Reglas confirmadas (Decision Log)

- Permisos: Admin y Editor gestionan catálogos; Lector solo consulta.
- Gate: `catalogs.manage` (ver `docsBmad/rbac.md`).
- Unicidad de nombre: case-insensitive + acento-insensitive.
- Unicidad incluye eliminados (soft-deleted): no se permite recrear el mismo nombre tras borrar; se debe restaurar o renombrar.
- Renombrar permitido aunque esté "en uso" (futuro) porque todo referencia por `id`.

## Especificación de datos

### Tabla

- `categories`
  - `id` (PK)
  - `name` (string, requerido)
  - `is_serialized` (bool, default `false`)
  - `requires_asset_tag` (bool, default `false`)
  - `deleted_at` (nullable) (para soft-delete; UI en Story 2.4)
  - timestamps

### Normalización de `name` (antes de persistir)

- `trim()`
- colapsar espacios internos múltiples a 1
- (opcional) mantener el texto tal cual en UI; en DB se guarda ya normalizado

### Unicidad de `name`

Requisito: "Café", "Cafe", " CAFÉ " y "cafe" cuentan como el mismo nombre.

Implementación:
- MySQL 8 con collation acento-insensible y case-insensible (ej. `utf8mb4_0900_ai_ci`) aplicada a la columna `name`.
- Índice único sobre `name`.
- Nota: como la unicidad incluye eliminados, **NO** se usa índice único parcial por `deleted_at`.

## UI / Rutas

### Rutas

- `GET /catalogs/categories` → `catalogs.categories.index`
- `GET /catalogs/categories/create` → `catalogs.categories.create`
- `GET /catalogs/categories/{category}/edit` → `catalogs.categories.edit`

Todas bajo middleware: `auth`, `active`, `can:catalogs.manage`.

### Navegación

- Agregar entrada "Catálogos → Categorías" visible solo para Admin/Editor.

### Pantallas (UX mínimo robusto)

**Index** (`/catalogs/categories`)
- Tabla con columnas:
  - Nombre
  - Serializado (sí/no)
  - Requiere asset_tag (sí/no)
  - Acciones: Editar
- Búsqueda por nombre (criterio simple "contiene").
- Botón "Nueva categoría".

**Form** (create/edit)
- Campo `name`.
- Checkbox `is_serialized`.
- Checkbox `requires_asset_tag`:
  - UI: deshabilitado si `is_serialized = false`.
  - Server: siempre valida consistencia (ver AC2).
  - UX: si el usuario desmarca `is_serialized`, el campo `requires_asset_tag` se resetea a `false` para no quedar en un estado inválido.
- Guardar / Cancelar.
- Feedback con toasts existentes.

## Acceptance Criteria

### AC1 - Acceso por rol

**Given** un usuario autenticado con rol Admin o Editor
**When** accede a `/catalogs/categories`
**Then** puede ver la lista y crear/editar

**Given** un usuario autenticado con rol Lector
**When** intenta acceder a cualquier ruta `/catalogs/categories*`
**Then** recibe 403 (o pantalla de "sin permisos") y no puede operar

### AC2 - Consistencia `is_serialized` / `requires_asset_tag`

**Given** el formulario de Categoría
**When** el usuario guarda
**Then** se persisten `name`, `is_serialized`, `requires_asset_tag`

**And** se cumple:
- Si `is_serialized = false` → `requires_asset_tag` debe ser `false`.
- Si `requires_asset_tag = true` → `is_serialized` debe ser `true`.

### AC3 - Nombre único (case + acento insensible) incluyendo eliminados

**Given** ya existe una Categoría con nombre "Café"
**When** intento crear otra Categoría con " cafe " o "CAFE" o "CAFÉ"
**Then** el sistema bloquea con error de validación ("Nombre ya existe")

**And** la regla aplica aunque la Categoría existente esté soft-deleted.

### AC4 - Renombrar seguro

**Given** una Categoría existente
**When** se renombra (ej. "HP" → "Hewlett-Packard")
**Then** el registro mantiene el mismo `id`

## Tasks / Subtasks (lista ejecutable)

1) Modelo y migración
- [x] Crear migración `create_categories_table`
- [x] Definir defaults seguros: `is_serialized=false`, `requires_asset_tag=false`
- [x] Definir collation de `name` para acento+case insensible (MySQL 8)
- [x] Crear índice único en `name`
- [x] Agregar soft deletes (`deleted_at`) (sin exponer UI de borrar/restaurar en esta story)

2) Modelo Eloquent
- [x] Crear `gatic/app/Models/Category.php` con `SoftDeletes` y casts boolean

3) Autorización
- [x] Usar `can:catalogs.manage` en rutas
- [x] En componentes Livewire, asegurar server-side auth (Gate/authorize)

4) Livewire (módulo Catalogs)
- [x] `gatic/app/Livewire/Catalogs/Categories/CategoriesIndex.php`
- [x] `gatic/app/Livewire/Catalogs/Categories/CategoryForm.php`
- [x] Vistas Blade correspondientes bajo `resources/views/livewire/catalogs/categories/*`

5) Validaciones
- [x] `name`: requerido, longitud razonable, normalización (trim + colapsar espacios)
- [x] `name` único con collation definida (incluye eliminados)
- [x] Regla de consistencia flags (AC2)

6) UX
- [x] Toast de éxito al guardar
- [x] Mostrar errores de validación en el formulario
- [x] "Cancelar" regresa a index sin modificar
- [x] El form no puede quedar con `requires_asset_tag=true` cuando `is_serialized=false` (reset automático)

7) Tests (mínimos y robustos)
- [x] Permisos: Admin y Editor pueden ver index (200)
- [x] Permisos: Admin puede ver create/edit (200)
- [x] Permisos: Lector no puede (403) en index/create/edit
- [x] Validación AC2: `requires_asset_tag=true` con `is_serialized=false` falla
- [x] Normalización: "  Foo   Bar  " se guarda como "Foo Bar"
- [x] Unicidad: crear "Café" y luego intentar "CAFE" falla (incluye soft-deleted)

## Definition of Done

- AC1-AC4 cubiertos por implementación y tests.
- Sin dependencias nuevas innecesarias.
- Respeta estructura de módulos (Livewire-first) definida en `_bmad-output/architecture.md`.

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `Get-Content _bmad-output/implementation-artifacts/2-1-gestionar-categorias-incluye-serializado-asset-tag.md`
- `Get-Content project-context.md`
- `Get-Content docsBmad/project-context.md`
- `Get-Content gatic/routes/web.php`
- `Get-Content gatic/resources/views/layouts/partials/sidebar-nav.blade.php`
- `docker compose -f gatic/compose.yaml exec -T laravel.test php artisan test --filter CategoriesTest`
- `docker compose -f gatic/compose.yaml exec -T laravel.test ./vendor/bin/pint --test`

### Completion Notes List

- CRUD base de Categorías (listar/crear/editar) implementado con Livewire + Bootstrap.
- Reglas AC2 (consistencia `is_serialized` / `requires_asset_tag`) validadas server-side y endurecidas en UX (reset automático).
- Normalización de `name` (trim + colapsar espacios) aplicada antes de validar/guardar.
- Unicidad de `name` garantizada vía collation `utf8mb4_0900_ai_ci` + índice único (incluye eliminados).

### File List

- `_bmad-output/implementation-artifacts/2-1-gestionar-categorias-incluye-serializado-asset-tag.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `gatic/app/Livewire/Catalogs/Categories/CategoriesIndex.php`
- `gatic/app/Livewire/Catalogs/Categories/CategoryForm.php`
- `gatic/app/Models/Category.php`
- `gatic/database/migrations/2025_12_31_000002_create_categories_table.php`
- `gatic/resources/views/layouts/partials/sidebar-nav.blade.php`
- `gatic/resources/views/livewire/catalogs/categories/categories-index.blade.php`
- `gatic/resources/views/livewire/catalogs/categories/category-form.blade.php`
- `gatic/routes/web.php`
- `gatic/tests/Feature/Catalogs/CategoriesTest.php`

### Change Log

- 2025-12-31: Code review + fixes (UX flags + test de ruta edit + collation MySQL 8 en `categories`).
- Agregado módulo de Categorías: rutas `/catalogs/categories*` protegidas por `catalogs.manage` (Admin/Editor) con listado + formulario create/edit.
- Migración `categories` con soft-delete, defaults seguros y unicidad de `name` (incluye eliminados) + collation acento/case-insensible.
- Tests de feature para RBAC, validación AC2, normalización de nombre y unicidad case/acento-insensible.

## Senior Developer Review (AI)

- Fecha: 2025-12-31
- Veredicto: **Aprobado**

### Verificación

- `docker compose -f gatic/compose.yaml exec -T laravel.test php artisan test --filter CategoriesTest`
- `docker compose -f gatic/compose.yaml exec -T laravel.test ./vendor/bin/pint --test`

