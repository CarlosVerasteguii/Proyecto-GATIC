# Story 1.6: Roles fijos + policies/gates base (server-side)

Status: done

Story Key: 1-6-roles-fijos-policies-gates-base-server-side  
Tracking: _bmad-output/implementation-artifacts/sprint-status.yaml  
Gate: 0 (Repo listo)  
GitHub (referencia): https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/13, https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/14, https://github.com/CarlosVerasteguii/Proyecto-GATIC/issues/15  
Fuentes: _bmad-output/project-planning-artifacts/epics.md, _bmad-output/prd.md, _bmad-output/architecture.md, docsBmad/project-context.md, project-context.md, _bmad-output/implementation-artifacts/1-5-livewire-3-instalado-e-integrado-en-el-layout.md

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Admin,
I want gestionar usuarios y roles y que el sistema aplique autorización por rol,
so that el acceso esté controlado en todas las acciones (FR2, FR3).

## Acceptance Criteria

1. **Autorización server-side obligatoria (defensa en profundidad)**
   - **Given** los roles fijos (Admin/Editor/Lector)
   - **When** un usuario intenta ejecutar una acción no permitida por su rol
   - **Then** el servidor bloquea la operación (403 o redirección segura)
   - **And** la UI oculta/inhabilita acciones no permitidas (defensa en profundidad)

2. **Admin puede crear usuarios + asignar rol**
   - **Given** un Admin autenticado
   - **When** crea un usuario y le asigna un rol (Admin/Editor/Lector)
   - **Then** el usuario queda creado con ese rol
   - **And** el rol aplicado define su acceso efectivo al navegar el sistema

3. **Admin puede deshabilitar usuarios**
   - **Given** un Admin autenticado
   - **When** deshabilita un usuario
   - **Then** ese usuario no puede iniciar sesión
   - **And** cualquier sesión activa queda invalidada o expira según la política definida

4. **Admin puede cambiar rol y aplica inmediato**
   - **Given** un Admin autenticado
   - **When** cambia el rol de un usuario
   - **Then** los permisos efectivos del usuario cambian inmediatamente (server-side)
   - **And** la UI refleja el nuevo rol en el menú y acciones visibles

5. **Hardening: Editor no accede a gestión de usuarios por URL directa**
   - **Given** un usuario con rol Editor
   - **When** intenta acceder por URL directa a gestión de usuarios
   - **Then** no obtiene acceso a la pantalla
   - **And** se redirige o muestra un 403 según la política definida

## Tasks / Subtasks

- [x] 1) Modelo de roles fijos (AC: 1, 2, 4)
  - [x] Confirmar/estandarizar fuente de verdad de roles: `Admin`, `Editor`, `Lector` (sin Spatie en MVP)
  - [x] Implementar `App\\Enums\\UserRole` (string-backed) o constantes equivalentes (evitar strings “mágicos”)
  - [x] Asegurar cast/validación de `users.role` y defaults consistentes (DB + app)

- [x] 2) Estado de usuario (habilitado/deshabilitado) (AC: 3)
  - [x] Definir columna: `users.is_active` (bool) **o** `users.disabled_at` (timestamp) y documentar la política
  - [x] Migración + cast en `App\\Models\\User`
  - [x] UI/Admin: toggle deshabilitar/habilitar con confirmación (copy en español)

- [x] 3) Enforcement en login + sesiones activas (AC: 3)
  - [x] Bloquear login de usuarios deshabilitados (mensaje claro)
  - [x] Middleware `EnsureUserIsActive` en rutas `auth` para cerrar sesión si el usuario fue deshabilitado mientras estaba logueado (política sugerida: “en el siguiente request se invalida la sesión y se redirige a login”)

- [x] 4) Gates/Policies base (AC: 1, 5)
  - [x] Crear `App\\Providers\\AuthServiceProvider` (Laravel 11: registrar en `bootstrap/providers.php`)
  - [x] Definir `Gate::before()` para que `Admin` tenga acceso total (MVP)
  - [x] Definir gates mínimos reutilizables:
    - [x] `admin-only` (Admin)
    - [x] `users.manage` (Admin)
    - [x] `attachments.manage` (Admin/Editor) y `attachments.view` (Admin/Editor) (preparar para Epic 8)
    - [x] `catalogs.manage` (Admin/Editor) (preparar para Epic 2)
  - [x] Crear una `BasePolicy`/helper para estandarizar reglas por rol (Admin all, Editor CRUD sin admin-only, Lector lectura)
  - [x] Asegurar uso server-side en acciones Livewire (`$this->authorize(...)`/`Gate::authorize(...)`) y en rutas (`can:` middleware)

- [x] 5) UI mínima: Gestión de usuarios (Admin) (AC: 2, 3, 4, 5)
  - [x] Crear módulo Livewire `App\\Livewire\\Admin\\Users\\UsersIndex` (listado) + `UserForm` (crear/editar)
  - [x] Listado: nombre, email, rol, estado (activo), acciones (editar, deshabilitar/habilitar)
  - [x] Crear usuario: nombre, email, password (set inicial), rol (select)
  - [x] Editar usuario: rol, estado (activo), reset de password (si se decide en MVP; si NO, documentar como follow-up)
  - [x] Integrar navegación mínima (ej. link “Usuarios” visible solo Admin usando `@can('users.manage')`)
  - [x] Proteger rutas `/admin/*` con `auth` + `can:users.manage` (Editor/Lector => 403 o redirección segura)

- [x] 6) Documentación de permisos (AC: 1, 2, 4, 5)
  - [x] Documentar matriz de permisos por rol (en `docsBmad/project-context.md` si aplica o `docsBmad/rbac.md` + referencia desde project-context)
  - [x] Alinear con “Fuente de verdad” (FR2/FR3, NFR4/NFR5)

- [x] 7) Tests (AC: 1-5)
  - [x] Feature tests de autorización:
    - [x] Admin puede acceder a `/admin/users` (200)
    - [x] Editor/Lector => 403 (o redirect seguro según política) en `/admin/users`
  - [x] Feature tests de login: usuario deshabilitado no puede iniciar sesión (mensaje estable)
  - [x] Feature test de sesión activa: usuario se deshabilita, siguiente request invalida sesión (middleware)
  - [x] Tests de gates/policies (si se definen como gates nombrados)

## Dev Notes

### Contexto del repo (estado actual observado)

- La app Laravel vive en `gatic/`; la raíz del repo se reserva para BMAD/docs/artefactos.
- Ya existe `users.role` en DB: `gatic/database/migrations/2025_12_28_114810_add_role_to_users_table.php` (default `Lector`).
- Ya existe seeding de usuarios base con roles fijos (dev): `gatic/database/seeders/DatabaseSeeder.php`.
- **Aún no hay** `AuthServiceProvider`, Gates, Policies, ni enforcement en rutas/acciones.
- Registro público ya está deshabilitado en `gatic/routes/auth.php` (Admin aprovisiona usuarios): esto hace **obligatoria** la UI de gestión de usuarios.

### Guardrails técnicos (MUST)

- **Server-side primero**: toda acción sensible debe pasar por `Gate::authorize(...)` / `$this->authorize(...)` en Livewire (no confiar en ocultar botones).
- **Sin Spatie en MVP**: roles fijos simples (Admin/Editor/Lector) con gates/policies propias.
- **Laravel 11**: providers declarados en `gatic/bootstrap/providers.php` (agregar `AuthServiceProvider` ahí).
- **Nombres/identificadores en inglés** (código/DB/rutas); copy de UI en español (ver `project-context.md`).
- Política sugerida para deshabilitar sesiones: middleware que invalida sesión en el próximo request si el usuario ya no está activo.

### Matriz de permisos (MVP, baseline)

- **Admin**: acceso total (incluye gestión de usuarios y overrides).
- **Editor**: operar inventario/activos (cuando existan módulos) pero **sin** gestión de usuarios.
- **Lector**: solo lectura (sin acciones destructivas ni adjuntos en MVP).

### Puntos de diseño a decidir (antes de codificar)

1. Columna de deshabilitado: `is_active` vs `disabled_at` (recomendación: `is_active` por simplicidad MVP).
2. Comportamiento ante acceso no autorizado:
   - Opción A: 403 con página amigable + botón “Volver al inicio”
   - Opción B: redirect a dashboard con flash “No autorizado”
   Elegir una y aplicar consistente (AC permite ambas).
3. Reset de password:
   - MVP actual deshabilita “forgot password”; decidir si Admin puede setear password manualmente desde UI.

## References

- Backlog (fuente de verdad): `_bmad-output/project-planning-artifacts/epics.md` (Story 1.6)
- PRD (FR2/FR3, NFR4/NFR5): `_bmad-output/prd.md`
- Arquitectura (RBAC, policies, estructura, Livewire-first): `_bmad-output/architecture.md`
- Reglas críticas (bible): `docsBmad/project-context.md` + `project-context.md`
- Contexto de implementación previa: `_bmad-output/implementation-artifacts/1-5-livewire-3-instalado-e-integrado-en-el-layout.md`
- GitHub (referencia): `_bmad-output/implementation-artifacts/epics-github.md` (Issues #13, #14, #15)
- Docs Laravel (autorización): https://laravel.com/docs/11.x/authorization

## Story Completion Status

- Status: **done**
- Completion note: Implementación completada (RBAC server-side + gestión de usuarios + enforcement de usuarios deshabilitados). Issues HIGH/MEDIUM corregidos; suite + smoke UX OK.

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `git log -5 --oneline` (para contexto de trabajo reciente)
- `docker exec -w /var/www/html gatic-laravel.test-1 ./vendor/bin/pint`
- `docker exec -w /var/www/html gatic-laravel.test-1 php artisan test`
- `docker compose exec -T laravel.test php artisan test`

### Completion Notes List

- Story seleccionada automáticamente desde el primer backlog en `sprint-status.yaml` (key `1-6-*`).
- Alineada a `_bmad-output/project-planning-artifacts/epics.md` (FR2/FR3) + `project-context.md` + `_bmad-output/architecture.md`.
- Incluye guardrails server-side, estructura Livewire-first y enfoque MVP sin Spatie.
- Roles fijos estandarizados con `App\\Enums\\UserRole` + cast/defaults consistentes en `App\\Models\\User`.
- Columna `users.is_active` agregada y aplicada en login + middleware (`EnsureUserIsActive`) para invalidar sesión en el siguiente request.
- Gates base agregados (incl. `users.manage`) y módulo Livewire Admin de usuarios (`/admin/users`) protegido por `can:users.manage`.
- Documentación RBAC agregada en `docsBmad/rbac.md` y referenciada desde `docsBmad/project-context.md`.
- Tests agregados para RBAC + usuarios deshabilitados; Pint + suite completa ejecutadas en Sail (PHP 8.4).

- UI: confirmaci¢n de habilitar/deshabilitar movida a `wire:confirm` (cancel real) y copy corregido.
- Hardening: Admin no puede cambiar su propio rol desde la UI (previene lock-out accidental).
- Validaci¢n: password opcional en edici¢n (solo valida fuerza si se ingresa).

### File List

- `_bmad-output/implementation-artifacts/1-6-roles-fijos-policies-gates-base-server-side.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `_bmad-output/implementation-artifacts/validation-report-2025-12-29T014615Z.md`
- `docsBmad/project-context.md`
- `docsBmad/rbac.md`
- `gatic/app/Enums/UserRole.php`
- `gatic/app/Http/Middleware/EnsureUserIsActive.php`
- `gatic/app/Http/Requests/Auth/LoginRequest.php`
- `gatic/app/Livewire/Admin/Users/UserForm.php`
- `gatic/app/Livewire/Admin/Users/UsersIndex.php`
- `gatic/app/Models/User.php`
- `gatic/app/Providers/AuthServiceProvider.php`
- `gatic/app/Providers/AppServiceProvider.php`
- `gatic/app/Support/Authorization/RoleAccess.php`
- `gatic/bootstrap/app.php`
- `gatic/bootstrap/providers.php`
- `gatic/database/factories/UserFactory.php`
- `gatic/database/migrations/2025_12_29_000000_add_is_active_to_users_table.php`
- `gatic/database/seeders/DatabaseSeeder.php`
- `gatic/resources/views/errors/403.blade.php`
- `gatic/resources/views/layouts/navigation.blade.php`
- `gatic/resources/views/livewire/admin/users/user-form.blade.php`
- `gatic/resources/views/livewire/admin/users/users-index.blade.php`
- `gatic/resources/views/livewire/dev/livewire-smoke-test.blade.php`
- `gatic/routes/auth.php`
- `gatic/routes/web.php`
- `gatic/tests/Feature/Admin/AdminLockoutPreventionTest.php`
- `gatic/tests/Feature/Admin/UsersAuthorizationTest.php`
- `gatic/tests/Feature/Auth/UserActiveTest.php`
- `gatic/tests/Feature/Dev/LivewireSmokeComponentTest.php`
- `gatic/tests/Feature/ExampleTest.php`
- `gatic/tests/Feature/LivewireInstallationTest.php`

### Change Log

- Added roles enum (`UserRole`) + cast/defaults for fixed roles (Admin/Editor/Lector).
- Added `users.is_active` + login enforcement and session invalidation middleware.
- Added base gates (`users.manage`, `catalogs.manage`, `attachments.*`) with Admin override (`Gate::before`).
- Added Admin Users management UI (Livewire) + `/admin/users` routes protected server-side; navbar link uses `@can`.
- Configured Laravel pagination to use Bootstrap 5 markup.
- Added RBAC documentation and feature tests; ran Pint + full test suite in Sail.
- Fixed confirm de habilitar/deshabilitar (usa `wire:confirm`).
- Fixed: Admin no puede cambiar su propio rol desde `UserForm` (hardening anti lock-out).
- Fixed: password en edici¢n es realmente opcional (solo valida si se ingresa).
