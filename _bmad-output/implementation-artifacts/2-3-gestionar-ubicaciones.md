# Story 2.3: Gestionar Ubicaciones

Status: done

Story Key: 2-3-gestionar-ubicaciones  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Backlog (fuente de verdad): `_bmad-output/project-planning-artifacts/epics.md` (Story 2.3)  
Fuentes: `_bmad-output/prd.md` (FR6, FR7, FR24), `_bmad-output/architecture.md` (Epic 2 mapping + patrones Livewire), `docsBmad/project-context.md`, `docsBmad/rbac.md`, `_bmad-output/project-context.md`, `project-context.md`, `gatic/docs/ui-patterns.md`, `_bmad-output/implementation-artifacts/2-2-gestionar-marcas.md`, `_bmad-output/implementation-artifacts/2-1-gestionar-categorias-incluye-serializado-asset-tag.md`

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Admin/Editor,
I want crear y mantener Ubicaciones,
so that pueda registrar donde estan los activos (FR6).

## Decision Log (reglas confirmadas)

- Autorizacion: usar el gate `catalogs.manage` (Admin/Editor). Lector NO gestiona (server-side obligatorio).
- Convencion de idioma: codigo/DB/rutas en ingles; UI/copy en espanol.
- Soft-delete: Ubicaciones se eliminan via soft-delete (sin borrado fisico).
  - Restauracion/papelera y bloqueo por "referenciado" se completan en Story 2.4; aqui solo CRUD basico + soft-delete.
- Unicidad de nombre: case-insensitive + acento-insensitive; incluye eliminadas (soft-deleted).
  - Normalizacion previa a validar/guardar: `trim` + colapsar espacios internos.
  - En DB: collation `utf8mb4_0900_ai_ci` + indice unico en `locations.name` (aplica incluso si esta soft-deleted).
- Busqueda por nombre: escapar comodines `LIKE` (`%`, `_`) para evitar matches inesperados.

## Acceptance Criteria

### AC1 - Acceso por rol (defensa en profundidad)

**Given** un usuario autenticado con rol Admin o Editor  
**When** navega a "Catalogos > Ubicaciones" (`/catalogs/locations`)  
**Then** puede ver el listado y crear/editar/eliminar (soft-delete) ubicaciones

**Given** un usuario autenticado con rol Lector  
**When** intenta acceder a `/catalogs/locations` (o dispara acciones Livewire del modulo)  
**Then** el servidor bloquea la operacion (403 o equivalente)

### AC2 - Listado funcional

**Given** el listado de Ubicaciones  
**When** existe al menos una ubicacion  
**Then** la tabla muestra nombre + acciones

**And** existe busqueda simple por nombre (case/acento-insensible desde la UX del usuario; la normalizacion de espacios evita duplicados)

### AC3 - Crear ubicacion

**Given** el formulario de creacion  
**When** el usuario guarda una ubicacion con nombre valido  
**Then** la ubicacion se crea y la UI muestra un toast de exito ("Ubicacion creada.")

**And** si el nombre ya existe (incluyendo eliminadas) en una forma equivalente por mayusculas/acentos/espacios  
**Then** se bloquea con un mensaje claro ("La ubicacion ya existe.")

### AC4 - Editar ubicacion

**Given** una ubicacion existente  
**When** el usuario la renombra  
**Then** el registro mantiene el mismo `id` y se actualiza el nombre

**And** se aplican las mismas reglas de unicidad/normalizacion

### AC5 - Eliminar ubicacion (soft-delete)

**Given** una ubicacion existente  
**When** el usuario ejecuta "Eliminar"  
**Then** el registro se marca como soft-deleted (no se borra fisicamente) y deja de aparecer en el listado

**And** intentar re-crear una ubicacion con el mismo nombre (equivalente por mayusculas/acentos/espacios) sigue siendo invalido (incluye eliminadas)

## Tasks / Subtasks

1) Data model (DB) (AC: 2-5)
- [x] Migracion `locations` con: `id`, `name`, `deleted_at`, timestamps
- [x] Definir charset/collation consistente con el proyecto (`utf8mb4` / `utf8mb4_0900_ai_ci`) para garantizar case+acento-insensitive
- [x] Indice unico para `name` que aplique incluso si `deleted_at` no es null (incluye eliminadas)

2) Dominio (AC: 2-5)
- [x] Crear `gatic/app/Models/Location.php` con `SoftDeletes`
- [x] Normalizar `name` (trim + colapsar espacios) antes de validar/guardar (no dejar variaciones "raras")

3) Autorizacion + rutas (AC: 1)
- [x] Anadir ruta en `gatic/routes/web.php` bajo middleware `auth`, `active`, `can:catalogs.manage`
  - [x] GET `/catalogs/locations` -> componente Livewire `App\\Livewire\\Catalogs\\Locations\\LocationsIndex` con name `catalogs.locations.index`
- [x] Asegurar `Gate::authorize('catalogs.manage')` en acciones Livewire (no solo en rutas)

4) UI (Livewire + Bootstrap) (AC: 2-5)
- [x] Crear componente Livewire `gatic/app/Livewire/Catalogs/Locations/LocationsIndex.php` (listado + create/edit + delete soft)
- [x] Crear view en `gatic/resources/views/livewire/catalogs/locations/locations-index.blade.php`
- [x] Reusar patrones UX existentes (NO reinventar):
  - [x] Toasts globales via `App\\Livewire\\Concerns\\InteractsWithToasts` (`toastSuccess/toastError`) [Source: `gatic/docs/ui-patterns.md`]
  - [x] `wire:loading` + overlay de operacion lenta con cancelar via `<x-ui.long-request target="save,delete" />` (NFR2)
  - [x] Confirmacion al eliminar (ej. `wire:confirm="Confirmas que deseas eliminar esta ubicacion?"`)
- [x] Busqueda con escape de comodines `LIKE` (ver patron en `gatic/app/Livewire/Catalogs/Brands/BrandsIndex.php`)
- [x] Paginacion: 15

5) Navegacion (AC: 1)
- [x] Agregar entrada "Ubicaciones" al sidebar en `gatic/resources/views/layouts/partials/sidebar-nav.blade.php` bajo `@can('catalogs.manage')`

6) Tests (Feature) (AC: 1-5)
- [x] Crear `gatic/tests/Feature/Catalogs/LocationsTest.php` siguiendo el patron de `BrandsTest.php`
  - [x] RBAC: Admin/Editor 200; Lector 403 en `/catalogs/locations`
  - [x] Crear ubicacion + normalizacion
  - [x] Unicidad case/acento/espacios (incluye soft-deleted)
  - [x] Soft-delete y desaparicion del listado

## Dev Notes

### Stack (congelado)

- Laravel 11.x + PHP 8.2+ (ver `gatic/composer.json`)
- Livewire 3.x
- Bootstrap 5
- MySQL 8 (`utf8mb4_0900_ai_ci`)
- NO agregar dependencias nuevas para este CRUD

### Guardrails (evitar errores tipicos)

- Livewire-first: no crear Controllers para este CRUD.
- Autorizacion server-side obligatoria en rutas y dentro del componente.
- No implementar restauracion/papelera ni bloqueo por "referenciado" (eso es Story 2.4).
- Soft-deleted no deben verse en el listado (comportamiento default de Eloquent).
- La regla de unicidad debe considerar eliminadas: NO usar `withoutTrashed()`.

### Archivos esperados a tocar (guia concreta)

- Rutas: `gatic/routes/web.php`
- Sidebar: `gatic/resources/views/layouts/partials/sidebar-nav.blade.php`
- Modelo: `gatic/app/Models/Location.php`
- Livewire: `gatic/app/Livewire/Catalogs/Locations/LocationsIndex.php`
- Blade: `gatic/resources/views/livewire/catalogs/locations/locations-index.blade.php`
- Migracion: `gatic/database/migrations/*_create_locations_table.php`
- Tests: `gatic/tests/Feature/Catalogs/LocationsTest.php`

### References

- RBAC/gates: `docsBmad/rbac.md`, `gatic/app/Providers/AuthServiceProvider.php`
- Patrones UI: `gatic/docs/ui-patterns.md`
- Patrones existentes (referencia directa):
  - `gatic/app/Livewire/Catalogs/Brands/BrandsIndex.php`
  - `gatic/resources/views/livewire/catalogs/brands/brands-index.blade.php`
  - `gatic/app/Livewire/Catalogs/Categories/CategoriesIndex.php`

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `Get-Content _bmad-output/implementation-artifacts/sprint-status.yaml` (auto-discovery: primer story en backlog: `2-3-gestionar-ubicaciones`)
- `Select-String _bmad-output/project-planning-artifacts/epics.md -Pattern "### Story 2.3"`
- `Select-String _bmad-output/prd.md -Pattern "FR6"`
- `Get-Content _bmad-output/architecture.md`
- `Get-Content docsBmad/rbac.md`
- `Get-Content gatic/docs/ui-patterns.md`
- `Get-Content gatic/routes/web.php`
- `Get-Content gatic/resources/views/layouts/partials/sidebar-nav.blade.php`
- `Get-Content gatic/app/Livewire/Catalogs/Brands/BrandsIndex.php`
- `Get-Content gatic/database/migrations/2025_12_31_000001_create_brands_table.php`
- `git log -5 --oneline`
- `docker compose -f compose.yaml exec -T laravel.test php artisan test --filter=LocationsTest`
- `docker compose -f compose.yaml exec -T laravel.test php artisan test`
- `docker compose -f compose.yaml exec -T laravel.test ./vendor/bin/pint --test`
- `docker compose -f compose.yaml exec -T laravel.test ./vendor/bin/pint`

### Completion Notes List

- Story seleccionada automaticamente desde `sprint-status.yaml` (primer `backlog` en `development_status`).
- ACs alineados a `_bmad-output/project-planning-artifacts/epics.md` (Story 2.3) + PRD (FR6).
- Guardrails explicitos para evitar errores tipicos: permisos server-side, rutas/identificadores en ingles, UI copy en espanol, reuso de toasts/patrones UX.
- Estado marcado como `ready-for-dev` y tracking actualizado.
- CRUD de Ubicaciones implementado con Livewire + Bootstrap (create/edit/delete soft), busqueda con escape `LIKE` y paginacion 15.
- Reglas de unicidad (case/acento/espacios) aplicadas via normalizacion + collation `utf8mb4_0900_ai_ci` + indice unico.
- Review fixes: agregados tests para defensa en profundidad (acciones Livewire) y para escape de comodines `LIKE`; endurecido el guardado ante carrera de unicidad (error amigable).
- Suite completa de tests pasando (`php artisan test`).
- Pint ejecutado.

### File List

- `_bmad-output/implementation-artifacts/2-3-gestionar-ubicaciones.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `gatic/routes/web.php`
- `gatic/resources/views/layouts/partials/sidebar-nav.blade.php`
- `gatic/app/Livewire/Catalogs/Brands/BrandsIndex.php`
- `gatic/app/Models/Location.php`
- `gatic/app/Livewire/Catalogs/Locations/LocationsIndex.php`
- `gatic/resources/views/livewire/catalogs/locations/locations-index.blade.php`
- `gatic/database/migrations/2025_12_31_000003_create_locations_table.php`
- `gatic/tests/Feature/Catalogs/LocationsTest.php`

### Change Log

- 2026-01-01: Senior Developer Review (AI) - fixes aplicados (tests RBAC en acciones Livewire, tests de busqueda con escape `LIKE`, manejo de colision de unicidad con mensaje claro).

## Senior Developer Review (AI)

Reviewer: Carlos  
Date: 2026-01-01

### Resultado (adversarial)

- AC1 (Acceso por rol): IMPLEMENTED (rutas `can:catalogs.manage` + `Gate::authorize()` en acciones Livewire). Evidencia: `gatic/routes/web.php`, `gatic/app/Livewire/Catalogs/Locations/LocationsIndex.php`.
- AC2 (Listado + busqueda): IMPLEMENTED (tabla + filtro por nombre con escape de comodines `LIKE`). Evidencia: `gatic/app/Livewire/Catalogs/Locations/LocationsIndex.php`, `gatic/resources/views/livewire/catalogs/locations/locations-index.blade.php`.
- AC3-AC5 (CRUD + unicidad + soft-delete): IMPLEMENTED (normalizacion, unique index, SoftDeletes, tests). Evidencia: `gatic/app/Models/Location.php`, `gatic/database/migrations/2025_12_31_000003_create_locations_table.php`, `gatic/tests/Feature/Catalogs/LocationsTest.php`.

### Hallazgos (corregidos)

#### MEDIUM

1) Defensa en profundidad sin test en acciones Livewire: faltaba probar que Lector no puede ejecutar `save/edit/delete` aunque intente llamar el componente. Fix: agregado test. Evidencia: `gatic/tests/Feature/Catalogs/LocationsTest.php`.
2) Busqueda `LIKE` (escape `%` y `_`) sin test: la implementacion existia, pero faltaba validarla. Fix: agregado test con `_` y `%`. Evidencia: `gatic/tests/Feature/Catalogs/LocationsTest.php`.
3) Riesgo de carrera en unicidad (DB unique): posible colision en `locations.name` entre validar y guardar. Fix: capturar `duplicate key` y mostrar error amigable ("La ubicacion ya existe."). Evidencia: `gatic/app/Livewire/Catalogs/Locations/LocationsIndex.php`.

### Outcome

- Review: APPROVED (con fixes aplicados).
