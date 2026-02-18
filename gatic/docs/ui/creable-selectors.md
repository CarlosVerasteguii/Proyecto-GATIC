# Audit UX/Técnico — Selectores “creables” (Laravel + Livewire)

Fecha: 2026-02-18  
Proyecto: GATIC (`gatic/`)

## Objetivo

Inventariar todos los lugares donde el usuario **selecciona/busca entidades existentes** (select/combobox/autocomplete) y detectar dónde tendría sentido agregar el patrón **“creable”**:

> Si no hay resultados → mostrar CTA para crear → **crear con RBAC + anti-duplicados** → **seleccionar automáticamente** la entidad recién creada.

## Opciones UX para “creable” (A/B/C)

- **(A) CTA inline dentro del dropdown**
  - Ideal para catálogos *name-only* (Ubicaciones, Marcas).
- **(B) CTA que abre modal de creación**
  - Ideal cuando se requieren 2–4 campos mínimos (Empleado: `rpe` + `name`; Proveedor: `name` + opcionales).
- **(C) Link seguro a pantalla de creación + `returnTo`**
  - Ideal cuando la entidad tiene más campos/reglas y conviene un flujo dedicado (Categorías; Productos, según alcance).

---

## Hallazgos clave (técnico/UX)

- `EmployeeCombobox` no tiene CTA “Crear …” y hoy cae en “Sin resultados” (`resources/views/livewire/ui/employee-combobox.blade.php:129`).
- `EmployeeCombobox` tiene issues A11y/multi-instancia:
  - `id="employee-listbox"` es estático (`resources/views/livewire/ui/employee-combobox.blade.php:92`) → colisión si hay múltiples instancias en la misma vista.
  - `id="employee-option-{{ $employee->id }}"` también puede colisionar entre instancias (`resources/views/livewire/ui/employee-combobox.blade.php:134`).
  - El `<input>` no expone `id` para asociarlo a un `<label for="...">` externo (`resources/views/livewire/ui/employee-combobox.blade.php:61`).
- **Sí existe** flujo de alta/edición de empleados (inline en `employees.index`):
  - UI: `resources/views/livewire/employees/employees-index.blade.php:34`
  - Acción: `EmployeesIndex::save()` usa `UpsertEmployee` (`app/Livewire/Employees/EmployeesIndex.php:119`)
  - Implicación: Empleado es **candidato real** a “creable” (probablemente opción **B**).
- Productos se cargan “completos” en `mount()` en 3 lugares (impacta performance y escala):
  - `app/Livewire/PendingTasks/QuickStockIn.php:53` → `loadProducts()` (`app/Livewire/PendingTasks/QuickStockIn.php:57`)
  - `app/Livewire/PendingTasks/QuickRetirement.php:47` → `loadProducts()` (`app/Livewire/PendingTasks/QuickRetirement.php:51`)
  - `app/Livewire/PendingTasks/PendingTaskShow.php:149` → `loadProducts()` (`app/Livewire/PendingTasks/PendingTaskShow.php:216`)
- Catálogos con base anti-duplicados:
  - Normalización en modelos (`Brand::normalizeName()` `app/Models/Brand.php:26`, `Location::normalizeName()` `app/Models/Location.php:26`, `Supplier::normalizeName()` `app/Models/Supplier.php:30`, `Category::normalizeName()` `app/Models/Category.php:41`).
  - Unicidad en DB: `brands.name`, `locations.name`, `suppliers.name`, `categories.name` (migrations: `database/migrations/*create_*_table.php`).
  - **Producto**: `products.name` NO es único, solo indexado (`database/migrations/2026_01_02_000000_create_products_table.php:23`).
- Tests afectados cuando se implemente CTA:
  - `tests/Feature/Employees/EmployeeComboboxTest.php:216` aserta “Sin resultados”.
  - `tests/Feature/Inventory/ProductsTest.php:219` aserta “Sin resultados”.
- RBAC: la mayoría de pantallas usan middleware `can:*` y componentes `Gate::authorize()`, pero `dashboard` no exige `can:inventory.view` (`routes/web.php:54`) y `DashboardMetrics` no autoriza en `mount()` (`app/Livewire/Dashboard/DashboardMetrics.php:152`).

---

## Audit Report

Leyenda: **Rec** = (A) inline dropdown, (B) modal, (C) link + `returnTo`.  
Clasificación en notas: **[Fuerte] [Medio] [No apto]**.

| # | Entidad | Ubicación (ruta/pantalla) | Archivo:línea | Control actual | Fuente de datos | Permiso | ¿Ya existe “crear”? | Rec | Riesgos/Notas |
|---:|---|---|---|---|---|---|---|:--:|---|
| 1 | Empleado | Componente UI reutilizable | `app/Livewire/Ui/EmployeeCombobox.php:14`<br>`resources/views/livewire/ui/employee-combobox.blade.php:61` | Combobox (Alpine + Livewire) | `SearchEmployees::execute()` (`app/Actions/Employees/SearchEmployees.php:13`) | `Gate::authorize('inventory.manage')` (`app/Livewire/Ui/EmployeeCombobox.php:39`) | Sí (form inline en `employees.index`) | B | **[Fuerte]** CTA “Crear empleado” (modal) cuando `showNoResults`. A11y: IDs estáticos (`...:92`, `...:134`) + input sin `id` (`...:61`). |
| 2 | Empleado | Asignar activo (`inventory.products.assets.assign`) | `resources/views/livewire/movements/assets/assign-asset-form.blade.php:69` | `<livewire:ui.employee-combobox />` | Ver #1 | `can:inventory.manage` + `Gate::authorize('inventory.manage')` | Sí (ver #1) | B | **[Fuerte]** Reusa #1. |
| 3 | Empleado | Desasignar activo (`inventory.products.assets.unassign`) | `resources/views/livewire/movements/assets/unassign-asset-form.blade.php:89` | Combobox | Ver #1 | `can:inventory.manage` + `Gate::authorize('inventory.manage')` | Sí | B | **[Fuerte]** Reusa #1. |
| 4 | Empleado | Prestar activo (`inventory.products.assets.loan`) | `resources/views/livewire/movements/assets/loan-asset-form.blade.php:49` | Combobox | Ver #1 | `can:inventory.manage` + `Gate::authorize('inventory.manage')` | Sí | B | **[Fuerte]** Reusa #1. |
| 5 | Empleado | Regresar activo (`inventory.products.assets.return`) | `resources/views/livewire/movements/assets/return-asset-form.blade.php:63` | Combobox | Ver #1 | `can:inventory.manage` + `Gate::authorize('inventory.manage')` | Sí | B | **[Fuerte]** Reusa #1. |
| 6 | Empleado | Movimiento por cantidad (`inventory.products.movements.quantity`) | `resources/views/livewire/movements/products/quantity-movement-form.blade.php:99` | Combobox | Ver #1 | `can:inventory.manage` + `Gate::authorize('inventory.manage')` | Sí | B | **[Fuerte]** Reusa #1. |
| 7 | Empleado | Crear/editar activo (`inventory.products.assets.create/edit`) | `resources/views/livewire/inventory/assets/asset-form.blade.php:136` | Combobox | Ver #1 | `can:inventory.manage` + `Gate::authorize('inventory.manage')` | Sí | B | **[Fuerte]** Reusa #1. |
| 8 | Empleado | Asignar por lote (`inventory.assets.index`) | `resources/views/livewire/inventory/assets/assets-global-index.blade.php:365` | Combobox | Ver #1 | `can:inventory.view` (pantalla) + acción `inventory.manage` (modal) | Sí | B | **[Fuerte]** Multi-instancia probable (modal + otras). Prioritario arreglar IDs. |
| 9 | Empleado | Procesar captura rápida (`pending-tasks.show`) | `resources/views/livewire/pending-tasks/pending-task-show.blade.php:697` | Combobox | Ver #1 | `can:inventory.manage` | Sí | B | **[Fuerte]** Reusa #1. |
| 10 | Empleado | Editar renglón (draft) (`pending-tasks.show`) | `resources/views/livewire/pending-tasks/pending-task-show.blade.php:935` | Combobox | Ver #1 | `can:inventory.manage` | Sí | B | **[Fuerte]** Reusa #1. |
| 11 | Empleado | Editar renglón (process) (`pending-tasks.show`) | `resources/views/livewire/pending-tasks/pending-task-show.blade.php:1048` | Combobox | Ver #1 | `can:inventory.manage` | Sí | B | **[Fuerte]** Reusa #1. |
| 12 | Empleado | Dev smoke test | `resources/views/livewire/dev/livewire-smoke-test.blade.php:91` | Combobox | Ver #1 | `auth` | Sí | B | **[No apto]** Solo dev. |
| 13 | Producto | Carga rápida (modal) (`pending-tasks.index`) | `resources/views/livewire/pending-tasks/quick-stock-in.blade.php:61` | `<select wire:model.live="productId">` | Precarga en `mount()` → `QuickStockIn::loadProducts()` (`app/Livewire/PendingTasks/QuickStockIn.php:57`) | `can:inventory.manage` | Sí: `inventory.products.create` | C | **[Medio]** Perf: precarga aunque el modal esté cerrado. Duplicados: `products.name` no es unique. Además existe modo *placeholder* (no “crear producto real”). |
| 14 | Producto | Retiro rápido (modal) (`pending-tasks.index`) | `resources/views/livewire/pending-tasks/quick-retirement.blade.php:81` | `<select wire:model.live="productId">` | Precarga en `mount()` → `QuickRetirement::loadProducts()` (`app/Livewire/PendingTasks/QuickRetirement.php:51`) | `can:inventory.manage` | Sí: `inventory.products.create` | C | **[Medio]** Perf/escala igual a #13. |
| 15 | Producto | Resolver placeholder (`pending-tasks.show`) | `resources/views/livewire/pending-tasks/pending-task-show.blade.php:646` | `<select wire:model.live="quickProcessProductId">` | `PendingTaskShow::loadProducts()` (`app/Livewire/PendingTasks/PendingTaskShow.php:216`) | `can:inventory.manage` | Sí: `inventory.products.create` | C | **[Medio]** Ya hay hint “crealo y vuelve” (`...pending-task-show.blade.php:665`) pero no rehidrata selección. |
| 16 | Producto | Renglón draft (`pending-tasks.show`) | `resources/views/livewire/pending-tasks/pending-task-show.blade.php:766` | `<select wire:model.live="productId">` | `PendingTaskShow::loadProducts()` (`app/Livewire/PendingTasks/PendingTaskShow.php:216`) | `can:inventory.manage` | Sí: `inventory.products.create` | C | **[Medio]** Lista completa; preferible autocomplete server-side. |
| 17 | Ubicación | Captura rápida: ubicación (`pending-tasks.show`) | `resources/views/livewire/pending-tasks/pending-task-show.blade.php:675` | `<select wire:model.live="quickProcessLocationId">` | `PendingTaskShow::loadLocations()` (`app/Livewire/PendingTasks/PendingTaskShow.php:233`) | `can:inventory.manage` (pantalla)<br>`can:catalogs.manage` (crear) | Sí: `catalogs.locations.index` (inline) | A | **[Fuerte]** Catálogo simple (name unique). Si falta ubicación, alta fricción. CTA inline + seleccionar. |
| 18 | Ubicación | Crear/editar activo (`inventory.products.assets.create/edit`) | `resources/views/livewire/inventory/assets/asset-form.blade.php:52` | `<select wire:model.defer="location_id">` | `AssetForm::mount()` carga `locations` (`app/Livewire/Inventory/Assets/AssetForm.php:108`) | `can:inventory.manage` (pantalla)<br>`can:catalogs.manage` (crear) | Sí: `catalogs.locations.index` (inline) | A | **[Fuerte]** Ideal para CTA inline (catálogo name-only + unique). |
| 19 | Ubicación | Ajuste de activo (`inventory.products.assets.adjust`) | `resources/views/livewire/inventory/adjustments/asset-adjustment-form.blade.php:68` | `<select wire:model="newLocationId">` | `AssetAdjustmentForm::mount()` (`app/Livewire/Inventory/Adjustments/AssetAdjustmentForm.php:80`) | `can:admin-only` (pantalla)<br>`can:catalogs.manage` (crear) | Sí: `catalogs.locations.index` | A | **[Medio]** Solo admin; baja prioridad. |
| 20 | Ubicación | Filtro activos por producto (`inventory.products.assets.index`) | `resources/views/livewire/inventory/assets/assets-index.blade.php:54` | `<select wire:model.live="locationId">` | `AssetsIndex::render()` (`app/Livewire/Inventory/Assets/AssetsIndex.php:103`) | `can:inventory.view` | Sí (catálogo) | — | **[No apto]** Es filtro; no crear desde filtros. |
| 21 | Ubicación | Filtro activos global (`inventory.assets.index`) | `resources/views/livewire/inventory/assets/assets-global-index.blade.php:32` | `<select wire:model.live="locationId">` | `AssetsGlobalIndex::render()` (`app/Livewire/Inventory/Assets/AssetsGlobalIndex.php:308`) | `can:inventory.view` | Sí (catálogo) | — | **[No apto]** Filtro. |
| 22 | Ubicación | Filtro dashboard (`dashboard`) | `resources/views/livewire/dashboard/dashboard-metrics.blade.php:131` | `<select wire:model.live="locationId">` | `DashboardMetrics::loadFilterOptions()` (`app/Livewire/Dashboard/DashboardMetrics.php:1088`) | `auth,active` (sin `can:inventory.view`) | Sí (catálogo) | — | **[No apto]** Filtro + RBAC inconsistente (`routes/web.php:54`). |
| 23 | Categoría | Crear/editar producto (`inventory.products.create/edit`) | `resources/views/livewire/inventory/products/product-form.blade.php:27` | `<select wire:model.live="category_id">` | `ProductForm::mount()` carga `categories` (`app/Livewire/Inventory/Products/ProductForm.php:55`) | `can:inventory.manage` (pantalla)<br>`can:catalogs.manage` (crear) | Sí: `catalogs.categories.create` | C | **[Medio]** Categoría tiene reglas (serializado/asset tag/vida útil). Mejor flujo dedicado + `returnTo`. |
| 24 | Categoría | Filtro productos (`inventory.products.index`) | `resources/views/livewire/inventory/products/products-index.blade.php:68` | `<select wire:model.live="categoryId">` | `ProductsIndex::render()` (`app/Livewire/Inventory/Products/ProductsIndex.php:159`) | `can:inventory.view` | Sí | — | **[No apto]** Filtro. |
| 25 | Categoría | Filtro activos global (`inventory.assets.index`) | `resources/views/livewire/inventory/assets/assets-global-index.blade.php:47` | `<select wire:model.live="categoryId">` | `AssetsGlobalIndex::render()` (`app/Livewire/Inventory/Assets/AssetsGlobalIndex.php:313`) | `can:inventory.view` | Sí | — | **[No apto]** Filtro. |
| 26 | Categoría | Filtro low-stock (`alerts.stock.index`) | `resources/views/livewire/alerts/stock/low-stock-alerts-index.blade.php:25` | `<select wire:model.live="categoryId">` | `LowStockAlertsIndex::render()` (`app/Livewire/Alerts/Stock/LowStockAlertsIndex.php:51`) | `can:inventory.manage` | Sí | — | **[No apto]** Filtro. |
| 27 | Categoría | Filtro dashboard (`dashboard`) | `resources/views/livewire/dashboard/dashboard-metrics.blade.php:146` | `<select wire:model.live="categoryId">` | `DashboardMetrics::loadFilterOptions()` (`app/Livewire/Dashboard/DashboardMetrics.php:1088`) | `auth,active` | Sí | — | **[No apto]** Filtro + RBAC. |
| 28 | Marca | Crear/editar producto (`inventory.products.create/edit`) | `resources/views/livewire/inventory/products/product-form.blade.php:55` | `<select wire:model.defer="brand_id">` | `ProductForm::mount()` carga `brands` (`app/Livewire/Inventory/Products/ProductForm.php:66`) | `can:inventory.manage` (pantalla)<br>`can:catalogs.manage` (crear) | Sí: `catalogs.brands.index` (inline) | A | **[Fuerte]** Name-only + unique. CTA inline ideal. |
| 29 | Marca | Filtro productos (`inventory.products.index`) | `resources/views/livewire/inventory/products/products-index.blade.php:82` | `<select wire:model.live="brandId">` | `ProductsIndex::render()` (`app/Livewire/Inventory/Products/ProductsIndex.php:164`) | `can:inventory.view` | Sí | — | **[No apto]** Filtro. |
| 30 | Marca | Filtro activos global (`inventory.assets.index`) | `resources/views/livewire/inventory/assets/assets-global-index.blade.php:62` | `<select wire:model.live="brandId">` | `AssetsGlobalIndex::render()` (`app/Livewire/Inventory/Assets/AssetsGlobalIndex.php:318`) | `can:inventory.view` | Sí | — | **[No apto]** Filtro. |
| 31 | Marca | Filtro low-stock (`alerts.stock.index`) | `resources/views/livewire/alerts/stock/low-stock-alerts-index.blade.php:39` | `<select wire:model.live="brandId">` | `LowStockAlertsIndex::render()` (`app/Livewire/Alerts/Stock/LowStockAlertsIndex.php:56`) | `can:inventory.manage` | Sí | — | **[No apto]** Filtro. |
| 32 | Marca | Filtro dashboard (`dashboard`) | `resources/views/livewire/dashboard/dashboard-metrics.blade.php:161` | `<select wire:model.live="brandId">` | `DashboardMetrics::loadFilterOptions()` (`app/Livewire/Dashboard/DashboardMetrics.php:1088`) | `auth,active` | Sí | — | **[No apto]** Filtro + RBAC. |
| 33 | Proveedor | Crear/editar producto (`inventory.products.create/edit`) | `resources/views/livewire/inventory/products/product-form.blade.php:72` | `<select wire:model.defer="supplier_id">` | `ProductForm::mount()` carga `suppliers` (`app/Livewire/Inventory/Products/ProductForm.php:76`) | `can:inventory.manage` (pantalla)<br>`can:catalogs.manage` (crear) | Sí: `catalogs.suppliers.index` (inline) | B | **[Fuerte]** `suppliers` tiene `contact/notes` (migración `...create_suppliers_table.php`). Modal reduce fricción sin perder calidad. |
| 34 | Proveedor | Proveedor garantía (activo) (`inventory.products.assets.create/edit`) | `resources/views/livewire/inventory/assets/asset-form.blade.php:227` | `<select wire:model.defer="warrantySupplierId">` | `AssetForm::mount()` carga `suppliers` (`app/Livewire/Inventory/Assets/AssetForm.php:118`) | `can:inventory.manage` (pantalla)<br>`can:catalogs.manage` (crear) | Sí: `catalogs.suppliers.index` | B | **[Fuerte]** Igual que #33. |
| 35 | Proveedor | Crear/editar contrato (`inventory.contracts.create/edit`) | `resources/views/livewire/inventory/contracts/contract-form.blade.php:59` | `<select wire:model="supplier_id">` | `ContractForm::mount()` carga `suppliers` (`app/Livewire/Inventory/Contracts/ContractForm.php:75`) | `can:inventory.manage` (pantalla)<br>`can:catalogs.manage` (crear) | Sí: `catalogs.suppliers.index` | B | **[Fuerte]** Igual que #33. |
| 36 | Proveedor | Filtro contratos (`inventory.contracts.index`) | `resources/views/livewire/inventory/contracts/contracts-index.blade.php:54` | `<select wire:model.live="supplierFilter">` | `ContractsIndex::mount()` precarga `suppliers` (`app/Livewire/Inventory/Contracts/ContractsIndex.php:31`) | `can:inventory.manage` | Sí | — | **[No apto]** Filtro; no crear desde filtros. |
| 37 | Estado/Enum | Estado de activo (`inventory.products.assets.create/edit`) | `resources/views/livewire/inventory/assets/asset-form.blade.php:117` | `<select wire:model.live="status">` | `AssetForm::$statuses` (`app/Livewire/Inventory/Assets/AssetForm.php:83`) | `can:inventory.manage` | N/A | — | **[No apto]** Enum controlado. |
| 38 | Moneda/Enum | Moneda adquisición (`inventory.products.assets.create/edit`) | `resources/views/livewire/inventory/assets/asset-form.blade.php:170` | `<select wire:model.defer="acquisitionCurrency" disabled>` | `AssetForm::$allowedCurrencies` (`app/Livewire/Inventory/Assets/AssetForm.php:68`) | `can:inventory.manage` | N/A | — | **[No apto]** Config/enum. |
| 39 | Estado/Enum | Estado (filtro) activos por producto (`inventory.products.assets.index`) | `resources/views/livewire/inventory/assets/assets-index.blade.php:68` | `<select wire:model.live="status">` | `Asset::STATUSES` (`app/Livewire/Inventory/Assets/AssetsIndex.php:112`) | `can:inventory.view` | N/A | — | **[No apto]** Filtro/enum. |
| 40 | Estado/Enum | Estado (filtro) activos global (`inventory.assets.index`) | `resources/views/livewire/inventory/assets/assets-global-index.blade.php:77` | `<select wire:model.live="status">` | `Asset::STATUSES` (`app/Livewire/Inventory/Assets/AssetsGlobalIndex.php:366`) | `can:inventory.view` | N/A | — | **[No apto]** Filtro/enum. |
| 41 | Disponibilidad/Enum | Disponibilidad (filtro) (`inventory.products.index`) | `resources/views/livewire/inventory/products/products-index.blade.php:96` | `<select wire:model.live="availability">` | Opciones hardcode en Blade | `can:inventory.view` | N/A | — | **[No apto]** Filtro/enum. |
| 42 | Tipo/Enum | Tipo de renglón (draft) (`pending-tasks.show`) | `resources/views/livewire/pending-tasks/pending-task-show.blade.php:789` | `<select wire:model.live="lineType">` | `PendingTaskLineType::cases()` (`app/Livewire/PendingTasks/PendingTaskShow.php:1351`) | `can:inventory.manage` | N/A | — | **[No apto]** Enum. |
| 43 | Estado/Enum | Filtro estado tarea (`pending-tasks.index`) | `resources/views/livewire/pending-tasks/pending-tasks-index.blade.php:27` | `<select wire:model.live="statusFilter">` | `PendingTaskStatus::cases()` (`app/Livewire/PendingTasks/PendingTasksIndex.php`) | `can:inventory.manage` | N/A | — | **[No apto]** Filtro/enum. |
| 44 | Tipo/Enum | Filtro tipo tarea (`pending-tasks.index`) | `resources/views/livewire/pending-tasks/pending-tasks-index.blade.php:41` | `<select wire:model.live="typeFilter">` | `PendingTaskType::cases()` (`app/Livewire/PendingTasks/PendingTasksIndex.php`) | `can:inventory.manage` | N/A | — | **[No apto]** Filtro/enum. |
| 45 | Tipo/Enum | Crear tarea pendiente (`pending-tasks.create`) | `resources/views/livewire/pending-tasks/create-pending-task.blade.php:20` | `<select wire:model="type">` | `PendingTaskType::cases()` (`app/Livewire/PendingTasks/CreatePendingTask.php`) | `can:inventory.manage` | N/A | — | **[No apto]** Enum. |
| 46 | Tipo/Enum | Tipo contrato (`inventory.contracts.create/edit`) | `resources/views/livewire/inventory/contracts/contract-form.blade.php:42` | `<select wire:model="type">` | `types` hardcode (`app/Livewire/Inventory/Contracts/ContractForm.php:349`) | `can:inventory.manage` | N/A | — | **[No apto]** Enum. |
| 47 | Tipo/Enum | Tipo (filtro) contratos (`inventory.contracts.index`) | `resources/views/livewire/inventory/contracts/contracts-index.blade.php:41` | `<select wire:model.live="typeFilter">` | Opciones hardcode en Blade | `can:inventory.manage` | N/A | — | **[No apto]** Filtro/enum. |
| 48 | Usuario | Filtro actor (auditoría) (`admin.audit.index`) | `resources/views/livewire/admin/audit/audit-logs-index.blade.php:38` | `<select wire:model.live="actorId">` | `AuditLogsIndex::render()` carga `actors` (`app/Livewire/Admin/Audit/AuditLogsIndex.php:135`) | `can:admin-only` | Sí: `admin.users.create` | — | **[No apto]** Filtro. |
| 49 | Acción/Enum | Filtro acción (auditoría) | `resources/views/livewire/admin/audit/audit-logs-index.blade.php:47` | `<select wire:model.live="action">` | `AuditLog::ACTIONS` (`app/Livewire/Admin/Audit/AuditLogsIndex.php:161`) | `can:admin-only` | N/A | — | **[No apto]** Enum. |
| 50 | Tipo entidad/Enum | Filtro entidad (auditoría) | `resources/views/livewire/admin/audit/audit-logs-index.blade.php:56` | `<select wire:model.live="subjectType">` | `subjectTypes` (`app/Livewire/Admin/Audit/AuditLogsIndex.php:141`) | `can:admin-only` | N/A | — | **[No apto]** Enum/tipo. |
| 51 | Rol/Enum | Filtro rol usuarios (`admin.users.index`) | `resources/views/livewire/admin/users/users-index.blade.php:51` | `<select wire:model.live="role">` | `UserRole::values()` (`app/Livewire/Admin/Users/UsersIndex.php`) | `can:users.manage` | N/A | — | **[No apto]** Enum. |
| 52 | Estado/Enum | Filtro estado usuarios (`admin.users.index`) | `resources/views/livewire/admin/users/users-index.blade.php:66` | `<select wire:model.live="status">` | Opciones hardcode en Blade | `can:users.manage` | N/A | — | **[No apto]** Enum. |
| 53 | Rol/Enum | Rol usuario (form) (`admin.users.create/edit`) | `resources/views/livewire/admin/users/user-form.blade.php:194` | `<select wire:model.live="role">` | `UserRole::values()` (`app/Livewire/Admin/Users/UserForm.php`) | `can:users.manage` | N/A | — | **[No apto]** Enum. |
| 54 | Config (días) | Settings: loans window | `resources/views/livewire/admin/settings/settings-form.blade.php:91` | `<select wire:model="loansDueSoonDefault">` | `SettingsForm::loadOptions()` (`app/Livewire/Admin/Settings/SettingsForm.php`) | `can:admin-only` | N/A | — | **[No apto]** Config. |
| 55 | Config (días) | Settings: warranties window | `resources/views/livewire/admin/settings/settings-form.blade.php:123` | `<select wire:model="warrantiesDueSoonDefault">` | Idem | `can:admin-only` | N/A | — | **[No apto]** Config. |
| 56 | Config (días) | Settings: renewals window | `resources/views/livewire/admin/settings/settings-form.blade.php:155` | `<select wire:model="renewalsDueSoonDefault">` | Idem | `can:admin-only` | N/A | — | **[No apto]** Config. |
| 57 | Moneda/Enum | Settings: default currency | `resources/views/livewire/admin/settings/settings-form.blade.php:213` | `<select wire:model="defaultCurrency">` | `allowedCurrencies` desde config | `can:admin-only` | N/A | — | **[No apto]** Config. |
| 58 | Ventana (días) | Alertas préstamos (`alerts.loans.index`) | `resources/views/livewire/alerts/loans/loan-alerts-index.blade.php:41` | `<select wire:model.live="windowDays">` | Opciones desde config (`LoanAlertsIndex`) | `can:inventory.manage` | N/A | — | **[No apto]** Config. |
| 59 | Ventana (días) | Alertas renovaciones (`alerts.renewals.index`) | `resources/views/livewire/alerts/renewals/renewal-alerts-index.blade.php:41` | `<select wire:model.live="windowDays">` | Opciones desde config | `can:inventory.manage` | N/A | — | **[No apto]** Config. |
| 60 | Ventana (días) | Alertas garantías (`alerts.warranties.index`) | `resources/views/livewire/alerts/warranties/warranty-alerts-index.blade.php:41` | `<select wire:model.live="windowDays">` | Opciones desde config | `can:inventory.manage` | N/A | — | **[No apto]** Config. |
| 61 | Estado/Enum | Ajuste activo: nuevo estado (`inventory.products.assets.adjust`) | `resources/views/livewire/inventory/adjustments/asset-adjustment-form.blade.php:52` | `<select wire:model="newStatus">` | `Asset::STATUSES` (`app/Livewire/Inventory/Adjustments/AssetAdjustmentForm.php:141`) | `can:admin-only` | N/A | — | **[No apto]** Enum. |

Notas:

- `resources/views/components/ui/toolbar.blade.php:17` y `resources/views/components/ui/toolbar.blade.php:18` son ejemplos en comentario, no UI real.

---

## Arquitectura estándar “creable” (Livewire, sin código)

### Objetivo común

Unificar “buscar → no hay resultados → CTA Crear ‘X’ → crear con RBAC → anti-duplicados → seleccionar entidad recién creada → cerrar dropdown/modal → toast”.

### Contrato base (para combobox creables)

- **Propiedades**
  - `#[Modelable] public ?int $selectedId`
  - `public string $search`
  - `public bool $showDropdown`
  - Parámetros: `minChars`, `maxResults`, `placeholder`, `inputId`, `labelText`
- **Sugerencias**
  - `getSuggestions($normalizedSearch): Collection|array` (Action por entidad; estilo `SearchEmployees`).
- **Create**
  - UI: `Gate::allows($createGate)` solo para mostrar CTA.
  - Server: `Gate::authorize($createGate)` dentro del método de creación.
  - Al crear: normalizar → pre-check exacto → create → seleccionar → toast.

### Opciones UX por entidad (A/B/C)

- **(A) CTA inline** (catálogos name-only: Ubicación/Marca)
  - Mostrar “Crear ‘{search}’” cuando `showNoResults && canCreate && searchNormalized != ''`.
  - Click: `findExact()` por nombre normalizado; si existe → seleccionar; si no → crear; luego seleccionar y cerrar.
- **(B) CTA → modal** (Empleado/Proveedor)
  - Modal con campos mínimos.
  - Al guardar: flujo anti-duplicados + seleccionar + cerrar + toast.
- **(C) Link + `returnTo`** (Categoría / Producto)
  - CTA navega a `route('...create', ['returnTo' => ... , 'prefill' => search])`.
  - Al guardar: redirige a `returnTo` con `created_id=...` (query o flash).
  - El selector, al montar/hidratar, lee `created_id` y setea `selectedId`.

### Anti-duplicados (mínimos)

- Normalizar entrada (usar `Model::normalizeName()`/`normalizeText()`).
- Pre-check exacto antes de crear (`where('name', $normalized)` o `where('rpe', ...)`).
- Race-safe: envolver create en `try/catch` de `QueryException` (MySQL 1062) y si dup → reconsultar y seleccionar.
- SoftDeletes + unique:
  - Si el nombre existe pero está soft-deleted, la unique puede bloquear altas.
  - Definir UX: “restaurar” (link a `catalogs.trash.index`) vs error claro.

### RBAC

- CTA solo si `Gate::allows(...)`.
- Create siempre con `Gate::authorize(...)`.
- No asumir que `inventory.manage` implica `catalogs.manage` (hoy son gates distintos; ver `routes/web.php`).

### A11y / teclado

- IDs únicos por instancia (listbox/opciones): evitar strings estáticas (`employee-listbox`).
- Input con label: aceptar `inputId` para que el padre haga `<label for="...">`.
- CTA “Crear …” navegable con teclado como opción del listbox (Enter/Escape consistentes).
- Multi-instancia en una misma vista: requisito explícito (bulk modal + forms).

### Estados / errores / toasts

- Loading: deshabilitar CTA durante create (y mostrar spinner) con `wire:loading`.
- Toasts: usar `App\Livewire\Concerns\InteractsWithToasts` (patrón ya existente).
- Errores inesperados: `App\Support\Errors\ErrorReporter` + `error_id` (patrón ya existente; ver `docs/ui-patterns.md`).

### Performance

- Evitar precargar listas grandes en `mount()` (productos) cuando el modal esté cerrado.
- Preferir búsqueda server-side (min chars + limit + debounce).
- Evitar arrays gigantes en props públicas Livewire.

---

## Implementation Map (sin código)

### Fase 1 — Fundaciones (componente base + A11y)

- Definir contrato “CreatableCombobox”.
- Corregir multi-instancia en `EmployeeCombobox` (IDs únicos + soporte label).
- Definir estándar de `returnTo` seguro (solo paths internos; no open-redirect).

### Fase 2 — Catálogos simples (A)

- Ubicación: aplicar en `asset-form` (`resources/views/livewire/inventory/assets/asset-form.blade.php:52`) y captura rápida (`...pending-task-show.blade.php:675`).
- Marca: aplicar en `product-form` (`resources/views/livewire/inventory/products/product-form.blade.php:55`).

### Fase 3 — Proveedor (B)

- Integraciones:
  - `resources/views/livewire/inventory/products/product-form.blade.php:72`
  - `resources/views/livewire/inventory/assets/asset-form.blade.php:227`
  - `resources/views/livewire/inventory/contracts/contract-form.blade.php:59`

### Fase 4 — Empleado (B)

- Implementar “crear empleado” desde el combobox (modal) y reusar en todos los puntos #2–#11.

### Fase 5 — Producto (C + búsqueda)

- Reemplazar selects de producto por búsqueda server-side y link creable:
  - `resources/views/livewire/pending-tasks/quick-stock-in.blade.php:61`
  - `resources/views/livewire/pending-tasks/quick-retirement.blade.php:81`
  - `resources/views/livewire/pending-tasks/pending-task-show.blade.php:646` y `:766`
- Criterios: eliminar precarga completa y rehidratar selección post-create.

### Fase 6 — Categoría (C)

- CTA a `catalogs.categories.create` con `returnTo`, por complejidad (serializado/asset tag/vida útil).

---

## Checklist de precauciones (técnico/UX)

- RBAC: CTA con `Gate::allows`, create con `Gate::authorize`.
- Anti-duplicados: normalizar + pre-check + 1062 dup-key → reselect.
- SoftDeletes: definir UX para “existe en papelera”.
- A11y: IDs únicos por instancia; input con label; CTA navegable con teclado; foco/escape consistentes.
- Estados: disable/spinner durante create; errores inline + toast; `error_id` para fallas inesperadas.
- Performance: eliminar precargas masivas en `mount()`; min chars + limit + debounce; no arrays grandes en props públicas.
- Consistencia: un solo contrato de toasts (trait) y de reporting (`ErrorReporter`) para todos los creables.
- Tests (cuando implementen): casos de RBAC (403), duplicate key, selección post-create, multi-instancia (IDs).

---

## Apéndice — comandos de auditoría (reproducibles)

```bash
rg -n '<select|role="combobox"|aria-autocomplete|autocomplete="off"|wire:model' resources/views -S
rg -n '<livewire:.*combobox|EmployeeCombobox|Combobox' resources/views app/Livewire -S
rg -n 'Gate::authorize|@can\\(|can:' app resources/views routes -S
rg -n 'load.*Options|function load|->pluck\\(|->select\\(|orderBy\\(' app/Livewire -S
```
