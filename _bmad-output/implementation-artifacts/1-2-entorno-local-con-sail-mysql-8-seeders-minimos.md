# Story 1.2: Entorno local con Sail + MySQL 8 + seeders mínimos

Status: done

Story Key: 1-2-entorno-local-con-sail-mysql-8-seeders-minimos  
Tracking: _bmad-output/implementation-artifacts/sprint-status.yaml  
Gate: 0 (Repo listo)  
GitHub: https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/7

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a desarrollador del proyecto,
I want levantar la app desde `gatic/` usando Laravel Sail (Docker) con MySQL 8.0 y seeders mínimos,
so that el equipo pueda iterar rápido y reproducir el entorno local de forma consistente (paridad local ↔ prod).

## Acceptance Criteria

1. **Sail + MySQL (arranque)**
   - **Given** Docker instalado y ejecutándose, y el repo clonado
   - **When** se ejecuta `./vendor/bin/sail up -d` desde `gatic/`
   - **Then** los contenedores levantan sin errores
   - **And** la app responde en el entorno local esperado (por defecto `http://localhost:${APP_PORT}`).

2. **Migraciones + seeders mínimos**
   - **Given** una base de datos vacía
   - **When** se ejecuta `./vendor/bin/sail artisan migrate --seed` desde `gatic/`
   - **Then** las migraciones aplican sin errores
   - **And** existen datos mínimos para operar el sistema en desarrollo local:
     - roles fijos `Admin`, `Editor`, `Lector` (según arquitectura: `users.role`)
     - al menos 1 usuario Admin inicial (credenciales documentadas para dev).

3. **Paridad de versiones (DB)**
   - El servicio de base de datos local usa **MySQL 8.0** (paridad con la arquitectura on‑prem).

## Tasks / Subtasks

- [ ] Preparación del entorno local (AC: 1)
  - [ ] Confirmar prerequisitos: Docker (Desktop) + Compose v2; puertos libres `APP_PORT` y `3306` (o configurar forwards)
  - [ ] Confirmar compatibilidad de PHP en host para `composer install` (actualmente el repo requiere **PHP >= 8.4** por `composer.lock`)
- [ ] Instalar scaffolding de Sail con MySQL (AC: 1)
  - [ ] En `gatic/`, ejecutar `php artisan sail:install --with=mysql --php=8.4` (evitar default `--php=8.5`)
  - [ ] Verificar que se generó archivo de compose (`gatic/compose.yaml` por default; o `docker-compose.yml` si ya existía)
- [ ] Alinear MySQL a 8.0 (paridad con arquitectura) (AC: 3)
  - [ ] En el compose generado, cambiar el servicio `mysql` a imagen `mysql:8.0` (Sail stub actual usa `mysql:8.4`)
  - [ ] Verificar que el script de creación de DB de testing se mantiene montado (para `phpunit`)
- [ ] Configurar variables de entorno para Sail + MySQL (AC: 1, 2)
  - [ ] Asegurar `WWWGROUP` y `WWWUSER` definidos (ej. `1000`) para evitar fallos de build/permisos
  - [ ] Definir `APP_PORT` (recomendado `8080` si `80` requiere privilegios o está ocupado)
  - [ ] Ajustar `.env.example` para MySQL en Sail (sin secretos): `DB_CONNECTION=mysql`, `DB_HOST=mysql`, `DB_PORT=3306`, `DB_DATABASE=gatic`, `DB_USERNAME=sail`, `DB_PASSWORD=password`
- [ ] Seeders mínimos para desarrollo (AC: 2)
  - [ ] Agregar migración para `users.role` (valores: `Admin|Editor|Lector`) según arquitectura (`users.role`)
  - [ ] Actualizar `DatabaseSeeder` (o seeders dedicados) para crear usuarios base:
    - Admin: `admin@gatic.local` (role `Admin`)
    - Editor: `editor@gatic.local` (role `Editor`)
    - Lector: `lector@gatic.local` (role `Lector`)
  - [ ] Documentar credenciales de dev (password de ejemplo) en este story (Dev Notes)
- [ ] Verificación end-to-end en entorno limpio (AC: 1, 2)
  - [ ] `./vendor/bin/sail up -d`
  - [ ] `./vendor/bin/sail artisan migrate:fresh --seed`
  - [ ] Confirmar respuesta HTTP (ej. abrir `http://localhost:${APP_PORT}`) y que existen usuarios seed en DB
- [ ] Documentación mínima de uso (AC: 1)
  - [ ] En `README.md` (raíz) agregar sección “Dev local con Sail” (up/down, migrate/seed, tests, troubleshooting Windows/WSL2)

## Dev Notes

### Contexto actual (antes de tocar código)

- La app Laravel vive en `gatic/` (Story 1.1 ya completada) y el repo raíz se reserva para BMAD/docs.
- Hoy `gatic/` NO tiene archivo de compose (`compose.yaml` / `docker-compose.yml`), por lo que `./vendor/bin/sail up` aún no aplica.
- `.env` / `.env.example` todavía usan `DB_CONNECTION=sqlite` por default, pero el stack objetivo es MySQL 8 en Sail.

### Contexto de Gate / Epic (para evitar decisiones aisladas)

- Este story pertenece a **Gate 0 (Repo listo)**: objetivo = entorno local reproducible (Sail+MySQL8) para poder ejecutar las siguientes historias sin fricción.
- Valor: baja el “setup time” del equipo y evita divergencias local↔prod que generan bugs difíciles de reproducir.

### Historias relacionadas / dependencias

- ✅ Story 1.1: Laravel 11 en `gatic/` + layout del repo (ya está).
- ⏭️ Story 1.3: Autenticación base (Breeze Blade) — se apoya en tener DB MySQL + seeders.
- ⏭️ Story 1.6: Roles fijos + Policies/Gates — reutiliza `users.role` y usuarios seed de esta historia.
- ⏭️ Story 1.7: Calidad/CI (Pint/PHPUnit/Larastan) — requiere que `sail test` funcione (DB `testing` creada).

**Mapa completo (Epic 1):** 1.1 Repo+Laravel base → **1.2 Sail+MySQL+seeders (esta)** → 1.3 Auth (Breeze) → 1.4 Bootstrap 5 (sin Tailwind) → 1.5 Livewire 3 → 1.6 Roles/Policies → 1.7 CI/calidad → 1.8 Layout (sidebar/topbar) → 1.9 Componentes UX → 1.10 Errores prod con ID → 1.11 Patrón polling.

### Alcance

**Lo que SÍ incluye:**
- Scaffolding de Sail + MySQL y variables necesarias para que `sail up -d` funcione en un clone limpio.
- Paridad de base de datos local: **MySQL 8.0**.
- Seeders mínimos (roles fijos + usuario Admin inicial) para poder avanzar historias siguientes sin fricción.
- Documentación mínima en `README.md` (raíz) para levantar/derribar, migrar/seed, tests.

**Lo que NO incluye (evitar scope creep):**
- Breeze / pantallas de auth / Bootstrap / Livewire / CI (historias 1.3–1.7).
- Implementar RBAC completo (Policies/Gates/UI) — aquí solo se “prepara” el dato (`users.role`) y usuarios base.

### Requisitos técnicos (guardrails) — DEV AGENT GUARDRAILS

- **Sail corre en Linux/WSL2**: en Windows usar WSL2 o Git Bash; `vendor/bin/sail` es un script bash.
- El compose de Sail requiere `WWWGROUP` (build arg) y recomienda `WWWUSER` para evitar problemas de permisos.
- Evitar puertos privilegiados/ocupados:
  - recomendado `APP_PORT=8080` (en vez de 80)
  - si `3306` está ocupado, usar `FORWARD_DB_PORT=3307` (y documentarlo).
- No comitear `.env` (solo ajustar y comitear `.env.example`).
- Mantener credenciales “conocidas” solo para dev local; no asumir esto para prod.

### Cumplimiento de arquitectura (obligatorio)

- **Local dev:** Sail (Docker) + MySQL 8.0.
- **Stack:** Laravel 11 + PHP 8.2+ (el repo hoy está bloqueado por dependencias con **PHP >= 8.4**).
- **Roles fijos:** `Admin`, `Editor`, `Lector` como `users.role` (sin Spatie en MVP por ahora).

### Requisitos de librerías / frameworks

- Usar `laravel/sail` vía `php artisan sail:install --with=mysql --php=8.4`.
  - Nota: Sail actual genera `compose.yaml` por default.
- Ajustar el servicio `mysql` a **MySQL 8.0** (Sail stub actual usa `mysql:8.4`).

### Requisitos de estructura / archivos a tocar

- `gatic/compose.yaml` (o `gatic/docker-compose.yml` si se decide renombrar para alinear documentación)
- `gatic/.env.example`
- `gatic/database/migrations/*add_role_to_users_table*.php`
- `gatic/database/seeders/*` (o solo `DatabaseSeeder.php`)
- `gatic/phpunit.xml` (Sail lo ajusta para DB `testing`)
- `README.md` (raíz): sección “Dev local con Sail”

### Requisitos de testing

- Smoke tests mínimos (no flaky):
  - `./vendor/bin/sail up -d`
  - `./vendor/bin/sail artisan migrate:fresh --seed`
  - `./vendor/bin/sail test` (o `./vendor/bin/sail phpunit`) después de migrar
- Confirmar que `phpunit.xml` apunta a DB `testing` (Sail monta script para crearla).

### Inteligencia de historia previa (Story 1.1)

- La app ya está creada en `gatic/` con Laravel 11 y dependencias instaladas.
- Importante: `gatic/.env.example` y `gatic/.env` quedaron con `DB_CONNECTION=sqlite` por default; esta historia debe mover el flujo de dev a MySQL vía Sail.
- El repo ya usa convención de commits `feat(gate-0): ...` y layout documentado en `README.md` (raíz).

### Inteligencia de Git reciente

- Último commit relevante: `feat(gate-0): initialize Laravel 11 in gatic/ + repo layout (Story 1.1)`.
- Mantener cambios de esta historia acotados a `gatic/` + `README.md` (raíz).

### Info técnica reciente (Sail / MySQL)

- `php artisan sail:install` soporta `--with=mysql` y `--php=<versión>`; el default actual es `--php=8.5`, así que hay que fijarlo a `8.4` por consistencia.
- Sail genera `compose.yaml` y usa por default `APP_PORT:-80` y `VITE_PORT:-5173`.
- El stub actual de MySQL en Sail usa `mysql:8.4`; para paridad con el server on‑prem, pinnear a `mysql:8.0`.

### Credenciales sugeridas (solo dev local)

- Admin: `admin@gatic.local` / `password` (role `Admin`)
- Editor: `editor@gatic.local` / `password` (role `Editor`)
- Lector: `lector@gatic.local` / `password` (role `Lector`)

### Project Structure Notes

- Mantener la separación: raíz = BMAD/docs; app = `gatic/`.
- No introducir carpetas nuevas en raíz; cualquier automation de dev queda dentro de `gatic/` o documentada.

### References

- Backlog/AC (fuente de verdad): `_bmad-output/project-planning-artifacts/epics.md` (Epic 1, Story 1.2).
- Bible: `docsBmad/project-context.md` (“Baseline Técnico”, “Seeders robustos”, “Local dev: Sail”).
- Reglas rápidas: `project-context.md` (“Development Workflow Rules”, “Framework-Specific Rules”).
- Arquitectura: `_bmad-output/architecture.md` (“Infrastructure & Deployment”, “Development Workflow Integration”).
- Sail (código del repo): `gatic/vendor/laravel/sail/src/Console/InstallCommand.php`, `gatic/vendor/laravel/sail/stubs/compose.stub`, `gatic/vendor/laravel/sail/stubs/mysql.stub`.
- Docs oficiales: https://laravel.com/docs/11.x/sail

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- Fuentes analizadas: `_bmad-output/project-planning-artifacts/epics.md`, `docsBmad/project-context.md`, `project-context.md`, `_bmad-output/architecture.md`, Story previa `1-1-repo-inicial-layout-laravel-11-base.md`
- Sail verificado en código: `gatic/vendor/laravel/sail/src/Console/InstallCommand.php`, stubs `gatic/vendor/laravel/sail/stubs/compose.stub` y `gatic/vendor/laravel/sail/stubs/mysql.stub`
- Nota de plataforma: `gatic/vendor/composer/platform_check.php` requiere PHP >= 8.4 para correr Artisan fuera de contenedor

### Implementation Plan

1. Generar compose de Sail con MySQL y fijar PHP runtime (`--php=8.4`)
2. Pinnar MySQL a 8.0 y configurar `.env.example` (APP_PORT, WWWUSER/WWWGROUP, DB_*)
3. Crear migración `users.role` y seeders mínimos (Admin/Editor/Lector)
4. Verificar `sail up -d` + `migrate:fresh --seed` + `sail test`
5. Documentar setup local en `README.md`

### Completion Notes List

- ✅ Story context creado y marcado `ready-for-dev`
- ✅ Validación ejecutada y reporte generado
- ✅ `sprint-status.yaml` actualizado a `ready-for-dev`

### Story Completion Status

✅ **READY-FOR-DEV** — Contexto y guardrails completos para implementar Sail+MySQL8.0+seeders mínimos, con sprint tracking actualizado y validación registrada.

### File List

- `_bmad-output/implementation-artifacts/1-2-entorno-local-con-sail-mysql-8-seeders-minimos.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (status update)
- `_bmad-output/implementation-artifacts/validation-report-2025-12-28T082303Z.md` (post-validación)
