# Story 14.7: Perfil de usuario interno (campos extra) + preferencias UI

Status: done

Story Key: `14-7-perfil-usuario-y-preferencias-ui`  
Epic: `14` (Datos de negocio: garantías, costos, proveedores, configuración, timeline, dashboard avanzado)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Fecha: 2026-02-07
Story ID: `14.7`

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/epics.md` (Epic 14 / Story 14.7)
- `docsBmad/project-context.md` (bible: stack/UX/arquitectura)
- `project-context.md` (reglas lean: idioma, stack, testing)
- `_bmad-output/implementation-artifacts/architecture.md` (patrones y estructura)
- `_bmad-output/implementation-artifacts/ux.md` (patrones UX: layout denso, toggles, accesibilidad)
- Código actual (reutilización / puntos de extensión):
  - `gatic/app/Livewire/Admin/Users/UsersIndex.php` (listado Admin-only)
  - `gatic/app/Livewire/Admin/Users/UserForm.php` (crear/editar; validaciones; anti-lockout)
  - `gatic/resources/views/livewire/admin/users/users-index.blade.php` (tabla + Column Manager)
  - `gatic/resources/views/livewire/admin/users/user-form.blade.php` (form Admin-only)
  - `gatic/app/Models/User.php` (+ fillable/casts)
  - `gatic/database/migrations/0001_01_01_000000_create_users_table.php` (+ futuras columnas)
  - `gatic/resources/js/ui/theme-toggle.js` (tema; `localStorage`)
  - `gatic/resources/js/ui/density-toggle.js` (densidad; `localStorage`)
  - `gatic/resources/js/ui/sidebar-toggle.js` (sidebar; `localStorage`)
  - `gatic/resources/js/ui/column-manager.js` (columnas por tabla; `localStorage`)
  - `gatic/resources/views/layouts/partials/topbar.blade.php` (toggles UI)
- Inteligencia previa (story anterior en la misma épica):
  - `_bmad-output/implementation-artifacts/14-6-configuracion-del-sistema-settings.md` (patrón Admin + persistencia + tests)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Admin,
I want capturar campos extra de usuarios internos (departamento/puesto) y habilitar persistencia de preferencias UI por usuario,
so that el sistema sea más útil para operación y personalización (sin depender del navegador/dispositivo).

## Epic Context (Epic 14 completo, resumido)

Objetivo epic: extender datos “enterprise” (garantías/costos/vida útil/proveedores/configuración/timeline/dashboard avanzado) sin perder simplicidad operativa.

Historias en Epic 14 (para contexto cruzado):
- **14.1 Proveedores:** `Supplier` + relación con `Product`.
- **14.2 Contratos:** `Contract` + relación con `Asset`.
- **14.3 Garantías:** fechas + alertas en activos.
- **14.4 Costos:** `acquisition_cost` + valor inventario en dashboard.
- **14.5 Vida útil:** reemplazo/renovación + alertas.
- **14.6 Settings (done):** overrides DB + fallback a `config/gatic.php`.
- **14.7 (esta story):** campos extra de `User` + preferencias UI por usuario.
- **14.8 Timeline:** changelog/audit/notas/movimientos/adjuntos unificados por entidad.
- **14.9 Dashboard avanzado:** métricas negocio + actividad reciente.

Dependencias prácticas:
- Preferencias UI se montan sobre UX existente (toggles + Column Manager) y NO deben romperlo.
- El patrón “store” + “persistir solo si difiere de default” de 14.6 es la referencia principal para esta story.

## Acceptance Criteria

### AC1 - Campos extra de usuario (Admin-only)

**Given** un usuario autenticado con permiso `users.manage` (Admin)  
**When** edita un usuario en `Administración → Usuarios → Editar`  
**Then** puede mantener campos extra:
- `department` (nullable)
- `position` (nullable)  
**And** al guardar, los cambios persisten en DB.

**Validaciones mínimas:**
- ambos campos opcionales
- `max:255`
- UI copy en español; identificadores/campos en inglés.

### AC2 - Seguridad / RBAC (server-side)

**Given** un usuario `Editor` o `Lector` autenticado  
**When** intenta acceder a rutas de `Administración → Usuarios`  
**Then** recibe `403 Forbidden` (no basta con ocultar links en UI).

### AC3 - Preferencias UI por usuario (self-service, sin página nueva)

**Given** un usuario autenticado y activo  
**When** usa los toggles existentes en topbar (tema/densidad/sidebar) o el Column Manager (columnas por tabla)  
**Then** la preferencia se persiste **por usuario** en DB (no solo en `localStorage`)  
**And** se aplica automáticamente en recargas y nuevas sesiones  
**And** se mantiene compatibilidad con el comportamiento actual (fallback a `localStorage` si no hay preferencia en DB).

**Preferencias mínimas a persistir:**
- `ui.theme`: `light|dark`
- `ui.density`: `normal|compact`
- `ui.sidebar_collapsed`: `true|false`
- `ui.columns.{tableKey}`: lista de `hiddenColumnKeys` (strings) por tabla (ej. `admin-users`)

**UX (detalle, para evitar ambigüedad):**
- **Sin pantalla nueva:** el usuario configura todo desde los toggles existentes.
- **Aplicación inmediata:** al click, el cambio se aplica en DOM y se actualiza `localStorage` (para mantener comportamiento actual).
- **Persistencia silenciosa:** en background se hace POST para persistir en DB (sin toasts ruidosos por polling/acciones repetidas).
- **Accesibilidad:**
  - mantener `aria-pressed`, `title` y el cambio de icono/texto ya existentes.
  - no introducir elementos que dependan solo de color (usar icono/texto).
- **Column Manager:** al cambiar un checkbox, se guarda el set de columnas ocultas por `tableKey` y se persiste (con debounce).
- **Debounce recomendado:** 300–800ms para evitar spam a DB en cambios sucesivos de columnas.

**Precedencia (DB vs localStorage) — multi-dispositivo:**
- Si existe preferencia en DB para una key → **DB manda** (y se “hidrata” `localStorage` con ese valor al cargar).
- Si NO existe preferencia en DB → usar `localStorage` (comportamiento actual) y/o defaults del sistema.

**Degradación segura:**
- si falla el guardado a DB (offline/error), el toggle debe seguir funcionando vía `localStorage` sin romper la UI.

### AC4 - Control Admin (reset)

**Given** un Admin (`users.manage`)  
**When** edita un usuario en `Administración → Usuarios → Editar` y ejecuta “Restablecer preferencias UI”  
**Then** el sistema elimina preferencias UI persistidas en DB para ese usuario  
**And** la UI vuelve a defaults (por sistema/`localStorage`) en la siguiente carga.

## Tasks / Subtasks

- [x] 1) Persistencia de campos extra (AC1)
  - [x] Migración: agregar `department` y `position` a `users`
  - [x] Actualizar `App\Models\User` (fillable/casts si aplica)
  - [x] Extender `App\Livewire\Admin\Users\UserForm` + vista para editar/guardar campos
- [x] 2) Persistencia de preferencias UI por usuario (AC3)
  - [x] Crear tabla `user_settings` (o equivalente) con `user_id + key` único y `value` JSON
  - [x] Implementar store/servicio (`UserSettingsStore`) para `get/set/forget` con defaults seguros
  - [x] Endpoint(s) autenticados para actualizar preferencias del usuario actual (validación estricta)
  - [x] Sincronizar JS existente:
    - [x] `resources/js/ui/theme-toggle.js`
    - [x] `resources/js/ui/density-toggle.js`
    - [x] `resources/js/ui/sidebar-toggle.js`
    - [x] `resources/js/ui/column-manager.js`
  - [x] Bootstrapper inicial (layout) para aplicar preferencia guardada antes de que Vite cargue (evitar “flash” especialmente en tema)
- [x] 3) Reset Admin de preferencias (AC4)
  - [x] Acción admin-only para borrar preferencias UI del usuario editado
  - [x] Confirmación UI + mensaje de éxito
- [x] 4) Tests (RBAC + persistencia)
  - [x] Feature: Admin edita `department/position`
  - [x] Feature: no-admin obtiene 403 en rutas admin/users
  - [x] Feature: endpoint de preferencias guarda y valida valores (`light|dark`, etc.)
  - [x] Feature: reset admin borra preferencias

## Dev Notes

### Developer Context (existente)

**Módulo Usuarios (Admin-only):**
- Ya existe `Administración → Usuarios` con Livewire:
  - `gatic/app/Livewire/Admin/Users/UsersIndex.php`
  - `gatic/app/Livewire/Admin/Users/UserForm.php`
- Autorización server-side existente: `Gate::authorize('users.manage')` + middleware `can:users.manage` en rutas admin.
- En edición (`UserForm` con `$isEdit=true`) actualmente:
  - `name` y `email` se muestran deshabilitados
  - se permite cambiar `role`, `is_active` y `password`
  - hay guardrails para evitar lockout del último Admin activo.

**Preferencias UI (hoy):**
- Ya hay toggles en topbar (solo desktop):
  - Densidad (`data-density-toggle`) → `gatic/resources/js/ui/density-toggle.js` (`localStorage` key: `gatic-density-mode`)
  - Tema (`data-theme-toggle`) → `gatic/resources/js/ui/theme-toggle.js` (`localStorage` key: `gatic:theme`)
  - Sidebar colapsable (`data-sidebar-toggle`) → `gatic/resources/js/ui/sidebar-toggle.js` (`localStorage` key: `gatic-sidebar-collapsed`)
- Column Manager por tabla:
  - `gatic/resources/js/ui/column-manager.js` (prefijo `localStorage`: `gatic:columns:{tableKey}`)
  - Se aplica de forma idempotente y re-aplica tras morpheos de Livewire.

**Gap del story:** todo lo anterior persiste por **navegador** (localStorage). Esta story requiere persistencia **por usuario** (DB) y sincronización con lo ya existente (sin romper UX).

### Actionable intelligence (cosas que el dev NO debe romper)

- No re-habilitar el “Profile link” público en el dropdown de usuario (está comentado con nota de scope histórico en `topbar.blade.php`).
- No cambiar el gate `users.manage` ni relajar permisos: `/admin/users*` debe seguir siendo Admin-only.
- Mantener los guardrails anti-lockout de `UserForm` (no permitir deshabilitarse a sí mismo / degradar el último Admin activo).
- Evitar duplicar lógica UI: reutilizar toggles/JS existentes y añadir solo el “bridge” de persistencia DB.

### Requisitos técnicos (recomendado)

**1) Datos (DB)**
- `users` (campos extra):
  - `department` `VARCHAR(255)` nullable
  - `position` `VARCHAR(255)` nullable
- Preferencias UI por usuario (evitar agregar 10 columnas a `users`):
  - tabla `user_settings` (patrón similar a `settings`)
    - `id`
    - `user_id` (FK a `users`, cascade delete)
    - `key` (string)
    - `value` (json)
    - `updated_by_user_id` nullable (opcional; útil si Admin resetea/edita)
    - timestamps
    - unique(`user_id`, `key`)

**2) Keys de preferencias (estables)**
- `ui.theme` = `light|dark`
- `ui.density` = `normal|compact`
- `ui.sidebar_collapsed` = boolean
- `ui.columns.{tableKey}` = array de strings (hidden column keys)

**3) Fuente de verdad + compatibilidad**
- Fuente de verdad: **DB** cuando exista preferencia (por usuario).
- Fallback: si no hay preferencia en DB → usar `localStorage` (comportamiento actual).
- Al cargar:
  - leer preferencias de DB (server-side) y exponerlas en layout (JSON pequeño)
  - **aplicar antes de Vite** al menos `ui.theme` (y si es posible `ui.density` y `ui.sidebar_collapsed` para evitar “flash”)
  - “hidratar” `localStorage` con el valor final resuelto para mantener compatibles los módulos JS existentes.

**4) Sincronización (JS → servidor)**
- Mantener UX inmediata: el toggle aplica en DOM y `localStorage` como hoy.
- Persistencia por usuario:
  - POST autenticado (con CSRF) para guardar preferencia (whitelist de keys).
  - Debounce de escrituras (especialmente columnas) para evitar spam a DB.
  - Si falla el POST, no bloquear ni mostrar errores ruidosos (fallback localStorage).

**5) Reset Admin**
- Acción admin-only desde `UserForm`:
  - borrar todas las keys `ui.*` de ese usuario (o al menos theme/density/sidebar/columns)
  - registrar `updated_by_user_id` cuando aplique.

### Cumplimiento de arquitectura (guardrails)

- **Livewire-first:** mantener `Administración → Usuarios` como rutas a componentes Livewire (como hoy).  
- **Controllers solo borde:** un endpoint POST mínimo para preferencias UI es aceptable (sin convertirlo en “API pública”).  
- **Autorización server-side obligatoria:**
  - Admin: `users.manage` (ya existe; no relajar)
  - Preferencias del usuario actual: `auth` + `active` (y validar que solo pueda tocar sus propias preferencias)
- **No inventar helpers globales:** si hace falta reutilización, preferir `app/Support/*` (ej. `Support/Settings/UserSettingsStore.php`).  
- **No mezclar dominio:** `Employee.department` ya existe; `User.department` es otro concepto. Mantenerlo explícito en nombres/UI.

### Librerías / Frameworks (no desviarse)

- Laravel **11** (ver `gatic/composer.json`)
- Livewire **3** (toggles + Livewire morph ya existen)
- Bootstrap **5.3.x** (en `gatic/package-lock.json` aparece `node_modules/bootstrap` 5.3.x; mantener compatibilidad con `data-bs-theme`)
- Vite **6** (tooling actual)
- **No agregar paquetes** para esto (no hace falta).

Implementación JS:
- Usar `fetch` con CSRF desde `<meta name="csrf-token" ...>` (ya existe en layout).
- No depender de jQuery.

### Requisitos de estructura (archivos a tocar)

**DB**
- `gatic/database/migrations/*_add_department_and_position_to_users_table.php`
- `gatic/database/migrations/*_create_user_settings_table.php`

**Backend**
- `gatic/app/Models/User.php` (agregar/permitir `department`, `position`; mantener casts existentes)
- `gatic/app/Models/UserSetting.php` (nuevo)
- `gatic/app/Support/Settings/UserSettingsStore.php` (nuevo; patrón similar a `SettingsStore`)
- `gatic/app/Http/Controllers/Me/UpdateUiPreferenceController.php` (nuevo; POST)
- `gatic/app/Http/Controllers/Admin/ResetUserUiPreferencesController.php` (nuevo o acción en Livewire admin)
- `gatic/routes/web.php` (rutas POST para preferencias + reset admin)

**Livewire Admin UI**
- `gatic/app/Livewire/Admin/Users/UserForm.php` (leer/escribir department/position + reset UI prefs)
- `gatic/resources/views/livewire/admin/users/user-form.blade.php` (inputs nuevos + botón reset)
- (Opcional) `gatic/resources/views/livewire/admin/users/users-index.blade.php` (columnas “Departamento”/“Puesto” con Column Manager)

**Layout + JS**
- `gatic/resources/views/layouts/app.blade.php` (exponer prefs del usuario y aplicar bootstrapper temprano)
- `gatic/resources/js/ui/theme-toggle.js` (sincronizar + preferencia DB)
- `gatic/resources/js/ui/density-toggle.js` (sincronizar + preferencia DB)
- `gatic/resources/js/ui/sidebar-toggle.js` (sincronizar + preferencia DB)
- `gatic/resources/js/ui/column-manager.js` (sincronizar columnas por tabla + debounce)

### Requisitos de testing (mínimo)

- **Feature (RBAC):**
  - Admin puede ver/editar campos extra en `UserForm`.
  - Editor/Lector no pueden acceder a `/admin/users*` (403).
- **Feature (preferencias):**
  - Endpoint de preferencias valida whitelist (no aceptar keys arbitrarias).
  - Guardado correcto de `ui.theme|ui.density|ui.sidebar_collapsed`.
  - Guardado correcto de `ui.columns.{tableKey}` con arrays de strings.
  - Reset admin borra preferencias del usuario objetivo.
- **No testear JS visual con PHPUnit**: validar backend + “estado fuente” (DB) y que el layout expone preferencias (si se implementa `window.gaticUserPrefs`).

Patrones existentes:
- `gatic/tests/Feature/Admin/AdminLockoutPreventionTest.php` (guardrails admin)
- `gatic/tests/Feature/LayoutNavigationTest.php` (navegación/layout)
- `gatic/tests/Feature/Admin/SettingsTest.php` (patrón de módulo Admin + tests)

### Inteligencia de story previa (Epic 14.6 → reutilizar)

De `_bmad-output/implementation-artifacts/14-6-configuracion-del-sistema-settings.md` (ya implementada):
- Patrón “store” centralizado (`SettingsStore`) para evitar lógica duplicada y tener defaults claros.
- Guardrail de persistencia: **no guardar overrides cuando el valor es igual al default** (reduce ruido y evita UX confusa).
- `set(null)` tratado como “forget” para volver a defaults.
- Tests feature bien enfocados a RBAC y comportamiento real del AC.

Aplicación directa en 14.7:
- Preferencias UI: solo persistir cuando difieren de default (ej. theme = system/default; density = normal; sidebar = expanded; columns = none hidden).
- Mantener compatibilidad con `localStorage`, pero hacer que DB sea la fuente cuando exista (hidratar localStorage desde DB).
- Debounce escrituras a DB (columnas/toggles) para evitar N updates por sesión.

### Correcciones/learnings de code review (qué evitar)

- Evitar crear “overrides” en DB si el valor final es igual al default (ruido y UX confusa).
- Cachear (o minimizar lecturas) de preferencias cuando se consultan múltiples veces en la misma request/render (evitar N queries).
- Tests deben probar el caso real: “cuando NO hay preferencia en DB” vs “cuando hay override”, no solo el happy path.

### Git intelligence (contexto reciente)

Commits más recientes (títulos):
- `f491cf1` feat(admin): add system settings module with DB-backed configuration
- `49191b5` feat(inventory): add useful life tracking and renewal alerts
- `000432c` feat(inventory): add acquisition cost tracking and inventory value dashboard
- `329707a` feat(assets): add warranty tracking with alerts
- `7f7692a` feat(inventory): add contracts module with asset linking

Relevancia para 14.7:
- `f491cf1` es la mejor referencia para patrón “persistencia key/value + store + tests + navegación Admin”.

### Latest tech info (para no implementar desactualizado) — verificado 2026-02-07

- Laravel:
  - El proyecto está en Laravel **11** (`gatic/composer.json`).
  - Existe Laravel **12** (release 2025-02-24). Evitar upgrades mayores en esta story; limitarse a patch/minor dentro de 11 salvo decisión explícita.
- Livewire:
  - Livewire **3.x** sigue siendo el baseline; validar versión real en `gatic/composer.lock` antes de usar APIs nuevas.
- Bootstrap:
  - Bootstrap **5.3.x** es el baseline efectivo en el repo (lockfile). `data-bs-theme` / color modes son válidos.
- Vite:
  - Vite **6.x** (tooling actual). Evitar cambios de bundling; solo tocar JS necesario.
- PHP:
  - Baseline del repo: PHP **8.2+**; máquina local usa PHP 8.4 para tooling (ver `project-context.md`).

### Project Context Reference (reglas que no se pueden romper)

- `docsBmad/project-context.md` manda sobre todo (bible).
- Identificadores de **código/DB/rutas en inglés**; copy/UI en **español**.
- Livewire 3 como UI principal (rutas → componentes). Controllers solo en bordes (descargas/POST internos puntuales).
- Autorización server-side obligatoria (Gates/Policies). UI no sustituye permisos.
- Roles fijos MVP: `Admin`, `Editor`, `Lector`.
- Sin WebSockets; polling `wire:poll.visible` (no aplica directo aquí, pero no introducir real-time).
- En topbar existe nota de alcance histórico: “Profile link deshabilitado” (no re-habilitar perfil público si no es parte explícita del alcance).

### Anti-disaster UX checklist (para dev-agent)

- No mostrar toasts repetitivos al togglear preferencias (evitar “spam UX”).
- Mantener labels/íconos consistentes con el patrón actual (Bootstrap Icons + tooltips).
- Evitar “flash” de tema: el valor final debe aplicarse antes de cargar Vite (igual que hoy con `data-bs-theme`).
- Si se introduce cualquier query que pueda tardar >3s (ej. lectura pesada de preferencias/columnas), integrar `<x-ui.long-request />` (ver checklist global).

### References

- `_bmad-output/implementation-artifacts/epics.md` → Epic 14 / Story 14.7
- `_bmad-output/implementation-artifacts/architecture.md` → stack/patrones: Livewire-first, Gates, estructura `app/Support/*`
- `_bmad-output/implementation-artifacts/ux.md` → toggles UI, densidad, dark mode, productividad desktop-first
- `docsBmad/project-context.md` + `project-context.md` → reglas no negociables (idioma, stack, RBAC, no WebSockets)
- Código (puntos de extensión):
  - `gatic/resources/views/layouts/partials/topbar.blade.php` (toggles existentes)
  - `gatic/resources/views/layouts/app.blade.php` (bootstrapper theme + meta CSRF)
  - `gatic/resources/js/ui/theme-toggle.js` / `density-toggle.js` / `sidebar-toggle.js` / `column-manager.js`
  - `gatic/app/Livewire/Admin/Users/UserForm.php` + `gatic/resources/views/livewire/admin/users/user-form.blade.php`
  - `gatic/app/Providers/AuthServiceProvider.php` (Gate `users.manage`)
  - `gatic/routes/web.php` (grupo admin `can:users.manage`)

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `C:\Users\carlo\.tools\php84\php.exe artisan test --filter=UserExtraFieldsTest` (pass)
- `C:\Users\carlo\.tools\php84\php.exe artisan test --filter=UserUiPreferencesTest` (fail local: `DB_HOST=mysql` no resuelve; correr tests con Sail/Docker)
- `C:\Users\carlo\.tools\php84\php.exe artisan test --filter="(AdminLockoutPreventionTest|UsersAuthorizationTest|UserExtraFieldsTest|UserUiPreferencesTest)"` (pass)
- `C:\Users\carlo\.tools\php84\php.exe vendor\bin\pint --test` (pass)
- `npm run build` (pass)
- `C:\Users\carlo\.tools\php84\php.exe artisan test` (partial fail por entorno: GD extension ausente en tests de attachments + memory limit en corrida completa)
- `C:\Users\carlo\.tools\php84\php.exe vendor\bin\phpstan analyse --memory-limit=1G` (fail en issues preexistentes fuera de alcance de esta story)

### Completion Notes List

- Se agregaron columnas `department` y `position` en `users` (nullable) con persistencia en `UserForm`.
- Se implementó persistencia UI por usuario en DB con tabla `user_settings` y servicio `UserSettingsStore` (keys whitelisteadas y validación estricta).
- Fix post-code-review: lectura de preferencias ahora respeta JSON casts (evita `pluck()` y asegura que el bootstrap de layout aplique preferencias realmente).
- Fix post-code-review: `user_settings.key` alineado a `max:120`.
- Fix post-code-review: se ignoran exports locales (`plan_*`) vía `.gitignore` para evitar ruido en git status.
- Fix post-code-review: mensajes de validación en español para `department/position` + mejoras a11y (labels/id/name) en formulario de usuario.
- Se agregó endpoint autenticado `POST /me/ui-preferences` para guardar preferencias del usuario actual.
- Se sincronizó `theme-toggle`, `density-toggle`, `sidebar-toggle` y `column-manager` para persistencia silenciosa en background con fallback localStorage.
- Se incorporó bootstrap en layout para hidratar localStorage desde DB y evitar flash de tema.
- Se añadió acción admin-only en `UserForm` para restablecer preferencias UI con confirmación y feedback.
- Se agregaron pruebas feature para AC1/AC2/AC3/AC4.

### File List

- `gatic/database/migrations/2026_02_07_130000_add_department_and_position_to_users_table.php`
- `gatic/database/migrations/2026_02_07_131000_create_user_settings_table.php`
- `gatic/app/Models/User.php`
- `gatic/app/Models/UserSetting.php`
- `gatic/app/Support/Settings/UserSettingsStore.php`
- `gatic/app/Http/Controllers/Me/UpdateUiPreferenceController.php`
- `gatic/routes/web.php`
- `gatic/app/Livewire/Admin/Users/UserForm.php`
- `gatic/resources/views/livewire/admin/users/user-form.blade.php`
- `gatic/resources/views/layouts/app.blade.php`
- `gatic/resources/js/ui/user-ui-preferences.js`
- `gatic/resources/js/ui/theme-toggle.js`
- `gatic/resources/js/ui/density-toggle.js`
- `gatic/resources/js/ui/sidebar-toggle.js`
- `gatic/resources/js/ui/column-manager.js`
- `gatic/tests/Feature/Admin/UserExtraFieldsTest.php`
- `gatic/tests/Feature/Admin/UserUiPreferencesTest.php`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `.gitignore`

## Change Log

- 2026-02-07: Implementadas AC1-AC4 de la story 14.7 (campos extra de usuario, persistencia UI por usuario, reset admin, pruebas feature y validación de build/calidad).
- 2026-02-07: Fix post-code-review: se corrigió bootstrap de preferencias (casts), se alineó `user_settings.key`, y se limpió ruido local de archivos `plan_*`.

## Open Questions (resolver antes de implementar)

1) ¿Las preferencias UI las puede editar el propio usuario (recomendado) y el Admin solo “reset”, o el Admin debe poder forzar valores?  
   - Recomendación MVP: self-service vía toggles existentes + reset admin (AC4).
2) ¿DB debe ser “source of truth” siempre que exista valor, o se debe priorizar localStorage (último cambio local)?  
   - Recomendación: DB manda si hay valor; localStorage solo fallback / cache.
3) ¿Se requiere que preferencias UI viajen a “Guest” (login) o solo autenticado?  
   - Recomendación: solo autenticado; mantener login simple.

## Story Completion Status

- Status: **done**
- Completion note: "AC1-AC4 implementadas. Fix post-code-review aplicado (bootstrap de preferencias/casts, longitud de key, limpieza de ruido local)."
