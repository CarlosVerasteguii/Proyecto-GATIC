# Story 1.9: Componentes UX reutilizables (toasts, loaders, cancelar, "Actualizado hace Xs")

Status: done

Story Key: 1-9-componentes-ux-reutilizables-toasts-loaders-cancelar-actualizado-hace-xs
Tracking: _bmad-output/implementation-artifacts/sprint-status.yaml
Gate: 1 (UX base + navegacion)
Epic: 1 (Acceso seguro y administracion de usuarios)
GitHub (referencia): N/A (este story key proviene del backlog BMAD; ver `docsBmad/gates-execution.md`)
Fuentes: _bmad-output/project-planning-artifacts/epics.md, docsBmad/gates-execution.md, _bmad-output/prd.md, _bmad-output/architecture.md, docsBmad/project-context.md, project-context.md, 03-visual-style-guide.md, _bmad-output/project-planning-artifacts/ux-design-specification.md, _bmad-output/implementation-artifacts/sprint-status.yaml, gatic/resources/js/bootstrap.js, gatic/resources/views/layouts/app.blade.php, gatic/resources/views/livewire/admin/users/user-form.blade.php, gatic/app/Livewire/Admin/Users/UserForm.php

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a usuario interno,
I want feedback inmediato (toasts/loaders) y control en busquedas lentas,
so that el sistema sea rapido y predecible en operacion diaria (NFR1, NFR2).

## Acceptance Criteria

1. Toasts consistentes (exito/error)
   - **Given** una accion exitosa o fallida
   - **When** el sistema responde (Livewire o redirect con flash)
   - **Then** se muestra un toast consistente segun el resultado
   - **And** el toast es accesible (teclado + `role="alert"` + `aria-live`)

2. "Deshacer" para acciones reversibles (best-effort, donde aplique)
   - **Given** una accion reversible (ej. cambios de UI, toggles no destructivos, altas/bajas reversibles)
   - **When** la accion se completa
   - **Then** se muestra un toast con accion **"Deshacer"**
   - **And** la ventana para deshacer es ~10s
   - **And** si el usuario ejecuta "Deshacer" dentro de la ventana, el sistema revierte y muestra confirmacion

3. Operaciones lentas (>3s): skeleton/loader + progreso + cancelar
   - **Given** una busqueda o carga que tarda mas de 3s (NFR2)
   - **When** el usuario espera resultados
   - **Then** se muestra skeleton/loader + mensaje de progreso (sin bloquear la navegacion)
   - **And** existe una opcion de **"Cancelar"** que detiene la espera y conserva el estado anterior (sin "parpadeos" ni limpiar resultados previos)

4. Vistas con polling: "Actualizado hace Xs"
   - **Given** una vista con polling (`wire:poll.visible`)
   - **When** se actualizan los datos automaticamente
   - **Then** se muestra el indicador **"Actualizado hace Xs"**
   - **And** el contador se actualiza de forma consistente (no spamea toasts)

## Tasks / Subtasks

- [x] 1) Sistema de toasts global (AC: 1, 2)
  - [x] Renderizar contenedor global de toasts en el layout (`gatic/resources/views/layouts/app.blade.php`) con posicion fija (Bootstrap)
  - [x] Implementar componente Blade reutilizable (toast + contenedor) y estilos minimos alineados a Bootstrap/branding
  - [x] Implementar puente "redirect/flash -> toast" (ej. `session('status')`, `session('error')`) para flows no Livewire
  - [x] Implementar canal Livewire -> JS (evento `ui:toast`) para toasts desde componentes Livewire
  - [x] Soportar tipos: `success`, `error`, `info`, `warning` + `title` opcional + `message` requerido
  - [x] Soportar accion opcional en toast (boton) para "Deshacer" (payload con expira ~10s)

- [x] 2) Skeleton/loader estandar (AC: 3)
  - [x] Crear componentes Blade "skeleton" (lineas/bloques) usando placeholders de Bootstrap
  - [x] Definir patron de uso en Livewire (`wire:loading`, `wire:target`) para listas/formularios
  - [x] Asegurar que el skeleton no rompa el layout (evitar CLS fuerte)

- [x] 3) Patron Cancelar en operaciones lentas (AC: 3)
  - [x] Definir implementacion base (reutilizable) para operaciones potencialmente lentas: UI de progreso + boton Cancelar
  - [x] Implementar al menos 1 demo verificable en `gatic/app/Livewire/Dev/LivewireSmokeTest.php` (local/testing) que muestre: loader >3s + cancelar sin perder estado previo
  - [x] Documentar el patron para que el resto de modulos lo reuse (sin inventar soluciones distintas)

- [x] 4) Indicador "Actualizado hace Xs" (AC: 4)
  - [x] Crear componente Blade reutilizable para "freshness"
  - [x] Implementar actualizacion de segundos en JS (sin dependencias nuevas) y reinicio del contador cuando llegue data nueva/poll
  - [x] Implementar demo verificable en `gatic/app/Livewire/Dev/LivewireSmokeTest.php` con polling visible y freshness indicator

## Dev Notes

### Developer Context (que existe hoy y que cambia)

- Stack UI actual: Blade + Livewire 3 + Bootstrap 5 (sin WebSockets). Ver `project-context.md` y `_bmad-output/architecture.md`.
- Hoy:
  - No hay sistema de toasts global (solo alerts puntuales por `session('status')`, por ejemplo en `gatic/resources/views/livewire/admin/users/user-form.blade.php`).
  - Ya existe Bootstrap JS disponible globalmente (se importa en `gatic/resources/js/bootstrap.js` como `window.bootstrap`).
  - Ya existe un layout autenticado estable (`gatic/resources/views/layouts/app.blade.php`) y tests de layout/roles (`gatic/tests/Feature/LayoutNavigationTest.php`).
- Esta story crea "building blocks" reutilizables para que las historias siguientes no reinventen feedback UX:
  - Toasts consistentes (incluye accion opcional tipo "Deshacer")
  - Skeleton/loader estandar para cargas >3s
  - Patron "Cancelar" en operaciones lentas sin perder el estado anterior
  - Indicador de frescura "Actualizado hace Xs" para vistas con polling

### Alcance realista (evitar scope creep)

- No se redisenan pantallas existentes salvo el minimo para conectar el contenedor de toasts al layout.
- No se implementan features de dominio (inventario, movimientos, etc.) aqui; solo componentes/patrones + demo/QA.
- "Cancelar" debe ser **funcional** al menos en un flujo demo (dev-only) para validar el patron; el objetivo es que el resto de modulos lo reutilice luego.

### Guardrails (anti-errores tipicos)

- No convertir esto en un "mini framework" custom: usar Bootstrap 5 + Livewire 3 + Blade.
- No dispersar logica de UI en muchos lugares: centralizar JS de UI en modulos bajo `resources/js/ui/*`.
- No duplicar APIs de notificacion (alert + toast + modal) sin reglas: el estandar para feedback breve es toast; alerts quedan para mensajes persistentes/inline.
- No spamear toasts por polling: el indicador de frescura cubre esa necesidad.
- Mantener copia/labels en espanol, pero nombres de archivos/clases/identificadores en ingles (regla global).

### Requisitos tecnicos (DEV AGENT GUARDRAILS)

- **Fuente de UI JS:** usar Vite (`gatic/resources/js/app.js`) y el Bootstrap ya importado (`gatic/resources/js/bootstrap.js`).
- **API Livewire 3 (eventos):**
  - Desde PHP: usar `$this->dispatch('ui:toast', ...)` (no `emit`).
  - Desde JS: registrar listeners en `document.addEventListener('livewire:init', ...)` y usar `Livewire.on('ui:toast', ...)`.
- **Toasts:**
  - Deben soportar cola/stack (multiples) y autocierre.
  - Deben tener boton de cerrar y no depender de jQuery.
  - Deben permitir un boton de accion opcional (ej. "Deshacer") sin acoplarse a un caso de negocio especifico.
  - No mostrar detalles tecnicos en toasts (stack traces, SQL, etc.). Si hay `error_id`, mostrarlo como referencia.
- **Operaciones lentas (>3s):**
  - Umbral configurable (idealmente centralizado en `gatic/config/gatic.php`).
  - "Cancelar" debe mantener los resultados/estado anterior (no limpiar tabla/lista si el usuario cancela).
- **Freshness indicator:**
  - Debe actualizarse cada segundo (client-side) sin drift notable.
  - Debe reiniciarse a "0s" cuando haya actualizacion real de datos (poll o refresh manual), sin disparar toasts.

### Cumplimiento de arquitectura (no negociable)

- Livewire es la unidad principal (route -> componente). Controllers solo en "bordes" (JSON/dev endpoints si se justifica).
- Sin "helpers globales" nuevos; si hace falta, usar `app/Support/*` o traits/reusables bien ubicados.
- Mantener convenciones del repo: nombres en ingles (codigo/rutas/DB) y copy UI en espanol.
- Mantener compatibilidad con el layout y tests existentes del Gate 0/1 (no romper `LayoutNavigationTest`).

### Librerias/framework (no inventar stack)

- Bootstrap 5: usar el componente oficial de Toast y placeholders (skeleton) cuando aplique.
- Livewire 3: eventos (`dispatch`/`Livewire.on`) y hooks (`Livewire.hook('request', ...)`) solo si son necesarios.
- Axios ya esta disponible; no agregar dependencias nuevas para toasts/skeleton/freshness.

### Testing requirements (minimo)

- Feature test: `/dashboard` incluye contenedor de toasts (marcador estable tipo `data-testid="app-toasts"`).
- Feature test: un flash de sesion (`with('status', ...)` o `with('error', ...)`) renderiza markup de toast listo para mostrarse.
- Livewire test: un componente que usa el helper/trait de toasts debe `assertDispatched('ui:toast', ...)`.
- QA manual (local): en `/dev/livewire-smoke` validar toasts, loader >3s, cancelar, y "Actualizado hace Xs".

### Previous Story Intelligence (Story 1.8)

- Gate 1 ya establecio un layout consistente (sidebar/topbar) y un baseline de tests de UI/roles:
  - Layout base: `gatic/resources/views/layouts/app.blade.php` incluye `@vite(...)` + `@livewireStyles/@livewireScripts`.
  - Navegacion: `gatic/resources/views/layouts/navigation.blade.php` (compone sidebar/topbar).
  - Tests: `gatic/tests/Feature/LayoutNavigationTest.php` valida marcadores `data-testid` en sidebar/topbar.
- Implicacion para esta story:
  - El contenedor de toasts debe vivir en el layout (no dentro de un componente puntual) para que funcione en toda la app.
  - Mantener marcadores estables (data-testid) para tests y evitar regresiones.
  - No introducir dependencias nuevas ni cambiar estructura del layout salvo el minimo.

### Git intelligence summary (patrones recientes)

- Convencion de commits: `feat(gate-1): ... (Story 1.8)` y `feat(gate-0): ... (Story 1.7)` (mantener consistencia).
- Cambios recientes relevantes:
  - Story 1.8 implemento layout y tests; esta story debe extender sin romper.
  - Livewire 3 y tooling de calidad ya estan instalados (Pint/PHPUnit/Larastan).

### Latest Tech Information (para evitar errores de implementacion)

- Livewire 3 (eventos):
  - PHP: `$this->dispatch('ui:toast', type: 'success', title: 'Listo', message: '...')`
  - JS: `document.addEventListener('livewire:init', () => { Livewire.on('ui:toast', (payload) => { ... }) })`
  - JS -> Livewire: `Livewire.dispatch('some-event', { ... })` (si se necesita para acciones tipo "Deshacer").
- Livewire 3 (JS hooks): se puede usar `Livewire.hook('request', ...)` para instrumentar requests y activar UI de "operacion lenta" (>3s).
- Bootstrap 5: el JS ya esta disponible como `window.bootstrap` (ver `gatic/resources/js/bootstrap.js`); usar `new bootstrap.Toast(element, options)` para mostrar.

### Implementation Blueprint (para que sea imposible hacerlo mal)

- Contrato de evento recomendado (Livewire -> JS) para toast:
  - Evento: `ui:toast`
  - Payload:
    - `type`: `success|error|info|warning`
    - `title` (opcional)
    - `message` (requerido, texto en espanol)
    - `timeoutMs` (opcional; default 5000; si hay accion tipo "Deshacer", default 10000)
    - `errorId` (opcional; si existe, mostrar como "ID: XXXXX")
    - `action` (opcional):
      - `label` (ej. "Deshacer")
      - `event` (ej. `ui:undo`)
      - `params` (objeto serializable)
- Contrato recomendado para "freshness":
  - Un componente que hace polling debe actualizar una marca de tiempo "last updated at" y la UI muestra "Actualizado hace Xs".
  - Evitar toasts para polling; el indicador de frescura cubre la necesidad de confianza del dato.
- Demo dev-only obligatorio (para QA y para documentar el patron):
  - En `/dev/livewire-smoke`, agregar botones para disparar:
    - Toast success/error (Livewire)
    - Toast con accion "Deshacer" (Livewire dispatch desde JS)
    - Operacion lenta simulada (>3s) que muestre skeleton + progreso + cancelar sin perder estado previo
    - Un bloque con polling + "Actualizado hace Xs" reiniciando correctamente

### Project Structure Notes

- Ubicacion recomendada (reutilizable y consistente):
  - Blade components:
    - `gatic/resources/views/components/ui/toast-container.blade.php` (marker `data-testid="app-toasts"`)
    - `gatic/resources/views/components/ui/toast.blade.php`
    - `gatic/resources/views/components/ui/skeleton.blade.php`
    - `gatic/resources/views/components/ui/freshness-indicator.blade.php`
  - JS (Vite):
    - `gatic/resources/js/ui/toasts.js` (creacion/stack de toasts + accion opcional)
    - `gatic/resources/js/ui/freshness.js` (contador "Actualizado hace Xs")
    - `gatic/resources/js/ui/long-request.js` (umbral >3s + UI de progreso/cancelacion, si aplica)
    - Importar modulos desde `gatic/resources/js/app.js`
  - PHP (Livewire helpers):
    - `gatic/app/Livewire/Concerns/InteractsWithToasts.php` (helpers `toastSuccess/toastError/...` que hacen `$this->dispatch('ui:toast', ...)`)
  - Config (centralizar constantes UX):
    - `gatic/config/gatic.php` (ej. `ui.toast.default_delay_ms`, `ui.toast.undo_delay_ms`, `ui.long_request_threshold_ms`)
  - Tests:
    - Extender `gatic/tests/Feature/LayoutNavigationTest.php` o crear `gatic/tests/Feature/UiComponentsTest.php`
  - Demo/QA (solo local/testing):
    - Extender `gatic/app/Livewire/Dev/LivewireSmokeTest.php` y su view `gatic/resources/views/livewire/dev/livewire-smoke-test.blade.php`
      para demostrar: toast success/error, toast con "Deshacer", loader >3s + cancelar, y freshness con polling.

### References

- Backlog fuente de verdad (Story 1.9): `_bmad-output/project-planning-artifacts/epics.md`
- Gate 1 (alcance de componentes UX): `docsBmad/gates-execution.md`
- NFRs de UX fluida y >3s con cancelacion: `_bmad-output/prd.md`
- Stack y patrones (Livewire-first, polling, loaders, "Actualizado hace Xs", estructura sugerida): `_bmad-output/architecture.md`
- Reglas criticas (idioma/codigo, Livewire-first, sin WebSockets, polling, etc.): `docsBmad/project-context.md`, `project-context.md`
- UX system/patrones (toasts, estados loading, freshness indicator): `_bmad-output/project-planning-artifacts/ux-design-specification.md`
- Branding/ejemplo de toast Bootstrap (referencia visual): `03-visual-style-guide.md`
- Estado actual del JS (Bootstrap disponible globalmente): `gatic/resources/js/bootstrap.js`
- Layout global (lugar para contenedor de toasts): `gatic/resources/views/layouts/app.blade.php`
- Ejemplo de feedback actual (alert por sesion): `gatic/resources/views/livewire/admin/users/user-form.blade.php`

## Story Completion Status

- Status: **done**
- Completion note: ACs implementados y verificados (toasts/undo, skeletons, cancelar >3s sin perder estado, freshness con polling) + regresión OK.

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- En Windows, el `php` en PATH es `C:\\xampp\\php\\php.exe` (PHP 8.0.30) y rompe el repo (Laravel 11 / deps requieren >= 8.2).
- Usar PHP 8.4 local en `C:\\Users\\carlo\\.tools\\php84\\php.exe` (y composer PHAR en `C:\\Users\\carlo\\.tools\\composer\\composer.phar`) o ejecutar tests con Sail (runtime 8.4).
- Regresion ejecutada en Sail (runtime 8.4 + MySQL 8.0) via `docker compose -f compose.yaml exec -T laravel.test php artisan test` (47 passed).

### Completion Notes List

- Se agrego sistema de toasts global con contenedor fijo (`data-testid="app-toasts"`) y puente flash/session -> toast.
- Se agrego canal Livewire -> JS por evento `ui:toast` y helpers Livewire via trait `InteractsWithToasts`.
- Se agrego soporte "Deshacer" como accion opcional del toast (ventana ~10s).
- Se agregaron componentes Blade: skeleton, long-request overlay (cancelable) y freshness indicator.
- Se agregaron modulos JS (sin dependencias nuevas): `toasts.js`, `long-request.js`, `freshness.js`.
- Se extendio `/dev/livewire-smoke` para demostrar: toast exito/error, toast con "Deshacer", operacion lenta (>3s) con Cancelar, y polling con "Actualizado hace Xs".
- Se agrego documentacion breve del patron en `gatic/docs/ui-patterns.md`.

### File List

- `_bmad-output/implementation-artifacts/1-9-componentes-ux-reutilizables-toasts-loaders-cancelar-actualizado-hace-xs.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `project-context.md`
- `gatic/app/Livewire/Concerns/InteractsWithToasts.php`
- `gatic/app/Livewire/Dev/LivewireSmokeTest.php`
- `gatic/config/gatic.php`
- `gatic/docs/ui-patterns.md`
- `gatic/resources/js/app.js`
- `gatic/resources/js/ui/freshness.js`
- `gatic/resources/js/ui/long-request.js`
- `gatic/resources/js/ui/toasts.js`
- `gatic/resources/sass/app.scss`
- `gatic/resources/views/components/ui/freshness-indicator.blade.php`
- `gatic/resources/views/components/ui/long-request.blade.php`
- `gatic/resources/views/components/ui/skeleton.blade.php`
- `gatic/resources/views/components/ui/toast-container.blade.php`
- `gatic/resources/views/layouts/app.blade.php`
- `gatic/resources/views/layouts/guest.blade.php`
- `gatic/resources/views/livewire/dev/livewire-smoke-test.blade.php`
- `gatic/tests/Feature/Dev/LivewireSmokeComponentTest.php`
- `gatic/tests/Feature/LivewireLayoutIntegrationTest.php`

### Change Log

- 2025-12-30: Implementacion de toasts/skeleton/cancelar/freshness + demo en `/dev/livewire-smoke`.
- 2025-12-30: Regresion completa OK en Sail (47 passed) + status -> review.
- 2025-12-30: Code review: hardening de toasts/long-request (init ordering + cancel/timers + `target`) + smoke UI con Playwright + status -> done.

## Senior Developer Review (AI)

- Fecha: 2025-12-30
- Veredicto: **Aprobado** (sin HIGH/MEDIUM pendientes)
- Validación (backend): `docker compose -f gatic/compose.yaml exec -T laravel.test php artisan test` (47 passed)
- Validación (UI, Playwright): `/dev/livewire-smoke` (toast success/error, undo, overlay >3s + Cancelar conservando estado, freshness)

### Fixes aplicados

- Toasts: listener robusto ante orden de carga (si `livewire:init` ya ocurrió) para evitar que no se muestren eventos `ui:toast`.
- Long request/cancel: overlay basado en duración real del request + limpieza de timers en cancel/fail.
- Long request: soporte opcional `target="metodoLivewire"` para evitar overlay en otras llamadas del mismo componente (ej. polling).
- Docs: actualizado `gatic/docs/ui-patterns.md` con `target`.
