# Story 8.5: Error ID consultable por Admin (end-to-end)

Status: done

Story Key: `8-5-error-id-consultable-por-admin-end-to-end`  
Epic: `8` (Gate 5: Trazabilidad y evidencia)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Fecha: 2026-01-25

> Nota anti-reinvención (CRÍTICO): este repo YA tiene implementado `error_id` end-to-end (persistencia + UI Admin lookup + Livewire/HTML) en Story 1.10.  
> Este story existe para **consolidar y asegurar el flujo** en el contexto de Gate 5 (Soporte/Admin), evitar duplicación y prevenir regresiones.

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/epics.md` (Epic 8 / Story 8.5; FR36, NFR10)  
  [Source: _bmad-output/implementation-artifacts/epics.md#Story 8.5: Error ID consultable por Admin (end-to-end)]
- `_bmad-output/implementation-artifacts/prd.md` (FR36, NFR10)  
  [Source: _bmad-output/implementation-artifacts/prd.md#Traceability, Attachments & Trash]
- `_bmad-output/implementation-artifacts/ux.md` (Journey 3 Admin: investigar error_id; patrones de errores)  
  [Source: _bmad-output/implementation-artifacts/ux.md#Journey 3 - Admin: Gobernanza + excepciones (locks + error_id)]
- `project-context.md` (reglas críticas; errores con `error_id`, RBAC, idioma)  
  [Source: project-context.md#Critical Implementation Rules]
- Implementación existente (no duplicar):
  - `gatic/bootstrap/app.php` (manejo global de excepciones en prod + JSON para Livewire)  
    [Source: gatic/bootstrap/app.php]
  - `gatic/app/Support/Errors/ErrorReporter.php` (generación/persistencia best-effort + redaction)  
    [Source: gatic/app/Support/Errors/ErrorReporter.php]
  - `gatic/database/migrations/2025_12_31_000000_create_error_reports_table.php`  
    [Source: gatic/database/migrations/2025_12_31_000000_create_error_reports_table.php]
  - `gatic/app/Livewire/Admin/ErrorReports/ErrorReportsLookup.php` + `gatic/resources/views/livewire/admin/error-reports/error-reports-lookup.blade.php`  
    [Source: gatic/app/Livewire/Admin/ErrorReports/ErrorReportsLookup.php]
  - `gatic/routes/web.php` (ruta `admin.error-reports.lookup`)  
    [Source: gatic/routes/web.php]
  - `gatic/resources/views/errors/500.blade.php` + `gatic/resources/js/ui/livewire-error-handling.js` (UI/UX del error_id)  
    [Source: gatic/resources/views/errors/500.blade.php]
  - `gatic/routes/console.php` + `gatic/config/gatic.php` (retención/purga de `error_reports`)  
    [Source: gatic/routes/console.php]

## Story

As a Admin,
I want poder consultar el detalle técnico asociado a un ID de error,
so that pueda diagnosticar incidentes reportados por usuarios (FR36, NFR10).

## Acceptance Criteria

### AC1 — Admin puede consultar por `error_id` y ver detalle útil

**Given** un `error_id` generado en producción
**When** Admin lo consulta en la UI de soporte
**Then** puede ver stack/contexto relevante
**And** queda claro cuándo ocurrió y en qué endpoint/acción (método, route name y/o URL)

### AC2 — RBAC: solo Admin ve el detalle técnico

**Given** un usuario NO Admin (Editor/Lector)
**When** intenta acceder a la UI de consulta o ver detalle por `error_id`
**Then** el servidor lo bloquea (403)

### AC3 — Producción: errores inesperados muestran mensaje humano + `error_id`

**Given** `APP_DEBUG=false` en producción
**When** ocurre una excepción no controlada (>=500)
**Then** el usuario ve un mensaje amigable
**And** se muestra un `error_id` copiable
**And** el detalle técnico NO se filtra a usuarios no Admin

### AC4 — Livewire: error inesperado devuelve JSON consistente con `error_id`

**Given** una acción Livewire falla con error inesperado (>=500)
**When** la UI procesa la respuesta
**Then** se evita el modal crudo de Livewire
**And** se muestra un toast/alert consistente incluyendo `error_id`

### AC5 — Higiene de datos: no filtrar secretos

**Given** un error inesperado en un request con inputs
**Then** el contexto persistido NO incluye passwords/tokens/cookies/headers sensibles
**And** el detalle visible a Admin no muestra secretos accidentalmente

### AC6 — Operabilidad: retención/purga controlada

**Given** registros `error_reports` antiguos
**When** se ejecuta la rutina de purga
**Then** se eliminan registros >N días (configurable) sin afectar operación diaria

## Tasks / Subtasks

- [x] 1) Confirmar reuso del sistema existente (AC: 1–6)
  - [x] Revisar `ErrorReporter` + `ErrorReport` + migración `error_reports` (no duplicar)
  - [x] Confirmar manejo global en `bootstrap/app.php` para HTML y JSON
  - [x] Confirmar UI Admin lookup existente y copy UX

- [x] 2) UX end-to-end: entry point “Errores/Soporte” para Admin (AC: 1)
  - [x] Agregar link visible solo a Admin (sidebar/topbar) a `route('admin.error-reports.lookup')`
  - [x] Copy sugerido: “Errores (soporte)” / “Consultar error por ID”

- [x] 3) Verificación de evidencia mínima en el lookup (AC: 1)
  - [x] Verifica que se muestre: timestamp, método, route/URL, excepción, stack trace, contexto redactado
  - [x] “Endpoint/acción” visible: `route` (nombre) y `path` (cuando exista) en la UI

- [x] 4) Seguridad (AC: 2, 5)
  - [x] Re-verificar `Gate::authorize('admin-only')` y middleware `can:admin-only`
  - [x] Confirmar redaction/allowlist: no headers sensibles, no querystrings, no input values

- [x] 5) Tests + no regresiones (AC: 1–5)
  - [x] Ejecutar tests existentes de ErrorReports (autorización + persistencia + render prod)
  - [x] Si se agrega navegación, agregar test simple de UI (feature) o snapshot de sidebar para Admin

- [x] 6) Operación (AC: 6)
  - [x] Confirmar `gatic:purge-error-reports` y variables env:
    - `GATIC_ERROR_REPORTING_ENABLED`
    - `GATIC_ERROR_REPORTS_RETENTION_DAYS`

## Dev Notes

### Contexto y guardrails (leer primero)

- NO reinstalar/duplicar solución: ya existe `error_reports` + UI Admin lookup + hooks Livewire. (Ver “Implementación existente” arriba.)
- NO agregar paquetes nuevos (Telescope/log viewers) salvo que el PO lo pida explícitamente.
- Mensajes/UI en español; identificadores/rutas/DB en inglés. [Source: project-context.md#Critical Implementation Rules]
- RBAC server-side obligatorio (Gates/Policies + middleware `can:`). [Source: project-context.md#Framework-Specific Rules (Laravel + Livewire)]

### Implementación existente (mapa rápido)

- Generación/persistencia/logging:
  - `App\Support\Errors\ErrorReporter::report(Throwable, Request): string` genera ULID, persiste best-effort, redacciona, y loguea con `error_id` + contexto.  
    [Source: gatic/app/Support/Errors/ErrorReporter.php]
  - Tabla `error_reports` con `error_id` único + request/user/exception/stack/context.  
    [Source: gatic/database/migrations/2025_12_31_000000_create_error_reports_table.php]

- Manejo global en producción:
  - `bootstrap/app.php` intercepta errores >=500 cuando NO es `local/testing` y `APP_DEBUG=false`.
  - HTML: render `errors.500` con `errorId`.
  - JSON (Livewire): `{ message, error_id }` status 500.  
    [Source: gatic/bootstrap/app.php]

- UI de consulta Admin:
  - Ruta: `/admin/error-reports` (middleware `can:admin-only`) → `ErrorReportsLookup` (Livewire).
  - UI muestra timestamp, método, route, URL, excepción, stack, contexto (redactado) y botón “Copiar ID”.  
    [Source: gatic/routes/web.php]

- Livewire UX:
  - `resources/js/ui/livewire-error-handling.js` intercepta fails >=500, extrae `error_id` y muestra toast.  
    [Source: gatic/resources/js/ui/livewire-error-handling.js]

- Retención:
  - Comando `gatic:purge-error-reports` borra registros >N días según `config('gatic.errors.reporting.retention_days')`.  
    [Source: gatic/routes/console.php]

### Requisitos técnicos (anti-disaster)

- `error_id` debe ser copiable y visible al usuario solo como identificador (sin stack trace).
- Admin debe ver “cuándo y dónde”:
  - mínimo: `created_at`, `method`, `route` y/o `url`.
- Higiene: NO persistir valores de inputs; solo llaves; headers allowlist (`accept`, `user-agent`, `referer` sin query).  
  [Source: gatic/app/Support/Errors/ErrorReporter.php]

### Cumplimiento de UX

- Journey Admin esperado: entrar a soporte → ingresar `error_id` → ver detalle o “no encontrado/expiró”.  
  [Source: _bmad-output/implementation-artifacts/ux.md#Journey 3 - Admin: Gobernanza + excepciones (locks + error_id)]
- Mantener mensajes humanos (“calma ante errores”) y feedback consistente.

### Testing

- Tests existentes relevantes:
  - `gatic/tests/Feature/ErrorReports/ProductionUnhandledExceptionTest.php`
  - `gatic/tests/Feature/ErrorReports/ErrorReportPersistenceTest.php`
  - `gatic/tests/Feature/ErrorReports/ErrorReportsAuthorizationTest.php`

### Git intelligence (contexto reciente)

Últimos commits (Epic 8):
- `3513269 feat(trash): modulo papelera con soft-delete y purga (Product, Asset, Employee)`
- `3d86892 feat(attachments): adjuntos seguros con control de acceso (Product, Asset, Employee)`
- `4932112 feat(notes): notas manuales en entidades relevantes (Product, Asset, Employee)`
- `6f39880 feat(audit): implementacion modulo auditoria best-effort (Story 8.1)`
- `15afa1d docs(bmad): formaliza sign-off, auditoría y preflight`

### Latest tech info (enero 2026)

- Laravel 11: la configuración de excepciones y render se hace en `bootstrap/app.php` con `withExceptions(...)`.
- Logging: `Log::withContext/shareContext` y `Context::add` pueden ayudar si se requiere un “request-id/trace-id” adicional, pero **no es necesario** para cumplir este story (ya existe `error_id`).

### Project Structure Notes

- Mantener “cross-cutting concerns” de errores en `gatic/app/Support/Errors/*` (evitar helpers globales).
- Persistencia y modelo: `gatic/database/migrations/*create_error_reports*` + `gatic/app/Models/ErrorReport.php`.
- UI Admin (Livewire-first): `gatic/app/Livewire/Admin/ErrorReports/*` + `gatic/resources/views/livewire/admin/error-reports/*`.
- Routing: `gatic/routes/web.php` dentro del grupo `admin` con middleware `can:admin-only`.

### References

- Requisitos: `_bmad-output/implementation-artifacts/epics.md` (Epic 8 / Story 8.5) y `_bmad-output/implementation-artifacts/prd.md` (FR36, NFR10).
- UX: `_bmad-output/implementation-artifacts/ux.md` (Journey Admin: Investigar `error_id`).
- Reglas críticas: `project-context.md` (RBAC, idioma, error_id en prod).
- Implementación existente: `gatic/bootstrap/app.php`, `gatic/app/Support/Errors/ErrorReporter.php`, `gatic/app/Livewire/Admin/ErrorReports/ErrorReportsLookup.php`, `gatic/resources/views/livewire/admin/error-reports/error-reports-lookup.blade.php`, `gatic/resources/js/ui/livewire-error-handling.js`.

## Story Completion Status

- Status: **done**
- Completion note: consolidación completada: entry point Admin “Errores (soporte)” + lookup re-validado (incluye route name + path cuando existe) + tests de no regresión.

## Senior Developer Review (AI)

- Fecha: 2026-01-25
- Resultado: issues HIGH/MEDIUM corregidos; status a **done**
- Fixes aplicados:
  - Story: estados y checklist internos alineados (sin “review vs ready-for-dev”).
  - UI lookup: muestra “Ruta (nombre)” y “Path” (cuando existe) para clarificar el endpoint.
  - Tests: navegación de sidebar ahora valida también el `href` de `admin.error-reports.lookup`.
  - Tracking: `sprint-status.yaml` sincronizado.

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `_bmad-output/implementation-artifacts/sprint-status.yaml` (tracking del gate/epic)
- `_bmad-output/implementation-artifacts/epics.md` (Epic 8 / Story 8.5)
- `_bmad-output/implementation-artifacts/prd.md` (FR36, NFR10)
- `_bmad-output/implementation-artifacts/ux.md` (Journey Admin: investigar `error_id`)
- `project-context.md` (reglas críticas)
- Implementación existente: `gatic/bootstrap/app.php`, `gatic/app/Support/Errors/ErrorReporter.php`, `gatic/app/Livewire/Admin/ErrorReports/ErrorReportsLookup.php`, `gatic/routes/web.php`
- `docker compose -f gatic/compose.yaml exec -T laravel.test php artisan test --filter ErrorReports` (validación)

### Completion Notes List

- ✅ Task 1-6 completados: Sistema error_id existente confirmado (ErrorReporter, bootstrap/app.php, UI lookup)
- ✅ Agregado link 'Errores (soporte)' en sidebar para Admin
- ✅ UI lookup muestra timestamp, método, ruta (nombre), path (si existe), URL, excepción, stack trace y contexto redactado
- ✅ Seguridad RBAC verificada: Gate 'admin-only' + middleware en ruta y componente
- ✅ Redaction de secretos verificada en ErrorReporter
- ✅ Purge command 'gatic:purge-error-reports' confirmado con env vars
- ✅ Test de navegación sidebar robustecido (valida texto + href)
- ✅ Sprint status sincronizado a done

### Change Log

- 2026-01-25: Review + fixes (alineación de estados, UI lookup con path, tests robustos, sprint-status sync).

### File List

- _bmad-output/implementation-artifacts/8-5-error-id-consultable-por-admin-end-to-end.md (actualizado: status + review + file list)
- _bmad-output/implementation-artifacts/sprint-status.yaml (modificado: 8-5 → done)
- gatic/resources/views/layouts/partials/sidebar-nav.blade.php (modificado: link Errores soporte)
- gatic/resources/views/livewire/admin/error-reports/error-reports-lookup.blade.php (modificado: muestra route name + path)
- gatic/tests/Feature/ErrorReports/ErrorReportsSidebarNavigationTest.php (nuevo/modificado: test navegación)
