# Story 1.10: Manejo de errores en producción con ID (detalle solo Admin)

Status: done

Story Key: 1-10-manejo-de-errores-en-produccion-con-id-detalle-solo-admin  
Tracking: _bmad-output/implementation-artifacts/sprint-status.yaml  
Gate: 1 (UX base + navegación)  
Epic: 1 (Acceso seguro y administración de usuarios)  
GitHub (referencia): N/A (este story key proviene del backlog BMAD; ver `docsBmad/gates-execution.md`)  
Fuentes: _bmad-output/project-planning-artifacts/epics.md, docsBmad/gates-execution.md, _bmad-output/prd.md, _bmad-output/architecture.md, _bmad-output/project-planning-artifacts/ux-design-specification.md, docsBmad/project-context.md, project-context.md, _bmad-output/implementation-artifacts/sprint-status.yaml, _bmad-output/implementation-artifacts/1-9-componentes-ux-reutilizables-toasts-loaders-cancelar-actualizado-hace-xs.md, gatic/bootstrap/app.php, gatic/resources/views/errors/403.blade.php, gatic/resources/js/ui/toasts.js, gatic/config/gatic.php, gatic/routes/web.php, gatic/app/Providers/AuthServiceProvider.php, gatic/vendor/livewire/livewire/dist/livewire.js

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a usuario interno (Admin/Editor/Lector),
I want ver un mensaje amigable con un ID de error cuando algo falla inesperadamente,
so that pueda reportar el problema y TI lo pueda diagnosticar rápido (FR36, NFR10).

## Acceptance Criteria

1. **Producción: error inesperado muestra mensaje humano + `error_id` (copiable)**
   - **Given** la app está en entorno producción (sin `APP_DEBUG`)
   - **When** ocurre una excepción no controlada (500) durante navegación normal
   - **Then** el usuario ve un mensaje humano y no técnico
   - **And** se muestra un `error_id` **copiable** (botón "Copiar")
   - **And** NO se muestra stack trace/SQL/detalle interno a usuarios no Admin

2. **`error_id` se registra con detalle técnico suficiente (DB + logs)**
   - **Given** ocurre un error inesperado
   - **When** el sistema genera un `error_id`
   - **Then** se registra un evento en logs con el mismo `error_id` y contexto (usuario, ruta, método, etc.)
   - **And** se persiste un registro consultable (ej. tabla `error_reports`) con:
     - timestamp, environment, user_id (si existe), request URL/route/method
     - exception class + message
     - stack trace (o referencia equivalente) y contexto útil para soporte

3. **Admin puede consultar detalle por `error_id`; no-Admin no**
   - **Given** un usuario Admin autenticado
   - **When** consulta el detalle del error por `error_id` en una pantalla Admin
   - **Then** puede ver información diagnóstica suficiente para soporte (clase, mensaje, stack, request context)
   - **And** un usuario no Admin NO puede ver ese detalle (403)

4. **Livewire: errores inesperados no abren el modal de Livewire; muestran feedback con `error_id`**
   - **Given** una acción Livewire que dispara un error inesperado (500)
   - **When** el request falla
   - **Then** la UI muestra feedback consistente (toast/alert) con `error_id`
   - **And** se evita el modal por defecto de Livewire (HTML crudo)

5. **Higiene de datos: no filtrar ni persistir secretos**
   - **Given** un error inesperado durante un request con inputs
   - **Then** el `error_reports.context` NO incluye passwords/tokens/cookies/headers sensibles
   - **And** el detalle visible a Admin no muestra secretos accidentalmente (redaction/allowlist)

6. **Dev experience: en local/testing se mantiene el comportamiento de debug**
   - **Given** entorno `local`/`testing`
   - **Then** el desarrollador conserva el detalle técnico estándar (no bloquear debugging)

## Tasks / Subtasks

- [x] 1) Persistencia `error_reports` (AC: 2, 5)
  - [x] Crear migración `error_reports` (índices, `error_id` único)
  - [x] Crear modelo `ErrorReport` (casts JSON, helpers de formato)
  - [x] Definir política de retención/limpieza (puede quedar como TODO con config)

- [x] 2) Servicio para generar `error_id` + redaction + persistencia best-effort (AC: 2, 5, 6)
  - [x] Implementar generador de `error_id` (ULID/UUID) y garantizar unicidad (DB + fallback)
  - [x] Definir allowlist/redaction de contexto (sin secrets)
  - [x] Log estructurado con `error_id` + contexto mínimo (user_id, route, method, url)

- [x] 3) Manejo global de excepciones (HTML + JSON) (AC: 1, 2, 6)
  - [x] Configurar `withExceptions` en `gatic/bootstrap/app.php` para capturar "errores inesperados"
  - [x] HTML: renderizar vista `errors/500` con mensaje humano + `error_id`
  - [x] JSON/Livewire: responder `{ message, error_id }` (500) cuando aplique

- [x] 4) UI: componente `ErrorAlertWithId` (AC: 1, 4)
  - [x] Implementar componente Blade reutilizable (mensaje + `error_id` + botón "Copiar")
  - [x] Agregar JS mínimo para copiar al clipboard (fallback sin dependencias nuevas)

- [x] 5) Livewire: interceptar fallos y evitar modal por defecto (AC: 4)
  - [x] Agregar módulo JS que use `Livewire.hook('request', ...)` y `fail(...)`
  - [x] Para `status >= 500`: parsear `error_id` del JSON y mostrar toast con `errorId`
  - [x] Llamar `preventDefault()` para que Livewire no muestre el modal de error

- [x] 6) Admin: consulta de detalle por `error_id` (AC: 3)
  - [x] Crear ruta Admin (ej. `admin.error-reports.lookup`) detrás de `auth` + `active` + `can:admin-only`
  - [x] Crear Livewire component con input `error_id` + render de detalle (clase/mensaje/stack/contexto)
  - [x] UX: "Copiar ID" y mostrar "No encontrado" cuando aplique

- [x] 7) Tests + regresión (AC: 1-6)
  - [x] Feature tests para: render 500 con `error_id`, persistencia `error_reports`, RBAC de pantalla Admin
  - [ ] (Opcional) Playwright smoke: forzar 500 y verificar que Livewire no abre modal y sí muestra toast con `ID: ...` (no ejecutado)

- [x] 8) Configuración y docs mínimas (AC: 5, 6)
  - [x] Extender `gatic/config/gatic.php` con flags de error reporting (enabled/retención)
  - [x] Documentar patrón `ErrorAlertWithId` (si ya existe `gatic/docs/ui-patterns.md`, agregar sección)

## Dev Notes

### Developer Context (qué existe hoy y qué cambia)

- Stack actual: Laravel 11 + Livewire 3 + Bootstrap 5 (ver `project-context.md` y `_bmad-output/architecture.md`).
- Hoy el “handler” de excepciones no está personalizado: `gatic/bootstrap/app.php` tiene `->withExceptions(...)` vacío.
- Hoy solo existe vista de error `403`: `gatic/resources/views/errors/403.blade.php`; no hay `500.blade.php` ni componente UX para `error_id`.
- Ya existe RBAC server-side y gates reutilizables:
  - `admin-only` (Admin)
  - `users.manage` (Admin)
  - patrón de rutas Admin: `gatic/routes/web.php` (`/admin/*` con `can:users.manage`)
- Ya existe sistema de toasts global con soporte de `errorId` (Story 1.9):
  - Trait Livewire: `gatic/app/Livewire/Concerns/InteractsWithToasts.php`
  - JS: `gatic/resources/js/ui/toasts.js` (renderiza “ID: …” si existe `errorId`)
- Livewire v3.7.3 por defecto muestra un modal con HTML cuando el request falla; el hook existe en `gatic/vendor/livewire/livewire/dist/livewire.js`:
  - `Livewire.hook('request', ({ fail }) => fail(({ status, content, preventDefault }) => ...))`
  - Esto permite reemplazar el modal por feedback consistente (toast/alert) y evitar fuga de detalles.

### UX (qué debe verse)

- UX spec define patrón “Error inesperado” como: mensaje humano + `error_id` (copiable) + “Reintentar” cuando sea seguro.
- Para esta story, el mínimo es: mensaje humano + `error_id` + botón “Copiar”; “Reintentar” solo si se puede garantizar idempotencia.

### Requerimientos técnicos (guardrails)

- `error_id`:
  - Generar un identificador **no adivinable** y fácil de copiar (recomendado: ULID vía `Str::ulid()` o UUID).
  - El `error_id` debe aparecer en **(a)** UI (prod) **y** **(b)** logs **y** **(c)** registro consultable (DB) cuando sea posible.
- Clasificación:
  - “Error esperado” (validación, dominio, autorización) → mensaje accionable; **NO** crear `error_id` por defecto.
  - “Error inesperado” (Throwable no controlado / 500) → mensaje humano + `error_id` + logging + persistencia best-effort.
- Respuestas:
  - Web (HTML): vista amigable `500` con `error_id` (sin detalle técnico para no-Admin).
  - Livewire/JSON (`expectsJson()` o header `X-Livewire`): responder `500` con JSON `{ "message": "...", "error_id": "..." }`.
- Persistencia `error_reports` (best-effort):
  - Si falla la persistencia (DB down), **no** bloquear el response: loggear el fallo y seguir mostrando `error_id`.
  - Guardar suficiente para soporte: timestamp, env, user_id, ruta/URL, clase/mensaje, stack trace, context saneado.
- Higiene de datos:
  - Nunca persistir/mostrar: passwords, tokens, cookies, `Authorization`, `X-CSRF-TOKEN`, etc.
  - Preferir allowlist de campos de contexto vs “guardar todo”.

### Cumplimiento de arquitectura (no negociar)

- Alineado a `_bmad-output/architecture.md`:
  - Errores inesperados: mensaje amigable + `error_id`; detalle técnico solo Admin.
  - Endpoints internos (si aplica): JSON con `{ "message": "...", "error_id": "..." }` en 500 inesperado.
  - Logging con correlación por `error_id` (context array con `user_id`, `route`, etc.).
- Alineado a `project-context.md` / `docsBmad/project-context.md`:
  - UI principal: Blade + Livewire 3 (no controllers para navegación).
  - Autorización server-side obligatoria (gates/policies + middleware `can:`).
  - Sin servicios externos de observabilidad en MVP (no Sentry, no nuevos packages).

### Requisitos de librerías/frameworks (versiones y APIs)

- Laravel: `laravel/framework` `^11.31` (ver `gatic/composer.json`)
  - Customizar excepciones vía `gatic/bootstrap/app.php` en `->withExceptions(function (Exceptions $exceptions) { ... })`.
- Livewire: `livewire/livewire` `v3.7.3` (ver `gatic/composer.lock`)
  - Interceptar fallos de request con `Livewire.hook('request', ...)` y el callback `fail(...)` para evitar el modal por defecto.
- UI: Bootstrap 5 + sistema de toasts existente (Story 1.9)
  - Reusar `window.GaticToasts.show(...)` para feedback consistente (incluyendo `errorId`).

### Project Structure Notes

- App vive dentro de `gatic/` (no mover; respetar multi-root).
- Backend (propuesto, alineado a `_bmad-output/architecture.md`):
  - `gatic/app/Models/ErrorReport.php`
  - `gatic/database/migrations/*_create_error_reports_table.php`
  - `gatic/app/Actions/Errors/*` o `gatic/app/Support/Errors/*` (generación de `error_id`, persistencia, redaction)
  - `gatic/bootstrap/app.php` (registro global `withExceptions`)
  - `gatic/config/gatic.php` (feature flags/timeouts/retención)
- UI (propuesto):
  - `gatic/resources/views/errors/500.blade.php` (mensaje humano + `error_id` copiable)
  - `gatic/resources/views/components/ui/error-alert-with-id.blade.php` (patrón reusable)
  - `gatic/app/Livewire/Admin/ErrorReports/*` + `gatic/resources/views/livewire/admin/error-reports/*` (lookup Admin por `error_id`)
  - `gatic/resources/js/ui/livewire-error-handling.js` + `gatic/resources/js/app.js` (hook Livewire request fail)

### Requisitos de testing (mínimo)

- Feature tests (prioridad) en `gatic/tests/Feature/*`:
  - Usuario no-Admin no puede acceder al detalle Admin del error (403).
  - Admin puede consultar un `error_id` existente y ver campos clave.
  - Un error inesperado retorna vista/JSON con `error_id` y persiste `error_reports` (best-effort).
- Evitar tests frágiles por copy exacto; preferir asserts por `data-testid`/estructura.
- Mantener CI verde: Pint + PHPUnit + Larastan (ver `project-context.md`).

### Previous Story Intelligence (reusar, no reinventar)

- Story 1.9 ya definió UX/guardrails para errores inesperados en UI:
  - No mostrar detalles técnicos en toasts; si existe `error_id`, mostrarlo como referencia.
  - Reusar `InteractsWithToasts` + `window.GaticToasts.show(...)` en vez de crear otro sistema de alerts.
- Story 1.6 ya dejó gates base (Admin/Editor/Lector); para el detalle de error usar gate Admin (ej. `admin-only`) y middleware `can:`.
- Story 1.7 fijó CI mínimo; esta story debe dejar Pint/PHPUnit/Larastan verde.

### Git Intelligence Summary (patrón reciente)

- Commits recientes muestran patrón “gate-<n> + story <n>” y cambios enfocados:
  - `4ec22bb` (Story 1.9): componentes UX reutilizables (toasts/loaders/cancel/freshness)
  - `2fc1d82` (Story 1.8): app shell (sidebar/topbar)
  - `289a45c` (Story 1.7): CI + calidad
  - `10d9ece` (Story 1.6): roles/gates base
  - `9ac1020` (Story 1.5): Livewire 3 integrado

### Latest Tech Information (encontrado en el repo)

- Livewire v3.7.3 soporta interceptar fallos de request vía `Livewire.hook('request', ...)` y `preventDefault()` (ver `gatic/vendor/livewire/livewire/dist/livewire.js`).
- Laravel 11 usa `bootstrap/app.php` para configurar exceptions; aquí se implementa el handler global sin tocar un `Handler.php` clásico.

### References

- Backlog (fuente de verdad): `_bmad-output/project-planning-artifacts/epics.md` (Story 1.10)
- Gate 1 scope: `docsBmad/gates-execution.md` (G1-E03 Errores)
- PRD: `_bmad-output/prd.md` (NFR10)
- Arquitectura: `_bmad-output/architecture.md` (errores con `error_id`, `ErrorReport`, JSON error)
- UX: `_bmad-output/project-planning-artifacts/ux-design-specification.md` (ErrorAlertWithId + copy)
- Reglas críticas: `docsBmad/project-context.md`, `project-context.md`
- Story previa (UX infra): `_bmad-output/implementation-artifacts/1-9-componentes-ux-reutilizables-toasts-loaders-cancelar-actualizado-hace-xs.md`
- Código actual:
  - `gatic/bootstrap/app.php` (exceptions)
  - `gatic/resources/views/errors/403.blade.php` (patrón de vistas de error)
  - `gatic/resources/js/ui/toasts.js` (render de `errorId`)
  - `gatic/routes/web.php`, `gatic/app/Providers/AuthServiceProvider.php` (RBAC y rutas Admin)
  - `gatic/vendor/livewire/livewire/dist/livewire.js` (hook request fail)

### Project Context Reference (reglas que esta story NO puede violar)

- Identificadores/código/DB/rutas en **inglés**; copy UI en **español**.
- UI principal Livewire-first; controllers solo en “bordes” cuando aplique.
- Autorización server-side obligatoria (Policies/Gates + middleware `can:`).
- Errores en prod: mensaje amigable + `error_id`; detalle técnico solo Admin.
- Sin WebSockets; no agregar dependencias nuevas para esto.

## Story Completion Status

- Status: **done**
- Completion note: implementación completa y validada con tests Feature (ErrorReports) para manejo de errores en producción con `error_id` y detalle Admin.

## Senior Developer Review (AI)

- Fecha: 2025-12-31
- Resultado: issues HIGH/MEDIUM corregidos; status a **done**
- Fixes aplicados:
  - `abort(500)` / `HttpExceptionInterface` con status `>= 500` ahora genera `error_id` (antes se omitía por tipo).
  - Higiene: `referer` se persiste sin querystring/fragment y se redaccionan secretos comunes en `exception_message`/`stack_trace`.
  - Retención: comando `gatic:purge-error-reports` para purgar registros antiguos según config.
  - Tests: cobertura para `abort(500)`, redaction y no filtrar detalle técnico en 500.

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `_bmad-output/implementation-artifacts/sprint-status.yaml` (auto-selección del primer story en `backlog`)
- `_bmad-output/project-planning-artifacts/epics.md` (Story 1.10)
- `_bmad-output/prd.md` (NFR10)
- `_bmad-output/architecture.md` (patrones de `error_id` + `ErrorReport`)
- `_bmad-output/project-planning-artifacts/ux-design-specification.md` (ErrorAlertWithId)
- `docsBmad/gates-execution.md` (Gate 1: Errores)
- `docsBmad/project-context.md`, `project-context.md` (reglas críticas)
- `gatic/bootstrap/app.php` (withExceptions)
- `gatic/resources/views/errors/403.blade.php` (patrón de error views)
- `gatic/resources/js/ui/toasts.js` (render de `errorId`)
- `gatic/vendor/livewire/livewire/dist/livewire.js` (hook request fail)
- `git log -5 --oneline` (inteligencia de cambios recientes)
- `docker compose -f gatic/compose.yaml up -d` (entorno de pruebas)
- `docker compose -f gatic/compose.yaml exec -T laravel.test php artisan test` (suite de tests)
- `docker compose -f gatic/compose.yaml exec -T laravel.test ./vendor/bin/pint --test` (Pint)
- `docker compose -f gatic/compose.yaml exec -T laravel.test ./vendor/bin/phpstan analyse --no-progress --memory-limit=1G` (Larastan)

### Completion Notes List

- Story derivada de `_bmad-output/implementation-artifacts/sprint-status.yaml`: `1-10-manejo-de-errores-en-produccion-con-id-detalle-solo-admin`.
- Requisitos extraídos de `_bmad-output/project-planning-artifacts/epics.md` (Story 1.10) y alineados a NFR10.
- Guardrails alineados a `_bmad-output/architecture.md` y `docsBmad/project-context.md`.
- Plan incluye handling para web + Livewire sin dependencias nuevas.
- Implementado `error_reports` (migración + modelo) y servicio best-effort con redaction/allowlist.
- Manejo global de excepciones en prod: vista 500 + JSON para Livewire con `error_id`.
- UI reusable: `<x-ui.error-alert-with-id />` + botón "Copiar" (sin dependencias nuevas).
- Livewire: interceptor de fallos (status >= 500) que evita modal por defecto cuando existe `error_id` y muestra toast con ID.
- Admin: pantalla de lookup por `error_id` con RBAC server-side (`can:admin-only`).
- Tests agregados para persistencia, RBAC y rendering/JSON en modo producción simulado.
- (Opcional) Playwright smoke no ejecutado; cobertura equivalente vía tests + comportamiento JS condicionado a `error_id`.

### Change Log

- Agregado sistema de reporte de errores en producción con `error_id` (persistencia + UI + Livewire + pantalla Admin).
- Ajustes post-review: `abort(500)` reportable, redaction/higiene, comando de retención y tests adicionales.

### File List

- `_bmad-output/implementation-artifacts/1-10-manejo-de-errores-en-produccion-con-id-detalle-solo-admin.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `gatic/app/Livewire/Admin/ErrorReports/ErrorReportsLookup.php`
- `gatic/app/Livewire/Dev/LivewireSmokeTest.php`
- `gatic/app/Models/ErrorReport.php`
- `gatic/app/Support/Errors/ErrorReporter.php`
- `gatic/bootstrap/app.php`
- `gatic/config/gatic.php`
- `gatic/database/migrations/2025_12_31_000000_create_error_reports_table.php`
- `gatic/docs/ui-patterns.md`
- `gatic/resources/js/app.js`
- `gatic/resources/js/ui/copy-to-clipboard.js`
- `gatic/resources/js/ui/livewire-error-handling.js`
- `gatic/resources/views/components/ui/error-alert-with-id.blade.php`
- `gatic/resources/views/errors/500.blade.php`
- `gatic/resources/views/livewire/admin/error-reports/error-reports-lookup.blade.php`
- `gatic/routes/console.php`
- `gatic/routes/web.php`
- `gatic/tests/Feature/ErrorReports/ErrorReportPersistenceTest.php`
- `gatic/tests/Feature/ErrorReports/ErrorReportsAuthorizationTest.php`
- `gatic/tests/Feature/ErrorReports/ProductionUnhandledExceptionTest.php`
