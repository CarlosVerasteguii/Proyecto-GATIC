# Story 1.5: Livewire 3 instalado e integrado en el layout

Status: done

Story Key: 1-5-livewire-3-instalado-e-integrado-en-el-layout  
Tracking: _bmad-output/implementation-artifacts/sprint-status.yaml  
Gate: 0 (Repo listo)  
GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/12  
Epic: 1 (Acceso seguro y administración de usuarios)  
Fuentes: _bmad-output/project-planning-artifacts/epics.md, _bmad-output/implementation-artifacts/epics-github.md, docsBmad/gates-execution.md, _bmad-output/architecture.md, docsBmad/project-context.md, project-context.md, https://livewire.laravel.com/docs/3.x/installation

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a desarrollador del proyecto,
I want contar con Livewire 3 instalado y configurado en el layout,
so that pueda implementar pantallas reactivas (polling/acciones) sin complejidad extra (NFR3, Arquitectura).

## Acceptance Criteria

1. **Livewire 3 instalado (Composer)**
   - **Given** el proyecto Laravel en `gatic/`
   - **When** se instala Livewire 3 via Composer
   - **Then** `livewire/livewire` queda agregado en `gatic/composer.json` y `gatic/composer.lock`
   - **And** el autoload/`php artisan` funciona sin errores

2. **Assets/scripts de Livewire incluidos en layouts base**
   - **Given** los layouts `gatic/resources/views/layouts/app.blade.php` y `gatic/resources/views/layouts/guest.blade.php`
   - **When** se renderiza cualquier vista que incluya un componente Livewire
   - **Then** el HTML incluye `@livewireStyles` en `<head>`
   - **And** incluye `@livewireScripts` antes del cierre de `</body>` (y antes de `@stack('scripts')` si aplica)

3. **Componente mínimo de prueba (smoke) renderiza e interactúa**
   - **Given** un usuario autenticado
   - **When** visita una pantalla de prueba que renderiza un componente Livewire mínimo
   - **Then** la página carga sin errores (HTTP 200) y sin excepciones en logs
   - **And** una interacción simple (ej. contador “+1” con `wire:click`) funciona

4. **Sin conflictos con Bootstrap + Vite**
   - **Given** Bootstrap 5 y Vite ya configurados (Story 1.4)
   - **When** se compilan assets (`npm run build` / `./vendor/bin/sail npm run build`)
   - **Then** el build termina sin errores
   - **And** la UI de prueba usa clases Bootstrap sin romper estilos ni JS (sin errores en consola)

## Tasks / Subtasks

- [x] 1) Instalar Livewire 3 (AC: 1)
  - [x] En `gatic/`, ejecutar `composer require livewire/livewire:^3.0`
  - [x] Verificar que se actualizan `gatic/composer.json` y `gatic/composer.lock`
  - [x] Verificar `php artisan about` / `php artisan list` sin errores

- [x] 2) Integrar Livewire en layouts base (AC: 2)
  - [x] En `gatic/resources/views/layouts/app.blade.php`:
    - [x] Agregar `@livewireStyles` en `<head>`
    - [x] Agregar `@livewireScripts` antes de `@stack('scripts')` (si no existe, antes de `</body>`)
  - [x] En `gatic/resources/views/layouts/guest.blade.php`:
    - [x] Agregar `@livewireStyles` en `<head>`
    - [x] Agregar `@livewireScripts` antes de `</body>`

- [x] 3) Crear componente smoke (mínimo) y exponerlo para validación (AC: 3, 4)
  - [x] Crear componente `App\\Livewire\\Dev\\LivewireSmokeTest` (ej. `php artisan make:livewire Dev/LivewireSmokeTest`)
  - [x] Vista con contador + botón (`wire:click`) usando clases Bootstrap (ej. `.btn.btn-primary`)
  - [x] Agregar ruta protegida por `auth` (ej. `GET /dev/livewire-smoke` → componente) en `gatic/routes/web.php`

- [x] 4) Testing + smoke checks (AC: 1-4)
  - [x] Feature test: un usuario autenticado puede cargar `/dev/livewire-smoke` (HTTP 200)
  - [x] (Opcional) Livewire test del componente (render + interacción)
  - [x] `cd gatic && npm run build` (o Sail) sin errores

### Review Follow-ups (AI)

- [x] [AI-Review][HIGH] Aislar cambios por story: el set de cambios quedo acotado a esta story (archivos listados en `git diff --name-only`) y se actualizo la documentacion de la story.
- [x] [AI-Review][HIGH] Arreglar tests de integracion de layouts: corregidos asserts/orden en `gatic/tests/Feature/LivewireLayoutIntegrationTest.php` y verificado con `php artisan test` (OK).
- [x] [AI-Review][HIGH] Alinear baseline de PHP con arquitectura: se fijo `config.platform.php=8.2.0` en `gatic/composer.json` y se regenero `gatic/composer.lock` para evitar dependencias `>=8.4`.
- [x] [AI-Review][MEDIUM] Restringir `/dev/livewire-smoke`: ruta limitada a entornos `local`/`testing` en `gatic/routes/web.php`.
- [x] [AI-Review][MEDIUM] Completar Dev Agent Record: `File List` y logs actualizados tras aplicar fixes.

## Dev Notes

### Contexto actual (repo)

- La app Laravel vive en `gatic/`; la raíz del repo se reserva para BMAD/docs/artefactos.
- Breeze (Blade) + Bootstrap 5 ya están integrados (sin Tailwind).
- Layouts actuales: `gatic/resources/views/layouts/app.blade.php` y `gatic/resources/views/layouts/guest.blade.php` (usan `@vite(...)`).
- Livewire 3 aún NO está instalado en `gatic/composer.json`.

### Objetivo (Gate 0)

- Dejar Livewire 3 listo para usar en pantallas futuras (polling/acciones) sin introducir SPA/WebSockets.
- Integrar Livewire en los layouts base y verificar con un componente mínimo (smoke) que renderice e interactúe.

### Alcance / No-alcance (para evitar scope creep)

- En alcance: instalación + integración en layout + componente mínimo de prueba + verificación básica + (opcional) test mínimo.
- Fuera de alcance: refactor de pantallas a “route → Livewire”, patrón reusable de polling (Story 1.11), RBAC/Policies (Story 1.6), componentes UX (Story 1.9).

### Guardrails técnicos (MUST)

- No introducir Tailwind ni frameworks UI adicionales.
- No “importar Livewire” por Vite/ESM salvo que haya una razón explícita; usar integración estándar con `@livewireStyles`/`@livewireScripts`.
- No dejar los scripts de Livewire después de `@stack('scripts')` si hay scripts que dependan de Livewire.

### Requisitos técnicos (hard rules)

- Stack objetivo: Laravel 11 + PHP 8.2+ + Blade + Livewire 3 + Bootstrap 5 (ver `project-context.md` y `_bmad-output/architecture.md`).
- Livewire: usar versión estable 3.x (evitar betas) y seguir instalación oficial.
- Sin WebSockets: este baseline es para polling futuro (NFR3), no para Echo/Pusher.
- Identificadores de código/rutas en inglés; copy/UI en español (regla bible).

### Cumplimiento de arquitectura (qué NO romper)

- UI target: MPA con Blade + Livewire (no SPA). Livewire será la unidad principal de pantallas más adelante.
- Para esta story: NO refactorizar pantallas existentes; solo habilitar Livewire y demostrarlo con un smoke component.
- Mantener controllers solo para bordes (descargas/JSON puntual) cuando lleguen; la interacción UI futura será Livewire.

### Librerías / herramientas (requisitos)

- Instalar Livewire 3 (oficial): `cd gatic && composer require livewire/livewire:^3.0`.
- Layout wiring (oficial):
  - Incluir `@livewireStyles` en `<head>` (idealmente después de `@vite(...)`).
  - Incluir `@livewireScripts` antes de cerrar `</body>` (y antes de `@stack('scripts')` si existe).
- Nota: `@livewireScriptConfig` solo aplica si se decide usar ESM/manual script loading; por defecto NO es necesario.
- Validación (opcional): usar `_bmad/core/tasks/validate-workflow.xml` con `_bmad/bmm/workflows/4-implementation/create-story/checklist.md`.

### Requisitos de estructura de archivos (paths + naming)

- Dependencias:
  - `gatic/composer.json`, `gatic/composer.lock`
- Layouts:
  - `gatic/resources/views/layouts/app.blade.php`
  - `gatic/resources/views/layouts/guest.blade.php`
- Smoke component (nombres en inglés):
  - Clase: `gatic/app/Livewire/Dev/LivewireSmokeTest.php`
  - Vista: `gatic/resources/views/livewire/dev/livewire-smoke-test.blade.php`
- Ruta de prueba (solo auth): `gatic/routes/web.php` (ej. `GET /dev/livewire-smoke` con nombre `dev.livewire-smoke`)

### Testing (requisitos)

- Test mínimo (recomendado):
  - Feature: un usuario autenticado puede cargar la ruta de smoke (HTTP 200).
  - (Opcional) Livewire test: `Livewire::test(LivewireSmokeTest::class)` renderiza y permite interacción simple.
- Verificación manual:
  - `cd gatic && php artisan serve` (o Sail) y abrir la ruta de smoke.
  - Confirmar que el botón/contador funciona y no hay errores en consola.
- Build check: `cd gatic && npm run build` (o `./vendor/bin/sail npm run build`) sin errores.

### Inteligencia de story previa (Story 1.4)

- La base UI usa Bootstrap + Vite; no tocar entrypoints salvo que sea necesario (`resources/sass/app.scss`, `resources/js/app.js`).
- El layout autenticado ya expone `@stack('styles')` y `@stack('scripts')`:
  - Colocar Livewire en el orden correcto para no romper scripts apilados.
- Mantener el criterio: identificadores en inglés; copy/UI en español.

### Git intelligence (reciente)

- El scaffolding de layouts/auth fue introducido en Gate 0 (commit `3a21bdd`): ahí están `layouts/app.blade.php`, `layouts/guest.blade.php`, Vite y Bootstrap. Esta story debe extender esa base sin reestructurarla.

### Info técnica actual (para evitar decisiones desactualizadas)

- Livewire 3 (docs oficiales v3.x):
  - Instalación: `composer require livewire/livewire`
  - Wiring en layout: `@livewireStyles` (head) + `@livewireScripts` (body)
  - Alternativa avanzada: `@livewireScriptConfig` solo si se usa carga ESM/manual (no requerida para este baseline).

### Project Structure Notes

- La app vive en `gatic/` (no mover). La raíz del repo es solo para artefactos BMAD/documentación.
- Livewire:
  - Componentes en `gatic/app/Livewire/**` (nombres/clases en inglés).
  - Vistas en `gatic/resources/views/livewire/**`.
- Rutas:
  - Paths en inglés (`/dev/livewire-smoke`), nombres `dot.case` (`dev.livewire-smoke`).

### References

- Backlog (fuente de verdad): `_bmad-output/project-planning-artifacts/epics.md` (Story 1.5)
- Gate 0: `docsBmad/gates-execution.md` (incluye #12 `G0-T08` Instalar Livewire 3)
- GitHub issue: `_bmad-output/implementation-artifacts/epics-github.md` (Issue #12) + https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/12
- Arquitectura: `_bmad-output/architecture.md` (stack objetivo Blade + Livewire 3 + Bootstrap 5; UI MPA)
- Reglas críticas: `docsBmad/project-context.md` + `project-context.md`
- Docs Livewire 3: https://livewire.laravel.com/docs/3.x/installation

## Story Completion Status

- Status: **done**
- Completion note: Livewire 3 integrado y validado (tests + Pint + build). Hallazgos HIGH/MEDIUM resueltos.

## Senior Developer Review (AI)

Fecha: 2025-12-29

### Resumen

- Outcome: APPROVE (sin HIGH/MEDIUM pendientes)
- Issues fixed: 3 High, 2 Medium

### Hallazgos (resumen)

**HIGH**
- RESUELTO: Baseline de PHP inconsistente: `gatic/composer.json` (PHP ^8.2) vs `gatic/composer.lock` (paquetes que requieren >=8.4).
- RESUELTO: Tests de layout fallando por asserts invertidos en `gatic/tests/Feature/LivewireLayoutIntegrationTest.php`.
- RESUELTO: Scope/mezcla de cambios: se acoto el set de cambios y se actualizo la documentacion de la story.

**MEDIUM**
- RESUELTO: Endpoint `/dev/livewire-smoke` no esta limitado a entorno; queda accesible para cualquier usuario autenticado.
- RESUELTO: Documentacion incompleta en Dev Agent Record (File List vs `git status`).

**LOW**
- Detalles de calidad/robustez en tipado (`render()`) y tests (asserts mas estables).

### Fixes aplicados

- `gatic/tests/Feature/LivewireLayoutIntegrationTest.php`: asserts corregidos + suite en verde.
- `gatic/composer.json`, `gatic/composer.lock`: lock regenerado para PHP 8.2 (sin dependencias `>=8.4`).
- `gatic/routes/web.php`: ruta `/dev/livewire-smoke` solo en `local`/`testing`.

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `docker exec gatic-laravel.test-1 composer update --no-interaction` (OK; lock regenerado para PHP 8.2)
- `docker exec gatic-laravel.test-1 php artisan test` (OK)
- `docker exec gatic-laravel.test-1 ./vendor/bin/pint --test` (OK)
- `cd gatic && npm run build` (OK; warnings Sass `@import` de Bootstrap)

### Completion Notes List

- Story creada desde el primer backlog en `sprint-status.yaml` (key `1-5-*`).
- Enlazada a Issue #12 (Gate 0) y alineada a `project-context.md` + `_bmad-output/architecture.md`.
- Livewire 3 instalado (Composer) y suite de tests pasando en Sail.
- Test legacy actualizado para reflejar redirect de `/`.
- Layouts base cableados con `@livewireStyles`/`@livewireScripts` (orden correcto con `@stack('scripts')`).
- Página de smoke `/dev/livewire-smoke` creada (auth) con componente Livewire mínimo (contador + `wire:click`).
- Tests agregados: feature (ruta) + Livewire (interacción) y build Vite OK.
- Baseline PHP alineado: `config.platform.php=8.2.0` en `gatic/composer.json` + `gatic/composer.lock` regenerado (sin dependencias `>=8.4`).
- Ruta dev restringida a `local`/`testing` (evita exponer endpoints de smoke en prod).

### File List

- `_bmad-output/implementation-artifacts/1-5-livewire-3-instalado-e-integrado-en-el-layout.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `_bmad-output/implementation-artifacts/validation-report-2025-12-28T234321Z.md`
- `gatic/app/Livewire/Dev/LivewireSmokeTest.php`
- `gatic/composer.json`
- `gatic/composer.lock`
- `gatic/resources/views/layouts/app.blade.php`
- `gatic/resources/views/layouts/guest.blade.php`
- `gatic/resources/views/livewire/dev/livewire-smoke-test.blade.php`
- `gatic/routes/web.php`
- `gatic/tests/Feature/Dev/LivewireSmokeComponentTest.php`
- `gatic/tests/Feature/Dev/LivewireSmokePageTest.php`
- `gatic/tests/Feature/ExampleTest.php`
- `gatic/tests/Feature/HomeRedirectTest.php`
- `gatic/tests/Feature/LivewireInstallationTest.php`
- `gatic/tests/Feature/LivewireLayoutIntegrationTest.php`

### Change Log

- Added Livewire 3 dependency (Composer).
- Wired Livewire assets into base layouts (app/guest) and enabled slot rendering for Livewire page components.
- Added `/dev/livewire-smoke` authenticated smoke page + minimal counter component.
- Added feature + Livewire component tests and ensured Vite build + Pint + PHPUnit pass.
- 2025-12-29: Senior Developer Review (AI) - fixes aplicados (tests/layout, lock PHP 8.2, restriccion de ruta dev) y suite validada; story pasa a done.
- 2025-12-29: Hardening - `render(): View` tipado en componente y smoke test menos fragil (assert por copy estable).
