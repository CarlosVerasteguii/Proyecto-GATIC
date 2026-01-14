# Story 4.3: Ficha de Empleado (detalle) y activos asociados (si existen)

Status: done

Story Key: `4-3-ficha-de-empleado-detalle-y-activos-asociados-si-existen`  
Epic: `4` (Gate 3: Operación diaria)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Backlog (fuente de verdad): `_bmad-output/implementation-artifacts/epics.md` (Epic 4, Story 4.3; FR15)

Fuentes:
- `_bmad-output/implementation-artifacts/epics.md` (Epic 4, Story 4.3)
- `_bmad-output/implementation-artifacts/architecture.md` (estructura + guardrails + mapeo Epic 4/5)
- `docsBmad/project-context.md` (bible: reglas no negociables + stack)
- `docsBmad/rbac.md` (gates y defensa en profundidad)
- `_bmad-output/implementation-artifacts/ux.md` (principios UX: estados vacíos, performance, densidad desktop)
- `_bmad-output/implementation-artifacts/4-1-crear-y-mantener-empleados-rpe.md` (módulo Employees existente)
- `_bmad-output/implementation-artifacts/4-2-buscar-seleccionar-empleados-al-registrar-movimientos-autocomplete.md` (patrones de Employee search/normalize)
- `gatic/app/Models/Employee.php` (campos + normalización)
- `gatic/app/Livewire/Employees/EmployeesIndex.php` (RBAC + estilo de componentes Livewire)
- `gatic/docs/ui-patterns.md` (toasts, long-request, polling, `error_id`)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a usuario interno (Admin/Editor),  
I want ver la ficha de un Empleado y, cuando existan, sus activos asignados/prestados,  
so that pueda responder "¿qué tiene esta persona?" (FR15).

## Alcance

Incluye:
- Vista de detalle (“ficha”) de Empleado con información mínima para soporte: `RPE`, `Nombre`, `Departamento`, `Puesto`.
- Navegación desde el listado de Empleados a la ficha.
- Secciones visibles: “Activos asignados” y “Activos prestados”.
- Estados vacíos útiles (cuando no existan asociaciones todavía).
- RBAC server-side (defensa en profundidad).

No incluye (explícitamente fuera de esta historia):
- Modelado/registro de movimientos o tenencias reales (Epic 5).
- Reglas de transición de estados para activos (Epic 5).
- Dashboard/métricas (Epic 5).

## Acceptance Criteria (BDD)

### AC1 - Acceso por rol (defensa en profundidad)

**Given** un usuario autenticado con rol Admin o Editor  
**When** navega a la ficha de un Empleado  
**Then** el servidor permite el acceso y renderiza la vista

**Given** un usuario autenticado con rol Lector  
**When** intenta acceder a la ficha (URL directa) o disparar acciones Livewire del componente  
**Then** el servidor bloquea la operación (403 o equivalente)

### AC2 - Ficha con información mínima y consistente

**Given** un Empleado existente  
**When** el usuario abre la ficha  
**Then** ve al menos: `Nombre`, `RPE`, `Departamento`, `Puesto`  
**And** la UI usa copy en español y mantiene identificadores/rutas en inglés

### AC3 - Secciones de activos (con estado vacío útil)

**Given** que todavía no existen asociaciones de activos al Empleado (antes de Epic 5)  
**When** el usuario abre la ficha  
**Then** ve secciones “Activos asignados” y “Activos prestados”  
**And** cada sección muestra un estado vacío claro (0 elementos) y una nota “Se habilita con Movimientos (Epic 5)”

### AC4 - Comportamiento ante datos inválidos

**Given** un `employee_id` inexistente  
**When** el usuario intenta abrir la ficha  
**Then** el sistema responde 404 (no 500)

## Tasks / Subtasks

1) Ruta + componente (AC: 1, 2, 4)
- [x] Agregar ruta `employees.show` dentro del grupo `/employees` y mantener middleware `auth`, `active`, `can:inventory.manage`.  
- [x] Crear componente Livewire `App\Livewire\Employees\EmployeeShow` (o equivalente) con `#[Layout('layouts.app')]` y `Gate::authorize('inventory.manage')`.
- [x] Cargar `Employee` por id (validar `ctype_digit` para parámetros string, patrón similar a `Inventory\\Assets\\AssetShow`).

2) Vista (AC: 2, 3)
- [x] Crear vista Blade `resources/views/livewire/employees/employee-show.blade.php` con Bootstrap 5.
- [x] UI: header con “Empleado: {RPE} — {Nombre}” + botón “Volver” a `employees.index`.
- [x] Renderizar tarjetas/secciones: “Datos del empleado”, “Activos asignados”, “Activos prestados”.
- [x] Estados vacíos: copy corto + sin “spam” de toasts.

3) Navegación desde listado (AC: 2)
- [x] En `EmployeesIndex` agregar acción “Ver ficha” por fila (botón/link) que navegue a `employees.show`.

4) Tests (AC: 1, 4)
- [x] Feature test: Admin/Editor pueden ver ficha; Lector recibe 403.
- [x] Feature test: 404 cuando el empleado no existe.

## Dev Notes (contexto para implementar sin sorpresas)

### Decision Log (reglas confirmadas)

- Stack fijo: Laravel 11 + Livewire 3 + Bootstrap 5. No introducir librerías nuevas para esta story. [Source: `docsBmad/project-context.md`]
- Idioma: rutas/identificadores en inglés; copy/UI en español. [Source: `docsBmad/project-context.md`, `project-context.md`]
- Autorización: siempre server-side (middleware `can:` + `Gate::authorize(...)` dentro del componente). [Source: `docsBmad/rbac.md`, `gatic/app/Livewire/Employees/EmployeesIndex.php`]
- “Empleado (RPE) != Usuario del sistema”. Esta ficha es del dominio `employees`, no de `users`. [Source: `docsBmad/project-context.md`]

### Reuso (NO reinventar ruedas)

- Patrón de componente Livewire “route -> componente”: seguir estilo de `EmployeesIndex` y `Inventory\\Assets\\AssetShow`. [Source: `gatic/app/Livewire/Employees/EmployeesIndex.php`, `gatic/app/Livewire/Inventory/Assets/AssetShow.php`]
- UI/UX: Bootstrap 5 + estados vacíos útiles. [Source: `_bmad-output/implementation-artifacts/ux.md`]
- Manejo de errores: si aparece un error inesperado, usar el patrón de `error_id` (toast/alert). [Source: `gatic/docs/ui-patterns.md`]

## Requisitos técnicos (guardrails para el dev agent)

### RBAC

- Requerido: `inventory.manage` para ver la ficha (consistente con el módulo actual de empleados). [Source: `gatic/routes/web.php`, `docsBmad/rbac.md`]
- Requerido: `Gate::authorize('inventory.manage')` en `mount()` (o `render()`) del componente. [Source: `gatic/app/Livewire/Employees/EmployeesIndex.php`]

### Datos / Modelo

- Usar `Employee` existente y sus campos: `rpe`, `name`, `department`, `job_title`. [Source: `gatic/app/Models/Employee.php`]
- Esta story NO crea relaciones de activos con empleados (eso vive en Epic 5). Las secciones de activos deben mostrarse vacías. [Source: `_bmad-output/implementation-artifacts/epics.md` (Epic 4 Story 4.3; Epic 5)]

### UX

- Desktop-first, denso y claro; estados vacíos informativos. [Source: `_bmad-output/implementation-artifacts/ux.md`]
- Evitar “magic numbers”: si se agrega paginación/listas futuras, usar `config('gatic.ui.*')` (patrón ya usado en `EmployeesIndex`). [Source: `gatic/app/Livewire/Employees/EmployeesIndex.php`, `_bmad-output/implementation-artifacts/architecture.md`]

## Architecture Compliance (no romper patrones)

- Mantener el módulo en `app/Livewire/Employees/*` y vistas en `resources/views/livewire/employees/*`. [Source: `_bmad-output/implementation-artifacts/architecture.md`]
- No Controllers para esta UI; preferir Livewire (controllers solo en “bordes” como descargas/JSON puntual). [Source: `docsBmad/project-context.md`]
- No WebSockets: no aplica aquí; no introducir polling innecesario en la ficha. [Source: `docsBmad/project-context.md`]

## Library / Framework Requirements

- Laravel 11: rutas `whereNumber(...)` y middleware `can:` como en el repo. [Source: `gatic/routes/web.php`]
- Livewire 3: componentes con `#[Layout('layouts.app')]` como estándar del repo. [Source: `gatic/app/Livewire/Employees/EmployeesIndex.php`]
- Bootstrap 5: estructura UI (cards/tables/buttons) consistente con el módulo de empleados. [Source: `gatic/resources/views/livewire/employees/employees-index.blade.php`]

## File Structure Requirements (esperado en implementación)

- `gatic/routes/web.php` (agregar `employees.show`)
- `gatic/app/Livewire/Employees/EmployeeShow.php` (nuevo)
- `gatic/resources/views/livewire/employees/employee-show.blade.php` (nuevo)
- `gatic/resources/views/livewire/employees/employees-index.blade.php` (agregar link/botón “Ver ficha”)
- `gatic/tests/Feature/Employees/EmployeeShowTest.php` (nuevo; o ampliar test existente de Employees)

## Testing Requirements

- Feature tests deben cubrir RBAC (Admin/Editor ok; Lector 403). [Source: `docsBmad/rbac.md`, `project-context.md`]
- Test 404: empleado inexistente -> 404 (no 500). [Source: patrón de `AssetShow` con `findOrFail`]
- Mantener tests deterministas (sin dependencias externas). [Source: `project-context.md`]

## Previous Story Intelligence (aprovechar lo ya hecho)

- Story 4.1 ya creó el módulo `employees` (migración, modelo, listado, RBAC y patrones UI). Reusar el mismo gate `inventory.manage` y estilo de componentes. [Source: `_bmad-output/implementation-artifacts/4-1-crear-y-mantener-empleados-rpe.md`]
- Story 4.2 ya estableció patrones de normalización/búsqueda y “no inventar ruedas” (Actions + Livewire + tests). Mantener consistencia (aunque aquí no haya autocomplete). [Source: `_bmad-output/implementation-artifacts/4-2-buscar-seleccionar-empleados-al-registrar-movimientos-autocomplete.md`]

## Git Intelligence Summary (para evitar regresiones)

- Código existente relevante ya está en producción local:
  - `gatic/app/Models/Employee.php`
  - `gatic/app/Livewire/Employees/EmployeesIndex.php`
  - `gatic/resources/views/livewire/employees/employees-index.blade.php`
  - `gatic/routes/web.php` (grupo `/employees`)
- No existe todavía una ruta/vista de detalle de empleado; esta story introduce esa navegación (cambio de UX mínimo, sin tocar inventario). [Source: repo actual]

## Latest Technical Information (web research)

- Livewire 3 (security): reforzar acceso con middleware `can:` en rutas y autorización server-side dentro del componente para cubrir requests Livewire (defensa en profundidad). [Source: https://livewire.laravel.com/docs/3.x/security]
- Livewire 3 (testing): la ruta que apunta a un componente Livewire se puede cubrir con `Livewire::test(...)` (para asserts de render y estados) y/o con Feature tests sobre la URL para RBAC. [Source: https://livewire.laravel.com/docs/3.x/testing]
- Laravel 11 (routing): usar constraints tipo `whereNumber(...)` para parámetros numéricos y evitar rutas ambiguas. [Source: https://laravel.com/docs/11.x/routing]

## Project Context Reference (leer antes de codear)

- `docsBmad/project-context.md` (bible; si hay conflicto, gana este documento)
- `docsBmad/rbac.md` (gates y defensa en profundidad)
- `_bmad-output/implementation-artifacts/ux.md` (estándares UX: estados vacíos, desktop-first)
- `_bmad-output/implementation-artifacts/architecture.md` (estructura y convenciones)
- `_bmad-output/implementation-artifacts/epics.md` (Epic 4 Story 4.3; Epic 5 como dependencia futura)

## Story Completion Status

- Status: `done`
- Nota: code review aplicado; tracking (`sprint-status.yaml`) actualizado a `done`.

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `Get-Content _bmad-output/implementation-artifacts/sprint-status.yaml` (auto-discovery: primer story en backlog: `4-3-ficha-de-empleado-detalle-y-activos-asociados-si-existen`)
- `Get-Content _bmad-output/implementation-artifacts/epics.md` (Epic 4, Story 4.3)
- `Get-Content _bmad-output/implementation-artifacts/architecture.md`
- `Get-Content docsBmad/project-context.md`
- `Get-Content docsBmad/rbac.md`
- `Get-Content _bmad-output/implementation-artifacts/ux.md`
- `Get-Content gatic/routes/web.php` + `Get-Content gatic/app/Livewire/Employees/EmployeesIndex.php`

### Completion Notes List

- Story seleccionada automáticamente desde `sprint-status.yaml` (primer `backlog` en `development_status`: `4-3-ficha-de-empleado-detalle-y-activos-asociados-si-existen`).
- Se definió alcance explícito: ficha + secciones vacías ahora; asociaciones reales en Epic 5.
- Guardrails para evitar errores típicos: RBAC server-side, no librerías nuevas, rutas/identificadores en inglés, copy en español.
- Implementación completada 2026-01-14:
  - Componente Livewire EmployeeShow con Gate::authorize en mount() y render() (defensa en profundidad)
  - Vista Bootstrap 5 con secciones "Datos del empleado", "Activos asignados", "Activos prestados"
  - Estados vacíos con nota "Se habilita con Movimientos (Epic 5)"
  - Ruta employees.show con middleware can:inventory.manage y whereNumber constraint
  - Link "Ver ficha" agregado en EmployeesIndex
  - 8 tests nuevos cubriendo AC1 (RBAC), AC2 (campos), AC3 (secciones vacías), AC4 (404)
  - 177/177 tests pasan, sin regresiones
- Code review (2026-01-14): artefacto alineado a `done` (status + task UI marcada) y limpieza menor de tests.

### File List

- `_bmad-output/implementation-artifacts/4-3-ficha-de-empleado-detalle-y-activos-asociados-si-existen.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `gatic/routes/web.php` (modified: added employees.show route)
- `gatic/app/Livewire/Employees/EmployeeShow.php` (new)
- `gatic/resources/views/livewire/employees/employee-show.blade.php` (new)
- `gatic/resources/views/livewire/employees/employees-index.blade.php` (modified: added "Ver ficha" link)
- `gatic/tests/Feature/Employees/EmployeeShowTest.php` (new)
