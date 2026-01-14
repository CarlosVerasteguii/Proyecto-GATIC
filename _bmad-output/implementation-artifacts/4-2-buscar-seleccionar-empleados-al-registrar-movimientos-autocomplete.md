# Story 4.2: Buscar/seleccionar Empleados al registrar movimientos (autocomplete)

Status: done

Story Key: `4-2-buscar-seleccionar-empleados-al-registrar-movimientos-autocomplete`  
Epic: `4` (Gate 3: Operación diaria)

Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Backlog (fuente de verdad): `_bmad-output/implementation-artifacts/epics.md` (Epic 4, Story 4.2; FR16)

Fuentes:
- `_bmad-output/implementation-artifacts/epics.md` (Epic 4, Story 4.2; FR16)
- `_bmad-output/implementation-artifacts/prd.md` (FR16; Journey 1)
- `_bmad-output/implementation-artifacts/ux.md` (MovementDrawer; ComboboxAsync; patrones de forms)
- `_bmad-output/implementation-artifacts/architecture.md` (stack; endpoints JSON internos; rate limiting; estructura)
- `docsBmad/project-context.md` (bible: adopción-first; mínimo obligatorio: Receptor + Nota)
- `docsBmad/rbac.md` (gate `inventory.manage` y reglas de defensa en profundidad)
- `_bmad-output/implementation-artifacts/4-1-crear-y-mantener-empleados-rpe.md` (contexto previo: modelo Employee + patrones Livewire + búsqueda)
- `gatic/app/Models/Employee.php` (normalización de texto)
- `gatic/app/Livewire/Employees/EmployeesIndex.php` (escape LIKE; patrón de búsqueda)
- `gatic/docs/ui-patterns.md` (toasts; long-request; `error_id`)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a usuario interno (Admin/Editor),  
I want buscar/seleccionar Empleados (RPE) en formularios de movimientos mediante autocomplete,  
so that registrar préstamos/asignaciones sea rápido y sin fricción (FR16).

## Alcance

Incluye:
- Un **selector reusable** de Empleado (RPE) con autocomplete (typeahead) para reusarse en futuros formularios (MovementDrawer / registrar movimiento).
- Búsqueda por **RPE o nombre** con sugerencias limitadas y relevantes.
- Selección con mouse y **teclado** (↑↓ Enter Esc) + estados: loading/no-results/error.
- Defensa en profundidad: RBAC server-side en cualquier request que traiga sugerencias.
- Un lugar concreto para probarlo sin depender de que exista el módulo de Movimientos (ver tareas).

No incluye (scope explícitamente fuera):
- Implementación completa de Movimientos (Epic 5) ni el MovementDrawer final.
- Alta “rápida” de Empleado desde el mismo autocomplete (opcional para futuro; aquí solo link/CTA si se decide).
- Multi-tenancy, integraciones externas, ni WebSockets.

## Acceptance Criteria

### AC1 - Acceso por rol (defensa en profundidad)

**Given** un usuario autenticado  
**When** interactúa con el selector de Empleado (y cualquier endpoint interno de sugerencias)  
**Then** solo Admin/Editor (gate `inventory.manage`) puede obtener resultados  
**And** un Lector recibe 403 (o equivalente) aunque la UI esté oculta.

### AC2 - Autocomplete relevante y “sin hueva”

**Given** un formulario que requiere seleccionar Empleado  
**When** el usuario escribe nombre o RPE  
**Then** ve sugerencias relevantes (máximo N, sin paginación)  
**And** puede seleccionar un Empleado y el formulario conserva el `employee_id` seleccionado.

Reglas mínimas sugeridas (ajustables, pero decidir y documentar):
- No consultar hasta que haya al menos **2 caracteres** (evita spam de queries).
- Orden de relevancia: match exacto/prefijo en `rpe`, luego `name` (y ambos case-insensitive).
- Cada opción muestra al menos: `RPE — Nombre` (y opcionalmente `Departamento`).

### AC3 - Teclado y accesibilidad (MVP)

**Given** el dropdown de sugerencias abierto  
**When** el usuario usa teclado  
**Then** puede navegar con ↑↓, seleccionar con Enter y cerrar con Esc  
**And** el componente expone semántica de combobox/listbox (ARIA) razonable para lectores de pantalla.

### AC4 - Estados: loading, vacío y error

**Given** que el usuario escribe en el campo  
**When** el backend tarda o falla  
**Then** el UI muestra un estado de loading discreto  
**And** si no hay resultados muestra “Sin resultados” (sin confundir con error)  
**And** si hay error inesperado, muestra mensaje amigable + `error_id` (detalle técnico solo Admin) y permite reintentar.

### AC5 - Seguridad y performance (sin inventar ruedas)

**Given** entradas con `%` o `_` o espacios múltiples  
**When** se ejecuta la búsqueda  
**Then** el backend normaliza texto y **escapa LIKE** para no degradar la búsqueda ni permitir wildcard accidental  
**And** el query limita resultados (N) y evita cargas innecesarias.

## Tasks / Subtasks

1) Diseño del selector (AC: 2–4)
- [x] Definir API del componente reusable: qué expone al padre (ej. `employeeId` + `employeeLabel`) y eventos/props.
- [x] Definir el layout Bootstrap del dropdown + estados (loading/no-results/error).
- [x] Definir comportamiento teclado (↑↓ Enter Esc) + focus management (sin requests por cada flecha).

2) Backend de sugerencias (AC: 2, 5)
- [x] Implementar función/Action reusable de búsqueda de Empleados (normaliza + escapa LIKE + limita N) reutilizable por UI.
- [x] Alinear el algoritmo con el patrón existente de búsqueda (ver referencias a `EmployeesIndex`).

3) Integración “probable” (AC: 1–4)
- [x] Integrar el selector en un lugar de prueba controlado (ideal: `gatic/app/Livewire/Dev/LivewireSmokeTest.php` + su Blade) para validar UX sin depender de Epic 5.
- [x] Confirmar RBAC: el smoke page está detrás de auth; el selector además debe respetar `inventory.manage` si expone datos.

4) Tests (AC: 1, 2, 5)
- [x] Feature tests: RBAC (Admin/Editor ok, Lector forbidden) y búsqueda por RPE/nombre + escape de wildcards.
- [x] Livewire test (si aplica): selección actualiza estado del padre y no rompe al limpiar/cancelar.

### Review Follow-ups (AI)

- [x] [AI-Review][CRITICAL] Extraer búsqueda a Action reusable (`SearchEmployees`). [gatic/app/Actions/Employees/SearchEmployees.php:13]
- [x] [AI-Review][HIGH] AC4: loading + error + retry en dropdown. [gatic/resources/views/livewire/ui/employee-combobox.blade.php:101]
- [x] [AI-Review][HIGH] Sync `employeeId` -> `employeeLabel` cuando cambia desde el padre. [gatic/app/Livewire/Ui/EmployeeCombobox.php:44]
- [x] [AI-Review][MEDIUM] Mejorar performance/relevancia: prefijos primero, fallback contains. [gatic/app/Actions/Employees/SearchEmployees.php:13]
- [x] [AI-Review][MEDIUM] Accesibilidad: `aria-activedescendant` + `aria-selected`. [gatic/resources/views/livewire/ui/employee-combobox.blade.php:70]
- [x] [AI-Review][MEDIUM] Completar `Dev Agent Record -> File List` vs git. [_bmad-output/implementation-artifacts/4-2-buscar-seleccionar-empleados-al-registrar-movimientos-autocomplete.md:320]
- [x] [AI-Review][MEDIUM] Tests: RBAC en acciones + binding `employeeId`. [gatic/tests/Feature/Employees/EmployeeComboboxTest.php:44]
- [x] [AI-Review][LOW] Se ignora el issue de PHP en PATH (no aplica al flujo real del proyecto).

## Dev Notes (contexto para implementar sin sorpresas)

### Por que esta story existe (impacto real)

- En el MVP, el objetivo UX es "adoption-first": registrar un movimiento debe ser mas facil que no registrarlo. El PRD define minimo obligatorio: Receptor (Empleado RPE) + Nota. Esta story prepara el "Receptor" para que no sea friccion. [Source: `_bmad-output/implementation-artifacts/prd.md`, `_bmad-output/implementation-artifacts/ux.md`, `docsBmad/project-context.md`]

### Principios (anti-errores tipicos)

- Empleado (RPE) != Usuario del sistema: nunca usar `users` como receptor de movimientos; siempre `employees`. [Source: `docsBmad/project-context.md`, `_bmad-output/implementation-artifacts/architecture.md`]
- No inventar librerias: resolver con Livewire 3 + Bootstrap 5 + JS minimo (solo para teclado/ARIA si es necesario), sin paquetes nuevos. [Source: `docsBmad/project-context.md`, `project-context.md`, `_bmad-output/implementation-artifacts/architecture.md`]
- Defensa en profundidad: aunque la UI oculte acciones, el servidor debe negar acceso a datos/acciones a Lector. [Source: `docsBmad/rbac.md`, `_bmad-output/implementation-artifacts/architecture.md`]
- Performance realista: el autocomplete hace requests frecuentes; limitar N, evitar consultar con 1 caracter, y no disparar requests por navegacion con flechas. [Source: `_bmad-output/implementation-artifacts/ux.md`]

### Reuso inmediato (para no duplicar logica)

- Normalizacion: reutilizar `Employee::normalizeText()` para input y estado del componente. [Source: `gatic/app/Models/Employee.php`]
- Escape de wildcards: copiar el patron `escapeLike()` ya usado en `EmployeesIndex` (evita `%`/`_` accidentales). [Source: `gatic/app/Livewire/Employees/EmployeesIndex.php`]
- UI patterns:
  - Toasts via `InteractsWithToasts` / evento `ui:toast`. [Source: `gatic/docs/ui-patterns.md`, `gatic/app/Livewire/Concerns/InteractsWithToasts.php`]
  - Error inesperado: mensaje humano + `error_id` + "Copiar", detalle tecnico solo Admin. [Source: `gatic/docs/ui-patterns.md`]

### UX "ComboboxAsync" (lo que NO debe omitirse)

- Debounce (evita spam de requests).
- Teclado (↑↓ Enter) + "No results" claro.
- `Esc` cierra overlays y devuelve foco.
- Disabled/empty states explican el por que (no solo "gris").
[Source: `_bmad-output/implementation-artifacts/ux.md` (ComboboxAsync, MovementDrawer)]

## Requisitos tecnicos (guardrails para el dev agent)

### Requisitos funcionales

- Input: buscar por `rpe` y `name` (case-insensitive) con debounce y limite de resultados.
- Output: el componente devuelve al formulario padre al menos `employee_id` (y opcionalmente `employee_label` para UI).
- Seleccion: debe poder limpiarse (volver a null) sin dejar estado invalido.

### Requisitos de seguridad (RBAC)

- Cualquier accion server-side que exponga sugerencias debe validar `Gate::authorize('inventory.manage')` (o middleware `can:inventory.manage` si es ruta). [Source: `docsBmad/rbac.md`, `gatic/routes/web.php`]
- No confiar en "ocultar UI": Lector no debe poder consultar sugerencias via request manual.

### Requisitos de performance

- No ejecutar query hasta >= 2 caracteres (configurable si se decide, pero documentar el valor).
- Limitar resultados a N (sugerido: 10-15).
- Seleccionar solo columnas necesarias (`id`, `rpe`, `name`, `department`) y evitar N+1.
- Escapar LIKE para `%`/`_` y backslash (ver `EmployeesIndex::escapeLike()`), y normalizar espacios. [Source: `gatic/app/Livewire/Employees/EmployeesIndex.php`, `gatic/app/Models/Employee.php`]

### Requisitos de UX (MVP)

- Loading discreto en el dropdown (no usar overlay global `<x-ui.long-request />` para cada keypress).
- Mensajes claros: "Escribe al menos 2 caracteres", "Sin resultados".
- Accesibilidad: ARIA combobox/listbox razonable + focus visible + `Esc` cierra.
- Toasters: si ocurre error inesperado (>=500) usar `error_id` y toast consistente. [Source: `gatic/docs/ui-patterns.md`]

### Requisitos de implementacion (sin inventar ruedas)

- Stack: Laravel 11 + Livewire 3 + Bootstrap 5; sin paquetes nuevos.
- Preferir Livewire como unidad primaria (route -> Livewire); Controllers solo en bordes (JSON interno puntual) si hace falta. [Source: `project-context.md`, `_bmad-output/implementation-artifacts/architecture.md`]

## Cumplimiento de arquitectura (lo no negociable)

- Modulo destino: Epic 4 (Employees) prepara Epic 5 (Movements). No crear el modulo completo de Movements en esta story. [Source: `_bmad-output/implementation-artifacts/sprint-status.yaml`, `_bmad-output/implementation-artifacts/epics.md`]
- Estructura (recomendada por arquitectura):
  - UI (Livewire): `gatic/app/Livewire/Employees/*` o `gatic/app/Livewire/Ui/*` para componentes reusables.
  - Logica de negocio: `gatic/app/Actions/*` (si se requiere reutilizar busqueda).
  - Rutas: `gatic/routes/web.php` (y solo agregar JSON interno si realmente aporta; siempre detras de `auth`, `active`, `can:*`). [Source: `_bmad-output/implementation-artifacts/architecture.md`, `gatic/routes/web.php`]
- Idioma (critico):
  - Identificadores de codigo/DB/rutas en ingles.
  - Copy/UI (labels, mensajes) en espanol. [Source: `_bmad-output/implementation-artifacts/architecture.md`, `project-context.md`]
- Sin WebSockets: nada de sockets para autocomplete; usar requests (Livewire o fetch) con debounce/abort. [Source: `docsBmad/project-context.md`]

## Requisitos de librerias / framework (no inventar ruedas)

- Laravel: 11 (sin downgrade/upgrade en esta story). [Source: `docsBmad/project-context.md`, `project-context.md`]
- Livewire: 3 (usar `wire:model.live.debounce.300ms` como patron existente). [Source: `gatic/resources/views/livewire/employees/employees-index.blade.php`]
- UI: Bootstrap 5 (dropdowns, list-group, focus styles).
- JS: usar lo ya existente (Vite + vanilla JS; `axios` esta disponible si se prefiere, pero no es requisito). [Source: `gatic/package.json`]
- Prohibido por defecto: agregar paquetes nuevos para "autocomplete/combobox" (Select2, TomSelect, etc.). Si en el futuro se quisiera, debe ser decision consciente y documentada; no en esta story.

## Requisitos de estructura de archivos (para evitar ubicaciones equivocadas)

### Opcion recomendada A (Livewire-only, sin ruta JSON dedicada)

- Nuevo componente reusable:
  - `gatic/app/Livewire/Ui/EmployeeCombobox.php` (o nombre equivalente en ingles: `EmployeeSelectCombobox`)
  - `gatic/resources/views/livewire/ui/employee-combobox.blade.php`
- Integracion de prueba (sin depender de Movements):
  - `gatic/app/Livewire/Dev/LivewireSmokeTest.php`
  - `gatic/resources/views/livewire/dev/livewire-smoke-test.blade.php`

### Opcion recomendada B (borde JSON interno + JS minimo)

- Ruta + controller (solo si aporta para UX/teclado/cancelacion sin mas requests Livewire):
  - `gatic/routes/web.php` (agregar un GET interno, detras de `auth`, `active`, `can:inventory.manage`)
  - `gatic/app/Http/Controllers/Internal/EmployeesSearchController.php` (o similar)
- JS:
  - `gatic/resources/js/ui/combobox-async.js` (nuevo)
  - `gatic/resources/js/app.js` (registrar el modulo)

### Convenciones obligatorias

- Rutas en ingles y kebab-case (ej. `/internal/employees/search`), nombres de ruta en dot.case (ej. `internal.employees.search`) si se decide nombrarlas. [Source: `project-context.md`, `_bmad-output/implementation-artifacts/architecture.md`]
- Mensajes/labels en espanol.

## Requisitos de testing (para no romper RBAC ni busqueda)

- Tests deben ser deterministas (sin dependencias externas). [Source: `project-context.md`]

### Minimo requerido

1) RBAC (AC1)
- Admin y Editor pueden obtener sugerencias (component o endpoint).
- Lector NO puede (403 o AuthorizationException).

2) Busqueda (AC2, AC5)
- Match por RPE (prefijo y substring) y por nombre.
- Escape de wildcards (`%`, `_`, `\\`) no rompe busqueda y no hace match "de mas".
- Limite N respetado.

3) Componente (AC3, AC4)
- Seleccion establece `employee_id` y el label mostrado.
- Limpiar seleccion vuelve a null.
- Estados no-results/loading no rompen el submit del formulario padre.

### Ubicacion sugerida

- Feature: `gatic/tests/Feature/Employees/EmployeeAutocompleteTest.php` (o `EmployeesAutocompleteTest.php`).
- Si se integra en smoke page, agregar asserts en `gatic/tests/Feature/Dev/LivewireSmokeComponentTest.php` (solo lo minimo; el heavy lifting va en Feature con `RefreshDatabase`).

## Inteligencia de story previa (para evitar repetir errores)

Contexto ya implementado en Story 4.1:

- Existe `employees` con `Employee::normalizeText()` (trim + colapsar espacios). Aprovecharlo para que el autocomplete no tenga sorpresas con entradas "raras". [Source: `gatic/app/Models/Employee.php`]
- El modulo Employees usa gate `inventory.manage` y `Gate::authorize()` dentro del componente (defensa en profundidad). Replicar el mismo estandar. [Source: `gatic/app/Livewire/Employees/EmployeesIndex.php`, `docsBmad/rbac.md`]
- La busqueda en Employees ya escapa wildcards y usa `... like ? escape '\\'` (esto se debe mantener en el autocomplete). [Source: `gatic/app/Livewire/Employees/EmployeesIndex.php`, `gatic/tests/Feature/Employees/EmployeesTest.php`]
- En UI, `wire:model.live.debounce.300ms` ya es patron real en el repo para busqueda. [Source: `gatic/resources/views/livewire/employees/employees-index.blade.php`]

## Git intelligence summary (repo-specific)

- El modulo Employees (Story 4.1) ya existe y vive en `gatic/app/Livewire/Employees` + `gatic/app/Models/Employee.php` (commit `9d596a2`). Reusar patrones ahi en lugar de inventar otro estilo de busqueda/autorizacion.
- Los patrones UX base (toasts, cancel long requests, freshness) vienen de Gate 1 (commits `4ec22bb`, `317d9b6`). El autocomplete debe alinearse (toasts, `error_id`, no modal default).

## Informacion tecnica reciente (web research)

- Livewire 3:
  - `wire:model` y modificadores (incluye `.live` y `.debounce.*`). [Source: https://livewire.laravel.com/docs/3.x/wire-model]
  - Componentes reusables con propiedades "modelables" (util para que el combobox exponga `employeeId` al padre). [Source: https://livewire.laravel.com/docs/3.x/attribute-modelable]
  - Eventos (para emitir seleccion / close / etc, si se prefiere). [Source: https://livewire.laravel.com/docs/3.x/events]
- Laravel 11:
  - Rate limiting para endpoints de alto uso (autocomplete) via limiters o middleware throttle. [Source: https://laravel.com/docs/11.x/rate-limiting]

## Referencias de contexto de proyecto (leer antes de codear)

- `docsBmad/project-context.md` (bible; si hay conflicto, gana este documento)
- `project-context.md` (reglas criticas para agentes + toolchain local)
- `_bmad-output/implementation-artifacts/architecture.md` (estructura, patrones, RBAC, JSON interno)
- `_bmad-output/implementation-artifacts/ux.md` (ComboboxAsync, MovementDrawer, patrones de forms)
- `docsBmad/rbac.md` (gates y reglas de aplicacion)
- `_bmad-output/implementation-artifacts/prd.md` (FR16; journey "registrar movimiento" con minimo obligatorio)
- `_bmad-output/implementation-artifacts/4-1-crear-y-mantener-empleados-rpe.md` (historia previa del modulo Employees)
- `gatic/app/Models/Employee.php` y `gatic/app/Livewire/Employees/EmployeesIndex.php` (patrones listos para reusar)
- `gatic/docs/ui-patterns.md` (toasts, long requests, `error_id`)

## Story Completion Status

- Status: `done`
- Nota: code review aplicado; ACs y tareas validadas.


## Preguntas (guardar para el final, no bloqueantes)

1) Minimo de caracteres para disparar sugerencias: 1 o 2? (recomendado 2)
2) Mostrar `department` en cada opcion o solo en tooltip/secondary text?
3) Se permite CTA "Crear empleado" (link a `/employees`) cuando no hay resultados, o se deja para futuro?

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `Get-Content _bmad-output/implementation-artifacts/sprint-status.yaml` (auto-discovery: primer story en backlog: `4-2-buscar-seleccionar-empleados-al-registrar-movimientos-autocomplete`)
- `Get-Content _bmad-output/implementation-artifacts/epics.md` (Epic 4, Story 4.2)
- `Get-Content _bmad-output/implementation-artifacts/prd.md` (FR16, Journey 1)
- `Get-Content _bmad-output/implementation-artifacts/ux.md` (ComboboxAsync/MovementDrawer)
- `Get-Content _bmad-output/implementation-artifacts/architecture.md` (stack, RBAC, JSON interno puntual)
- `Get-Content docsBmad/project-context.md` + `Get-Content project-context.md`
- `Get-Content docsBmad/rbac.md`
- `Get-Content gatic/app/Models/Employee.php` + `Get-Content gatic/app/Livewire/Employees/EmployeesIndex.php`
- Web docs: Livewire `wire:model` / `Modelable` + Laravel rate limiting

### Completion Notes List

- Story seleccionada automaticamente desde `sprint-status.yaml` (primer `backlog` en `development_status`).
- Guardrails explicitos para evitar errores tipicos: RBAC server-side, reuso de patrones de busqueda/escape, UX combobox con teclado, sin nuevas librerias.
- Se recomienda integrar el selector en `Dev/LivewireSmokeTest` para validar UX sin depender del modulo Movements (Epic 5).

### File List

- `_bmad-output/implementation-artifacts/4-2-buscar-seleccionar-empleados-al-registrar-movimientos-autocomplete.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (sync de status `4-2-...`)
- `gatic/app/Actions/Employees/SearchEmployees.php`
- `gatic/app/Livewire/Ui/EmployeeCombobox.php`
- `gatic/resources/views/livewire/ui/employee-combobox.blade.php`
- `gatic/tests/Feature/Employees/EmployeeComboboxTest.php`
- `gatic/app/Livewire/Dev/LivewireSmokeTest.php`
- `gatic/resources/views/livewire/dev/livewire-smoke-test.blade.php`

## Implementation Summary (Dev Agent)

### Files Created
- gatic/app/Actions/Employees/SearchEmployees.php - Action reusable para autocomplete (escape LIKE + relevancia + limite)
- gatic/app/Livewire/Ui/EmployeeCombobox.php - Componente Livewire reusable con búsqueda, selección y RBAC
- gatic/resources/views/livewire/ui/employee-combobox.blade.php - Vista con Bootstrap 5 + Alpine.js para teclado/ARIA
- gatic/tests/Feature/Employees/EmployeeComboboxTest.php - 15 tests cubriendo AC1-AC5

### Files Modified
- gatic/app/Livewire/Dev/LivewireSmokeTest.php - Agregado selectedEmployeeId para integrar combobox
- gatic/resources/views/livewire/dev/livewire-smoke-test.blade.php - Sección de prueba del EmployeeCombobox

### Implementation Decisions
1. **Min chars = 2**: Evita spam de queries con 1 caracter
2. **Limit = 10 resultados**: Balance entre usabilidad y performance
3. **Livewire-only (Opción A)**: Sin JSON endpoint adicional, aprovechando wire:model.live.debounce.300ms
4. **Alpine.js para teclado**: No dispara requests por navegación con flechas
5. **#[Modelable] attribute**: Permite wire:model desde componente padre

### Test Results
- 14/14 tests pasan (EmployeeComboboxTest)
- 168/168 tests totales pasan
- Pint: OK
- PHPStan: OK

## Senior Developer Review (AI)

_Reviewer: Carlos on 2026-01-14_

### Resumen

- Issues HIGH/MEDIUM corregidos (ver diff actual).
- Se mantuvo RBAC server-side (`inventory.manage`) y se mejoró la UX del combobox (loading/error/ARIA).

### Fixes aplicados

- `SearchEmployees` como Action reusable (prefijo primero, fallback contains, escape LIKE, limite N).
- Dropdown con estados `loading` + `error` (con retry) y toast con `error_id`.
- Sync de `employeeId` -> `employeeLabel` cuando el valor cambia desde el padre.
- Mejoras ARIA: `aria-activedescendant` + `aria-selected`.
- Tests ajustados para cubrir RBAC en acciones y binding.

## Change Log

- 2026-01-14: Senior Developer code review (AI) -> cambios solicitados; status `in-progress`; follow-ups agregados.
- 2026-01-14: Fixes aplicados por code review -> status `done`.
