# Story 1.1: Repo inicial (layout) + Laravel 11 base

Status: done

Story Key: 1-1-repo-inicial-layout-laravel-11-base  
Tracking: _bmad-output/implementation-artifacts/sprint-status.yaml  
Gate: 0 (Repo listo)  

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a desarrollador del proyecto,
I want definir el layout del repo e inicializar Laravel 11 en una subcarpeta `gatic/`,
so that el equipo tenga una base consistente para construir el MVP y mantener separados “planning/BMAD” y la app.

## Acceptance Criteria

1. **Layout documentado**
   - Existe `README.md` en la raíz con la decisión (app en subcarpeta `gatic/`) y su justificación.
   - El README incluye un árbol mínimo del repo y cómo ubicar la app (`gatic/`).
2. **Laravel 11 inicializado**
   - Existe el proyecto Laravel en `gatic/` (con `gatic/composer.json` y `gatic/artisan`).
   - `php gatic/artisan --version` reporta Laravel **11.x**.
3. **Configuración base segura**
   - Existe `gatic/.env.example` con variables mínimas para boot (sin secretos).
   - No se comitea `gatic/.env` (se mantiene ignorado por git).
4. **Higiene del repo (ignores)**
   - `.gitignore` cubre artefactos de `gatic/` (`gatic/vendor/`, `gatic/node_modules/`, `gatic/public/build/`, `gatic/public/hot`, caches, etc.).

## Tasks / Subtasks

- [x] Definir layout del repo y documentarlo (AC: 1)
  - [x] Confirmar que la app vive en `gatic/` y que la raíz se mantiene para BMAD/docs
  - [x] Crear `README.md` con decisión, justificación y árbol mínimo del repo
- [x] Inicializar Laravel 11 en `gatic/` (AC: 2, 3)
  - [x] Ejecutar `composer create-project --prefer-dist laravel/laravel gatic "11.*"`
  - [x] Verificar `php gatic/artisan --version` -> 11.x
  - [x] Verificar `gatic/.env.example` y ajustar solo defaults no sensibles si aplica
- [x] Ajustar `.gitignore` para subcarpeta `gatic/` (AC: 4)
  - [x] Ignorar `gatic/.env*` (permitiendo `gatic/.env.example`)
  - [x] Ignorar `gatic/vendor/`, `gatic/node_modules/`, `gatic/public/build/`, `gatic/public/hot`, caches de pruebas

## Dev Notes

### Contexto actual (antes de tocar código)

- Este repositorio hoy es “planning + BMAD” (sin app Laravel todavía). La app se crea en Gate 0 dentro de `gatic/`.
- Fuentes de verdad para esta historia:
  - Bible: `docsBmad/project-context.md`
  - Arquitectura: `_bmad-output/architecture.md`
  - Backlog (AC): `_bmad-output/project-planning-artifacts/epics.md`
  - Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`

### Decisión de layout (no negociable)

- La app Laravel vive en `gatic/` (no en la raíz). La raíz se reserva para BMAD, docs y artefactos de planeación.
- La decisión y justificación deben quedar en `README.md` (raíz del repo), incluyendo un árbol mínimo del repo.

### Alcance (lo que sí se hace en esta historia)

- Crear el skeleton de Laravel 11 en `gatic/` usando Composer.
- Verificar versión (`php gatic/artisan --version`) y existencia de `gatic/.env.example` (sin secretos).
- Ajustar `.gitignore` para cubrir artefactos dentro de `gatic/`.

### Fuera de alcance (NO hacerlo aquí)

- Breeze/Bootstrap, Sail/MySQL, seeders base, CI y tooling de calidad (esas historias vienen después dentro de Gate 0).
  - Si aparece la tentación de “aprovechar” y dejarlo listo, detenerse: esta historia solo deja el repo ordenado + Laravel 11 base.

### Requisitos técnicos (guardrails)

- Requisitos locales mínimos:
  - PHP **8.2+** y Composer **2.x** disponibles en tu PATH.
  - Git instalado (para versionado y convenciones del repo).
- Comando canónico de inicialización (según arquitectura):
  - `composer create-project --prefer-dist laravel/laravel gatic "11.*"`
- Verificaciones obligatorias (antes de dar por “done”):
  - `php gatic/artisan --version` -> reporta **Laravel 11.x**
  - Existe `gatic/.env.example` (sin secretos) y NO se comitea `gatic/.env`.
- No introducir decisiones extra:
  - No instalar Breeze, Bootstrap, Sail, CI, Larastan, Pint, etc. en esta historia (pertenecen a historias siguientes).
- Higiene de gitignore (la app está en subcarpeta):
  - `.gitignore` debe ignorar explícitamente artefactos dentro de `gatic/` (los ignores en raíz con `/vendor/` NO aplican a `gatic/vendor/`).
  - Añadir (o equivalente) en `.gitignore`:
    - `gatic/.env` / `gatic/.env.*` y permitir `!gatic/.env.example`
    - `gatic/vendor/`
    - `gatic/node_modules/`
    - `gatic/public/build/`, `gatic/public/hot`
    - caches de pruebas en `gatic/` (phpunit/pest) si aparecen

### Cumplimiento de arquitectura (obligatorio)

- Layout del repo: mantener `_bmad/`, `_bmad-output/`, `docsBmad/` en raíz; la app Laravel vive en `gatic/`.
- Stack objetivo (no desviarse): Laravel 11 + PHP 8.2+ + MySQL 8; Blade + Livewire 3 + Bootstrap 5 (las partes de UI vendrán después).
- No comprometer branding corporativo: `03-visual-style-guide.md` es solo referencia local y ya está ignorado por `.gitignore`.
- Preparar el terreno para la estructura propuesta (sin implementarla aún): `app/Actions/*`, `app/Livewire/*`, `app/Policies/*`, `config/gatic.php` (se materializa en historias posteriores).

### Requisitos de librerías / frameworks

- Única “dependencia” esperada aquí: `laravel/laravel` en versión `11.*` (creado via Composer).
- Evitar introducir scaffolds alternativos o templates “todo incluido”.
- Paquetes mencionados en arquitectura (NO instalar en esta historia):
  - `guizoxxv/laravel-breeze-bootstrap` (cuando toque Breeze + Bootstrap).
  - Sail/MySQL (cuando toque entorno con contenedores).

### Info técnica actual (para evitar implementaciones desactualizadas)

- Laravel 11 establece baseline moderno y requiere PHP **8.2+** (ver release notes / docs oficiales).
- El patrón recomendado para fijar major en creación del proyecto es usar `composer create-project ... "11.*"` (evita caer en Laravel 12+ por defecto si cambian los defaults del installer).

### Requisitos de estructura de archivos

- Estructura mínima esperada al terminar:
  - `README.md` en la raíz (decisión + justificación + árbol mínimo).
  - `gatic/` creado con el esqueleto Laravel:
    - `gatic/artisan`
    - `gatic/composer.json`
    - `gatic/.env.example`
- No mover ni renombrar:
  - `_bmad/`, `_bmad-output/`, `docsBmad/`, `project-context.md`, `COMMIT_CONVENTIONS.md`.
- Si ya existe un `gatic/` previo:
  - No mezclar dos apps; decidir si se reemplaza o se parte de ese `gatic/` (documentar la decisión en `README.md`).

### Requisitos de verificación (tests / checks)

- Checks mínimos (local):
  - `php -v` confirma PHP 8.2+
  - `composer -V` confirma Composer disponible
  - `php gatic/artisan --version` confirma Laravel 11.x
- Check de repo (sin “setup” extra):
  - `git status` limpio (solo cambios esperados: `README.md`, `.gitignore`, `gatic/`).

### Git intelligence (contexto reciente)

- Commit recientes (planning/docs): el repo hoy contiene principalmente artefactos BMAD y documentos (sin app Laravel aún).
- Implicación para DEV: los primeros cambios “de código” de verdad vivirán dentro de `gatic/` y deben minimizar ruido fuera de esa carpeta.

### Project Structure Notes

- Layout alineado a arquitectura: raíz para BMAD/docs; app Laravel dentro de `gatic/`.
- No hay conflictos detectados en este punto (todavía no existe código de aplicación).

### References

- Backlog/AC: `_bmad-output/project-planning-artifacts/epics.md` (Epic 1, Story 1.1).
- Arquitectura (layout `gatic/` + comandos): `_bmad-output/architecture.md` (“Selected Starter” y “Project Structure & Boundaries”).
- Bible (stack y restricciones): `docsBmad/project-context.md` (“Baseline técnico”, “Restricciones”).
- Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`.
- Issue fuente (GitHub): https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/5
- Docs Laravel 11 (instalación): https://laravel.com/docs/11.x/installation

## Dev Agent Record

### Agent Model Used

Claude Sonnet 4.5 (via Claude Code CLI)

### Debug Log References

- Verificaciones realizadas: PHP 8.4.16, Composer 2.9.2, Laravel 11.47.0
- Todas las AC verificadas exitosamente el 2025-12-28T01:29:27-06:00
- Estado del repo limpio (solo cambios esperados en git status)

### Implementation Plan

1. ✅ Documentado layout del repo con decisión de app en `gatic/` (README.md)
2. ✅ Laravel 11.47.0 instalado en subcarpeta `gatic/` usando PHP 8.4.16
3. ✅ Ajustado .gitignore para ignorar artefactos de `gatic/` (vendor, node_modules, .env, etc.)
4. ✅ Verificadas todas las acceptance criteria

### Completion Notes List

- ✅ README.md creado con decisión de layout, justificación y árbol mínimo del repo
- ✅ Laravel 11.47.0 instalado exitosamente en `gatic/` usando Composer 2.9.2
- ✅ Verificado que `gatic/.env.example` existe sin secretos
- ✅ Configurado .gitignore para subcarpeta `gatic/` (env files, vendor, node_modules, build artifacts, caches)
- ✅ Confirmado que `gatic/.env` está ignorado por git (no se commitea)
- ✅ Todas las AC (1-4) verificadas y cumplidas

### Story Completion Status

✅ **COMPLETADO** - Todas las tareas ejecutadas, todas las AC verificadas. Historia lista para code review.

### File List

- `.gitignore` (modificado: agregada sección para gatic/)
- `README.md` (nuevo: documentación del layout del repo)
- `gatic/` (nuevo: proyecto Laravel 11.47.0 completo)
  - `gatic/artisan`
  - `gatic/composer.json`
  - `gatic/composer.lock`
  - `gatic/.env.example`
  - `gatic/.gitignore` (propio de Laravel)
  - `gatic/.gitattributes`
  - `gatic/.editorconfig`
  - `gatic/package.json`
  - `gatic/vite.config.js`
  - `gatic/tailwind.config.js` (Laravel default, será reemplazado por Bootstrap en Story 1-4)
  - `gatic/postcss.config.js`
  - `gatic/phpunit.xml`
  - `gatic/README.md` (Laravel default)
  - `gatic/app/` (Models, Http, Providers)
  - `gatic/bootstrap/` (cache, app.php)
  - `gatic/config/` (10 archivos de configuración)
  - `gatic/database/` (factories, migrations, seeders)
  - `gatic/public/` (index.php, assets)
  - `gatic/resources/` (css, js, views)
  - `gatic/routes/` (web.php, console.php)
  - `gatic/storage/` (app, framework, logs)
  - `gatic/tests/` (Feature, Unit)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (modificado: status actualizado)
- `_bmad-output/implementation-artifacts/1-1-repo-inicial-layout-laravel-11-base.md` (modificado: tareas marcadas, notas agregadas)

---

## Senior Developer Review (AI)

**Reviewer:** Code Review Workflow (Adversarial)  
**Fecha:** 2025-12-28T01:29:27-06:00  
**Resultado:** ✅ APROBADO con observaciones menores

### Issues Encontrados y Resueltos

| # | Severidad | Descripción | Estado |
|---|-----------|-------------|--------|
| 1 | INFO | PHP 8.4 usado en desarrollo (requisito es 8.2+) | ✅ Documentado en architecture.md |
| 2 | MEDIO | `gatic/` y `README.md` no commiteados | ⏳ Pendiente git add/commit |
| 3 | MEDIO | `.gitignore` modificado no commiteado | ⏳ Pendiente git add/commit |
| 4 | BAJO | File List incompleto | ✅ Corregido |
| 5 | INFO | Laravel incluye Tailwind por default | ✅ Documentado (será reemplazado en 1-4) |
| 6 | BAJO | `gatic/.env` existe (verificar placeholders) | ✅ Ignorado por git |
| 7 | MEDIO | `sprint-status.yaml` sin commitear | ⏳ Pendiente git add/commit |
| 8 | BAJO | README mencionaba `scripts/` inexistente | ✅ Corregido |
| 9 | BAJO | Fecha sin formato ISO 8601 | ✅ Corregido |
| 10 | BAJO | Línea en blanco extra en `.gitignore` | ✅ Corregido |

### Notas del Reviewer

- **PHP 8.4:** Totalmente válido. Laravel 11 requiere PHP 8.2 como mínimo. La arquitectura fue actualizada para documentar que el desarrollo se realiza con PHP 8.4.
- **Tailwind CSS:** El skeleton de Laravel 11 incluye Tailwind por default. Esto será reemplazado por Bootstrap 5 en la Story 1-4 según la arquitectura.
- **Commits pendientes:** Todos los archivos nuevos/modificados deben ser commiteados para completar la historia.

### Acción Requerida

Ejecutar los siguientes comandos para completar la historia:

```bash
git add .
git commit -m "feat(gate-0): initialize Laravel 11 in gatic/ + repo layout (Story 1.1)"
```
