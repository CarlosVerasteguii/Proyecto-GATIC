# Story 3.2: Crear y mantener Activos (serializados) con reglas de unicidad

Status: done

Story Key: `3-2-crear-y-mantener-activos-serializados-con-reglas-de-unicidad`  
Epic: `3` (Gate 2: Inventario navegable)

Fuentes:
- `_bmad-output/project-planning-artifacts/epics.md` (Epic 3 / Story 3.2; FR10, FR11)
- `_bmad-output/prd.md` (FR10, FR11)
- `_bmad-output/architecture.md` (stack + estructura + constraints DB para `assets`)
- `_bmad-output/project-planning-artifacts/ux-design-specification.md` (patrones UX base)
- `docsBmad/project-context.md` (bible: glosario + reglas cr�ticas)
- `project-context.md` (reglas resumidas para agentes)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (orden + notas del Epic 3)
- `_bmad-output/implementation-artifacts/3-1-crear-y-mantener-productos.md` (patrones Inventory + gates + tests)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Admin/Editor,
I want crear y mantener Activos serializados (unidades) para Productos serializados, capturando `serial` y `asset_tag` cuando aplique,
so that el inventario sea confiable y se pueda identificar cada unidad f�sica sin ambig�edad (FR10, FR11).

## Acceptance Criteria

### AC1 - Acceso por rol (defensa en profundidad)

**Given** un usuario autenticado con rol Admin o Editor  
**When** navega a la gesti�n de Activos de un Producto serializado  
**Then** puede ver el listado y crear/editar Activos

**And** todas las acciones se autorizan server-side (rutas + Livewire).

**Given** un usuario autenticado con rol Lector  
**When** intenta acceder a rutas de gesti�n (create/edit) o ejecutar acciones Livewire de guardado  
**Then** el servidor bloquea (403 o equivalente).

### AC2 - Solo aplica a Productos serializados (guardrail)

**Given** un Producto cuya Categor�a NO es serializada (`is_serialized=false`)  
**When** el usuario intenta crear/editar Activos para ese Producto  
**Then** el sistema bloquea la operaci�n y muestra un mensaje claro (no hay Activos para productos por cantidad).

### AC3 - Unicidad de `serial` por Producto (FR11)

**Given** un Producto cuya Categor�a es serializada  
**When** se registra un Activo con `serial`  
**Then** el sistema aplica unicidad de `serial` por Producto

**And** rechaza duplicados con un mensaje claro.

### AC4 - `asset_tag` condicional y �nico global (FR11)

**Given** una Categor�a que requiere `asset_tag` (`requires_asset_tag=true`)  
**When** se registra un Activo sin `asset_tag`  
**Then** el sistema rechaza la creaci�n/edici�n

**And** muestra un mensaje claro indicando que es obligatorio.

**Given** una Categor�a que requiere `asset_tag`  
**When** se registra un Activo con `asset_tag`  
**Then** el sistema aplica unicidad global de `asset_tag`

**And** rechaza duplicados con un mensaje claro.

### AC5 - Captura m�nima adicional (estado + ubicaci�n) para coherencia de inventario

**Given** el formulario de Activo  
**When** el usuario guarda con datos v�lidos  
**Then** el Activo se persiste asociado al Producto

**And** captura al menos:
- `location_id` (FK a `locations.id`, solo ubicaciones activas)
- `status` (default `Disponible`, permitido: `Disponible`, `Asignado`, `Prestado`, `Pendiente de Retiro`, `Retirado`)

**And** NO captura tenencia/Empleado (en Epic 3 la tenencia es N/A; se implementa en Epic 4/5).

## Tasks / Subtasks

1) Data model (DB) (AC: 2-5)
- [x] Crear migraci�n `assets` con:
  - [x] `id`
  - [x] `product_id` (FK real a `products.id`, requerido)
  - [x] `location_id` (FK real a `locations.id`, requerido)
  - [x] `serial` (string, requerido)
  - [x] `asset_tag` (string, nullable; requerido solo si la categor�a lo exige)
  - [x] `status` (string, requerido; default `Disponible`)
  - [x] `deleted_at` + timestamps (soft-delete)
- [x] Constraints DB:
  - [x] unique `(product_id, serial)`
  - [x] unique `asset_tag` (permitiendo m�ltiples NULLs)
- [x] Charset/collation consistente MySQL 8 (`utf8mb4` / `utf8mb4_0900_ai_ci`)

2) Dominio (AC: 2-5)
- [x] `app/Models/Asset.php` con relaciones:
  - [x] `belongsTo(Product::class)`
  - [x] `belongsTo(Location::class)`
- [x] Normalizaci�n:
  - [x] `serial`: trim (sin colapsar significado)
  - [x] `asset_tag`: trim + uppercase (evita duplicados por casing)

3) UI (Livewire + Bootstrap) (AC: 1-5)
- [x] Listado de Activos por Producto (tabla densa, b�squeda por `serial`/`asset_tag`, acciones Create/Edit)
- [x] Formulario Create/Edit con validaciones claras y toasts de �xito
- [x] Guardrails visibles: si el Producto no es serializado, bloquear la vista y explicar

4) Routing + navegaci�n (AC: 1)
- [x] Rutas en ingl�s bajo `inventory` (ej. `/inventory/products/{product}/assets`)
- [x] Link desde Productos hacia Activos (solo si producto serializado)

5) Tests (AC: 1-5)
- [x] Feature tests: RBAC (Admin/Editor vs Lector), guardrail producto no serializado, unicidades, `asset_tag` condicional

## Dev Notes

### Developer Context (por qu� existe esta story)

- Esta story habilita la gesti�n base de **Activos** (unidades serializadas) para que el inventario sea navegable y consistente en historias siguientes (listados/detalles/conteos).
- Un Activo es **hijo de un Producto** cuya Categor�a es serializada (`categories.is_serialized=true`).
- En **Epic 3** NO existe tenencia real (Empleado RPE) ni movimientos (asignar/prestar/devolver): si aparece un concepto de tenencia en UI, debe mostrarse como **N/A**.

### Alcance (DO)

- CRUD de Activos para Productos serializados: listar, crear, editar.
- Reglas de unicidad/validaci�n: `serial` por Producto + `asset_tag` global (cuando aplique).
- Captura m�nima adicional para coherencia futura: `location_id` y `status` (default `Disponible`).

### Fuera de alcance (NO hacer en esta story)

- Movimientos / transiciones por operaci�n diaria (Epic 5).
- Empleados (RPE) o cualquier forma de asignar/prestar/devolver a una persona (Epic 4/5).
- B�squeda unificada global (Epic 6) o dashboards (Epic 5/6).
- Locks / Tareas Pendientes (Epic 7).

### Technical Requirements (guardrails para el dev agent)

- Stack fijo: Laravel 11 + PHP 8.2+ + MySQL 8 + Blade + Livewire 3 + Bootstrap 5 (no introducir frameworks nuevos).
- Idioma: identificadores (c�digo/DB/rutas) en ingl�s; copy/UI en espa�ol.
- Autorizaci�n obligatoria server-side:
  - rutas bajo `can:inventory.view` para lectura
  - rutas/acciones de guardado bajo `can:inventory.manage`
  - `Gate::authorize()` tambi�n dentro de Livewire (ej. `mount()`, `save()`, hooks `updated*()`).
- Validaciones deben cubrir:
  - producto serializado requerido
  - `serial` requerido + unicidad por producto
  - `asset_tag` requerido solo si `category.requires_asset_tag=true` + unicidad global cuando se use
  - `location_id` existente y activo (no soft-deleted)
  - `status` dentro del set permitido
- Errores y UX:
  - mensajes de validaci�n en espa�ol, accionables
  - evitar "500" por entradas inv�lidas: validar antes

### Architecture Compliance (lo que NO se negocia)

- Estructura por m�dulo (ver `_bmad-output/architecture.md`):
  - `app/Models/Asset.php`
  - `app/Livewire/Inventory/Assets/*` (o anidado bajo `Inventory/Products/Assets/*` si se decide por recurso hijo)
  - views en `resources/views/livewire/inventory/assets/*`
  - si se usan casos de uso: `app/Actions/Inventory/*` (recomendado para normalizaci�n/guardar)
- Constraints DB alineados a arquitectura:
  - `assets` unique `(product_id, serial)`
  - `assets.asset_tag` unique global (nullable)
- No "tenencia" en Epic 3:
  - no crear `employee_id` en `assets` ni relaciones a Empleados
  - no crear `movements` ni historial (eso es Epic 5)

### Library / Framework Requirements

- Livewire 3:
  - rutas deben apuntar a componentes Livewire (no controllers) salvo "bordes" (descargas/JSON puntual).
  - evitar queries en `render()`; precargar cat�logos/datos en `mount()` como se hizo en `ProductForm`.
  - autorizar en hooks `updated*()` si estos afectan reglas (evitar que un Lector dispare l�gica).
- Bootstrap 5:
  - usar componentes/utilidades Bootstrap ya presentes; no agregar frameworks UI.
  - toasts/mensajes deben seguir el patr�n existente (flash `with('status', ...)` + contenedor global).
- Dependencias:
  - no introducir paquetes nuevos para este CRUD; resolver con Eloquent + Livewire + reglas de validaci�n.

### Project Structure Notes

- Rutas (paths) en ingl�s y kebab-case:
  - recomendado: `/inventory/products/{product}/assets` (recurso hijo del Producto)
- Route names en dot.case:
  - recomendado: `inventory.products.assets.index|create|edit`
- Livewire:
  - recomendado: `gatic/app/Livewire/Inventory/Assets/AssetsIndex.php` + `AssetForm.php`
  - alternativa: `gatic/app/Livewire/Inventory/Products/Assets/*` si se quiere dejar claro el parent `Product`
- Views:
  - `gatic/resources/views/livewire/inventory/assets/*` (o el path equivalente si se anida)
- Migraci�n + modelo:
  - `gatic/database/migrations/*create_assets_table.php`
  - `gatic/app/Models/Asset.php`
- Navegaci�n:
  - agregar entry/CTA desde Productos hacia Activos solo cuando el Producto sea serializado

### Testing Requirements

- Agregar `gatic/tests/Feature/Inventory/AssetsTest.php` (patr�n similar a `ProductsTest`):
  - RBAC:
    - Admin/Editor: puede ver index + create/edit
    - Lector: puede ver index (si se decide exponer) pero NO create/edit; no puede ejecutar `save()` (AuthorizationException)
  - Guardrails:
    - no permitir crear Activo si el Producto no es serializado
  - Validaciones:
    - `serial` requerido
    - `serial` �nico por producto (permitir mismo serial en productos distintos)
    - `asset_tag` requerido cuando `category.requires_asset_tag=true`
    - `asset_tag` �nico global (cuando no null)
    - `location_id` requerido y existente (y activo)
  - Persistencia:
    - normalizaci�n (`serial` trim, `asset_tag` uppercase) reflejada en DB

### Previous Story Intelligence (de 3-1 Productos)

- Evitar queries en `render()`: precargar cat�logos/datos en `mount()` y pasar arrays al view (ver `gatic/app/Livewire/Inventory/Products/ProductForm.php`).
- Autorizar tambi�n en hooks Livewire (`updated*()`), no solo en rutas (defensa en profundidad).
- Preferir reglas de validaci�n expl�citas con mensajes en espa�ol (sin ambig�edad).
- Mantener consistencia de rutas: `/inventory/...` y nombres `inventory.*`.

### Git Intelligence Summary (patrones recientes)

- Commits recientes relevantes:
  - `feat(inventory): crear y mantener Productos... (3-1)` establece el patr�n para Inventory (Livewire + rutas + tests).
  - fixes de CI aseguran que tests corran sin Vite manifest y que Larastan est� verde.
- Implicaci�n para esta story:
  - seguir exactamente el estilo de m�dulo + tests de `gatic/tests/Feature/Inventory/ProductsTest.php`
  - no introducir dependencias ni cambios de tooling

### Latest Tech Information (web research)

- Bootstrap 5:
  - docs indican que la rama actual es 5.3 y el �ltimo update listado es `v5.3.8`.
  - fuente: `https://getbootstrap.com/docs/versions/`
  - guardrail: este repo hoy usa `bootstrap@^5.2.3` (ver `gatic/package.json`); NO actualizar Bootstrap en esta story salvo necesidad expl�cita.
- Livewire:
  - hay releases activos en la serie 3.x (ej. 3.6).
  - fuentes: `https://laravel-news.com/livewire-36-released`, `https://github.com/livewire/livewire/releases`
  - guardrail: mantener Livewire dentro de lo ya definido en `gatic/composer.json` (no subir versi�n para este CRUD).
- Laravel 11:
  - m�nimo PHP 8.2; consistente con este repo.
  - fuente: `https://laravel-news.com/laravel-11`

### References

- Backlog (AC base):
  - `_bmad-output/project-planning-artifacts/epics.md` (Epic 3 / Story 3.2)
- Requisitos (FR10/FR11):
  - `_bmad-output/prd.md` (secci�n "Inventory: Products & Assets")
- Arquitectura/estructura:
  - `_bmad-output/architecture.md` (secci�n "Requirements to Structure Mapping" + constraints DB de `assets`)
- Reglas de dominio (bible):
  - `docsBmad/project-context.md` (Producto vs Activo; unicidades; estados; sin WebSockets; tenencia N/A en Epic 3)
- Reglas operativas para agentes:
  - `project-context.md` (stack, idioma, reglas cr�ticas, testing)
- UX base:
  - `_bmad-output/project-planning-artifacts/ux-design-specification.md` (tablas densas, loaders, toasts, accesibilidad)
- Implementaci�n existente (patrones a copiar):
  - `gatic/app/Livewire/Inventory/Products/*`
  - `gatic/tests/Feature/Inventory/ProductsTest.php`

## Story Completion Status

- Status: `done`
- Nota de completitud: "CRUD de Activos serializados implementado (migraci�n + modelo + UI Livewire + rutas + tests)."
- Siguiente paso recomendado: correr `code-review`.

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `docker compose -f gatic/compose.yaml up -d`
- `docker compose -f gatic/compose.yaml exec -T laravel.test php artisan test`
- `docker compose -f gatic/compose.yaml exec -T laravel.test ./vendor/bin/pint --test`
- `docker compose -f gatic/compose.yaml exec -T laravel.test ./vendor/bin/phpstan analyse`

### Completion Notes List

- Implementado `assets` (migraci�n + constraints: unique `(product_id, serial)` y unique `asset_tag`).
- Agregado `Asset` con normalizaci�n (`serial` trim, `asset_tag` uppercase) y estados permitidos.
- Agregadas rutas + UI Livewire (index + create/edit) con guardrails para productos no serializados y RBAC server-side.
- Agregados feature tests para RBAC, guardrails, unicidades, `asset_tag` condicional y ubicaciones activas.
- Code review follow-up: optimizado el listado para no consultar Activos cuando el Producto no es serializado.
- Code review follow-up: ajustado el test de Lector para validar bloqueo real de Livewire en `AssetForm`.

### File List

- `_bmad-output/implementation-artifacts/3-2-crear-y-mantener-activos-serializados-con-reglas-de-unicidad.md`
- `_bmad-output/implementation-artifacts/architecture.md`
- `_bmad-output/implementation-artifacts/epics.md`
- `_bmad-output/implementation-artifacts/prd.md`
- `_bmad-output/implementation-artifacts/ux.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (actualizado a `done`)
- `gatic/app/Livewire/Inventory/Assets/AssetForm.php`
- `gatic/app/Livewire/Inventory/Assets/AssetsIndex.php`
- `gatic/app/Livewire/Inventory/Products/ProductForm.php`
- `gatic/app/Models/Asset.php`
- `gatic/database/migrations/2026_01_02_000001_create_assets_table.php`
- `gatic/resources/views/livewire/inventory/assets/asset-form.blade.php`
- `gatic/resources/views/livewire/inventory/assets/assets-index.blade.php`
- `gatic/resources/views/livewire/inventory/products/products-index.blade.php`
- `gatic/routes/web.php`
- `gatic/tests/Feature/Inventory/AssetsTest.php`

### Change Log

- Inventario: CRUD de Activos serializados (tabla `assets`) por Producto.
- UI: rutas `/inventory/products/{product}/assets` + Livewire (index/create/edit) + guardrails.
- Review: follow-ups aplicados (test Livewire de Lector + micro-optimizacion de listado + sync de File List).
- Tests: feature tests RBAC/guardrails/unicidades/ubicaci�n activa.

## Senior Developer Review (AI)

Fecha: 2026-01-02

Resultado: **Aprobado** (se aplicaron los follow-ups del review).

Acciones realizadas:
- Sync de documentacion: el Git detectaba docs adicionales en `_bmad-output/implementation-artifacts/*` y se agregaron a la "File List".
- Tests: se ajusto el test de Lector para validar el bloqueo real por autorizacion al montar `AssetForm`.
- Performance: `AssetsIndex` ya no ejecuta query/paginacion cuando el Producto no es serializado (solo muestra el guardrail).
