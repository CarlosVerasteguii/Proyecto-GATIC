# Story 14.3: Garantías en activos (fechas + alertas)

Status: done

Story Key: `14-3-garantias-en-activos-fechas-y-alertas`  
Epic: `14` (Datos de negocio)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Backlog (fuente de verdad): `_bmad-output/implementation-artifacts/epics.md` (Epic 14, Story 14.3)

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/epics.md` (Epic 14)
- `_bmad-output/implementation-artifacts/14-2-contratos-compra-arrendamiento-y-relacion-con-activos.md` (patrones de datos/UX/RBAC para “datos de negocio” en Activos)
- `_bmad-output/implementation-artifacts/13-2-alertas-prestamos-vencidos-y-por-vencer.md` (patrón de “alertas”: queries on-demand + ventana configurable + tabla densa + tests)
- `_bmad-output/implementation-artifacts/architecture.md` (stack, estructura, Livewire → Actions → Models/Eloquent)
- `_bmad-output/implementation-artifacts/ux.md` (desktop-first: toolbar + tablas densas + filtros)
- `docsBmad/project-context.md` (bible)
- `project-context.md` (reglas críticas adicionales + tooling local Windows)
- `gatic/app/Models/Asset.php` (casts/date patterns; relación con Contract; soft-delete)
- `gatic/app/Models/Supplier.php` (catálogo proveedor para `warranty_supplier_id`)
- `gatic/config/gatic.php` (patrón: ventanas configurables para alertas)
- `gatic/app/Livewire/Alerts/Loans/LoanAlertsIndex.php` (patrón: filtros `type` + `windowDays` + query eficiente)
- `gatic/routes/web.php` (patrones de rutas y permisos para `/alerts/*` y `/inventory/*`)
- `03-visual-style-guide.md` (branding base)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Admin/Editor,  
I want registrar fechas de garantía por activo,  
so that el sistema muestre garantías vencidas o por vencer.

## Alcance (MVP)

Incluye:
- Captura de garantía en Activos serializados: inicio, fin, proveedor (opcional) y notas (opcional).
- Mostrar garantía en el detalle del Activo.
- Consultas/listado para:
  - **Garantía vencida**: `warranty_end_date < hoy`.
  - **Por vencer**: `hoy <= warranty_end_date <= hoy + ventana_días` (ventana configurable).

No incluye (fuera de alcance):
- Notificaciones (email/SMS/push) o jobs/cron para “generar alertas”.
- Costos/valor/vida útil (Stories 14.4–14.5).
- Settings UI avanzada (Story 14.6) más allá de una configuración mínima en `config/gatic.php`.
- Timeline unificado por entidad (Story 14.8).

## Definiciones operativas (para evitar ambigüedad)

- La garantía vive **en el Activo** (no en el Producto) porque el vencimiento puede variar por unidad.
- Si `warranty_end_date` es `null`, el Activo **no participa** en alertas de garantía.
- “Hoy” = `Carbon::today()` (timezone de la app).
- Default recomendado: excluir `status = Retirado` de alertas (ruido), salvo que el usuario aplique un filtro explícito.

## Acceptance Criteria

### AC1 — Captura y persistencia de garantía (inicio/fin/proveedor/notas)

**Given** un activo serializado  
**When** el usuario captura garantía (inicio/fin/proveedor/notas)  
**Then** los campos se guardan correctamente  
**And** el sistema valida coherencia (`start_date <= end_date` si ambas existen).

### AC2 — Mostrar garantía en detalle de Activo

**Given** un activo con garantía capturada  
**When** el usuario consulta su detalle  
**Then** ve la garantía (fechas, proveedor si aplica, notas) de forma clara  
**And** la UI es consistente con el estilo “cards + dl” ya usado en `asset-show`.

### AC3 — Consultas/listado: “vencida” y “por vencer” con ventana configurable

**Given** existen activos con `warranty_end_date`  
**When** el usuario consulta garantías vencidas o por vencer  
**Then** el sistema lista resultados correctos según definición operativa  
**And** la ventana de “por vencer” es configurable con opciones acotadas (p.ej. 7/14/30).

### AC4 — RBAC (defensa en profundidad)

**Given** un usuario Lector  
**When** visita un Activo con garantía  
**Then** puede ver la garantía (solo lectura)  
**And** no puede editar garantía ni acceder a listados/acciones que requieran `inventory.manage` (según política de rutas de Alerts).

## Tasks / Subtasks

1) Datos/DB + Model (AC: 1, 3)
- [x] Migración: agregar a `assets` (nombres en inglés):
  - [x] `warranty_start_date` (date nullable)
  - [x] `warranty_end_date` (date nullable, index)
  - [x] `warranty_supplier_id` (FK nullable → `suppliers.id`, `restrictOnDelete`)
  - [x] `warranty_notes` (text nullable)
- [x] `gatic/app/Models/Asset.php`: casts `immutable_date` para fechas de garantía y relación `warrantySupplier()`.

2) UI: capturar garantía en Activo (AC: 1)
- [x] Extender `gatic/app/Livewire/Inventory/Assets/AssetForm.php`:
  - [x] Campos públicos `warrantyStartDate`, `warrantyEndDate`, `warrantySupplierId`, `warrantyNotes`.
  - [x] Rules: fechas `Y-m-d`, `end >= start` si ambas, supplier exists y `deleted_at` null.
  - [x] Mensajes/labels en español.
- [x] Extender `gatic/resources/views/livewire/inventory/assets/asset-form.blade.php`:
  - [x] Sección "Garantía" (card/accordion) con 2 date inputs + select proveedor + notas.

3) UI: mostrar garantía en detalle de Activo (AC: 2)
- [x] `gatic/app/Livewire/Inventory/Assets/AssetShow.php`: eager load `warrantySupplier` (y mostrar en vista).
- [x] `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php`: card "Garantía" (N/A si no hay datos).

4) Alertas: consultas "vencida" y "por vencer" (AC: 3)
- [x] Nuevo listado Livewire (patrón de `LoanAlertsIndex`):
  - [x] `gatic/app/Livewire/Alerts/Warranties/WarrantyAlertsIndex.php`
  - [x] `gatic/resources/views/livewire/alerts/warranties/warranty-alerts-index.blade.php`
  - [x] Query eficiente + paginación; filtros por query string:
    - [x] `type=expired|due-soon`
    - [x] `windowDays=7|14|30|60|90` (solo `due-soon`)
  - [x] UX: tabla densa + acciones mínimas (ver detalle de activo). Envuelto con `<x-ui.long-request />`.
- [x] Rutas: agregar `/alerts/warranties` en `gatic/routes/web.php` con middleware/permiso consistente (`can:inventory.manage` como el resto de Alerts).
- [x] Config: agregar `gatic.alerts.warranties.*` en `gatic/config/gatic.php` (default + options) siguiendo el patrón de loans.

5) Tests (AC: 3, 4 + regresiones)
- [x] Feature test `WarrantyAlertsIndexTest` similar a `LoanAlertsIndexTest`:
  - [x] Unauthorized → redirect login.
  - [x] Usuario sin permiso → 403.
  - [x] Filtro expired/due-soon con ventana.
  - [x] **Soft-delete regression**: un Activo soft-deleted no aparece en alertas.
- [x] Test adicional: activos con status `Retirado` excluidos de alertas.
- [x] Test adicional: proveedor de garantía se muestra en listado.

## Dev Notes

### Developer Context (anti-regresiones)

- Stack fijo: Laravel 11 + Livewire 3 + Bootstrap 5 + MySQL 8. No introducir paquetes “alerting/cron” para esto (se calcula on-demand). [Source: `_bmad-output/implementation-artifacts/architecture.md`]
- “Alerts” ya existe como módulo y patrón: `/alerts/loans` + ventanas configurables + filtros por query string. Reusar el enfoque para garantías. [Source: `gatic/app/Livewire/Alerts/Loans/LoanAlertsIndex.php`, `_bmad-output/implementation-artifacts/13-2-alertas-prestamos-vencidos-y-por-vencer.md`]
- UI desktop-first: tablas densas, toolbar, filtros rápidos; evitar pantallas “SPA”. [Source: `_bmad-output/implementation-artifacts/ux.md`]
- RBAC: siempre server-side con Gates; la UI no sustituye permisos. [Source: `docsBmad/project-context.md`, `project-context.md`]

### Requisitos técnicos (guardrails)

**Datos/DB**
- Guardar garantía en `assets` (no en `products`).
- Columnas (inglés) sugeridas:
  - `warranty_start_date` (date nullable)
  - `warranty_end_date` (date nullable, index para queries de alertas)
  - `warranty_supplier_id` (FK nullable → `suppliers.id`, `restrictOnDelete`)
  - `warranty_notes` (text nullable)
- Validación obligatoria: `warranty_start_date <= warranty_end_date` si ambas existen (Form + DB best-effort).

**Queries de alertas**
- Base query: `Asset::query()->whereNotNull('warranty_end_date')`.
- `expired`: `warranty_end_date < today()`.
- `due-soon`: `today() <= warranty_end_date <= today()+windowDays`.
- Default recomendado: excluir `status = Retirado` para reducir ruido (documentar claramente si se aplica).

**UX (long-request)**
- Si el listado de alertas hace joins/filters que podrían tardar >3s en dataset real, envolver el área de resultados con `<x-ui.long-request />` (loader + Cancelar). [Source: `_bmad/bmm/workflows/4-implementation/create-story/checklist.md`]

### Cumplimiento de arquitectura (obligatorio)

- Unit of UI: Livewire (route → componente). Controllers solo “bordes”.
- Estructura por módulo (consistente con loans/stock):
  - `app/Livewire/Alerts/Warranties/*`
  - `resources/views/livewire/alerts/warranties/*`
  - `config/gatic.php` para defaults/opciones de ventana
  - `routes/web.php` en el grupo `/alerts` con middleware/permiso consistente

### Requisitos de librerías/frameworks

- No usar JS datepickers ni librerías externas; inputs `type="date"` ya están en uso (préstamos/contratos).
- Usar `Carbon::today()` y comparaciones por `toDateString()` para consistencia.

### Requisitos de estructura de archivos

- Mantener nombres en inglés para código/rutas/DB; copy en español.
- Vista `asset-show` usa cards con `<dl>`; seguir el mismo patrón para “Garantía”.

### Requisitos de testing

- Feature tests deterministas con `RefreshDatabase` (ver `LoanAlertsIndexTest`).
- Regresión soft-delete: Activos con `deleted_at` no deben aparecer en conteos/listados.
- RBAC: al menos “sin auth” y “sin permiso” para `/alerts/warranties`.

### Inteligencia de story previa (14.2) — reusar, no reinventar

- Ya existe el patrón “dato de negocio” asociado a Activo (Contrato) con:
  - Migraciones + FK restrictivas.
  - UI en `asset-show` con card + `<dl>`.
  - RBAC consistente (`inventory.view` para ver, `inventory.manage` para editar).
  [Source: `_bmad-output/implementation-artifacts/14-2-contratos-compra-arrendamiento-y-relacion-con-activos.md`, `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php`]

### Git intelligence summary (últimos commits relevantes)

- `feat(inventory): add contracts module with asset linking` → patrón de “dato empresarial” en Activo (Contract) que garantías debe imitar.
- `feat(alerts): implement overdue and due-soon loan alerts` → patrón exacto de filtros + ventanas + tests que garantías debe replicar.
[Source: `git log -5`]

### Latest tech information (repositorio)

- Mantener stack/versiones del repo (no “upgrade drift”):
  - `laravel/framework`: `^11.31`
  - `livewire/livewire`: `^3.0`
  - `php`: `^8.2`
  [Source: `gatic/composer.json`]

### Project context reference (reglas no negociables)

- Bible: `docsBmad/project-context.md` (gana sobre todo).
- Reglas críticas adicionales + tooling Windows: `project-context.md`.

### Preguntas abiertas (resolver antes de implementar si aplica)

1) ¿El listado `/alerts/warranties` debe ser visible para `inventory.view` (Lector) o solo `inventory.manage` (como loans/stock)?  
2) ¿Excluir `Retirado` por defecto es correcto para operación, o se necesita incluirlo siempre?  
3) ¿Se requiere capturar también “tipo de garantía” (fabricante/extendida) o MVP = fechas + proveedor + notas?

### Project Structure Notes

- **Módulos existentes a reusar:** Alerts (loans/stock), Inventory/Assets (show/form), Catalogs/Suppliers.
- **Rutas:** seguir el grupo `/alerts` ya existente (`gatic/routes/web.php`) y el naming `alerts.warranties.index`.
- **Conflictos potenciales:** el sidebar actualmente no incluye sección “Alertas”; hoy las alertas se acceden por URL. Si se agrega navegación, mantener consistencia con permisos `inventory.manage`.

### References

- Epic/Story fuente: `_bmad-output/implementation-artifacts/epics.md` (Epic 14, Story 14.3)
- Patrones de “datos de negocio” en Activo: `_bmad-output/implementation-artifacts/14-2-contratos-compra-arrendamiento-y-relacion-con-activos.md`
- Patrones de alertas + ventana configurable + tests: `_bmad-output/implementation-artifacts/13-2-alertas-prestamos-vencidos-y-por-vencer.md`, `gatic/app/Livewire/Alerts/Loans/LoanAlertsIndex.php`, `gatic/tests/Feature/LoanAlertsIndexTest.php`
- Stack/estructura: `_bmad-output/implementation-artifacts/architecture.md`, `docsBmad/project-context.md`, `project-context.md`
- UX: `_bmad-output/implementation-artifacts/ux.md`

## Dev Agent Record

### Agent Model Used

Claude Opus 4.5 (claude-opus-4-5-20251101)

### Debug Log References

- Migración ejecutada exitosamente: `2026_02_03_000002_add_warranty_fields_to_assets_table`
- Tests (previo): 682 passed (1708 assertions)
- Tests (review-fixes, 2026-02-05): `php artisan test --filter=AssetsTest` + `php artisan test --filter=WarrantyAlertsIndexTest` (Sail/Docker)
- Pint (previo): PASS (267 files)
- PHPStan (previo): 3 errores (2 pre-existentes en LoanAlertsIndex, 1 nuevo con mismo patrón)

### Completion Notes List

- Story seleccionada automáticamente desde `sprint-status.yaml`: `14-3-garantias-en-activos-fechas-y-alertas`.
- Implementación completa siguiendo patrones existentes:
  - Migración con FK restrictiva hacia `suppliers.id`
  - Casts `immutable_date` en modelo Asset
  - Relación `warrantySupplier()` para eager loading
  - Formulario con validación `end >= start` si ambas fechas existen
  - Card "Garantía" en detalle de activo con badges de estado (Vencida/Por vencer/Vigente)
  - Listado de alertas con filtros `type` y `windowDays` configurables
  - Excluye automáticamente activos `Retirado` y soft-deleted
- Tests exhaustivos: RBAC, filtros, soft-delete regression, status retirement exclusion
- Resueltas preguntas abiertas:
  1. `/alerts/warranties` usa `inventory.manage` (consistente con loans/stock)
  2. `Retirado` excluido por defecto (implementado)
  3. MVP = fechas + proveedor + notas (sin tipo de garantía)
- Code review fixes (2026-02-05):
  - Se corrigió el formulario de Activo para seleccionar/persistir `current_employee_id` cuando el estado es `Asignado` o `Prestado`.
  - La badge “Por vencer” en el detalle del activo ahora usa el default de `config/gatic.php` (sin hardcode a 30 días).
  - `WarrantyAlertsIndex` autoriza también en `mount()` y se acotaron opciones de ventana a 7/14/30.
  - Ajuste de soporte para tests en Sail: `VIEW_COMPILED_PATH` en `phpunit.xml` para evitar permisos en `/tmp/views`.
  - (Low) Migración sin `after()` para mayor portabilidad y docblock del modelo `Asset` consistente con casts inmutables.

### Change Log

- 2026-02-03: Implementación completa de Story 14.3 (Claude Opus 4.5)
- 2026-02-05: Fixes por code review (GPT-5.2)

### File List

**Implementados:**
- `gatic/database/migrations/2026_02_03_000002_add_warranty_fields_to_assets_table.php` (NEW)
- `gatic/app/Models/Asset.php` (MODIFIED)
- `gatic/app/Livewire/Inventory/Assets/AssetForm.php` (MODIFIED)
- `gatic/resources/views/livewire/inventory/assets/asset-form.blade.php` (MODIFIED)
- `gatic/app/Livewire/Inventory/Assets/AssetShow.php` (MODIFIED)
- `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php` (MODIFIED)
- `gatic/app/Livewire/Alerts/Warranties/WarrantyAlertsIndex.php` (NEW)
- `gatic/resources/views/livewire/alerts/warranties/warranty-alerts-index.blade.php` (NEW)
- `gatic/routes/web.php` (MODIFIED)
- `gatic/config/gatic.php` (MODIFIED)
- `gatic/tests/Feature/WarrantyAlertsIndexTest.php` (NEW)
- `gatic/tests/Feature/Inventory/AssetsTest.php` (MODIFIED)
- `gatic/phpunit.xml` (MODIFIED)
- `_bmad-output/implementation-artifacts/sprint-status.yaml` (MODIFIED)
- `_bmad-output/implementation-artifacts/14-3-garantias-en-activos-fechas-y-alertas.md` (NEW)
