# Story 14.8: Timeline / changelog por entidad (audit + notas + movimientos + adjuntos)

Status: done

Story Key: `14-8-timeline-y-changelog-por-entidad`  
Epic: `14` (Datos de negocio: garantías, costos, proveedores, configuración, timeline, dashboard avanzado)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Fecha: 2026-02-07  
Story ID: `14.8`

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/epics.md` (Epic 14 / Story 14.8)
- `docsBmad/project-context.md` (bible: stack/UX/arquitectura)
- `docsBmad/rbac.md` (gates, restricciones Lector)
- `project-context.md` (reglas lean: idioma, stack, testing)
- `_bmad-output/implementation-artifacts/architecture.md` (patrones y estructura)
- `_bmad-output/implementation-artifacts/ux.md` (principio: “historial accesible”)
- Inteligencia previa (técnica / reutilización):
  - `_bmad-output/implementation-artifacts/8-1-auditoria-consultable-best-effort.md`
  - `_bmad-output/implementation-artifacts/8-2-notas-manuales-en-entidades-relevantes.md`
  - `_bmad-output/implementation-artifacts/8-3-adjuntos-seguros-con-control-de-acceso.md`
  - `_bmad-output/implementation-artifacts/5-1-reglas-de-estado-y-transiciones-para-activos-serializados.md`
  - `_bmad-output/implementation-artifacts/5-4-movimientos-por-cantidad-vinculados-a-producto-y-empleado.md`
  - `_bmad-output/implementation-artifacts/5-5-kardex-historial-para-productos-por-cantidad.md`
- Código actual (puntos de extensión / fuentes técnicas):
  - `gatic/app/Models/AuditLog.php`
  - `gatic/app/Livewire/Ui/NotesPanel.php`
  - `gatic/app/Livewire/Ui/AttachmentsPanel.php`
  - `gatic/app/Models/AssetMovement.php`
  - `gatic/app/Models/ProductQuantityMovement.php`
  - `gatic/app/Models/InventoryAdjustment.php`
  - `gatic/app/Models/InventoryAdjustmentEntry.php`
  - `gatic/app/Livewire/Inventory/Products/ProductShow.php` + `gatic/resources/views/livewire/inventory/products/product-show.blade.php`
  - `gatic/app/Livewire/Inventory/Assets/AssetShow.php` + `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php`
  - `gatic/app/Livewire/Employees/EmployeeShow.php` + `gatic/resources/views/livewire/employees/employee-show.blade.php`
  - `gatic/app/Livewire/PendingTasks/PendingTaskShow.php` + `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php`

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a usuario interno (Admin/Editor/Lector),
I want ver una línea de tiempo unificada por entidad (Producto/Activo/Empleado/Tarea) que combine movimientos, ajustes, auditoría, notas y adjuntos,
so that tenga contexto completo, verificable y accionable en un solo lugar sin saltar entre pantallas.

## Epic Context (Epic 14 completo, resumido)

Objetivo Epic 14: extender datos “enterprise” (garantías/costos/vida útil/proveedores/configuración/timeline/dashboard avanzado) sin perder simplicidad operativa.

Historias en Epic 14 (para contexto cruzado):
- **14.1 Proveedores:** `Supplier` + relación con `Product`.
- **14.2 Contratos:** `Contract` + relación con `Asset`.
- **14.3 Garantías:** fechas + alertas en activos.
- **14.4 Costos:** `acquisition_cost` + valor inventario en dashboard.
- **14.5 Vida útil:** reemplazo/renovación + alertas.
- **14.6 Settings:** configuración global DB-backed.
- **14.7 Perfil/Preferencias UI:** campos extra en `User` + preferencias UI por usuario.
- **14.8 (esta story):** timeline/changelog unificado por entidad.
- **14.9 Dashboard avanzado:** métricas negocio + actividad reciente (futuro).

Dependencias prácticas (ya resueltas en el repo):
- **Auditoría** (Story 8.1): tabla `audit_logs` + `AuditRecorder` best-effort.
- **Notas manuales** (Story 8.2): `Note` + `NotesPanel` reusable, view basado en visibilidad de entidad, create restringido.
- **Adjuntos seguros** (Story 8.3): `Attachment` + `AttachmentsPanel` reusable + `DownloadAttachmentController`, gates `attachments.*`.
- **Movimientos/ajustes** (Epic 5 / ajustes): `AssetMovement`, `ProductQuantityMovement`, `InventoryAdjustment*` con actor y notas obligatorias.

## Alcance (MVP)

- Agregar un **panel “Timeline”** en el detalle de:
  - Producto (`inventory.products.show`)
  - Activo (`inventory.products.assets.show`)
  - Empleado (`employees.show`)
  - Tarea Pendiente (`pending-tasks.show`)
- La timeline es un **feed cronológico (más reciente primero)** que unifica (cuando aplique):
  - Movimientos (serializado: `AssetMovement`; cantidad: `ProductQuantityMovement`)
  - Ajustes admin (`InventoryAdjustmentEntry` + razón)
  - Notas manuales (`Note`)
  - Adjuntos (subidas y eliminaciones; acceso controlado)
  - Auditoría transversal (`AuditLog`) para eventos que no tienen “registro visible” (p.ej. eliminaciones de adjuntos / papelera)
- Reutilizar y respetar **RBAC server-side** y el stack existente (Laravel 11 + Livewire 3 + Bootstrap 5).

## Fuera de alcance (NO hacer aquí)

- Reemplazar módulos existentes:
  - NO reemplazar `Auditoría` admin (Story 8.1) ni su UI.
  - NO reemplazar `Kardex`/pantallas actuales; la timeline puede **linkear** al kardex cuando aplique.
- Notificaciones en tiempo real / WebSockets (prohibido por bible).
- “Actividad reciente global” o dashboard de actividad (eso es Story 14.9).
- Nuevo sistema de comentarios o “mentions”.

## Definiciones (para evitar ambigüedad)

- **Timeline por entidad**: UI en la página de detalle que responde “¿qué ha pasado con este registro?” (y permite ver contexto).
- **Auditoría**: registro transversal best-effort (no bloqueante) usado para compliance/soporte; su UI completa es Admin-only.
- **Historial de dominio**: tablas de negocio (movimientos/ajustes) con semántica propia; NO duplicarlas.

## Acceptance Criteria

### AC1 — Timeline visible y cronológica por entidad

**Given** un usuario autorizado abre el detalle de una entidad (Producto/Activo/Empleado/Tarea)  
**When** la pantalla carga  
**Then** ve un panel “Timeline” con una lista cronológica (más reciente primero)  
**And** cada evento muestra al menos:
- tipo (label + ícono),
- fecha/hora,
- actor (si aplica),
- resumen corto (1 línea),
- acceso a detalle (expand/collapse o link) sin recargar la página.

### AC2 — Fuentes de eventos (unificación real)

**Given** existen eventos relacionados a la entidad  
**When** el usuario consulta la Timeline  
**Then** la lista combina (cuando aplique) fuentes ya existentes del sistema, sin duplicar data:

**Producto (cantidad / no serializado):**
- `ProductQuantityMovement` (entradas/salidas) + nota + empleado + actor
- `InventoryAdjustmentEntry` (ajustes de cantidad) + razón + actor
- `Note` (notas manuales)
- `Attachment` + auditoría de eliminaciones (si tiene permisos de adjuntos)

**Activo (serializado):**
- `AssetMovement` (asignar / desasignar / prestar / devolver) + nota + empleado + actor
- `InventoryAdjustmentEntry` (cambios de estado/ubicación) + razón + actor
- `Note`
- `Attachment` + auditoría de eliminaciones (si aplica)

**Empleado:**
- `AssetMovement` donde `employee_id = employee` (asignaciones/préstamos/devoluciones) + actor + nota
- `ProductQuantityMovement` donde `employee_id = employee` (movimientos por cantidad vinculados) + actor + nota
- `Note`
- `Attachment` + auditoría de eliminaciones (si aplica)

**Tarea Pendiente:**
- eventos auditados de locks/admin overrides (`AuditLog` con subject `PendingTask`) como mínimo
- (si existen) eventos de procesamiento/finalización relevantes.

### AC3 — RBAC y control de acceso (sin filtraciones)

**Given** un usuario `Lector` autenticado  
**When** abre el detalle de Producto/Activo  
**Then** puede ver la Timeline **sin acciones destructivas**  
**And** NO ve ni puede descargar/subir/eliminar adjuntos (MVP)  
**And** no se filtra metadata sensible de adjuntos (nombres, IDs) vía Timeline.

**Given** un usuario `Admin` o `Editor` autenticado  
**When** abre la Timeline  
**Then** ve eventos de adjuntos y puede acceder a descarga (si aplica) respetando `attachments.view` y visibilidad de entidad.

### AC4 — Rendimiento + UX “no se siente lenta”

**Given** una entidad con muchos eventos (movimientos/adjuntos/notas)  
**When** el usuario abre la Timeline o carga más  
**Then** la UI sigue siendo usable:
- paginación / “Cargar más” (sin traer miles de registros de golpe),
- sin N+1 (queries eficientes + eager loading),
- si una query puede tardar >3s, integrar `<x-ui.long-request />` en el área de resultados.

### AC5 — No reinventar ruedas / consistencia

**Given** ya existen componentes/infraestructura (`NotesPanel`, `AttachmentsPanel`, `AuditRecorder`, kardex/movimientos)  
**When** se implementa la Timeline  
**Then** se reutilizan los modelos/patrones existentes y se evita duplicar lógica o crear “otro audit/kardex”.

## Tasks / Subtasks

- [x] 1) Diseño de eventos unificados (AC1–AC2)
  - [x] Definir `TimelineEvent` (DTO) + mapeo de cada fuente (movimientos/ajustes/notas/adjuntos/auditoría)
  - [x] Definir filtros por tipo (chips) y formato de resumen por evento
- [x] 2) Componente Livewire `ui.timeline-panel` (AC1, AC4)
  - [x] Props bloqueadas: `entityType`, `entityId`, `viewGate`
  - [x] Paginación ("Cargar más") + filtros (sin recargar)
  - [x] Integrar `<x-ui.long-request />` si aplica
- [x] 3) Integración en pantallas (AC1–AC3)
  - [x] `inventory/products/product-show.blade.php`
  - [x] `inventory/assets/asset-show.blade.php`
  - [x] `employees/employee-show.blade.php`
  - [x] `pending-tasks/pending-task-show.blade.php`
- [x] 4) Tests (AC3–AC4)
  - [x] Feature: Lector NO ve eventos/links de adjuntos en timeline (Producto/Activo)
  - [x] Feature: Admin/Editor sí ve eventos de adjuntos (y descarga si aplica)
  - [x] Feature: mezcla cronológica correcta (2+ fuentes)
  - [x] Regression: queries no incluyen soft-deleted donde aplique (ver checklist)

## Dev Notes

### Developer Context (reuse-first)

**Ya existe** casi todo lo necesario; esta story es principalmente **composición** + “pegamento”:

- **Notas manuales (Story 8.2)**:
  - Componente reusable: `gatic/app/Livewire/Ui/NotesPanel.php`
  - Vista: `gatic/resources/views/livewire/ui/notes-panel.blade.php`
  - Gate de vista depende de la entidad (`inventory.view` para Product/Asset; `inventory.manage` para Employee).
  - Create restringido a `notes.manage` (Admin/Editor).
- **Adjuntos (Story 8.3)**:
  - Componente reusable: `gatic/app/Livewire/Ui/AttachmentsPanel.php`
  - Vista: `gatic/resources/views/livewire/ui/attachments-panel.blade.php`
  - Download “borde”: `gatic/app/Http/Controllers/Attachments/DownloadAttachmentController.php`
  - Gates: `attachments.view` / `attachments.manage` (Lector NO).
  - Auditoría de adjuntos ya registra eventos por entidad (subject = Product/Asset/Employee) con `AuditRecorder`.
- **Auditoría transversal (Story 8.1)**:
  - Modelo: `gatic/app/Models/AuditLog.php` (acciones estables + labels)
  - Recorder: `gatic/app/Support/Audit/AuditRecorder.php` + job best-effort.
- **Historial de dominio**:
  - Serializados: `gatic/app/Models/AssetMovement.php` (assign/unassign/loan/return)
  - Cantidad: `gatic/app/Models/ProductQuantityMovement.php` (in/out, before/after)
  - Ajustes: `gatic/app/Models/InventoryAdjustment.php` + `InventoryAdjustmentEntry.php` (before/after + razón)
- **Pantallas objetivo (ya existen)**:
  - Producto: `gatic/app/Livewire/Inventory/Products/ProductShow.php` + `.../product-show.blade.php`
  - Activo: `gatic/app/Livewire/Inventory/Assets/AssetShow.php` + `.../asset-show.blade.php`
  - Empleado: `gatic/app/Livewire/Employees/EmployeeShow.php` + `.../employee-show.blade.php`
  - Tarea Pendiente: `gatic/app/Livewire/PendingTasks/PendingTaskShow.php` + `.../pending-task-show.blade.php`

### Guardrails (cosas que NO se deben romper)

- **RBAC server-side** siempre:
  - Producto/Activo: `inventory.view` (incluye Lector).
  - Empleados + Tareas Pendientes: `inventory.manage` (solo Admin/Editor).
  - Adjuntos: requieren además `attachments.view`/`attachments.manage` (Lector NO puede ver metadata, links ni IDs).
- **No duplicar UIs existentes**:
  - NO crear “otro kardex”.
  - NO crear “otra auditoría” paralela.
  - Timeline puede **linkear** a pantallas existentes.
- **Evitar duplicados en el feed**:
  - Si un evento está representado por un registro de dominio (movimiento/ajuste/nota/adjunto), no mostrar una segunda fila “audit” idéntica salvo que agregue valor (p.ej. eliminación de adjunto que ya no existe en tabla).
- **Evitar filtraciones por side-channel**:
  - La timeline no debe revelar nombres/IDs de adjuntos a usuarios sin `attachments.view`.
  - Si se muestra información de auditoría, debe ser **sanitizada** (no escupir JSON completo a Lector).

### UX / UI Notes (consistencia con el producto)

- UI en español; identificadores/código en inglés.
- Timeline se siente “productiva” (desktop-first): lista escaneable, íconos claros, timestamps consistentes.
- Para performance/latencia:
  - Si el panel hace queries potencialmente lentas (>3s), integrar `<x-ui.long-request />` (obligatorio).
  - Evitar N+1: cargar `actor`/`employee`/`product` con eager loading cuando aplique.
- Estados vacíos claros: “Sin actividad registrada aún.”

### Project Structure Notes (sugerido)

- Lógica de agregación: `gatic/app/Support/Timeline/*`
- Componente UI: `gatic/app/Livewire/Ui/TimelinePanel.php`
- Vista: `gatic/resources/views/livewire/ui/timeline-panel.blade.php`
- Tests: `gatic/tests/Feature/Timeline/*`

### References (source of truth)

- Epic/AC: `_bmad-output/implementation-artifacts/epics.md` (Epic 14 / Story 14.8)
- Bible: `docsBmad/project-context.md`
- RBAC: `docsBmad/rbac.md` + `gatic/app/Providers/AuthServiceProvider.php`
- Arquitectura/patrones: `_bmad-output/implementation-artifacts/architecture.md`
- UX: `_bmad-output/implementation-artifacts/ux.md`
- Implementación existente: Story 8.1/8.2/8.3 (archivos en la sección “Fuentes” arriba)

## Technical Requirements

### TR1 — Timeline como agregador (no como nuevo sistema)

- La timeline **no introduce** un nuevo modelo “histórico” duplicado. Consume fuentes existentes:
  - `AssetMovement`, `ProductQuantityMovement`, `InventoryAdjustmentEntry`
  - `Note`, `Attachment`
  - `AuditLog` (solo donde agrega valor: eliminaciones/operaciones sin registro visible)

### TR2 — Evento unificado (DTO) con contrato estable

Definir un DTO (p.ej. `App\Support\Timeline\TimelineEvent`) con campos mínimos:
- `type` (string estable: `movement.asset.loan`, `note.create`, etc.)
- `occurred_at` (datetime)
- `actor_user_id` (nullable) + `actor_name` (string visible)
- `title` (1 línea) + `summary` (1–2 líneas)
- `meta` allowlisted (array) solo con datos no sensibles para render
- `link` opcional (route name + params) cuando exista una pantalla “source of truth” (ej. kardex)

### TR3 — Allowlist de entidades soportadas (seguridad)

- La UI debe aceptar `entityType` como class-string **solo** si está en una allowlist:
  - `App\Models\Product`, `App\Models\Asset`, `App\Models\Employee`, `App\Models\PendingTask`
- Rechazar cualquier otro valor con `404` (igual que Notes/Attachments panels).

### TR4 — Fuente de eventos por entidad (queries concretas)

**Product (id = X):**
- `ProductQuantityMovement::where('product_id', X)` con `actorUser` y `employee`
- `InventoryAdjustmentEntry::where('product_id', X)` con `adjustment.actor`
- `Note::where(noteable_type=Product::class, noteable_id=X)` con `author`
- `Attachment::where(attachable_type=Product::class, attachable_id=X)` con `uploader` (solo si `attachments.view`)
- `AuditLog::where(subject_type=Product::class, subject_id=X)` filtrando a acciones relevantes (notas/adjuntos/papelera)

**Asset (id = X):**
- `AssetMovement::where('asset_id', X)` con `actor` y `employee`
- `InventoryAdjustmentEntry::where('asset_id', X)` con `adjustment.actor`
- `Note` / `Attachment` / `AuditLog` igual que Product

**Employee (id = X):**
- `AssetMovement::where('employee_id', X)` con `actor` y `asset.product` (para resumen)
- `ProductQuantityMovement::where('employee_id', X)` con `actorUser` y `product`
- `Note` / `Attachment` / `AuditLog` por Employee

**PendingTask (id = X):**
- `AuditLog::where(subject_type=PendingTask::class, subject_id=X)` para:
  - overrides admin (force claim/release)
  - (si aplica) otras acciones auditadas relevantes

### TR5 — Ordenamiento, paginación y “Cargar más”

- Orden base: `occurred_at desc`, tie-breaker por `source` + `id` para orden estable.
- Implementación recomendada (MVP):
  - Traer “ventanas” por fuente (p.ej. 25–50 por fuente), mapear a `TimelineEvent`, **merge+sort** y devolver `pageSize` (p.ej. 25).
  - “Cargar más” usa cursor (p.ej. `beforeOccurredAt` + `beforeKey`) y aplica `where created_at < cursor` por fuente.
- Evitar traer listas completas (no `->get()` masivo).

### TR6 — Filtrado por tipo + RBAC en el servidor

- Filtros (chips) por categorías: `Movimientos`, `Ajustes`, `Notas`, `Adjuntos`, `Sistema`.
- El filtrado debe aplicarse en servidor (Livewire) y **respetar gates**:
  - Si no `attachments.view`, la categoría `Adjuntos` ni aparece y no se consulta DB.
  - Lector: nunca ver metadata de adjuntos (ni aunque existan).

## Architecture Compliance

### ACmp1 — Livewire-first, controllers solo “bordes”

- Timeline debe ser una **pantalla/panel Livewire** (route → componente o componente embebido).
- No agregar controllers salvo que sea estrictamente un “borde” (ya existe download de adjuntos).

### ACmp2 — Dónde vive la lógica

- Agregación/normalización de eventos en `app/Support/*` (no en Blade).
- Livewire se limita a:
  - autorizar,
  - recibir props,
  - aplicar filtros/paginación,
  - delegar a `Support\Timeline`.

### ACmp3 — Seguridad y autorización

- Usar `Gate::authorize(...)` en `mount()`/`render()` (como en `ProductShow`, `AssetShow`, `EmployeeShow`).
- La timeline no puede ser “solo UI”; el servidor debe bloquear.

### ACmp4 — Performance y consistencia

- Evitar N+1 con eager loading.
- Evitar queries “sin índice” o full scans:
  - filtrar por FK (`asset_id`, `product_id`, `employee_id`) y fechas (cursor).
  - si falta índice, agregar migración (ver sección Testing/Perf).

### ACmp5 — Copy/UI vs identifiers

- Copy en español (labels, empty states, mensajes).
- Identificadores de código/DB/rutas en inglés.

## Library / Framework Requirements

### Stack baseline (repo actual)

- Laravel: `laravel/framework` `v11.47.0` (ver `gatic/composer.lock`)
- Livewire: `livewire/livewire` `v3.7.3` (ver `gatic/composer.lock`)
- Bootstrap: `bootstrap` `^5.2.3` + `bootstrap-icons` `^1.11.3` (ver `gatic/package.json`)
- Vite: `vite` `^6.0.11` (ver `gatic/package.json`)
- DB: MySQL 8 (por bible)

### Reglas de implementación (para evitar “wrong libraries”)

- No agregar paquetes nuevos para “activity feed” o “audit viewers”.
- Usar patrones Livewire existentes:
  - `#[Locked]` para props de identidad (como `NotesPanel`/`AttachmentsPanel`)
  - `#[Computed]` para queries derivadas cuando aplique
  - `WithPagination` (theme bootstrap) si se usa paginación interna
- Mantener consistencia de icons: Bootstrap Icons (`bi bi-*`).

## File Structure Requirements

### Nuevos archivos (sugeridos)

- `gatic/app/Support/Timeline/TimelineEvent.php` (DTO)
- `gatic/app/Support/Timeline/TimelineEventType.php` (constantes/labels/íconos)
- `gatic/app/Support/Timeline/TimelineBuilder.php` (agregación por entidad + filtros + cursor)
- `gatic/app/Support/Timeline/Providers/*` (opcional; 1 provider por fuente)
- `gatic/app/Livewire/Ui/TimelinePanel.php`
- `gatic/resources/views/livewire/ui/timeline-panel.blade.php`
- `gatic/tests/Feature/Timeline/TimelineRbacTest.php`
- `gatic/tests/Feature/Timeline/TimelineChronologyTest.php`
- `gatic/tests/Feature/Timeline/TimelinePerformanceTest.php` (si aplica; validar #queries o límites)

### Archivos a modificar (integración)

- `gatic/resources/views/livewire/inventory/products/product-show.blade.php`
  - Insertar `<livewire:ui.timeline-panel :entity-type="\\App\\Models\\Product::class" :entity-id=\"$product->id\" />`
  - Mantener `NotesPanel` y `AttachmentsPanel` (o justificar refactor a tabs) sin duplicar UX.
- `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php`
- `gatic/resources/views/livewire/employees/employee-show.blade.php`
- `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php`

### Naming / routing

- No agregar rutas nuevas solo para timeline; es un panel embebido en pantallas existentes.

## Testing Requirements

### Test suite mínima (feature)

- **RBAC / no filtraciones (crítico)**:
  - `Lector` puede ver Producto/Activo pero:
    - no ve eventos de adjuntos,
    - no ve links `attachments.download`,
    - no ve metadata/IDs de adjuntos en HTML.
  - `Admin/Editor` sí ve eventos de adjuntos (y link de descarga) cuando corresponde.
- **Cronología (combinación real)**:
  - Crear 2+ fuentes con timestamps distintos (p.ej. un movimiento y una nota) y verificar orden.
- **Sanitización**:
  - Notas se renderizan escapadas (texto plano); no permitir HTML (regresión de 8.2).
- **Soft-delete regression check (según checklist)**:
  - Si se agregan queries/listados nuevos que cruzan modelos con `SoftDeletes`, incluir un test que asegure que registros con `deleted_at` NO aparecen donde no deben.
  - Si se decide mostrar historial aunque el “sujeto” esté eliminado, el test debe validar copy consistente (p.ej. “Registro eliminado”) sin 500.

### Recomendaciones de pruebas Livewire

- Preferir `Livewire::test()` sobre asserts frágiles de HTML completo.
- Asegurar que el componente falla con `404` si `entityType` no está allowlisted.

## Previous Story Intelligence (reutilización / evitar regresiones)

### Desde Story 8.1 (Auditoría best-effort)

- `AuditRecorder::record(...)` es best-effort; nunca debe romper UI.
- `AuditLog` tiene **acciones estables** (`ACTION_*`) y labels (`ACTION_LABELS`) ya usados en UI Admin.
- Para timeline:
  - usar `AuditLog::ACTION_LABELS` cuando aplique (consistencia),
  - pero **no** mostrar `context` completo a roles no Admin.

### Desde Story 8.2 (Notas manuales)

- Notas se guardan/renderizan como **texto plano escapado** (no HTML).
- Vista de notas sigue visibilidad de entidad; crear notas restringido a `notes.manage`.
- Timeline debe respetar lo mismo y evitar duplicar el formulario (puede dejar creación en `NotesPanel`).

### Desde Story 8.3 (Adjuntos seguros)

- Lector no ve/descarga/sube/elimina adjuntos (gates `attachments.*`).
- `AttachmentsPanel` ya combina:
  - Gate de entidad + `attachments.view/manage`,
  - validación de tipo/tamaño,
  - y auditoría de subidas/eliminaciones por **entidad** (subject = Product/Asset/Employee).
- Timeline debe:
  - ocultar por completo adjuntos si no `attachments.view`,
  - evitar “side-channels” (no renderizar nombre/ID si no puede ver),
  - usar route `attachments.download` (ya protegido por middleware `can:attachments.view`) solo cuando el usuario puede.

### Desde Story 14.7 (Patrones de “pegamento” UI)

- En este repo ya existe el patrón de “bridge” incremental sin romper UX:
  - Cambios pequeños en Blade/JS, lógica en `app/Support/*`, tests feature.
- Timeline debería seguir la misma filosofía: **pequeña superficie de cambio**, alta reutilización, tests claros.

## Git Intelligence Summary (contexto reciente)

Commits más recientes (títulos):
- `c5fc425` feat(users): agregar campos departamento/cargo y preferencias UI de usuario
- `f491cf1` feat(admin): add system settings module with DB-backed configuration
- `49191b5` feat(inventory): add useful life tracking and renewal alerts
- `000432c` feat(inventory): add acquisition cost tracking and inventory value dashboard
- `329707a` feat(assets): add warranty tracking with alerts

Relevancia para 14.8:
- El repo ya trae muchos módulos “históricos” (movimientos, ajustes, auditoría, notas, adjuntos). La timeline debe **conectar** y no reinventar.
- Mantener consistencia con la forma actual de agregar “paneles” en vistas de detalle (ver integración de `NotesPanel`/`AttachmentsPanel`).

## Latest Tech Information (verificado en repo) — 2026-02-07

- Laravel: `11.47.0` (no hacer upgrades mayores en esta story).
- Livewire: `3.7.3` (usar APIs ya presentes en el repo).
- Bootstrap: `5.2.x` + Bootstrap Icons `1.11.x` (mantener consistencia visual).
- Vite: `6.x` (evitar cambios de bundling).

Regla práctica: **si no está roto, no lo actualices** dentro de esta story; el objetivo es UI/queries, no upgrades.

## Project Context Reference (must-read)

- `docsBmad/project-context.md` (bible):
  - sin WebSockets; polling cuando aplique
  - errores en prod con `error_id` (detalle solo Admin)
  - adjuntos: UUID en disco + ACL estricta
  - auditoría: best-effort, no bloqueante
- `docsBmad/rbac.md` + `gatic/app/Providers/AuthServiceProvider.php`:
  - Lector: consulta, sin acciones destructivas ni adjuntos (MVP)
  - gates relevantes: `inventory.view`, `inventory.manage`, `attachments.view/manage`, `notes.manage`
- `_bmad-output/implementation-artifacts/architecture.md`:
  - Livewire-first; lógica en `app/Support/*`
  - controllers solo bordes
- `_bmad-output/implementation-artifacts/ux.md`:
  - “historial accesible” como driver de confianza/control
  - UX de baja fricción; estados claros; listas escaneables

## Story Completion Status

- Status: **done**
- Completion note: "Code review aplicado — Timeline panel integrado en 4 pantallas de detalle con RBAC, filtros, paginación estable y tests."

## Dev Agent Record

### Agent Model Used

Claude Opus 4.6

### Debug Log References

- `_bmad-output/implementation-artifacts/validation-report-2026-02-07T215834Z.md`

### Implementation Plan

- Task 1: Created `TimelineEvent` DTO, `TimelineEventType` constants/labels/icons, and `TimelineBuilder` aggregator
- Task 2: Created `TimelinePanel` Livewire component with filter chips, "Cargar más" pagination, and RBAC
- Task 3: Integrated `<livewire:ui.timeline-panel>` in 4 show views (Product, Asset, Employee, PendingTask)
- Task 4: Created feature tests for RBAC (Lector/Admin/Editor) and chronological ordering

### Completion Notes List

- Story `14-8-timeline-y-changelog-por-entidad` creada y marcada `ready-for-dev` (GPT-5.2).
- Implementation: Timeline panel as aggregator consuming existing domain tables (no new DB tables/migrations).
- Timeline sources: AssetMovement, ProductQuantityMovement, InventoryAdjustmentEntry, Note, Attachment, AuditLog.
- RBAC: Lector cannot see attachment events/metadata; attachment filter chip hidden for non-attachments.view users.
- Security: Entity type allowlist (404 for invalid types), Gate::authorize on every action.
- Performance: Per-source limit (50), keyset pagination estable (cursor por `occurred_at` + `sort_key`), eager loading.
- Quality: Pint clean, Larastan level 5 clean, 16 feature tests covering RBAC + chronology + XSS.
- Tests require Docker/Sail MySQL to run (standard for this project).

### File List

**Creado:**
- `gatic/app/Support/Timeline/TimelineEvent.php`
- `gatic/app/Support/Timeline/TimelineEventType.php`
- `gatic/app/Support/Timeline/TimelineBuilder.php`
- `gatic/app/Livewire/Ui/TimelinePanel.php`
- `gatic/resources/views/livewire/ui/timeline-panel.blade.php`
- `gatic/tests/Feature/Timeline/TimelineRbacTest.php`
- `gatic/tests/Feature/Timeline/TimelineChronologyTest.php`

**Modificado:**
- `gatic/resources/views/livewire/inventory/products/product-show.blade.php`
- `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php`
- `gatic/resources/views/livewire/employees/employee-show.blade.php`
- `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `_bmad-output/implementation-artifacts/14-8-timeline-y-changelog-por-entidad.md`

### Change Log

- 2026-02-07: Implemented Timeline/changelog por entidad — unified timeline panel aggregating movements, adjustments, notes, attachments, and audit events across Product/Asset/Employee/PendingTask detail pages. RBAC enforced server-side. 16 feature tests added.
- 2026-02-07: Senior code review fixes — corrected escaping (XSS test), added per-event details (AC1), and fixed pagination cursor to avoid skipping same-timestamp events.

## Senior Developer Review (AI)

Fecha: 2026-02-07

### Hallazgos (resueltos)

- Fixed doble-escape en Blade para `summary` (XSS test + rendering).
- Implementado acceso a “Detalle” por evento (expand/collapse) en Timeline (AC1).
- Cursor de paginación endurecido: `cursorSortKey` + comparación estable para evitar saltos con timestamps iguales.
- Fixed generación de URL de descarga de adjuntos: parámetro correcto `{id}` para `attachments.download` (evita `UrlGenerationException`).

### Notas

- La story file aparece como `??` (untracked) en git: asegúrate de agregarla al commit para que el historial quede trazable.
