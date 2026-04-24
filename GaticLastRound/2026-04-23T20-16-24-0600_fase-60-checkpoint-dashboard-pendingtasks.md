# Fase 60 Checkpoint - DashboardMetrics y PendingTaskShow

- Status: active
- Created at: `2026-04-23T20:16:24-06:00`
- Scope: `DashboardMetrics`, dashboard support readers/builders, `PendingTaskShow`, pending task support classes
- Canonical roadmap: `2026-04-02T20-35-16-0600_gatic-30-60-90-roadmap.md`
- Checkpoint type: technical audit, no refactor

## Estado general de Fase 60

Fase 60 esta parcialmente estabilizada y lista para continuar con una condicion: antes de abrir otro frente de refactor, conviene correr las suites focalizadas con Docker/Sail activo para validar los tests que dependen de MySQL.

La auditoria confirma avance real en los dos hotspots principales:

- `PendingTaskShow` ya fue reducido mediante extracciones conservadoras hacia `gatic/app/Support/PendingTasks`.
- `DashboardMetrics` ya delega varios calculos y lecturas hacia `gatic/app/Support/Dashboard`.
- Los tests focalizados existen en `gatic/tests/Unit/Dashboard`, `gatic/tests/Feature/Dashboard`, `gatic/tests/Unit/PendingTasks` y `gatic/tests/Feature/PendingTasks`.
- No se tocaron `UndoMovementByToken`, Blade ni `resources/js/pages/dashboard.js` durante este checkpoint.

El arbol de trabajo ya venia sucio antes del checkpoint. Los cambios existentes se trataron como trabajo previo y no se revirtieron.

## PendingTaskShow estabilizado

Piezas reales confirmadas en `gatic/app/Support/PendingTasks`:

- `PendingTaskDraftLineSaver`
- `PendingTaskDraftNewLineSaver`
- `PendingTaskEditableLineResolver`
- `PendingTaskLineEditorStateFactory`
- `PendingTaskLinePayloadNormalizer`
- `PendingTaskLineValidationErrorMapper`
- `PendingTaskProcessLineSaver`
- `SerializedBulkPasteAnalysis`
- `SerializedBulkPasteAnalyzer`

Responsabilidades ya movidas fuera del componente:

- normalizacion de payload de renglones serializados/cantidad
- estado de editor para modal de borrador y modal de proceso
- resolucion de renglones editables
- guardado de linea nueva y linea existente en borrador
- guardado de datos de procesamiento por renglon
- mapeo de errores de validacion hacia campos Livewire
- analisis de pegado masivo de seriales

Responsabilidades que siguen dentro de `PendingTaskShow` y no conviene tocar todavia sin una verificacion DB completa:

- coordinacion lock/session/process mode
- quick capture y conversion a flujo normal
- finalizacion de tarea y liberacion de lock
- override admin de locks
- dispatch/UI state del componente Livewire

## DashboardMetrics estabilizado

Piezas reales confirmadas en `gatic/app/Support/Dashboard`:

- `PendingTaskCountsReader`
- `AlertWindowConfigResolver`
- `AlertComparativePeriodResolver`
- `ComparativeAlertCountsReader`
- `DeltaBadgeBuilder`
- `AlertsSnapshotBuilder`
- `MovementTrendRowsReader`
- `MovementTrendPayloadBuilder`
- `InventoryValueReader`
- `InventoryValuePayloadBuilder`
- `CriticalQueueAssetRowMapper`
- `CriticalQueuePreviewBuilder`
- `RecentActivityBuilder`

Responsabilidades ya movidas fuera del componente:

- conteos de tareas pendientes
- configuracion de ventana de alertas
- periodo comparativo de alertas
- conteos comparativos
- delta badge
- snapshot de alertas
- filas y payload de tendencia de movimientos
- lectura y payload de valor de inventario
- mapeo de filas de activos en cola critica
- preview ordenado/limitado de cola critica
- actividad reciente centralizada en builder dedicado

Responsabilidades que siguen dentro de `DashboardMetrics` y no conviene tocar todavia sin verificacion DB completa:

- query assembly de conteos de estado de activos
- movimientos del dia
- alertas de prestamos, garantias y renovaciones
- construccion de cola critica antes del preview builder
- normalizacion y aplicacion de filtros
- queries base para movimientos serializados y cantidad

## Tests y verificaciones

### Intento DB-backed `2026-04-23T21:36:15-06:00`

Preflight ejecutado:

```bash
.\bin\gatic-doctor.cmd
.\bin\gatic-status.cmd
```

Resultado:

- `gatic-doctor`: PASS para estructura del repo, source-of-truth, `gatic/`, `compose.yaml`, `artisan` y disponibilidad de `docker compose`.
- `gatic-doctor`: WARN porque Docker engine no estaba reachable.
- `gatic-status`: rama `main`, worktree con cambios tracked/untracked preexistentes.

Comando DB-backed intentado desde `gatic/`:

```bash
docker compose -f compose.yaml up -d
```

Resultado:

- Fallo ambiental antes de levantar servicios.
- Error: `open //./pipe/dockerDesktopLinuxEngine: The system cannot find the file specified`.
- Diagnostico: Docker Desktop / Docker Linux engine no esta levantado o no expone el pipe esperado.
- No se ejecutaron tests DB-backed en `laravel.test`.
- No se aplicaron fixes de codigo.
- Se mantiene pendiente correr la validacion DB-backed focalizada cuando Docker este disponible.

### Reintento DB-backed `2026-04-23T21:56:13-06:00`

Se reintento despues de abrir Docker Desktop.

Comandos de diagnostico ejecutados:

```bash
.\bin\gatic-doctor.cmd
.\bin\gatic-status.cmd
docker version
docker context ls
docker --context default version
docker compose -f compose.yaml up -d
```

Resultado:

- `gatic-doctor`: PASS general, pero siguio reportando Docker engine not reachable.
- `gatic-status`: worktree sucio preexistente.
- `docker context ls`: contexto activo `desktop-linux`.
- `docker version`: fallo con `Access is denied` sobre `//./pipe/dockerDesktopLinuxEngine`.
- `docker --context default version`: fallo con `Access is denied` sobre `//./pipe/docker_engine`.
- `docker compose -f compose.yaml up -d`: fallo con `Access is denied` sobre `//./pipe/dockerDesktopLinuxEngine`.

Verificacion de usuario/grupos:

- Usuario: `LAPTOPVERA\carlo`.
- `whoami /groups` muestra membresia habilitada en `LAPTOPVERA\docker-users`.

Diagnostico:

- Docker Desktop ya expone los pipes, pero el proceso actual no puede abrirlos.
- El bloqueo actual es de permisos/estado de Docker Desktop sobre named pipes, no de codigo de GATIC.
- No se ejecutaron tests DB-backed en `laravel.test`.
- No se aplicaron fixes de codigo.
- Sigue pendiente correr la validacion DB-backed focalizada cuando el acceso a Docker quede operativo.

### Diagnostico adicional Docker `2026-04-23T22:05:02-06:00`

Se reintento desde una nueva sesion de WezTerm/Codex con Docker Desktop abierto.

Comandos ejecutados:

```bash
.\bin\gatic-doctor.cmd
whoami /groups
docker version
docker context ls
Get-Service *docker*
Get-Process *Docker*
Start-Service com.docker.service
net session
docker compose -f compose.yaml up -d
```

Resultado:

- `gatic-doctor`: PASS general, pero siguio indicando Docker engine not reachable.
- `whoami /groups`: `LAPTOPVERA\carlo` sigue con membresia habilitada en `LAPTOPVERA\docker-users`.
- `docker context ls`: contexto activo `desktop-linux`.
- `docker version`: `Access is denied` sobre `//./pipe/dockerDesktopLinuxEngine`.
- `Get-Service *docker*`: `com.docker.service` aparece `Stopped`.
- Procesos de Docker Desktop/backend estan corriendo.
- `Start-Service com.docker.service`: no pudo abrir el servicio.
- `net session`: `Error de sistema 5. Acceso denegado.`
- Check PowerShell de admin: `IsInRole(Administrator) = False`.
- `docker compose -f compose.yaml up -d`: `Access is denied` sobre `//./pipe/dockerDesktopLinuxEngine`.

Diagnostico:

- La sesion actual no tiene privilegio Administrador efectivo aunque tenga etiqueta de integridad alta.
- Docker Desktop esta abierto, pero el pipe del engine Linux sigue negando acceso al proceso actual.
- El servicio `com.docker.service` esta detenido y esta sesion no puede iniciarlo.
- El bloqueo sigue siendo ambiental/permisos Docker, no una regresion del codigo.
- No se ejecutaron tests DB-backed.
- No se aplicaron fixes de codigo.

### Verificacion DB-backed completada `2026-04-23T22:40:08-06:00`

Docker quedo operativo y se ejecuto la validacion focalizada dentro de `laravel.test`, en serie.

Servicios levantados desde `gatic/`:

```bash
docker compose -f compose.yaml up -d
```

Resultado:

- `gatic-mysql-1`: started
- `gatic-laravel.test-1`: started

Dashboard DB-backed:

```bash
docker compose -f compose.yaml exec -T laravel.test php artisan test tests/Feature/DashboardMetricsTest.php --stop-on-failure
docker compose -f compose.yaml exec -T laravel.test php artisan test tests/Feature/Dashboard/DashboardMetricsCriticalQueueSmokeTest.php --stop-on-failure
docker compose -f compose.yaml exec -T laravel.test php artisan test tests/Feature/Dashboard/DashboardMetricsMovementTrendSmokeTest.php --stop-on-failure
```

Resultado:

- `DashboardMetricsTest`: 30 tests passed, 91 assertions.
- `DashboardMetricsCriticalQueueSmokeTest`: 1 test passed, 1 assertion.
- `DashboardMetricsMovementTrendSmokeTest`: 1 test passed, 2 assertions.

PendingTasks DB-backed:

```bash
docker compose -f compose.yaml exec -T laravel.test php artisan test tests/Feature/PendingTasks/PendingTaskDraftLineSaveLivewireTest.php --stop-on-failure
docker compose -f compose.yaml exec -T laravel.test php artisan test tests/Feature/PendingTasks/PendingTaskProcessLineSaveLivewireTest.php --stop-on-failure
docker compose -f compose.yaml exec -T laravel.test php artisan test tests/Feature/PendingTasks/PendingTaskLockLivewireTest.php --stop-on-failure
docker compose -f compose.yaml exec -T laravel.test php artisan test tests/Feature/PendingTasks/PendingTaskLineEditorStateLivewireTest.php --stop-on-failure
docker compose -f compose.yaml exec -T laravel.test php artisan test tests/Feature/PendingTasks/PendingTaskBulkPasteTest.php --stop-on-failure
```

Resultado:

- `PendingTaskDraftLineSaveLivewireTest`: 2 tests passed, 14 assertions.
- `PendingTaskProcessLineSaveLivewireTest`: 1 test passed, 10 assertions.
- `PendingTaskLockLivewireTest`: 12 tests passed, 34 assertions.
- `PendingTaskLineEditorStateLivewireTest`: 3 tests passed, 57 assertions.
- `PendingTaskBulkPasteTest`: 5 tests passed, 20 assertions.

Query-adjacent:

```bash
docker compose -f compose.yaml exec -T laravel.test php artisan test tests/Unit/Dashboard/CriticalQueueAssetRowMapperTest.php --stop-on-failure
```

Resultado:

- `CriticalQueueAssetRowMapperTest`: 2 tests passed, 2 assertions.

Dictamen:

- No hubo fallas.
- No se aplicaron fixes de codigo.
- La verificacion DB-backed focalizada de Fase 60 queda cerrada para Dashboard y PendingTasks.
- Fase 60 puede continuar, pero el siguiente trabajo debe elegirse como un hotspot puntual y acotado.

Contexto inicial:

- `git status --short --branch` mostro rama `main...origin/main`.
- Habia cambios modificados en `DashboardMetrics`, `PendingTaskShow` y otros archivos.
- Habia archivos nuevos en `gatic/app/Support/Dashboard`, `gatic/app/Support/PendingTasks`, `gatic/tests/Unit/Dashboard`, `gatic/tests/Feature/Dashboard`, `gatic/tests/Unit/PendingTasks` y `gatic/tests/Feature/PendingTasks`.

Docker:

- `docker version` no pudo conectar al daemon.
- Error: `open //./pipe/dockerDesktopLinuxEngine: The system cannot find the file specified`.
- Diagnostico: Docker Desktop / engine Linux no estaba disponible; no se ejecutaron suites DB-backed con Sail.

Verificacion local con PHP 8.4:

Primer intento:

- Comando: `php artisan test` con builders/resolvers puros y `CriticalQueueAssetRowMapperTest`.
- Resultado: 34 tests pasaron, 1 fallo.
- Diagnostico del fallo: `CriticalQueueAssetRowMapperTest` intento insertar en MySQL (`locations`) y el host `mysql` no resolvio fuera de Sail. Es fallo ambiental, no evidencia de regresion en codigo.

Rerun verde:

```bash
php artisan test \
  tests/Unit/Dashboard/AlertComparativePeriodResolverTest.php \
  tests/Unit/Dashboard/AlertsSnapshotBuilderTest.php \
  tests/Unit/Dashboard/AlertWindowConfigResolverTest.php \
  tests/Unit/Dashboard/CriticalQueuePreviewBuilderTest.php \
  tests/Unit/Dashboard/DeltaBadgeBuilderTest.php \
  tests/Unit/Dashboard/InventoryValuePayloadBuilderTest.php \
  tests/Unit/Dashboard/MovementTrendPayloadBuilderTest.php \
  tests/Unit/PendingTasks/PendingTaskLinePayloadNormalizerTest.php \
  tests/Unit/PendingTasks/PendingTaskLineEditorStateFactoryTest.php \
  tests/Unit/PendingTasks/PendingTaskLineValidationErrorMapperTest.php \
  tests/Unit/PendingTasks/SerializedBulkPasteAnalyzerTest.php
```

Resultado:

- 33 tests passed
- 98 assertions
- Duration: 3.06s

Suites existentes pero no ejecutadas por falta de Docker/MySQL:

- `gatic/tests/Unit/Dashboard/PendingTaskCountsReaderTest.php`
- `gatic/tests/Unit/Dashboard/ComparativeAlertCountsReaderTest.php`
- `gatic/tests/Feature/Dashboard/MovementTrendRowsReaderTest.php`
- `gatic/tests/Feature/Dashboard/InventoryValueReaderTest.php`
- `gatic/tests/Feature/Dashboard/DashboardMetricsMovementTrendSmokeTest.php`
- `gatic/tests/Feature/Dashboard/DashboardMetricsCriticalQueueSmokeTest.php`
- tests feature de `PendingTaskShow`/locks/draft/process/finalize que usan `RefreshDatabase`

## Que sigue pendiente

- Encender Docker Desktop y correr la verificacion focalizada DB-backed.
- Validar los readers dashboard que consultan MySQL.
- Validar los smokes Livewire de dashboard.
- Validar los tests focalizados de `PendingTaskShow` sobre locks, guardado de borrador, estado de editor y guardado de proceso.
- Solo despues de ese bloque verde conviene abrir el siguiente refactor interno.

## Que no conviene tocar todavia

- `UndoMovementByToken`
- Blade de dashboard o pending tasks
- `resources/js/pages/dashboard.js`
- modularizacion cosmetica de rutas/carpetas
- rediseno completo de dashboard o pending tasks
- nuevas abstracciones compartidas antes de cerrar la verificacion DB-backed

## Recomendacion concreta del siguiente frente

Siguiente frente recomendado: cerrar verificacion Fase 60 con Sail y despues continuar con el remanente read-heavy de `DashboardMetrics`.

Orden sugerido:

1. Levantar Docker/Sail desde `gatic/`.
2. Correr tests DB-backed focalizados de dashboard y pending tasks.
3. Si pasan, continuar con una extraccion conservadora de una sola responsabilidad restante de `DashboardMetrics`, preferentemente una lectura/query de dashboard que ya tenga test o smoke cercano.
4. Si falla algo, aplicar fix minimo sin abrir refactor nuevo.

Comandos sugeridos para la proxima sesion con Docker activo:

```bash
docker compose -f compose.yaml up -d
docker compose exec -T laravel.test php artisan test \
  tests/Unit/Dashboard \
  tests/Feature/Dashboard \
  tests/Unit/PendingTasks \
  tests/Feature/PendingTasks/PendingTaskLockLivewireTest.php \
  tests/Feature/PendingTasks/PendingTaskDraftLineSaveLivewireTest.php \
  tests/Feature/PendingTasks/PendingTaskLineEditorStateLivewireTest.php \
  tests/Feature/PendingTasks/PendingTaskProcessLineSaveLivewireTest.php
```
