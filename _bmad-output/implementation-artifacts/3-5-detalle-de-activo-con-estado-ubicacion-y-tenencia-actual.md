# Story 3.5: Detalle de Activo con estado, ubicación y tenencia actual

Status: done

Story Key: `3-5-detalle-de-activo-con-estado-ubicacion-y-tenencia-actual`  
Epic: `3` (Gate 2: Inventario navegable)

Fuentes:
- `_bmad-output/implementation-artifacts/epics.md` (Story 3.5; FR13)
- `_bmad-output/implementation-artifacts/prd.md` (FR13)
- `_bmad-output/implementation-artifacts/architecture.md` (stack + constraints + RBAC + sin WebSockets)
- `_bmad-output/implementation-artifacts/ux.md` (patrones UX: master-detail, tablas densas, claridad de estado/tenencia)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (orden + notas del Epic 3: tenencia N/A)
- `docsBmad/project-context.md` (bible: estados + semántica + tenencia N/A en Epic 3)
- `project-context.md` (reglas críticas para agentes)
- `_bmad-output/implementation-artifacts/3-4-detalle-de-producto-con-conteos-y-desglose-por-estado.md` (patrones + learnings)
- `_bmad-output/implementation-artifacts/3-2-crear-y-mantener-activos-serializados-con-reglas-de-unicidad.md` (estructura actual de assets + RBAC)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a usuario interno (Soporte TI),
I want ver el detalle de un Activo con su estado actual, ubicación y tenencia actual,
so that pueda saber dónde está y qué disponibilidad real tiene, y decidir la acción siguiente sin pasos extra (FR13).

## Acceptance Criteria

### AC1 - Acceso por rol (defensa en profundidad)

**Given** un usuario autenticado (Admin/Editor/Lector)  
**When** navega al detalle de un Activo (`/inventory/products/{product}/assets/{asset}`)  
**Then** puede ver la pantalla

**And** el servidor autoriza el acceso con `Gate::authorize('inventory.view')`.

### AC2 - Guardrails de routing (Activo pertenece al Producto)

**Given** un Activo existente  
**When** el usuario intenta abrir el detalle usando un `product_id` que no coincide con el Activo  
**Then** el servidor responde `404` (evita filtrar información o mostrar datos cruzados).

### AC3 - Información mínima del detalle (estado + ubicación)

**Given** un Activo existente (no soft-deleted)  
**When** el usuario abre el detalle  
**Then** ve como mínimo:
- `Serial`
- `Asset tag` (o `-` si no aplica)
- `Estado` (texto visible; no solo color)
- `Ubicación` (nombre de `locations`)

### AC4 - Tenencia actual (Epic 3 = N/A)

**Given** un Activo existente  
**When** el usuario abre el detalle  
**Then** ve una sección "Tenencia actual" con valor:
`N/A (se habilita en Épica 4/5)`

**And** NO se consulta ni depende de Empleados/RPE ni de Movimientos en Epic 3.

### AC5 - Navegación y acciones

**Given** el detalle de Activo  
**When** el usuario llega desde Inventario > Activos  
**Then** puede volver al listado de Activos del Producto sin perder contexto

**And** puede navegar al detalle del Producto padre.

**And** si el usuario tiene permiso `inventory.manage`, ve la acción "Editar" (link a `/edit`).

### AC6 - Rendimiento (anti N+1)

**Given** el detalle de Activo  
**When** se renderiza  
**Then** el sistema carga el Activo con sus relaciones requeridas (`location`, `product`) sin N+1.

## Tasks / Subtasks

1) Routing + pantalla (AC: 1, 2, 5)
- [x] Agregar ruta `GET /inventory/products/{product}/assets/{asset}` hacia un componente Livewire `Inventory/Assets/AssetShow`.
- [x] Agregar constraints numéricos (`whereNumber`) para `product` y `asset`.

2) Carga y guardrails (AC: 1-4, 6)
- [x] En `mount()`, ejecutar `Gate::authorize('inventory.view')`.
- [x] Cargar `Product` con `category` y validar que es serializado (si no, `404`).
- [x] Cargar `Asset` verificando `assets.product_id = product.id` y `assets.deleted_at IS NULL`; si no existe, `404`.
- [x] Cargar relación `location` para mostrar `Ubicación`.

3) UI (Blade + Bootstrap) (AC: 3-5)
- [x] Renderizar tarjetas/tabla densa con `Serial`, `Asset tag`, `Estado`, `Ubicación`.
- [x] Mostrar badge de estado con texto (alineado a semántica del dominio).
- [x] Mostrar "Tenencia actual: N/A (se habilita en Épica 4/5)".
- [x] Incluir CTAs: "Volver" (al listado de Activos), "Producto" (detalle de Producto), "Editar" (solo `inventory.manage`).

## Dev Notes

### DEV AGENT GUARDRAILS (no negociables)

- En Epic 3, la tenencia real (Empleado RPE) y Movimientos viven en Épica 4/5: en este detalle, **Tenencia = N/A** siempre.
- Mantener semántica de estados canónicos en `Asset` (no inventar estados nuevos): `Disponible`, `Asignado`, `Prestado`, `Pendiente de Retiro`, `Retirado`.
- Autorización server-side obligatoria (`Gate::authorize('inventory.view')`); la UI es defensa en profundidad, no la fuente de permisos.
- Asegurar aislamiento: el Activo que se muestra debe pertenecer al Producto del route (`assets.product_id`), o retornar `404`.

### Developer Context (por qué existe esta story)

Esta pantalla responde rápidamente “¿dónde está esta unidad?” y “¿en qué estado está?” sin requerir acciones operativas (asignar/prestar/devolver), que se habilitan hasta Épica 5. Debe ser una vista de consulta clara, con navegación fácil para volver al flujo de Inventario.

## Technical Requirements

- Identificadores de código/rutas/DB en inglés; copy/UI en español.
- Livewire 3: route → componente (sin Controllers para esta pantalla).
- Validación de parámetros: usar constraints `whereNumber` y también validar en `mount()` (si no es dígito, `404`).
- Soft-delete: no mostrar Activos eliminados; si el `asset` no existe o está soft-deleted, `404`.
- Cargar relaciones explícitas necesarias (`location`) para evitar N+1.

## Architecture Compliance

- Mantener el patrón existente en inventario: `Inventory/*` (Livewire) + autorización con Gates.
- No introducir WebSockets; no usar eventos en tiempo real (solo polling cuando aplique; esta pantalla no lo requiere).
- Mantener integridad y aislamiento: no permitir ver un Activo “por ID” si no pertenece al Producto del route.

## Library / Framework Requirements

- Laravel 11 (repo usa `laravel/framework:^11.31`): no cambiar versión por esta story.
- Livewire 3 (`livewire/livewire:^3.0`): seguir el patrón `#[Layout('layouts.app')]` + `mount()` + `render()`.
- Bootstrap 5 (repo usa `bootstrap:^5.2.3`): no actualizar Bootstrap; usar componentes existentes (card, table, badges, buttons).

## File Structure Requirements

- Crear Livewire component: `gatic/app/Livewire/Inventory/Assets/AssetShow.php`
- Crear view: `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php`
- Actualizar listado de Activos para enlazar a detalle: `gatic/resources/views/livewire/inventory/assets/assets-index.blade.php`
- Agregar ruta show (con `whereNumber`): `gatic/routes/web.php`
- Agregar/ajustar tests: `gatic/tests/Feature/Inventory/AssetsTest.php`

## Testing Requirements

- Feature tests (mínimo):
  - RBAC: Admin/Editor/Lector pueden ver el detalle (`inventory.view`).
  - Isolation: si `asset` no pertenece al `product` del route → `404`.
  - Soft-delete: Activo eliminado → `404`.
  - UI: el detalle muestra “Tenencia actual: N/A (se habilita en Épica 4/5)”.
- Comando recomendado (Sail, desde `gatic/`):
  - `vendor\\bin\\sail artisan test --filter AssetsTest`

## Previous Story Intelligence

- Story 3.2 estableció el modelo `assets` y RBAC: reusar constantes de `Asset` para estados y mantener `Gate::authorize(...)` server-side.
- Story 3.4 aplicó guardrails de routing con `whereNumber(...)` y validación adicional en `mount()`; replicar para evitar colisiones con `/create` y rutas inválidas.
- Mantener UX “backoffice” ya usada: header con CTAs (Volver / Editar) y contenido en card/tabla densa (Bootstrap 5).

## Git Intelligence Summary

- Commits recientes relevantes:
  - `bcfb674` feat(inventory): detalle de Producto con conteos y desglose por estado (3-4) → patrón de pantalla “show” + route constraints.
  - `d2d4fec` feat(inventory): implementación código - indicadores disponibilidad (3-3) → estilo de queries/guardrails.
  - `d9e754c` feat(inventory): indicadores de disponibilidad en listado de productos (3-3) → documentación y estructura.
  - `4dc901b`/`5248ba8` feat(inventory): crear y mantener Activos serializados (3-2) → módulo `Inventory/Assets/*` + tests `AssetsTest`.
- Mantener consistencia con el estilo ya establecido: autorización en `mount()` y `render()`, rutas con `whereNumber`, y vistas Bootstrap (card/table).

## Latest Tech Information

- Bootstrap: hay releases más nuevos (ej. `v5.3.8`), pero este repo está en `bootstrap:^5.2.3`; no actualizar Bootstrap por esta story.
  - Fuente: `https://blog.getbootstrap.com/2025/08/25/bootstrap-5-3-8/`
- Livewire: la serie 3.x continúa activa; usar patrones oficiales de Livewire 3.
  - Fuentes: `https://github.com/livewire/livewire/releases`, `https://livewire.laravel.com/`
- Laravel 11: mantener el rango actual del repo y preferir documentación oficial vigente.
  - Fuente: `https://laravel.com/docs/11.x`

## Project Context Reference

- Fuente de verdad: `docsBmad/project-context.md` (gana ante cualquier contradicción).
- Reglas críticas relevantes:
  - Épica 3: “Tenencia actual” debe mostrarse como `N/A (se habilita en Épica 4/5)`.
  - Estados canónicos de Activo: `Disponible`, `Asignado`, `Prestado`, `Pendiente de Retiro`, `Retirado`.
  - Sin WebSockets; mantener simplicidad (polling solo cuando aplique).
  - Autorización server-side obligatoria en todas las pantallas.

### References

- Backlog/AC base: `_bmad-output/implementation-artifacts/epics.md` (Epic 3 / Story 3.5; FR13)
- Reglas de dominio (bible): `docsBmad/project-context.md` (estados; tenencia N/A en Epic 3)
- UX baseline: `_bmad-output/implementation-artifacts/ux.md` (claridad de estado/tenencia; navegación rápida)
- Arquitectura/estructura: `_bmad-output/implementation-artifacts/architecture.md` (stack; patrones de estructura)
- Implementación existente relevante:
  - `gatic/app/Models/Asset.php` (estados canónicos)
  - `gatic/app/Livewire/Inventory/Assets/AssetsIndex.php` (módulo actual)
  - `gatic/routes/web.php` (patrón de rutas `inventory.*`)

## Story Completion Status

 - Status: `done`
- Nota de completitud: "Implementación completada y revisada: AC verificados, fixes de code review aplicados, story lista como done."

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `vendor\\bin\\sail artisan test --filter AssetsTest`
- `vendor\\bin\\sail composer pint -- --test` (o `./vendor/bin/pint --test` dentro del contenedor)
- `vendor\\bin\\sail composer larastan` (si existe script) o `./vendor/bin/phpstan analyse`

### Implementation Plan

- Agregar ruta `inventory.products.assets.show` con `whereNumber`.
- Crear Livewire `AssetShow` con guardrails: authorize + producto serializado + asset pertenece al producto.
- Crear `asset-show.blade.php` (Bootstrap) con estado + ubicación + tenencia N/A y CTAs.
- Actualizar `assets-index.blade.php` para enlazar al detalle.
- Agregar tests de detalle/aislamiento/soft-delete.

### Completion Notes List

- Definida la pantalla de detalle de Activo (solo lectura) para Epic 3, con “Tenencia actual: N/A (Épica 4/5)”.
- Definidos guardrails críticos (RBAC server-side + asset pertenece al producto + soft-delete) para prevenir fugas/bugs.
- Definida estructura de archivos y tests mínimos para implementar sin regresiones.

### File List

- `_bmad-output/implementation-artifacts/3-5-detalle-de-activo-con-estado-ubicacion-y-tenencia-actual.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `gatic/app/Livewire/Inventory/Assets/AssetsIndex.php`
- `gatic/app/Livewire/Inventory/Assets/AssetShow.php`
- `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php`
- `gatic/resources/views/livewire/inventory/assets/assets-index.blade.php`
- `gatic/routes/web.php`
- `gatic/tests/Feature/Inventory/AssetsTest.php`

---

## Senior Developer Review (AI)

Fecha: 2026-01-03

Resultado: **Aprobado (done)** tras corregir issues detectados en el review.

### Issues Encontrados y Resueltos

| # | Severidad | Descripción | Estado |
|---|-----------|-------------|--------|
| 1 | MEDIO | Badge de estado sin semántica visual (siempre gris) | ✅ Corregido |
| 2 | MEDIO | “Volver” no preservaba contexto (búsqueda/paginación) | ✅ Corregido (`q` + `page`) |
| 3 | MEDIO | Faltaba test HTTP explícito para Lector en ruta show | ✅ Corregido |

### Notas del Reviewer

- La navegación de retorno ahora preserva contexto vía query string (`q` para búsqueda y `page` para paginación).
- Se mantuvo “Tenencia actual: N/A (se habilita en Épica 4/5)” sin depender de Empleados/RPE ni Movimientos (Epic 3).

### Change Log

- 2026-01-03: Code review adversarial (Senior Dev) - fixes aplicados (badge de estado, volver con contexto, test HTTP show) + status actualizado a done + sprint-status sync.
