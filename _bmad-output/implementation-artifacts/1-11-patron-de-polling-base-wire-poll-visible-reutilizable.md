# Story 1.11: Patrón de polling base (wire:poll.visible) reutilizable

Status: done

Story Key: 1-11-patron-de-polling-base-wire-poll-visible-reutilizable  
Tracking: _bmad-output/implementation-artifacts/sprint-status.yaml  
Gate: 1 (UX base + navegación)  
Epic: 1 (Acceso seguro y administración de usuarios)  
Fuentes: _bmad-output/project-planning-artifacts/epics.md, _bmad-output/prd.md, _bmad-output/architecture.md, docsBmad/project-context.md, project-context.md, gatic/docs/ui-patterns.md, _bmad-output/implementation-artifacts/1-5-livewire-3-instalado-e-integrado-en-el-layout.md, _bmad-output/implementation-artifacts/1-9-componentes-ux-reutilizables-toasts-loaders-cancelar-actualizado-hace-xs.md

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a desarrollador del proyecto,
I want contar con un patr¢n **reutilizable y consistente** para polling con `wire:poll.visible` (con intervalos configurables),
so that podamos mantener estados/badges/m‚tricas/locks actualizados **sin WebSockets**, con UX predecible y sin sobrecargar la app (NFR3).

## Acceptance Criteria

1. **Patr¢n est ndar y reusable**
   - **Given** una pantalla que requiera datos "near-real-time" (badges/locks/m‚tricas)
   - **When** el dev implementa polling
   - **Then** usa un patr¢n est ndar (componente Blade o snippet documentado) que genera `wire:poll.visible.<interval>s`
   - **And** el intervalo se obtiene de `config('gatic.ui.polling.*')` (sin hardcode en vistas)

2. **Polling solo cuando visible (sin WebSockets)**
   - **Given** una pesta¤a/elemento no visible
   - **When** se usa polling
   - **Then** el polling se detiene con `wire:poll.visible`
   - **And** NO se agrega WebSockets, SSE, Echo/Pusher u otro mecanismo (regla no negociable)

3. **Intervalos alineados a la "bible"**
   - **Given** los NFR/decisiones de `docsBmad/project-context.md`
   - **Then** existen defaults configurables:
     - listas/badges: ~15s
     - m‚tricas dashboard: ~60s
     - heartbeat locks: ~10s

4. **Integraci¢n con "Actualizado hace Xs"**
   - **Given** una vista con polling
   - **When** llega data nueva por poll
   - **Then** se actualiza `updatedAt` y el UI muestra `<x-ui.freshness-indicator />` sin spamear toasts (ver `gatic/docs/ui-patterns.md`)

5. **No interfiere con "operaci¢n lenta (>3s) + Cancelar"**
   - **Given** un componente con polling y tambi‚n acciones manuales (ej. `search`, `save`)
   - **When** se usa el overlay `<x-ui.long-request target="...">`
   - **Then** el overlay NO se dispara por el polling (solo por el/los m‚todos target)

6. **Demo verificable + tests m¡nimos**
   - **Given** el smoke page existente `/dev/livewire-smoke` (solo `local/testing`)
   - **When** se actualiza al patr¢n nuevo
   - **Then** hay una demo verificable del polling + freshness y un test que asegura que la marca `wire:poll.visible.<interval>s` existe en el HTML.

## Tasks / Subtasks

- [x] 1) Agregar configuraci¢n de polling (AC: 1, 3)
  - [x] En `gatic/config/gatic.php`, agregar `ui.polling` con intervalos (segundos) para:
    - [x] `badges_interval_s` (15)
    - [x] `metrics_interval_s` (60)
    - [x] `locks_heartbeat_interval_s` (10)
    - [x] (Opcional) `enabled` (bool, default true) para permitir apagar polling en entornos especiales

- [x] 2) Implementar el patr¢n reusable (AC: 1, 2)
  - [x] Crear componente Blade wrapper (recomendado) en `gatic/resources/views/components/ui/poll.blade.php` que reciba:
    - [x] `method` (string, requerido)
    - [x] `intervalS` (int, default desde config)
    - [x] `visible` (bool, default true) para usar `wire:poll.visible`
  - [x] Alternativa aceptable (si el wrapper complica): documentar snippet can¢nico y refactorizar vistas para evitar hardcode (N/A: se implement¢ el wrapper recomendado)

- [x] 3) Integrar con freshness indicator (AC: 4)
  - [x] En componentes que usen polling, actualizar `lastUpdatedAtIso` (ISO8601) dentro del m‚todo de poll
  - [x] Renderizar `<x-ui.freshness-indicator :updated-at="$lastUpdatedAtIso" />`

- [x] 4) Hardening: no activar overlay por polling (AC: 5)
  - [x] Si el componente usa `<x-ui.long-request />`, asegurar que se pasa `target="..."` para excluir el m‚todo de poll

- [x] 5) Demo + tests (AC: 6)
  - [x] Actualizar `gatic/resources/views/livewire/dev/livewire-smoke-test.blade.php` para que el poll use el intervalo definido (y no hardcode `5s`)
  - [x] Test Feature: `/dev/livewire-smoke` contiene `wire:poll.visible.` y el intervalo esperado
  - [x] (Opcional) Test Livewire: invocar el m‚todo de poll y assert que incrementa contador + actualiza `lastUpdatedAtIso`

## Dev Notes

### Developer Context (qu‚ existe hoy y qu‚ falta)

- Stack: Laravel 11 + Livewire 3 + Blade + Bootstrap 5, **sin WebSockets** (ver `docsBmad/project-context.md`, `project-context.md`, `_bmad-output/architecture.md`).
- Ya existe el indicador reutilizable de "Actualizado hace Xs": `gatic/resources/views/components/ui/freshness-indicator.blade.php` + JS asociado (documentado en `gatic/docs/ui-patterns.md`).
- Ya existe una demo de polling en `/dev/livewire-smoke`:
  - Vista: `gatic/resources/views/livewire/dev/livewire-smoke-test.blade.php` usa `wire:poll.visible.5s="pollTick"`.
  - Componente: `gatic/app/Livewire/Dev/LivewireSmokeTest.php` implementa `pollTick()` y actualiza `lastUpdatedAtIso`.
- Gap actual: los intervalos est n **hardcode** (ej. `5s`) y no hay un patr¢n "can¢nico" reusable que gu¡e a los devs para:
  - elegir intervalos correctos (15s/60s/10s seg£n NFR3),
  - evitar sobrecarga por polling demasiado frecuente,
  - integrar consistentemente con el indicador de freshness,
  - evitar que el overlay de "operaci¢n lenta" se dispare por el polling.

### Objetivo de esta story

Estandarizar el polling para que cualquier m¢dulo (inventario, dashboard, locks) implemente `wire:poll.visible` **igual**, con intervalos centralizados y sin efectos secundarios inesperados.

### Technical Requirements (DEV AGENT GUARDRAILS)

- NO agregar WebSockets/SSE/Echo/Pusher ni dependencias nuevas para "tiempo real" (regla cr¡tica).
- Polling debe ser **visible-aware** (`wire:poll.visible`) para reducir carga cuando la pesta¤a o el elemento no est  visible.
- Evitar side effects en poll: el m‚todo de polling debe ser idempotente y **solo** refrescar datos/contadores necesarios.
- No hardcodear intervalos en Blade: centralizar en `config('gatic.ui.polling.*')`.
- Si se usa overlay cancelable (`<x-ui.long-request />`), siempre limitar con `target="..."` para que polling no lo active.

Snippet can¢nico (si NO se usa wrapper):

```blade
@php($interval = config('gatic.ui.polling.badges_interval_s'))
<div wire:poll.visible.{{ $interval }}s="pollTick">
    ...
</div>
```

### Architecture Compliance

- Alineado a `_bmad-output/architecture.md`:
  - "Near-real-time" sin WebSockets se implementa con `wire:poll.visible`.
  - Timeouts/defaults deben centralizarse en `config/gatic.php` (evitar "magic numbers").
- Alineado a `docsBmad/project-context.md` y `project-context.md`:
  - badges/listas ~15s, m‚tricas ~60s, locks heartbeat ~10s.

### Library / Framework Requirements (NO inventar)

- Laravel: `laravel/framework:^11.31` (`gatic/composer.json`)
- Livewire: `livewire/livewire:^3.0` (usar docs 3.x; `wire:poll.visible.<interval>s`)
- UI: Bootstrap `^5.2.3` (`gatic/package.json`) + Blade
- Tooling: Vite `^6.0.11`
- PHP: `^8.2` (`gatic/composer.json` + `config.platform.php=8.2.0`)

### Testing Requirements

- Agregar/ajustar tests Feature para asegurar que el HTML renderiza el atributo de polling esperado:
  - Request a `/dev/livewire-smoke` (solo `local/testing`) y assert que incluye `wire:poll.visible.` y el intervalo esperado (derivado de config).
- Tests deben ser deterministas (sin dependencias externas).
- Ejecutar suite recomendada en Sail (evitar PHP 8.0 de XAMPP; ver `project-context.md`):
  - `cd gatic && vendor\\bin\\sail artisan test`

### Previous Story Intelligence (no repetir errores)

- Story 1.5 (Livewire): Livewire ya est  integrado en layouts base; mantener `@livewireStyles/@livewireScripts` en el orden correcto.
- Story 1.9 (UX components): ya existe patr¢n de "Actualizado hace Xs" y overlay cancelable con `target`; **no** inventar otro patr¢n de polling ni otro indicador de freshness.
- Story 1.8 (layout/nav): endpoints `/dev/*` deben quedar limitados a `local/testing` (no exponer demos en prod).
- Story 1.10 (errores con `error_id`): acciones Livewire deben mantener feedback consistente ante fallos; el polling no debe introducir spam ni errores ruidosos.

### Git Intelligence Summary (lo m s reciente relevante)

- `feat(gate-1): implement reusable UX components` (Story 1.9): existen `freshness-indicator` y docs `gatic/docs/ui-patterns.md` (reusar).
- `feat(gate-1): implement production error handling with ID` (Story 1.10): no romper hooks/JS Livewire al agregar markup de polling.

### Latest Tech Information (Livewire 3 polling)

- Livewire 3 ofrece `wire:poll` para refrescar un componente peri¢dicamente; por defecto el interval es ~2.5s si no se especifica.
- Se puede especificar intervalo como modificador: `wire:poll.15s`, `wire:poll.750ms`.
- Se puede ejecutar un m‚todo en lugar de refrescar todo: `wire:poll.15s="refreshBadges"`.
- Para no ejecutar si el elemento no est  visible: `wire:poll.visible.15s="..."`.
- Fuente: https://livewire.laravel.com/docs/3.x/wire-poll

### Project Context Reference (reglas que esta story NO puede violar)

- Sin WebSockets; polling con `wire:poll.visible`.
- Intervalos gu¡a: badges ~15s, m‚tricas ~60s, locks heartbeat ~10s.
- UI copy en espa¤ol; identificadores/rutas/c¢digo en ingl‚s.
- Autorizaci¢n server-side obligatoria; no exponer rutas `/dev/*` en prod.

### Project Structure Notes

- Config:
  - `gatic/config/gatic.php`: agregar `ui.polling.*` (intervalos en segundos; sin hardcode en vistas).
- Blade components (UI reusable):
  - `gatic/resources/views/components/ui/poll.blade.php` (wrapper recomendado para estandarizar el markup de polling).
  - Reusar lo existente: `gatic/resources/views/components/ui/freshness-indicator.blade.php`, `gatic/resources/views/components/ui/long-request.blade.php`.
- Docs internas:
  - `gatic/docs/ui-patterns.md`: extender con secci¢n de Polling (intervalos, snippet can¢nico, gotchas con long-request overlay).
- Demos:
  - Mantener la demo existente `/dev/livewire-smoke` (solo `local/testing`): actualizar para usar el patr¢n nuevo.
- Convenciones:
  - Identificadores/c¢digo/paths en ingl‚s; copy UI en espa¤ol (ver `project-context.md`).

### References

- Backlog (fuente de verdad): `_bmad-output/project-planning-artifacts/epics.md` (Story 1.11)
- Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml` (key `1-11-*`)
- Reglas cr¡ticas (bible): `docsBmad/project-context.md`
- Reglas lean: `project-context.md`
- Arquitectura: `_bmad-output/architecture.md` (polling sin WebSockets; defaults en `config/gatic.php`)
- UI patterns existentes: `gatic/docs/ui-patterns.md` (freshness + long-request overlay con `target`)
- Demo actual (a refactorizar a patr¢n can¢nico):
  - `gatic/resources/views/livewire/dev/livewire-smoke-test.blade.php`
  - `gatic/app/Livewire/Dev/LivewireSmokeTest.php`
- Livewire 3 docs (polling): https://livewire.laravel.com/docs/3.x/wire-poll

## Story Completion Status

- Status: **done**
- Completion note: Implementaci¢n verificada: polling reusable sin hardcode, intervalos en config, demo `/dev/livewire-smoke`, tests y validaci¢n manual con Playwright.

## Senior Developer Review (AI)

### Hallazgos (resueltos)

- [HIGH] `x-ui.poll`: boolean props (`enabled="false"`, `visible="false"`) se interpretan como truthy en PHP si llegan como string. Fix: cast robusto con `FILTER_VALIDATE_BOOL`.
- [HIGH] `x-ui.poll`: construcci¢n de atributos con `{!! !!}`. Fix: usar `$attributes->merge([...])` para render seguro.
- [HIGH] Inconsistencia de status en la story (`review` vs `ready-for-dev`). Fix: unificar a `done`.
- [MEDIUM] Faltaba test del kill-switch global `gatic.ui.polling.enabled`. Fix: agregar test.
- [MEDIUM] Documentar kill-switch global. Fix: `gatic/docs/ui-patterns.md`.

### Evidencia (tests)

- PHPUnit (Docker): `php artisan test --filter LivewireSmoke` PASS.
- Playwright (localhost): login, navegar a `/dev/livewire-smoke`, detectar `wire:poll.visible.15s="pollTick"`, validar que el contador de polls incrementa con el tiempo y que el overlay de long-request no se activa por polling.

### Questions / Clarifications (resolver antes de cerrar esta story)

1. ¨El polling se habilita globalmente por defecto (config), o solo se usa por m¢dulo cuando es necesario? (Recomendado: solo en pantallas que realmente lo requieren.)
2. ¨Intervalos exactos para Gate 1: mantenemos 15s/60s/10s como defaults o quieres un default de demo (ej. 5s) solo para smoke? (Recomendado: demo = defaults reales, no hardcode.)

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `Get-Content -Raw _bmad-output/implementation-artifacts/sprint-status.yaml` (auto-selecci¢n del primer story en backlog)
- `Get-Content -Raw _bmad-output/project-planning-artifacts/epics.md` (Story 1.11 definition)
- `Get-Content -Raw _bmad-output/architecture.md`, `Get-Content -Raw docsBmad/project-context.md`, `Get-Content -Raw project-context.md`
- `Get-Content -Raw gatic/docs/ui-patterns.md` (patrones UX existentes)
- `Get-Content -Raw gatic/resources/views/livewire/dev/livewire-smoke-test.blade.php`, `Get-Content -Raw gatic/app/Livewire/Dev/LivewireSmokeTest.php`
- `Get-Content -Raw gatic/config/gatic.php`, `Get-Content -Raw gatic/composer.json`, `Get-Content -Raw gatic/package.json`
- Web research: Livewire 3 polling docs (link en References)
- `git log -15 --oneline` (inteligencia de cambios recientes)
- `docker compose -f gatic/compose.yaml exec -T laravel.test php artisan test` (suite completa)

### Completion Notes List

- Story generada desde el primer backlog en `_bmad-output/implementation-artifacts/sprint-status.yaml` (key `1-11-*`).
- Contexto alineado a `docsBmad/project-context.md` + `_bmad-output/architecture.md`.
- Se definieron ACs y tareas para estandarizar polling: intervalos centralizados, `wire:poll.visible`, integraci¢n con freshness y no interferir con long-request overlay.

- Se agreg¢ configuraci¢n `gatic.ui.polling` (intervalos + toggle) y un wrapper `<x-ui.poll />` para estandarizar `wire:poll.visible` sin hardcode, con tests de respaldo.

## Change Log

- Agregar `gatic.ui.polling` (intervalos + toggle por env).
- Crear wrapper Blade `<x-ui.poll />` para `wire:poll.visible.<interval>s`.
- Refactorizar `/dev/livewire-smoke` a usar el wrapper y documentar el patrón en `gatic/docs/ui-patterns.md`.
- Añadir/ajustar tests para asegurar el markup de polling y el update de `lastUpdatedAtIso`.

- Code review: hardening de props booleanos + render seguro de atributos + tests del kill-switch global + validaci¢n manual con Playwright.

## File List

- `_bmad-output/implementation-artifacts/1-11-patron-de-polling-base-wire-poll-visible-reutilizable.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `gatic/config/gatic.php`
- `gatic/docs/ui-patterns.md`
- `gatic/resources/views/components/ui/poll.blade.php`
- `gatic/resources/views/livewire/dev/livewire-smoke-test.blade.php`
- `gatic/tests/Feature/Dev/LivewireSmokeComponentTest.php`
- `gatic/tests/Feature/Dev/LivewireSmokePageTest.php`
- `gatic/tests/Feature/Ui/PollComponentTest.php`
