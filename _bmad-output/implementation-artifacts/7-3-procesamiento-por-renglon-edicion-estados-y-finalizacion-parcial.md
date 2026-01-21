# Story 7.3: Procesamiento por renglón (edición + estados) y finalización parcial

Status: done

Story Key: `7-3-procesamiento-por-renglon-edicion-estados-y-finalizacion-parcial`  
Epic: `7` (Gate 4: Tareas Pendientes + locks de concurrencia)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/epics.md` (Epic 7 / Story 7.3; FR28)
- `_bmad-output/implementation-artifacts/prd.md` (FR28; NFR2, NFR7, NFR9)
- `_bmad-output/implementation-artifacts/ux.md` (Journey 2: procesamiento por renglón, resumen de errores, finalización parcial; concurrencia)
- `_bmad-output/implementation-artifacts/architecture.md` (Epic 7 mapping; Actions/Livewire/Models; reglas de estructura)
- `docsBmad/project-context.md` (bible: locks, NFR2 >3s, transacciones)
- `project-context.md` (reglas críticas del repo + toolchain Windows)
- `docsBmad/rbac.md` (gates; `inventory.manage`)
- `_bmad-output/implementation-artifacts/7-1-crear-tarea-pendiente-y-administrar-renglones.md` (base del módulo PendingTasks + decisiones)
- `_bmad-output/implementation-artifacts/7-2-captura-de-renglones-serializado-cantidad-con-validaciones-minimas.md` (captura masiva + validación mínima + UX long-request)
- `gatic/app/Models/PendingTask.php` (duplicados en tarea)
- `gatic/app/Models/PendingTaskLine.php` (campos + `line_status`/`error_message`)
- `gatic/app/Enums/PendingTaskStatus.php`, `gatic/app/Enums/PendingTaskLineStatus.php` (estados)
- `gatic/app/Actions/PendingTasks/*` (captura/edición/bulk paste/MarkReady)
- `gatic/app/Actions/Movements/Assets/*` y `gatic/app/Actions/Movements/Products/RegisterProductQuantityMovement.php` (aplicar movimientos)
- `gatic/app/Livewire/PendingTasks/PendingTaskShow.php` + `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php` (UI actual)
- `gatic/resources/views/components/ui/long-request.blade.php` + `gatic/resources/js/ui/long-request.js` (NFR2 >3s + cancelar)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Admin/Editor,  
I want procesar una Tarea Pendiente por renglón y poder finalizar aplicando lo válido,  
so that errores no bloqueen todo el lote (FR28).

## Alcance (MVP)

Esta story implementa el **procesamiento y aplicación diferida** de una Tarea Pendiente (Epic 7), con finalización parcial.

Incluye:
- Modo “Procesar” para tareas en estado `ready` (y/o `partially_completed`).
- Procesamiento por renglón: edición puntual + validación “real” contra inventario/estado, y persistencia de `line_status` + `error_message`.
- Botón “Finalizar” que aplica **solo lo válido** y deja los errores sin bloquear el lote.
- Resumen final: aplicados vs errores + mensajes accionables por renglón.

No incluye (fuera de scope / otras stories):
- Locks/claim/heartbeat/TTL/override Admin (Stories 7.4 y 7.5).  
  **Pero** esta story debe dejar puntos de integración claros (status `processing`, heartbeat interval, UI read-only para terceros).

## Acceptance Criteria

### AC1 — Modo Procesar + estado por renglón (FR28)

**Given** una Tarea Pendiente en estado `ready` con renglones  
**When** el usuario entra a modo “Procesar”  
**Then** el sistema muestra estado por renglón con badges (Pendiente / Procesando / Aplicado / Error)  
**And** el usuario puede editar un renglón y re-validarlo  
**And** el sistema persiste `line_status` y `error_message` por renglón.

### AC2 — Validación real por renglón (previo a aplicar)

**Given** un renglón serializado (con `serial` y/o `asset_tag`)  
**When** el usuario lo marca como listo/validado (o se intenta aplicar)  
**Then** el sistema valida contra el inventario/estado actual (existencia + transiciones permitidas)  
**And** si falla, marca el renglón como `error` con mensaje accionable.

**Given** un renglón por cantidad (`quantity`)  
**When** el usuario lo marca como listo/validado (o se intenta aplicar)  
**Then** el sistema valida stock suficiente cuando aplique (salidas)  
**And** si falla, marca el renglón como `error` con mensaje accionable.

### AC3 — Finalización parcial (FR28)

**Given** una Tarea Pendiente con renglones válidos e inválidos  
**When** el usuario selecciona “Finalizar”  
**Then** el sistema aplica los renglones válidos (crea movimientos + actualiza inventario/estado)  
**And** deja los renglones con error marcados con mensaje accionable  
**And** muestra un resumen “aplicados vs errores”  
**And** no bloquea el lote por un solo error.

### AC4 — Duplicados en tarea y conflictos de inventario (FR28)

**Given** una Tarea Pendiente serializada con series duplicadas dentro de la tarea  
**When** el usuario selecciona “Finalizar”  
**Then** el sistema marca esos renglones duplicados como `error` (sin aplicar)  
**And** aplica los renglones válidos restantes de forma parcial.

## Tasks / Subtasks

- [x] Definir y documentar la matriz de aplicación `task.type` × `line_type` (AC: 1–4)
  - [x] Serializado: `assign`/`loan`/`return` (aplican a `assets` + `asset_movements`)
  - [x] Cantidad: `stock_in`/`stock_out`/`assign`/`loan`/`return`/`retirement` (aplican a `products.qty_total` + `product_quantity_movements`)
  - [x] Combinaciones no soportadas: marcar `error` con mensaje claro (no hacer "best guess" silencioso)
  - **Implementado en:** `gatic/app/Enums/PendingTaskType.php` (métodos `supportsSerialized()`, `supportsQuantity()`, `quantityDirection()`, `unsupportedLineTypeMessage()`)
- [x] Backend: Action "finalizar tarea" con aplicación parcial (AC: 3, 4)
  - [x] Bloquear re-aplicación: solo aplicar renglones `pending`/`processing` (nunca `applied`)
  - [x] Actualizar `line_status` a `applied` o `error` con `error_message`
  - [x] Actualizar `pending_tasks.status` a `completed` o `partially_completed`
  - [x] Transacciones + locks DB para evitar inconsistencias (NFR7)
  - **Implementado en:** `gatic/app/Actions/PendingTasks/FinalizePendingTask.php`
- [x] Backend: validación profunda por renglón (AC: 2)
  - [x] Serializado: buscar Asset por `product_id` + (`serial`/`asset_tag`) y validar transición según tipo de tarea
  - [x] Cantidad: validar `qty_total` inicializado y suficiente para salidas
  - [x] Duplicados serial/asset_tag dentro de la tarea: bloquear al aplicar (FR28; decisión de Story 7.1)
  - **Implementado en:** `gatic/app/Actions/PendingTasks/ValidatePendingTaskLine.php`
- [x] UI Livewire: modo Procesar en detalle de tarea (AC: 1–3)
  - [x] Acciones por renglón: editar, validar, limpiar error
  - [x] Botón Finalizar con confirmación y resumen
  - [x] UX: estados claros, mensajes accionables, no perder contexto
  - **Implementado en:** `gatic/app/Livewire/PendingTasks/PendingTaskShow.php` + `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php`
- [x] NFR2 (>3s): long request overlay en acciones pesadas (Finalizar) (NFR2)
  - [x] Usar `<x-ui.long-request />` ya presente; opcional: set `target` al método Livewire de finalización
  - **Implementado:** `<x-ui.long-request target="finalizeTask" />` en la vista
- [x] Tests (Feature): cobertura de happy path + parciales + errores (NFR7)
  - [x] Aplicación parcial: 1 renglón OK, 1 renglón Error → aplica solo 1
  - [x] Serializado: transición válida/inválida según estado del Asset
  - [x] Cantidad: stock insuficiente → error, sin afectar stock
  - [x] Duplicados en tarea → error (no aplica duplicados)
  - **Implementado en:** `gatic/tests/Feature/PendingTasks/FinalizePendingTaskTest.php`

## Dev Notes

### Contexto actual (ya existen Stories 7.1 y 7.2)

- Módulo base PendingTasks ya existe (crear tarea, agregar/editar/eliminar renglones en `draft`, marcar `ready`).
  - UI: `gatic/app/Livewire/PendingTasks/*` + `gatic/resources/views/livewire/pending-tasks/*`
  - Actions: `gatic/app/Actions/PendingTasks/*`
  - Estados existentes:
    - `pending_tasks.status`: `draft` → `ready` → (a implementar) `processing`/`completed`/`partially_completed`
    - `pending_task_lines.line_status`: `pending`/`processing`/`applied`/`error`
- Duplicados dentro de la tarea se **permiten** en captura (Story 7.2) pero se deben **bloquear al aplicar** (decisión tomada en Story 7.1).
- Ya existe UX para requests lentas + cancelar: `<x-ui.long-request />` (ver `gatic/resources/views/components/ui/long-request.blade.php` y `gatic/resources/js/ui/long-request.js`).

### Objetivo de esta story (7.3)

Convertir la Tarea Pendiente de un “carrito” de captura a un flujo robusto de **procesamiento + aplicación diferida**:
- Permitir que el usuario itere renglón por renglón, corrija datos y vea errores claros.
- Aplicar parcialmente lo válido, sin perder el trabajo por un error.

### Requisitos técnicos (DEV guardrails)

**1) No reinventar ruedas:**
- Para serializados, reutilizar Actions de movimientos ya existentes:
  - `gatic/app/Actions/Movements/Assets/AssignAssetToEmployee.php`
  - `gatic/app/Actions/Movements/Assets/LoanAssetToEmployee.php`
  - `gatic/app/Actions/Movements/Assets/ReturnLoanedAsset.php`
- Para cantidad, reutilizar:
  - `gatic/app/Actions/Movements/Products/RegisterProductQuantityMovement.php`

**2) Transaccionalidad / integridad (NFR7):**
- `Finalizar` NO puede dejar inventario “a medias” por renglón.
- Recomendación: aplicar por renglón con transacción + locks (`lockForUpdate`) en el recurso que se modifica (Asset/Product).
- El resultado por renglón debe persistir siempre (`applied` o `error`) con mensaje claro.

**3) Duplicados:**
- Duplicados serial/asset_tag dentro de la tarea: al aplicar, marcar `error` y **no ejecutar** el movimiento.
- Preferir error_message accionable: “Duplicado en la tarea: el identificador aparece en los renglones X, Y”.

**4) UX (Journey 2):**
- Estado por renglón + resumen final aplicados vs errores.
- Mantener “modo edición” por renglón y no perder el contexto (tabla).
- Preparar integración futura de locks (Story 7.4): UI debe poder volverse read-only si no hay lock.

**4.1) Soft-delete regression check (Lección Epic 6):**
- Al buscar/contar modelos con soft-delete (Asset/Product/Category/Brand/Location/Employee), NO incluir `deleted_at`.
- Agregar al menos 1 test de regresión: crear registros soft-deleted y verificar que **no** se consideran “existentes”/aplicables al finalizar.

**5) Errores inesperados + error_id (bible):**
- Si ocurre un fallo inesperado al aplicar, mostrar mensaje humano + `error_id` y conservar el estado previo (no romper la UI).

### Cumplimiento de arquitectura (no negociables)

- UI principal con Livewire 3 (route → componente). Controllers solo “bordes”.
- Casos de uso transaccionales en `app/Actions/*` (ideal: `app/Actions/PendingTasks/*` para orquestación).
- Respetar estructura por módulos:
  - `app/Models/PendingTask*.php`
  - `app/Livewire/PendingTasks/*`
  - `app/Actions/PendingTasks/*`

### Librerías / Frameworks (evitar implementaciones desactualizadas)

Versiones observadas en el repo (no actualizar por esta story):
- Laravel: `laravel/framework` v11.x (ver `gatic/composer.lock`)
- Livewire: `livewire/livewire` v3.x (ver `gatic/composer.lock`)
- Bootstrap: 5.2.3 (ver `gatic/package.json`)
- PHP objetivo del proyecto: 8.2+ (ver `gatic/composer.json`)

### Git Intelligence (contexto reciente)

Últimos commits relevantes:
- `e58c08d` feat(pending-tasks): captura serializado/cantidad + validaciones (Story 7.2)
- `548be79` feat(gate4): crear tarea pendiente y administrar renglones (Story 7.1)

Archivos que ya tocaron 7.1–7.2 (alto impacto para 7.3):
- `gatic/app/Livewire/PendingTasks/PendingTaskShow.php`
- `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php`
- `gatic/app/Actions/PendingTasks/*`
- `gatic/app/Models/PendingTask.php`
- `gatic/app/Enums/PendingTaskStatus.php`, `gatic/app/Enums/PendingTaskLineStatus.php`
- `gatic/tests/Feature/PendingTasks/*`

### Latest Tech Information (web research)

- Livewire 3 tuvo un advisory de seguridad (RCE) parchado en `livewire/livewire` v3.6.4; el repo ya usa una versión 3.7.x, pero **no bajar** de 3.6.4.
- PHP 8.4.x recibió releases recientes (ej. 8.4.16, 2025‑12‑18); el proyecto usa Sail runtime 8.4 y requiere PHP >= 8.2.
- Bootstrap 5.3.x tiene parches recientes (ej. 5.3.8, 2025‑07‑30); **no** actualizar en esta story (riesgo UI).

## Project Context Reference (must-read)

- `docsBmad/project-context.md`:
  - NFR2: si tarda >3s → loader/skeleton + mensaje + cancelar
  - NFR7: operaciones críticas atómicas (movimientos/estados)
  - Locks: timeout rolling 15m + TTL 3m + idle guard 2m + heartbeat 10s (Story 7.4)
- `project-context.md`:
  - Identificadores en inglés; copy/UI en español
  - Gate `inventory.manage` (server-side) para PendingTasks

## Story Completion Status

- Status: **done**
- Completion note: Code review issues fixed (concurrencia en finalización, error_id en UI) y tests validados en Docker (Sail/MySQL).

## Dev Agent Record

### Agent Model Used

Claude Opus 4.5 (Claude Code CLI)

### Debug Log References

- Larastan analysis: All files pass without errors

### Completion Notes List

1. Matriz de aplicación implementada en `PendingTaskType` enum con métodos helper
2. Action `FinalizePendingTask` orquesta la aplicación parcial con:
   - Transacciones por renglón para aislamiento
   - Detección de duplicados pre-aplicación
   - Reutilización de Actions existentes de movimientos
   - Never re-apply already applied lines
3. Action `ValidatePendingTaskLine` para validación dry-run individual
4. Action `ClearLineError` para limpiar errores y re-validar
5. UI Livewire actualizada con modo Procesar completo:
   - Badges de estado por renglón
   - Edición puntual en modal
   - Validación individual
   - Confirmación de finalización con resumen
   - Long request overlay para operaciones pesadas
6. Tests Feature cubren todos los casos de AC
7. Code review (AI): hardening de concurrencia al aplicar por renglón, UI con error_id y logging, tests ajustados y ejecutados.

### File List

**Nuevos archivos:**
- `gatic/app/Actions/PendingTasks/FinalizePendingTask.php`
- `gatic/app/Actions/PendingTasks/ValidatePendingTaskLine.php`
- `gatic/app/Actions/PendingTasks/ClearLineError.php`
- `gatic/tests/Feature/PendingTasks/FinalizePendingTaskTest.php`

**Archivos modificados:**
- `gatic/app/Enums/PendingTaskType.php` (matriz de aplicación)
- `gatic/app/Livewire/PendingTasks/PendingTaskShow.php` (modo Procesar)
- `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php` (UI modo Procesar)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (sync de estado de story)
