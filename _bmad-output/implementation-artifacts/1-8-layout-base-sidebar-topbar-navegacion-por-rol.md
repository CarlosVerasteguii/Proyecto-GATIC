# Story 1.8: Layout base (sidebar/topbar) + navegación por rol

Status: done

Story Key: 1-8-layout-base-sidebar-topbar-navegacion-por-rol  
Tracking: _bmad-output/implementation-artifacts/sprint-status.yaml  
Gate: 1 (UX base + navegación)  
Epic: 1 (Acceso seguro y administración de usuarios)  
GitHub (referencia): N/A (este story key proviene del backlog BMAD; ver `docsBmad/gates-execution.md`)  
Fuentes: _bmad-output/project-planning-artifacts/epics.md, docsBmad/gates-execution.md, _bmad-output/prd.md, _bmad-output/architecture.md, docsBmad/project-context.md, project-context.md, _bmad-output/project-planning-artifacts/ux-design-specification.md, _bmad-output/implementation-artifacts/sprint-status.yaml, gatic/resources/views/layouts/app.blade.php, gatic/resources/views/layouts/navigation.blade.php, gatic/routes/web.php, gatic/package.json, _bmad-output/implementation-artifacts/1-5-livewire-3-instalado-e-integrado-en-el-layout.md, _bmad-output/implementation-artifacts/1-6-roles-fijos-policies-gates-base-server-side.md, _bmad-output/implementation-artifacts/1-7-calidad-y-ci-minima-pint-phpunit-larastan.md

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a usuario interno autenticado (Admin/Editor/Lector),
I want un layout desktop-first con **sidebar + topbar** y navegación basada en rol,
so that pueda moverme rápido por el sistema y solo vea opciones que realmente puedo usar (defensa en profundidad).

## Acceptance Criteria

1. **Layout base (app shell) visible para usuario autenticado**
   - **Given** un usuario autenticado
   - **When** entra a `/dashboard` (o cualquier vista autenticada)
   - **Then** ve un layout con **sidebar** y **topbar**
   - **And** el contenido principal se muestra en un área de trabajo clara y consistente

2. **Menú por rol (UI) + enforcement server-side**
   - **Given** un Admin
   - **When** navega el sistema
   - **Then** ve opciones de Admin (ej. link a `admin.users.index`)
   - **And** al hacer click, la ruta funciona (no 403)
   - **Given** un Editor o Lector
   - **When** navega el sistema
   - **Then** **NO** ve opciones de Admin
   - **And** si intenta entrar por URL directa a rutas de Admin, el servidor lo bloquea (403 o redirección segura)

3. **Responsive mínimo (sin inventar framework)**
   - **Given** viewport pequeño (móvil/tablet)
   - **When** el usuario abre el menú
   - **Then** la sidebar se puede abrir/cerrar (ej. `offcanvas` de Bootstrap)
   - **And** la topbar permanece accesible (brand + user menu + botón de menú)

4. **Accesibilidad mínima**
   - **Given** navegación con teclado
   - **When** el usuario tabula por el layout
   - **Then** hay foco visible consistente (ya alineado al branding por Story 1.4)
   - **And** los controles de menú (botón/sidebar/dropdown) tienen labels/ARIA razonables

## Tasks / Subtasks

- [x] 1) Implementar app shell (AC: 1, 3, 4)
  - [x] Definir estructura base en `gatic/resources/views/layouts/app.blade.php` (wrapper + slots/yields)
  - [x] Reemplazar `gatic/resources/views/layouts/navigation.blade.php` por composición **sidebar + topbar** (o separar en parciales `layouts/partials/*`)
  - [x] Sidebar: fija en `md+`, `offcanvas` en pantallas pequeñas (Bootstrap)
  - [x] Topbar: brand + botón de menú + user dropdown (logout)
  - [x] Mantener `@livewireStyles/@livewireScripts` y `@stack('styles')/@stack('scripts')`

- [x] 2) Menú por rol (AC: 2)
  - [x] En la sidebar, renderizar solo links permitidos (mínimo: `dashboard`; Admin: `admin.users.index`)
  - [x] Usar `@can(...)`/`Gate` existentes (ej. `@can('users.manage')`) para evitar duplicar lógica
  - [x] No agregar links a módulos/rutas que aún no existen (evita 404 "fantasma")

- [x] 3) Estilos y UX del layout (AC: 1, 3, 4)
  - [x] Crear/ajustar un partial SCSS (ej. `resources/sass/_layout.scss`) para ancho de sidebar, estado activo, espaciados
  - [x] Reusar tokens/branding existentes (Story 1.4) y utilities de Bootstrap (evitar CSS "custom" innecesario)

- [x] 4) Tests (AC: 1, 2)
  - [x] Feature test: `/dashboard` incluye marcadores estables (ej. `data-testid="app-sidebar"` y `data-testid="app-topbar"`)
  - [x] Feature test: Admin ve link "Usuarios" y Editor/Lector no lo ven
  - [x] Feature test: Editor/Lector reciben 403 en `/admin/users` (ya existe; validar no se rompe)

- [x] 5) QA manual (smoke)
  - [x] Login → `/dashboard`: sidebar + topbar visibles
  - [x] Mobile: toggle abre/cierra sidebar
  - [x] Admin: “Usuarios” visible y navega; Editor/Lector: no visible

## Dev Notes

### Developer Context (qué existe hoy y qué cambia)

- **Hoy** el layout autenticado (`gatic/resources/views/layouts/app.blade.php`) incluye `@include('layouts.navigation')` y el `navigation.blade.php` es un **navbar superior** (sin sidebar).
- Ya existe RBAC server-side y el navbar ya usa `@can('users.manage')` para mostrar “Usuarios” solo a Admin; **esto se debe conservar** y expandir al nuevo layout.
- La app vive dentro de `gatic/` (no mover). La raíz del repo es para BMAD/artefactos.

### DEV AGENT GUARDRAILS (no romper / no inventar)

- **No cambiar stack**: Laravel 11 + Blade + Livewire 3 + Bootstrap 5 (sin Tailwind, sin frameworks extra).
- **No romper Livewire**: mantener `@livewireStyles`/`@livewireScripts` y `@stack('styles')/@stack('scripts')` en los layouts.
- **No duplicar autorización**: el menú puede ocultar links por rol, pero el enforcement debe seguir siendo server-side (Gates/Policies + middleware `can:`).
- **No meter “links fantasma”** a rutas aún no existentes (evita 404 y confusión de UX).
- **IDs/nombres técnicos en inglés** (rutas, nombres de archivos, variables); **copy en español**.

### Technical Requirements (Dev Agent Guardrails)

- Implementar layout como **app shell**:
  - **Sidebar**: navegación principal (vertical), con estado activo claro.
  - **Topbar**: brand + botón de menú (mobile) + user dropdown (logout).
  - **Main**: área de contenido consistente (`$slot` / `@yield('content')`).
- Activos/links:
  - Determinar estado activo con `request()->routeIs(...)` y aplicar clases Bootstrap (`.active`) o utilidades (evita lógica frágil por URL string).
  - Mantener logout como POST al route `logout` (form oculto), como ya existe en `layouts/navigation.blade.php`.
- Responsive mínimo:
  - `md+`: sidebar fija (desktop-first).
  - `<md`: sidebar en `offcanvas` usando Bootstrap (aprovechar `data-bs-*` ya disponible por `resources/js/bootstrap.js`).
- Accesibilidad:
  - Botón de toggle con `aria-controls`, `aria-expanded` y label claro.
  - Sidebar con `nav`/`aria-label` y links focusables.

### Architecture Compliance (alineación obligatoria)

- UI principal con Blade/Livewire; no introducir controllers “por conveniencia” para navegación.
- RBAC/authorization:
  - UI: `@can(...)` para mostrar/ocultar items.
  - Server-side: mantener middleware `can:users.manage` en `gatic/routes/web.php` para `/admin/*`.
- Estructura y convenciones: respetar lo descrito en `_bmad-output/architecture.md` y `docsBmad/project-context.md`.

### Library / Framework Requirements

- Bootstrap (npm): actualmente `bootstrap:^5.2.3` en `gatic/package.json` (no cambiar versión dentro de esta story salvo decisión explícita).
- Componentes Bootstrap recomendados:
  - `offcanvas` para sidebar en mobile
  - `navbar`/`dropdown` para topbar y user menu
- Livewire: el layout debe seguir soportando páginas Livewire por ruta (ej. `UsersIndex::class` en `/admin/users`).

### File Structure Requirements (qué tocar / qué crear)

- Mantener `gatic/resources/views/layouts/app.blade.php` como layout principal autenticado.
- Refactor sugerido:
  - `gatic/resources/views/layouts/navigation.blade.php` → convertirlo en “app chrome” (sidebar + topbar) **o** dividir en:
    - `gatic/resources/views/layouts/partials/sidebar.blade.php`
    - `gatic/resources/views/layouts/partials/topbar.blade.php`
    - `gatic/resources/views/layouts/partials/user-menu.blade.php` (opcional)
- Estilos:
  - `gatic/resources/sass/_layout.scss` (nuevo) importado desde `gatic/resources/sass/app.scss`
  - Reusar tokens definidos en Story 1.4 (no reinventar paleta).

### Testing Requirements

- Feature tests (PHPUnit) en `gatic/tests/Feature/*`:
  - Auth: `/dashboard` retorna 200 y contiene marcadores del layout (sidebar/topbar) para no tener asserts frágiles.
  - RBAC: Admin ve link “Usuarios”; Editor/Lector no lo ven.
  - RBAC server-side: Editor/Lector reciben 403 al visitar `/admin/users` (asegurar que el refactor no rompa middleware ni gates).
- Mantener suite verde: Pint + PHPUnit + Larastan (Story 1.7).

### Previous Story Intelligence (aplicable: Story 1.7 y anteriores)

- **Story 1.4 (Bootstrap + branding / sin Tailwind):** no reintroducir Tailwind; reusar tokens y foco visible ya definidos; el layout debe seguir usando el pipeline Vite (`resources/sass/app.scss`, `resources/js/app.js`).
- **Story 1.5 (Livewire 3):** layouts `app`/`guest` ya están cableados con Livewire; al refactor de markup, no mover/eliminar `@livewireStyles`/`@livewireScripts` ni romper el slot/yields (`{{ $slot ?? '' }}` + `@yield('content')`).
- **Story 1.6 (RBAC):** `Gate::before` da acceso total a Admin y existe `users.manage`; usar `@can('users.manage')` para el item de “Usuarios” y no replicar reglas de rol manualmente.
- **Story 1.7 (calidad/CI):** el repo es multi-root (app en `gatic/`); mantener cambios del layout dentro de `gatic/` y conservar la disciplina de calidad (Pint/PhpUnit/Larastan).

### Git Intelligence Summary (patrones recientes)

- Commits recientes (títulos): CI/calidad (Story 1.7) → RBAC/gates (Story 1.6) → Livewire (Story 1.5).
- Patrón consistente: cambios de app dentro de `gatic/` + documentación/tracking en `_bmad-output/implementation-artifacts/*`.
- Implicación: esta story debería tocar principalmente:
  - `gatic/resources/views/layouts/*` (+ parciales si se crean)
  - `gatic/resources/sass/*` (si se agrega `_layout.scss`)
  - `gatic/tests/Feature/*` (si se agregan asserts del layout)

### Latest Tech Information (evitar implementaciones desactualizadas)

- **Bootstrap:** hay releases 5.3.x (ej. 5.3.4–5.3.6 en 2025). El repo hoy está en `bootstrap:^5.2.3` (`gatic/package.json`).
  - Recomendación para esta story: **NO** actualizar versión; implementar el layout usando componentes existentes (`offcanvas`, `navbar`, `dropdown`).
  - Si se decide upgrade a 5.3.x, hacerlo en un PR/story dedicado y validar visualmente `offcanvas`/`dropdown` (y el pipeline SCSS).
- **Livewire:** la línea 3.x sigue activa (ej. 3.6 anunciado en 2025). Esta story no requiere cambios de versión; solo asegurar compatibilidad del markup con el render de Livewire.

### Project Structure Notes

- App en `gatic/`; no mover rutas ni cambiar a “app en raíz”.
- Rutas: paths en inglés, kebab-case; names `dot.case` (ver `project-context.md`).
- Navegación: links solo a rutas existentes; el resto se agregará cuando se implementen módulos.

### References

- Backlog (fuente de verdad): `_bmad-output/project-planning-artifacts/epics.md` (Story 1.8)
- Gate 1 scope/DoD: `docsBmad/gates-execution.md` (Gate 1 — UX base + navegación)
- PRD (NFR1): `_bmad-output/prd.md`
- Arquitectura (stack + estructura + Livewire-first): `_bmad-output/architecture.md`
- Reglas críticas: `docsBmad/project-context.md`, `project-context.md`
- Estado actual del layout: `gatic/resources/views/layouts/app.blade.php`, `gatic/resources/views/layouts/navigation.blade.php`
- Stack frontend: `gatic/package.json`, `gatic/resources/js/bootstrap.js`
- UX patterns (sidebar/header): `_bmad-output/project-planning-artifacts/ux-design-specification.md`

### Project Context Reference (reglas que esta story NO puede violar)

- Identificadores/rutas/código en **inglés**; copy UI en **español**.
- UI principal con Blade + Livewire 3; controllers solo en “bordes” cuando aplique (no para navegación).
- Autorización server-side obligatoria (Policies/Gates + middleware `can:`); UI es defensa en profundidad.
- Sin WebSockets; cuando se requiera “near-real-time” usar `wire:poll.visible` (esto se trabaja en stories posteriores).

## Story Completion Status

- Status: **done**
- Completion note: code review completada; ACs validados contra implementación y ajustes aplicados.

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `Get-Content _bmad-output/implementation-artifacts/sprint-status.yaml` (selección automática del primer backlog)
- `Get-Content _bmad-output/project-planning-artifacts/epics.md` (Story 1.8)
- `Get-Content _bmad-output/architecture.md`, `Get-Content project-context.md`
- `Get-Content gatic/resources/views/layouts/app.blade.php`, `Get-Content gatic/resources/views/layouts/navigation.blade.php`
- `Get-Content gatic/routes/web.php`, `Get-Content gatic/package.json`, `Get-Content gatic/resources/js/bootstrap.js`
- `git log -5 --oneline` (patrones recientes)
- Web research: Bootstrap 5.3.x + Livewire 3.x (referencias usadas en "Latest Tech Information")
- `docker compose up -d` (Sail runtime 8.4)
- `docker compose exec -T laravel.test php artisan test` (suite completa + nuevos tests de layout)
- `docker compose exec -T laravel.test ./vendor/bin/pint --test` (Pint)
- `docker compose exec -T laravel.test ./vendor/bin/phpstan analyse` (Larastan)
- Playwright smoke: login (Admin/Lector) + sidebar/topbar + offcanvas toggle + acceso directo a `/admin/users`
- Code review: fixes (logout sin JS + a11y + tests) + re-run tests + Playwright smoke

### Completion Notes List

- Story seleccionada desde `_bmad-output/implementation-artifacts/sprint-status.yaml` (primer story en `backlog`: `1-8-*`).
- Requisitos base tomados de `_bmad-output/project-planning-artifacts/epics.md` (Story 1.8) y Gate 1 (`docsBmad/gates-execution.md`).
- Guardrails alineados a `_bmad-output/architecture.md` + `docsBmad/project-context.md`/`project-context.md`.
- Se evitó scope creep: menú solo para rutas existentes (`dashboard`, `admin.users.index`) y sin upgrades de dependencias.
- Se implementó app shell con grid (sidebar md+ + topbar + main) y sidebar offcanvas para móvil.
- Se agregaron feature tests para layout/roles + toggle offcanvas, y se corrió la suite completa en contenedor Sail.
- Pint/Larastan sin errores (contenedor Sail).
- Estado actualizado a `done` (code review completada).

### File List

- `_bmad-output/implementation-artifacts/1-8-layout-base-sidebar-topbar-navegacion-por-rol.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `gatic/resources/views/layouts/app.blade.php`
- `gatic/resources/views/layouts/navigation.blade.php`
- `gatic/resources/views/layouts/partials/sidebar.blade.php`
- `gatic/resources/views/layouts/partials/sidebar-nav.blade.php`
- `gatic/resources/views/layouts/partials/topbar.blade.php`
- `gatic/resources/sass/app.scss`
- `gatic/resources/sass/_layout.scss`
- `gatic/tests/Feature/LayoutNavigationTest.php`

## Senior Developer Review (AI)

Reviewer: Carlos on 2025-12-29

Outcome: Approve (después de fixes)

### Findings (resueltos)

- [HIGH] Logout dependía de JS (onclick) en lugar de submit HTML
  - Fixed: `gatic/resources/views/layouts/partials/topbar.blade.php`
- [HIGH] Foco visible no consistente en controles clave (toggler/offcanvas close/dropdown items)
  - Fixed: `gatic/resources/sass/app.scss`
- [MEDIUM] Tests incompletos/frágiles para AC2 (Admin debe acceder `/admin/users`; asserts por texto)
  - Fixed: `gatic/tests/Feature/LayoutNavigationTest.php`
- [MEDIUM] A11y: falta `aria-current` en links activos
  - Fixed: `gatic/resources/views/layouts/partials/sidebar-nav.blade.php`
- [MEDIUM] Breakpoint hardcode en SCSS (mejor alineado a Bootstrap mixins)
  - Fixed: `gatic/resources/sass/_layout.scss`

### Change Log

- 2025-12-29: Senior Dev Review (AI) -> fixes aplicados + status -> done
