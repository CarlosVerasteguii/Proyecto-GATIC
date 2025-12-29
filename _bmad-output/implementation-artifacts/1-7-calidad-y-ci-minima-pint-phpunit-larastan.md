# Story 1.7: Calidad y CI mínima (Pint + PHPUnit + Larastan)

Status: done

Story Key: 1-7-calidad-y-ci-minima-pint-phpunit-larastan  
Tracking: _bmad-output/implementation-artifacts/sprint-status.yaml  
Gate: 0 (Repo listo)  
GitHub (referencia): https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/4, https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/16, https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/17, https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/18  
Fuentes: _bmad-output/project-planning-artifacts/epics.md, _bmad-output/implementation-artifacts/epics-github.md, _bmad-output/architecture.md, docsBmad/project-context.md, project-context.md, gatic/composer.json, gatic/phpunit.xml, _bmad-output/implementation-artifacts/1-6-roles-fijos-policies-gates-base-server-side.md

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a mantenedor del repositorio,
I want un pipeline de CI que ejecute formato, tests y análisis estático,
so that los merges mantengan calidad y no rompan el sistema (Arquitectura, Calidad/CI).

## Acceptance Criteria

1. **CI bloquea merges si falla calidad**
   - **Given** un Pull Request abierto
   - **When** corre el workflow de CI
   - **Then** ejecuta `pint --test`, `php artisan test` (PHPUnit) y `phpstan` (Larastan)
   - **And** el PR bloquea el merge si alguna verificación falla

2. **Comandos reproducibles en local (sin “magia oculta”)**
   - **Given** el repositorio recién clonado
   - **When** se ejecutan los comandos de calidad en local (idealmente en Sail, consistente con el proyecto)
   - **Then** corren sin configuración adicional oculta
   - **And** existe documentación mínima para ejecutarlos

3. **Pint configurado y el código actual cumple**
   - **Given** `laravel/pint` en `require-dev`
   - **When** se ejecuta `./vendor/bin/pint --test`
   - **Then** termina sin errores
   - **And** existe un `pint.json` versionado con reglas acordadas

4. **Larastan configurado con nivel inicial y baseline si aplica**
   - **Given** `larastan/larastan` instalado
   - **When** se ejecuta `./vendor/bin/phpstan analyse`
   - **Then** el análisis corre y pasa en el nivel acordado (inicial: 5)
   - **And** si hay deuda técnica preexistente, existe un baseline versionado para no bloquear el avance

## Tasks / Subtasks

- [x] 1) Pint (AC: 3)
  - [x] Agregar `gatic/pint.json` con preset Laravel + reglas mínimas acordadas
  - [x] Ejecutar `./vendor/bin/pint --test` y corregir formato hasta pasar

- [x] 2) Larastan (AC: 4)
  - [x] Instalar `larastan/larastan` (compatible con Laravel 11)
  - [x] Crear `gatic/phpstan.neon` (incluye `vendor/larastan/larastan/extension.neon`) y setear `level: 5`
  - [x] Ejecutar `./vendor/bin/phpstan analyse` (con `--memory-limit` si se requiere)
  - [x] Si hay errores legacy, generar `phpstan-baseline.neon` y configurarlo en `phpstan.neon`

- [x] 3) PHPUnit / `php artisan test` (AC: 1, 2)
  - [x] Confirmar que `php artisan test` pasa en local (Sail) con configuración de DB equivalente a `gatic/phpunit.xml`
  - [x] Documentar comandos mínimos (local + CI) para correr calidad

- [x] 4) GitHub Actions CI mínimo (AC: 1, 2)
  - [x] Crear `.github/workflows/ci.yml` (en la raíz del repo)
  - [x] Configurar job con PHP 8.2, Composer cache, e instalar deps dentro de `gatic/`
  - [x] Provisionar MySQL 8 como service (host `mysql`) para que coincida con `gatic/phpunit.xml`
  - [x] Ejecutar en CI (en orden): `./vendor/bin/pint --test`, `php artisan test`, `./vendor/bin/phpstan analyse`
  - [x] Disparadores: `push` y `pull_request` a `main`

## Dev Notes

### Developer Context

Este repo tiene la app Laravel dentro de `gatic/` (no en la raíz). En este momento:
- `gatic/composer.json` ya incluye `laravel/pint` y `phpunit/phpunit` en `require-dev`
- No existe `gatic/pint.json` (se requiere para reglas consistentes)
- No existe `gatic/phpstan.neon` / `larastan/larastan` (se requiere para análisis estático)
- No existe `.github/workflows/` (se requiere para CI mínimo)

Punto crítico: `gatic/phpunit.xml` está configurado para correr con MySQL (`DB_HOST=mysql`). Para CI, o se provisiona un service MySQL con hostname `mysql`, o se ajusta la estrategia explícitamente (no cambiar “a escondidas”).

### Technical Requirements (Dev Agent Guardrails)

- **No romper el stack base:** Laravel 11 + PHP 8.2+ + MySQL 8 + Livewire 3 + Bootstrap 5 (no introducir herramientas/librerías fuera del scope).
- **Comandos canónicos (y orden):** `./vendor/bin/pint --test` → `php artisan test` → `./vendor/bin/phpstan analyse`.
- **Sin auto-fix en CI:** CI solo valida (`pint --test`), los fixes se hacen en commits locales.
- **Paridad DB en tests:** `gatic/phpunit.xml` usa MySQL (`DB_HOST=mysql`); CI debe reflejarlo (service MySQL 8) salvo decisión explícita documentada.
- **No subir PHPUnit a v12 en este repo:** `phpunit/phpunit` v12 requiere PHP ≥8.3; este proyecto está en PHP 8.2 (mantener PHPUnit 11.x).
- **Rutas/identificadores:** identificadores de código/DB/rutas en inglés; copy/UI en español (ver `project-context.md`).

### Architecture Compliance

- **CI mínimo requerido por arquitectura:** GitHub Actions con `pint --test`, `phpunit` (vía `php artisan test`) y `larastan/larastan` (ver `_bmad-output/architecture.md`).
- **Estructura esperada en raíz de la app:** `gatic/pint.json` y `gatic/phpstan.neon` viven junto a `gatic/phpunit.xml` (ver estructura propuesta en `_bmad-output/architecture.md`).
- **Repo multi-root:** la app está en `gatic/`, pero los workflows de GitHub Actions viven en la raíz del repo (`.github/workflows/*`).

### Library / Framework Requirements

- **PHP:** 8.2 (ver `gatic/composer.json` `config.platform.php`)
- **Laravel:** `laravel/framework` ^11.31 (no cambiar de major)
- **Pint:** `laravel/pint` (ya está en `require-dev`; Packagist muestra `v1.22.0` como versión estable reciente; mantener 1.x)
- **PHPUnit:** mantener 11.x (Packagist muestra PHPUnit 12 requiere PHP ≥8.3)
- **Larastan:** instalar `larastan/larastan:^3.0` (GitHub/Packagist: latest 3.8.1, soporte para Laravel 11.16+)

### File Structure Requirements

- Crear/editar archivos **en los paths exactos**:
  - `gatic/pint.json`
  - `gatic/phpstan.neon`
  - `gatic/phpstan-baseline.neon` (solo si aplica)
  - `.github/workflows/ci.yml`
- Todos los comandos de PHP/Composer deben correr con working directory `gatic/` (CI incluido).
- Si se documentan comandos, preferir `README.md` (raíz) o `gatic/README.md` con una sección “Calidad/CI”.

### Testing Requirements

- Local (recomendado: Sail, para paridad):
  - Pint: `./vendor/bin/pint --test`
  - Tests: `php artisan test`
  - Larastan: `./vendor/bin/phpstan analyse`
  - En Sail (ejemplo): `docker compose exec -T laravel.test ./vendor/bin/pint --test` (workdir `gatic/`)
- CI (GitHub Actions):
  - Debe provisionar MySQL 8 como service llamado `mysql` (para que `DB_HOST=mysql` funcione)
  - Antes de correr tests: `cp .env.example .env` + `php artisan key:generate` + `php artisan migrate --force`
  - Los tests deben correr deterministas (sin dependencias externas), alineados a `project-context.md`

### Previous Story Intelligence (Story 1.6)

- En Story 1.6 ya se ejecutó Pint y la suite de tests en Sail; la expectativa es formalizar esto (config + CI) sin cambiar el comportamiento funcional.
- Hay feature tests ya creados en `gatic/tests/Feature/*` (RBAC, Livewire smoke). El CI debe ejecutarlos con DB equivalente (MySQL) para evitar falsos positivos/negativos.
- Convención de commits y trazabilidad ya en uso (`feat(gate-0): ... (Story X.Y)`); CI debe integrarse sin fricción al flujo trunk-based.

### Git Intelligence Summary

Últimos commits (contexto reciente):
- `10d9ece` feat(gate-0): roles fijos, policies y gates base server-side (Story 1.6)
- `9ac1020` feat(gate-0): install Livewire 3, integrate in layout (Story 1.5)
- `3957f4f` chore(tracking): mark Story 1.4 as done
- `7c71b79` feat(gate-0): Bootstrap 5 UI base, remove Tailwind, add CFE branding (Story 1.4)
- `3a21bdd` feat(gate-0): implement Breeze auth with Bootstrap 5 (Story 1.3)

Implicación para esta story: CI debe proteger este baseline (RBAC + Livewire + Bootstrap) y evitar que cambios de formato/tests/estático rompan Gate 0.

### Latest Tech Information (para evitar implementaciones desactualizadas)

- **Pint:** Packagist muestra `laravel/pint` `v1.22.0` (PHP ^8.2). Mantenerse en 1.x; usar `pint --test` en CI.
- **Larastan:** Packagist/GitHub muestran línea 3.x con latest `3.8.1` (dic 2025) y soporte para Laravel 11.16+. Recomendación: `larastan/larastan:^3.0`.
- **PHPUnit:** Packagist muestra PHPUnit 12.x requiere PHP ≥8.3; para PHP 8.2 se mantiene PHPUnit 11.x (coincide con `gatic/composer.json`).

### Project Context Reference (reglas que CI debe reforzar)

- “Merge solo con CI verde” y CI mínimo: `pint --test`, `phpunit`, `larastan/larastan` (ver `project-context.md` + `docsBmad/project-context.md`).
- Identificadores en inglés; copy UI en español.
- Tests: feature tests para flujos críticos, deterministas, sin dependencias externas (ver `project-context.md`).

### References

- Backlog (fuente de verdad): `_bmad-output/project-planning-artifacts/epics.md` (Story 1.7)
- Arquitectura: `_bmad-output/architecture.md` (sección CI mínimo + estructura propuesta)
- Reglas críticas (bible): `docsBmad/project-context.md` + `project-context.md`
- GitHub (referencia de issues): `_bmad-output/implementation-artifacts/epics-github.md` (Epic “Calidad y CI”)

## Story Completion Status

- Status: **in-progress**
- Completion note: CI + calidad minima (Pint, PHPUnit, Larastan) configurados; pendiente activar Branch protection en GitHub para bloquear merges si falla CI

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `git log -5 --oneline`
- `cd gatic; ./vendor/bin/pint --test`
- `cd gatic; php artisan test`
- `cd gatic; ./vendor/bin/phpstan analyse`
- `cd gatic; ./vendor/bin/phpstan analyse --no-progress`
- `cd gatic; docker compose exec -T laravel.test ./vendor/bin/pint --test`
- `cd gatic; docker compose exec -T laravel.test php artisan test`
- `cd gatic; docker compose exec -T laravel.test ./vendor/bin/phpstan analyse --no-progress`
- `cd gatic; docker compose exec -T laravel.test composer require --dev larastan/larastan:^3.0`

### Completion Notes List

- Story seleccionada automáticamente desde el primer backlog en `_bmad-output/implementation-artifacts/sprint-status.yaml` (key `1-7-*`).
- Requirements base de `_bmad-output/project-planning-artifacts/epics.md` + guardrails de `_bmad-output/architecture.md` + bible (`docsBmad/project-context.md`).
- Considera estructura multi-root (workflow en raíz, app en `gatic/`) y DB MySQL en tests (`gatic/phpunit.xml`).

- Agregado `gatic/pint.json` y verificado `./vendor/bin/pint --test` (pasa).
- Instalado `larastan/larastan` + `phpstan/phpstan`, creado `gatic/phpstan.neon` (level 5) y verificado `./vendor/bin/phpstan analyse` (pasa sin baseline).
- Ajustes menores para phpstan (tipos/enum roles + type-guard en `VerifyEmailController`) y limpieza de test de ejemplo.
- Agregado workflow `.github/workflows/ci.yml` con MySQL 8 para ejecutar calidad en PRs.
- Documentados comandos de calidad en `README.md` (incluye nota de protecci¢n de rama requerida para bloquear merges).
- Ajustado cache de Composer en CI para usar el directorio real de Composer.
- Ignorados artefactos locales (`validation-report-*.md`, `git-status.txt`) para evitar ruido en git.
- Email verification se mantiene deshabilitada (MVP); el ajuste en `VerifyEmailController` es solo para satisfacer Larastan.

### File List

- `_bmad-output/implementation-artifacts/1-7-calidad-y-ci-minima-pint-phpunit-larastan.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `.gitignore`
- `gatic/pint.json`
- `gatic/phpstan.neon`
- `gatic/composer.json` (agregar `larastan/larastan` + scripts opcionales)
- `.github/workflows/ci.yml`
- `README.md` (documentación mínima)

- `gatic/composer.lock`
- `gatic/app/Models/User.php`
- `gatic/app/Http/Controllers/Auth/VerifyEmailController.php`
- `gatic/app/Livewire/Admin/Users/UserForm.php`
- `gatic/tests/Unit/ExampleTest.php` (deleted)

## Change Log

- 2025-12-29: Configurado CI + calidad minima (Pint, PHPUnit, Larastan) y documentados comandos locales.
- 2025-12-29: Senior Dev Review: fixes aplicados + changes requested por Branch protection pendiente.

## Senior Developer Review (AI)

- Resultado: **Changes Requested** (falta enforcement real de bloqueo de merge por CI).
- Fixes aplicados en esta revisión:
  - README: aclarado PHP (8.2+) y añadida guía de Branch protection para que CI bloquee merges.
  - CI: cache de Composer corregido (usa `composer config cache-files-dir`).
  - Higiene git: ignorados `validation-report-*.md` y `git-status.txt`.
  - Scope: email verification sigue deshabilitada (MVP); cambios solo para Larastan en `VerifyEmailController`.
- Pendiente manual (GitHub): activar Branch protection en `main` requiriendo el check **CI**.
