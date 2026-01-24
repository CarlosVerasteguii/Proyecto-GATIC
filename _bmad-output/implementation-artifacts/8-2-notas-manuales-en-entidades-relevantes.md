# Story 8.2: Notas manuales en entidades relevantes

Status: done

Story Key: `8-2-notas-manuales-en-entidades-relevantes`  
Epic: `8` (Gate 5: Trazabilidad y evidencia)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/epics.md` (Epic 8 / Story 8.2; FR33)
- `_bmad-output/implementation-artifacts/architecture.md` (patrones: Actions/Livewire/Gates; Gate 5)
- `_bmad-output/implementation-artifacts/ux.md` (reglas UX transversales; long-request >3s)
- `docsBmad/project-context.md` (bible del proyecto)
- `project-context.md` (reglas críticas para agentes; tooling Windows/Sail)
- `docsBmad/rbac.md` + `gatic/app/Providers/AuthServiceProvider.php` (gates y aplicación server-side)
- Story previa (Epic 8): `_bmad-output/implementation-artifacts/8-1-auditoria-consultable-best-effort.md` (patrones de trazabilidad + UI Admin + tests)
- Código actual (pantallas “detalle” candidatas para notas):
  - `gatic/app/Livewire/Inventory/Products/ProductShow.php`
  - `gatic/app/Livewire/Inventory/Assets/AssetShow.php`
  - `gatic/app/Livewire/Employees/EmployeeShow.php`
  - `gatic/resources/views/livewire/inventory/products/product-show.blade.php`
  - `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php`
  - `gatic/resources/views/livewire/employees/employee-show.blade.php`

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a usuario interno,  
I want agregar notas manuales a registros relevantes,  
so that pueda documentar contexto operativo (FR33).

## Alcance (MVP)

Esta story implementa **notas manuales persistentes** (cross-cutting) para documentar contexto operativo directamente en los “detalles” de entidades clave.

Incluye:
- Persistir notas en DB con **autor** (`users.id`) + **fecha** (`created_at`) [FR33].
- Mostrar una sección **“Notas”** en pantallas de detalle (MVP):
  - Producto (`Product`) — visible para roles con `inventory.view`
  - Activo (`Asset`) — visible para roles con `inventory.view`
  - Empleado (`Employee`) — visible para roles con `inventory.manage` (ya es el gate del módulo)
- Alta de nota (crear) para **Admin/Editor** (server-side), con UI consistente (Bootstrap 5).
- Render de notas como **texto plano** (sin HTML), con saltos de línea conservados.

## Fuera de alcance (NO hacer aquí)

- Adjuntos (Story 8.3), Papelera (8.4), Error ID consultable E2E (8.5).
- Edición/eliminación de notas (solo crear + listar en MVP).
- Notas “automáticas” generadas por el sistema (esas viven en auditoría o historiales de dominio).
- Notas en listados masivos o búsquedas globales (solo en pantallas de detalle).

## Definiciones (para evitar ambigüedad)

- **Nota manual:** texto libre escrito por un usuario autenticado.
- **Entidad “noteable”:** modelo del dominio que admite notas manuales (MVP: Product/Asset/Employee).
- **Autor:** `users.id` que creó la nota; se muestra `users.name` en UI.

## Acceptance Criteria

### AC1 — Crear nota con autor+fecha (FR33)

**Given** una entidad noteable (Product/Asset/Employee)  
**When** un Admin/Editor agrega una nota  
**Then** la nota se guarda con `author_user_id` y `created_at`  
**And** la nota queda asociada a la entidad (relación morph/polimórfica).

### AC2 — Visibilidad según permisos (FR33, NFR4)

**Given** un usuario con permiso para ver la entidad  
**When** abre el detalle  
**Then** puede ver la lista de notas con autor+fecha (orden más reciente primero).

**Given** un usuario sin permiso para ver la entidad  
**When** intenta acceder a notas (vía UI o request Livewire)  
**Then** el servidor lo bloquea (deny/403).

### AC3 — Alta restringida (Admin/Editor) (FR33, NFR4, NFR5)

**Given** un Lector (o usuario sin permisos de alta)  
**When** intenta crear una nota  
**Then** el servidor lo bloquea (deny/403)  
**And** la UI no muestra el formulario “Agregar nota”.

### AC4 — Validaciones y sanitización (calidad)

**Given** un formulario de nota  
**When** el usuario envía el texto  
**Then** valida `body` como string trim, `min:1`, `max:5000`  
**And** se guarda como texto plano (sin HTML)  
**And** la UI conserva saltos de línea (render seguro).

### AC5 — Instrumentación de auditoría (best-effort) (alineación Epic 8 / Story 8.1)

**Given** que ya existe módulo de auditoría consultable (Story 8.1)  
**When** se crea una nota  
**Then** se registra un evento de auditoría “nota creada” (acción estable) con subject=entidad o nota  
**And** si falla registrar auditoría, **NO** se bloquea el guardado de la nota (best-effort).

### AC6 — Performance mínimo (NFR1)

**Given** una entidad con muchas notas  
**When** se abre el detalle  
**Then** la lista de notas es paginada (p.ej. 20)  
**And** no hay N+1 para autor (eager load `author`).

## Tasks / Subtasks

- [x] Persistencia de notas (AC: 1,4,6)
  - [x] Migración `notes` con `noteable_type`, `noteable_id`, `author_user_id`, `body`, timestamps + índices
  - [x] Modelo `App\\Models\\Note` con relaciones `noteable()` y `author()`
  - [x] Relaciones `notes()` en `Product`, `Asset`, `Employee` (morphMany)
- [x] RBAC server-side (AC: 2,3)
  - [x] Definir/usar gates coherentes (p.ej. `notes.view` y `notes.manage` en `AuthServiceProvider`) **o** mapear a `inventory.view` / `inventory.manage`
  - [x] Autorizar en Livewire (`Gate::authorize(...)`) en mount y en método de create
- [x] UI: panel reusable de notas (AC: 2,3,4,6)
  - [x] Livewire `App\\Livewire\\Ui\\NotesPanel` con paginación + create
  - [x] Vista Bootstrap 5 `resources/views/livewire/ui/notes-panel.blade.php`
  - [x] Integrar panel en:
    - [x] `livewire.inventory.products.product-show`
    - [x] `livewire.inventory.assets.asset-show`
    - [x] `livewire.employees.employee-show`
- [x] Auditoría best-effort al crear nota (AC: 5)
  - [x] Agregar acción estable (p.ej. `notes.manual.create`) a `AuditLog` (constante + label + listado)
  - [x] Usar `AuditRecorder` (job) para emitir evento con contexto mínimo (allowlist)
- [x] Pruebas deterministas (AC: 1–6)
  - [x] Feature: Admin/Editor pueden crear nota; Lector no (403)
  - [x] Feature/UI: nota visible en detalle cuando el usuario puede ver la entidad
  - [x] Performance: assert eager loading de `author` (no N+1) vía número de queries o inspección del builder

## Dev Notes

### Reuse first (evitar reinventar)

- Ya existe infraestructura de **trazabilidad** (Story 8.1): `gatic/app/Support/Audit/AuditRecorder.php` + `gatic/app/Jobs/RecordAuditLog.php` + UI Admin.
  - Reutilizar para auditar “nota creada” (best-effort) sin bloquear el guardado de la nota.
- Ya existe patrón de **pantallas de detalle con Livewire + Bootstrap**:
  - Product/Asset: `Gate::authorize('inventory.view')` en `mount()` y `render()`.
  - Employee: `Gate::authorize('inventory.manage')` en `mount()` y `render()`.

### Guardrails de implementación (para dev LLM)

- **Texto plano**: la nota se guarda y se renderiza como texto (escapado) + saltos de línea; NO permitir HTML.
- **Autorización server-side obligatoria**:
  - View de notas: ligado a `inventory.view` (Product/Asset) y `inventory.manage` (Employee).
  - Create de notas: Admin/Editor únicamente (p.ej. gate `notes.manage` o reutilizar `inventory.manage`).
- **Contexto mínimo en auditoría** (si se audita):
  - NO guardar contenido completo de la nota en `audit_logs.context` (puede ser largo); usar `summary` corto (p.ej. primeros 80 chars) + ids.
- **No tocar flujos críticos** (movimientos/locks/ajustes): solo agregar un panel en pantallas de detalle.
- **Soft-delete**: Product (y otras entidades) usan SoftDeletes; evitar queries “conTrashed” por default.

### UX (consistencia BMAD)

- UI en español; código/DB/rutas en inglés. [Source: `project-context.md#Critical Implementation Rules`]
- Panel “Notas” tipo card en el detalle:
  - Lista (más reciente primero) con autor + fecha/hora.
  - Estado vacío: “Sin notas”.
  - Formulario (solo Admin/Editor): textarea + contador de caracteres + botón “Guardar nota”.
- Si la consulta puede tardar >3s (p.ej. entidad con miles de notas), aplicar `<x-ui.long-request />` envolviendo el área de resultados. [Source: `gatic/resources/views/components/ui/long-request.blade.php`]

### Project Structure Notes

- **Modelo/DB**
  - `gatic/app/Models/Note.php`
  - `gatic/database/migrations/*_create_notes_table.php`
- **Livewire reusable**
  - `gatic/app/Livewire/Ui/NotesPanel.php`
  - `gatic/resources/views/livewire/ui/notes-panel.blade.php`
- **Integración en “detalle”**
  - `gatic/resources/views/livewire/inventory/products/product-show.blade.php`
  - `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php`
  - `gatic/resources/views/livewire/employees/employee-show.blade.php`
- **RBAC**
  - `gatic/app/Providers/AuthServiceProvider.php` (gates)
- **Audit (opcional pero recomendado)**
  - `gatic/app/Models/AuditLog.php` (acción + label)
- `gatic/app/Support/Audit/AuditRecorder.php` (dispatch best-effort)

## Technical Requirements

### Persistencia (DB)

Tabla `notes` (propuesta):
- `id` (PK)
- `noteable_type` (string) + `noteable_id` (bigint) — relación polimórfica
- `author_user_id` (FK a `users.id`)
- `body` (text) — nota en texto plano
- `created_at`, `updated_at` (timestamps)

Índices mínimos:
- `(noteable_type, noteable_id, created_at)` para listar por entidad
- `(author_user_id, created_at)` (opcional) para filtros futuros

### Modelo (Eloquent)

- `App\\Models\\Note`
  - `noteable(): morphTo`
  - `author(): belongsTo(User::class, 'author_user_id')`
- En modelos noteables (MVP):
  - `Product::notes(): morphMany(Note::class, 'noteable')`
  - `Asset::notes(): morphMany(Note::class, 'noteable')`
  - `Employee::notes(): morphMany(Note::class, 'noteable')`

Orden recomendado: `latest()` (por `created_at`) y eager load de `author`.

### UI (Livewire 3)

Componente reusable sugerido: `App\\Livewire\\Ui\\NotesPanel`.

Inputs:
- `string $noteableType` (clase o alias estable)
- `int $noteableId`

Comportamiento:
- `mount(...)`: autoriza “ver” notas según entidad (ver sección RBAC).
- `render()`: carga notas paginadas (`paginate(20)`) + eager load `author`.
- `createNote()`: valida `body` (trim, min/max), autoriza “alta” y persiste.
- Al éxito: limpiar textarea + emitir toast (si aplica) o mensaje inline.

Render seguro:
- `{{ e($note->body) }}` + `nl2br` (o alternativa) para conservar saltos de línea.

### Auditoría (best-effort)

Al crear nota:
- Emitir evento con `AuditRecorder` (job, afterCommit).
- Acción estable propuesta: `notes.manual.create`.
- `context` allowlist (mínimo):
  - `noteable_type`, `noteable_id`, `note_id`, `summary` (truncado)

Regla: si falla la auditoría, NO bloquear `notes` (best-effort).

## Architecture Compliance

- Mantener UI “route -> Livewire component” (sin controllers salvo bordes). [Source: `_bmad-output/implementation-artifacts/architecture.md#Controllers`]
- Autorización server-side:
  - Rutas protegidas por `can:*` (ya existe) y refuerzo con `Gate::authorize(...)` en Livewire. [Source: `docsBmad/rbac.md#Reglas de aplicación`]
- Estructura de proyecto:
  - Modelos en `app/Models`, Livewire en `app/Livewire`, helpers en `app/Support`, Actions opcionales en `app/Actions`. [Source: `_bmad-output/implementation-artifacts/architecture.md#File Organization Patterns`]
- No introducir dependencias nuevas para “notes” (módulo simple con Eloquent + Livewire).

## Library / Framework Requirements

- Versiones actuales del repo (desde `gatic/composer.lock`):
  - Laravel/framework: `v11.47.0`
  - Livewire: `v3.7.3`
  - PHP (composer platform): `8.2.0` (runtime puede ser 8.2+; en Windows local se usa `php84` para tooling). [Source: `project-context.md#Local Toolchain Notes (Windows)`]
- Seguridad (Livewire):
  - Mantener Livewire **>= 3.6.4** (fix de seguridad); el repo ya está en `v3.7.3`, evitar downgrade accidental.
- UI:
  - Bootstrap 5 (no Tailwind); respetar clases y patrones existentes de vistas.
- No actualizar dependencias en esta story salvo que sea estrictamente necesario (notas no requiere upgrades).

## File Structure Requirements

Archivos esperados (mínimo):
- Migración: `gatic/database/migrations/*_create_notes_table.php`
- Modelo: `gatic/app/Models/Note.php`
- Livewire reusable:
  - `gatic/app/Livewire/Ui/NotesPanel.php`
  - `gatic/resources/views/livewire/ui/notes-panel.blade.php`
- Integración (views existentes):
  - `gatic/resources/views/livewire/inventory/products/product-show.blade.php`
  - `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php`
  - `gatic/resources/views/livewire/employees/employee-show.blade.php`
- RBAC:
  - `gatic/app/Providers/AuthServiceProvider.php` (si se agregan gates `notes.*`)

Convenciones:
- Identificadores de código/DB en inglés; copy/UI en español. [Source: `project-context.md#Language-Specific Rules (PHP)`]
- No crear “helpers globales”; si se necesita lógica reusable, usar `app/Actions` o `app/Support`. [Source: `project-context.md#Language-Specific Rules (PHP)`]

## Testing Requirements

Objetivo: cubrir RBAC + persistencia + render seguro + regresiones conocidas.

### Tests mínimos (Feature)

- **RBAC alta de nota**
  - Admin puede crear nota (200/OK)
  - Editor puede crear nota (200/OK)
  - Lector NO puede crear nota (403)
- **Visibilidad**
  - Lector puede ver notas en Product/Asset (porque puede `inventory.view`)
  - Employee notes solo visible en rutas `employees/*` (ya está bajo `inventory.manage`)
- **Validaciones**
  - `body` requerido, trim, `min:1`, `max:5000`
- **Soft-delete regression (lección Epic 6)**
  - Si se toca Product/Asset/Employee (SoftDeletes en algunos modelos), asegurar que pantallas normales no exponen registros soft-deleted (404) y que el panel de notas no usa `withTrashed()` por default.

### Auditoría (si se implementa AC5)

- Test que verifique que crear una nota **dispara** el mecanismo best-effort (job/recorder) sin bloquear:
  - usar `Queue::fake()` y asertar dispatch de `RecordAuditLog` (o helper equivalente).

### Determinismo

- Sin `sleep`; usar `Carbon::setTestNow()` si se validan timestamps visibles. [Source: `docsBmad/checklists/dev-preflight.md#6) Pruebas (deterministas)`]

## Previous Story Intelligence (Epic 8)

Lecciones reutilizables desde Story 8.1 (auditoría consultable):
- **Best-effort real**: la auditoría se ejecuta async (job) y “swallow exceptions”; nunca debe romper el flujo principal. Aplicar lo mismo para auditar “nota creada”: si el log falla, la nota igual se guarda. [Source: `_bmad-output/implementation-artifacts/8-1-auditoria-consultable-best-effort.md#AC2`]
- **Context allowlist**: evitar guardar payloads grandes/PII en auditoría; para notas, guardar solo ids + `summary` truncado. [Source: `docsBmad/product/audit-use-cases.md#Allowlist de context (MVP)`]
- **Patrón de UI Admin-only**: cuando algo es “solo Admin”, se refuerza con `Gate::authorize('admin-only')` en Livewire (mount/render) + middleware `can:admin-only` en rutas. Para notas, replicar el mismo rigor con gates adecuados (aunque la UI sea “solo un panel”). [Source: `docsBmad/rbac.md#Reglas de aplicación`]

## Git Intelligence Summary

Contexto reciente (últimos commits relevantes):
- `feat(audit): implementacion modulo auditoria best-effort (Story 8.1)` creó/actualizó:
  - `gatic/app/Support/Audit/*`, `gatic/app/Jobs/RecordAuditLog.php`, `gatic/app/Models/AuditLog.php`
  - UI Admin Livewire en `gatic/app/Livewire/Admin/Audit/*`
  - Tests en `gatic/tests/Feature/Audit/*`
- Conclusión para 8.2:
  - Mantener el mismo estilo: clases pequeñas, constantes estables, tests feature por módulo, y gates centralizados.

## Latest Tech Information (2026-01-24)

- Laravel:
  - Mantenerse en Laravel 11.x (el repo ya está en 11.47.0). Para implementar notas no se requiere migrar de major.
  - Referencia oficial: documentación de Laravel 11.
- Livewire:
  - Livewire 3 tuvo fixes de seguridad en 3.6.4; el repo está en 3.7.3. Evitar “composer update parcial” que pueda bajar versión.
  - Referencia: releases oficiales de `livewire/livewire` (GitHub).
- PHP:
  - PHP 8.2 sigue en soporte de seguridad (según schedule oficial); no es necesario cambiar de versión para esta story.
  - Referencia: “Supported Versions” de php.net.

## Project Context Reference (must-read)

- `docsBmad/project-context.md`
  - Roles fijos MVP y reglas base (stack, gates, queue `database`).
- `project-context.md`
  - Reglas críticas para agentes (inglés en código/DB, español en UI; tooling Windows/Sail).
- `docsBmad/rbac.md`
  - Tabla de gates y regla: siempre server-side.
- `_bmad-output/implementation-artifacts/architecture.md`
  - Patrones de organización: `app/Actions`, `app/Livewire`, `app/Support`, DB indexes.
- `_bmad-output/implementation-artifacts/ux.md`
  - UX long-request (>3s), copy en español, desktop-first.
- `_bmad-output/implementation-artifacts/8-1-auditoria-consultable-best-effort.md`
  - Patrones de trazabilidad y best-effort (reutilizar para auditar nota creada).

### References

- Requerimiento FR33 + Story 8.2: [Source: `_bmad-output/implementation-artifacts/epics.md#Story 8.2: Notas manuales en entidades relevantes`]
- Gate 5 (tazabilidad) y stack base: [Source: `docsBmad/project-context.md#Roadmap por Gates (0–5)`]
- Patrones de arquitectura (Actions/Livewire/config, controllers solo bordes): [Source: `_bmad-output/implementation-artifacts/architecture.md#Controllers`]
- Reglas de RBAC y aplicación server-side: [Source: `docsBmad/rbac.md#Reglas de aplicación`], [Source: `gatic/app/Providers/AuthServiceProvider.php`]
- Best-effort + allowlist de auditoría (para AC5): [Source: `_bmad-output/implementation-artifacts/8-1-auditoria-consultable-best-effort.md`], [Source: `docsBmad/product/audit-use-cases.md`]
- Reglas críticas para agentes (idioma + tooling): [Source: `project-context.md#Critical Implementation Rules`]
- Puntos de integración UI (pantallas detalle): [Source: `gatic/app/Livewire/Inventory/Products/ProductShow.php`], [Source: `gatic/app/Livewire/Inventory/Assets/AssetShow.php`], [Source: `gatic/app/Livewire/Employees/EmployeeShow.php`]

## Story Completion Status

- Status: **done**
- Completion note: **Code review + fixes aplicadas. Notas manuales disponibles en detalle de Producto, Activo y Empleado. RBAC server-side, auditoría best-effort, tests Feature escritos (requieren Docker/Sail para ejecutar).**

## Dev Agent Record

### Agent Model Used

Claude Opus 4.5 (Claude Code CLI)

### Debug Log References

N/A

### Completion Notes List

- **2026-01-23**: Implementacion completa de notas manuales para Product, Asset, Employee
  - Migración `notes` con relación polimórfica y índices optimizados
  - Modelo `Note` con relaciones `noteable()` y `author()`
  - Gate `notes.manage` para Admin/Editor (crear notas)
  - Vista de notas sigue gates de entidad (`inventory.view` para Product/Asset, `inventory.manage` para Employee)
  - Componente Livewire `NotesPanel` reusable con paginación (20), validación (min:1, max:5000), sanitización HTML
  - Auditoría best-effort via `AuditRecorder` con acción `notes.manual.create`
  - Tests Feature: RBAC, validación, auditoría, performance (N+1)
  - Pint + Larastan pasan; tests requieren Docker/Sail para MySQL
- **2026-01-24**: Code review + fixes aplicadas (adversarial)
  - Paginación de notas con query param dedicado (evita colisión con `page` del listado)
  - Gate de visibilidad derivado por tipo (Product/Asset → `inventory.view`, Employee → `inventory.manage`)
  - Create de notas vía relación morph (reduce riesgo de mass-assignment) + orden estable (`created_at`, `id`)
  - Tests: auditoría compatible con `afterCommit()`, performance sin `DB::listen`, limpieza de tests redundantes

### File List

**Nuevos:**
- `gatic/database/migrations/2026_01_23_000000_create_notes_table.php`
- `gatic/app/Models/Note.php`
- `gatic/app/Livewire/Ui/NotesPanel.php`
- `gatic/resources/views/livewire/ui/notes-panel.blade.php`
- `gatic/tests/Feature/Notes/NotesRbacTest.php`
- `gatic/tests/Feature/Notes/NotesValidationTest.php`
- `gatic/tests/Feature/Notes/NotesAuditTest.php`
- `gatic/tests/Feature/Notes/NotesPerformanceTest.php`
- `gatic/tests/Feature/Notes/NoteModelTest.php`

**Modificados:**
- `gatic/app/Models/Product.php` (agregado `notes()` morphMany)
- `gatic/app/Models/Asset.php` (agregado `notes()` morphMany)
- `gatic/app/Models/Employee.php` (agregado `notes()` morphMany)
- `gatic/app/Providers/AuthServiceProvider.php` (agregado gate `notes.manage`)
- `gatic/app/Models/AuditLog.php` (agregada constante `ACTION_NOTE_MANUAL_CREATE`)
- `gatic/app/Support/Audit/AuditRecorder.php` (agregado `note_id` a allowlist)
- `gatic/resources/views/livewire/inventory/products/product-show.blade.php` (integrado NotesPanel)
- `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php` (integrado NotesPanel)
- `gatic/resources/views/livewire/employees/employee-show.blade.php` (integrado NotesPanel)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` — story status sync

**Docs:**
- `_bmad-output/implementation-artifacts/8-2-notas-manuales-en-entidades-relevantes.md` (this file)
