# AGENTS (GATIC)

## Start here

- GATIC es un sistema interno para gestión de **Inventario** y **Activos** (intranet/on‑prem).
- La aplicación Laravel vive en [`gatic/`](gatic/) (no en la raíz del repo).
- Este archivo es un mapa para agentes: sigue el orden de “Source of Truth”.

## Source of Truth (orden)

1. [`docsBmad/project-context.md`](docsBmad/project-context.md) — bible
2. [`project-context.md`](project-context.md) — reglas críticas lean para agentes
3. [`_bmad-output/architecture.md`](_bmad-output/architecture.md) y [`_bmad-output/prd.md`](_bmad-output/prd.md)
4. [`README.md`](README.md) — layout/stack/comandos
5. [`.github/workflows/ci.yml`](.github/workflows/ci.yml) — quality gates
6. [`gatic/docs/agent-enforcement.md`](gatic/docs/agent-enforcement.md) — principios + enforcement mecánico

## Comandos canónicos (desde `gatic/`)

- Levantar / bajar: `docker compose -f compose.yaml up -d` / `docker compose -f compose.yaml down`
- Calidad: `./vendor/bin/pint --test`, `php artisan test`, `./vendor/bin/phpstan analyse --no-progress`
- Nota: CI corre Pint + PHPUnit + Larastan

## Dónde buscar según tarea

### Rutas/navegación

- [`gatic/routes/web.php`](gatic/routes/web.php), [`gatic/app/Livewire/*`](gatic/app/Livewire/), [`gatic/resources/views/*`](gatic/resources/views/)

### Dominio inventario/semántica

- [`gatic/app/Models/README.md`](gatic/app/Models/README.md), [`gatic/app/Models/*`](gatic/app/Models/)

### Locks/Tareas pendientes

- [`gatic/docs/patterns/concurrency-locks.md`](gatic/docs/patterns/concurrency-locks.md), [`gatic/docs/state-machines/pending-task-*.md`](gatic/docs/state-machines/), [`gatic/app/Livewire/PendingTasks/*`](gatic/app/Livewire/PendingTasks/)

### Adjuntos

- [`gatic/app/Models/Attachment.php`](gatic/app/Models/Attachment.php), [`gatic/app/Http/Controllers/Attachments/*`](gatic/app/Http/Controllers/Attachments/), [`gatic/app/Livewire/Ui/AttachmentsPanel.php`](gatic/app/Livewire/Ui/AttachmentsPanel.php), [`gatic/tests/Feature/Attachments/*`](gatic/tests/Feature/Attachments/)

### RBAC

- [`docsBmad/rbac.md`](docsBmad/rbac.md), [`gatic/app/Models/User.php`](gatic/app/Models/User.php), [`gatic/app/Providers/AuthServiceProvider.php`](gatic/app/Providers/AuthServiceProvider.php), [`gatic/app/Support/Authorization/RoleAccess.php`](gatic/app/Support/Authorization/RoleAccess.php)

### Errores con `error_id`

- [`gatic/app/Models/ErrorReport.php`](gatic/app/Models/ErrorReport.php), [`gatic/app/Support/Errors/ErrorReporter.php`](gatic/app/Support/Errors/ErrorReporter.php), [`gatic/app/Livewire/Admin/ErrorReports/ErrorReportsLookup.php`](gatic/app/Livewire/Admin/ErrorReports/ErrorReportsLookup.php), [`gatic/resources/views/livewire/admin/error-reports/error-reports-lookup.blade.php`](gatic/resources/views/livewire/admin/error-reports/error-reports-lookup.blade.php), [`gatic/tests/Feature/ErrorReports/*`](gatic/tests/Feature/ErrorReports/)

## Golden principles (resumen)

- Leer “Source of Truth” en orden; si hay conflicto, gana `docsBmad/project-context.md`.
- Trabajar en `gatic/`; fuera de ahí solo docs/artefactos de planeación.
- UI: Blade + Livewire 3 (MPA) + Bootstrap 5; no WebSockets; polling cuando aplique.
- Identificadores (código/DB/rutas) en inglés; copy/UI en español.
- Evitar helpers globales; preferir `gatic/app/Actions/*` y `gatic/app/Support/*`.
- Autorización server-side obligatoria (Gates/Policies); roles `Admin`/`Editor`/`Lector`.
- Errores inesperados: UI humana + `error_id`; detalle técnico solo Admin; log/DB best-effort.
- Antes de merge: Pint + PHPUnit + Larastan (CI debe estar verde).

Ver detalles y enforcement en [`gatic/docs/agent-enforcement.md`](gatic/docs/agent-enforcement.md).

## Skills

- Skills viven en [`.codex/skills/`](.codex/skills/) y [`.agents/skills/`](.agents/skills/).
- Cuando una tarea matchee una skill, abrir su `SKILL.md` relevante y seguirla (no duplicar listas aquí).
