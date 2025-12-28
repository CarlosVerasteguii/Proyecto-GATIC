---
project_name: 'GATIC'
user_name: 'Carlos'
date: '2025-12-27T16:11:32.3720620-06:00'
sections_completed:
  - technology_stack
  - language_rules
  - framework_rules
  - testing_rules
  - quality_rules
  - workflow_rules
  - anti_patterns
status: 'complete'
rule_count: 64
optimized_for_llm: true
sources:
  - docsBmad/project-context.md
  - _bmad-output/architecture.md
  - _bmad-output/prd.md
  - _bmad-output/project-planning-artifacts/epics.md
  - 03-visual-style-guide.md
---

# Project Context for AI Agents (GATIC)

_Reglas criticas para agentes IA. Mantener esto **lean**._

Fuente de verdad (bible): `docsBmad/project-context.md` (si algo contradice este archivo, gana el bible).  
Arquitectura: `_bmad-output/architecture.md`.

---

## Technology Stack & Versions

- Backend: Laravel **11** + PHP **8.2+**
- DB: MySQL **8.0**
- UI: Blade + Livewire **3** + Bootstrap **5** (branding/colores basados en `03-visual-style-guide.md`)
- Auth: Laravel Breeze (Blade) + re-maquetado a Bootstrap (sin registro publico)
- Queue: driver `database`
- Tooling: Vite/NPM, Laravel Sail (dev), Docker Compose (prod)
- Calidad/CI: `pint --test`, `phpunit`, `larastan/larastan`

---

## Critical Implementation Rules

### Language-Specific Rules (PHP)

- Identificadores de **codigo/DB/rutas en ingles**; copy/UI en **espanol** (labels, mensajes).
- No inventar "helpers globales": preferir `app/Actions/*` y `app/Support/*`.
- Errores: siempre incluir `error_id` en logs y en UI (prod); detalle tecnico solo Admin.

### Framework-Specific Rules (Laravel + Livewire)

- UI principal con Livewire 3 (route -> componente); Controllers solo "bordes" (descargas/JSON interno puntual).
- Autorizacion server-side obligatoria (Policies/Gates). La UI ayuda, pero no reemplaza permisos.
- Roles fijos MVP: `Admin`, `Editor`, `Lector` (sin Spatie/permissions complejos por ahora).
- Auth MVP: solo login usuario/contrasena, sin registro publico; Admin crea usuarios; sin reset/verify email.
- Sin WebSockets: usar polling Livewire:
  - listas/badges: ~15s con `wire:poll.visible`
  - metricas dashboard: ~60s con `wire:poll.visible`
  - locks heartbeat: ~10s (solo visible/activo)
- Operaciones de inventario/estados/locks: transaccionales (DB) y con validaciones consistentes.
- Adjuntos (MVP): storage local; nombre en disco UUID; mostrar nombre original; control de acceso estricto.

### Testing Rules

- Feature tests para flujos criticos: RBAC (Admin/Editor/Lector) + locks (claim/timeout/override) + movimientos.
- Tests deterministas (sin dependencias externas); usar `RefreshDatabase` cuando aplique.

### Code Quality & Style Rules

- Pasar Pint + PHPUnit + Larastan antes de merge (CI debe estar verde).
- Rutas:
  - paths: `kebab-case` en ingles (ej. `/pending-tasks`)
  - names: `dot.case` por modulo (ej. `inventory.products.index`)
- No mezclar rutas/identificadores en espanol/ingles.

### Development Workflow Rules (BMAD-first)

- Backlog fuente de verdad: `_bmad-output/project-planning-artifacts/epics.md`.
- Tracking sprint: `_bmad-output/implementation-artifacts/sprint-status.yaml`.
- Trabajar un Gate a la vez (ver `docsBmad/gates-execution.md`).
- Si se hacen commits, seguir `COMMIT_CONVENTIONS.md`.

### Critical Dont-Miss Rules (Dominio/Edge Cases)

- Empleado (RPE) != Usuario del sistema.
- Inventario dual:
  - serializado: Activos (Asset)
  - no serializado: stock por cantidad + kardex
- Unicidades DB:
  - `assets.asset_tag` unico global (cuando exista)
  - `assets` unique `(product_id, serial)`
- Semantica QTY:
  - no disponibles = Asignado + Prestado + Pendiente de Retiro
  - disponibles = Total - No disponibles
  - `Retirado` no cuenta por defecto (solo por filtro/historial)
- Busqueda unificada:
  - match exacto por serial/asset_tag -> ir directo a detalle de Activo
  - NO indexar/mezclar Tareas Pendientes en busqueda de inventario (MVP)
- Locks (Tareas Pendientes):
  - claim al entrar a "Procesar" (preventivo)
  - timeout rolling 15m + lease TTL 3m + idle guard 2m + heartbeat 10s
  - Admin puede forzar liberacion/reclamo (auditado)
- Soft-delete: retencion indefinida hasta vaciar papelera (Admin).

---

## Usage Guidelines

- Agentes: leer este archivo + `_bmad-output/architecture.md` antes de implementar.
- Si hay conflicto: gana `docsBmad/project-context.md`.

Last Updated: 2025-12-27
