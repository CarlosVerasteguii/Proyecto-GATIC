# Story 7.1: Crear Tarea Pendiente y administrar renglones

Status: done

Story Key: `7-1-crear-tarea-pendiente-y-administrar-renglones`  
Epic: `7` (Gate 4: Tareas Pendientes + locks de concurrencia)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`

Fuentes (relevantes):
- `_bmad-output/project-planning-artifacts/epics.md` (Epic 7 / Story 7.1; FR26, FR27)
- `_bmad-output/prd.md` (FR26, FR27; NFR7; Journey 2)
- `_bmad-output/project-planning-artifacts/ux-design-specification.md` (Tareas Pendientes; Journey 2; LockBanner; adoption-first)
- `_bmad-output/architecture.md` (Epic 7 mapping; `app/Actions/PendingTasks/*`; `app/Livewire/PendingTasks/*`; transacciones + validaciones)
- `docsBmad/project-context.md` (bible: stack; RBAC server-side; rutas/código en inglés y UI en español)
- `project-context.md` (stack; reglas críticas para agentes; testing; locks + timeouts)
- `docsBmad/rbac.md` (gates `inventory.manage`, defensa en profundidad)
- `gatic/app/Models/Product.php` + `gatic/app/Models/Category.php` (producto por cantidad vs serializado)
- `gatic/app/Actions/Inventory/Adjustments/ApplyProductQuantityAdjustment.php` (patrón transaccional)
- `gatic/app/Livewire/Inventory/Products/ProductsIndex.php` (patrón de lista + acciones)
- `gatic/app/Livewire/Ui/EmployeeCombobox.php` (patrón de selector con validación)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Admin/Editor,  
I want crear una Tarea Pendiente para procesar varios renglones en lote,  
so that pueda registrar operaciones de forma rápida y estructurada (FR26, FR27).

## Alcance (MVP)

Incluye:
- Crear una Tarea Pendiente con metadata mínima: **tipo de operación** (ej. Salida, Entrada, Asignación, Préstamo, Retiro), **descripción** (opcional), **usuario creador** y **timestamp**.
- Estados básicos de la Tarea:
  - `draft` (borrador): permite añadir/editar/eliminar renglones.
  - `ready` (listo para procesar): no permite editar renglones; lista para claim/procesamiento.
  - `processing` (en procesamiento): bloqueada por lock (Story 7.4).
  - `completed` (finalizada): procesamiento completo.
  - `partially_completed` (finalización parcial): algunos renglones aplicados, otros con error.
  - `cancelled` (cancelada): descartada sin aplicar.
- Administración de renglones antes de finalizar (estado `draft`):
  - **Añadir renglón**: especificar tipo (serializado vs cantidad), Producto, datos específicos (serial/asset_tag o cantidad), Empleado (RPE) y nota.
  - **Editar renglón**: modificar datos del renglón (antes de finalizar).
  - **Eliminar renglón**: quitar renglón de la tarea (antes de finalizar).
- Vista de lista de Tareas Pendientes con filtros básicos (estado, tipo de operación, usuario creador).
- Vista de detalle de Tarea Pendiente con lista de renglones y acciones según estado y rol.
- Validaciones mínimas por renglón:
  - Renglón **serializado**: `serial` y/o `asset_tag` con formato alfanumérico mínimo (longitud mínima acordada; ej. 3 caracteres).
  - Renglón **por cantidad**: cantidad entero > 0.
  - **Empleado** y **Nota** obligatorios para todos los renglones (adoption-first).
- Transición de estado `draft` → `ready` cuando el usuario indica que está listo para procesar.
- Defensa en profundidad: rutas protegidas con `can:inventory.manage` + `Gate::authorize('inventory.manage')` en Livewire/Actions.

No incluye (fuera de scope):
- Lock/claim de concurrencia (Story 7.4).
- Procesamiento/finalización de renglones (Story 7.3).
- Heartbeat/TTL/timeout (Story 7.4).
- Override Admin de locks (Story 7.5).
- Validaciones profundas contra inventario (ej. verificar si serial ya existe, disponibilidad de stock) – eso se valida al procesar (Story 7.3).
- Auditoría (Epic 8).
- Adjuntos (Epic 8).

## Acceptance Criteria

### AC1 - Crear Tarea Pendiente (FR26)

**Given** un Admin/Editor autenticado  
**When** crea una nueva Tarea Pendiente  
**Then** la tarea queda registrada con estado `draft`  
**And** se captura tipo de operación, descripción opcional, usuario creador y timestamp  
**And** la tarea aparece en la lista de Tareas Pendientes.

### AC2 - Añadir renglón a Tarea Pendiente (FR27)

**Given** una Tarea Pendiente en estado `draft`  
**When** el usuario añade un renglón (serializado o cantidad)  
**Then** el renglón queda registrado con los datos especificados (Producto, serial/asset_tag o cantidad, Empleado, nota)  
**And** el sistema valida formato mínimo (alfanum para serial/asset_tag; entero > 0 para cantidad)  
**And** permite duplicados de serial/asset_tag dentro de la tarea (los resalta visualmente) sin bloquear el guardado.

### AC3 - Editar renglón de Tarea Pendiente (FR27)

**Given** una Tarea Pendiente en estado `draft` con renglones existentes  
**When** el usuario edita un renglón  
**Then** el sistema actualiza los datos del renglón  
**And** aplica las mismas validaciones que al añadir.

### AC4 - Eliminar renglón de Tarea Pendiente (FR27)

**Given** una Tarea Pendiente en estado `draft` con renglones existentes  
**When** el usuario elimina un renglón  
**Then** el renglón se quita de la tarea  
**And** no queda rastro en la lista de renglones (borrado físico en esta story; soft-delete es opcional para MVP).

### AC5 - Validación: Empleado y Nota obligatorios (adoption-first)

**Given** el formulario de renglón  
**When** el usuario intenta guardar sin Empleado o sin Nota  
**Then** el sistema bloquea la operación  
**And** muestra mensajes de validación inline indicando los campos obligatorios.

### AC6 - Transición de estado `draft` → `ready`

**Given** una Tarea Pendiente en estado `draft` con al menos un renglón válido  
**When** el usuario marca la tarea como "Lista para procesar"  
**Then** el estado cambia a `ready`  
**And** ya no se permite añadir/editar/eliminar renglones  
**And** la tarea queda disponible para claim/procesamiento (Story 7.4).

### AC7 - Bloqueo de edición en estados no `draft`

**Given** una Tarea Pendiente en estado `ready`, `processing`, `completed`, `partially_completed` o `cancelled`  
**When** el usuario intenta añadir/editar/eliminar renglones  
**Then** el sistema bloquea la operación  
**And** la UI muestra los renglones como solo lectura.

### AC8 - Acceso por rol (defensa en profundidad)

**Given** un usuario Admin o Editor  
**When** crea o gestiona una Tarea Pendiente  
**Then** el servidor permite la operación.

**Given** un usuario Lector  
**When** intenta acceder por URL directa o ejecutar acciones Livewire de Tareas Pendientes  
**Then** el servidor bloquea (403 o equivalente).

### AC9 - Vista de lista de Tareas Pendientes con filtros básicos

**Given** múltiples Tareas Pendientes en el sistema  
**When** el usuario navega a la lista de Tareas Pendientes  
**Then** ve todas las tareas con información básica (tipo, estado, usuario creador, fecha)  
**And** puede filtrar por estado y tipo de operación  
**And** puede navegar al detalle de cada tarea.

### AC10 - Vista de detalle de Tarea Pendiente con lista de renglones

**Given** una Tarea Pendiente existente  
**When** el usuario navega al detalle de la tarea  
**Then** ve la metadata de la tarea (tipo, estado, descripción, usuario creador, fecha)  
**And** ve la lista de renglones con sus datos (Producto, serial/asset_tag o cantidad, Empleado, nota)  
**And** las acciones disponibles (añadir/editar/eliminar renglón, marcar lista) dependen del estado y rol.

## Tasks / Subtasks

1) Modelo de datos (AC: 1, 2, 3, 4, 6, 7)
- [x] Crear tabla `pending_tasks` con:
  - `id` (PK)
  - `type` (enum: `stock_out`, `stock_in`, `assign`, `loan`, `return`, `retirement`) – tipo de operación
  - `description` (text nullable)
  - `status` (enum: `draft`, `ready`, `processing`, `completed`, `partially_completed`, `cancelled`)
  - `creator_user_id` (FK a `users`)
  - `locked_by_user_id` (FK a `users`, nullable) – para Story 7.4
  - `locked_at`, `heartbeat_at`, `expires_at` (timestamps nullable) – para Story 7.4
  - `timestamps` + `soft_deletes` (opcional)
  - Índices: (`status`, `creator_user_id`, `created_at`)
- [x] Crear tabla `pending_task_lines` (renglones) con:
  - `id` (PK)
  - `pending_task_id` (FK a `pending_tasks`)
  - `line_type` (enum: `serialized`, `quantity`) – tipo de renglón
  - `product_id` (FK a `products`)
  - `serial` (string nullable) – para renglón serializado
  - `asset_tag` (string nullable) – para renglón serializado
  - `quantity` (unsigned int nullable) – para renglón por cantidad
  - `employee_id` (FK a `employees`)
  - `note` (text)
  - `line_status` (enum: `pending`, `processing`, `applied`, `error`) – para Story 7.3; por ahora solo `pending`
  - `error_message` (text nullable) – para Story 7.3
  - `order` (unsigned int) – orden de captura/display
  - `timestamps`
  - Índices: (`pending_task_id`, `order`), (`line_status`)
- [x] Crear modelos `PendingTask` y `PendingTaskLine` con relaciones:
  - `PendingTask` hasMany `PendingTaskLine`
  - `PendingTask` belongsTo `User` (creator)
  - `PendingTask` belongsTo `User` (locked_by) – nullable
  - `PendingTaskLine` belongsTo `PendingTask`
  - `PendingTaskLine` belongsTo `Product`
  - `PendingTaskLine` belongsTo `Employee`
- [x] Crear Enums:
  - `PendingTaskType` (stock_out, stock_in, assign, loan, return, retirement)
  - `PendingTaskStatus` (draft, ready, processing, completed, partially_completed, cancelled)
  - `PendingTaskLineType` (serialized, quantity)
  - `PendingTaskLineStatus` (pending, processing, applied, error)

2) Casos de uso / Actions (AC: 1, 2, 3, 4, 6)
- [x] Action `CreatePendingTask` en `app/Actions/PendingTasks/`:
  - Validaciones: `type`, `description` (opcional), `creator_user_id`.
  - Crear tarea con estado `draft`.
  - Retornar modelo creado.
- [x] Action `AddLineToTask` en `app/Actions/PendingTasks/`:
  - Validaciones:
    - Tarea en estado `draft`.
    - `line_type`, `product_id`, `employee_id`, `note` obligatorios.
    - Si `line_type=serialized`: validar `serial` y/o `asset_tag` (alfanum, longitud mínima).
    - Si `line_type=quantity`: validar `quantity` (entero > 0).
  - Cargar Producto y validar que `line_type` coincide con `category.is_serialized`.
  - Asignar `order` (último + 1).
  - Crear renglón con `line_status=pending`.
  - Detectar duplicados de `serial`/`asset_tag` dentro de la tarea (flag o metadato para UI).
  - Retornar renglón creado.
- [x] Action `UpdateTaskLine` en `app/Actions/PendingTasks/`:
  - Validaciones: tarea en estado `draft`; mismas validaciones que `AddLineToTask`.
  - Actualizar renglón existente.
  - Retornar renglón actualizado.
- [x] Action `RemoveLineFromTask` en `app/Actions/PendingTasks/`:
  - Validaciones: tarea en estado `draft`.
  - Eliminar renglón (borrado físico).
  - Retornar confirmación.
- [x] Action `MarkTaskAsReady` en `app/Actions/PendingTasks/`:
  - Validaciones:
    - Tarea en estado `draft`.
    - Al menos un renglón con `line_status=pending`.
  - Cambiar estado a `ready`.
  - Retornar tarea actualizada.

3) UI Livewire (AC: 1, 2, 3, 4, 5, 6, 7, 8, 9, 10)
- [x] Agregar rutas protegidas bajo `inventory.manage`:
  - `/pending-tasks` (route name: `pending-tasks.index`) – lista de Tareas Pendientes
  - `/pending-tasks/create` (route name: `pending-tasks.create`) – crear nueva tarea
  - `/pending-tasks/{pending_task}` (route name: `pending-tasks.show`) – detalle + gestión de renglones
- [x] Livewire component `PendingTasksIndex` en `app/Livewire/PendingTasks/`:
  - Lista de tareas con info básica (tipo, estado, usuario creador, fecha).
  - Filtros por estado y tipo de operación.
  - Badge para estado (draft/ready/processing/completed/etc.) con colores consistentes (UX spec).
  - Link a detalle de cada tarea.
  - Botón "Nueva Tarea" (redirige a `pending-tasks.create`).
  - Autorización: `Gate::authorize('inventory.manage')` en `mount()`.
- [x] Livewire component `CreatePendingTask` en `app/Livewire/PendingTasks/`:
  - Form: tipo de operación (select), descripción (textarea opcional).
  - Validación inline.
  - Al guardar: llama `CreatePendingTask` Action y redirige a `pending-tasks.show` con toast de confirmación.
  - Autorización: `Gate::authorize('inventory.manage')` en `mount()`.
- [x] Livewire component `PendingTaskShow` en `app/Livewire/PendingTasks/`:
  - Muestra metadata de la tarea (tipo, estado, descripción, usuario creador, fecha).
  - Lista de renglones con tabla (Producto, serial/asset_tag o cantidad, Empleado, nota, acciones).
  - Si estado = `draft`:
    - Botón "Añadir renglón" (abre modal/drawer).
    - Acciones por renglón: Editar, Eliminar.
    - Botón "Marcar como lista" (llama `MarkTaskAsReady`).
  - Si estado != `draft`:
    - Renglones en modo solo lectura.
    - Sin botón de añadir/editar/eliminar.
  - Detectar duplicados de `serial`/`asset_tag` dentro de la tarea y resaltarlos visualmente (badge warning + tooltip).
  - Autorización: `Gate::authorize('inventory.manage')` en `mount()`.
- [x] Livewire component `TaskLineForm` (modal/drawer) en `app/Livewire/PendingTasks/`:
  - **IMPLEMENTATION NOTE**: Implemented embedded within `PendingTaskShow` component logic rather than standalone component for simplicity and state sharing.
  - Form: tipo de renglón (serializado/cantidad), Producto (select), serial/asset_tag (text) o cantidad (number), Empleado (reusar `EmployeeCombobox`), nota (textarea).
  - Validación inline (alfanum para serial/asset_tag; entero > 0 para cantidad; Empleado y nota obligatorios).
  - Modo crear: llama `AddLineToTask`.
  - Modo editar: llama `UpdateTaskLine`.
  - Al guardar: cierra modal/drawer, refresca lista de renglones, muestra toast de confirmación.
  - Autorización: validar que tarea esté en estado `draft` antes de guardar.

4) Integración en UI existente
- [x] Añadir entry point en sidebar: "Tareas Pendientes" (solo visible para Admin/Editor).
- [ ] En dashboard (si existe): badge con conteo de tareas en estado `draft` o `ready` (opcional; puede esperar a Story 7.4).

5) Tests (AC: 1–10)
- [x] Feature tests para:
  - RBAC: Lector bloqueado; Admin/Editor permitidos.
  - Crear Tarea Pendiente en estado `draft`.
  - Añadir renglón serializado (happy path + validación de formato + duplicados).
  - Añadir renglón por cantidad (happy path + validación entero > 0).
  - Editar renglón (happy path + validación de estado `draft`).
  - Eliminar renglón (happy path + validación de estado `draft`).
  - Validación: Empleado y Nota obligatorios.
  - Transición `draft` → `ready` (happy path + validación de al menos un renglón).
  - Bloqueo de edición en estados != `draft`.
  - Vista de lista con filtros.
  - Vista de detalle con lista de renglones.

### Review Follow-ups (AI)

- [x] [AI-Review][High] Mark completed tasks in story file as `[x]`. (H1)
- [x] [AI-Review][High] Document that `TaskLineForm` is embedded in `PendingTaskShow`. (H2)
- [x] [AI-Review][Medium] Refactor `AddLineToTask` and `UpdateTaskLine` to use shared `ValidatesTaskLines` trait. (M1)
- [x] [AI-Review][Medium] Add missing tests for `line_type` vs `category.is_serialized` mismatch. (M2)
- [x] [AI-Review][Medium] Add N+1 warning to `PendingTask::getLinesCountAttribute`. (M3)
- [x] [AI-Review][Low] Add icon `bi-list-task` to sidebar navigation. (L1)
- [x] [AI-Review][Low] Note in seeders requirement that factories differ. (L2)

## Dev Notes

### Objetivo técnico

Implementar el núcleo de "Tareas Pendientes" como un sistema de batch/carrito operativo que permite:
1. Captura rápida de múltiples renglones (serializados o por cantidad) antes de procesarlos.
2. Validación temprana (formato mínimo) sin bloquear el guardado por duplicados internos (los resalta pero permite continuar).
3. Transición clara de estado `draft` → `ready` cuando el usuario confirma que está listo para procesar.
4. Base sólida para Stories 7.2, 7.3, 7.4 (captura masiva, procesamiento por renglón, locks).

### UX / Comportamiento esperado (muy importante)

- **Adoption-first**: el flujo debe ser rápido y sin fricción. Obligatorio solo lo indispensable (Empleado + Nota por renglón).
- **Desktop-first**: tablas densas, acciones en contexto (botones Editar/Eliminar por fila), modal/drawer para formularios.
- **Validación temprana pero no bloqueante**:
  - Formato mínimo (alfanum para serial/asset_tag; entero > 0 para cantidad) se valida al guardar el renglón.
  - Duplicados de `serial`/`asset_tag` **dentro de la tarea** se resaltan visualmente (badge warning + tooltip) pero NO bloquean el guardado (el usuario puede corregir después o el sistema los marcará como error al procesar en Story 7.3).
  - Validaciones profundas contra inventario (ej. serial ya existe, stock insuficiente) se aplican al **procesar** (Story 7.3), no al capturar.
- **Estados claros**:
  - `draft`: edición libre de renglones.
  - `ready`: bloqueada para edición; lista para claim (Story 7.4).
  - Otros estados (processing, completed, etc.) se habilitan en Stories posteriores.
- **Feedback inmediato**:
  - Toast al crear tarea: "Tarea creada. Añade renglones para comenzar."
  - Toast al añadir/editar/eliminar renglón: "Renglón añadido/actualizado/eliminado."
  - Toast al marcar lista: "Tarea marcada como lista. Ya no puedes editar renglones."
  - Contadores: "X renglones capturados" + "Y duplicados detectados" (si aplica).

### Patrones existentes a respetar (no reinventar)

- **Modelos + Enums**: usar `app/Enums/*` para estados tipados (ver `AssetStatus`, `AssetMovementType`).
- **Actions transaccionales**: seguir patrón de `ApplyProductQuantityAdjustment` (validar → `DB::transaction()` → guardar → retornar).
- **Livewire + Autorización**: `Gate::authorize('inventory.manage')` en `mount()` + validación inline + toasts.
- **Selector de Empleado**: reusar `EmployeeCombobox` (ver `AssetMovementForm`).
- **Tabla con acciones**: ver `ProductsIndex` y `AssetsIndex` para patrón de tabla + filtros + acciones por fila.
- **Modal/Drawer**: preferir drawer derecho para formularios en contexto (ver UX spec); modal para confirmaciones destructivas.

### Project Structure Notes

- Rutas/código/DB: en inglés (kebab-case para paths; dot.case para names). UI/mensajes: en español.
- Componentes sugeridos (alineado a `_bmad-output/architecture.md`):
  - Livewire: `gatic/app/Livewire/PendingTasks/*` (ej. `PendingTasksIndex`, `PendingTaskShow`, `CreatePendingTask`, `TaskLineForm`)
  - Actions: `gatic/app/Actions/PendingTasks/*` (ej. `CreatePendingTask`, `AddLineToTask`, `UpdateTaskLine`, `RemoveLineFromTask`, `MarkTaskAsReady`)
  - Models: `gatic/app/Models/PendingTask.php`, `gatic/app/Models/PendingTaskLine.php`
  - Enums: `gatic/app/Enums/PendingTaskType.php`, `gatic/app/Enums/PendingTaskStatus.php`, `gatic/app/Enums/PendingTaskLineType.php`, `gatic/app/Enums/PendingTaskLineStatus.php`
  - Views: `gatic/resources/views/livewire/pending-tasks/*`
  - Routes: `gatic/routes/web.php` (grupo `inventory.manage`)
- Navegación:
  - Lista: `pending-tasks.index` (`/pending-tasks`)
  - Crear: `pending-tasks.create` (`/pending-tasks/create`)
  - Detalle: `pending-tasks.show` (`/pending-tasks/{pending_task}`)

### Validaciones específicas

- **Renglón serializado**:
  - Al menos uno de `serial` o `asset_tag` debe estar presente.
  - Formato: alfanumérico, longitud mínima 3 caracteres (ajustar según necesidad real; este es un placeholder razonable).
  - Normalización: aplicar `Asset::normalizeSerial()` y `Asset::normalizeAssetTag()` antes de guardar (consistencia con búsqueda).
- **Renglón por cantidad**:
  - `quantity` debe ser entero > 0.
- **Empleado y Nota**: obligatorios para todos los renglones (validación en Action).
- **Producto**: debe existir y coincidir con `line_type` (si `line_type=serialized`, `category.is_serialized=true`; si `line_type=quantity`, `category.is_serialized=false`).

### Duplicados dentro de la tarea

- El sistema **permite** guardar duplicados de `serial`/`asset_tag` dentro de la misma tarea (no bloquea el guardado).
- La UI **resalta** visualmente los duplicados (badge warning + tooltip) para que el usuario los note y corrija si lo desea.
- Al procesar (Story 7.3), el sistema validará contra inventario y marcará como error los renglones duplicados o que ya existan.

### Estados de Tarea (para esta story)

- `draft`: estado inicial; permite añadir/editar/eliminar renglones.
- `ready`: marcada como lista; no permite editar renglones; lista para claim (Story 7.4).
- Otros estados (`processing`, `completed`, `partially_completed`, `cancelled`) se habilitan en Stories posteriores pero deben estar definidos en el Enum para evitar migraciones futuras.

### Estados de Renglón (para esta story)

- `pending`: estado inicial al crear el renglón.
- Otros estados (`processing`, `applied`, `error`) se habilitan en Story 7.3 pero deben estar definidos en el Enum.

### Campos de lock (para Story 7.4, pero definir estructura ahora)

- `locked_by_user_id`, `locked_at`, `heartbeat_at`, `expires_at` en tabla `pending_tasks`.
- En esta story, estos campos quedan `null` (no se usan aún).

### UX de duplicados

- En la tabla de renglones (vista de detalle de tarea), si un `serial`/`asset_tag` aparece más de una vez:
  - Agregar badge warning (color amarillo) con texto "Duplicado" + tooltip con lista de líneas duplicadas.
  - No bloquear el guardado ni la transición a `ready`.
  - El usuario puede corregir eliminando/editando renglones duplicados si lo desea.

### UX de estados (badges)

- Usar colores consistentes con UX spec:
  - `draft`: `bg-secondary text-white` (gris)
  - `ready`: `bg-info text-dark` (azul claro)
  - `processing`: `bg-warning text-dark` (amarillo/naranja; en Story 7.4)
  - `completed`: `bg-success text-white` (verde)
  - `partially_completed`: `bg-purple text-white` (morado)
  - `cancelled`: `bg-danger text-white` (rojo)

### Integración con sidebar

- Añadir item "Tareas Pendientes" en `resources/views/components/app-shell.blade.php` (sidebar).
- Solo visible para Admin/Editor (usar `@can('inventory.manage')`).
- Icono sugerido: `bi-list-task` (Bootstrap Icons).

### Tests específicos

- **RBAC**:
  - Lector intenta acceder a `/pending-tasks` → 403.
  - Admin/Editor accede a `/pending-tasks` → 200.
- **Crear tarea**:
  - Happy path: crea tarea en estado `draft`.
  - Validación: tipo de operación obligatorio.
- **Añadir renglón serializado**:
  - Happy path: guarda renglón con `serial` y `asset_tag`.
  - Validación: formato alfanum, longitud mínima.
  - Validación: Empleado y Nota obligatorios.
  - Duplicado: permite guardar duplicado, pero flag para UI (opcional en test, más importante en UI).
- **Añadir renglón por cantidad**:
  - Happy path: guarda renglón con `quantity`.
  - Validación: cantidad entero > 0.
  - Validación: Empleado y Nota obligatorios.
- **Editar renglón**:
  - Happy path: actualiza renglón en tarea `draft`.
  - Validación: bloquea si tarea no es `draft`.
- **Eliminar renglón**:
  - Happy path: elimina renglón de tarea `draft`.
  - Validación: bloquea si tarea no es `draft`.
- **Transición draft → ready**:
  - Happy path: cambia estado a `ready`.
  - Validación: bloquea si no hay renglones.
  - Validación: bloquea edición de renglones después de transición.
- **Vista de lista**:
  - Muestra todas las tareas del usuario (o todas si Admin).
  - Filtros por estado y tipo funcionan.
- **Vista de detalle**:
  - Muestra metadata y lista de renglones.
  - Acciones disponibles según estado.

## Dev Agent Guardrails (No Negociables)

- Identificadores de código/DB/rutas en inglés; copy/UI en español.
- No usar controllers para pantallas; usar Livewire (controllers solo "bordes").
- Siempre `authorize + validate` antes de mutaciones.
- Operaciones críticas: siempre en transacción (`DB::transaction()`) cuando aplique (aunque en esta story no hay cambios de inventario, sí hay creación de múltiples renglones que deben ser atómicas si se hace en batch).
- Usar Enums para estados tipados (no strings mágicos).
- Rutas: `kebab-case` en inglés; route names: `dot.case` en inglés.
- Tests: cubrir RBAC, happy paths y validaciones críticas.
- UX: feedback inmediato con toasts; validación inline; estados claros con badges.

## References

- Requerimientos/AC: `_bmad-output/project-planning-artifacts/epics.md#Epic 7` y `_bmad-output/prd.md#Pending Tasks & Concurrency Locks`
- Arquitectura: `_bmad-output/architecture.md#Epic 7 (Tareas Pendientes + Locks)`
- UX: `_bmad-output/project-planning-artifacts/ux-design-specification.md#Journey 2 - Editor (Soporte): Tarea Pendiente con lock (concurrencia)`
- Patrones existentes:
  - Actions transaccionales: `gatic/app/Actions/Inventory/Adjustments/ApplyProductQuantityAdjustment.php`
  - Livewire + RBAC: `gatic/app/Livewire/Inventory/Products/ProductsIndex.php`
  - Selector Empleado: `gatic/app/Livewire/Ui/EmployeeCombobox.php`
  - Enums: `gatic/app/Enums/AssetStatus.php`, `gatic/app/Enums/AssetMovementType.php`

## Story Dependencies

- **Depends on**: Epic 1 (Auth/RBAC), Epic 3 (Productos/Activos), Epic 4 (Empleados).
- **Blocks**: Story 7.2 (Captura de renglones masiva), Story 7.3 (Procesamiento por renglón), Story 7.4 (Locks de concurrencia), Story 7.5 (Admin override locks).

## Estimation Notes

- **Complejidad**: Medium
  - Múltiples tablas + Enums + Actions + Livewire components + tests.
  - No hay lógica de inventario compleja (solo validaciones de formato).
  - Base para las siguientes stories (importante hacerlo bien).
- **Esfuerzo estimado**: ~3-5 días (1 dev fullstack)
  - Día 1: Modelos + Enums + migraciones + seeders básicos.
  - Día 2: Actions (Create/Add/Update/Remove/MarkReady) + unit tests.
  - Día 3: Livewire components (Index/Create/Show/TaskLineForm) + feature tests.
  - Día 4: Integración UI (sidebar, badges, duplicados) + polish.
  - Día 5: Tests finales + validación con UX spec + ajustes.

## Rollout / Deploy Notes

- Esta story es "foundational" para Epic 7; debe estar 100% funcional antes de continuar con Story 7.2.
- No requiere feature flag (on-prem; sin usuarios externos).
- Seeders recomendados:
  - 1-2 tareas en estado `draft` con renglones mixtos (serializado + cantidad).
  - 1 tarea en estado `ready` para testing de Story 7.4.
  - Incluir duplicados en una tarea para validar resaltado visual.

## Open Questions / Decisions Pending

1. **Longitud mínima de `serial`/`asset_tag`**: ¿3 caracteres es suficiente? (propuesta razonable; ajustar según data real).
   - **Decisión recomendada**: 3 caracteres como mínimo; si hay assets con serial más corto, bajar a 2. Validar con Carlos.
2. **Borrado de renglones**: ¿físico o soft-delete?
   - **Decisión recomendada**: físico en esta story (simplicidad); soft-delete solo si auditoría lo requiere (Epic 8).
3. **Orden de renglones**: ¿se permite reordenar?
   - **Decisión recomendada**: por ahora, orden de captura (campo `order` auto-incrementa). Drag-and-drop es post-MVP.
4. **Duplicados**: ¿se permite guardar o se bloquea?
   - **Decisión tomada**: se permite guardar (resaltar visualmente); bloqueo al procesar (Story 7.3).
5. **Tipo de operación**: ¿se valida que los renglones coincidan con el tipo de tarea? (ej. si tarea es "Salida", todos los renglones deben ser salida)
   - **Decisión recomendada**: NO validar en esta story (complejidad innecesaria); la tarea es un "contenedor" y el tipo es metadata informativa. La validación real ocurre al procesar (Story 7.3).

## Success Metrics (Post-Implementation)

- [ ] Admin/Editor puede crear tarea en <10s (crear + añadir 1er renglón).
- [ ] Admin/Editor puede añadir 10 renglones en <2 min (carga rápida).
- [ ] Duplicados se resaltan visualmente sin bloquear guardado.
- [ ] Tests cubren RBAC, happy paths y validaciones críticas (coverage ≥80% en Actions).
- [ ] UI es consistente con UX spec (badges, feedback, estados).

## Story Completion Status

- Status: **done**
- Completion note: ACs implementados + regresión OK (tests + pint). Se cerraron hallazgos HIGH/MEDIUM de code review (ver sección "Senior Developer Review (AI)").

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- En Windows, el `php` en PATH es `C:\\xampp\\php\\php.exe` (PHP 8.0.30) y NO debe usarse para este repo (Laravel 11 / deps requieren >= 8.2). Ver `project-context.md`.
- Se ejecutaron pruebas dentro del runtime Sail 8.4 (Docker): `docker compose -f gatic/compose.yaml exec -T laravel.test php artisan test`.
- Se validó estilo con Pint dentro del contenedor: `docker compose -f gatic/compose.yaml exec -T laravel.test vendor/bin/pint --test`.

### Completion Notes List

- Se corrigió UI de Empleado en detalle (se agregó `Employee::$full_name`) para cumplir AC10.
- Se hardenizó `AddLineToTask` para evitar colisiones de `order` (lock en `pending_tasks` dentro de transacción).
- Se corrigió badge `partially_completed` (Bootstrap no incluye `bg-purple`).
- Se corrigió `PendingTaskLineFactory` para que el `product` sea consistente con `line_type`.
- Se arreglaron tests de mismatch (`ValidationException` no expone mensaje directo) y se agregaron tests UI para filtros y detalle.
- Se mejoró mensaje de error al eliminar renglón en `PendingTaskShow`.

### File List

- `_bmad-output/implementation-artifacts/7-1-crear-tarea-pendiente-y-administrar-renglones.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `gatic/app/Actions/PendingTasks/AddLineToTask.php`
- `gatic/app/Actions/PendingTasks/Concerns/ValidatesTaskLines.php`
- `gatic/app/Actions/PendingTasks/CreatePendingTask.php`
- `gatic/app/Actions/PendingTasks/MarkTaskAsReady.php`
- `gatic/app/Actions/PendingTasks/RemoveLineFromTask.php`
- `gatic/app/Actions/PendingTasks/UpdateTaskLine.php`
- `gatic/app/Enums/PendingTaskLineStatus.php`
- `gatic/app/Enums/PendingTaskLineType.php`
- `gatic/app/Enums/PendingTaskStatus.php`
- `gatic/app/Enums/PendingTaskType.php`
- `gatic/app/Livewire/PendingTasks/CreatePendingTask.php`
- `gatic/app/Livewire/PendingTasks/PendingTaskShow.php`
- `gatic/app/Livewire/PendingTasks/PendingTasksIndex.php`
- `gatic/app/Models/Employee.php`
- `gatic/app/Models/PendingTask.php`
- `gatic/app/Models/PendingTaskLine.php`
- `gatic/database/factories/PendingTaskFactory.php`
- `gatic/database/factories/PendingTaskLineFactory.php`
- `gatic/database/migrations/2026_01_18_000000_create_pending_tasks_table.php`
- `gatic/database/migrations/2026_01_18_000001_create_pending_task_lines_table.php`
- `gatic/resources/views/layouts/partials/sidebar-nav.blade.php`
- `gatic/resources/views/livewire/pending-tasks/create-pending-task.blade.php`
- `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php`
- `gatic/resources/views/livewire/pending-tasks/pending-tasks-index.blade.php`
- `gatic/routes/web.php`
- `gatic/tests/Feature/PendingTasks/PendingTaskActionsTest.php`
- `gatic/tests/Feature/PendingTasks/PendingTasksRbacTest.php`
- `gatic/tests/Feature/PendingTasks/PendingTasksUiTest.php`

### Change Log

- 2026-01-20: Implementación Story 7.1 (Pending Tasks: modelos + migraciones + enums + actions + Livewire + tests).
- 2026-01-20: Code review + fixes (Employee full_name, lock+order, badge class, factory consistency, tests UI + mismatch) + regresión/pint OK.

## Senior Developer Review (AI)

- Fecha: 2026-01-20
- Veredicto: **Aprobado** (sin HIGH/MEDIUM pendientes)
- Validación (backend): `docker compose -f gatic/compose.yaml exec -T laravel.test php artisan test` (336 passed, 843 assertions).
- Validación (estilo): `docker compose -f gatic/compose.yaml exec -T laravel.test vendor/bin/pint --test` (PASS).

### Fixes aplicados

- UI: `Employee::$full_name` para que el detalle muestre `RPE - Nombre` (antes se veía `-`).
- Concurrencia: `AddLineToTask` ahora crea renglones en transacción con `lockForUpdate()` para asignar `order` sin colisiones.
- Badges: `PendingTaskStatus::PartiallyCompleted` usa clases Bootstrap válidas.
- Factories: `PendingTaskLineFactory` crea `product` con `category.is_serialized` consistente con `line_type`.
- Tests: se corrigieron asserts de mismatch y se agregaron pruebas para filtros y vista detalle.
