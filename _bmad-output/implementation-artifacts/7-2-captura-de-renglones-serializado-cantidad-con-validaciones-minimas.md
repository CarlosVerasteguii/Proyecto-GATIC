# Story 7.2: Captura de renglones (serializado / cantidad) con validaciones mínimas

Status: done

Story Key: `7-2-captura-de-renglones-serializado-cantidad-con-validaciones-minimas`  
Epic: `7` (Gate 4: Tareas Pendientes + locks de concurrencia)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/epics.md` (Epic 7 / Story 7.2; FR27)
- `_bmad-output/implementation-artifacts/prd.md` (FR27; NFR2, NFR3)
- `_bmad-output/implementation-artifacts/ux.md` (Journey 2; eficiencia en Tareas Pendientes; feedback/loading)
- `_bmad-output/implementation-artifacts/architecture.md` (Epic 7 mapping; `app/Actions/PendingTasks/*`; `app/Livewire/PendingTasks/*`)
- `docsBmad/project-context.md` (bible: stack; locks; reglas críticas)
- `project-context.md` (toolchain Windows + reglas críticas para agentes)
- `docsBmad/rbac.md` (gates; `inventory.manage`)
- `_bmad-output/implementation-artifacts/7-1-crear-tarea-pendiente-y-administrar-renglones.md` (patrones/guardrails ya establecidos)
- `gatic/app/Livewire/PendingTasks/PendingTaskShow.php` (punto de extensión para UI de captura)
- `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php` (UI actual de captura)
- `gatic/app/Actions/PendingTasks/AddLineToTask.php` (patrón transaccional + orden + duplicados)
- `gatic/app/Actions/PendingTasks/Concerns/ValidatesTaskLines.php` (validación mínima actual)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Admin/Editor,  
I want capturar renglones serializados (pegar series) o por cantidad,  
so that la carga rápida sea eficiente y con validación temprana (FR27).

## Alcance (MVP)

Esta story se enfoca en la **captura eficiente** de renglones dentro de una Tarea Pendiente (Epic 7), sin entrar aún a procesamiento/aplicación.

Incluye:
- Para renglones **Serializado**: entrada tipo “pegar series” (1 por línea) con validación mínima y feedback temprano.
- Para renglones **Cantidad**: validación mínima de cantidad (>0) y bloqueo de guardado cuando es inválida.
- Duplicados **dentro de la tarea**: permitidos (se resaltan), no bloquean el guardado.

No incluye (fuera de scope / otras stories):
- Validación “profunda” contra inventario (ej. serial ya existe en `assets`, disponibilidad de stock, conflictos de estado). Eso se aborda al procesar/finalizar (Story 7.3).
- Locks/claim/heartbeat/TTL/override Admin (Stories 7.4 y 7.5).
- Auditoría, adjuntos, papelera, error_id consultable (Epic 8).

## Acceptance Criteria

### AC1 — Serializado: pegar series + validación mínima (FR27)

**Given** un renglón de tipo Serializado  
**When** el usuario pega series (1 por línea)  
**Then** el sistema valida el formato mínimo (alfanum, longitud mínima acordada)  
**And** muestra contador y errores por línea si aplica  
**And** permite duplicados dentro de la tarea (los resalta) sin bloquear el guardado.

### AC2 — Cantidad: validación mínima (FR27)

**Given** un renglón de tipo Cantidad  
**When** el usuario ingresa una cantidad  
**Then** el sistema valida que sea entero > 0  
**And** no permite guardar cantidades inválidas.

## Tasks / Subtasks

- [x] UI: Captura “pegar series” en modal (AC: 1)
  - [x] En `pending-task-show.blade.php`, para producto serializado, agregar textarea “Series (1 por línea)”
  - [x] Mostrar contador + lista de validación por línea (OK / inválida / duplicada)
  - [x] Mantener Empleado (RPE) + Nota como obligatorios (regla adoption-first)
  - [x] Asegurar que duplicados solo advierten (no bloquean)
- [x] Livewire: Parse + feedback + guardado masivo (AC: 1)
  - [x] En `PendingTaskShow.php`, agregar propiedades para input crudo + preview + errores
  - [x] Implementar parsing/validación mínima reutilizando las reglas actuales (o espejo fiel) para feedback temprano
  - [x] En el submit, bloquear guardado si hay líneas inválidas; permitir si solo hay duplicados
- [x] Backend: Action para crear renglones serializados en batch (AC: 1)
  - [x] Crear `app/Actions/PendingTasks/*` (ej. `AddSerializedLinesToTask`) que reciba lista de series
  - [x] Validar: tarea existe y está `draft`; producto serializado; employee existe; note no vacía
  - [x] Insertar en transacción con lock en `pending_tasks` + `order` incremental sin colisiones
- [x] Cantidad: asegurar bloqueo de inválidos (AC: 2)
  - [x] Confirmar que `quantity` inválida no se guarda y muestra error accionable
- [x] Tests mínimos (AC: 1,2)
  - [x] Feature/Action: batch insert crea N renglones y conserva `order` secuencial
  - [x] Livewire/UI: pegar series con inválidas bloquea; duplicadas permite y resalta

## Dev Notes

### Contexto actual (ya existe Story 7.1)

- Ya existe el módulo base de **Tareas Pendientes** con creación de tarea, detalle y administración de renglones en estado `draft`.  
  Fuente principal: `_bmad-output/implementation-artifacts/7-1-crear-tarea-pendiente-y-administrar-renglones.md`.
- UI actual para agregar/editar renglón vive en: `gatic/app/Livewire/PendingTasks/PendingTaskShow.php` + `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php`.
- Persistencia actual:
  - Tabla `pending_task_lines` (ver migración `gatic/database/migrations/2026_01_18_000001_create_pending_task_lines_table.php`) con `line_type`, `serial`, `asset_tag`, `quantity`, `employee_id`, `note`, `order`.
  - Orden de renglones: `order` incremental (se calcula en transacción; ver `gatic/app/Actions/PendingTasks/AddLineToTask.php`).
- Validación mínima actual (reutilizable) está centralizada en `gatic/app/Actions/PendingTasks/Concerns/ValidatesTaskLines.php`.
- Duplicados actuales (serial/asset_tag) dentro de la tarea:
  - Se permiten.
  - Se detectan para **resaltar** en UI: `gatic/app/Models/PendingTask.php::getDuplicateIdentifiers()`.
- UX “long request”: ya existe `<x-ui.long-request />` en `pending-task-show.blade.php` (cumple NFR2 cuando haya requests lentas).

### Objetivo de esta story (7.2)

Hacer la captura de renglones **mucho más rápida** y con feedback temprano, especialmente para serializados:
- El usuario debe poder **pegar múltiples series** (1 por línea) en un solo paso.
- El sistema debe mostrar:
  - **contador** de series detectadas,
  - **errores por línea** (formato mínimo),
  - **duplicados** (dentro del input y/o contra la tarea) como advertencia visual, sin bloquear.

### Sugerencia de UX (mínimo viable y consistente con Bootstrap/Livewire)

- En el modal “Añadir renglón”, cuando el Producto sea serializado:
  - Mostrar un textarea “Series (1 por línea)”.
  - Permitir pegar 1 o muchas series (una sola línea también es válida).
  - Mostrar debajo una previsualización (lista compacta) con estado por línea: OK / inválida / duplicada.
- Cuando el Producto NO sea serializado:
  - Mantener el input numérico “Cantidad” (entero > 0).
- El Empleado (RPE) y Nota se mantienen **obligatorios** (regla adoption-first definida en Story 7.1).

### Project Structure Notes

- Respetar el mapeo de arquitectura: Epic 7 → `app/Models/PendingTask*.php`, `app/Livewire/PendingTasks/*`, `app/Actions/PendingTasks/*` (ver `_bmad-output/implementation-artifacts/architecture.md`).
- No duplicar lógica en Livewire: validaciones y creación masiva deben vivir en `app/Actions/PendingTasks/*` y reutilizar `ValidatesTaskLines`.
- Seguridad: siempre `Gate::authorize('inventory.manage')` en Livewire y middleware `can:inventory.manage` en rutas (ver `docsBmad/rbac.md`).

### Requisitos técnicos (DEV guardrails)

**Validación mínima (alineada a implementación existente):**
- Reusar `gatic/app/Actions/PendingTasks/Concerns/ValidatesTaskLines.php` para que las reglas sean consistentes entre UI y Actions.
- Para serial/asset_tag: solo `[a-zA-Z0-9\\-_]` y longitud mínima **3** (ya implementado).
- Para cantidad: entero `> 0` (ya implementado).

**Parsing de “pegar series” (obligatorio para AC1):**
- Normalizar saltos de línea (`\\r\\n`/`\\n`) y `trim()` por línea.
- Ignorar líneas vacías (no deben romper la UX).
- Mantener el número de línea original para reportar errores (“Línea 7: …”).
- Recomendar un límite razonable de líneas pegadas (ej. 200) para evitar requests excesivas; si se define, centralizarlo en `config/gatic.php` (sin “magic numbers”).

**Errores por línea (UX):**
- Mostrar una lista/tabla compacta con estado por línea: `OK` / `Inválida` (+ mensaje) / `Duplicada`.
- Si existe cualquier línea inválida, bloquear el guardado (para mantener consistencia y evitar datos basura).
- Duplicados NO bloquean el guardado (solo advertencia visual).

**Persistencia (bulk add recomendado):**
- Para serializados, cada serie pegada debe generar **un renglón** `pending_task_lines` con:
  - `line_type = serialized`, `serial = <valor>`, `asset_tag = null`, `quantity = null`,
  - `employee_id` y `note` aplican a todos los renglones creados en el batch,
  - `order` incremental sin colisiones (obligatorio: usar transacción + lock similar a `AddLineToTask`).
- Evitar N queries por serie: calcular `max(order)` una sola vez y hacer `insert`/creación en loop dentro de la misma transacción.

**Autorización y estado:**
- Solo se puede capturar/editar renglones si la tarea está en `draft` (ya existe en Story 7.1; mantener).
- No confiar en UI: validar server-side en Action/Livewire.

**NFR2 (long request):**
- Si el usuario pega muchas series, la operación puede tardar >3s; asegurar que el overlay `<x-ui.long-request />` cubra la acción de guardado (opcional: configurar `target` al método de Livewire que hace el bulk insert).

### Cumplimiento de arquitectura (no negociables)

- UI principal con Livewire 3 (route → componente). No introducir controllers salvo “bordes” (no se requiere en esta story).
- Casos de uso (crear masivo, validaciones, checks de estado) en `app/Actions/PendingTasks/*`.
- Mantener enums existentes (`PendingTaskLineType`, `PendingTaskStatus`, etc.) y no introducir strings sueltos.
- Mantener queries excluyendo soft-deleted donde aplique (Producto/Categoría). La UI actual ya filtra `deleted_at` en `PendingTaskShow::loadProducts()`; no reintroducir productos/categorías borrados.
- No inventar helpers globales: si se necesita utilitario de parsing, ubicarlo como método privado en Action o en `app/Support/*` (solo si realmente reusable).

### Librerías / Frameworks (evitar implementaciones desactualizadas)

Versiones observadas en el repo (no actualizar por esta story):
- Laravel: `laravel/framework` **v11.47.0** (ver `gatic/composer.lock`).
- Livewire: `livewire/livewire` **v3.7.3** (ver `gatic/composer.lock`).
- Bootstrap: **5.2.3** (ver `gatic/package.json`).
- PHP objetivo del proyecto: **8.2+** (ver `gatic/composer.json`).

Notas Livewire 3 relevantes para esta story:
- En Livewire v3, `wire:model` es **deferred** por defecto (no sincroniza al servidor hasta un request).  
  Para previsualización/validación mientras el usuario pega, usar `.live` (con debounce) o `.blur` según convenga.
- Para pruebas de componentes, usar helpers oficiales Livewire v3 (`Livewire::actingAs(...)->test(...)->set(...)->call(...)->assertSee(...)`).

### Testing (mínimo requerido)

- Tests deterministas y con `RefreshDatabase` (patrón existente en `tests/Feature/PendingTasks/*`).
- Cubrir al menos:
  1. **Action batch**: crea N renglones serializados, con `order` incremental y datos comunes (`employee_id`, `note`).
  2. **Validación por línea**: serial inválido (caracteres fuera de patrón o longitud <3) bloquea y reporta error accionable.
  3. **Duplicados**: permitir guardar duplicados (misma serie repetida), y el UI debe mostrar el badge de “duplicados” en el detalle (ya existe infraestructura de highlight).
  4. **Cantidad**: `quantity <= 0` no se guarda y muestra error.
  5. **Soft-delete regression**: si se consulta/filtra `Product`/`Category`, asegurar exclusión de `deleted_at` (crear registros soft-deleted y verificar que no aparecen/seleccionan).

### Learnings de Story 7.1 (aplicar aquí)

- **Orden sin colisiones**: ya se corrigió un edge case asignando `order` dentro de transacción con `lockForUpdate()` sobre `pending_tasks` (ver `AddLineToTask`). El bulk insert de esta story debe seguir el mismo patrón para no reintroducir colisiones.
- **Duplicados como warning**: el sistema resalta duplicados sin bloquear guardado (ver `PendingTask::getDuplicateIdentifiers()` + UI). No convertir esto en error.
- **UX long request**: `PendingTaskShow` ya incluye `<x-ui.long-request />`. Si se agrega un flujo que puede tardar (pegar muchas series), asegurarse de no “romper” ese overlay (y opcionalmente targetearlo al método de bulk).
- **Tests existentes**: hay cobertura de RBAC, UI básico y Actions para Story 7.1; extender esa suite, no crear un estilo nuevo de testing.

### Git intelligence (contexto reciente)

- Commit más reciente relevante: `548be79 feat(gate4): crear tarea pendiente y administrar renglones (Story 7.1)` — introdujo `PendingTask*`, Actions, Livewire y tests en módulo `PendingTasks`.
- En esta story, preferir cambios **incrementales** sobre ese módulo (sin tocar arquitectura global ni rutas fuera del scope).

### Latest tech info (para no implementar “como en Livewire v2”)

- Livewire v3 cambió el comportamiento por defecto de `wire:model`: ahora es **deferred** y no sincroniza al servidor en cada tecla.  
  Si se necesita feedback “live” mientras el usuario pega/edita el textarea, usar `wire:model.live` (tiene debounce) o una estrategia intermedia como `wire:model.blur` para reducir requests.
- Livewire ya anuncia v4 en su documentación pública; **este repo está en v3.7.3**, por lo que cualquier snippet debe ser compatible con v3.x (evitar APIs de v4).

### References

- Requisitos funcionales de Story 7.2: [Source: `_bmad-output/implementation-artifacts/epics.md#Epic 7: Tareas Pendientes + locks de concurrencia`]
- FR27 + NFR2/NFR3: [Source: `_bmad-output/implementation-artifacts/prd.md#Pending Tasks & Concurrency Locks`]
- Journey 2 + feedback/loading + LockBanner: [Source: `_bmad-output/implementation-artifacts/ux.md#Journey 2 - Editor (Soporte): Tarea Pendiente con lock (concurrencia)`]
- Arquitectura / estructura / mapping Epic 7: [Source: `_bmad-output/implementation-artifacts/architecture.md#Requirements to Structure Mapping`]
- Reglas críticas (bible): [Source: `docsBmad/project-context.md#Restricciones (no negociables)`]
- RBAC `inventory.manage`: [Source: `docsBmad/rbac.md#Gates (nombres y alcance)`]
- Implementación existente (Story 7.1): [Source: `_bmad-output/implementation-artifacts/7-1-crear-tarea-pendiente-y-administrar-renglones.md#Alcance (MVP)`]
- UI actual a extender: [Source: `gatic/app/Livewire/PendingTasks/PendingTaskShow.php`], [Source: `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php`]

## Story Completion Status

- Status: **done**
- Completion note: Ultimate context engine analysis completed - comprehensive developer guide created.

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

 - Auto-discovery: `_bmad-output/implementation-artifacts/sprint-status.yaml` → primera story en `ready-for-dev` fue `7-2-captura-de-renglones-serializado-cantidad-con-validaciones-minimas`.
 - Artefactos analizados: `_bmad-output/implementation-artifacts/epics.md`, `_bmad-output/implementation-artifacts/prd.md`, `_bmad-output/implementation-artifacts/ux.md`, `_bmad-output/implementation-artifacts/architecture.md`, `docsBmad/project-context.md`, `docsBmad/rbac.md`, y el código actual en `gatic/` (módulo PendingTasks).
 - Ejecución: `docker compose exec -T laravel.test php artisan test`, `docker compose exec -T laravel.test ./vendor/bin/pint --test`, `docker compose exec -T laravel.test ./vendor/bin/phpstan analyse --no-progress` (todos OK).

### Implementation Plan

- UI (Livewire): textarea de pegado + previsualización por línea (OK/Inválida/Duplicada), contador y bloqueo si hay inválidas.
- Backend: Action batch con transacción + `lockForUpdate()` sobre `pending_tasks` y `order` secuencial desde `max(order)`.
- Tests: cobertura para batch insert (order y duplicados) y Livewire (bloqueo por inválidas / duplicados permitidos).

### Completion Notes List

 - Ultimate context engine analysis completed - comprehensive developer guide created.
 - Se implementó pegado masivo de series con validación mínima y feedback temprano (AC1).
 - Se mantuvo cantidad con validación server-side (entero > 0) y se agregó test de bloqueo (AC2).
 - Se agregó Action `AddSerializedLinesToTask` con inserción en batch y orden secuencial.
 - Tests nuevos/extendidos para batch + Livewire; suite completa, Pint y PHPStan en verde.
 - Code review fixes: cantidad valida entero > 0 (sin truncar decimales), validación de series unificada (UI/Backend), duplicados contra tarea optimizados, mensaje de error más accionable, tests extra (límite y cantidad no entera) + README actualizado.

### File List

 - `_bmad-output/implementation-artifacts/7-2-captura-de-renglones-serializado-cantidad-con-validaciones-minimas.md`
 - `_bmad-output/implementation-artifacts/sprint-status.yaml`
 - `gatic/app/Actions/PendingTasks/AddSerializedLinesToTask.php`
 - `gatic/app/Livewire/PendingTasks/PendingTaskShow.php`
 - `gatic/config/gatic.php`
 - `gatic/resources/views/livewire/pending-tasks/pending-task-show.blade.php`
 - `gatic/tests/Feature/PendingTasks/PendingTaskActionsTest.php`
 - `gatic/tests/Feature/PendingTasks/PendingTaskBulkPasteTest.php`
 - `README.md`

### Change Log

- 2026-01-20: Implementación Story 7.2 (pegar series + validación temprana + batch insert + tests + pint/phpstan OK).
- 2026-01-21: Code review: fixes de validación (cantidad/series), optimización de duplicados, mejoras de mensajes y tests adicionales.
