# Fase 30: corte seguro de schema hardening

- Fecha: `2026-04-02T22:36:29-06:00`
- Estado: `active`
- Alcance: verificación focalizada + hardening aditivo de esquema/datos

## Avance

- Se verificó en entorno con DB disponible la suite focalizada:
  - `tests/Unit/Search/SearchInventoryTest.php`
  - `tests/Unit/Assets/AssetStatusTransitionsTest.php`
- Se corrigió un detalle de compatibilidad en `SearchInventory` para el `LIKE ... ESCAPE`, sin tocar contratos funcionales.
- Se agregó la migración `2026_04_02_220000_harden_roles_assets_and_pending_tasks_schema.php` con:
  - `CHECK` MySQL para `users.role` con dominio `Admin|Editor|Lector`
  - `CHECK` MySQL para `assets.status` con dominio alineado a `Asset::STATUSES`
  - índice `pending_tasks_created_at_index`
  - índice `pending_tasks_type_created_at_index`
- Se agregó `tests/Feature/Dev/SchemaHardeningTest.php` para dejar verificables los checks e índices nuevos.

## Evaluación de `pending_task_lines`

- No se aplicó una unicidad física sobre duplicados de negocio (`serial` / `asset_tag` por `pending_task_id`) en este corte.
- Motivo: el contrato actual permite insertar duplicados y marcarlos después para corrección en UI/finalización.
- Evidencia vigente:
  - `tests/Feature/PendingTasks/PendingTaskActionsTest.php`
  - `tests/Feature/PendingTasks/PendingTaskBulkPasteTest.php`
- Forzar una `UNIQUE` hoy rompería ese flujo y metería retrabajo en `PendingTaskShow`, que quedó fuera de este corte.

## Verificación ejecutada

- `docker compose -f compose.yaml up -d`
- `docker compose -f compose.yaml exec -T laravel.test php artisan migrate --force`
- `docker compose -f compose.yaml exec -T laravel.test php artisan test tests/Unit/Search/SearchInventoryTest.php tests/Unit/Assets/AssetStatusTransitionsTest.php`
- `docker compose -f compose.yaml exec -T laravel.test php artisan test tests/Feature/Dev/SchemaHardeningTest.php`
- `docker compose -f compose.yaml exec -T laravel.test php artisan test tests/Feature/PendingTasks/PendingTaskActionsTest.php`
- `docker compose -f compose.yaml exec -T laravel.test php artisan test tests/Feature/PendingTasks/PendingTaskBulkPasteTest.php`
- `php -l` sobre los archivos tocados
- `docker compose -f compose.yaml exec -T laravel.test ./vendor/bin/pint --test ...`
- `docker compose -f compose.yaml exec -T laravel.test ./vendor/bin/phpstan analyse --no-progress app/Actions/Search/SearchInventory.php tests/Feature/Dev/SchemaHardeningTest.php`

## Nota operativa

- Las suites con `RefreshDatabase` sobre MySQL deben correrse en serie en este entorno; al ejecutarlas en paralelo colisionan sobre la misma base `testing`.
