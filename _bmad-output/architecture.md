---
stepsCompleted: [1, 2, 3, 4, 5, 6, 7, 8]
inputDocuments:
  - _bmad-output/prd.md
  - _bmad-output/project-planning-artifacts/epics.md
  - docsBmad/index.md
  - docsBmad/project-context.md
  - docsBmad/development-flow.md
  - docsBmad/gates-execution.md
  - docsBmad/project-overview.md
  - docsBmad/source-tree-analysis.md
  - project-context.md
  - 03-visual-style-guide.md
hasProjectContext: true
projectContextFiles:
  - docsBmad/project-context.md
  - project-context.md
workflowType: 'architecture'
project_name: 'GATIC'
user_name: 'Carlos'
date: '2025-12-27T14:48:08.8487707-06:00'
lastStep: 8
status: 'complete'
completedAt: '2025-12-27T15:59:21.6164986-06:00'
---

# Architecture Decision Document

_This document builds collaboratively through step-by-step discovery. Sections are appended as we work through each architectural decision together._

## Project Context Analysis

### Requirements Overview

**Functional Requirements (36) – implicaciones arquitectónicas:**
- Acceso y roles (FR1–FR3): autenticación + RBAC server-side (Admin/Editor/Lector), UI como “defensa en profundidad”.
- Catálogos (FR4–FR7): entidades maestras (Categorías/Marcas/Ubicaciones) con integridad referencial + soft-delete.
- Inventario (FR8–FR14): modelo dual (serializado vs cantidad), reglas de unicidad (serial por producto, asset_tag global), vistas de detalle con conteos/estados.
- Empleados (FR15–FR16): directorio RPE como receptor de movimientos (separado de usuarios del sistema).
- Movimientos (FR17–FR22): transiciones de estado y validaciones; kardex para cantidad; nota obligatoria en operaciones.
- Búsqueda/filtros (FR23–FR25): búsqueda unificada por nombre/serial/asset_tag (match exacto → salto a Activo), filtros por catálogos/estado.
- Tareas Pendientes + locks (FR26–FR31): procesamiento por renglón, finalización parcial, exclusividad por lock visible + override Admin.
- Trazabilidad (FR32–FR36): auditoría consultable, notas, adjuntos con permisos, papelera, errores con ID.

**Non-Functional Requirements (10) – drivers de arquitectura:**
- Performance/UX: consultas operativas rápidas; si >3s → loader/skeleton + progreso + cancelar.
- “Near-real-time” sin WebSockets: polling (listas ~15s, métricas ~60s) + heartbeat locks ~10s (solo visible/activo).
- Seguridad: autorización server-side; Lector sin acciones destructivas ni adjuntos (MVP).
- Integridad: operaciones atómicas; evitar estados inconsistentes.
- Operabilidad: error amigable con ID; detalle técnico solo Admin.
- Auditoría “best effort”: si falla, NO bloquea la operación principal (registrar internamente).
- Adjuntos: nombre saneado, almacenar UUID, mostrar nombre original; control estricto de acceso.
- Locks: evitar bloqueos eternos (timeout rolling ~15m, lease TTL ~3m, idle guard ~2m, force-release Admin).

**Scale & Complexity:**
- Primary domain: web app intranet (operación TI) con workflows operativos.
- Complejidad: medium (por concurrencia/locks + integridad de inventario + adjuntos/auditoría).
- Indicadores: sin multi-tenancy; sin integraciones externas explícitas; interacción UX moderada (Livewire/polling); datos moderados (inventario + movimientos + tareas + auditoría).
- Estimated architectural components: ~12 (auth/rbac, catálogos, productos, activos, empleados, movimientos serializados, movimientos cantidad+kardex, búsqueda, tareas pendientes, locks, auditoría/errores, adjuntos+papelera).

### Technical Constraints & Dependencies

- Entorno: intranet/on-prem; paridad local-prod con contenedores.
- UX: sin WebSockets; preferir polling.
- Estilo: `docsBmad/project-context.md` define `03-visual-style-guide.md` como restricción dura; el PRD lo menciona como referencia “desactualizada” (requiere alineación).
- Stack objetivo ya definido: Laravel 11 + PHP 8.2+ + MySQL 8; Blade + Livewire 3 + Bootstrap 5; Auth con Breeze (Blade) adaptado a Bootstrap.
- Operación: queue driver `database`; CI mínimo `pint + phpunit + larastan`; trunk-based; merge solo con CI verde.
- Soft-delete: retención indefinida hasta purga por Admin.

### Cross-Cutting Concerns Identified

- Autorización (RBAC) consistente en UI + servidor.
- Concurrencia/locking (Tareas Pendientes) con visibilidad + expiración + override.
- Integridad transaccional (movimientos/estados/stock) + validaciones.
- Observabilidad/soporte: errores con ID + logs; auditoría best-effort.
- Seguridad de archivos (adjuntos) + control de acceso por rol.
- Rendimiento UX: polling visible + “cancelar” en operaciones lentas.

## Starter Template Evaluation

### Primary Technology Domain

Web application (intranet/on-prem) tipo monolito Laravel (MPA con Blade + Livewire, sin WebSockets).

### Starter Options Considered

1) **Laravel 12 + starter kits nuevos (React/Vue/Livewire)**  
- Pro: “lo más nuevo” del ecosistema.  
- Con: tu baseline y tooling (Bootstrap+Breeze) están definidos para Laravel 11; el paquete `guizoxxv/laravel-breeze-bootstrap` declara soporte para **Laravel 11**.

2) **Laravel 11 (`laravel/laravel`) + Sail + Breeze + Bootstrap vía `guizoxxv/laravel-breeze-bootstrap`** ✅  
- Pro: alinea con `docsBmad/project-context.md` y evita remaquetar Breeze a mano desde Tailwind.  
- Con: añade dependencia third‑party (MIT) para scaffolding Bootstrap, pero enfocada y con uso acotado (solo bootstrap/auth scaffolding).

3) **Starters “todo incluido” (Bootstrap + Livewire + tema/admin template)**  
- Pro: UI lista muy rápido.  
- Con: mete muchas decisiones de UI/estructura que podrían chocar con `03-visual-style-guide.md` y con el enfoque de “mínima complejidad”.

### Selected Starter: Laravel 11 + Sail + Breeze Bootstrap (guizoxxv)

**Rationale for Selection:**
- Respeta el baseline técnico ya definido para GATIC.  
- Acelera alineación con Bootstrap 5 desde Gate 0 (sin Tailwind como dependencia principal de UI).  
- Mantiene el proyecto cerca del “Laravel default”, evitando un template pesado.

**Initialization Command:**

```bash
composer create-project --prefer-dist laravel/laravel gatic "11.*"
cd gatic

# Bootstrap auth scaffolding (Breeze + Bootstrap)
composer require guizoxxv/laravel-breeze-bootstrap --dev
php artisan breeze-bootstrap:install

# (Opcional) entorno docker para dev consistente
php artisan sail:install --with=mysql
```

**Architectural Decisions Provided by Starter:**

**Language & Runtime:**
- PHP ^8.2, Laravel 11.x, Composer.

**Styling Solution:**
- Bootstrap + Sass (scaffolding Bootstrap para auth y base de vistas).

**Build Tooling:**
- Vite / npm scripts (build/dev).

**Testing Framework:**
- PHPUnit por defecto (opcional: Pest está soportado por el instalador con flag).

**Code Organization:**
- Estructura estándar Laravel (`app/`, `routes/`, `resources/`, `tests/`), con rutas `auth.php` y vistas de auth pre-generadas.

**Development Experience:**
- Base lista para workflow con Sail (Docker) y CI (`pint` / `phpunit` / `larastan`) según el bible.

## Core Architectural Decisions

### Data Architecture

- **Database:** MySQL 8.0 (compatibilidad con infraestructura actual / desconocida).
- **Data modeling:** esquema relacional normalizado + Eloquent; reglas de dominio reforzadas con constraints (FK/unique) cuando aplique.
- **Data integrity:** operaciones críticas (movimientos, cambios de estado, procesamiento de tareas) dentro de transacciones.
- **Validation:** validación en app (Form Requests / Livewire rules) + validación en DB (índices únicos, FKs) como red de seguridad.
- **Migrations:** Laravel migrations estándar; soft-delete donde aplique y “papelera” operada por Admin (sin purga automática).
- **Caching (MVP):** sin Redis; optimización por índices/queries primero (caching se evalúa si el polling o dashboard lo exige).

### Authentication & Security

- **User provisioning:** sin registro público; solo **Admin** crea usuarios (cuentas administradas).
- **Authentication (MVP):** sesión web (Breeze) con **usuario/contraseña local**.
- **Authorization:** roles fijos **Admin / Editor / Lector** como `users.role` + **Policies/Gates** (server-side) como patrón estándar.
- **Password recovery / email verification (MVP):** **no**; restablecimiento/alta/baja de cuentas vía Admin.
- **API auth (MVP):** no aplica (sin API pública en MVP).

### API & Communication Patterns

- **API pública (MVP):** no.
- **Comunicación principal:** MPA (Blade) + Livewire (acciones/requests internos).
- **JSON endpoints internos (si aplica):** solo para casos puntuales (ej. autocomplete/búsqueda) y siempre detrás de auth.
- **Errores (estándar):** mensaje amigable + `error_id`; detalle técnico solo Admin; logging completo server-side.
- **Rate limiting:** mantener rate limiting de login (Breeze) y agregar throttles a endpoints de alto uso (búsqueda/polling/heartbeat) para evitar abuso.
- **Integraciones externas (MVP):** ninguna (se evalúan post-MVP).

### Frontend Architecture

- **UI architecture:** MPA server-rendered con Blade + Livewire 3; no SPA.
- **Styling:** Bootstrap 5 alineado a `03-visual-style-guide.md`.
- **Polling estándar (sin WebSockets):**
  - Badges/estados en listas: ~15s con `wire:poll.visible`.
  - Métricas dashboard: ~60s con `wire:poll.visible`.
  - Locks heartbeat: ~10s (solo visible/activo; con idle guard).
- **State management:** estado local por componente Livewire; query params cuando aplique; evitar stores globales en MVP.
- **UX performance:** skeleton loaders + “Cancelar” cuando una búsqueda/consulta >3s + indicador “Actualizado hace Xs”.
- **Accessibility (MVP):** HTML semántico, labels/errores claros y manejo correcto de foco en flujos principales.

### Infrastructure & Deployment

- **Local dev:** Laravel Sail (Docker) + MySQL 8.0.
- **Production (on-prem):** Docker Compose con Nginx + PHP-FPM + MySQL 8.0.
- **Queue/async:** driver `database`; worker `queue:work` supervisado en prod.
- **File storage (adjuntos):** filesystem local en servidor (UUID en disco, nombre original en UI) + backups; sin S3 en MVP.
- **CI:** GitHub Actions con `pint --test`, `phpunit`, `larastan/larastan`.
- **Logging/errores:** logs en disco con correlación por `error_id`; detalle técnico solo Admin.

## Implementation Patterns & Consistency Rules

### Pattern Categories Defined

**Critical Conflict Points Identified:** 8 áreas donde agentes pueden divergir (naming, estructura, formatos, comunicación, procesos).

### Naming Patterns

**Language rule (critical):**
- **Code/DB/routes identifiers:** English.
- **UI copy (labels/titles/messages):** Español.

**Database Naming Conventions (Laravel defaults):**
- Tables: `snake_case` + plural (e.g., `products`, `assets`, `employees`, `pending_tasks`).
- Columns: `snake_case` (e.g., `asset_tag`, `product_id`, `created_at`).
- Foreign keys: `<model>_id` (e.g., `employee_id`).
- Pivot tables: `snake_case` (alphabetical if needed) and only when many-to-many is real.
- Soft delete column: `deleted_at`.

**Routes & route names:**
- URI paths: `kebab-case` segments in English (e.g., `/inventory/products`, `/pending-tasks`).
- Route names: `dot.case` in English by module (e.g., `inventory.products.index`).

**PHP code naming:**
- Classes: `StudlyCase` (e.g., `PendingTask`, `AssetMovementService`).
- Methods/vars: `camelCase`.
- Config/env keys: Laravel defaults.

### Structure Patterns

**UI composition (critical):**
- Pages/screens: **Livewire components** as the primary unit (route → Livewire).
- Controllers: solo para “bordes” (descarga de adjuntos, endpoints JSON puntuales).

**Where logic lives (critical):**
- “Business operations” fuera del componente Livewire en clases dedicadas:
  - `app/Actions/...` para casos de uso (Create/Update/Assign/Loan/Return/AcquireLock, etc.).
- Livewire: orquesta (authorize → validate → call Action → toast/redirect).

**Validation:**
- Livewire: `$this->validate()` (rules en el componente/form object).
- Controllers: `FormRequest` en `app/Http/Requests`.

**Authorization:**
- Siempre server-side: `$this->authorize(...)` (Livewire/Controllers).
- Policies en `app/Policies` (una policy por modelo cuando aplique).
- Roles fijos vía `users.role` (Admin/Editor/Lector).

**Testing:**
- PHPUnit.
- Feature: `tests/Feature/...`
- Unit: `tests/Unit/...`

### Format Patterns

**Internal JSON endpoints (si existen):**
- Preferir respuestas consistentes:
  - OK: `{ "data": ... }`
  - Error inesperado: `{ "message": "...", "error_id": "..." }`

**Dates/times:**
- Persistir timestamps con el estándar Laravel (`created_at/updated_at`) y mostrar en UI con formato consistente.

### Communication Patterns

**Logging (critical):**
- Logs estructurados (message + context array).
- Incluir cuando aplique: `user_id`, `entity_type`, `entity_id`, `error_id`.

**Async / queues:**
- Driver `database`.
- Jobs en `app/Jobs` con nombres explícitos (e.g., `RecordAuditLog`).

### Process Patterns

**Error handling (critical):**
- Errores esperados (validación/dominio): mensaje claro al usuario.
- Errores inesperados: mensaje amigable + `error_id`; detalle técnico solo Admin.

**Loading states (Livewire):**
- Usar `wire:loading` + `wire:target` para spinners por acción.
- Deshabilitar botones durante requests (`wire:loading.attr="disabled"`).

### Enforcement Guidelines

**All AI Agents MUST:**
- Seguir naming Laravel (snake_case DB, StudlyCase clases, camelCase métodos).
- Implementar pantallas como Livewire; controllers solo en bordes.
- Aplicar authorize+validate antes de mutaciones.
- Mantener logging con `error_id` en fallos inesperados.

**Anti-Patterns:**
- Mezclar rutas en español/inglés.
- Duplicar lógica de negocio en múltiples Livewire components.
- Saltarse Policies/Gates “porque la UI lo oculta”.

## Project Structure & Boundaries

### Complete Project Directory Structure

> Nota: este repositorio hoy es “planning + BMAD” (sin código). La app Laravel se crea en Gate 0 en una subcarpeta `gatic/`.

```
Proyecto GATIC/
├─ _bmad/
├─ _bmad-output/
│  ├─ prd.md
│  ├─ architecture.md
│  ├─ project-planning-artifacts/
│  │  └─ epics.md
│  └─ implementation-artifacts/
├─ docsBmad/
├─ .github/
│  └─ agents/
├─ 03-visual-style-guide.md
├─ COMMIT_CONVENTIONS.md
├─ project-context.md
└─ gatic/                           # Laravel 11 app (se genera en Gate 0)
   ├─ artisan
   ├─ composer.json
   ├─ package.json
   ├─ vite.config.js
   ├─ phpunit.xml
   ├─ pint.json
   ├─ phpstan.neon                  # Larastan (larastan/larastan)
   ├─ .env.example
   ├─ docker-compose.yml            # Sail (dev)
   ├─ app/
   │  ├─ Actions/
   │  │  ├─ Catalogs/
   │  │  ├─ Inventory/
   │  │  ├─ Employees/
   │  │  ├─ Movements/
   │  │  ├─ PendingTasks/
   │  │  ├─ Audit/
   │  │  ├─ Attachments/
   │  │  └─ Errors/
   │  ├─ Enums/
   │  ├─ Exceptions/
   │  ├─ Http/
   │  │  ├─ Controllers/            # “bordes” (descargas, JSON internos puntuales)
   │  │  ├─ Middleware/
   │  │  └─ Requests/
   │  ├─ Jobs/
   │  ├─ Livewire/                  # pantallas principales (route → Livewire)
   │  │  ├─ Dashboard/
   │  │  ├─ Catalogs/
   │  │  ├─ Inventory/
   │  │  ├─ Employees/
   │  │  ├─ Movements/
   │  │  ├─ Search/
   │  │  ├─ PendingTasks/
   │  │  └─ Admin/
   │  │     ├─ Users/
   │  │     ├─ Errors/
   │  │     └─ Trash/
   │  ├─ Models/
   │  ├─ Policies/
   │  └─ Support/
   ├─ bootstrap/
   ├─ config/
   │  └─ gatic.php                  # configuración de dominio (timeouts/polling/defaults)
   ├─ database/
   │  ├─ factories/
   │  ├─ migrations/
   │  └─ seeders/
   ├─ public/
   ├─ resources/
   │  ├─ css/
   │  ├─ js/
   │  └─ views/
   │     ├─ layouts/
   │     ├─ components/
   │     └─ livewire/
   ├─ routes/
   │  ├─ web.php
   │  ├─ auth.php
   │  └─ api.php                    # solo “internal” si se necesita JSON
   ├─ storage/
   └─ tests/
      ├─ Feature/
      └─ Unit/
```

### Architectural Boundaries

**API Boundaries:**
- Público: ninguno (MVP).
- Interno: `routes/api.php` solo para autocomplete/búsqueda (si se necesita) y siempre detrás de `auth`.

**Component Boundaries:**
- UI: Livewire por módulo (Inventory, Catalogs, Employees, PendingTasks, Admin).
- Controllers: solo “bordes” (descarga de adjuntos, JSON interno puntual).

**Service/Domain Boundaries:**
- Casos de uso transaccionales en `app/Actions/*` (movimientos, locks, finalizar parcial).
- Estados/reglas en `app/Enums/*` (ej. estado de Activo, estado de renglones).

**Data Boundaries:**
- Modelos Eloquent en `app/Models/*`.
- Constraints DB clave:
  - `assets.asset_tag` único global (cuando exista).
  - `assets` unique `(product_id, serial)`.

### Requirements to Structure Mapping

**Epic Mapping:**
- Epic 1 (Acceso/Usuarios/Roles) → `app/Models/User.php`, `app/Policies/*`, `app/Livewire/Admin/Users/*`, seeders.
- Epic 2 (Catálogos) → `app/Models/{Category,Brand,Location}.php`, `app/Livewire/Catalogs/*`, `app/Actions/Catalogs/*`.
- Epic 3 (Productos/Activos) → `app/Models/{Product,Asset}.php`, `app/Livewire/Inventory/*`, `app/Actions/Inventory/*`.
- Epic 4 (Empleados) → `app/Models/Employee.php`, `app/Livewire/Employees/*`, `app/Actions/Employees/*`.
- Epic 5 (Movimientos) → `app/Models/*Movement*.php`, `app/Livewire/Movements/*`, `app/Actions/Movements/*`.
- Epic 6 (Búsqueda) → `app/Livewire/Search/*` (+ `app/Http/Controllers/*` si JSON interno).
- Epic 7 (Tareas Pendientes + Locks) → `app/Models/PendingTask*.php`, `app/Livewire/PendingTasks/*`, `app/Actions/PendingTasks/*`.
- Epic 8 (Auditoría/Adjuntos/Papelera/Errores) → `app/Models/{AuditLog,Attachment,ErrorReport}.php`, `app/Jobs/*`, `app/Livewire/Admin/*`, controllers de descarga.

**Cross-Cutting Concerns:**
- Error ID + logging: `app/Exceptions/*` + `app/Support/*`.
- Queue database: `app/Jobs/*` + migraciones de `jobs/failed_jobs`.

### Integration Points

**Internal Communication:**
- Livewire → Actions → Models/Eloquent → DB.
- Auditoría best-effort: Action/Service → Job `RecordAuditLog` (queue `database`).

**External Integrations:**
- Ninguna en MVP (on-prem).

**Data Flow (alto nivel):**
- Movimientos/locks siempre en transacción; UI refresca estado por polling visible.

### File Organization Patterns

**Configuration Files:**
- Dominio: `config/gatic.php` para timeouts/polling/defaults (sin “magic numbers” en UI).

**Test Organization:**
- `tests/Feature/<Module>/*` para flujos (locks, movimientos, permisos).
- `tests/Unit/*` para reglas/servicios puros.

**Asset Organization:**
- `resources/*` (Bootstrap/Vite).
- Adjuntos en `storage/app/*` (UUID).

### Development Workflow Integration

- Dev: Sail (`gatic/docker-compose.yml`) + Vite.
- Build: `npm run build` + Composer.
- Deploy: Compose (Nginx+PHP-FPM+MySQL) + worker `queue:work` supervisado.

## Architecture Validation & Completion

### Coherence Validation ✅

- Stack coherente: Laravel 11 + PHP 8.2+ + MySQL 8.0 + Blade/Livewire 3 + Bootstrap 5 (on-prem) sin dependencias externas.
- "Near-real-time" sin WebSockets se resuelve con polling Livewire (`wire:poll.visible`) y timeouts centralizados en `config/gatic.php`.
- Autenticacion/autorizacion alineadas: Breeze (login local) + roles fijos (Admin/Editor/Lector) con Policies/Gates.
- Adjuntos, auditoria y manejo de errores mantienen consistencia (control de acceso server-side + `error_id` en logs/UI).

### Requirements Coverage ✅

La arquitectura cubre los 8 epics de `_bmad-output/project-planning-artifacts/epics.md`:

- Epic 1 (Acceso/Usuarios/Roles): Breeze + Admin gestiona usuarios; Policies/Gates por rol.
- Epic 2 (Catalogos): Eloquent + CRUD Livewire con integridad referencial y soft-delete.
- Epic 3 (Productos/Activos): modelo dual (serializado vs cantidad) + unicidades DB.
- Epic 4 (Empleados): modulo separado de usuarios del sistema.
- Epic 5 (Movimientos): Actions transaccionales + kardex para cantidad + notas obligatorias.
- Epic 6 (Busqueda): busqueda unificada con salto a Activo por match exacto.
- Epic 7 (Tareas Pendientes + Locks): locks visibles + expiracion/heartbeat + override Admin.
- Epic 8 (Auditoria/Adjuntos/Papelera/Errores): auditoria "best effort" en queue `database`, adjuntos locales con ACL, papelera y errores con ID.

### Implementation Readiness ✅

- Decisiones criticas especificadas (versiones, drivers, limites MVP) para evitar ambiguedad en implementacion.
- Estructura propuesta (`app/Actions`, `app/Livewire`, `app/Policies`, `config/gatic.php`) reduce conflictos entre agentes y centraliza reglas.
- CI minimo definido (`pint --test`, `phpunit`, `larastan/larastan`) para consistencia de codigo.

### Gap Analysis (acciones pendientes)

**Critical / bloquearia implementacion si se ignora:**
- Definir campos minimos del lock: `locked_by_user_id`, `locked_at`, `heartbeat_at`, `expires_at`, `resource_type`, `resource_id` + indice unico por recurso.
- Politica exacta de adjuntos en MVP: quien puede ver/descargar segun rol y relacion al registro (y como se audita).
- Persistencia/consulta de `error_id`: tabla `error_reports` (o equivalente) para que Admin consulte detalle tecnico por ID.

**Important / puede resolverse durante implementacion inicial:**
- Reglas de busqueda vs Tareas Pendientes: excluir registros "en procesamiento" o marcarlos claramente para no generar doble atencion.

### Architecture Completeness Checklist

- [x] Contexto y restricciones (on-prem, sin WebSockets) capturadas
- [x] Stack y versiones fijadas (Laravel 11 / PHP 8.2+ / MySQL 8.0)
- [x] Patrones de implementacion (Actions, Livewire, Policies, logging con `error_id`)
- [x] Estructura de proyecto y mapeo a epics
- [x] NFRs clave cubiertos (seguridad, integridad, operabilidad, rendimiento UX)

### Architecture Readiness Assessment

**Overall Status:** READY FOR IMPLEMENTATION  
**Confidence Level:** medium-high (por requerir definicion puntual de locks/adjuntos/error catalog)

### Implementation Handoff

**First Implementation Priority:** ejecutar el starter de Laravel 11 en `gatic/` y aplicar Breeze+Bootstrap, luego seed inicial de usuarios/roles fijos (Admin/Editor/Lector) y scaffolding de Policies.
