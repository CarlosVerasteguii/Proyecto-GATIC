# Story 4.1: Crear y mantener Empleados (RPE)

Status: done

Story Key: 4-1-crear-y-mantener-empleados-rpe  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Backlog (fuente de verdad): `_bmad-output/implementation-artifacts/epics.md` (Epic 4, Story 4.1)  
Fuentes:
- `_bmad-output/implementation-artifacts/epics.md` (Epic 4, Story 4.1)
- `_bmad-output/implementation-artifacts/architecture.md` (estructura de proyecto + mapeo Epic 4)
- `docsBmad/project-context.md` (bible: restricciones no negociables + stack)
- `docsBmad/rbac.md` (gates existentes y reglas de aplicacion)
- `_bmad-output/implementation-artifacts/prd.md` (FR15)
- `gatic/docs/ui-patterns.md` (toasts + long requests + errores con `error_id`)
- `_bmad-output/implementation-artifacts/2-3-gestionar-ubicaciones.md` (patrones CRUD Livewire + defensa en profundidad)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Admin/Editor,  
I want crear y mantener Empleados (RPE) como receptores,  
so that pueda asociar movimientos a personas reales (FR15).

## Alcance

Incluye:
- Alta/edicion de Empleados.
- Listado + busqueda simple (nombre o RPE).
- Validaciones (RPE unico, campos minimos).
- Autorizacion server-side (RBAC).

No incluye (se cubre en historias posteriores):
- Autocomplete/seleccion de Empleado en formularios de movimientos (Story 4.2).
- Ficha de Empleado + activos asociados (Story 4.3).
- Movimientos, tenencia real, reglas de estado/transiciones (Epic 5).

## Acceptance Criteria

### AC1 - Acceso por rol (defensa en profundidad)

**Given** un usuario autenticado con rol Admin o Editor  
**When** navega al modulo de Empleados (RPE)  
**Then** puede ver el listado y crear/editar Empleados

**Given** un usuario autenticado con rol Lector  
**When** intenta acceder a la ruta del modulo o dispara acciones Livewire del modulo  
**Then** el servidor bloquea la operacion (403 o equivalente)

### AC2 - Crear/editar Empleado con validaciones minimas

**Given** un Admin/Editor autenticado  
**When** crea un Empleado con datos validos  
**Then** el sistema guarda el registro y la UI muestra un toast de exito

**And** si el RPE ya existe  
**Then** el sistema bloquea con mensaje claro de validacion (RPE debe ser unico)

**Given** un Empleado existente  
**When** el usuario lo edita  
**Then** el sistema actualiza el registro (mismo `id`) y conserva la regla de unicidad de RPE (ignorando el propio registro)

### AC3 - Listado y busqueda simple

**Given** el listado de Empleados  
**When** existe al menos un Empleado  
**Then** la tabla muestra al menos: Nombre + RPE + acciones

**And** el usuario puede buscar por Nombre o RPE con resultados relevantes (sin requerir filtros avanzados)

## Dev Notes (contexto para implementar sin sorpresas)

### Decision Log (reglas confirmadas)

- Dominio: **Empleado (RPE) != Usuario del sistema**. Los movimientos se asocian a `employees`, no a `users`. [Source: `docsBmad/project-context.md`]
- Stack/estilo: Laravel 11 + Livewire 3 + Bootstrap 5; **no inventar nuevas librerias** para este modulo. [Source: `docsBmad/project-context.md`, `gatic/composer.json`, `gatic/package.json`]
- Idioma: codigo/DB/rutas/identificadores en **ingles**; copy/UI en **espanol**. [Source: `_bmad-output/project-context.md`, `docsBmad/project-context.md`]
- Autorizacion: **server-side obligatorio** (middleware `can:` + `Gate::authorize(...)` dentro del componente Livewire). [Source: `docsBmad/rbac.md`, `gatic/app/Livewire/Catalogs/Brands/BrandsIndex.php`]

### Modelo minimo sugerido (para soportar Epic 4 y preparar Epic 5)

Tabla `employees` (minimo MVP para Gate 3 / Epic 4):
- `id` (PK)
- `rpe` (string, **unique**)  *(si se decide hacerlo opcional, debe ser `nullable` + unique solo cuando exista; confirmar antes de implementar)*
- `name` (string)
- `department` (nullable string) *(soporte/consulta; Story 4.3 lo sugiere)*
- `job_title` (nullable string)
- timestamps

Notas:
- Buscar por `rpe` debe ser rapido (indice unico ya lo cubre).
- Buscar por `name` puede ser `LIKE` con escape de comodines y orden alfabetico, como en Catalogos. [Source: `gatic/app/Livewire/Catalogs/Brands/BrandsIndex.php`]

### Reuso (NO reinventar ruedas)

- CRUD + busqueda + paginacion: copiar el patron de `BrandsIndex`/`LocationsIndex` (mismos traits y estructura). [Source: `gatic/app/Livewire/Catalogs/Brands/BrandsIndex.php`]
- Toasts: `App\\Livewire\\Concerns\\InteractsWithToasts` + `toastSuccess/toastError`. [Source: `gatic/docs/ui-patterns.md`]
- Operaciones lentas: usar `<x-ui.long-request target=\"save,delete\" />` (si aplica) para cumplir NFR2 sin afectar polling futuro. [Source: `gatic/docs/ui-patterns.md`]

## Requisitos tecnicos (guardrails para el dev agent)

- RBAC:
  - Requerido: solo `Admin`/`Editor` pueden entrar y ejecutar acciones del modulo. [Source: `_bmad-output/implementation-artifacts/epics.md` (Story 4.1), `docsBmad/rbac.md`]
  - Recomendado (para no inventar gates nuevos): usar `can:inventory.manage` (mismos roles) en rutas del modulo. Si se crea un gate nuevo (`employees.manage`), actualizar `docsBmad/rbac.md` y `gatic/app/Providers/AuthServiceProvider.php`.
- Validaciones:
  - `name`: requerido, `max:255`.
  - `rpe`: requerido y unico (ver nota de optionalidad en Dev Notes).
  - Mensajes en espanol, directos (sin tecnicismos).
- Concurrencia:
  - Manejar colision de unicidad de RPE con `QueryException` (MySQL duplicate key) y mostrar error amigable, como en `BrandsIndex`. [Source: `gatic/app/Livewire/Catalogs/Brands/BrandsIndex.php`]
- Errores:
- Errores inesperados deben terminar en UI con `error_id` (infra existente). Evitar introducir modales custom; usar patrones existentes. [Source: `gatic/docs/ui-patterns.md`]

## Cumplimiento de arquitectura

- Ubicacion del codigo (no negociar): `gatic/app/Models/Employee.php`, `gatic/app/Livewire/Employees/*`, `gatic/app/Actions/Employees/*` (si se usan Actions). [Source: `_bmad-output/implementation-artifacts/architecture.md`]
- Controllers: solo para "bordes" (descargas/JSON interno puntual). Este modulo debe ser Livewire-first (route -> componente). [Source: `_bmad-output/implementation-artifacts/architecture.md`]
- Config (si se requiere algun default/limit): centralizar en `gatic/config/gatic.php` (sin numeros magicos en la vista). [Source: `_bmad-output/implementation-artifacts/architecture.md`, `gatic/docs/ui-patterns.md`]

## Requisitos de librerias/frameworks

- PHP: `^8.2` (no usar features que requieran >8.2). [Source: `gatic/composer.json`]
- Laravel: `laravel/framework ^11.31` (no migrar a Laravel 12 en esta historia). [Source: `gatic/composer.json`, `_bmad-output/implementation-artifacts/architecture.md`]
- Livewire: `livewire/livewire ^3.0` (seguir patrones existentes `#[Layout('layouts.app')]`, `Gate::authorize`, `WithPagination`). [Source: `gatic/composer.json`, `gatic/app/Livewire/Catalogs/Brands/BrandsIndex.php`]
- Bootstrap: `^5.2.3` via Vite (reusar componentes/estilos existentes). [Source: `gatic/package.json`]

## Requisitos de estructura de archivos (que tocar y donde)

## Tasks / Subtasks

1) Data model (DB) (AC: 2-3)
- [x] Migracion `employees` con: `id`, `rpe`, `name`, `department` (nullable), `job_title` (nullable), timestamps
- [x] Indice unico para `employees.rpe` (y decidir si `rpe` es obligatorio u opcional antes de migrar)

2) Dominio (AC: 2-3)
- [x] Crear `gatic/app/Models/Employee.php`
- [x] (Recomendado) Normalizar `rpe` (trim + colapsar espacios; opcional upper) y `name` (trim + colapsar espacios) antes de validar/guardar, siguiendo patrones de Catalogos. [Source: `gatic/app/Models/Brand.php` (normalizacion), `gatic/app/Livewire/Catalogs/Brands/BrandsIndex.php`]

3) Autorizacion + rutas + navegacion (AC: 1)
- [x] Anadir ruta en `gatic/routes/web.php` bajo middleware `auth`, `active`, `can:inventory.manage`
  - [x] GET `/employees` -> componente Livewire `App\\Livewire\\Employees\\EmployeesIndex` con name `employees.index`
- [x] Asegurar `Gate::authorize('inventory.manage')` (o gate elegido) en acciones Livewire (no solo en rutas). [Source: `gatic/app/Livewire/Catalogs/Brands/BrandsIndex.php`]
- [x] Anadir item de menu en `gatic/resources/views/layouts/partials/sidebar-nav.blade.php` para Admin/Editor

4) UI (Livewire + Bootstrap) (AC: 2-3)
- [x] Crear componente Livewire `gatic/app/Livewire/Employees/EmployeesIndex.php` (listado + busqueda + create/edit)
- [x] Crear view en `gatic/resources/views/livewire/employees/employees-index.blade.php`
- [x] Reusar patrones UX existentes (NO reinventar):
  - [x] Toasts globales via `App\\Livewire\\Concerns\\InteractsWithToasts` [Source: `gatic/docs/ui-patterns.md`]
  - [x] Overlay de operacion lenta con cancelar via `<x-ui.long-request target=\"save\" />` [Source: `gatic/docs/ui-patterns.md`]

## Requisitos de testing

- Feature tests (minimo):
  - RBAC: Admin/Editor pueden ver/crear/editar; Lector recibe 403 en ruta y en acciones Livewire (defensa en profundidad). [Source: `docsBmad/rbac.md`, `_bmad-output/implementation-artifacts/2-3-gestionar-ubicaciones.md`]
  - Validacion: `rpe` duplicado debe fallar con mensaje claro.
  - Busqueda: buscar por `rpe` y por `name` retorna resultados; `LIKE` debe escapar `%`/`_` si se usa `whereRaw like` (patron existente). [Source: `gatic/app/Livewire/Catalogs/Brands/BrandsIndex.php`]
- Ubicacion sugerida: `gatic/tests/Feature/Employees/EmployeesTest.php` (o similar por convencion de modulos).

## Informacion tecnica reciente (web research)

- Livewire 3 pagination:
  - Requiere `Livewire\\WithPagination` en el componente y `Model::paginate(...)` en `render()`. [Source: https://livewire.laravel.com/docs/3.x/pagination]
  - Mantener el patron existente `updatedSearch() { $this->resetPage(); }` para que la busqueda no deje al usuario en una pagina invalida. [Source: `gatic/app/Livewire/Catalogs/Brands/BrandsIndex.php`]
- Laravel 11 validation:
- Para unicidad en update, usar `Rule::unique(...)->ignore($id)` (sin queries manuales). [Source: https://laravel.com/docs/11.x/validation]

## Referencias de contexto de proyecto (leer antes de codear)

- `docsBmad/project-context.md` (bible; si hay conflicto, gana este documento)
- `_bmad-output/implementation-artifacts/architecture.md` (estructura + patrones)
- `docsBmad/rbac.md` (gates y defensa en profundidad)
- `gatic/docs/ui-patterns.md` (toasts, long-request, `error_id`)
- `_bmad-output/implementation-artifacts/epics.md` (Epic 4 y siguientes: Story 4.2, 4.3, Epic 5)

## Estado de completitud (create-story)

- Story file creado y marcado como `review`.
- Tracking en `sprint-status.yaml` debe reflejar:
  - `epic-4: in-progress`
  - `4-1-crear-y-mantener-empleados-rpe: review`

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `Get-Content _bmad-output/implementation-artifacts/sprint-status.yaml` (auto-discovery: primer story en backlog: `4-1-crear-y-mantener-empleados-rpe`)
- `Get-Content _bmad-output/implementation-artifacts/epics.md` (Epic 4, Story 4.1)
- `Get-Content _bmad-output/implementation-artifacts/architecture.md` (estructura + patrones)
- `Get-Content docsBmad/project-context.md`
- `Get-Content docsBmad/rbac.md`
- `Get-Content gatic/docs/ui-patterns.md`
- `Get-Content gatic/app/Livewire/Catalogs/Brands/BrandsIndex.php` (patron de CRUD/search/paginacion)

### Completion Notes List

- Story seleccionada automaticamente desde `sprint-status.yaml` (primer `backlog` en `development_status`).
- Guardrails explicitos para evitar errores tipicos: RBAC server-side, reuso de patrones Livewire, no mezclar Empleados con Usuarios.
- Versiones fijadas por repo (Laravel 11 / Livewire 3 / Bootstrap 5) para evitar implementaciones incompatibles.
- Implementado: Migración `employees` con campos `id`, `rpe` (unique), `name`, `department`, `job_title`, timestamps.
- Implementado: Modelo `Employee` con normalización de texto (trim + colapsar espacios).
- Implementado: Ruta `/employees` con middleware `auth`, `active`, `can:inventory.manage`.
- Implementado: Componente Livewire `EmployeesIndex` con CRUD completo, búsqueda por RPE/nombre, paginación.
- Implementado: Vista Blade con Bootstrap 5, toasts, long-request overlay.
- Implementado: Item de menú en sidebar para Admin/Editor.
- Tests: cobertura RBAC, validaciones, busqueda y operaciones CRUD (incluye rol Editor).
- Suite completa: 152 tests, 407 assertions - Sin regresiones.
- Pint: Sin errores de estilo (corregidos automáticamente).
- PHPStan: Sin errores.

### File List (esperado en implementacion)

- `_bmad-output/implementation-artifacts/4-1-crear-y-mantener-empleados-rpe.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `gatic/routes/web.php`
- `gatic/resources/views/layouts/partials/sidebar-nav.blade.php`
- `gatic/app/Models/Employee.php`
- `gatic/app/Actions/Employees/UpsertEmployee.php`
- `gatic/app/Actions/Employees/DeleteEmployee.php`
- `gatic/app/Livewire/Employees/EmployeesIndex.php`
- `gatic/resources/views/livewire/employees/employees-index.blade.php`
- `gatic/database/migrations/*_create_employees_table.php`
- `gatic/tests/Feature/Employees/EmployeesTest.php`

## Senior Developer Review (AI)

Reviewer: Carlos  
Date: 2026-01-14

### Resumen

- Se alineo el estado de la story con el tracking del sprint.
- Se elimino ruido de cambios ajenos (restaurado `git_diff_3_2.txt` para que no aparezca en el diff de esta story).
- Se centralizo el per-page del listado de Empleados via `config('gatic.ui.pagination.per_page')` (sin numeros magicos en el componente).
- Se amplio cobertura de tests para rol Editor (crear/editar).
- Se ajusto el componente para limpiar validaciones al cancelar/guardar.

### Nota de ejecucion

- No se pudo correr `php artisan test` en este equipo por version de PHP local (< 8.2). Validar en Sail/CI.

## Change Log

- 2026-01-14: Code review (AI) + fixes aplicados (estado, paginacion, tests Editor, limpieza de validaciones).
