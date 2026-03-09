# Testing Notes

Guia corta para decidir cuanta validacion correr localmente antes de integrar cambios.

## Politica de validacion

- En cambios acotados de UI, Blade o Livewire: correr `pint`, `phpstan`, tests del area tocada y smoke runtime/visual.
- En cambios que toquen componentes compartidos, queries, eager loading, layout global, RBAC o flujos transversales: ampliar la cobertura.
- Antes de integrar a `main` o cerrar un checkpoint importante: correr la suite completa (`php artisan test`) ademas de `pint` y `phpstan`.

## Regla practica

- No usar `php artisan test` completo por default en cada iteracion visual pequena.
- Si el diff esta contenido a un modulo o pantalla, validar primero solo ese modulo.
- Si aparece una falla fuera del area tocada, o si el cambio altera infraestructura comun, subir a validacion completa.

## Comandos base

- Calidad base:
  - `./vendor/bin/pint --test`
  - `./vendor/bin/phpstan analyse --no-progress`
- Tests focalizados:
  - `php artisan test --filter=InventorySearchTest`
  - `php artisan test --filter=LayoutNavigationTest`
- Suite completa:
  - `php artisan test`

## Nota de CI

- Esta politica optimiza el loop local de desarrollo.
- No reemplaza la validacion final: antes de merge/checkpoint, CI y la suite completa deben quedar verdes.
