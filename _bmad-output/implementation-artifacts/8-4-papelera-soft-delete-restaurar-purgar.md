# Story 8.4: Papelera (soft-delete, restaurar, purgar)

Status: done

Story Key: `8-4-papelera-soft-delete-restaurar-purgar`  
Epic: `8` (Gate 5: Trazabilidad y evidencia)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Fecha: 2026-01-24

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/epics.md` (Epic 8 / Story 8.4; FR35)
- `_bmad-output/implementation-artifacts/epics-github.md` (Gate 5 / G5-E03 Papelera: Issues #106–#108)
- `docsBmad/gates-execution.md` (Gate 5 DoD: papelera soft-delete/restaurar/vaciar)
- `_bmad-output/implementation-artifacts/prd.md` (FR35, NFR4, NFR5, NFR8)
- `_bmad-output/implementation-artifacts/architecture.md` (soft-delete, Livewire-first, audit best-effort, naming/estructura)
- `_bmad-output/implementation-artifacts/ux.md` (patrones UX; acciones destructivas; confirmaciones; long-request)
- `docsBmad/project-context.md` + `project-context.md` (retención indefinida; Admin vacía papelera; reglas críticas)
- `docsBmad/rbac.md` + `gatic/app/Providers/AuthServiceProvider.php` (gates: `admin-only`, `inventory.manage`, `catalogs.manage`)
- Story previa (Epic 8): `_bmad-output/implementation-artifacts/8-3-adjuntos-seguros-con-control-de-acceso.md` (entity visibility y patterns de auditoría)
- Implementación existente (Papelera Catálogos): `gatic/app/Livewire/Catalogs/Trash/CatalogsTrash.php`, `gatic/resources/views/livewire/catalogs/trash/catalogs-trash.blade.php`
- Versiones reales del repo: `gatic/composer.lock`, `gatic/package.json`

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Admin,  
I want una **Papelera** para **restaurar** o **purgar/vaciar** elementos eliminados (soft-delete),  
so that el sistema sea tolerante a errores operativos y mantenga historial (FR35).

### Alcance (Gate 5 / G5-E03: Papelera)

- Soft-deletes consistentes en entidades clave (G5-T08).
- UI de Papelera (Admin) para listar, buscar, restaurar y vaciar (G5-T09).
- Restauración conserva historial y se registra en auditoría (G5-T10).

### Fuera de alcance (NO hacer aquí)

- Purga automática programada (cron). La política es retención indefinida hasta acción explícita de Admin.
- Cambiar el modelo de auditoría (solo se agregan acciones/eventos nuevos).

## Acceptance Criteria

### AC1 — Soft-deletes consistentes (G5-T08)

**Given** una entidad “eliminable” en el MVP  
**When** un usuario autorizado elimina el registro  
**Then** la eliminación es **soft-delete** (set `deleted_at`, sin borrado físico)  
**And** por defecto queda **excluido** de queries y rutas “normales” (sin `withTrashed()` accidental).

**Definition of Done (AC1):** el código deja explícito **qué entidades usan soft-delete** y están cubiertas por tests.

### AC2 — UI Papelera (Admin) con tabs, filtros y búsqueda (G5-T09)

**Given** un Admin autenticado  
**When** entra a la vista de Papelera  
**Then** puede alternar tabs por tipo como mínimo: **Productos**, **Activos**, **Empleados**  
**And** puede buscar/filtrar por campos relevantes (nombre, serial/asset_tag, RPE, etc.)  
**And** ve la **fecha de eliminación** (`deleted_at`).

### AC3 — Restaurar por ítem (G5-T09)

**Given** un registro en papelera (`onlyTrashed`)  
**When** Admin lo restaura  
**Then** vuelve a estar disponible en el sistema  
**And** la restauración **conserva historial y relaciones** según corresponda (G5-T10).

### AC4 — Vaciar papelera / Purga definitiva (G5-T09 + FR35)

**Given** registros en papelera  
**When** Admin ejecuta “Vaciar papelera” (o purga definitiva)  
**Then** el sistema hace **borrado físico** (`forceDelete`) **solo** sobre registros soft-deleted  
**And** pide confirmación explícita antes de ejecutar  
**And** maneja restricciones de integridad (FKs `restrictOnDelete`) de forma segura:
- si un ítem **no se puede** purgar por dependencias/historial → se mantiene en papelera y se muestra mensaje claro.

### AC5 — Auditoría best-effort (FR32/NFR8, G5-T10)

**Given** una eliminación/restauración/purga desde la papelera  
**When** la acción se ejecuta correctamente  
**Then** se registra un evento en `audit_logs` (best-effort, no bloqueante) con actor y sujeto  
**And** el evento queda visible en el módulo de auditoría (Story 8.1).

### AC6 — RBAC (defensa en profundidad)

**Given** un usuario con rol Editor o Lector  
**When** intenta acceder a la UI de Papelera o disparar acciones de restaurar/vaciar  
**Then** el servidor bloquea la operación (403 o equivalente)  
**And** la UI no expone acciones ni links a dichos usuarios.

### AC7 — Regresión soft-delete (Lección Epic 6)

**Given** registros soft-deleted en entidades que participan en conteos/listados  
**When** se consultan listados y conteos “normales”  
**Then** los soft-deleted **no** aparecen ni se cuentan (por defecto)  
**And** existe al menos 1 test de regresión que lo verifica (crear soft-deleted y validar exclusión).

### AC8 — UX de acciones destructivas

**Given** la acción “Vaciar/Purgar”  
**When** se presenta en UI  
**Then** se usa estilo destructivo (rojo) solo para acciones irreversibles  
**And** se integra `long-request` si la acción puede tardar (loader + cancelar).

## Tasks / Subtasks

1) Soft-deletes consistentes (AC: 1, 6, 7)
- [x] Agregar migration para `employees.deleted_at` + index (si aplica)
- [x] Agregar `SoftDeletes` a `App\\Models\\Employee`
- [x] Alinear acción “Eliminar empleado” a soft-delete (y RBAC admin-only si corresponde)
- [x] Validar que pantallas normales excluyen soft-deleted (sin `withTrashed()` accidental)
- [x] Documentar en código/story qué entidades usan soft delete (mínimo: Product/Asset/Employee + Catálogos)

2) UI Papelera (Admin) con tabs (AC: 2, 6, 8)
- [x] Agregar ruta `GET /admin/trash` (`can:admin-only`)
- [x] Implementar Livewire `App\\Livewire\\Admin\\Trash\\TrashIndex`
  - [x] Tabs: Productos / Activos / Empleados
  - [x] Búsqueda/filtros por tab + paginación
  - [x] Mostrar `deleted_at`
  - [x] Integrar `<x-ui.long-request />` para acciones
- [x] Agregar link “Papelera” en sidebar (solo Admin)

3) Restaurar por ítem + reglas de dependencias (AC: 3, 5)
- [x] Acción “Restaurar” (solo Admin) por ítem, sobre `onlyTrashed()`
- [x] Si la restauración requiere dependencias (ej. Asset→Product), bloquear con mensaje claro
- [x] Registrar auditoría best-effort `trash.restore`

4) Vaciar/Purgar definitivo (AC: 4, 5, 8)
- [x] Acción “Purgar” por ítem (solo Admin) con confirmación y estilo destructivo
- [x] Acción “Vaciar papelera” por tab (solo Admin) con confirmación explícita
- [x] Manejar fallos por FK restrict (sin 500; feedback claro; best-effort en bulk)
- [x] Registrar auditoría best-effort `trash.purge`

5) Cerrar brecha en Papelera de catálogos (recomendado, AC: 4, 5)
- [x] Extender `/catalogs/trash` para soportar purga/vaciar (manteniendo restore existente)
- [x] Registrar auditoría en restore/purge de catálogos

6) Tests (AC: 5, 6, 7)
- [x] Tests RBAC para `/admin/trash` + acciones restore/purge
- [x] Tests de restore/purge por entidad (mínimo 1 por tab)
- [x] Test de regresión: soft-deleted no aparece en listados/conteos “normales”
- [x] Test de auditoría: se crea `audit_logs` para restore/purge

## Dev Notes

### Developer Context (guardrails anti-desastres)

**Estado actual del repo (importante para no reinventar):**
- Ya existe una “Papelera de catálogos” (Admin) con tabs y restauración:
  - Livewire: `gatic/app/Livewire/Catalogs/Trash/CatalogsTrash.php`
  - View: `gatic/resources/views/livewire/catalogs/trash/catalogs-trash.blade.php`
  - Ruta: `GET /catalogs/trash` (`can:admin-only`) en `gatic/routes/web.php`
- En esta implementación **no** existe aún “vaciar/purgar” y no hay una “Papelera general” para productos/activos/empleados.

**Riesgos reales a anticipar (no asumir que `forceDelete()` siempre funciona):**
- El esquema usa FKs `restrictOnDelete` en varias tablas (ej. movimientos), por lo que la purga física puede fallar aunque el registro esté soft-deleted.
  - Esto es correcto: **historial y consistencia** primero. La UI debe manejarlo sin 500.
- Un restore puede fallar por unicidades (p.ej. catálogos con `name` único incluyendo eliminados). Si hay colisión, mostrar mensaje y NO romper.

**Reglas de arquitectura/estructura a respetar (Livewire-first):**
- Pantallas = componentes Livewire (route → Livewire). Controllers solo “bordes” (adjuntos ya siguen esto).
- Autorización **server-side** siempre:
  - Rutas: middleware `can:admin-only` donde aplique.
  - Livewire: `Gate::authorize('admin-only')` en `render()` y acciones (`restore`, `purge`, `emptyTrash`, etc.).
- Copy/UI en español; identificadores/rutas en inglés.

**Qué entidades deben considerarse para Papelera (Gate 5 / Issues #106–#108):**
- `Product`, `Asset` y `Employee` son el mínimo requerido por la UI de Papelera (tabs).
- Catálogos ya tienen papelera (se puede extender con “Vaciar” para cerrar FR35).
- Nota: hoy `Employee` **NO** tiene `deleted_at` en DB ni `SoftDeletes` en el modelo; habrá que agregarlo para cumplir “soft deletes consistentes”.

**UX obligatorio en acciones potencialmente lentas (check transversal):**
- Si un action Livewire puede tardar (restore/purge/bulk), integrar `<x-ui.long-request />` (ya usado en Papelera Catálogos).

**Auditoría (best-effort, NFR8):**
- Las acciones de eliminar/restaurar/purgar deben disparar `AuditRecorder::record(...)` (no bloqueante) para que el feed admin sea útil.

### Technical Requirements (lo mínimo para “ready-for-dev”)

**Soft delete consistente (G5-T08):**
- `Employee`:
  - DB: agregar `deleted_at` (migration nueva: `employees`).
  - Modelo: agregar `use SoftDeletes;`.
  - UI/acciones: el “Eliminar” debe convertirse a soft-delete (no borrado físico).
- `Product` / `Asset`:
  - Ya tienen `deleted_at` y `SoftDeletes`; validar que rutas y queries “normales” NO incluyan `withTrashed()`.
- Documentar explícitamente en código/story qué entidades usan soft delete (para no “adivinar” en el futuro).

**UI Papelera (G5-T09):**
- Nueva vista admin-only (sugerencia de ruta): `GET /admin/trash` (nombre sugerido: `admin.trash.index`).
- Tabs mínimos: Productos / Activos / Empleados.
- Búsqueda simple por texto (escapar `%` y `_` como en `CatalogsTrash`).
- Mostrar `deleted_at` (formato humano) + campos clave por entidad:
  - Product: nombre, categoría, marca (si aplica), `deleted_at`.
  - Asset: `serial`, `asset_tag` (si aplica), producto, estado, `deleted_at`.
  - Employee: `rpe`, nombre, `deleted_at`.

**Restaurar (por ítem):**
- Solo Admin.
- Debe ejecutar `restore()` sobre `onlyTrashed()` (evitar restaurar registros no eliminados).
- Si hay dependencia requerida (ej. restaurar Asset cuyo Product está eliminado), definir comportamiento:
  - recomendado: bloquear con mensaje claro y sugerir restaurar primero la dependencia.

**Vaciar / Purgar (por tab y/o por ítem):**
- Solo Admin.
- Debe ejecutar `forceDelete()` únicamente en registros `onlyTrashed()`.
- Manejar fallos por integridad referencial (FK restrict):
  - Capturar excepción y mostrar feedback claro (sin 500).
  - En bulk “Vaciar”, aplicar best-effort: purgar lo posible y reportar cuántos quedaron por dependencias.

**Auditoría (G5-T10):**
- Registrar (best-effort) al menos:
  - `trash.soft_delete` (cuando se elimina → soft delete)
  - `trash.restore`
  - `trash.purge`
- `subject_type` y `subject_id` deben apuntar a la entidad real (Product/Asset/Employee/etc).
- Agregar labels en español en `gatic/app/Models/AuditLog.php`.

### Architecture Compliance (alineación con `_bmad-output/implementation-artifacts/architecture.md`)

- Livewire-first: `GET /admin/trash` debe apuntar a un componente Livewire (sin controller).
- Mantener componentes Livewire delgados:
  - la lógica de “restaurar/purgar/vaciar” idealmente vive en `app/Actions/Trash/*` (o módulo equivalente) para:
    - reuso (bulk + ítem),
    - testeo directo,
    - manejo uniforme de excepciones (FK restrict),
    - disparo de auditoría best-effort.
- No romper naming rules:
  - rutas/route names en inglés (`admin.trash.index`),
  - UI copy en español.
- Evitar `withTrashed()` en pantallas normales; solo usarlo en:
  - UI Papelera,
  - validaciones puntuales (e.g., checar dependencia al restaurar).

### Library / Framework Requirements (versiones reales del repo)

- Backend:
  - Laravel `laravel/framework` **v11.47.0**
  - Livewire `livewire/livewire` **v3.7.3**
  - PHP `^8.2` (platform: 8.2.0 en `gatic/composer.json`)
- Tooling:
  - Pint **v1.26.0**
  - PHPUnit **11.5.46**
  - Larastan **v3.8.1**
- Frontend:
  - Bootstrap **^5.2.3** + Popper **^2.11.6**

**API Laravel SoftDeletes (no inventar):**
- Listar eliminados: `Model::query()->onlyTrashed()`
- Restaurar: `$model->restore()`
- Purga definitiva: `$model->forceDelete()` (solo sobre registros eliminados)

### File Structure Requirements (paths concretos)

**Rutas:**
- `gatic/routes/web.php`
  - agregar `GET /admin/trash` bajo middleware `['auth', 'active', 'can:admin-only']`.

**Livewire (nuevo módulo admin):**
- `gatic/app/Livewire/Admin/Trash/TrashIndex.php` (tabs + búsqueda + acciones)
- `gatic/resources/views/livewire/admin/trash/trash-index.blade.php`

**Acciones/Soporte (recomendado para mantener Livewire delgado):**
- `gatic/app/Actions/Trash/RestoreTrashedItem.php`
- `gatic/app/Actions/Trash/PurgeTrashedItem.php`
- `gatic/app/Actions/Trash/EmptyTrash.php` (bulk por tipo/tab; best-effort)

**Modelos/Migraciones:**
- `gatic/database/migrations/*_add_deleted_at_to_employees_table.php`
- `gatic/app/Models/Employee.php` (agregar `SoftDeletes`)

**Auditoría:**
- `gatic/app/Models/AuditLog.php` (acciones + labels nuevas)
- `gatic/app/Support/Audit/AuditRecorder.php` (solo si se requiere ampliar allowlist de `context`)

**Navegación:**
- `gatic/resources/views/layouts/partials/sidebar-nav.blade.php` (link “Papelera” solo Admin)

**Tests (Feature):**
- `gatic/tests/Feature/Admin/Trash/AdminTrashRbacTest.php`
- `gatic/tests/Feature/Admin/Trash/AdminTrashRestoreTest.php`
- `gatic/tests/Feature/Admin/Trash/AdminTrashPurgeTest.php`
- `gatic/tests/Feature/SoftDeletes/SoftDeleteRegressionTest.php` (o suite existente equivalente)

### Testing Requirements (obligatorio antes de “done”)

**RBAC (defensa en profundidad):**
- Admin: puede ver `GET /admin/trash`, restaurar y purgar.
- Editor/Lector: no pueden ver la ruta ni disparar acciones (403/deny).

**Funcional (restore/purge):**
- Restaurar:
  - crear registro → soft delete → aparece en papelera → restore → vuelve a aparecer en listados normales.
  - validar que `deleted_at` vuelve a `null`.
- Purgar:
  - registro soft-deleted → purge → ya no existe en DB.
  - caso con FK restrict: preparar dependencias para forzar fallo (cuando aplique) y verificar:
    - no hay 500,
    - el registro sigue en papelera,
    - se muestra mensaje claro (toast).

**Regresión soft-delete (Lección Epic 6):**
- Para entidades que afectan conteos/listados, agregar al menos un test que cree un registro soft-deleted y valide que:
  - NO aparece en listados normales,
  - NO afecta conteos (si la pantalla los usa).

**Auditoría best-effort:**
- Al menos 1 test que verifique que al restaurar/purgar se crea un `audit_logs` con la acción correspondiente.
- No hacer tests frágiles dependientes de queues externas; si se usa `afterCommit`, usar modo sync en testing o ajustar aserciones según patrón existente.

### Previous Story Intelligence (no repetir errores)

- Story 2.4 (catálogos) dejó un patrón probado:
  - `onlyTrashed()` + búsqueda con escape de comodines (`%`/`_`)
  - `can:admin-only` en ruta + `Gate::authorize()` en Livewire
  - `<x-ui.long-request />` para acciones
  - Confirmaciones con `wire:confirm` y feedback con toasts
- Story 8.3 (adjuntos) dejó un guardrail crítico:
  - Adjuntos de entidades soft-deleted NO deben ser accesibles; al restaurar, deben volver a comportarse normal.
  - Auditoría se registra vía `AuditRecorder` en modo best-effort (no bloquear UX si falla).

### Git Intelligence Summary (patrones recientes del repo)

- Commits recientes consolidaron Epic 8:
  - Auditoría best-effort (`AuditRecorder`, `audit_logs`) + módulo admin para consulta.
  - Paneles reutilizables de UI (Notas/Adjuntos) integrados en pantallas de detalle.
- Mantener consistencia con esos patrones:
  - acciones auditables deben usar `AuditRecorder::record(...)`
  - módulos admin se agrupan bajo `App\\Livewire\\Admin\\*` y rutas `/admin/*`

### Latest Tech Information (evitar drift por “versiones imaginarias”)

- No asumir versiones “latest” de internet: usar las versiones del repo (ver sección Library / Framework Requirements).
- Livewire 3: preferir acciones simples + paginación, y usar `wire:loading.attr=\"disabled\"`/`wire:target` como en implementaciones existentes.
- Soft-deletes: recordar que `forceDelete()` elimina físicamente y puede disparar errores por FK restrict; la UI debe manejarlo.

### Project Structure Notes

- Mantener consistencia con estructura actual:
  - Admin: `App\\Livewire\\Admin\\*` + rutas `/admin/*`
  - Catálogos: `App\\Livewire\\Catalogs\\*` + rutas `/catalogs/*`
  - Inventario: `App\\Livewire\\Inventory\\*`
- No mezclar lógica de negocio dentro de Livewire: extraer a `app/Actions/*` cuando el flujo tenga reglas, auditoría y/o manejo de excepciones.
- Evitar duplicar “helpers” globales; preferir clases por módulo (Trash/Employees/Inventory).

### References

- Backlog (fuente de verdad): `_bmad-output/implementation-artifacts/epics.md` (Epic 8 / Story 8.4; FR35).
- Gate 5 / issues desglosadas: `_bmad-output/implementation-artifacts/epics-github.md` (G5-T08, G5-T09, G5-T10).
- DoD Gate 5: `docsBmad/gates-execution.md` (Papelera soft-delete/restaurar/vaciar).
- Requerimientos: `_bmad-output/implementation-artifacts/prd.md` (FR35, NFR4, NFR5, NFR8).
- Arquitectura y reglas de estructura: `_bmad-output/implementation-artifacts/architecture.md` (Livewire-first, `deleted_at`, sin purga automática).
- UX: `_bmad-output/implementation-artifacts/ux.md` (acciones destructivas; confirmación; long-request).
- Bible/política soft-delete: `docsBmad/project-context.md` (retención indefinida; Admin vacía papelera).
- Reglas operativas para agentes: `project-context.md`.
- RBAC: `docsBmad/rbac.md`, `gatic/app/Providers/AuthServiceProvider.php`.
- Implementación existente (catálogos): `gatic/app/Livewire/Catalogs/Trash/CatalogsTrash.php`, `gatic/resources/views/livewire/catalogs/trash/catalogs-trash.blade.php`, `gatic/routes/web.php`.
- Auditoría best-effort: `gatic/app/Support/Audit/AuditRecorder.php`, `gatic/app/Models/AuditLog.php`.
- Story previa: `_bmad-output/implementation-artifacts/2-4-soft-delete-y-restauracion-de-catalogos.md`, `_bmad-output/implementation-artifacts/8-3-adjuntos-seguros-con-control-de-acceso.md`.

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

### Completion Notes List

 - Story creada como guía de implementación (ready-for-dev) con alcance Gate 5 (G5-E03) y ACs explícitos para soft-delete, restauración, purga y auditoría best-effort.
 - Incluye guardrails contra errores comunes: RBAC server-side, manejo de FKs restrict en purge, y tests de regresión de soft-delete.
 - **Implementation completed 2026-01-24** - All ACs satisfied:
   - AC1: Employee soft-delete migration and model updated; Products/Assets already had SoftDeletes
   - AC2: Admin Papelera UI at `/admin/trash` with tabs for Productos/Activos/Empleados, search, and pagination
   - AC3: Restore per item with dependency checking (Asset blocked if Product is soft-deleted)
   - AC4: Purge/Empty trash with FK constraint handling (no 500 errors, clear user feedback)
   - AC5: Audit logging for restore/purge/empty via AuditRecorder (best-effort)
   - AC6: RBAC enforced via route middleware `can:admin-only` and `Gate::authorize()` in Livewire
   - AC7: Regression tests verify soft-deleted items excluded from normal listings
   - AC8: Destructive actions use red styling with `wire:confirm` and `<x-ui.long-request />`
 - CatalogsTrash extended with purge and empty trash actions
 - Test results: 552 tests passing, 1384 assertions
 - Code quality: pint --test passing (232 files), larastan has 1 pre-existing error in NotesPanel.php (not from this story)
 - **Code review (2026-01-24):** Fixes aplicados a hallazgos HIGH/MEDIUM:
   - Agregado test faltante para purga de **Activos** (AdminTrashPurgeTest)
   - Validación server-side de tipos permitidos en `Admin\\Trash\\TrashIndex` (solo products/assets/employees)
   - Mensajes de error más seguros (sin filtrar excepciones crudas) + `error_id` en errores inesperados
   - `EmptyTrash` mejorado para procesar en chunks (no cargar todo en memoria) y distinguir errores inesperados
   - Sidebar: renombrado “Papelera de catálogos” para evitar duplicidad de links “Papelera”
   - Paginación alineada a `config('gatic.ui.pagination.per_page')`
   - Tests re-ejecutados: `AdminTrash*`, `SoftDeleteRegressionTest`, `EmployeesTest` ✅

### RBAC Decision: Who Can Soft-Delete (Eliminar) per Entity

> **Decision Date:** 2026-01-24  
> **Decision By:** Dev Agent (Story 8.4 implementation)

| Entity | Gate para Eliminar | Roles | Justificación |
|--------|-------------------|-------|---------------|
| Catálogos (Category/Brand/Location) | `catalogs.manage` | Admin, Editor | Per `docsBmad/rbac.md` tabla gates |
| Product | `inventory.manage` | Admin, Editor | Per `docsBmad/rbac.md` + `_bmad-output/implementation-artifacts/prd.md` FR8 |
| Asset | `inventory.manage` | Admin, Editor | Per `docsBmad/rbac.md` + `_bmad-output/implementation-artifacts/prd.md` FR10 |
| Employee | `inventory.manage` | Admin, Editor | Per `docsBmad/rbac.md` + FR15; patrón existente `gatic/app/Livewire/Employees/EmployeesIndex.php` línea 153 |

**UI Papelera + restore/purge/vaciar:** Solo Admin (`admin-only`)

**Referencias:**
- `docsBmad/rbac.md`: tabla gates define `inventory.manage` = Admin + Editor
- `docsBmad/project-context.md`: "Soft-delete: retención indefinida hasta que Admin vacíe papelera"
- `gatic/app/Providers/AuthServiceProvider.php`: Gate `admin-only` para acciones exclusivas Admin

### File List

**New files created:**
- `gatic/database/migrations/2026_01_24_000001_add_deleted_at_to_employees_table.php`
- `gatic/app/Actions/Trash/RestoreTrashedItem.php`
- `gatic/app/Actions/Trash/PurgeTrashedItem.php`
- `gatic/app/Actions/Trash/EmptyTrash.php`
- `gatic/app/Livewire/Admin/Trash/TrashIndex.php`
- `gatic/resources/views/livewire/admin/trash/trash-index.blade.php`
- `gatic/tests/Feature/Admin/Trash/AdminTrashRbacTest.php`
- `gatic/tests/Feature/Admin/Trash/AdminTrashRestoreTest.php`
- `gatic/tests/Feature/Admin/Trash/AdminTrashPurgeTest.php`
- `gatic/tests/Feature/Admin/Trash/AdminTrashAuditTest.php`
- `gatic/tests/Feature/SoftDeletes/SoftDeleteRegressionTest.php`

**Files modified:**
- `gatic/app/Models/Employee.php` - added SoftDeletes trait
- `gatic/app/Models/AuditLog.php` - added trash action constants and labels
- `gatic/app/Livewire/Catalogs/Trash/CatalogsTrash.php` - added purge/emptyTrash actions
- `gatic/resources/views/livewire/catalogs/trash/catalogs-trash.blade.php` - added purge buttons
- `gatic/resources/views/layouts/partials/sidebar-nav.blade.php` - added Papelera link for Admin
- `gatic/routes/web.php` - added admin.trash.index route
- `gatic/tests/Feature/Employees/EmployeesTest.php` - updated to use assertSoftDeleted
- `_bmad-output/implementation-artifacts/sprint-status.yaml` - synced story status (`8-4-papelera-soft-delete-restaurar-purgar` → done)
- `_bmad-output/implementation-artifacts/8-4-papelera-soft-delete-restaurar-purgar.md` - added RBAC decision + completion notes + code review follow-ups
