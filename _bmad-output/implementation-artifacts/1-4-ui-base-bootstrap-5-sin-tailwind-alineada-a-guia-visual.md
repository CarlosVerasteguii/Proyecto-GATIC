# Story 1.4: UI base Bootstrap 5 (sin Tailwind) alineada a guía visual

Status: in-progress

Story Key: 1-4-ui-base-bootstrap-5-sin-tailwind-alineada-a-guia-visual  
Tracking: _bmad-output/implementation-artifacts/sprint-status.yaml  
Gate: 0 (Repo listo)  
GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/10  
Epic: 1 (Acceso seguro y administración de usuarios)  
Fuentes: _bmad-output/project-planning-artifacts/epics.md, 03-visual-style-guide.md, _bmad-output/project-context.md, _bmad-output/architecture.md, _bmad-output/project-planning-artifacts/ux-design-specification.md

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a usuario interno,
I want que las pantallas base (auth + layout autenticado) usen Bootstrap 5 sin Tailwind y con branding CFE/GATIC,
so that la UX sea consistente, accesible y mantenible desde el inicio (NFR1).

## Acceptance Criteria

1. **Bootstrap 5 integrado (build y runtime)**
   - **Given** el proyecto Laravel en `gatic/`
   - **When** se compilan assets (`npm run build` / `./vendor/bin/sail npm run build`)
   - **Then** Bootstrap 5 queda integrado y usable en las vistas base
   - **And** el build termina sin requerir Tailwind

2. **Tailwind eliminado completamente (sin residuos)**
   - **Given** el repositorio
   - **When** se busca `tailwind` / `@tailwind` / `tailwindcss` en `gatic/`
   - **Then** no existen configuraciones/archivos de Tailwind activos (ej. `tailwind.config.*`, `postcss.config.*` con plugin tailwind, `resources/css/app.css` con `@tailwind`)
   - **And** no se usan clases Tailwind en vistas que se renderizan en runtime

3. **Pantallas base con maquetación Bootstrap + branding corporativo**
   - **Given** las pantallas de autenticación (login) y el layout autenticado (dashboard/nav)
   - **When** un usuario navega login → dashboard → logout
   - **Then** la maquetación usa componentes Bootstrap 5 (form, buttons, navbar, containers)
   - **And** respeta colores/tono corporativo tomando `03-visual-style-guide.md` como referencia (sin tratarlo como catálogo rígido)

4. **Home consistente (sin “welcome” Tailwind por defecto)**
   - **Given** un usuario sin sesión visita `/`
   - **When** la página se renderiza
   - **Then** la UI es consistente con el theme Bootstrap (o se redirige a login/dashboard)
   - **And** no se muestra la plantilla por defecto con Tailwind (ni inline-fallback)

5. **Accesibilidad básica (focus visible + no color-only)**
   - **Given** inputs/botones/links en pantallas base
   - **When** se navega con teclado
   - **Then** existe focus visible consistente alineado al branding (ring/outline)
   - **And** estados importantes no dependen solo del color (texto + opcional icono)

## Tasks / Subtasks

- [x] 1) Eliminar Tailwind por completo (AC: 2, 4)
  - [x] Quitar `tailwindcss` de `gatic/package.json` y actualizar `gatic/package-lock.json`
  - [x] Eliminar `gatic/tailwind.config.js`
  - [x] Actualizar `gatic/postcss.config.js` para remover Tailwind (dejar solo `autoprefixer` o eliminar el archivo si no se requiere)
  - [x] Eliminar `gatic/resources/css/app.css` (contiene `@tailwind ...`) y cualquier referencia a ese entrypoint
  - [x] Reemplazar `gatic/resources/views/welcome.blade.php`:
    - [x] Sin Tailwind inline-fallback
    - [x] Usar Bootstrap + el mismo pipeline Vite (`resources/sass/app.scss`)
    - [x] Opcional: convertir `/` a redirect a `login`/`dashboard` para evitar “landing pública”
  - [x] Verificación de repositorio: `rg -n \"@tailwind|tailwindcss|tailwind.config|resources/css/app.css\" gatic` no devuelve matches relevantes

- [x] 2) Implementar tokens de branding CFE/GATIC (AC: 3, 5)
  - [x] Definir tokens/variables (preferir en `gatic/resources/sass/_variables.scss` o partial dedicado):
    - [x] Primary recomendado por contraste: `#006B47` (UX spec)
    - [x] Acento/branding: `#008E5A` (visual guide)
    - [x] Links, focus ring, estados base (sin sobrecustomizar)
  - [x] Asegurar que `.btn-primary`, links y focus visible reflejan los tokens definidos

- [x] 3) Ajustar vistas base a Bootstrap + copy en español (AC: 3, 5)
  - [x] Revisar/ajustar `gatic/resources/views/layouts/app.blade.php` y `gatic/resources/views/layouts/guest.blade.php` (consistencia de assets y estructura)
  - [x] Ajustar `gatic/resources/views/layouts/navigation.blade.php`:
    - [x] Branding (nombre app) consistente
    - [x] Labels mínimos en español (ej. "Inicio/Dashboard", "Cerrar sesión")
  - [x] Revisar `gatic/resources/views/auth/login.blade.php` (layout y componentes Bootstrap consistentes)

- [ ] 4) Smoke checks (AC: 1–5)
  - [x] `cd gatic && npm run build` (o equivalente con Sail) sin errores
  - [ ] Navegación manual: `/` → login → dashboard → logout (sin estilos Tailwind)

## Dev Notes

### Contexto actual (repo)

- La app Laravel vive en `gatic/`; la raíz del repo se reserva para BMAD/docs/artefactos.
- Ya existe Vite compilando `resources/sass/app.scss` + `resources/js/app.js` y las layouts principales (`layouts/app.blade.php`, `layouts/guest.blade.php`) ya incluyen esos assets.
- Hay residuos de Tailwind en el repo (dependencias/config/`welcome.blade.php`) aunque las pantallas autenticadas ya usan SCSS + Bootstrap.

### Objetivo

- “Cerrar la puerta” a Tailwind (sin configuración, sin assets, sin plantilla default) y estandarizar la UI base en Bootstrap 5 con tokens de branding CFE/GATIC.

### Alcance / fuera de alcance

**Incluye**
- Integración limpia de Bootstrap 5 (SCSS/Vite) como única base de UI.
- Branding mínimo (tokens/variables, primary, links, focus ring, botones) alineado a `03-visual-style-guide.md` y UX spec.
- Ajustes de maquetación visibles en pantallas base (login, dashboard/nav, y home `/`).

**No incluye**
- Layout final completo con sidebar/topbar por rol (eso vive en Gate 1).
- Componentes UX avanzados (toasts/loaders/cancelar/“actualizado hace Xs”) (Gate 1).
- Livewire 3 en layout/arquitectura de componentes (Story 1.5).

### Guardrails técnicos (MUST)

- UI principal debe quedar en **Bootstrap 5** (no introducir otro framework CSS).
- Eliminar Tailwind de forma completa: dependencia NPM, config de PostCSS, config tailwind, y cualquier asset/vista que lo use.
- Mantener una sola entrada de estilos vía Vite: `resources/sass/app.scss` (evitar revivir `resources/css/app.css`).
- Branding: usar `03-visual-style-guide.md` como referencia, pero priorizar accesibilidad (UX spec recomienda `#006B47` como primary por contraste; `#008E5A` como acento).
- Idioma: copy/UI visible en **español**; identificadores de código/rutas/DB en **inglés** (Project Context).

### Librerías / herramientas (requisitos)

- Laravel **11** + PHP **8.2+** (baseline del proyecto).
- Asset pipeline: Vite (`laravel-vite-plugin`) con SCSS (Sass).
- UI: Bootstrap **5** (mantener la versión existente salvo que se acuerde upgrade explícito).
- PostCSS: solo lo necesario (autoprefixer); **sin** plugin Tailwind.

### Testing (requisitos)

- Mantener el repo en estado “mergeable”: no introducir fallas nuevas en tests/build.
- Verificación mínima:
  - `npm run build` (o vía Sail) sin errores.
  - Smoke manual de pantallas base (login/dashboard/logout y `/`).
- Si para cumplir AC se toca routing (`/` redirect), cubrir con un test Feature simple (status/redirect esperado) si ya existe suite activa.

### Inteligencia de story previa (Story 1.3)

- Ya existe integración Bootstrap vía SCSS: `gatic/resources/sass/app.scss` importa `bootstrap/scss/bootstrap` y las layouts principales usan `@vite(['resources/sass/app.scss', 'resources/js/app.js'])`.
- Aún hay residuos de Tailwind que generan riesgo de “doble stack”:
  - `gatic/postcss.config.js` tiene plugin `tailwindcss`.
  - `gatic/resources/css/app.css` contiene directivas `@tailwind`.
  - `gatic/resources/views/welcome.blade.php` referencia `resources/css/app.css` y contiene un inline-fallback enorme de Tailwind si no hay build/hot.
- Navegación/copy aún tiene strings en inglés (ej. “Dashboard”, “Log Out”); al menos en pantallas base se deben alinear a español (Project Context).

### Git intelligence (estado actual detectado)

- Se detectó stack mixto en `gatic/`: Bootstrap (SCSS + Vite) convive con archivos/config de Tailwind; esto es exactamente lo que esta story debe eliminar para evitar confusión y regressions.
- Archivos clave donde hoy aparece Tailwind (a eliminar/limpiar):
  - `gatic/package.json` (`tailwindcss`)
  - `gatic/postcss.config.js` (plugin `tailwindcss`)
  - `gatic/resources/css/app.css` (`@tailwind ...`)
  - `gatic/tailwind.config.js`
  - `gatic/resources/views/welcome.blade.php` (fallback Tailwind)

### Info técnica actual (para evitar decisiones desactualizadas)

- Bootstrap 5 tiene releases 5.3.x (mejor soporte de color modes y CSS variables). Si se decide upgrade, hacerlo explícito y validar cambios visuales; si no, mantener la versión actual y solo limpiar Tailwind.
- Vite compila SCSS sin requerir Tailwind: el camino recomendado es un entrypoint único (`resources/sass/app.scss`) con overrides de variables antes del import de Bootstrap.

### Project Structure Notes

- La app vive en `gatic/` (no mover). La raíz del repo es para artefactos BMAD/documentación.
- Estilos:
  - Entrada única: `gatic/resources/sass/app.scss` (Vite).
  - Variables/tokens: `gatic/resources/sass/_variables.scss` (y/o un partial dedicado, p. ej. `_gatic-tokens.scss`).
- Vistas base: `gatic/resources/views/layouts/*`, `gatic/resources/views/auth/*`, `gatic/resources/views/dashboard.blade.php`, `gatic/resources/views/welcome.blade.php` (o reemplazo).
- No introducir rutas/nombres/identificadores en español (solo copy/UI).

### References

- Project context (reglas críticas): `docsBmad/project-context.md`
- Project context (resumen LLM): `project-context.md`
- Arquitectura (stack + estructura): `_bmad-output/architecture.md`
- Backlog fuente de verdad (Story 1.4): `_bmad-output/project-planning-artifacts/epics.md` (Story 1.4)
- UX spec (tokens/branding/contraste): `_bmad-output/project-planning-artifacts/ux-design-specification.md` (tokens CFE/GATIC)
- Guía visual (referencia de colores/branding): `03-visual-style-guide.md`

## Senior Developer Review (AI)

### Resumen

- Build de frontend OK: `cd gatic && npm run build`.
- Tailwind eliminado: sin matches en `gatic/` para `@tailwind|tailwindcss|tailwind.config|resources/css/app.css`.
- Home `/` redirige según sesión (guest->login, auth->dashboard) y pantallas base están maquetadas con Bootstrap.

### Hallazgos y fixes aplicados (review)

- (MEDIUM) Inconsistencia de tracking: story vs sprint-status desalineados → sincronizado a `in-progress` en `sprint-status.yaml` y actualizado status aquí.
- (MEDIUM) Git vs File List: existía un `validation-report-*.md` sin documentar → agregado a la File List.
- (LOW) Import sin uso en `gatic/routes/web.php` → removido.
- (LOW) Info disclosure en `welcome.blade.php` (versiones) → removido.

### Pendientes / Riesgos

- Warnings de Sass por `@import` (deprecado en Dart Sass 3). No rompe el build hoy, pero conviene planear migración a `@use`.
- Validación manual recomendada: `/` → login → dashboard → logout (solo smoke visual).

## Story Completion Status

- Status: **in-progress**
- Completion note: Story creada con guardrails explícitos para evitar “stack mixto” (Tailwind + Bootstrap) y para alinear branding con accesibilidad.

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `cd gatic && npm run build` (OK; sin Tailwind)
- `rg -n "@tailwind|tailwindcss|tailwind.config|resources/css/app.css" gatic` (0 matches)
- Tests PHP no ejecutables en este host (PHP 8.0.30; dependencias requieren >= 8.4.0)

### Completion Notes List

- Tailwind eliminado por completo (dependencia, config y entrypoint `resources/css/app.css`).
- `/` ahora redirige a `login` (guest) o `dashboard` (autenticado); `welcome.blade.php` quedo en Bootstrap sin fallback Tailwind.
- Tokens de branding aplicados via Sass: primary `#006B47`, acento/links `#008E5A`, focus ring verde con buen contraste.
- Copy base en español (login, navegación y dashboard).
- Test agregado para redirects de `/` (`HomeRedirectTest`); pendiente correr suite en entorno con PHP >= 8.4 (Sail/WSL/CI).
- Smoke manual pendiente: `/` → login → dashboard → logout (validar visualmente).
- Post-review: removido import sin uso en `routes/web.php` y removida exposición de versiones en `welcome.blade.php`.

### File List

- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `_bmad-output/implementation-artifacts/1-4-ui-base-bootstrap-5-sin-tailwind-alineada-a-guia-visual.md`
- `_bmad-output/implementation-artifacts/validation-report-2025-12-28T220911Z.md`
- `gatic/package.json`
- `gatic/package-lock.json`
- `gatic/postcss.config.js`
- `gatic/routes/web.php`
- `gatic/resources/sass/_variables.scss`
- `gatic/resources/sass/app.scss`
- `gatic/resources/views/welcome.blade.php`
- `gatic/resources/views/layouts/app.blade.php`
- `gatic/resources/views/layouts/guest.blade.php`
- `gatic/resources/views/layouts/navigation.blade.php`
- `gatic/resources/views/auth/login.blade.php`
- `gatic/resources/views/dashboard.blade.php`
- `gatic/tests/Feature/HomeRedirectTest.php`
- (deleted) `gatic/tailwind.config.js`
- (deleted) `gatic/resources/css/app.css`

### Change Log

- Removed Tailwind stack; kept Bootstrap 5 + Vite SCSS entrypoint.
- Added CFE/GATIC branding tokens and consistent focus styles.
- Updated base pages and navigation copy to Spanish.
- Senior Dev Review: synced tracking, documented validation report, removed unused import + version disclosure.
