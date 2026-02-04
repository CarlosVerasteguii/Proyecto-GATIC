# Story 14.2: Contratos (compra/arrendamiento) + relación con Activos

Status: done

Story Key: `14-2-contratos-compra-arrendamiento-y-relacion-con-activos`  
Epic: `14` (Datos de negocio)  
Tracking: `_bmad-output/implementation-artifacts/sprint-status.yaml`  
Backlog (fuente de verdad): `_bmad-output/implementation-artifacts/epics.md` (Epic 14, Story 14.2)

Fuentes (relevantes):
- `_bmad-output/implementation-artifacts/epics.md` (Epic 14)
- `_bmad-output/implementation-artifacts/architecture.md` (stack/patrones/estructura)
- `_bmad-output/implementation-artifacts/ux.md` (reglas UX desktop-first)
- `_bmad-output/implementation-artifacts/prd.md` (NFRs: errores con `error_id`, no WebSockets)
- `docsBmad/project-context.md` (bible)
- `project-context.md` (reglas críticas)
- `gatic/app/Models/Asset.php` (detalle activo + notas/adjuntos)
- `gatic/app/Models/Supplier.php` + `gatic/app/Livewire/Catalogs/Suppliers/*` (proveedores existentes)
- `gatic/routes/web.php` (patrones de rutas + gates)

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Admin/Editor,  
I want registrar contratos (compra/arrendamiento) y asociarlos a activos,  
so that tenga trazabilidad contractual (y base para garantías extendidas).

## Alcance (MVP)

Incluye:
- CRUD de Contratos: listar/buscar, crear, editar.
- Captura de campos: identificador, proveedor, vigencia (rango de fechas), notas, tipo (compra/arrendamiento).
- Asociación **opcional** Activo → Contrato (un contrato puede asociarse a múltiples activos).
- Mostrar contrato en el detalle del Activo (y acceso directo al detalle del Contrato).

No incluye (fuera de alcance):
- Alertas por vigencia (esto se cubre en garantías/alertas: Story 14.3).
- Costos/valor inventario (Stories 14.4–14.5).
- Settings globales (Story 14.6).
- Timeline unificado (Story 14.8).

## Acceptance Criteria

### AC1 — Crear/Editar contrato (mínimo + validaciones)

**Given** un usuario autorizado (Admin/Editor)  
**When** crea/edita un contrato  
**Then** puede capturar identificador, proveedor, vigencia y notas  
**And** puede definir el tipo de contrato (compra o arrendamiento)  
**And** el sistema valida datos y guarda cambios correctamente.

### AC2 — Asociación contrato ↔ activos (opcional)

**Given** un contrato existente  
**When** el usuario vincula uno o más Activos al contrato  
**Then** la relación se guarda y es reversible (desvincular/cambiar contrato)  
**And** el vínculo no afecta estados operativos del Activo (asignado/prestado/etc.).

### AC3 — Mostrar contrato en detalle del Activo

**Given** un activo con contrato vinculado  
**When** se consulta su detalle  
**Then** se muestra el vínculo al contrato y su vigencia de forma clara  
**And** existe un acceso directo a ver el detalle del contrato.

### AC4 — RBAC (defensa en profundidad)

**Given** un usuario Lector (solo consulta)  
**When** visita un Activo con contrato  
**Then** puede ver el contrato (solo lectura)  
**And** no puede crear/editar contratos ni cambiar asociaciones.

## Definiciones operativas (para evitar ambigüedad)

- **Contrato (Contract)**: entidad que representa un acuerdo de compra o arrendamiento; contiene identificador, proveedor opcional, vigencia (inicio/fin) y notas.
- **Proveedor (Supplier)**: catálogo ya existente; un contrato puede asociarse a 0..1 proveedor.
- **Vigencia**: rango de fechas (`start_date`/`end_date`) mostrado en UI (si falta una fecha, tratar como “Sin fecha”).
- **Relación con Activo**: un activo puede tener **0..1** contrato vigente (MVP); un contrato puede vincularse a múltiples activos.

## Requisitos técnicos (guardrails)

### Datos / DB

- Crear tabla `contracts` con columnas (nombres en inglés):
  - `identifier` (string, requerido; recomendado: único)
  - `type` (string, requerido; valores: `purchase` | `lease`)
  - `supplier_id` (FK nullable → `suppliers.id`; validar `deleted_at` null)
  - `start_date` / `end_date` (date nullable; validar `start_date <= end_date` si ambas)
  - `notes` (text nullable)
  - `timestamps()` (y opcional `softDeletes()` aunque no haya UI de borrado en MVP)
- Relación con activos (MVP): agregar `assets.contract_id` nullable con FK `restrictOnDelete`.
  - Reglas: el sistema permite mover un activo entre contratos (update del FK) o dejarlo sin contrato (null).

### Validación / UX

- Formularios deben validar con mensajes claros en español (copy), pero keys/código en inglés.
- En selects de entidades con soft-delete (Supplier, Asset), excluir `deleted_at` en queries/validaciones.
- Si la búsqueda/listado de activos para vincular puede ser “pesada”, integrar `<x-ui.long-request />` alrededor del área de resultados.

### Seguridad

- Crear/editar/vincular: solo `can:inventory.manage` (Admin/Editor).
- Ver detalle: al menos `can:inventory.view` para ver contrato en detalle de activo.

## Cumplimiento de arquitectura (obligatorio)

- UI por Livewire pages (route → componente) siguiendo el patrón actual de `gatic/routes/web.php`.
- Mantener estructura por módulos:
  - Inventory para pantallas de inventario (Contratos y “mostrar en Activo”).
  - Catalogs para catálogos (Proveedor ya vive ahí; NO duplicar).
- Sin WebSockets: cualquier “freshness”/actualización debe usar patrones existentes (polling solo si aplica; en esta story probablemente no necesario).
- Errores en producción: respetar patrón de `error_id` (mensaje amigable; detalle solo Admin).
- Integridad: cambios de vínculo contrato↔activo deben ser atómicos y con validaciones consistentes (evitar estados inconsistentes).

## Requisitos de librerías/frameworks (no inventar stack)

- Backend: Laravel `laravel/framework` `^11.31` (ver `gatic/composer.json`).
- Livewire: `livewire/livewire` `^3.0` (ver `gatic/composer.json`).
- UI: Bootstrap `^5.2.3` (ver `gatic/package.json`) + Bootstrap Icons.
- DB: MySQL 8; migraciones con FK consistentes (`restrictOnDelete`) como en el proyecto.
- Evitar dependencias nuevas para “CRUD scaffolding” o permisos (no Spatie nuevo en esta story).

## Requisitos de estructura de archivos (dónde tocar código)

### Nuevos (sugeridos)

- `gatic/app/Models/Contract.php`
- `gatic/database/migrations/*_create_contracts_table.php`
- `gatic/database/migrations/*_add_contract_id_to_assets_table.php`
- `gatic/app/Livewire/Inventory/Contracts/ContractsIndex.php`
- `gatic/app/Livewire/Inventory/Contracts/ContractForm.php` (create/edit)
- `gatic/resources/views/livewire/inventory/contracts/contracts-index.blade.php`
- `gatic/resources/views/livewire/inventory/contracts/contract-form.blade.php`
- `gatic/tests/Feature/Inventory/ContractsTest.php`

### Modificar (esperado)

- `gatic/routes/web.php` (rutas nuevas bajo `inventory` + `can:inventory.manage`)
- `gatic/app/Models/Asset.php` (relación `contract()` si se implementa `assets.contract_id`)
- `gatic/app/Livewire/Inventory/Assets/AssetShow.php` + view correspondiente (mostrar contrato en detalle)

### Convenciones

- Rutas en inglés + kebab-case; namespacing `inventory.contracts.*` (ver `project-context.md`).
- Copy/UI en español.

## Requisitos de testing (mínimo para CI verde)

- Agregar `gatic/tests/Feature/Inventory/ContractsTest.php` cubriendo:
  - RBAC: solo `Admin/Editor` (gate `inventory.manage`) puede ver create/edit y persistir cambios.
  - Validación: `identifier` requerido y único; `type` ∈ {purchase, lease}; `supplier_id` solo si existe y `deleted_at` es null.
  - Asociación: al guardar un contrato, se puede vincular/desvincular activos (update `assets.contract_id`).
- Agregar/actualizar tests para regresión soft-delete:
  - Si se listan activos para vincular, verificar que activos soft-deleted no aparezcan.
  - Si se lista “proveedor” en contrato, verificar que proveedores soft-deleted no sean válidos (misma regla que en Products/Suppliers).
- Evitar tests frágiles (sin DDL en runtime, usar factories, `RefreshDatabase`).

## Inteligencia de story previa (14.1) — no repetir errores

Lecciones aplicables desde `14-1-proveedores-catalogo-y-relacion-con-productos`:

- Reusar `Supplier` (catálogo) y su patrón de validación `Rule::exists(...)->whereNull('deleted_at')` (ver `gatic/app/Livewire/Inventory/Products/ProductForm.php`).
- Seguir patrón de CRUD Livewire con RBAC en `mount`/acciones (ver `gatic/app/Livewire/Catalogs/Suppliers/SuppliersIndex.php`).
- FKs consistentes con `restrictOnDelete` en migraciones (ver migraciones de Suppliers/Product).
- Tests: evitar DDL en runtime; cubrir soft-delete y reglas de integridad (ver `gatic/tests/Feature/Catalogs/SuppliersTest.php` y `gatic/tests/Feature/Inventory/ProductsTest.php`).

## Git intelligence (últimos commits relevantes)

- Commit más reciente relacionado al Epic 14: `feat(inventory): add provider detail functionality and tests` (toca Suppliers + Products + story docs).
- Patrones observados:
  - Preferencia por Livewire pages (no controllers para CRUD).
  - Validaciones explícitas y mensajes en español.
  - Tests feature robustos por módulo.

## Información técnica “latest” (para no usar docs equivocadas)

- Laravel 11.x (`gatic/composer.json`) → usar documentación de Laravel 11 para migraciones, validation rules y authorization.
- Livewire 3.x (`gatic/composer.json`) → usar APIs/patrones de Livewire 3 (bindings `wire:model.*`, lifecycle, etc.).
- Bootstrap 5.2.x (`gatic/package.json`) → componentes/clases de Bootstrap 5 (no Tailwind).

## Tasks / Subtasks

1) DB: contratos (AC: 1)
- [x] Migración `contracts`:
  - [x] `identifier` (string, unique recomendado)
  - [x] `type` (string: `purchase|lease`)
  - [x] `supplier_id` nullable + FK `restrictOnDelete`
  - [x] `start_date`, `end_date` (date nullable)
  - [x] `notes` (text nullable)
  - [x] `timestamps()` (+ `softDeletes()` opcional)
- [x] Modelo `Contract`:
  - [x] Constantes/valores permitidos para `type`
  - [x] Casts para fechas
  - [x] Relaciones: `supplier()` y `assets()`

2) DB: vínculo Activo → Contrato (AC: 2, 3)
- [x] Migración agregar `assets.contract_id` nullable + FK `restrictOnDelete`
- [x] Modelo `Asset`: relación `contract()`

3) UI: Contratos (AC: 1, 2)
- [x] Rutas Livewire bajo `inventory`:
  - [x] index (listar/buscar)
  - [x] create/edit (form)
  - [x] show (opcional, pero recomendado para link desde Activo)
- [x] `ContractsIndex`: tabla densa, búsqueda por `identifier` y filtros mínimos (tipo/proveedor).
- [x] `ContractForm`:
  - [x] Validaciones (incluye supplier soft-deleted = inválido)
  - [x] UI para vincular activos (buscar/seleccionar múltiples) y para desvincular
  - [x] Si la búsqueda de activos puede ser lenta: `<x-ui.long-request />`

4) UI: detalle del Activo (AC: 3, 4)
- [x] Mostrar bloque "Contrato" en `InventoryAssetShow` (solo lectura para Lector):
  - [x] Identificador + tipo + proveedor (si existe)
  - [x] Vigencia (inicio/fin) con formato claro
  - [x] Link a detalle del contrato (si existe pantalla)

5) Seguridad y calidad (AC: 4)
- [x] Gate/authorize en componentes (server-side) + rutas protegidas.
- [x] Tests feature (ver sección "Requisitos de testing").

## Dev Notes

### Contexto para dev (lo que evita regresiones)

- Stack y baseline: Laravel 11 + Livewire 3 + Bootstrap 5 (ver `project-context.md`).
- UI principal es Livewire (route → componente). Controllers solo para “bordes” (ej. downloads).
- RBAC server-side obligatorio: usar gates/policies (defensa en profundidad). No confiar en ocultar botones.
- Errores en prod: mensaje amigable + `error_id`; detalle técnico solo Admin (ver `project-context.md` y patrones existentes).
- Soft-delete existe en dominio (Assets, Suppliers, etc.). Cuidado con queries/conteos para no incluir `deleted_at`.
- Rendimiento UX: si una query de lista/búsqueda puede tardar >3s, integrar `<x-ui.long-request />` (ver checklist).

### Decisiones recomendadas (MVP) — para evitar sobre-diseño

- **Relación Activo ↔ Contrato:** usar `assets.contract_id` nullable (0..1 contrato por activo en MVP) y `Contract hasMany Asset`.
  - Racional: cubre AC (“vincular uno o más Activos” a un contrato) sin pivot/“historial” por ahora.
  - Si luego se requiere historial/múltiples contratos por activo (garantías extendidas, renovaciones), migrar a pivot en una story futura.
- **Proveedor en contrato:** `contracts.supplier_id` nullable referenciando `suppliers` (reusar catálogo existente).
- **CRUD:** limitar a listar/crear/editar (sin delete/papelera en esta story) para reducir superficie y evitar expandir Admin Trash.

### Project Structure Notes

- Código/DB/rutas en inglés; copy/mensajes/labels en español (regla no negociable).
- Ubicación sugerida:
  - Model: `gatic/app/Models/Contract.php`
  - Livewire (pantallas): `gatic/app/Livewire/Inventory/Contracts/*`
  - Views: `gatic/resources/views/livewire/inventory/contracts/*`
  - Tests: `gatic/tests/Feature/Inventory/ContractsTest.php` (+ assertions en `AssetsTest.php` si aplica)
- Rutas sugeridas (consistentes con `gatic/routes/web.php`):
  - `inventory/contracts` (index)
  - `inventory/contracts/create` (create)
  - `inventory/contracts/{contract}/edit` (edit)
  - `inventory/contracts/{contract}` (show opcional; al menos link desde Activo)

### References

- Epic + AC fuente: `_bmad-output/implementation-artifacts/epics.md` (Epic 14, Story 14.2)
- Reglas de arquitectura/stack/patrones: `_bmad-output/implementation-artifacts/architecture.md`
- NFRs (errores con `error_id`, no WebSockets): `_bmad-output/implementation-artifacts/prd.md` + `project-context.md`
- Bible: `docsBmad/project-context.md`
- Proveedores existentes: `gatic/app/Models/Supplier.php`, `gatic/app/Livewire/Catalogs/Suppliers/SuppliersIndex.php`
- Patrón validación soft-delete (exists + whereNull): `gatic/app/Livewire/Inventory/Products/ProductForm.php`
- Activos (modelo + detalle): `gatic/app/Models/Asset.php`, `gatic/app/Livewire/Inventory/Assets/AssetShow.php`
- UX long request loader/cancel: `gatic/resources/views/components/ui/long-request.blade.php`
- Learnings Epic 14.1: `_bmad-output/implementation-artifacts/14-1-proveedores-catalogo-y-relacion-con-productos.md`

## Referencia de project context (obligatorio)

- `docsBmad/project-context.md` (bible; si hay conflicto, gana aquí)
- `project-context.md` (reglas críticas “lean”)
- `_bmad-output/implementation-artifacts/architecture.md` (estructura, stack, patrones)
- `_bmad-output/implementation-artifacts/ux.md` (desktop-first, progressive disclosure, performance UX)

## Story completion status

- Status: `done`
- Nota: Code review aplicado (fixes + Pint + PHPStan + tests en Sail/Docker).

## Dev Agent Record

### Agent Model Used

Claude Opus 4.5 (claude-opus-4-5-20251101)

### Debug Log References

N/A

### Implementation Plan

- Seguir patrón existente de Suppliers/Products para CRUD de Contratos
- Usar `assets.contract_id` nullable para relación simple (0..1 contrato por activo)
- Gate `inventory.manage` para create/edit, `inventory.view` para show
- Validación de supplier soft-deleted usando `Rule::exists()->whereNull('deleted_at')`
- Tests comprehensivos cubriendo RBAC, validaciones y asociación de activos

### Completion Notes List

 - Story seleccionada automáticamente desde `_bmad-output/implementation-artifacts/sprint-status.yaml` (primer `backlog`: `14-2-*`).
 - Se reusó contexto y learnings de Epic 14.1 para evitar duplicación y regresiones.
 - Se definió una implementación MVP deliberadamente simple (`assets.contract_id`) para cumplir AC sin sobre-diseño.
 - Validación guardada en `_bmad-output/implementation-artifacts/validation-report-20260203-213626.md`.
  - **2026-02-03**: Implementación completada por Claude Opus 4.5:
   - Creadas migraciones para tabla `contracts` y FK `assets.contract_id`
   - Modelo `Contract` con constantes TYPE_PURCHASE/TYPE_LEASE, casts de fechas, relaciones supplier/assets
   - Modelo `Asset` actualizado con relación `contract()`
   - Componentes Livewire: ContractsIndex (listado con filtros), ContractForm (CRUD + vincular activos), ContractShow (detalle)
   - Vistas Blade siguiendo patrones UX existentes (Bootstrap 5, `<x-ui.long-request />`, etc.)
   - Rutas bajo `inventory/contracts/*` con gates apropiados
   - AssetShow actualizado para mostrar bloque "Contrato" con link al detalle
   - 26 tests feature cubriendo: modelo, RBAC (Admin/Editor/Lector), validaciones, linking/unlinking de activos, soft-delete exclusion
   - Pint y PHPStan pasaron sin errores
 - **2026-02-04**: Code review (GPT-5.2) - fixes aplicados:
   - Pint: orden de imports + limpieza de imports no usados (CI verde).
   - UX/RBAC: breadcrumb “Contratos” no enlaza a una ruta prohibida para Lector.
   - Asociación de activos: confirmación explícita al reasignar un activo desde otro contrato.
   - Navegación: link “Contratos” visible en sidebar para Admin/Editor.
   - Limpieza: eliminado archivo suelto `proveedores_after_delete_attempt.md`.

### File List

 - `gatic/database/migrations/2026_02_03_000000_create_contracts_table.php` (NEW)
 - `gatic/database/migrations/2026_02_03_000001_add_contract_id_to_assets_table.php` (NEW)
 - `gatic/app/Models/Contract.php` (NEW)
 - `gatic/app/Models/Asset.php` (MODIFIED - added contract_id fillable, contract() relation)
 - `gatic/app/Livewire/Inventory/Contracts/ContractsIndex.php` (NEW)
 - `gatic/app/Livewire/Inventory/Contracts/ContractForm.php` (NEW)
 - `gatic/app/Livewire/Inventory/Contracts/ContractShow.php` (NEW)
 - `gatic/resources/views/livewire/inventory/contracts/contracts-index.blade.php` (NEW)
  - `gatic/resources/views/livewire/inventory/contracts/contract-form.blade.php` (NEW)
  - `gatic/resources/views/livewire/inventory/contracts/contract-show.blade.php` (NEW)
  - `gatic/routes/web.php` (MODIFIED - added contracts routes)
  - `gatic/app/Livewire/Inventory/Assets/AssetShow.php` (MODIFIED - load contract relation)
   - `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php` (MODIFIED - added contract card)
   - `gatic/tests/Feature/Inventory/ContractsTest.php` (NEW)
   - `gatic/resources/views/layouts/partials/sidebar-nav.blade.php` (MODIFIED - add contratos nav)
   - `_bmad-output/implementation-artifacts/sprint-status.yaml` (MODIFIED)
   - `_bmad-output/implementation-artifacts/14-2-contratos-compra-arrendamiento-y-relacion-con-activos.md` (NEW)
   - `proveedores_after_delete_attempt.md` (DELETED - stray debug artifact)

## Senior Developer Review (AI)

_Reviewer: Carlos — 2026-02-04_

### Resumen

- Los Acceptance Criteria (AC1–AC4) se ven implementados en el código.
- Los issues MEDIUM detectados en la revisión fueron corregidos.
- Recomendación actual: **Approved**.

### Validación de Acceptance Criteria (evidencia)

- AC1 (crear/editar + validaciones): `gatic/app/Livewire/Inventory/Contracts/ContractForm.php:117` (rules/messages) y `gatic/app/Livewire/Inventory/Contracts/ContractForm.php:274` (save/create/update).
- AC2 (asociación contrato ↔ activos, reversible): `gatic/app/Livewire/Inventory/Contracts/ContractForm.php:176` (search/link/unlink) y `gatic/app/Livewire/Inventory/Contracts/ContractForm.php:274` (persistencia del vínculo).
- AC3 (mostrar contrato en detalle del Activo + acceso directo): `gatic/app/Livewire/Inventory/Assets/AssetShow.php:42` (eager load `contract.supplier`) y `gatic/resources/views/livewire/inventory/assets/asset-show.blade.php:116` (contract card + link a show).
- AC4 (RBAC): rutas `gatic/routes/web.php:89` (show bajo `can:inventory.view`) y `gatic/routes/web.php:113` (index/create/edit bajo `can:inventory.manage`), más `Gate::authorize(...)` en componentes.

### Discrepancias Git vs Story (transparencia)

- Se eliminó el archivo suelto `proveedores_after_delete_attempt.md`.
- Se corrigió la story para marcarse a sí misma como (NEW) y se ajustó el conteo de tests.
- Pint: corregido (verde).

### Hallazgos (corregidos)

#### MEDIUM (corregidos)

1) Breadcrumb en ContractShow apuntaba a una página que el Lector no puede abrir  
   - Fix: breadcrumb “Contratos” ahora solo enlaza al index si el usuario tiene `inventory.manage`.  
   - Evidencia: `gatic/resources/views/livewire/inventory/contracts/contract-show.blade.php:8`.

2) Vincular activos podía reasignar desde otro contrato sin aviso  
   - Fix: se muestra el contrato actual en resultados y se requiere confirmación (doble clic) antes de reasignar.  
   - Evidencia: `gatic/app/Livewire/Inventory/Contracts/ContractForm.php:212`, `gatic/resources/views/livewire/inventory/contracts/contract-form.blade.php:154`, `gatic/tests/Feature/Inventory/ContractsTest.php:427`.

3) Baseline de calidad: Pint no estaba verde  
   - Fix: orden de imports + limpieza de imports no usados; Pint quedó verde.  

4) Archivo suelto no documentado  
   - Fix: eliminado `proveedores_after_delete_attempt.md`.

5) Sidebar no mostraba “Contratos” para Admin/Editor  
   - Fix: agregado item en `layouts.partials.sidebar-nav` bajo `@can('inventory.manage')`.  
   - Evidencia: `gatic/resources/views/layouts/partials/sidebar-nav.blade.php:73`.

#### LOW (corregidos)

1) Claims imprecisos en Completion Notes List  
   - Fix: se corrigieron claims (tests y Pint) y se registró el review como Approved.

### Verificación rápida (evidencia)

- Tests (en Docker Compose / Sail stack): `docker compose -f gatic/compose.yaml exec -T laravel.test php artisan test --filter ContractsTest` (28 passed).
- PHPStan (en Docker Compose / Sail stack): `docker compose -f gatic/compose.yaml exec -T laravel.test vendor/bin/phpstan analyse app/Models/Contract.php app/Models/Asset.php app/Livewire/Inventory/Contracts app/Livewire/Inventory/Assets/AssetShow.php routes/web.php -c phpstan.neon --no-progress` (OK).

## Change Log

- 2026-02-04: Senior Developer Review (AI) - Approved (fixes aplicados: Pint + breadcrumb RBAC + confirmación de reasignación + sidebar Contratos + limpieza de archivo suelto).
