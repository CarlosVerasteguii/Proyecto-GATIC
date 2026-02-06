# Story 14.5: Vida útil y renovación

Status: done

Story Key: `14-5-vida-util-y-renovacion`  
Epic: `14` (Datos de negocio: garantías, costos, proveedores, configuración, timeline, dashboard avanzado)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/epics.md` (Epic 14 / Story 14.5)
- `docsBmad/project-context.md` (bible: stack/UX; semántica de `Retirado`)
- `project-context.md` (reglas lean: idioma, stack, testing)
- `_bmad-output/implementation-artifacts/architecture.md` (patrones y estructura)
- `_bmad-output/implementation-artifacts/ux.md` (UX: loader/cancelar >3s; tablas/reportes)
- `gatic/app/Models/Asset.php` (modelo, casts, SoftDeletes)
- `gatic/app/Models/Category.php` (catálogo; nuevo default de vida útil)
- `gatic/app/Livewire/Inventory/Assets/AssetForm.php` + `gatic/resources/views/livewire/inventory/assets/asset-form.blade.php` (captura/validación de Activo)
- `gatic/app/Livewire/Inventory/Assets/AssetShow.php` + `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php` (detalle de Activo)
- `gatic/app/Livewire/Catalogs/Categories/CategoryForm.php` + `gatic/resources/views/livewire/catalogs/categories/category-form.blade.php` (captura/validación de Categoría)
- `gatic/app/Livewire/Alerts/Warranties/WarrantyAlertsIndex.php` + `gatic/resources/views/livewire/alerts/warranties/warranty-alerts-index.blade.php` (patrón de alertas con ventana)
- `gatic/routes/web.php` (grupo `/alerts`)
- `gatic/config/gatic.php` (config: ventanas, `long_request_threshold_ms`)
- `gatic/tests/Feature/WarrantyAlertsIndexTest.php` (patrón tests: filtros + soft-delete + Retirado)
- `gatic/tests/Feature/Catalogs/CategoriesTest.php` (patrón tests para catálogos)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Admin/Editor,  
I want definir **vida útil** y **fecha estimada de reemplazo** para Activos serializados,  
so that pueda planificar renovaciones y presupuesto con anticipación.

## Acceptance Criteria

### AC1 - Default de vida útil por Categoría (Catálogos)

**Given** un usuario con permiso `catalogs.manage`  
**When** crea o edita una Categoría **serializada**  
**Then** puede capturar un default `default_useful_life_months` (meses, opcional)  
**And** el sistema valida:
- entero `>= 1` y `<= 600` (50 años como máximo razonable)  
- nullable (sin default = “no definido”)

**And** si la Categoría **no** es serializada, el campo no aplica (UI deshabilitada) y se guarda como `NULL`.

### AC2 - Vida útil por Activo (override) + precarga desde Categoría

**Given** un usuario con permiso `inventory.manage`  
**When** crea o edita un Activo serializado  
**Then** puede capturar `useful_life_months` (meses, opcional)  
**And** por defecto la UI precarga el valor desde la Categoría del Producto si existe `default_useful_life_months`  
**And** el sistema valida el mismo rango que en Categoría (`1..600`, nullable).

### AC3 - Cálculo / captura de `expected_replacement_date`

**Given** un Activo con `useful_life_months` definido (directo o por default aplicado)  
**When** el sistema tiene fecha base (MVP: usar `assets.created_at` como “fecha de alta” si no hay otra)  
**Then** calcula `expected_replacement_date = fecha_base + useful_life_months` (meses)  
**And** usa un cálculo consistente (sin overflow de fin de mes).

**And** el usuario puede **capturar manualmente** `expected_replacement_date` para sobreescribir el cálculo (opcional).  
**And** si el usuario limpia `expected_replacement_date`, el sistema vuelve a calcularla (si hay meses).

### AC4 - Visualización en detalle del Activo

**Given** un Activo con vida útil o `expected_replacement_date`  
**When** el usuario consulta el detalle del Activo  
**Then** ve “Vida útil (meses)” y “Fecha estimada de reemplazo”  
**And** si no hay datos, se muestra `—` sin errores  
**And** se muestra un badge:
- **Vencido** si `expected_replacement_date < hoy`
- **Por vencer** si está dentro de la ventana configurada
- **En tiempo** si está fuera de la ventana

### AC5 - Reporte / listado “Activos por renovar” (periodo configurable)

**Given** existen Activos con `expected_replacement_date`  
**When** el usuario entra a `/alerts/renewals`  
**Then** ve un listado paginado de Activos **no retirados** (`status != Retirado`) con:
- Producto, Serial, Asset tag, Ubicación
- `expected_replacement_date` (dd/mm/yyyy)
- días vencidos o días restantes según filtro
- acción “Ver detalle”

**And** el listado soporta 2 modos (query param `type`):
- `overdue`: `expected_replacement_date < hoy`
- `due-soon`: `expected_replacement_date` entre `hoy..(hoy + windowDays)`

**And** `windowDays` se restringe a opciones permitidas en config (similar a préstamos/garantías).

### AC6 - Performance + UX + regresiones

**Given** que el listado puede crecer y la query puede tardar >3s  
**When** se renderiza la vista de alertas  
**Then** integra `<x-ui.long-request />` (loader + Cancelar)  
**And** evita N+1 (usa `with(...)`)  
**And** incluye índice DB en `expected_replacement_date` para filtros por rango.

**And** Activos soft-deleted no aparecen en el reporte ni afectan conteos (regresión cubierta por tests).

## Tasks / Subtasks

1) DB: nuevos campos para vida útil y reemplazo (AC: 1, 2, 3, 5)
- [x] Migration: `categories.default_useful_life_months` (SMALLINT/INT unsigned, nullable)
- [x] Migration: `assets.useful_life_months` (SMALLINT/INT unsigned, nullable)
- [x] Migration: `assets.expected_replacement_date` (DATE, nullable) + índice
- [x] Definir comportamiento de “sin datos”: permitir `NULL` (no forzar captura en MVP)

2) Modelos + casts (AC: 1, 2, 3, 4, 5)
- [x] `gatic/app/Models/Category.php`: agregar campo a `$fillable` + cast `int|null`
- [x] `gatic/app/Models/Asset.php`: agregar campos a `$fillable`
- [x] `gatic/app/Models/Asset.php`: cast `expected_replacement_date` a `immutable_date`

3) UI Catálogos: default por Categoría (AC: 1)
- [x] `gatic/app/Livewire/Catalogs/Categories/CategoryForm.php`: state + rules + persistencia
- [x] `gatic/resources/views/livewire/catalogs/categories/category-form.blade.php`: input “Vida útil default (meses)” + help text
- [x] UX: deshabilitar/limpiar el campo si `is_serialized=false`

4) UI Activos: captura/override + cálculo (AC: 2, 3)
- [x] `gatic/app/Livewire/Inventory/Assets/AssetForm.php`: props + rules + normalización (`'' -> null`)
- [x] UI: precargar meses desde Categoría cuando no hay override
- [x] UI: permitir capturar `expected_replacement_date` manualmente (opcional)
- [x] Cálculo: si no hay fecha manual y hay meses → calcular (fecha base MVP: `created_at`)

5) Detalle del Activo: visualización + estado (AC: 4)
- [x] `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php`: mostrar vida útil + fecha estimada + badge (vencido/por vencer/en tiempo)
- [x] Usar ventana configurable (similar a warranties) para “por vencer”

6) Alertas: “Activos por renovar” (AC: 5, 6)
- [x] `gatic/app/Livewire/Alerts/Renewals/RenewalAlertsIndex.php`: filtros `type` + `windowDays` (query params) + query paginada
- [x] `gatic/resources/views/livewire/alerts/renewals/renewal-alerts-index.blade.php`: tabla + filtros + empty state + `<x-ui.long-request />`
- [x] `gatic/routes/web.php`: agregar `Route::get('/renewals', ...)` bajo `alerts.`

7) Config: ventanas y defaults (AC: 5, 6)
- [x] `gatic/config/gatic.php`: `gatic.alerts.renewals.due_soon_window_days_default` y `..._options`

8) Tests (AC: 1..6)
- [x] `gatic/tests/Feature/RenewalAlertsIndexTest.php`: acceso, filtros, ventana, soft-delete, Retirado
- [x] `gatic/tests/Feature/Inventory/AssetsTest.php`: persistencia y validación de meses/fecha
- [x] `gatic/tests/Feature/Catalogs/CategoriesTest.php`: persistencia/validación de default de vida útil

## Dev Notes

### Guardrails (no negociables)

- Stack existente: Laravel 11 + Livewire 3 + Bootstrap 5; sin WebSockets; usar patrones del repo (no introducir libs nuevas).
- Idioma: identificadores en inglés (`expected_replacement_date`, `useful_life_months`); copy/UI en español.
- Autorización server-side:
  - Categorías: `Gate::authorize('catalogs.manage')`
  - Activos/Alertas: `Gate::authorize('inventory.manage')` (alertas ya están bajo middleware `can:inventory.manage`)
- `Retirado`:
  - No cuenta por defecto en inventario; en alertas de renovación debe excluirse por defecto (como garantías).
- Performance/UX:
  - En listados con queries potencialmente lentas: `<x-ui.long-request />` (ver patrón en alertas existentes).

### Decisiones de diseño (propuestas para MVP)

- Fecha base para cálculo: usar `assets.created_at` como “fecha de alta” (si más adelante se agrega “fecha de compra”, se puede migrar la fórmula).
- Precedencia:
  1) Si el usuario captura `expected_replacement_date` → se respeta (override manual)
  2) Si no hay override y hay meses → se calcula y persiste
  3) Si no hay meses → `expected_replacement_date = NULL`
- Rango de meses: `1..600` para evitar valores absurdos.

### Reuso de patrones existentes

- Alertas con ventana: copiar enfoque de `WarrantyAlertsIndex` (tipo `expired/due-soon`, `windowDays` en query string, opciones desde config).
- Badges “vencido/por vencer”: reutilizar lógica estilo garantía (comparación con `today` + ventana).

### Requisitos técnicos (detallados)

- DB
  - `categories.default_useful_life_months`: `unsignedSmallInteger` nullable.
  - `assets.useful_life_months`: `unsignedSmallInteger` nullable.
  - `assets.expected_replacement_date`: `date` nullable + índice (`index()`).
- Validación (Livewire)
  - Meses: `nullable|integer|min:1|max:600`.
  - Fecha: `nullable|date` (formato UI `Y-m-d`).
  - Normalización: strings vacíos (`''`) a `null` antes de validar/persistir.
- Cálculo de fecha (MVP)
  - Base: `assets.created_at` (solo fecha; ignorar hora).
  - Cálculo meses: usar Carbon/CarbonImmutable con `addMonthsNoOverflow()` para evitar problemas de fin de mes (ej. 31 → 30).
  - Regla: no recalcular si el usuario capturó `expected_replacement_date` manualmente.
- Query de alertas “renewals”
  - Filtro base: `whereNotNull('expected_replacement_date')` + `where('status', '!=', Asset::STATUS_RETIRED)`.
  - `overdue`: `expected_replacement_date < today`.
  - `due-soon`: `expected_replacement_date between [today, today + windowDays]`.
  - `with(['product:id,name', 'location:id,name'])` para evitar N+1.

### Compliance con arquitectura (extracto aplicable)

- UI: route → Livewire (ver `gatic/routes/web.php` y patrón `Alerts/*Index`); evitar controllers salvo “bordes”.
- Configuración: ventanas/días/thresholds en `gatic/config/gatic.php` (no hardcode en componentes).
- Autorización: siempre server-side (`Gate::authorize(...)` en Livewire) + middleware `can:*` en rutas.
- Soft-delete: confiar en scopes Eloquent y agregar tests de regresión cuando haya listados/queries.
- Performance: índices + queries agregadas/filtradas; integrar `<x-ui.long-request />` cuando aplique.

### Librerías / frameworks (versiones del repo)

- Laravel/framework: `v11.47.0` (ver `gatic/composer.lock`)
- Livewire: `v3.7.3` (ver `gatic/composer.lock`)
- Carbon: `3.11.0` (ver `gatic/composer.lock`)
- DB: MySQL 8.0 (baseline del proyecto)
- UI: Bootstrap 5 (baseline del proyecto)

Regla: implementar con APIs de estas versiones (no asumir v10/v12, no “copiar-pegar” snippets incompatibles).

### Archivos y ubicación (estructura)

**DB**
- `gatic/database/migrations/*_add_default_useful_life_months_to_categories_table.php` (nuevo)
- `gatic/database/migrations/*_add_replacement_fields_to_assets_table.php` (nuevo)

**Modelos**
- `gatic/app/Models/Category.php` (modificar)
- `gatic/app/Models/Asset.php` (modificar)

**Livewire + Vistas**
- `gatic/app/Livewire/Catalogs/Categories/CategoryForm.php` (modificar)
- `gatic/resources/views/livewire/catalogs/categories/category-form.blade.php` (modificar)
- `gatic/app/Livewire/Inventory/Assets/AssetForm.php` (modificar)
- `gatic/resources/views/livewire/inventory/assets/asset-form.blade.php` (modificar)
- `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php` (modificar)
- `gatic/app/Livewire/Alerts/Renewals/RenewalAlertsIndex.php` (nuevo)
- `gatic/resources/views/livewire/alerts/renewals/renewal-alerts-index.blade.php` (nuevo)

**Rutas + Config**
- `gatic/routes/web.php` (modificar: `/alerts/renewals`)
- `gatic/config/gatic.php` (modificar: `alerts.renewals.*`)

**Tests**
- `gatic/tests/Feature/RenewalAlertsIndexTest.php` (nuevo)
- `gatic/tests/Feature/Inventory/AssetsTest.php` (modificar)
- `gatic/tests/Feature/Catalogs/CategoriesTest.php` (modificar)

### Testing (mínimo requerido)

- Feature (HTTP)
  - Renovaciones: `/alerts/renewals` requiere auth y `can:inventory.manage`.
  - Filtros:
    - `type=overdue` solo vencidos
    - `type=due-soon&windowDays=N` respeta ventana y opciones permitidas
- Regresión soft-delete (obligatorio)
  - Crear un Asset con `expected_replacement_date`, soft-deletearlo y asegurar que no aparece.
- Regresión `Retirado`
  - Un Asset `status=Retirado` con `expected_replacement_date` no debe aparecer.
- Livewire (forms)
  - Categoría: valida/persiste `default_useful_life_months` y lo limpia si `is_serialized=false`.
  - Activo: valida/persiste `useful_life_months` y `expected_replacement_date` (cálculo vs override).

### Intelligence (historias previas inmediatas)

- Story 14.3 (Garantías): ya existe patrón de alertas por fecha con `type` + `windowDays` y config:
  - Livewire: `gatic/app/Livewire/Alerts/Warranties/WarrantyAlertsIndex.php`
  - UI: `gatic/resources/views/livewire/alerts/warranties/warranty-alerts-index.blade.php` incluye `<x-ui.long-request />`
  - Tests: `gatic/tests/Feature/WarrantyAlertsIndexTest.php` cubre soft-delete + exclusión de `Retirado`
- Story 14.4 (Costos): patrón para extender `Asset` + `AssetForm` + `asset-show` + config + tests en un solo cambio:
  - `gatic/app/Models/Asset.php` (`$fillable` + casts)
  - `gatic/app/Livewire/Inventory/Assets/AssetForm.php` (normalización de inputs + reglas Livewire)
  - `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php` (display con fallback `—`)
  - `gatic/tests/Feature/Inventory/AssetsTest.php` (cubre UI + persistencia)

### Git intelligence (últimos commits)

- `000432c` Costos (14.4): campos nuevos en `assets`, actualización de `AssetForm/asset-show`, config `gatic.php`, tests.
- `329707a` Garantías + alertas (14.3): componente de alertas + ruta `/alerts/warranties` + config + tests.
- `7f7692a` Contratos (14.2): patrón de módulo `Inventory/Contracts` + link desde `asset-show`.
- `c0b3646` Proveedores (14.1): patrón CRUD + tests.
- `a726991` Alertas stock: patrón de listado simple con `WithPagination`.

Regla: para “Renovaciones”, mantener la misma ergonomía (filtros via query params + config + tests + long-request).

### Tech specifics (para evitar bugs típicos)

- Meses y fin de mes: usar `addMonthsNoOverflow()` en Carbon para evitar fechas inválidas (ej. 2026-01-31 + 1 mes).
- Comparaciones en alertas: usar `Carbon::today()` y comparar contra `->toDateString()` como en `WarrantyAlertsIndex`.
- Date inputs (HTML): almacenar/validar como `Y-m-d`; al mostrar en UI usar `d/m/Y` (consistencia con el resto del sistema).

### Project Structure Notes

- Mantener estructura por módulo:
  - Alertas: `app/Livewire/Alerts/Renewals/*` + `resources/views/livewire/alerts/renewals/*`
  - Catálogos: `app/Livewire/Catalogs/Categories/*`
  - Inventario: `app/Livewire/Inventory/Assets/*`
- Evitar “helpers” globales; si la lógica crece, extraer a `app/Actions/*` o `app/Support/*`.
- Mantener config en `config/gatic.php` (ventanas/thresholds), sin magic numbers.

### References

- Requerimiento base: `_bmad-output/implementation-artifacts/epics.md` → Epic 14 / Story 14.5.
- Reglas del dominio (estados, “Retirado”, polling, UX >3s): `docsBmad/project-context.md`.
- Reglas lean (idioma, testing, stack): `project-context.md`.
- Arquitectura (estructura, naming, config centralizada): `_bmad-output/implementation-artifacts/architecture.md`.
- UX (long-request, tablas/listados, feedback): `_bmad-output/implementation-artifacts/ux.md`.
- Patrones existentes de alertas por fecha: `gatic/app/Livewire/Alerts/Warranties/WarrantyAlertsIndex.php` + `gatic/tests/Feature/WarrantyAlertsIndexTest.php`.

## Dev Agent Record

### Agent Model Used

GPT-5.2 (Codex CLI)

### Debug Log References

- `_bmad/core/tasks/workflow.xml`
- `_bmad/bmm/workflows/4-implementation/dev-story/workflow.yaml`
- `_bmad/bmm/workflows/4-implementation/dev-story/instructions.xml`
- `_bmad/bmm/workflows/4-implementation/dev-story/checklist.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`

### Completion Notes List

- ✅ Se implementaron campos de vida útil y reemplazo en DB para categorías y activos.
- ✅ Se actualizó captura en Catálogos/Activos con validaciones `1..600`, normalización de nulos y cálculo por `created_at`.
- ✅ Se agregó visualización en detalle de Activo con badge de estado (`Vencido`, `Por vencer`, `En tiempo`).
- ✅ Se implementó módulo de alertas `/alerts/renewals` con filtros `type` y `windowDays`, exclusión de `Retirado` y soft-delete.
- ✅ Se agregaron pruebas de regresión para categorías, activos y alertas de renovación.
- ✅ Post code-review: evitar backfill implícito de `useful_life_months` (NULL = hereda default; override solo si se toca el campo).
- ✅ Post code-review: normalización consistente de ventana “Por vencer” (detalle vs `/alerts/renewals`) contra opciones permitidas.
- ✅ Post code-review: limpieza de regla de validación (sin `null` en rules) + tests adicionales.
- ✅ Validación ejecutada: `php artisan test` (718 pruebas, 1815 aserciones) en entorno MySQL efímero Docker (Sail/Compose).

### File List

- `_bmad-output/implementation-artifacts/14-5-vida-util-y-renovacion.md`
- `gatic/database/migrations/2026_02_06_000000_add_useful_life_fields_to_categories_and_assets_tables.php`
- `gatic/app/Models/Category.php`
- `gatic/app/Models/Asset.php`
- `gatic/database/factories/CategoryFactory.php`
- `gatic/database/factories/AssetFactory.php`
- `gatic/app/Livewire/Catalogs/Categories/CategoryForm.php`
- `gatic/resources/views/livewire/catalogs/categories/category-form.blade.php`
- `gatic/app/Livewire/Inventory/Assets/AssetForm.php`
- `gatic/resources/views/livewire/inventory/assets/asset-form.blade.php`
- `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php`
- `gatic/app/Livewire/Alerts/Renewals/RenewalAlertsIndex.php`
- `gatic/resources/views/livewire/alerts/renewals/renewal-alerts-index.blade.php`
- `gatic/routes/web.php`
- `gatic/config/gatic.php`
- `gatic/tests/Feature/Catalogs/CategoriesTest.php`
- `gatic/tests/Feature/Inventory/AssetsTest.php`
- `gatic/tests/Feature/RenewalAlertsIndexTest.php`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`

### Change Log

- 2026-02-06: Implementación completa Story 14.5 (vida útil, fecha estimada de reemplazo, alertas de renovación, UI, configuración y pruebas).
- 2026-02-06: Post code-review: corregida persistencia de override vs default, normalización de ventana y cobertura de pruebas.

## Open Questions (guardar para el final antes de implementar)

1) ¿Se requiere capturar una “fecha de compra” real distinta a `created_at`?  
   - Si sí: definir campo (ej. `acquisition_date`) y qué pasa con existentes (backfill).
2) ¿El default por Categoría debe aplicarse retroactivamente a Activos existentes (backfill automático) o solo para nuevos/edición?
   - Decisión: no backfill automático; `assets.useful_life_months = NULL` significa “hereda default”. Se guarda override solo si el usuario modifica el campo.
3) ¿Ventana por defecto para “Por vencer” en renovaciones? Propuesta: 90 días (opciones: 30/60/90/180).
4) ¿Quién debe ver el reporte? Hoy `/alerts/*` está bajo `can:inventory.manage` (Admin/Editor). ¿Lector debe verlo?
5) ¿Se requieren filtros adicionales en el reporte (Categoría/Marca/Ubicación/Proveedor) en MVP o se deja para Story 14.9?

## Story Completion Status

- Status: **done**
- Completion note: "Code review aplicado (2 Medium + 1 Low corregidos) y suite de pruebas completa OK."
