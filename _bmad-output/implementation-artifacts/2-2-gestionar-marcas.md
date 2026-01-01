# Story 2.2: Gestionar Marcas

Status: done

Story Key: 2-2-gestionar-marcas  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Backlog (fuente de verdad): `_bmad-output/project-planning-artifacts/epics.md` (Story 2.2)  
Fuentes: `_bmad-output/prd.md`, `_bmad-output/architecture.md`, `docsBmad/project-context.md`, `project-context.md`, `_bmad-output/project-planning-artifacts/ux-design-specification.md`, `_bmad-output/implementation-artifacts/1-6-roles-fijos-policies-gates-base-server-side.md`, `_bmad-output/implementation-artifacts/1-8-layout-base-sidebar-topbar-navegacion-por-rol.md`, `_bmad-output/implementation-artifacts/1-9-componentes-ux-reutilizables-toasts-loaders-cancelar-actualizado-hace-xs.md`

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Admin/Editor,
I want crear y mantener Marcas,
so that pueda clasificar productos de inventario (FR5).

## Decision Log (reglas confirmadas)

- Autorizacion: usar el gate `catalogs.manage` (Admin/Editor). Lector NO gestiona (server-side obligatorio).
- Convencion de idioma: codigo/DB/rutas en ingles; UI/copy en espanol.
- Soft-delete: Marcas se eliminan via soft-delete (sin borrado fisico).
  - Restauracion/papelera y bloqueo por "referenciado" se completan en Story 2.4; aqui solo se implementa el CRUD basico + soft-delete.
- Unicidad de nombre: case-insensitive + acento-insensitive; incluye eliminadas (soft-deleted).
  - Normalizacion previa a validar/guardar: `trim` + colapsar espacios internos.
  - En DB: indice unico que NO permite duplicados aunque esten eliminados.
  - En validacion Laravel: NO usar `Rule::unique(...)->withoutTrashed()`; debe considerar tambien registros eliminados.

## Acceptance Criteria

### AC1 - Acceso por rol (defensa en profundidad)

**Given** un usuario autenticado con rol Admin o Editor  
**When** navega a "Catalogos > Marcas" (`/catalogs/brands`)  
**Then** puede ver el listado y crear/editar/eliminar (soft-delete) marcas

**Given** un usuario autenticado con rol Lector  
**When** intenta acceder a `/catalogs/brands` (o dispara acciones Livewire del modulo)  
**Then** el servidor bloquea la operacion (403 o equivalente)

### AC2 - Listado funcional

**Given** el listado de Marcas  
**When** existe al menos una marca  
**Then** la tabla muestra nombre + acciones

**And** existe busqueda simple por nombre (case/acento-insensible desde la UX del usuario; la normalizacion de espacios evita duplicados)

### AC3 - Crear marca

**Given** el formulario de creacion  
**When** el usuario guarda una marca con nombre valido  
**Then** la marca se crea y la UI muestra un toast de exito ("Marca creada.")

**And** si el nombre ya existe (incluyendo eliminadas) en una forma equivalente por mayusculas/acentos/espacios (ej. " HP ", "hp", "Hewlett Packard" vs "Hewlett  Packard")  
**Then** se bloquea con un mensaje claro ("La marca ya existe.")

### AC4 - Editar marca

**Given** una marca existente  
**When** el usuario la renombra  
**Then** el registro mantiene el mismo `id` y se actualiza el nombre

**And** se aplican las mismas reglas de unicidad/normalizacion

### AC5 - Eliminar marca (soft-delete)

**Given** una marca existente  
**When** el usuario ejecuta "Eliminar"  
**Then** el registro se marca como soft-deleted (no se borra fisicamente) y deja de aparecer en el listado

**And** intentar re-crear una marca con el mismo nombre (equivalente por mayusculas/acentos/espacios) sigue siendo invalido (incluye eliminadas)

## Tasks / Subtasks

1) Data model (DB) (AC: 2-5)
- [x] Migracion `brands` con: `id`, `name`, `deleted_at`, timestamps
- [x] Definir charset/collation consistente con el proyecto (`utf8mb4` / `utf8mb4_0900_ai_ci`) para garantizar case+acento-insensitive
- [x] Indice unico para `name` que aplique incluso si `deleted_at` no es null (incluye eliminadas)

2) Dominio (AC: 2-5)
- [x] Crear `gatic/app/Models/Brand.php` con `SoftDeletes` + casts necesarios
- [x] Normalizar `name` (trim + colapsar espacios) antes de validar/guardar (no dejar variaciones "raras")

3) Autorizacion + rutas (AC: 1)
- [x] Anadir rutas en `gatic/routes/web.php` bajo middleware `auth`, `active`, `can:catalogs.manage`
  - [x] GET `/catalogs/brands` -> componente Livewire (ej. `App\Livewire\Catalogs\Brands\BrandsIndex`) con name `catalogs.brands.index`
- [x] Asegurar `Gate::authorize('catalogs.manage')` en acciones Livewire (no solo en rutas)

4) UI (Livewire + Bootstrap) (AC: 2-5)
- [x] Crear modulo Livewire `gatic/app/Livewire/Catalogs/Brands/*` (listado + create/edit + delete soft)
- [x] Crear views en `gatic/resources/views/livewire/catalogs/brands/*`
- [x] Reusar patrones UX existentes:
  - [x] Toasts globales via `App\\Livewire\\Concerns\\InteractsWithToasts` (`toastSuccess/toastError`) [Source: `gatic/docs/ui-patterns.md`]
  - [x] `wire:loading` + skeleton/overlay para requests lentos (NFR2) cuando aplique
- [x] Confirmacion al eliminar (ej. `wire:confirm="Confirmas que deseas eliminar esta marca?"`)

5) Navegacion (AC: 1)
- [x] Agregar link "Marcas" en `gatic/resources/views/layouts/partials/sidebar-nav.blade.php` dentro de `@can('catalogs.manage')`
  - [x] NO agregar links a rutas que todavia no existan (evitar 404 "fantasma"; ver Story 1.8)

6) Tests minimos (AC: 1-5)
- [x] Feature: Admin/Editor pueden acceder `/catalogs/brands` y Lector recibe 403
- [x] Feature: crear marca valida persiste y muestra exito (o assert de DB)
- [x] Feature: unicidad case+acento+espacios (normalizacion) se rechaza, incluyendo soft-deleted
- [x] Feature: delete marca = soft-delete (no se elimina fisicamente) y deja de aparecer en listado por defecto

## Dev Notes

### Contexto tecnico (guardrails)

- Stack: Laravel 11 + Livewire 3 + Bootstrap 5; sin WebSockets; UI Livewire-first. [Source: `project-context.md`]
- Permisos: `catalogs.manage` ya existe y esta definido como Admin/Editor. [Source: `gatic/app/Providers/AuthServiceProvider.php`]
- UX: preferir toasts globales y componentes reutilizables (evitar alerts ad-hoc por pagina). [Source: `gatic/docs/ui-patterns.md`]

### Archivos esperados a tocar (orientativo)

- DB/Models:
  - `gatic/database/migrations/*_create_brands_table.php`
  - `gatic/app/Models/Brand.php`
- UI/Livewire:
  - `gatic/app/Livewire/Catalogs/Brands/BrandsIndex.php` (y/o `BrandForm.php`)
  - `gatic/resources/views/livewire/catalogs/brands/*.blade.php`
- Rutas/Navegacion:
  - `gatic/routes/web.php`
  - `gatic/resources/views/layouts/partials/sidebar-nav.blade.php`
- Tests:
  - `gatic/tests/Feature/Catalogs/*`

### Notas de alcance y dependencias

- Story 2.4 completa "Papelera / Restaurar / Bloquear eliminacion si esta referenciado"; aqui NO se implementa restauracion ni reglas por referencias (aun no hay Productos que referencien Marcas).

### References

- Backlog (Story 2.2): `_bmad-output/project-planning-artifacts/epics.md` (seccion "Story 2.2: Gestionar Marcas")
- FR5 / NFRs: `_bmad-output/prd.md`
- Patrones de estructura (Catalogos): `_bmad-output/architecture.md` (mapeo Epic 2 -> `app/Livewire/Catalogs/*`, `app/Actions/Catalogs/*`)
- Reglas criticas: `docsBmad/project-context.md`, `project-context.md`
- RBAC/gates base: `_bmad-output/implementation-artifacts/1-6-roles-fijos-policies-gates-base-server-side.md`
- Layout + navegacion por rol: `_bmad-output/implementation-artifacts/1-8-layout-base-sidebar-topbar-navegacion-por-rol.md`
- Patrones UX (toasts/skeleton/cancel): `_bmad-output/implementation-artifacts/1-9-componentes-ux-reutilizables-toasts-loaders-cancelar-actualizado-hace-xs.md`, `gatic/docs/ui-patterns.md`

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `Get-Content _bmad-output/implementation-artifacts/sprint-status.yaml` (auto-discovery del primer story en backlog: `2-2-gestionar-marcas`)
- `Select-String _bmad-output/project-planning-artifacts/epics.md -Pattern "### Story 2.2" -Context 0,40`
- `Get-Content _bmad-output/prd.md`, `Get-Content _bmad-output/architecture.md`
- `Get-Content project-context.md`
- `Get-Content gatic/app/Providers/AuthServiceProvider.php` (gate `catalogs.manage`)
- `Get-Content gatic/routes/web.php`, `Get-Content gatic/resources/views/layouts/partials/sidebar-nav.blade.php`
- `git -C gatic log -10 --oneline` (patrones recientes)
- `docker compose -f gatic/compose.yaml up -d` (servicios para tests)
- `docker compose -f gatic/compose.yaml exec -T laravel.test php artisan test --filter BrandsTest`
- `docker compose -f gatic/compose.yaml exec -T laravel.test php artisan test` (regresion completa)
- `docker compose -f gatic/compose.yaml exec -T laravel.test ./vendor/bin/pint --test`

### Completion Notes List

- Story seleccionada automaticamente desde `sprint-status.yaml` (primer `ready-for-dev` en `development_status`).
- ACs alineados a `_bmad-output/project-planning-artifacts/epics.md` (Story 2.2) + PRD (FR5) + reglas criticas.
- Guardrails explicitos para evitar errores tipicos: permisos server-side, rutas/identificadores en ingles, UI copy en espanol, reuso de toasts/patrones UX.
- CRUD de Marcas implementado (Livewire + Bootstrap) con soft-delete y busqueda por nombre.
- Unicidad de `brands.name` garantizada via normalizacion + collation `utf8mb4_0900_ai_ci` + indice unico (incluye eliminadas).
- Tests de feature agregados para RBAC, create, unicidad (incluye soft-deleted) y soft-delete.

### File List

- `_bmad-output/implementation-artifacts/2-2-gestionar-marcas.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `gatic/app/Livewire/Catalogs/Brands/BrandsIndex.php`
- `gatic/app/Models/Brand.php`
- `gatic/app/Support/Errors/ErrorReporter.php`
- `gatic/database/migrations/2025_12_31_000001_create_brands_table.php`
- `gatic/resources/views/layouts/partials/sidebar-nav.blade.php`
- `gatic/resources/views/livewire/catalogs/brands/brands-index.blade.php`
- `gatic/routes/web.php`
- `gatic/tests/Feature/Catalogs/BrandsTest.php`
- `gatic/tests/Feature/Ui/PollComponentTest.php`

### Change Log

- Agregado CRUD de Marcas (ruta `/catalogs/brands`) protegido por `catalogs.manage` (Admin/Editor) con Livewire + Bootstrap.
- Migracion `brands` con `utf8mb4_0900_ai_ci` + soft-delete + indice unico por `name` (incluye eliminadas) y normalizacion de espacios.
- Tests de feature para acceso por rol, crear, unicidad (case/acento/espacios, incluye soft-deleted) y soft-delete.
- Fix: Agregada ruta faltante `GET /catalogs/brands` en `web.php` que causaba 404 (2025-12-31).
- Fix (2026-01-01): Agregado link "Marcas" al sidebar y endurecida busqueda (`LIKE`) contra comodines (`%`, `_`).

## Senior Developer Review (AI)

Reviewer: Carlos  
Date: 2026-01-01

### Resultado (adversarial)

- AC1 (Acceso por rol): IMPLEMENTED (rutas `can:catalogs.manage` + `Gate::authorize()` en acciones Livewire). Evidencia: `gatic/routes/web.php`, `gatic/app/Livewire/Catalogs/Brands/BrandsIndex.php`.
- AC2 (Listado + busqueda): IMPLEMENTED (tabla + filtro por nombre). Evidencia: `gatic/app/Livewire/Catalogs/Brands/BrandsIndex.php`, `gatic/resources/views/livewire/catalogs/brands/brands-index.blade.php`.
- AC3-AC5 (CRUD + unicidad + soft-delete): IMPLEMENTED (normalizacion, unique index, SoftDeletes, tests). Evidencia: `gatic/app/Models/Brand.php`, `gatic/database/migrations/2025_12_31_000001_create_brands_table.php`, `gatic/tests/Feature/Catalogs/BrandsTest.php`.

### Git vs Story (discrepancias)

- Story marcaba como [x] el link "Marcas" en sidebar, pero no estaba presente. Corregido en: `gatic/resources/views/layouts/partials/sidebar-nav.blade.php`.

### Hallazgos (corregidos)

#### HIGH

1) Navegacion incompleta: faltaba el link "Marcas" en el sidebar (AC1). Fix: agregado bajo `@can('catalogs.manage')`. Evidencia: `gatic/resources/views/layouts/partials/sidebar-nav.blade.php`.

#### MEDIUM

1) Busqueda `LIKE` sin escapar comodines (`%`, `_`): podia devolver resultados inesperados. Fix: escape + `ESCAPE '\\'`. Evidencia: `gatic/app/Livewire/Catalogs/Brands/BrandsIndex.php`.
2) Validacion `unique` con `ignore(NULL)` en creacion: funciona, pero es confuso y facilita bugs en refactor. Fix: aplicar `ignore()` solo cuando hay `brandId`. Evidencia: `gatic/app/Livewire/Catalogs/Brands/BrandsIndex.php`.

### Outcome

- Review: APPROVED (con fixes aplicados).  
- Status actualizado a **done** y sprint tracking sincronizado.
